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
		<h3><?php esc_html_e( 'Storten', 'wp-maffia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			/* translators: %d: percent */
			printf( esc_html__( 'Je witwasser rekent %d%% van elk gestort bedrag.', 'wp-maffia-game' ), (int) $tax );
			?>
		</p>
		<p><?php esc_html_e( 'Contant:', 'wp-maffia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->money ) ); ?></strong></p>
		<?php echo $this->form( 'deposit' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Bedrag', 'wp-maffia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Storten', 'wp-maffia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'Alles', 'wp-maffia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Opnemen', 'wp-maffia-game' ); ?></h3>
		<p><?php esc_html_e( 'Op de bank:', 'wp-maffia-game' ); ?> <strong><?php echo esc_html( Format::money( $c->bank ) ); ?></strong></p>
		<?php echo $this->form( 'withdraw' ); // phpcs:ignore ?>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Bedrag', 'wp-maffia-game' ); ?>">
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Opnemen', 'wp-maffia-game' ); ?></button>
			<button type="submit" class="dfmg-button dfmg-button--ghost" name="all" value="1"><?php esc_html_e( 'Alles', 'wp-maffia-game' ); ?></button>
		</form>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Overmaken', 'wp-maffia-game' ); ?></h3>
		<p class="dfmg-muted">
			<?php
			esc_html_e( 'Maak geld van je bankrekening over naar een andere speler.', 'wp-maffia-game' );
			if ( $fee ) {
				/* translators: %d: percent */
				echo ' ' . esc_html( sprintf( __( 'De bank rekent %d%%.', 'wp-maffia-game' ), $fee ) );
			}
			?>
		</p>
		<?php echo $this->form( 'transfer' ); // phpcs:ignore ?>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'Spelersnaam', 'wp-maffia-game' ); ?>" required>
			<input type="text" inputmode="numeric" name="amount" placeholder="<?php esc_attr_e( 'Bedrag', 'wp-maffia-game' ); ?>" required>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Overmaken', 'wp-maffia-game' ); ?></button>
		</form>
	</section>
</div>
