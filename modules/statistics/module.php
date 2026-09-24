<?php
/**
 * Module Name: Statistics
 * Description: Numbers about the whole game world.
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
use DigiFalk\MaffiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class Statistics extends Module {

	public function title(): string {
		return __( 'Statistics', 'wp-maffia-game' );
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
				'label' => __( 'Statistics', 'wp-maffia-game' ),
				'group' => 'community',
				'order' => 40,
			),
		);
	}

	private function count_action( string $action, bool $success = true ): int {
		return (int) DB::value( 'SELECT COUNT(*) FROM {activity} WHERE action = %s AND success = %d', $action, $success ? 1 : 0 );
	}

	public function render( Character $c, array $query ): string {
		$stats = get_transient( 'dfmg_statistics' );
		if ( ! is_array( $stats ) ) {
			$stats = array(
				__( 'Living players', 'wp-maffia-game' )     => Format::number( (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1' ) ),
				__( 'Murdered players', 'wp-maffia-game' )   => Format::number( (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 0' ) ),
				__( 'Money in circulation', 'wp-maffia-game' )      => Format::money( (int) DB::value( 'SELECT COALESCE(SUM(money + bank), 0) FROM {characters} WHERE status = 1' ) ),
				__( 'Bullets in circulation', 'wp-maffia-game' )    => Format::number( (int) DB::value( 'SELECT COALESCE(SUM(bullets), 0) FROM {characters} WHERE status = 1' ) ),
				__( 'Successful crimes', 'wp-maffia-game' )  => Format::number( $this->count_action( 'crimes' ) ),
				__( 'Failed crimes', 'wp-maffia-game' )   => Format::number( $this->count_action( 'crimes', false ) ),
				__( 'Stolen cars', 'wp-maffia-game' )    => Format::number( $this->count_action( 'car-theft' ) ),
				__( 'Breakouts', 'wp-maffia-game' )           => Format::number( $this->count_action( 'jail.bust' ) ),
				__( 'Murders', 'wp-maffia-game' )             => Format::number( $this->count_action( 'murder' ) ),
			);
			$stats = apply_filters( 'dfmg_statistics', $stats );
			set_transient( 'dfmg_statistics', $stats, 5 * MINUTE_IN_SECONDS );
		}

		$html = '<div class="dfmg-grid dfmg-grid--3">';
		foreach ( $stats as $label => $value ) {
			$html .= '<div class="dfmg-card dfmg-stat-card"><strong>' . esc_html( (string) $value ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
		}
		$html .= '</div>';

		$html .= '<div class="dfmg-grid dfmg-grid--2"><section class="dfmg-card"><h3>' . esc_html__( 'Players per city', 'wp-maffia-game' ) . '</h3><table class="dfmg-table">';
		foreach ( Locations::all() as $loc ) {
			$n     = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1 AND location_id = %d', $loc['id'] );
			$html .= '<tr><td>' . esc_html( $loc['name'] ) . '</td><td>' . esc_html( Format::number( $n ) ) . '</td></tr>';
		}
		$html .= '</table></section><section class="dfmg-card"><h3>' . esc_html__( 'Players per rank', 'wp-maffia-game' ) . '</h3><table class="dfmg-table">';
		foreach ( Ranks::all() as $rank ) {
			$n     = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1 AND rank_id = %d', $rank['id'] );
			$html .= '<tr><td>' . esc_html( $rank['name'] ) . '</td><td>' . esc_html( Format::number( $n ) ) . '</td></tr>';
		}
		return $html . '</table></section></div>';
	}
}

return new Statistics();
