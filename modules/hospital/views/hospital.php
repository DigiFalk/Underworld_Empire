<?php
/**
 * @var \DigiFalk\MaffiaGame\Character   $c
 * @var array                            $quote
 * @var \DigiFalk\MaffiaGame\Character[] $patients
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card">
	<?php if ( $c->is_hospitalized() ) : ?>
		<?php echo UI::cooldown( __( 'Je wordt behandeld. Ontslag over', 'wp-maffia-game' ), $c->timer( 'hospital' ) ); // phpcs:ignore ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Je gezondheid:', 'wp-maffia-game' ); ?></p>
		<?php echo UI::bar( $c->health_percent() ); // phpcs:ignore ?>
		<?php if ( $quote['cost'] || $quote['time'] ) : ?>
			<p>
				<?php
				/* translators: 1: cost, 2: duration */
				printf( esc_html__( 'Volledig herstel kost %1$s en duurt %2$s.', 'wp-maffia-game' ), esc_html( Format::money( $quote['cost'] ) ), esc_html( Format::duration( $quote['time'] ) ) );
				?>
			</p>
			<?php echo $this->button( 'admit', __( 'Laat je opnemen', 'wp-maffia-game' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Je hebt geen verwondingen.', 'wp-maffia-game' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
</div>

<h3><?php esc_html_e( 'Patiënten', 'wp-maffia-game' ); ?></h3>
<?php if ( ! $patients ) : ?>
	<?php echo UI::empty_state( __( 'Er liggen geen patiënten.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table">
		<?php foreach ( $patients as $dfmg_p ) : ?>
			<tr><td><?php echo $dfmg_p->link(); // phpcs:ignore ?></td><td><?php echo Format::countdown( $dfmg_p->timer( 'hospital' ) ); // phpcs:ignore ?></td></tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
