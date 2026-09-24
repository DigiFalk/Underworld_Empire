<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $inmates
 * @var int                            $bail
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->is_jailed() ) {
	echo UI::cooldown( $c->is_in_supermax() ? __( 'You are in solitary confinement. Free in', 'wp-mafia-game' ) : __( 'You are in jail. Free in', 'wp-mafia-game' ), $c->timer( 'jail' ) ); // phpcs:ignore
	if ( $bail ) {
		echo '<p>' . $this->button( 'bail', sprintf( /* translators: %s: money */ __( 'Pay bail (%s)', 'wp-mafia-game' ), Format::money( $bail ) ) ) . '</p>'; // phpcs:ignore
	}
}
?>
<h3>
	<?php
	/* translators: %s: city */
	printf( esc_html__( 'Inmates in %s', 'wp-mafia-game' ), esc_html( $c->location_name() ) );
	?>
</h3>
<?php if ( ! $inmates ) : ?>
	<?php echo UI::empty_state( __( 'The cells are empty.', 'wp-mafia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Inmate', 'wp-mafia-game' ); ?></th>
				<th><?php esc_html_e( 'Rank', 'wp-mafia-game' ); ?></th>
				<th><?php esc_html_e( 'Free in', 'wp-mafia-game' ); ?></th>
				<th><?php esc_html_e( 'Chance', 'wp-mafia-game' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $inmates as $dfmg_row ) : ?>
				<?php $dfmg_inmate = $dfmg_row['character']; ?>
				<tr>
					<td><?php echo $dfmg_inmate->link(); // phpcs:ignore ?><?php echo $dfmg_row['supermax'] ? ' <em class="dfmg-tag">' . esc_html__( 'solitary', 'wp-mafia-game' ) . '</em>' : ''; ?></td>
					<td><?php echo esc_html( $dfmg_inmate->rank_name() ); ?></td>
					<td><?php echo Format::countdown( $dfmg_inmate->timer( 'jail' ) ); // phpcs:ignore ?></td>
					<td><?php echo esc_html( $dfmg_row['chance'] . '%' ); ?></td>
					<td>
						<?php if ( $dfmg_row['chance'] ) : ?>
							<?php echo $this->button( 'bust', $dfmg_inmate->id() === $c->id() ? __( 'Escape', 'wp-mafia-game' ) : __( 'Break out', 'wp-mafia-game' ), array( 'target' => $dfmg_inmate->id() ) ); // phpcs:ignore ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
