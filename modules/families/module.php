<?php
/**
 * Module Name: Families
 * Description: Found a family or join one. With roles (boss, underboss), per-member permissions, a shared vault for money and bullets, invitations, a log and an expandable member limit.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Locations;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Families extends Module {

	public function title(): string {
		return __( 'Families', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function schema(): array {
		return array(
			'families'        => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(40) NOT NULL DEFAULT '',
				location_id int(11) NOT NULL DEFAULT 0,
				boss_id bigint(20) unsigned NOT NULL DEFAULT 0,
				underboss_id bigint(20) unsigned NOT NULL DEFAULT 0,
				level int(11) NOT NULL DEFAULT 1,
				money bigint(20) NOT NULL DEFAULT 0,
				bullets bigint(20) NOT NULL DEFAULT 0,
				description text NULL,
				internal text NULL,
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY name (name)",
			'family_members'  => "
				character_id bigint(20) unsigned NOT NULL,
				family_id bigint(20) unsigned NOT NULL,
				permissions text NULL,
				joined_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (character_id),
				KEY family_id (family_id)",
			'family_invites'  => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				family_id bigint(20) unsigned NOT NULL,
				character_id bigint(20) unsigned NOT NULL,
				invited_by bigint(20) unsigned NOT NULL,
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY character_id (character_id)',
			'family_log'      => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				family_id bigint(20) unsigned NOT NULL,
				character_id bigint(20) unsigned NOT NULL DEFAULT 0,
				message varchar(255) NOT NULL DEFAULT '',
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY family_id (family_id)",
		);
	}

	public function round_tables(): array {
		return array( 'families', 'family_members', 'family_invites', 'family_log' );
	}

	public function settings_fields(): array {
		return array(
			'family_cost'          => array(
				'label'   => __( 'Cost to found a family', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 1000000,
			),
			'family_one_per_city'  => array(
				'label'   => __( 'At most one family per city', 'wp-maffia-game' ),
				'type'    => 'checkbox',
				'default' => 1,
			),
			'family_base_capacity' => array(
				'label'   => __( 'Base number of members', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 5,
			),
			'family_upgrade_cost'  => array(
				'label'       => __( 'Expansion cost per level', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 250000,
				'description' => __( 'Cost = level × this amount, paid from the family vault. Each level adds 1 extra slot.', 'wp-maffia-game' ),
			),
			'family_cash_tax'      => array(
				'label'   => __( 'Laundering fee for money deposits (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 15,
			),
			'family_bullet_tax'    => array(
				'label'   => __( 'Loss when depositing bullets (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 25,
			),
		);
	}

	public function admin_tables(): array {
		return array(
			'families' => array(
				'label'      => __( 'Families', 'wp-maffia-game' ),
				'table'      => 'families',
				'can_create' => false,
				'search'     => 'name',
				'columns'    => array(
					'name'        => array( 'label' => __( 'Name', 'wp-maffia-game' ), 'required' => true ),
					'location_id' => array( 'label' => __( 'City', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Locations::class, 'options' ) ),
					'level'       => array( 'label' => __( 'Level', 'wp-maffia-game' ), 'type' => 'int' ),
					'money'       => array( 'label' => __( 'Vault', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullets'     => array( 'label' => __( 'Bullets', 'wp-maffia-game' ), 'type' => 'int' ),
					'description' => array( 'label' => __( 'Profile', 'wp-maffia-game' ), 'type' => 'textarea' ),
				),
			),
		);
	}

	/* ------------------------------------------------------------------ */
	/* Hooks                                                                */
	/* ------------------------------------------------------------------ */

	public function boot(): void {
		add_action( 'dfmg_character_killed', array( $this, 'on_killed' ), 10, 2 );
		add_filter(
			'dfmg_profile_fields',
			function ( $fields, Character $c ) {
				$family = self::family_of( $c );
				if ( $family ) {
					$fields[ __( 'Family', 'wp-maffia-game' ) ] = '<a href="' . esc_url( $this->url( array( 'view' => 'family', 'id' => $family['id'] ) ) ) . '">' . esc_html( $family['name'] ) . '</a>';
				}
				return $fields;
			},
			10,
			2
		);
		add_filter(
			'dfmg_can_attack',
			static function ( $allowed, Character $attacker, Character $target ) {
				$mine = self::family_of( $attacker );
				if ( true === $allowed && $mine && ( self::family_of( $target )['id'] ?? 0 ) === $mine['id'] ) {
					return new \WP_Error( 'family', __( 'You don\'t murder family.', 'wp-maffia-game' ) );
				}
				return $allowed;
			},
			10,
			3
		);
	}

	public function on_killed( Character $victim, ?Character $killer ): void {
		$family = self::family_of( $victim );
		if ( ! $family ) {
			return;
		}
		DB::delete( 'family_members', array( 'character_id' => $victim->id() ) );
		/* translators: %s: player */
		self::log( (int) $family['id'], 0, sprintf( __( '%s was murdered.', 'wp-maffia-game' ), $victim->name ) );

		if ( (int) $family['underboss_id'] === $victim->id() ) {
			DB::update( 'families', array( 'underboss_id' => 0 ), array( 'id' => $family['id'] ) );
		}
		if ( (int) $family['boss_id'] === $victim->id() ) {
			$heir = Character::find( (int) $family['underboss_id'] );
			if ( $heir && $heir->is_alive() && (int) $family['underboss_id'] !== $victim->id() ) {
				DB::update(
					'families',
					array(
						'boss_id'      => $heir->id(),
						'underboss_id' => 0,
					),
					array( 'id' => $family['id'] )
				);
				$heir->notify( __( 'Your boss was murdered. You now lead the family!', 'wp-maffia-game' ) );
				/* translators: %s: player */
				self::log( (int) $family['id'], $heir->id(), sprintf( __( '%s is the new boss.', 'wp-maffia-game' ), $heir->name ) );
			} else {
				self::dissolve( (int) $family['id'], __( 'Your family has been disbanded: the boss was murdered without a successor.', 'wp-maffia-game' ) );
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Data helpers                                                         */
	/* ------------------------------------------------------------------ */

	public static function family_of( Character $c ): ?array {
		static $cache = array();
		if ( ! array_key_exists( $c->id(), $cache ) ) {
			$cache[ $c->id() ] = DB::row(
				'SELECT f.*, m.permissions FROM {family_members} m INNER JOIN {families} f ON f.id = m.family_id WHERE m.character_id = %d',
				$c->id()
			);
		}
		return $cache[ $c->id() ];
	}

	public static function get( int $id ): ?array {
		return DB::row( 'SELECT * FROM {families} WHERE id = %d', $id );
	}

	public static function members( int $family_id ): array {
		$rows = DB::results( 'SELECT * FROM {family_members} WHERE family_id = %d ORDER BY joined_at ASC', $family_id );
		foreach ( $rows as &$row ) {
			$row['character'] = Character::find( (int) $row['character_id'] );
		}
		unset( $row );
		return array_filter(
			$rows,
			static function ( $r ) {
				return $r['character'] && $r['character']->is_alive();
			}
		);
	}

	public function capacity( array $family ): int {
		return (int) $this->setting( 'family_base_capacity' ) + (int) $family['level'] - 1;
	}

	public static function log( int $family_id, int $character_id, string $message ): void {
		DB::insert(
			'family_log',
			array(
				'family_id'    => $family_id,
				'character_id' => $character_id,
				'message'      => mb_substr( $message, 0, 255 ),
				'created_at'   => time(),
			)
		);
	}

	public static function permissions(): array {
		return apply_filters(
			'dfmg_family_permissions',
			array(
				'invite'           => __( 'Invite members', 'wp-maffia-game' ),
				'kick'             => __( 'Kick members', 'wp-maffia-game' ),
				'upgrade'          => __( 'Expand family', 'wp-maffia-game' ),
				'withdraw_money'   => __( 'Withdraw money from the vault', 'wp-maffia-game' ),
				'withdraw_bullets' => __( 'Withdraw bullets from the vault', 'wp-maffia-game' ),
				'edit_profile'     => __( 'Edit profile', 'wp-maffia-game' ),
				'edit_internal'    => __( 'Edit internal notice', 'wp-maffia-game' ),
				'logs'             => __( 'View log', 'wp-maffia-game' ),
			)
		);
	}

	/**
	 * Whether a member has a permission. Boss and underboss can do everything.
	 */
	public static function can( Character $c, string $permission ): bool {
		$family = self::family_of( $c );
		if ( ! $family ) {
			return false;
		}
		if ( in_array( $c->id(), array( (int) $family['boss_id'], (int) $family['underboss_id'] ), true ) ) {
			return true;
		}
		return in_array( $permission, array_filter( explode( ',', (string) $family['permissions'] ) ), true );
	}

	public static function role( array $family, int $character_id ): string {
		if ( (int) $family['boss_id'] === $character_id ) {
			return __( 'Boss', 'wp-maffia-game' );
		}
		if ( (int) $family['underboss_id'] === $character_id ) {
			return __( 'Underboss', 'wp-maffia-game' );
		}
		return __( 'Member', 'wp-maffia-game' );
	}

	private static function dissolve( int $family_id, string $message ): void {
		foreach ( self::members( $family_id ) as $member ) {
			$member['character']->notify( $message );
		}
		DB::delete( 'family_members', array( 'family_id' => $family_id ) );
		DB::delete( 'family_invites', array( 'family_id' => $family_id ) );
		DB::delete( 'family_log', array( 'family_id' => $family_id ) );
		DB::delete( 'families', array( 'id' => $family_id ) );
		do_action( 'dfmg_family_dissolved', $family_id );
	}

	/* ------------------------------------------------------------------ */
	/* Frontend                                                             */
	/* ------------------------------------------------------------------ */

	public function menu( Character $c ): array {
		$items   = array(
			array(
				'label' => __( 'All families', 'wp-maffia-game' ),
				'group' => 'family',
				'order' => 10,
			),
		);
		$family  = self::family_of( $c );
		$invites = (int) DB::value( 'SELECT COUNT(*) FROM {family_invites} WHERE character_id = %d', $c->id() );
		if ( $family ) {
			$items[] = array(
				'label' => __( 'My family', 'wp-maffia-game' ),
				'group' => 'family',
				'order' => 20,
				'args'  => array( 'view' => 'home' ),
			);
		} elseif ( $invites ) {
			$items[0]['badge'] = $invites;
		}
		return $items;
	}

	public function render( Character $c, array $query ): string {
		$view   = $query['view'] ?? '';
		$family = self::family_of( $c );

		if ( 'family' === $view ) {
			$profile = self::get( absint( $query['id'] ?? 0 ) );
			if ( $profile ) {
				return $this->view(
					'profile',
					array(
						'c'       => $c,
						'family'  => $profile,
						'members' => self::members( (int) $profile['id'] ),
						'cap'     => $this->capacity( $profile ),
					)
				);
			}
		}

		if ( 'home' === $view && $family ) {
			return $this->view(
				'home',
				array(
					'c'           => $c,
					'family'      => $family,
					'members'     => self::members( (int) $family['id'] ),
					'cap'         => $this->capacity( $family ),
					'invites'     => DB::results( 'SELECT * FROM {family_invites} WHERE family_id = %d', $family['id'] ),
					'log'         => self::can( $c, 'logs' ) ? DB::results( 'SELECT * FROM {family_log} WHERE family_id = %d ORDER BY id DESC LIMIT 30', $family['id'] ) : array(),
					'permissions' => self::permissions(),
					'is_boss'     => (int) $family['boss_id'] === $c->id(),
					'upgrade'     => (int) $family['level'] * (int) $this->setting( 'family_upgrade_cost' ),
					'cash_tax'    => (int) $this->setting( 'family_cash_tax' ),
					'bullet_tax'  => (int) $this->setting( 'family_bullet_tax' ),
				)
			);
		}

		$families = DB::results( 'SELECT * FROM {families} ORDER BY level DESC, name ASC' );
		foreach ( $families as &$row ) {
			$row['members'] = (int) DB::value( 'SELECT COUNT(*) FROM {family_members} WHERE family_id = %d', $row['id'] );
		}
		unset( $row );
		$taken = array_map( 'intval', wp_list_pluck( $families, 'location_id' ) );

		return $this->view(
			'list',
			array(
				'c'         => $c,
				'family'    => $family,
				'families'  => $families,
				'invites'   => DB::results( 'SELECT * FROM {family_invites} WHERE character_id = %d', $c->id() ),
				'cost'      => (int) $this->setting( 'family_cost' ),
				'free'      => array_filter(
					Locations::all(),
					function ( $loc ) use ( $taken ) {
						return ! $this->setting( 'family_one_per_city' ) || ! in_array( (int) $loc['id'], $taken, true );
					}
				),
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Actions                                                              */
	/* ------------------------------------------------------------------ */

	private function require_family( Character $c, string $permission = '' ): ?array {
		$family = self::family_of( $c );
		if ( ! $family ) {
			$this->error( __( 'You are not in a family.', 'wp-maffia-game' ) );
			return null;
		}
		if ( $permission && ! self::can( $c, $permission ) ) {
			$this->error( __( 'You don\'t have the family permission for that.', 'wp-maffia-game' ) );
			return null;
		}
		return $family;
	}

	private function home(): array {
		return array( 'view' => 'home' );
	}

	/**
	 * @return array|void
	 */
	public function action_create( Character $c, array $input ) {
		$name     = trim( sanitize_text_field( $input['name'] ?? '' ) );
		$location = absint( $input['location'] ?? 0 );
		$cost     = (int) $this->setting( 'family_cost' );
		if ( self::family_of( $c ) ) {
			$this->error( __( 'You are already in a family.', 'wp-maffia-game' ) );
			return;
		}
		if ( mb_strlen( $name ) < 3 || mb_strlen( $name ) > 30 ) {
			$this->error( __( 'A family name is 3 to 30 characters long.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! Locations::get( $location ) ) {
			$this->error( __( 'Choose a city.', 'wp-maffia-game' ) );
			return;
		}
		if ( DB::value( 'SELECT id FROM {families} WHERE name = %s', $name ) ) {
			$this->error( __( 'That name is already taken.', 'wp-maffia-game' ) );
			return;
		}
		if ( $this->setting( 'family_one_per_city' ) && DB::value( 'SELECT id FROM {families} WHERE location_id = %d', $location ) ) {
			$this->error( __( 'A family already rules this city.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Founding a family costs %s in cash.', 'wp-maffia-game' ), Format::money( $cost ) ) );
			return;
		}
		$id = DB::insert(
			'families',
			array(
				'name'        => $name,
				'location_id' => $location,
				'boss_id'     => $c->id(),
				'level'       => 1,
				'description' => '',
				'internal'    => '',
				'created_at'  => time(),
			)
		);
		DB::insert(
			'family_members',
			array(
				'character_id' => $c->id(),
				'family_id'    => $id,
				'permissions'  => '',
				'joined_at'    => time(),
			)
		);
		DB::delete( 'family_invites', array( 'character_id' => $c->id() ) );
		/* translators: %s: player */
		self::log( $id, $c->id(), sprintf( __( '%s founded the family.', 'wp-maffia-game' ), $c->name ) );
		$c->log( 'family.create', true, $cost, $id );
		/* translators: %s: family */
		$this->success( sprintf( __( 'The family %s has been founded. You are the boss.', 'wp-maffia-game' ), $name ) );
		return $this->home();
	}

	public function action_invite( Character $c, array $input ): array {
		$family = $this->require_family( $c, 'invite' );
		if ( ! $family ) {
			return $this->home();
		}
		$target = Character::find_by_name( sanitize_text_field( $input['name'] ?? '' ) );
		if ( ! $target || ! $target->is_alive() ) {
			$this->error( __( 'This player doesn\'t exist.', 'wp-maffia-game' ) );
		} elseif ( self::family_of( $target ) ) {
			$this->error( __( 'This player is already in a family.', 'wp-maffia-game' ) );
		} elseif ( DB::value( 'SELECT id FROM {family_invites} WHERE family_id = %d AND character_id = %d', $family['id'], $target->id() ) ) {
			$this->error( __( 'This player has already been invited.', 'wp-maffia-game' ) );
		} else {
			DB::insert(
				'family_invites',
				array(
					'family_id'    => $family['id'],
					'character_id' => $target->id(),
					'invited_by'   => $c->id(),
					'created_at'   => time(),
				)
			);
			/* translators: %s: family */
			$target->notify( sprintf( __( 'You have been invited to the family %s. Check it under Families.', 'wp-maffia-game' ), esc_html( $family['name'] ) ) );
			/* translators: 1: player, 2: player */
			self::log( (int) $family['id'], $c->id(), sprintf( __( '%1$s invited %2$s.', 'wp-maffia-game' ), $c->name, $target->name ) );
			$this->success( __( 'Invitation sent.', 'wp-maffia-game' ) );
		}
		return $this->home();
	}

	public function action_cancel_invite( Character $c, array $input ): array {
		$family = $this->require_family( $c, 'invite' );
		if ( $family ) {
			DB::delete(
				'family_invites',
				array(
					'id'        => absint( $input['invite'] ?? 0 ),
					'family_id' => $family['id'],
				)
			);
		}
		return $this->home();
	}

	/**
	 * @return array|void
	 */
	public function action_accept( Character $c, array $input ) {
		$invite = DB::row( 'SELECT * FROM {family_invites} WHERE id = %d AND character_id = %d', absint( $input['invite'] ?? 0 ), $c->id() );
		$family = $invite ? self::get( (int) $invite['family_id'] ) : null;
		if ( ! $family ) {
			$this->error( __( 'This invitation is no longer valid.', 'wp-maffia-game' ) );
			return;
		}
		if ( self::family_of( $c ) ) {
			$this->error( __( 'You are already in a family.', 'wp-maffia-game' ) );
			return;
		}
		if ( count( self::members( (int) $family['id'] ) ) >= $this->capacity( $family ) ) {
			$this->error( __( 'This family is full.', 'wp-maffia-game' ) );
			return;
		}
		DB::insert(
			'family_members',
			array(
				'character_id' => $c->id(),
				'family_id'    => $family['id'],
				'permissions'  => '',
				'joined_at'    => time(),
			)
		);
		DB::delete( 'family_invites', array( 'character_id' => $c->id() ) );
		/* translators: %s: player */
		self::log( (int) $family['id'], $c->id(), sprintf( __( '%s joined the family.', 'wp-maffia-game' ), $c->name ) );
		/* translators: %s: family */
		$this->success( sprintf( __( 'Welcome to %s.', 'wp-maffia-game' ), $family['name'] ) );
		return $this->home();
	}

	public function action_decline( Character $c, array $input ): void {
		DB::delete(
			'family_invites',
			array(
				'id'           => absint( $input['invite'] ?? 0 ),
				'character_id' => $c->id(),
			)
		);
	}

	/**
	 * @return array|void
	 */
	public function action_leave( Character $c, array $input ) {
		$family = $this->require_family( $c );
		if ( ! $family ) {
			return;
		}
		if ( (int) $family['boss_id'] === $c->id() ) {
			$this->error( __( 'A boss doesn\'t leave. Hand over leadership or disband the family.', 'wp-maffia-game' ) );
			return $this->home();
		}
		DB::delete( 'family_members', array( 'character_id' => $c->id() ) );
		if ( (int) $family['underboss_id'] === $c->id() ) {
			DB::update( 'families', array( 'underboss_id' => 0 ), array( 'id' => $family['id'] ) );
		}
		/* translators: %s: player */
		self::log( (int) $family['id'], $c->id(), sprintf( __( '%s left the family.', 'wp-maffia-game' ), $c->name ) );
		$this->success( __( 'You left the family.', 'wp-maffia-game' ) );
	}

	public function action_kick( Character $c, array $input ): array {
		$family = $this->require_family( $c, 'kick' );
		$target = Character::find( absint( $input['member'] ?? 0 ) );
		if ( ! $family || ! $target ) {
			return $this->home();
		}
		if ( in_array( $target->id(), array( (int) $family['boss_id'], $c->id() ), true ) ) {
			$this->error( __( 'You can\'t remove that member.', 'wp-maffia-game' ) );
			return $this->home();
		}
		if ( DB::delete( 'family_members', array( 'character_id' => $target->id(), 'family_id' => $family['id'] ) ) ) {
			if ( (int) $family['underboss_id'] === $target->id() ) {
				DB::update( 'families', array( 'underboss_id' => 0 ), array( 'id' => $family['id'] ) );
			}
			/* translators: %s: family */
			$target->notify( sprintf( __( 'You were kicked out of the family %s.', 'wp-maffia-game' ), esc_html( $family['name'] ) ) );
			/* translators: 1: player, 2: player */
			self::log( (int) $family['id'], $c->id(), sprintf( __( '%1$s kicked %2$s out of the family.', 'wp-maffia-game' ), $c->name, $target->name ) );
		}
		return $this->home();
	}

	public function action_roles( Character $c, array $input ): array {
		$family = $this->require_family( $c );
		if ( ! $family ) {
			return $this->home();
		}
		if ( (int) $family['boss_id'] !== $c->id() ) {
			$this->error( __( 'Only the boss assigns roles.', 'wp-maffia-game' ) );
			return $this->home();
		}
		$member = Character::find( absint( $input['member'] ?? 0 ) );
		$in     = $member ? DB::value( 'SELECT family_id FROM {family_members} WHERE character_id = %d', $member->id() ) : 0;
		if ( ! $member || (int) $in !== (int) $family['id'] ) {
			return $this->home();
		}
		$role = sanitize_key( $input['role'] ?? '' );
		if ( 'underboss' === $role && $member->id() !== $c->id() ) {
			DB::update( 'families', array( 'underboss_id' => $member->id() ), array( 'id' => $family['id'] ) );
			/* translators: %s: player */
			self::log( (int) $family['id'], $c->id(), sprintf( __( '%s was appointed underboss.', 'wp-maffia-game' ), $member->name ) );
		} elseif ( 'boss' === $role && $member->id() !== $c->id() ) {
			DB::update(
				'families',
				array(
					'boss_id'      => $member->id(),
					'underboss_id' => (int) $family['underboss_id'] === $member->id() ? $c->id() : (int) $family['underboss_id'],
				),
				array( 'id' => $family['id'] )
			);
			/* translators: %s: player */
			self::log( (int) $family['id'], $c->id(), sprintf( __( '%s is the new boss.', 'wp-maffia-game' ), $member->name ) );
			$member->notify( __( 'You are the new boss of your family.', 'wp-maffia-game' ) );
		} elseif ( 'member' === $role && (int) $family['underboss_id'] === $member->id() ) {
			DB::update( 'families', array( 'underboss_id' => 0 ), array( 'id' => $family['id'] ) );
		}
		$perms = array_intersect( array_keys( self::permissions() ), array_map( 'sanitize_key', (array) ( $input['perms'] ?? array() ) ) );
		DB::update( 'family_members', array( 'permissions' => implode( ',', $perms ) ), array( 'character_id' => $member->id() ) );
		$this->success( __( 'Permissions updated.', 'wp-maffia-game' ) );
		return $this->home();
	}

	public function action_deposit( Character $c, array $input ): array {
		$family = $this->require_family( $c );
		if ( ! $family ) {
			return $this->home();
		}
		$field  = 'bullets' === ( $input['what'] ?? '' ) ? 'bullets' : 'money';
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		if ( $amount < 1 || ! $c->spend( $field, $amount ) ) {
			$this->error( __( 'You don\'t have that much.', 'wp-maffia-game' ) );
			return $this->home();
		}
		$tax    = (int) $this->setting( 'money' === $field ? 'family_cash_tax' : 'family_bullet_tax' );
		$credit = (int) floor( $amount * ( 100 - max( 0, min( 100, $tax ) ) ) / 100 );
		DB::query( "UPDATE {families} SET `$field` = `$field` + %d WHERE id = %d", $credit, $family['id'] );
		$label = 'money' === $field ? Format::money( $credit ) : sprintf( /* translators: %s: number */ __( '%s bullets', 'wp-maffia-game' ), Format::number( $credit ) );
		/* translators: 1: player, 2: amount */
		self::log( (int) $family['id'], $c->id(), sprintf( __( '%1$s deposited %2$s into the vault.', 'wp-maffia-game' ), $c->name, $label ) );
		/* translators: %s: amount */
		$this->success( sprintf( __( '%s has been deposited into the family vault.', 'wp-maffia-game' ), $label ) );
		return $this->home();
	}

	public function action_withdraw( Character $c, array $input ): array {
		$field  = 'bullets' === ( $input['what'] ?? '' ) ? 'bullets' : 'money';
		$family = $this->require_family( $c, 'money' === $field ? 'withdraw_money' : 'withdraw_bullets' );
		if ( ! $family ) {
			return $this->home();
		}
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		$done   = $amount > 0 && DB::query( "UPDATE {families} SET `$field` = `$field` - %d WHERE id = %d AND `$field` >= %d", $amount, $family['id'], $amount );
		if ( ! $done ) {
			$this->error( __( 'The vault doesn\'t hold that much.', 'wp-maffia-game' ) );
			return $this->home();
		}
		$c->add( $field, $amount );
		$label = 'money' === $field ? Format::money( $amount ) : sprintf( /* translators: %s: number */ __( '%s bullets', 'wp-maffia-game' ), Format::number( $amount ) );
		/* translators: 1: player, 2: amount */
		self::log( (int) $family['id'], $c->id(), sprintf( __( '%1$s withdrew %2$s from the vault.', 'wp-maffia-game' ), $c->name, $label ) );
		/* translators: %s: amount */
		$this->success( sprintf( __( 'You withdrew %s.', 'wp-maffia-game' ), $label ) );
		return $this->home();
	}

	public function action_upgrade( Character $c, array $input ): array {
		$family = $this->require_family( $c, 'upgrade' );
		if ( ! $family ) {
			return $this->home();
		}
		$cost = (int) $family['level'] * (int) $this->setting( 'family_upgrade_cost' );
		$done = DB::query( 'UPDATE {families} SET money = money - %d, level = level + 1 WHERE id = %d AND money >= %d AND level = %d', $cost, $family['id'], $cost, $family['level'] );
		if ( ! $done ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Expanding costs %s from the family vault.', 'wp-maffia-game' ), Format::money( $cost ) ) );
			return $this->home();
		}
		/* translators: 1: player, 2: money */
		self::log( (int) $family['id'], $c->id(), sprintf( __( '%1$s expanded the family for %2$s.', 'wp-maffia-game' ), $c->name, Format::money( $cost ) ) );
		$this->success( __( 'The family has an extra slot.', 'wp-maffia-game' ) );
		return $this->home();
	}

	public function action_edit( Character $c, array $input ): array {
		$family = $this->require_family( $c );
		if ( ! $family ) {
			return $this->home();
		}
		$field = 'internal' === ( $input['field'] ?? '' ) ? 'internal' : 'description';
		if ( ! self::can( $c, 'internal' === $field ? 'edit_internal' : 'edit_profile' ) ) {
			$this->error( __( 'You don\'t have the family permission for that.', 'wp-maffia-game' ) );
			return $this->home();
		}
		DB::update( 'families', array( $field => wp_kses_post( mb_substr( (string) ( $input['text'] ?? '' ), 0, 5000 ) ) ), array( 'id' => $family['id'] ) );
		$this->success( __( 'Saved.', 'wp-maffia-game' ) );
		return $this->home();
	}

	/**
	 * @return array|void
	 */
	public function action_disband( Character $c, array $input ) {
		$family = $this->require_family( $c );
		if ( ! $family || (int) $family['boss_id'] !== $c->id() ) {
			$this->error( __( 'Only the boss can disband the family.', 'wp-maffia-game' ) );
			return;
		}
		if ( empty( $input['confirm'] ) ) {
			$this->error( __( 'Tick the confirmation box.', 'wp-maffia-game' ) );
			return $this->home();
		}
		$c->add( 'money', (int) $family['money'] );
		$c->add( 'bullets', (int) $family['bullets'] );
		/* translators: %s: family */
		self::dissolve( (int) $family['id'], sprintf( __( 'The family %s was disbanded by the boss.', 'wp-maffia-game' ), esc_html( $family['name'] ) ) );
		$this->success( __( 'The family has been disbanded. The vault went to you.', 'wp-maffia-game' ) );
	}
}

return new Families();
