<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var array|null                     $game
 * @var \DigiFalk\MafiaPBBGEngine\Property  $table
 * @var int                            $min
 * @var int                            $max
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Frontend\UI;
use DigiFalk\MafiaPBBGEngine\Modules\Blackjack;

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
		<p><?php esc_html_e( 'Bet:', 'mafia-pbbg-engine' ); ?> <strong><?php echo esc_html( Format::money( $game['bet'] ) ); ?></strong></p>
		<h4><?php esc_html_e( 'Bank', 'mafia-pbbg-engine' ); ?></h4>
		<?php echo $dfmg_cards( $game['dealer'], true ); // phpcs:ignore ?>
		<h4><?php esc_html_e( 'You', 'mafia-pbbg-engine' ); ?> (<?php echo (int) Blackjack::score( $game['player'] ); ?>)</h4>
		<?php echo $dfmg_cards( $game['player'] ); // phpcs:ignore ?>
		<div class="dfmg-actions">
			<?php echo $this->button( 'hit', __( 'Hit', 'mafia-pbbg-engine' ) ); // phpcs:ignore ?>
			<?php echo $this->button( 'stand', __( 'Stand', 'mafia-pbbg-engine' ), array(), 'dfmg-button dfmg-button--ghost' ); // phpcs:ignore ?>
		</div>
	<?php else : ?>
		<?php if ( $dfmg_last ) : ?>
			<div class="dfmg-bj-last">
				<h4><?php esc_html_e( 'Bank', 'mafia-pbbg-engine' ); ?> (<?php echo (int) Blackjack::score( $dfmg_last['dealer'] ); ?>)</h4>
				<?php echo $dfmg_cards( $dfmg_last['dealer'] ); // phpcs:ignore ?>
				<h4><?php esc_html_e( 'You', 'mafia-pbbg-engine' ); ?> (<?php echo (int) Blackjack::score( $dfmg_last['player'] ); ?>)</h4>
				<?php echo $dfmg_cards( $dfmg_last['player'] ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<?php echo $this->form( 'bet' ); // phpcs:ignore ?>
			<label>
				<?php
				/* translators: 1: min, 2: max */
				printf( esc_html__( 'Bet (%1$s – %2$s)', 'mafia-pbbg-engine' ), esc_html( Format::money( $min ) ), esc_html( Format::money( $max ) ) );
				?>
				<input type="text" inputmode="numeric" name="bet" value="<?php echo esc_attr( (string) ( $dfmg_last['bet'] ?? $min ) ); ?>">
			</label>
			<button type="submit" class="dfmg-button"><?php esc_html_e( 'Deal', 'mafia-pbbg-engine' ); ?></button>
		</form>
	<?php endif; ?>
</div>
