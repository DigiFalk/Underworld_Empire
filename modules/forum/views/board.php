<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $board
 * @var array                          $topics
 * @var int                            $paged
 * @var int                            $pages
 * @var bool                           $can_post
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<p><a href="<?php echo esc_url( $this->url() ); ?>">&larr; <?php esc_html_e( 'Forum', 'wp-maffia-game' ); ?></a> / <strong><?php echo esc_html( $board['name'] ); ?></strong></p>
<?php if ( ! $topics ) : ?>
	<?php echo UI::empty_state( __( 'No topics yet.', 'wp-maffia-game' ) ); // phpcs:ignore ?>
<?php else : ?>
	<table class="dfmg-table dfmg-forum">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Topic', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'By', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Posts', 'wp-maffia-game' ); ?></th>
				<th><?php esc_html_e( 'Last post', 'wp-maffia-game' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $topics as $dfmg_t ) : ?>
				<tr>
					<td>
						<?php if ( $dfmg_t['sticky'] ) : ?><em class="dfmg-tag"><?php esc_html_e( 'pinned', 'wp-maffia-game' ); ?></em><?php endif; ?>
						<?php if ( $dfmg_t['locked'] ) : ?><em class="dfmg-tag"><?php esc_html_e( 'locked', 'wp-maffia-game' ); ?></em><?php endif; ?>
						<a href="<?php echo esc_url( $this->url( array( 'topic' => $dfmg_t['id'] ) ) ); ?>"><?php echo esc_html( $dfmg_t['title'] ); ?></a>
					</td>
					<td><?php echo Character::link_by_id( (int) $dfmg_t['character_id'] ); // phpcs:ignore ?></td>
					<td><?php echo (int) $dfmg_t['posts']; ?></td>
					<td><small><?php echo esc_html( Format::ago( (int) $dfmg_t['last_post_at'] ) ); ?></small></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php echo UI::pager( $this->id(), array( 'board' => $board['id'] ), $paged, $pages ); // phpcs:ignore ?>
<?php endif; ?>

<?php if ( $can_post ) : ?>
	<div class="dfmg-card">
		<h3><?php esc_html_e( 'New topic', 'wp-maffia-game' ); ?></h3>
		<?php echo $this->form( 'new_topic', array( 'board' => $board['id'] ), 'dfmg-form dfmg-form--stacked' ); // phpcs:ignore ?>
			<input type="text" name="title" maxlength="150" placeholder="<?php esc_attr_e( 'Title', 'wp-maffia-game' ); ?>" required>
			<textarea name="body" rows="6" required></textarea>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Place', 'wp-maffia-game' ); ?></button>
		</form>
	</div>
<?php endif; ?>
