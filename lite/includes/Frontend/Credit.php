<?php
/**
 * "Mafia PBBG Engine by DigiFalk" at the bottom of every game page.
 *
 * Off by default: the site owner chooses to show it (Settings → Show "Mafia PBBG Engine by
 * DigiFalk"). Mafia PBBG Engine Extended always shows it through the dfmg_show_credit filter;
 * the premium module White Label (ue-white-label) removes it, or replaces it with the site's
 * own text, while its license is active.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

use DigiFalk\UnderworldEmpire\Settings;

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
		/**
		 * Show "Mafia PBBG Engine by DigiFalk" at the bottom of game pages.
		 *
		 * @param bool $show The "show_credit" setting (off by default).
		 */
		if ( ! apply_filters( 'dfmg_show_credit', (bool) Settings::get( 'show_credit', 0 ) ) ) {
			return '';
		}
		return '<div class="dfmg-footer__credit">' . sprintf(
			/* translators: 1: game name, 2: link to DigiFalk */
			esc_html__( '%1$s by %2$s', 'mafia-pbbg-engine' ),
			'Mafia PBBG Engine',
			'<a href="' . esc_url( self::URL ) . '" target="_blank" rel="noopener">DigiFalk</a>'
		) . '</div>';
	}
}
