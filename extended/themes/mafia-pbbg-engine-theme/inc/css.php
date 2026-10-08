<?php
/**
 * Dynamic CSS generated from the Customizer settings.
 *
 * The colours are also written to the WordPress preset variables
 * (--wp--preset--color--accent, ...) so blocks, the editor and the
 * Mafia PBBG Engine game use the same palette.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Google Fonts stylesheet URL for the chosen fonts (empty when disabled / not needed).
 */
function mpet_google_fonts_url(): string {
	if ( ! mpet_opt( 'google_fonts' ) ) {
		return '';
	}
	$fonts    = mpet_fonts();
	$families = array();
	foreach ( array( mpet_opt( 'body_font' ), mpet_opt( 'heading_font' ) ) as $key ) {
		if ( ! empty( $fonts[ $key ][2] ) ) {
			$families[ $fonts[ $key ][2] ] = 'family=' . $fonts[ $key ][2];
		}
	}
	return $families ? 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap' : '';
}

function mpet_font_stack( string $key ): string {
	$fonts = mpet_fonts();
	if ( ! isset( $fonts[ $key ] ) || ( $fonts[ $key ][2] && ! mpet_opt( 'google_fonts' ) ) ) {
		return $fonts['system'][1];
	}
	return $fonts[ $key ][1];
}

/**
 * Breakpoints used for tablet and mobile values.
 */
const MPET_TABLET = 1024;
const MPET_MOBILE = 767;

/**
 * CSS custom properties for the current settings, with tablet and mobile overrides.
 */
/**
 * Colour variables for one colour set, also written to the WordPress presets.
 */
function mpet_color_vars( array $c, bool $light_set = false ): array {
	$row = static function ( string $key, string $fallback ) use ( $light_set ) {
		return ( $light_set ? '' : (string) mpet_opt( $key ) ) ?: $fallback;
	};
	return array(
		'color-scheme'                     => mpet_is_light_color( $c['base'] ) ? 'light' : 'dark',
		'--mpet-base'                       => $c['base'],
		'--mpet-surface'                    => $c['surface'],
		'--mpet-surface-2'                  => $c['surface_2'],
		'--mpet-border'                     => $c['border'],
		'--mpet-text'                       => $c['text'],
		'--mpet-muted'                      => $c['muted'],
		'--mpet-heading'                    => $c['heading'],
		'--mpet-accent'                     => $c['accent'],
		'--mpet-link-hover'                 => $c['link_hover'],
		'--mpet-button-bg'                  => $c['button_bg'],
		'--mpet-button-text'                => $c['button_text'],
		'--mpet-header-bg'                  => $c['header_bg'],
		'--mpet-header-text'                => $c['header_text'],
		'--mpet-footer-bg'                  => $c['footer_bg'],
		'--mpet-footer-text'                => $c['footer_text'],
		'--mpet-hrow-above-bg'              => $row( 'hrow_above_bg', $c['surface_2'] ),
		'--mpet-hrow-primary-bg'            => $row( 'hrow_primary_bg', 'transparent' ),
		'--mpet-hrow-below-bg'              => $row( 'hrow_below_bg', 'transparent' ),
		'--mpet-frow-above-bg'              => $row( 'frow_above_bg', 'transparent' ),
		'--mpet-frow-primary-bg'            => $row( 'frow_primary_bg', 'transparent' ),
		'--mpet-frow-below-bg'              => $row( 'frow_below_bg', 'transparent' ),
		// WordPress presets, used by blocks and by the Mafia PBBG Engine game.
		'--wp--preset--color--base'        => $c['base'],
		'--wp--preset--color--surface'     => $c['surface'],
		'--wp--preset--color--surface-2'   => $c['surface_2'],
		'--wp--preset--color--border'      => $c['border'],
		'--wp--preset--color--contrast'    => $c['text'],
		'--wp--preset--color--muted'       => $c['muted'],
		'--wp--preset--color--heading'     => $c['heading'],
		'--wp--preset--color--accent'      => $c['accent'],
		'--wp--preset--color--button'      => $c['button_bg'],
		'--wp--preset--color--button-text' => $c['button_text'],
	);
}

