<?php
/**
 * Site header (built with Appearance → Customize → Header → Header builder).
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'underworld-empire-theme' ); ?></a>
<div class="uet-site">
<?php
if ( uet_show_header() ) {
	uet_render_header();
}
?>
<div class="uet-content" id="content">
