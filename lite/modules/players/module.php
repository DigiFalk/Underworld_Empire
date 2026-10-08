<?php
/**
 * Module Name: Players
 * Description: Who is online and player search.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Modules;

use DigiFalk\MafiaPBBGEngine\Character;
use DigiFalk\MafiaPBBGEngine\DB;
use DigiFalk\MafiaPBBGEngine\Frontend\UI;
use DigiFalk\MafiaPBBGEngine\Module\Module;
use DigiFalk\MafiaPBBGEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Players extends Module {

	public function title(): string {
		return __( 'Players', 'mafia-pbbg-engine' );
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
				'label' => __( 'Players', 'mafia-pbbg-engine' ),
				'group' => 'community',
				'order' => 20,
				'badge' => $online ?: '',
			),
		);
	}

	private function table( array $ids, bool $with_location = false ): string {
		if ( ! $ids ) {
			return UI::empty_state( __( 'Nobody found.', 'mafia-pbbg-engine' ) );
		}
		$html = '<table class="dfmg-table"><thead><tr><th>' . esc_html__( 'Name', 'mafia-pbbg-engine' ) . '</th><th>' . esc_html__( 'Rank', 'mafia-pbbg-engine' ) . '</th>';
		if ( $with_location ) {
			$html .= '<th>' . esc_html__( 'Status', 'mafia-pbbg-engine' ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $ids as $id ) {
			$p = Character::find( (int) $id );
			if ( ! $p ) {
				continue;
			}
			$html .= '<tr><td>' . $p->link() . '</td><td>' . esc_html( $p->rank_name() ) . '</td>';
			if ( $with_location ) {
				$html .= '<td>' . ( $p->is_alive() ? ( $p->is_online() ? '<span class="dfmg-online">' . esc_html__( 'online', 'mafia-pbbg-engine' ) . '</span>' : esc_html__( 'offline', 'mafia-pbbg-engine' ) ) : '<span class="dfmg-dead">' . esc_html__( 'murdered', 'mafia-pbbg-engine' ) . '</span>' ) . '</td>';
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
		$html .= '<input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="' . esc_attr__( 'Search for a player…', 'mafia-pbbg-engine' ) . '">';
		$html .= '<button type="submit" class="dfmg-button">' . esc_html__( 'Search', 'mafia-pbbg-engine' ) . '</button></form>';

		if ( '' !== $q ) {
			$ids   = DB::column( 'SELECT id FROM {characters} WHERE name LIKE %s ORDER BY status DESC, name ASC LIMIT 50', '%' . DB::wpdb()->esc_like( $q ) . '%' );
			$html .= '<h3>' . esc_html__( 'Search results', 'mafia-pbbg-engine' ) . '</h3>' . $this->table( $ids, true );
		}

		$online = DB::column(
			'SELECT id FROM {characters} WHERE status = 1 AND last_active > %d ORDER BY last_active DESC LIMIT 200',
			time() - 60 * Settings::int( 'online_minutes', 15 )
		);
		$html  .= '<h3>' . esc_html__( 'Online now', 'mafia-pbbg-engine' ) . ' (' . count( $online ) . ')</h3>' . $this->table( $online );
		return $html;
	}
}

return new Players();
