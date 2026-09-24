<?php
/**
 * Module Name: Black Market
 * Description: Buy weapons, armor and other goods. Items are managed in the admin under Game data > Items.
 * Version: 1.0.0
 * Author: DigiFalk
 * Requires: inventory
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame\Modules;

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\DB;
use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Items;
use DigiFalk\MafiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class BlackMarket extends Module {

	public function title(): string {
		return __( 'Black market', 'wp-mafia-game' );
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Black market', 'wp-mafia-game' ),
				'group' => 'city',
				'order' => 50,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$items   = (array) apply_filters( 'dfmg_module_data', DB::results( 'SELECT * FROM {items} WHERE buyable = 1 ORDER BY type, price' ), 'black-market', $c );
		$grouped = array();
		foreach ( $items as $item ) {
			$grouped[ $item['type'] ][] = $item;
		}
		return $this->view(
			'market',
			array(
				'c'       => $c,
				'grouped' => $grouped,
				'types'   => Items::types(),
			)
		);
	}

	public function action_buy( Character $c, array $input ): void {
		$item = Items::get( absint( $input['item'] ?? 0 ) );
		$qty  = max( 1, min( 100, absint( $input['qty'] ?? 1 ) ) );
		if ( ! $item || ! (int) $item['buyable'] ) {
			$this->error( __( 'This item isn\'t sold here.', 'wp-mafia-game' ) );
			return;
		}
		$check = apply_filters( 'dfmg_can_buy_item', true, $c, $item, $qty );
		if ( is_wp_error( $check ) ) {
			$this->error( $check->get_error_message() );
			return;
		}
		$cost = (int) $item['price'] * $qty;
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'That costs %s. You don\'t have that in cash.', 'wp-mafia-game' ), Format::money( $cost ) ) );
			return;
		}
		Items::give( $c, (int) $item['id'], $qty );
		$c->log( 'black-market.buy', true, $cost, (int) $item['id'] );
		/* translators: 1: quantity, 2: item, 3: money */
		$this->success( sprintf( __( 'You bought %1$dx %2$s for %3$s. You\'ll find it in your inventory.', 'wp-mafia-game' ), $qty, $item['name'], Format::money( $cost ) ) );
	}
}

return new BlackMarket();
