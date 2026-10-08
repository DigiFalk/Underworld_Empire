<?php
/**
 * Page content.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( mpet_is_game_page() ? 'mpet-page mpet-page--game' : 'mpet-card mpet-page' ); ?>>
	<?php if ( mpet_show_title() ) : ?>
		<header class="mpet-entry__header"><?php the_title( '<h1 class="mpet-page-title">', '</h1>' ); ?></header>
	<?php endif; ?>
	<?php mpet_featured_image(); ?>
	<div class="mpet-entry__content">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div>
</article>
