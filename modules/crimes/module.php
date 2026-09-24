<?php
/**
 * Module Name: Crimes
 * Description: Commit small and big crimes for money, bullets and experience. The more often you commit a crime, the better you get at it.
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
use DigiFalk\UnderworldEmpire\Ranks;

defined( 'ABSPATH' ) || exit;

final class Crimes extends Module {

	const TIMER = 'crime';

	public function title(): string {
		return __( 'Crimes', 'underworld-empire' );
	}

	public function schema(): array {
		return array(
			'crimes'      => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				description text NULL,
				min_rank int(11) NOT NULL DEFAULT 1,
				cooldown int(11) NOT NULL DEFAULT 60,
				min_money bigint(20) NOT NULL DEFAULT 0,
				max_money bigint(20) NOT NULL DEFAULT 0,
				min_bullets int(11) NOT NULL DEFAULT 0,
				max_bullets int(11) NOT NULL DEFAULT 0,
				exp int(11) NOT NULL DEFAULT 1,
				start_chance int(11) NOT NULL DEFAULT 20,
				jail_time int(11) NOT NULL DEFAULT 60,
				PRIMARY KEY  (id)",
			'crime_skill' => '
				character_id bigint(20) unsigned NOT NULL,
				crime_id int(11) NOT NULL,
				skill int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (character_id,crime_id)',
		);
	}

	public function round_tables(): array {
		return array( 'crime_skill' );
	}

	public function seed(): void {
		$crimes = array(
			// name, description, rank, cooldown, money min/max, bullets min/max, exp, start chance, jail.
			array( 'Pickpocket at the market', 'A quick grab into a full bag.', 1, 30, 15, 60, 0, 2, 1, 40, 30 ),
			array( 'Rob a corner shop', 'Empty the till, hood up.', 1, 60, 40, 150, 0, 5, 2, 30, 45 ),
			array( 'Scam tourists', 'Fake watches for real prices.', 2, 90, 100, 350, 0, 5, 3, 25, 60 ),
			array( 'Rob a jewellery store', 'Fast, loud and risky.', 3, 150, 400, 1200, 5, 15, 5, 15, 90 ),
			array( 'Hit an armoured truck', 'Plan it well or do long time.', 5, 300, 2000, 6000, 10, 40, 10, 10, 180 ),
			array( 'Crack the casino vault', 'The big job, professionals only.', 7, 600, 10000, 30000, 25, 100, 20, 5, 300 ),
		);
		foreach ( $crimes as $r ) {
			DB::insert(
				'crimes',
				array(
					'name'         => $r[0],
					'description'  => $r[1],
					'min_rank'     => $r[2],
					'cooldown'     => $r[3],
					'min_money'    => $r[4],
					'max_money'    => $r[5],
					'min_bullets'  => $r[6],
					'max_bullets'  => $r[7],
					'exp'          => $r[8],
					'start_chance' => $r[9],
					'jail_time'    => $r[10],
				)
			);
		}
	}

	public function settings_fields(): array {
		return array(
			'crimes_jail_chance' => array(
				'label'       => __( 'Chance of jail on failure (%)', 'underworld-empire' ),
				'type'        => 'int',
				'default'     => 33,
			),
			'crimes_max_skill'   => array(
				'label'   => __( 'Maximum success chance (%)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 95,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'crimes' => array(
				'label'   => __( 'Crimes', 'underworld-empire' ),
				'table'   => 'crimes',
				'order'   => 'min_rank ASC, id ASC',
				'columns' => array(
					'name'         => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'description'  => array( 'label' => __( 'Description', 'underworld-empire' ), 'type' => 'textarea' ),
					'min_rank'     => array( 'label' => __( 'From rank (level)', 'underworld-empire' ), 'type' => 'int', 'default' => 1 ),
					'cooldown'     => array( 'label' => __( 'Cooldown (sec)', 'underworld-empire' ), 'type' => 'int', 'default' => 60 ),
					'min_money'    => array( 'label' => __( 'Min. money', 'underworld-empire' ), 'type' => 'int' ),
					'max_money'    => array( 'label' => __( 'Max. money', 'underworld-empire' ), 'type' => 'int' ),
					'min_bullets'  => array( 'label' => __( 'Min. bullets', 'underworld-empire' ), 'type' => 'int', 'list' => false ),
					'max_bullets'  => array( 'label' => __( 'Max. bullets', 'underworld-empire' ), 'type' => 'int', 'list' => false ),
					'exp'          => array( 'label' => __( 'Experience', 'underworld-empire' ), 'type' => 'int', 'default' => 1 ),
					'start_chance' => array( 'label' => __( 'Starting chance (%)', 'underworld-empire' ), 'type' => 'int', 'default' => 20 ),
					'jail_time'    => array( 'label' => __( 'Jail time (sec)', 'underworld-empire' ), 'type' => 'int', 'default' => 60 ),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Crimes', 'underworld-empire' ),
				'group' => 'crime',
				'order' => 10,
				'timer' => self::TIMER,
			),
		);
	}

	private function skill( Character $c, array $crime ): int {
		$skill = DB::value( 'SELECT skill FROM {crime_skill} WHERE character_id = %d AND crime_id = %d', $c->id(), $crime['id'] );
		return null === $skill ? (int) $crime['start_chance'] : (int) $skill;
	}

	/**
	 * Crimes available to a character, after the dfmg_module_data filter.
	 */
	private function crimes( Character $c ): array {
		$rows = DB::results( 'SELECT * FROM {crimes} WHERE min_rank <= %d ORDER BY min_rank ASC, id ASC', Ranks::level( (int) $c->rank_id ) );
		return (array) apply_filters( 'dfmg_module_data', $rows, 'crimes', $c );
	}

	public function render( Character $c, array $query ): string {
		$crimes = array();
		foreach ( $this->crimes( $c ) as $crime ) {
			$crime['skill']    = $this->skill( $c, $crime );
			$crime['cooldown'] = $c->cooldown_seconds( self::TIMER, (int) $crime['cooldown'] );
			$crimes[]          = $crime;
		}
		return $this->view(
			'crimes',
			array(
				'c'      => $c,
				'crimes' => $crimes,
			)
		);
	}

	public function action_commit( Character $c, array $input ): void {
		$id    = absint( $input['crime'] ?? 0 );
		$crime = null;
		foreach ( $this->crimes( $c ) as $row ) {
			if ( (int) $row['id'] === $id ) {
				$crime = $row;
			}
		}
		if ( ! $crime ) {
			$this->error( __( 'This crime doesn\'t exist or is too hard for you yet.', 'underworld-empire' ) );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) $crime['cooldown'] ) ) {
			$this->error( __( 'You have to wait a little before your next crime.', 'underworld-empire' ) );
			return;
		}

		$skill   = $this->skill( $c, $crime );
		$success = wp_rand( 1, 100 ) <= $skill;

		if ( $success ) {
			$money   = wp_rand( (int) $crime['min_money'], max( (int) $crime['min_money'], (int) $crime['max_money'] ) );
			$bullets = wp_rand( (int) $crime['min_bullets'], max( (int) $crime['min_bullets'], (int) $crime['max_bullets'] ) );
			$c->add( 'money', $money );
			$c->add( 'bullets', $bullets );
			$c->add( 'exp', (int) $crime['exp'] );
			$gain = wp_rand( 1, 4 );

			$loot = array( Format::money( $money ) );
			if ( $bullets ) {
				/* translators: %s: number of bullets */
				$loot[] = sprintf( __( '%s bullets', 'underworld-empire' ), Format::number( $bullets ) );
			}
			/* translators: 1: crime, 2: loot */
			$this->success( sprintf( __( 'Success: "%1$s". Your loot: %2$s.', 'underworld-empire' ), $crime['name'], implode( ' ' . __( 'and', 'underworld-empire' ) . ' ', $loot ) ) );
			$c->log( 'crimes', true, $money, $id );
		} else {
			$gain = wp_rand( 1, 2 );
			if ( wp_rand( 1, 100 ) <= (int) $this->setting( 'crimes_jail_chance' ) ) {
				$c->jail( (int) $crime['jail_time'] );
				$this->error( __( 'Failed! The police were faster and you end up in a cell.', 'underworld-empire' ) );
			} else {
				$this->error( __( 'Failed, but you managed to get away unseen.', 'underworld-empire' ) );
			}
			$c->log( 'crimes', false, 0, $id );
		}

		$max = max( 1, min( 100, (int) $this->setting( 'crimes_max_skill' ) ) );
		DB::query(
			'INSERT INTO {crime_skill} (character_id, crime_id, skill) VALUES (%d, %d, %d)
			 ON DUPLICATE KEY UPDATE skill = LEAST(%d, skill + %d)',
			$c->id(),
			$id,
			min( $max, $skill + $gain ),
			$max,
			$gain
		);
	}
}

return new Crimes();
