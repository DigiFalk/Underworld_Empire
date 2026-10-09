<?php
/**
 * Plugin Name:       Mafia PBBG Engine
 * Plugin URI:        https://github.com/DigiFalk/Mafia_PBBG_engine
 * Description:       A complete, modular mafia browser game (PBBG) for WordPress. Crimes, car theft, jail, travel, bank and more — all as separate, extendable modules. Families, murders and the Mafia PBBG Engine theme come with the free Mafia PBBG Engine Extended.
 * Version:           2.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            DigiFalk
 * Author URI:        https://digifalk.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/DigiFalk/Mafia_PBBG_engine
 * Text Domain:       mafia-pbbg-engine
 * Domain Path:       /languages
 *
 * @package DigiFalk\MafiaPBBGEngine
 * @copyright DigiFalk
 */

/*
 * Copyright (C) 2026 DigiFalk
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License (LICENSE.txt) for more details.
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_VERSION', '2.0.2' );
define( 'DFMG_DB_VERSION', '1' );
define( 'DFMG_FILE', __FILE__ );
define( 'DFMG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_URL', plugin_dir_url( __FILE__ ) );

/*
 * Custom modules that should survive plugin updates can be placed in this directory.
 * It can be changed from wp-config.php.
 */
if ( ! defined( 'DFMG_CUSTOM_MODULES_DIR' ) ) {
	define( 'DFMG_CUSTOM_MODULES_DIR', WP_CONTENT_DIR . '/mafia-pbbg-modules' );
}

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\MafiaPBBGEngine\\';
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

register_activation_hook( __FILE__, array( 'DigiFalk\\MafiaPBBGEngine\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DigiFalk\\MafiaPBBGEngine\\Installer', 'deactivate' ) );

add_action( 'init', array( 'DigiFalk\\MafiaPBBGEngine\\Plugin', 'instance' ), 1 );
