<?php
/**
 * @var string $active
 *
 * @package DigiFalk\UnderworldEmpire
 */

defined( 'ABSPATH' ) || exit;

$dfmg_tabs = array(
	'inbox'   => __( 'Inbox', 'underworld-empire' ),
	'sent'    => __( 'Sent', 'underworld-empire' ),
	'compose' => __( 'New message', 'underworld-empire' ),
);
?>
<nav class="dfmg-tabs">
	<?php foreach ( $dfmg_tabs as $dfmg_key => $dfmg_label ) : ?>
		<a class="<?php echo $dfmg_key === $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->url( 'inbox' === $dfmg_key ? array() : array( 'view' => $dfmg_key ) ) ); ?>"><?php echo esc_html( $dfmg_label ); ?></a>
	<?php endforeach; ?>
</nav>
