<?php
/**
 * Sidebar.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

if ( 'none' === uet_sidebar() ) {
	return;
}
?>
<aside class="uet-sidebar widget-area" aria-label="<?php esc_attr_e( 'Sidebar', 'underworld-empire-theme' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
