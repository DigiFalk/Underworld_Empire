<?php
/**
 * Module Name: Forum
 * Description: A player forum with boards, topics, pinned and locked topics. Moderated by administrators.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Module\Module;
use DigiFalk\UnderworldEmpire\Ranks;

defined( 'ABSPATH' ) || exit;

final class Forum extends Module {

	const PER_PAGE = 20;

	public function title(): string {
		return __( 'Forum', 'underworld-empire' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function schema(): array {
		return array(
			'forum_boards' => "
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(120) NOT NULL DEFAULT '',
				description varchar(255) NOT NULL DEFAULT '',
				sort int(11) NOT NULL DEFAULT 0,
				min_rank int(11) NOT NULL DEFAULT 1,
				staff_only tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)",
			'forum_topics' => "
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				board_id int(11) NOT NULL,
				character_id bigint(20) unsigned NOT NULL,
				title varchar(150) NOT NULL DEFAULT '',
				sticky tinyint(1) NOT NULL DEFAULT 0,
				locked tinyint(1) NOT NULL DEFAULT 0,
				created_at int(11) NOT NULL DEFAULT 0,
				last_post_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY board (board_id,sticky,last_post_at)",
			'forum_posts'  => '
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				topic_id bigint(20) unsigned NOT NULL,
				character_id bigint(20) unsigned NOT NULL,
				body text NOT NULL,
				created_at int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY topic_id (topic_id)',
		);
	}

	public function seed(): void {
		$boards = array(
			array( 'General', 'Everything about the game.', 1, 0 ),
			array( 'Trade', 'Buying, selling and swapping.', 2, 0 ),
			array( 'Families', 'Recruiting and waging war.', 3, 0 ),
			array( 'Announcements', 'News from the staff.', 0, 1 ),
		);
		foreach ( $boards as $b ) {
			DB::insert(
				'forum_boards',
				array(
					'name'        => $b[0],
					'description' => $b[1],
					'sort'        => $b[2],
					'min_rank'    => 1,
					'staff_only'  => $b[3],
				)
			);
		}
	}

	public function settings_fields(): array {
		return array(
			'forum_cooldown'     => array(
				'label'   => __( 'Cooldown between posts (sec)', 'underworld-empire' ),
				'type'    => 'int',
				'default' => 15,
			),
			'forum_round_reset' => array(
				'label'       => __( 'Clear forum when a new round starts', 'underworld-empire' ),
				'type'        => 'checkbox',
				'default'     => 0,
			),
		);
	}

	public function round_tables(): array {
		return $this->setting( 'forum_round_reset' ) ? array( 'forum_topics', 'forum_posts' ) : array();
	}

	public function admin_tables(): array {
		return array(
			'forum_boards' => array(
				'label'   => __( 'Forum boards', 'underworld-empire' ),
				'table'   => 'forum_boards',
				'order'   => 'sort ASC',
				'columns' => array(
					'name'        => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'description' => array( 'label' => __( 'Description', 'underworld-empire' ) ),
					'sort'        => array( 'label' => __( 'Order', 'underworld-empire' ), 'type' => 'int' ),
					'min_rank'    => array( 'label' => __( 'From rank (level)', 'underworld-empire' ), 'type' => 'int', 'default' => 1 ),
					'staff_only'  => array( 'label' => __( 'Only administrators may post', 'underworld-empire' ), 'type' => 'checkbox' ),
				),
			),
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'Forum', 'underworld-empire' ),
				'group' => 'community',
				'order' => 10,
			),
		);
	}

	public static function is_staff(): bool {
		return current_user_can( 'dfmg_manage' ) || current_user_can( 'moderate_comments' );
	}

	private function boards( Character $c ): array {
		return DB::results( 'SELECT * FROM {forum_boards} WHERE min_rank <= %d ORDER BY sort ASC, id ASC', Ranks::level( (int) $c->rank_id ) );
	}

	private function board( Character $c, int $id ): ?array {
		foreach ( $this->boards( $c ) as $board ) {
			if ( (int) $board['id'] === $id ) {
				return $board;
			}
		}
		return null;
	}

	private function can_post( array $board ): bool {
		return ! (int) $board['staff_only'] || self::is_staff();
	}

	public function render( Character $c, array $query ): string {
		$topic_id = absint( $query['topic'] ?? 0 );
		if ( $topic_id ) {
			$topic = DB::row( 'SELECT * FROM {forum_topics} WHERE id = %d', $topic_id );
			$board = $topic ? $this->board( $c, (int) $topic['board_id'] ) : null;
			if ( $topic && $board ) {
				$paged = max( 1, absint( $query['paged'] ?? 1 ) );
				$total = (int) DB::value( 'SELECT COUNT(*) FROM {forum_posts} WHERE topic_id = %d', $topic_id );
				return $this->view(
					'topic',
					array(
						'c'        => $c,
						'board'    => $board,
						'topic'    => $topic,
						'posts'    => DB::results( 'SELECT * FROM {forum_posts} WHERE topic_id = %d ORDER BY id ASC LIMIT %d OFFSET %d', $topic_id, self::PER_PAGE, ( $paged - 1 ) * self::PER_PAGE ),
						'paged'    => $paged,
						'pages'    => (int) ceil( $total / self::PER_PAGE ),
						'can_post' => ! (int) $topic['locked'] || self::is_staff(),
						'staff'    => self::is_staff(),
					)
				);
			}
		}

		$board_id = absint( $query['board'] ?? 0 );
		$board    = $board_id ? $this->board( $c, $board_id ) : null;
		if ( $board ) {
			$paged = max( 1, absint( $query['paged'] ?? 1 ) );
			$total = (int) DB::value( 'SELECT COUNT(*) FROM {forum_topics} WHERE board_id = %d', $board_id );
			return $this->view(
				'board',
				array(
					'c'        => $c,
					'board'    => $board,
					'topics'   => DB::results(
						'SELECT t.*, (SELECT COUNT(*) FROM {forum_posts} p WHERE p.topic_id = t.id) AS posts FROM {forum_topics} t
						 WHERE t.board_id = %d ORDER BY t.sticky DESC, t.last_post_at DESC LIMIT %d OFFSET %d',
						$board_id,
						self::PER_PAGE,
						( $paged - 1 ) * self::PER_PAGE
					),
					'paged'    => $paged,
					'pages'    => (int) ceil( $total / self::PER_PAGE ),
					'can_post' => $this->can_post( $board ),
				)
			);
		}

		$boards = $this->boards( $c );
		foreach ( $boards as &$b ) {
			$b['topics'] = (int) DB::value( 'SELECT COUNT(*) FROM {forum_topics} WHERE board_id = %d', $b['id'] );
			$b['last']   = DB::row( 'SELECT * FROM {forum_topics} WHERE board_id = %d ORDER BY last_post_at DESC LIMIT 1', $b['id'] );
		}
		unset( $b );
		return $this->view(
			'boards',
			array(
				'c'      => $c,
				'boards' => $boards,
			)
		);
	}

	private function clean_body( string $body ): string {
		return trim( mb_substr( sanitize_textarea_field( $body ), 0, 10000 ) );
	}

	/**
	 * @return array|void
	 */
	public function action_new_topic( Character $c, array $input ) {
		$board = $this->board( $c, absint( $input['board'] ?? 0 ) );
		$title = mb_substr( trim( sanitize_text_field( $input['title'] ?? '' ) ), 0, 150 );
		$body  = $this->clean_body( (string) ( $input['body'] ?? '' ) );
		if ( ! $board || ! $this->can_post( $board ) ) {
			$this->error( __( 'You can\'t start a topic here.', 'underworld-empire' ) );
			return;
		}
		$back = array( 'board' => $board['id'] );
		if ( mb_strlen( $title ) < 3 || '' === $body ) {
			$this->error( __( 'Enter a title (min. 3 characters) and a message.', 'underworld-empire' ) );
			return $back;
		}
		if ( ! $c->claim_cooldown( 'forum', (int) $this->setting( 'forum_cooldown' ) ) ) {
			$this->error( __( 'You\'re posting too fast. Wait a moment.', 'underworld-empire' ) );
			return $back;
		}
		$topic = DB::insert(
			'forum_topics',
			array(
				'board_id'     => $board['id'],
				'character_id' => $c->id(),
				'title'        => $title,
				'created_at'   => time(),
				'last_post_at' => time(),
			)
		);
		DB::insert(
			'forum_posts',
			array(
				'topic_id'     => $topic,
				'character_id' => $c->id(),
				'body'         => $body,
				'created_at'   => time(),
			)
		);
		return array( 'topic' => $topic );
	}

	/**
	 * @return array|void
	 */
	public function action_reply( Character $c, array $input ) {
		$topic = DB::row( 'SELECT * FROM {forum_topics} WHERE id = %d', absint( $input['topic'] ?? 0 ) );
		if ( ! $topic || ! $this->board( $c, (int) $topic['board_id'] ) ) {
			return;
		}
		$back = array( 'topic' => $topic['id'] );
		if ( (int) $topic['locked'] && ! self::is_staff() ) {
			$this->error( __( 'This topic is locked.', 'underworld-empire' ) );
			return $back;
		}
		$body = $this->clean_body( (string) ( $input['body'] ?? '' ) );
		if ( '' === $body ) {
			$this->error( __( 'Your message is empty.', 'underworld-empire' ) );
			return $back;
		}
		if ( ! $c->claim_cooldown( 'forum', (int) $this->setting( 'forum_cooldown' ) ) ) {
			$this->error( __( 'You\'re posting too fast. Wait a moment.', 'underworld-empire' ) );
			return $back;
		}
		DB::insert(
			'forum_posts',
			array(
				'topic_id'     => $topic['id'],
				'character_id' => $c->id(),
				'body'         => $body,
				'created_at'   => time(),
			)
		);
		DB::update( 'forum_topics', array( 'last_post_at' => time() ), array( 'id' => $topic['id'] ) );
		$last = (int) ceil( (int) DB::value( 'SELECT COUNT(*) FROM {forum_posts} WHERE topic_id = %d', $topic['id'] ) / self::PER_PAGE );
		return array( 'topic' => $topic['id'], 'paged' => $last );
	}

	/**
	 * Moderation: lock, sticky, delete topic or post.
	 *
	 * @return array|void
	 */
	public function action_moderate( Character $c, array $input ) {
		if ( ! self::is_staff() ) {
			return;
		}
		$topic = DB::row( 'SELECT * FROM {forum_topics} WHERE id = %d', absint( $input['topic'] ?? 0 ) );
		if ( ! $topic ) {
			return;
		}
		switch ( sanitize_key( $input['task'] ?? '' ) ) {
			case 'lock':
				DB::update( 'forum_topics', array( 'locked' => (int) $topic['locked'] ? 0 : 1 ), array( 'id' => $topic['id'] ) );
				break;
			case 'sticky':
				DB::update( 'forum_topics', array( 'sticky' => (int) $topic['sticky'] ? 0 : 1 ), array( 'id' => $topic['id'] ) );
				break;
			case 'delete_post':
				DB::delete( 'forum_posts', array( 'id' => absint( $input['post'] ?? 0 ), 'topic_id' => $topic['id'] ) );
				break;
			case 'delete_topic':
				DB::delete( 'forum_posts', array( 'topic_id' => $topic['id'] ) );
				DB::delete( 'forum_topics', array( 'id' => $topic['id'] ) );
				return array( 'board' => $topic['board_id'] );
		}
		return array( 'topic' => $topic['id'] );
	}
}

return new Forum();
