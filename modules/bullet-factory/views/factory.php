<?php
/**
 * @var \DigiFalk\UnderworldEmpire\Character $c
 * @var array                          $location
 * @var \DigiFalk\UnderworldEmpire\Property  $property
 * @var int                            $price
 * @var int                            $max
 *
 * @package DigiFalk\UnderworldEmpire
 */

use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\UI;

defined( 'ABSPATH' ) || exit;

echo UI::property( $c, $property, $this->id() ); // phpcs:ignore

if ( $c->timer_active( 'bullets' ) ) {
	echo UI::cooldown( __( 'You can buy bullets again in', 'underworld-empire' ), $c->timer( 'bullets' ) ); // phpcs:ignore
}
?>
<div class="dfmg-card">
	<table class="dfmg-table dfmg-table--keyvalue">
		<tr><th><?php esc_html_e( 'Stock', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::number( $location['bullet_stock'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Price per bullet', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::money( $price ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Max. per purchase', 'underworld-empire' ); ?></th><td><?php echo esc_html( Format::number( $max ) ); ?></td></tr>
	</table>
	<?php echo $this->form( 'buy' ); // phpcs:ignore ?>
		<input type="number" name="qty" min="1" max="<?php echo esc_attr( (string) $max ); ?>" value="<?php echo esc_attr( (string) $max ); ?>">
		<button type="submit" class="dfmg-button"><?php esc_html_e( 'Buy', 'underworld-empire' ); ?></button>
	</form>
</div>
