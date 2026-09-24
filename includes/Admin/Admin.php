<?php
/**
 * WordPress admin screens.
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Admin;

use DigiFalk\MaffiaGame\DB;
use DigiFalk\MaffiaGame\Format;
use DigiFalk\MaffiaGame\Frontend\Game;
use DigiFalk\MaffiaGame\Items;
use DigiFalk\MaffiaGame\Locations;
use DigiFalk\MaffiaGame\Plugin;
use DigiFalk\MaffiaGame\Ranks;
use DigiFalk\MaffiaGame\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const CAP = 'dfmg_manage';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_dfmg_data_save', array( __CLASS__, 'handle_data_save' ) );
		add_action( 'admin_post_dfmg_data_delete', array( __CLASS__, 'handle_data_delete' ) );
		add_action( 'admin_post_dfmg_settings', array( __CLASS__, 'handle_settings' ) );
		add_action( 'admin_post_dfmg_module', array( __CLASS__, 'handle_module' ) );
		add_action( 'admin_post_dfmg_new_round', array( __CLASS__, 'handle_new_round' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DFMG_FILE ), array( __CLASS__, 'plugin_links' ) );
	}

	/**
	 * Administrators always have access, even if the capability was never added.
	 */
	public static function can(): bool {
		return current_user_can( self::CAP ) || current_user_can( 'manage_options' );
	}

	private static function cap(): string {
		return current_user_can( self::CAP ) ? self::CAP : 'manage_options';
	}

	public static function plugin_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=dfmg' ) ) . '">' . esc_html__( 'Manage', 'wp-maffia-game' ) . '</a>' );
		return $links;
	}

	public static function assets( string $hook ): void {
		if ( false !== strpos( $hook, 'dfmg' ) ) {
			wp_enqueue_style( 'dfmg-admin', DFMG_URL . 'assets/css/admin.css', array(), DFMG_VERSION );
		}
	}

	public static function menu(): void {
		$cap = self::cap();
		add_menu_page( __( 'Mafia Game', 'wp-maffia-game' ), __( 'Mafia Game', 'wp-maffia-game' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ), 'dashicons-shield-alt', 58 );
		add_submenu_page( 'dfmg', __( 'Dashboard', 'wp-maffia-game' ), __( 'Dashboard', 'wp-maffia-game' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'dfmg', __( 'Modules', 'wp-maffia-game' ), __( 'Modules', 'wp-maffia-game' ), $cap, 'dfmg-modules', array( __CLASS__, 'page_modules' ) );
		add_submenu_page( 'dfmg', __( 'Game data', 'wp-maffia-game' ), __( 'Game data', 'wp-maffia-game' ), $cap, 'dfmg-data', array( __CLASS__, 'page_data' ) );
		add_submenu_page( 'dfmg', __( 'Settings', 'wp-maffia-game' ), __( 'Settings', 'wp-maffia-game' ), $cap, 'dfmg-settings', array( __CLASS__, 'page_settings' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Data tables                                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * All editable tables: core ones plus those of active modules.
	 */
	public static function tables(): array {
		$tables = array(
			'characters'  => array(
				'label'      => __( 'Players', 'wp-maffia-game' ),
				'table'      => 'characters',
				'order'      => 'id DESC',
				'can_create' => false,
				'search'     => 'name',
				'help'       => __( 'Player characters. Here you can, among other things, award premium points.', 'wp-maffia-game' ),
				'columns'    => array(
					'name'        => array( 'label' => __( 'Name', 'wp-maffia-game' ), 'type' => 'text', 'required' => true ),
					'status'      => array(
						'label'   => __( 'Status', 'wp-maffia-game' ),
						'type'    => 'select',
						'options' => array(
							1 => __( 'Alive', 'wp-maffia-game' ),
							0 => __( 'Dead', 'wp-maffia-game' ),
						),
					),
					'money'       => array( 'label' => __( 'Cash', 'wp-maffia-game' ), 'type' => 'int' ),
					'bank'        => array( 'label' => __( 'Bank', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullets'     => array( 'label' => __( 'Bullets', 'wp-maffia-game' ), 'type' => 'int' ),
					'exp'         => array( 'label' => __( 'Experience', 'wp-maffia-game' ), 'type' => 'int' ),
					'points'      => array( 'label' => __( 'Points', 'wp-maffia-game' ), 'type' => 'int' ),
					'damage'      => array( 'label' => __( 'Damage', 'wp-maffia-game' ), 'type' => 'int', 'list' => false ),
					'rank_id'     => array( 'label' => __( 'Rank', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Ranks::class, 'options' ) ),
					'location_id' => array( 'label' => __( 'City', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Locations::class, 'options' ) ),
					'bio'         => array( 'label' => __( 'Profile text', 'wp-maffia-game' ), 'type' => 'textarea' ),
				),
			),
			'ranks'       => array(
				'label'   => __( 'Ranks', 'wp-maffia-game' ),
				'table'   => 'ranks',
				'order'   => 'exp_required ASC',
				'columns' => array(
					'name'          => array( 'label' => __( 'Name', 'wp-maffia-game' ), 'required' => true ),
					'exp_required'  => array( 'label' => __( 'Required experience', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_players'   => array( 'label' => __( 'Max. players (0 = unlimited)', 'wp-maffia-game' ), 'type' => 'int' ),
					'cash_reward'   => array( 'label' => __( 'Cash reward', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_reward' => array( 'label' => __( 'Bullet reward', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_health'    => array( 'label' => __( 'Health', 'wp-maffia-game' ), 'type' => 'int', 'default' => 1000 ),
				),
			),
			'money_ranks' => array(
				'label'   => __( 'Wealth titles', 'wp-maffia-game' ),
				'table'   => 'money_ranks',
				'order'   => 'min_money ASC',
				'columns' => array(
					'name'      => array( 'label' => __( 'Title', 'wp-maffia-game' ), 'required' => true ),
					'min_money' => array( 'label' => __( 'From amount', 'wp-maffia-game' ), 'type' => 'int' ),
				),
			),
			'locations'   => array(
				'label'   => __( 'Cities', 'wp-maffia-game' ),
				'table'   => 'locations',
				'columns' => array(
					'name'         => array( 'label' => __( 'Name', 'wp-maffia-game' ), 'required' => true ),
					'travel_cost'  => array( 'label' => __( 'Travel cost', 'wp-maffia-game' ), 'type' => 'int' ),
					'travel_time'  => array( 'label' => __( 'Cooldown after travelling (sec)', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_stock' => array( 'label' => __( 'Bullet stock', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_price' => array( 'label' => __( 'Default bullet price', 'wp-maffia-game' ), 'type' => 'int' ),
				),
			),
			'items'       => array(
				'label'   => __( 'Items', 'wp-maffia-game' ),
				'table'   => 'items',
				'search'  => 'name',
				'columns' => array(
					'name'        => array( 'label' => __( 'Name', 'wp-maffia-game' ), 'required' => true ),
					'type'        => array( 'label' => __( 'Type', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Items::class, 'type_options' ) ),
					'price'       => array( 'label' => __( 'Price', 'wp-maffia-game' ), 'type' => 'int' ),
					'buyable'     => array( 'label' => __( 'For sale on the black market', 'wp-maffia-game' ), 'type' => 'checkbox', 'default' => 1 ),
					'description' => array( 'label' => __( 'Description', 'wp-maffia-game' ), 'type' => 'textarea' ),
					'effects'     => array(
						'label'       => __( 'Effects', 'wp-maffia-game' ),
						'type'        => 'textarea',
						'description' => self::effects_help(),
					),
				),
			),
		);
		foreach ( Plugin::instance()->modules->active() as $module ) {
			foreach ( $module->admin_tables() as $key => $def ) {
				$def['module']  = $module->name();
				$tables[ $key ] = $def;
			}
		}
		return apply_filters( 'dfmg_admin_tables', $tables );
	}

	private static function effects_help(): string {
		$lines = array();
		foreach ( Items::effects() as $key => $effect ) {
			$lines[] = '<code>' . esc_html( $key ) . '=…</code> ' . esc_html( $effect['label'] );
		}
		return __( 'One effect per line.', 'wp-maffia-game' ) . '<br>' . implode( '<br>', $lines );
	}

	public static function page_data(): void {
		if ( ! self::can() ) {
			return;
		}
		$tables  = self::tables();
		$current = sanitize_key( wp_unslash( $_GET['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $tables[ $current ] ) ) {
			$current = (string) key( $tables );
		}
		$edit = sanitize_text_field( wp_unslash( $_GET['edit'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="wrap dfmg-admin"><h1>' . esc_html__( 'Game data', 'wp-maffia-game' ) . '</h1>';
		self::notices();
		echo '<div class="dfmg-admin-data"><ul class="dfmg-admin-tabs">';
		$group = null;
		foreach ( $tables as $key => $def ) {
			$g = $def['module'] ?? __( 'Core', 'wp-maffia-game' );
			if ( $g !== $group ) {
				echo '<li class="dfmg-admin-tabs__group">' . esc_html( $g ) . '</li>';
				$group = $g;
			}
			printf( '<li class="%1$s"><a href="%2$s">%3$s</a></li>', $key === $current ? 'is-active' : '', esc_url( DataTable::base_url( $key ) ), esc_html( $def['label'] ) );
		}
		echo '</ul><div class="dfmg-admin-data__main">';
		if ( $edit ) {
			DataTable::render_form( $current, $tables[ $current ], 'new' === $edit ? 'new' : (int) $edit );
		} else {
			DataTable::render_list( $current, $tables[ $current ] );
		}
		echo '</div></div></div>';
	}

	public static function handle_data_save(): void {
		$key    = sanitize_key( wp_unslash( $_POST['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tables = self::tables();
		if ( ! self::can() || ! isset( $tables[ $key ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-maffia-game' ) );
		}
		check_admin_referer( 'dfmg_data_save_' . $key );
		DataTable::save( $key, $tables[ $key ] );
		self::redirect( DataTable::base_url( $key, array( 'dfmg_notice' => 'saved' ) ) );
	}

	public static function handle_data_delete(): void {
		$key    = sanitize_key( wp_unslash( $_GET['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tables = self::tables();
		if ( ! self::can() || ! isset( $tables[ $key ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'wp-maffia-game' ) );
		}
		check_admin_referer( 'dfmg_data_delete_' . $key . '_' . $id );
		DataTable::delete( $key, $tables[ $key ], $id );
		self::redirect( DataTable::base_url( $key, array( 'dfmg_notice' => 'deleted' ) ) );
	}

	/* ------------------------------------------------------------------ */
	/* Dashboard                                                            */
	/* ------------------------------------------------------------------ */

	public static function page_dashboard(): void {
		if ( ! self::can() ) {
			return;
		}
		$alive   = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1' );
		$dead    = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 0' );
		$online  = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE status = 1 AND last_active > %d', time() - 60 * Settings::int( 'online_minutes', 15 ) );
		$money   = (int) DB::value( 'SELECT COALESCE(SUM(money + bank), 0) FROM {characters} WHERE status = 1' );
		$actions = (int) DB::value( 'SELECT COUNT(*) FROM {activity} WHERE created_at > %d', time() - DAY_IN_SECONDS );
		?>
		<div class="wrap dfmg-admin">
			<h1><?php esc_html_e( 'Mafia Game', 'wp-maffia-game' ); ?></h1>
			<?php self::notices(); ?>
			<div class="dfmg-admin-cards">
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $alive ) ); ?></strong><span><?php esc_html_e( 'Living players', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $online ) ); ?></strong><span><?php esc_html_e( 'Online now', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $dead ) ); ?></strong><span><?php esc_html_e( 'Murdered', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::money( $money ) ); ?></strong><span><?php esc_html_e( 'Money in circulation', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $actions ) ); ?></strong><span><?php esc_html_e( 'Actions (24 hours)', 'wp-maffia-game' ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Game page', 'wp-maffia-game' ); ?></h2>
			<p>
				<?php esc_html_e( 'The game runs on the page with the shortcode', 'wp-maffia-game' ); ?> <code>[maffia_game]</code>:
				<a href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank"><?php echo esc_html( Game::page_url() ); ?></a>
			</p>

			<h2><?php esc_html_e( 'New round', 'wp-maffia-game' ); ?></h2>
			<p><?php esc_html_e( 'Erases all characters and player data (money, cars, families, messages, ...). Game data like crimes, cities and items is kept.', 'wp-maffia-game' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'All player data will be erased. Continue?', 'wp-maffia-game' ) ); ?>');">
				<input type="hidden" name="action" value="dfmg_new_round">
				<?php wp_nonce_field( 'dfmg_new_round' ); ?>
				<p><label><?php esc_html_e( 'New round name', 'wp-maffia-game' ); ?> <input type="text" name="round_name" value="<?php echo esc_attr( (string) Settings::get( 'round_name' ) ); ?>"></label></p>
				<p><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I understand this can\'t be undone', 'wp-maffia-game' ); ?></label></p>
				<?php submit_button( __( 'Start new round', 'wp-maffia-game' ), 'delete' ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_new_round(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'wp-maffia-game' ) );
		}
		check_admin_referer( 'dfmg_new_round' );
		if ( empty( $_POST['confirm'] ) ) {
			self::redirect( admin_url( 'admin.php?page=dfmg' ) );
		}
		$name = sanitize_text_field( wp_unslash( $_POST['round_name'] ?? '' ) );
		if ( $name ) {
			Settings::set( 'round_name', $name );
		}
		Plugin::instance()->new_round();
		self::redirect( admin_url( 'admin.php?page=dfmg&dfmg_notice=round' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Modules                                                              */
	/* ------------------------------------------------------------------ */

	public static function page_modules(): void {
		if ( ! self::can() ) {
			return;
		}
		$registry = Plugin::instance()->modules;
		$sources  = array(
			'bundled' => __( 'Bundled', 'wp-maffia-game' ),
			'custom'  => __( 'Custom module', 'wp-maffia-game' ),
			'plugin'  => __( 'Other plugin', 'wp-maffia-game' ),
		);
		?>
		<div class="wrap dfmg-admin">
			<h1><?php esc_html_e( 'Modules', 'wp-maffia-game' ); ?></h1>
			<?php self::notices(); ?>
			<p>
				<?php
				printf(
					/* translators: %s: directory */
					esc_html__( 'Place custom modules in %s (one folder per module containing a module.php). See docs/MODULES.md in the plugin.', 'wp-maffia-game' ),
					'<code>' . esc_html( str_replace( ABSPATH, '', DFMG_CUSTOM_MODULES_DIR ) ) . '</code>'
				);
				?>
			</p>
			<table class="widefat striped dfmg-modules">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Module', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Description', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Requires', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Source', 'wp-maffia-game' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $registry->available() as $id => $info ) : ?>
						<?php $on = $registry->is_enabled( $id ); ?>
						<tr class="<?php echo $on ? 'is-active' : ''; ?>">
							<td><strong><?php echo esc_html( $info['name'] ); ?></strong><br><small><?php echo esc_html( $id . ' · v' . $info['version'] . ' · ' . $info['author'] ); ?></small></td>
							<td><?php echo esc_html( $info['description'] ); ?></td>
							<td><?php echo esc_html( implode( ', ', $info['requires'] ) ); ?></td>
							<td><?php echo esc_html( $sources[ $info['source'] ] ?? $info['source'] ); ?></td>
							<td>
								<?php if ( $info['required'] ) : ?>
									<em><?php esc_html_e( 'Required', 'wp-maffia-game' ); ?></em>
								<?php else : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="dfmg_module">
										<input type="hidden" name="module" value="<?php echo esc_attr( $id ); ?>">
										<input type="hidden" name="state" value="<?php echo $on ? 'off' : 'on'; ?>">
										<?php wp_nonce_field( 'dfmg_module_' . $id ); ?>
										<button class="button <?php echo $on ? '' : 'button-primary'; ?>"><?php echo $on ? esc_html__( 'Disable', 'wp-maffia-game' ) : esc_html__( 'Enable', 'wp-maffia-game' ); ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_module(): void {
		$id = sanitize_key( wp_unslash( $_POST['module'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'wp-maffia-game' ) );
		}
		check_admin_referer( 'dfmg_module_' . $id );
		$registry = Plugin::instance()->modules;
		$result   = 'on' === ( $_POST['state'] ?? '' ) ? $registry->enable( $id ) : $registry->disable( $id );
		if ( is_wp_error( $result ) ) {
			set_transient( 'dfmg_admin_error_' . get_current_user_id(), $result->get_error_message(), 60 );
			self::redirect( admin_url( 'admin.php?page=dfmg-modules' ) );
		}
		self::redirect( admin_url( 'admin.php?page=dfmg-modules&dfmg_notice=saved' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Settings                                                             */
	/* ------------------------------------------------------------------ */

	private static function settings_sections(): array {
		$sections = array(
			'core' => array(
				'label'  => __( 'General', 'wp-maffia-game' ),
				'fields' => Settings::core_fields(),
			),
		);
		foreach ( Plugin::instance()->modules->active() as $module ) {
			$fields = $module->settings_fields();
			if ( $fields ) {
				$sections[ $module->id() ] = array(
					'label'  => $module->name(),
					'fields' => $fields,
				);
			}
		}
		return $sections;
	}

	public static function page_settings(): void {
		if ( ! self::can() ) {
			return;
		}
		?>
		<div class="wrap dfmg-admin">
			<h1><?php esc_html_e( 'Settings', 'wp-maffia-game' ); ?></h1>
			<?php self::notices(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php foreach ( self::settings_sections() as $section ) : ?>
					<h2><?php echo esc_html( $section['label'] ); ?></h2>
					<table class="form-table">
						<?php foreach ( $section['fields'] as $key => $field ) : ?>
							<?php $field = wp_parse_args( $field, array( 'type' => 'text', 'default' => '', 'description' => '', 'options' => array() ) ); ?>
							<tr>
								<th><label for="dfmg-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
								<td>
									<?php DataTable::field( $key, $field, Settings::get( $key, $field['default'] ), 'settings[' . $key . ']' ); ?>
									<?php if ( $field['description'] ) : ?>
										<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>
				<?php submit_button( __( 'Save settings', 'wp-maffia-game' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_settings(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'wp-maffia-game' ) );
		}
		check_admin_referer( 'dfmg_settings' );
		$input  = (array) wp_unslash( $_POST['settings'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$values = Settings::all();
		foreach ( self::settings_sections() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				$field          = wp_parse_args( $field, array( 'type' => 'text', 'default' => '', 'options' => array() ) );
				$values[ $key ] = DataTable::sanitize( $field, $input[ $key ] ?? '' );
			}
		}
		Settings::save( $values );
		self::redirect( admin_url( 'admin.php?page=dfmg-settings&dfmg_notice=saved' ) );
	}

	/* ------------------------------------------------------------------ */

	private static function notices(): void {
		$error = get_transient( 'dfmg_admin_error_' . get_current_user_id() );
		if ( $error ) {
			delete_transient( 'dfmg_admin_error_' . get_current_user_id() );
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
		$notice   = sanitize_key( wp_unslash( $_GET['dfmg_notice'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$messages = array(
			'saved'   => __( 'Saved.', 'wp-maffia-game' ),
			'deleted' => __( 'Deleted.', 'wp-maffia-game' ),
			'round'   => __( 'A new round has started.', 'wp-maffia-game' ),
		);
		if ( isset( $messages[ $notice ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $notice ] ) . '</p></div>';
		}
	}

	/**
	 * @return never
	 */
	private static function redirect( string $url ): void {
		wp_safe_redirect( $url );
		exit;
	}
}
