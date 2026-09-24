<?php
/**
 * Module Name: Inventaris
 * Description: Bekijk je spullen, rust wapens en bescherming uit, gebruik verbruiksartikelen of verkoop wat je niet nodig hebt.
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
		return __( 'Inventaris', 'wp-maffia-game' );
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
				'label'   => __( 'Verkoopwaarde items (% van prijs)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 40,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Inventaris', 'wp-maffia-game' ),
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
		$result = $slot ? Items::equip( $c, (int) $item['id'], $slot ) : new \WP_Error( 'slot', __( 'Dit item kun je niet uitrusten.', 'wp-maffia-game' ) );
		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
			return;
		}
		/* translators: %s: item */
		$this->success( sprintf( __( 'Je hebt %s uitgerust.', 'wp-maffia-game' ), $item['name'] ) );
	}

	public function action_unequip( Character $c, array $input ): void {
		if ( Items::unequip( $c, sanitize_key( $input['slot'] ?? '' ) ) ) {
			$this->success( __( 'Item terug in je inventaris gelegd.', 'wp-maffia-game' ) );
		}
	}

	public function action_use( Character $c, array $input ): void {
		$item   = Items::get( absint( $input['item'] ?? 0 ) );
		$result = $item ? Items::consume( $c, (int) $item['id'] ) : false;
		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		} elseif ( $result ) {
			/* translators: %s: item */
			$this->success( sprintf( __( 'Je hebt %s gebruikt.', 'wp-maffia-game' ), $item['name'] ) );
		}
	}

	public function action_sell( Character $c, array $input ): void {
		$item = Items::get( absint( $input['item'] ?? 0 ) );
		if ( ! $item || ! Items::take( $c, (int) $item['id'] ) ) {
			$this->error( __( 'Je hebt dit item niet.', 'wp-maffia-game' ) );
			return;
		}
		$value = (int) floor( (int) $item['price'] * (int) $this->setting( 'inventory_sell_percent' ) / 100 );
		$c->add( 'money', $value );
		/* translators: 1: item, 2: money */
		$this->success( sprintf( __( 'Je verkocht %1$s voor %2$s.', 'wp-maffia-game' ), $item['name'], Format::money( $value ) ) );
	}
}

return new Inventory();
