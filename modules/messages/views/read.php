<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array                          $message
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\Format;

defined( 'ABSPATH' ) || exit;

$dfmg_incoming = (int) $message['recipient_id'] === $c->id();
echo $this->view( 'tabs', array( 'active' => $dfmg_incoming ? 'inbox' : 'sent' ) ); // phpcs:ignore
?>
<article class="dfmg-card dfmg-message">
	<h3><?php echo esc_html( $message['subject'] ); ?></h3>
	<p class="dfmg-muted">
		<?php
		/* translators: 1: sender, 2: recipient, 3: date */
		printf( esc_html__( 'From %1$s to %2$s, %3$s', 'wp-mafia-game' ), Character::link_by_id( (int) $message['sender_id'] ), Character::link_by_id( (int) $message['recipient_id'] ), esc_html( Format::date( (int) $message['created_at'] ) ) ); // phpcs:ignore
		?>
	</p>
	<div class="dfmg-usertext"><?php echo wp_kses_post( wpautop( esc_html( $message['body'] ) ) ); ?></div>
	<div class="dfmg-actions">
		<?php if ( $dfmg_incoming ) : ?>
			<a class="dfmg-button" href="<?php echo esc_url( $this->url( array( 'view' => 'compose', 'reply' => $message['id'] ) ) ); ?>"><?php esc_html_e( 'Reply', 'wp-mafia-game' ); ?></a>
		<?php endif; ?>
		<?php echo $this->button( 'delete', __( 'Delete', 'wp-mafia-game' ), array( 'id' => $message['id'], 'sent' => $dfmg_incoming ? 0 : 1 ), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
	</div>
</article>
