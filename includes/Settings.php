<?php
/**
 * Game settings, stored in a single option.
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame;

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
			'currency_symbol'    => array(
				'label'   => __( 'Valutateken', 'wp-maffia-game' ),
				'type'    => 'text',
				'default' => '€',
			),
			'points_name'        => array(
				'label'   => __( 'Naam van premium punten', 'wp-maffia-game' ),
				'type'    => 'text',
				'default' => __( 'Punten', 'wp-maffia-game' ),
			),
			'start_money'        => array(
				'label'   => __( 'Startgeld nieuw personage', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 250,
			),
			'start_bullets'      => array(
				'label'   => __( 'Startkogels nieuw personage', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 100,
			),
			'round_name'         => array(
				'label'   => __( 'Naam huidige ronde', 'wp-maffia-game' ),
				'type'    => 'text',
				'default' => __( 'Ronde 1', 'wp-maffia-game' ),
			),
			'round_start'        => array(
				'label'       => __( 'Start ronde', 'wp-maffia-game' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leeg laten = direct open.', 'wp-maffia-game' ),
			),
			'round_end'          => array(
				'label'       => __( 'Einde ronde', 'wp-maffia-game' ),
				'type'        => 'datetime',
				'default'     => '',
				'description' => __( 'Leeg laten = geen einddatum.', 'wp-maffia-game' ),
			),
			'online_minutes'     => array(
				'label'   => __( 'Minuten dat een speler als online telt', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 15,
			),
			'hide_admin_bar'     => array(
				'label'   => __( 'Verberg de WordPress admin-balk voor spelers', 'wp-maffia-game' ),
				'type'    => 'checkbox',
				'default' => 1,
			),
			'delete_on_uninstall' => array(
				'label'       => __( 'Verwijder alle speldata bij verwijderen plugin', 'wp-maffia-game' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'description' => __( 'Let op: alle tabellen en instellingen worden gewist.', 'wp-maffia-game' ),
			),
		);
	}
}
