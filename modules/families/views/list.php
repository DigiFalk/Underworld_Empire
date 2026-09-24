<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array|null                     $family
 * @var array                          $families
 * @var array                          $invites
 * @var int                            $cost
 * @var array                          $free
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Locations;
use DigiFalk\MaffiaGame\Modules\Families;

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $family ) : ?>
	<p><a class="dfmg-button" href="<?php echo esc_url( $this->url( array( 'view' => 'home' ) ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: family */ __( 'Go to %s', 'wp-maffia-game' ), $family['name'] ) ); ?></a></p>
<?php else : ?>
	<?php if ( $invites ) : ?>
		<div class="dfmg-card">
			<h3><?php esc_html_e( 'Invitations', 'wp-maffia-game' ); ?></h3>
			<table class="dfmg-table">
				<?php foreach ( $invites as $dfmg_inv ) : ?>
					<?php $dfmg_f = Families::get( (int) $dfmg_inv['family_id'] ); ?>
					<?php if ( ! $dfmg_f ) { continue; } ?>
					<tr>
						<td><strong><?php echo esc_html( $dfmg_f['name'] ); ?></strong> <small class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: %s: player */ __( 'via %s', 'wp-maffia-game' ), wp_strip_all_tags( Character::link_by_id( (int) $dfmg_inv['invited_by'] ) ) ) ); ?></small></td>
						<td class="dfmg-actions">
							<?php echo $this->button( 'accept', __( 'Accept', 'wp-maffia-game' ), array( 'invite' => $dfmg_inv['id'] ) ); // phpcs:ignore ?>
							<?php echo $this->button( 'decline', __( 'Decline', 'wp-maffia-game' ), array( 'invite' => $dfmg_inv['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	<?php endif; ?>

	<div class="dfmg-card">
		<h3><?php esc_html_e( 'Found a family', 'wp-maffia-game' ); ?></h3>
		<?php if ( ! $free ) : ?>
			<p><?php esc_html_e( 'Every city already has a family. Join one or wait for your chance.', 'wp-maffia-game' ); ?></p>
		<?php else : ?>
			<p class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: %s: money */ __( 'Cost: %s in cash.', 'wp-maffia-game' ), Format::money( $cost ) ) ); ?></p>
			<?php echo $this->form( 'create' ); // phpcs:ignore ?>
				<input type="text" name="name" minlength="3" maxlength="30" placeholder="<?php esc_attr_e( 'Family name', 'wp-maffia-game' ); ?>" required>
				<select name="location">
					<?php foreach ( $free as $dfmg_loc ) : ?>
						<option value="<?php echo esc_attr( $dfmg_loc['id'] ); ?>" <?php selected( (int) $dfmg_loc['id'], (int) $c->location_id ); ?>><?php echo esc_html( $dfmg_loc['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="dfmg-button"><?php esc_html_e( 'Found', 'wp-maffia-game' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
<?php endif; ?>

<h3><?php esc_html_e( 'Families', 'wp-maffia-game' ); ?></h3>
<?php if ( ! $families ) : ?>
	<?php echo UI::empty_state( __( 'There are no families yet.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Family', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'City', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Boss', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Members', 'wp-maffia-game' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $families as $dfmg_f ) : ?>
				<tr>
					<td><a href="<?php echo esc_url( $this->url( array( 'view' => 'family', 'id' => $dfmg_f['id'] ) ) ); ?>"><strong><?php echo esc_html( $dfmg_f['name'] ); ?></strong></a></td>
					<td><?php echo esc_html( Locations::name( (int) $dfmg_f['location_id'] ) ); ?></td>
					<td><?php echo Character::link_by_id( (int) $dfmg_f['boss_id'] ); // phpcs:ignore ?></td>
					<td><?php echo (int) $dfmg_f['members'] . ' / ' . (int) $this->capacity( $dfmg_f ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
