<?php
/**
 * One-off data migrations between plugin versions. Each migration runs once and is remembered
 * in the dfmg_migrations option:
 *
 *     if ( empty( $done['my_change'] ) ) {
 *         self::my_change();
 *         $done['my_change'] = time();
 *         update_option( self::OPTION, $done );
 *     }
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine;

defined( 'ABSPATH' ) || exit;

final class Migrations {

	const OPTION = 'dfmg_migrations';

	public static function run(): void {
		// No migrations yet.
	}
}
