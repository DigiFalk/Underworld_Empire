<?php
/**
 * Shown to visitors that are not logged in.
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome">
	<div class="dfmg-card">
		<h2><?php esc_html_e( 'Welcome to the underworld', 'wp-mafia-game' ); ?></h2>
		<p><?php esc_html_e( 'Commit crimes, steal cars, build a family and work your way up to Godfather. Log in to play.', 'wp-mafia-game' ); ?></p>
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
				<a class="dfmg-button" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create account', 'wp-mafia-game' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_lostpassword_url( Game::page_url() ) ); ?>"><?php esc_html_e( 'Lost your password?', 'wp-mafia-game' ); ?></a>
		</p>
	</div>
	<?php do_action( 'dfmg_login_page' ); ?>
</div>
