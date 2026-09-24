<?php
/**
 * Customizer panels, sections and controls, generated from uet_options().
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'uet_customize_register' );
function uet_customize_register( WP_Customize_Manager $wp_customize ): void {
	$panels = array(
		'uet_global' => array( __( 'Global', 'underworld-empire-theme' ), 20 ),
		'uet_header' => array( __( 'Header', 'underworld-empire-theme' ), 21 ),
		'uet_footer' => array( __( 'Footer', 'underworld-empire-theme' ), 22 ),
		'uet_blog'   => array( __( 'Blog', 'underworld-empire-theme' ), 23 ),
	);
	foreach ( $panels as $id => $panel ) {
		$wp_customize->add_panel(
			$id,
			array(
				'title'    => $panel[0],
				'priority' => $panel[1],
			)
		);
	}

	$sections = array(
		'uet_colors'     => array( 'uet_global', __( 'Colours', 'underworld-empire-theme' ) ),
		'uet_typography' => array( 'uet_global', __( 'Typography', 'underworld-empire-theme' ) ),
		'uet_layout'     => array( 'uet_global', __( 'Container & buttons', 'underworld-empire-theme' ) ),
		'uet_sidebar'    => array( 'uet_global', __( 'Sidebar', 'underworld-empire-theme' ) ),
		'uet_misc'       => array( 'uet_global', __( 'Breadcrumbs, titles & scroll to top', 'underworld-empire-theme' ) ),
		'uet_header'     => array( 'uet_header', __( 'Header layout', 'underworld-empire-theme' ) ),
		'uet_topbar'     => array( 'uet_header', __( 'Top bar', 'underworld-empire-theme' ) ),
		'uet_mobile'     => array( 'uet_header', __( 'Mobile header', 'underworld-empire-theme' ) ),
		'uet_footer'     => array( 'uet_footer', __( 'Footer', 'underworld-empire-theme' ) ),
		'uet_blog'       => array( 'uet_blog', __( 'Blog & archives', 'underworld-empire-theme' ) ),
		'uet_single'     => array( 'uet_blog', __( 'Single post', 'underworld-empire-theme' ) ),
	);
	foreach ( $sections as $id => $section ) {
		$wp_customize->add_section(
			$id,
			array(
				'title' => $section[1],
				'panel' => $section[0],
			)
		);
	}

	$priority = 10;
	foreach ( uet_options() as $key => $opt ) {
		$setting = 'uet_' . $key;
		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => $opt['default'],
				'transport'         => 'refresh',
				'sanitize_callback' => static function ( $value ) use ( $opt ) {
					return uet_sanitize( $value, $opt );
				},
			)
		);
		$args = array(
			'label'       => $opt['label'],
			'description' => $opt['description'],
			'section'     => $opt['section'],
			'priority'    => $priority++,
		);
		if ( 'color' === $opt['type'] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting, $args ) );
			continue;
		}
		$args['type'] = $opt['type'];
		if ( $opt['choices'] ) {
			$args['choices'] = $opt['choices'];
		}
		if ( $opt['input_attrs'] ) {
			$args['input_attrs'] = $opt['input_attrs'];
		}
		$wp_customize->add_control( $setting, $args );
	}

	// Live update of the site title and tagline.
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.uet-site-title a',
				'render_callback' => static function () {
					return get_bloginfo( 'name', 'display' );
				},
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'blogdescription',
			array(
				'selector'        => '.uet-site-tagline',
				'render_callback' => static function () {
					return get_bloginfo( 'description', 'display' );
				},
			)
		);
	}
}

/**
 * @param mixed $value
 * @return mixed
 */
function uet_sanitize( $value, array $opt ) {
	switch ( $opt['type'] ) {
		case 'color':
			return '' === $value ? '' : (string) sanitize_hex_color( $value );
		case 'checkbox':
			return $value ? 1 : 0;
		case 'number':
			$value = (int) $value;
			if ( isset( $opt['input_attrs']['min'] ) ) {
				$value = max( (int) $opt['input_attrs']['min'], $value );
			}
			if ( isset( $opt['input_attrs']['max'] ) ) {
				$value = min( (int) $opt['input_attrs']['max'], $value );
			}
			return $value;
		case 'select':
		case 'radio':
			return array_key_exists( (string) $value, $opt['choices'] ) ? (string) $value : $opt['default'];
		case 'url':
			return esc_url_raw( (string) $value );
		case 'textarea':
			return wp_kses_post( (string) $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Palette presets: picking a palette fills in the colour controls, editing a colour switches to "custom".
 */
add_action( 'customize_controls_enqueue_scripts', 'uet_customize_controls' );
function uet_customize_controls(): void {
	wp_enqueue_script( 'uet-customize-controls', UET_URI . 'assets/js/customize-controls.js', array( 'customize-controls' ), UET_VERSION, true );
	$palettes = array();
	foreach ( uet_palettes() as $key => $palette ) {
		$palettes[ $key ] = $palette['colors'];
	}
	wp_localize_script( 'uet-customize-controls', 'uetPalettes', $palettes );
}

add_action( 'customize_preview_init', 'uet_customize_preview' );
function uet_customize_preview(): void {
	wp_enqueue_script( 'uet-customize-preview', UET_URI . 'assets/js/customize-preview.js', array( 'customize-preview' ), UET_VERSION, true );
}
