<?php
/**
 * Plugin Name:       WP Mafia Game
 * Plugin URI:        https://github.com/DigiFalk/WP_Maffia_Game
 * Description:       A complete, modular mafia browser game (PBBG) for WordPress. Crimes, car theft, families, casino, murders and more — all as separate, extendable modules.
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            DigiFalk
 * Author URI:        https://github.com/DigiFalk
 * License:           DigiFalk License (see LICENSE.md)
 * Update URI:        https://github.com/DigiFalk/WP_Maffia_Game
 * Text Domain:       wp-mafia-game
 * Domain Path:       /languages
 *
 * @package DigiFalk\MafiaGame
 * @copyright DigiFalk
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_VERSION', '1.1.1' );
define( 'DFMG_DB_VERSION', '1' );
define( 'DFMG_FILE', __FILE__ );
define( 'DFMG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_URL', plugin_dir_url( __FILE__ ) );

/*
 * Custom modules that should survive plugin updates can be placed in this directory.
 * It can be changed from wp-config.php.
 */
if ( ! defined( 'DFMG_CUSTOM_MODULES_DIR' ) ) {
	define( 'DFMG_CUSTOM_MODULES_DIR', WP_CONTENT_DIR . '/mafia-modules' );
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\MafiaGame\\';
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

register_activation_hook( __FILE__, array( 'DigiFalk\\MafiaGame\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DigiFalk\\MafiaGame\\Installer', 'deactivate' ) );

add_action( 'init', array( 'DigiFalk\\MafiaGame\\Plugin', 'instance' ), 1 );
