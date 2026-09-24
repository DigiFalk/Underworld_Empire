<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $packages
 * @var array                          $benefits
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'membership' ) ) {
	echo '<div class="dfmg-alert dfmg-alert--success">' . esc_html__( 'Je bent premium lid. Je lidmaatschap loopt af over', 'wp-maffia-game' ) . ' ' . Format::countdown( $c->timer( 'membership' ) ) . '</div>'; // phpcs:ignore
}
?>
<div class="dfmg-card">
	<h3><?php esc_html_e( 'Voordelen', 'wp-maffia-game' ); ?></h3>
	<ul class="dfmg-list">
		<?php foreach ( $benefits as $dfmg_b ) : ?>
			<li><?php echo esc_html( $dfmg_b ); ?></li>
		<?php endforeach; ?>
	</ul>
	<p>
		<?php
		/* translators: %s: points */
		printf( esc_html__( 'Je hebt %s.', 'wp-maffia-game' ), '<strong>' . esc_html( Format::points( (int) $c->points ) ) . '</strong>' );
		?>
	</p>
</div>
<?php if ( ! $packages ) : ?>
	<?php echo UI::empty_state( __( 'Er zijn nog geen lidmaatschappen.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<div class="dfmg-grid dfmg-grid--3">
		<?php foreach ( $packages as $dfmg_p ) : ?>
			<div class="dfmg-card dfmg-item">
				<h4><?php echo esc_html( $dfmg_p['name'] ); ?></h4>
				<p><?php echo esc_html( sprintf( /* translators: %d: days */ _n( '%d dag', '%d dagen', (int) $dfmg_p['days'], 'wp-maffia-game' ), (int) $dfmg_p['days'] ) ); ?></p>
				<p class="dfmg-price"><?php echo esc_html( Format::points( (int) $dfmg_p['cost'] ) ); ?></p>
				<?php echo $this->button( 'buy', __( 'Kopen', 'wp-maffia-game' ), array( 'package' => $dfmg_p['id'] ) ); // phpcs:ignore ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
