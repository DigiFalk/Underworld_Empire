<?php
/**
 * One-off data migrations between plugin versions.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

final class Migrations {

	const OPTION = 'dfmg_migrations';

	public static function run(): void {
		$done = (array) get_option( self::OPTION, array() );
		if ( empty( $done['english_seed'] ) ) {
			self::english_seed();
			$done['english_seed'] = time();
			update_option( self::OPTION, $done );
		}
		if ( empty( $done['round_name_en'] ) ) {
			self::round_name_en();
			$done['round_name_en'] = time();
			update_option( self::OPTION, $done );
		}
	}

	/**
	 * The first migration only knew "Ronde 1". Any Dutch round name like "Ronde 2"
	 * becomes "Round 2" (and "Punten" in any case becomes "Points").
	 */
	private static function round_name_en(): void {
		$settings = Settings::all();
		$changed  = false;
		if ( isset( $settings['round_name'] ) && preg_match( '/^\s*ronde\s+(\d+)\s*$/i', (string) $settings['round_name'], $m ) ) {
			$settings['round_name'] = 'Round ' . $m[1];
			$changed                = true;
		}
		if ( isset( $settings['points_name'] ) && 'punten' === strtolower( trim( (string) $settings['points_name'] ) ) ) {
			$settings['points_name'] = 'Points';
			$changed                 = true;
		}
		if ( $changed ) {
			Settings::save( $settings );
		}
	}

	/**
	 * Installs from the first (Dutch) version still carry Dutch starting data.
	 * Rows that were never renamed by the admin are translated to English.
	 */
	private static function english_seed(): void {
		$map = array(
			'ranks'        => array(
				'name' => array(
					'Straatschoffie' => 'Street Rat',
					'Loopjongen'     => 'Errand Boy',
					'Zakkenroller'   => 'Pickpocket',
					'Kruimeldief'    => 'Petty Thief',
					'Crimineel'      => 'Criminal',
					'Huurmoordenaar' => 'Hitman',
					'Onderbaas'      => 'Underboss',
					'Peetvader'      => 'Godfather',
				),
			),
			'money_ranks'  => array(
				'name' => array(
					'Platzak'           => 'Broke',
					'Arm'               => 'Poor',
					'Modaal'            => 'Average',
					'Rijk'              => 'Rich',
					'Steenrijk'         => 'Filthy Rich',
					'Onbetaalbaar rijk' => 'Obscenely Rich',
				),
			),
			'locations'    => array(
				'name' => array(
					'Antwerpen' => 'Antwerp',
					'Napels'    => 'Naples',
				),
			),
			'items'        => array(
				'name'        => array(
					'Machinepistool'        => 'Submachine Gun',
					'Aanvalsgeweer'         => 'Assault Rifle',
					'Scherpschuttersgeweer' => 'Sniper Rifle',
					'Leren jas'             => 'Leather Jacket',
					'Kogelwerend vest'      => 'Bulletproof Vest',
					'Gepantserd pak'        => 'Armored Suit',
					'Verbanddoos'           => 'First Aid Kit',
					'Dokter aan huis'       => 'House Call Doctor',
					'Corrupte advocaat'     => 'Crooked Lawyer',
				),
				'description' => array(
					'Oud maar betrouwbaar.'                       => 'Old but reliable.',
					'Veel kogels in korte tijd.'                  => 'Lots of bullets in no time.',
					'Voor wie geen genoegen neemt met half werk.' => 'For those who never settle for half a job.',
					'Eén schot, één probleem minder.'             => 'One shot, one less problem.',
					'Beter dan niets.'                            => 'Better than nothing.',
					'Standaard uitrusting van elke lijfwacht.'    => 'Standard gear for every bodyguard.',
					'Maatwerk van een discrete kleermaker.'       => 'Tailored by a discreet tailor.',
					'Herstelt een deel van je gezondheid.'        => 'Restores part of your health.',
					'Volledig herstel, geen vragen.'              => 'Full recovery, no questions asked.',
					'Direct vrij uit de gevangenis.'              => 'Out of jail instantly.',
				),
			),
			'crimes'       => array(
				'name'        => array(
					'Zakkenrollen op de markt'     => 'Pickpocket at the market',
					'Een nachtwinkel beroven'      => 'Rob a corner shop',
					'Toeristen oplichten'          => 'Scam tourists',
					'Een juwelier overvallen'      => 'Rob a jewellery store',
					'Een geldtransport overvallen' => 'Hit an armoured truck',
					'De casinokluis kraken'        => 'Crack the casino vault',
				),
				'description' => array(
					'Een snelle greep in een volle tas.'         => 'A quick grab into a full bag.',
					'Kassa leeg, capuchon op.'                   => 'Empty the till, hood up.',
					'Nep-horloges voor echte prijzen.'           => 'Fake watches for real prices.',
					'Snel, luid en riskant.'                     => 'Fast, loud and risky.',
					'Goed plannen of lang zitten.'               => 'Plan it well or do long time.',
					'Het grote werk, alleen voor professionals.' => 'The big job, professionals only.',
				),
			),
			'theft_spots'  => array(
				'name' => array(
					'Een woonwijk in de nacht'            => 'A residential street at night',
					'Een parkeergarage in het centrum'    => 'A downtown parking garage',
					'Het parkeerterrein van het vliegveld' => 'The airport parking lot',
					'Een villawijk'                       => 'A villa district',
					'De showroom van een autodealer'      => 'A car dealer showroom',
				),
			),
			'cars'         => array(
				'name' => array( 'Mercedes S-Klasse' => 'Mercedes S-Class' ),
			),
			'memberships'  => array(
				'name' => array(
					'Maand'    => 'Month',
					'Kwartaal' => 'Quarter',
				),
			),
			'forum_boards' => array(
				'name'        => array(
					'Algemeen'       => 'General',
					'Handel'         => 'Trade',
					'Aankondigingen' => 'Announcements',
				),
				'description' => array(
					'Alles over het spel.'         => 'Everything about the game.',
					'Kopen, verkopen en ruilen.'   => 'Buying, selling and swapping.',
					'Rekruteren en oorlog voeren.' => 'Recruiting and waging war.',
					'Mededelingen van de leiding.' => 'News from the staff.',
				),
			),
		);

		foreach ( $map as $table => $columns ) {
			if ( ! DB::table_exists( $table ) ) {
				continue;
			}
			foreach ( $columns as $column => $pairs ) {
				foreach ( $pairs as $dutch => $english ) {
					DB::query( "UPDATE {{$table}} SET `$column` = %s WHERE `$column` = %s", $english, $dutch );
				}
			}
		}

		$settings = Settings::all();
		$defaults = array(
			'points_name' => array( 'Punten' => 'Points' ),
			'round_name'  => array( 'Ronde 1' => 'Round 1' ),
		);
		foreach ( $defaults as $key => $pairs ) {
			if ( isset( $settings[ $key ], $pairs[ $settings[ $key ] ] ) ) {
				$settings[ $key ] = $pairs[ $settings[ $key ] ];
			}
		}
		Settings::save( $settings );

		$page = get_post( (int) get_option( 'dfmg_page_id' ) );
		if ( $page ) {
			$content = str_replace( array( '[maffia_game]', '[mafia_game]' ), '[underworld_empire]', $page->post_content );
			$title   = in_array( $page->post_title, array( 'Maffia Game', 'Mafia Game' ), true ) ? 'Underworld Empire' : $page->post_title;
			if ( in_array( get_post_meta( $page->ID, '_wp_page_template', true ), array( '', 'default' ), true ) ) {
				update_post_meta( $page->ID, '_wp_page_template', 'page-game' );
			}
			if ( $content !== $page->post_content || $title !== $page->post_title ) {
				wp_update_post(
					array(
						'ID'           => $page->ID,
						'post_title'   => $title,
						'post_content' => $content,
					)
				);
			}
		}
		Ranks::flush();
		Locations::flush();
	}
}
