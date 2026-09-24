<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $destinations
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'travel' ) ) {
	echo UI::cooldown( __( 'Je kunt weer vliegen over', 'wp-maffia-game' ), $c->timer( 'travel' ) ); // phpcs:ignore
}
?>
<p>
	<?php
	/* translators: %s: city */
	printf( esc_html__( 'Je bent nu in %s. Waar wil je heen?', 'wp-maffia-game' ), '<strong>' . esc_html( $c->location_name() ) . '</strong>' );
	?>
</p>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Bestemming', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Ticket', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Wachttijd na aankomst', 'wp-maffia-game' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $destinations as $dfmg_dest ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $dfmg_dest['name'] ); ?></strong></td>
				<td><?php echo esc_html( Format::money( $dfmg_dest['travel_cost'] ) ); ?></td>
				<td><?php echo esc_html( Format::duration( (int) $dfmg_dest['travel_time'] ) ); ?></td>
				<td><?php echo $this->button( 'fly', __( 'Vliegen', 'wp-maffia-game' ), array( 'to' => $dfmg_dest['id'] ) ); // phpcs:ignore ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
