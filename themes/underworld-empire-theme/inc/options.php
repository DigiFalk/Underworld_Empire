<?php
/**
 * Option registry. Every Customizer setting is defined once here: default, section,
 * control type and choices. uet_opt() reads a value with its default.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colour palettes. "custom" uses the individual colour settings.
 */
function uet_palettes(): array {
	return array(
		'dark'    => array(
			'label'  => __( 'Underworld (dark & gold)', 'underworld-empire-theme' ),
			'colors' => array(
				'base'        => '#121417',
				'surface'     => '#1b1e23',
				'surface_2'   => '#23272e',
				'border'      => '#2f343c',
				'text'        => '#e6e3dc',
				'muted'       => '#9a978f',
				'heading'     => '#f2efe8',
				'accent'      => '#c9a24a',
				'link_hover'  => '#e0bb63',
				'button_bg'   => '#c9a24a',
				'button_text' => '#1a1405',
			),
		),
		'crimson' => array(
			'label'  => __( 'Noir (black & crimson)', 'underworld-empire-theme' ),
			'colors' => array(
				'base'        => '#0f0d0e',
				'surface'     => '#1a1617',
				'surface_2'   => '#241e20',
				'border'      => '#3a2f32',
				'text'        => '#ece6e3',
				'muted'       => '#a3999a',
				'heading'     => '#ffffff',
				'accent'      => '#d04545',
				'link_hover'  => '#e86a6a',
				'button_bg'   => '#c23b3b',
				'button_text' => '#ffffff',
			),
		),
		'light'   => array(
			'label'  => __( 'Daylight (light & bronze)', 'underworld-empire-theme' ),
			'colors' => array(
				'base'        => '#f7f5f0',
				'surface'     => '#ffffff',
				'surface_2'   => '#efece5',
				'border'      => '#ddd8cc',
				'text'        => '#2a2723',
				'muted'       => '#6f6a60',
				'heading'     => '#16140f',
				'accent'      => '#8a6720',
				'link_hover'  => '#6b4f16',
				'button_bg'   => '#8a6720',
				'button_text' => '#ffffff',
			),
		),
	);
}

/**
 * Fonts offered in the typography settings. Value => [ label, CSS stack, google family or '' ].
 */
