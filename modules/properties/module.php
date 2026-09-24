<?php
/**
 * Module Name: Bezittingen
 * Description: Beheer je bedrijven (kogelfabrieken, casinotafels, ...): prijzen instellen, winst bekijken, overdragen of opgeven. Wie een eigenaar vermoordt, neemt diens bezittingen over.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Locations;
use DigiFalk\MaffiaGame\Module\Module;
use DigiFalk\MaffiaGame\Property;

defined( 'ABSPATH' ) || exit;

final class Properties extends Module {

	public function title(): string {
		return __( 'Bezittingen', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'dfmg_character_killed', array( $this, 'take_over' ), 10, 2 );
	}

	public function take_over( Character $victim, ?Character $killer ): void {
		if ( ! $killer ) {
			DB::query( 'UPDATE {properties} SET owner_id = 0, price = 0, profit = 0 WHERE owner_id = %d', $victim->id() );
			return;
		}
		$count = (int) DB::query( 'UPDATE {properties} SET owner_id = %d, profit = 0 WHERE owner_id = %d', $killer->id(), $victim->id() );
		if ( $count ) {
			/* translators: 1: count, 2: player */
			$killer->notify( sprintf( _n( 'Je hebt %1$d bezit overgenomen van %2$s.', 'Je hebt %1$d bezittingen overgenomen van %2$s.', $count, 'wp-maffia-game' ), $count, esc_html( $victim->name ) ) );
		}
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Bezittingen', 'wp-maffia-game' ),
				'group' => 'money',
				'order' => 40,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$all = DB::results( 'SELECT * FROM {properties} WHERE owner_id > 0 ORDER BY type, location_id' );
		return $this->view(
			'properties',
			array(
				'c'     => $c,
				'mine'  => Property::owned_by( $c ),
				'all'   => $all,
				'types' => Property::types(),
			)
		);
	}

	private function mine( Character $c, array $input ): ?Property {
		$type     = sanitize_key( $input['type'] ?? '' );
		$location = absint( $input['location'] ?? 0 );
		if ( ! Property::type( $type ) || ! Locations::get( $location ) ) {
			return null;
		}
		$property = Property::get( $type, $location );
		if ( ! $property->is_owned_by( $c ) ) {
			$this->error( __( 'Dit bezit is niet van jou.', 'wp-maffia-game' ) );
			return null;
		}
		return $property;
	}

	public function action_price( Character $c, array $input ): void {
		$property = $this->mine( $c, $input );
		if ( ! $property ) {
			return;
		}
		$type  = Property::type( $property->type_key() );
		$price = Format::parse_amount( $input['price'] ?? 0 );
		$min   = (int) ( $type['setting_min'] ?? 1 );
		$max   = (int) ( $type['setting_max'] ?? 0 );
		if ( $price < $min || ( $max && $price > $max ) ) {
			/* translators: 1: min, 2: max */
			$this->error( $max ? sprintf( __( 'Kies een waarde tussen %1$s en %2$s.', 'wp-maffia-game' ), Format::money( $min ), Format::money( $max ) ) : sprintf( __( 'Kies minimaal %s.', 'wp-maffia-game' ), Format::money( $min ) ) );
			return;
		}
		$property->set_price( $price );
		$this->success( __( 'Opgeslagen.', 'wp-maffia-game' ) );
	}

	public function action_reset( Character $c, array $input ): void {
		$property = $this->mine( $c, $input );
		if ( $property ) {
			$property->reset_profit();
		}
	}

	public function action_transfer( Character $c, array $input ): void {
		$property = $this->mine( $c, $input );
		if ( ! $property ) {
			return;
		}
		$to = Character::find_by_name( sanitize_text_field( $input['to'] ?? '' ) );
		if ( ! $to || ! $to->is_alive() || $to->id() === $c->id() ) {
			$this->error( __( 'Kies een andere, levende speler.', 'wp-maffia-game' ) );
			return;
		}
		$property->transfer( $to->id() );
		/* translators: 1: player, 2: property, 3: city */
		$to->notify( sprintf( __( '%1$s heeft je %2$s in %3$s gegeven.', 'wp-maffia-game' ), $c->link(), esc_html( $property->label() ), esc_html( Locations::name( $property->location_id() ) ) ) );
		/* translators: %s: player */
		$this->success( sprintf( __( 'Overgedragen aan %s.', 'wp-maffia-game' ), $to->name ) );
	}

	public function action_drop( Character $c, array $input ): void {
		$property = $this->mine( $c, $input );
		if ( $property ) {
			$property->transfer( 0 );
			$this->success( __( 'Je hebt dit bezit opgegeven.', 'wp-maffia-game' ) );
		}
	}
}

return new Properties();
