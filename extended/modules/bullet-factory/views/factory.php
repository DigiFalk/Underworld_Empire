<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var array                          $location
 * @var \DigiFalk\MafiaPBBGEngine\Property  $property
 * @var int                            $price
 * @var int                            $max
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Frontend\UI;

defined( 'ABSPATH' ) || exit;

echo UI::property( $c, $property, $this->id() ); // phpcs:ignore

if ( $c->timer_active( 'bullets' ) ) {
	echo UI::cooldown( __( 'You can buy bullets again in', 'mafia-pbbg-engine' ), $c->timer( 'bullets' ) ); // phpcs:ignore
}
?>
<div class="dfmg-card">
	<table class="dfmg-table dfmg-table--keyvalue">
		<tr><th><?php esc_html_e( 'Stock', 'mafia-pbbg-engine' ); ?></th><td><?php echo esc_html( Format::number( $location['bullet_stock'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Price per bullet', 'mafia-pbbg-engine' ); ?></th><td><?php echo esc_html( Format::money( $price ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Max. per purchase', 'mafia-pbbg-engine' ); ?></th><td><?php echo esc_html( Format::number( $max ) ); ?></td></tr>
	</table>
	<?php echo $this->form( 'buy' ); // phpcs:ignore ?>
		<input type="number" name="qty" min="1" max="<?php echo esc_attr( (string) $max ); ?>" value="<?php echo esc_attr( (string) $max ); ?>">
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Buy', 'mafia-pbbg-engine' ); ?></button>
	</form>
</div>
