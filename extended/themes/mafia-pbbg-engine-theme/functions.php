<?php
/**
 * Mafia PBBG Engine theme.
 *
 * All options are theme mods, edited in Appearance → Customize.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

define( 'MPET_VERSION', '4.0.0' );
define( 'MPET_DIR', trailingslashit( get_template_directory() ) );
define( 'MPET_URI', trailingslashit( get_template_directory_uri() ) );

require MPET_DIR . 'inc/options.php';
require MPET_DIR . 'inc/setup.php';
require MPET_DIR . 'inc/css.php';
require MPET_DIR . 'inc/customizer.php';
require MPET_DIR . 'inc/layout.php';
require MPET_DIR . 'inc/template-tags.php';
require MPET_DIR . 'inc/builder.php';
require MPET_DIR . 'inc/wrapper.php';
require MPET_DIR . 'inc/meta-box.php';

if ( class_exists( 'WooCommerce' ) ) {
	require MPET_DIR . 'inc/woocommerce.php';
}
