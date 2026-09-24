<?php
/**
 * Post in the blog list / grid.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'uet-card uet-entry' ); ?>>
	<?php uet_featured_image( 'medium_large' ); ?>
	<div class="uet-entry__body">
		<?php the_title( '<h2 class="uet-entry__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
		<?php uet_post_meta(); ?>
		<?php if ( (int) uet_opt( 'excerpt_length' ) > 0 ) : ?>
			<div class="uet-entry__excerpt"><?php the_excerpt(); ?></div>
		<?php endif; ?>
		<?php uet_read_more(); ?>
	</div>
</article>
