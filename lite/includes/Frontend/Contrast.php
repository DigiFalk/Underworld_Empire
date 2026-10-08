<?php
/**
 * Readable accent colour for text and icons when the game follows the theme.
 *
 * Block themes often have a very light (or very dark) accent colour, for example the yellow
 * accent of Twenty Twenty-Five. That works for backgrounds and bars, but not for text on the
 * theme's background. When the contrast is below 4.5:1 (WCAG AA), this sets
 * --dfmg-accent-text to the same colour made just dark (or light) enough to be readable.
 * Good accent colours are left alone.
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Frontend;

use DigiFalk\MafiaPBBGEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Contrast {

	const MIN_RATIO = 4.5;

	/**
	 * Inline CSS with --dfmg-accent-text, or '' when the theme's accent is fine (or unknown).
	 */
	public static function css(): string {
		if ( 'dark' === Settings::get( 'appearance', 'theme' ) ) {
			return '';
		}
		// The Mafia PBBG Engine theme (Extended) has its own light and dark colours.
		if ( 'mafia-pbbg-engine-theme' === get_template() ) {
			return '';
		}
		if ( ! function_exists( 'wp_theme_has_theme_json' ) || ! wp_theme_has_theme_json() ) {
			return '';
		}
		$palette = self::palette();
		$accent  = self::hex( $palette['accent'] ?? $palette['primary'] ?? $palette['accent-1'] ?? '', $palette );
		$styles  = (array) wp_get_global_styles( array( 'color' ) );
		$bg      = self::hex( (string) ( $styles['background'] ?? '' ), $palette ) ?: self::hex( $palette['base'] ?? '', $palette );
		$text    = self::hex( (string) ( $styles['text'] ?? '' ), $palette ) ?: self::hex( $palette['contrast'] ?? '', $palette );
		if ( ! $accent || ! $bg || ! $text ) {
			return '';
		}
		$color = self::readable( $accent, $bg, $text );
		if ( $color === $accent ) {
			return '';
		}
		/**
		 * Accent colour for text and icons in the game ('' to keep the theme's accent).
		 *
		 * @param string $color  Hex colour.
		 * @param string $accent The theme's accent.
		 * @param string $bg     The theme's background.
		 */
		$color = (string) apply_filters( 'dfmg_accent_text', $color, $accent, $bg );
		if ( ! preg_match( '/^#[0-9a-f]{6}$/i', $color ) ) {
			return '';
		}
		return '.dfmg, .dfmg-hud, .dfmg-hud-group { --dfmg-accent-text: ' . $color . '; }';
	}

	/**
	 * Palette colours by slug (theme colours override the default ones).
	 */
	private static function palette(): array {
		$out     = array();
		$palette = (array) wp_get_global_settings( array( 'color', 'palette' ) );
		foreach ( array( 'default', 'theme', 'custom' ) as $origin ) {
			foreach ( (array) ( $palette[ $origin ] ?? array() ) as $color ) {
				if ( ! empty( $color['slug'] ) && isset( $color['color'] ) ) {
					$out[ (string) $color['slug'] ] = (string) $color['color'];
				}
			}
		}
		return $out;
	}

	/**
	 * A colour as #rrggbb: hex values and references to palette colours. Anything else
	 * (gradients, color-mix(), named colours) gives ''.
	 */
	private static function hex( string $value, array $palette, int $depth = 0 ): string {
		$value = trim( $value );
		if ( preg_match( '/^var:preset\|color\|([a-z0-9-]+)$/i', $value, $m ) || preg_match( '/^var\(\s*--wp--preset--color--([a-z0-9-]+)\s*\)$/i', $value, $m ) ) {
			return $depth < 3 && isset( $palette[ $m[1] ] ) ? self::hex( $palette[ $m[1] ], $palette, $depth + 1 ) : '';
		}
		if ( preg_match( '/^#([0-9a-f]{3})$/i', $value, $m ) ) {
			$value = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
		}
		return preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? strtolower( $value ) : '';
	}

	/**
	 * The accent with the same hue and saturation, made darker (light background) or lighter
	 * (dark background) step by step until it reads well. Falls back to the text colour.
	 */
	public static function readable( string $accent, string $bg, string $text ): string {
		if ( self::ratio( $accent, $bg ) >= self::MIN_RATIO ) {
			return $accent;
		}
		list( $h, $sat, $l ) = self::hsl( $accent );
		$step                = self::luminance( $bg ) > 0.18 ? -0.01 : 0.01;
		for ( $l += $step; $l >= 0 && $l <= 1; $l += $step ) {
			$color = self::from_hsl( $h, $sat, $l );
			if ( self::ratio( $color, $bg ) >= self::MIN_RATIO ) {
				return $color;
			}
		}
		return $text;
	}

	/**
	 * @return float[] Hue (0-360), saturation and lightness (0-1).
	 */
	private static function hsl( string $hex ): array {
		list( $r, $g, $b ) = array_map(
			static function ( $v ) {
				return $v / 255;
			},
			self::rgb( $hex )
		);
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$l   = ( $max + $min ) / 2;
		$d   = $max - $min;
		if ( 0.0 === (float) $d ) {
			return array( 0.0, 0.0, $l );
		}
		$sat = $d / ( 1 - abs( 2 * $l - 1 ) );
		if ( $max === $r ) {
			$h = 60 * fmod( ( $g - $b ) / $d + 6, 6 );
		} elseif ( $max === $g ) {
			$h = 60 * ( ( $b - $r ) / $d + 2 );
		} else {
			$h = 60 * ( ( $r - $g ) / $d + 4 );
		}
		return array( $h, $sat, $l );
	}

	private static function from_hsl( float $h, float $sat, float $l ): string {
		$c   = ( 1 - abs( 2 * $l - 1 ) ) * $sat;
		$x   = $c * ( 1 - abs( fmod( $h / 60, 2 ) - 1 ) );
		$m   = $l - $c / 2;
		$rgb = array( array( $c, $x, 0 ), array( $x, $c, 0 ), array( 0, $c, $x ), array( 0, $x, $c ), array( $x, 0, $c ), array( $c, 0, $x ) )[ (int) floor( $h / 60 ) % 6 ];
		return vsprintf(
			'#%02x%02x%02x',
			array_map(
				static function ( $v ) use ( $m ) {
					return (int) round( max( 0, min( 1, $v + $m ) ) * 255 );
				},
				$rgb
			)
		);
	}

	private static function rgb( string $hex ): array {
		return array( hexdec( substr( $hex, 1, 2 ) ), hexdec( substr( $hex, 3, 2 ) ), hexdec( substr( $hex, 5, 2 ) ) );
	}

	private static function luminance( string $hex ): float {
		$l = array();
		foreach ( self::rgb( $hex ) as $v ) {
			$v   = $v / 255;
			$l[] = $v <= 0.03928 ? $v / 12.92 : ( ( $v + 0.055 ) / 1.055 ) ** 2.4;
		}
		return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
	}

	public static function ratio( string $a, string $b ): float {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );
		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}
}
