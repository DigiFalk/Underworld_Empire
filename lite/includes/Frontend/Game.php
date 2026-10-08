<?php
/**
 * Front end: the [underworld_empire] shortcode, routing and action handling.
 *
 * Pages:   <game page>?mg=<module id>&...
 * Actions: POST to admin-post.php with action=dfmg, module=<id>, do=<action>, nonce.
 *          The module method action_<do>( Character $c, array $input ) is called,
 *          after which the player is redirected back (Post/Redirect/Get).
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Flash;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Plugin;
use DigiFalk\UnderworldEmpire\Property;
use DigiFalk\UnderworldEmpire\Settings;

defined( 'ABSPATH' ) || exit;

final class Game {

	const DEFAULT_ROUTE = 'overview';

	public static function init(): void {
		add_shortcode( 'underworld_empire', array( __CLASS__, 'shortcode' ) );
		// Shortcode names of earlier versions.
		add_shortcode( 'mafia_game', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'maffia_game', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_post_dfmg', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_post_nopriv_dfmg', array( __CLASS__, 'handle_guest_action' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets(): void {
		wp_register_style( 'dfmg-game', DFMG_URL . 'assets/css/game.css', array(), DFMG_VERSION );
		$contrast = Contrast::css();
		if ( '' !== $contrast ) {
			wp_add_inline_style( 'dfmg-game', $contrast );
		}
		wp_register_script( 'dfmg-game', DFMG_URL . 'assets/js/game.js', array(), DFMG_VERSION, true );
		if ( self::is_game_page() || self::uses_hud() ) {
			wp_enqueue_style( 'dfmg-game' );
			wp_enqueue_script( 'dfmg-game' );
			add_action( 'wp_head', array( __CLASS__, 'print_mode_script' ), 2 );
		}
	}

	/**
	 * Light or dark game: the player's choice (light/dark switch, kept in the browser) or the
	 * Appearance setting. Set on <html> before the page is painted, so nothing flickers.
	 */
	public static function print_mode_script(): void {
		$default = 'dark' === Settings::get( 'appearance', 'theme' ) ? 'dark' : 'light';
		echo wp_get_inline_script_tag( "(function(d){var m=null;try{m=localStorage.getItem('dfmg-mode')}catch(e){}if(m!=='light'&&m!=='dark'){m='" . $default . "'}d.documentElement.setAttribute('data-dfmg-mode',m)})(document);", array( 'id' => 'dfmg-mode' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The light/dark switch (game element "Light/dark switch"). Light follows the colours of
	 * the theme, dark is the built-in dark look.
	 */
	public static function mode_toggle(): string {
		$sun  = '<svg class="dfmg-mode-toggle__sun" width="19" height="19" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5" fill="currentColor"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>';
		$moon = '<svg class="dfmg-mode-toggle__moon" width="19" height="19" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.7 14.6A8.5 8.5 0 0 1 9.4 3.3a8.5 8.5 0 1 0 11.3 11.3z"/></svg>';
		return '<button type="button" class="dfmg-hud-icon dfmg-mode-toggle" aria-pressed="false" aria-label="' . esc_attr__( 'Switch between light and dark', 'mafia-pbbg-engine' ) . '" title="' . esc_attr__( 'Light / dark', 'mafia-pbbg-engine' ) . '">' . $moon . $sun . '</button>';
	}

	/**
	 * Is the current request the game page (or a page with the game shortcode)?
	 */
	public static function is_game_page(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$id = get_queried_object_id();
		return ( $id && (int) get_option( 'dfmg_page_id' ) === $id ) || self::has_game_shortcode( (string) get_post_field( 'post_content', $id ) );
	}

	/**
	 * Game elements outside the game (theme header/footer, widgets, shortcode) need the
	 * styles in the head. Anything missed here is still enqueued late by Hud::render().
	 */
	private static function uses_hud(): bool {
		if ( is_customize_preview() || is_active_widget( false, false, 'dfmg_hud' ) ) {
			return true;
		}
		foreach ( array( 'uet_header_builder', 'uet_footer_builder' ) as $mod ) {
			if ( false !== strpos( (string) get_theme_mod( $mod, '' ), '"game-' ) ) {
				return true;
			}
		}
		return is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'ue_hud' );
	}

	private static function has_game_shortcode( string $content ): bool {
		foreach ( array( 'underworld_empire', 'mafia_game', 'maffia_game' ) as $tag ) {
			if ( has_shortcode( $content, $tag ) ) {
				return true;
			}
		}
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* URLs and forms                                                       */
	/* ------------------------------------------------------------------ */

	public static function page_url(): string {
		$page_id = (int) get_option( 'dfmg_page_id' );
		$url     = $page_id ? get_permalink( $page_id ) : '';
		return $url ?: home_url( '/' );
	}

	public static function url( string $route = '', array $args = array() ): string {
		// WordPress reserves query vars like "name", "s", "p", "page" and "paged"; using them
		// in a game link makes WordPress look for other content (usually a 404).
		if ( $args && defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $GLOBALS['wp'] ) ) {
			$reserved = array_intersect( array_keys( $args ), (array) $GLOBALS['wp']->public_query_vars );
			if ( $reserved ) {
				/* translators: %s: query vars */
				_doing_it_wrong( __METHOD__, esc_html( sprintf( __( 'Game links can\'t use the WordPress query vars: %s. Pick another name (for example "pg" instead of "paged").', 'mafia-pbbg-engine' ), implode( ', ', $reserved ) ) ), '1.10.1' );
			}
		}
		if ( $route && self::DEFAULT_ROUTE !== $route ) {
			$args = array_merge( array( 'mg' => $route ), $args );
		}
		return $args ? add_query_arg( array_map( 'rawurlencode', $args ), self::page_url() ) : self::page_url();
	}

	public static function nonce_action( string $module, string $action ): string {
		return 'dfmg_' . $module . '_' . $action;
	}

	public static function form_open( string $module, string $action, array $hidden = array(), string $class = 'dfmg-form', bool $upload = false ): string {
		$html  = '<form method="post" class="' . esc_attr( $class ) . '" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $upload ? ' enctype="multipart/form-data"' : '' ) . '>';
		$html .= '<input type="hidden" name="action" value="dfmg">';
		$html .= '<input type="hidden" name="module" value="' . esc_attr( $module ) . '">';
		$html .= '<input type="hidden" name="do" value="' . esc_attr( $action ) . '">';
		$html .= wp_nonce_field( self::nonce_action( $module, $action ), '_dfmg_nonce', false, false );
		foreach ( $hidden as $name => $value ) {
			$html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
		}
		return $html;
	}

	/**
	 * Render a core template (overridable in <theme>/underworld-empire/<name>.php).
	 */
	public static function template( string $name, array $vars = array() ): string {
		$file = locate_template( 'underworld-empire/' . $name . '.php' );
		if ( ! $file ) {
			$file = DFMG_DIR . 'templates/' . $name . '.php';
		}
		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $file;
		return (string) ob_get_clean();
	}

	/* ------------------------------------------------------------------ */
	/* Routing                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Apply restrictions (jail, hospital, ...) to the requested module.
	 */
	public static function resolve( Character $c, Module $module ): Module {
		$route    = (string) apply_filters( 'dfmg_route', $module->id(), $c, $module );
		$resolved = Plugin::instance()->modules->get( $route );
		return $resolved ?: $module;
	}

	private static function query(): array {
		$query = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( wp_unslash( $_GET ) as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$query[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
			}
		}
		return $query;
	}

	public static function shortcode(): string {
		wp_enqueue_style( 'dfmg-game' );
		wp_enqueue_script( 'dfmg-game' );

		if ( ! is_user_logged_in() ) {
			return self::wrap( self::template( 'login' ) );
		}

		$c = Character::current();
		if ( ! $c ) {
			return self::wrap( self::template( 'create-character', array( 'messages' => Flash::pull() ) ) );
		}
		if ( ! $c->is_alive() ) {
			return self::wrap(
				self::template(
					'dead',
					array(
						'character' => $c,
						'killer'    => Character::find( (int) $c->shot_by ),
						'messages'  => Flash::pull(),
					)
				)
			);
		}
		if ( ! Plugin::round_open() && ! current_user_can( 'dfmg_manage' ) ) {
			return self::wrap( self::template( 'closed' ) );
		}

		$c->touch();
		$c->check_rank();
		$registry = Plugin::instance()->modules;
		$query    = self::query();
		$route    = $query['mg'] ?? self::DEFAULT_ROUTE;
		$module   = $registry->get( $route ) ?: $registry->get( self::DEFAULT_ROUTE );

		if ( ! $module ) {
			return self::wrap( '<p>' . esc_html__( 'No modules are active.', 'mafia-pbbg-engine' ) . '</p>' );
		}
		if ( $module->id() !== $route ) {
			Flash::error( __( 'This page doesn\'t exist.', 'mafia-pbbg-engine' ) );
		}

		$module    = self::resolve( $c, $module );
		Hud::$route = $module->id();
		$content   = $module->render( $c, $query );
		$c->refresh();

		return self::wrap(
			self::template(
				'layout',
				array(
					'character' => $c,
					'module'    => $module,
					'menu'      => self::menu( $c ),
					'messages'  => Flash::pull(),
					'content'   => $content,
				)
			)
		);
	}

	private static function wrap( string $html ): string {
		$class = 'dark' === Settings::get( 'appearance', 'theme' ) ? 'dfmg dfmg--dark' : 'dfmg';
		// The credit sits in the game footer; pages without it (login, dead, closed, or a theme's
		// own layout template) get it underneath.
		if ( false === strpos( $html, 'dfmg-footer__credit' ) ) {
			$html .= Credit::html();
		}
		// Follow the light/dark choice set on <html> (see print_mode_script()) before anything is painted.
		$sync = wp_get_inline_script_tag( "(function(e){var m=document.documentElement.getAttribute('data-dfmg-mode');if(e&&m){e.classList.toggle('dfmg--dark',m==='dark')}})(document.currentScript&&document.currentScript.parentNode);" );
		return '<div class="' . esc_attr( $class ) . '" data-now="' . esc_attr( (string) time() ) . '">' . $sync . $html . '</div>';
	}

	/**
	 * Menu grouped by section.
	 */
	public static function menu( Character $c ): array {
		$groups = apply_filters(
			'dfmg_menu_groups',
			array(
				'general'   => __( 'General', 'mafia-pbbg-engine' ),
				'crime'     => __( 'Crime', 'mafia-pbbg-engine' ),
				'city'      => __( 'City', 'mafia-pbbg-engine' ),
				'casino'    => __( 'Casino', 'mafia-pbbg-engine' ),
				'murder'    => __( 'Murder', 'mafia-pbbg-engine' ),
				'family'    => __( 'Family', 'mafia-pbbg-engine' ),
				'money'     => __( 'Assets', 'mafia-pbbg-engine' ),
				'premium'   => __( 'Premium', 'mafia-pbbg-engine' ),
				'community' => __( 'Community', 'mafia-pbbg-engine' ),
			)
		);

		$items = array();
		foreach ( Plugin::instance()->modules->active() as $module ) {
			foreach ( $module->menu( $c ) as $item ) {
				$item['route'] = $item['route'] ?? $module->id();
				$items[]       = $item;
			}
		}
		$items = apply_filters( 'dfmg_menu_items', $items, $c );

		$out = array();
		foreach ( $groups as $key => $label ) {
			$out[ $key ] = array(
				'label' => $label,
				'items' => array(),
			);
		}
		foreach ( $items as $item ) {
			$group = $item['group'] ?? 'general';
			if ( ! isset( $out[ $group ] ) ) {
				$out[ $group ] = array(
					'label' => ucfirst( $group ),
					'items' => array(),
				);
			}
			$item['url']              = self::url( $item['route'], $item['args'] ?? array() );
			$item['icon']             = $item['icon'] ?? ( \DigiFalk\UnderworldEmpire\Icons::has( $item['route'] ) ? $item['route'] : 'dot' );
			$out[ $group ]['items'][] = $item;
		}
		foreach ( $out as $key => $group ) {
			if ( ! $group['items'] ) {
				unset( $out[ $key ] );
				continue;
			}
			usort(
				$out[ $key ]['items'],
				static function ( $a, $b ) {
					return ( $a['order'] ?? 50 ) <=> ( $b['order'] ?? 50 );
				}
			);
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Actions                                                              */
	/* ------------------------------------------------------------------ */

	public static function handle_guest_action(): void {
		wp_safe_redirect( wp_login_url( self::page_url() ) );
		exit;
	}

	public static function handle_action(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below.
		$module_id = sanitize_key( wp_unslash( $_POST['module'] ?? '' ) );
		$action    = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		$input     = wp_unslash( $_POST );
		// phpcs:enable

		if ( ! wp_verify_nonce( sanitize_text_field( $input['_dfmg_nonce'] ?? '' ), self::nonce_action( $module_id, $action ) ) ) {
			Flash::error( __( 'Your session has expired, please try again.', 'mafia-pbbg-engine' ) );
			self::redirect( 'core' === $module_id ? '' : $module_id );
		}

		if ( 'core' === $module_id && 'create_character' === $action ) {
			self::core_action( $action, $input );
		}

		$c = Character::current();
		if ( ! $c || ! $c->is_alive() ) {
			self::redirect( '' );
		}
		if ( ! Plugin::round_open() && ! current_user_can( 'dfmg_manage' ) ) {
			self::redirect( '' );
		}

		if ( 'core' === $module_id && 'buy_property' === $action ) {
			self::buy_property( $c, $input );
		}

		$registry = Plugin::instance()->modules;
		$module   = $registry->get( $module_id );
		$method   = 'action_' . str_replace( '-', '_', $action );
		if ( ! $module || ! is_callable( array( $module, $method ) ) ) {
			Flash::error( __( 'Unknown action.', 'mafia-pbbg-engine' ) );
			self::redirect( '' );
		}

		$resolved = self::resolve( $c, $module );
		if ( $resolved->id() !== $module->id() ) {
			Flash::error( __( 'You can\'t do that right now.', 'mafia-pbbg-engine' ) );
			self::redirect( $resolved->id() );
		}

		$args = $module->{$method}( $c, $input );
		do_action( 'dfmg_after_action', $c, $module_id, $action );

		$args  = is_array( $args ) ? $args : array();
		$route = $args['mg'] ?? $module_id;
		unset( $args['mg'] );
		self::redirect( $route, $args );
	}

	/**
	 * Actions that do not require an alive character.
	 */
	private static function core_action( string $action, array $input ): void {
		if ( 'create_character' === $action ) {
			$result = Character::create( get_current_user_id(), sanitize_text_field( $input['name'] ?? '' ) );
			if ( is_wp_error( $result ) ) {
				Flash::error( $result->get_error_message() );
			} else {
				/* translators: %s: character name */
				Flash::success( sprintf( __( 'Welcome to the underworld, %s.', 'mafia-pbbg-engine' ), $result->name ) );
			}
		}
		self::redirect( '' );
	}

	/**
	 * Buy an unowned property in the current city.
	 */
	private static function buy_property( Character $c, array $input ): void {
		$type   = sanitize_key( $input['type'] ?? '' );
		$config = Property::type( $type );
		$route  = sanitize_key( $input['return'] ?? '' );
		if ( ! $config ) {
			Flash::error( __( 'This property doesn\'t exist.', 'mafia-pbbg-engine' ) );
			self::redirect( $route );
		}
		$property = Property::get( $type, (int) $c->location_id );
		if ( $property->is_owned() ) {
			Flash::error( __( 'This property already has an owner.', 'mafia-pbbg-engine' ) );
			self::redirect( $route );
		}
		$price = $property->buy_price();
		if ( ! $c->spend( 'money', $price ) ) {
			/* translators: %s: money */
			Flash::error( sprintf( __( 'You need %s in cash.', 'mafia-pbbg-engine' ), Format::money( $price ) ) );
			self::redirect( $route );
		}
		$property->transfer( $c->id() );
		$c->log( 'property.buy', true, $price, $property->location_id() );
		/* translators: 1: property, 2: city */
		Flash::success( sprintf( __( 'Congratulations, the %1$s in %2$s is now yours.', 'mafia-pbbg-engine' ), $property->label(), $c->location_name() ) );
		self::redirect( $route );
	}

	/**
	 * @return never
	 */
	public static function redirect( string $route, array $args = array() ): void {
		Flash::persist();
		wp_safe_redirect( self::url( $route, $args ) );
		exit;
	}
}
