<?php
/**
 * Module Name: News
 * Description: Game news as a custom post type in WordPress. Shown in the game and on the login page.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Modules;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

final class News extends Module {

	const POST_TYPE = 'dfmg_news';

	public function title(): string {
		return __( 'News', 'wp-maffia-game' );
	}

	public function allowed_in_jail(): bool {
		return true;
	}

	public function allowed_in_hospital(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'dfmg_login_page', array( $this, 'login_news' ) );
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Game news', 'wp-maffia-game' ),
					'singular_name' => __( 'News item', 'wp-maffia-game' ),
					'add_new_item'  => __( 'New news item', 'wp-maffia-game' ),
					'edit_item'     => __( 'Edit news item', 'wp-maffia-game' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'dfmg',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'author' ),
				'capability_type' => 'post',
			)
		);
	}

	public function menu( Character $c ): array {
		return array(
			array(
				'label' => __( 'News', 'wp-maffia-game' ),
				'group' => 'community',
				'order' => 5,
			),
		);
	}

	private function posts( int $count ): array {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => $count,
				'post_status'    => 'publish',
			)
		);
	}

	private function markup( array $posts, bool $full ): string {
		if ( ! $posts ) {
			return UI::empty_state( __( 'No news yet.', 'wp-maffia-game' ) );
		}
		$html = '';
		foreach ( $posts as $post ) {
			$html .= '<article class="dfmg-card dfmg-news"><h3>' . esc_html( get_the_title( $post ) ) . '</h3>'
				. '<p class="dfmg-muted">' . esc_html( Format::date( (int) get_post_time( 'U', true, $post ) ) ) . '</p>'
				. '<div class="dfmg-usertext">' . ( $full ? apply_filters( 'the_content', $post->post_content ) : wp_kses_post( wpautop( wp_trim_words( $post->post_content, 40 ) ) ) ) . '</div></article>'; // phpcs:ignore
		}
		return $html;
	}

	public function login_news(): void {
		echo '<div class="dfmg-login-news"><h3>' . esc_html__( 'Latest news', 'wp-maffia-game' ) . '</h3>' . $this->markup( $this->posts( 3 ), false ) . '</div>'; // phpcs:ignore
	}

	public function render( Character $c, array $query ): string {
		return $this->markup( $this->posts( 10 ), true );
	}
}

return new News();
