<?php
/**
 * Module Name: Jail
 * Description: Whoever gets caught ends up in a cell. Break out fellow inmates, pay bail or sit out your time. Failed breakouts from inside lead to solitary confinement.
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

final class Jail extends Module {

	public function title(): string {
		return __( 'Jail', 'underworld-empire' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function boot(): void {
		add_filter(
			'dfmg_route',
			static function ( $route, Character $c, Module $module ) {
				if ( $c->is_jailed() && ! $module->allowed_in_jail() ) {
					return 'jail';
				}
				return $route;
			},
			10,
			3
		);
	}

	public function settings_fields(): array {
		return array(
			'jail_fail_time'       => array(
				'label'   => __( 'Penalty for a failed breakout (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 90,
			),
			'jail_bust_cooldown'   => array(
				'label'   => __( 'Cooldown between breakout attempts (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 20,
			),
			'jail_bust_exp'        => array(
				'label'   => __( 'Experience per successful breakout', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 2,
			),
			'jail_bail_per_second' => array(
				'label'       => __( 'Bail per remaining second', 'underworld-empire' ),
				'type'        => 'int',
				'default'     => 40,
				'description' => __( '0 = bail disabled.', 'underworld-empire' ),
			),
		);
	}

	public function menu( Character $c ): array {
		$count = (int) DB::value(
			'SELECT COUNT(*) FROM {timers} t INNER JOIN {characters} ch ON ch.id = t.character_id
			 WHERE t.name = %s AND t.expires_at > %d AND ch.location_id = %d AND ch.status = 1',
			'jail',
			time(),
			(int) $c->location_id
		);
		return array(
			array(
				'label' => __( 'Jail', 'underworld-empire' ),
				'group' => 'city',
				'order' => 20,
				'timer' => 'jail',
				'badge' => $count ?: '',
			),
		);
	}

	/**
	 * Break out chance for $target when attempted by $by.
	 */
	public function chance( Character $by, Character $target ): int {
		if ( $target->is_in_supermax() || $by->is_in_supermax() ) {
			return 0;
		}
		$chance = max( 10, 95 - Ranks::level( (int) $target->rank_id ) * 7 );
		if ( $by->is_jailed() ) {
			$chance = (int) floor( $chance / 2 );
		}
		return (int) apply_filters( 'dfmg_jail_bust_chance', $chance, $by, $target );
	}

	public function bail( Character $c ): int {
		if ( $c->is_in_supermax() ) {
			return 0;
		}
		return $c->timer_left( 'jail' ) * (int) $this->setting( 'jail_bail_per_second' );
	}

	public function render( Character $c, array $query ): string {
		$ids     = DB::column(
			'SELECT ch.id FROM {timers} t INNER JOIN {characters} ch ON ch.id = t.character_id
			 WHERE t.name = %s AND t.expires_at > %d AND ch.location_id = %d AND ch.status = 1
			 ORDER BY t.expires_at ASC',
			'jail',
			time(),
			(int) $c->location_id
		);
		$inmates = array();
		foreach ( $ids as $id ) {
			$inmate = Character::find( (int) $id );
			if ( $inmate ) {
				$inmates[] = array(
					'character' => $inmate,
					'chance'    => $this->chance( $c, $inmate ),
					'supermax'  => $inmate->is_in_supermax(),
				);
			}
		}
		return $this->view(
			'jail',
			array(
				'c'       => $c,
				'inmates' => $inmates,
				'bail'    => $c->is_jailed() ? $this->bail( $c ) : 0,
			)
		);
	}

	public function action_bust( Character $c, array $input ): void {
		$target = Character::find( absint( $input['target'] ?? 0 ) );
		if ( ! $target || ! $target->is_alive() || ! $target->is_jailed() || (int) $target->location_id !== (int) $c->location_id ) {
			$this->error( __( 'This person isn\'t locked up here.', 'underworld-empire' ) );
			return;
		}
		$chance = $this->chance( $c, $target );
		if ( ! $chance ) {
			$this->error( __( 'Nobody breaks out of solitary confinement.', 'underworld-empire' ) );
			return;
		}
		if ( ! $c->claim_cooldown( 'jail_bust', (int) $this->setting( 'jail_bust_cooldown' ) ) ) {
			$this->error( __( 'Easy, the guards are watching. Try again in a moment.', 'underworld-empire' ) );
			return;
		}
		$self = $target->id() === $c->id();

		if ( wp_rand( 1, 100 ) <= $chance ) {
			$target->clear_timer( 'jail' );
			$target->clear_timer( 'supermax' );
			if ( ! $self ) {
				$c->add( 'exp', (int) $this->setting( 'jail_bust_exp' ) );
				/* translators: %s: player */
				$target->notify( sprintf( __( '%s broke you out of jail!', 'underworld-empire' ), $c->link() ) );
				/* translators: %s: player */
				$this->success( sprintf( __( 'You broke %s out of jail.', 'underworld-empire' ), $target->name ) );
			} else {
				$this->success( __( 'You escaped!', 'underworld-empire' ) );
			}
			$c->log( 'jail.bust', true, $self ? 1 : 0, $target->id() );
			return;
		}

		$penalty = (int) $this->setting( 'jail_fail_time' );
		if ( $c->is_jailed() ) {
			$until = $c->timer( 'jail' ) + $penalty;
			$c->set_timer( 'jail', $until );
			$c->set_timer( 'supermax', $until );
			$this->error( __( 'Failed! You\'re being moved to solitary confinement.', 'underworld-empire' ) );
		} else {
			$c->jail( $penalty );
			/* translators: %s: player */
			$this->error( sprintf( __( 'Failed! The guards saw you at %s and now you\'re locked up yourself.', 'underworld-empire' ), $target->name ) );
		}
		$c->log( 'jail.bust', false, 0, $target->id() );
	}

	public function action_bail( Character $c, array $input ): void {
		if ( ! $c->is_jailed() ) {
			$this->error( __( 'You are not locked up.', 'underworld-empire' ) );
			return;
		}
		$bail = $this->bail( $c );
		if ( ! $bail ) {
			$this->error( __( 'Bail isn\'t possible for you.', 'underworld-empire' ) );
			return;
		}
		if ( ! $c->spend( 'money', $bail ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Bail is %s. You don\'t have that in cash.', 'underworld-empire' ), Format::money( $bail ) ) );
			return;
		}
		$c->clear_timer( 'jail' );
		$c->log( 'jail.bail', true, $bail );
		/* translators: %s: money */
		$this->success( sprintf( __( 'You paid %s bail and you are free.', 'underworld-empire' ), Format::money( $bail ) ) );
	}
}

return new Jail();
