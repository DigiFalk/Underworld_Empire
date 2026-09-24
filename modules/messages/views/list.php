<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $rows
 * @var bool                           $sent
 * @var int                            $paged
 * @var int                            $pages
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

echo $this->view( 'tabs', array( 'active' => $sent ? 'sent' : 'inbox' ) ); // phpcs:ignore

if ( ! $rows ) {
	echo UI::empty_state( __( 'Geen berichten.', 'wp-maffia-game' ) ); // phpcs:ignore
	return;
}
?>
<?php echo $this->form( 'delete', $sent ? array( 'sent' => 1 ) : array() ); // phpcs:ignore ?>
	<table class="dfmg-table dfmg-messages">
		<thead>
			<tr>
				<th class="dfmg-col-check"></th>
				<th><?php echo $sent ? esc_html__( 'Aan', 'wp-maffia-game' ) : esc_html__( 'Van', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Onderwerp', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Datum', 'wp-maffia-game' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rows as $dfmg_m ) : ?>
				<tr class="<?php echo ( ! $sent && ! $dfmg_m['is_read'] ) ? 'is-unread' : ''; ?>">
					<td class="dfmg-col-check"><input type="checkbox" name="ids[]" value="<?php echo esc_attr( $dfmg_m['id'] ); ?>"></td>
					<td><?php echo Character::link_by_id( (int) ( $sent ? $dfmg_m['recipient_id'] : $dfmg_m['sender_id'] ) ); // phpcs:ignore ?></td>
					<td><a href="<?php echo esc_url( $this->url( array( 'view' => 'read', 'id' => $dfmg_m['id'] ) ) ); ?>"><?php echo esc_html( $dfmg_m['subject'] ); ?></a></td>
					<td><small><?php echo esc_html( Format::ago( (int) $dfmg_m['created_at'] ) ); ?></small></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<button type="submit" class="dfmg-button dfmg-button--ghost dfmg-button--small"><?php esc_html_e( 'Geselecteerde verwijderen', 'wp-maffia-game' ); ?></button>
</form>
<?php
if ( ! $sent ) {
	echo $this->button( 'read_all', __( 'Alles als gelezen markeren', 'wp-maffia-game' ), array(), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore
}
echo UI::pager( $this->id(), $sent ? array( 'view' => 'sent' ) : array(), $paged, $pages ); // phpcs:ignore
