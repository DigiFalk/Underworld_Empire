<?php
/**
 * @var \DigiFalk\MafiaPBBGEngine\Character $c
 * @var array                          $reports
 * @var int                            $protected
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Frontend\UI;
use DigiFalk\MafiaPBBGEngine\Locations;

defined( 'ABSPATH' ) || exit;

if ( $protected > time() ) {
	echo UI::cooldown( __( 'You are under new player protection. You can attack in', 'mafia-pbbg-engine' ), $protected ); // phpcs:ignore
}
if ( $c->timer_active( 'murder' ) ) {
	echo UI::cooldown( __( 'You can shoot again in', 'mafia-pbbg-engine' ), $c->timer( 'murder' ) ); // phpcs:ignore
}
?>
<div class="dfmg-card">
	<p>
		<?php
		/* translators: 1: attack, 2: bullets */
		printf( esc_html__( 'Your attack power is %1$s and you have %2$s bullets. The more bullets, the more damage.', 'mafia-pbbg-engine' ), '<strong>' . esc_html( (string) round( $c->attack_power() ) ) . '</strong>', '<strong>' . esc_html( Format::number( $c->bullets ) ) . '</strong>' );
		?>
	</p>
	<?php if ( ! $reports ) : ?>
		<p>
			<?php esc_html_e( 'You don\'t have a valid detective report.', 'mafia-pbbg-engine' ); ?>
			<a href="<?php echo esc_url( $this->url( array(), 'detectives' ) ); ?>"><?php esc_html_e( 'Hire detectives', 'mafia-pbbg-engine' ); ?></a>
		</p>
	<?php else : ?>
		<table class="dfmg-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Target', 'mafia-pbbg-engine' ); ?></th>
					<th><?php esc_html_e( 'City', 'mafia-pbbg-engine' ); ?></th>
					<th><?php esc_html_e( 'Report valid', 'mafia-pbbg-engine' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $reports as $dfmg_r ) : ?>
					<tr>
						<td><?php echo $dfmg_r['target']->link(); // phpcs:ignore ?></td>
						<td><?php echo esc_html( Locations::name( (int) $dfmg_r['found_location'] ) ); ?></td>
						<td><?php echo Format::countdown( (int) $dfmg_r['ready_at'] + (int) dfmg_setting( 'detective_valid', 900 ) ); // phpcs:ignore ?></td>
						<td>
							<?php echo $this->form( 'shoot', array( 'report' => $dfmg_r['id'] ), 'dfmg-inline-form' ); // phpcs:ignore ?>
								<input type="number" name="bullets" min="1" max="<?php echo esc_attr( (string) $c->bullets ); ?>" placeholder="<?php esc_attr_e( 'Bullets', 'mafia-pbbg-engine' ); ?>" required>
								<button type="submit" class="dfmg-button dfmg-button--danger" onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'mafia-pbbg-engine' ) ); ?>');"><?php esc_html_e( 'Shoot', 'mafia-pbbg-engine' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
