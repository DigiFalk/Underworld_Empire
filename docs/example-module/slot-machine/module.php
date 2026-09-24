<?php
/**
 * Module Name: Slot Machine
 * Description: Example module: a simple slot machine. Copy this folder to wp-content/underworld-modules/ to activate it.
 * Version: 1.0.0
 * Author: DigiFalk
 * Default: yes
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class SlotMachine extends Module {

	const TIMER   = 'slots';
	const SYMBOLS = array( '🍒', '🍋', '🔔', '💎', '7' );

	public function title(): string {
		return __( 'Slot machine', 'underworld-empire' );
	}

	/** Settings appear automatically under Underworld Empire > Settings. */
	public function settings_fields(): array {
		return array(
			'slots_bet'      => array(
				'label'   => __( 'Bet per spin', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 500,
			),
			'slots_cooldown' => array(
				'label'   => __( 'Cooldown (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 10,
			),
		);
	}

	/** Adds the page to the game menu, with a live timer. */
	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Slot machine', 'underworld-empire' ),
				'group' => 'casino',
				'order' => 20,
				'timer' => self::TIMER,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'slots',
			array(
				'c'    => $c,
				'bet'  => (int) $this->setting( 'slots_bet' ),
				'last' => get_transient( 'dfmg_slots_' . $c->id() ),
			)
		);
	}

	/** Called by the form built with $this->button( 'spin', ... ). */
	public function action_spin( Character $c, array $input ): void {
		$bet = (int) $this->setting( 'slots_bet' );
		if ( ! $c->claim_cooldown( self::TIMER, (int) $this->setting( 'slots_cooldown' ) ) ) {
			$this->error( __( 'The machine is still spinning.', 'underworld-empire' ) );
			return;
		}
		if ( ! $c->spend( 'money', $bet ) ) {
			$this->error( __( 'You don\'t have enough cash.', 'underworld-empire' ) );
			return;
		}
		$reels = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$reels[] = self::SYMBOLS[ wp_rand( 0, count( self::SYMBOLS ) - 1 ) ];
		}
		set_transient( 'dfmg_slots_' . $c->id(), $reels, HOUR_IN_SECONDS );

		$unique = count( array_unique( $reels ) );
		$win    = 1 === $unique ? $bet * 20 : ( 2 === $unique ? $bet * 2 : 0 );
		if ( $win ) {
			$c->add( 'money', $win );
			/* translators: %s: money */
			$this->success( sprintf( __( 'You win! You get %s.', 'underworld-empire' ), Format::money( $win ) ) );
		} else {
			$this->error( __( 'Too bad, nothing won.', 'underworld-empire' ) );
		}
		// Statistics and the dfmg_action hook.
		$c->log( 'slots', $win > 0, $win - $bet );
	}
}

return new SlotMachine();
