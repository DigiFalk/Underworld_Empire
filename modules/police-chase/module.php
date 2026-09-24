<?php
/**
 * Module Name: Politieachtervolging
 * Description: Probeer de politie af te schudden door de stad. Ontsnap je, dan krijg je een beloning; word je gepakt, dan ga je de cel in.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;
use DigiFalk\MaffiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class PoliceChase extends Module {

	const TIMER = 'chase';

	public function title(): string {
		return __( 'Politieachtervolging', 'wp-maffia-game' );
	}

	public function settings_fields(): array {
		return array(
			'chase_cooldown'     => array(
				'label'   => __( 'Wachttijd na afloop (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 300,
			),
			'chase_escape'       => array(
				'label'   => __( 'Kans op ontsnappen per zet (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_caught'       => array(
				'label'   => __( 'Kans om gepakt te worden per zet (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_jail'         => array(
				'label'   => __( 'Celstraf (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_min'   => array(
				'label'   => __( 'Min. beloning per rangniveau', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_max'   => array(
				'label'   => __( 'Max. beloning per rangniveau', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 850,
			),
			'chase_exp'          => array(
				'label'   => __( 'Ervaring bij ontsnappen', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 3,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Achtervolging', 'wp-maffia-game' ),
				'group' => 'crime',
				'order' => 30,
				'timer' => self::TIMER,
			),
		);
	}

	public static function routes(): array {
		return array(
			'alley'   => __( 'Linksaf, de steeg in', 'wp-maffia-game' ),
			'highway' => __( 'Rechtdoor, de snelweg op', 'wp-maffia-game' ),
			'harbour' => __( 'Rechtsaf, richting de haven', 'wp-maffia-game' ),
			'tunnel'  => __( 'Door de tunnel', 'wp-maffia-game' ),
		);
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'chase',
			array(
				'c'      => $c,
				'routes' => self::routes(),
			)
		);
	}

	public function action_move( Character $c, array $input ): void {
		$route = sanitize_key( $input['route'] ?? '' );
		if ( ! isset( self::routes()[ $route ] ) ) {
			return;
		}
		if ( $c->timer_active( self::TIMER ) ) {
			$this->error( __( 'De politie zoekt je nog. Wacht even voor je opnieuw de straat op gaat.', 'wp-maffia-game' ) );
			return;
		}
		$roll   = wp_rand( 1, 100 );
		$escape = (int) $this->setting( 'chase_escape' );
		$caught = (int) $this->setting( 'chase_caught' );

		if ( $roll <= $escape ) {
			if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'chase_cooldown' ) ) ) {
				return;
			}
			$level  = Ranks::level( (int) $c->rank_id );
			$reward = wp_rand( (int) $this->setting( 'chase_reward_min' ), max( (int) $this->setting( 'chase_reward_min' ), (int) $this->setting( 'chase_reward_max' ) ) ) * $level;
			$c->add( 'money', $reward );
			$c->add( 'exp', (int) $this->setting( 'chase_exp' ) );
			$c->log( 'police-chase', true, $reward );
			/* translators: %s: money */
			$this->success( sprintf( __( 'Je bent ontsnapt! Onderweg vond je %s in het dashboardkastje.', 'wp-maffia-game' ), Format::money( $reward ) ) );
		} elseif ( $roll <= $escape + $caught ) {
			if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'chase_cooldown' ) ) ) {
				return;
			}
			$c->jail( (int) $this->setting( 'chase_jail' ) );
			$c->log( 'police-chase', false );
			$this->error( __( 'Wegversperring! Je bent gepakt en gaat de cel in.', 'wp-maffia-game' ) );
		} else {
			$this->notice( __( 'De sirenes zitten je nog op de hielen... kies je volgende afslag!', 'wp-maffia-game' ) );
		}
	}
}

return new PoliceChase();
