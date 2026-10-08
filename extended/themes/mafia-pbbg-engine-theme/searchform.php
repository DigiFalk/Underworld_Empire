<?php
/**
 * Search form.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

$mpet_id = wp_unique_id( 'mpet-search-' );
?>
<form role="search" method="get" class="mpet-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $mpet_id ); ?>"><?php esc_html_e( 'Search for:', 'mafia-pbbg-engine-theme' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $mpet_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'mafia-pbbg-engine-theme' ); ?>">
	<button type="submit" class="mpet-button"><?php esc_html_e( 'Search', 'mafia-pbbg-engine-theme' ); ?></button>
</form>
