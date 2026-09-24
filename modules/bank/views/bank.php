<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var int                            $tax
 * @var int                            $fee
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--3">
	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Deposit', 'wp-maffia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			/* translators: %d: percent */
			printf( esc_html__( 'Your money launderer charges %d%% of every deposit.', 'wp-maffia-game' ), (int) $tax );
			?>
		</p>
		<p><?php esc_html_e( 'Cash:', 'wp-maffia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->money ) ); ?></strong></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-maffia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deposit', 'wp-maffia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'wp-maffia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Withdraw', 'wp-maffia-game' ); ?></h3>
		<p><?php esc_html_e( 'In the bank:', 'wp-maffia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->bank ) ); ?></strong></p>
		<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-maffia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Withdraw', 'wp-maffia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'wp-maffia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Transfer', 'wp-maffia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			esc_html_e( 'Transfer money from your bank account to another player.', 'wp-maffia-game' );
			if ( $fee ) {
				/* translators: %d: percent */
				echo ' ' . esc_html( sprintf( __( 'The bank charges %d%%.', 'wp-maffia-game' ), $fee ) );
			}
			?>
		</p>
		<?php echo $this->form( 'transfer' ); // phpcs:ignore ?>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'Player name', 'wp-maffia-game' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-maffia-game' ); ?>" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Transfer', 'wp-maffia-game' ); ?></button>
		</form>
	</section>
</div>
