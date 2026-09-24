<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $spots
 * @var int                            $cooldown
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'theft' ) ) {
	echo UI::cooldown( __( 'Je kunt weer een auto stelen over', 'wp-maffia-game' ), $c->timer( 'theft' ) ); // phpcs:ignore
}
?>
<p class="dfmg-muted">
	<?php
	/* translators: %s: duration */
	printf( esc_html__( 'Na elke poging moet je %s wachten.', 'wp-maffia-game' ), esc_html( Format::duration( $cooldown ) ) );
	?>
</p>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Plek', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Auto\'s tot', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Kans', 'wp-maffia-game' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $spots as $dfmg_spot ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $dfmg_spot['name'] ); ?></strong></td>
				<td><?php echo esc_html( Format::money( $dfmg_spot['max_value'] ) ); ?></td>
				<td class="dfmg-col-bar"><?php echo UI::bar( (float) min( 100, (int) $dfmg_spot['chance'] ) ); // phpcs:ignore ?></td>
				<td><?php echo $this->button( 'steal', __( 'Stelen', 'wp-maffia-game' ), array( 'spot' => $dfmg_spot['id'] ) ); // phpcs:ignore ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
