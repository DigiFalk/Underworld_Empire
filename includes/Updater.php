<?php
/**
 * Self-updates from GitHub releases through the standard WordPress plugin updater.
 *
 * The plugin header contains "Update URI: https://github.com/...", so WordPress (5.8+)
 * asks the update_plugins_github.com filter for update information instead of
 * WordPress.org. We answer with the latest GitHub release and its underworld-empire.zip asset.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Updater {

	const CACHE_KEY  = 'dfmg_github_release';
	const ASSET_NAME = 'underworld-empire.zip';
	const SLUG       = 'underworld-empire';

	public static function init(): void {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check' ), 10, 4 );
		// Fallback: WordPress skips the hook above when WordPress.org can't be reached.
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder' ), 10, 4 );
		add_filter( 'http_request_args', array( __CLASS__, 'auth_download' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ), 10, 0 );
		add_filter( 'plugin_action_links_' . plugin_basename( DFMG_FILE ), array( __CLASS__, 'action_link' ) );
		add_action( 'admin_post_dfmg_check_updates', array( __CLASS__, 'force_check' ) );
	}

	public static function repo(): string {
		return defined( 'DFMG_UPDATE_REPO' ) ? (string) DFMG_UPDATE_REPO : 'DigiFalk/WP_Maffia_Game';
	}

	private static function token(): string {
		return defined( 'DFMG_GITHUB_TOKEN' ) ? (string) DFMG_GITHUB_TOKEN : '';
	}

	private static function headers(): array {
		$headers = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'Underworld-Empire/' . DFMG_VERSION . '; ' . home_url(),
		);
		if ( self::token() ) {
			$headers['Authorization'] = 'Bearer ' . self::token();
		}
		return $headers;
	}

	/**
	 * Latest published release, cached for 6 hours (1 hour after a failed request).
	 */
	public static function release( bool $force = false ): ?array {
		$cached = $force ? false : get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached['version'] ? $cached : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::repo() . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => self::headers(),
			)
		);
		$data = is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response )
			? null
			: json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_site_transient( self::CACHE_KEY, array( 'version' => '' ), HOUR_IN_SECONDS );
			return null;
		}

		$package = '';
		foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
			if ( self::ASSET_NAME === ( $asset['name'] ?? '' ) ) {
				// Private repositories need the API url (with auth), public ones the direct download.
				$package = self::token() ? (string) $asset['url'] : (string) $asset['browser_download_url'];
			}
		}

		$release = array(
			'version'   => ltrim( (string) $data['tag_name'], 'vV' ),
			'package'   => $package ?: (string) ( $data['zipball_url'] ?? '' ),
			'url'       => (string) ( $data['html_url'] ?? '' ),
			'notes'     => (string) ( $data['body'] ?? '' ),
			'published' => (string) ( $data['published_at'] ?? '' ),
		);
		set_site_transient( self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	/**
	 * Answer WordPress' update check for this plugin.
	 *
	 * @param array|false $update
	 * @return array|false
	 */
	public static function check( $update, $plugin_data, $plugin_file, $locales ) {
		if ( plugin_basename( DFMG_FILE ) !== $plugin_file ) {
			return $update;
		}
		$release = self::release();
		if ( ! $release || ! $release['package'] ) {
			return $update;
		}
		return array(
			'id'           => self::repo(),
			'slug'         => self::SLUG,
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'new_version'  => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => '6.0',
			'requires_php' => '7.4',
		);
	}

	/**
	 * Make sure a newer release always shows up in the update data, also when
	 * WordPress.org was unreachable during the regular check.
	 *
	 * @param mixed $transient
	 * @return mixed
	 */
	public static function inject( $transient ) {
		$file = plugin_basename( DFMG_FILE );
		if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}
		if ( isset( $transient->response[ $file ] ) ) {
			return $transient;
		}
		$update = self::check( false, array(), $file, array() );
		if ( ! $update || ! version_compare( $update['version'], DFMG_VERSION, '>' ) ) {
			return $transient;
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$transient->response[ $file ] = (object) $update;
		unset( $transient->no_update[ $file ] );
		return $transient;
	}

	/**
	 * Details shown in the "View version x.y.z details" popup.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object|array
	 */
	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::release();
		if ( ! $release ) {
			return $result;
		}
		return (object) array(
			'name'          => 'Underworld Empire',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => '<a href="https://github.com/DigiFalk">DigiFalk</a>',
			'homepage'      => 'https://github.com/' . self::repo(),
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'last_updated'  => $release['published'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => esc_html__( 'A complete, modular mafia browser game (PBBG) for WordPress.', 'underworld-empire' ),
				'changelog'   => self::markdown( $release['notes'] ),
			),
		);
	}

	/**
	 * Very small Markdown to HTML conversion for release notes.
	 */
	private static function markdown( string $text ): string {
		$html = '';
		$list = false;
		foreach ( preg_split( '/\r?\n/', esc_html( $text ) ) as $line ) {
			$line = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line );
			$line = preg_replace( '/`(.+?)`/', '<code>$1</code>', $line );
			if ( preg_match( '/^\s*[-*]\s+(.*)$/', $line, $m ) ) {
				$html .= ( $list ? '' : '<ul>' ) . '<li>' . $m[1] . '</li>';
				$list  = true;
				continue;
			}
			if ( $list ) {
				$html .= '</ul>';
				$list  = false;
			}
			if ( preg_match( '/^#+\s*(.*)$/', $line, $m ) ) {
				$html .= '<h4>' . $m[1] . '</h4>';
			} elseif ( '' !== trim( $line ) ) {
				$html .= '<p>' . $line . '</p>';
			}
		}
		return $html . ( $list ? '</ul>' : '' );
	}

	/**
	 * Make sure the unpacked update ends up in the folder the plugin is installed in,
	 * whatever the name of the folder inside the zip.
	 *
	 * @param string|\WP_Error $source
	 * @return string|\WP_Error
	 */
	public static function fix_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		if ( is_wp_error( $source ) || ( $hook_extra['plugin'] ?? '' ) !== plugin_basename( DFMG_FILE ) ) {
			return $source;
		}
		$wanted = trailingslashit( $remote_source ) . dirname( plugin_basename( DFMG_FILE ) ) . '/';
		if ( untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
			return $source;
		}
		if ( $wp_filesystem && $wp_filesystem->move( $source, $wanted, true ) ) {
			return $wanted;
		}
		return new \WP_Error( 'dfmg_update_folder', __( 'The update could not be unpacked into the plugin folder.', 'underworld-empire' ) );
	}

	/**
	 * Private repositories: send the token when downloading the release asset.
	 *
	 * @param array  $args
	 * @param string $url
	 */
	public static function auth_download( $args, $url ): array {
		if ( self::token() && 0 === strpos( (string) $url, 'https://api.github.com/repos/' . self::repo() . '/releases/assets/' ) ) {
			$args['headers']                  = (array) ( $args['headers'] ?? array() );
			$args['headers']['Authorization'] = 'Bearer ' . self::token();
			$args['headers']['Accept']        = 'application/octet-stream';
		}
		return (array) $args;
	}

	public static function flush(): void {
		delete_site_transient( self::CACHE_KEY );
	}

	public static function action_link( array $links ): array {
		if ( current_user_can( 'update_plugins' ) ) {
			$links[] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dfmg_check_updates' ), 'dfmg_check_updates' ) ) . '">' . esc_html__( 'Check for updates', 'underworld-empire' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Clear the caches and ask WordPress to check again right now.
	 */
	public static function force_check(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_check_updates' );
		self::flush();
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}
}
