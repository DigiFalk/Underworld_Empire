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
				'label'       => __( 'Appearance', 'mafia-pbbg-engine' ),
				'type'        => 'select',
				'default'     => 'theme',
				'options'     => array(
					'theme' => __( 'Follow the colours and font of the WordPress theme', 'mafia-pbbg-engine' ),
					'dark'  => __( 'Built-in dark look', 'mafia-pbbg-engine' ),
				),
				'description' => __( 'Tip: Mafia PBBG Engine Extended comes with the matching "Mafia PBBG Engine" theme (Appearance → Themes). With "Follow the theme" the game also switches along with the light/dark mode of that theme. This is the starting look: players can switch between light and dark with the light/dark switch (a game element, see Appearance → Customize → Game layout).', 'mafia-pbbg-engine' ),
			),
			'show_credit'        => array(
				'label'       => __( 'Show "Mafia PBBG Engine by DigiFalk" at the bottom of game pages', 'mafia-pbbg-engine' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'description' => __( 'A small line with a link to the maker of the game. Thank you for your support! Always shown while Mafia PBBG Engine Extended is active.', 'mafia-pbbg-engine' ),
			),
			'currency_symbol'    => array(
				'label'   => __( 'Currency symbol', 'mafia-pbbg-engine' ),
				'type'    => 'text',
				'default' => '$',
			),
			'start_money'        => array(
				'label'   => __( 'Starting money for new characters', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 250,
			),
			'start_bullets'      => array(
				'label'   => __( 'Starting bullets for new characters', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 100,
			),
			'round_name'         => array(
				'label'   => __( 'Current round name', 'mafia-pbbg-engine' ),
				'type'    => 'text',
				'default' => __( 'Round 1', 'mafia-pbbg-engine' ),
			),
			'round_start'        => array(
				'label'       => __( 'Round start', 'mafia-pbbg-engine' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leave empty = open immediately.', 'mafia-pbbg-engine' ),
			),
			'round_end'          => array(
				'label'       => __( 'Round end', 'mafia-pbbg-engine' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leave empty = no end date.', 'mafia-pbbg-engine' ),
			),
			'online_minutes'     => array(
				'label'   => __( 'Minutes a player counts as online', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 15,
			),
			'hide_admin_bar'     => array(
				'label'   => __( 'Hide the WordPress admin bar for players', 'mafia-pbbg-engine' ),
				'type'    => 'checkbox',
				'default' => 1,
			),
			'delete_on_uninstall' => array(
				'label'       => __( 'Delete all game data when the plugin is deleted', 'mafia-pbbg-engine' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'description' => __( 'Warning: all tables and settings will be erased.', 'mafia-pbbg-engine' ),
			),
		);
	}
}
