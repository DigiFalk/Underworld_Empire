<?php
/**
 * Module Name: Slot Machine
 * Description: Example module: a simple slot machine. Copy this folder to wp-content/mafia-pbbg-modules/ to activate it.
 * Version: 1.0.0
 * Author: DigiFalk
 * Default: yes
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Modules;

use DigiFalk\MafiaPBBGEngine\Character;
use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Module\Module;

defined( 'ABSPATH' ) || exit;

final class SlotMachine extends Module {

	const TIMER   = 'slots';
	const SYMBOLS = array( '🍒', '🍋', '🔔', '💎', '7' );

	public function title(): string {
		return __( 'Slot machine', 'mafia-pbbg-engine' );
	}

	/** Settings appear automatically under Mafia PBBG Engine > Modules > Configure. */
	public function settings_fields(): array {
		return array(
			'slots_bet'      => array(
				'label'   => __( 'Bet per spin', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 500,
			),
			'slots_cooldown' => array(
				'label'   => __( 'Cooldown (sec)', 'mafia-pbbg-engine' ),
				'type'    => 'int',
				'default' => 10,
			),
		);
	}

	/** Adds the page to the game menu, with a live timer. */
	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Slot machine', 'mafia-pbbg-engine' ),
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
			$this->error( __( 'The machine is still spinning.', 'mafia-pbbg-engine' ) );
			return;
		}
		if ( ! $c->spend( 'money', $bet ) ) {
			$this->error( __( 'You don\'t have enough cash.', 'mafia-pbbg-engine' ) );
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
			$this->success( sprintf( __( 'You win! You get %s.', 'mafia-pbbg-engine' ), Format::money( $win ) ) );
		} else {
			$this->error( __( 'Too bad, nothing won.', 'mafia-pbbg-engine' ) );
		}
		// Statistics and the dfmg_action hook.
		$c->log( 'slots', $win > 0, $win - $bet );
	}
}

return new SlotMachine();
