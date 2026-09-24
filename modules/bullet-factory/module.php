<?php
/**
 * Module Name: Bullet Factory
 * Description: Buy bullets at your city's factory. Every factory can be bought by a player, who then sets the price and earns a share.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Locations;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Property;

defined( 'ABSPATH' ) || exit;

final class BulletFactory extends Module {

	const TYPE  = 'bullet-factory';
	const TIMER = 'bullets';

	public function title(): string {
		return __( 'Bullet factory', 'underworld-empire' );
	}

	public function boot(): void {
		add_filter(
			'dfmg_property_types',
			function ( $types ) {
				$types[ self::TYPE ] = array(
					'label'         => __( 'Bullet factory', 'underworld-empire' ),
					'price'         => (int) $this->setting( 'bullets_property_price' ),
					'setting_label' => __( 'Price per bullet', 'underworld-empire' ),
					'setting_min'   => 1,
					'setting_max'   => (int) $this->setting( 'bullets_max_price' ),
					'route'         => $this->id(),
				);
				return $types;
			}
		);
		add_action( 'dfmg_hourly_tick', array( $this, 'restock' ) );
	}

	public function settings_fields(): array {
		return array(
			'bullets_max_per_buy'    => array(
				'label'   => __( 'Max. bullets per purchase', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 250,
			),
			'bullets_cooldown'       => array(
				'label'   => __( 'Cooldown between purchases (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 60,
			),
			'bullets_max_price'      => array(
				'label'   => __( 'Max. price an owner may charge', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 500,
			),
			'bullets_owner_share'    => array(
				'label'   => __( 'Owner share of revenue (%)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 50,
			),
			'bullets_property_price' => array(
				'label'   => __( 'Factory purchase price', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 1000000,
			),
			'bullets_restock_min'    => array(
				'label'   => __( 'Production per hour (min.)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 2000,
			),
			'bullets_restock_max'    => array(
				'label'   => __( 'Production per hour (max.)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 3000,
			),
			'bullets_max_stock'      => array(
				'label'   => __( 'Maximum stock per city', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 50000,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'bullet factory', 'underworld-empire' ),
				'group' => 'city',
				'order' => 40,
				'timer' => self::TIMER,
			),
		);
	}

	/**
	 * Produce bullets for every hour since the last run (max 12 hours at once).
	 */
	public function restock(): void {
		$hour  = (int) ( floor( time() / HOUR_IN_SECONDS ) * HOUR_IN_SECONDS );
		$last  = (int) get_option( 'dfmg_bullets_restocked', 0 );
		$hours = $last ? min( 12, (int) floor( ( $hour - $last ) / HOUR_IN_SECONDS ) ) : 1;
		if ( $hours < 1 ) {
			return;
		}
		update_option( 'dfmg_bullets_restocked', $hour, false );
		$min = (int) $this->setting( 'bullets_restock_min' );
		$max = max( $min, (int) $this->setting( 'bullets_restock_max' ) );
		foreach ( Locations::all() as $loc ) {
			$made = 0;
			for ( $i = 0; $i < $hours; $i++ ) {
				$made += wp_rand( $min, $max );
			}
			DB::query(
				'UPDATE {locations} SET bullet_stock = LEAST(%d, bullet_stock + %d) WHERE id = %d',
				(int) $this->setting( 'bullets_max_stock' ),
				$made,
				(int) $loc['id']
			);
		}
		Locations::flush();
	}

	private function price( Property $property, array $location ): int {
		$owner_price = $property->is_owned() ? $property->price() : 0;
		$price       = $owner_price ?: (int) $location['bullet_price'];
		return max( 1, min( $price, (int) $this->setting( 'bullets_max_price' ) ) );
	}

	public function render( Character $c, array $query ): string {
		$this->restock();
		$location = Locations::get( (int) $c->location_id );
		if ( ! $location ) {
			return '';
		}
		$property = Property::get( self::TYPE, (int) $location['id'] );
		return $this->view(
			'factory',
			array(
				'c'        => $c,
				'location' => $location,
				'property' => $property,
				'price'    => $this->price( $property, $location ),
				'max'      => (int) $this->setting( 'bullets_max_per_buy' ),
			)
		);
	}

	public function action_buy( Character $c, array $input ): void {
		$qty      = Format::parse_amount( $input['qty'] ?? 0 );
		$max      = (int) $this->setting( 'bullets_max_per_buy' );
		$location = Locations::get( (int) $c->location_id );
		if ( ! $location || $qty < 1 ) {
			$this->error( __( 'How many bullets do you want to buy?', 'underworld-empire' ) );
			return;
		}
		if ( $qty > $max ) {
			/* translators: %s: number */
			$this->error( sprintf( __( 'You can buy at most %s bullets at a time.', 'underworld-empire' ), Format::number( $max ) ) );
			return;
		}
		if ( $c->timer_active( self::TIMER ) ) {
			$this->error( __( 'The factory will serve you again shortly.', 'underworld-empire' ) );
			return;
		}
		$property = Property::get( self::TYPE, (int) $location['id'] );
		$cost     = $qty * $this->price( $property, $location );

		// Reserve stock first so two buyers can not take the same bullets.
		$reserved = DB::query( 'UPDATE {locations} SET bullet_stock = bullet_stock - %d WHERE id = %d AND bullet_stock >= %d', $qty, (int) $location['id'], $qty );
		if ( ! $reserved ) {
			$this->error( __( 'The factory doesn\'t have that many bullets in stock.', 'underworld-empire' ) );
			return;
		}
		if ( ! $c->spend( 'money', $cost ) ) {
			DB::query( 'UPDATE {locations} SET bullet_stock = bullet_stock + %d WHERE id = %d', $qty, (int) $location['id'] );
			/* translators: %s: money */
			$this->error( sprintf( __( 'That costs %s. You don\'t have that in cash.', 'underworld-empire' ), Format::money( $cost ) ) );
			return;
		}
		Locations::flush();
		$c->add( 'bullets', $qty );
		$c->claim_cooldown( self::TIMER, (int) $this->setting( 'bullets_cooldown' ) );

		$owner = $property->owner();
		if ( $owner ) {
			$share = (int) floor( $cost * (int) $this->setting( 'bullets_owner_share' ) / 100 );
			$owner->add( 'bank', $share );
			$property->add_profit( $share );
		}
		$c->log( 'bullets.buy', true, $qty );
		/* translators: 1: bullets, 2: money */
		$this->success( sprintf( __( 'You bought %1$s bullets for %2$s.', 'underworld-empire' ), Format::number( $qty ), Format::money( $cost ) ) );
	}
}

return new BulletFactory();
