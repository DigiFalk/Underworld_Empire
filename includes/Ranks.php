<?php
/**
 * Rank (experience) and wealth titles.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Ranks {

	/** @var array|null */
	private static $ranks = null;

	/** @var array|null */
	private static $wealth = null;

	public static function flush(): void {
		self::$ranks  = null;
		self::$wealth = null;
	}

	/**
	 * All ranks ordered from low to high.
	 */
	public static function all(): array {
		if ( null === self::$ranks ) {
			self::$ranks = DB::results( 'SELECT * FROM {ranks} ORDER BY exp_required ASC, id ASC' );
		}
		return self::$ranks;
	}

	public static function first(): array {
		$all = self::all();
		return $all ? $all[0] : array(
			'id'            => 0,
			'name'          => __( 'Unknown', 'underworld-empire' ),
			'exp_required'  => 0,
			'max_players'   => 0,
			'cash_reward'   => 0,
			'bullet_reward' => 0,
			'max_health'    => 1000,
		);
	}

	public static function get( int $id ): array {
		foreach ( self::all() as $rank ) {
			if ( (int) $rank['id'] === $id ) {
				return $rank;
			}
		}
		return self::first();
	}

	/**
	 * 1-based position of a rank in the ladder.
	 */
	public static function level( int $id ): int {
		foreach ( self::all() as $i => $rank ) {
			if ( (int) $rank['id'] === $id ) {
				return $i + 1;
			}
		}
		return 1;
	}

	public static function next( int $id ): ?array {
		$found = false;
		foreach ( self::all() as $rank ) {
			if ( $found ) {
				return $rank;
			}
			if ( (int) $rank['id'] === $id ) {
				$found = true;
			}
		}
		return null;
	}

	public static function options(): array {
		$out = array();
		foreach ( self::all() as $rank ) {
			$out[ $rank['id'] ] = $rank['name'];
		}
		return $out;
	}

	/**
	 * Wealth title for a total amount of money.
	 */
	public static function wealth_title( int $total ): string {
		if ( null === self::$wealth ) {
			self::$wealth = DB::results( 'SELECT * FROM {money_ranks} ORDER BY min_money ASC' );
		}
		$title = '';
		foreach ( self::$wealth as $row ) {
			if ( $total >= (int) $row['min_money'] ) {
				$title = $row['name'];
			}
		}
		return $title;
	}
}
