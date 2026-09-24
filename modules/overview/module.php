<?php
/**
 * Module Name: Overview
 * Description: Player home page with status, timers and latest notifications.
 * Version: 1.0.0
 * Author: DigiFalk
 * Required: yes
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Frontend\Game;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Ranks;

defined( 'ABSPATH' ) || exit;

final class Overview extends Module {

	public function title(): string {
		return __( 'Overview', 'underworld-empire' );
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
				'label' => __( 'Overview', 'underworld-empire' ),
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
