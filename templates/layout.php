<?php
/**
 * Game layout.
 *
 * @var \DigiFalk\UnderworldEmpire\Character     $character
 * @var \DigiFalk\UnderworldEmpire\Module\Module $module
 * @var array                              $menu
 * @var array                              $messages
 * @var string                             $content
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\Game;

defined( 'ABSPATH' ) || exit;

$dfmg_stats = apply_filters(
	'dfmg_header_stats',
	array(
		'rank'     => array( __( 'Rank', 'underworld-empire' ), esc_html( $character->rank_name() ) ),
		'money'    => array( __( 'Cash', 'underworld-empire' ), esc_html( Format::money( $character->money ) ) ),
		'bank'     => array( __( 'Bank', 'underworld-empire' ), esc_html( Format::money( $character->bank ) ) ),
		'bullets'  => array( __( 'Bullets', 'underworld-empire' ), esc_html( Format::number( $character->bullets ) ) ),
		'health'   => array( __( 'Health', 'underworld-empire' ), esc_html( $character->health_percent() . '%' ) ),
		'location' => array( __( 'City', 'underworld-empire' ), esc_html( $character->location_name() ) ),
		'points'   => array( esc_html( (string) \DigiFalk\UnderworldEmpire\Settings::get( 'points_name' ) ), esc_html( Format::number( $character->points ) ) ),
	),
	$character
);
?>
<div class="dfmg-shell">
	<header class="dfmg-header">
		<div class="dfmg-header__who">
			<?php $dfmg_has_profile = (bool) \DigiFalk\UnderworldEmpire\Plugin::instance()->modules->get( 'profile' ); ?>
			<a class="dfmg-header__name" href="<?php echo esc_url( $dfmg_has_profile ? Game::url( 'profile' ) : Game::url() ); ?>" title="<?php echo esc_attr( $dfmg_has_profile ? __( 'My profile', 'underworld-empire' ) : __( 'Overview', 'underworld-empire' ) ); ?>">
				<span class="dfmg-header__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $character->name, 0, 1 ) ) ); ?></span>
				<span class="dfmg-header__label"><?php echo esc_html( $character->name ); ?></span>
			</a>
			<div class="dfmg-progress" title="<?php echo esc_attr( sprintf( /* translators: %s: percent */ __( '%s%% to next rank', 'underworld-empire' ), $character->rank_progress() ) ); ?>">
				<span style="width:<?php echo esc_attr( (string) $character->rank_progress() ); ?>%"></span>
			</div>
		</div>
		<dl class="dfmg-stats">
			<?php foreach ( $dfmg_stats as $dfmg_key => $dfmg_stat ) : ?>
				<div class="dfmg-stat dfmg-stat--<?php echo esc_attr( $dfmg_key ); ?>">
					<dt><?php echo esc_html( $dfmg_stat[0] ); ?></dt>
					<dd><?php echo $dfmg_stat[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<button type="button" class="dfmg-menu-toggle" aria-controls="dfmg-nav" aria-expanded="false"><?php esc_html_e( 'Menu', 'underworld-empire' ); ?></button>
	</header>

	<div class="dfmg-body">
		<nav class="dfmg-nav" id="dfmg-nav">
			<?php foreach ( $menu as $dfmg_group ) : ?>
				<div class="dfmg-nav__group">
					<h4><?php echo esc_html( $dfmg_group['label'] ); ?></h4>
					<ul>
						<?php foreach ( $dfmg_group['items'] as $dfmg_item ) : ?>
							<li class="<?php echo $dfmg_item['route'] === $module->id() ? 'is-active' : ''; ?>">
								<a href="<?php echo esc_url( $dfmg_item['url'] ); ?>">
									<span><?php echo esc_html( $dfmg_item['label'] ); ?></span>
									<?php if ( ! empty( $dfmg_item['badge'] ) ) : ?>
										<em class="dfmg-badge"><?php echo esc_html( (string) $dfmg_item['badge'] ); ?></em>
									<?php endif; ?>
									<?php if ( ! empty( $dfmg_item['timer'] ) && $character->timer_active( $dfmg_item['timer'] ) ) : ?>
										<small class="dfmg-nav__timer"><?php echo Format::countdown( $character->timer( $dfmg_item['timer'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
			<div class="dfmg-nav__group">
				<ul>
					<li><a href="<?php echo esc_url( wp_logout_url( Game::page_url() ) ); ?>"><?php esc_html_e( 'Log out', 'underworld-empire' ); ?></a></li>
				</ul>
			</div>
		</nav>

		<main class="dfmg-main">
			<h2 class="dfmg-title"><?php echo esc_html( $module->title() ); ?></h2>
			<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- modules escape their own output. ?>
		</main>
	</div>
	<footer class="dfmg-footer">
		<?php echo esc_html( (string) \DigiFalk\UnderworldEmpire\Settings::get( 'round_name' ) ); ?> &middot; Underworld Empire &copy; DigiFalk
	</footer>
</div>
