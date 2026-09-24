<?php
/**
 * First visit: choose a character name.
 *
 * @var array $messages
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome">
	<div class="dfmg-card">
		<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2><?php esc_html_e( 'Choose your gangster name', 'wp-maffia-game' ); ?></h2>
		<p><?php esc_html_e( 'The city will know you by this name. Choose well: it can\'t be changed.', 'wp-maffia-game' ); ?></p>
		<?php echo Game::form_open( 'core', 'create_character' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label for="dfmg-name"><?php esc_html_e( 'Name', 'wp-maffia-game' ); ?></label>
			<input type="text" id="dfmg-name" name="name" minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Start playing', 'wp-maffia-game' ); ?></button>
		</form>
	</div>
</div>
