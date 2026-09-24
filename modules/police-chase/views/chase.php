<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $routes
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'chase' ) ) {
	echo UI::cooldown( __( 'Je kunt weer de straat op over', 'wp-maffia-game' ), $c->timer( 'chase' ) ); // phpcs:ignore
	return;
}
?>
<div class="dfmg-card">
	<p><?php esc_html_e( 'Een politieauto zit achter je aan. Kies snel een route!', 'wp-maffia-game' ); ?></p>
	<div class="dfmg-choice-grid">
		<?php foreach ( $routes as $dfmg_key => $dfmg_label ) : ?>
			<?php echo $this->button( 'move', $dfmg_label, array( 'route' => $dfmg_key ), 'dfmg-button dfmg-button--big' ); // phpcs:ignore ?>
		<?php endforeach; ?>
	</div>
</div>
