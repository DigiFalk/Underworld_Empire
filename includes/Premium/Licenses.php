<?php
/**
 * Premium modules: catalogue, license keys, download and updates.
 *
 * Premium modules are sold on the DigiFalk store (https://digifalk.com), which runs the
 * "DigiFalk Licenses" plugin. The buyer enters the license key on the Modules screen; this
 * class activates the key for this site, downloads the module zip, checks its SHA-256 (and
 * Ed25519 signature when a public key is configured) and installs it in
 * wp-content/underworld-modules/<product>/. The product slug is the module id.
 *
 * Store API (namespace digifalk-licenses/v1), see docs/PREMIUM.md:
 *   GET  /catalog?client=underworld-empire
 *   POST /activate   { license_key, product, site_url, client, client_version }
 *   POST /check      { license_key, product, activation_id, activation_secret, installed_version }
 *   POST /deactivate { license_key, product, activation_id, activation_secret }
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Premium;

use DigiFalk\UnderworldEmpire\Plugin;

defined( 'ABSPATH' ) || exit;

final class Licenses {

	const OPTION        = 'dfmg_licenses';
	const CATALOG_CACHE = 'dfmg_premium_catalog';
	const CLIENT        = 'underworld-empire';
	const API_NAMESPACE = 'digifalk-licenses/v1';

	public static function init(): void {
		add_action( 'admin_post_dfmg_license_activate', array( __CLASS__, 'handle_activate' ) );
		add_action( 'admin_post_dfmg_license_deactivate', array( __CLASS__, 'handle_deactivate' ) );
		add_action( 'admin_post_dfmg_license_update', array( __CLASS__, 'handle_update' ) );
		add_action( 'admin_post_dfmg_license_refresh', array( __CLASS__, 'handle_refresh' ) );
		add_action( 'dfmg_daily_license_check', array( __CLASS__, 'check_all' ) );
		if ( ! wp_next_scheduled( 'dfmg_daily_license_check' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'dfmg_daily_license_check' );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Configuration                                                        */
	/* ------------------------------------------------------------------ */

	/**
	 * Store URL. Override with define( 'DFMG_STORE_URL', '…' ) or the dfmg_store_url filter.
	 */
	public static function store_url(): string {
		$url = defined( 'DFMG_STORE_URL' ) ? (string) DFMG_STORE_URL : 'https://digifalk.com';
		return untrailingslashit( (string) apply_filters( 'dfmg_store_url', $url ) );
	}

	private static function api( string $route ): string {
		return self::store_url() . '/wp-json/' . self::API_NAMESPACE . '/' . ltrim( $route, '/' );
	}

	/**
	 * Base64 Ed25519 public key of the store. When set, every package must carry a valid
	 * signature. Set it with define( 'DFMG_STORE_PUBLIC_KEY', '…' ) or the filter.
	 */
	public static function public_key(): string {
		$key = defined( 'DFMG_STORE_PUBLIC_KEY' ) ? (string) DFMG_STORE_PUBLIC_KEY : '';
		return (string) apply_filters( 'dfmg_store_public_key', $key );
	}

	/* ------------------------------------------------------------------ */
	/* Catalogue                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * Premium modules for sale: the store catalogue (cached for 12 hours), merged over the
	 * placeholders shipped with the plugin (premium/catalog.php). Keyed by product slug.
	 */
	public static function catalog( bool $refresh = false ): array {
		$bundled = file_exists( DFMG_DIR . 'premium/catalog.php' ) ? (array) include DFMG_DIR . 'premium/catalog.php' : array();
		$remote  = $refresh ? false : get_transient( self::CATALOG_CACHE );
		if ( false === $remote ) {
			$remote   = array();
			$response = wp_remote_get(
				add_query_arg( 'client', self::CLIENT, self::api( 'catalog' ) ),
				array(
					'timeout' => 8,
					'headers' => array( 'Accept' => 'application/json' ),
				)
			);
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( 200 === $code && isset( $body['products'] ) && is_array( $body['products'] ) ) {
				foreach ( $body['products'] as $product ) {
					$product = self::clean_product( (array) $product );
					if ( $product ) {
						$remote[ $product['product'] ] = $product;
					}
				}
			}
			// Cache failures shorter, so a store outage doesn't hide the catalogue for long.
			set_transient( self::CATALOG_CACHE, $remote, $remote ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		}
		$catalog = array();
		foreach ( $bundled as $product ) {
			$product = self::clean_product( (array) $product );
			if ( $product ) {
				$catalog[ $product['product'] ] = $product;
			}
		}
		foreach ( (array) $remote as $slug => $product ) {
			$catalog[ $slug ] = array_merge( $catalog[ $slug ] ?? array(), $product );
		}
		return (array) apply_filters( 'dfmg_premium_catalog', $catalog );
	}

	private static function clean_product( array $p ): ?array {
		$slug = sanitize_key( (string) ( $p['product'] ?? '' ) );
		if ( '' === $slug || '' === (string) ( $p['name'] ?? '' ) ) {
			return null;
		}
		return array(
			'product'     => $slug,
			'name'        => sanitize_text_field( (string) $p['name'] ),
			'description' => sanitize_text_field( (string) ( $p['description'] ?? '' ) ),
			'version'     => sanitize_text_field( (string) ( $p['version'] ?? '' ) ),
			'price'       => sanitize_text_field( (string) ( $p['price'] ?? '' ) ),
			'buy_url'     => esc_url_raw( (string) ( $p['buy_url'] ?? self::store_url() ) ),
			'icon'        => sanitize_key( (string) ( $p['icon'] ?? '' ) ),
			'requires'    => sanitize_text_field( (string) ( $p['requires'] ?? '' ) ),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Stored licenses                                                      */
	/* ------------------------------------------------------------------ */

	public static function all(): array {
		return (array) get_option( self::OPTION, array() );
	}

	public static function get( string $product ): ?array {
		return self::all()[ $product ] ?? null;
	}

	private static function put( string $product, ?array $data ): void {
		$all = self::all();
		if ( null === $data ) {
			unset( $all[ $product ] );
		} else {
			$all[ $product ] = $data;
		}
		update_option( self::OPTION, $all, false );
	}

	/**
	 * May this premium module run? Only with an activated, non-revoked license.
	 */
	public static function is_licensed( string $product ): bool {
		$license = self::get( $product );
		$ok      = $license && ! empty( $license['activation_id'] ) && 'revoked' !== ( $license['status'] ?? '' );
		return (bool) apply_filters( 'dfmg_premium_is_licensed', $ok, $product, $license );
	}

	public static function mask( string $key ): string {
		$key = trim( $key );
		return strlen( $key ) > 8 ? substr( $key, 0, 4 ) . str_repeat( '•', 6 ) . substr( $key, -4 ) : str_repeat( '•', strlen( $key ) );
	}

	/* ------------------------------------------------------------------ */
	/* Store API                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * POST to the store. Returns the decoded body or a WP_Error with the store's message.
	 *
	 * @return array|\WP_Error
	 */
	private static function request( string $route, array $body ) {
		if ( 0 !== strpos( self::store_url(), 'https://' ) && ! apply_filters( 'dfmg_store_allow_http', false ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The store must use HTTPS.', 'underworld-empire' ) );
		}
		$response = wp_remote_post(
			self::api( $route ),
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => (string) wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			/* translators: %s: error */
			return new \WP_Error( 'dfmg_store', sprintf( __( 'The store could not be reached: %s', 'underworld-empire' ), $response->get_error_message() ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			$message = is_array( $data ) && ! empty( $data['message'] ) ? sanitize_text_field( (string) $data['message'] ) : __( 'The store gave an unexpected answer. Please try again later.', 'underworld-empire' );
			return new \WP_Error( is_array( $data ) && ! empty( $data['code'] ) ? sanitize_key( (string) $data['code'] ) : 'dfmg_store', $message );
		}
		return $data;
	}

	private static function site_payload(): array {
		global $wp_version;
		return array(
			'site_url'       => home_url( '/' ),
			'client'         => self::CLIENT,
			'client_version' => DFMG_VERSION,
			'wp_version'     => $wp_version,
			'php_version'    => PHP_VERSION,
		);
	}

	/**
	 * Activate a key for this site, then download and install the module.
	 *
	 * @return true|\WP_Error
	 */
	public static function activate( string $product, string $key ) {
		// Keys are shown in capitals in the purchase email; the store accepts any case.
		$key = strtoupper( trim( $key ) );
		if ( '' === $key || ! preg_match( '/^[A-Za-z0-9\-]{8,64}$/', $key ) ) {
			return new \WP_Error( 'dfmg_store', __( 'This doesn\'t look like a license key. Copy it from the email you received after buying.', 'underworld-empire' ) );
		}
		$data = self::request(
			'activate',
			array_merge(
				array(
					'license_key' => $key,
					'product'     => $product,
				),
				self::site_payload()
			)
		);
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['activation_id'] ) || empty( $data['activation_secret'] ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The store gave an unexpected answer. Please try again later.', 'underworld-empire' ) );
		}
		self::put(
			$product,
			array(
				'key'               => $key,
				'activation_id'     => sanitize_text_field( (string) $data['activation_id'] ),
				'activation_secret' => sanitize_text_field( (string) $data['activation_secret'] ),
				'status'            => sanitize_key( (string) ( $data['license']['status'] ?? 'active' ) ),
				'type'              => sanitize_key( (string) ( $data['license']['type'] ?? 'lifetime' ) ),
				'activated_at'      => time(),
				'latest'            => '',
				'checked_at'        => 0,
			)
		);
		$installed = self::install( $product );
		if ( is_wp_error( $installed ) ) {
			return $installed;
		}
		return true;
	}

	/**
	 * Ask the store for the license status and the latest version (with a download link).
	 *
	 * @return array|\WP_Error
	 */
	public static function check( string $product ) {
		$license = self::get( $product );
		if ( ! $license ) {
			return new \WP_Error( 'dfmg_store', __( 'No license for this module.', 'underworld-empire' ) );
		}
		$info = Plugin::instance()->modules->info( $product );
		$data = self::request(
			'check',
			array_merge(
				array(
					'license_key'       => $license['key'],
					'product'           => $product,
					'activation_id'     => $license['activation_id'],
					'activation_secret' => $license['activation_secret'],
					'installed_version' => $info['version'] ?? '',
				),
				self::site_payload()
			)
		);
		if ( is_wp_error( $data ) ) {
			// A revoked or deleted license stops the module; network problems don't.
			if ( in_array( $data->get_error_code(), array( 'license_revoked', 'license_not_found', 'activation_not_found' ), true ) ) {
				$license['status'] = 'revoked';
				self::put( $product, $license );
			}
			return $data;
		}
		$license['status']     = sanitize_key( (string) ( $data['license']['status'] ?? 'active' ) );
		$license['latest']     = sanitize_text_field( (string) ( $data['latest']['version'] ?? '' ) );
		$license['changelog']  = sanitize_textarea_field( (string) ( $data['latest']['changelog'] ?? '' ) );
		$license['checked_at'] = time();
		self::put( $product, $license );
		return $data;
	}

	/**
	 * Daily: refresh status and latest versions of all licensed modules.
	 */
	public static function check_all(): void {
		foreach ( array_keys( self::all() ) as $product ) {
			self::check( (string) $product );
		}
		delete_transient( self::CATALOG_CACHE );
	}

	public static function update_available( string $product ): bool {
		$license = self::get( $product );
		$info    = Plugin::instance()->modules->info( $product );
		return $license && $info && ! empty( $license['latest'] ) && version_compare( $license['latest'], (string) $info['version'], '>' );
	}

	/**
	 * Download the latest package of a licensed module and install it.
	 *
	 * @return true|\WP_Error
	 */
	public static function install( string $product ) {
		$data = self::check( $product );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$package = (array) ( $data['package'] ?? array() );
		$url     = esc_url_raw( (string) ( $package['url'] ?? '' ) );
		$sha256  = strtolower( (string) ( $package['sha256'] ?? '' ) );
		if ( '' === $url || ! preg_match( '/^[a-f0-9]{64}$/', $sha256 ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The store did not send a download for this module.', 'underworld-empire' ) );
		}
		// The package must come from the store itself.
		if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( self::store_url(), PHP_URL_HOST ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The download link does not point to the store.', 'underworld-empire' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = download_url( $url, 60 );
		if ( is_wp_error( $tmp ) ) {
			/* translators: %s: error */
			return new \WP_Error( 'dfmg_store', sprintf( __( 'Downloading the module failed: %s', 'underworld-empire' ), $tmp->get_error_message() ) );
		}
		$verified = self::verify( $tmp, $sha256, (string) ( $package['signature'] ?? '' ) );
		if ( is_wp_error( $verified ) ) {
			wp_delete_file( $tmp );
			return $verified;
		}
		$result = self::unpack( $tmp, $product );
		wp_delete_file( $tmp );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		do_action( 'dfmg_premium_installed', $product, $data['latest']['version'] ?? '' );
		return true;
	}

	/**
	 * SHA-256 always; Ed25519 signature over the hex hash when a public key is configured.
	 *
	 * @return true|\WP_Error
	 */
	private static function verify( string $file, string $sha256, string $signature ) {
		if ( ! hash_equals( $sha256, (string) hash_file( 'sha256', $file ) ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The downloaded file is damaged (checksum mismatch). Please try again.', 'underworld-empire' ) );
		}
		$public = self::public_key();
		if ( '' === $public ) {
			return true;
		}
		$sig = base64_decode( $signature, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$key = base64_decode( $public, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! function_exists( 'sodium_crypto_sign_verify_detached' ) || false === $sig || false === $key
			|| SODIUM_CRYPTO_SIGN_BYTES !== strlen( $sig ) || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen( $key )
			|| ! sodium_crypto_sign_verify_detached( $sig, $sha256, $key ) ) {
			return new \WP_Error( 'dfmg_store', __( 'The download is not signed by the DigiFalk store and was not installed.', 'underworld-empire' ) );
		}
		return true;
	}

	/**
	 * Unzip into a temporary folder, check it is the right module, then move it in place.
	 *
	 * @return true|\WP_Error
	 */
	private static function unpack( string $zip, string $product ) {
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( ! WP_Filesystem() ) {
			return new \WP_Error( 'dfmg_store', __( 'WordPress can\'t write files on this server. Check the file permissions of wp-content.', 'underworld-empire' ) );
		}
		$work = trailingslashit( get_temp_dir() ) . 'dfmg-premium-' . wp_generate_password( 8, false, false );
		$res  = unzip_file( $zip, $work );
		if ( is_wp_error( $res ) ) {
			$wp_filesystem->delete( $work, true );
			/* translators: %s: error */
			return new \WP_Error( 'dfmg_store', sprintf( __( 'The module could not be unpacked: %s', 'underworld-empire' ), $res->get_error_message() ) );
		}
		// The zip holds one folder named after the product, with a module.php inside.
		$source = trailingslashit( $work ) . $product;
		$header = is_readable( $source . '/module.php' ) ? get_file_data( $source . '/module.php', array( 'name' => 'Module Name', 'premium' => 'Premium' ) ) : array();
		if ( empty( $header['name'] ) || 'yes' !== strtolower( trim( (string) $header['premium'] ) ) ) {
			$wp_filesystem->delete( $work, true );
			return new \WP_Error( 'dfmg_store', __( 'The download does not contain this premium module.', 'underworld-empire' ) );
		}
		if ( ! wp_mkdir_p( DFMG_CUSTOM_MODULES_DIR ) ) {
			$wp_filesystem->delete( $work, true );
			return new \WP_Error( 'dfmg_store', __( 'The modules folder can\'t be created.', 'underworld-empire' ) );
		}
		$target = trailingslashit( DFMG_CUSTOM_MODULES_DIR ) . $product;
		$backup = $target . '.old-' . time();
		if ( $wp_filesystem->exists( $target ) && ! $wp_filesystem->move( $target, $backup, true ) ) {
			$wp_filesystem->delete( $work, true );
			return new \WP_Error( 'dfmg_store', __( 'The old version could not be replaced.', 'underworld-empire' ) );
		}
		if ( ! $wp_filesystem->move( $source, $target, true ) && ! copy_dir( $source, $target ) ) {
			if ( $wp_filesystem->exists( $backup ) ) {
				$wp_filesystem->move( $backup, $target, true );
			}
			$wp_filesystem->delete( $work, true );
			return new \WP_Error( 'dfmg_store', __( 'The module could not be installed.', 'underworld-empire' ) );
		}
		if ( $wp_filesystem->exists( $backup ) ) {
			$wp_filesystem->delete( $backup, true );
		}
		$wp_filesystem->delete( $work, true );
		if ( function_exists( 'opcache_reset' ) ) {
			@opcache_reset(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		return true;
	}

	/**
	 * Free the activation on the store and remove the module files.
	 *
	 * @return true|\WP_Error
	 */
	public static function deactivate( string $product ) {
		$license = self::get( $product );
		if ( ! $license ) {
			return true;
		}
		$data = self::request(
			'deactivate',
			array_merge(
				array(
					'license_key'       => $license['key'],
					'product'           => $product,
					'activation_id'     => $license['activation_id'],
					'activation_secret' => $license['activation_secret'],
				),
				self::site_payload()
			)
		);
		// If the store already forgot the activation we still clean up locally.
		if ( is_wp_error( $data ) && ! in_array( $data->get_error_code(), array( 'activation_not_found', 'license_not_found', 'license_revoked' ), true ) ) {
			return $data;
		}
		$registry = Plugin::instance()->modules;
		if ( $registry->is_enabled( $product ) ) {
			$registry->disable( $product );
		}
		self::put( $product, null );
		self::remove_files( $product );
		return true;
	}

	private static function remove_files( string $product ): void {
		global $wp_filesystem;
		$target = trailingslashit( DFMG_CUSTOM_MODULES_DIR ) . sanitize_key( $product );
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( is_dir( $target ) && WP_Filesystem() ) {
			$wp_filesystem->delete( $target, true );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Admin actions                                                        */
	/* ------------------------------------------------------------------ */

	private static function guard( string $action ): string {
		$product = sanitize_key( wp_unslash( $_POST['product'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( $action . '_' . $product );
		return $product;
	}

	private static function back( $result, string $success, string $product = '' ): void {
		if ( is_wp_error( $result ) ) {
			set_transient( 'dfmg_admin_error_' . get_current_user_id(), $result->get_error_message(), 60 );
		} else {
			set_transient( 'dfmg_admin_success_' . get_current_user_id(), $success, 60 );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dfmg-modules' . ( $product ? '#premium-' . $product : '' ) ) );
		exit;
	}

	public static function handle_activate(): void {
		$product = self::guard( 'dfmg_license_activate' );
		$key     = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result  = self::activate( $product, $key );
		if ( true === $result ) {
			// Discover the new module and switch it on.
			$registry = Plugin::instance()->modules;
			$registry->add( trailingslashit( DFMG_CUSTOM_MODULES_DIR ) . $product . '/module.php', 'custom' );
			$enabled = $registry->enable( $product );
			if ( is_wp_error( $enabled ) ) {
				/* translators: %s: reason */
				$result = new \WP_Error( 'dfmg_store', sprintf( __( 'The module was installed but could not be switched on: %s', 'underworld-empire' ), $enabled->get_error_message() ) );
			}
		}
		self::back( $result, __( 'License activated. The module is installed and switched on.', 'underworld-empire' ), $product );
	}

	public static function handle_deactivate(): void {
		$product = self::guard( 'dfmg_license_deactivate' );
		self::back( self::deactivate( $product ), __( 'License deactivated and module removed. You can use the key on another site now.', 'underworld-empire' ), $product );
	}

	public static function handle_update(): void {
		$product  = self::guard( 'dfmg_license_update' );
		$registry = Plugin::instance()->modules;
		// First download (for example when the download failed right after activating).
		$first    = ! $registry->info( $product );
		$result   = self::install( $product );
		$message  = __( 'The module was updated.', 'underworld-empire' );
		if ( true === $result ) {
			$registry->add( trailingslashit( DFMG_CUSTOM_MODULES_DIR ) . $product . '/module.php', 'custom' );
			if ( $first ) {
				$enabled = $registry->enable( $product );
				$message = __( 'The module is installed and switched on.', 'underworld-empire' );
				if ( is_wp_error( $enabled ) ) {
					/* translators: %s: reason */
					$result = new \WP_Error( 'dfmg_store', sprintf( __( 'The module was installed but could not be switched on: %s', 'underworld-empire' ), $enabled->get_error_message() ) );
				}
			} else {
				$module = $registry->load( $product );
				if ( $module && $registry->is_enabled( $product ) ) {
					$registry->install( $module );
				}
			}
		}
		self::back( $result, $message, $product );
	}

	public static function handle_refresh(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_license_refresh' );
		self::check_all();
		self::catalog( true );
		self::back( true, __( 'Premium modules refreshed from the store.', 'underworld-empire' ) );
	}
}
