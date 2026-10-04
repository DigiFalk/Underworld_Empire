<?php
/**
 * Module Name: Police Chase
 * Description: Try to shake off the police through the city. Escape and you get a reward; get caught and you go to jail.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Ranks;

defined( 'ABSPATH' ) || exit;

final class PoliceChase extends Module {

	const TIMER = 'chase';

	public function title(): string {
		return __( 'Police chase', 'underworld-empire' );
	}

	public function settings_fields(): array {
		return array(
			'chase_cooldown'     => array(
				'label'   => __( 'Cooldown afterwards (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 300,
			),
			'chase_escape'       => array(
				'label'   => __( 'Chance of escaping per move (%)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_caught'       => array(
				'label'   => __( 'Chance of getting caught per move (%)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 25,
			),
			'chase_jail'         => array(
				'label'   => __( 'Jail time (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_min'   => array(
				'label'   => __( 'Min. reward per rank level', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 150,
			),
			'chase_reward_max'   => array(
				'label'   => __( 'Max. reward per rank level', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 850,
			),
			'chase_exp'          => array(
				'label'   => __( 'Experience for escaping', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 3,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Police chase', 'underworld-empire' ),
				'group' => 'crime',
				'order' => 30,
				'timer' => self::TIMER,
			),
		);
	}

	public static function routes(): array {
		return array(
			'alley'   => __( 'Left, into the alley', 'underworld-empire' ),
			'highway' => __( 'Straight ahead, onto the highway', 'underworld-empire' ),
			'harbour' => __( 'Right, towards the harbour', 'underworld-empire' ),
			'tunnel'  => __( 'Through the tunnel', 'underworld-empire' ),
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
			$this->error( __( 'The police are still looking for you. Wait a while before hitting the streets again.', 'underworld-empire' ) );
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
			$this->success( sprintf( __( 'You escaped! On the way you found %s in the glove box.', 'underworld-empire' ), Format::money( $reward ) ) );
		} elseif ( $roll <= $escape + $caught ) {
			if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'chase_cooldown' ) ) ) {
				return;
			}
			$c->jail( (int) $this->setting( 'chase_jail' ) );
			$c->log( 'police-chase', false );
			$this->error( __( 'Roadblock! You got caught and you\'re going to jail.', 'underworld-empire' ) );
		} else {
			$this->notice( __( 'The sirens are still on your tail... pick your next turn!', 'underworld-empire' ) );
		}
	}
}

return new PoliceChase();
