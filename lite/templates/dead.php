<?php
/**
 * Shown when the character has been murdered.
 *
 * @var \DigiFalk\UnderworldEmpire\Character      $character
 * @var \DigiFalk\UnderworldEmpire\Character|null $killer
 * @var array                               $messages
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome dfmg-welcome--dead">
	<div class="dfmg-card dfmg-card--dead">
		<span class="dfmg-emblem"><?php echo \DigiFalk\UnderworldEmpire\Icons::svg( 'murder', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2><?php esc_html_e( 'Rest in peace', 'mafia-pbbg-engine' ); ?></h2>
		<p>
			<?php
			if ( $killer ) {
				/* translators: 1: your character, 2: killer */
				printf( esc_html__( '%1$s was murdered by %2$s.', 'mafia-pbbg-engine' ), '<strong>' . esc_html( $character->name ) . '</strong>', $killer->link() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				/* translators: %s: your character */
				printf( esc_html__( '%s is no longer with us.', 'mafia-pbbg-engine' ), '<strong>' . esc_html( $character->name ) . '</strong>' );
			}
			?>
		</p>
		<p><?php esc_html_e( 'Start again with a new character.', 'mafia-pbbg-engine' ); ?></p>
		<?php echo Game::form_open( 'core', 'create_character' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label for="dfmg-name"><?php esc_html_e( 'New name', 'mafia-pbbg-engine' ); ?></label>
			<input type="text" id="dfmg-name" name="name" minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Start over', 'mafia-pbbg-engine' ); ?></button>
		</form>
	</div>
</div>
