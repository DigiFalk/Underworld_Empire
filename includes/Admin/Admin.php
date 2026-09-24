<?php
/**
 * WordPress admin screens.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Admin;

use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Frontend\Game;
use DigiFalk\UnderworldEmpire\Items;
use DigiFalk\UnderworldEmpire\Locations;
use DigiFalk\UnderworldEmpire\Plugin;
use DigiFalk\UnderworldEmpire\Ranks;
use DigiFalk\UnderworldEmpire\Settings;

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
		add_filter( 'submenu_file', array( __CLASS__, 'highlight_menu' ) );
		// Hide the module configuration page from the menu once WordPress has checked access to it.
		add_action(
			'admin_head',
			static function () {
				remove_submenu_page( 'dfmg', 'dfmg-module' );
			}
		);
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
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=dfmg' ) ) . '">' . esc_html__( 'Manage', 'underworld-empire' ) . '</a>' );
		return $links;
	}

	public static function assets( string $hook ): void {
		if ( false !== strpos( $hook, 'dfmg' ) ) {
			wp_enqueue_style( 'dfmg-admin', DFMG_URL . 'assets/css/admin.css', array(), DFMG_VERSION );
		}
	}

	public static function menu(): void {
		$cap = self::cap();
		add_menu_page( __( 'Underworld Empire', 'underworld-empire' ), __( 'Underworld Empire', 'underworld-empire' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ), 'dashicons-shield-alt', 58 );
		add_submenu_page( 'dfmg', __( 'Dashboard', 'underworld-empire' ), __( 'Dashboard', 'underworld-empire' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'dfmg', __( 'Modules', 'underworld-empire' ), __( 'Modules', 'underworld-empire' ), $cap, 'dfmg-modules', array( __CLASS__, 'page_modules' ) );
		add_submenu_page( 'dfmg', __( 'Game data', 'underworld-empire' ), __( 'Game data', 'underworld-empire' ), $cap, 'dfmg-data', array( __CLASS__, 'page_data' ) );
		add_submenu_page( 'dfmg', __( 'Settings', 'underworld-empire' ), __( 'Settings', 'underworld-empire' ), $cap, 'dfmg-settings', array( __CLASS__, 'page_settings' ) );
		// Per module configuration page, reached through the Modules screen (not shown in the menu).
		add_submenu_page( 'dfmg', __( 'Configure module', 'underworld-empire' ), __( 'Configure module', 'underworld-empire' ), $cap, 'dfmg-module', array( __CLASS__, 'page_module' ) );
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
				'label'      => __( 'Players', 'underworld-empire' ),
				'table'      => 'characters',
				'order'      => 'id DESC',
				'can_create' => false,
				'search'     => 'name',
				'help'       => __( 'Player characters. Here you can, among other things, award premium points.', 'underworld-empire' ),
				'columns'    => array(
					'name'        => array( 'label' => __( 'Name', 'underworld-empire' ), 'type' => 'text', 'required' => true ),
					'status'      => array(
						'label'   => __( 'Status', 'underworld-empire' ),
						'type'    => 'select',
						'options' => array(
							1 => __( 'Alive', 'underworld-empire' ),
							0 => __( 'Dead', 'underworld-empire' ),
						),
					),
					'money'       => array( 'label' => __( 'Cash', 'underworld-empire' ), 'type' => 'int' ),
					'bank'        => array( 'label' => __( 'Bank', 'underworld-empire' ), 'type' => 'int' ),
					'bullets'     => array( 'label' => __( 'Bullets', 'underworld-empire' ), 'type' => 'int' ),
					'exp'         => array( 'label' => __( 'Experience', 'underworld-empire' ), 'type' => 'int' ),
					'points'      => array( 'label' => __( 'Points', 'underworld-empire' ), 'type' => 'int' ),
					'damage'      => array( 'label' => __( 'Damage', 'underworld-empire' ), 'type' => 'int', 'list' => false ),
					'rank_id'     => array( 'label' => __( 'Rank', 'underworld-empire' ), 'type' => 'select', 'options' => array( Ranks::class, 'options' ) ),
					'location_id' => array( 'label' => __( 'City', 'underworld-empire' ), 'type' => 'select', 'options' => array( Locations::class, 'options' ) ),
					'bio'         => array( 'label' => __( 'Profile text', 'underworld-empire' ), 'type' => 'textarea' ),
				),
			),
			'ranks'       => array(
				'label'   => __( 'Ranks', 'underworld-empire' ),
				'table'   => 'ranks',
				'order'   => 'exp_required ASC',
				'columns' => array(
					'name'          => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'exp_required'  => array( 'label' => __( 'Required experience', 'underworld-empire' ), 'type' => 'int' ),
					'max_players'   => array( 'label' => __( 'Max. players (0 = unlimited)', 'underworld-empire' ), 'type' => 'int' ),
					'cash_reward'   => array( 'label' => __( 'Cash reward', 'underworld-empire' ), 'type' => 'int' ),
					'bullet_reward' => array( 'label' => __( 'Bullet reward', 'underworld-empire' ), 'type' => 'int' ),
					'max_health'    => array( 'label' => __( 'Health', 'underworld-empire' ), 'type' => 'int', 'default' => 1000 ),
				),
			),
			'money_ranks' => array(
				'label'   => __( 'Wealth titles', 'underworld-empire' ),
				'table'   => 'money_ranks',
				'order'   => 'min_money ASC',
				'columns' => array(
					'name'      => array( 'label' => __( 'Title', 'underworld-empire' ), 'required' => true ),
					'min_money' => array( 'label' => __( 'From amount', 'underworld-empire' ), 'type' => 'int' ),
				),
			),
			'locations'   => array(
				'label'   => __( 'Cities', 'underworld-empire' ),
				'table'   => 'locations',
				'columns' => array(
					'name'         => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'travel_cost'  => array( 'label' => __( 'Travel cost', 'underworld-empire' ), 'type' => 'int' ),
					'travel_time'  => array( 'label' => __( 'Cooldown after travelling (sec)', 'underworld-empire' ), 'type' => 'int' ),
					'bullet_stock' => array( 'label' => __( 'Bullet stock', 'underworld-empire' ), 'type' => 'int' ),
					'bullet_price' => array( 'label' => __( 'Default bullet price', 'underworld-empire' ), 'type' => 'int' ),
				),
			),
			'items'       => array(
				'label'   => __( 'Items', 'underworld-empire' ),
				'table'   => 'items',
				'search'  => 'name',
				'columns' => array(
					'name'        => array( 'label' => __( 'Name', 'underworld-empire' ), 'required' => true ),
					'type'        => array( 'label' => __( 'Type', 'underworld-empire' ), 'type' => 'select', 'options' => array( Items::class, 'type_options' ) ),
					'price'       => array( 'label' => __( 'Price', 'underworld-empire' ), 'type' => 'int' ),
					'buyable'     => array( 'label' => __( 'For sale on the black market', 'underworld-empire' ), 'type' => 'checkbox', 'default' => 1 ),
					'description' => array( 'label' => __( 'Description', 'underworld-empire' ), 'type' => 'textarea' ),
					'effects'     => array(
						'label'       => __( 'Effects', 'underworld-empire' ),
						'type'        => 'textarea',
						'description' => self::effects_help(),
					),
				),
			),
		);
		foreach ( Plugin::instance()->modules->active() as $module ) {
			foreach ( $module->admin_tables() as $key => $def ) {
				$def['module']    = $module->name();
				$def['module_id'] = $module->id();
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
		return __( 'One effect per line.', 'underworld-empire' ) . '<br>' . implode( '<br>', $lines );
	}

	public static function page_data(): void {
		if ( ! self::can() ) {
			return;
		}
		$tables = array_filter(
			self::tables(),
			static function ( $def ) {
				return empty( $def['module_id'] );
			}
		);
		echo '<div class="wrap dfmg-admin"><h1>' . esc_html__( 'Game data', 'underworld-empire' ) . '</h1>';
		self::notices();
		echo '<p class="description">' . esc_html__( 'Core game data. The data of each module is managed on its own page: Modules → Configure.', 'underworld-empire' ) . '</p>';
		self::render_tables( $tables, array( 'page' => 'dfmg-data' ) );
		echo '</div></div></div>';
	}

	/**
	 * Vertical tabs with data tables; the selected table is listed or edited.
	 *
	 * @param array $tables    Table definitions.
	 * @param array $page_args Query args of the current admin page.
	 * @param array $extra     Extra tabs before the tables: key => [ label, url, active ].
	 */
	private static function render_tables( array $tables, array $page_args, array $extra = array() ): string {
		DataTable::$page_args = $page_args;
		$current              = sanitize_key( wp_unslash( $_GET['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit                 = sanitize_text_field( wp_unslash( $_GET['edit'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $tables[ $current ] ) && ! $extra ) {
			$current = (string) key( $tables );
		}
		echo '<div class="dfmg-admin-data"><ul class="dfmg-admin-tabs">';
		foreach ( $extra as $tab ) {
			printf( '<li class="%1$s"><a href="%2$s">%3$s</a></li>', $tab['active'] ? 'is-active' : '', esc_url( $tab['url'] ), esc_html( $tab['label'] ) );
		}
		if ( $extra && $tables ) {
			echo '<li class="dfmg-admin-tabs__group">' . esc_html__( 'Game data', 'underworld-empire' ) . '</li>';
		}
		foreach ( $tables as $key => $def ) {
			printf( '<li class="%1$s"><a href="%2$s">%3$s</a></li>', $key === $current ? 'is-active' : '', esc_url( DataTable::base_url( $key ) ), esc_html( $def['label'] ) );
		}
		echo '</ul><div class="dfmg-admin-data__main">';
		if ( isset( $tables[ $current ] ) ) {
			if ( $edit ) {
				DataTable::render_form( $current, $tables[ $current ], 'new' === $edit ? 'new' : (int) $edit );
			} else {
				DataTable::render_list( $current, $tables[ $current ] );
			}
		}
		return $current;
	}

	/**
	 * Point data table links and redirects to the page that owns the table.
	 */
	private static function table_context( array $def ): void {
		DataTable::$page_args = empty( $def['module_id'] )
			? array( 'page' => 'dfmg-data' )
			: array(
				'page'   => 'dfmg-module',
				'module' => $def['module_id'],
			);
	}

	public static function handle_data_save(): void {
		$key    = sanitize_key( wp_unslash( $_POST['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$tables = self::tables();
		if ( ! self::can() || ! isset( $tables[ $key ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_data_save_' . $key );
		DataTable::save( $key, $tables[ $key ] );
		self::table_context( $tables[ $key ] );
		self::redirect( DataTable::base_url( $key, array( 'dfmg_notice' => 'saved' ) ) );
	}

	public static function handle_data_delete(): void {
		$key    = sanitize_key( wp_unslash( $_GET['table'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tables = self::tables();
		if ( ! self::can() || ! isset( $tables[ $key ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_data_delete_' . $key . '_' . $id );
		DataTable::delete( $key, $tables[ $key ], $id );
		self::table_context( $tables[ $key ] );
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
			<h1><?php esc_html_e( 'Underworld Empire', 'underworld-empire' ); ?></h1>
			<?php self::notices(); ?>
			<div class="dfmg-admin-cards">
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $alive ) ); ?></strong><span><?php esc_html_e( 'Living players', 'underworld-empire' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $online ) ); ?></strong><span><?php esc_html_e( 'Online now', 'underworld-empire' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $dead ) ); ?></strong><span><?php esc_html_e( 'Murdered', 'underworld-empire' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::money( $money ) ); ?></strong><span><?php esc_html_e( 'Money in circulation', 'underworld-empire' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $actions ) ); ?></strong><span><?php esc_html_e( 'Actions (24 hours)', 'underworld-empire' ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Game page', 'underworld-empire' ); ?></h2>
			<p>
				<?php esc_html_e( 'The game runs on the page with the shortcode', 'underworld-empire' ); ?> <code>[underworld_empire]</code>:
				<a href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank"><?php echo esc_html( Game::page_url() ); ?></a>
			</p>

			<h2><?php esc_html_e( 'New round', 'underworld-empire' ); ?></h2>
			<p><?php esc_html_e( 'Erases all characters and player data (money, cars, families, messages, ...). Game data like crimes, cities and items is kept.', 'underworld-empire' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'All player data will be erased. Continue?', 'underworld-empire' ) ); ?>');">
				<input type="hidden" name="action" value="dfmg_new_round">
				<?php wp_nonce_field( 'dfmg_new_round' ); ?>
				<p><label><?php esc_html_e( 'New round name', 'underworld-empire' ); ?> <input type="text" name="round_name" value="<?php echo esc_attr( (string) Settings::get( 'round_name' ) ); ?>"></label></p>
				<p><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I understand this can\'t be undone', 'underworld-empire' ); ?></label></p>
				<?php submit_button( __( 'Start new round', 'underworld-empire' ), 'delete' ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_new_round(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
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
			'bundled' => __( 'Bundled', 'underworld-empire' ),
			'custom'  => __( 'Custom module', 'underworld-empire' ),
			'plugin'  => __( 'Other plugin', 'underworld-empire' ),
		);
		?>
		<div class="wrap dfmg-admin">
			<h1><?php esc_html_e( 'Modules', 'underworld-empire' ); ?></h1>
			<?php self::notices(); ?>
			<p>
				<?php
				printf(
					/* translators: %s: directory */
					esc_html__( 'Place custom modules in %s (one folder per module containing a module.php). See docs/MODULES.md in the plugin.', 'underworld-empire' ),
					'<code>' . esc_html( str_replace( ABSPATH, '', DFMG_CUSTOM_MODULES_DIR ) ) . '</code>'
				);
				?>
			</p>
			<table class="widefat striped dfmg-modules">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Module', 'underworld-empire' ); ?></th>
						<th><?php esc_html_e( 'Description', 'underworld-empire' ); ?></th>
						<th><?php esc_html_e( 'Requires', 'underworld-empire' ); ?></th>
						<th><?php esc_html_e( 'Source', 'underworld-empire' ); ?></th>
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
								<?php $module = $registry->get( $id ); ?>
								<?php if ( $module && ( $module->settings_fields() || $module->admin_tables() ) ) : ?>
									<a class="button button-secondary dfmg-configure" href="<?php echo esc_url( self::module_url( $id ) ); ?>"><?php esc_html_e( 'Configure', 'underworld-empire' ); ?></a>
								<?php endif; ?>
								<?php if ( $info['required'] ) : ?>
									<em><?php esc_html_e( 'Required', 'underworld-empire' ); ?></em>
								<?php else : ?>
									<form method="post" class="dfmg-inline" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="dfmg_module">
										<input type="hidden" name="module" value="<?php echo esc_attr( $id ); ?>">
										<input type="hidden" name="state" value="<?php echo $on ? 'off' : 'on'; ?>">
										<?php wp_nonce_field( 'dfmg_module_' . $id ); ?>
										<button class="button <?php echo $on ? '' : 'button-primary'; ?>"><?php echo $on ? esc_html__( 'Disable', 'underworld-empire' ) : esc_html__( 'Enable', 'underworld-empire' ); ?></button>
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
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
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
				'label'  => __( 'General', 'underworld-empire' ),
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
			<h1><?php esc_html_e( 'Settings', 'underworld-empire' ); ?></h1>
			<?php self::notices(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="core">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<p class="description"><?php esc_html_e( 'General game settings. The settings of each module are on its own page: Modules → Configure.', 'underworld-empire' ); ?></p>
				<?php self::render_fields( Settings::core_fields() ); ?>
				<?php submit_button( __( 'Save settings', 'underworld-empire' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function render_fields( array $fields ): void {
		echo '<table class="form-table">';
		foreach ( $fields as $key => $field ) {
			$field = wp_parse_args( $field, array( 'type' => 'text', 'default' => '', 'description' => '', 'options' => array() ) );
			echo '<tr><th><label for="dfmg-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			DataTable::field( $key, $field, Settings::get( $key, $field['default'] ), 'settings[' . $key . ']' );
			if ( $field['description'] ) {
				echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/* ------------------------------------------------------------------ */
	/* Module configuration                                                 */
	/* ------------------------------------------------------------------ */

	public static function module_url( string $id, array $args = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page'   => 'dfmg-module',
					'module' => $id,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Keep "Modules" highlighted in the menu while configuring a module.
	 *
	 * @param string|null $submenu_file
	 * @return string|null
	 */
	public static function highlight_menu( $submenu_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ( 'dfmg-module' === ( $_GET['page'] ?? '' ) ) ? 'dfmg-modules' : $submenu_file;
	}

	/**
	 * All settings and game data of one module on a single page.
	 */
	public static function page_module(): void {
		if ( ! self::can() ) {
			return;
		}
		$id     = sanitize_key( wp_unslash( $_GET['module'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$module = Plugin::instance()->modules->get( $id );
		echo '<div class="wrap dfmg-admin">';
		if ( ! $module ) {
			echo '<h1>' . esc_html__( 'Configure module', 'underworld-empire' ) . '</h1><p>' . esc_html__( 'This module is not enabled.', 'underworld-empire' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=dfmg-modules' ) ) . '">&larr; ' . esc_html__( 'Back to modules', 'underworld-empire' ) . '</a></p></div>';
			return;
		}
		$fields = $module->settings_fields();
		$tables = array();
		foreach ( self::tables() as $key => $def ) {
			if ( ( $def['module_id'] ?? '' ) === $id ) {
				$tables[ $key ] = $def;
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$on_settings = $fields && ( ! isset( $_GET['table'] ) || ! isset( $tables[ sanitize_key( wp_unslash( $_GET['table'] ) ) ] ) );

		/* translators: %s: module name */
		echo '<h1>' . esc_html( sprintf( __( 'Configure: %s', 'underworld-empire' ), $module->name() ) ) . '</h1>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=dfmg-modules' ) ) . '">&larr; ' . esc_html__( 'Back to modules', 'underworld-empire' ) . '</a></p>';
		echo '<p class="description">' . esc_html( (string) $module->info( 'description' ) ) . '</p>';
		self::notices();

		$extra = array();
		if ( $fields ) {
			$extra['settings'] = array(
				'label'  => __( 'Settings', 'underworld-empire' ),
				'url'    => self::module_url( $id ),
				'active' => $on_settings,
			);
		}
		if ( ! $on_settings && $tables && ! isset( $_GET['table'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$_GET['table'] = (string) key( $tables );
		}
		self::render_tables(
			$tables,
			array(
				'page'   => 'dfmg-module',
				'module' => $id,
			),
			$extra
		);
		if ( $on_settings ) {
			?>
			<h2><?php esc_html_e( 'Settings', 'underworld-empire' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php self::render_fields( $fields ); ?>
				<?php submit_button( __( 'Save settings', 'underworld-empire' ) ); ?>
			</form>
			<?php
		}
		echo '</div></div></div>';
	}

	public static function handle_settings(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		check_admin_referer( 'dfmg_settings' );
		$input    = (array) wp_unslash( $_POST['settings'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$section  = sanitize_key( wp_unslash( $_POST['section'] ?? 'core' ) );
		$sections = self::settings_sections();
		if ( ! isset( $sections[ $section ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'underworld-empire' ) );
		}
		$values = Settings::all();
		foreach ( $sections[ $section ]['fields'] as $key => $field ) {
			$field          = wp_parse_args( $field, array( 'type' => 'text', 'default' => '', 'options' => array() ) );
			$values[ $key ] = DataTable::sanitize( $field, $input[ $key ] ?? '' );
		}
		Settings::save( $values );
		self::redirect(
			'core' === $section
				? admin_url( 'admin.php?page=dfmg-settings&dfmg_notice=saved' )
				: self::module_url( $section, array( 'dfmg_notice' => 'saved' ) )
		);
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
			'saved'   => __( 'Saved.', 'underworld-empire' ),
			'deleted' => __( 'Deleted.', 'underworld-empire' ),
			'round'   => __( 'A new round has started.', 'underworld-empire' ),
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
