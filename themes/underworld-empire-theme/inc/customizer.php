<?php
/**
 * Customizer panels, sections and controls, generated from uet_options().
 *
 * Most settings update the preview without a reload:
 *  - CSS settings re-render the <style id="uet-dynamic-css"> element,
 *  - header and footer settings re-render the header / footer.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * How a setting is previewed: css, header, footer or refresh.
 */
function uet_preview_mode( string $key, array $opt ): string {
	if ( 'header_builder' === $key || 0 === strpos( $key, 'hb_' ) || in_array( $key, array( 'header_width', 'show_title', 'show_tagline', 'mobile_popup', 'mobile_menu_label' ), true ) ) {
		return 'header';
	}
	if ( 'footer_builder' === $key || 0 === strpos( $key, 'fb_' ) || preg_match( '/^frow_.*_align$/', $key ) || 'footer_copyright' === $key ) {
		return 'footer';
	}
	if ( 0 === strpos( $key, 'social_' ) ) {
		return 'both';
	}
	if ( 'color' === $opt['type'] || 'responsive' === $opt['type'] || in_array( $key, array( 'palette', 'light_palette' ), true )
		|| in_array( $key, array( 'body_line_height', 'heading_weight', 'heading_transform', 'container_width', 'narrow_width', 'button_radius', 'sidebar_width', 'mobile_breakpoint' ), true ) ) {
		return 'css';
	}
	return 'refresh';
}

