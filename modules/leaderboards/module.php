<?php
/**
 * Module Name: Leaderboards
 * Description: The top of the underworld: highest rank, richest players, most murders and most crimes.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Leaderboards extends Module {

	public function title(): string {
		return __( 'Leaderboards', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Leaderboards', 'wp-maffia-game' ),
				'group' => 'community',
				'order' => 30,
			),
		);
	}

	/**
	 * Board definitions: key => [ label, callback returning [ character id => display value ] ].
	 */
	private function boards(): array {
		$activity = static function ( string $action ) {
			return static function () use ( $action ) {
				$rows = DB::results(
					'SELECT a.character_id AS id, COUNT(*) AS total FROM {activity} a INNER JOIN {characters} ch ON ch.id = a.character_id
					 WHERE a.action = %s AND a.success = 1 AND ch.status = 1 GROUP BY a.character_id ORDER BY total DESC LIMIT 25',
					$action
				);
				return wp_list_pluck( $rows, 'total', 'id' );
			};
		};
		return apply_filters(
			'dfmg_leaderboards',
			array(
				'rank'    => array(
					'label'    => __( 'Rank', 'wp-maffia-game' ),
					'callback' => static function () {
						$out = array();
						foreach ( DB::column( 'SELECT id FROM {characters} WHERE status = 1 ORDER BY exp DESC LIMIT 25' ) as $id ) {
							$out[ $id ] = Character::find( (int) $id )->rank_name();
						}
						return $out;
					},
				),
				'wealth'  => array(
					'label'    => __( 'Wealth', 'wp-maffia-game' ),
					'callback' => static function () {
						$out = array();
						foreach ( DB::column( 'SELECT id FROM {characters} WHERE status = 1 ORDER BY (money + bank) DESC LIMIT 25' ) as $id ) {
							$out[ $id ] = Character::find( (int) $id )->wealth_title();
						}
						return $out;
					},
				),
				'murders' => array(
					'label'    => __( 'Murders', 'wp-maffia-game' ),
					'callback' => $activity( 'murder' ),
				),
				'crimes'  => array(
					'label'    => __( 'Crimes', 'wp-maffia-game' ),
					'callback' => $activity( 'crimes' ),
				),
				'busts'   => array(
					'label'    => __( 'Breakouts', 'wp-maffia-game' ),
					'callback' => $activity( 'jail.bust' ),
				),
			)
		);
	}

	public function render( Character $c, array $query ): string {
		$boards  = $this->boards();
		$current = isset( $boards[ $query['board'] ?? '' ] ) ? $query['board'] : (string) key( $boards );

		$html = '<nav class="dfmg-tabs">';
		foreach ( $boards as $key => $board ) {
			$html .= '<a class="' . ( $key === $current ? 'is-active' : '' ) . '" href="' . esc_url( $this->url( array( 'board' => $key ) ) ) . '">' . esc_html( $board['label'] ) . '</a>';
		}
		$html .= '</nav>';

		$cache_key = 'dfmg_lb_' . $current;
		$rows      = get_transient( $cache_key );
		if ( ! is_array( $rows ) ) {
			$rows = (array) call_user_func( $boards[ $current ]['callback'] );
			set_transient( $cache_key, $rows, 5 * MINUTE_IN_SECONDS );
		}
		if ( ! $rows ) {
			return $html . UI::empty_state( __( 'No data yet.', 'wp-maffia-game' ) );
		}
		$html .= '<table class="dfmg-table dfmg-leaderboard"><tbody>';
		$pos   = 0;
		foreach ( $rows as $id => $value ) {
			$html .= '<tr class="' . ( (int) $id === $c->id() ? 'is-me' : '' ) . '"><td class="dfmg-pos">' . ( ++$pos ) . '</td><td>' . Character::link_by_id( (int) $id ) . '</td><td>'
				. esc_html( is_numeric( $value ) ? Format::number( $value ) : (string) $value ) . '</td></tr>';
		}
		return $html . '</tbody></table><p class="dfmg-muted">' . esc_html__( 'Updated every 5 minutes.', 'wp-maffia-game' ) . '</p>';
	}
}

return new Leaderboards();
