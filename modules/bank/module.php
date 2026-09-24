<?php
/**
 * Module Name: Bank
 * Description: Keep money safe in the bank (for a laundering fee), withdraw it or transfer money to other players.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Bank extends Module {

	public function title(): string {
		return __( 'Bank', 'wp-maffia-game' );
	}

	public function settings_fields(): array {
		return array(
			'bank_tax'          => array(
				'label'       => __( 'Laundering fee on deposits (%)', 'wp-maffia-game' ),
				'type'        => 'int',
				'default'     => 10,
			),
			'bank_transfer_fee' => array(
				'label'   => __( 'Transfer fee (%)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 0,
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Bank', 'wp-maffia-game' ),
				'group' => 'money',
				'order' => 10,
			),
		);
	}

	public function render( Character $c, array $query ): string {
		return $this->view(
			'bank',
			array(
				'c'   => $c,
				'tax' => (int) $this->setting( 'bank_tax' ),
				'fee' => (int) $this->setting( 'bank_transfer_fee' ),
			)
		);
	}

	public function action_deposit( Character $c, array $input ): void {
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		if ( ! empty( $input['all'] ) ) {
			$amount = (int) $c->money;
		}
		if ( $amount <= 0 ) {
			$this->error( __( 'Enter an amount.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'money', $amount ) ) {
			$this->error( __( 'You don\'t have that much cash.', 'wp-maffia-game' ) );
			return;
		}
		$tax      = max( 0, min( 100, (int) $this->setting( 'bank_tax' ) ) );
		$credited = (int) floor( $amount * ( 100 - $tax ) / 100 );
		$c->add( 'bank', $credited );
		$c->log( 'bank.deposit', true, $amount );
		/* translators: 1: amount, 2: credited */
		$this->success( sprintf( __( 'You deposited %1$s. After laundering fees %2$s was added to your account.', 'wp-maffia-game' ), Format::money( $amount ), Format::money( $credited ) ) );
	}

	public function action_withdraw( Character $c, array $input ): void {
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		if ( ! empty( $input['all'] ) ) {
			$amount = (int) $c->bank;
		}
		if ( $amount <= 0 ) {
			$this->error( __( 'Enter an amount.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'bank', $amount ) ) {
			$this->error( __( 'You don\'t have that much in your account.', 'wp-maffia-game' ) );
			return;
		}
		$c->add( 'money', $amount );
		$c->log( 'bank.withdraw', true, $amount );
		/* translators: %s: amount */
		$this->success( sprintf( __( 'You withdrew %s.', 'wp-maffia-game' ), Format::money( $amount ) ) );
	}

	public function action_transfer( Character $c, array $input ): void {
		$amount = Format::parse_amount( $input['amount'] ?? 0 );
		$to     = Character::find_by_name( sanitize_text_field( $input['to'] ?? '' ) );
		if ( ! $to || ! $to->is_alive() ) {
			$this->error( __( 'This player doesn\'t exist (anymore).', 'wp-maffia-game' ) );
			return;
		}
		if ( $to->id() === $c->id() ) {
			$this->error( __( 'Sending money to yourself makes little sense.', 'wp-maffia-game' ) );
			return;
		}
		if ( $amount <= 0 ) {
			$this->error( __( 'Enter an amount.', 'wp-maffia-game' ) );
			return;
		}
		if ( ! $c->spend( 'bank', $amount ) ) {
			$this->error( __( 'You don\'t have that much in your account.', 'wp-maffia-game' ) );
			return;
		}
		$fee      = max( 0, min( 100, (int) $this->setting( 'bank_transfer_fee' ) ) );
		$received = (int) floor( $amount * ( 100 - $fee ) / 100 );
		$to->add( 'bank', $received );
		/* translators: 1: player, 2: amount */
		$to->notify( sprintf( __( '%1$s transferred %2$s to your bank account.', 'wp-maffia-game' ), $c->link(), esc_html( Format::money( $received ) ) ) );
		$c->log( 'bank.transfer', true, $amount, $to->id() );
		/* translators: 1: amount, 2: player */
		$this->success( sprintf( __( 'You transferred %1$s to %2$s.', 'wp-maffia-game' ), Format::money( $received ), $to->name ) );
	}
}

return new Bank();
