<?php
/**
 * Post in the blog list / grid.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'mpet-card mpet-entry' ); ?>>
	<?php mpet_featured_image( 'medium_large' ); ?>
	<div class="mpet-entry__body">
		<?php the_title( '<h2 class="mpet-entry__title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
		<?php mpet_post_meta(); ?>
		<?php if ( (int) mpet_opt( 'excerpt_length' ) > 0 ) : ?>
			<div class="mpet-entry__excerpt"><?php the_excerpt(); ?></div>
		<?php endif; ?>
		<?php mpet_read_more(); ?>
	</div>
</article>
