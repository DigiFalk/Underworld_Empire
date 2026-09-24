<?php
/**
 * Site footer.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

$uet_columns = (int) uet_opt( 'footer_widgets' );
?>
</div><!-- .uet-content -->
<?php if ( uet_show_footer() ) : ?>
	<footer class="uet-footer" id="colophon">
		<?php if ( $uet_columns ) : ?>
			<div class="uet-footer__widgets uet-container uet-cols-<?php echo (int) $uet_columns; ?>">
				<?php for ( $uet_i = 1; $uet_i <= $uet_columns; $uet_i++ ) : ?>
					<div class="uet-footer__col"><?php dynamic_sidebar( 'footer-' . $uet_i ); ?></div>
				<?php endfor; ?>
			</div>
		<?php endif; ?>
		<div class="uet-footer__bar">
			<div class="uet-container uet-footer__bar-inner uet-footer--<?php echo esc_attr( (string) uet_opt( 'footer_layout' ) ); ?>">
				<div class="uet-copyright"><?php echo uet_copyright(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location'  => 'footer',
							'container'       => 'nav',
							'container_class' => 'uet-footer__menu',
							'menu_class'      => 'uet-inline-menu',
							'depth'           => 1,
						)
					);
				}
				?>
			</div>
		</div>
	</footer>
<?php endif; ?>
</div><!-- .uet-site -->
<?php if ( uet_opt( 'scroll_top' ) ) : ?>
	<a href="#" class="uet-scroll-top uet-scroll-top--<?php echo esc_attr( (string) uet_opt( 'scroll_top_position' ) ); ?>" aria-label="<?php esc_attr_e( 'Scroll to top', 'underworld-empire-theme' ); ?>">
		<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 7.6 4.7 14.9l1.4 1.4L12 10.4l5.9 5.9 1.4-1.4z"/></svg>
	</a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
