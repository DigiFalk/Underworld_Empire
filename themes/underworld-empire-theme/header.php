<?php
/**
 * Site header.
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
<?php if ( uet_show_header() ) : ?>
	<?php if ( uet_opt( 'top_bar' ) ) : ?>
		<div class="uet-topbar">
			<div class="uet-topbar__inner uet-container">
				<div class="uet-topbar__text"><?php echo wp_kses_post( (string) uet_opt( 'top_bar_text' ) ); ?></div>
				<?php
				if ( has_nav_menu( 'top' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'top',
							'container'      => 'nav',
							'container_class' => 'uet-topbar__menu',
							'menu_class'     => 'uet-inline-menu',
							'depth'          => 1,
						)
					);
				}
				?>
			</div>
		</div>
	<?php endif; ?>
	<header class="uet-header uet-header--<?php echo esc_attr( (string) uet_opt( 'header_layout' ) ); ?> uet-header--<?php echo esc_attr( (string) uet_opt( 'header_width' ) ); ?>" id="masthead">
		<div class="uet-header__inner <?php echo 'full' === uet_opt( 'header_width' ) ? 'uet-container-fluid' : 'uet-container'; ?>">
			<?php uet_site_branding(); ?>
			<button class="uet-menu-toggle" type="button" aria-controls="uet-primary-nav" aria-expanded="false">
				<span class="uet-menu-toggle__icon" aria-hidden="true"></span>
				<span><?php echo esc_html( (string) uet_opt( 'mobile_menu_label' ) ); ?></span>
			</button>
			<nav class="uet-primary-nav" id="uet-primary-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'underworld-empire-theme' ); ?>">
				<?php uet_primary_menu(); ?>
			</nav>
			<?php uet_header_actions(); ?>
		</div>
	</header>
<?php endif; ?>
<div class="uet-content" id="content">
