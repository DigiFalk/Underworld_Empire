<?php
/**
 * Header & footer builder: element registry, stored layouts and rendering.
 *
 * Header layout (theme mod uet_header_builder, JSON):
 *   { "desktop": { "above": {"left":[],"center":[],"right":[]}, "primary": {...}, "below": {...} },
 *     "mobile":  { "above": {...}, "primary": {...}, "below": {...}, "popup": [] } }
 *
 * Footer layout (theme mod uet_footer_builder, JSON):
 *   { "above": {"cols":0,"c1":[],"c2":[],"c3":[],"c4":[]}, "primary": {...}, "below": {...} }
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

const UET_ROWS  = array( 'above', 'primary', 'below' );
const UET_ZONES = array( 'left', 'center', 'right' );
const UET_COLS  = array( 'c1', 'c2', 'c3', 'c4' );

function uet_header_elements(): array {
	return apply_filters(
		'uet_header_elements',
		array(
			'logo'           => __( 'Logo & site title', 'underworld-empire-theme' ),
			'menu-primary'   => __( 'Primary menu', 'underworld-empire-theme' ),
			'menu-secondary' => __( 'Secondary menu', 'underworld-empire-theme' ),
			'search'         => __( 'Search', 'underworld-empire-theme' ),
			'button'         => __( 'Button', 'underworld-empire-theme' ),
			'html'           => __( 'HTML / text', 'underworld-empire-theme' ),
			'social'         => __( 'Social icons', 'underworld-empire-theme' ),
			'account'        => __( 'Player account', 'underworld-empire-theme' ),
			'mode'           => __( 'Light/dark switch', 'underworld-empire-theme' ),
			'toggle'         => __( 'Menu toggle', 'underworld-empire-theme' ),
		)
	);
}

function uet_footer_elements(): array {
	return apply_filters(
		'uet_footer_elements',
		array(
			'copyright'   => __( 'Copyright', 'underworld-empire-theme' ),
			'menu-footer' => __( 'Footer menu', 'underworld-empire-theme' ),
			'social'      => __( 'Social icons', 'underworld-empire-theme' ),
			'html'        => __( 'HTML / text', 'underworld-empire-theme' ),
			'logo'        => __( 'Logo & site title', 'underworld-empire-theme' ),
			'mode'        => __( 'Light/dark switch', 'underworld-empire-theme' ),
			'widget-1'    => __( 'Widgets: Footer 1', 'underworld-empire-theme' ),
			'widget-2'    => __( 'Widgets: Footer 2', 'underworld-empire-theme' ),
			'widget-3'    => __( 'Widgets: Footer 3', 'underworld-empire-theme' ),
			'widget-4'    => __( 'Widgets: Footer 4', 'underworld-empire-theme' ),
		)
	);
}

/* -------------------------------------------------------------------------- */
/* Defaults (derived from the options of theme version 2.0)                    */
/* -------------------------------------------------------------------------- */

function uet_empty_header_row(): array {
	return array(
		'left'   => array(),
		'center' => array(),
		'right'  => array(),
	);
}

function uet_default_header_builder(): array {
	$desktop = array(
		'above'   => uet_empty_header_row(),
		'primary' => array(
			'left'   => array( 'logo' ),
			'center' => array(),
			'right'  => array( 'menu-primary' ),
		),
		'below'   => uet_empty_header_row(),
	);
	// Carry over the header options of theme 2.0.
	$layout = get_theme_mod( 'uet_header_layout', 'logo-left' );
	if ( 'logo-right' === $layout ) {
		$desktop['primary'] = array(
			'left'   => array( 'menu-primary' ),
			'center' => array(),
			'right'  => array( 'logo' ),
		);
	} elseif ( 'centered' === $layout ) {
		$desktop['primary'] = array(
			'left'   => array(),
			'center' => array( 'logo' ),
			'right'  => array(),
		);
		$desktop['below']   = array(
			'left'   => array(),
			'center' => array( 'menu-primary' ),
			'right'  => array(),
		);
	}
	$actions = 'logo-right' === $layout ? 'left' : 'right';
	// The light/dark switch only shows when it is enabled (Global → Light & dark mode).
	$desktop['primary'][ $actions ][] = 'mode';
	if ( get_theme_mod( 'uet_header_search' ) ) {
		$desktop['primary'][ $actions ][] = 'search';
	}
	if ( get_theme_mod( 'uet_header_button_text' ) ) {
		$desktop['primary'][ $actions ][] = 'button';
	}
	if ( get_theme_mod( 'uet_top_bar' ) ) {
		$desktop['above'] = array(
			'left'   => array( 'html' ),
			'center' => array(),
			'right'  => array( 'menu-secondary' ),
		);
	}
	return array(
		'desktop' => $desktop,
		'mobile'  => array(
			'above'   => uet_empty_header_row(),
			'primary' => array(
				'left'   => array( 'logo' ),
				'center' => array(),
				'right'  => array( 'mode', 'toggle' ),
			),
			'below'   => uet_empty_header_row(),
			'popup'   => array( 'menu-primary', 'search', 'button' ),
		),
	);
}

