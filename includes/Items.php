<?php
/**
 * Items, inventory and equipment.
 *
 * Item types, equipment slots and effects are all filterable so modules can add their own:
 *  - dfmg_item_types   : type => [ label, usage (equip|use) ]
 *  - dfmg_equip_slots  : slot => [ label, types[] ]
 *  - dfmg_item_effects : effect => [ label, usage, filter?, apply(callable) ]
 *
 * Effects are stored per item as lines "effect=value".
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame;

defined( 'ABSPATH' ) || exit;

final class Items {

	/** @var array character id => slot => item row */
	private static $equipped = array();

	public static function boot(): void {
		foreach ( array( 'dfmg_attack_power', 'dfmg_defense_power', 'dfmg_max_health' ) as $filter ) {
			add_filter(
				$filter,
				static function ( $value, $character ) use ( $filter ) {
					return Items::apply_equipment( $filter, $value, $character );
				},
				10,
				2
			);
		}
	}

	public static function types(): array {
		return apply_filters(
			'dfmg_item_types',
			array(
				'weapon'     => array(
					'label' => __( 'Weapon', 'wp-mafia-game' ),
					'usage' => 'equip',
				),
				'armor'      => array(
					'label' => __( 'Armor', 'wp-mafia-game' ),
					'usage' => 'equip',
				),
				'consumable' => array(
					'label' => __( 'Consumable', 'wp-mafia-game' ),
					'usage' => 'use',
				),
			)
		);
	}

	public static function type_options(): array {
		return wp_list_pluck( self::types(), 'label' );
	}

	public static function slots(): array {
		return apply_filters(
			'dfmg_equip_slots',
			array(
				'weapon' => array(
					'label' => __( 'Weapon', 'wp-mafia-game' ),
					'types' => array( 'weapon' ),
				),
				'armor'  => array(
					'label' => __( 'Armor', 'wp-mafia-game' ),
					'types' => array( 'armor' ),
				),
			)
		);
	}

	public static function effects(): array {
		return apply_filters(
			'dfmg_item_effects',
			array(
				'attack_pct'   => array(
					'label'  => __( 'Attack power +%', 'wp-mafia-game' ),
					'usage'  => 'equip',
					'filter' => 'dfmg_attack_power',
					'apply'  => static function ( $current, $value ) {
						return $current * ( 1 + (float) $value / 100 );
					},
				),
				'defense_pct'  => array(
					'label'  => __( 'Defense +%', 'wp-mafia-game' ),
					'usage'  => 'equip',
					'filter' => 'dfmg_defense_power',
					'apply'  => static function ( $current, $value ) {
						return $current * ( 1 + (float) $value / 100 );
					},
				),
				'max_health'   => array(
					'label'  => __( 'Maximum health +', 'wp-mafia-game' ),
					'usage'  => 'equip',
					'filter' => 'dfmg_max_health',
					'apply'  => static function ( $current, $value ) {
						return $current + (int) $value;
					},
				),
				'heal_pct'     => array(
					'label' => __( 'Heals % health', 'wp-mafia-game' ),
					'usage' => 'use',
					'apply' => static function ( Character $c, $value ) {
						$heal = (int) round( $c->max_health() * (float) $value / 100 );
						$c->set( 'damage', max( 0, $c->damage - $heal ) );
					},
				),
				'reset_timer'  => array(
					'label' => __( 'Reset timer (e.g. jail)', 'wp-mafia-game' ),
					'usage' => 'use',
					'apply' => static function ( Character $c, $value ) {
						$c->clear_timer( sanitize_key( $value ) );
					},
				),
				'give_money'   => array(
					'label' => __( 'Gives money', 'wp-mafia-game' ),
					'usage' => 'use',
					'apply' => static function ( Character $c, $value ) {
						$c->add( 'money', (int) $value );
					},
				),
				'give_bullets' => array(
					'label' => __( 'Gives bullets', 'wp-mafia-game' ),
					'usage' => 'use',
					'apply' => static function ( Character $c, $value ) {
						$c->add( 'bullets', (int) $value );
					},
				),
				'give_exp'     => array(
					'label' => __( 'Gives experience', 'wp-mafia-game' ),
					'usage' => 'use',
					'apply' => static function ( Character $c, $value ) {
						$c->add( 'exp', (int) $value );
					},
				),
			)
		);
	}

	/**
	 * Parse "effect=value" lines.
	 */
	public static function parse_effects( string $raw ): array {
		$out = array();
		foreach ( preg_split( '/[\r\n;]+/', $raw ) as $line ) {
			$parts = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( 2 === count( $parts ) && '' !== $parts[0] ) {
				$out[ sanitize_key( $parts[0] ) ] = $parts[1];
			}
		}
		return $out;
	}

	public static function describe_effects( array $item ): array {
		$effects = self::effects();
		$lines   = array();
		foreach ( self::parse_effects( (string) $item['effects'] ) as $key => $value ) {
			$label   = $effects[ $key ]['label'] ?? $key;
			$lines[] = $label . ': ' . $value;
		}
		return $lines;
	}

	public static function get( int $id ): ?array {
		return $id ? DB::row( 'SELECT * FROM {items} WHERE id = %d', $id ) : null;
	}

	public static function usage( array $item ): string {
		$types = self::types();
		return $types[ $item['type'] ]['usage'] ?? 'none';
	}

	/* Inventory ---------------------------------------------------------- */

	public static function inventory( Character $c ): array {
		return DB::results(
			'SELECT i.*, inv.qty FROM {inventory} inv INNER JOIN {items} i ON i.id = inv.item_id
			 WHERE inv.character_id = %d AND inv.qty > 0 ORDER BY i.type, i.name',
			$c->id()
		);
	}

	public static function quantity( Character $c, int $item_id ): int {
		return (int) DB::value( 'SELECT qty FROM {inventory} WHERE character_id = %d AND item_id = %d', $c->id(), $item_id );
	}

	public static function give( Character $c, int $item_id, int $qty = 1 ): void {
		DB::query(
			'INSERT INTO {inventory} (character_id, item_id, qty) VALUES (%d, %d, %d) ON DUPLICATE KEY UPDATE qty = qty + %d',
			$c->id(),
			$item_id,
			$qty,
			$qty
		);
	}

	public static function take( Character $c, int $item_id, int $qty = 1 ): bool {
		return (bool) DB::query(
			'UPDATE {inventory} SET qty = qty - %d WHERE character_id = %d AND item_id = %d AND qty >= %d',
			$qty,
			$c->id(),
			$item_id,
			$qty
		);
	}

	/* Equipment ---------------------------------------------------------- */

	public static function equipped( Character $c ): array {
		$id = $c->id();
		if ( ! isset( self::$equipped[ $id ] ) ) {
			self::$equipped[ $id ] = array();
			$rows                  = DB::results(
				'SELECT e.slot, i.* FROM {equipment} e INNER JOIN {items} i ON i.id = e.item_id WHERE e.character_id = %d',
				$id
			);
			foreach ( $rows as $row ) {
				self::$equipped[ $id ][ $row['slot'] ] = $row;
			}
		}
		return self::$equipped[ $id ];
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function equip( Character $c, int $item_id, string $slot ) {
		$slots = self::slots();
		$item  = self::get( $item_id );
		if ( ! $item || ! isset( $slots[ $slot ] ) ) {
			return new \WP_Error( 'item', __( 'This item or slot doesn\'t exist.', 'wp-mafia-game' ) );
		}
		if ( ! in_array( $item['type'], (array) $slots[ $slot ]['types'], true ) ) {
			return new \WP_Error( 'item', __( 'This item doesn\'t fit in this slot.', 'wp-mafia-game' ) );
		}
		$check = apply_filters( 'dfmg_can_equip', true, $c, $item, $slot );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		if ( ! self::take( $c, $item_id ) ) {
			return new \WP_Error( 'item', __( 'You don\'t have this item.', 'wp-mafia-game' ) );
		}
		self::unequip( $c, $slot );
		DB::query(
			'INSERT INTO {equipment} (character_id, slot, item_id) VALUES (%d, %s, %d) ON DUPLICATE KEY UPDATE item_id = %d',
			$c->id(),
			$slot,
			$item_id,
			$item_id
		);
		unset( self::$equipped[ $c->id() ] );
		return true;
	}

	public static function unequip( Character $c, string $slot ): bool {
		$current = self::equipped( $c )[ $slot ] ?? null;
		if ( ! $current ) {
			return false;
		}
		DB::delete(
			'equipment',
			array(
				'character_id' => $c->id(),
				'slot'         => $slot,
			)
		);
		self::give( $c, (int) $current['id'] );
		unset( self::$equipped[ $c->id() ] );
		return true;
	}

	/**
	 * Use a consumable.
	 *
	 * @return true|\WP_Error
	 */
	public static function consume( Character $c, int $item_id ) {
		$item = self::get( $item_id );
		if ( ! $item || 'use' !== self::usage( $item ) ) {
			return new \WP_Error( 'item', __( 'This item can\'t be used.', 'wp-mafia-game' ) );
		}
		if ( ! self::take( $c, $item_id ) ) {
			return new \WP_Error( 'item', __( 'You don\'t have this item.', 'wp-mafia-game' ) );
		}
		$effects = self::effects();
		foreach ( self::parse_effects( (string) $item['effects'] ) as $key => $value ) {
			if ( isset( $effects[ $key ] ) && 'use' === $effects[ $key ]['usage'] && is_callable( $effects[ $key ]['apply'] ) ) {
				call_user_func( $effects[ $key ]['apply'], $c, $value );
			}
		}
		do_action( 'dfmg_item_used', $c, $item );
		return true;
	}

	/**
	 * Apply equip effects that hook into a given filter.
	 *
	 * @param float|int $value
	 * @return float|int
	 */
	public static function apply_equipment( string $filter, $value, Character $c ) {
		$effects = self::effects();
		foreach ( self::equipped( $c ) as $item ) {
			foreach ( self::parse_effects( (string) $item['effects'] ) as $key => $amount ) {
				$effect = $effects[ $key ] ?? null;
				if ( $effect && 'equip' === $effect['usage'] && ( $effect['filter'] ?? '' ) === $filter ) {
					$value = call_user_func( $effect['apply'], $value, $amount );
				}
			}
		}
		return $value;
	}
}
