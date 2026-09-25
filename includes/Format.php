<?php
/**
 * Formatting helpers.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Format {

	/**
	 * @param int|float $amount
	 */
	public static function money( $amount ): string {
		$symbol = (string) Settings::get( 'currency_symbol', '$' );
		$amount = (float) $amount;
		$sign   = $amount < 0 ? '-' : '';
		return $sign . $symbol . number_format_i18n( abs( $amount ) );
	}

	/**
	 * @param int|float $n
	 */
	public static function number( $n ): string {
		return number_format_i18n( (float) $n );
	}


	/**
	 * Human readable duration: "1h 04m 09s".
	 */
	public static function duration( int $seconds ): string {
		$seconds = max( 0, $seconds );
		$d       = intdiv( $seconds, 86400 );
		$h       = intdiv( $seconds % 86400, 3600 );
		$m       = intdiv( $seconds % 3600, 60 );
		$s       = $seconds % 60;
		$parts   = array();
		if ( $d ) {
			$parts[] = $d . 'd';
		}
		if ( $h || $d ) {
			$parts[] = $h . 'h';
		}
		if ( $m || $h || $d ) {
			$parts[] = sprintf( '%02dm', $m );
		}
		$parts[] = sprintf( '%02ds', $s );
		return implode( ' ', $parts );
	}

	/**
	 * Markup for a live countdown until $expires (unix time).
	 */
	public static function countdown( int $expires, string $done_text = '' ): string {
		$left = $expires - time();
		if ( $left <= 0 ) {
			return '<span class="dfmg-ready">' . esc_html( $done_text ?: __( 'Ready', 'underworld-empire' ) ) . '</span>';
		}
		return sprintf(
			'<span class="dfmg-countdown" data-expires="%1$d" data-done="%2$s">%3$s</span>',
			$expires,
			esc_attr( $done_text ?: __( 'Ready', 'underworld-empire' ) ),
			esc_html( self::duration( $left ) )
		);
	}

	public static function date( int $timestamp ): string {
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function ago( int $timestamp ): string {
		/* translators: %s: human time difference */
		return sprintf( __( '%s ago', 'underworld-empire' ), human_time_diff( $timestamp, time() ) );
	}

	/**
	 * Parse user input like "1.000", "$ 2,500" or "1000" into a positive integer.
	 *
	 * @param mixed $value
	 */
	public static function parse_amount( $value ): int {
		$value = preg_replace( '/[^0-9]/', '', (string) $value );
		if ( '' === $value ) {
			return 0;
		}
		// Clamp to a sane maximum to avoid integer overflow.
		return (int) min( (float) $value, 9.0E15 );
	}

	/**
	 * Safe user generated text: limited HTML, line breaks.
	 */
	public static function user_text( string $text ): string {
		$allowed = array(
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
			'u'      => array(),
			'a'      => array(
				'href' => array(),
			),
			'br'     => array(),
			'p'      => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
			'blockquote' => array(),
		);
		return wpautop( wp_kses( $text, $allowed ) );
	}
}
