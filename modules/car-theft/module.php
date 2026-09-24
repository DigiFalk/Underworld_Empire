<?php
/**
 * Module Name: Auto stelen
 * Description: Steel auto's op verschillende plekken in de stad. Hoe beter de plek, hoe duurder de auto's maar hoe kleiner de kans.
 * Version: 1.0.0
 * Author: DigiFalk
 * Requires: garage
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;
use DigiFalk\MaffiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class CarTheft extends Module {

	const TIMER = 'theft';

	public function title(): string {
		return __( 'Auto stelen', 'wp-maffia-game' );
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
			array( 'Een woonwijk in de nacht', 55, 1, 90, 0, 3000, 2, 45 ),
			array( 'Een parkeergarage in het centrum', 40, 1, 70, 0, 7000, 3, 60 ),
			array( 'Het parkeerterrein van het vliegveld', 30, 2, 50, 2000, 20000, 4, 90 ),
			array( 'Een villawijk', 18, 4, 30, 5000, 90000, 6, 150 ),
			array( 'De showroom van een autodealer', 8, 6, 10, 15000, 250000, 10, 240 ),
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
				'label'   => __( 'Wachttijd tussen pogingen (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 180,
			),
			'theft_jail_chance' => array(
				'label'   => __( 'Kans op gevangenis bij mislukken (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 33,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'theft_spots' => array(
				'label'   => __( 'Steelplekken', 'wp-maffia-game' ),
				'table'   => 'theft_spots',
				'order'   => 'min_rank ASC, chance DESC',
				'columns' => array(
					'name'       => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'chance'     => array( 'label' => __( 'Kans (%)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 50 ),
					'min_rank'   => array( 'label' => __( 'Vanaf rang (niveau)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 1 ),
					'max_damage' => array( 'label' => __( 'Max. schade (%)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 50 ),
					'min_value'  => array( 'label' => __( 'Min. autowaarde', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_value'  => array( 'label' => __( 'Max. autowaarde', 'wp-maffia-game' ), 'type' => 'int' ),
					'exp'        => array( 'label' => __( 'Ervaring', 'wp-maffia-game' ), 'type' => 'int', 'default' => 2 ),
					'jail_time'  => array( 'label' => __( 'Celstraf (sec)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 60 ),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Auto stelen', 'wp-maffia-game' ),
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
			$this->error( __( 'Deze plek ken je (nog) niet.', 'wp-maffia-game' ) );
			return;
		}
		$car = $this->pick_car( (int) $spot['min_value'], (int) $spot['max_value'] );
		if ( ! $car ) {
			$this->error( __( 'Er staan hier geen auto\'s. Vraag de beheerder om auto\'s toe te voegen.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'theft_cooldown' ) ) ) {
			$this->error( __( 'Je moet nog even wachten.', 'wp-maffia-game' ) );
			return;
		}

		if ( wp_rand( 1, 100 ) <= min( 100, (int) $spot['chance'] ) ) {
			$damage = wp_rand( 0, max( 0, (int) $spot['max_damage'] ) );
			Garage::give( $c, (int) $car['id'], $damage );
			$c->add( 'exp', (int) $spot['exp'] );
			$c->log( 'car-theft', true, (int) round( $car['value'] * ( 100 - $damage ) / 100 ), (int) $car['id'] );
			/* translators: 1: car, 2: damage percent, 3: value */
			$this->success( sprintf( __( 'Je hebt een %1$s gestolen met %2$d%% schade (waarde %3$s).', 'wp-maffia-game' ), $car['name'], $damage, Format::money( round( $car['value'] * ( 100 - $damage ) / 100 ) ) ) );
			return;
		}

		$c->log( 'car-theft', false, 0, (int) $car['id'] );
		if ( wp_rand( 1, 100 ) <= (int) $this->setting( 'theft_jail_chance' ) ) {
			$c->jail( (int) $spot['jail_time'] );
			/* translators: %s: car */
			$this->error( sprintf( __( 'Het alarm van de %s ging af. De politie pakt je op.', 'wp-maffia-game' ), $car['name'] ) );
		} else {
			/* translators: %s: car */
			$this->error( sprintf( __( 'Het lukte niet om de %s open te krijgen.', 'wp-maffia-game' ), $car['name'] ) );
		}
	}
}

return new CarTheft();
