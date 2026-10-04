<?php
/**
 * Cities in the game world.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Locations {

	/** @var array|null */
	private static $all = null;

	public static function flush(): void {
		self::$all = null;
	}

	public static function all(): array {
		if ( null === self::$all ) {
			self::$all = DB::results( 'SELECT * FROM {locations} ORDER BY id ASC' );
		}
		return self::$all;
	}

	public static function get( int $id ): ?array {
		foreach ( self::all() as $row ) {
			if ( (int) $row['id'] === $id ) {
				return $row;
			}
		}
		return null;
	}

	public static function name( int $id ): string {
		$row = self::get( $id );
		return $row ? $row['name'] : __( 'Unknown', 'underworld-empire' );
	}

	public static function first_id(): int {
		$all = self::all();
		return $all ? (int) $all[0]['id'] : 0;
	}

	public static function options(): array {
		$out = array();
		foreach ( self::all() as $row ) {
			$out[ $row['id'] ] = $row['name'];
		}
		return $out;
	}
}
