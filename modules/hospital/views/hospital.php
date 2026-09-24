<?php
/**
 * @var \DigiFalk\MafiaGame\Character   $c
 * @var array                            $quote
 * @var \DigiFalk\MafiaGame\Character[] $patients
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card">
	<?php if ( $c->is_hospitalized() ) : ?>
		<?php echo UI::cooldown( __( 'You are being treated. Discharged in', 'wp-mafia-game' ), $c->timer( 'hospital' ) ); // phpcs:ignore ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Your health:', 'wp-mafia-game' ); ?></p>
		<?php echo UI::bar( $c->health_percent() ); // phpcs:ignore ?>
		<?php if ( $quote['cost'] || $quote['time'] ) : ?>
			<p>
				<?php
				/* translators: 1: cost, 2: duration */
				printf( esc_html__( 'A full recovery costs %1$s and takes %2$s.', 'wp-mafia-game' ), esc_html( Format::money( $quote['cost'] ) ), esc_html( Format::duration( $quote['time'] ) ) );
				?>
			</p>
			<?php echo $this->button( 'admit', __( 'Get admitted', 'wp-mafia-game' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<p><?php esc_html_e( 'You have no injuries.', 'wp-mafia-game' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>

<h3><?php esc_html_e( 'Patients', 'wp-mafia-game' ); ?></h3>
<?php if ( ! $patients ) : ?>
	<?php echo UI::empty_state( __( 'There are no patients.', 'wp-mafia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<?php foreach ( $patients as $dfmg_p ) : ?>
			<tr><td><?php echo $dfmg_p->link(); // phpcs:ignore ?></td><td><?php echo Format::countdown( $dfmg_p->timer( 'hospital' ) ); // phpcs:ignore ?></td></tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
