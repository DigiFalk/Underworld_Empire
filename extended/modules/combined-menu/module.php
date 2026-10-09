<?php
/**
 * Module Name: Combined menu
 * Description: With the Mafia PBBG Engine theme: the mobile menu (hamburger) of the site also holds the game menu, under a separator line. The game then has no menu button of its own on phones. Without that theme nothing changes.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Modules;

use DigiFalk\MafiaPBBGEngine\Character;
use DigiFalk\MafiaPBBGEngine\Frontend\Hud;
use DigiFalk\MafiaPBBGEngine\Module\Module;

defined( 'ABSPATH' ) || exit;

final class CombinedMenu extends Module {

	const THEME = 'mafia-pbbg-engine-theme';

	public function title(): string {
		return __( 'Combined menu', 'mafia-pbbg-engine' );
	}

	public function boot(): void {
		add_filter( 'mpet_header_popup', array( $this, 'popup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 20 );
	}

	/**
	 * Only with the Mafia PBBG Engine theme (also as parent theme) and for players with a character.
	 */
	private static function character(): ?Character {
		if ( self::THEME !== get_template() ) {
			return null;
		}
		$c = dfmg_character();
		return $c instanceof Character ? $c : null;
	}

	/**
	 * Adds the game menu to the mobile menu panel of the theme, under a separator line.
	 *
	 * @param string $popup
	 */
	public function popup( $popup ): string {
		$popup = (string) $popup;
		$c     = self::character();
		$menu  = $c ? Hud::render_menu( $c, 'stack' ) : '';
		if ( '' === $menu ) {
			return $popup;
		}
		return $popup
			. ( '' !== $popup ? '<hr class="mpe-combined-menu__line">' : '' )
			. '<div class="mpet-popup__item mpet-popup__item--game-menu"><div class="dfmg-hud dfmg-hud--menu mpe-combined-menu">' . $menu . '</div></div>';
	}

	/**
	 * Width below which the game hides its menu button: where the game shows it (720px) and the
	 * theme shows its mobile menu.
	 */
	private static function breakpoint(): int {
		$theme = function_exists( 'mpet_opt' ) ? absint( mpet_opt( 'mobile_breakpoint' ) ) : 720;
		return min( 720, $theme ? $theme : 720 );
	}

	public function assets(): void {
		if ( ! self::character() ) {
			return;
		}
		// The game menu is styled by the game stylesheet, also on the other pages of the site.
		wp_enqueue_style( 'dfmg-game' );
		wp_add_inline_style(
			'dfmg-game',
			'.mpe-combined-menu__line{height:0;margin:16px 0;border:0;border-top:1px solid var(--mpet-border,currentColor);opacity:1}'
			. '.mpe-combined-menu{padding:0}'
			. '.mpe-combined-menu .dfmg-nav__group h4{margin:12px 4px 4px}'
			. '.mpe-combined-menu .dfmg-nav ul{margin:0;padding:0;list-style:none}'
			. '.mpe-combined-menu .dfmg-nav li a{display:flex;align-items:center;gap:10px;padding:10px 4px;min-height:40px;color:var(--mpet-text,inherit)}'
			// The site menu now holds the game menu, so the game's own menu button goes on phones.
			. '@media (max-width:' . self::breakpoint() . 'px){.dfmg .dfmg-menu-toggle,.dfmg .dfmg-header--toggle-only{display:none!important}}'
		);
	}
}

return new CombinedMenu();
