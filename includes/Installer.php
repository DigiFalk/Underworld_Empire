<?php
/**
 * Core tables, starting data and activation logic.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Installer {

	const OPTION_DB = 'dfmg_core_db_version';

	public static function activate(): void {
		self::install();
		self::create_game_page();
		if ( ! wp_next_scheduled( 'dfmg_hourly' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'dfmg_hourly' );
		}
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'dfmg_manage' );
		}
		update_option( 'dfmg_flush_rewrite', 1 );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'dfmg_hourly' );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION_DB ) !== DFMG_DB_VERSION ) {
			self::install();
		}
	}

	public static function schema(): array {
		return array(
			'characters'    => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				name varchar(40) NOT NULL DEFAULT '',
				status tinyint(1) NOT NULL DEFAULT 1,
				money bigint(20) NOT NULL DEFAULT 0,
				bank bigint(20) NOT NULL DEFAULT 0,
				bullets bigint(20) NOT NULL DEFAULT 0,
				exp bigint(20) NOT NULL DEFAULT 0,
				points bigint(20) NOT NULL DEFAULT 0,
				damage bigint(20) NOT NULL DEFAULT 0,
				rank_id int(11) NOT NULL DEFAULT 0,
				location_id int(11) NOT NULL DEFAULT 0,
				shot_by bigint(20) unsigned NOT NULL DEFAULT 0,
				bio text NULL,
				created_at int(11) NOT NULL DEFAULT 0,
				last_active int(11) NOT NULL DEFAULT 0,
				died_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY name (name),
				KEY user_id (user_id),
				KEY status_location (status,location_id)",
			'timers'        => "
				character_id bigint(20) unsigned NOT NULL,
				name varchar(40) NOT NULL,
				expires_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (character_id,name),
				KEY name_expires (name,expires_at)",
			'notifications' => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				character_id bigint(20) unsigned NOT NULL,
				message text NOT NULL,
				created_at int(11) NOT NULL DEFAULT 0,
				is_read tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY character_id (character_id,is_read)',
			'activity'      => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				character_id bigint(20) unsigned NOT NULL,
				action varchar(60) NOT NULL DEFAULT '',
				ref_id bigint(20) NOT NULL DEFAULT 0,
				success tinyint(1) NOT NULL DEFAULT 1,
				amount bigint(20) NOT NULL DEFAULT 0,
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY character_id (character_id),
				KEY action (action,success)",
			'ranks'         => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(80) NOT NULL DEFAULT '',
				exp_required bigint(20) NOT NULL DEFAULT 0,
				max_players int(11) NOT NULL DEFAULT 0,
				cash_reward bigint(20) NOT NULL DEFAULT 0,
				bullet_reward bigint(20) NOT NULL DEFAULT 0,
				max_health int(11) NOT NULL DEFAULT 1000,
				PRIMARY KEY  (id)",
			'money_ranks'   => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(80) NOT NULL DEFAULT '',
				min_money bigint(20) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)",
			'locations'     => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(80) NOT NULL DEFAULT '',
				travel_cost bigint(20) NOT NULL DEFAULT 0,
				travel_time int(11) NOT NULL DEFAULT 0,
				bullet_stock bigint(20) NOT NULL DEFAULT 0,
				bullet_price int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)",
			'items'         => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				type varchar(40) NOT NULL DEFAULT '',
				price bigint(20) NOT NULL DEFAULT 0,
				buyable tinyint(1) NOT NULL DEFAULT 1,
				description text NULL,
				effects text NULL,
				PRIMARY KEY  (id),
				KEY type (type)",
			'inventory'     => '
				character_id bigint(20) unsigned NOT NULL,
				item_id int(11) NOT NULL,
				qty int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (character_id,item_id)',
			'equipment'     => '
				character_id bigint(20) unsigned NOT NULL,
				slot varchar(40) NOT NULL,
				item_id int(11) NOT NULL,
				PRIMARY KEY  (character_id,slot)',
			'properties'    => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				type varchar(60) NOT NULL DEFAULT '',
				location_id int(11) NOT NULL DEFAULT 0,
				owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
				price bigint(20) NOT NULL DEFAULT 0,
				profit bigint(20) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY type_location (type,location_id),
				KEY owner_id (owner_id)",
		);
	}

	/**
	 * Core tables that hold round (player) data.
	 */
	public static function round_tables(): array {
		return array( 'characters', 'timers', 'notifications', 'activity', 'inventory', 'equipment', 'properties' );
	}

	public static function install(): void {
		DB::create_tables( self::schema() );
		self::seed();
		update_option( self::OPTION_DB, DFMG_DB_VERSION );
	}

	private static function seed(): void {
		if ( ! (int) DB::value( 'SELECT COUNT(*) FROM {ranks}' ) ) {
			$ranks = array(
				// name, exp, max players, cash, bullets, health.
				array( 'Street Rat', 0, 0, 0, 0, 3000 ),
				array( 'Errand Boy', 50, 0, 250, 25, 5000 ),
				array( 'Pickpocket', 150, 0, 750, 50, 8000 ),
				array( 'Petty Thief', 350, 0, 1500, 75, 12000 ),
				array( 'Criminal', 700, 0, 3000, 100, 17000 ),
				array( 'Gangster', 1200, 0, 6000, 150, 23000 ),
				array( 'Hitman', 2000, 0, 12000, 250, 30000 ),
				array( 'Capo', 3500, 0, 25000, 400, 40000 ),
				array( 'Consigliere', 6000, 0, 50000, 600, 55000 ),
				array( 'Underboss', 10000, 0, 100000, 1000, 75000 ),
				array( 'Godfather', 16000, 10, 250000, 2000, 100000 ),
			);
			foreach ( $ranks as $r ) {
				DB::insert(
					'ranks',
					array(
						'name'          => $r[0],
						'exp_required'  => $r[1],
						'max_players'   => $r[2],
						'cash_reward'   => $r[3],
						'bullet_reward' => $r[4],
						'max_health'    => $r[5],
					)
				);
			}
		}

		if ( ! (int) DB::value( 'SELECT COUNT(*) FROM {money_ranks}' ) ) {
			$wealth = array(
				array( 'Broke', 0 ),
				array( 'Poor', 10000 ),
				array( 'Average', 100000 ),
				array( 'Rich', 1000000 ),
				array( 'Filthy Rich', 10000000 ),
				array( 'Obscenely Rich', 100000000 ),
			);
			foreach ( $wealth as $w ) {
				DB::insert(
					'money_ranks',
					array(
						'name'      => $w[0],
						'min_money' => $w[1],
					)
				);
			}
		}

		if ( ! (int) DB::value( 'SELECT COUNT(*) FROM {locations}' ) ) {
			$cities = array(
				// name, travel cost, travel cooldown, bullet stock, bullet price.
				array( 'Amsterdam', 250, 1800, 20000, 30 ),
				array( 'Rotterdam', 200, 1800, 20000, 28 ),
				array( 'Antwerp', 300, 2400, 15000, 32 ),
				array( 'Marseille', 450, 3000, 15000, 35 ),
				array( 'Naples', 500, 3600, 15000, 38 ),
				array( 'Palermo', 600, 3600, 10000, 40 ),
			);
			foreach ( $cities as $c ) {
				DB::insert(
					'locations',
					array(
						'name'         => $c[0],
						'travel_cost'  => $c[1],
						'travel_time'  => $c[2],
						'bullet_stock' => $c[3],
						'bullet_price' => $c[4],
					)
				);
			}
		}

		if ( ! (int) DB::value( 'SELECT COUNT(*) FROM {items}' ) ) {
			$items = array(
				array( 'Revolver', 'weapon', 7500, 'Old but reliable.', 'attack_pct=10' ),
				array( 'Submachine Gun', 'weapon', 40000, 'Lots of bullets in no time.', 'attack_pct=25' ),
				array( 'Assault Rifle', 'weapon', 150000, 'For those who never settle for half a job.', 'attack_pct=50' ),
				array( 'Sniper Rifle', 'weapon', 500000, 'One shot, one less problem.', "attack_pct=80\nmax_health=-2000" ),
				array( 'Leather Jacket', 'armor', 5000, 'Better than nothing.', 'defense_pct=5' ),
				array( 'Bulletproof Vest', 'armor', 50000, 'Standard gear for every bodyguard.', 'defense_pct=20' ),
				array( 'Armored Suit', 'armor', 250000, 'Tailored by a discreet tailor.', "defense_pct=40\nmax_health=5000" ),
				array( 'First Aid Kit', 'consumable', 3000, 'Restores part of your health.', 'heal_pct=25' ),
				array( 'House Call Doctor', 'consumable', 20000, 'Full recovery, no questions asked.', 'heal_pct=100' ),
				array( 'Crooked Lawyer', 'consumable', 25000, 'Out of jail instantly.', 'reset_timer=jail' ),
			);
			foreach ( $items as $i ) {
				DB::insert(
					'items',
					array(
						'name'        => $i[0],
						'type'        => $i[1],
						'price'       => $i[2],
						'buyable'     => 1,
						'description' => $i[3],
						'effects'     => $i[4],
					)
				);
			}
		}

		if ( false === get_option( Settings::OPTION, false ) ) {
			$defaults = array();
			foreach ( Settings::core_fields() as $key => $field ) {
				$defaults[ $key ] = $field['default'];
			}
			add_option( Settings::OPTION, $defaults );
		}
	}

	/**
	 * Create the page that hosts the game (shortcode [underworld_empire]).
	 */
	public static function create_game_page(): void {
		$page_id = (int) get_option( 'dfmg_page_id' );
		if ( $page_id && get_post( $page_id ) && 'trash' !== get_post_status( $page_id ) ) {
			return;
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Underworld Empire', 'underworld-empire' ),
				'post_name'    => 'underworld-empire',
				'post_content' => '[underworld_empire]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'dfmg_page_id', (int) $page_id );
			// Wide template of the bundled theme; other themes ignore it.
			update_post_meta( (int) $page_id, '_wp_page_template', 'page-game' );
		}
	}
}
