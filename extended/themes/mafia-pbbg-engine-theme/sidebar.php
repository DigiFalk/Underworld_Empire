<?php
/**
 * Sidebar.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

if ( 'none' === mpet_sidebar() ) {
	return;
}
?>
<aside class="mpet-sidebar widget-area" aria-label="<?php esc_attr_e( 'Sidebar', 'mafia-pbbg-engine-theme' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
