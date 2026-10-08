<?php
/**
 * Page not found.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
mpet_main_open();
?>
<section class="mpet-card mpet-404">
	<h1 class="mpet-page-title"><?php esc_html_e( 'Page not found', 'mafia-pbbg-engine-theme' ); ?></h1>
	<p><?php esc_html_e( 'This page doesn\'t exist. Try a search:', 'mafia-pbbg-engine-theme' ); ?></p>
	<?php get_search_form(); ?>
</section>
<?php
mpet_main_close();
get_footer();
