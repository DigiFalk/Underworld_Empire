<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $inmates
 * @var int                            $bail
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->is_jailed() ) {
	echo UI::cooldown( $c->is_in_supermax() ? __( 'You are in solitary confinement. Free in', 'mafia-pbbg-engine' ) : __( 'You are in jail. Free in', 'mafia-pbbg-engine' ), $c->timer( 'jail' ) ); // phpcs:ignore
	if ( $bail ) {
		echo '<p>' . $this->button( 'bail', sprintf( /* translators: %s: money */ __( 'Pay bail (%s)', 'mafia-pbbg-engine' ), Format::money( $bail ) ) ) . '</p>'; // phpcs:ignore
	}
}
?>
<h3>
	<?php
	/* translators: %s: city */
	printf( esc_html__( 'Inmates in %s', 'mafia-pbbg-engine' ), esc_html( $c->location_name() ) );
	?>
</h3>
<?php if ( ! $inmates ) : ?>
	<?php echo UI::empty_state( __( 'The cells are empty.', 'mafia-pbbg-engine' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Inmate', 'mafia-pbbg-engine' ); ?></th>
				<th><?php esc_html_e( 'Rank', 'mafia-pbbg-engine' ); ?></th>
				<th><?php esc_html_e( 'Free in', 'mafia-pbbg-engine' ); ?></th>
				<th><?php esc_html_e( 'Chance', 'mafia-pbbg-engine' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $inmates as $dfmg_row ) : ?>
				<?php $dfmg_inmate = $dfmg_row['character']; ?>
				<tr>
					<td><?php echo $dfmg_inmate->link(); // phpcs:ignore ?><?php echo $dfmg_row['supermax'] ? ' <em class="dfmg-tag">' . esc_html__( 'solitary', 'mafia-pbbg-engine' ) . '</em>' : ''; ?></td>
					<td><?php echo esc_html( $dfmg_inmate->rank_name() ); ?></td>
					<td><?php echo Format::countdown( $dfmg_inmate->timer( 'jail' ) ); // phpcs:ignore ?></td>
					<td><?php echo esc_html( $dfmg_row['chance'] . '%' ); ?></td>
					<td>
						<?php if ( $dfmg_row['chance'] ) : ?>
							<?php echo $this->button( 'bust', $dfmg_inmate->id() === $c->id() ? __( 'Escape', 'mafia-pbbg-engine' ) : __( 'Break out', 'mafia-pbbg-engine' ), array( 'target' => $dfmg_inmate->id() ) ); // phpcs:ignore ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
