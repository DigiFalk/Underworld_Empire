<?php
/**
 * Plugin Name:       WP Maffia Game
 * Plugin URI:        https://github.com/DigiFalk/WP_Maffia_Game
 * Description:       Een complete, modulaire maffia browsergame (PBBG) voor WordPress. Misdaden, auto's stelen, families, casino, moorden en meer — alles als losse, uitbreidbare modules.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            DigiFalk
 * Author URI:        https://github.com/DigiFalk
 * License:           DigiFalk License (zie LICENSE.md)
 * Text Domain:       wp-maffia-game
 * Domain Path:       /languages
 *
 * @package DigiFalk\MaffiaGame
 * @copyright DigiFalk
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_VERSION', '1.0.0' );
define( 'DFMG_DB_VERSION', '1' );
define( 'DFMG_FILE', __FILE__ );
define( 'DFMG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_URL', plugin_dir_url( __FILE__ ) );

/*
 * Custom modules that should survive plugin updates can be placed in this directory.
 * It can be changed from wp-config.php.
 */
if ( ! defined( 'DFMG_CUSTOM_MODULES_DIR' ) ) {
	define( 'DFMG_CUSTOM_MODULES_DIR', WP_CONTENT_DIR . '/maffia-modules' );
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\MaffiaGame\\';
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

register_activation_hook( __FILE__, array( 'DigiFalk\\MaffiaGame\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DigiFalk\\MaffiaGame\\Installer', 'deactivate' ) );

add_action( 'init', array( 'DigiFalk\\MaffiaGame\\Plugin', 'instance' ), 1 );
