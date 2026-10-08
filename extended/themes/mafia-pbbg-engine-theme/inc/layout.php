<?php
/**
 * Works out the layout of the current request: sidebar, container, which
 * parts are shown. Per page settings (meta box) override the Customizer.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per page option from the "Page options" meta box.
 */
function mpet_meta( string $key ): string {
	if ( ! is_singular() ) {
		return '';
	}
	return (string) get_post_meta( get_queried_object_id(), '_uet_' . $key, true );
}

/**
 * Whether the current page hosts the Mafia PBBG Engine game.
 */
function mpet_is_game_page(): bool {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	if ( $id && (int) get_option( 'dfmg_page_id' ) === $id ) {
		return true;
	}
	$content = (string) get_post_field( 'post_content', $id );
	foreach ( array( 'mafia_pbbg_engine', 'mafia_game', 'maffia_game' ) as $tag ) {
		if ( has_shortcode( $content, $tag ) ) {
			return true;
		}
	}
	return false;
}

function mpet_page_template(): string {
	return is_singular() ? (string) get_page_template_slug( get_queried_object_id() ) : '';
}

/**
 * Container: normal, narrow or full-width.
 */
function mpet_content_layout(): string {
	$meta = mpet_meta( 'content_layout' );
	if ( in_array( $meta, array( 'normal', 'narrow', 'full-width' ), true ) ) {
		return $meta;
	}
	switch ( mpet_page_template() ) {
		case 'mpet-full-width':
			return 'full-width';
		case 'mpet-narrow':
			return 'narrow';
	}
	return 'normal';
}

/**
 * Sidebar position: none, left or right.
 */
function mpet_sidebar(): string {
	$sidebar = mpet_meta( 'sidebar' );
	if ( ! in_array( $sidebar, array( 'none', 'left', 'right' ), true ) ) {
		if ( mpet_is_game_page() || 'page-game' === mpet_page_template() || 'full-width' === mpet_content_layout() ) {
			$sidebar = 'none';
		} elseif ( is_page() ) {
			$sidebar = (string) mpet_opt( 'sidebar_page' );
		} elseif ( is_singular() ) {
			$sidebar = (string) mpet_opt( 'sidebar_single' );
		} else {
			$sidebar = (string) mpet_opt( 'sidebar_archive' );
		}
		if ( 'default' === $sidebar || '' === $sidebar ) {
			$sidebar = (string) mpet_opt( 'sidebar_default' );
		}
	}
	if ( 'none' !== $sidebar && ! is_active_sidebar( 'sidebar-1' ) ) {
		$sidebar = 'none';
	}
	return apply_filters( 'mpet_sidebar', $sidebar );
}

function mpet_show_title(): bool {
	if ( mpet_meta( 'hide_title' ) || mpet_is_game_page() || 'page-game' === mpet_page_template() ) {
		return false;
	}
	return is_page() ? (bool) mpet_opt( 'page_titles' ) : true;
}

function mpet_show_header(): bool {
	return ! mpet_meta( 'hide_header' );
}

function mpet_show_footer(): bool {
	return ! mpet_meta( 'hide_footer' );
}

function mpet_show_featured(): bool {
	if ( mpet_meta( 'hide_featured' ) ) {
		return false;
	}
	return is_singular() ? (bool) mpet_opt( 'single_featured' ) : (bool) mpet_opt( 'blog_featured' );
}

function mpet_show_breadcrumbs(): bool {
	if ( ! mpet_opt( 'breadcrumbs' ) || mpet_meta( 'hide_breadcrumbs' ) ) {
		return false;
	}
	return ! is_front_page() || (bool) mpet_opt( 'breadcrumbs_home' );
}

function mpet_transparent_header(): bool {
	$meta = mpet_meta( 'transparent_header' );
	if ( 'on' === $meta ) {
		return true;
	}
	if ( 'off' === $meta ) {
		return false;
	}
	$option = (string) mpet_opt( 'header_transparent' );
	return 'all' === $option || ( 'front' === $option && is_front_page() );
}

add_filter( 'body_class', 'mpet_body_classes' );
function mpet_body_classes( array $classes ): array {
	$classes[] = 'mpet-site-' . sanitize_html_class( (string) mpet_opt( 'site_layout' ) );
	$classes[] = 'mpet-sidebar-' . mpet_sidebar();
	$classes[] = 'mpet-content-' . mpet_content_layout();
	$classes[] = 'mpet-blog-' . sanitize_html_class( (string) mpet_opt( 'blog_layout' ) );
	if ( mpet_opt( 'header_sticky' ) ) {
		$classes[] = 'mpet-sticky-header';
	}
	if ( mpet_transparent_header() ) {
		$classes[] = 'mpet-transparent-header';
	}
	if ( mpet_is_game_page() ) {
		$classes[] = 'mpet-game-page';
	}
	return $classes;
}