function uet_default_footer_builder(): array {
	$empty   = array(
		'cols' => 0,
		'c1'   => array(),
		'c2'   => array(),
		'c3'   => array(),
		'c4'   => array(),
	);
	$above   = $empty;
	$widgets = (int) get_theme_mod( 'uet_footer_widgets', 0 );
	if ( $widgets ) {
		$above['cols'] = $widgets;
		for ( $i = 1; $i <= $widgets; $i++ ) {
			$above[ 'c' . $i ] = array( 'widget-' . $i );
		}
	}
	$below = $empty;
	if ( 'split' === get_theme_mod( 'uet_footer_layout', 'center' ) ) {
		$below['cols'] = 2;
		$below['c1']   = array( 'copyright' );
		$below['c2']   = array( 'menu-footer' );
	} else {
		$below['cols'] = 1;
		$below['c1']   = array( 'copyright', 'menu-footer' );
	}
	return array(
		'above'   => $above,
		'primary' => $empty,
		'below'   => $below,
	);
}

/* -------------------------------------------------------------------------- */
/* Sanitizing & reading                                                        */
/* -------------------------------------------------------------------------- */

/**
 * @param mixed $list
 */
function uet_clean_items( $list, array $allowed, array &$used ): array {
	$out = array();
	foreach ( (array) $list as $item ) {
		$item = sanitize_key( (string) $item );
		if ( isset( $allowed[ $item ] ) && ! in_array( $item, $used, true ) ) {
			$out[]  = $item;
			$used[] = $item;
		}
	}
	return $out;
}

/**
 * @param mixed $value JSON string or array.
 */
function uet_sanitize_header_builder( $value ): string {
	$data     = is_array( $value ) ? $value : json_decode( (string) $value, true );
	$data     = is_array( $data ) ? $data : array();
	$allowed  = uet_header_elements();
	$defaults = uet_default_header_builder();
	$out      = array();
	foreach ( array( 'desktop', 'mobile' ) as $device ) {
		$used = array();
		foreach ( UET_ROWS as $row ) {
			foreach ( UET_ZONES as $zone ) {
				$src                            = $data[ $device ][ $row ][ $zone ] ?? ( $data ? array() : $defaults[ $device ][ $row ][ $zone ] );
				$out[ $device ][ $row ][ $zone ] = uet_clean_items( $src, $allowed, $used );
			}
		}
	}
	$used                   = array();
	$out['mobile']['popup'] = uet_clean_items( $data['mobile']['popup'] ?? ( $data ? array() : $defaults['mobile']['popup'] ), $allowed, $used );
	return (string) wp_json_encode( $out );
}

/**
 * @param mixed $value JSON string or array.
 */
function uet_sanitize_footer_builder( $value ): string {
	$data    = is_array( $value ) ? $value : json_decode( (string) $value, true );
	$data    = is_array( $data ) ? $data : uet_default_footer_builder();
	$allowed = uet_footer_elements();
	$used    = array();
	$out     = array();
	foreach ( UET_ROWS as $row ) {
		$out[ $row ]['cols'] = max( 0, min( 4, (int) ( $data[ $row ]['cols'] ?? 0 ) ) );
		foreach ( UET_COLS as $col ) {
			$out[ $row ][ $col ] = uet_clean_items( $data[ $row ][ $col ] ?? array(), $allowed, $used );
		}
	}
	return (string) wp_json_encode( $out );
}

