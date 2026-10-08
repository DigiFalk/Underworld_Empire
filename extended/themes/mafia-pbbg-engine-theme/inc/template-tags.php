<?php
/**
 * Template helpers.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

function mpet_site_branding(): void {
	echo '<div class="mpet-branding">';
	if ( has_custom_logo() ) {
		echo '<div class="mpet-logo">' . get_custom_logo() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( mpet_opt( 'show_title' ) || mpet_opt( 'show_tagline' ) || is_customize_preview() ) {
		echo '<div class="mpet-branding__text">';
		if ( mpet_opt( 'show_title' ) ) {
			$tag = ( is_front_page() && is_home() ) ? 'h1' : 'p';
			printf( '<%1$s class="mpet-site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>', esc_attr( $tag ), esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
		}
		if ( mpet_opt( 'show_tagline' ) && get_bloginfo( 'description' ) ) {
			echo '<p class="mpet-site-tagline">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Breadcrumb trail.
 */
function mpet_breadcrumbs(): void {
	if ( ! mpet_show_breadcrumbs() ) {
		return;
	}
	$items   = array();
	$items[] = array( __( 'Home', 'mafia-pbbg-engine-theme' ), home_url( '/' ) );

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
		$items[] = array( sprintf( __( 'Search results for "%s"', 'mafia-pbbg-engine-theme' ), get_search_query() ), '' );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), '' );
	} elseif ( is_archive() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	} elseif ( is_404() ) {
		$items[] = array( __( 'Page not found', 'mafia-pbbg-engine-theme' ), '' );
	}

	echo '<nav class="mpet-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'mafia-pbbg-engine-theme' ) . '"><ol>';
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
function mpet_post_meta(): void {
	if ( 'post' !== get_post_type() || ( is_singular() && ! mpet_opt( 'single_meta' ) ) ) {
		return;
	}
	$parts = array();
	if ( mpet_opt( 'meta_date' ) ) {
		$parts[] = '<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
	}
	if ( mpet_opt( 'meta_author' ) && get_the_author() ) {
		$parts[] = '<a href="' . esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>';
	}
	if ( mpet_opt( 'meta_categories' ) && get_the_category_list( ', ' ) ) {
		$parts[] = get_the_category_list( ', ' );
	}
	if ( mpet_opt( 'meta_comments' ) && comments_open() ) {
		/* translators: %d: comments */
		$parts[] = '<a href="' . esc_url( get_comments_link() ) . '">' . esc_html( sprintf( _n( '%d comment', '%d comments', (int) get_comments_number(), 'mafia-pbbg-engine-theme' ), (int) get_comments_number() ) ) . '</a>';
	}
	if ( $parts ) {
		echo '<div class="mpet-post-meta">' . implode( '<span class="mpet-sep">/</span>', $parts ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function mpet_featured_image( string $size = 'large' ): void {
	if ( ! mpet_show_featured() || ! has_post_thumbnail() || post_password_required() ) {
		return;
	}
	if ( is_singular() ) {
		echo '<figure class="mpet-featured">' . get_the_post_thumbnail( null, $size ) . '</figure>';
	} else {
		echo '<a class="mpet-featured" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail( null, $size ) . '</a>';
	}
}

function mpet_author_box(): void {
	if ( ! mpet_opt( 'author_box' ) || 'post' !== get_post_type() ) {
		return;
	}
	$id = (int) get_the_author_meta( 'ID' );
	echo '<aside class="mpet-author-box">' . get_avatar( $id, 72 ) . '<div><h2 class="mpet-author-box__name">' . esc_html( get_the_author() ) . '</h2>';
	if ( get_the_author_meta( 'description' ) ) {
		echo '<p>' . esc_html( get_the_author_meta( 'description' ) ) . '</p>';
	}
	echo '<a href="' . esc_url( get_author_posts_url( $id ) ) . '">' . esc_html__( 'All posts', 'mafia-pbbg-engine-theme' ) . '</a></div></aside>';
}

/**
 * Footer copyright with [current_year] [site_title] [site_url] [theme_author].
 */
function mpet_copyright(): string {
	$text = (string) mpet_opt( 'footer_copyright' );
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

function mpet_read_more(): void {
	$text = (string) mpet_opt( 'read_more' );
	if ( $text ) {
		echo '<a class="mpet-read-more" href="' . esc_url( get_permalink() ) . '">' . esc_html( $text ) . ' <span class="screen-reader-text">' . esc_html( get_the_title() ) . '</span></a>';
	}
}
