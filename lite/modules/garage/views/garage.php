<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $cars
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Locations;

defined( 'ABSPATH' ) || exit;

if ( ! $cars ) {
	echo UI::empty_state( __( 'Your garage is empty. Time to steal a car?', 'underworld-empire' ) ); // phpcs:ignore
	return;
}
?>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Car', 'underworld-empire' ); ?></th>
			<th><?php esc_html_e( 'Damage', 'underworld-empire' ); ?></th>
			<th><?php esc_html_e( 'Value', 'underworld-empire' ); ?></th>
			<th><?php esc_html_e( 'City', 'underworld-empire' ); ?></th>
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
						<?php echo $this->button( 'sell', __( 'Sell', 'underworld-empire' ), array( 'car' => $dfmg_car['id'] ) ); // phpcs:ignore ?>
						<?php echo $this->button( 'crush', sprintf( /* translators: %s: bullets */ __( 'Crush (%s bullets)', 'underworld-empire' ), Format::number( $dfmg_car['bullets'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php if ( $dfmg_car['damage'] ) : ?>
							<?php echo $this->button( 'repair', sprintf( /* translators: %s: cost */ __( 'Repair (%s)', 'underworld-empire' ), Format::money( $dfmg_car['repair_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php endif; ?>
					<?php else : ?>
						<?php echo $this->button( 'ship', sprintf( /* translators: %s: cost */ __( 'Ship here (%s)', 'underworld-empire' ), Format::money( $dfmg_car['ship_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
