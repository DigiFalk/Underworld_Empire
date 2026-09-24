<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $packages
 * @var array                          $benefits
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( $c->timer_active( 'membership' ) ) {
	echo '<div class="dfmg-alert dfmg-alert--success">' . esc_html__( 'You are a premium member. Your membership expires in', 'underworld-empire' ) . ' ' . Format::countdown( $c->timer( 'membership' ) ) . '</div>'; // phpcs:ignore
}
?>
<div class="dfmg-card">
	<h3><?php esc_html_e( 'Benefits', 'underworld-empire' ); ?></h3>
	<ul class="dfmg-list">
		<?php foreach ( $benefits as $dfmg_b ) : ?>
			<li><?php echo esc_html( $dfmg_b ); ?></li>
		<?php endforeach; ?>
	</ul>
	<p>
		<?php
		/* translators: %s: points */
		printf( esc_html__( 'You have %s.', 'underworld-empire' ), '<strong>' . esc_html( Format::points( (int) $c->points ) ) . '</strong>' );
		?>
	</p>
</div>
<?php if ( ! $packages ) : ?>
	<?php echo UI::empty_state( __( 'There are no memberships yet.', 'underworld-empire' ) ); // phpcs:ignore ?>
<?php else : ?>
	<div class="dfmg-grid dfmg-grid--3">
		<?php foreach ( $packages as $dfmg_p ) : ?>
			<div class="dfmg-card dfmg-item">
				<h4><?php echo esc_html( $dfmg_p['name'] ); ?></h4>
				<p><?php echo esc_html( sprintf( /* translators: %d: days */ _n( '%d day', '%d days', (int) $dfmg_p['days'], 'underworld-empire' ), (int) $dfmg_p['days'] ) ); ?></p>
				<p class="dfmg-price"><?php echo esc_html( Format::points( (int) $dfmg_p['cost'] ) ); ?></p>
				<?php echo $this->button( 'buy', __( 'Buy', 'underworld-empire' ), array( 'package' => $dfmg_p['id'] ) ); // phpcs:ignore ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
