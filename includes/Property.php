<?php
/**
 * Ownable businesses per city (bullet factory, casino tables, ...).
 *
 * Modules register property types via the dfmg_property_types filter:
 *   'my-type' => [ 'label' => 'Name', 'price' => 1000000, 'setting_label' => 'Max bet',
 *                  'setting_min' => 100, 'setting_max' => 0, 'route' => 'my-module' ]
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Property {

	/** @var array */
	private $row;

	private function __construct( array $row ) {
		$this->row = $row;
	}

	public static function types(): array {
		return apply_filters( 'dfmg_property_types', array() );
	}

	public static function type( string $type ): ?array {
		return self::types()[ $type ] ?? null;
	}

	/**
	 * Property of $type in a city. Always returns an object, unowned when no row exists.
	 */
	public static function get( string $type, int $location_id ): self {
		$row = DB::row( 'SELECT * FROM {properties} WHERE type = %s AND location_id = %d', $type, $location_id );
		if ( ! $row ) {
			$row = array(
				'id'          => 0,
				'type'        => $type,
				'location_id' => $location_id,
				'owner_id'    => 0,
				'price'       => 0,
				'profit'      => 0,
			);
		}
		return new self( $row );
	}

	public static function owned_by( Character $c ): array {
		$out = array();
		foreach ( DB::results( 'SELECT * FROM {properties} WHERE owner_id = %d ORDER BY type, location_id', $c->id() ) as $row ) {
			$out[] = new self( $row );
		}
		return $out;
	}

	public function type_key(): string {
		return $this->row['type'];
	}

	public function label(): string {
		$type = self::type( $this->row['type'] );
		return $type['label'] ?? $this->row['type'];
	}

	public function location_id(): int {
		return (int) $this->row['location_id'];
	}

	public function owner_id(): int {
		return (int) $this->row['owner_id'];
	}

	public function owner(): ?Character {
		$owner = Character::find( $this->owner_id() );
		return ( $owner && $owner->is_alive() ) ? $owner : null;
	}

	public function is_owned(): bool {
		return null !== $this->owner();
	}

	public function is_owned_by( Character $c ): bool {
		return $this->owner_id() === $c->id() && $c->is_alive();
	}

	/**
	 * Owner configured value (price, max bet). 0 = not set.
	 */
	public function price(): int {
		return (int) $this->row['price'];
	}

	public function profit(): int {
		return (int) $this->row['profit'];
	}

	public function buy_price(): int {
		$type = self::type( $this->row['type'] );
		return (int) ( $type['price'] ?? 1000000 );
	}

	/**
	 * Give ownership to a character (0 = release).
	 */
	public function transfer( int $character_id ): void {
		DB::query(
			'INSERT INTO {properties} (type, location_id, owner_id, price, profit) VALUES (%s, %d, %d, 0, 0)
			 ON DUPLICATE KEY UPDATE owner_id = %d, price = 0, profit = 0',
			$this->row['type'],
			$this->row['location_id'],
			$character_id,
			$character_id
		);
		$this->row['owner_id'] = $character_id;
		$this->row['price']    = 0;
		$this->row['profit']   = 0;
		do_action( 'dfmg_property_transferred', $this, $character_id );
	}

	public function set_price( int $price ): void {
		DB::query( 'UPDATE {properties} SET price = %d WHERE type = %s AND location_id = %d', $price, $this->row['type'], $this->row['location_id'] );
		$this->row['price'] = $price;
	}

	public function add_profit( int $amount ): void {
		DB::query( 'UPDATE {properties} SET profit = profit + %d WHERE type = %s AND location_id = %d', $amount, $this->row['type'], $this->row['location_id'] );
		$this->row['profit'] += $amount;
	}

	public function reset_profit(): void {
		DB::query( 'UPDATE {properties} SET profit = 0 WHERE type = %s AND location_id = %d', $this->row['type'], $this->row['location_id'] );
		$this->row['profit'] = 0;
	}
}
