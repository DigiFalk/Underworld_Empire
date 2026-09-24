<?php
/**
 * Game layout. The zones are filled with game elements chosen in
 * Appearance → Customize → Game layout (see Frontend\Layout and Frontend\Hud).
 *
 * @var \DigiFalk\UnderworldEmpire\Character     $character
 * @var \DigiFalk\UnderworldEmpire\Module\Module $module
 * @var array                              $menu
 * @var array                              $messages
 * @var string                             $content
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Frontend\Game;
use DigiFalk\UnderworldEmpire\Frontend\Layout;

defined( 'ABSPATH' ) || exit;

$dfmg_z = array();
foreach ( array_keys( Layout::zones() ) as $dfmg_zone ) {
	$dfmg_z[ $dfmg_zone ] = Layout::zone( $dfmg_zone );
}
$dfmg_header  = $dfmg_z['header-left'] . $dfmg_z['header-center'] . $dfmg_z['header-right'];
$dfmg_footer  = $dfmg_z['footer-left'] . $dfmg_z['footer-center'] . $dfmg_z['footer-right'];
$dfmg_sidebar = '' !== $dfmg_z['sidebar'];

$dfmg_zone_html = static function ( string $zone ) use ( $dfmg_z ): string {
	return '' === $dfmg_z[ $zone ] ? '' : '<div class="dfmg-zone dfmg-zone--' . esc_attr( $zone ) . '">' . $dfmg_z[ $zone ] . '</div>';
};
?>
<div class="dfmg-shell">
	<?php if ( '' !== $dfmg_header || $dfmg_sidebar ) : ?>
		<header class="dfmg-header<?php echo '' === $dfmg_header ? ' dfmg-header--toggle-only' : ''; ?>">
			<?php
			echo $dfmg_zone_html( 'header-left' ) . $dfmg_zone_html( 'header-center' ) . $dfmg_zone_html( 'header-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the elements.
			?>
			<?php if ( $dfmg_sidebar ) : ?>
				<button type="button" class="dfmg-menu-toggle" aria-controls="dfmg-nav" aria-expanded="false"><span class="dfmg-menu-toggle__icon" aria-hidden="true"></span><span><?php esc_html_e( 'Menu', 'underworld-empire' ); ?></span></button>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<div class="dfmg-body<?php echo $dfmg_sidebar ? '' : ' dfmg-body--full'; ?>">
		<?php if ( $dfmg_sidebar ) : ?>
			<aside class="dfmg-sidebar" id="dfmg-nav">
				<?php echo $dfmg_z['sidebar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</aside>
		<?php endif; ?>

		<main class="dfmg-main">
			<?php echo $dfmg_zone_html( 'top' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="dfmg-page-head">
				<span class="dfmg-page-head__icon"><?php echo \DigiFalk\UnderworldEmpire\Icons::svg( \DigiFalk\UnderworldEmpire\Icons::has( $module->id() ) ? $module->id() : 'dot', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h2 class="dfmg-title"><?php echo esc_html( $module->title() ); ?></h2>
			</div>
			<?php echo Game::template( 'messages', array( 'messages' => $messages ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- modules escape their own output. ?>
			<?php echo $dfmg_zone_html( 'bottom' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</main>
	</div>
	<?php if ( '' !== $dfmg_footer ) : ?>
		<footer class="dfmg-footer">
			<?php echo $dfmg_zone_html( 'footer-left' ) . $dfmg_zone_html( 'footer-center' ) . $dfmg_zone_html( 'footer-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</footer>
	<?php endif; ?>
</div>
