<?php
/**
 * Thin database layer on top of $wpdb.
 *
 * SQL passed to the query helpers may reference game tables as {name};
 * these are expanded to the full prefixed table name ({characters} -> wp_dfmg_characters).
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame;

defined( 'ABSPATH' ) || exit;

final class DB {

	/**
	 * @return \wpdb
	 */
	public static function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Full table name for a short game table name.
	 */
	public static function table( string $name ): string {
		return self::wpdb()->prefix . 'dfmg_' . $name;
	}

	/**
	 * Expand {table} tokens and prepare placeholders.
	 */
	public static function sql( string $sql, array $args = array() ): string {
		$sql = preg_replace_callback(
			'/\{([a-z0-9_]+)\}/',
			static function ( $m ) {
				return self::table( $m[1] );
			},
			$sql
		);
		if ( $args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = self::wpdb()->prepare( $sql, $args );
		}
		return $sql;
	}

	public static function row( string $sql, ...$args ): ?array {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = self::wpdb()->get_row( self::sql( $sql, $args ), ARRAY_A );
		return $row ?: null;
	}

	public static function results( string $sql, ...$args ): array {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = self::wpdb()->get_results( self::sql( $sql, $args ), ARRAY_A );
		return $rows ?: array();
	}

	/**
	 * @return string|null
	 */
	public static function value( string $sql, ...$args ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return self::wpdb()->get_var( self::sql( $sql, $args ) );
	}

	public static function column( string $sql, ...$args ): array {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return self::wpdb()->get_col( self::sql( $sql, $args ) );
	}

	/**
	 * Run a write query, returns affected rows (or false).
	 *
	 * @return int|false
	 */
	public static function query( string $sql, ...$args ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return self::wpdb()->query( self::sql( $sql, $args ) );
	}

	public static function insert( string $table, array $data ): int {
		self::wpdb()->insert( self::table( $table ), $data );
		return (int) self::wpdb()->insert_id;
	}

	/**
	 * @return int|false
	 */
	public static function update( string $table, array $data, array $where ) {
		return self::wpdb()->update( self::table( $table ), $data, $where );
	}

	/**
	 * @return int|false
	 */
	public static function delete( string $table, array $where ) {
		return self::wpdb()->delete( self::table( $table ), $where );
	}

	/**
	 * Create or upgrade tables. $schema maps short table name => column/key definition body.
	 */
	public static function create_tables( array $schema ): void {
		if ( ! $schema ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = self::wpdb()->get_charset_collate();
		foreach ( $schema as $name => $body ) {
			dbDelta( 'CREATE TABLE ' . self::table( $name ) . " (\n" . trim( $body ) . "\n) {$charset};" );
		}
	}

	public static function truncate( string $table ): void {
		self::query( 'TRUNCATE TABLE {' . $table . '}' );
	}

	public static function drop( string $table ): void {
		self::query( 'DROP TABLE IF EXISTS {' . $table . '}' );
	}

	public static function table_exists( string $table ): bool {
		$name = self::table( $table );
		return self::wpdb()->get_var( self::wpdb()->prepare( 'SHOW TABLES LIKE %s', $name ) ) === $name;
	}
}
