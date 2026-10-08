<?php
/**
 * Shown when the round has not started or has ended.
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

use DigiFalk\MafiaPBBGEngine\Settings;

defined( 'ABSPATH' ) || exit;

$dfmg_start = (string) Settings::get( 'round_start', '' );
?>
<div class="dfmg-welcome dfmg-welcome--closed">
	<div class="dfmg-card">
		<span class="dfmg-emblem"><?php echo \DigiFalk\MafiaPBBGEngine\Icons::svg( 'timer', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h2><?php echo esc_html( (string) Settings::get( 'round_name' ) ); ?></h2>
		<?php if ( $dfmg_start && strtotime( $dfmg_start ) > current_time( 'timestamp' ) ) : // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested ?>
			<p>
				<?php
				/* translators: %s: date */
				printf( esc_html__( 'The round starts on %s.', 'mafia-pbbg-engine' ), esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $dfmg_start ) ) );
				?>
			</p>
		<?php else : ?>
			<p><?php esc_html_e( 'This round has ended. Keep an eye on the site for the next round!', 'mafia-pbbg-engine' ); ?></p>
		<?php endif; ?>
	</div>
</div>
