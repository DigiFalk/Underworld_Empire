<?php
/**
 * Custom Customizer controls: header/footer builder and per device values.
 *
 * @package MafiaPBBGEngineTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Drag & drop builder. The UI is built by assets/js/customize-controls.js.
 */
class MPET_Builder_Control extends WP_Customize_Control {

	/** @var string */
	public $type = 'mpet_builder';

	/** @var string header or footer */
	public $builder = 'header';

	public function render_content() {
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<?php if ( $this->description ) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>
		<p class="mpet-builder-note"><?php esc_html_e( 'The builder is shown at the bottom of the preview.', 'mafia-pbbg-engine-theme' ); ?></p>
		<input type="hidden" class="mpet-builder-value" <?php $this->link(); ?> value="<?php echo esc_attr( (string) $this->value() ); ?>">
		<div class="mpet-builder" data-builder="<?php echo esc_attr( $this->builder ); ?>" data-section="<?php echo esc_attr( $this->section ); ?>"></div>
		<?php
	}
}

/**
 * Number with separate desktop, tablet and mobile values.
 * Settings: default (desktop), tablet, mobile.
 */
class MPET_Responsive_Control extends WP_Customize_Control {

	/** @var string */
	public $type = 'mpet_responsive';

	public function render_content() {
		$devices = array(
			'default' => array( 'desktop', __( 'Desktop', 'mafia-pbbg-engine-theme' ) ),
			'tablet'  => array( 'tablet', __( 'Tablet', 'mafia-pbbg-engine-theme' ) ),
			'mobile'  => array( 'mobile', __( 'Mobile', 'mafia-pbbg-engine-theme' ) ),
		);
		?>
		<div class="mpet-responsive">
			<div class="mpet-responsive__head">
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<span class="mpet-responsive__devices" role="group">
					<?php foreach ( $devices as $key => $device ) : ?>
						<button type="button" class="mpet-device-btn" data-device="<?php echo esc_attr( $device[0] ); ?>" title="<?php echo esc_attr( $device[1] ); ?>" aria-label="<?php echo esc_attr( $device[1] ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( 'mobile' === $device[0] ? 'smartphone' : $device[0] ); ?>"></span></button>
					<?php endforeach; ?>
				</span>
			</div>
			<?php foreach ( $devices as $key => $device ) : ?>
				<?php if ( isset( $this->settings[ $key ] ) ) : ?>
					<div class="mpet-responsive__field" data-device="<?php echo esc_attr( $device[0] ); ?>">
						<input type="range" class="mpet-range" min="<?php echo esc_attr( (string) ( $this->input_attrs['min'] ?? 0 ) ); ?>" max="<?php echo esc_attr( (string) ( $this->input_attrs['max'] ?? 100 ) ); ?>" step="1" value="<?php echo esc_attr( (string) $this->value( $key ) ); ?>">
						<input type="number" class="mpet-number" min="<?php echo esc_attr( (string) ( $this->input_attrs['min'] ?? 0 ) ); ?>" max="<?php echo esc_attr( (string) ( $this->input_attrs['max'] ?? 100 ) ); ?>" step="1" <?php $this->link( $key ); ?> value="<?php echo esc_attr( (string) $this->value( $key ) ); ?>">
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
