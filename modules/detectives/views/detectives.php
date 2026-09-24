<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $reports
 * @var int                            $cost
 * @var int                            $max
 * @var int                            $hour
 * @var int                            $valid
 * @var string                         $target
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;
use DigiFalk\MafiaGame\Locations;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card">
	<h3><?php esc_html_e( 'Hire detectives', 'wp-mafia-game' ); ?></h3>
	<p class="dfmg-muted">
		<?php
		/* translators: 1: cost, 2: duration */
		printf( esc_html__( 'A detective costs %1$s per search hour (%2$s). More detectives and more hours give a better chance of success.', 'wp-mafia-game' ), esc_html( Format::money( $cost ) ), esc_html( Format::duration( $hour ) ) );
		?>
	</p>
	<?php echo $this->form( 'hire' ); // phpcs:ignore ?>
		<input type="text" name="target" value="<?php echo esc_attr( $target ); ?>" placeholder="<?php esc_attr_e( 'Player name', 'wp-mafia-game' ); ?>" required>
		<label><?php esc_html_e( 'Detectives', 'wp-mafia-game' ); ?>
			<select name="detectives"><?php for ( $dfmg_i = 1; $dfmg_i <= $max; $dfmg_i++ ) : ?><option><?php echo (int) $dfmg_i; ?></option><?php endfor; ?></select>
		</label>
		<label><?php esc_html_e( 'Hours', 'wp-mafia-game' ); ?>
			<select name="hours"><?php for ( $dfmg_i = 1; $dfmg_i <= $max; $dfmg_i++ ) : ?><option><?php echo (int) $dfmg_i; ?></option><?php endfor; ?></select>
		</label>
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Hire', 'wp-mafia-game' ); ?></button>
	</form>
</div>

<h3><?php esc_html_e( 'Reports', 'wp-mafia-game' ); ?></h3>
<?php if ( ! $reports ) : ?>
	<?php echo UI::empty_state( __( 'You haven\'t hired any detectives yet.', 'wp-mafia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Target', 'wp-mafia-game' ); ?></th>
				<th><?php esc_html_e( 'Bet', 'wp-mafia-game' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-mafia-game' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $reports as $dfmg_r ) : ?>
				<tr>
					<td><?php echo Character::link_by_id( (int) $dfmg_r['target_id'] ); // phpcs:ignore ?></td>
					<td>
						<?php
						/* translators: 1: detectives, 2: hours */
						printf( esc_html__( '%1$d detectives, %2$d hours', 'wp-mafia-game' ), (int) $dfmg_r['detectives'], (int) $dfmg_r['hours'] );
						?>
					</td>
					<td>
						<?php
						if ( (int) $dfmg_r['ready_at'] > time() ) {
							echo esc_html__( 'Still searching…', 'wp-mafia-game' ) . ' ' . Format::countdown( (int) $dfmg_r['ready_at'] ); // phpcs:ignore
						} elseif ( ! (int) $dfmg_r['success'] || (int) $dfmg_r['found_location'] < 1 ) {
							esc_html_e( 'Not found', 'wp-mafia-game' );
						} elseif ( (int) $dfmg_r['used'] ) {
							esc_html_e( 'Used', 'wp-mafia-game' );
						} elseif ( (int) $dfmg_r['ready_at'] + $valid < time() ) {
							esc_html_e( 'Expired', 'wp-mafia-game' );
						} else {
							/* translators: %s: city */
							echo '<strong>' . esc_html( sprintf( __( 'Found in %s', 'wp-mafia-game' ), Locations::name( (int) $dfmg_r['found_location'] ) ) ) . '</strong> ';
							echo '<small>' . esc_html__( 'still valid', 'wp-mafia-game' ) . ' ' . Format::countdown( (int) $dfmg_r['ready_at'] + $valid ) . '</small>'; // phpcs:ignore
						}
						?>
					</td>
					<td><?php echo $this->button( 'remove', __( 'Remove', 'wp-mafia-game' ), array( 'report' => $dfmg_r['id'] ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
