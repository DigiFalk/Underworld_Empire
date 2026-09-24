<?php
/**
 * @var \DigiFalk\MafiaGame\Character $c
 * @var array|null                     $game
 * @var \DigiFalk\MafiaGame\Property  $table
 * @var int                            $min
 * @var int                            $max
 *
 * @package DigiFalk\MafiaGame
 */

use DigiFalk\MafiaGame\Format;
use DigiFalk\MafiaGame\Frontend\UI;
use DigiFalk\MafiaGame\Modules\Blackjack;

defined( 'ABSPATH' ) || exit;

$dfmg_cards = static function ( array $hand, bool $hide_second = false ) {
	$html = '<div class="dfmg-cards">';
	foreach ( $hand as $i => $card ) {
		if ( $hide_second && 1 === $i ) {
			$html .= '<span class="dfmg-card-face is-hidden">?</span>';
			continue;
		}
		$red   = in_array( $card['s'], array( '♥', '♦' ), true ) ? ' is-red' : '';
		$html .= '<span class="dfmg-card-face' . $red . '">' . esc_html( $card['r'] . $card['s'] ) . '</span>';
	}
	return $html . '</div>';
};

echo UI::property( $c, $table, $this->id() ); // phpcs:ignore

$dfmg_last = $game ? null : $this->last_game( $c );
?>
<div class="dfmg-card dfmg-blackjack">
	<?php if ( $game ) : ?>
		<p><?php esc_html_e( 'Bet:', 'wp-mafia-game' ); ?> <strong><?php echo esc_html( Format::money( $game['bet'] ) ); ?></strong></p>
		<h4><?php esc_html_e( 'Bank', 'wp-mafia-game' ); ?></h4>
		<?php echo $dfmg_cards( $game['dealer'], true ); // phpcs:ignore ?>
		<h4><?php esc_html_e( 'You', 'wp-mafia-game' ); ?> (<?php echo (int) Blackjack::score( $game['player'] ); ?>)</h4>
		<?php echo $dfmg_cards( $game['player'] ); // phpcs:ignore ?>
		<div class="dfmg-actions">
			<?php echo $this->button( 'hit', __( 'Hit', 'wp-mafia-game' ) ); // phpcs:ignore ?>
			<?php echo $this->button( 'stand', __( 'Stand', 'wp-mafia-game' ), array(), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
		</div>
	<?php else : ?>
		<?php if ( $dfmg_last ) : ?>
			<div class="dfmg-bj-last">
				<h4><?php esc_html_e( 'Bank', 'wp-mafia-game' ); ?> (<?php echo (int) Blackjack::score( $dfmg_last['dealer'] ); ?>)</h4>
				<?php echo $dfmg_cards( $dfmg_last['dealer'] ); // phpcs:ignore ?>
				<h4><?php esc_html_e( 'You', 'wp-mafia-game' ); ?> (<?php echo (int) Blackjack::score( $dfmg_last['player'] ); ?>)</h4>
				<?php echo $dfmg_cards( $dfmg_last['player'] ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<?php echo $this->form( 'bet' ); // phpcs:ignore ?>
			<label>
				<?php
				/* translators: 1: min, 2: max */
				printf( esc_html__( 'Bet (%1$s – %2$s)', 'wp-mafia-game' ), esc_html( Format::money( $min ) ), esc_html( Format::money( $max ) ) );
				?>
				<input type="text" inputmode="numeric" name="bet" value="<?php echo esc_attr( (string) ( $dfmg_last['bet'] ?? $min ) ); ?>">
			</label>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deal', 'wp-mafia-game' ); ?></button>
		</form>
	<?php endif; ?>
</div>