function uet_fonts(): array {
	$sans  = ', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
	$serif = ', Georgia, "Times New Roman", serif';
	return array(
		'system'           => array( __( 'System font', 'underworld-empire-theme' ), 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif', '' ),
		'georgia'          => array( 'Georgia', 'Georgia, "Times New Roman", serif', '' ),
		'inter'            => array( 'Inter', '"Inter"' . $sans, 'Inter:wght@400;600;700' ),
		'roboto'           => array( 'Roboto', '"Roboto"' . $sans, 'Roboto:wght@400;500;700' ),
		'open-sans'        => array( 'Open Sans', '"Open Sans"' . $sans, 'Open+Sans:wght@400;600;700' ),
		'lato'             => array( 'Lato', '"Lato"' . $sans, 'Lato:wght@400;700' ),
		'montserrat'       => array( 'Montserrat', '"Montserrat"' . $sans, 'Montserrat:wght@400;600;700;800' ),
		'poppins'          => array( 'Poppins', '"Poppins"' . $sans, 'Poppins:wght@400;600;700' ),
		'raleway'          => array( 'Raleway', '"Raleway"' . $sans, 'Raleway:wght@400;600;700' ),
		'nunito'           => array( 'Nunito', '"Nunito"' . $sans, 'Nunito:wght@400;600;700' ),
		'oswald'           => array( 'Oswald', '"Oswald"' . $sans, 'Oswald:wght@400;500;700' ),
		'bebas-neue'       => array( 'Bebas Neue', '"Bebas Neue"' . $sans, 'Bebas+Neue' ),
		'playfair-display' => array( 'Playfair Display', '"Playfair Display"' . $serif, 'Playfair+Display:wght@400;700;800' ),
		'merriweather'     => array( 'Merriweather', '"Merriweather"' . $serif, 'Merriweather:wght@400;700' ),
		'lora'             => array( 'Lora', '"Lora"' . $serif, 'Lora:wght@400;600;700' ),
		'cinzel'           => array( 'Cinzel', '"Cinzel"' . $serif, 'Cinzel:wght@400;600;700' ),
	);
}

/**
 * All Customizer settings.
 *
 * key => [ default, section, type, label, choices|input_attrs, description ]
 */
function uet_options(): array {
	static $options = null;
	if ( null !== $options ) {
		return $options;
	}

	$palette_choices = array();
	foreach ( uet_palettes() as $key => $palette ) {
		$palette_choices[ $key ] = $palette['label'];
	}
	$palette_choices['custom'] = __( 'Custom colours', 'underworld-empire-theme' );

	$font_choices = array();
	foreach ( uet_fonts() as $key => $font ) {
		$font_choices[ $key ] = $font[0];
	}

	$sidebar_choices = array(
		'default' => __( 'Default', 'underworld-empire-theme' ),
		'none'    => __( 'No sidebar', 'underworld-empire-theme' ),
		'right'   => __( 'Right sidebar', 'underworld-empire-theme' ),
		'left'    => __( 'Left sidebar', 'underworld-empire-theme' ),
	);
	$dark = uet_palettes()['dark']['colors'];

	$o = array(
		/* Colours ----------------------------------------------------------- */
		'palette'                => array( 'dark', 'uet_colors', 'select', __( 'Colour palette', 'underworld-empire-theme' ), $palette_choices, __( 'Choosing a palette fills in the colours below. Change any colour to switch to custom colours.', 'underworld-empire-theme' ) ),
		'color_base'             => array( $dark['base'], 'uet_colors', 'color', __( 'Background', 'underworld-empire-theme' ) ),
		'color_surface'          => array( $dark['surface'], 'uet_colors', 'color', __( 'Surface (boxes, header)', 'underworld-empire-theme' ) ),
		'color_surface_2'        => array( $dark['surface_2'], 'uet_colors', 'color', __( 'Surface 2 (highlights)', 'underworld-empire-theme' ) ),
		'color_border'           => array( $dark['border'], 'uet_colors', 'color', __( 'Borders', 'underworld-empire-theme' ) ),
		'color_text'             => array( $dark['text'], 'uet_colors', 'color', __( 'Text', 'underworld-empire-theme' ) ),
		'color_muted'            => array( $dark['muted'], 'uet_colors', 'color', __( 'Muted text', 'underworld-empire-theme' ) ),
		'color_heading'          => array( $dark['heading'], 'uet_colors', 'color', __( 'Headings', 'underworld-empire-theme' ) ),
		'color_accent'           => array( $dark['accent'], 'uet_colors', 'color', __( 'Accent / links', 'underworld-empire-theme' ) ),
		'color_link_hover'       => array( $dark['link_hover'], 'uet_colors', 'color', __( 'Link hover', 'underworld-empire-theme' ) ),
		'color_button_bg'        => array( $dark['button_bg'], 'uet_colors', 'color', __( 'Button background', 'underworld-empire-theme' ) ),
		'color_button_text'      => array( $dark['button_text'], 'uet_colors', 'color', __( 'Button text', 'underworld-empire-theme' ) ),

		/* Typography -------------------------------------------------------- */
		'body_font'              => array( 'system', 'uet_typography', 'select', __( 'Body font', 'underworld-empire-theme' ), $font_choices ),
		'body_size'              => array( 16, 'uet_typography', 'number', __( 'Body font size (px)', 'underworld-empire-theme' ), array( 'min' => 12, 'max' => 24 ) ),
		'body_line_height'       => array( 16, 'uet_typography', 'number', __( 'Line height (×10)', 'underworld-empire-theme' ), array( 'min' => 10, 'max' => 24 ), __( '16 = 1.6', 'underworld-empire-theme' ) ),
		'heading_font'           => array( 'system', 'uet_typography', 'select', __( 'Heading font', 'underworld-empire-theme' ), $font_choices ),
		'heading_weight'         => array( '700', 'uet_typography', 'select', __( 'Heading weight', 'underworld-empire-theme' ), array( '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800' ) ),
		'heading_transform'      => array( 'none', 'uet_typography', 'select', __( 'Heading letter case', 'underworld-empire-theme' ), array( 'none' => __( 'Normal', 'underworld-empire-theme' ), 'uppercase' => __( 'Uppercase', 'underworld-empire-theme' ) ) ),
		'h1_size'                => array( 40, 'uet_typography', 'number', __( 'H1 size (px)', 'underworld-empire-theme' ), array( 'min' => 20, 'max' => 96 ) ),
		'h2_size'                => array( 30, 'uet_typography', 'number', __( 'H2 size (px)', 'underworld-empire-theme' ), array( 'min' => 16, 'max' => 72 ) ),
		'h3_size'                => array( 24, 'uet_typography', 'number', __( 'H3 size (px)', 'underworld-empire-theme' ), array( 'min' => 14, 'max' => 56 ) ),
		'google_fonts'           => array( 0, 'uet_typography', 'checkbox', __( 'Load fonts from Google Fonts', 'underworld-empire-theme' ), null, __( 'Needed for the web fonts in the lists above. Off = the system font is used instead (no external requests, GDPR friendly).', 'underworld-empire-theme' ) ),

		/* Layout ------------------------------------------------------------ */
		'site_layout'            => array( 'full-width', 'uet_layout', 'select', __( 'Site layout', 'underworld-empire-theme' ), array( 'full-width' => __( 'Full width', 'underworld-empire-theme' ), 'boxed' => __( 'Boxed (site in a box)', 'underworld-empire-theme' ), 'content-boxed' => __( 'Content boxed (content in cards)', 'underworld-empire-theme' ) ) ),
		'container_width'        => array( 1200, 'uet_layout', 'number', __( 'Container width (px)', 'underworld-empire-theme' ), array( 'min' => 720, 'max' => 1920 ) ),
		'narrow_width'           => array( 760, 'uet_layout', 'number', __( 'Narrow container width (px)', 'underworld-empire-theme' ), array( 'min' => 480, 'max' => 1200 ) ),
		'button_radius'          => array( 6, 'uet_layout', 'number', __( 'Button & box corner radius (px)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 40 ) ),
		'sidebar_default'        => array( 'right', 'uet_sidebar', 'select', __( 'Default sidebar', 'underworld-empire-theme' ), array_slice( $sidebar_choices, 1, null, true ) ),
		'sidebar_page'           => array( 'none', 'uet_sidebar', 'select', __( 'Pages', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_single'         => array( 'default', 'uet_sidebar', 'select', __( 'Single posts', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_archive'        => array( 'default', 'uet_sidebar', 'select', __( 'Blog & archives', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_width'          => array( 30, 'uet_sidebar', 'number', __( 'Sidebar width (%)', 'underworld-empire-theme' ), array( 'min' => 15, 'max' => 50 ) ),

		/* Header ------------------------------------------------------------ */
		'header_layout'          => array( 'logo-left', 'uet_header', 'select', __( 'Header layout', 'underworld-empire-theme' ), array( 'logo-left' => __( 'Logo left, menu right', 'underworld-empire-theme' ), 'logo-right' => __( 'Logo right, menu left', 'underworld-empire-theme' ), 'centered' => __( 'Logo centered, menu below', 'underworld-empire-theme' ) ) ),
		'header_width'           => array( 'contained', 'uet_header', 'select', __( 'Header width', 'underworld-empire-theme' ), array( 'contained' => __( 'Contained', 'underworld-empire-theme' ), 'full' => __( 'Full width', 'underworld-empire-theme' ) ) ),
		'header_padding'         => array( 18, 'uet_header', 'number', __( 'Header vertical padding (px)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 80 ) ),
		'logo_width'             => array( 180, 'uet_header', 'number', __( 'Logo width (px)', 'underworld-empire-theme' ), array( 'min' => 40, 'max' => 600 ) ),
		'show_title'             => array( 1, 'uet_header', 'checkbox', __( 'Show site title', 'underworld-empire-theme' ) ),
		'show_tagline'           => array( 0, 'uet_header', 'checkbox', __( 'Show tagline', 'underworld-empire-theme' ) ),
		'header_search'          => array( 0, 'uet_header', 'checkbox', __( 'Show search in header', 'underworld-empire-theme' ) ),
		'header_button_text'     => array( '', 'uet_header', 'text', __( 'Header button text', 'underworld-empire-theme' ), null, __( 'Leave empty to hide the button.', 'underworld-empire-theme' ) ),
		'header_button_url'      => array( '', 'uet_header', 'url', __( 'Header button link', 'underworld-empire-theme' ) ),
		'header_sticky'          => array( 0, 'uet_header', 'checkbox', __( 'Sticky header', 'underworld-empire-theme' ) ),
		'header_transparent'     => array( 'off', 'uet_header', 'select', __( 'Transparent header', 'underworld-empire-theme' ), array( 'off' => __( 'Off', 'underworld-empire-theme' ), 'front' => __( 'Front page only', 'underworld-empire-theme' ), 'all' => __( 'Whole site', 'underworld-empire-theme' ) ), __( 'The header floats over the page content. Can be changed per page.', 'underworld-empire-theme' ) ),
		'color_header_bg'        => array( '', 'uet_header', 'color', __( 'Header background', 'underworld-empire-theme' ), null, __( 'Empty = palette surface colour.', 'underworld-empire-theme' ) ),
		'color_header_text'      => array( '', 'uet_header', 'color', __( 'Header text & menu', 'underworld-empire-theme' ) ),
		'top_bar'                => array( 0, 'uet_topbar', 'checkbox', __( 'Show top bar above the header', 'underworld-empire-theme' ) ),
		'top_bar_text'           => array( '', 'uet_topbar', 'textarea', __( 'Top bar text', 'underworld-empire-theme' ), null, __( 'Shown left; the "Top bar" menu is shown right. Simple HTML allowed.', 'underworld-empire-theme' ) ),
		'mobile_breakpoint'      => array( 921, 'uet_mobile', 'number', __( 'Mobile menu below width (px)', 'underworld-empire-theme' ), array( 'min' => 480, 'max' => 1400 ) ),
		'mobile_menu_label'      => array( __( 'Menu', 'underworld-empire-theme' ), 'uet_mobile', 'text', __( 'Mobile menu button label', 'underworld-empire-theme' ) ),

		/* Footer ------------------------------------------------------------ */
		'footer_widgets'         => array( 0, 'uet_footer', 'select', __( 'Footer widget columns', 'underworld-empire-theme' ), array( '0' => __( 'None', 'underworld-empire-theme' ), '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), __( 'Add widgets under Appearance → Widgets → Footer 1-4.', 'underworld-empire-theme' ) ),
		'footer_layout'          => array( 'center', 'uet_footer', 'select', __( 'Footer bar layout', 'underworld-empire-theme' ), array( 'center' => __( 'Centered', 'underworld-empire-theme' ), 'split' => __( 'Copyright left, menu right', 'underworld-empire-theme' ) ) ),
		'footer_copyright'       => array( 'Copyright &copy; [current_year] [site_title]', 'uet_footer', 'textarea', __( 'Copyright text', 'underworld-empire-theme' ), null, __( 'Available codes: [current_year] [site_title] [site_url] [theme_author]', 'underworld-empire-theme' ) ),
		'color_footer_bg'        => array( '', 'uet_footer', 'color', __( 'Footer background', 'underworld-empire-theme' ), null, __( 'Empty = palette surface colour.', 'underworld-empire-theme' ) ),
		'color_footer_text'      => array( '', 'uet_footer', 'color', __( 'Footer text', 'underworld-empire-theme' ) ),

		/* Blog -------------------------------------------------------------- */
		'blog_layout'            => array( 'list', 'uet_blog', 'select', __( 'Blog layout', 'underworld-empire-theme' ), array( 'list' => __( 'List', 'underworld-empire-theme' ), 'grid-2' => __( 'Grid, 2 columns', 'underworld-empire-theme' ), 'grid-3' => __( 'Grid, 3 columns', 'underworld-empire-theme' ) ) ),
		'blog_featured'          => array( 1, 'uet_blog', 'checkbox', __( 'Show featured images', 'underworld-empire-theme' ) ),
		'meta_date'              => array( 1, 'uet_blog', 'checkbox', __( 'Show date', 'underworld-empire-theme' ) ),
		'meta_author'            => array( 1, 'uet_blog', 'checkbox', __( 'Show author', 'underworld-empire-theme' ) ),
		'meta_categories'        => array( 1, 'uet_blog', 'checkbox', __( 'Show categories', 'underworld-empire-theme' ) ),
		'meta_comments'          => array( 0, 'uet_blog', 'checkbox', __( 'Show comment count', 'underworld-empire-theme' ) ),
		'excerpt_length'         => array( 30, 'uet_blog', 'number', __( 'Excerpt length (words)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 150 ) ),
		'read_more'              => array( __( 'Read more', 'underworld-empire-theme' ), 'uet_blog', 'text', __( 'Read more text', 'underworld-empire-theme' ), null, __( 'Leave empty to hide the link.', 'underworld-empire-theme' ) ),
		'single_featured'        => array( 1, 'uet_single', 'checkbox', __( 'Show featured image', 'underworld-empire-theme' ) ),
		'single_meta'            => array( 1, 'uet_single', 'checkbox', __( 'Show post meta', 'underworld-empire-theme' ) ),
		'single_tags'            => array( 1, 'uet_single', 'checkbox', __( 'Show tags', 'underworld-empire-theme' ) ),
		'author_box'             => array( 0, 'uet_single', 'checkbox', __( 'Show author box', 'underworld-empire-theme' ) ),
		'post_navigation'        => array( 1, 'uet_single', 'checkbox', __( 'Show previous / next post', 'underworld-empire-theme' ) ),

		/* Misc -------------------------------------------------------------- */
		'breadcrumbs'            => array( 0, 'uet_misc', 'checkbox', __( 'Show breadcrumbs', 'underworld-empire-theme' ) ),
		'breadcrumbs_home'       => array( 0, 'uet_misc', 'checkbox', __( 'Also on the front page', 'underworld-empire-theme' ) ),
		'scroll_top'             => array( 1, 'uet_misc', 'checkbox', __( 'Show "scroll to top" button', 'underworld-empire-theme' ) ),
		'scroll_top_position'    => array( 'right', 'uet_misc', 'select', __( 'Scroll to top position', 'underworld-empire-theme' ), array( 'right' => __( 'Right', 'underworld-empire-theme' ), 'left' => __( 'Left', 'underworld-empire-theme' ) ) ),
		'page_titles'            => array( 1, 'uet_misc', 'checkbox', __( 'Show page titles', 'underworld-empire-theme' ), null, __( 'Can be changed per page.', 'underworld-empire-theme' ) ),
	);

	$options = array();
	foreach ( $o as $key => $row ) {
		$options[ $key ] = array(
			'default'     => $row[0],
			'section'     => $row[1],
			'type'        => $row[2],
			'label'       => $row[3],
			'choices'     => ( 'number' === $row[2] ) ? array() : ( $row[4] ?? array() ),
			'input_attrs' => ( 'number' === $row[2] ) ? ( $row[4] ?? array() ) : array(),
			'description' => $row[5] ?? '',
		);
	}
	return $options;
}

/**
 * Read an option (theme mod) with its default.
 *
 * @return mixed
 */
function uet_opt( string $key ) {
	$options = uet_options();
	$default = $options[ $key ]['default'] ?? null;
	return get_theme_mod( 'uet_' . $key, $default );
}

/**
 * Resolved colours: palette values, or the individual colour settings for "custom".
 */
function uet_colors(): array {
	$palette  = (string) uet_opt( 'palette' );
	$palettes = uet_palettes();
	$colors   = array();
	foreach ( $palettes['dark']['colors'] as $key => $default ) {
		$colors[ $key ] = isset( $palettes[ $palette ] )
			? $palettes[ $palette ]['colors'][ $key ]
			: ( (string) uet_opt( 'color_' . $key ) ?: $default );
	}
	$colors['header_bg']   = (string) uet_opt( 'color_header_bg' ) ?: $colors['surface'];
	$colors['header_text'] = (string) uet_opt( 'color_header_text' ) ?: $colors['text'];
	$colors['footer_bg']   = (string) uet_opt( 'color_footer_bg' ) ?: $colors['surface'];
	$colors['footer_text'] = (string) uet_opt( 'color_footer_text' ) ?: $colors['muted'];
	return $colors;
}
