<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $routes
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'chase' ) ) {
	echo UI::cooldown( __( 'You can hit the streets again in', 'underworld-empire' ), $c->timer( 'chase' ) ); // phpcs:ignore
	return;
}
?>
<div class="dfmg-card">
	<p><?php esc_html_e( 'A police car is on your tail. Pick a route, fast!', 'underworld-empire' ); ?></p>
	<div class="dfmg-choice-grid">
		<?php foreach ( $routes as $dfmg_key => $dfmg_label ) : ?>
			<?php echo $this->button( 'move', $dfmg_label, array( 'route' => $dfmg_key ), 'dfmg-button dfmg-button--big' ); // phpcs:ignore ?>
		<?php endforeach; ?>
	</div>
</div>
