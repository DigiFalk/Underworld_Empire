<?php
/**
 * Dynamic CSS generated from the Customizer settings.
 *
 * The colours are also written to the WordPress preset variables
 * (--wp--preset--color--accent, ...) so blocks, the editor and the
 * Underworld Empire game use the same palette.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Google Fonts stylesheet URL for the chosen fonts (empty when disabled / not needed).
 */
function uet_google_fonts_url(): string {
	if ( ! uet_opt( 'google_fonts' ) ) {
		return '';
	}
	$fonts    = uet_fonts();
	$families = array();
	foreach ( array( uet_opt( 'body_font' ), uet_opt( 'heading_font' ) ) as $key ) {
		if ( ! empty( $fonts[ $key ][2] ) ) {
			$families[ $fonts[ $key ][2] ] = 'family=' . $fonts[ $key ][2];
		}
	}
	return $families ? 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap' : '';
}

function uet_font_stack( string $key ): string {
	$fonts = uet_fonts();
	if ( ! isset( $fonts[ $key ] ) || ( $fonts[ $key ][2] && ! uet_opt( 'google_fonts' ) ) ) {
		return $fonts['system'][1];
	}
	return $fonts[ $key ][1];
}

/**
 * Breakpoints used for tablet and mobile values.
 */
const UET_TABLET = 1024;
const UET_MOBILE = 767;

/**
 * CSS custom properties for the current settings, with tablet and mobile overrides.
 */
function uet_dynamic_css( string $scope = ':root' ): string {
	$c    = uet_colors();
	$vars = array(
		'--uet-base'              => $c['base'],
		'--uet-surface'           => $c['surface'],
		'--uet-surface-2'         => $c['surface_2'],
		'--uet-border'            => $c['border'],
		'--uet-text'              => $c['text'],
		'--uet-muted'             => $c['muted'],
		'--uet-heading'           => $c['heading'],
		'--uet-accent'            => $c['accent'],
		'--uet-link-hover'        => $c['link_hover'],
		'--uet-button-bg'         => $c['button_bg'],
		'--uet-button-text'       => $c['button_text'],
		'--uet-header-bg'         => $c['header_bg'],
		'--uet-header-text'       => $c['header_text'],
		'--uet-footer-bg'         => $c['footer_bg'],
		'--uet-footer-text'       => $c['footer_text'],
		'--uet-hrow-above-bg'     => (string) uet_opt( 'hrow_above_bg' ) ?: $c['surface_2'],
		'--uet-hrow-primary-bg'   => (string) uet_opt( 'hrow_primary_bg' ) ?: 'transparent',
		'--uet-hrow-below-bg'     => (string) uet_opt( 'hrow_below_bg' ) ?: 'transparent',
		'--uet-frow-above-bg'     => (string) uet_opt( 'frow_above_bg' ) ?: 'transparent',
		'--uet-frow-primary-bg'   => (string) uet_opt( 'frow_primary_bg' ) ?: 'transparent',
		'--uet-frow-below-bg'     => (string) uet_opt( 'frow_below_bg' ) ?: 'transparent',
		'--uet-font-body'         => uet_font_stack( (string) uet_opt( 'body_font' ) ),
		'--uet-font-heading'      => uet_font_stack( (string) uet_opt( 'heading_font' ) ),
		'--uet-line-height'       => ( absint( uet_opt( 'body_line_height' ) ) / 10 ),
		'--uet-heading-weight'    => (string) uet_opt( 'heading_weight' ),
		'--uet-heading-transform' => (string) uet_opt( 'heading_transform' ),
		'--uet-container'         => absint( uet_opt( 'container_width' ) ) . 'px',
		'--uet-narrow'            => absint( uet_opt( 'narrow_width' ) ) . 'px',
		'--uet-radius'            => absint( uet_opt( 'button_radius' ) ) . 'px',
		'--uet-sidebar'           => absint( uet_opt( 'sidebar_width' ) ) . '%',
		// WordPress presets, used by blocks and by the Underworld Empire game.
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

	// Values per device: css variable => option.
	$responsive = array(
		'--uet-body-size'      => 'body_size',
		'--uet-h1'             => 'h1_size',
		'--uet-h2'             => 'h2_size',
		'--uet-h3'             => 'h3_size',
		'--uet-gutter'         => 'container_padding',
		'--uet-logo-width'     => 'logo_width',
		'--uet-hrow-above-h'   => 'hrow_above_height',
		'--uet-hrow-primary-h' => 'hrow_primary_height',
		'--uet-hrow-below-h'   => 'hrow_below_height',
		'--uet-frow-padding'   => 'footer_row_padding',
	);
	$tablet = array();
	$mobile = array();
	foreach ( $responsive as $var => $key ) {
		$values         = uet_opt_r( $key );
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
	$out .= '@media (max-width:' . UET_TABLET . 'px){' . $block( $tablet ) . '}';
	$out .= '@media (max-width:' . UET_MOBILE . 'px){' . $block( $mobile ) . '}';

	if ( ':root' === $scope ) {
		// Desktop or mobile header. A media query can't use a CSS variable.
		$bp   = max( 320, absint( uet_opt( 'mobile_breakpoint' ) ) );
		$out .= '@media (min-width:' . ( $bp + 1 ) . 'px){.uet-header__mobile,.uet-popup{display:none!important}}';
		$out .= '@media (max-width:' . $bp . 'px){.uet-header__desktop{display:none}}';
	}
	return $out;
}

/**
 * Print the dynamic CSS in its own element so the Customizer can replace it live.
 */
add_action(
	'wp_head',
	static function () {
		echo '<style id="uet-dynamic-css">' . uet_dynamic_css() . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		$c = uet_colors();
		$p = array(
			'base'        => array( __( 'Background', 'underworld-empire-theme' ), $c['base'] ),
			'surface'     => array( __( 'Surface', 'underworld-empire-theme' ), $c['surface'] ),
			'surface-2'   => array( __( 'Surface 2', 'underworld-empire-theme' ), $c['surface_2'] ),
			'border'      => array( __( 'Border', 'underworld-empire-theme' ), $c['border'] ),
			'contrast'    => array( __( 'Text', 'underworld-empire-theme' ), $c['text'] ),
			'muted'       => array( __( 'Muted text', 'underworld-empire-theme' ), $c['muted'] ),
			'heading'     => array( __( 'Headings', 'underworld-empire-theme' ), $c['heading'] ),
			'accent'      => array( __( 'Accent', 'underworld-empire-theme' ), $c['accent'] ),
			'button'      => array( __( 'Button', 'underworld-empire-theme' ), $c['button_bg'] ),
			'button-text' => array( __( 'Button text', 'underworld-empire-theme' ), $c['button_text'] ),
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
						'contentSize' => absint( uet_opt( 'narrow_width' ) ) . 'px',
						'wideSize'    => absint( uet_opt( 'container_width' ) ) . 'px',
					),
				),
			)
		);
	}
);
