<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $cars
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Locations;

defined( 'ABSPATH' ) || exit;

if ( ! $cars ) {
	echo UI::empty_state( __( 'Je garage is leeg. Tijd om een auto te stelen?', 'wp-maffia-game' ) ); // phpcs:ignore
	return;
}
?>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Auto', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Schade', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Waarde', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Stad', 'wp-maffia-game' ); ?></th>
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
						<?php echo $this->button( 'sell', __( 'Verkopen', 'wp-maffia-game' ), array( 'car' => $dfmg_car['id'] ) ); // phpcs:ignore ?>
						<?php echo $this->button( 'crush', sprintf( /* translators: %s: bullets */ __( 'Pers (%s kogels)', 'wp-maffia-game' ), Format::number( $dfmg_car['bullets'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php if ( $dfmg_car['damage'] ) : ?>
							<?php echo $this->button( 'repair', sprintf( /* translators: %s: cost */ __( 'Repareren (%s)', 'wp-maffia-game' ), Format::money( $dfmg_car['repair_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
						<?php endif; ?>
					<?php else : ?>
						<?php echo $this->button( 'ship', sprintf( /* translators: %s: cost */ __( 'Hierheen verschepen (%s)', 'wp-maffia-game' ), Format::money( $dfmg_car['ship_cost'] ) ), array( 'car' => $dfmg_car['id'] ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
