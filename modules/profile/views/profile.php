<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var \DigiFalk\MaffiaGame\Character $target
 * @var bool                           $own
 * @var array                          $fields  Label => HTML.
 * @var array                          $actions HTML buttons.
 * @var string                         $avatar
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card dfmg-profile">
	<div class="dfmg-profile__head">
		<?php echo $avatar; // phpcs:ignore ?>
		<h3><?php echo esc_html( $target->name ); ?></h3>
	</div>
	<table class="dfmg-table dfmg-table--keyvalue">
		<?php foreach ( $fields as $dfmg_label => $dfmg_value ) : ?>
			<tr><th><?php echo esc_html( $dfmg_label ); ?></th><td><?php echo wp_kses_post( (string) $dfmg_value ); ?></td></tr>
		<?php endforeach; ?>
	</table>
	<?php if ( $actions ) : ?>
		<div class="dfmg-actions"><?php echo implode( ' ', $actions ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<?php if ( $target->bio ) : ?>
		<div class="dfmg-usertext"><?php echo Format::user_text( (string) $target->bio ); // phpcs:ignore ?></div>
	<?php endif; ?>
</div>
<?php if ( $own ) : ?>
	<div class="dfmg-card">
		<h3><?php esc_html_e( 'Edit profile text', 'wp-maffia-game' ); ?></h3>
		<?php echo $this->form( 'bio', array(), 'dfmg-form dfmg-form--stacked' ); // phpcs:ignore ?>
			<textarea name="bio" rows="6"><?php echo esc_textarea( (string) $target->bio ); ?></textarea>
			<p class="dfmg-muted"><?php esc_html_e( 'Allowed: bold, italic, links and lists. Your profile picture comes from Gravatar.', 'wp-maffia-game' ); ?></p>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Save', 'wp-maffia-game' ); ?></button>
		</form>
	</div>
<?php endif; ?>
