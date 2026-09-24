<?php
/**
 * Discovers, installs, enables and boots modules.
 *
 * Modules are found in:
 *  1. <plugin>/modules/<id>/module.php            (bundled)
 *  2. wp-content/underworld-modules/<id>/module.php   (your own, survives updates; overrides bundled modules with the same id)
 *  3. Other plugins: add_action( 'dfmg_register_modules', fn( $registry ) => $registry->add( '/path/to/module.php' ) );
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Module;

use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Settings;

defined( 'ABSPATH' ) || exit;

final class Registry {

	const OPTION_ENABLED   = 'dfmg_enabled_modules';
	const OPTION_INSTALLED = 'dfmg_installed_modules';

	const HEADERS = array(
		'name'        => 'Module Name',
		'description' => 'Description',
		'version'     => 'Version',
		'author'      => 'Author',
		'requires'    => 'Requires',
		'default'     => 'Default',
		'required'    => 'Required',
	);

	/** @var array id => info */
	private $available = array();

	/** @var Module[] id => loaded instance */
	private $loaded = array();

	/** @var Module[] id => booted instance */
	private $booted = array();

	/** @var bool */
	private $discovered = false;

	public function discover(): void {
		if ( $this->discovered ) {
			return;
		}
		$this->discovered = true;
		foreach ( (array) glob( DFMG_DIR . 'modules/*/module.php' ) as $file ) {
			$this->add( $file, 'bundled' );
		}
		if ( is_dir( DFMG_CUSTOM_MODULES_DIR ) ) {
			foreach ( (array) glob( trailingslashit( DFMG_CUSTOM_MODULES_DIR ) . '*/module.php' ) as $file ) {
				$this->add( $file, 'custom' );
			}
		}
		do_action( 'dfmg_register_modules', $this );
		uasort(
			$this->available,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);
	}

	/**
	 * Register a module file. The id is the directory name.
	 */
	public function add( string $file, string $source = 'plugin' ): void {
		if ( ! is_readable( $file ) ) {
			return;
		}
		$id = sanitize_key( basename( dirname( $file ) ) );
		if ( ! $id ) {
			return;
		}
		$info = get_file_data( $file, self::HEADERS );
		if ( '' === $info['name'] ) {
			return;
		}
		$info['id']       = $id;
		$info['file']     = $file;
		$info['source']   = $source;
		$info['requires'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $info['requires'] ) ) ) );
		$info['default']  = 'no' !== strtolower( trim( $info['default'] ) );
		$info['required'] = 'yes' === strtolower( trim( $info['required'] ) );
		$this->available[ $id ] = $info;
	}

	public function available(): array {
		$this->discover();
		return $this->available;
	}

	public function exists( string $id ): bool {
		return isset( $this->available()[ $id ] );
	}

	public function info( string $id ): ?array {
		return $this->available()[ $id ] ?? null;
	}

	/**
	 * Enabled module ids.
	 */
	public function enabled_ids(): array {
		$stored = get_option( self::OPTION_ENABLED, null );
		if ( ! is_array( $stored ) ) {
			$stored = array();
			foreach ( $this->available() as $id => $info ) {
				if ( $info['default'] || $info['required'] ) {
					$stored[] = $id;
				}
			}
		}
		foreach ( $this->available() as $id => $info ) {
			if ( $info['required'] && ! in_array( $id, $stored, true ) ) {
				$stored[] = $id;
			}
		}
		return array_values( array_filter( $stored, array( $this, 'exists' ) ) );
	}

	public function is_enabled( string $id ): bool {
		return isset( $this->booted[ $id ] ) || in_array( $id, $this->enabled_ids(), true );
	}

	/**
	 * Load a module file (without booting it).
	 */
	public function load( string $id ): ?Module {
		if ( isset( $this->loaded[ $id ] ) ) {
			return $this->loaded[ $id ];
		}
		$info = $this->info( $id );
		if ( ! $info ) {
			return null;
		}
		$module = include $info['file'];
		if ( ! $module instanceof Module ) {
			return null;
		}
		$module->setup( $id, dirname( $info['file'] ), $info );
		$this->loaded[ $id ] = $module;
		return $module;
	}

	/**
	 * Enabled ids ordered so dependencies come first; modules with missing dependencies are left out.
	 */
	private function boot_order(): array {
		$enabled = $this->enabled_ids();
		$ordered = array();
		$visit   = function ( $id, $stack = array() ) use ( &$visit, &$ordered, $enabled ) {
			if ( in_array( $id, $ordered, true ) ) {
				return true;
			}
			if ( in_array( $id, $stack, true ) || ! in_array( $id, $enabled, true ) ) {
				return false;
			}
			$stack[] = $id;
			foreach ( $this->info( $id )['requires'] as $dep ) {
				if ( ! $visit( $dep, $stack ) ) {
					return false;
				}
			}
			$ordered[] = $id;
			return true;
		};
		foreach ( $enabled as $id ) {
			$visit( $id );
		}
		return $ordered;
	}

	public function boot_enabled(): void {
		$installed = (array) get_option( self::OPTION_INSTALLED, array() );
		foreach ( $this->boot_order() as $id ) {
			$module = $this->load( $id );
			if ( ! $module ) {
				continue;
			}
			// Install when enabled by default (fresh install) or after a version change.
			if ( ( $installed[ $id ] ?? '' ) !== $module->info( 'version' ) . '|' . DFMG_DB_VERSION ) {
				$this->install( $module );
			}
			foreach ( $module->settings_fields() as $key => $field ) {
				Settings::register_default( $key, $field['default'] ?? '' );
			}
			$module->boot();
			$this->booted[ $id ] = $module;
		}
		do_action( 'dfmg_modules_booted', $this );
	}

	/**
	 * Create/upgrade tables and seed data the first time.
	 */
	public function install( Module $module ): void {
		DB::create_tables( $module->schema() );
		$installed = (array) get_option( self::OPTION_INSTALLED, array() );
		if ( ! isset( $installed[ $module->id() ] ) ) {
			$module->seed();
		}
		$installed[ $module->id() ] = $module->info( 'version' ) . '|' . DFMG_DB_VERSION;
		update_option( self::OPTION_INSTALLED, $installed );
	}

	/**
	 * @return true|\WP_Error
	 */
	public function enable( string $id ) {
		$info = $this->info( $id );
		if ( ! $info ) {
			return new \WP_Error( 'module', __( 'Module not found.', 'underworld-empire' ) );
		}
		$enabled = $this->enabled_ids();
		foreach ( $info['requires'] as $dep ) {
			if ( ! in_array( $dep, $enabled, true ) ) {
				/* translators: 1: module, 2: required module */
				return new \WP_Error( 'module', sprintf( __( '%1$s requires the module "%2$s". Enable that one first.', 'underworld-empire' ), $info['name'], $dep ) );
			}
		}
		$module = $this->load( $id );
		if ( ! $module ) {
			return new \WP_Error( 'module', __( 'Module could not be loaded.', 'underworld-empire' ) );
		}
		$this->install( $module );
		if ( ! in_array( $id, $enabled, true ) ) {
			$enabled[] = $id;
		}
		update_option( self::OPTION_ENABLED, $enabled );
		do_action( 'dfmg_module_enabled', $id );
		return true;
	}

	/**
	 * @return true|\WP_Error
	 */
	public function disable( string $id ) {
		$info = $this->info( $id );
		if ( ! $info ) {
			return new \WP_Error( 'module', __( 'Module not found.', 'underworld-empire' ) );
		}
		if ( $info['required'] ) {
			return new \WP_Error( 'module', __( 'This module is required and can\'t be disabled.', 'underworld-empire' ) );
		}
		$enabled = $this->enabled_ids();
		foreach ( $enabled as $other ) {
			if ( in_array( $id, $this->info( $other )['requires'], true ) ) {
				/* translators: %s: module name */
				return new \WP_Error( 'module', sprintf( __( 'The module "%s" requires this module. Disable that one first.', 'underworld-empire' ), $this->info( $other )['name'] ) );
			}
		}
		update_option( self::OPTION_ENABLED, array_values( array_diff( $enabled, array( $id ) ) ) );
		do_action( 'dfmg_module_disabled', $id );
		return true;
	}

	/**
	 * Booted (active) module by id.
	 */
	public function get( string $id ): ?Module {
		return $this->booted[ $id ] ?? null;
	}

	/**
	 * @return Module[]
	 */
	public function active(): array {
		return $this->booted;
	}

	/**
	 * Load every available module (used by uninstall and admin tools).
	 *
	 * @return Module[]
	 */
	public function load_all(): array {
		$out = array();
		foreach ( array_keys( $this->available() ) as $id ) {
			$module = $this->load( $id );
			if ( $module ) {
				$out[ $id ] = $module;
			}
		}
		return $out;
	}
}
