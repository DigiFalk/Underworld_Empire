<?php
/**
 * Game elements ("HUD"): small live pieces of the game that can be placed anywhere –
 * in the game layout, in the theme header/footer, in widget areas or with a shortcode.
 *
 * Contexts:
 *  - bar   : horizontal (headers, footers, above/below content),
 *  - stack : vertical (game sidebar, widgets),
 *  - inline: inside content (shortcode).
 *
 * Add your own elements with the dfmg_hud_elements filter:
 *   $elements['my-el'] = [ 'label' => 'My element', 'render' => fn( ?Character $c, string $context ) => '<span>…</span>' ];
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Plugin;
use DigiFalk\UnderworldEmpire\Settings;

defined( 'ABSPATH' ) || exit;

final class Hud {

	/** @var array|null */
	private static $elements = null;

	/** @var string|null Route of the game page being rendered (set by Game). */
	public static $route = null;

	public static function init(): void {
		add_shortcode( 'ue_hud', array( __CLASS__, 'shortcode' ) );
		// The plugin boots on init priority 1, after WordPress fired widgets_init.
		if ( did_action( 'widgets_init' ) ) {
			self::register_widget();
			global $wp_widget_factory;
			if ( isset( $wp_widget_factory->widgets[ HudWidget::class ] ) ) {
				$wp_widget_factory->widgets[ HudWidget::class ]->_register();
			}
		} else {
			add_action( 'widgets_init', array( __CLASS__, 'register_widget' ) );
		}

		// Theme builder integration (Underworld Empire theme and compatible themes).
		add_filter( 'uet_header_elements', array( __CLASS__, 'theme_elements' ) );
		add_filter( 'uet_footer_elements', array( __CLASS__, 'theme_elements' ) );
		add_filter(
			'uet_render_header_element',
			static function ( $html, $element, $context ) {
				return 0 === strpos( (string) $element, 'game-' ) ? self::render( substr( $element, 5 ), 'popup' === $context ? 'stack' : 'bar', 'theme' ) : $html;
			},
			10,
			3
		);
		add_filter(
			'uet_render_footer_element',
			static function ( $html, $element ) {
				return 0 === strpos( (string) $element, 'game-' ) ? self::render( substr( $element, 5 ), 'bar', 'theme' ) : $html;
			},
			10,
			2
		);
	}

	public static function register_widget(): void {
		register_widget( HudWidget::class );
	}

	/**
	 * Element registry: key => [ label, render ].
	 */
	public static function elements(): array {
		if ( null !== self::$elements ) {
			return self::$elements;
		}
		$stat = static function ( string $label, callable $value, string $icon = '' ) {
			return static function ( ?Character $c, string $context ) use ( $label, $value, $icon ) {
				if ( ! $c ) {
					return '';
				}
				return '<div class="dfmg-hud-stat">' . ( $icon ? '<span class="dfmg-hud-stat__icon" aria-hidden="true">' . $icon . '</span>' : '' )
					. '<span class="dfmg-hud-stat__label">' . esc_html( $label ) . '</span><span class="dfmg-hud-stat__value">' . $value( $c ) . '</span></div>';
			};
		};

		$elements = array(
			'player'        => array(
				'label'  => __( 'Player (name & avatar)', 'underworld-empire' ),
				'render' => array( __CLASS__, 'render_player' ),
			),
			'rank'          => array(
				'label'  => __( 'Rank', 'underworld-empire' ),
				'render' => $stat( __( 'Rank', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( $c->rank_name() );
				}, '★' ),
			),
			'rank-progress' => array(
				'label'  => __( 'Rank progress bar', 'underworld-empire' ),
				'render' => static function ( ?Character $c ) {
					if ( ! $c ) {
						return '';
					}
					/* translators: %s: percent */
					return '<div class="dfmg-hud-progress" title="' . esc_attr( sprintf( __( '%s%% to next rank', 'underworld-empire' ), $c->rank_progress() ) ) . '"><span style="width:' . esc_attr( (string) $c->rank_progress() ) . '%"></span></div>';
				},
			),
			'cash'          => array(
				'label'  => __( 'Cash', 'underworld-empire' ),
				'render' => $stat( __( 'Cash', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( Format::money( $c->money ) );
				}, '$' ),
			),
			'bank'          => array(
				'label'  => __( 'Bank', 'underworld-empire' ),
				'render' => $stat( __( 'Bank', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( Format::money( $c->bank ) );
				}, '⌂' ),
			),
			'bullets'       => array(
				'label'  => __( 'Bullets', 'underworld-empire' ),
				'render' => $stat( __( 'Bullets', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( Format::number( $c->bullets ) );
				}, '•' ),
			),
			'health'        => array(
				'label'  => __( 'Health', 'underworld-empire' ),
				'render' => $stat( __( 'Health', 'underworld-empire' ), static function ( Character $c ) {
					$pct = $c->health_percent();
					return '<span class="dfmg-hud-health" style="--dfmg-hp:' . esc_attr( (string) $pct ) . '%">' . esc_html( $pct . '%' ) . '</span>';
				}, '♥' ),
			),
			'points'        => array(
				'label'  => __( 'Premium points', 'underworld-empire' ),
				'render' => $stat( (string) Settings::get( 'points_name', __( 'Points', 'underworld-empire' ) ), static function ( Character $c ) {
					return esc_html( Format::number( $c->points ) );
				}, '◆' ),
			),
			'city'          => array(
				'label'  => __( 'City', 'underworld-empire' ),
				'render' => $stat( __( 'City', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( $c->location_name() );
				}, '⌖' ),
			),
			'wealth'        => array(
				'label'  => __( 'Wealth title', 'underworld-empire' ),
				'render' => $stat( __( 'Wealth', 'underworld-empire' ), static function ( Character $c ) {
					return esc_html( $c->wealth_title() );
				} ),
			),
			'notifications' => array(
				'label'  => __( 'Notifications (with counter)', 'underworld-empire' ),
				'render' => static function ( ?Character $c ) {
					if ( ! $c || ! self::active( 'notifications' ) ) {
						return '';
					}
					$n = (int) DB::value( 'SELECT COUNT(*) FROM {notifications} WHERE character_id = %d AND is_read = 0', $c->id() );
					return self::icon_link( Game::url( 'notifications' ), __( 'Notifications', 'underworld-empire' ), '<path fill="currentColor" d="M12 22a2.5 2.5 0 0 0 2.4-2h-4.8a2.5 2.5 0 0 0 2.4 2zm7-6V11a7 7 0 0 0-5.5-6.8V3a1.5 1.5 0 0 0-3 0v1.2A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z"/>', $n );
				},
			),
			'messages'      => array(
				'label'  => __( 'Messages (with counter)', 'underworld-empire' ),
				'render' => static function ( ?Character $c ) {
					if ( ! $c || ! self::active( 'messages' ) ) {
						return '';
					}
					$n = (int) DB::value( 'SELECT COUNT(*) FROM {messages} WHERE recipient_id = %d AND is_read = 0 AND recipient_deleted = 0', $c->id() );
					return self::icon_link( Game::url( 'messages' ), __( 'Messages', 'underworld-empire' ), '<path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/>', $n );
				},
			),
			'timers'        => array(
				'label'  => __( 'Active timers', 'underworld-empire' ),
				'render' => array( __CLASS__, 'render_timers' ),
			),
			'menu'          => array(
				'label'  => __( 'Game menu (all pages)', 'underworld-empire' ),
				'render' => array( __CLASS__, 'render_menu' ),
			),
			'online'        => array(
				'label'  => __( 'Players online', 'underworld-empire' ),
				'render' => static function () {
					$n = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1 AND last_active > %d', time() - 60 * Settings::int( 'online_minutes', 15 ) );
					$text = '<span class="dfmg-hud-online__dot" aria-hidden="true"></span>' . esc_html( sprintf( /* translators: %s: number */ _n( '%s player online', '%s players online', $n, 'underworld-empire' ), Format::number( $n ) ) );
					return self::active( 'players' ) ? '<a class="dfmg-hud-online" href="' . esc_url( Game::url( 'players' ) ) . '">' . $text . '</a>' : '<span class="dfmg-hud-online">' . $text . '</span>';
				},
			),
			'round'         => array(
				'label'  => __( 'Round name & end', 'underworld-empire' ),
				'render' => static function () {
					$end  = (string) Settings::get( 'round_end', '' );
					$html = '<span class="dfmg-hud-round">' . esc_html( (string) Settings::get( 'round_name' ) );
					if ( $end && strtotime( $end ) ) {
						$ts    = strtotime( get_gmt_from_date( $end ) . ' UTC' );
						$html .= ' &middot; <small>' . esc_html__( 'ends in', 'underworld-empire' ) . ' ' . Format::countdown( (int) $ts ) . '</small>';
					}
					return $html . '</span>';
				},
			),
			'play'          => array(
				'label'  => __( 'Play / log in button', 'underworld-empire' ),
				'render' => static function ( ?Character $c ) {
					if ( $c ) {
						return '<a class="dfmg-button dfmg-hud-play" href="' . esc_url( Game::url() ) . '">' . esc_html__( 'Play', 'underworld-empire' ) . '</a>';
					}
					$label = is_user_logged_in() ? __( 'Start playing', 'underworld-empire' ) : __( 'Log in to play', 'underworld-empire' );
					return '<a class="dfmg-button dfmg-hud-play" href="' . esc_url( Game::url() ) . '">' . esc_html( $label ) . '</a>';
				},
			),
			'logout'        => array(
				'label'  => __( 'Log out link', 'underworld-empire' ),
				'render' => static function () {
					return is_user_logged_in() ? '<a class="dfmg-hud-logout" href="' . esc_url( wp_logout_url( Game::page_url() ) ) . '">' . esc_html__( 'Log out', 'underworld-empire' ) . '</a>' : '';
				},
			),
		);

		// One element per menu group ("Crime", "City", ...).
		foreach ( self::menu_groups() as $key => $label ) {
			$elements[ 'menu-' . $key ] = array(
				/* translators: %s: menu group */
				'label'  => sprintf( __( 'Game menu: %s', 'underworld-empire' ), $label ),
				'render' => static function ( ?Character $c, string $context ) use ( $key ) {
					return self::render_menu( $c, $context, $key );
				},
			);
		}

		self::$elements = apply_filters( 'dfmg_hud_elements', $elements );
		return self::$elements;
	}

	private static function menu_groups(): array {
		return apply_filters(
			'dfmg_menu_groups',
			array(
				'general'   => __( 'General', 'underworld-empire' ),
				'crime'     => __( 'Crime', 'underworld-empire' ),
				'city'      => __( 'City', 'underworld-empire' ),
				'casino'    => __( 'Casino', 'underworld-empire' ),
				'murder'    => __( 'Murder', 'underworld-empire' ),
				'family'    => __( 'Family', 'underworld-empire' ),
				'money'     => __( 'Assets', 'underworld-empire' ),
				'premium'   => __( 'Premium', 'underworld-empire' ),
				'community' => __( 'Community', 'underworld-empire' ),
			)
		);
	}

	public static function labels(): array {
		return wp_list_pluck( self::elements(), 'label' );
	}

	/**
	 * Theme builder elements, prefixed with "game-".
	 */
	public static function theme_elements( array $elements ): array {
		foreach ( self::labels() as $key => $label ) {
			/* translators: %s: element */
			$elements[ 'game-' . $key ] = sprintf( __( 'Game: %s', 'underworld-empire' ), $label );
		}
		return $elements;
	}

	private static function active( string $module ): bool {
		return (bool) Plugin::instance()->modules->get( $module );
	}

	/**
	 * Alive character of the visitor (null for guests / dead players).
	 */
	public static function character(): ?Character {
		$c = Character::current();
		return ( $c && $c->is_alive() ) ? $c : null;
	}

	/**
	 * Render one element. $where: game (inside the game layout) or theme/widget/shortcode.
	 */
	public static function render( string $key, string $context = 'bar', string $where = 'game' ): string {
		$elements = self::elements();
		if ( ! isset( $elements[ $key ] ) || ! is_callable( $elements[ $key ]['render'] ) ) {
			return '';
		}
		$html = (string) call_user_func( $elements[ $key ]['render'], self::character(), $context );
		if ( '' === $html ) {
			return '';
		}
		$class = 'dfmg-hud dfmg-hud--' . sanitize_html_class( $key ) . ' dfmg-hud--' . $context;
		if ( 'game' !== $where ) {
			$class .= ' dfmg-hud--outside';
			wp_enqueue_style( 'dfmg-game' );
			wp_enqueue_script( 'dfmg-game' );
		}
		return '<div class="' . esc_attr( $class ) . '" data-dfmg-now="' . esc_attr( (string) time() ) . '">' . $html . '</div>';
	}

	private static function icon_link( string $url, string $label, string $path, int $count ): string {
		$badge = $count ? '<span class="dfmg-hud-badge">' . esc_html( $count > 99 ? '99+' : (string) $count ) . '</span>' : '';
		/* translators: 1: label, 2: count */
		$aria = $count ? sprintf( __( '%1$s (%2$d new)', 'underworld-empire' ), $label, $count ) : $label;
		return '<a class="dfmg-hud-icon" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria ) . '" title="' . esc_attr( $label ) . '"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>' . $badge . '</a>';
	}

	public static function render_player( ?Character $c ): string {
		if ( ! $c ) {
			return '';
		}
		$profile = self::active( 'profile' );
		return '<a class="dfmg-hud-player" href="' . esc_url( $profile ? Game::url( 'profile' ) : Game::url() ) . '" title="' . esc_attr( $profile ? __( 'My profile', 'underworld-empire' ) : __( 'Overview', 'underworld-empire' ) ) . '">'
			. '<span class="dfmg-hud-player__avatar" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $c->name, 0, 1 ) ) ) . '</span>'
			. '<span class="dfmg-hud-player__name">' . esc_html( $c->name ) . '</span></a>';
	}

	public static function render_timers( ?Character $c, string $context ): string {
		if ( ! $c ) {
			return '';
		}
		$items = '';
		foreach ( Game::menu( $c ) as $group ) {
			foreach ( $group['items'] as $item ) {
				if ( ! empty( $item['timer'] ) && $c->timer_active( $item['timer'] ) ) {
					$items .= '<li><a href="' . esc_url( $item['url'] ) . '"><span>' . esc_html( $item['label'] ) . '</span> ' . Format::countdown( $c->timer( $item['timer'] ) ) . '</a></li>';
				}
			}
		}
		if ( ! $items ) {
			return 'stack' === $context ? '<p class="dfmg-hud-timers__none">' . esc_html__( 'No active timers.', 'underworld-empire' ) . '</p>' : '';
		}
		return '<ul class="dfmg-hud-timers">' . $items . '</ul>';
	}

	/**
	 * Game menu. Vertical groups in a stack, a dropdown button in a bar.
	 */
	public static function render_menu( ?Character $c, string $context, string $only_group = '' ): string {
		if ( ! $c ) {
			return '';
		}
		$current = self::$route ?? sanitize_key( wp_unslash( $_GET['mg'] ?? 'overview' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$groups  = Game::menu( $c );
		if ( $only_group ) {
			$groups = isset( $groups[ $only_group ] ) ? array( $only_group => $groups[ $only_group ] ) : array();
		}
		if ( ! $groups ) {
			return '';
		}
		$html = '';
		foreach ( $groups as $group ) {
			$html .= '<div class="dfmg-nav__group">' . ( $only_group && 'bar' === $context ? '' : '<h4>' . esc_html( $group['label'] ) . '</h4>' ) . '<ul>';
			foreach ( $group['items'] as $item ) {
				$html .= '<li class="' . ( $item['route'] === $current && ( null !== self::$route || Game::is_game_page() ) ? 'is-active' : '' ) . '"><a href="' . esc_url( $item['url'] ) . '"><span>' . esc_html( $item['label'] ) . '</span>';
				if ( ! empty( $item['badge'] ) ) {
					$html .= '<em class="dfmg-badge">' . esc_html( (string) $item['badge'] ) . '</em>';
				}
				if ( ! empty( $item['timer'] ) && $c->timer_active( $item['timer'] ) ) {
					$html .= '<small class="dfmg-nav__timer">' . Format::countdown( $c->timer( $item['timer'] ) ) . '</small>';
				}
				$html .= '</a></li>';
			}
			$html .= '</ul></div>';
		}
		if ( 'bar' === $context && ! $only_group ) {
			return '<details class="dfmg-hud-menu"><summary><span class="dfmg-menu-toggle__icon" aria-hidden="true"></span>' . esc_html__( 'Game menu', 'underworld-empire' ) . '</summary><nav class="dfmg-nav dfmg-hud-menu__panel">' . $html . '</nav></details>';
		}
		return '<nav class="dfmg-nav dfmg-nav--' . esc_attr( $context ) . ( $only_group ? ' dfmg-nav--group' : '' ) . '">' . $html . '</nav>';
	}

	/**
	 * [ue_hud element="cash"] – or several: element="cash,bank,bullets".
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'element' => 'player',
				'layout'  => 'inline',
			),
			$atts,
			'ue_hud'
		);
		$context = in_array( $atts['layout'], array( 'bar', 'stack', 'inline' ), true ) ? $atts['layout'] : 'inline';
		$html    = '';
		foreach ( array_map( 'trim', explode( ',', (string) $atts['element'] ) ) as $key ) {
			$html .= self::render( sanitize_key( $key ), $context, 'shortcode' );
		}
		return $html ? '<div class="dfmg-hud-group dfmg-hud-group--' . esc_attr( $context ) . '">' . $html . '</div>' : '';
	}
}
