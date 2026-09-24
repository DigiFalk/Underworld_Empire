<?php
/**
 * Customizer control for the game layout. The drag & drop UI is built by assets/js/customize-layout.js.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

defined( 'ABSPATH' ) || exit;

class LayoutControl extends \WP_Customize_Control {

	/** @var string */
	public $type = 'dfmg_layout';

	public function render_content() {
		?>
		<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		<p class="description customize-control-description"><?php esc_html_e( 'The layout builder is shown at the bottom of the preview. Changes are visible immediately.', 'underworld-empire' ); ?></p>
		<input type="hidden" class="dfmg-layout-value" <?php $this->link(); ?> value="<?php echo esc_attr( (string) $this->value() ); ?>">
		<?php
	}
}
