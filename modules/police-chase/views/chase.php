<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $routes
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'chase' ) ) {
	echo UI::cooldown( __( 'You can hit the streets again in', 'wp-mafia-game' ), $c->timer( 'chase' ) ); // phpcs:ignore
	return;
}
?>
<div class="dfmg-card">
	<p><?php esc_html_e( 'A police car is on your tail. Pick a route, fast!', 'wp-mafia-game' ); ?></p>
	<div class="dfmg-choice-grid">
		<?php foreach ( $routes as $dfmg_key => $dfmg_label ) : ?>
			<?php echo $this->button( 'move', $dfmg_label, array( 'route' => $dfmg_key ), 'dfmg-button dfmg-button--big' ); // phpcs:ignore ?>
		<?php endforeach; ?>
	</div>
</div>