add_action( 'customize_register', 'uet_customize_register' );
function uet_customize_register( WP_Customize_Manager $wp_customize ): void {
	require_once UET_DIR . 'inc/controls.php';

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
		'uet_colors'         => array( 'uet_global', __( 'Colours', 'underworld-empire-theme' ) ),
		'uet_color_modes'    => array( 'uet_global', __( 'Light & dark mode', 'underworld-empire-theme' ) ),
		'uet_typography'     => array( 'uet_global', __( 'Typography', 'underworld-empire-theme' ) ),
		'uet_layout'         => array( 'uet_global', __( 'Container & buttons', 'underworld-empire-theme' ) ),
		'uet_sidebar'        => array( 'uet_global', __( 'Sidebar', 'underworld-empire-theme' ) ),
		'uet_misc'           => array( 'uet_global', __( 'Breadcrumbs, titles & scroll to top', 'underworld-empire-theme' ) ),
		'uet_header_builder' => array( 'uet_header', __( 'Header builder', 'underworld-empire-theme' ) ),
		'uet_header_general' => array( 'uet_header', __( 'Header settings', 'underworld-empire-theme' ) ),
		'uet_header_rows'    => array( 'uet_header', __( 'Header rows', 'underworld-empire-theme' ) ),
		'uet_header_button'  => array( 'uet_header', __( 'Button', 'underworld-empire-theme' ) ),
		'uet_header_html'    => array( 'uet_header', __( 'HTML / text', 'underworld-empire-theme' ) ),
		'uet_header_search'  => array( 'uet_header', __( 'Search', 'underworld-empire-theme' ) ),
		'uet_social'         => array( 'uet_header', __( 'Social icons', 'underworld-empire-theme' ) ),
		'uet_mobile'         => array( 'uet_header', __( 'Mobile header & menu', 'underworld-empire-theme' ) ),
		'uet_footer_builder' => array( 'uet_footer', __( 'Footer builder', 'underworld-empire-theme' ) ),
		'uet_footer_rows'    => array( 'uet_footer', __( 'Footer rows', 'underworld-empire-theme' ) ),
		'uet_footer'         => array( 'uet_footer', __( 'Copyright, text & colours', 'underworld-empire-theme' ) ),
		'uet_blog'           => array( 'uet_blog', __( 'Blog & archives', 'underworld-empire-theme' ) ),
		'uet_single'         => array( 'uet_blog', __( 'Single post', 'underworld-empire-theme' ) ),
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

	$partials = array(
		'css'    => array(),
		'header' => array( 'blogname', 'blogdescription' ),
		'footer' => array( 'blogname' ),
	);
	$priority = 10;
	foreach ( uet_options() as $key => $opt ) {
		$mode      = uet_preview_mode( $key, $opt );
		$transport = 'refresh' === $mode ? 'refresh' : 'postMessage';
		$section   = 'uet_header_logo' === $opt['section'] ? 'title_tagline' : $opt['section'];
		$sanitize  = static function ( $value ) use ( $opt ) {
			return uet_sanitize( $value, $opt );
		};

		if ( 'responsive' === $opt['type'] ) {
			$ids = array();
			foreach ( array( 'default' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ) as $device => $suffix ) {
				$index = array( 'default' => 0, 'tablet' => 1, 'mobile' => 2 )[ $device ];
				$id    = 'uet_' . $key . $suffix;
				$wp_customize->add_setting(
					$id,
					array(
						'default'           => $opt['default'][ $index ],
						'transport'         => $transport,
						'sanitize_callback' => $sanitize,
					)
				);
				$ids[ $device ]      = $id;
				$partials['css'][]   = $id;
			}
			$wp_customize->add_control(
				new UET_Responsive_Control(
					$wp_customize,
					'uet_' . $key,
					array(
						'label'       => $opt['label'],
						'description' => $opt['description'],
						'section'     => $section,
						'priority'    => $priority++,
						'settings'    => $ids,
						'input_attrs' => $opt['input_attrs'],
					)
				)
			);
			continue;
		}

		$setting = 'uet_' . $key;
		$builder = in_array( $opt['type'], array( 'header_builder', 'footer_builder' ), true );
		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => $builder ? '' : $opt['default'],
				'transport'         => $transport,
				'sanitize_callback' => $builder
					? ( 'header_builder' === $opt['type'] ? 'uet_sanitize_header_builder' : 'uet_sanitize_footer_builder' )
					: $sanitize,
			)
		);
		if ( 'both' === $mode ) {
			$partials['header'][] = $setting;
			$partials['footer'][] = $setting;
		} elseif ( isset( $partials[ $mode ] ) ) {
			$partials[ $mode ][] = $setting;
		}

		$args = array(
			'label'       => $opt['label'],
			'description' => $opt['description'],
			'section'     => $section,
			'priority'    => $priority++,
		);
		if ( $builder ) {
			$args['builder'] = 'header_builder' === $opt['type'] ? 'header' : 'footer';
			$wp_customize->add_control( new UET_Builder_Control( $wp_customize, $setting, $args ) );
			continue;
		}
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

	foreach ( array( 'blogname', 'blogdescription' ) as $core ) {
		$setting = $wp_customize->get_setting( $core );
		if ( $setting ) {
			$setting->transport = 'postMessage';
		}
	}

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'uet_css',
			array(
				'selector'            => '#uet-dynamic-css',
				'settings'            => $partials['css'],
				'container_inclusive' => false,
				'fallback_refresh'    => true,
				'render_callback'     => static function () {
					return uet_dynamic_css();
				},
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'uet_header',
			array(
				'selector'            => '#masthead',
				'settings'            => $partials['header'],
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => 'uet_render_header',
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'uet_footer',
			array(
				'selector'            => '#colophon',
				'settings'            => $partials['footer'],
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => 'uet_render_footer',
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
		case 'responsive':
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

add_action( 'customize_controls_enqueue_scripts', 'uet_customize_controls' );
function uet_customize_controls(): void {
	wp_enqueue_style( 'uet-customize-controls', UET_URI . 'assets/css/customize-controls.css', array(), UET_VERSION );
	wp_enqueue_script( 'uet-customize-controls', UET_URI . 'assets/js/customize-controls.js', array( 'customize-controls', 'jquery-ui-sortable' ), UET_VERSION, true );
	$palettes = array();
	foreach ( uet_palettes() as $key => $palette ) {
		$palettes[ $key ] = $palette['colors'];
	}
	wp_localize_script(
		'uet-customize-controls',
		'uetCustomizer',
		array(
			'palettes' => $palettes,
			'header'   => array(
				'elements' => uet_header_elements(),
				'rows'     => array(
					'above'   => __( 'Top row', 'underworld-empire-theme' ),
					'primary' => __( 'Main row', 'underworld-empire-theme' ),
					'below'   => __( 'Bottom row', 'underworld-empire-theme' ),
				),
				'zones'    => array(
					'left'   => __( 'Left', 'underworld-empire-theme' ),
					'center' => __( 'Center', 'underworld-empire-theme' ),
					'right'  => __( 'Right', 'underworld-empire-theme' ),
				),
			),
			'footer'   => array(
				'elements' => uet_footer_elements(),
				'rows'     => array(
					'above'   => __( 'Top row', 'underworld-empire-theme' ),
					'primary' => __( 'Middle row', 'underworld-empire-theme' ),
					'below'   => __( 'Bottom row', 'underworld-empire-theme' ),
				),
			),
			'i18n'     => array(
				'desktop'   => __( 'Desktop', 'underworld-empire-theme' ),
				'mobile'    => __( 'Tablet & mobile', 'underworld-empire-theme' ),
				'popup'     => __( 'Mobile menu panel', 'underworld-empire-theme' ),
				'available' => __( 'Available elements (drag into a row, or use +)', 'underworld-empire-theme' ),
				'add'       => __( 'Add element', 'underworld-empire-theme' ),
				'remove'    => __( 'Remove', 'underworld-empire-theme' ),
				'columns'   => __( 'Columns', 'underworld-empire-theme' ),
				'column'    => __( 'Column', 'underworld-empire-theme' ),
				'hidden'    => __( 'Row hidden', 'underworld-empire-theme' ),
				'settings'  => __( 'Element settings', 'underworld-empire-theme' ),
			),
			'sections' => array(
				'button'         => 'uet_header_button',
				'html'           => 'uet_header_html',
				'search'         => 'uet_header_search',
				'social'         => 'uet_social',
				'toggle'         => 'uet_mobile',
				'logo'           => 'title_tagline',
				'menu-primary'   => 'menu_locations',
				'menu-secondary' => 'menu_locations',
				'copyright'      => 'uet_footer',
				'menu-footer'    => 'menu_locations',
				'footer-html'    => 'uet_footer',
				'widget-1'       => 'sidebar-widgets-footer-1',
				'widget-2'       => 'sidebar-widgets-footer-2',
				'widget-3'       => 'sidebar-widgets-footer-3',
				'widget-4'       => 'sidebar-widgets-footer-4',
			),
		)
	);
}

add_action( 'customize_preview_init', 'uet_customize_preview' );
function uet_customize_preview(): void {
	wp_enqueue_script( 'uet-customize-preview', UET_URI . 'assets/js/customize-preview.js', array( 'customize-preview', 'customize-selective-refresh' ), UET_VERSION, true );
}
