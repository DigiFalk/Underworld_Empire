<?php
/**
 * Page not found.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
uet_main_open();
?>
<section class="uet-card uet-404">
	<h1 class="uet-page-title"><?php esc_html_e( 'Page not found', 'underworld-empire-theme' ); ?></h1>
	<p><?php esc_html_e( 'This page doesn\'t exist. Try a search:', 'underworld-empire-theme' ); ?></p>
	<?php get_search_form(); ?>
</section>
<?php
uet_main_close();
get_footer();
