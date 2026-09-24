<?php
/**
 * Module Name: Meldingen
 * Description: Overzicht van alle gebeurtenissen rond je personage: promoties, aanslagen, overboekingen en meer.
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

final class Notifications extends Module {

	const PER_PAGE = 25;

	public function title(): string {
		return __( 'Meldingen', 'wp-maffia-game' );
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
				'label' => __( 'Meldingen', 'wp-maffia-game' ),
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
			return UI::empty_state( __( 'Je hebt geen meldingen.', 'wp-maffia-game' ) );
		}
		$html = '<ul class="dfmg-list dfmg-notifications">';
		foreach ( $rows as $row ) {
			$html .= '<li class="' . ( $row['is_read'] ? '' : 'is-unread' ) . '">' . wp_kses_post( $row['message'] )
				. ' <small>' . esc_html( Format::ago( (int) $row['created_at'] ) ) . '</small></li>';
		}
		$html .= '</ul>';
		$html .= UI::pager( $this->id(), array(), $paged, (int) ceil( $total / self::PER_PAGE ) );
		$html .= $this->button( 'clear', __( 'Alle meldingen verwijderen', 'wp-maffia-game' ), array(), 'dfmg-button dfmg-button--ghost dfmg-button--small' );
		return $html;
	}

	public function action_clear( Character $c, array $input ): void {
		DB::delete( 'notifications', array( 'character_id' => $c->id() ) );
	}
}

return new Notifications();
