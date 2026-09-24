<?php
/**
 * "Page options" meta box on pages and posts (like Astra's page settings).
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

function uet_meta_fields(): array {
	return array(
		'sidebar'            => array(
			'label'   => __( 'Sidebar', 'underworld-empire-theme' ),
			'choices' => array(
				''      => __( 'Customizer setting', 'underworld-empire-theme' ),
				'none'  => __( 'No sidebar', 'underworld-empire-theme' ),
				'right' => __( 'Right sidebar', 'underworld-empire-theme' ),
				'left'  => __( 'Left sidebar', 'underworld-empire-theme' ),
			),
		),
		'content_layout'     => array(
			'label'   => __( 'Content layout', 'underworld-empire-theme' ),
			'choices' => array(
				''           => __( 'Default', 'underworld-empire-theme' ),
				'normal'     => __( 'Normal container', 'underworld-empire-theme' ),
				'narrow'     => __( 'Narrow container', 'underworld-empire-theme' ),
				'full-width' => __( 'Full width (page builders)', 'underworld-empire-theme' ),
			),
		),
		'transparent_header' => array(
			'label'   => __( 'Transparent header', 'underworld-empire-theme' ),
			'choices' => array(
				''    => __( 'Customizer setting', 'underworld-empire-theme' ),
				'on'  => __( 'On', 'underworld-empire-theme' ),
				'off' => __( 'Off', 'underworld-empire-theme' ),
			),
		),
		'hide_title'         => array( 'label' => __( 'Hide title', 'underworld-empire-theme' ) ),
		'hide_featured'      => array( 'label' => __( 'Hide featured image', 'underworld-empire-theme' ) ),
		'hide_breadcrumbs'   => array( 'label' => __( 'Hide breadcrumbs', 'underworld-empire-theme' ) ),
		'hide_header'        => array( 'label' => __( 'Hide header', 'underworld-empire-theme' ) ),
		'hide_footer'        => array( 'label' => __( 'Hide footer', 'underworld-empire-theme' ) ),
	);
}

add_action( 'init', 'uet_register_meta' );
function uet_register_meta(): void {
	foreach ( array( 'post', 'page' ) as $type ) {
		foreach ( uet_meta_fields() as $key => $field ) {
			register_post_meta(
				$type,
				'_uet_' . $key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}

add_action( 'add_meta_boxes', 'uet_add_meta_box' );
function uet_add_meta_box(): void {
	foreach ( array( 'post', 'page' ) as $type ) {
		add_meta_box( 'uet-page-options', __( 'Page options', 'underworld-empire-theme' ), 'uet_render_meta_box', $type, 'side', 'default' );
	}
}

function uet_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'uet_meta', 'uet_meta_nonce' );
	foreach ( uet_meta_fields() as $key => $field ) {
		$value = (string) get_post_meta( $post->ID, '_uet_' . $key, true );
		$id    = 'uet-meta-' . $key;
		if ( isset( $field['choices'] ) ) {
			echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';
			echo '<select id="' . esc_attr( $id ) . '" name="uet_meta[' . esc_attr( $key ) . ']" style="width:100%">';
			foreach ( $field['choices'] as $option => $label ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></p>';
		} else {
			echo '<p><label><input type="checkbox" name="uet_meta[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html( $field['label'] ) . '</label></p>';
		}
	}
}

add_action( 'save_post', 'uet_save_meta_box', 10, 2 );
function uet_save_meta_box( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['uet_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uet_meta_nonce'] ) ), 'uet_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$input = isset( $_POST['uet_meta'] ) ? (array) wp_unslash( $_POST['uet_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( uet_meta_fields() as $key => $field ) {
		$value = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
		if ( isset( $field['choices'] ) ) {
			$value = array_key_exists( $value, $field['choices'] ) ? $value : '';
		} else {
			$value = $value ? '1' : '';
		}
		if ( '' === $value ) {
			delete_post_meta( $post_id, '_uet_' . $key );
		} else {
			update_post_meta( $post_id, '_uet_' . $key, $value );
		}
	}
}
