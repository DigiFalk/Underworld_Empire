<?php
/**
 * Mafia PBBG Engine Extended: the free add-on with families, murders, the theme and premium
 * modules. This class only detects it and links to it; the GitHub edition of this plugin can
 * also install it (see Bridge).
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine;

defined( 'ABSPATH' ) || exit;

final class Extended {

	public static function active(): bool {
		return defined( 'DFMG_EXTENDED_VERSION' );
	}

	/**
	 * Download page of Extended on digifalk.com.
	 */
	public static function url(): string {
		/**
		 * Download page of Mafia PBBG Engine Extended.
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'dfmg_extended_url', 'https://digifalk.com/en/product/mafia-pbbg-engine-extended/' );
	}

	/**
	 * Install button (GitHub edition) or a link to the download page.
	 */
	public static function button(): string {
		if ( class_exists( Bridge::class ) ) {
			return Bridge::button();
		}
		return '<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="' . esc_url( self::url() ) . '" target="_blank" rel="noopener">'
			. esc_html__( 'Get Extended (free)', 'mafia-pbbg-engine' ) . ' ' . Icons::svg( 'external', 14 ) . '</a>';
	}
}
