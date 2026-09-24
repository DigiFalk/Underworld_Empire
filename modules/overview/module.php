<?php
/**
 * Module Name: Overzicht
 * Description: Startpagina van de speler met status, timers en laatste meldingen.
 * Version: 1.0.0
 * Author: DigiFalk
 * Required: yes
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Frontend\Game;
use DigiFalk\MaffiaGame\Module\Module;
use DigiFalk\MaffiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class Overview extends Module {

	public function title(): string {
		return __( 'Overzicht', 'wp-maffia-game' );
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
				'label' => __( 'Overzicht', 'wp-maffia-game' ),
				'group' => 'general',
				'order' => 1,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$timers = array();
		foreach ( Game::menu( $c ) as $group ) {
			foreach ( $group['items'] as $item ) {
				if ( ! empty( $item['timer'] ) ) {
					$timers[ $item['timer'] ] = array(
						'label'   => $item['label'],
						'url'     => $item['url'],
						'expires' => $c->timer( $item['timer'] ),
					);
				}
			}
		}
		$timers = apply_filters( 'dfmg_overview_timers', $timers, $c );

		$notifications = DB::results(
			'SELECT * FROM {notifications} WHERE character_id = %d ORDER BY id DESC LIMIT 5',
			$c->id()
		);

		return $this->view(
			'overview',
			array(
				'c'             => $c,
				'next'          => Ranks::next( (int) $c->rank_id ),
				'timers'        => $timers,
				'notifications' => $notifications,
				'panels'        => apply_filters( 'dfmg_overview_panels', array(), $c ),
			)
		);
	}
}

return new Overview();
