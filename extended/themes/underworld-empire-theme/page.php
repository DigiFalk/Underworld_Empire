<?php
/**
 * Pages.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
uet_main_open();

while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/content', 'page' );
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
}

uet_main_close();
get_footer();
