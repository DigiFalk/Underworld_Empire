<?php
/**
 * Works out the layout of the current request: sidebar, container, which
 * parts are shown. Per page settings (meta box) override the Customizer.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Per page option from the "Page options" meta box.
 */
function uet_meta( string $key ): string {
	if ( ! is_singular() ) {
		return '';
	}
	return (string) get_post_meta( get_queried_object_id(), '_uet_' . $key, true );
}

/**
 * Whether the current page hosts the Underworld Empire game.
 */
function uet_is_game_page(): bool {
	if ( ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	if ( $id && (int) get_option( 'dfmg_page_id' ) === $id ) {
		return true;
	}
	$content = (string) get_post_field( 'post_content', $id );
	foreach ( array( 'underworld_empire', 'mafia_game', 'maffia_game' ) as $tag ) {
		if ( has_shortcode( $content, $tag ) ) {
			return true;
		}
	}
	return false;
}

function uet_page_template(): string {
	return is_singular() ? (string) get_page_template_slug( get_queried_object_id() ) : '';
}

/**
 * Container: normal, narrow or full-width.
 */
function uet_content_layout(): string {
	$meta = uet_meta( 'content_layout' );
	if ( in_array( $meta, array( 'normal', 'narrow', 'full-width' ), true ) ) {
		return $meta;
	}
	switch ( uet_page_template() ) {
		case 'uet-full-width':
			return 'full-width';
		case 'uet-narrow':
			return 'narrow';
	}
	return 'normal';
}

/**
 * Sidebar position: none, left or right.
 */
function uet_sidebar(): string {
	$sidebar = uet_meta( 'sidebar' );
	if ( ! in_array( $sidebar, array( 'none', 'left', 'right' ), true ) ) {
		if ( uet_is_game_page() || 'page-game' === uet_page_template() || 'full-width' === uet_content_layout() ) {
			$sidebar = 'none';
		} elseif ( is_page() ) {
			$sidebar = (string) uet_opt( 'sidebar_page' );
		} elseif ( is_singular() ) {
			$sidebar = (string) uet_opt( 'sidebar_single' );
		} else {
			$sidebar = (string) uet_opt( 'sidebar_archive' );
		}
		if ( 'default' === $sidebar || '' === $sidebar ) {
			$sidebar = (string) uet_opt( 'sidebar_default' );
		}
	}
	if ( 'none' !== $sidebar && ! is_active_sidebar( 'sidebar-1' ) ) {
		$sidebar = 'none';
	}
	return apply_filters( 'uet_sidebar', $sidebar );
}

function uet_show_title(): bool {
	if ( uet_meta( 'hide_title' ) || uet_is_game_page() || 'page-game' === uet_page_template() ) {
		return false;
	}
	return is_page() ? (bool) uet_opt( 'page_titles' ) : true;
}

function uet_show_header(): bool {
	return ! uet_meta( 'hide_header' );
}

function uet_show_footer(): bool {
	return ! uet_meta( 'hide_footer' );
}

function uet_show_featured(): bool {
	if ( uet_meta( 'hide_featured' ) ) {
		return false;
	}
	return is_singular() ? (bool) uet_opt( 'single_featured' ) : (bool) uet_opt( 'blog_featured' );
}

function uet_show_breadcrumbs(): bool {
	if ( ! uet_opt( 'breadcrumbs' ) || uet_meta( 'hide_breadcrumbs' ) ) {
		return false;
	}
	return ! is_front_page() || (bool) uet_opt( 'breadcrumbs_home' );
}

function uet_transparent_header(): bool {
	$meta = uet_meta( 'transparent_header' );
	if ( 'on' === $meta ) {
		return true;
	}
	if ( 'off' === $meta ) {
		return false;
	}
	$option = (string) uet_opt( 'header_transparent' );
	return 'all' === $option || ( 'front' === $option && is_front_page() );
}

add_filter( 'body_class', 'uet_body_classes' );
function uet_body_classes( array $classes ): array {
	$classes[] = 'uet-site-' . sanitize_html_class( (string) uet_opt( 'site_layout' ) );
	$classes[] = 'uet-sidebar-' . uet_sidebar();
	$classes[] = 'uet-content-' . uet_content_layout();
	$classes[] = 'uet-blog-' . sanitize_html_class( (string) uet_opt( 'blog_layout' ) );
	if ( uet_opt( 'header_sticky' ) ) {
		$classes[] = 'uet-sticky-header';
	}
	if ( uet_transparent_header() ) {
		$classes[] = 'uet-transparent-header';
	}
	if ( uet_is_game_page() ) {
		$classes[] = 'uet-game-page';
	}
	return $classes;
}
