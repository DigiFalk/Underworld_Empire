<?php
/**
 * WooCommerce support.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

// Use the theme's own content wrapper.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
add_action( 'woocommerce_before_main_content', 'mpet_main_open', 10 );
add_action( 'woocommerce_after_main_content', 'mpet_main_close', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'mpet-woocommerce', MPET_URI . 'assets/css/woocommerce.css', array( 'mpet-theme' ), MPET_VERSION );
	},
	20
);
