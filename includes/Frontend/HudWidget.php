<?php
/**
 * "Game element" widget: shows one game element (cash, timers, menu, …) in any widget area.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Frontend;

defined( 'ABSPATH' ) || exit;

class HudWidget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'dfmg_hud',
			__( 'Underworld Empire: game element', 'underworld-empire' ),
			array(
				'description'                 => __( 'Show a live piece of the game, such as cash, timers or the game menu.', 'underworld-empire' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	public function widget( $args, $instance ) {
		$key  = sanitize_key( $instance['element'] ?? 'player' );
		$html = Hud::render( $key, 'stack', 'widget' );
		if ( '' === $html ) {
			return;
		}
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the element.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		$title   = (string) ( $instance['title'] ?? '' );
		$element = (string) ( $instance['element'] ?? 'player' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'underworld-empire' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'element' ) ); ?>"><?php esc_html_e( 'Element:', 'underworld-empire' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'element' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'element' ) ); ?>">
				<?php foreach ( Hud::labels() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $element, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
		return '';
	}

	public function update( $new_instance, $old_instance ) {
		$key = sanitize_key( $new_instance['element'] ?? '' );
		return array(
			'title'   => sanitize_text_field( $new_instance['title'] ?? '' ),
			'element' => isset( Hud::labels()[ $key ] ) ? $key : 'player',
		);
	}
}
