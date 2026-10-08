<?php
/**
 * Pages.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
mpet_main_open();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/content', 'page' );
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
}

mpet_main_close();
get_footer();
