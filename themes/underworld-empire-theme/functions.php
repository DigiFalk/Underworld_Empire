<?php
/**
 * Underworld Empire theme.
 *
 * All options are theme mods, edited in Appearance → Customize.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

define( 'UET_VERSION', '3.1.0' );
define( 'UET_DIR', trailingslashit( get_template_directory() ) );
define( 'UET_URI', trailingslashit( get_template_directory_uri() ) );

require UET_DIR . 'inc/options.php';
require UET_DIR . 'inc/setup.php';
require UET_DIR . 'inc/css.php';
require UET_DIR . 'inc/customizer.php';
require UET_DIR . 'inc/layout.php';
require UET_DIR . 'inc/template-tags.php';
require UET_DIR . 'inc/builder.php';
require UET_DIR . 'inc/wrapper.php';
require UET_DIR . 'inc/meta-box.php';

if ( class_exists( 'WooCommerce' ) ) {
	require UET_DIR . 'inc/woocommerce.php';
}
