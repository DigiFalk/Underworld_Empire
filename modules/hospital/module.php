<?php
/**
 * Module Name: Hospital
 * Description: Get your wounds treated. The bigger the damage, the more expensive and longer the stay.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Hospital extends Module {

	public function title(): string {
		return __( 'Hospital', 'wp-maffia-game' );
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function allowed_in_jail(): bool {
		return false;
	}

	public function boot(): void {
		add_filter(
			'dfmg_route',
			static function ( $route, Character $c, Module $module ) {
				if ( 'jail' !== $route && $c->is_hospitalized() && ! $module->allowed_in_hospital() ) {
					return 'hospital';
				}
				return $route;
			},
			20,
			3
		);
	}

	public function settings_fields(): array {
		return array(
			'hospital_full_cost' => array(
				'label'   => __( 'Cost of a full recovery', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25000,
			),
			'hospital_full_time' => array(
				'label'   => __( 'Admission time for a full recovery (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 3600,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Hospital', 'wp-maffia-game' ),
				'group' => 'city',
				'order' => 30,
				'timer' => 'hospital',
			),
		);
	}

	private function quote( Character $c ): array {
		$damage_pct = min( 100, (int) $c->damage / $c->max_health() * 100 );
		return array(
			'percent' => round( $damage_pct, 1 ),
			'cost'    => (int) round( (int) $this->setting( 'hospital_full_cost' ) * $damage_pct / 100 ),
			'time'    => (int) round( (int) $this->setting( 'hospital_full_time' ) * $damage_pct / 100 ),
		);
	}

	public function render( Character $c, array $query ): string {
		$patients = DB::column(
			'SELECT t.character_id FROM {timers} t INNER JOIN {characters} ch ON ch.id = t.character_id
			 WHERE t.name = %s AND t.expires_at > %d AND ch.location_id = %d AND ch.status = 1',
			'hospital',
			time(),
			(int) $c->location_id
		);
		return $this->view(
			'hospital',
			array(
				'c'        => $c,
				'quote'    => $this->quote( $c ),
				'patients' => array_filter( array_map( array( Character::class, 'find' ), array_map( 'intval', $patients ) ) ),
			)
		);
	}

	public function action_admit( Character $c, array $input ): void {
		if ( $c->is_hospitalized() ) {
			$this->error( __( 'You are already in hospital.', 'wp-maffia-game' ) );
			return;
		}
		$quote = $this->quote( $c );
		if ( ! (int) $c->damage ) {
			$this->error( __( 'You are perfectly healthy.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'money', $quote['cost'] ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'The treatment costs %s. You don\'t have that in cash.', 'wp-maffia-game' ), Format::money( $quote['cost'] ) ) );
			return;
		}
		$c->set( 'damage', 0 );
		$c->set_timer( 'hospital', time() + $quote['time'] );
		$c->log( 'hospital', true, $quote['cost'] );
		$this->success( __( 'You have been admitted. The doctors are doing their job.', 'wp-maffia-game' ) );
	}
}

return new Hospital();
