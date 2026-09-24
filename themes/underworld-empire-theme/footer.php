<?php
/**
 * Site footer (built with Appearance → Customize → Footer → Footer builder).
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;
?>
</div><!-- .uet-content -->
<?php
if ( uet_show_footer() ) {
	uet_render_footer();
}
?>
</div><!-- .uet-site -->
<?php if ( uet_opt( 'scroll_top' ) ) : ?>
	<a href="#" class="uet-scroll-top uet-scroll-top--<?php echo esc_attr( (string) uet_opt( 'scroll_top_position' ) ); ?>" aria-label="<?php esc_attr_e( 'Scroll to top', 'underworld-empire-theme' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 7.6 4.7 14.9l1.4 1.4L12 10.4l5.9 5.9 1.4-1.4z"/></svg>
	</a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
