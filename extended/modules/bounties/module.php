<?php
/**
 * Module Name: Bounties
 * Description: Put a price on someone's head. Whoever murders the target collects the bounty. Targets can buy themselves free.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Bounties extends Module {

	public function title(): string {
		return __( 'Bounties', 'underworld-empire' );
	}

	public function schema(): array {
		return array(
			'bounties' => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				placed_by bigint(20) unsigned NOT NULL,
				target_id bigint(20) unsigned NOT NULL,
				amount bigint(20) NOT NULL DEFAULT 0,
				anonymous tinyint(1) NOT NULL DEFAULT 0,
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY target_id (target_id)',
		);
	}

	public function round_tables(): array {
		return array( 'bounties' );
	}

	public function settings_fields(): array {
		return array(
			'bounty_min'            => array(
				'label'   => __( 'Minimum bounty', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 10000,
			),
			'bounty_buyoff_percent' => array(
				'label'       => __( 'Buy-off cost (% of the bounties)', 'underworld-empire' ),
				'type'        => 'int',
				'default'     => 150,
			),
			'bounty_cancel_refund'  => array(
				'label'   => __( 'Refund when withdrawing (%)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 50,
			),
		);
	}

	public function boot(): void {
		add_action( 'dfmg_character_killed', array( $this, 'pay_out' ), 10, 2 );
	}

	public function pay_out( Character $victim, ?Character $killer ): void {
		$total = (int) DB::value( 'SELECT COALESCE(SUM(amount), 0) FROM {bounties} WHERE target_id = %d', $victim->id() );
		DB::delete( 'bounties', array( 'target_id' => $victim->id() ) );
		if ( $killer && $total > 0 ) {
			$killer->add( 'money', $total );
			/* translators: 1: money, 2: player */
			$killer->notify( sprintf( __( 'You received a bounty of %1$s for the murder of %2$s.', 'underworld-empire' ), esc_html( Format::money( $total ) ), esc_html( $victim->name ) ) );
		}
	}

	public function menu( Character $c ): array {
		$on_me = (int) DB::value( 'SELECT COUNT(*) FROM {bounties} WHERE target_id = %d', $c->id() );
		return array(
			array(
				'label' => __( 'Bounties', 'underworld-empire' ),
				'group' => 'murder',
				'order' => 30,
				'badge' => $on_me ? '!' : '',
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$list = DB::results(
			'SELECT target_id, SUM(amount) AS total, COUNT(*) AS cnt FROM {bounties} GROUP BY target_id ORDER BY total DESC LIMIT 50'
		);
		$mine = DB::results( 'SELECT * FROM {bounties} WHERE placed_by = %d ORDER BY created_at DESC', $c->id() );
		$on_me = (int) DB::value( 'SELECT COALESCE(SUM(amount), 0) FROM {bounties} WHERE target_id = %d', $c->id() );
		return $this->view(
			'bounties',
			array(
				'c'       => $c,
				'list'    => $list,
				'mine'    => $mine,
				'on_me'   => $on_me,
				'buyoff'  => (int) ceil( $on_me * (int) $this->setting( 'bounty_buyoff_percent' ) / 100 ),
				'min'     => (int) $this->setting( 'bounty_min' ),
				'refund'  => (int) $this->setting( 'bounty_cancel_refund' ),
				'target'  => $query['target'] ?? '',
			)
		);
	}

	public function action_place( Character $c, array $input ): void {
		$target = Character::find_by_name( sanitize_text_field( $input['target'] ?? '' ) );
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		$min    = (int) $this->setting( 'bounty_min' );
		if ( ! $target || ! $target->is_alive() ) {
			$this->error( __( 'This player doesn\'t exist or is no longer alive.', 'underworld-empire' ) );
			return;
		}
		if ( $target->id() === $c->id() ) {
			$this->error( __( 'A bounty on yourself? Better not.', 'underworld-empire' ) );
			return;
		}
		if ( $amount < $min ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'The minimum bounty is %s.', 'underworld-empire' ), Format::money( $min ) ) );
			return;
		}
		if ( ! $c->spend( 'money', $amount ) ) {
			$this->error( __( 'You don\'t have that much cash.', 'underworld-empire' ) );
			return;
		}
		$anonymous = ! empty( $input['anonymous'] );
		DB::insert(
			'bounties',
			array(
				'placed_by'  => $c->id(),
				'target_id'  => $target->id(),
				'amount'     => $amount,
				'anonymous'  => $anonymous ? 1 : 0,
				'created_at' => time(),
			)
		);
		$by = $anonymous ? esc_html__( 'Someone', 'underworld-empire' ) : $c->link();
		/* translators: 1: player, 2: money */
		$target->notify( sprintf( __( '%1$s put a bounty of %2$s on your head!', 'underworld-empire' ), $by, esc_html( Format::money( $amount ) ) ) );
		$c->log( 'bounty.place', true, $amount, $target->id() );
		/* translators: 1: money, 2: player */
		$this->success( sprintf( __( 'There is now %1$s on the head of %2$s.', 'underworld-empire' ), Format::money( $amount ), $target->name ) );
	}

	public function action_buyoff( Character $c, array $input ): void {
		$total = (int) DB::value( 'SELECT COALESCE(SUM(amount), 0) FROM {bounties} WHERE target_id = %d', $c->id() );
		if ( ! $total ) {
			$this->error( __( 'There is no bounty on your head.', 'underworld-empire' ) );
			return;
		}
		$cost = (int) ceil( $total * (int) $this->setting( 'bounty_buyoff_percent' ) / 100 );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Buying yourself free costs %s in cash.', 'underworld-empire' ), Format::money( $cost ) ) );
			return;
		}
		DB::delete( 'bounties', array( 'target_id' => $c->id() ) );
		$c->log( 'bounty.buyoff', true, $cost );
		$this->success( __( 'You bought off all bounties on your head.', 'underworld-empire' ) );
	}

	public function action_cancel( Character $c, array $input ): void {
		$row = DB::row( 'SELECT * FROM {bounties} WHERE id = %d AND placed_by = %d', absint( $input['bounty'] ?? 0 ), $c->id() );
		if ( ! $row ) {
			return;
		}
		if ( DB::delete( 'bounties', array( 'id' => $row['id'] ) ) ) {
			$refund = (int) floor( (int) $row['amount'] * (int) $this->setting( 'bounty_cancel_refund' ) / 100 );
			$c->add( 'money', $refund );
			/* translators: %s: money */
			$this->success( sprintf( __( 'Bounty withdrawn. You got %s back.', 'underworld-empire' ), Format::money( $refund ) ) );
		}
	}
}

return new Bounties();
