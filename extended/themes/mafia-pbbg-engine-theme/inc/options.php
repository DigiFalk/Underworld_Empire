<?php
/**
 * Option registry. Every Customizer setting is defined once here: default, section,
 * control type and choices. mpet_opt() reads a value with its default.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Colour palettes. "custom" uses the individual colour settings.
 */
function mpet_palettes(): array {
	return array(
		'dark'    => array(
			'label'  => __( 'Underworld (dark & gold)', 'mafia-pbbg-engine-theme' ),
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
			'label'  => __( 'Noir (black & crimson)', 'mafia-pbbg-engine-theme' ),
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
			'label'  => __( 'Daylight (light & bronze)', 'mafia-pbbg-engine-theme' ),
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
function mpet_fonts(): array {
	$sans  = ', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
	$serif = ', Georgia, "Times New Roman", serif';
	return array(
		'system'           => array( __( 'System font', 'mafia-pbbg-engine-theme' ), 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif', '' ),
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
function mpet_options(): array {
	static $options = null;
	if ( null !== $options ) {
		return $options;
	}

	$palette_choices = array();
	foreach ( mpet_palettes() as $key => $palette ) {
		$palette_choices[ $key ] = $palette['label'];
	}
	$palette_choices['custom'] = __( 'Custom colours', 'mafia-pbbg-engine-theme' );

	$font_choices = array();
	foreach ( mpet_fonts() as $key => $font ) {
		$font_choices[ $key ] = $font[0];
	}

	$sidebar_choices = array(
		'default' => __( 'Default', 'mafia-pbbg-engine-theme' ),
		'none'    => __( 'No sidebar', 'mafia-pbbg-engine-theme' ),
		'right'   => __( 'Right sidebar', 'mafia-pbbg-engine-theme' ),
		'left'    => __( 'Left sidebar', 'mafia-pbbg-engine-theme' ),
	);
	$dark  = mpet_palettes()['dark']['colors'];
	$light = mpet_palettes()['light']['colors'];

	$o = array(
		/* Colours ----------------------------------------------------------- */
		'palette'                => array( 'dark', 'mpet_colors', 'select', __( 'Colour palette', 'mafia-pbbg-engine-theme' ), $palette_choices, __( 'Choosing a palette fills in the colours below. Change any colour to switch to custom colours.', 'mafia-pbbg-engine-theme' ) ),
		'color_base'             => array( $dark['base'], 'mpet_colors', 'color', __( 'Background', 'mafia-pbbg-engine-theme' ) ),
		'color_surface'          => array( $dark['surface'], 'mpet_colors', 'color', __( 'Surface (boxes, header)', 'mafia-pbbg-engine-theme' ) ),
		'color_surface_2'        => array( $dark['surface_2'], 'mpet_colors', 'color', __( 'Surface 2 (highlights)', 'mafia-pbbg-engine-theme' ) ),
		'color_border'           => array( $dark['border'], 'mpet_colors', 'color', __( 'Borders', 'mafia-pbbg-engine-theme' ) ),
		'color_text'             => array( $dark['text'], 'mpet_colors', 'color', __( 'Text', 'mafia-pbbg-engine-theme' ) ),
		'color_muted'            => array( $dark['muted'], 'mpet_colors', 'color', __( 'Muted text', 'mafia-pbbg-engine-theme' ) ),
		'color_heading'          => array( $dark['heading'], 'mpet_colors', 'color', __( 'Headings', 'mafia-pbbg-engine-theme' ) ),
		'color_accent'           => array( $dark['accent'], 'mpet_colors', 'color', __( 'Accent / links', 'mafia-pbbg-engine-theme' ) ),
		'color_link_hover'       => array( $dark['link_hover'], 'mpet_colors', 'color', __( 'Link hover', 'mafia-pbbg-engine-theme' ) ),
		'color_button_bg'        => array( $dark['button_bg'], 'mpet_colors', 'color', __( 'Button background', 'mafia-pbbg-engine-theme' ) ),
		'color_button_text'      => array( $dark['button_text'], 'mpet_colors', 'color', __( 'Button text', 'mafia-pbbg-engine-theme' ) ),
		/* Light & dark mode ------------------------------------------------- */
		'color_mode'             => array( 'toggle', 'mpet_color_modes', 'select', __( 'Light and dark mode', 'mafia-pbbg-engine-theme' ), array(
			'single'       => __( 'One colour scheme (only the colours above)', 'mafia-pbbg-engine-theme' ),
			'toggle'       => __( 'Dark by default, visitors can switch to light', 'mafia-pbbg-engine-theme' ),
			'toggle_light' => __( 'Light by default, visitors can switch to dark', 'mafia-pbbg-engine-theme' ),
			'auto'         => __( 'Follow the visitor\'s device, visitors can switch', 'mafia-pbbg-engine-theme' ),
		), __( 'With a switch, the Colours section holds the dark mode colours and the colours below are used in light mode. Place the "Light/dark switch" element in the header or footer builder (or the game layout). The choice of the visitor is remembered in their browser.', 'mafia-pbbg-engine-theme' ) ),
		'light_palette'          => array( 'light', 'mpet_color_modes', 'select', __( 'Light mode palette', 'mafia-pbbg-engine-theme' ), $palette_choices, __( 'Choosing a palette fills in the light mode colours below.', 'mafia-pbbg-engine-theme' ) ),
		'light_color_base' => array( $light['base'], 'mpet_color_modes', 'color', __( 'Background', 'mafia-pbbg-engine-theme' ) ),
		'light_color_surface' => array( $light['surface'], 'mpet_color_modes', 'color', __( 'Surface (boxes, header)', 'mafia-pbbg-engine-theme' ) ),
		'light_color_surface_2' => array( $light['surface_2'], 'mpet_color_modes', 'color', __( 'Surface 2 (highlights)', 'mafia-pbbg-engine-theme' ) ),
		'light_color_border' => array( $light['border'], 'mpet_color_modes', 'color', __( 'Borders', 'mafia-pbbg-engine-theme' ) ),
		'light_color_text' => array( $light['text'], 'mpet_color_modes', 'color', __( 'Text', 'mafia-pbbg-engine-theme' ) ),
		'light_color_muted' => array( $light['muted'], 'mpet_color_modes', 'color', __( 'Muted text', 'mafia-pbbg-engine-theme' ) ),
		'light_color_heading' => array( $light['heading'], 'mpet_color_modes', 'color', __( 'Headings', 'mafia-pbbg-engine-theme' ) ),
		'light_color_accent' => array( $light['accent'], 'mpet_color_modes', 'color', __( 'Accent / links', 'mafia-pbbg-engine-theme' ) ),
		'light_color_link_hover' => array( $light['link_hover'], 'mpet_color_modes', 'color', __( 'Link hover', 'mafia-pbbg-engine-theme' ) ),
		'light_color_button_bg' => array( $light['button_bg'], 'mpet_color_modes', 'color', __( 'Button background', 'mafia-pbbg-engine-theme' ) ),
		'light_color_button_text' => array( $light['button_text'], 'mpet_color_modes', 'color', __( 'Button text', 'mafia-pbbg-engine-theme' ) ),

		/* Typography -------------------------------------------------------- */
		'body_font'              => array( 'system', 'mpet_typography', 'select', __( 'Body font', 'mafia-pbbg-engine-theme' ), $font_choices ),
		'body_size'              => array( array( 16, 16, 15 ), 'mpet_typography', 'responsive', __( 'Body font size (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 12, 'max' => 24 ) ),
		'body_line_height'       => array( 16, 'mpet_typography', 'number', __( 'Line height (×10)', 'mafia-pbbg-engine-theme' ), array( 'min' => 10, 'max' => 24 ), __( '16 = 1.6', 'mafia-pbbg-engine-theme' ) ),
		'heading_font'           => array( 'system', 'mpet_typography', 'select', __( 'Heading font', 'mafia-pbbg-engine-theme' ), $font_choices ),
		'heading_weight'         => array( '700', 'mpet_typography', 'select', __( 'Heading weight', 'mafia-pbbg-engine-theme' ), array( '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800' ) ),
		'heading_transform'      => array( 'none', 'mpet_typography', 'select', __( 'Heading letter case', 'mafia-pbbg-engine-theme' ), array( 'none' => __( 'Normal', 'mafia-pbbg-engine-theme' ), 'uppercase' => __( 'Uppercase', 'mafia-pbbg-engine-theme' ) ) ),
		'h1_size'                => array( array( 40, 34, 28 ), 'mpet_typography', 'responsive', __( 'H1 size (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 18, 'max' => 96 ) ),
		'h2_size'                => array( array( 30, 27, 23 ), 'mpet_typography', 'responsive', __( 'H2 size (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 16, 'max' => 72 ) ),
		'h3_size'                => array( array( 24, 21, 19 ), 'mpet_typography', 'responsive', __( 'H3 size (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 14, 'max' => 56 ) ),
		'google_fonts'           => array( 0, 'mpet_typography', 'checkbox', __( 'Load fonts from Google Fonts', 'mafia-pbbg-engine-theme' ), null, __( 'Needed for the web fonts in the lists above. Off = the system font is used instead (no external requests, GDPR friendly).', 'mafia-pbbg-engine-theme' ) ),

		/* Layout ------------------------------------------------------------ */
		'site_layout'            => array( 'full-width', 'mpet_layout', 'select', __( 'Site layout', 'mafia-pbbg-engine-theme' ), array( 'full-width' => __( 'Full width', 'mafia-pbbg-engine-theme' ), 'boxed' => __( 'Boxed (site in a box)', 'mafia-pbbg-engine-theme' ), 'content-boxed' => __( 'Content boxed (content in cards)', 'mafia-pbbg-engine-theme' ) ) ),
		'container_width'        => array( 1200, 'mpet_layout', 'number', __( 'Container width (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 720, 'max' => 1920 ) ),
		'container_padding'      => array( array( 24, 20, 16 ), 'mpet_layout', 'responsive', __( 'Side spacing (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 0, 'max' => 80 ) ),
		'narrow_width'           => array( 760, 'mpet_layout', 'number', __( 'Narrow container width (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 480, 'max' => 1200 ) ),
		'button_radius'          => array( 6, 'mpet_layout', 'number', __( 'Button & box corner radius (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 0, 'max' => 40 ) ),
		'sidebar_default'        => array( 'right', 'mpet_sidebar', 'select', __( 'Default sidebar', 'mafia-pbbg-engine-theme' ), array_slice( $sidebar_choices, 1, null, true ) ),
		'sidebar_page'           => array( 'none', 'mpet_sidebar', 'select', __( 'Pages', 'mafia-pbbg-engine-theme' ), $sidebar_choices ),
		'sidebar_single'         => array( 'default', 'mpet_sidebar', 'select', __( 'Single posts', 'mafia-pbbg-engine-theme' ), $sidebar_choices ),
		'sidebar_archive'        => array( 'default', 'mpet_sidebar', 'select', __( 'Blog & archives', 'mafia-pbbg-engine-theme' ), $sidebar_choices ),
		'sidebar_width'          => array( 30, 'mpet_sidebar', 'number', __( 'Sidebar width (%)', 'mafia-pbbg-engine-theme' ), array( 'min' => 15, 'max' => 50 ) ),

		/* Header ------------------------------------------------------------ */
		'header_builder'         => array( '', 'mpet_header_builder', 'header_builder', __( 'Header layout', 'mafia-pbbg-engine-theme' ), null, __( 'Drag elements into the rows. Switch between desktop and mobile with the tabs; the mobile menu panel holds what opens under the menu toggle.', 'mafia-pbbg-engine-theme' ) ),
		'header_width'           => array( 'contained', 'mpet_header_general', 'select', __( 'Header width', 'mafia-pbbg-engine-theme' ), array( 'contained' => __( 'Contained', 'mafia-pbbg-engine-theme' ), 'full' => __( 'Full width', 'mafia-pbbg-engine-theme' ) ) ),
		'header_sticky'          => array( 0, 'mpet_header_general', 'checkbox', __( 'Sticky header', 'mafia-pbbg-engine-theme' ) ),
		'header_transparent'     => array( 'off', 'mpet_header_general', 'select', __( 'Transparent header', 'mafia-pbbg-engine-theme' ), array( 'off' => __( 'Off', 'mafia-pbbg-engine-theme' ), 'front' => __( 'Front page only', 'mafia-pbbg-engine-theme' ), 'all' => __( 'Whole site', 'mafia-pbbg-engine-theme' ) ), __( 'The header floats over the page content. Can be changed per page.', 'mafia-pbbg-engine-theme' ) ),
		'color_header_bg'        => array( '', 'mpet_header_general', 'color', __( 'Header background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = palette surface colour.', 'mafia-pbbg-engine-theme' ) ),
		'color_header_text'      => array( '', 'mpet_header_general', 'color', __( 'Header text & menu', 'mafia-pbbg-engine-theme' ) ),
		'hrow_above_height'      => array( array( 40, 40, 36 ), 'mpet_header_rows', 'responsive', __( 'Top row height (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 24, 'max' => 200 ) ),
		'hrow_above_bg'          => array( '', 'mpet_header_rows', 'color', __( 'Top row background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = palette surface 2 colour.', 'mafia-pbbg-engine-theme' ) ),
		'hrow_primary_height'    => array( array( 76, 68, 60 ), 'mpet_header_rows', 'responsive', __( 'Main row height (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 30, 'max' => 250 ) ),
		'hrow_primary_bg'        => array( '', 'mpet_header_rows', 'color', __( 'Main row background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = header background.', 'mafia-pbbg-engine-theme' ) ),
		'hrow_below_height'      => array( array( 52, 48, 44 ), 'mpet_header_rows', 'responsive', __( 'Bottom row height (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 24, 'max' => 200 ) ),
		'hrow_below_bg'          => array( '', 'mpet_header_rows', 'color', __( 'Bottom row background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = header background.', 'mafia-pbbg-engine-theme' ) ),
		'logo_width'             => array( array( 180, 160, 130 ), 'mpet_header_logo', 'responsive', __( 'Logo width (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 40, 'max' => 600 ) ),
		'show_title'             => array( 1, 'mpet_header_logo', 'checkbox', __( 'Show site title', 'mafia-pbbg-engine-theme' ) ),
		'show_tagline'           => array( 0, 'mpet_header_logo', 'checkbox', __( 'Show tagline', 'mafia-pbbg-engine-theme' ) ),
		'hb_button_text'         => array( (string) get_theme_mod( 'mpet_header_button_text', '' ) ?: __( 'Play now', 'mafia-pbbg-engine-theme' ), 'mpet_header_button', 'text', __( 'Button text', 'mafia-pbbg-engine-theme' ) ),
		'hb_button_url'          => array( (string) get_theme_mod( 'mpet_header_button_url', '' ), 'mpet_header_button', 'url', __( 'Button link', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = the game page.', 'mafia-pbbg-engine-theme' ) ),
		'hb_button_style'        => array( 'filled', 'mpet_header_button', 'select', __( 'Button style', 'mafia-pbbg-engine-theme' ), array( 'filled' => __( 'Filled', 'mafia-pbbg-engine-theme' ), 'outline' => __( 'Outline', 'mafia-pbbg-engine-theme' ) ) ),
		'hb_html'                => array( (string) get_theme_mod( 'mpet_top_bar_text', '' ), 'mpet_header_html', 'textarea', __( 'HTML / text', 'mafia-pbbg-engine-theme' ), null, __( 'Simple HTML and shortcodes are allowed.', 'mafia-pbbg-engine-theme' ) ),
		'hb_search_style'        => array( 'icon', 'mpet_header_search', 'select', __( 'Search style', 'mafia-pbbg-engine-theme' ), array( 'icon' => __( 'Icon with dropdown', 'mafia-pbbg-engine-theme' ), 'field' => __( 'Search field', 'mafia-pbbg-engine-theme' ) ) ),
		'social_facebook'        => array( '', 'mpet_social', 'url', 'Facebook' ),
		'social_instagram'       => array( '', 'mpet_social', 'url', 'Instagram' ),
		'social_x'               => array( '', 'mpet_social', 'url', 'X' ),
		'social_youtube'         => array( '', 'mpet_social', 'url', 'YouTube' ),
		'social_tiktok'          => array( '', 'mpet_social', 'url', 'TikTok' ),
		'social_discord'         => array( '', 'mpet_social', 'url', 'Discord' ),
		'social_twitch'          => array( '', 'mpet_social', 'url', 'Twitch' ),
		'mobile_breakpoint'      => array( 921, 'mpet_mobile', 'number', __( 'Use the mobile header below this width (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 480, 'max' => 1400 ) ),
		'mobile_popup'           => array( 'dropdown', 'mpet_mobile', 'select', __( 'Mobile menu panel', 'mafia-pbbg-engine-theme' ), array( 'dropdown' => __( 'Dropdown below the header', 'mafia-pbbg-engine-theme' ), 'offcanvas' => __( 'Off-canvas (slides in from the side)', 'mafia-pbbg-engine-theme' ) ) ),
		'mobile_menu_label'      => array( __( 'Menu', 'mafia-pbbg-engine-theme' ), 'mpet_mobile', 'text', __( 'Menu toggle label', 'mafia-pbbg-engine-theme' ), null, __( 'Leave empty for an icon-only button.', 'mafia-pbbg-engine-theme' ) ),

		/* Footer ------------------------------------------------------------ */
		'footer_builder'         => array( '', 'mpet_footer_builder', 'footer_builder', __( 'Footer layout', 'mafia-pbbg-engine-theme' ), null, __( 'Choose the number of columns per row, then drag elements into the columns.', 'mafia-pbbg-engine-theme' ) ),
		'frow_above_align'       => array( 'left', 'mpet_footer_rows', 'select', __( 'Top row alignment', 'mafia-pbbg-engine-theme' ), array( 'left' => __( 'Left', 'mafia-pbbg-engine-theme' ), 'center' => __( 'Center', 'mafia-pbbg-engine-theme' ), 'right' => __( 'Right', 'mafia-pbbg-engine-theme' ), 'split' => __( 'Spread (first column left, last right)', 'mafia-pbbg-engine-theme' ) ) ),
		'frow_primary_align'     => array( 'left', 'mpet_footer_rows', 'select', __( 'Middle row alignment', 'mafia-pbbg-engine-theme' ), array( 'left' => __( 'Left', 'mafia-pbbg-engine-theme' ), 'center' => __( 'Center', 'mafia-pbbg-engine-theme' ), 'right' => __( 'Right', 'mafia-pbbg-engine-theme' ), 'split' => __( 'Spread (first column left, last right)', 'mafia-pbbg-engine-theme' ) ) ),
		'frow_below_align'       => array( 'split', 'mpet_footer_rows', 'select', __( 'Bottom row alignment', 'mafia-pbbg-engine-theme' ), array( 'left' => __( 'Left', 'mafia-pbbg-engine-theme' ), 'center' => __( 'Center', 'mafia-pbbg-engine-theme' ), 'right' => __( 'Right', 'mafia-pbbg-engine-theme' ), 'split' => __( 'Spread (first column left, last right)', 'mafia-pbbg-engine-theme' ) ) ),
		'footer_row_padding'     => array( array( 28, 24, 20 ), 'mpet_footer_rows', 'responsive', __( 'Row padding (px)', 'mafia-pbbg-engine-theme' ), array( 'min' => 0, 'max' => 120 ) ),
		'frow_above_bg'          => array( '', 'mpet_footer_rows', 'color', __( 'Top row background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = footer background.', 'mafia-pbbg-engine-theme' ) ),
		'frow_primary_bg'        => array( '', 'mpet_footer_rows', 'color', __( 'Middle row background', 'mafia-pbbg-engine-theme' ) ),
		'frow_below_bg'          => array( '', 'mpet_footer_rows', 'color', __( 'Bottom row background', 'mafia-pbbg-engine-theme' ) ),
		'footer_copyright'       => array( 'Copyright &copy; [current_year] [site_title]', 'mpet_footer', 'textarea', __( 'Copyright text', 'mafia-pbbg-engine-theme' ), null, __( 'Available codes: [current_year] [site_title] [site_url] [theme_author]', 'mafia-pbbg-engine-theme' ) ),
		'fb_html'                => array( '', 'mpet_footer', 'textarea', __( 'Footer HTML / text', 'mafia-pbbg-engine-theme' ), null, __( 'Used by the "HTML / text" footer element. Simple HTML and shortcodes are allowed.', 'mafia-pbbg-engine-theme' ) ),
		'color_footer_bg'        => array( '', 'mpet_footer', 'color', __( 'Footer background', 'mafia-pbbg-engine-theme' ), null, __( 'Empty = palette surface colour.', 'mafia-pbbg-engine-theme' ) ),
		'color_footer_text'      => array( '', 'mpet_footer', 'color', __( 'Footer text', 'mafia-pbbg-engine-theme' ) ),

		/* Blog -------------------------------------------------------------- */
		'blog_layout'            => array( 'list', 'mpet_blog', 'select', __( 'Blog layout', 'mafia-pbbg-engine-theme' ), array( 'list' => __( 'List', 'mafia-pbbg-engine-theme' ), 'grid-2' => __( 'Grid, 2 columns', 'mafia-pbbg-engine-theme' ), 'grid-3' => __( 'Grid, 3 columns', 'mafia-pbbg-engine-theme' ) ) ),
		'blog_featured'          => array( 1, 'mpet_blog', 'checkbox', __( 'Show featured images', 'mafia-pbbg-engine-theme' ) ),
		'meta_date'              => array( 1, 'mpet_blog', 'checkbox', __( 'Show date', 'mafia-pbbg-engine-theme' ) ),
		'meta_author'            => array( 1, 'mpet_blog', 'checkbox', __( 'Show author', 'mafia-pbbg-engine-theme' ) ),
		'meta_categories'        => array( 1, 'mpet_blog', 'checkbox', __( 'Show categories', 'mafia-pbbg-engine-theme' ) ),
		'meta_comments'          => array( 0, 'mpet_blog', 'checkbox', __( 'Show comment count', 'mafia-pbbg-engine-theme' ) ),
		'excerpt_length'         => array( 30, 'mpet_blog', 'number', __( 'Excerpt length (words)', 'mafia-pbbg-engine-theme' ), array( 'min' => 0, 'max' => 150 ) ),
		'read_more'              => array( __( 'Read more', 'mafia-pbbg-engine-theme' ), 'mpet_blog', 'text', __( 'Read more text', 'mafia-pbbg-engine-theme' ), null, __( 'Leave empty to hide the link.', 'mafia-pbbg-engine-theme' ) ),
		'single_featured'        => array( 1, 'mpet_single', 'checkbox', __( 'Show featured image', 'mafia-pbbg-engine-theme' ) ),
		'single_meta'            => array( 1, 'mpet_single', 'checkbox', __( 'Show post meta', 'mafia-pbbg-engine-theme' ) ),
		'single_tags'            => array( 1, 'mpet_single', 'checkbox', __( 'Show tags', 'mafia-pbbg-engine-theme' ) ),
		'author_box'             => array( 0, 'mpet_single', 'checkbox', __( 'Show author box', 'mafia-pbbg-engine-theme' ) ),
		'post_navigation'        => array( 1, 'mpet_single', 'checkbox', __( 'Show previous / next post', 'mafia-pbbg-engine-theme' ) ),

		/* Misc -------------------------------------------------------------- */
		'breadcrumbs'            => array( 0, 'mpet_misc', 'checkbox', __( 'Show breadcrumbs', 'mafia-pbbg-engine-theme' ) ),
		'breadcrumbs_home'       => array( 0, 'mpet_misc', 'checkbox', __( 'Also on the front page', 'mafia-pbbg-engine-theme' ) ),
		'scroll_top'             => array( 1, 'mpet_misc', 'checkbox', __( 'Show "scroll to top" button', 'mafia-pbbg-engine-theme' ) ),
		'scroll_top_position'    => array( 'right', 'mpet_misc', 'select', __( 'Scroll to top position', 'mafia-pbbg-engine-theme' ), array( 'right' => __( 'Right', 'mafia-pbbg-engine-theme' ), 'left' => __( 'Left', 'mafia-pbbg-engine-theme' ) ) ),
		'page_titles'            => array( 1, 'mpet_misc', 'checkbox', __( 'Show page titles', 'mafia-pbbg-engine-theme' ), null, __( 'Can be changed per page.', 'mafia-pbbg-engine-theme' ) ),
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
function mpet_opt( string $key ) {
	$options = mpet_options();
	$default = $options[ $key ]['default'] ?? null;
	if ( is_array( $default ) ) {
		$default = $default[0];
	}
	return get_theme_mod( 'mpet_' . $key, $default );
}

/**
 * Responsive value: [ desktop, tablet, mobile ].
 */
function mpet_opt_r( string $key ): array {
	$options  = mpet_options();
	$defaults = (array) ( $options[ $key ]['default'] ?? array( 0, 0, 0 ) );
	return array(
		(int) get_theme_mod( 'mpet_' . $key, $defaults[0] ),
		(int) get_theme_mod( 'mpet_' . $key . '_tablet', $defaults[1] ?? $defaults[0] ),
		(int) get_theme_mod( 'mpet_' . $key . '_mobile', $defaults[2] ?? $defaults[0] ),
	);
}

/**
 * Resolved colours: palette values, or the individual colour settings for "custom".
 *
 * @param string $set main (the Colours section) or light (light mode colours).
 */
function mpet_colors( string $set = 'main' ): array {
	$light    = 'light' === $set;
	$palette  = (string) mpet_opt( $light ? 'light_palette' : 'palette' );
	$prefix   = $light ? 'light_color_' : 'color_';
	$palettes = mpet_palettes();
	$fallback = $palettes[ $light ? 'light' : 'dark' ]['colors'];
	$colors   = array();
	foreach ( $fallback as $key => $default ) {
		$colors[ $key ] = isset( $palettes[ $palette ] )
			? $palettes[ $palette ]['colors'][ $key ]
			: ( (string) mpet_opt( $prefix . $key ) ?: $default );
	}
	// Header and footer colour overrides belong to the main colours.
	$colors['header_bg']   = ( $light ? '' : (string) mpet_opt( 'color_header_bg' ) ) ?: $colors['surface'];
	$colors['header_text'] = ( $light ? '' : (string) mpet_opt( 'color_header_text' ) ) ?: $colors['text'];
	$colors['footer_bg']   = ( $light ? '' : (string) mpet_opt( 'color_footer_bg' ) ) ?: $colors['surface'];
	$colors['footer_text'] = ( $light ? '' : (string) mpet_opt( 'color_footer_text' ) ) ?: $colors['muted'];
	return $colors;
}

/**
 * Light / dark mode setting: single, toggle, toggle_light or auto.
 */
function mpet_color_mode(): string {
	$mode = (string) mpet_opt( 'color_mode' );
	return in_array( $mode, array( 'toggle', 'toggle_light', 'auto' ), true ) ? $mode : 'single';
}
