<?php
/**
 * Template helpers.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

function uet_site_branding(): void {
	echo '<div class="uet-branding">';
	if ( has_custom_logo() ) {
		echo '<div class="uet-logo">' . get_custom_logo() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( uet_opt( 'show_title' ) || uet_opt( 'show_tagline' ) || is_customize_preview() ) {
		echo '<div class="uet-branding__text">';
		if ( uet_opt( 'show_title' ) ) {
			$tag = ( is_front_page() && is_home() ) ? 'h1' : 'p';
			printf( '<%1$s class="uet-site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>', esc_attr( $tag ), esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
		}
		if ( uet_opt( 'show_tagline' ) && get_bloginfo( 'description' ) ) {
			echo '<p class="uet-site-tagline">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

function uet_primary_menu(): void {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'uet-menu',
				'depth'          => 3,
			)
		);
	} elseif ( current_user_can( 'edit_theme_options' ) ) {
		echo '<ul class="uet-menu"><li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Add a menu', 'underworld-empire-theme' ) . '</a></li></ul>';
	}
}

function uet_header_actions(): void {
	$text = (string) uet_opt( 'header_button_text' );
	$url  = (string) uet_opt( 'header_button_url' );
	if ( ! $text && ! uet_opt( 'header_search' ) ) {
		return;
	}
	echo '<div class="uet-header__actions">';
	if ( uet_opt( 'header_search' ) ) {
		echo '<details class="uet-header-search"><summary aria-label="' . esc_attr__( 'Search', 'underworld-empire-theme' ) . '">';
		echo '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 2a8 8 0 0 1 6.32 12.9l5.39 5.4-1.41 1.4-5.4-5.39A8 8 0 1 1 10 2zm0 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12z"/></svg>';
		echo '</summary><div class="uet-header-search__panel">' . get_search_form( array( 'echo' => false ) ) . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $text ) {
		echo '<a class="uet-button uet-header-button" href="' . esc_url( $url ?: home_url( '/' ) ) . '">' . esc_html( $text ) . '</a>';
	}
	echo '</div>';
}

/**
 * Breadcrumb trail.
 */
function uet_breadcrumbs(): void {
	if ( ! uet_show_breadcrumbs() ) {
		return;
	}
	$items   = array();
	$items[] = array( __( 'Home', 'underworld-empire-theme' ), home_url( '/' ) );

	if ( is_home() && ! is_front_page() ) {
		$items[] = array( get_the_title( (int) get_option( 'page_for_posts' ) ), '' );
	} elseif ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$items[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$items[] = array( get_the_title(), '' );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $ancestor ) {
			$items[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		if ( ! is_front_page() ) {
			$items[] = array( get_the_title(), '' );
		}
	} elseif ( is_singular() ) {
		$items[] = array( get_the_title(), '' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array( single_term_title( '', false ), '' );
	} elseif ( is_search() ) {
		/* translators: %s: search terms */
		$items[] = array( sprintf( __( 'Search results for "%s"', 'underworld-empire-theme' ), get_search_query() ), '' );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), '' );
	} elseif ( is_archive() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	} elseif ( is_404() ) {
		$items[] = array( __( 'Page not found', 'underworld-empire-theme' ), '' );
	}

	echo '<nav class="uet-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'underworld-empire-theme' ) . '"><ol>';
	foreach ( $items as $i => $item ) {
		$last = count( $items ) - 1 === $i;
		echo '<li>';
		if ( $item[1] && ! $last ) {
			echo '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item[0] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Post meta line according to the Customizer settings.
 */
function uet_post_meta(): void {
	if ( 'post' !== get_post_type() || ( is_singular() && ! uet_opt( 'single_meta' ) ) ) {
		return;
	}
	$parts = array();
	if ( uet_opt( 'meta_date' ) ) {
		$parts[] = '<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
	}
	if ( uet_opt( 'meta_author' ) && get_the_author() ) {
		$parts[] = '<a href="' . esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>';
	}
	if ( uet_opt( 'meta_categories' ) && get_the_category_list( ', ' ) ) {
		$parts[] = get_the_category_list( ', ' );
	}
	if ( uet_opt( 'meta_comments' ) && comments_open() ) {
		/* translators: %d: comments */
		$parts[] = '<a href="' . esc_url( get_comments_link() ) . '">' . esc_html( sprintf( _n( '%d comment', '%d comments', (int) get_comments_number(), 'underworld-empire-theme' ), (int) get_comments_number() ) ) . '</a>';
	}
	if ( $parts ) {
		echo '<div class="uet-post-meta">' . implode( '<span class="uet-sep">/</span>', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function uet_featured_image( string $size = 'large' ): void {
	if ( ! uet_show_featured() || ! has_post_thumbnail() || post_password_required() ) {
		return;
	}
	if ( is_singular() ) {
		echo '<figure class="uet-featured">' . get_the_post_thumbnail( null, $size ) . '</figure>';
	} else {
		echo '<a class="uet-featured" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( null, $size ) . '</a>';
	}
}

function uet_author_box(): void {
	if ( ! uet_opt( 'author_box' ) || 'post' !== get_post_type() ) {
		return;
	}
	$id = (int) get_the_author_meta( 'ID' );
	echo '<aside class="uet-author-box">' . get_avatar( $id, 72 ) . '<div><h2 class="uet-author-box__name">' . esc_html( get_the_author() ) . '</h2>';
	if ( get_the_author_meta( 'description' ) ) {
		echo '<p>' . esc_html( get_the_author_meta( 'description' ) ) . '</p>';
	}
	echo '<a href="' . esc_url( get_author_posts_url( $id ) ) . '">' . esc_html__( 'All posts', 'underworld-empire-theme' ) . '</a></div></aside>';
}

/**
 * Footer copyright with [current_year] [site_title] [site_url] [theme_author].
 */
function uet_copyright(): string {
	$text = (string) uet_opt( 'footer_copyright' );
	return wp_kses_post(
		strtr(
			$text,
			array(
				'[current_year]' => wp_date( 'Y' ),
				'[site_title]'   => esc_html( get_bloginfo( 'name' ) ),
				'[site_url]'     => '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>',
				'[theme_author]' => '<a href="https://github.com/DigiFalk">DigiFalk</a>',
			)
		)
	);
}

function uet_read_more(): void {
	$text = (string) uet_opt( 'read_more' );
	if ( $text ) {
		echo '<a class="uet-read-more" href="' . esc_url( get_permalink() ) . '">' . esc_html( $text ) . ' <span class="screen-reader-text">' . esc_html( get_the_title() ) . '</span></a>';
	}
}
