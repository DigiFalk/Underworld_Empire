<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var string                         $to
 * @var string                         $subject
 * @var int                            $reply
 *
 * @package DigiFalk\MaffiaGame
 */

defined( 'ABSPATH' ) || exit;

echo $this->view( 'tabs', array( 'active' => 'compose' ) ); // phpcs:ignore
?>
<div class="dfmg-card">
	<?php echo $this->form( 'send', array( 'reply' => $reply ), 'dfmg-form dfmg-form--stacked' ); // phpcs:ignore ?>
		<label><?php esc_html_e( 'Aan', 'wp-maffia-game' ); ?><input type="text" name="to" value="<?php echo esc_attr( $to ); ?>" required></label>
		<label><?php esc_html_e( 'Onderwerp', 'wp-maffia-game' ); ?><input type="text" name="subject" maxlength="120" value="<?php echo esc_attr( $subject ); ?>"></label>
		<label><?php esc_html_e( 'Bericht', 'wp-maffia-game' ); ?><textarea name="body" rows="8" required></textarea></label>
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Versturen', 'wp-maffia-game' ); ?></button>
	</form>
</div>
