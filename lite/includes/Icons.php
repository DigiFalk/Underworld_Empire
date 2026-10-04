<?php
/**
 * Line icons for the game and the admin screens (24×24, drawn with currentColor).
 *
 * Every game page gets an icon by its route. Custom modules can add their own:
 *   add_filter( 'dfmg_icons', fn( $icons ) => $icons + [ 'my-module' => '<path d="…"/>' ] );
 * A value is a path "d" attribute, or raw SVG markup when it starts with "<".
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Icons {

	/** @var array|null */
	private static $icons = null;

	public static function all(): array {
		if ( null !== self::$icons ) {
			return self::$icons;
		}
		$icons = array(
			// Pages.
			'overview'       => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
			'crimes'         => 'M13 2 4 14h7l-1 8 9-12h-7z',
			'car-theft'      => '<path d="M3 13l2-5.5A2 2 0 0 1 6.9 6h10.2a2 2 0 0 1 1.9 1.5L21 13v4h-2.5M5.5 17H3v-4h18M9.5 17h5"/><circle cx="7.5" cy="17" r="2"/><circle cx="16.5" cy="17" r="2"/>',
			'garage'         => 'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z',
			'jail'           => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8.5 3v18M12 3v18M15.5 3v18"/>',
			'hospital'       => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/>',
			'travel'         => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>',
			'bank'           => 'M3 10h18M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18M12 3l9 5H3z',
			'bullet-factory' => 'M3 21V10l6 3v-3l6 3V6l6-3v18zM7 17h2M12 17h2',
			'police-chase'   => 'M7 18v-6a5 5 0 0 1 10 0v6M5 21h14v-3H5zM12 3v2M4.2 6.2l1.4 1.4M19.8 6.2l-1.4 1.4',
			'detectives'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
			'murder'         => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2"/><path d="M12 2v5M12 17v5M2 12h5M17 12h5"/>',
			'bounties'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/>',
			'blackjack'      => '<rect x="3" y="6" width="11" height="15" rx="2"/><path d="M9 3h9a2 2 0 0 1 2 2v12M8.5 10.5c-1.5 1.2-2.5 2-2.5 3a1.3 1.3 0 0 0 2.5.6 1.3 1.3 0 0 0 2.5-.6c0-1-1-1.8-2.5-3zM8.5 14v2.5"/>',
			'black-market'   => 'M5 8h14l-1 13H6zM9 8V6a3 3 0 0 1 6 0v2',
			'inventory'      => 'M3 7.5 12 3l9 4.5v9L12 21l-9-4.5zM3 7.5l9 4.5 9-4.5M12 12v9',
			'membership'     => 'M3 8l4.5 4L12 5l4.5 7L21 8l-2 11H5z',
			'families'       => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
			'properties'     => 'M4 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17M15 9h4a1 1 0 0 1 1 1v11M2 21h20M8 7h3M8 11h3M8 15h3',
			'messages'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
			'notifications'  => 'M6 16v-5a6 6 0 0 1 12 0v5l2 2H4zM10 20.5a2 2 0 0 0 4 0',
			'profile'        => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'players'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2.5"/><path d="M5.5 17a3.5 3.5 0 0 1 7 0M15 10h3M15 14h3"/>',
			'leaderboards'   => 'M7 4h10v5a5 5 0 0 1-10 0zM7 6H4a3 3 0 0 0 3 4M17 6h3a3 3 0 0 1-3 4M12 14v4M8 21h8M10 18h4',
			'statistics'     => 'M5 20v-9M12 20V5M19 20v-6M3 20h18',
			'news'           => 'M4 5h13v14a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2zM17 9h3v10a2 2 0 0 1-2 2M8 9h5M8 13h5M8 17h3',
			'forum'          => 'M4 5h16v11H9l-5 4zM8 9h8M8 12h5',
			// Stats and interface.
			'cash'           => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
			'bullets'        => 'M8 21V9a2 2 0 0 1 .6-1.4L12 4l3.4 3.6A2 2 0 0 1 16 9v12zM8 17h8',
			'health'         => 'M12 20s-7.5-4.6-9-9.3A4.8 4.8 0 0 1 12 7a4.8 4.8 0 0 1 9 3.7C19.5 15.4 12 20 12 20z',
			'rank'           => 'm12 3 2.7 5.6 6.2.9-4.5 4.3 1.1 6.1L12 17l-5.5 2.9 1.1-6.1L3.1 9.5l6.2-.9z',
			'city'           => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
			'points'         => 'M6 3h12l4 6-10 12L2 9zM2 9h20M12 21 9 9l3-6 3 6z',
			'wealth'         => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
			'power'          => 'M14.5 17.5 3 6V3h3l11.5 11.5M13 19l6-6M16 16l4 4M19 21l2-2',
			'timer'          => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2.5M10 2h4"/>',
			'calendar'       => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'round'          => 'M5 21V4M5 4h11l-2 4 2 4H5',
			'logout'         => 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3',
			'play'           => 'M7 4v16l13-8z',
			'check'          => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
			'alert'          => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.01"/>',
			'info'           => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.01"/>',
			'wait'           => 'M6 3h12M6 21h12M7 3v3a5 5 0 0 0 10 0V3M7 21v-3a5 5 0 0 1 10 0v3',
			'shield'         => 'M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z',
			'module'         => 'M9 3h6v3a1.5 1.5 0 0 0 3 0V3h3v6h-3a1.5 1.5 0 0 0 0 3h3v9h-6v-3a1.5 1.5 0 0 0-3 0v3H3v-6h3a1.5 1.5 0 0 0 0-3H3V3z',
			'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
			'data'           => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
			'external'       => 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
			'layout'         => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 9v12"/>',
			'palette'        => 'M12 3a9 9 0 1 0 0 18c1 0 1.5-.8 1.5-1.6 0-.5-.2-.8-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.7-1.5 1.6-1.5H16a5 5 0 0 0 5-5c0-4.1-4-7.5-9-7.5zM7.5 11.5h.01M10 7.5h.01M15 7.5h.01',
			'book'           => 'M4 5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2zM4 21a2 2 0 0 1 2-2h14v2',
			'activity'       => 'M3 12h4l3-8 4 16 3-8h4',
			'dot'            => '<circle cx="12" cy="12" r="3" fill="currentColor"/>',
		);
		self::$icons = (array) apply_filters( 'dfmg_icons', $icons );
		return self::$icons;
	}

	/**
	 * Inline SVG for an icon ('dot' when unknown).
	 */
	public static function svg( string $name, int $size = 18, string $class = '' ): string {
		$icons = self::all();
		$body  = $icons[ $name ] ?? $icons['dot'];
		if ( '<' !== substr( $body, 0, 1 ) ) {
			$body = '<path d="' . esc_attr( $body ) . '"/>';
		}
		return '<svg class="dfmg-icon' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
	}

	public static function has( string $name ): bool {
		return isset( self::all()[ $name ] );
	}
}
