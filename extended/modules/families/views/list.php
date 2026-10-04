<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array|null                     $family
 * @var array                          $families
 * @var array                          $invites
 * @var int                            $cost
 * @var array                          $free
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Locations;
use DigiFalk\UnderworldEmpire\Modules\Families;

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $family ) : ?>
	<p><a class="dfmg-button" href="<?php echo esc_url( $this->url( array( 'view' => 'home' ) ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: family */ __( 'Go to %s', 'underworld-empire' ), $family['name'] ) ); ?></a></p>
<?php else : ?>
	<?php if ( $invites ) : ?>
		<div class="dfmg-card">
			<h3><?php esc_html_e( 'Invitations', 'underworld-empire' ); ?></h3>
			<table class="dfmg-table">
				<?php foreach ( $invites as $dfmg_inv ) : ?>
					<?php $dfmg_f = Families::get( (int) $dfmg_inv['family_id'] ); ?>
					<?php if ( ! $dfmg_f ) { continue; } ?>
					<tr>
						<td><strong><?php echo esc_html( $dfmg_f['name'] ); ?></strong> <small class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: %s: player */ __( 'via %s', 'underworld-empire' ), wp_strip_all_tags( Character::link_by_id( (int) $dfmg_inv['invited_by'] ) ) ) ); ?></small></td>
						<td class="dfmg-actions">
							<?php echo $this->button( 'accept', __( 'Accept', 'underworld-empire' ), array( 'invite' => $dfmg_inv['id'] ) ); // phpcs:ignore ?>
							<?php echo $this->button( 'decline', __( 'Decline', 'underworld-empire' ), array( 'invite' => $dfmg_inv['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	<?php endif; ?>

	<div class="dfmg-card">
		<h3><?php esc_html_e( 'Found a family', 'underworld-empire' ); ?></h3>
		<?php if ( ! $free ) : ?>
			<p><?php esc_html_e( 'Every city already has a family. Join one or wait for your chance.', 'underworld-empire' ); ?></p>
		<?php else : ?>
			<p class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: %s: money */ __( 'Cost: %s in cash.', 'underworld-empire' ), Format::money( $cost ) ) ); ?></p>
			<?php echo $this->form( 'create' ); // phpcs:ignore ?>
				<input type="text" name="name" minlength="3" maxlength="30" placeholder="<?php esc_attr_e( 'Family name', 'underworld-empire' ); ?>" required>
				<select name="location">
					<?php foreach ( $free as $dfmg_loc ) : ?>
						<option value="<?php echo esc_attr( $dfmg_loc['id'] ); ?>" <?php selected( (int) $dfmg_loc['id'], (int) $c->location_id ); ?>><?php echo esc_html( $dfmg_loc['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="dfmg-button"><?php esc_html_e( 'Found', 'underworld-empire' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
<?php endif; ?>

<h3><?php esc_html_e( 'Families', 'underworld-empire' ); ?></h3>
<?php if ( ! $families ) : ?>
	<?php echo UI::empty_state( __( 'There are no families yet.', 'underworld-empire' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Family', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'City', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Boss', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Members', 'underworld-empire' ); ?></th>
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
