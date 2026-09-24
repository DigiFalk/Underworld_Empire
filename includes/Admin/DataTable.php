<?php
/**
 * Generic list/create/edit/delete screen for game data tables.
 *
 * Definition format (returned by Module::admin_tables()):
 *
 *   'crimes' => [
 *       'label'      => 'Crimes',
 *       'table'      => 'crimes',            // short table name
 *       'order'      => 'id ASC',
 *       'can_create' => true,
 *       'can_delete' => true,
 *       'search'     => 'name',              // optional search column
 *       'columns'    => [
 *           'name'   => [ 'label' => 'Name', 'type' => 'text', 'required' => true ],
 *           'money'  => [ 'label' => 'Money', 'type' => 'int', 'list' => true ],
 *           'city'   => [ 'label' => 'City', 'type' => 'select', 'options' => callable|array ],
 *           'text'   => [ 'label' => 'Text', 'type' => 'textarea', 'list' => false ],
 *       ],
 *   ]
 *
 * @package DigiFalk\MafiaGame
 */

namespace DigiFalk\MafiaGame\Admin;

use DigiFalk\MafiaGame\DB;
use DigiFalk\MafiaGame\Locations;
use DigiFalk\MafiaGame\Ranks;

defined( 'ABSPATH' ) || exit;

final class DataTable {

	const PER_PAGE = 50;

	/**
	 * Normalize a definition.
	 */
	public static function normalize( array $def ): array {
		$def = wp_parse_args(
			$def,
			array(
				'label'      => '',
				'table'      => '',
				'order'      => 'id ASC',
				'can_create' => true,
				'can_delete' => true,
				'search'     => '',
				'columns'    => array(),
				'help'       => '',
			)
		);
		foreach ( $def['columns'] as $key => $col ) {
			$def['columns'][ $key ] = wp_parse_args(
				$col,
				array(
					'label'       => $key,
					'type'        => 'text',
					'list'        => 'textarea' !== ( $col['type'] ?? 'text' ),
					'required'    => false,
					'default'     => '',
					'description' => '',
					'options'     => array(),
				)
			);
		}
		return $def;
	}

	private static function options( array $col ): array {
		$options = $col['options'];
		return is_callable( $options ) ? (array) call_user_func( $options ) : (array) $options;
	}

	private static function display_value( array $col, $value ): string {
		switch ( $col['type'] ) {
			case 'select':
				$options = self::options( $col );
				return (string) ( $options[ $value ] ?? $value );
			case 'checkbox':
				return $value ? '✔' : '—';
			case 'int':
				return number_format_i18n( (float) $value );
			default:
				return wp_trim_words( wp_strip_all_tags( (string) $value ), 12 );
		}
	}

