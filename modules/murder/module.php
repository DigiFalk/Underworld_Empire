<?php
/**
 * Module Name: Murder
 * Description: Shoot other players. You need a valid detective report and have to be in the same city. Weapons and armor affect the damage.
 * Version: 1.0.0
 * Author: DigiFalk
 * Requires: detectives
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Murder extends Module {

	const TIMER = 'murder';

	public function title(): string {
		return __( 'Murder', 'wp-maffia-game' );
	}

	public function settings_fields(): array {
		return array(
			'murder_protection_hours' => array(
				'label'       => __( 'New player protection (hours)', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 24,
				'description' => __( 'New characters can\'t be attacked for this long and can\'t attack either.', 'wp-maffia-game' ),
			),
			'murder_cooldown'         => array(
				'label'   => __( 'Cooldown between attacks (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 120,
			),
			'murder_exp'              => array(
				'label'   => __( 'Experience for a successful murder', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 50,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Murder', 'wp-maffia-game' ),
				'group' => 'murder',
				'order' => 20,
				'timer' => self::TIMER,
			),
		);
	}

	public function protected_until( Character $c ): int {
		return (int) $c->created_at + (int) $this->setting( 'murder_protection_hours' ) * HOUR_IN_SECONDS;
	}

	public function render( Character $c, array $query ): string {
		$reports = array();
		foreach ( Detectives::valid_reports( $c ) as $report ) {
			$target = Character::find( (int) $report['target_id'] );
			if ( $target && $target->is_alive() ) {
				$report['target'] = $target;
				$reports[]        = $report;
			}
		}
		return $this->view(
			'murder',
			array(
				'c'         => $c,
				'reports'   => $reports,
				'protected' => $this->protected_until( $c ),
			)
		);
	}

	public function action_shoot( Character $c, array $input ): void {
		$report_id = absint( $input['report'] ?? 0 );
		$bullets   = Format::parse_amount( $input['bullets'] ?? 0 );
		$report    = null;
		foreach ( Detectives::valid_reports( $c ) as $row ) {
			if ( (int) $row['id'] === $report_id ) {
				$report = $row;
			}
		}
		if ( ! $report ) {
			$this->error( __( 'You don\'t have a valid detective report for this target.', 'wp-maffia-game' ) );
			return;
		}
		$target = Character::find( (int) $report['target_id'] );
		if ( ! $target || ! $target->is_alive() ) {
			$this->error( __( 'Your target is no longer alive.', 'wp-maffia-game' ) );
			return;
		}
		if ( $this->protected_until( $c ) > time() ) {
			$this->error( __( 'You are still under new player protection and can\'t attack anyone yet.', 'wp-maffia-game' ) );
			return;
		}
		if ( $this->protected_until( $target ) > time() ) {
			$this->error( __( 'This player is new and still protected.', 'wp-maffia-game' ) );
			return;
		}
		if ( (int) $target->location_id !== (int) $c->location_id ) {
			/* translators: %s: player */
			$this->error( sprintf( __( '%s is not in your city. Travel to the city from the report first.', 'wp-maffia-game' ), $target->name ) );
			return;
		}
		if ( $bullets < 1 ) {
			$this->error( __( 'How many bullets do you want to shoot?', 'wp-maffia-game' ) );
			return;
		}
		$allowed = apply_filters( 'dfmg_can_attack', true, $c, $target );
		if ( is_wp_error( $allowed ) ) {
			$this->error( $allowed->get_error_message() );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'murder_cooldown' ) ) ) {
			$this->error( __( 'You have to wait a little before your next attack.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'bullets', $bullets ) ) {
			$c->clear_timer( self::TIMER );
			$this->error( __( 'You don\'t have that many bullets.', 'wp-maffia-game' ) );
			return;
		}

		Detectives::use_report( $report_id );
		$damage = (int) floor( $c->attack_power() / $target->defense_power() * $bullets );
		$damage = (int) apply_filters( 'dfmg_murder_damage', $damage, $c, $target, $bullets );
		$target->add( 'damage', $damage );

		if ( (int) $target->damage >= $target->max_health() ) {
			$target->kill( $c );
			$c->add( 'exp', (int) $this->setting( 'murder_exp' ) );
			$c->log( 'murder', true, $bullets, $target->id() );
			/* translators: %s: player */
			$this->success( sprintf( __( 'You murdered %s. The city will know.', 'wp-maffia-game' ), $target->name ) );
			return;
		}

		/* translators: %s: player */
		$target->notify( sprintf( __( '%s shot at you! You were wounded.', 'wp-maffia-game' ), $c->link() ) );
		$c->log( 'murder', false, $bullets, $target->id() );
		/* translators: 1: player, 2: health percent */
		$this->error( sprintf( __( '%1$s survived your attack with %2$s%% health.', 'wp-maffia-game' ), $target->name, $target->health_percent() ) );
	}
}

return new Murder();
