<?php
/**
 * "Page options" meta box on pages and posts (like Astra's page settings).
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

function mpet_meta_fields(): array {
	return array(
		'sidebar'            => array(
			'label'   => __( 'Sidebar', 'mafia-pbbg-engine-theme' ),
			'choices' => array(
				''      => __( 'Customizer setting', 'mafia-pbbg-engine-theme' ),
				'none'  => __( 'No sidebar', 'mafia-pbbg-engine-theme' ),
				'right' => __( 'Right sidebar', 'mafia-pbbg-engine-theme' ),
				'left'  => __( 'Left sidebar', 'mafia-pbbg-engine-theme' ),
			),
		),
		'content_layout'     => array(
			'label'   => __( 'Content layout', 'mafia-pbbg-engine-theme' ),
			'choices' => array(
				''           => __( 'Default', 'mafia-pbbg-engine-theme' ),
				'normal'     => __( 'Normal container', 'mafia-pbbg-engine-theme' ),
				'narrow'     => __( 'Narrow container', 'mafia-pbbg-engine-theme' ),
				'full-width' => __( 'Full width (page builders)', 'mafia-pbbg-engine-theme' ),
			),
		),
		'transparent_header' => array(
			'label'   => __( 'Transparent header', 'mafia-pbbg-engine-theme' ),
			'choices' => array(
				''    => __( 'Customizer setting', 'mafia-pbbg-engine-theme' ),
				'on'  => __( 'On', 'mafia-pbbg-engine-theme' ),
				'off' => __( 'Off', 'mafia-pbbg-engine-theme' ),
			),
		),
		'hide_title'         => array( 'label' => __( 'Hide title', 'mafia-pbbg-engine-theme' ) ),
		'hide_featured'      => array( 'label' => __( 'Hide featured image', 'mafia-pbbg-engine-theme' ) ),
		'hide_breadcrumbs'   => array( 'label' => __( 'Hide breadcrumbs', 'mafia-pbbg-engine-theme' ) ),
		'hide_header'        => array( 'label' => __( 'Hide header', 'mafia-pbbg-engine-theme' ) ),
		'hide_footer'        => array( 'label' => __( 'Hide footer', 'mafia-pbbg-engine-theme' ) ),
	);
}

add_action( 'init', 'mpet_register_meta' );
function mpet_register_meta(): void {
	foreach ( array( 'post', 'page' ) as $type ) {
		foreach ( mpet_meta_fields() as $key => $field ) {
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

add_action( 'add_meta_boxes', 'mpet_add_meta_box' );
function mpet_add_meta_box(): void {
	foreach ( array( 'post', 'page' ) as $type ) {
		add_meta_box( 'mpet-page-options', __( 'Page options', 'mafia-pbbg-engine-theme' ), 'mpet_render_meta_box', $type, 'side', 'default' );
	}
}

function mpet_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mpet_meta', 'mpet_meta_nonce' );
	foreach ( mpet_meta_fields() as $key => $field ) {
		$value = (string) get_post_meta( $post->ID, '_uet_' . $key, true );
		$id    = 'mpet-meta-' . $key;
		if ( isset( $field['choices'] ) ) {
			echo '<p><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label><br>';
			echo '<select id="' . esc_attr( $id ) . '" name="mpet_meta[' . esc_attr( $key ) . ']" style="width:100%">';
			foreach ( $field['choices'] as $option => $label ) {
				echo '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select></p>';
		} else {
			echo '<p><label><input type="checkbox" name="mpet_meta[' . esc_attr( $key ) . ']" value="1" ' . checked( $value, '1', false ) . '> ' . esc_html( $field['label'] ) . '</label></p>';
		}
	}
}

add_action( 'save_post', 'mpet_save_meta_box', 10, 2 );
function mpet_save_meta_box( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST['mpet_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mpet_meta_nonce'] ) ), 'mpet_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$input = isset( $_POST['mpet_meta'] ) ? (array) wp_unslash( $_POST['mpet_meta'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( mpet_meta_fields() as $key => $field ) {
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
