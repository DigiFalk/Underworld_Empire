<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var int                            $tax
 * @var int                            $fee
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--3">
	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Deposit', 'underworld-empire' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			/* translators: %d: percent */
			printf( esc_html__( 'Your money launderer charges %d%% of every deposit.', 'underworld-empire' ), (int) $tax );
			?>
		</p>
		<p><?php esc_html_e( 'Cash:', 'underworld-empire' ); ?> <strong><?php echo esc_html( Format::money( $c->money ) ); ?></strong></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'underworld-empire' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deposit', 'underworld-empire' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'underworld-empire' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Withdraw', 'underworld-empire' ); ?></h3>
		<p><?php esc_html_e( 'In the bank:', 'underworld-empire' ); ?> <strong><?php echo esc_html( Format::money( $c->bank ) ); ?></strong></p>
		<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'underworld-empire' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Withdraw', 'underworld-empire' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'underworld-empire' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Transfer', 'underworld-empire' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			esc_html_e( 'Transfer money from your bank account to another player.', 'underworld-empire' );
			if ( $fee ) {
				/* translators: %d: percent */
				echo ' ' . esc_html( sprintf( __( 'The bank charges %d%%.', 'underworld-empire' ), $fee ) );
			}
			?>
		</p>
		<?php echo $this->form( 'transfer' ); // phpcs:ignore ?>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'Player name', 'underworld-empire' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'underworld-empire' ); ?>" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Transfer', 'underworld-empire' ); ?></button>
		</form>
	</section>
</div>
