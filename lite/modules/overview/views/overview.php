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
use DigiFalk\UnderworldEmpire\Frontend\Hud;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Icons;

defined( 'ABSPATH' ) || exit;

$dfmg_tiles = array(
	array( 'cash', __( 'Cash', 'underworld-empire' ), Format::money( $c->money ) ),
	array( 'bank', __( 'Bank', 'underworld-empire' ), Format::money( $c->bank ) ),
	array( 'bullets', __( 'Bullets', 'underworld-empire' ), Format::number( $c->bullets ) ),
	array( 'power', __( 'Attack / defense', 'underworld-empire' ), round( $c->attack_power() ) . ' / ' . round( $c->defense_power() ) ),
	array( 'city', __( 'City', 'underworld-empire' ), $c->location_name() ),
	array( 'wealth', __( 'Wealth', 'underworld-empire' ), $c->wealth_title() ),
);
$dfmg_ready = 0;
foreach ( $timers as $dfmg_timer ) {
	$dfmg_ready += (int) $dfmg_timer['expires'] <= time() ? 1 : 0;
}
?>
<section class="dfmg-hero">
	<div class="dfmg-hero__who">
		<?php echo Hud::avatar_ring( $c, 72 ); // phpcs:ignore ?>
		<div>
			<p class="dfmg-hero__eyebrow"><?php echo esc_html( $c->rank_name() ); ?></p>
			<h3 class="dfmg-hero__name"><?php echo esc_html( $c->name ); ?></h3>
			<p class="dfmg-hero__meta"><?php echo Icons::svg( 'calendar', 14 ); // phpcs:ignore ?> <?php /* translators: %s: date */ echo esc_html( sprintf( __( 'Playing since %s', 'underworld-empire' ), Format::date( (int) $c->created_at ) ) ); ?></p>
		</div>
	</div>
	<div class="dfmg-hero__bars">
		<div class="dfmg-meter">
			<div class="dfmg-meter__head"><span><?php echo Icons::svg( 'rank', 14 ); // phpcs:ignore ?> <?php echo esc_html( $next ? sprintf( /* translators: %s: rank */ __( 'Next rank: %s', 'underworld-empire' ), $next['name'] ) : __( 'Highest rank reached', 'underworld-empire' ) ); ?></span><strong><?php echo esc_html( $c->rank_progress() . '%' ); ?></strong></div>
			<?php echo UI::bar( $c->rank_progress(), '', true ); // phpcs:ignore ?>
		</div>
		<div class="dfmg-meter dfmg-meter--health">
			<div class="dfmg-meter__head"><span><?php echo Icons::svg( 'health', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'Health', 'underworld-empire' ); ?></span><strong><?php echo esc_html( $c->health_percent() . '%' ); ?></strong></div>
			<?php echo UI::bar( $c->health_percent(), '', true ); // phpcs:ignore ?>
		</div>
	</div>
</section>

<div class="dfmg-tiles">
	<?php foreach ( $dfmg_tiles as $dfmg_tile ) : ?>
		<div class="dfmg-tile">
			<span class="dfmg-tile__icon"><?php echo Icons::svg( $dfmg_tile[0], 20 ); // phpcs:ignore ?></span>
			<span class="dfmg-tile__label"><?php echo esc_html( $dfmg_tile[1] ); ?></span>
			<strong class="dfmg-tile__value"><?php echo esc_html( (string) $dfmg_tile[2] ); ?></strong>
		</div>
	<?php endforeach; ?>
</div>

<div class="dfmg-grid dfmg-grid--2">
	<section class="dfmg-card">
		<h3 class="dfmg-card__title"><?php echo Icons::svg( 'timer', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Timers', 'underworld-empire' ); ?>
			<?php if ( $timers ) : ?>
				<?php /* translators: 1: ready timers, 2: all timers */ ?>
				<span class="dfmg-pill"><?php echo esc_html( sprintf( __( '%1$d of %2$d ready', 'underworld-empire' ), $dfmg_ready, count( $timers ) ) ); ?></span>
			<?php endif; ?>
		</h3>
		<?php if ( ! $timers ) : ?>
			<?php echo UI::empty_state( __( 'No timers.', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="dfmg-timerlist">
				<?php foreach ( $timers as $dfmg_timer ) : ?>
					<li>
						<a href="<?php echo esc_url( $dfmg_timer['url'] ); ?>">
							<span class="dfmg-timerlist__icon"><?php echo Icons::svg( $dfmg_timer['icon'] ?? 'timer', 16 ); // phpcs:ignore ?></span>
							<span class="dfmg-timerlist__label"><?php echo esc_html( $dfmg_timer['label'] ); ?></span>
							<?php echo Format::countdown( (int) $dfmg_timer['expires'] ); // phpcs:ignore ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="dfmg-card">
		<h3 class="dfmg-card__title"><?php echo Icons::svg( 'notifications', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Latest notifications', 'underworld-empire' ); ?></h3>
		<?php if ( ! $notifications ) : ?>
			<?php echo UI::empty_state( __( 'No notifications yet.', 'underworld-empire' ) ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="dfmg-feed">
				<?php foreach ( $notifications as $dfmg_n ) : ?>
					<li class="<?php echo $dfmg_n['is_read'] ? '' : 'is-unread'; ?>">
						<div><?php echo wp_kses_post( $dfmg_n['message'] ); ?></div>
						<small><?php echo esc_html( Format::ago( (int) $dfmg_n['created_at'] ) ); ?></small>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php foreach ( $panels as $dfmg_panel ) : ?>
		<section class="dfmg-card">
			<h3 class="dfmg-card__title"><?php echo esc_html( $dfmg_panel['title'] ); ?></h3>
			<?php echo $dfmg_panel['html']; // phpcs:ignore ?>
		</section>
	<?php endforeach; ?>
</div>
