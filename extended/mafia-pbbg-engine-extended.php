<?php
/**
 * Plugin Name:       Mafia PBBG Engine Extended
 * Plugin URI:        https://digifalk.com/
 * Description:       Families, murders, detectives, bounties, the bullet factory, black market, blackjack, police chases, properties, the forum and the Mafia PBBG Engine theme for the Mafia PBBG Engine mafia game. Also needed for premium modules.
 * Version:           2.0.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            DigiFalk
 * Author URI:        https://digifalk.com/
 * License:           DigiFalk License (see LICENSE.md)
 * License URI:       https://digifalk.com/
 * Update URI:        https://github.com/DigiFalk/Mafia_PBBG_engine
 * Text Domain:       mafia-pbbg-engine
 *
 * @package DigiFalk\MafiaPBBGEngine\Extended
 * @copyright DigiFalk
 */

defined( 'ABSPATH' ) || exit;

define( 'DFMG_EXTENDED_VERSION', '2.0.2' );
define( 'DFMG_EXTENDED_FILE', __FILE__ );
define( 'DFMG_EXTENDED_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFMG_EXTENDED_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'DigiFalk\\MafiaPBBGEngine\\Extended\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}
		$file = DFMG_EXTENDED_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// The "Mafia PBBG Engine" theme: shows up under Appearance → Themes while this plugin is active.
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

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'dfmg_daily_license_check' );
	}
);

// Mafia PBBG Engine itself is loaded after this plugin, so wait until all plugins are loaded.
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'DFMG_VERSION' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Mafia PBBG Engine Extended needs the plugin Mafia PBBG Engine. Install and activate it first.', 'mafia-pbbg-engine' ) . '</p></div>';
				}
			);
			return;
		}
		\DigiFalk\MafiaPBBGEngine\Extended\Setup::init();
		\DigiFalk\MafiaPBBGEngine\Extended\Licenses::init();
		\DigiFalk\MafiaPBBGEngine\Extended\PremiumScreen::init();
		\DigiFalk\MafiaPBBGEngine\Extended\Updater::init();
	}
);