function uet_header_builder(): array {
	$raw = get_theme_mod( 'uet_header_builder', '' );
	return json_decode( uet_sanitize_header_builder( $raw ? $raw : uet_default_header_builder() ), true );
}

function uet_footer_builder(): array {
	$raw = get_theme_mod( 'uet_footer_builder', '' );
	return json_decode( uet_sanitize_footer_builder( $raw ? $raw : uet_default_footer_builder() ), true );
}

/* -------------------------------------------------------------------------- */
/* Rendering                                                                   */
/* -------------------------------------------------------------------------- */

function uet_menu( string $location, string $class, string $context ): string {
	if ( ! has_nav_menu( $location ) ) {
		if ( 'primary' === $location && current_user_can( 'edit_theme_options' ) ) {
			return '<nav class="uet-nav ' . esc_attr( $class ) . '"><ul class="uet-menu"><li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Add a menu', 'underworld-empire-theme' ) . '</a></li></ul></nav>';
		}
		return '';
	}
	// The same menu may be printed twice (desktop and mobile): avoid duplicate ids.
	$strip = 'desktop' !== $context;
	if ( $strip ) {
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
	}
	$html = (string) wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => 'nav',
			'container_class' => 'uet-nav ' . $class,
			'container_aria_label' => 'primary' === $location ? __( 'Primary menu', 'underworld-empire-theme' ) : __( 'Secondary menu', 'underworld-empire-theme' ),
			'menu_class'     => 'uet-menu',
			'menu_id'        => '',
			'depth'          => 3,
			'echo'           => false,
			'fallback_cb'    => false,
		)
	);
	if ( $strip ) {
		remove_filter( 'nav_menu_item_id', '__return_empty_string' );
		$html = preg_replace( '/ id="menu-[^"]*"/', '', $html );
	}
	return $html;
}

function uet_social_links(): string {
	$networks = array(
		'facebook'  => array( 'Facebook', 'M14 8h3V4h-3c-2.8 0-5 2.2-5 5v2H7v4h2v9h4v-9h3l1-4h-4V9c0-.6.4-1 1-1z' ),
		'instagram' => array( 'Instagram', 'M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4zM17.3 5.5a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4zM12 2c-2.7 0-3 0-4.1.1C4.2 2.3 2.3 4.2 2.1 7.9 2 9 2 9.3 2 12s0 3 .1 4.1c.2 3.7 2.1 5.6 5.8 5.8 1.1.1 1.4.1 4.1.1s3 0 4.1-.1c3.7-.2 5.6-2.1 5.8-5.8.1-1.1.1-1.4.1-4.1s0-3-.1-4.1c-.2-3.7-2.1-5.6-5.8-5.8C15 2 14.7 2 12 2zm0 1.8c2.7 0 3 0 4 .1 2.7.1 4 1.4 4.1 4.1.1 1 .1 1.3.1 4s0 3-.1 4c-.1 2.7-1.4 4-4.1 4.1-1 .1-1.3.1-4 .1s-3 0-4-.1c-2.7-.1-4-1.4-4.1-4.1-.1-1-.1-1.3-.1-4s0-3 .1-4C3.9 5.3 5.3 4 8 3.9c1-.1 1.3-.1 4-.1z' ),
		'x'         => array( 'X', 'M17.8 3h3.3l-7.2 8.2L22.4 21h-6.6l-5.2-6.8L4.6 21H1.3l7.7-8.8L.9 3h6.8l4.7 6.2L17.8 3zm-1.2 16.2h1.8L6.6 4.7H4.6l12 14.5z' ),
		'youtube'   => array( 'YouTube', 'M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1c.4-1.6.5-3.2.5-4.8s-.1-3.2-.5-4.8zM9.8 15V9l5.8 3-5.8 3z' ),
		'tiktok'    => array( 'TikTok', 'M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.3v12.4a2.6 2.6 0 1 1-2.6-2.6c.3 0 .5 0 .8.1V9.5a5.9 5.9 0 1 0 5.1 5.9V9.1a7.5 7.5 0 0 0 4.4 1.4V7.2a4.3 4.3 0 0 1-3.3-1.4z' ),
		'discord'   => array( 'Discord', 'M20.3 4.4A19.8 19.8 0 0 0 15.4 3l-.6 1.3a18.4 18.4 0 0 0-5.5 0L8.6 3a19.7 19.7 0 0 0-4.9 1.5C.6 9.1-.3 13.7.1 18.2a19.9 19.9 0 0 0 6 3l1.3-2a12.9 12.9 0 0 1-2-1l.5-.4a14.2 14.2 0 0 0 12.2 0l.5.4c-.6.4-1.3.7-2 1l1.3 2a19.8 19.8 0 0 0 6-3c.5-5.2-.8-9.7-3.6-13.8zM8 15.4c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4zm8 0c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4z' ),
		'twitch'    => array( 'Twitch', 'M4.3 2 3 5.4V19h4.6v2.6h2.6l2.6-2.6h3.7l5-5V2H4.3zm15.7 11-2.9 2.9h-4.6L10 18.3v-2.4H6.1V3.7H20V13zm-2.9-5.9h-1.7v5h1.7v-5zm-4.6 0h-1.7v5h1.7v-5z' ),
	);
	$items = '';
	foreach ( $networks as $key => $network ) {
		$url = (string) uet_opt( 'social_' . $key );
		if ( $url ) {
			$items .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener me" aria-label="' . esc_attr( $network[0] ) . '"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="' . esc_attr( $network[1] ) . '"/></svg></a></li>';
		}
	}
	return $items ? '<ul class="uet-social">' . $items . '</ul>' : '';
}

