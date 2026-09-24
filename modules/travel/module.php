<?php
/**
 * Module Name: Travel
 * Description: Fly to other cities. Every city has its own inmates, properties and families.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Locations;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Travel extends Module {

	const TIMER = 'travel';

	public function title(): string {
		return __( 'Airport', 'wp-maffia-game' );
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Travel', 'wp-maffia-game' ),
				'group' => 'city',
				'order' => 10,
				'timer' => self::TIMER,
			),
		);
	}

	private function destinations( Character $c ): array {
		$out = array();
		foreach ( Locations::all() as $loc ) {
			if ( (int) $loc['id'] === (int) $c->location_id ) {
				continue;
			}
			$loc['travel_time'] = $c->cooldown_seconds( self::TIMER, (int) $loc['travel_time'] );
			$out[]              = $loc;
		}
		return (array) apply_filters( 'dfmg_module_data', $out, 'travel', $c );
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'travel',
			array(
				'c'            => $c,
				'destinations' => $this->destinations( $c ),
			)
		);
	}

	public function action_fly( Character $c, array $input ): void {
		$id   = absint( $input['to'] ?? 0 );
		$dest = null;
		foreach ( $this->destinations( $c ) as $loc ) {
			if ( (int) $loc['id'] === $id ) {
				$dest = $loc;
			}
		}
		if ( ! $dest ) {
			$this->error( __( 'There are no flights to that destination.', 'wp-maffia-game' ) );
			return;
		}
		if ( $c->timer_active( self::TIMER ) ) {
			$this->error( __( 'You just landed. Wait until you\'re allowed to fly again.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'money', (int) $dest['travel_cost'] ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'A ticket costs %s. You don\'t have that in cash.', 'wp-maffia-game' ), Format::money( $dest['travel_cost'] ) ) );
			return;
		}
		if ( ! $c->claim_cooldown( self::TIMER, (int) Locations::get( $id )['travel_time'] ) ) {
			$c->add( 'money', (int) $dest['travel_cost'] );
			return;
		}
		$c->set( 'location_id', $id );
		$c->log( 'travel', true, (int) $dest['travel_cost'], $id );
		do_action( 'dfmg_travelled', $c, $id );
		/* translators: %s: city */
		$this->success( sprintf( __( 'Welcome to %s!', 'wp-maffia-game' ), $dest['name'] ) );
	}
}

return new Travel();
