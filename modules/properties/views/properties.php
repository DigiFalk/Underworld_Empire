<?php
/**
 * @var \DigiFalk\MaffiaGame\Character  $c
 * @var \DigiFalk\MaffiaGame\Property[] $mine
 * @var array                           $all
 * @var array                           $types
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Locations;

defined( 'ABSPATH' ) || exit;
?>
<h3><?php esc_html_e( 'Jouw bezittingen', 'wp-maffia-game' ); ?></h3>
<?php if ( ! $mine ) : ?>
	<?php echo UI::empty_state( __( 'Je bezit nog niets. Bedrijven zonder eigenaar kun je ter plekke kopen.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
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
			<p><?php esc_html_e( 'Winst sinds overname:', 'wp-maffia-game' ); ?> <strong><?php echo esc_html( Format::money( $dfmg_p->profit() ) ); ?></strong></p>
			<?php if ( ! empty( $dfmg_type['setting_label'] ) ) : ?>
				<?php echo $this->form( 'price', $dfmg_hidden ); // phpcs:ignore ?>
					<label><?php echo esc_html( $dfmg_type['setting_label'] ); ?>
						<input type="text" inputmode="numeric" name="price" value="<?php echo esc_attr( (string) $dfmg_p->price() ); ?>">
					</label>
					<button type="submit" class="dfmg-button"><?php esc_html_e( 'Opslaan', 'wp-maffia-game' ); ?></button>
				</form>
			<?php endif; ?>
			<?php echo $this->form( 'transfer', $dfmg_hidden ); // phpcs:ignore ?>
				<input type="text" name="to" placeholder="<?php esc_attr_e( 'Overdragen aan (naam)', 'wp-maffia-game' ); ?>" required>
				<button type="submit" class="dfmg-button dfmg-button--ghost"><?php esc_html_e( 'Overdragen', 'wp-maffia-game' ); ?></button>
			</form>
			<div class="dfmg-actions">
				<?php echo $this->button( 'reset', __( 'Winst op nul', 'wp-maffia-game' ), $dfmg_hidden, 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
				<?php echo $this->button( 'drop', __( 'Opgeven', 'wp-maffia-game' ), $dfmg_hidden, 'dfmg-button dfmg-button--danger dfmg-button--small' ); // phpcs:ignore ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>

<h3><?php esc_html_e( 'Eigenaren in de wereld', 'wp-maffia-game' ); ?></h3>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Bedrijf', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Stad', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Eigenaar', 'wp-maffia-game' ); ?></th>
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
					<td><?php echo ( $dfmg_owner_c && $dfmg_owner_c->is_alive() ) ? $dfmg_owner_c->link() : '<em class="dfmg-muted">' . esc_html__( 'te koop', 'wp-maffia-game' ) . '</em>'; // phpcs:ignore ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</tbody>
</table>
