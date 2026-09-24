<?php
/**
 * Module Name: Profile
 * Description: Public player profiles, your own profile text and an uploaded avatar (saved as WebP and used as the WordPress avatar).
 * Version: 1.1.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Avatar;
use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Profile extends Module {

	public function title(): string {
		return __( 'Profile', 'underworld-empire' );
	}

	public function settings_fields(): array {
		return array(
			'avatar_upload' => array(
				'label'       => __( 'Players can upload an avatar', 'underworld-empire' ),
				'type'        => 'checkbox',
				'default'     => 1,
				'description' => __( 'Saved as a square .webp image and used as the WordPress avatar of the user on the whole site.', 'underworld-empire' ),
			),
			'avatar_max_kb' => array(
				'label'   => __( 'Maximum upload size (KB)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 2048,
			),
			'avatar_size'   => array(
				'label'   => __( 'Avatar size (pixels)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 256,
			),
		);
	}

	private function uploads_enabled(): bool {
		return (bool) $this->setting( 'avatar_upload' );
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
				'label' => __( 'My profile', 'underworld-empire' ),
				'group' => 'general',
				'order' => 30,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$target = isset( $query['name'] ) ? Character::find_by_name( $query['name'] ) : $c;
		if ( ! $target ) {
			$this->error( __( 'This player doesn\'t exist.', 'underworld-empire' ) );
			$target = $c;
		}
		$fields = array(
			__( 'Rank', 'underworld-empire' )    => esc_html( $target->rank_name() ),
			__( 'Wealth', 'underworld-empire' ) => esc_html( $target->wealth_title() ),
			__( 'Status', 'underworld-empire' )  => $target->is_alive()
				? ( $target->is_online() ? '<span class="dfmg-online">' . esc_html__( 'Online', 'underworld-empire' ) . '</span>' : esc_html__( 'Alive', 'underworld-empire' ) )
				: '<span class="dfmg-dead">' . esc_html__( 'Murdered', 'underworld-empire' ) . '</span>',
			__( 'Murders', 'underworld-empire' ) => (int) DB::value( "SELECT COUNT(*) FROM {activity} WHERE character_id = %d AND action = 'murder' AND success = 1", $target->id() ),
			__( 'Started', 'underworld-empire' ) => esc_html( \DigiFalk\UnderworldEmpire\Format::date( (int) $target->created_at ) ),
		);
		if ( ! $target->is_alive() && $target->shot_by ) {
			$fields[ __( 'Murdered by', 'underworld-empire' ) ] = Character::link_by_id( (int) $target->shot_by );
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
				'upload'  => $own && $this->uploads_enabled(),
				'has_own' => $own && '' !== Avatar::url( (int) $c->user_id ),
				'max_kb'  => max( 1, (int) $this->setting( 'avatar_max_kb' ) ),
			)
		);
	}

	public function action_bio( Character $c, array $input ): void {
		$c->set( 'bio', wp_kses_post( mb_substr( (string) ( $input['bio'] ?? '' ), 0, 5000 ) ) );
		$this->success( __( 'Profile saved.', 'underworld-empire' ) );
	}

	public function action_avatar( Character $c, array $input ): void {
		if ( ! $this->uploads_enabled() ) {
			$this->error( __( 'Uploading an avatar is switched off.', 'underworld-empire' ) );
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- nonce checked by Game::handle_action, file validated by Avatar::save.
		$file   = isset( $_FILES['avatar'] ) && is_array( $_FILES['avatar'] ) ? $_FILES['avatar'] : array();
		$result = Avatar::save( (int) $c->user_id, $file, max( 32, min( 1024, (int) $this->setting( 'avatar_size' ) ) ), max( 1, (int) $this->setting( 'avatar_max_kb' ) ) );
		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
			return;
		}
		$this->success( __( 'Your new avatar is saved. It is also your profile picture on the rest of the site.', 'underworld-empire' ) );
	}

	public function action_avatar_remove( Character $c, array $input ): void {
		Avatar::delete( (int) $c->user_id );
		$this->success( __( 'Your avatar was removed.', 'underworld-empire' ) );
	}
}

return new Profile();