function uet_account_link(): string {
	if ( function_exists( 'dfmg_character' ) && function_exists( 'dfmg_url' ) ) {
		$character = is_user_logged_in() ? dfmg_character() : null;
		if ( $character && $character->is_alive() ) {
			$avatar = function_exists( 'dfmg_player_avatar_url' ) ? dfmg_player_avatar_url( (int) $character->user_id, 28 ) : ( function_exists( 'dfmg_avatar_url' ) ? dfmg_avatar_url( (int) $character->user_id ) : '' );
			$avatar = $avatar
				? '<img class="uet-account__avatar" src="' . esc_url( $avatar ) . '" alt="" width="28" height="28">'
				: '<span class="uet-account__avatar" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $character->name, 0, 1 ) ) ) . '</span>';
			return '<a class="uet-account" href="' . esc_url( dfmg_url( 'profile' ) ) . '">' . $avatar . '<span class="uet-account__name">' . esc_html( $character->name ) . '</span></a>';
		}
		$label = is_user_logged_in() ? __( 'Play', 'underworld-empire-theme' ) : __( 'Log in', 'underworld-empire-theme' );
		return '<a class="uet-account" href="' . esc_url( dfmg_url() ) . '"><span class="uet-account__avatar" aria-hidden="true">&#9679;</span><span class="uet-account__name">' . esc_html( $label ) . '</span></a>';
	}
	if ( is_user_logged_in() ) {
		return '<a class="uet-account" href="' . esc_url( get_edit_profile_url() ) . '">' . esc_html( wp_get_current_user()->display_name ) . '</a>';
	}
	return '<a class="uet-account" href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Log in', 'underworld-empire-theme' ) . '</a>';
}

/**
 * Light/dark switch (only when the visitor may switch, see Global → Light & dark mode).
 */
function uet_mode_toggle( bool $with_label = false ): string {
	if ( 'single' === uet_color_mode() ) {
		return '';
	}
	$sun  = '<svg class="uet-mode-toggle__sun" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5" fill="currentColor"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>';
	$moon = '<svg class="uet-mode-toggle__moon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.7 14.6A8.5 8.5 0 0 1 9.4 3.3a8.5 8.5 0 1 0 11.3 11.3z"/></svg>';
	$html = '<button type="button" class="uet-mode-toggle' . ( $with_label ? ' uet-mode-toggle--label' : '' ) . '" aria-pressed="false" data-label-light="' . esc_attr__( 'Switch to dark mode', 'underworld-empire-theme' ) . '" data-label-dark="' . esc_attr__( 'Switch to light mode', 'underworld-empire-theme' ) . '" aria-label="' . esc_attr__( 'Switch between light and dark mode', 'underworld-empire-theme' ) . '">' . $sun . $moon;
	if ( $with_label ) {
		$html .= '<span class="uet-mode-toggle__text uet-mode-toggle__text--dark">' . esc_html__( 'Light mode', 'underworld-empire-theme' ) . '</span><span class="uet-mode-toggle__text uet-mode-toggle__text--light">' . esc_html__( 'Dark mode', 'underworld-empire-theme' ) . '</span>';
	}
	return $html . '</button>';
}

