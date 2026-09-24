<?php
/**
 * Shown when the character has been murdered.
 *
 * @var \DigiFalk\MaffiaGame\Character      $character
 * @var \DigiFalk\MaffiaGame\Character|null $killer
 * @var array                               $messages
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome">
	<div class="dfmg-card dfmg-card--dead">
		<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2><?php esc_html_e( 'Rust in vrede', 'wp-maffia-game' ); ?></h2>
		<p>
			<?php
			if ( $killer ) {
				/* translators: 1: your character, 2: killer */
				printf( esc_html__( '%1$s is vermoord door %2$s.', 'wp-maffia-game' ), '<strong>' . esc_html( $character->name ) . '</strong>', $killer->link() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				/* translators: %s: your character */
				printf( esc_html__( '%s is niet meer onder ons.', 'wp-maffia-game' ), '<strong>' . esc_html( $character->name ) . '</strong>' );
			}
			?>
		</p>
		<p><?php esc_html_e( 'Je premium punten blijven bewaard. Begin opnieuw met een nieuw personage.', 'wp-maffia-game' ); ?></p>
		<?php echo Game::form_open( 'core', 'create_character' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label for="dfmg-name"><?php esc_html_e( 'Nieuwe naam', 'wp-maffia-game' ); ?></label>
			<input type="text" id="dfmg-name" name="name" minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Opnieuw beginnen', 'wp-maffia-game' ); ?></button>
		</form>
	</div>
</div>
