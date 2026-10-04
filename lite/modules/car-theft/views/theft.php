<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $spots
 * @var int                            $cooldown
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'theft' ) ) {
	echo UI::cooldown( __( 'You can steal a car again in', 'underworld-empire' ), $c->timer( 'theft' ) ); // phpcs:ignore
}
?>
<p class="dfmg-muted">
	<?php
	/* translators: %s: duration */
	printf( esc_html__( 'After every attempt you have to wait %s.', 'underworld-empire' ), esc_html( Format::duration( $cooldown ) ) );
	?>
</p>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Spot', 'underworld-empire' ); ?></th>
			<th><?php esc_html_e( 'Cars up to', 'underworld-empire' ); ?></th>
			<th><?php esc_html_e( 'Chance', 'underworld-empire' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $spots as $dfmg_spot ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $dfmg_spot['name'] ); ?></strong></td>
				<td><?php echo esc_html( Format::money( $dfmg_spot['max_value'] ) ); ?></td>
				<td class="dfmg-col-bar"><?php echo UI::bar( (float) min( 100, (int) $dfmg_spot['chance'] ) ); // phpcs:ignore ?></td>
				<td><?php echo $this->button( 'steal', __( 'Steal', 'underworld-empire' ), array( 'spot' => $dfmg_spot['id'] ) ); // phpcs:ignore ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
