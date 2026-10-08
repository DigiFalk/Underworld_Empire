<?php
/**
 * Customizer panels, sections and controls, generated from mpet_options().
 *
 * Most settings update the preview without a reload:
 *  - CSS settings re-render the <style id="mpet-dynamic-css"> element,
 *  - header and footer settings re-render the header / footer.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * How a setting is previewed: css, header, footer or refresh.
 */
function mpet_preview_mode( string $key, array $opt ): string {
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

add_action( 'customize_register', 'mpet_customize_register' );
function mpet_customize_register( WP_Customize_Manager $wp_customize ): void {
	require_once MPET_DIR . 'inc/controls.php';

	$panels = array(
		'mpet_global' => array( __( 'Global', 'mafia-pbbg-engine-theme' ), 20 ),
		'mpet_header' => array( __( 'Header', 'mafia-pbbg-engine-theme' ), 21 ),
		'mpet_footer' => array( __( 'Footer', 'mafia-pbbg-engine-theme' ), 22 ),
		'mpet_blog'   => array( __( 'Blog', 'mafia-pbbg-engine-theme' ), 23 ),
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
		'mpet_colors'         => array( 'mpet_global', __( 'Colours', 'mafia-pbbg-engine-theme' ) ),
		'mpet_color_modes'    => array( 'mpet_global', __( 'Light & dark mode', 'mafia-pbbg-engine-theme' ) ),
		'mpet_typography'     => array( 'mpet_global', __( 'Typography', 'mafia-pbbg-engine-theme' ) ),
		'mpet_layout'         => array( 'mpet_global', __( 'Container & buttons', 'mafia-pbbg-engine-theme' ) ),
		'mpet_sidebar'        => array( 'mpet_global', __( 'Sidebar', 'mafia-pbbg-engine-theme' ) ),
		'mpet_misc'           => array( 'mpet_global', __( 'Breadcrumbs, titles & scroll to top', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_builder' => array( 'mpet_header', __( 'Header builder', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_general' => array( 'mpet_header', __( 'Header settings', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_rows'    => array( 'mpet_header', __( 'Header rows', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_button'  => array( 'mpet_header', __( 'Button', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_html'    => array( 'mpet_header', __( 'HTML / text', 'mafia-pbbg-engine-theme' ) ),
		'mpet_header_search'  => array( 'mpet_header', __( 'Search', 'mafia-pbbg-engine-theme' ) ),
		'mpet_social'         => array( 'mpet_header', __( 'Social icons', 'mafia-pbbg-engine-theme' ) ),
		'mpet_mobile'         => array( 'mpet_header', __( 'Mobile header & menu', 'mafia-pbbg-engine-theme' ) ),
		'mpet_footer_builder' => array( 'mpet_footer', __( 'Footer builder', 'mafia-pbbg-engine-theme' ) ),
		'mpet_footer_rows'    => array( 'mpet_footer', __( 'Footer rows', 'mafia-pbbg-engine-theme' ) ),
		'mpet_footer'         => array( 'mpet_footer', __( 'Copyright, text & colours', 'mafia-pbbg-engine-theme' ) ),
		'mpet_blog'           => array( 'mpet_blog', __( 'Blog & archives', 'mafia-pbbg-engine-theme' ) ),
		'mpet_single'         => array( 'mpet_blog', __( 'Single post', 'mafia-pbbg-engine-theme' ) ),
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
	foreach ( mpet_options() as $key => $opt ) {
		$mode      = mpet_preview_mode( $key, $opt );
		$transport = 'refresh' === $mode ? 'refresh' : 'postMessage';
		$section   = 'mpet_header_logo' === $opt['section'] ? 'title_tagline' : $opt['section'];
		$sanitize  = static function ( $value ) use ( $opt ) {
			return mpet_sanitize( $value, $opt );
		};

		if ( 'responsive' === $opt['type'] ) {
			$ids = array();
			foreach ( array( 'default' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ) as $device => $suffix ) {
				$index = array( 'default' => 0, 'tablet' => 1, 'mobile' => 2 )[ $device ];
				$id    = 'mpet_' . $key . $suffix;
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
				new MPET_Responsive_Control(
					$wp_customize,
					'mpet_' . $key,
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

		$setting = 'mpet_' . $key;
		$builder = in_array( $opt['type'], array( 'header_builder', 'footer_builder' ), true );
		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => $builder ? '' : $opt['default'],
				'transport'         => $transport,
				'sanitize_callback' => $builder
					? ( 'header_builder' === $opt['type'] ? 'mpet_sanitize_header_builder' : 'mpet_sanitize_footer_builder' )
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
			$wp_customize->add_control( new MPET_Builder_Control( $wp_customize, $setting, $args ) );
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
			'mpet_css',
			array(
				'selector'            => '#mpet-dynamic-css',
				'settings'            => $partials['css'],
				'container_inclusive' => false,
				'fallback_refresh'    => true,
				'render_callback'     => static function () {
					return mpet_dynamic_css();
				},
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'mpet_header',
			array(
				'selector'            => '#masthead',
				'settings'            => $partials['header'],
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => 'mpet_render_header',
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'mpet_footer',
			array(
				'selector'            => '#colophon',
				'settings'            => $partials['footer'],
				'container_inclusive' => true,
				'fallback_refresh'    => true,
				'render_callback'     => 'mpet_render_footer',
			)
		);
	}
}

/**
 * @param mixed $value
 * @return mixed
 */
function mpet_sanitize( $value, array $opt ) {
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

add_action( 'customize_controls_enqueue_scripts', 'mpet_customize_controls' );
function mpet_customize_controls(): void {
	wp_enqueue_style( 'mpet-customize-controls', MPET_URI . 'assets/css/customize-controls.css', array(), MPET_VERSION );
	wp_enqueue_script( 'mpet-customize-controls', MPET_URI . 'assets/js/customize-controls.js', array( 'customize-controls', 'jquery-ui-sortable' ), MPET_VERSION, true );
	$palettes = array();
	foreach ( mpet_palettes() as $key => $palette ) {
		$palettes[ $key ] = $palette['colors'];
	}
	wp_localize_script(
		'mpet-customize-controls',
		'mpetCustomizer',
		array(
			'palettes' => $palettes,
			'header'   => array(
				'elements' => mpet_header_elements(),
				'rows'     => array(
					'above'   => __( 'Top row', 'mafia-pbbg-engine-theme' ),
					'primary' => __( 'Main row', 'mafia-pbbg-engine-theme' ),
					'below'   => __( 'Bottom row', 'mafia-pbbg-engine-theme' ),
				),
				'zones'    => array(
					'left'   => __( 'Left', 'mafia-pbbg-engine-theme' ),
					'center' => __( 'Center', 'mafia-pbbg-engine-theme' ),
					'right'  => __( 'Right', 'mafia-pbbg-engine-theme' ),
				),
			),
			'footer'   => array(
				'elements' => mpet_footer_elements(),
				'rows'     => array(
					'above'   => __( 'Top row', 'mafia-pbbg-engine-theme' ),
					'primary' => __( 'Middle row', 'mafia-pbbg-engine-theme' ),
					'below'   => __( 'Bottom row', 'mafia-pbbg-engine-theme' ),
				),
			),
			'i18n'     => array(
				'desktop'   => __( 'Desktop', 'mafia-pbbg-engine-theme' ),
				'mobile'    => __( 'Tablet & mobile', 'mafia-pbbg-engine-theme' ),
				'popup'     => __( 'Mobile menu panel', 'mafia-pbbg-engine-theme' ),
				'available' => __( 'Available elements (drag into a row, or use +)', 'mafia-pbbg-engine-theme' ),
				'add'       => __( 'Add element', 'mafia-pbbg-engine-theme' ),
				'remove'    => __( 'Remove', 'mafia-pbbg-engine-theme' ),
				'columns'   => __( 'Columns', 'mafia-pbbg-engine-theme' ),
				'column'    => __( 'Column', 'mafia-pbbg-engine-theme' ),
				'hidden'    => __( 'Row hidden', 'mafia-pbbg-engine-theme' ),
				'settings'  => __( 'Element settings', 'mafia-pbbg-engine-theme' ),
			),
			'sections' => array(
				'button'         => 'mpet_header_button',
				'html'           => 'mpet_header_html',
				'search'         => 'mpet_header_search',
				'social'         => 'mpet_social',
				'toggle'         => 'mpet_mobile',
				'logo'           => 'title_tagline',
				'menu-primary'   => 'menu_locations',
				'menu-secondary' => 'menu_locations',
				'copyright'      => 'mpet_footer',
				'menu-footer'    => 'menu_locations',
				'footer-html'    => 'mpet_footer',
				'widget-1'       => 'sidebar-widgets-footer-1',
				'widget-2'       => 'sidebar-widgets-footer-2',
				'widget-3'       => 'sidebar-widgets-footer-3',
				'widget-4'       => 'sidebar-widgets-footer-4',
			),
		)
	);
}

add_action( 'customize_preview_init', 'mpet_customize_preview' );
function mpet_customize_preview(): void {
	wp_enqueue_script( 'mpet-customize-preview', MPET_URI . 'assets/js/customize-preview.js', array( 'customize-preview', 'customize-selective-refresh' ), MPET_VERSION, true );
}
