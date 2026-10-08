<?php
/**
 * Themes can override this file with <theme>/mafia-pbbg-engine/slot-machine/slots.php
 *
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var int                            $bet
 * @var array|false                    $last
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'slots' ) ) {
	echo UI::cooldown( __( 'You can spin again in', 'mafia-pbbg-engine' ), $c->timer( 'slots' ) ); // phpcs:ignore
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
	<p><?php echo esc_html( sprintf( /* translators: %s: money */ __( 'Bet: %s. Two of a kind = 2x, three of a kind = 20x.', 'mafia-pbbg-engine' ), Format::money( $bet ) ) ); ?></p>
	<?php echo $this->button( 'spin', __( 'Spin!', 'mafia-pbbg-engine' ) ); // phpcs:ignore ?>
</div>
