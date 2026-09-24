<?php
/**
 * @var string $active
 *
 * @package DigiFalk\MafiaGame
 */

defined( 'ABSPATH' ) || exit;

$dfmg_tabs = array(
	'inbox'   => __( 'Inbox', 'wp-mafia-game' ),
	'sent'    => __( 'Sent', 'wp-mafia-game' ),
	'compose' => __( 'New message', 'wp-mafia-game' ),
);
?>
<nav class="dfmg-tabs">
	<?php foreach ( $dfmg_tabs as $dfmg_key => $dfmg_label ) : ?>
		<a class="<?php echo $dfmg_key === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->url( 'inbox' === $dfmg_key ? array() : array( 'view' => $dfmg_key ) ) ); ?>"><?php echo esc_html( $dfmg_label ); ?></a>
	<?php endforeach; ?>
</nav>
