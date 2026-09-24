<?php
/**
 * Shown to visitors that are not logged in.
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome">
	<div class="dfmg-card">
		<h2><?php esc_html_e( 'Welkom in de onderwereld', 'wp-maffia-game' ); ?></h2>
		<p><?php esc_html_e( 'Pleeg misdaden, steel auto\'s, bouw een familie op en werk je omhoog tot Peetvader. Log in om te spelen.', 'wp-maffia-game' ); ?></p>
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
				<a class="dfmg-button" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Account aanmaken', 'wp-maffia-game' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_lostpassword_url( Game::page_url() ) ); ?>"><?php esc_html_e( 'Wachtwoord vergeten?', 'wp-maffia-game' ); ?></a>
		</p>
	</div>
	<?php do_action( 'dfmg_login_page' ); ?>
</div>
