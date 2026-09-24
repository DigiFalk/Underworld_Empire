<?php
/**
 * Blog, archives and search results.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
uet_main_open();

if ( is_home() && ! is_front_page() ) {
	echo '<header class="uet-page-header"><h1 class="uet-page-title">' . esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ) . '</h1></header>';
} elseif ( is_search() ) {
	/* translators: %s: search terms */
	echo '<header class="uet-page-header"><h1 class="uet-page-title">' . esc_html( sprintf( __( 'Search results for "%s"', 'underworld-empire-theme' ), get_search_query() ) ) . '</h1></header>';
} elseif ( is_archive() ) {
	echo '<header class="uet-page-header">';
	the_archive_title( '<h1 class="uet-page-title">', '</h1>' );
	the_archive_description( '<div class="uet-archive-description">', '</div>' );
	echo '</header>';
}

if ( have_posts() ) {
	echo '<div class="uet-posts">';
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content' );
	}
	echo '</div>';
	the_posts_pagination(
		array(
			'prev_text' => __( 'Previous', 'underworld-empire-theme' ),
			'next_text' => __( 'Next', 'underworld-empire-theme' ),
		)
	);
} else {
	get_template_part( 'template-parts/content', 'none' );
}

uet_main_close();
get_footer();
