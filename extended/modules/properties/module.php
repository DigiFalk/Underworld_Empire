<?php
/**
 * Module Name: Properties
 * Description: Manage your businesses (bullet factories, casino tables, ...): set prices, view profit, transfer or give up. Whoever murders an owner takes over their properties.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Locations;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Property;

defined( 'ABSPATH' ) || exit;

final class Properties extends Module {

	public function title(): string {
		return __( 'Properties', 'underworld-empire' );
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
			$killer->notify( sprintf( _n( 'You took over %1$d property from %2$s.', 'You took over %1$d properties from %2$s.', $count, 'underworld-empire' ), $count, esc_html( $victim->name ) ) );
		}
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Properties', 'underworld-empire' ),
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
			$this->error( __( 'This property isn\'t yours.', 'underworld-empire' ) );
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
			$this->error( $max ? sprintf( __( 'Choose a value between %1$s and %2$s.', 'underworld-empire' ), Format::money( $min ), Format::money( $max ) ) : sprintf( __( 'Choose at least %s.', 'underworld-empire' ), Format::money( $min ) ) );
			return;
		}
		$property->set_price( $price );
		$this->success( __( 'Saved.', 'underworld-empire' ) );
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
			$this->error( __( 'Choose another living player.', 'underworld-empire' ) );
			return;
		}
		$property->transfer( $to->id() );
		/* translators: 1: player, 2: property, 3: city */
		$to->notify( sprintf( __( '%1$s gave you their %2$s in %3$s.', 'underworld-empire' ), $c->link(), esc_html( $property->label() ), esc_html( Locations::name( $property->location_id() ) ) ) );
		/* translators: %s: player */
		$this->success( sprintf( __( 'Transferred to %s.', 'underworld-empire' ), $to->name ) );
	}

	public function action_drop( Character $c, array $input ): void {
		$property = $this->mine( $c, $input );
		if ( $property ) {
			$property->transfer( 0 );
			$this->success( __( 'You gave up this property.', 'underworld-empire' ) );
		}
	}
}

return new Properties();
