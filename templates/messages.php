<?php
/**
 * Flash messages.
 *
 * @var array $messages
 *
 * @package DigiFalk\MaffiaGame
 */

defined( 'ABSPATH' ) || exit;

foreach ( (array) $messages as $dfmg_message ) : ?>
	<div class="dfmg-alert dfmg-alert--<?php echo esc_attr( $dfmg_message['type'] ); ?>" role="alert">
		<?php echo wp_kses_post( $dfmg_message['message'] ); ?>
	</div>
<?php endforeach; ?>
