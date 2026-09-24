<?php
/**
 * One-time messages shown after a redirect.
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame;

defined( 'ABSPATH' ) || exit;

final class Flash {

	/** @var array Messages added during this request. */
	private static $queue = array();

	public static function add( string $type, string $message ): void {
		self::$queue[] = array(
			'type'    => $type,
			'message' => $message,
		);
	}

	public static function success( string $message ): void {
		self::add( 'success', $message );
	}

	public static function error( string $message ): void {
		self::add( 'error', $message );
	}

	public static function info( string $message ): void {
		self::add( 'info', $message );
	}

	public static function has_errors(): bool {
		foreach ( self::$queue as $m ) {
			if ( 'error' === $m['type'] ) {
				return true;
			}
		}
		return false;
	}

	private static function key(): string {
		return 'dfmg_flash_' . get_current_user_id();
	}

	/**
	 * Store queued messages so they survive the redirect.
	 */
	public static function persist(): void {
		if ( ! self::$queue || ! is_user_logged_in() ) {
			return;
		}
		$existing = get_transient( self::key() );
		$existing = is_array( $existing ) ? $existing : array();
		set_transient( self::key(), array_merge( $existing, self::$queue ), 5 * MINUTE_IN_SECONDS );
		self::$queue = array();
	}

	/**
	 * Get and clear all messages.
	 */
	public static function pull(): array {
		$messages = self::$queue;
		if ( is_user_logged_in() ) {
			$stored = get_transient( self::key() );
			if ( is_array( $stored ) ) {
				$messages = array_merge( $stored, $messages );
				delete_transient( self::key() );
			}
		}
		self::$queue = array();
		return $messages;
	}
}
