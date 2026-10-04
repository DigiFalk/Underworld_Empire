<?php
/**
 * Plugin Name:       Underworld Empire Extended
 * Plugin URI:        https://digifalk.com/
 * Description:       Families, murders, detectives, bounties, the bullet factory, black market, blackjack, police chases, properties, the forum and the Underworld Empire theme for the Underworld Empire mafia game. Also needed for premium modules.
 * Version:           1.13.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  underworld-empire
 * Author:            DigiFalk
 * Author URI:        https://digifalk.com/
 * License:           DigiFalk License (see LICENSE.md)
 * License URI:       https://digifalk.com/
 * Update URI:        https://github.com/DigiFalk/Underworld_Empire
 * Text Domain:       underworld-empire
 *
 * @package DigiFalk\UnderworldEmpire\Extended
 * @copyright DigiFalk
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_EXTENDED_VERSION', '1.13.2' );
define( 'DFMG_EXTENDED_FILE', __FILE__ );
define( 'DFMG_EXTENDED_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_EXTENDED_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\UnderworldEmpire\\Extended\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}
		$file = DFMG_EXTENDED_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// The "Underworld Empire" theme: shows up under Appearance → Themes while this plugin is active.
register_theme_directory( DFMG_EXTENDED_DIR . 'themes' );
add_filter(
	'theme_root_uri',
	static function ( $uri, $siteurl, $stylesheet_or_template ) {
		// Build the URL through the plugin URL, which also works for symlinked plugin folders.
		if ( $stylesheet_or_template && is_dir( DFMG_EXTENDED_DIR . 'themes/' . $stylesheet_or_template ) ) {
			return untrailingslashit( DFMG_EXTENDED_URL ) . '/themes';
		}
		return $uri;
	},
	10,
	3
);

add_action( 'plugins_loaded', array( 'DigiFalk\\UnderworldEmpire\\Extended\\Setup', 'fix_theme_root' ), 1 );

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'dfmg_daily_license_check' );
	}
);

// Underworld Empire itself is loaded after this plugin, so wait until all plugins are loaded.
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'DFMG_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Underworld Empire Extended needs the plugin Underworld Empire. Install and activate it first.', 'underworld-empire' ) . '</p></div>';
				}
			);
			return;
		}
		\DigiFalk\UnderworldEmpire\Extended\Setup::init();
		\DigiFalk\UnderworldEmpire\Extended\Licenses::init();
		\DigiFalk\UnderworldEmpire\Extended\PremiumScreen::init();
		\DigiFalk\UnderworldEmpire\Extended\Updater::init();
	}
);
