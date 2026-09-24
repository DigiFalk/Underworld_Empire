<?php
/**
 * Module Name: Berichten
 * Description: Privéberichten tussen spelers met inbox, verzonden items en antwoorden.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class Messages extends Module {

	const PER_PAGE = 20;

	public function title(): string {
		return __( 'Berichten', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function schema(): array {
		return array(
			'messages' => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				sender_id bigint(20) unsigned NOT NULL,
				recipient_id bigint(20) unsigned NOT NULL,
				subject varchar(120) NOT NULL DEFAULT '',
				body text NOT NULL,
				parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at int(11) NOT NULL DEFAULT 0,
				is_read tinyint(1) NOT NULL DEFAULT 0,
				sender_deleted tinyint(1) NOT NULL DEFAULT 0,
				recipient_deleted tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY recipient (recipient_id,recipient_deleted,is_read),
				KEY sender (sender_id,sender_deleted)",
		);
	}

	public function round_tables(): array {
		return array( 'messages' );
	}

	public function settings_fields(): array {
		return array(
			'messages_cooldown' => array(
				'label'   => __( 'Wachttijd tussen berichten (sec)', 'wp-maffia-game' ),
				'type'    => 'int',
				'default' => 10,
			),
		);
	}

	public function boot(): void {
		add_filter(
			'dfmg_profile_actions',
			function ( $actions, Character $target ) {
				$actions[] = '<a class="dfmg-button dfmg-button--ghost" href="' . esc_url( $this->url( array( 'view' => 'compose', 'to' => $target->name ) ) ) . '">' . esc_html__( 'Bericht sturen', 'wp-maffia-game' ) . '</a>';
				return $actions;
			},
			10,
			2
		);
	}

	private static function unread( Character $c ): int {
		return (int) DB::value( 'SELECT COUNT(*) FROM {messages} WHERE recipient_id = %d AND is_read = 0 AND recipient_deleted = 0', $c->id() );
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Berichten', 'wp-maffia-game' ),
				'group' => 'general',
				'order' => 20,
				'badge' => self::unread( $c ) ?: '',
			),
		);
	}

	public function render( Character $c, array $query ): string {
		$view = $query['view'] ?? 'inbox';

		if ( 'read' === $view ) {
			$message = DB::row(
				'SELECT * FROM {messages} WHERE id = %d AND ((recipient_id = %d AND recipient_deleted = 0) OR (sender_id = %d AND sender_deleted = 0))',
				absint( $query['id'] ?? 0 ),
				$c->id(),
				$c->id()
			);
			if ( $message ) {
				if ( (int) $message['recipient_id'] === $c->id() && ! (int) $message['is_read'] ) {
					DB::update( 'messages', array( 'is_read' => 1 ), array( 'id' => $message['id'] ) );
				}
				return $this->view(
					'read',
					array(
						'c'       => $c,
						'message' => $message,
					)
				);
			}
		}

		if ( 'compose' === $view ) {
			$reply = DB::row( 'SELECT * FROM {messages} WHERE id = %d AND recipient_id = %d', absint( $query['reply'] ?? 0 ), $c->id() );
			return $this->view(
				'compose',
				array(
					'c'       => $c,
					'to'      => $reply ? ( Character::find( (int) $reply['sender_id'] )->name ?? '' ) : ( $query['to'] ?? '' ),
					'subject' => $reply ? ( 0 === strpos( $reply['subject'], 'Re: ' ) ? $reply['subject'] : 'Re: ' . $reply['subject'] ) : '',
					'reply'   => $reply ? (int) $reply['id'] : 0,
				)
			);
		}

		$sent  = 'sent' === $view;
		$paged = max( 1, absint( $query['paged'] ?? 1 ) );
		$where = $sent ? 'sender_id = %d AND sender_deleted = 0' : 'recipient_id = %d AND recipient_deleted = 0';
		$total = (int) DB::value( "SELECT COUNT(*) FROM {messages} WHERE $where", $c->id() );
		$rows  = DB::results(
			"SELECT * FROM {messages} WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d",
			$c->id(),
			self::PER_PAGE,
			( $paged - 1 ) * self::PER_PAGE
		);
		return $this->view(
			'list',
			array(
				'c'     => $c,
				'rows'  => $rows,
				'sent'  => $sent,
				'paged' => $paged,
				'pages' => (int) ceil( $total / self::PER_PAGE ),
			)
		);
	}

	/**
	 * @return array
	 */
	public function action_send( Character $c, array $input ) {
		$to      = Character::find_by_name( sanitize_text_field( $input['to'] ?? '' ) );
		$subject = mb_substr( trim( sanitize_text_field( $input['subject'] ?? '' ) ), 0, 120 );
		$body    = trim( sanitize_textarea_field( $input['body'] ?? '' ) );
		$back    = array(
			'view' => 'compose',
			'to'   => $input['to'] ?? '',
		);
		if ( ! $to || ! $to->is_alive() ) {
			$this->error( __( 'Deze speler bestaat niet (meer).', 'wp-maffia-game' ) );
			return $back;
		}
		if ( '' === $body ) {
			$this->error( __( 'Je bericht is leeg.', 'wp-maffia-game' ) );
			return $back;
		}
		if ( ! $c->claim_cooldown( 'message', (int) $this->setting( 'messages_cooldown' ) ) ) {
			$this->error( __( 'Je stuurt te snel berichten. Wacht even.', 'wp-maffia-game' ) );
			return $back;
		}
		DB::insert(
			'messages',
			array(
				'sender_id'    => $c->id(),
				'recipient_id' => $to->id(),
				'subject'      => $subject ?: __( '(geen onderwerp)', 'wp-maffia-game' ),
				'body'         => mb_substr( $body, 0, 10000 ),
				'parent_id'    => absint( $input['reply'] ?? 0 ),
				'created_at'   => time(),
			)
		);
		do_action( 'dfmg_message_sent', $c, $to );
		/* translators: %s: player */
		$this->success( sprintf( __( 'Bericht verstuurd naar %s.', 'wp-maffia-game' ), $to->name ) );
		return array( 'view' => 'sent' );
	}

	public function action_delete( Character $c, array $input ): array {
		$ids = array_map( 'absint', (array) ( $input['ids'] ?? array( $input['id'] ?? 0 ) ) );
		foreach ( $ids as $id ) {
			DB::query( 'UPDATE {messages} SET recipient_deleted = 1 WHERE id = %d AND recipient_id = %d', $id, $c->id() );
			DB::query( 'UPDATE {messages} SET sender_deleted = 1 WHERE id = %d AND sender_id = %d', $id, $c->id() );
		}
		DB::query( 'DELETE FROM {messages} WHERE sender_deleted = 1 AND recipient_deleted = 1' );
		return ! empty( $input['sent'] ) ? array( 'view' => 'sent' ) : array();
	}

	public function action_read_all( Character $c, array $input ): void {
		DB::query( 'UPDATE {messages} SET is_read = 1 WHERE recipient_id = %d', $c->id() );
	}
}

return new Messages();
