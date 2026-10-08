<?php
/**
 * Opening and closing markup of the main content area, shared by all templates.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

function mpet_main_open(): void {
	$container = array(
		'normal'     => 'mpet-container',
		'narrow'     => 'mpet-container mpet-container--narrow',
		'full-width' => 'mpet-container-full',
	)[ mpet_content_layout() ];
	echo '<div class="mpet-main-wrap ' . esc_attr( $container ) . '">';
	mpet_breadcrumbs();
	echo '<div class="mpet-columns">';
	echo '<main id="primary" class="mpet-main">';
}

function mpet_main_close(): void {
	echo '</main>';
	get_sidebar();
	echo '</div></div>';
}
