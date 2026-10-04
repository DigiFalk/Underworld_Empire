<?php
/**
 * Module Name: Detectives
 * Description: Hire detectives to find out in which city another player is hiding.
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

final class Detectives extends Module {

	public function title(): string {
		return __( 'Detectives', 'underworld-empire' );
	}

	public function schema(): array {
		return array(
			'detectives' => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				character_id bigint(20) unsigned NOT NULL,
				target_id bigint(20) unsigned NOT NULL,
				detectives int(11) NOT NULL DEFAULT 1,
				hours int(11) NOT NULL DEFAULT 1,
				started_at int(11) NOT NULL DEFAULT 0,
				ready_at int(11) NOT NULL DEFAULT 0,
				success tinyint(1) NOT NULL DEFAULT 0,
				found_location int(11) NOT NULL DEFAULT 0,
				used tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY character_id (character_id)',
		);
	}

	public function round_tables(): array {
		return array( 'detectives' );
	}

	public function settings_fields(): array {
		return array(
			'detective_cost'         => array(
				'label'   => __( 'Cost per detective per hour', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 25000,
			),
			'detective_hour_seconds' => array(
				'label'       => __( 'Length of a search "hour" (sec)', 'underworld-empire' ),
				'type'        => 'int',
				'default'     => 600,
				'description' => __( 'How long a search hour takes in real time.', 'underworld-empire' ),
			),
			'detective_valid'        => array(
				'label'   => __( 'Report valid after completion (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 900,
			),
			'detective_max'          => array(
				'label'   => __( 'Max. detectives / hours', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 5,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Detectives', 'underworld-empire' ),
				'group' => 'murder',
				'order' => 10,
			),
		);
	}

	/**
	 * Fill in the location of finished, successful searches.
	 */
	private static function resolve_finished( Character $c ): void {
		$rows = DB::results(
			'SELECT id, target_id FROM {detectives} WHERE character_id = %d AND success = 1 AND found_location = 0 AND ready_at <= %d',
			$c->id(),
			time()
		);
		foreach ( $rows as $row ) {
			$target = Character::find( (int) $row['target_id'] );
			DB::update( 'detectives', array( 'found_location' => $target ? (int) $target->location_id : -1 ), array( 'id' => $row['id'] ) );
		}
	}

	/**
	 * Successful, finished and still valid reports. Used by the murder module.
	 */
	public static function valid_reports( Character $c ): array {
		self::resolve_finished( $c );
		$valid = (int) \DigiFalk\UnderworldEmpire\Settings::get( 'detective_valid', 900 );
		return DB::results(
			'SELECT * FROM {detectives} WHERE character_id = %d AND success = 1 AND used = 0 AND found_location > 0
			 AND ready_at <= %d AND ready_at > %d ORDER BY ready_at DESC',
			$c->id(),
			time(),
			time() - $valid
		);
	}

	public static function use_report( int $id ): void {
		DB::update( 'detectives', array( 'used' => 1 ), array( 'id' => $id ) );
	}

	public function render( Character $c, array $query ): string {
		self::resolve_finished( $c );
		$reports = DB::results( 'SELECT * FROM {detectives} WHERE character_id = %d ORDER BY started_at DESC LIMIT 25', $c->id() );
		return $this->view(
			'detectives',
			array(
				'c'       => $c,
				'reports' => $reports,
				'cost'    => (int) $this->setting( 'detective_cost' ),
				'max'     => max( 1, (int) $this->setting( 'detective_max' ) ),
				'hour'    => (int) $this->setting( 'detective_hour_seconds' ),
				'valid'   => (int) $this->setting( 'detective_valid' ),
				'target'  => $query['target'] ?? '',
			)
		);
	}

	public function action_hire( Character $c, array $input ): void {
		$target = Character::find_by_name( sanitize_text_field( $input['target'] ?? '' ) );
		$max    = max( 1, (int) $this->setting( 'detective_max' ) );
		$count  = absint( $input['detectives'] ?? 0 );
		$hours  = absint( $input['hours'] ?? 0 );
		if ( ! $target || ! $target->is_alive() ) {
			$this->error( __( 'This player doesn\'t exist or is no longer alive.', 'underworld-empire' ) );
			return;
		}
		if ( $target->id() === $c->id() ) {
			$this->error( __( 'Surely you know where you are yourself?', 'underworld-empire' ) );
			return;
		}
		if ( $count < 1 || $count > $max || $hours < 1 || $hours > $max ) {
			/* translators: %d: max */
			$this->error( sprintf( __( 'Choose 1 to %d detectives and 1 to %d hours.', 'underworld-empire' ), $max, $max ) );
			return;
		}
		$cost = $count * $hours * (int) $this->setting( 'detective_cost' );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'That costs %s. You don\'t have that in cash.', 'underworld-empire' ), Format::money( $cost ) ) );
			return;
		}
		$chance  = (int) apply_filters( 'dfmg_detective_chance', min( 100, $count * $hours * 4 ), $c, $target, $count, $hours );
		$success = wp_rand( 1, 100 ) <= $chance;
		DB::insert(
			'detectives',
			array(
				'character_id' => $c->id(),
				'target_id'    => $target->id(),
				'detectives'   => $count,
				'hours'        => $hours,
				'started_at'   => time(),
				'ready_at'     => time() + $hours * (int) $this->setting( 'detective_hour_seconds' ),
				'success'      => $success ? 1 : 0,
			)
		);
		$c->log( 'detectives.hire', true, $cost, $target->id() );
		/* translators: %s: player */
		$this->success( sprintf( __( 'Your detectives are searching for %s.', 'underworld-empire' ), $target->name ) );
	}

	public function action_remove( Character $c, array $input ): void {
		DB::delete(
			'detectives',
			array(
				'id'           => absint( $input['report'] ?? 0 ),
				'character_id' => $c->id(),
			)
		);
	}
}

return new Detectives();
