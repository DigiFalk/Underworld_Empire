<?php
/**
 * Module Name: Misdaden
 * Description: Kleine en grote misdaden plegen voor geld, kogels en ervaring. Hoe vaker je een misdaad pleegt, hoe beter je erin wordt.
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
use DigiFalk\MaffiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class Crimes extends Module {

	const TIMER = 'crime';

	public function title(): string {
		return __( 'Misdaden', 'wp-maffia-game' );
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
			array( 'Zakkenrollen op de markt', 'Een snelle greep in een volle tas.', 1, 30, 15, 60, 0, 2, 1, 40, 30 ),
			array( 'Een nachtwinkel beroven', 'Kassa leeg, capuchon op.', 1, 60, 40, 150, 0, 5, 2, 30, 45 ),
			array( 'Toeristen oplichten', 'Nep-horloges voor echte prijzen.', 2, 90, 100, 350, 0, 5, 3, 25, 60 ),
			array( 'Een juwelier overvallen', 'Snel, luid en riskant.', 3, 150, 400, 1200, 5, 15, 5, 15, 90 ),
			array( 'Een geldtransport overvallen', 'Goed plannen of lang zitten.', 5, 300, 2000, 6000, 10, 40, 10, 10, 180 ),
			array( 'De casinokluis kraken', 'Het grote werk, alleen voor professionals.', 7, 600, 10000, 30000, 25, 100, 20, 5, 300 ),
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
				'label'       => __( 'Kans op gevangenis bij mislukken (%)', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 33,
			),
			'crimes_max_skill'   => array(
				'label'   => __( 'Maximale slagingskans (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 95,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'crimes' => array(
				'label'   => __( 'Misdaden', 'wp-maffia-game' ),
				'table'   => 'crimes',
				'order'   => 'min_rank ASC, id ASC',
				'columns' => array(
					'name'         => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'description'  => array( 'label' => __( 'Omschrijving', 'wp-maffia-game' ), 'type' => 'textarea' ),
					'min_rank'     => array( 'label' => __( 'Vanaf rang (niveau)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 1 ),
					'cooldown'     => array( 'label' => __( 'Wachttijd (sec)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 60 ),
					'min_money'    => array( 'label' => __( 'Min. geld', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_money'    => array( 'label' => __( 'Max. geld', 'wp-maffia-game' ), 'type' => 'int' ),
					'min_bullets'  => array( 'label' => __( 'Min. kogels', 'wp-maffia-game' ), 'type' => 'int', 'list' => false ),
					'max_bullets'  => array( 'label' => __( 'Max. kogels', 'wp-maffia-game' ), 'type' => 'int', 'list' => false ),
					'exp'          => array( 'label' => __( 'Ervaring', 'wp-maffia-game' ), 'type' => 'int', 'default' => 1 ),
					'start_chance' => array( 'label' => __( 'Startkans (%)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 20 ),
					'jail_time'    => array( 'label' => __( 'Celstraf (sec)', 'wp-maffia-game' ), 'type' => 'int', 'default' => 60 ),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Misdaden', 'wp-maffia-game' ),
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
			$this->error( __( 'Deze misdaad bestaat niet of is nog te zwaar voor jou.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) $crime['cooldown'] ) ) {
			$this->error( __( 'Je moet nog even wachten voor je volgende misdaad.', 'wp-maffia-game' ) );
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
				$loot[] = sprintf( __( '%s kogels', 'wp-maffia-game' ), Format::number( $bullets ) );
			}
			/* translators: 1: crime, 2: loot */
			$this->success( sprintf( __( 'Gelukt: "%1$s". Je buit: %2$s.', 'wp-maffia-game' ), $crime['name'], implode( ' ' . __( 'en', 'wp-maffia-game' ) . ' ', $loot ) ) );
			$c->log( 'crimes', true, $money, $id );
		} else {
			$gain = wp_rand( 1, 2 );
			if ( wp_rand( 1, 100 ) <= (int) $this->setting( 'crimes_jail_chance' ) ) {
				$c->jail( (int) $crime['jail_time'] );
				$this->error( __( 'Mislukt! De politie was er sneller bij en je belandt in de cel.', 'wp-maffia-game' ) );
			} else {
				$this->error( __( 'Mislukt, maar je wist ongezien weg te komen.', 'wp-maffia-game' ) );
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
