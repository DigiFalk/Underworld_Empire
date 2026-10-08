<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var array                          $routes
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'chase' ) ) {
	echo UI::cooldown( __( 'You can hit the streets again in', 'mafia-pbbg-engine' ), $c->timer( 'chase' ) ); // phpcs:ignore
	return;
}
?>
<div class="dfmg-card">
	<p><?php esc_html_e( 'A police car is on your tail. Pick a route, fast!', 'mafia-pbbg-engine' ); ?></p>
	<div class="dfmg-choice-grid">
		<?php foreach ( $routes as $dfmg_key => $dfmg_label ) : ?>
			<?php echo $this->button( 'move', $dfmg_label, array( 'route' => $dfmg_key ), 'dfmg-button dfmg-button--big' ); // phpcs:ignore ?>
		<?php endforeach; ?>
	</div>
</div>
