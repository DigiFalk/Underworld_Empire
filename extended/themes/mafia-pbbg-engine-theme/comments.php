<?php
/**
 * Comments.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="mpet-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="mpet-comments__title">
			<?php
			/* translators: %d: number of comments */
			echo esc_html( sprintf( _n( '%d comment', '%d comments', (int) get_comments_number(), 'mafia-pbbg-engine-theme' ), (int) get_comments_number() ) );
			?>
		</h2>
		<ol class="mpet-comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="mpet-muted"><?php esc_html_e( 'Comments are closed.', 'mafia-pbbg-engine-theme' ); ?></p>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
