<?php
/**
 * "Underworld Empire by DigiFalk" at the bottom of every game page.
 *
 * The free plugin always shows it. The premium module White Label (ue-white-label) removes
 * it, or replaces it with the site's own text, while its license is active.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

defined( 'ABSPATH' ) || exit;

final class Credit {

	const WHITE_LABEL = 'ue-white-label';
	const URL         = 'https://digifalk.com/';

	/**
	 * Is the licensed White Label module running on this site?
	 */
	public static function white_label(): bool {
		$modules = dfmg()->modules;
		return null !== $modules->get( self::WHITE_LABEL ) && $modules->runnable( self::WHITE_LABEL );
	}

	public static function html(): string {
		if ( self::white_label() ) {
			/**
			 * Footer text of a white-labelled game ('' for none). Only used while the White Label
			 * module is licensed and active.
			 *
			 * @param string $html
			 */
			$html = (string) apply_filters( 'dfmg_white_label_credit', '' );
			return '' === $html ? '' : '<div class="dfmg-footer__credit">' . wp_kses_post( $html ) . '</div>';
		}
		return '<div class="dfmg-footer__credit">' . sprintf(
			/* translators: 1: game name, 2: link to DigiFalk */
			esc_html__( '%1$s by %2$s', 'underworld-empire' ),
			'Underworld Empire',
			'<a href="' . esc_url( self::URL ) . '" target="_blank" rel="noopener">DigiFalk</a>'
		) . '</div>';
	}
}
