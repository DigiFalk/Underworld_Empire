<?php
/**
 * Module Name: Gevangenis
 * Description: Wie gepakt wordt belandt in de cel. Breek medegevangenen uit, betaal borg of wacht je straf uit. Mislukte uitbraken in de cel leiden tot de isoleercel.
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

final class Jail extends Module {

	public function title(): string {
		return __( 'Gevangenis', 'wp-maffia-game' );
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
				'label'   => __( 'Straf bij mislukte uitbraak (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 90,
			),
			'jail_bust_cooldown'   => array(
				'label'   => __( 'Wachttijd tussen uitbraakpogingen (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 20,
			),
			'jail_bust_exp'        => array(
				'label'   => __( 'Ervaring per geslaagde uitbraak', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 2,
			),
			'jail_bail_per_second' => array(
				'label'       => __( 'Borg per resterende seconde', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 40,
				'description' => __( '0 = borg uitgeschakeld.', 'wp-maffia-game' ),
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
				'label' => __( 'Gevangenis', 'wp-maffia-game' ),
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
			$this->error( __( 'Deze persoon zit hier niet vast.', 'wp-maffia-game' ) );
			return;
		}
		$chance = $this->chance( $c, $target );
		if ( ! $chance ) {
			$this->error( __( 'Uit de isoleercel breekt niemand.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->claim_cooldown( 'jail_bust', (int) $this->setting( 'jail_bust_cooldown' ) ) ) {
			$this->error( __( 'Rustig aan, de bewakers letten op. Probeer het zo weer.', 'wp-maffia-game' ) );
			return;
		}
		$self = $target->id() === $c->id();

		if ( wp_rand( 1, 100 ) <= $chance ) {
			$target->clear_timer( 'jail' );
			$target->clear_timer( 'supermax' );
			if ( ! $self ) {
				$c->add( 'exp', (int) $this->setting( 'jail_bust_exp' ) );
				/* translators: %s: player */
				$target->notify( sprintf( __( '%s heeft je uit de gevangenis bevrijd!', 'wp-maffia-game' ), $c->link() ) );
				/* translators: %s: player */
				$this->success( sprintf( __( 'Je hebt %s uit de gevangenis bevrijd.', 'wp-maffia-game' ), $target->name ) );
			} else {
				$this->success( __( 'Je bent ontsnapt!', 'wp-maffia-game' ) );
			}
			$c->log( 'jail.bust', true, $self ? 1 : 0, $target->id() );
			return;
		}

		$penalty = (int) $this->setting( 'jail_fail_time' );
		if ( $c->is_jailed() ) {
			$until = $c->timer( 'jail' ) + $penalty;
			$c->set_timer( 'jail', $until );
			$c->set_timer( 'supermax', $until );
			$this->error( __( 'Mislukt! Je wordt overgeplaatst naar de isoleercel.', 'wp-maffia-game' ) );
		} else {
			$c->jail( $penalty );
			/* translators: %s: player */
			$this->error( sprintf( __( 'Mislukt! De bewakers zagen je bij %s en nu zit je zelf vast.', 'wp-maffia-game' ), $target->name ) );
		}
		$c->log( 'jail.bust', false, 0, $target->id() );
	}

	public function action_bail( Character $c, array $input ): void {
		if ( ! $c->is_jailed() ) {
			$this->error( __( 'Je zit niet vast.', 'wp-maffia-game' ) );
			return;
		}
		$bail = $this->bail( $c );
		if ( ! $bail ) {
			$this->error( __( 'Voor jou is geen borg mogelijk.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'money', $bail ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'De borg is %s. Dat heb je niet contant.', 'wp-maffia-game' ), Format::money( $bail ) ) );
			return;
		}
		$c->clear_timer( 'jail' );
		$c->log( 'jail.bail', true, $bail );
		/* translators: %s: money */
		$this->success( sprintf( __( 'Je hebt %s borg betaald en bent vrij.', 'wp-maffia-game' ), Format::money( $bail ) ) );
	}
}

return new Jail();
