<?php
/**
 * Game settings, stored in a single option.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'dfmg_settings';

	/** @var array|null */
	private static $values = null;

	/** @var array key => default */
	private static $defaults = array();

	public static function register_default( string $key, $value ): void {
		self::$defaults[ $key ] = $value;
	}

	public static function all(): array {
		if ( null === self::$values ) {
			$stored       = get_option( self::OPTION, array() );
			self::$values = is_array( $stored ) ? $stored : array();
		}
		return self::$values;
	}

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$values = self::all();
		if ( array_key_exists( $key, $values ) && '' !== $values[ $key ] ) {
			return $values[ $key ];
		}
		if ( null !== $default ) {
			return $default;
		}
		return self::$defaults[ $key ] ?? null;
	}

	public static function int( string $key, int $default = 0 ): int {
		return (int) self::get( $key, $default );
	}

	/**
	 * @param mixed $value
	 */
	public static function set( string $key, $value ): void {
		$values         = self::all();
		$values[ $key ] = $value;
		self::save( $values );
	}

	public static function save( array $values ): void {
		self::$values = $values;
		update_option( self::OPTION, $values );
	}

	/**
	 * Core settings fields (shown on the settings page).
	 */
	public static function core_fields(): array {
		return array(
			'appearance'         => array(
				'label'       => __( 'Appearance', 'underworld-empire' ),
				'type'        => 'select',
				'default'     => 'theme',
				'options'     => array(
					'theme' => __( 'Follow the colours and font of the WordPress theme', 'underworld-empire' ),
					'dark'  => __( 'Built-in dark look', 'underworld-empire' ),
				),
				'description' => __( 'Tip: activate the bundled "Underworld Empire" theme under Appearance → Themes for a matching website.', 'underworld-empire' ),
			),
			'currency_symbol'    => array(
				'label'   => __( 'Currency symbol', 'underworld-empire' ),
				'type'    => 'text',
				'default' => '$',
			),
			'points_name'        => array(
				'label'   => __( 'Name of premium points', 'underworld-empire' ),
				'type'    => 'text',
				'default' => __( 'Points', 'underworld-empire' ),
			),
			'start_money'        => array(
				'label'   => __( 'Starting money for new characters', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 250,
			),
			'start_bullets'      => array(
				'label'   => __( 'Starting bullets for new characters', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 100,
			),
			'round_name'         => array(
				'label'   => __( 'Current round name', 'underworld-empire' ),
				'type'    => 'text',
				'default' => __( 'Round 1', 'underworld-empire' ),
			),
			'round_start'        => array(
				'label'       => __( 'Round start', 'underworld-empire' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leave empty = open immediately.', 'underworld-empire' ),
			),
			'round_end'          => array(
				'label'       => __( 'Round end', 'underworld-empire' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leave empty = no end date.', 'underworld-empire' ),
			),
			'online_minutes'     => array(
				'label'   => __( 'Minutes a player counts as online', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 15,
			),
			'hide_admin_bar'     => array(
				'label'   => __( 'Hide the WordPress admin bar for players', 'underworld-empire' ),
				'type'    => 'checkbox',
				'default' => 1,
			),
			'delete_on_uninstall' => array(
				'label'       => __( 'Delete all game data when the plugin is deleted', 'underworld-empire' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'description' => __( 'Warning: all tables and settings will be erased.', 'underworld-empire' ),
			),
		);
	}
}
