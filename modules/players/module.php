<?php
/**
 * Module Name: Players
 * Description: Who is online and player search.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Module\Module;
use DigiFalk\MaffiaGame\Settings;

defined( 'ABSPATH' ) || exit;

final class Players extends Module {

	public function title(): string {
		return __( 'Players', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function menu( Character $c ): array {
		$online = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1 AND last_active > %d', time() - 60 * Settings::int( 'online_minutes', 15 ) );
		return array(
			array(
				'label' => __( 'Players', 'wp-maffia-game' ),
				'group' => 'community',
				'order' => 20,
				'badge' => $online ?: '',
			),
		);
	}

	private function table( array $ids, bool $with_location = false ): string {
		if ( ! $ids ) {
			return UI::empty_state( __( 'Nobody found.', 'wp-maffia-game' ) );
		}
		$html = '<table class="dfmg-table"><thead><tr><th>' . esc_html__( 'Name', 'wp-maffia-game' ) . '</th><th>' . esc_html__( 'Rank', 'wp-maffia-game' ) . '</th>';
		if ( $with_location ) {
			$html .= '<th>' . esc_html__( 'Status', 'wp-maffia-game' ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $ids as $id ) {
			$p = Character::find( (int) $id );
			if ( ! $p ) {
				continue;
			}
			$html .= '<tr><td>' . $p->link() . '</td><td>' . esc_html( $p->rank_name() ) . '</td>';
			if ( $with_location ) {
				$html .= '<td>' . ( $p->is_alive() ? ( $p->is_online() ? '<span class="dfmg-online">' . esc_html__( 'online', 'wp-maffia-game' ) . '</span>' : esc_html__( 'offline', 'wp-maffia-game' ) ) : '<span class="dfmg-dead">' . esc_html__( 'murdered', 'wp-maffia-game' ) . '</span>' ) . '</td>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table>';
	}

	public function render( Character $c, array $query ): string {
		$q    = trim( (string) ( $query['q'] ?? '' ) );
		$html = '<form method="get" class="dfmg-form" action="' . esc_url( $this->url() ) . '">';
		// Keep the page query args (page_id or permalink).
		foreach ( wp_parse_args( (string) wp_parse_url( $this->url(), PHP_URL_QUERY ) ) as $key => $value ) {
			$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		$html .= '<input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="' . esc_attr__( 'Search for a player…', 'wp-maffia-game' ) . '">';
		$html .= '<button type="submit" class="dfmg-button">' . esc_html__( 'Search', 'wp-maffia-game' ) . '</button></form>';

		if ( '' !== $q ) {
			$ids   = DB::column( 'SELECT id FROM {characters} WHERE name LIKE %s ORDER BY status DESC, name ASC LIMIT 50', '%' . DB::wpdb()->esc_like( $q ) . '%' );
			$html .= '<h3>' . esc_html__( 'Search results', 'wp-maffia-game' ) . '</h3>' . $this->table( $ids, true );
		}

		$online = DB::column(
			'SELECT id FROM {characters} WHERE status = 1 AND last_active > %d ORDER BY last_active DESC LIMIT 200',
			time() - 60 * Settings::int( 'online_minutes', 15 )
		);
		$html  .= '<h3>' . esc_html__( 'Online now', 'wp-maffia-game' ) . ' (' . count( $online ) . ')</h3>' . $this->table( $online );
		return $html;
	}
}

return new Players();
