<?php
/**
 * Single post content.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'mpet-card mpet-single' ); ?>>
	<?php if ( mpet_show_title() ) : ?>
		<header class="mpet-entry__header">
			<?php the_title( '<h1 class="mpet-page-title">', '</h1>' ); ?>
			<?php mpet_post_meta(); ?>
		</header>
	<?php endif; ?>
	<?php mpet_featured_image(); ?>
	<div class="mpet-entry__content">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div>
	<?php if ( mpet_opt( 'single_tags' ) && get_the_tag_list() ) : ?>
		<footer class="mpet-entry__tags"><?php the_tags( '', ' ' ); ?></footer>
	<?php endif; ?>
</article>
