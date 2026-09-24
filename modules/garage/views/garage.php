<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $cars
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;
use DigiFalk\MafiaGame\Locations;

defined( 'ABSPATH' ) || exit;

if ( ! $cars ) {
	echo UI::empty_state( __( 'Your garage is empty. Time to steal a car?', 'wp-mafia-game' ) ); // phpcs:ignore
	return;
}
?>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Car', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Damage', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'Value', 'wp-mafia-game' ); ?></th>
			<th><?php esc_html_e( 'City', 'wp-mafia-game' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $cars as $dfmg_car ) : ?>
			<?php $dfmg_here = (int) $dfmg_car['location_id'] === (int) $c->location_id; ?>
			<tr>
				<td><strong><?php echo esc_html( $dfmg_car['name'] ); ?></strong></td>
				<td><?php echo esc_html( $dfmg_car['damage'] . '%' ); ?></td>
				<td><?php echo esc_html( Format::money( $dfmg_car['worth'] ) ); ?></td>
				<td><?php echo esc_html( Locations::name( (int) $dfmg_car['location_id'] ) ); ?></td>
				<td class="dfmg-actions">
					<?php if ( $dfmg_here ) : ?>
						<?php echo $this->button( 'sell', __( 'Sell', 'wp-mafia-game' ), array( 'car' => $dfmg_car['id'] ) ); // phpcs:ignore ?>
						<?php echo $this->button( 'crush', sprintf( /* translators: %s: bullets */ __( 'Crush (%s bullets)', 'wp-mafia-game' ), Format::number( $dfmg_car['bullets'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php if ( $dfmg_car['damage'] ) : ?>
							<?php echo $this->button( 'repair', sprintf( /* translators: %s: cost */ __( 'Repair (%s)', 'wp-mafia-game' ), Format::money( $dfmg_car['repair_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php endif; ?>
					<?php else : ?>
						<?php echo $this->button( 'ship', sprintf( /* translators: %s: cost */ __( 'Ship here (%s)', 'wp-mafia-game' ), Format::money( $dfmg_car['ship_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
