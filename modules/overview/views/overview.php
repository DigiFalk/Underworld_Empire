<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array|null                     $next
 * @var array                          $timers
 * @var array                          $notifications
 * @var array                          $panels  List of [ 'title' => , 'html' => ] added by other modules.
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-grid dfmg-grid--2">
	<section class="dfmg-card">
		<h3><?php echo esc_html( $c->name ); ?></h3>
		<table class="dfmg-table dfmg-table--keyvalue">
			<tr><th><?php esc_html_e( 'Rank', 'underworld-empire' ); ?></th><td><?php echo esc_html( $c->rank_name() ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Progress', 'underworld-empire' ); ?></th><td><?php echo UI::bar( $c->rank_progress(), $next ? $c->rank_progress() . '% → ' . $next['name'] : __( 'Highest rank', 'underworld-empire' ) ); // phpcs:ignore ?></td></tr>
			<tr><th><?php esc_html_e( 'Health', 'underworld-empire' ); ?></th><td><?php echo UI::bar( $c->health_percent() ); // phpcs:ignore ?></td></tr>
			<tr><th><?php esc_html_e( 'Wealth', 'underworld-empire' ); ?></th><td><?php echo esc_html( $c->wealth_title() ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Cash / bank', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::money( $c->money ) . ' / ' . Format::money( $c->bank ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Bullets', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::number( $c->bullets ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Attack / defense', 'underworld-empire' ); ?></th><td><?php echo esc_html( round( $c->attack_power() ) . ' / ' . round( $c->defense_power() ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'City', 'underworld-empire' ); ?></th><td><?php echo esc_html( $c->location_name() ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Playing since', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::date( (int) $c->created_at ) ); ?></td></tr>
		</table>
	</section>

	<section class="dfmg-card">
		<h3><?php esc_html_e( 'Timers', 'underworld-empire' ); ?></h3>
		<?php if ( ! $timers ) : ?>
			<?php echo UI::empty_state( __( 'No timers.', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<table class="dfmg-table">
				<?php foreach ( $timers as $dfmg_timer ) : ?>
					<tr>
						<th><a href="<?php echo esc_url( $dfmg_timer['url'] ); ?>"><?php echo esc_html( $dfmg_timer['label'] ); ?></a></th>
						<td><?php echo Format::countdown( (int) $dfmg_timer['expires'] ); // phpcs:ignore ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Latest notifications', 'underworld-empire' ); ?></h3>
		<?php if ( ! $notifications ) : ?>
			<?php echo UI::empty_state( __( 'No notifications yet.', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="dfmg-list">
				<?php foreach ( $notifications as $dfmg_n ) : ?>
					<li class="<?php echo $dfmg_n['is_read'] ? '' : 'is-unread'; ?>">
						<?php echo wp_kses_post( $dfmg_n['message'] ); ?>
						<small><?php echo esc_html( Format::ago( (int) $dfmg_n['created_at'] ) ); ?></small>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php foreach ( $panels as $dfmg_panel ) : ?>
		<section class="dfmg-card">
			<h3><?php echo esc_html( $dfmg_panel['title'] ); ?></h3>
			<?php echo $dfmg_panel['html']; // phpcs:ignore ?>
		</section>
	<?php endforeach; ?>
</div>
