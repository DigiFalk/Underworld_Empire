<?php
/**
 * Module Name: Inventory
 * Description: View your belongings, equip weapons and armor, use consumables or sell what you don't need.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Items;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Inventory extends Module {

	public function title(): string {
		return __( 'Inventory', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function settings_fields(): array {
		return array(
			'inventory_sell_percent' => array(
				'label'   => __( 'Item resale value (% of price)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 40,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Inventory', 'wp-maffia-game' ),
				'group' => 'money',
				'order' => 20,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'inventory',
			array(
				'c'        => $c,
				'slots'    => Items::slots(),
				'equipped' => Items::equipped( $c ),
				'items'    => Items::inventory( $c ),
				'sell_pct' => (int) $this->setting( 'inventory_sell_percent' ),
			)
		);
	}

	public function action_equip( Character $c, array $input ): void {
		$item = Items::get( absint( $input['item'] ?? 0 ) );
		if ( ! $item ) {
			return;
		}
		$slot = '';
		foreach ( Items::slots() as $key => $def ) {
			if ( in_array( $item['type'], (array) $def['types'], true ) ) {
				$slot = $key;
				break;
			}
		}
		$result = $slot ? Items::equip( $c, (int) $item['id'], $slot ) : new \WP_Error( 'slot', __( 'You can\'t equip this item.', 'wp-maffia-game' ) );
		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
			return;
		}
		/* translators: %s: item */
		$this->success( sprintf( __( 'You equipped %s.', 'wp-maffia-game' ), $item['name'] ) );
	}

	public function action_unequip( Character $c, array $input ): void {
		if ( Items::unequip( $c, sanitize_key( $input['slot'] ?? '' ) ) ) {
			$this->success( __( 'Item returned to your inventory.', 'wp-maffia-game' ) );
		}
	}

	public function action_use( Character $c, array $input ): void {
		$item   = Items::get( absint( $input['item'] ?? 0 ) );
		$result = $item ? Items::consume( $c, (int) $item['id'] ) : false;
		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		} elseif ( $result ) {
			/* translators: %s: item */
			$this->success( sprintf( __( 'You used %s.', 'wp-maffia-game' ), $item['name'] ) );
		}
	}

	public function action_sell( Character $c, array $input ): void {
		$item = Items::get( absint( $input['item'] ?? 0 ) );
		if ( ! $item || ! Items::take( $c, (int) $item['id'] ) ) {
			$this->error( __( 'You don\'t have this item.', 'wp-maffia-game' ) );
			return;
		}
		$value = (int) floor( (int) $item['price'] * (int) $this->setting( 'inventory_sell_percent' ) / 100 );
		$c->add( 'money', $value );
		/* translators: 1: item, 2: money */
		$this->success( sprintf( __( 'You sold %1$s for %2$s.', 'wp-maffia-game' ), $item['name'], Format::money( $value ) ) );
	}
}

return new Inventory();
