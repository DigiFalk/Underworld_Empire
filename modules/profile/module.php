<?php
/**
 * Module Name: Profile
 * Description: Public player profiles and editing your own profile text.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame\Modules;

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\DB;
use DigiFalk\MafiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Profile extends Module {

	public function title(): string {
		return __( 'Profile', 'wp-mafia-game' );
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
				'label' => __( 'My profile', 'wp-mafia-game' ),
				'group' => 'general',
				'order' => 30,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$target = isset( $query['name'] ) ? Character::find_by_name( $query['name'] ) : $c;
		if ( ! $target ) {
			$this->error( __( 'This player doesn\'t exist.', 'wp-mafia-game' ) );
			$target = $c;
		}
		$fields = array(
			__( 'Rank', 'wp-mafia-game' )    => esc_html( $target->rank_name() ),
			__( 'Wealth', 'wp-mafia-game' ) => esc_html( $target->wealth_title() ),
			__( 'Status', 'wp-mafia-game' )  => $target->is_alive()
				? ( $target->is_online() ? '<span class="dfmg-online">' . esc_html__( 'Online', 'wp-mafia-game' ) . '</span>' : esc_html__( 'Alive', 'wp-mafia-game' ) )
				: '<span class="dfmg-dead">' . esc_html__( 'Murdered', 'wp-mafia-game' ) . '</span>',
			__( 'Murders', 'wp-mafia-game' ) => (int) DB::value( "SELECT COUNT(*) FROM {activity} WHERE character_id = %d AND action = 'murder' AND success = 1", $target->id() ),
			__( 'Started', 'wp-mafia-game' ) => esc_html( \DigiFalk\MafiaGame\Format::date( (int) $target->created_at ) ),
		);
		if ( ! $target->is_alive() && $target->shot_by ) {
			$fields[ __( 'Murdered by', 'wp-mafia-game' ) ] = Character::link_by_id( (int) $target->shot_by );
		}
		$own = $target->id() === $c->id();
		return $this->view(
			'profile',
			array(
				'c'       => $c,
				'target'  => $target,
				'own'     => $own,
				'fields'  => apply_filters( 'dfmg_profile_fields', $fields, $target, $c ),
				'actions' => ( $own || ! $target->is_alive() ) ? array() : apply_filters( 'dfmg_profile_actions', array(), $target, $c ),
				'avatar'  => get_avatar( (int) $target->user_id, 96 ),
			)
		);
	}

	public function action_bio( Character $c, array $input ): void {
		$c->set( 'bio', wp_kses_post( mb_substr( (string) ( $input['bio'] ?? '' ), 0, 5000 ) ) );
		$this->success( __( 'Profile saved.', 'wp-mafia-game' ) );
	}
}

return new Profile();
