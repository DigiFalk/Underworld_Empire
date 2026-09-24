<?php
/**
 * Opening and closing markup of the main content area, shared by all templates.
 *
 * @package UnderworldEmpireTheme
 */

defined( 'ABSPATH' ) || exit;

function uet_main_open(): void {
	$container = array(
		'normal'     => 'uet-container',
		'narrow'     => 'uet-container uet-container--narrow',
		'full-width' => 'uet-container-full',
	)[ uet_content_layout() ];
	echo '<div class="uet-main-wrap ' . esc_attr( $container ) . '">';
	uet_breadcrumbs();
	echo '<div class="uet-columns">';
	echo '<main id="primary" class="uet-main">';
}

function uet_main_close(): void {
	echo '</main>';
	get_sidebar();
	echo '</div></div>';
}
