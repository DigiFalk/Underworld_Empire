<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $crimes
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'crime' ) ) {
	echo UI::cooldown( __( 'You can commit a crime again in', 'underworld-empire' ), $c->timer( 'crime' ) ); // phpcs:ignore
}
?>
<?php if ( ! $crimes ) : ?>
	<?php echo UI::empty_state( __( 'There are no crimes available yet.', 'underworld-empire' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Crime', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Loot', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Cooldown', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Chance', 'underworld-empire' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $crimes as $dfmg_crime ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $dfmg_crime['name'] ); ?></strong>
						<?php if ( $dfmg_crime['description'] ) : ?>
							<br><small class="dfmg-muted"><?php echo esc_html( $dfmg_crime['description'] ); ?></small>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( Format::money( $dfmg_crime['min_money'] ) . ' – ' . Format::money( $dfmg_crime['max_money'] ) ); ?></td>
					<td><?php echo esc_html( Format::duration( (int) $dfmg_crime['cooldown'] ) ); ?></td>
					<td class="dfmg-col-bar"><?php echo UI::bar( (float) $dfmg_crime['skill'] ); // phpcs:ignore ?></td>
					<td><?php echo $this->button( 'commit', __( 'Commit', 'underworld-empire' ), array( 'crime' => $dfmg_crime['id'] ) ); // phpcs:ignore ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
