<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $inmates
 * @var int                            $bail
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->is_jailed() ) {
	echo UI::cooldown( $c->is_in_supermax() ? __( 'Je zit in de isoleercel. Vrij over', 'wp-maffia-game' ) : __( 'Je zit in de gevangenis. Vrij over', 'wp-maffia-game' ), $c->timer( 'jail' ) ); // phpcs:ignore
	if ( $bail ) {
		echo '<p>' . $this->button( 'bail', sprintf( /* translators: %s: money */ __( 'Borg betalen (%s)', 'wp-maffia-game' ), Format::money( $bail ) ) ) . '</p>'; // phpcs:ignore
	}
}
?>
<h3>
	<?php
	/* translators: %s: city */
	printf( esc_html__( 'Gevangenen in %s', 'wp-maffia-game' ), esc_html( $c->location_name() ) );
	?>
</h3>
<?php if ( ! $inmates ) : ?>
	<?php echo UI::empty_state( __( 'De cellen zijn leeg.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Gevangene', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Rang', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Vrij over', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Kans', 'wp-maffia-game' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $inmates as $dfmg_row ) : ?>
				<?php $dfmg_inmate = $dfmg_row['character']; ?>
				<tr>
					<td><?php echo $dfmg_inmate->link(); // phpcs:ignore ?><?php echo $dfmg_row['supermax'] ? ' <em class="dfmg-tag">' . esc_html__( 'isoleercel', 'wp-maffia-game' ) . '</em>' : ''; ?></td>
					<td><?php echo esc_html( $dfmg_inmate->rank_name() ); ?></td>
					<td><?php echo Format::countdown( $dfmg_inmate->timer( 'jail' ) ); // phpcs:ignore ?></td>
					<td><?php echo esc_html( $dfmg_row['chance'] . '%' ); ?></td>
					<td>
						<?php if ( $dfmg_row['chance'] ) : ?>
							<?php echo $this->button( 'bust', $dfmg_inmate->id() === $c->id() ? __( 'Ontsnappen', 'wp-maffia-game' ) : __( 'Uitbreken', 'wp-maffia-game' ), array( 'target' => $dfmg_inmate->id() ) ); // phpcs:ignore ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
