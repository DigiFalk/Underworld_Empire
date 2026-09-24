<?php
/**
 * Shown to visitors that are not logged in.
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome dfmg-welcome--login">
	<div class="dfmg-card">
		<span class="dfmg-emblem"><?php echo \DigiFalk\UnderworldEmpire\Icons::svg( 'shield', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h2><?php esc_html_e( 'Welcome to the underworld', 'underworld-empire' ); ?></h2>
		<p><?php esc_html_e( 'Commit crimes, steal cars, build a family and work your way up to Godfather. Log in to play.', 'underworld-empire' ); ?></p>
		<?php
		wp_login_form(
			array(
				'redirect' => Game::page_url(),
				'form_id'  => 'dfmg-login',
			)
		);
		?>
		<p class="dfmg-links">
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				<a class="dfmg-button" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create account', 'underworld-empire' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_lostpassword_url( Game::page_url() ) ); ?>"><?php esc_html_e( 'Lost your password?', 'underworld-empire' ); ?></a>
		</p>
	</div>
	<?php do_action( 'dfmg_login_page' ); ?>
</div>
