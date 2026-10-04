<?php
/**
 * Underworld Empire Extended: detection and installation.
 *
 * Only in the GitHub edition of this plugin: the wordpress.org edition leaves this file (and
 * Updater.php) out, because plugins from WordPress.org may not install other plugins.
 *
 * Since 1.12.0 families, murders, the theme, premium licenses and more live in the separate
 * plugin Underworld Empire Extended. Sites that used those modules before get Extended
 * installed and activated automatically after updating, so the game keeps working. Other
 * sites can install it with one click on the Modules screen.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

use DigiFalk\UnderworldEmpire\Module\Registry;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	const PLUGIN        = 'underworld-empire-extended/underworld-empire-extended.php';
	const ASSET_NAME    = 'underworld-empire-extended.zip';
	const OPTION_CHECK  = 'dfmg_bridge_checked';
	const OPTION_AUTO   = 'dfmg_bridge_auto';
	const OPTION_ERROR  = 'dfmg_bridge_error';
	const HOOK          = 'dfmg_install_extended';

	/** Modules that moved from this plugin to Extended in 1.12.0. */
	const MOVED = array( 'families', 'murder', 'detectives', 'bounties', 'bullet-factory', 'black-market', 'blackjack', 'police-chase', 'properties', 'forum' );

	public static function init(): void {
		add_action( self::HOOK, array( __CLASS__, 'cron' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin' ) );
		add_action( 'admin_post_dfmg_install_extended', array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		// The Underworld Empire theme moved to Extended: until Extended is active, don't let
		// WordPress switch an active Underworld Empire theme back to a default theme.
		if ( ! self::extended_active() && 'underworld-empire-theme' === get_option( 'stylesheet' ) ) {
			add_filter( 'validate_current_theme', '__return_false' );
		}
		self::detect();
	}

	public static function extended_active(): bool {
		return Extended::active();
	}

	public static function installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN );
	}

	/**
	 * Once: does this site miss modules it used before (they moved to Extended)? Then install
	 * Extended automatically.
	 */
	private static function detect(): void {
		if ( self::extended_active() || get_option( self::OPTION_CHECK ) ) {
			return;
		}
		update_option( self::OPTION_CHECK, 1 );
		$installed = array_keys( (array) get_option( Registry::OPTION_INSTALLED, array() ) );
		if ( array_intersect( self::MOVED, $installed ) ) {
			update_option( self::OPTION_AUTO, 1 );
			wp_schedule_single_event( time(), self::HOOK );
		}
	}

	/**
	 * Download link of Extended from the latest GitHub release.
	 */
	private static function package(): string {
		$release = Updater::release();
		$url     = (string) ( $release['assets'][ self::ASSET_NAME ] ?? '' );
		/**
		 * Download url of Underworld Empire Extended.
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
				return new \WP_Error( 'dfmg_extended', __( 'Underworld Empire Extended could not be found on GitHub. Try again later.', 'underworld-empire' ) );
			}
			if ( 'direct' !== get_filesystem_method() || ! WP_Filesystem() ) {
				return new \WP_Error( 'dfmg_extended', __( 'WordPress may not write to the plugins folder on this site. Install Underworld Empire Extended yourself: Plugins → Add New → Upload Plugin.', 'underworld-empire' ) );
			}
			$skin     = new \WP_Ajax_Upgrader_Skin();
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $package );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result || ! self::installed() ) {
				$errors = $skin->get_errors();
				return $errors->has_errors() ? $errors : new \WP_Error( 'dfmg_extended', __( 'Underworld Empire Extended could not be installed.', 'underworld-empire' ) );
			}
		}
		$activated = activate_plugin( self::PLUGIN );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}
		delete_option( self::OPTION_ERROR );
		return true;
	}

	private static function auto(): void {
		delete_option( self::OPTION_AUTO );
		$result = self::install();
		if ( is_wp_error( $result ) ) {
			update_option( self::OPTION_ERROR, $result->get_error_message() );
		}
	}

	public static function cron(): void {
		if ( get_option( self::OPTION_AUTO ) && ! self::extended_active() ) {
			self::auto();
		}
	}

	public static function admin(): void {
		if ( get_option( self::OPTION_AUTO ) && ! self::extended_active() && current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) && ! wp_doing_ajax() ) {
			self::auto();
		}
	}

	/**
	 * Install button (or a link when the user may not install plugins).
	 */
	public static function button(): string {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return '<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="' . esc_url( 'https://github.com/' . Updater::repo() . '/releases/latest' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Download Extended', 'underworld-empire' ) . '</a>';
		}
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. '<input type="hidden" name="action" value="dfmg_install_extended">'
			. wp_nonce_field( 'dfmg_install_extended', '_wpnonce', true, false )
			. '<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--gold">'
			. ( self::installed() ? esc_html__( 'Activate Extended', 'underworld-empire' ) : esc_html__( 'Install Extended (free)', 'underworld-empire' ) )
			. '</button></form>';
	}

	public static function handle(): void {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_install_extended' );
		$result = self::install();
		if ( is_wp_error( $result ) ) {
			update_option( self::OPTION_ERROR, $result->get_error_message() );
			set_transient( 'dfmg_admin_error_' . get_current_user_id(), $result->get_error_message(), 60 );
		} else {
			set_transient( 'dfmg_admin_success_' . get_current_user_id(), __( 'Underworld Empire Extended is installed and active.', 'underworld-empire' ), 60 );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=dfmg-modules' ) );
		exit;
	}

	/**
	 * Warning on every admin screen when the automatic installation failed: the game is
	 * missing modules it used before.
	 */
	public static function notice(): void {
		$error = get_option( self::OPTION_ERROR );
		if ( ! $error || self::extended_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Underworld Empire: families, murders, the theme and premium modules now live in the free plugin Underworld Empire Extended. It could not be installed automatically.', 'underworld-empire' ) . '</strong> '
			. esc_html( (string) $error ) . ' '
			. esc_html__( 'Your game data is kept; it comes back as soon as Extended is active.', 'underworld-empire' ) . '</p>'
			. '<p>' . self::button() . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