/**
 * The switch can also be placed in the game (Customize → Game layout, widget, shortcode).
 */
add_filter(
	'dfmg_hud_elements',
	static function ( $elements ) {
		if ( 'single' !== uet_color_mode() ) {
			$elements['mode-toggle'] = array(
				'label'  => __( 'Light/dark switch', 'underworld-empire-theme' ),
				'theme'  => false,
				'render' => static function ( $character, $context ) {
					return uet_mode_toggle( 'stack' === $context );
				},
			);
		}
		return $elements;
	}
);

/**
 * One header element. $context: desktop, mobile or popup.
 */
function uet_header_element( string $element, string $context ): string {
	switch ( $element ) {
		case 'logo':
			ob_start();
			uet_site_branding();
			return (string) ob_get_clean();
		case 'menu-primary':
			return uet_menu( 'primary', 'uet-nav--primary uet-nav--' . $context, $context );
		case 'menu-secondary':
			return uet_menu( 'top', 'uet-nav--secondary uet-nav--' . $context, $context );
		case 'search':
			if ( 'popup' === $context || 'field' === uet_opt( 'hb_search_style' ) ) {
				return '<div class="uet-search-inline">' . get_search_form( array( 'echo' => false ) ) . '</div>';
			}
			return '<details class="uet-header-search"><summary aria-label="' . esc_attr__( 'Search', 'underworld-empire-theme' ) . '"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.32 12.9l5.39 5.4-1.41 1.4-5.4-5.39A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg></summary><div class="uet-header-search__panel">' . get_search_form( array( 'echo' => false ) ) . '</div></details>';
		case 'button':
			$text = (string) uet_opt( 'hb_button_text' );
			if ( '' === $text ) {
				return '';
			}
			$url = (string) uet_opt( 'hb_button_url' );
			if ( ! $url && function_exists( 'dfmg_url' ) ) {
				$url = dfmg_url();
			}
			$style = 'outline' === uet_opt( 'hb_button_style' ) ? ' uet-button--outline' : '';
			return '<a class="uet-button uet-header-button' . $style . '" href="' . esc_url( $url ?: home_url( '/' ) ) . '">' . esc_html( $text ) . '</a>';
		case 'html':
			$html = (string) uet_opt( 'hb_html' );
			return $html ? '<div class="uet-html">' . do_shortcode( wp_kses_post( $html ) ) . '</div>' : '';
		case 'social':
			return uet_social_links();
		case 'account':
			return uet_account_link();
		case 'mode':
			return uet_mode_toggle( 'popup' === $context );
		case 'toggle':
			return '<button class="uet-menu-toggle" type="button" aria-controls="uet-mobile-popup" aria-expanded="false"><span class="uet-menu-toggle__icon" aria-hidden="true"></span><span class="uet-menu-toggle__label">' . esc_html( (string) uet_opt( 'mobile_menu_label' ) ) . '</span></button>';
	}
	return (string) apply_filters( 'uet_render_header_element', '', $element, $context );
}

function uet_header_rows( array $layout, string $context ): string {
	$html = '';
	foreach ( UET_ROWS as $row ) {
		$zones = '';
		$has   = false;
		foreach ( UET_ZONES as $zone ) {
			$items = '';
			foreach ( $layout[ $row ][ $zone ] as $element ) {
				$items .= uet_header_element( $element, $context );
			}
			$has   = $has || '' !== $items;
			$zones .= '<div class="uet-zone uet-zone--' . $zone . '">' . $items . '</div>';
		}
		if ( ! $has ) {
			continue;
		}
		$center = $layout[ $row ]['center'] ? ' has-center' : '';
		$html  .= '<div class="uet-hrow uet-hrow--' . $row . '"><div class="uet-hrow__inner ' . ( 'full' === uet_opt( 'header_width' ) ? 'uet-container-fluid' : 'uet-container' ) . $center . '">' . $zones . '</div></div>';
	}
	return $html;
}

