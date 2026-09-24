<?php
/**
 * Module Name: Garage
 * Description: Manage your cars: sell, repair, ship to your current city or crush them for bullets.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Garage extends Module {

	public function title(): string {
		return __( 'Garage', 'underworld-empire' );
	}

	public function schema(): array {
		return array(
			'cars'   => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				value bigint(20) NOT NULL DEFAULT 0,
				rarity int(11) NOT NULL DEFAULT 100,
				PRIMARY KEY  (id)",
			'garage' => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				character_id bigint(20) unsigned NOT NULL,
				car_id int(11) NOT NULL,
				damage int(11) NOT NULL DEFAULT 0,
				location_id int(11) NOT NULL DEFAULT 0,
				acquired_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY character_id (character_id)',
		);
	}

	public function round_tables(): array {
		return array( 'garage' );
	}

	public function seed(): void {
		$cars = array(
			array( 'Fiat Panda', 500, 400 ),
			array( 'Opel Corsa', 900, 350 ),
			array( 'Volkswagen Golf', 2500, 250 ),
			array( 'Audi A4', 6000, 150 ),
			array( 'BMW M3', 18000, 80 ),
			array( 'Mercedes S-Class', 40000, 40 ),
			array( 'Porsche 911', 85000, 15 ),
			array( 'Lamborghini Huracán', 220000, 4 ),
		);
		foreach ( $cars as $car ) {
			DB::insert(
				'cars',
				array(
					'name'   => $car[0],
					'value'  => $car[1],
					'rarity' => $car[2],
				)
			);
		}
	}

	public function settings_fields(): array {
		return array(
			'garage_crush_rate'    => array(
				'label'       => __( 'Car crusher: value per bullet', 'underworld-empire' ),
				'type'        => 'int',
				'default'     => 15,
			),
			'garage_repair_factor' => array(
				'label'   => __( 'Repair cost (% of damage value)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 80,
			),
			'garage_ship_percent'  => array(
				'label'   => __( 'Shipping cost (% of car value)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 10,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'cars' => array(
				'label'   => __( 'Cars', 'underworld-empire' ),
				'table'   => 'cars',
				'order'   => 'value ASC',
				'columns' => array(
					'name'   => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'value'  => array( 'label' => __( 'Value', 'underworld-empire' ), 'type' => 'int' ),
					'rarity' => array(
						'label'       => __( 'Rarity (weight)', 'underworld-empire' ),
						'type'        => 'int',
						'default'     => 100,
						'description' => __( 'The higher, the more often this car gets stolen.', 'underworld-empire' ),
					),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		$count = (int) DB::value( 'SELECT COUNT(*) FROM {garage} WHERE character_id = %d', $c->id() );
		return array(
			array(
				'label' => __( 'Garage', 'underworld-empire' ),
				'group' => 'money',
				'order' => 30,
				'badge' => $count ?: '',
			),
		);
	}

	/**
	 * Value of a car after damage.
	 */
	public static function value( array $row ): int {
		return (int) round( (int) $row['value'] * ( 100 - (int) $row['damage'] ) / 100 );
	}

	/**
	 * Give a car to a character. Used by other modules (car theft, rewards...).
	 */
	public static function give( Character $c, int $car_id, int $damage = 0, int $location_id = 0 ): int {
		return DB::insert(
			'garage',
			array(
				'character_id' => $c->id(),
				'car_id'       => $car_id,
				'damage'       => max( 0, min( 100, $damage ) ),
				'location_id'  => $location_id ?: (int) $c->location_id,
				'acquired_at'  => time(),
			)
		);
	}

	private function owned( Character $c, int $id ): ?array {
		$row = DB::row(
			'SELECT g.*, cars.name, cars.value FROM {garage} g INNER JOIN {cars} cars ON cars.id = g.car_id WHERE g.id = %d AND g.character_id = %d',
			$id,
			$c->id()
		);
		if ( ! $row ) {
			$this->error( __( 'This car is not in your garage.', 'underworld-empire' ) );
		}
		return $row;
	}

	private function here( Character $c, array $row ): bool {
		if ( (int) $row['location_id'] !== (int) $c->location_id ) {
			$this->error( __( 'This car is in another city. Ship it here first.', 'underworld-empire' ) );
			return false;
		}
		return true;
	}

	public function render( Character $c, array $query ): string {
		$cars = DB::results(
			'SELECT g.*, cars.name, cars.value FROM {garage} g INNER JOIN {cars} cars ON cars.id = g.car_id
			 WHERE g.character_id = %d ORDER BY g.location_id = %d DESC, cars.value DESC',
			$c->id(),
			(int) $c->location_id
		);
		foreach ( $cars as &$car ) {
			$car['worth']       = self::value( $car );
			$car['repair_cost'] = $this->repair_cost( $car );
			$car['ship_cost']   = $this->ship_cost( $car );
			$car['bullets']     = $this->crush_bullets( $car );
		}
		unset( $car );
		return $this->view(
			'garage',
			array(
				'c'    => $c,
				'cars' => $cars,
			)
		);
	}

	private function repair_cost( array $row ): int {
		return (int) round( (int) $row['value'] * (int) $row['damage'] / 100 * (int) $this->setting( 'garage_repair_factor' ) / 100 );
	}

	private function ship_cost( array $row ): int {
		return (int) round( self::value( $row ) * (int) $this->setting( 'garage_ship_percent' ) / 100 );
	}

	private function crush_bullets( array $row ): int {
		return (int) floor( self::value( $row ) / max( 1, (int) $this->setting( 'garage_crush_rate' ) ) );
	}

	public function action_sell( Character $c, array $input ): void {
		$row = $this->owned( $c, absint( $input['car'] ?? 0 ) );
		if ( ! $row || ! $this->here( $c, $row ) ) {
			return;
		}
		if ( DB::delete( 'garage', array( 'id' => $row['id'], 'character_id' => $c->id() ) ) ) {
			$worth = self::value( $row );
			$c->add( 'money', $worth );
			$c->log( 'garage.sell', true, $worth, (int) $row['car_id'] );
			/* translators: 1: car, 2: money */
			$this->success( sprintf( __( 'You sold your %1$s for %2$s.', 'underworld-empire' ), $row['name'], Format::money( $worth ) ) );
		}
	}

	public function action_crush( Character $c, array $input ): void {
		$row = $this->owned( $c, absint( $input['car'] ?? 0 ) );
		if ( ! $row || ! $this->here( $c, $row ) ) {
			return;
		}
		if ( DB::delete( 'garage', array( 'id' => $row['id'], 'character_id' => $c->id() ) ) ) {
			$bullets = $this->crush_bullets( $row );
			$c->add( 'bullets', $bullets );
			$c->log( 'garage.crush', true, $bullets, (int) $row['car_id'] );
			/* translators: 1: car, 2: bullets */
			$this->success( sprintf( __( 'Your %1$s went into the crusher. You got %2$s bullets.', 'underworld-empire' ), $row['name'], Format::number( $bullets ) ) );
		}
	}

	public function action_repair( Character $c, array $input ): void {
		$row = $this->owned( $c, absint( $input['car'] ?? 0 ) );
		if ( ! $row || ! $this->here( $c, $row ) ) {
			return;
		}
		if ( ! (int) $row['damage'] ) {
			$this->error( __( 'This car has no damage.', 'underworld-empire' ) );
			return;
		}
		$cost = $this->repair_cost( $row );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'The repair costs %s. You don\'t have that in cash.', 'underworld-empire' ), Format::money( $cost ) ) );
			return;
		}
		DB::update( 'garage', array( 'damage' => 0 ), array( 'id' => $row['id'] ) );
		/* translators: 1: car, 2: money */
		$this->success( sprintf( __( 'Your %1$s is as good as new. Cost: %2$s.', 'underworld-empire' ), $row['name'], Format::money( $cost ) ) );
	}

	public function action_ship( Character $c, array $input ): void {
		$row = $this->owned( $c, absint( $input['car'] ?? 0 ) );
		if ( ! $row ) {
			return;
		}
		if ( (int) $row['location_id'] === (int) $c->location_id ) {
			$this->error( __( 'This car is already in this city.', 'underworld-empire' ) );
			return;
		}
		$cost = $this->ship_cost( $row );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Shipping costs %s. You don\'t have that in cash.', 'underworld-empire' ), Format::money( $cost ) ) );
			return;
		}
		DB::update( 'garage', array( 'location_id' => (int) $c->location_id ), array( 'id' => $row['id'] ) );
		/* translators: 1: car, 2: city */
		$this->success( sprintf( __( 'Your %1$s is now in %2$s.', 'underworld-empire' ), $row['name'], $c->location_name() ) );
	}
}

return new Garage();
