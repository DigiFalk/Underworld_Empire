<?php
/**
 * Search form.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

$uet_id = wp_unique_id( 'uet-search-' );
?>
<form role="search" method="get" class="uet-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $uet_id ); ?>"><?php esc_html_e( 'Search for:', 'underworld-empire-theme' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $uet_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'underworld-empire-theme' ); ?>">
	<button type="submit" class="uet-button"><?php esc_html_e( 'Search', 'underworld-empire-theme' ); ?></button>
</form>
