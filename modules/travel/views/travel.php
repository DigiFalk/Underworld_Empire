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
	echo UI::cooldown( __( 'You can fly again in', 'wp-maffia-game' ), $c->timer( 'travel' ) ); // phpcs:ignore
}
?>
<p>
	<?php
	/* translators: %s: city */
	printf( esc_html__( 'You are in %s. Where do you want to go?', 'wp-maffia-game' ), '<strong>' . esc_html( $c->location_name() ) . '</strong>' );
	?>
</p>
<table class="dfmg-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Destination', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Ticket', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Cooldown after arrival', 'wp-maffia-game' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $destinations as $dfmg_dest ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $dfmg_dest['name'] ); ?></strong></td>
				<td><?php echo esc_html( Format::money( $dfmg_dest['travel_cost'] ) ); ?></td>
				<td><?php echo esc_html( Format::duration( (int) $dfmg_dest['travel_time'] ) ); ?></td>
				<td><?php echo $this->button( 'fly', __( 'Fly', 'wp-maffia-game' ), array( 'to' => $dfmg_dest['id'] ) ); // phpcs:ignore ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
