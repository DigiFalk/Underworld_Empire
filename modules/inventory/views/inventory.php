<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $slots
 * @var array                          $equipped
 * @var array                          $items
 * @var int                            $sell_pct
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Items;

defined( 'ABSPATH' ) || exit;
?>
<h3><?php esc_html_e( 'Equipment', 'underworld-empire' ); ?></h3>
<div class="dfmg-grid dfmg-grid--3">
	<?php foreach ( $slots as $dfmg_key => $dfmg_slot ) : ?>
		<?php $dfmg_item = $equipped[ $dfmg_key ] ?? null; ?>
		<div class="dfmg-card dfmg-slot">
			<h4><?php echo esc_html( $dfmg_slot['label'] ); ?></h4>
			<?php if ( $dfmg_item ) : ?>
				<p><strong><?php echo esc_html( $dfmg_item['name'] ); ?></strong></p>
				<ul class="dfmg-effects">
					<?php foreach ( Items::describe_effects( $dfmg_item ) as $dfmg_line ) : ?>
						<li><?php echo esc_html( $dfmg_line ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php echo $this->button( 'unequip', __( 'Unequip', 'underworld-empire' ), array( 'slot' => $dfmg_key ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
			<?php else : ?>
				<p class="dfmg-muted"><?php esc_html_e( 'Empty', 'underworld-empire' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
<p class="dfmg-muted">
	<?php
	/* translators: 1: attack, 2: defense, 3: health */
	printf( esc_html__( 'Attack %1$s · Defense %2$s · Max. health %3$s', 'underworld-empire' ), esc_html( (string) round( $c->attack_power() ) ), esc_html( (string) round( $c->defense_power() ) ), esc_html( Format::number( $c->max_health() ) ) );
	?>
</p>

<h3><?php esc_html_e( 'Belongings', 'underworld-empire' ); ?></h3>
<?php if ( ! $items ) : ?>
	<?php echo UI::empty_state( __( 'Your inventory is empty.', 'underworld-empire' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Item', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Amount', 'underworld-empire' ); ?></th>
				<th><?php esc_html_e( 'Effect', 'underworld-empire' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $dfmg_item ) : ?>
				<?php $dfmg_usage = Items::usage( $dfmg_item ); ?>
				<tr>
					<td><strong><?php echo esc_html( $dfmg_item['name'] ); ?></strong></td>
					<td><?php echo esc_html( Format::number( $dfmg_item['qty'] ) ); ?></td>
					<td><small><?php echo esc_html( implode( ', ', Items::describe_effects( $dfmg_item ) ) ); ?></small></td>
					<td class="dfmg-actions">
						<?php if ( 'equip' === $dfmg_usage ) : ?>
							<?php echo $this->button( 'equip', __( 'Equip', 'underworld-empire' ), array( 'item' => $dfmg_item['id'] ) ); // phpcs:ignore ?>
						<?php elseif ( 'use' === $dfmg_usage ) : ?>
							<?php echo $this->button( 'use', __( 'Use', 'underworld-empire' ), array( 'item' => $dfmg_item['id'] ) ); // phpcs:ignore ?>
						<?php endif; ?>
						<?php echo $this->button( 'sell', sprintf( /* translators: %s: money */ __( 'Sell (%s)', 'underworld-empire' ), Format::money( floor( $dfmg_item['price'] * $sell_pct / 100 ) ) ), array( 'item' => $dfmg_item['id'] ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
