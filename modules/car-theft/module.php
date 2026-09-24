<?php
/**
 * Module Name: Car Theft
 * Description: Steal cars at different spots in the city. The better the spot, the more expensive the cars but the lower the chance.
 * Version: 1.0.0
 * Author: DigiFalk
 * Requires: garage
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame\Modules;

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\DB;
use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Module\Module;
use DigiFalk\MafiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class CarTheft extends Module {

	const TIMER = 'theft';

	public function title(): string {
		return __( 'Car theft', 'wp-mafia-game' );
	}

	public function schema(): array {
		return array(
			'theft_spots' => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				chance int(11) NOT NULL DEFAULT 50,
				min_rank int(11) NOT NULL DEFAULT 1,
				max_damage int(11) NOT NULL DEFAULT 50,
				min_value bigint(20) NOT NULL DEFAULT 0,
				max_value bigint(20) NOT NULL DEFAULT 0,
				exp int(11) NOT NULL DEFAULT 2,
				jail_time int(11) NOT NULL DEFAULT 60,
				PRIMARY KEY  (id)",
		);
	}

	public function seed(): void {
		$spots = array(
			// name, chance, rank, max damage, min value, max value, exp, jail.
			array( 'A residential street at night', 55, 1, 90, 0, 3000, 2, 45 ),
			array( 'A downtown parking garage', 40, 1, 70, 0, 7000, 3, 60 ),
			array( 'The airport parking lot', 30, 2, 50, 2000, 20000, 4, 90 ),
			array( 'A villa district', 18, 4, 30, 5000, 90000, 6, 150 ),
			array( 'A car dealer showroom', 8, 6, 10, 15000, 250000, 10, 240 ),
		);
		foreach ( $spots as $s ) {
			DB::insert(
				'theft_spots',
				array(
					'name'       => $s[0],
					'chance'     => $s[1],
					'min_rank'   => $s[2],
					'max_damage' => $s[3],
					'min_value'  => $s[4],
					'max_value'  => $s[5],
					'exp'        => $s[6],
					'jail_time'  => $s[7],
				)
			);
		}
	}

	public function settings_fields(): array {
		return array(
			'theft_cooldown'    => array(
				'label'   => __( 'Cooldown between attempts (sec)', 'wp-mafia-game' ),
				'type'    => 'int',
				'default' => 180,
			),
			'theft_jail_chance' => array(
				'label'   => __( 'Chance of jail on failure (%)', 'wp-mafia-game' ),
				'type'    => 'int',
				'default' => 33,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'theft_spots' => array(
				'label'   => __( 'Theft spots', 'wp-mafia-game' ),
				'table'   => 'theft_spots',
				'order'   => 'min_rank ASC, chance DESC',
				'columns' => array(
					'name'       => array( 'label' => __( 'Name', 'wp-mafia-game' ), 'required' => true ),
					'chance'     => array( 'label' => __( 'Chance (%)', 'wp-mafia-game' ), 'type' => 'int', 'default' => 50 ),
					'min_rank'   => array( 'label' => __( 'From rank (level)', 'wp-mafia-game' ), 'type' => 'int', 'default' => 1 ),
					'max_damage' => array( 'label' => __( 'Max. damage (%)', 'wp-mafia-game' ), 'type' => 'int', 'default' => 50 ),
					'min_value'  => array( 'label' => __( 'Min. car value', 'wp-mafia-game' ), 'type' => 'int' ),
					'max_value'  => array( 'label' => __( 'Max. car value', 'wp-mafia-game' ), 'type' => 'int' ),
					'exp'        => array( 'label' => __( 'Experience', 'wp-mafia-game' ), 'type' => 'int', 'default' => 2 ),
					'jail_time'  => array( 'label' => __( 'Jail time (sec)', 'wp-mafia-game' ), 'type' => 'int', 'default' => 60 ),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Car theft', 'wp-mafia-game' ),
				'group' => 'crime',
				'order' => 20,
				'timer' => self::TIMER,
			),
		);
	}

	private function spots( Character $c ): array {
		$rows = DB::results( 'SELECT * FROM {theft_spots} WHERE min_rank <= %d ORDER BY chance DESC', Ranks::level( (int) $c->rank_id ) );
		return (array) apply_filters( 'dfmg_module_data', $rows, 'car-theft', $c );
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'theft',
			array(
				'c'        => $c,
				'spots'    => $this->spots( $c ),
				'cooldown' => $c->cooldown_seconds( self::TIMER, (int) $this->setting( 'theft_cooldown' ) ),
			)
		);
	}

	/**
	 * Pick a random car within a value range, weighted by rarity.
	 */
	private function pick_car( int $min, int $max ): ?array {
		$cars  = DB::results( 'SELECT * FROM {cars} WHERE value >= %d AND value <= %d AND rarity > 0', $min, $max );
		$total = array_sum( array_map( 'intval', wp_list_pluck( $cars, 'rarity' ) ) );
		if ( ! $total ) {
			return null;
		}
		$roll = wp_rand( 1, $total );
		foreach ( $cars as $car ) {
			$roll -= (int) $car['rarity'];
			if ( $roll <= 0 ) {
				return $car;
			}
		}
		return end( $cars ) ?: null;
	}

	public function action_steal( Character $c, array $input ): void {
		$id   = absint( $input['spot'] ?? 0 );
		$spot = null;
		foreach ( $this->spots( $c ) as $row ) {
			if ( (int) $row['id'] === $id ) {
				$spot = $row;
			}
		}
		if ( ! $spot ) {
			$this->error( __( 'You don\'t know this spot (yet).', 'wp-mafia-game' ) );
			return;
		}
		$car = $this->pick_car( (int) $spot['min_value'], (int) $spot['max_value'] );
		if ( ! $car ) {
			$this->error( __( 'There are no cars here. Ask the administrator to add cars.', 'wp-mafia-game' ) );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'theft_cooldown' ) ) ) {
			$this->error( __( 'You have to wait a little.', 'wp-mafia-game' ) );
			return;
		}

		if ( wp_rand( 1, 100 ) <= min( 100, (int) $spot['chance'] ) ) {
			$damage = wp_rand( 0, max( 0, (int) $spot['max_damage'] ) );
			Garage::give( $c, (int) $car['id'], $damage );
			$c->add( 'exp', (int) $spot['exp'] );
			$c->log( 'car-theft', true, (int) round( $car['value'] * ( 100 - $damage ) / 100 ), (int) $car['id'] );
			/* translators: 1: car, 2: damage percent, 3: value */
			$this->success( sprintf( __( 'Car stolen: %1$s with %2$d%% damage (value %3$s).', 'wp-mafia-game' ), $car['name'], $damage, Format::money( round( $car['value'] * ( 100 - $damage ) / 100 ) ) ) );
			return;
		}

		$c->log( 'car-theft', false, 0, (int) $car['id'] );
		if ( wp_rand( 1, 100 ) <= (int) $this->setting( 'theft_jail_chance' ) ) {
			$c->jail( (int) $spot['jail_time'] );
			/* translators: %s: car */
			$this->error( sprintf( __( 'The alarm of the %s went off. The police arrest you.', 'wp-mafia-game' ), $car['name'] ) );
		} else {
			/* translators: %s: car */
			$this->error( sprintf( __( 'You couldn\'t get the %s open.', 'wp-mafia-game' ), $car['name'] ) );
		}
	}
}

return new CarTheft();
