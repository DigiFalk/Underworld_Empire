<?php
/**
 * Site footer (built with Appearance → Customize → Footer → Footer builder).
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- .mpet-content -->
<?php
if ( mpet_show_footer() ) {
	mpet_render_footer();
}
?>
</div><!-- .mpet-site -->
<?php if ( mpet_opt( 'scroll_top' ) ) : ?>
	<a href="#" class="mpet-scroll-top mpet-scroll-top--<?php echo esc_attr( (string) mpet_opt( 'scroll_top_position' ) ); ?>" aria-label="<?php esc_attr_e( 'Scroll to top', 'mafia-pbbg-engine-theme' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 7.6 4.7 14.9l1.4 1.4L12 10.4l5.9 5.9 1.4-1.4z"/></svg>
	</a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
