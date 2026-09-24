<?php
/**
 * Game layout: which game elements go where around the game pages.
 * Stored in the dfmg_layout option and edited with drag & drop in Appearance → Customize.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

defined( 'ABSPATH' ) || exit;

final class Layout {

	const OPTION = 'dfmg_layout';

	public static function init(): void {
		add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );
		add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'controls_assets' ) );
	}

	/**
	 * Zones: key => [ label, context ].
	 */
	public static function zones(): array {
		return array(
			'header-left'   => array( __( 'Header left', 'underworld-empire' ), 'bar' ),
			'header-center' => array( __( 'Header center', 'underworld-empire' ), 'bar' ),
			'header-right'  => array( __( 'Header right', 'underworld-empire' ), 'bar' ),
			'sidebar'       => array( __( 'Sidebar', 'underworld-empire' ), 'stack' ),
			'top'           => array( __( 'Above the content', 'underworld-empire' ), 'bar' ),
			'bottom'        => array( __( 'Below the content', 'underworld-empire' ), 'bar' ),
			'footer-left'   => array( __( 'Footer left', 'underworld-empire' ), 'bar' ),
			'footer-center' => array( __( 'Footer center', 'underworld-empire' ), 'bar' ),
			'footer-right'  => array( __( 'Footer right', 'underworld-empire' ), 'bar' ),
		);
	}

	/**
	 * The classic layout: player and stats in the header, menu on the left.
	 */
	public static function defaults(): array {
		return array(
			'header-left'   => array( 'player', 'rank-progress' ),
			'header-center' => array(),
			'header-right'  => array( 'rank', 'cash', 'bank', 'bullets', 'health', 'city', 'points' ),
			'sidebar'       => array( 'menu' ),
			'top'           => array(),
			'bottom'        => array(),
			'footer-left'   => array(),
			'footer-center' => array( 'round', 'logout' ),
			'footer-right'  => array(),
		);
	}

	public static function get(): array {
		$raw = get_option( self::OPTION, '' );
		return '' === $raw || false === $raw ? self::defaults() : self::sanitize_array( is_array( $raw ) ? $raw : json_decode( (string) $raw, true ) );
	}

	/**
	 * Known zones and elements only, every element at most once.
	 */
	private static function sanitize_array( $data ): array {
		if ( ! is_array( $data ) ) {
			return self::defaults();
		}
		$known = Hud::labels();
		$used  = array();
		$out   = array();
		foreach ( array_keys( self::zones() ) as $zone ) {
			$out[ $zone ] = array();
			foreach ( (array) ( $data[ $zone ] ?? array() ) as $key ) {
				$key = sanitize_key( (string) $key );
				if ( isset( $known[ $key ] ) && ! isset( $used[ $key ] ) ) {
					$out[ $zone ][] = $key;
					$used[ $key ]   = true;
				}
			}
		}
		return $out;
	}

	public static function sanitize( $value ): string {
		return (string) wp_json_encode( self::sanitize_array( is_array( $value ) ? $value : json_decode( (string) $value, true ) ) );
	}

	/**
	 * HTML of all elements in a zone ('' when empty).
	 */
	public static function zone( string $zone ): string {
		$layout = self::get();
		$zones  = self::zones();
		if ( empty( $layout[ $zone ] ) || ! isset( $zones[ $zone ] ) ) {
			return '';
		}
		$html = '';
		foreach ( $layout[ $zone ] as $key ) {
			$html .= Hud::render( $key, $zones[ $zone ][1], 'game' );
		}
		return $html;
	}

	/* ------------------------------------------------------------------ */
	/* Customizer                                                           */
	/* ------------------------------------------------------------------ */

	public static function customize_register( \WP_Customize_Manager $wp_customize ): void {
		$wp_customize->add_section(
			'dfmg_game_layout',
			array(
				'title'       => __( 'Game layout', 'underworld-empire' ),
				'description' => __( 'Drag game elements to the header, sidebar, footer or around the content of the game pages. Tip: game elements can also be placed in the theme header and footer, in widget areas, or anywhere with the [ue_hud element="cash"] shortcode.', 'underworld-empire' ),
				'priority'    => 25,
				'capability'  => 'manage_options',
			)
		);
		$wp_customize->add_setting(
			self::OPTION,
			array(
				'type'              => 'option',
				'capability'        => 'manage_options',
				'default'           => (string) wp_json_encode( self::defaults() ),
				'transport'         => 'postMessage',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
		$wp_customize->add_control(
			new LayoutControl(
				$wp_customize,
				self::OPTION,
				array(
					'label'   => __( 'Game layout', 'underworld-empire' ),
					'section' => 'dfmg_game_layout',
				)
			)
		);
		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'dfmg_layout',
				array(
					'selector'            => '.dfmg',
					'settings'            => array( self::OPTION ),
					'container_inclusive' => true,
					'render_callback'     => array( Game::class, 'shortcode' ),
					'fallback_refresh'    => true,
				)
			);
		}
	}

	public static function controls_assets(): void {
		wp_enqueue_style( 'dfmg-customize-layout', DFMG_URL . 'assets/css/customize-layout.css', array(), DFMG_VERSION );
		wp_enqueue_script( 'dfmg-customize-layout', DFMG_URL . 'assets/js/customize-layout.js', array( 'customize-controls', 'jquery-ui-sortable' ), DFMG_VERSION, true );
		$zones = array();
		foreach ( self::zones() as $key => $zone ) {
			$zones[ $key ] = $zone[0];
		}
		wp_localize_script(
			'dfmg-customize-layout',
			'dfmgLayout',
			array(
				'elements' => Hud::labels(),
				'zones'    => $zones,
				'defaults' => self::defaults(),
				'gameUrl'  => Game::page_url(),
				'i18n'     => array(
					'available' => __( 'Available elements – drag them into a zone', 'underworld-empire' ),
					'add'       => __( 'Add element', 'underworld-empire' ),
					'remove'    => __( 'Remove', 'underworld-empire' ),
					'reset'     => __( 'Reset to default', 'underworld-empire' ),
					'header'    => __( 'Header', 'underworld-empire' ),
					'content'   => __( 'Game page', 'underworld-empire' ),
					'footer'    => __( 'Footer', 'underworld-empire' ),
					'page'      => __( 'Page content', 'underworld-empire' ),
					'title'     => __( 'Game layout', 'underworld-empire' ),
				),
			)
		);
	}
}
