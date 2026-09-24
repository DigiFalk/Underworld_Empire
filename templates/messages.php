<?php
/**
 * Flash messages.
 *
 * @var array $messages
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Icons;

defined( 'ABSPATH' ) || exit;

$dfmg_alert_icons = array(
	'success' => 'check',
	'error'   => 'alert',
	'wait'    => 'wait',
	'info'    => 'info',
);
foreach ( (array) $messages as $dfmg_message ) : ?>
	<div class="dfmg-alert dfmg-alert--<?php echo esc_attr( $dfmg_message['type'] ); ?>" role="alert">
		<span class="dfmg-alert__icon"><?php echo Icons::svg( $dfmg_alert_icons[ $dfmg_message['type'] ] ?? 'info', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<div class="dfmg-alert__text"><?php echo wp_kses_post( $dfmg_message['message'] ); ?></div>
	</div>
<?php endforeach; ?>