function mpet_is_light_color( string $hex ): bool {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return false;
	}
	list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
	return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) > 150;
}

/**
 * CSS custom properties for the current settings, with tablet and mobile overrides
 * and the light / dark mode colours.
 */
function mpet_dynamic_css( string $scope = ':root' ): string {
	$mode    = ':root' === $scope ? mpet_color_mode() : 'single';
	$default = mpet_colors( 'toggle_light' === $mode ? 'light' : 'main' );
	$vars    = array_merge(
		mpet_color_vars( $default, 'toggle_light' === $mode ),
		array(
			'--mpet-font-body'         => mpet_font_stack( (string) mpet_opt( 'body_font' ) ),
			'--mpet-font-heading'      => mpet_font_stack( (string) mpet_opt( 'heading_font' ) ),
			'--mpet-line-height'       => ( absint( mpet_opt( 'body_line_height' ) ) / 10 ),
			'--mpet-heading-weight'    => (string) mpet_opt( 'heading_weight' ),
			'--mpet-heading-transform' => (string) mpet_opt( 'heading_transform' ),
			'--mpet-container'         => absint( mpet_opt( 'container_width' ) ) . 'px',
			'--mpet-narrow'            => absint( mpet_opt( 'narrow_width' ) ) . 'px',
			'--mpet-radius'            => absint( mpet_opt( 'button_radius' ) ) . 'px',
			'--mpet-sidebar'           => absint( mpet_opt( 'sidebar_width' ) ) . '%',
		)
	);

	// Values per device: css variable => option.
	$responsive = array(
		'--mpet-body-size'      => 'body_size',
		'--mpet-h1'             => 'h1_size',
		'--mpet-h2'             => 'h2_size',
		'--mpet-h3'             => 'h3_size',
		'--mpet-gutter'         => 'container_padding',
		'--mpet-logo-width'     => 'logo_width',
		'--mpet-hrow-above-h'   => 'hrow_above_height',
		'--mpet-hrow-primary-h' => 'hrow_primary_height',
		'--mpet-hrow-below-h'   => 'hrow_below_height',
		'--mpet-frow-padding'   => 'footer_row_padding',
	);
	$tablet = array();
	$mobile = array();
	foreach ( $responsive as $var => $key ) {
		$values         = mpet_opt_r( $key );
		$vars[ $var ]   = $values[0] . 'px';
		$tablet[ $var ] = $values[1] . 'px';
		$mobile[ $var ] = $values[2] . 'px';
	}

	$selector = ':root' === $scope ? ':root,body' : $scope;
	$block    = static function ( array $list ) use ( $selector ) {
		$css = '';
		foreach ( $list as $name => $value ) {
			$css .= $name . ':' . $value . ';';
		}
		return $selector . '{' . $css . '}';
	};
	$out  = $block( $vars );
	$out .= '@media (max-width:' . MPET_TABLET . 'px){' . $block( $tablet ) . '}';
	$out .= '@media (max-width:' . MPET_MOBILE . 'px){' . $block( $mobile ) . '}';

	// The other colour mode, switched with data-mpet-mode on <html> (see mpet_color_mode_script()).
	if ( 'single' !== $mode ) {
		$alt      = 'toggle_light' === $mode ? 'dark' : 'light';
		$alt_vars = mpet_color_vars( mpet_colors( 'light' === $alt ? 'light' : 'main' ), 'light' === $alt );
		$list     = '';
		foreach ( $alt_vars as $name => $value ) {
			$list .= $name . ':' . $value . ';';
		}
		$out .= ':root[data-mpet-mode="' . $alt . '"],:root[data-mpet-mode="' . $alt . '"] body{' . $list . '}';
		if ( 'auto' === $mode ) {
			// Without JavaScript: follow the device.
			$out .= '@media (prefers-color-scheme: light){:root:not([data-mpet-mode]),:root:not([data-mpet-mode]) body{' . $list . '}}';
		}
	}

	if ( ':root' === $scope ) {
		// Desktop or mobile header. A media query can't use a CSS variable.
		$bp   = max( 320, absint( mpet_opt( 'mobile_breakpoint' ) ) );
		$out .= '@media (min-width:' . ( $bp + 1 ) . 'px){.mpet-header__mobile,.mpet-popup{display:none!important}}';
		$out .= '@media (max-width:' . $bp . 'px){.mpet-header__desktop{display:none}}';
	}
	return $out;
}

