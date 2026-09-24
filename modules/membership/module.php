<?php
/**
 * Module Name: Premium lidmaatschap
 * Description: Spelers kopen met premium punten een lidmaatschap dat wachttijden verkort. Punten ken je toe via Spelgegevens > Spelers (of koppel ze aan een webshop).
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

final class Membership extends Module {

	const TIMER = 'membership';

	public function title(): string {
		return __( 'Premium lidmaatschap', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function schema(): array {
		return array(
			'memberships' => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				days int(11) NOT NULL DEFAULT 7,
				cost int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)",
		);
	}

	public function seed(): void {
		foreach ( array( array( 'Week', 7, 50 ), array( 'Maand', 30, 175 ), array( 'Kwartaal', 90, 450 ) ) as $row ) {
			DB::insert(
				'memberships',
				array(
					'name' => $row[0],
					'days' => $row[1],
					'cost' => $row[2],
				)
			);
		}
	}

	public function settings_fields(): array {
		return array(
			'membership_reduction' => array(
				'label'   => __( 'Verkorting wachttijden (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25,
			),
			'membership_timers'    => array(
				'label'       => __( 'Timers met verkorting', 'wp-maffia-game' ),
				'type'        => 'text',
				'default'     => 'crime,theft,travel,chase',
				'description' => __( 'Kommagescheiden timer-namen.', 'wp-maffia-game' ),
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'memberships' => array(
				'label'   => __( 'Lidmaatschappen', 'wp-maffia-game' ),
				'table'   => 'memberships',
				'order'   => 'days ASC',
				'columns' => array(
					'name' => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'days' => array( 'label' => __( 'Dagen', 'wp-maffia-game' ), 'type' => 'int', 'default' => 7 ),
					'cost' => array( 'label' => __( 'Kosten (punten)', 'wp-maffia-game' ), 'type' => 'int' ),
				),
			),
		);
	}

	public function boot(): void {
		add_filter( 'dfmg_cooldown_seconds', array( $this, 'reduce' ), 10, 3 );
		add_filter(
			'dfmg_membership_benefits',
			function ( $benefits ) {
				/* translators: %d: percent */
				$benefits[] = sprintf( __( '%d%% kortere wachttijden voor misdaden, auto stelen, reizen en achtervolgingen.', 'wp-maffia-game' ), (int) $this->setting( 'membership_reduction' ) );
				return $benefits;
			}
		);
	}

	public static function is_member( Character $c ): bool {
		return $c->timer_active( self::TIMER );
	}

	/**
	 * @param int $seconds
	 */
	public function reduce( $seconds, string $timer, Character $c ): int {
		$timers = array_map( 'trim', explode( ',', (string) $this->setting( 'membership_timers' ) ) );
		if ( in_array( $timer, $timers, true ) && self::is_member( $c ) ) {
			$seconds = (int) ceil( $seconds * ( 100 - (int) $this->setting( 'membership_reduction' ) ) / 100 );
		}
		return (int) $seconds;
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Lidmaatschap', 'wp-maffia-game' ),
				'group' => 'premium',
				'order' => 10,
				'timer' => self::TIMER,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'membership',
			array(
				'c'        => $c,
				'packages' => DB::results( 'SELECT * FROM {memberships} ORDER BY days ASC' ),
				'benefits' => (array) apply_filters( 'dfmg_membership_benefits', array(), $c ),
			)
		);
	}

	public function action_buy( Character $c, array $input ): void {
		$package = DB::row( 'SELECT * FROM {memberships} WHERE id = %d', absint( $input['package'] ?? 0 ) );
		if ( ! $package ) {
			return;
		}
		if ( ! $c->spend( 'points', (int) $package['cost'] ) ) {
			/* translators: %s: points */
			$this->error( sprintf( __( 'Je hebt %s nodig.', 'wp-maffia-game' ), Format::points( (int) $package['cost'] ) ) );
			return;
		}
		$from = max( time(), $c->timer( self::TIMER ) );
		$c->set_timer( self::TIMER, $from + (int) $package['days'] * DAY_IN_SECONDS );
		$c->log( 'membership.buy', true, (int) $package['cost'], (int) $package['id'] );
		/* translators: %s: package */
		$this->success( sprintf( __( 'Bedankt! Je lidmaatschap (%s) is actief.', 'wp-maffia-game' ), $package['name'] ) );
	}
}

return new Membership();
