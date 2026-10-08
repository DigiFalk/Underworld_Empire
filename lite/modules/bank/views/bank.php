<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var int                            $tax
 * @var int                            $fee
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Format;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--3">
	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Deposit', 'mafia-pbbg-engine' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			/* translators: %d: percent */
			printf( esc_html__( 'Your money launderer charges %d%% of every deposit.', 'mafia-pbbg-engine' ), (int) $tax );
			?>
		</p>
		<p><?php esc_html_e( 'Cash:', 'mafia-pbbg-engine' ); ?> <strong><?php echo esc_html( Format::money( $c->money ) ); ?></strong></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'mafia-pbbg-engine' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deposit', 'mafia-pbbg-engine' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'mafia-pbbg-engine' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Withdraw', 'mafia-pbbg-engine' ); ?></h3>
		<p><?php esc_html_e( 'In the bank:', 'mafia-pbbg-engine' ); ?> <strong><?php echo esc_html( Format::money( $c->bank ) ); ?></strong></p>
		<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'mafia-pbbg-engine' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Withdraw', 'mafia-pbbg-engine' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'All', 'mafia-pbbg-engine' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Transfer', 'mafia-pbbg-engine' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			esc_html_e( 'Transfer money from your bank account to another player.', 'mafia-pbbg-engine' );
			if ( $fee ) {
				/* translators: %d: percent */
				echo ' ' . esc_html( sprintf( __( 'The bank charges %d%%.', 'mafia-pbbg-engine' ), $fee ) );
			}
			?>
		</p>
		<?php echo $this->form( 'transfer' ); // phpcs:ignore ?>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'Player name', 'mafia-pbbg-engine' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'mafia-pbbg-engine' ); ?>" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Transfer', 'mafia-pbbg-engine' ); ?></button>
		</form>
	</section>
</div>
