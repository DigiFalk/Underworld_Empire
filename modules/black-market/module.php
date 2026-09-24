<?php
/**
 * Module Name: Zwarte markt
 * Description: Koop wapens, bescherming en andere spullen. Items beheer je in de admin onder Spelgegevens > Items.
 * Version: 1.0.0
 * Author: DigiFalk
 * Requires: inventory
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Items;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class BlackMarket extends Module {

	public function title(): string {
		return __( 'Zwarte markt', 'wp-maffia-game' );
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Zwarte markt', 'wp-maffia-game' ),
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
			$this->error( __( 'Dit item wordt hier niet verkocht.', 'wp-maffia-game' ) );
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
			$this->error( sprintf( __( 'Dat kost %s. Dat heb je niet contant.', 'wp-maffia-game' ), Format::money( $cost ) ) );
			return;
		}
		Items::give( $c, (int) $item['id'], $qty );
		$c->log( 'black-market.buy', true, $cost, (int) $item['id'] );
		/* translators: 1: quantity, 2: item, 3: money */
		$this->success( sprintf( __( 'Je kocht %1$dx %2$s voor %3$s. Je vindt het in je inventaris.', 'wp-maffia-game' ), $qty, $item['name'], Format::money( $cost ) ) );
	}
}

return new BlackMarket();
