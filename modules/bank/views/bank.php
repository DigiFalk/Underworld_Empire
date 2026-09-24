<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var int                            $tax
 * @var int                            $fee
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Format;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--3">
	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Deposit', 'wp-mafia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			/* translators: %d: percent */
			printf( esc_html__( 'Your money launderer charges %d%% of every deposit.', 'wp-mafia-game' ), (int) $tax );
			?>
		</p>
		<p><?php esc_html_e( 'Cash:', 'wp-mafia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->money ) ); ?></strong></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-mafia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deposit', 'wp-mafia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'wp-mafia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Withdraw', 'wp-mafia-game' ); ?></h3>
		<p><?php esc_html_e( 'In the bank:', 'wp-mafia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->bank ) ); ?></strong></p>
		<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-mafia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Withdraw', 'wp-mafia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'wp-mafia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Transfer', 'wp-mafia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			esc_html_e( 'Transfer money from your bank account to another player.', 'wp-mafia-game' );
			if ( $fee ) {
				/* translators: %d: percent */
				echo ' ' . esc_html( sprintf( __( 'The bank charges %d%%.', 'wp-mafia-game' ), $fee ) );
			}
			?>
		</p>
		<?php echo $this->form( 'transfer' ); // phpcs:ignore ?>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'Player name', 'wp-mafia-game' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'wp-mafia-game' ); ?>" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Transfer', 'wp-mafia-game' ); ?></button>
		</form>
	</section>
</div>
