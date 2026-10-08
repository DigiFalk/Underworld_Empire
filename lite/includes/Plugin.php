<?php
/**
 * Plugin bootstrap.
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine;

use DigiFalk\MafiaPBBGEngine\Admin\Admin;
use DigiFalk\MafiaPBBGEngine\Frontend\Game;
use DigiFalk\MafiaPBBGEngine\Frontend\Hud;
use DigiFalk\MafiaPBBGEngine\Frontend\Layout;
use DigiFalk\MafiaPBBGEngine\Module\Registry;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/** @var Registry */
	public $modules;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->boot();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->modules = new Registry();
	}

	private function boot(): void {
		Installer::maybe_upgrade();

		foreach ( Settings::core_fields() as $key => $field ) {
			Settings::register_default( $key, $field['default'] );
		}

		// Only in the GitHub edition; the wordpress.org edition gets its updates from WordPress.org.
		if ( class_exists( Updater::class ) ) {
			Updater::init();
		}
		if ( class_exists( Bridge::class ) ) {
			Bridge::init();
		}
		Avatar::init();
		Items::boot();
		$this->modules->boot_enabled();
		Migrations::run();

		Game::init();
		Hud::init();
		Layout::init();
		if ( is_admin() ) {
			Admin::init();
		}

		add_action( 'dfmg_hourly', array( $this, 'hourly' ) );
		add_filter( 'show_admin_bar', array( $this, 'admin_bar' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite' ), 99 );
	}

	public function hourly(): void {
		// Clean up old notifications and activity to keep tables small.
		DB::query( 'DELETE FROM {notifications} WHERE is_read = 1 AND created_at < %d', time() - 30 * DAY_IN_SECONDS );
		DB::query( 'DELETE FROM {timers} WHERE expires_at > 0 AND expires_at < %d', time() - 7 * DAY_IN_SECONDS );
		do_action( 'dfmg_hourly_tick' );
	}

	/**
	 * @param bool $show
	 */
	public function admin_bar( $show ): bool {
		if ( $show && Settings::get( 'hide_admin_bar', 1 ) && ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		return (bool) $show;
	}

	public function maybe_flush_rewrite(): void {
		if ( get_option( 'dfmg_flush_rewrite' ) ) {
			delete_option( 'dfmg_flush_rewrite' );
			flush_rewrite_rules();
		}
	}

	/**
	 * Whether the current round is open for play.
	 */
	public static function round_open(): bool {
		$now   = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$start = (string) Settings::get( 'round_start', '' );
		$end   = (string) Settings::get( 'round_end', '' );
		if ( $start && strtotime( $start ) > $now ) {
			return false;
		}
		if ( $end && strtotime( $end ) < $now ) {
			return false;
		}
		return (bool) apply_filters( 'dfmg_round_open', true );
	}

	/**
	 * Wipe all player data and start fresh. Game data (crimes, cars, cities...) is kept.
	 */
	public function new_round(): void {
		do_action( 'dfmg_before_new_round' );

		foreach ( Installer::round_tables() as $table ) {
			DB::truncate( $table );
		}
		foreach ( $this->modules->active() as $module ) {
			foreach ( $module->round_tables() as $table ) {
				if ( DB::table_exists( $table ) ) {
					DB::truncate( $table );
				}
			}
			$module->reset_round();
		}
		Character::reset_cache();
		do_action( 'dfmg_new_round' );
	}
}