	public static function base_url( string $key, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => 'dfmg-data', 'table' => $key ), $args ), admin_url( 'admin.php' ) );
	}

	public static function render_list( string $key, array $def ): void {
		$def    = self::normalize( $def );
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$where  = '';
		$args   = array();
		if ( $search && $def['search'] ) {
			$where  = 'WHERE `' . esc_sql( $def['search'] ) . '` LIKE %s';
			$args[] = '%' . DB::wpdb()->esc_like( $search ) . '%';
		}
		$order = preg_replace( '/[^a-z0-9_ ,]/i', '', $def['order'] );
		$total = (int) DB::value( 'SELECT COUNT(*) FROM {' . $def['table'] . '} ' . $where, ...$args );
		$rows  = DB::results(
			'SELECT * FROM {' . $def['table'] . '} ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . ( ( $paged - 1 ) * self::PER_PAGE ),
			...$args
		);
		$list_cols = array_filter(
			$def['columns'],
			static function ( $c ) {
				return $c['list'];
			}
		);
		?>
		<h2>
			<?php echo esc_html( $def['label'] ); ?>
			<?php if ( $def['can_create'] ) : ?>
				<a class="page-title-action" href="<?php echo esc_url( self::base_url( $key, array( 'edit' => 'new' ) ) ); ?>"><?php esc_html_e( 'Add new', 'wp-mafia-game' ); ?></a>
			<?php endif; ?>
		</h2>
		<?php if ( $def['help'] ) : ?>
			<p class="description"><?php echo wp_kses_post( $def['help'] ); ?></p>
		<?php endif; ?>
		<?php if ( $def['search'] ) : ?>
			<form method="get" class="dfmg-admin-search">
				<input type="hidden" name="page" value="dfmg-data">
				<input type="hidden" name="table" value="<?php echo esc_attr( $key ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>">
				<button class="button"><?php esc_html_e( 'Search', 'wp-mafia-game' ); ?></button>
			</form>
		<?php endif; ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>#</th>
					<?php foreach ( $list_cols as $col ) : ?>
						<th><?php echo esc_html( $col['label'] ); ?></th>
					<?php endforeach; ?>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="<?php echo count( $list_cols ) + 2; ?>"><?php esc_html_e( 'Nothing added yet.', 'wp-mafia-game' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['id'] ); ?></td>
						<?php foreach ( $list_cols as $name => $col ) : ?>
							<td><?php echo esc_html( self::display_value( $col, $row[ $name ] ?? '' ) ); ?></td>
						<?php endforeach; ?>
						<td class="dfmg-admin-actions">
							<a href="<?php echo esc_url( self::base_url( $key, array( 'edit' => $row['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-mafia-game' ); ?></a>
							<?php if ( $def['can_delete'] ) : ?>
								| <a class="dfmg-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'wp-mafia-game' ) ); ?>');" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dfmg_data_delete&table=' . rawurlencode( $key ) . '&id=' . (int) $row['id'] ), 'dfmg_data_delete_' . $key . '_' . $row['id'] ) ); ?>"><?php esc_html_e( 'Delete', 'wp-mafia-game' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages > 1 ) {
			echo '<p class="dfmg-admin-pages">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				printf(
					'<a class="button %1$s" href="%2$s">%3$d</a> ',
					$i === $paged ? 'button-primary' : '',
					esc_url( self::base_url( $key, array( 'paged' => $i, 's' => $search ) ) ),
					(int) $i
				);
			}
			echo '</p>';
		}
	}

	/**
	 * @param int|string $id Row id or 'new'.
	 */
	public static function render_form( string $key, array $def, $id ): void {
		$def = self::normalize( $def );
		$row = array();
		if ( 'new' !== $id ) {
			$row = DB::row( 'SELECT * FROM {' . $def['table'] . '} WHERE id = %d', (int) $id ) ?: array();
			if ( ! $row ) {
				echo '<p>' . esc_html__( 'Not found.', 'wp-mafia-game' ) . '</p>';
				return;
			}
		} elseif ( ! $def['can_create'] ) {
			return;
		}
		?>
		<h2><?php echo esc_html( $def['label'] ); ?> &mdash; <?php echo 'new' === $id ? esc_html__( 'new', 'wp-mafia-game' ) : '#' . (int) $id; ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="dfmg_data_save">
			<input type="hidden" name="table" value="<?php echo esc_attr( $key ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
			<?php wp_nonce_field( 'dfmg_data_save_' . $key ); ?>
			<table class="form-table">
				<?php foreach ( $def['columns'] as $name => $col ) : ?>
					<?php $value = $row[ $name ] ?? $col['default']; ?>
					<tr>
						<th><label for="dfmg-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $col['label'] ); ?></label></th>
						<td>
							<?php self::field( $name, $col, $value ); ?>
							<?php if ( $col['description'] ) : ?>
								<p class="description"><?php echo wp_kses_post( $col['description'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( __( 'Save', 'wp-mafia-game' ) ); ?>
			<a href="<?php echo esc_url( self::base_url( $key ) ); ?>">&larr; <?php esc_html_e( 'Back to overview', 'wp-mafia-game' ); ?></a>
		</form>
		<?php
	}

	/**
	 * Render a single input. Shared with the settings page.
	 *
	 * @param mixed $value
	 */
	public static function field( string $name, array $col, $value, string $input_name = '' ): void {
		$input_name = $input_name ?: 'fields[' . $name . ']';
		$id         = 'dfmg-' . $name;
		switch ( $col['type'] ) {
			case 'textarea':
				printf( '<textarea class="large-text" rows="5" id="%1$s" name="%2$s">%3$s</textarea>', esc_attr( $id ), esc_attr( $input_name ), esc_textarea( (string) $value ) );
				break;
			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $input_name ) );
				foreach ( self::options( $col ) as $opt => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( (string) $opt ), selected( (string) $opt, (string) $value, false ), esc_html( (string) $label ) );
				}
				echo '</select>';
				break;
			case 'checkbox':
				printf( '<input type="hidden" name="%1$s" value="0"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s>', esc_attr( $input_name ), esc_attr( $id ), checked( (bool) $value, true, false ) );
				break;
			case 'int':
				printf( '<input type="number" step="1" class="regular-text" id="%1$s" name="%2$s" value="%3$s">', esc_attr( $id ), esc_attr( $input_name ), esc_attr( (string) $value ) );
				break;
			case 'datetime':
				printf( '<input type="datetime-local" id="%1$s" name="%2$s" value="%3$s">', esc_attr( $id ), esc_attr( $input_name ), esc_attr( str_replace( ' ', 'T', (string) $value ) ) );
				break;
			default:
				printf( '<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" %4$s>', esc_attr( $id ), esc_attr( $input_name ), esc_attr( (string) $value ), ! empty( $col['required'] ) ? 'required' : '' );
		}
	}

	/**
	 * Sanitize a submitted value according to its column type.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public static function sanitize( array $col, $value ) {
		switch ( $col['type'] ) {
			case 'int':
				return (int) $value;
			case 'checkbox':
				return $value ? 1 : 0;
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'select':
				$options = self::options( $col );
				return array_key_exists( $value, $options ) ? $value : $col['default'];
			case 'datetime':
				$value = sanitize_text_field( str_replace( 'T', ' ', (string) $value ) );
				return $value && strtotime( $value ) ? gmdate( 'Y-m-d H:i', strtotime( $value ) ) : '';
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	public static function save( string $key, array $def ): void {
		$def    = self::normalize( $def );
		$id     = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by caller.
		$fields = (array) wp_unslash( $_POST['fields'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data   = array();
		foreach ( $def['columns'] as $name => $col ) {
			$data[ $name ] = self::sanitize( $col, $fields[ $name ] ?? '' );
		}
		$data = apply_filters( 'dfmg_admin_save_data', $data, $key, $id );
		if ( 'new' === $id ) {
			if ( $def['can_create'] ) {
				DB::insert( $def['table'], $data );
			}
		} else {
			DB::update( $def['table'], $data, array( 'id' => (int) $id ) );
		}
		Ranks::flush();
		Locations::flush();
		do_action( 'dfmg_admin_data_saved', $key, $id, $data );
	}

	public static function delete( string $key, array $def, int $id ): void {
		$def = self::normalize( $def );
		if ( $def['can_delete'] ) {
			DB::delete( $def['table'], array( 'id' => $id ) );
			do_action( 'dfmg_admin_data_deleted', $key, $id );
		}
	}
}
