<?php
/**
 * Discovers, installs, enables and boots modules.
 *
 * Modules are found in:
 *  1. <plugin>/modules/<id>/module.php            (bundled)
 *  2. wp-content/mafia-pbbg-modules/<id>/module.php   (your own, survives updates; overrides bundled modules with the same id)
 *  3. Other plugins: add_action( 'dfmg_register_modules', fn( $registry ) => $registry->add( '/path/to/module.php' ) );
 *
 * Modules with the header "Premium: yes" are bought on the DigiFalk store. They only run when
 * the dfmg_module_runnable filter allows it: Mafia PBBG Engine Extended does that for modules
 * with an activated license.
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Module;

use DigiFalk\MafiaPBBGEngine\DB;
use DigiFalk\MafiaPBBGEngine\Settings;

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
		'premium'     => 'Premium',
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
		$info['premium']  = 'yes' === strtolower( trim( $info['premium'] ) );
		if ( $info['premium'] ) {
			$info['default']  = false;
			$info['required'] = false;
		}
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
		return array_values( array_filter( $stored, array( $this, 'runnable' ) ) );
	}

	/**
	 * Exists, and when premium: licensed for this site.
	 */
	public function runnable( string $id ): bool {
		$info = $this->available()[ $id ] ?? null;
		if ( ! $info ) {
			return false;
		}
		/**
		 * Whether a module may run. Premium modules only run when a plugin (Mafia PBBG Engine
		 * Extended) confirms their license.
		 *
		 * @param bool   $runnable
		 * @param string $id
		 * @param array  $info
		 */
		return (bool) apply_filters( 'dfmg_module_runnable', empty( $info['premium'] ), $id, $info );
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
		// A module that breaks (for example one made for an older version of the engine) is
		// skipped and reported in the admin, instead of taking the whole site down.
		try {
			$module = include $info['file'];
		} catch ( \Throwable $e ) {
			self::report_error( $id, $e->getMessage() );
			return null;
		}
		if ( ! $module instanceof Module ) {
			return null;
		}
		$module->setup( $id, dirname( $info['file'] ), $info );
		$this->loaded[ $id ] = $module;
		self::clear_error( $id );
		return $module;
	}

	const OPTION_ERRORS = 'dfmg_module_errors';

	/**
	 * Modules that could not be loaded: id => error message.
	 */
	public static function errors(): array {
		return (array) get_option( self::OPTION_ERRORS, array() );
	}

	private static function report_error( string $id, string $message ): void {
		$errors = self::errors();
		if ( ( $errors[ $id ] ?? '' ) !== $message ) {
			$errors[ $id ] = $message;
			update_option( self::OPTION_ERRORS, $errors, false );
		}
	}

	private static function clear_error( string $id ): void {
		$errors = self::errors();
		if ( isset( $errors[ $id ] ) ) {
			unset( $errors[ $id ] );
			update_option( self::OPTION_ERRORS, $errors, false );
		}
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
			return new \WP_Error( 'module', __( 'Module not found.', 'mafia-pbbg-engine' ) );
		}
		if ( ! $this->runnable( $id ) ) {
			return new \WP_Error( 'module', __( 'This premium module needs Mafia PBBG Engine Extended and an active license. Enter your license key on the Modules screen.', 'mafia-pbbg-engine' ) );
		}
		$enabled = $this->enabled_ids();
		foreach ( $info['requires'] as $dep ) {
			if ( ! in_array( $dep, $enabled, true ) ) {
				/* translators: 1: module, 2: required module */
				return new \WP_Error( 'module', sprintf( __( '%1$s requires the module "%2$s". Enable that one first.', 'mafia-pbbg-engine' ), $info['name'], $dep ) );
			}
		}
		$module = $this->load( $id );
		if ( ! $module ) {
			$error = self::errors()[ $id ] ?? '';
			return new \WP_Error(
				'module',
				'' === $error
					? __( 'Module could not be loaded.', 'mafia-pbbg-engine' )
					/* translators: %s: error message */
					: sprintf( __( 'Module could not be loaded. It may be made for another version of Mafia PBBG Engine; update the module. Error: %s', 'mafia-pbbg-engine' ), $error )
			);
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
			return new \WP_Error( 'module', __( 'Module not found.', 'mafia-pbbg-engine' ) );
		}
		if ( $info['required'] ) {
			return new \WP_Error( 'module', __( 'This module is required and can\'t be disabled.', 'mafia-pbbg-engine' ) );
		}
		$enabled = $this->enabled_ids();
		foreach ( $enabled as $other ) {
			if ( in_array( $id, $this->info( $other )['requires'], true ) ) {
				/* translators: %s: module name */
				return new \WP_Error( 'module', sprintf( __( 'The module "%s" requires this module. Disable that one first.', 'mafia-pbbg-engine' ), $this->info( $other )['name'] ) );
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
