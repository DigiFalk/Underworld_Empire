<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var array                          $family
 * @var array                          $members
 * @var int                            $cap
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Character;
use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Locations;
use DigiFalk\MafiaPBBGEngine\Modules\Families;

defined( 'ABSPATH' ) || exit;
?>
<div class="dfmg-card">
	<h3><?php echo esc_html( $family['name'] ); ?></h3>
	<table class="dfmg-table dfmg-table--keyvalue">
		<tr><th><?php esc_html_e( 'City', 'mafia-pbbg-engine' ); ?></th><td><?php echo esc_html( Locations::name( (int) $family['location_id'] ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'Boss', 'mafia-pbbg-engine' ); ?></th><td><?php echo Character::link_by_id( (int) $family['boss_id'] ); // phpcs:ignore ?></td></tr>
		<?php if ( (int) $family['underboss_id'] ) : ?>
			<tr><th><?php esc_html_e( 'Underboss', 'mafia-pbbg-engine' ); ?></th><td><?php echo Character::link_by_id( (int) $family['underboss_id'] ); // phpcs:ignore ?></td></tr>
		<?php endif; ?>
		<tr><th><?php esc_html_e( 'Members', 'mafia-pbbg-engine' ); ?></th><td><?php echo (int) count( $members ) . ' / ' . (int) $cap; ?></td></tr>
		<tr><th><?php esc_html_e( 'Founded', 'mafia-pbbg-engine' ); ?></th><td><?php echo esc_html( Format::date( (int) $family['created_at'] ) ); ?></td></tr>
	</table>
	<?php if ( $family['description'] ) : ?>
		<div class="dfmg-usertext"><?php echo Format::user_text( (string) $family['description'] ); // phpcs:ignore ?></div>
	<?php endif; ?>
</div>
<h3><?php esc_html_e( 'Members', 'mafia-pbbg-engine' ); ?></h3>
<table class="dfmg-table">
	<?php foreach ( $members as $dfmg_m ) : ?>
		<tr>
			<td><?php echo $dfmg_m['character']->link(); // phpcs:ignore ?></td>
			<td><?php echo esc_html( Families::role( $family, $dfmg_m['character']->id() ) ); ?></td>
			<td><?php echo esc_html( $dfmg_m['character']->rank_name() ); ?></td>
		</tr>
	<?php endforeach; ?>
</table>
<p><a href="<?php echo esc_url( $this->url() ); ?>">&larr; <?php esc_html_e( 'All families', 'mafia-pbbg-engine' ); ?></a></p>
