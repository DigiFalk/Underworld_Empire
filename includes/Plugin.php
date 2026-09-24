<?php
/**
 * Plugin bootstrap.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

use DigiFalk\UnderworldEmpire\Admin\Admin;
use DigiFalk\UnderworldEmpire\Frontend\Game;
use DigiFalk\UnderworldEmpire\Module\Registry;

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
		load_plugin_textdomain( 'underworld-empire', false, dirname( plugin_basename( DFMG_FILE ) ) . '/languages' );

		Installer::maybe_upgrade();

		foreach ( Settings::core_fields() as $key => $field ) {
			Settings::register_default( $key, $field['default'] );
		}

		Updater::init();
		Items::boot();
		$this->modules->boot_enabled();

		Game::init();
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

		// Premium points are bought by the player, keep them for the next round.
		foreach ( DB::results( 'SELECT user_id, SUM(points) AS points FROM {characters} GROUP BY user_id HAVING points > 0' ) as $row ) {
			$carry = (int) get_user_meta( (int) $row['user_id'], 'dfmg_carry_points', true );
			update_user_meta( (int) $row['user_id'], 'dfmg_carry_points', $carry + (int) $row['points'] );
		}

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
