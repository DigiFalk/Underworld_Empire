<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $boards
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

if ( ! $boards ) {
	echo UI::empty_state( __( 'There are no forum boards yet.', 'wp-maffia-game' ) ); // phpcs:ignore
	return;
}
?>
<table class="dfmg-table dfmg-forum">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Board', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Topics', 'wp-maffia-game' ); ?></th>
			<th><?php esc_html_e( 'Last activity', 'wp-maffia-game' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $boards as $dfmg_b ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( $this->url( array( 'board' => $dfmg_b['id'] ) ) ); ?>"><strong><?php echo esc_html( $dfmg_b['name'] ); ?></strong></a>
					<br><small class="dfmg-muted"><?php echo esc_html( $dfmg_b['description'] ); ?></small>
				</td>
				<td><?php echo (int) $dfmg_b['topics']; ?></td>
				<td>
					<?php if ( $dfmg_b['last'] ) : ?>
						<a href="<?php echo esc_url( $this->url( array( 'topic' => $dfmg_b['last']['id'] ) ) ); ?>"><?php echo esc_html( $dfmg_b['last']['title'] ); ?></a>
						<br><small><?php echo esc_html( Format::ago( (int) $dfmg_b['last']['last_post_at'] ) ); ?></small>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
