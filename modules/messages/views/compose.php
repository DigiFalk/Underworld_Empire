<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var string                         $to
 * @var string                         $subject
 * @var int                            $reply
 *
 * @package DigiFalk\UnderworldEmpire
 */

defined( 'ABSPATH' ) || exit;

echo $this->view( 'tabs', array( 'active' => 'compose' ) ); // phpcs:ignore
?>
<div class="dfmg-card">
	<?php echo $this->form( 'send', array( 'reply' => $reply ), 'dfmg-form dfmg-form--stacked' ); // phpcs:ignore ?>
		<label><?php esc_html_e( 'To', 'underworld-empire' ); ?><input type="text" name="to" value="<?php echo esc_attr( $to ); ?>" required></label>
		<label><?php esc_html_e( 'Subject', 'underworld-empire' ); ?><input type="text" name="subject" maxlength="120" value="<?php echo esc_attr( $subject ); ?>"></label>
		<label><?php esc_html_e( 'Message', 'underworld-empire' ); ?><textarea name="body" rows="8" required></textarea></label>
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Send', 'underworld-empire' ); ?></button>
	</form>
</div>
