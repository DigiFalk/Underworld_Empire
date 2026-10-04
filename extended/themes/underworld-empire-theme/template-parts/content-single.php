<?php
/**
 * Single post content.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'uet-card uet-single' ); ?>>
	<?php if ( uet_show_title() ) : ?>
		<header class="uet-entry__header">
			<?php the_title( '<h1 class="uet-page-title">', '</h1>' ); ?>
			<?php uet_post_meta(); ?>
		</header>
	<?php endif; ?>
	<?php uet_featured_image(); ?>
	<div class="uet-entry__content">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div>
	<?php if ( uet_opt( 'single_tags' ) && get_the_tag_list() ) : ?>
		<footer class="uet-entry__tags"><?php the_tags( '', ' ' ); ?></footer>
	<?php endif; ?>
</article>
