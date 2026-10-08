<?php
/**
 * Module Name: Police Chase
 * Description: Try to shake off the police through the city. Escape and you get a reward; get caught and you go to jail.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Modules;

use DigiFalk\MafiaPBBGEngine\Character;
use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Module\Module;
use DigiFalk\MafiaPBBGEngine\Ranks;

defined( 'ABSPATH' ) || exit;

final class PoliceChase extends Module {

	const TIMER = 'chase';

	public function title(): string {
		return __( 'Police chase', 'mafia-pbbg-engine' );
	}

	public function settings_fields(): array {
		return array(
			'chase_cooldown'     => array(
				'label'   => __( 'Cooldown afterwards (sec)', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 300,
			),
			'chase_escape'       => array(
				'label'   => __( 'Chance of escaping per move (%)', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_caught'       => array(
				'label'   => __( 'Chance of getting caught per move (%)', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_jail'         => array(
				'label'   => __( 'Jail time (sec)', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_min'   => array(
				'label'   => __( 'Min. reward per rank level', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_max'   => array(
				'label'   => __( 'Max. reward per rank level', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 850,
			),
			'chase_exp'          => array(
				'label'   => __( 'Experience for escaping', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 3,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Police chase', 'mafia-pbbg-engine' ),
				'group' => 'crime',
				'order' => 30,
				'timer' => self::TIMER,
			),
		);
	}

	public static function routes(): array {
		return array(
			'alley'   => __( 'Left, into the alley', 'mafia-pbbg-engine' ),
			'highway' => __( 'Straight ahead, onto the highway', 'mafia-pbbg-engine' ),
			'harbour' => __( 'Right, towards the harbour', 'mafia-pbbg-engine' ),
			'tunnel'  => __( 'Through the tunnel', 'mafia-pbbg-engine' ),
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
			$this->error( __( 'The police are still looking for you. Wait a while before hitting the streets again.', 'mafia-pbbg-engine' ) );
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
			$this->success( sprintf( __( 'You escaped! On the way you found %s in the glove box.', 'mafia-pbbg-engine' ), Format::money( $reward ) ) );
		} elseif ( $roll <= $escape + $caught ) {
			if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'chase_cooldown' ) ) ) {
				return;
			}
			$c->jail( (int) $this->setting( 'chase_jail' ) );
			$c->log( 'police-chase', false );
			$this->error( __( 'Roadblock! You got caught and you\'re going to jail.', 'mafia-pbbg-engine' ) );
		} else {
			$this->notice( __( 'The sirens are still on your tail... pick your next turn!', 'mafia-pbbg-engine' ) );
		}
	}
}

return new PoliceChase();
