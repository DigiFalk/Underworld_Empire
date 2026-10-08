<?php
/**
 * Blog, archives and search results.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
mpet_main_open();

if ( is_home() && ! is_front_page() ) {
	echo '<header class="mpet-page-header"><h1 class="mpet-page-title">' . esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ) . '</h1></header>';
} elseif ( is_search() ) {
	/* translators: %s: search terms */
	echo '<header class="mpet-page-header"><h1 class="mpet-page-title">' . esc_html( sprintf( __( 'Search results for "%s"', 'mafia-pbbg-engine-theme' ), get_search_query() ) ) . '</h1></header>';
} elseif ( is_archive() ) {
	echo '<header class="mpet-page-header">';
	the_archive_title( '<h1 class="mpet-page-title">', '</h1>' );
	the_archive_description( '<div class="mpet-archive-description">', '</div>' );
	echo '</header>';
}

if ( have_posts() ) {
	echo '<div class="mpet-posts">';
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content' );
	}
	echo '</div>';
	the_posts_pagination(
		array(
			'prev_text' => __( 'Previous', 'mafia-pbbg-engine-theme' ),
			'next_text' => __( 'Next', 'mafia-pbbg-engine-theme' ),
		)
	);
} else {
	get_template_part( 'template-parts/content', 'none' );
}

mpet_main_close();
get_footer();