function uet_render_header(): void {
	$layout = uet_header_builder();
	$popup  = '';
	foreach ( $layout['mobile']['popup'] as $element ) {
		$popup .= '<div class="uet-popup__item uet-popup__item--' . esc_attr( $element ) . '">' . uet_header_element( $element, 'popup' ) . '</div>';
	}
	$style = 'offcanvas' === uet_opt( 'mobile_popup' ) ? 'offcanvas' : 'dropdown';
	echo '<header class="uet-header" id="masthead">';
	echo '<div class="uet-header__desktop">' . uet_header_rows( $layout['desktop'], 'desktop' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="uet-header__mobile">' . uet_header_rows( $layout['mobile'], 'mobile' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	if ( $popup ) {
		echo '<div class="uet-popup uet-popup--' . esc_attr( $style ) . '" id="uet-mobile-popup" hidden>';
		if ( 'offcanvas' === $style ) {
			echo '<div class="uet-popup__backdrop" data-uet-close></div>';
		}
		echo '<div class="uet-popup__panel" role="dialog" aria-label="' . esc_attr__( 'Menu', 'underworld-empire-theme' ) . '">';
		if ( 'offcanvas' === $style ) {
			echo '<button type="button" class="uet-popup__close" data-uet-close aria-label="' . esc_attr__( 'Close menu', 'underworld-empire-theme' ) . '">&times;</button>';
		}
		echo $popup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div></div>';
	}
	echo '</header>';
}

function uet_footer_element( string $element ): string {
	switch ( $element ) {
		case 'mode':
			return uet_mode_toggle();
		case 'copyright':
			return '<div class="uet-copyright">' . uet_copyright() . '</div>';
		case 'menu-footer':
			if ( ! has_nav_menu( 'footer' ) ) {
				return '';
			}
			return (string) wp_nav_menu(
				array(
					'theme_location'  => 'footer',
					'container'       => 'nav',
					'container_class' => 'uet-footer__menu',
					'container_aria_label' => __( 'Footer menu', 'underworld-empire-theme' ),
					'menu_class'      => 'uet-inline-menu',
					'depth'           => 1,
					'echo'            => false,
				)
			);
		case 'social':
			return uet_social_links();
		case 'html':
			$html = (string) uet_opt( 'fb_html' );
			return $html ? '<div class="uet-html">' . do_shortcode( wp_kses_post( $html ) ) . '</div>' : '';
		case 'logo':
			ob_start();
			uet_site_branding();
			return (string) ob_get_clean();
	}
	if ( preg_match( '/^widget-([1-4])$/', $element, $m ) ) {
		if ( ! is_active_sidebar( 'footer-' . $m[1] ) ) {
			return '';
		}
		ob_start();
		dynamic_sidebar( 'footer-' . $m[1] );
		return '<div class="uet-fwidgets">' . ob_get_clean() . '</div>';
	}
	return (string) apply_filters( 'uet_render_footer_element', '', $element );
}

function uet_render_footer(): void {
	$layout = uet_footer_builder();
	echo '<footer class="uet-footer" id="colophon">';
	foreach ( UET_ROWS as $row ) {
		$cols = (int) $layout[ $row ]['cols'];
		if ( ! $cols ) {
			continue;
		}
		$html = '';
		$has  = false;
		for ( $i = 1; $i <= $cols; $i++ ) {
			$items = '';
			foreach ( $layout[ $row ][ 'c' . $i ] as $element ) {
				$items .= uet_footer_element( $element );
			}
			$has   = $has || '' !== $items;
			$html .= '<div class="uet-fcol">' . $items . '</div>';
		}
		if ( ! $has ) {
			continue;
		}
		$align = sanitize_html_class( (string) uet_opt( 'frow_' . $row . '_align' ) );
		echo '<div class="uet-frow uet-frow--' . esc_attr( $row ) . ' uet-frow--align-' . esc_attr( $align ) . '"><div class="uet-container uet-frow__inner uet-cols-' . (int) $cols . '">' . $html . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</footer>';
}
