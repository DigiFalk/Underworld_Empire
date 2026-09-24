<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $location
 * @var \DigiFalk\MaffiaGame\Property  $property
 * @var int                            $price
 * @var int                            $max
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;

defined( 'ABSPATH' ) || exit;

echo UI::property( $c, $property, $this->id() ); // phpcs:ignore

if ( $c->timer_active( 'bullets' ) ) {
	echo UI::cooldown( __( 'You can buy bullets again in', 'wp-maffia-game' ), $c->timer( 'bullets' ) ); // phpcs:ignore
}
?>
<div class="dfmg-card">
	<table class="dfmg-table dfmg-table--keyvalue">
		<tr><th><?php esc_html_e( 'Stock', 'wp-maffia-game' ); ?></th><td><?php echo esc_html( Format::number( $location['bullet_stock'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Price per bullet', 'wp-maffia-game' ); ?></th><td><?php echo esc_html( Format::money( $price ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Max. per purchase', 'wp-maffia-game' ); ?></th><td><?php echo esc_html( Format::number( $max ) ); ?></td></tr>
	</table>
	<?php echo $this->form( 'buy' ); // phpcs:ignore ?>
		<input type="number" name="qty" min="1" max="<?php echo esc_attr( (string) $max ); ?>" value="<?php echo esc_attr( (string) $max ); ?>">
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Buy', 'wp-maffia-game' ); ?></button>
	</form>
</div>
