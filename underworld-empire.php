<?php
/**
 * Plugin Name:       Underworld Empire
 * Plugin URI:        https://github.com/DigiFalk/Underworld_Empire
 * Description:       A complete, modular mafia browser game (PBBG) for WordPress. Crimes, car theft, families, casino, murders and more — all as separate, extendable modules.
 * Version:           1.10.7
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            DigiFalk
 * Author URI:        https://github.com/DigiFalk
 * License:           DigiFalk License (see LICENSE.md)
 * Update URI:        https://github.com/DigiFalk/Underworld_Empire
 * Text Domain:       underworld-empire
 * Domain Path:       /languages
 *
 * @package DigiFalk\UnderworldEmpire
 * @copyright DigiFalk
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_VERSION', '1.10.7' );
define( 'DFMG_DB_VERSION', '1' );
define( 'DFMG_FILE', __FILE__ );
define( 'DFMG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_URL', plugin_dir_url( __FILE__ ) );

/*
 * Custom modules that should survive plugin updates can be placed in this directory.
 * It can be changed from wp-config.php.
 */
if ( ! defined( 'DFMG_CUSTOM_MODULES_DIR' ) ) {
	define( 'DFMG_CUSTOM_MODULES_DIR', WP_CONTENT_DIR . '/underworld-modules' );
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\UnderworldEmpire\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		// Bundled modules live in modules/<id>/module.php and are loaded by the registry.
		if ( strpos( $relative, 'Modules\\' ) === 0 ) {
			return;
		}
		$file = DFMG_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

require_once DFMG_DIR . 'includes/functions.php';

// Bundled "Underworld Empire" block theme: shows up under Appearance → Themes while the plugin is active.
register_theme_directory( DFMG_DIR . 'themes' );
add_filter(
	'theme_root_uri',
	static function ( $uri, $siteurl, $stylesheet_or_template ) {
		// Build the URL through the plugin URL, which also works for symlinked plugin folders.
		if ( $stylesheet_or_template && is_dir( DFMG_DIR . 'themes/' . $stylesheet_or_template ) ) {
			return untrailingslashit( DFMG_URL ) . '/themes';
		}
		return $uri;
	},
	10,
	3
);

register_activation_hook( __FILE__, array( 'DigiFalk\\UnderworldEmpire\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DigiFalk\\UnderworldEmpire\\Installer', 'deactivate' ) );

add_action( 'init', array( 'DigiFalk\\UnderworldEmpire\\Plugin', 'instance' ), 1 );
