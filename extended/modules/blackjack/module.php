<?php
/**
 * Module Name: Blackjack
 * Description: Play blackjack at your city's casino. The table can be bought by a player, who sets the maximum bet and has to pay out winnings.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Property;

defined( 'ABSPATH' ) || exit;

final class Blackjack extends Module {

	const TYPE = 'blackjack';

	public function title(): string {
		return __( 'Blackjack', 'underworld-empire' );
	}

	public function boot(): void {
		add_filter(
			'dfmg_property_types',
			function ( $types ) {
				$types[ self::TYPE ] = array(
					'label'         => __( 'Blackjack table', 'underworld-empire' ),
					'price'         => (int) $this->setting( 'blackjack_property_price' ),
					'setting_label' => __( 'Maximum bet', 'underworld-empire' ),
					'setting_min'   => (int) $this->setting( 'blackjack_min_bet' ),
					'setting_max'   => 0,
					'route'         => $this->id(),
				);
				return $types;
			}
		);
	}

	public function settings_fields(): array {
		return array(
			'blackjack_min_bet'        => array(
				'label'   => __( 'Minimum bet', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 100,
			),
			'blackjack_max_bet'        => array(
				'label'   => __( 'Default maximum bet', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 50000,
			),
			'blackjack_property_price' => array(
				'label'   => __( 'Blackjack table purchase price', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 1000000,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Blackjack', 'underworld-empire' ),
				'group' => 'casino',
				'order' => 10,
			),
		);
	}

	/* Game state ---------------------------------------------------------- */

	private function key( Character $c ): string {
		return 'dfmg_bj_' . $c->id();
	}

	private function game( Character $c ): ?array {
		$game = get_transient( $this->key( $c ) );
		return is_array( $game ) ? $game : null;
	}

	private function save( Character $c, ?array $game ): void {
		if ( null === $game ) {
			delete_transient( $this->key( $c ) );
		} else {
			set_transient( $this->key( $c ), $game, DAY_IN_SECONDS );
		}
	}

	private static function deck(): array {
		$deck = array();
		foreach ( array( '♠', '♥', '♦', '♣' ) as $suit ) {
			foreach ( array( 'A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K' ) as $rank ) {
				$deck[] = array(
					'r' => $rank,
					's' => $suit,
				);
			}
		}
		// Fisher-Yates with a cryptographically secure RNG.
		for ( $i = count( $deck ) - 1; $i > 0; $i-- ) {
			$j          = wp_rand( 0, $i );
			$tmp        = $deck[ $i ];
			$deck[ $i ] = $deck[ $j ];
			$deck[ $j ] = $tmp;
		}
		return $deck;
	}

	public static function score( array $hand ): int {
		$total = 0;
		$aces  = 0;
		foreach ( $hand as $card ) {
			if ( 'A' === $card['r'] ) {
				$total += 11;
				++$aces;
			} elseif ( in_array( $card['r'], array( 'J', 'Q', 'K' ), true ) ) {
				$total += 10;
			} else {
				$total += (int) $card['r'];
			}
		}
		while ( $total > 21 && $aces-- > 0 ) {
			$total -= 10;
		}
		return $total;
	}

	private static function is_blackjack( array $hand ): bool {
		return 2 === count( $hand ) && 21 === self::score( $hand );
	}

	private function max_bet( Property $table ): int {
		return ( $table->is_owned() && $table->price() ) ? $table->price() : (int) $this->setting( 'blackjack_max_bet' );
	}

	/* Rendering ----------------------------------------------------------- */

	public function render( Character $c, array $query ): string {
		$table = Property::get( self::TYPE, (int) $c->location_id );
		return $this->view(
			'blackjack',
			array(
				'c'     => $c,
				'game'  => $this->game( $c ),
				'table' => $table,
				'min'   => (int) $this->setting( 'blackjack_min_bet' ),
				'max'   => $this->max_bet( $table ),
			)
		);
	}

	/* Actions -------------------------------------------------------------- */

	public function action_bet( Character $c, array $input ): void {
		if ( $this->game( $c ) ) {
			$this->error( __( 'Finish your current game first.', 'underworld-empire' ) );
			return;
		}
		$bet   = Format::parse_amount( $input['bet'] ?? 0 );
		$table = Property::get( self::TYPE, (int) $c->location_id );
		$min   = (int) $this->setting( 'blackjack_min_bet' );
		$max   = $this->max_bet( $table );
		if ( $bet < $min || $bet > $max ) {
			/* translators: 1: min, 2: max */
			$this->error( sprintf( __( 'Your bet must be between %1$s and %2$s.', 'underworld-empire' ), Format::money( $min ), Format::money( $max ) ) );
			return;
		}
		if ( ! $c->spend( 'money', $bet ) ) {
			$this->error( __( 'You don\'t have that much cash.', 'underworld-empire' ) );
			return;
		}
		$owner = $table->owner();
		if ( $owner && $owner->id() !== $c->id() ) {
			$owner->add( 'money', $bet );
			$table->add_profit( $bet );
		}

		$deck = self::deck();
		$game = array(
			'bet'      => $bet,
			'location' => (int) $c->location_id,
			'player'   => array( array_pop( $deck ), array_pop( $deck ) ),
			'dealer'   => array( array_pop( $deck ), array_pop( $deck ) ),
			'deck'     => $deck,
		);
		$this->save( $c, $game );

		if ( self::is_blackjack( $game['player'] ) ) {
			$this->finish( $c, $game );
		}
	}

	public function action_hit( Character $c, array $input ): void {
		$game = $this->game( $c );
		if ( ! $game ) {
			return;
		}
		$game['player'][] = array_pop( $game['deck'] );
		$this->save( $c, $game );
		if ( self::score( $game['player'] ) >= 21 ) {
			$this->finish( $c, $game );
		}
	}

	public function action_stand( Character $c, array $input ): void {
		$game = $this->game( $c );
		if ( $game ) {
			$this->finish( $c, $game );
		}
	}

	/**
	 * Dealer plays, result is paid out and the game is cleared.
	 */
	private function finish( Character $c, array $game ): void {
		$player = self::score( $game['player'] );
		if ( $player <= 21 && ! self::is_blackjack( $game['player'] ) ) {
			while ( self::score( $game['dealer'] ) < 17 ) {
				$game['dealer'][] = array_pop( $game['deck'] );
			}
		}
		$dealer = self::score( $game['dealer'] );
		$bet    = (int) $game['bet'];
		$payout = 0;

		if ( $player > 21 ) {
			/* translators: %s: money */
			$result = sprintf( __( 'Bust! You lose your bet of %s.', 'underworld-empire' ), Format::money( $bet ) );
		} elseif ( self::is_blackjack( $game['player'] ) && ! self::is_blackjack( $game['dealer'] ) ) {
			$payout = (int) floor( $bet * 2.5 );
			/* translators: %s: money */
			$result = sprintf( __( 'Blackjack! You win %s.', 'underworld-empire' ), Format::money( $payout - $bet ) );
		} elseif ( $dealer > 21 || $player > $dealer ) {
			$payout = $bet * 2;
			/* translators: %s: money */
			$result = sprintf( __( 'You won! You win %s.', 'underworld-empire' ), Format::money( $bet ) );
		} elseif ( $player === $dealer ) {
			$payout = $bet;
			$result = __( 'Push. You get your bet back.', 'underworld-empire' );
		} else {
			/* translators: %s: money */
			$result = sprintf( __( 'The house wins. You lose %s.', 'underworld-empire' ), Format::money( $bet ) );
		}

		if ( $payout ) {
			$this->pay( $c, (int) $game['location'], $payout );
		}
		$c->log( 'blackjack', $payout > $bet, $payout - $bet );

		$game['result']   = $result;
		$game['finished'] = true;
		$this->save( $c, null );
		set_transient( $this->key( $c ) . '_last', $game, HOUR_IN_SECONDS );

		if ( $payout > $bet ) {
			$this->success( $result );
		} elseif ( $payout === $bet ) {
			$this->notice( $result );
		} else {
			$this->error( $result );
		}
	}

	/**
	 * Pay a player; the owner pays, and loses the table when he can not.
	 */
	private function pay( Character $c, int $location_id, int $amount ): void {
		$table = Property::get( self::TYPE, $location_id );
		$owner = $table->owner();
		if ( $owner && $owner->id() !== $c->id() ) {
			if ( $owner->spend( 'money', $amount ) ) {
				$table->add_profit( -$amount );
			} else {
				$table->transfer( $c->id() );
				/* translators: %s: player */
				$owner->notify( sprintf( __( 'You couldn\'t pay out a blackjack win. %s took over your table!', 'underworld-empire' ), $c->link() ) );
				$this->notice( __( 'The owner couldn\'t pay. The blackjack table is now yours!', 'underworld-empire' ) );
			}
		}
		$c->add( 'money', $amount );
	}

	public function last_game( Character $c ): ?array {
		$game = get_transient( $this->key( $c ) . '_last' );
		return is_array( $game ) ? $game : null;
	}
}

return new Blackjack();
