<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $list
 * @var array                          $mine
 * @var int                            $on_me
 * @var int                            $buyoff
 * @var int                            $min
 * @var int                            $refund
 * @var string                         $target
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $on_me ) : ?>
	<div class="dfmg-alert dfmg-alert--error">
		<?php
		/* translators: %s: money */
		printf( esc_html__( 'There is %s on your head!', 'underworld-empire' ), '<strong>' . esc_html( Format::money( $on_me ) ) . '</strong>' );
		?>
		<?php echo $this->button( 'buyoff', sprintf( /* translators: %s: money */ __( 'Buy yourself free for %s', 'underworld-empire' ), Format::money( $buyoff ) ) ); // phpcs:ignore ?>
	</div>
<?php endif; ?>

<div class="dfmg-grid dfmg-grid--2">
	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Place a bounty', 'underworld-empire' ); ?></h3>
		<?php echo $this->form( 'place' ); // phpcs:ignore ?>
			<input type="text" name="target" value="<?php echo esc_attr( $target ); ?>" placeholder="<?php esc_attr_e( 'Player name', 'underworld-empire' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: money */ __( 'Min. %s', 'underworld-empire' ), Format::money( $min ) ) ); ?>" required>
			<label><input type="checkbox" name="anonymous" value="1"> <?php esc_html_e( 'Anonymous', 'underworld-empire' ); ?></label>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Place', 'underworld-empire' ); ?></button>
		</form>

		<?php if ( $mine ) : ?>
			<h3><?php esc_html_e( 'Your bounties', 'underworld-empire' ); ?></h3>
			<table class="dfmg-table">
				<?php foreach ( $mine as $dfmg_b ) : ?>
					<tr>
						<td><?php echo Character::link_by_id( (int) $dfmg_b['target_id'] ); // phpcs:ignore ?></td>
						<td><?php echo esc_html( Format::money( $dfmg_b['amount'] ) ); ?></td>
						<td><?php echo $this->button( 'cancel', sprintf( /* translators: %d: percent */ __( 'Withdraw (%d%% back)', 'underworld-empire' ), $refund ), array( 'bounty' => $dfmg_b['id'] ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Most wanted', 'underworld-empire' ); ?></h3>
		<?php if ( ! $list ) : ?>
			<?php echo UI::empty_state( __( 'There are no bounties.', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<table class="dfmg-table">
				<?php foreach ( $list as $dfmg_row ) : ?>
					<tr>
						<td><?php echo Character::link_by_id( (int) $dfmg_row['target_id'] ); // phpcs:ignore ?></td>
						<td><strong><?php echo esc_html( Format::money( $dfmg_row['total'] ) ); ?></strong></td>
						<td><small class="dfmg-muted"><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d bounty', '%d bounties', (int) $dfmg_row['cnt'], 'underworld-empire' ), (int) $dfmg_row['cnt'] ) ); ?></small></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</section>
</div>
