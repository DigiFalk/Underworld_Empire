<?php
/**
 * Module Name: Players
 * Description: Who is online and player search.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Settings;

defined( 'ABSPATH' ) || exit;

final class Players extends Module {

	public function title(): string {
		return __( 'Players', 'underworld-empire' );
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
				'label' => __( 'Players', 'underworld-empire' ),
				'group' => 'community',
				'order' => 20,
				'badge' => $online ?: '',
			),
		);
	}

	private function table( array $ids, bool $with_location = false ): string {
		if ( ! $ids ) {
			return UI::empty_state( __( 'Nobody found.', 'underworld-empire' ) );
		}
		$html = '<table class="dfmg-table"><thead><tr><th>' . esc_html__( 'Name', 'underworld-empire' ) . '</th><th>' . esc_html__( 'Rank', 'underworld-empire' ) . '</th>';
		if ( $with_location ) {
			$html .= '<th>' . esc_html__( 'Status', 'underworld-empire' ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $ids as $id ) {
			$p = Character::find( (int) $id );
			if ( ! $p ) {
				continue;
			}
			$html .= '<tr><td>' . $p->link() . '</td><td>' . esc_html( $p->rank_name() ) . '</td>';
			if ( $with_location ) {
				$html .= '<td>' . ( $p->is_alive() ? ( $p->is_online() ? '<span class="dfmg-online">' . esc_html__( 'online', 'underworld-empire' ) . '</span>' : esc_html__( 'offline', 'underworld-empire' ) ) : '<span class="dfmg-dead">' . esc_html__( 'murdered', 'underworld-empire' ) . '</span>' ) . '</td>';
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
		$html .= '<input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="' . esc_attr__( 'Search for a player…', 'underworld-empire' ) . '">';
		$html .= '<button type="submit" class="dfmg-button">' . esc_html__( 'Search', 'underworld-empire' ) . '</button></form>';

		if ( '' !== $q ) {
			$ids   = DB::column( 'SELECT id FROM {characters} WHERE name LIKE %s ORDER BY status DESC, name ASC LIMIT 50', '%' . DB::wpdb()->esc_like( $q ) . '%' );
			$html .= '<h3>' . esc_html__( 'Search results', 'underworld-empire' ) . '</h3>' . $this->table( $ids, true );
		}

		$online = DB::column(
			'SELECT id FROM {characters} WHERE status = 1 AND last_active > %d ORDER BY last_active DESC LIMIT 200',
			time() - 60 * Settings::int( 'online_minutes', 15 )
		);
		$html  .= '<h3>' . esc_html__( 'Online now', 'underworld-empire' ) . ' (' . count( $online ) . ')</h3>' . $this->table( $online );
		return $html;
	}
}

return new Players();
