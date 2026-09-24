<?php
/**
 * Small reusable pieces of markup for module views.
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Frontend;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Property;

defined( 'ABSPATH' ) || exit;

final class UI {

	/**
	 * "You have to wait" box with live countdown.
	 */
	public static function cooldown( string $text, int $expires ): string {
		return '<div class="dfmg-alert dfmg-alert--wait"><span>' . esc_html( $text ) . '</span> '
			. Format::countdown( $expires ) . '</div>';
	}

	public static function bar( float $percent, string $label = '' ): string {
		$percent = max( 0, min( 100, $percent ) );
		return '<div class="dfmg-bar" title="' . esc_attr( $label ?: $percent . '%' ) . '"><span style="width:' . esc_attr( (string) $percent ) . '%"></span><em>'
			. esc_html( $label ?: $percent . '%' ) . '</em></div>';
	}

	public static function empty_state( string $text ): string {
		return '<p class="dfmg-empty">' . esc_html( $text ) . '</p>';
	}

	/**
	 * Simple pager. Returns markup for pages 1..$pages linking to $base_args + paged.
	 */
	public static function pager( string $route, array $base_args, int $current, int $pages ): string {
		if ( $pages < 2 ) {
			return '';
		}
		$html = '<nav class="dfmg-pager">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			$html .= sprintf(
				'<a class="%1$s" href="%2$s">%3$d</a>',
				$i === $current ? 'is-current' : '',
				esc_url( Game::url( $route, array_merge( $base_args, array( 'paged' => $i ) ) ) ),
				$i
			);
		}
		return $html . '</nav>';
	}

	/**
	 * Ownership panel for a property in the current city, with a buy button when unowned.
	 */
	public static function property( Character $c, Property $property, string $return_route ): string {
		$owner = $property->owner();
		$html  = '<div class="dfmg-property">';
		if ( $owner && $owner->id() === $c->id() ) {
			$html .= '<span>' . esc_html__( 'Dit is jouw bezit.', 'wp-maffia-game' ) . '</span> ';
			$html .= '<a class="dfmg-button dfmg-button--ghost" href="' . esc_url( Game::url( 'properties' ) ) . '">' . esc_html__( 'Beheren', 'wp-maffia-game' ) . '</a>';
			$html .= ' <span class="dfmg-muted">' . esc_html__( 'Winst:', 'wp-maffia-game' ) . ' ' . esc_html( Format::money( $property->profit() ) ) . '</span>';
		} elseif ( $owner ) {
			/* translators: %s: player */
			$html .= sprintf( esc_html__( 'Eigenaar: %s', 'wp-maffia-game' ), $owner->link() );
		} else {
			$html .= '<span>' . esc_html__( 'Dit bezit heeft geen eigenaar.', 'wp-maffia-game' ) . '</span> ';
			$html .= Game::form_open( 'core', 'buy_property', array( 'type' => $property->type_key(), 'return' => $return_route ), 'dfmg-inline-form' );
			/* translators: %s: price */
			$html .= '<button type="submit" class="dfmg-button">' . esc_html( sprintf( __( 'Kopen voor %s', 'wp-maffia-game' ), Format::money( $property->buy_price() ) ) ) . '</button></form>';
		}
		return $html . '</div>';
	}
}
