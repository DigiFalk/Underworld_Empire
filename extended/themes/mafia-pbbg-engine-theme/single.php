<?php
/**
 * Single posts.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
mpet_main_open();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/content', 'single' );
	mpet_author_box();
	if ( mpet_opt( 'post_navigation' ) ) {
		the_post_navigation(
			array(
				'prev_text' => '<span class="mpet-nav-label">' . esc_html__( 'Previous', 'mafia-pbbg-engine-theme' ) . '</span> %title',
				'next_text' => '<span class="mpet-nav-label">' . esc_html__( 'Next', 'mafia-pbbg-engine-theme' ) . '</span> %title',
			)
		);
	}
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
}

mpet_main_close();
get_footer();
