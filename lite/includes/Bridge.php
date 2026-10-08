<?php
/**
 * One-click installation of Mafia PBBG Engine Extended from GitHub.
 *
 * Only in the GitHub edition of this plugin: the wordpress.org edition leaves this file (and
 * Updater.php) out, because plugins from WordPress.org may not install other plugins. That
 * edition links to the download page instead (see Extended).
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	const PLUGIN     = 'mafia-pbbg-engine-extended/mafia-pbbg-engine-extended.php';
	const ASSET_NAME = 'mafia-pbbg-engine-extended.zip';

	public static function init(): void {
		add_action( 'admin_post_dfmg_install_extended', array( __CLASS__, 'handle' ) );
	}

	public static function extended_active(): bool {
		return Extended::active();
	}

	public static function installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN );
	}

	/**
	 * Download link of Extended from the latest GitHub release.
	 */
	private static function package(): string {
		$release = Updater::release();
		$url     = (string) ( $release['assets'][ self::ASSET_NAME ] ?? '' );
		/**
		 * Download url of Mafia PBBG Engine Extended.
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'dfmg_extended_package', $url );
	}

	/**
	 * Install (when needed) and activate Extended.
	 *
	 * @return true|\WP_Error
	 */
	public static function install() {
		if ( self::extended_active() ) {
			return true;
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( ! self::installed() ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			$package = self::package();
			if ( '' === $package ) {
				return new \WP_Error( 'dfmg_extended', __( 'Mafia PBBG Engine Extended could not be found on GitHub. Try again later.', 'mafia-pbbg-engine' ) );
			}
			if ( 'direct' !== get_filesystem_method() || ! WP_Filesystem() ) {
				return new \WP_Error( 'dfmg_extended', __( 'WordPress may not write to the plugins folder on this site. Install Mafia PBBG Engine Extended yourself: Plugins → Add New → Upload Plugin.', 'mafia-pbbg-engine' ) );
			}
			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $package );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result || ! self::installed() ) {
				$errors = $skin->get_errors();
				return $errors->has_errors() ? $errors : new \WP_Error( 'dfmg_extended', __( 'Mafia PBBG Engine Extended could not be installed.', 'mafia-pbbg-engine' ) );
			}
		}
		$activated = activate_plugin( self::PLUGIN );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}
		return true;
	}

	/**
	 * Install button (or a link when the user may not install plugins).
	 */
	public static function button(): string {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return '<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="' . esc_url( 'https://github.com/' . Updater::repo() . '/releases/latest' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Download Extended', 'mafia-pbbg-engine' ) . '</a>';
		}
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="dfmg_install_extended">'
			. wp_nonce_field( 'dfmg_install_extended', '_wpnonce', true, false )
			. '<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--gold">'
			. ( self::installed() ? esc_html__( 'Activate Extended', 'mafia-pbbg-engine' ) : esc_html__( 'Install Extended (free)', 'mafia-pbbg-engine' ) )
			. '</button></form>';
	}

	public static function handle(): void {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
		}
		check_admin_referer( 'dfmg_install_extended' );
		$result = self::install();
		if ( is_wp_error( $result ) ) {
			set_transient( 'dfmg_admin_error_' . get_current_user_id(), $result->get_error_message(), 60 );
		} else {
			set_transient( 'dfmg_admin_success_' . get_current_user_id(), __( 'Mafia PBBG Engine Extended is installed and active.', 'mafia-pbbg-engine' ), 60 );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dfmg-modules' ) );
		exit;
	}
}
