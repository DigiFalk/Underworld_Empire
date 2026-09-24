<?php
/**
 * Page content.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( uet_is_game_page() ? 'uet-page uet-page--game' : 'uet-card uet-page' ); ?>>
	<?php if ( uet_show_title() ) : ?>
		<header class="uet-entry__header"><?php the_title( '<h1 class="uet-page-title">', '</h1>' ); ?></header>
	<?php endif; ?>
	<?php uet_featured_image(); ?>
	<div class="uet-entry__content">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div>
</article>
