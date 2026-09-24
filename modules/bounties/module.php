<?php
/**
 * Module Name: Premies
 * Description: Zet een prijs op iemands hoofd. Wie het doelwit vermoordt, krijgt de premie. Doelwitten kunnen zichzelf vrijkopen.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Bounties extends Module {

	public function title(): string {
		return __( 'Premies', 'wp-maffia-game' );
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
				'label'   => __( 'Minimale premie', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 10000,
			),
			'bounty_buyoff_percent' => array(
				'label'       => __( 'Vrijkopen kost (% van de premies)', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 150,
			),
			'bounty_cancel_refund'  => array(
				'label'   => __( 'Terugbetaling bij intrekken (%)', 'wp-maffia-game' ),
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
			$killer->notify( sprintf( __( 'Je ontving een premie van %1$s voor de moord op %2$s.', 'wp-maffia-game' ), esc_html( Format::money( $total ) ), esc_html( $victim->name ) ) );
		}
	}

	public function menu( Character $c ): array {
		$on_me = (int) DB::value( 'SELECT COUNT(*) FROM {bounties} WHERE target_id = %d', $c->id() );
		return array(
			array(
				'label' => __( 'Premies', 'wp-maffia-game' ),
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
			$this->error( __( 'Deze speler bestaat niet of leeft niet meer.', 'wp-maffia-game' ) );
			return;
		}
		if ( $target->id() === $c->id() ) {
			$this->error( __( 'Een premie op jezelf? Liever niet.', 'wp-maffia-game' ) );
			return;
		}
		if ( $amount < $min ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'De minimale premie is %s.', 'wp-maffia-game' ), Format::money( $min ) ) );
			return;
		}
		if ( ! $c->spend( 'money', $amount ) ) {
			$this->error( __( 'Zoveel contant geld heb je niet.', 'wp-maffia-game' ) );
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
		$by = $anonymous ? esc_html__( 'Iemand', 'wp-maffia-game' ) : $c->link();
		/* translators: 1: player, 2: money */
		$target->notify( sprintf( __( '%1$s heeft een premie van %2$s op je hoofd gezet!', 'wp-maffia-game' ), $by, esc_html( Format::money( $amount ) ) ) );
		$c->log( 'bounty.place', true, $amount, $target->id() );
		/* translators: 1: money, 2: player */
		$this->success( sprintf( __( 'Er staat nu %1$s op het hoofd van %2$s.', 'wp-maffia-game' ), Format::money( $amount ), $target->name ) );
	}

	public function action_buyoff( Character $c, array $input ): void {
		$total = (int) DB::value( 'SELECT COALESCE(SUM(amount), 0) FROM {bounties} WHERE target_id = %d', $c->id() );
		if ( ! $total ) {
			$this->error( __( 'Er staat geen premie op jouw hoofd.', 'wp-maffia-game' ) );
			return;
		}
		$cost = (int) ceil( $total * (int) $this->setting( 'bounty_buyoff_percent' ) / 100 );
		if ( ! $c->spend( 'money', $cost ) ) {
			/* translators: %s: money */
			$this->error( sprintf( __( 'Vrijkopen kost %s contant.', 'wp-maffia-game' ), Format::money( $cost ) ) );
			return;
		}
		DB::delete( 'bounties', array( 'target_id' => $c->id() ) );
		$c->log( 'bounty.buyoff', true, $cost );
		$this->success( __( 'Je hebt alle premies op je hoofd afgekocht.', 'wp-maffia-game' ) );
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
			$this->success( sprintf( __( 'Premie ingetrokken. Je kreeg %s terug.', 'wp-maffia-game' ), Format::money( $refund ) ) );
		}
	}
}

return new Bounties();
