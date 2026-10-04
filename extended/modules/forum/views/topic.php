<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $board
 * @var array                          $topic
 * @var array                          $posts
 * @var int                            $paged
 * @var int                            $pages
 * @var bool                           $can_post
 * @var bool                           $staff
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Character;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;
?>
<p>
	<a href="<?php echo esc_url( $this->url() ); ?>"><?php esc_html_e( 'Forum', 'underworld-empire' ); ?></a> /
	<a href="<?php echo esc_url( $this->url( array( 'board' => $board['id'] ) ) ); ?>"><?php echo esc_html( $board['name'] ); ?></a>
</p>
<h3><?php echo esc_html( $topic['title'] ); ?></h3>

<?php if ( $staff ) : ?>
	<div class="dfmg-actions dfmg-moderation">
		<?php echo $this->button( 'moderate', $topic['locked'] ? __( 'Unlock', 'underworld-empire' ) : __( 'Lock', 'underworld-empire' ), array( 'topic' => $topic['id'], 'task' => 'lock' ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
		<?php echo $this->button( 'moderate', $topic['sticky'] ? __( 'Unpin', 'underworld-empire' ) : __( 'Pin', 'underworld-empire' ), array( 'topic' => $topic['id'], 'task' => 'sticky' ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
		<?php echo $this->button( 'moderate', __( 'Delete topic', 'underworld-empire' ), array( 'topic' => $topic['id'], 'task' => 'delete_topic' ), 'dfmg-button dfmg-button--danger dfmg-button--small' ); // phpcs:ignore ?>
	</div>
<?php endif; ?>

<?php foreach ( $posts as $dfmg_p ) : ?>
	<?php $dfmg_author = Character::find( (int) $dfmg_p['character_id'] ); ?>
	<article class="dfmg-card dfmg-post" id="post-<?php echo (int) $dfmg_p['id']; ?>">
		<header>
			<?php echo $dfmg_author ? $dfmg_author->link() : ''; // phpcs:ignore ?>
			<?php if ( $dfmg_author ) : ?><small class="dfmg-muted"><?php echo esc_html( $dfmg_author->rank_name() ); ?></small><?php endif; ?>
			<small class="dfmg-post__date"><?php echo esc_html( Format::date( (int) $dfmg_p['created_at'] ) ); ?></small>
		</header>
		<div class="dfmg-usertext"><?php echo wp_kses_post( wpautop( make_clickable( esc_html( $dfmg_p['body'] ) ) ) ); ?></div>
		<?php if ( $staff ) : ?>
			<?php echo $this->button( 'moderate', __( 'Delete post', 'underworld-empire' ), array( 'topic' => $topic['id'], 'task' => 'delete_post', 'post' => $dfmg_p['id'] ), 'dfmg-button dfmg-button--ghost dfmg-button--small' ); // phpcs:ignore ?>
		<?php endif; ?>
	</article>
<?php endforeach; ?>

<?php echo UI::pager( $this->id(), array( 'topic' => $topic['id'] ), $paged, $pages ); // phpcs:ignore ?>

<?php if ( $can_post ) : ?>
	<div class="dfmg-card">
		<?php echo $this->form( 'reply', array( 'topic' => $topic['id'] ), 'dfmg-form dfmg-form--stacked' ); // phpcs:ignore ?>
			<textarea name="body" rows="5" placeholder="<?php esc_attr_e( 'Your reply…', 'underworld-empire' ); ?>" required></textarea>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Reply', 'underworld-empire' ); ?></button>
		</form>
	</div>
<?php else : ?>
	<p class="dfmg-muted"><?php esc_html_e( 'This topic is locked.', 'underworld-empire' ); ?></p>
<?php endif; ?>
