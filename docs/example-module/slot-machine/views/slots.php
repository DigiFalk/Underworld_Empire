<?php
/**
 * Themes can override this file with <theme>/underworld-empire/slot-machine/slots.php
 *
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var int                            $bet
 * @var array|false                    $last
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'slots' ) ) {
	echo UI::cooldown( __( 'You can spin again in', 'underworld-empire' ), $c->timer( 'slots' ) ); // phpcs:ignore
}
?>
<div class="dfmg-card">
	<?php if ( $last ) : ?>
		<div class="dfmg-cards">
			<?php foreach ( $last as $dfmg_symbol ) : ?>
				<span class="dfmg-card-face"><?php echo esc_html( $dfmg_symbol ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<p><?php echo esc_html( sprintf( /* translators: %s: money */ __( 'Bet: %s. Two of a kind = 2x, three of a kind = 20x.', 'underworld-empire' ), Format::money( $bet ) ) ); ?></p>
	<?php echo $this->button( 'spin', __( 'Spin!', 'underworld-empire' ) ); // phpcs:ignore ?>
</div>
