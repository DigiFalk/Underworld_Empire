<?php
/**
 * First visit: choose a character name.
 *
 * @var array $messages
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Frontend\Game;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-welcome dfmg-welcome--create-character">
	<div class="dfmg-card">
		<span class="dfmg-emblem"><?php echo \DigiFalk\UnderworldEmpire\Icons::svg( 'profile', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h2><?php esc_html_e( 'Choose your gangster name', 'underworld-empire' ); ?></h2>
		<p><?php esc_html_e( 'The city will know you by this name. Choose well: it can\'t be changed.', 'underworld-empire' ); ?></p>
		<?php echo Game::form_open( 'core', 'create_character' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<label for="dfmg-name"><?php esc_html_e( 'Name', 'underworld-empire' ); ?></label>
			<input type="text" id="dfmg-name" name="name" minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Start playing', 'underworld-empire' ); ?></button>
		</form>
	</div>
</div>
