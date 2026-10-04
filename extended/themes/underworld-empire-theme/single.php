<?php
/**
 * Single posts.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
uet_main_open();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/content', 'single' );
	uet_author_box();
	if ( uet_opt( 'post_navigation' ) ) {
		the_post_navigation(
			array(
				'prev_text' => '<span class="uet-nav-label">' . esc_html__( 'Previous', 'underworld-empire-theme' ) . '</span> %title',
				'next_text' => '<span class="uet-nav-label">' . esc_html__( 'Next', 'underworld-empire-theme' ) . '</span> %title',
			)
		);
	}
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
}

uet_main_close();
get_footer();
