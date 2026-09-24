<?php
/**
 * Module Name: Detectives
 * Description: Huur detectives in om uit te zoeken in welke stad een andere speler zich schuilhoudt.
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

final class Detectives extends Module {

	public function title(): string {
		return __( 'Detectives', 'wp-maffia-game' );
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
				'label'   => __( 'Kosten per detective per uur', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25000,
			),
			'detective_hour_seconds' => array(
				'label'       => __( 'Duur van een zoek-"uur" (sec)', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 600,
				'description' => __( 'Hoe lang een zoekuur in het echt duurt.', 'wp-maffia-game' ),
			),
			'detective_valid'        => array(
				'label'   => __( 'Rapport geldig na afronding (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 900,
			),
			'detective_max'          => array(
				'label'   => __( 'Max. detectives / uren', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 5,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Detectives', 'wp-maffia-game' ),
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
		$valid = (int) \DigiFalk\MaffiaGame\Settings::get( 'detective_valid', 900 );
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
			$this->error( __( 'Deze speler bestaat niet of leeft niet meer.', 'wp-maffia-game' ) );
			return;
		}
		if ( $target->id() === $c->id() ) {
			$this->error( __( 'Je weet zelf toch wel waar je bent?', 'wp-maffia-game' ) );
			return;
		}
		if ( $count < 1 || $count > $max || $hours < 1 || $hours > $max ) {
			/* translators: %d: max */
			$this->error( sprintf( __( 'Kies 1 tot %d detectives en 1 tot %d uur.', 'wp-maffia-game' ), $max, $max ) );
			return;
		}
		$cost = $count * $hours * (int) $this->setting( 'detective_cost' );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Dat kost %s. Dat heb je niet contant.', 'wp-maffia-game' ), Format::money( $cost ) ) );
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
		$this->success( sprintf( __( 'Je detectives gaan op zoek naar %s.', 'wp-maffia-game' ), $target->name ) );
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
