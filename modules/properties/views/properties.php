<?php
/**
 * @var \DigiFalk\MafiaGame\Character  $c
 * @var \DigiFalk\MafiaGame\Property[] $mine
 * @var array                           $all
 * @var array                           $types
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;
use DigiFalk\MafiaGame\Locations;

defined( 'ABSPATH' ) || exit;
?>
<h3><?php esc_html_e( 'Your properties', 'wp-mafia-game' ); ?></h3>
<?php if ( ! $mine ) : ?>
	<?php echo UI::empty_state( __( 'You don\'t own anything yet. Businesses without an owner can be bought on the spot.', 'wp-mafia-game' ) ); // phpcs:ignore ?>
<?php endif; ?>
<div class="dfmg-grid dfmg-grid--2">
	<?php foreach ( $mine as $dfmg_p ) : ?>
		<?php
		$dfmg_type   = $types[ $dfmg_p->type_key() ] ?? array();
		$dfmg_hidden = array(
			'type'     => $dfmg_p->type_key(),
			'location' => $dfmg_p->location_id(),
		);
		?>
		<section class="dfmg-card">
			<h4><?php echo esc_html( $dfmg_p->label() . ' – ' . Locations::name( $dfmg_p->location_id() ) ); ?></h4>
			<p><?php esc_html_e( 'Profit since takeover:', 'wp-mafia-game' ); ?> <strong><?php echo esc_html( Format::money( $dfmg_p->profit() ) ); ?></strong></p>
			<?php if ( ! empty( $dfmg_type['setting_label'] ) ) : ?>
				<?php echo $this->form( 'price', $dfmg_hidden ); // phpcs:ignore ?>
					<label><?php echo esc_html( $dfmg_type['setting_label'] ); ?>
						<input type="text" inputmode="numeric" name="price" value="<?php echo esc_attr( (string) $dfmg_p->price() ); ?>">
					</label>
					<button type="submit" class="dfmg-button"><?php esc_html_e( 'Save', 'wp-mafia-game' ); ?></button>
				</form>
			<?php endif; ?>
			<?php echo $this->form( 'transfer', $dfmg_hidden ); // phpcs:ignore ?>
				<input type="text" name="to" placeholder="<?php esc_attr_e( 'Transfer to (name)', 'wp-mafia-game' ); ?>" required>
				<button type="submit" class="dfmg-button dfmg-button--ghost"><?php esc_html_e( 'Transfer', 'wp-mafia-game' ); ?></button>
			</form>
			<div class="dfmg-actions">
				<?php echo $this->button( 'reset', __( 'Reset profit', 'wp-mafia-game' ), $dfmg_hidden, 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
				<?php echo $this->button( 'drop', __( 'Give up', 'wp-mafia-game' ), $dfmg_hidden, 'dfmg-button dfmg-button--danger dfmg-button--small' ); // phpcs:ignore ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>

<h3><?php esc_html_e( 'Owners around the world', 'wp-mafia-game' ); ?></h3>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Business', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'City', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Owner', 'wp-mafia-game' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( Locations::all() as $dfmg_loc ) : ?>
			<?php foreach ( $types as $dfmg_key => $dfmg_type ) : ?>
				<?php
				$dfmg_owner = 0;
				foreach ( $all as $dfmg_row ) {
					if ( $dfmg_row['type'] === $dfmg_key && (int) $dfmg_row['location_id'] === (int) $dfmg_loc['id'] ) {
						$dfmg_owner = (int) $dfmg_row['owner_id'];
					}
				}
				$dfmg_owner_c = $dfmg_owner ? Character::find( $dfmg_owner ) : null;
				?>
				<tr>
					<td><?php echo esc_html( $dfmg_type['label'] ); ?></td>
					<td><?php echo esc_html( $dfmg_loc['name'] ); ?></td>
					<td><?php echo ( $dfmg_owner_c && $dfmg_owner_c->is_alive() ) ? $dfmg_owner_c->link() : '<em class="dfmg-muted">' . esc_html__( 'for sale', 'wp-mafia-game' ) . '</em>'; // phpcs:ignore ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</tbody>
</table>
