<?php
/**
 * @var \DigiFalk\MaffiaGame\Character $c
 * @var array                          $grouped
 * @var array                          $types
 *
 * @package DigiFalk\MaffiaGame
 */

use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\UI;
use DigiFalk\MaffiaGame\Items;

defined( 'ABSPATH' ) || exit;

if ( ! $grouped ) {
	echo UI::empty_state( __( 'De handelaar heeft vandaag niets in de aanbieding.', 'wp-maffia-game' ) ); // phpcs:ignore
	return;
}
foreach ( $grouped as $dfmg_type => $dfmg_items ) : ?>
	<h3><?php echo esc_html( $types[ $dfmg_type ]['label'] ?? $dfmg_type ); ?></h3>
	<div class="dfmg-grid dfmg-grid--3">
		<?php foreach ( $dfmg_items as $dfmg_item ) : ?>
			<div class="dfmg-card dfmg-item">
				<h4><?php echo esc_html( $dfmg_item['name'] ); ?></h4>
				<?php if ( $dfmg_item['description'] ) : ?>
					<p class="dfmg-muted"><?php echo esc_html( $dfmg_item['description'] ); ?></p>
				<?php endif; ?>
				<ul class="dfmg-effects">
					<?php foreach ( Items::describe_effects( $dfmg_item ) as $dfmg_line ) : ?>
						<li><?php echo esc_html( $dfmg_line ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p class="dfmg-price"><?php echo esc_html( Format::money( $dfmg_item['price'] ) ); ?></p>
				<?php echo $this->button( 'buy', __( 'Kopen', 'wp-maffia-game' ), array( 'item' => $dfmg_item['id'] ) ); // phpcs:ignore ?>
			</div>
		<?php endforeach; ?>
	</div>
<?php endforeach; ?>
