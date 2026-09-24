<?php
/**
 * Module Name: Notifications
 * Description: Overview of everything that happens to your character: promotions, attacks, transfers and more.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Notifications extends Module {

	const PER_PAGE = 25;

	public function title(): string {
		return __( 'Notifications', 'underworld-empire' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function menu( Character $c ): array {
		$unread = (int) DB::value( 'SELECT COUNT(*) FROM {notifications} WHERE character_id = %d AND is_read = 0', $c->id() );
		return array(
			array(
				'label' => __( 'Notifications', 'underworld-empire' ),
				'group' => 'general',
				'order' => 10,
				'badge' => $unread ?: '',
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$paged = max( 1, absint( $query['paged'] ?? 1 ) );
		$total = (int) DB::value( 'SELECT COUNT(*) FROM {notifications} WHERE character_id = %d', $c->id() );
		$rows  = DB::results(
			'SELECT * FROM {notifications} WHERE character_id = %d ORDER BY id DESC LIMIT %d OFFSET %d',
			$c->id(),
			self::PER_PAGE,
			( $paged - 1 ) * self::PER_PAGE
		);
		DB::query( 'UPDATE {notifications} SET is_read = 1 WHERE character_id = %d AND is_read = 0', $c->id() );

		if ( ! $rows ) {
			return UI::empty_state( __( 'You have no notifications.', 'underworld-empire' ) );
		}
		$html = '<ul class="dfmg-list dfmg-notifications">';
		foreach ( $rows as $row ) {
			$html .= '<li class="' . ( $row['is_read'] ? '' : 'is-unread' ) . '">' . wp_kses_post( $row['message'] )
				. ' <small>' . esc_html( Format::ago( (int) $row['created_at'] ) ) . '</small></li>';
		}
		$html .= '</ul>';
		$html .= UI::pager( $this->id(), array(), $paged, (int) ceil( $total / self::PER_PAGE ) );
		$html .= $this->button( 'clear', __( 'Delete all notifications', 'underworld-empire' ), array(), 'dfmg-button dfmg-button--ghost dfmg-button--small' );
		return $html;
	}

	public function action_clear( Character $c, array $input ): void {
		DB::delete( 'notifications', array( 'character_id' => $c->id() ) );
	}
}

return new Notifications();
