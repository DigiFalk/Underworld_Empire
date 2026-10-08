<?php
/**
 * Theme supports, menus, widget areas and assets.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'mpet_setup' );
function mpet_setup(): void {
	load_theme_textdomain( 'mafia-pbbg-engine-theme', MPET_DIR . 'languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'mafia-pbbg-engine-theme' ),
			'top'     => __( 'Top bar menu', 'mafia-pbbg-engine-theme' ),
			'footer'  => __( 'Footer menu', 'mafia-pbbg-engine-theme' ),
		)
	);

	// Starter content for fresh sites (Appearance → Customize shows a ready-made site).
	add_theme_support(
		'starter-content',
		array(
			'posts'     => array(
				'home' => array(
					'post_type'    => 'page',
					'post_title'   => __( 'Welcome to the underworld', 'mafia-pbbg-engine-theme' ),
					'post_content' => '<!-- wp:paragraph --><p>' . __( 'Commit crimes, steal cars, build a family and work your way up to Godfather.', 'mafia-pbbg-engine-theme' ) . '</p><!-- /wp:paragraph --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( home_url( '/mafia-pbbg-engine/' ) ) . '">' . __( 'Play now', 'mafia-pbbg-engine-theme' ) . '</a></div><!-- /wp:button --></div><!-- /wp:buttons -->',
				),
				'blog',
			),
			'options'   => array(
				'show_on_front'  => 'page',
				'page_on_front'  => '{{home}}',
				'page_for_posts' => '{{blog}}',
			),
			'nav_menus' => array(
				'primary' => array(
					'name'  => __( 'Primary menu', 'mafia-pbbg-engine-theme' ),
					'items' => array( 'page_home', 'page_blog' ),
				),
			),
			'widgets'   => array(
				'sidebar-1' => array( 'search', 'text_about' ),
			),
		)
	);
}

add_action( 'after_setup_theme', 'mpet_content_width', 0 );
function mpet_content_width(): void {
	$GLOBALS['content_width'] = 760;
}

add_action( 'widgets_init', 'mpet_widgets' );
function mpet_widgets(): void {
	$args = array(
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	);
	register_sidebar(
		array_merge(
			$args,
			array(
				'name'        => __( 'Sidebar', 'mafia-pbbg-engine-theme' ),
				'id'          => 'sidebar-1',
				'description' => __( 'Shown on pages and posts that have a sidebar.', 'mafia-pbbg-engine-theme' ),
			)
		)
	);
	for ( $i = 1; $i <= 4; $i++ ) {
		register_sidebar(
			array_merge(
				$args,
				array(
					/* translators: %d: column number */
					'name' => sprintf( __( 'Footer %d', 'mafia-pbbg-engine-theme' ), $i ),
					'id'   => 'footer-' . $i,
				)
			)
		);
	}
}

add_action( 'wp_enqueue_scripts', 'mpet_assets' );
function mpet_assets(): void {
	$google = mpet_google_fonts_url();
	if ( $google ) {
		wp_enqueue_style( 'mpet-fonts', $google, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	wp_enqueue_style( 'mpet-theme', MPET_URI . 'assets/css/theme.css', array(), MPET_VERSION );
	wp_enqueue_script( 'mpet-theme', MPET_URI . 'assets/js/theme.js', array(), MPET_VERSION, true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'enqueue_block_editor_assets', 'mpet_editor_assets' );
function mpet_editor_assets(): void {
	$google = mpet_google_fonts_url();
	if ( $google ) {
		wp_enqueue_style( 'mpet-editor-fonts', $google, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	wp_register_style( 'mpet-editor-vars', false, array(), MPET_VERSION );
	wp_enqueue_style( 'mpet-editor-vars' );
	wp_add_inline_style( 'mpet-editor-vars', mpet_dynamic_css( '.editor-styles-wrapper' ) );
}

/**
 * Excerpt length and "read more".
 */
add_filter(
	'excerpt_length',
	static function ( $length ) {
		return is_admin() ? $length : max( 0, (int) mpet_opt( 'excerpt_length' ) );
	}
);
add_filter(
	'excerpt_more',
	static function () {
		return is_admin() ? ' [&hellip;]' : '&hellip;';
	}
);

/**
 * Page templates offered in the editor. They don't need files: layout.php reads them.
 */
add_filter(
	'theme_page_templates',
	static function ( $templates ) {
		$templates['page-game']     = __( 'Game (wide, no title)', 'mafia-pbbg-engine-theme' );
		$templates['mpet-full-width'] = __( 'Full width (page builders)', 'mafia-pbbg-engine-theme' );
		$templates['mpet-narrow']    = __( 'Narrow', 'mafia-pbbg-engine-theme' );
		return $templates;
	}
);
