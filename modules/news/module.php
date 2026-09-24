<?php
/**
 * Module Name: News
 * Description: Game news as a custom post type in WordPress. Shown in the game and on the login page.
 * Version: 1.0.0
 * Author: DigiFalk
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Modules;

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;
use DigiFalk\UnderworldEmpire\Module\Module;

defined( 'ABSPATH' ) || exit;

final class News extends Module {

	const POST_TYPE = 'dfmg_news';

	public function title(): string {
		return __( 'News', 'underworld-empire' );
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
					'name'          => __( 'Game news', 'underworld-empire' ),
					'singular_name' => __( 'News item', 'underworld-empire' ),
					'add_new_item'  => __( 'New news item', 'underworld-empire' ),
					'edit_item'     => __( 'Edit news item', 'underworld-empire' ),
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
				'label' => __( 'News', 'underworld-empire' ),
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
			return UI::empty_state( __( 'No news yet.', 'underworld-empire' ) );
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
		echo '<div class="dfmg-login-news"><h3>' . esc_html__( 'Latest news', 'underworld-empire' ) . '</h3>' . $this->markup( $this->posts( 3 ), false ) . '</div>'; // phpcs:ignore
	}

	public function render( Character $c, array $query ): string {
		return $this->markup( $this->posts( 10 ), true );
	}
}

return new News();
