<?php
/**
 * Registers the modules and theme of Extended and switches new modules on.
 *
 * @package DigiFalk\UnderworldEmpire\Extended
 */

namespace DigiFalk\UnderworldEmpire\Extended;

use DigiFalk\UnderworldEmpire\Module\Registry;

defined( 'ABSPATH' ) || exit;

final class Setup {

	const OPTION = 'dfmg_extended_setup';

	public static function init(): void {
		add_action( 'dfmg_register_modules', array( __CLASS__, 'register_modules' ) );
		add_action( 'dfmg_modules_booted', array( __CLASS__, 'enable_new' ) );
		// "Mafia PBBG Engine by DigiFalk" stays on game pages (White Label can change it).
		add_filter( 'dfmg_show_credit', '__return_true', 5 );
	}

	/**
	 * The theme moved from the Mafia PBBG Engine plugin to this plugin in 1.12.0. WordPress
	 * remembers the folder of the active theme (and caches all theme folders), so point it to
	 * the new folder when the remembered one no longer exists.
	 */
	public static function fix_theme_root(): void {
		$theme = 'underworld-empire-theme';
		$keys  = array();
		if ( get_option( 'stylesheet' ) === $theme ) {
			$keys[] = 'stylesheet_root';
		}
		if ( get_option( 'template' ) === $theme ) {
			$keys[] = 'template_root';
		}
		// The folder WordPress will actually use for the theme (as get_theme_root() resolves it).
		if ( ! $keys || is_dir( get_theme_root( $theme ) . '/' . $theme ) ) {
			return;
		}
		delete_site_transient( 'theme_roots' );
		$new = get_raw_theme_root( $theme, true );
		if ( $new ) {
			foreach ( $keys as $key ) {
				update_option( $key, $new );
			}
		}
	}

	/**
	 * Module ids of Extended.
	 */
	public static function ids(): array {
		$ids = array();
		foreach ( (array) glob( DFMG_EXTENDED_DIR . 'modules/*/module.php' ) as $file ) {
			$ids[] = sanitize_key( basename( dirname( $file ) ) );
		}
		return $ids;
	}

	/**
	 * @param Registry $registry
	 */
	public static function register_modules( $registry ): void {
		foreach ( (array) glob( DFMG_EXTENDED_DIR . 'modules/*/module.php' ) as $file ) {
			$info = $registry->info( sanitize_key( basename( dirname( $file ) ) ) );
			// A custom module with the same id replaces the bundled one, as in the free plugin.
			if ( $info && 'custom' === $info['source'] ) {
				continue;
			}
			$registry->add( $file, 'bundled' );
		}
	}

	/**
	 * Once per version of Extended: switch on modules that are on by default and were never
	 * installed on this site. Modules the site owner switched off stay off.
	 *
	 * @param Registry $registry
	 */
	public static function enable_new( $registry ): void {
		if ( get_option( self::OPTION ) === DFMG_EXTENDED_VERSION ) {
			return;
		}
		update_option( self::OPTION, DFMG_EXTENDED_VERSION );
		$installed = (array) get_option( Registry::OPTION_INSTALLED, array() );
		$todo      = array();
		foreach ( self::ids() as $id ) {
			$info = $registry->info( $id );
			if ( $info && $info['default'] && ! isset( $installed[ $id ] ) && ! $registry->is_enabled( $id ) ) {
				$todo[] = $id;
			}
		}
		// Enable in rounds, so a module comes after the modules it requires.
		do {
			$progress = false;
			foreach ( $todo as $i => $id ) {
				if ( true === $registry->enable( $id ) ) {
					unset( $todo[ $i ] );
					$progress = true;
				}
			}
		} while ( $progress && $todo );
	}
}
