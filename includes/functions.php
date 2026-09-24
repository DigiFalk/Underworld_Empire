<?php
/**
 * Public helper functions, handy for themes and custom modules.
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Plugin;
use DigiFalk\MaffiaGame\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin instance.
 */
function dfmg() {
	return Plugin::instance();
}

/**
 * Character of the logged in user (or null).
 *
 * @return Character|null
 */
function dfmg_character() {
	return Character::current();
}

/**
 * Read a game setting.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function dfmg_setting( $key, $default = null ) {
	return Settings::get( $key, $default );
}

/**
 * Format an amount of in-game money.
 *
 * @param int|float $amount Amount.
 * @return string
 */
function dfmg_money( $amount ) {
	return Format::money( $amount );
}

/**
 * URL to a page inside the game.
 *
 * @param string $route Module id.
 * @param array  $args  Extra query args.
 * @return string
 */
function dfmg_url( $route = '', $args = array() ) {
	return \DigiFalk\MaffiaGame\Frontend\Game::url( $route, $args );
}
