<?php
/**
 * Small reusable pieces of markup for module views.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Property;

defined( 'ABSPATH' ) || exit;

final class UI {

	/**
	 * "You have to wait" box with live countdown.
	 */
	public static function cooldown( string $text, int $expires ): string {
		return '<div class="dfmg-alert dfmg-alert--wait"><span class="dfmg-alert__icon">' . \DigiFalk\UnderworldEmpire\Icons::svg( 'wait', 20 ) . '</span><div class="dfmg-alert__text"><span>' . esc_html( $text ) . '</span> '
			. Format::countdown( $expires ) . '</div></div>';
	}

	public static function bar( float $percent, string $label = '', bool $slim = false ): string {
		$percent = max( 0, min( 100, $percent ) );
		if ( $slim ) {
			return '<div class="dfmg-bar dfmg-bar--slim" role="progressbar" aria-valuenow="' . esc_attr( (string) $percent ) . '" aria-valuemin="0" aria-valuemax="100"><span style="width:' . esc_attr( (string) $percent ) . '%"></span></div>';
		}
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
			$html .= '<span>' . esc_html__( 'This is your property.', 'underworld-empire' ) . '</span> ';
			$html .= '<a class="dfmg-button dfmg-button--ghost" href="' . esc_url( Game::url( 'properties' ) ) . '">' . esc_html__( 'Manage', 'underworld-empire' ) . '</a>';
			$html .= ' <span class="dfmg-muted">' . esc_html__( 'Profit:', 'underworld-empire' ) . ' ' . esc_html( Format::money( $property->profit() ) ) . '</span>';
		} elseif ( $owner ) {
			/* translators: %s: player */
			$html .= sprintf( esc_html__( 'Owner: %s', 'underworld-empire' ), $owner->link() );
		} else {
			$html .= '<span>' . esc_html__( 'This property has no owner.', 'underworld-empire' ) . '</span> ';
			$html .= Game::form_open( 'core', 'buy_property', array( 'type' => $property->type_key(), 'return' => $return_route ), 'dfmg-inline-form' );
			/* translators: %s: price */
			$html .= '<button type="submit" class="dfmg-button">' . esc_html( sprintf( __( 'Buy for %s', 'underworld-empire' ), Format::money( $property->buy_price() ) ) ) . '</button></form>';
		}
		return $html . '</div>';
	}
}