/**
 * Sets data-mpet-mode on <html> before the page is painted: the visitor's choice,
 * the device preference (auto) or the default mode. No flash of the wrong colours.
 */
function mpet_color_mode_script(): string {
	$mode = mpet_color_mode();
	if ( 'single' === $mode ) {
		return '';
	}
	$default = 'toggle_light' === $mode ? 'light' : 'dark';
	$auto    = 'auto' === $mode ? 'true' : 'false';
	return "(function(d){var m=null;try{m=localStorage.getItem('mpet-mode')}catch(e){}if(m!=='light'&&m!=='dark'){m=" . $auto . "&&window.matchMedia&&matchMedia('(prefers-color-scheme: light)').matches?'light':'" . $default . "'}d.documentElement.setAttribute('data-mpet-mode',m)})(document);";
}

add_action(
	'wp_head',
	static function () {
		$script = mpet_color_mode_script();
		if ( $script ) {
			wp_print_inline_script_tag( $script, array( 'id' => 'mpet-color-mode' ) );
		}
	},
	0
);

/**
 * Print the dynamic CSS in its own element so the Customizer can replace it live.
 */
add_action(
	'wp_head',
	static function () {
		echo '<style id="mpet-dynamic-css">' . mpet_dynamic_css() . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	20
);

/**
 * Feed the Customizer colours into the theme.json palette (block editor colour picker).
 */
add_filter(
	'wp_theme_json_data_theme',
	static function ( $theme_json ) {
		if ( ! method_exists( $theme_json, 'update_with' ) || ! did_action( 'init' ) ) {
			return $theme_json;
		}
		$c = mpet_colors();
		$p = array(
			'base'        => array( __( 'Background', 'mafia-pbbg-engine-theme' ), $c['base'] ),
			'surface'     => array( __( 'Surface', 'mafia-pbbg-engine-theme' ), $c['surface'] ),
			'surface-2'   => array( __( 'Surface 2', 'mafia-pbbg-engine-theme' ), $c['surface_2'] ),
			'border'      => array( __( 'Border', 'mafia-pbbg-engine-theme' ), $c['border'] ),
			'contrast'    => array( __( 'Text', 'mafia-pbbg-engine-theme' ), $c['text'] ),
			'muted'       => array( __( 'Muted text', 'mafia-pbbg-engine-theme' ), $c['muted'] ),
			'heading'     => array( __( 'Headings', 'mafia-pbbg-engine-theme' ), $c['heading'] ),
			'accent'      => array( __( 'Accent', 'mafia-pbbg-engine-theme' ), $c['accent'] ),
			'button'      => array( __( 'Button', 'mafia-pbbg-engine-theme' ), $c['button_bg'] ),
			'button-text' => array( __( 'Button text', 'mafia-pbbg-engine-theme' ), $c['button_text'] ),
		);
		$palette = array();
		foreach ( $p as $slug => $row ) {
			$palette[] = array(
				'slug'  => $slug,
				'name'  => $row[0],
				'color' => $row[1],
			);
		}
		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => array(
					'color'  => array( 'palette' => $palette ),
					'layout' => array(
						'contentSize' => absint( mpet_opt( 'narrow_width' ) ) . 'px',
						'wideSize'    => absint( mpet_opt( 'container_width' ) ) . 'px',
					),
				),
			)
		);
	}
);
