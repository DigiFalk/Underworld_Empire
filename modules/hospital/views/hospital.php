<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character   $c
 * @var array                            $quote
 * @var \DigiFalk\UnderworldEmpire\Character[] $patients
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card">
	<?php if ( $c->is_hospitalized() ) : ?>
		<?php echo UI::cooldown( __( 'You are being treated. Discharged in', 'underworld-empire' ), $c->timer( 'hospital' ) ); // phpcs:ignore ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Your health:', 'underworld-empire' ); ?></p>
		<?php echo UI::bar( $c->health_percent() ); // phpcs:ignore ?>
		<?php if ( $quote['cost'] || $quote['time'] ) : ?>
			<p>
				<?php
				/* translators: 1: cost, 2: duration */
				printf( esc_html__( 'A full recovery costs %1$s and takes %2$s.', 'underworld-empire' ), esc_html( Format::money( $quote['cost'] ) ), esc_html( Format::duration( $quote['time'] ) ) );
				?>
			</p>
			<?php echo $this->button( 'admit', __( 'Get admitted', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<p><?php esc_html_e( 'You have no injuries.', 'underworld-empire' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>

<h3><?php esc_html_e( 'Patients', 'underworld-empire' ); ?></h3>
<?php if ( ! $patients ) : ?>
	<?php echo UI::empty_state( __( 'There are no patients.', 'underworld-empire' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<?php foreach ( $patients as $dfmg_p ) : ?>
			<tr><td><?php echo $dfmg_p->link(); // phpcs:ignore ?></td><td><?php echo Format::countdown( $dfmg_p->timer( 'hospital' ) ); // phpcs:ignore ?></td></tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
