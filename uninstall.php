<?php
/**
 * Runs when the plugin is deleted from the WordPress admin.
 * Data is only removed when "Verwijder alle speldata bij verwijderen plugin" is enabled.
 *
 * @package DigiFalk\MaffiaGame
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$dfmg_settings = get_option( 'dfmg_settings', array() );
if ( empty( $dfmg_settings['delete_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$dfmg_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'dfmg_' ) . '%' ) );
foreach ( $dfmg_tables as $dfmg_table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $dfmg_table ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

foreach ( array( 'dfmg_settings', 'dfmg_enabled_modules', 'dfmg_installed_modules', 'dfmg_core_db_version', 'dfmg_page_id', 'dfmg_bullets_restocked', 'dfmg_flush_rewrite' ) as $dfmg_option ) {
	delete_option( $dfmg_option );
}
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_dfmg\\_%' OR option_name LIKE '\\_transient\\_timeout\\_dfmg\\_%'" );
delete_metadata( 'user', 0, 'dfmg_carry_points', '', true );

$dfmg_news = get_posts(
	array(
		'post_type'   => 'dfmg_news',
		'numberposts' => -1,
		'post_status' => 'any',
		'fields'      => 'ids',
	)
);
foreach ( $dfmg_news as $dfmg_post_id ) {
	wp_delete_post( $dfmg_post_id, true );
}

$dfmg_role = get_role( 'administrator' );
if ( $dfmg_role ) {
	$dfmg_role->remove_cap( 'dfmg_manage' );
}
wp_clear_scheduled_hook( 'dfmg_hourly' );
