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
	$dark  = uet_palettes()['dark']['colors'];
	$light = uet_palettes()['light']['colors'];

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
		/* Light & dark mode ------------------------------------------------- */
		'color_mode'             => array( 'single', 'uet_color_modes', 'select', __( 'Light and dark mode', 'underworld-empire-theme' ), array(
			'single'       => __( 'One colour scheme (only the colours above)', 'underworld-empire-theme' ),
			'toggle'       => __( 'Dark by default, visitors can switch to light', 'underworld-empire-theme' ),
			'toggle_light' => __( 'Light by default, visitors can switch to dark', 'underworld-empire-theme' ),
			'auto'         => __( 'Follow the visitor\'s device, visitors can switch', 'underworld-empire-theme' ),
		), __( 'With a switch, the Colours section holds the dark mode colours and the colours below are used in light mode. Place the "Light/dark switch" element in the header or footer builder (or the game layout). The choice of the visitor is remembered in their browser.', 'underworld-empire-theme' ) ),
		'light_palette'          => array( 'light', 'uet_color_modes', 'select', __( 'Light mode palette', 'underworld-empire-theme' ), $palette_choices, __( 'Choosing a palette fills in the light mode colours below.', 'underworld-empire-theme' ) ),
		'light_color_base' => array( $light['base'], 'uet_color_modes', 'color', __( 'Background', 'underworld-empire-theme' ) ),
		'light_color_surface' => array( $light['surface'], 'uet_color_modes', 'color', __( 'Surface (boxes, header)', 'underworld-empire-theme' ) ),
		'light_color_surface_2' => array( $light['surface_2'], 'uet_color_modes', 'color', __( 'Surface 2 (highlights)', 'underworld-empire-theme' ) ),
		'light_color_border' => array( $light['border'], 'uet_color_modes', 'color', __( 'Borders', 'underworld-empire-theme' ) ),
		'light_color_text' => array( $light['text'], 'uet_color_modes', 'color', __( 'Text', 'underworld-empire-theme' ) ),
		'light_color_muted' => array( $light['muted'], 'uet_color_modes', 'color', __( 'Muted text', 'underworld-empire-theme' ) ),
		'light_color_heading' => array( $light['heading'], 'uet_color_modes', 'color', __( 'Headings', 'underworld-empire-theme' ) ),
		'light_color_accent' => array( $light['accent'], 'uet_color_modes', 'color', __( 'Accent / links', 'underworld-empire-theme' ) ),
		'light_color_link_hover' => array( $light['link_hover'], 'uet_color_modes', 'color', __( 'Link hover', 'underworld-empire-theme' ) ),
		'light_color_button_bg' => array( $light['button_bg'], 'uet_color_modes', 'color', __( 'Button background', 'underworld-empire-theme' ) ),
		'light_color_button_text' => array( $light['button_text'], 'uet_color_modes', 'color', __( 'Button text', 'underworld-empire-theme' ) ),

		/* Typography -------------------------------------------------------- */
		'body_font'              => array( 'system', 'uet_typography', 'select', __( 'Body font', 'underworld-empire-theme' ), $font_choices ),
		'body_size'              => array( array( 16, 16, 15 ), 'uet_typography', 'responsive', __( 'Body font size (px)', 'underworld-empire-theme' ), array( 'min' => 12, 'max' => 24 ) ),
		'body_line_height'       => array( 16, 'uet_typography', 'number', __( 'Line height (×10)', 'underworld-empire-theme' ), array( 'min' => 10, 'max' => 24 ), __( '16 = 1.6', 'underworld-empire-theme' ) ),
		'heading_font'           => array( 'system', 'uet_typography', 'select', __( 'Heading font', 'underworld-empire-theme' ), $font_choices ),
		'heading_weight'         => array( '700', 'uet_typography', 'select', __( 'Heading weight', 'underworld-empire-theme' ), array( '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800' ) ),
		'heading_transform'      => array( 'none', 'uet_typography', 'select', __( 'Heading letter case', 'underworld-empire-theme' ), array( 'none' => __( 'Normal', 'underworld-empire-theme' ), 'uppercase' => __( 'Uppercase', 'underworld-empire-theme' ) ) ),
		'h1_size'                => array( array( 40, 34, 28 ), 'uet_typography', 'responsive', __( 'H1 size (px)', 'underworld-empire-theme' ), array( 'min' => 18, 'max' => 96 ) ),
		'h2_size'                => array( array( 30, 27, 23 ), 'uet_typography', 'responsive', __( 'H2 size (px)', 'underworld-empire-theme' ), array( 'min' => 16, 'max' => 72 ) ),
		'h3_size'                => array( array( 24, 21, 19 ), 'uet_typography', 'responsive', __( 'H3 size (px)', 'underworld-empire-theme' ), array( 'min' => 14, 'max' => 56 ) ),
		'google_fonts'           => array( 0, 'uet_typography', 'checkbox', __( 'Load fonts from Google Fonts', 'underworld-empire-theme' ), null, __( 'Needed for the web fonts in the lists above. Off = the system font is used instead (no external requests, GDPR friendly).', 'underworld-empire-theme' ) ),

		/* Layout ------------------------------------------------------------ */
		'site_layout'            => array( 'full-width', 'uet_layout', 'select', __( 'Site layout', 'underworld-empire-theme' ), array( 'full-width' => __( 'Full width', 'underworld-empire-theme' ), 'boxed' => __( 'Boxed (site in a box)', 'underworld-empire-theme' ), 'content-boxed' => __( 'Content boxed (content in cards)', 'underworld-empire-theme' ) ) ),
		'container_width'        => array( 1200, 'uet_layout', 'number', __( 'Container width (px)', 'underworld-empire-theme' ), array( 'min' => 720, 'max' => 1920 ) ),
		'container_padding'      => array( array( 24, 20, 16 ), 'uet_layout', 'responsive', __( 'Side spacing (px)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 80 ) ),
		'narrow_width'           => array( 760, 'uet_layout', 'number', __( 'Narrow container width (px)', 'underworld-empire-theme' ), array( 'min' => 480, 'max' => 1200 ) ),
		'button_radius'          => array( 6, 'uet_layout', 'number', __( 'Button & box corner radius (px)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 40 ) ),
		'sidebar_default'        => array( 'right', 'uet_sidebar', 'select', __( 'Default sidebar', 'underworld-empire-theme' ), array_slice( $sidebar_choices, 1, null, true ) ),
		'sidebar_page'           => array( 'none', 'uet_sidebar', 'select', __( 'Pages', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_single'         => array( 'default', 'uet_sidebar', 'select', __( 'Single posts', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_archive'        => array( 'default', 'uet_sidebar', 'select', __( 'Blog & archives', 'underworld-empire-theme' ), $sidebar_choices ),
		'sidebar_width'          => array( 30, 'uet_sidebar', 'number', __( 'Sidebar width (%)', 'underworld-empire-theme' ), array( 'min' => 15, 'max' => 50 ) ),

		/* Header ------------------------------------------------------------ */
		'header_builder'         => array( '', 'uet_header_builder', 'header_builder', __( 'Header layout', 'underworld-empire-theme' ), null, __( 'Drag elements into the rows. Switch between desktop and mobile with the tabs; the mobile menu panel holds what opens under the menu toggle.', 'underworld-empire-theme' ) ),
		'header_width'           => array( 'contained', 'uet_header_general', 'select', __( 'Header width', 'underworld-empire-theme' ), array( 'contained' => __( 'Contained', 'underworld-empire-theme' ), 'full' => __( 'Full width', 'underworld-empire-theme' ) ) ),
		'header_sticky'          => array( 0, 'uet_header_general', 'checkbox', __( 'Sticky header', 'underworld-empire-theme' ) ),
		'header_transparent'     => array( 'off', 'uet_header_general', 'select', __( 'Transparent header', 'underworld-empire-theme' ), array( 'off' => __( 'Off', 'underworld-empire-theme' ), 'front' => __( 'Front page only', 'underworld-empire-theme' ), 'all' => __( 'Whole site', 'underworld-empire-theme' ) ), __( 'The header floats over the page content. Can be changed per page.', 'underworld-empire-theme' ) ),
		'color_header_bg'        => array( '', 'uet_header_general', 'color', __( 'Header background', 'underworld-empire-theme' ), null, __( 'Empty = palette surface colour.', 'underworld-empire-theme' ) ),
		'color_header_text'      => array( '', 'uet_header_general', 'color', __( 'Header text & menu', 'underworld-empire-theme' ) ),
		'hrow_above_height'      => array( array( 40, 40, 36 ), 'uet_header_rows', 'responsive', __( 'Top row height (px)', 'underworld-empire-theme' ), array( 'min' => 24, 'max' => 200 ) ),
		'hrow_above_bg'          => array( '', 'uet_header_rows', 'color', __( 'Top row background', 'underworld-empire-theme' ), null, __( 'Empty = palette surface 2 colour.', 'underworld-empire-theme' ) ),
		'hrow_primary_height'    => array( array( 76, 68, 60 ), 'uet_header_rows', 'responsive', __( 'Main row height (px)', 'underworld-empire-theme' ), array( 'min' => 30, 'max' => 250 ) ),
		'hrow_primary_bg'        => array( '', 'uet_header_rows', 'color', __( 'Main row background', 'underworld-empire-theme' ), null, __( 'Empty = header background.', 'underworld-empire-theme' ) ),
		'hrow_below_height'      => array( array( 52, 48, 44 ), 'uet_header_rows', 'responsive', __( 'Bottom row height (px)', 'underworld-empire-theme' ), array( 'min' => 24, 'max' => 200 ) ),
		'hrow_below_bg'          => array( '', 'uet_header_rows', 'color', __( 'Bottom row background', 'underworld-empire-theme' ), null, __( 'Empty = header background.', 'underworld-empire-theme' ) ),
		'logo_width'             => array( array( 180, 160, 130 ), 'uet_header_logo', 'responsive', __( 'Logo width (px)', 'underworld-empire-theme' ), array( 'min' => 40, 'max' => 600 ) ),
		'show_title'             => array( 1, 'uet_header_logo', 'checkbox', __( 'Show site title', 'underworld-empire-theme' ) ),
		'show_tagline'           => array( 0, 'uet_header_logo', 'checkbox', __( 'Show tagline', 'underworld-empire-theme' ) ),
		'hb_button_text'         => array( (string) get_theme_mod( 'uet_header_button_text', '' ) ?: __( 'Play now', 'underworld-empire-theme' ), 'uet_header_button', 'text', __( 'Button text', 'underworld-empire-theme' ) ),
		'hb_button_url'          => array( (string) get_theme_mod( 'uet_header_button_url', '' ), 'uet_header_button', 'url', __( 'Button link', 'underworld-empire-theme' ), null, __( 'Empty = the game page.', 'underworld-empire-theme' ) ),
		'hb_button_style'        => array( 'filled', 'uet_header_button', 'select', __( 'Button style', 'underworld-empire-theme' ), array( 'filled' => __( 'Filled', 'underworld-empire-theme' ), 'outline' => __( 'Outline', 'underworld-empire-theme' ) ) ),
		'hb_html'                => array( (string) get_theme_mod( 'uet_top_bar_text', '' ), 'uet_header_html', 'textarea', __( 'HTML / text', 'underworld-empire-theme' ), null, __( 'Simple HTML and shortcodes are allowed.', 'underworld-empire-theme' ) ),
		'hb_search_style'        => array( 'icon', 'uet_header_search', 'select', __( 'Search style', 'underworld-empire-theme' ), array( 'icon' => __( 'Icon with dropdown', 'underworld-empire-theme' ), 'field' => __( 'Search field', 'underworld-empire-theme' ) ) ),
		'social_facebook'        => array( '', 'uet_social', 'url', 'Facebook' ),
		'social_instagram'       => array( '', 'uet_social', 'url', 'Instagram' ),
		'social_x'               => array( '', 'uet_social', 'url', 'X' ),
		'social_youtube'         => array( '', 'uet_social', 'url', 'YouTube' ),
		'social_tiktok'          => array( '', 'uet_social', 'url', 'TikTok' ),
		'social_discord'         => array( '', 'uet_social', 'url', 'Discord' ),
		'social_twitch'          => array( '', 'uet_social', 'url', 'Twitch' ),
		'mobile_breakpoint'      => array( 921, 'uet_mobile', 'number', __( 'Use the mobile header below this width (px)', 'underworld-empire-theme' ), array( 'min' => 480, 'max' => 1400 ) ),
		'mobile_popup'           => array( 'dropdown', 'uet_mobile', 'select', __( 'Mobile menu panel', 'underworld-empire-theme' ), array( 'dropdown' => __( 'Dropdown below the header', 'underworld-empire-theme' ), 'offcanvas' => __( 'Off-canvas (slides in from the side)', 'underworld-empire-theme' ) ) ),
		'mobile_menu_label'      => array( __( 'Menu', 'underworld-empire-theme' ), 'uet_mobile', 'text', __( 'Menu toggle label', 'underworld-empire-theme' ), null, __( 'Leave empty for an icon-only button.', 'underworld-empire-theme' ) ),

		/* Footer ------------------------------------------------------------ */
		'footer_builder'         => array( '', 'uet_footer_builder', 'footer_builder', __( 'Footer layout', 'underworld-empire-theme' ), null, __( 'Choose the number of columns per row, then drag elements into the columns.', 'underworld-empire-theme' ) ),
		'frow_above_align'       => array( 'left', 'uet_footer_rows', 'select', __( 'Top row alignment', 'underworld-empire-theme' ), array( 'left' => __( 'Left', 'underworld-empire-theme' ), 'center' => __( 'Center', 'underworld-empire-theme' ), 'right' => __( 'Right', 'underworld-empire-theme' ), 'split' => __( 'Spread (first column left, last right)', 'underworld-empire-theme' ) ) ),
		'frow_primary_align'     => array( 'left', 'uet_footer_rows', 'select', __( 'Middle row alignment', 'underworld-empire-theme' ), array( 'left' => __( 'Left', 'underworld-empire-theme' ), 'center' => __( 'Center', 'underworld-empire-theme' ), 'right' => __( 'Right', 'underworld-empire-theme' ), 'split' => __( 'Spread (first column left, last right)', 'underworld-empire-theme' ) ) ),
		'frow_below_align'       => array( 'split', 'uet_footer_rows', 'select', __( 'Bottom row alignment', 'underworld-empire-theme' ), array( 'left' => __( 'Left', 'underworld-empire-theme' ), 'center' => __( 'Center', 'underworld-empire-theme' ), 'right' => __( 'Right', 'underworld-empire-theme' ), 'split' => __( 'Spread (first column left, last right)', 'underworld-empire-theme' ) ) ),
		'footer_row_padding'     => array( array( 28, 24, 20 ), 'uet_footer_rows', 'responsive', __( 'Row padding (px)', 'underworld-empire-theme' ), array( 'min' => 0, 'max' => 120 ) ),
		'frow_above_bg'          => array( '', 'uet_footer_rows', 'color', __( 'Top row background', 'underworld-empire-theme' ), null, __( 'Empty = footer background.', 'underworld-empire-theme' ) ),
		'frow_primary_bg'        => array( '', 'uet_footer_rows', 'color', __( 'Middle row background', 'underworld-empire-theme' ) ),
		'frow_below_bg'          => array( '', 'uet_footer_rows', 'color', __( 'Bottom row background', 'underworld-empire-theme' ) ),
		'footer_copyright'       => array( 'Copyright &copy; [current_year] [site_title]', 'uet_footer', 'textarea', __( 'Copyright text', 'underworld-empire-theme' ), null, __( 'Available codes: [current_year] [site_title] [site_url] [theme_author]', 'underworld-empire-theme' ) ),
		'fb_html'                => array( '', 'uet_footer', 'textarea', __( 'Footer HTML / text', 'underworld-empire-theme' ), null, __( 'Used by the "HTML / text" footer element. Simple HTML and shortcodes are allowed.', 'underworld-empire-theme' ) ),
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
		$numeric         = in_array( $row[2], array( 'number', 'responsive' ), true );
		$options[ $key ] = array(
			'default'     => $row[0],
			'section'     => $row[1],
			'type'        => $row[2],
			'label'       => $row[3],
			'choices'     => $numeric ? array() : ( $row[4] ?? array() ),
			'input_attrs' => $numeric ? ( $row[4] ?? array() ) : array(),
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
	if ( is_array( $default ) ) {
		$default = $default[0];
	}
	return get_theme_mod( 'uet_' . $key, $default );
}

/**
 * Responsive value: [ desktop, tablet, mobile ].
 */
function uet_opt_r( string $key ): array {
	$options  = uet_options();
	$defaults = (array) ( $options[ $key ]['default'] ?? array( 0, 0, 0 ) );
	return array(
		(int) get_theme_mod( 'uet_' . $key, $defaults[0] ),
		(int) get_theme_mod( 'uet_' . $key . '_tablet', $defaults[1] ?? $defaults[0] ),
		(int) get_theme_mod( 'uet_' . $key . '_mobile', $defaults[2] ?? $defaults[0] ),
	);
}

/**
 * Resolved colours: palette values, or the individual colour settings for "custom".
 *
 * @param string $set main (the Colours section) or light (light mode colours).
 */
function uet_colors( string $set = 'main' ): array {
	$light    = 'light' === $set;
	$palette  = (string) uet_opt( $light ? 'light_palette' : 'palette' );
	$prefix   = $light ? 'light_color_' : 'color_';
	$palettes = uet_palettes();
	$fallback = $palettes[ $light ? 'light' : 'dark' ]['colors'];
	$colors   = array();
	foreach ( $fallback as $key => $default ) {
		$colors[ $key ] = isset( $palettes[ $palette ] )
			? $palettes[ $palette ]['colors'][ $key ]
			: ( (string) uet_opt( $prefix . $key ) ?: $default );
	}
	// Header and footer colour overrides belong to the main colours.
	$colors['header_bg']   = ( $light ? '' : (string) uet_opt( 'color_header_bg' ) ) ?: $colors['surface'];
	$colors['header_text'] = ( $light ? '' : (string) uet_opt( 'color_header_text' ) ) ?: $colors['text'];
	$colors['footer_bg']   = ( $light ? '' : (string) uet_opt( 'color_footer_bg' ) ) ?: $colors['surface'];
	$colors['footer_text'] = ( $light ? '' : (string) uet_opt( 'color_footer_text' ) ) ?: $colors['muted'];
	return $colors;
}

/**
 * Light / dark mode setting: single, toggle, toggle_light or auto.
 */
function uet_color_mode(): string {
	$mode = (string) uet_opt( 'color_mode' );
	return in_array( $mode, array( 'toggle', 'toggle_light', 'auto' ), true ) ? $mode : 'single';
}
