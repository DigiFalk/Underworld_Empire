<?php
/**
 * WordPress admin screens.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire\Admin;

use DigiFalk\UnderworldEmpire\DB;
use DigiFalk\UnderworldEmpire\Format;
use DigiFalk\UnderworldEmpire\Icons;
use DigiFalk\UnderworldEmpire\Frontend\Game;
use DigiFalk\UnderworldEmpire\Items;
use DigiFalk\UnderworldEmpire\Locations;
use DigiFalk\UnderworldEmpire\Plugin;
use DigiFalk\UnderworldEmpire\Premium\Licenses;
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
			wp_enqueue_script( 'dfmg-admin', DFMG_URL . 'assets/js/admin.js', array(), DFMG_VERSION, true );
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
		echo '<div class="wrap dfmg-admin">';
		self::header( __( 'Game data', 'underworld-empire' ), __( 'Core game data. The data of each module is managed on its own page: Modules → Configure.', 'underworld-empire' ), 'dfmg-data' );
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
	/* Branded page header                                                  */
	/* ------------------------------------------------------------------ */

	/**
	 * Header shown on every Underworld Empire admin screen: brand, page title,
	 * quick actions and the section navigation.
	 */
	private static function header( string $title, string $subtitle = '', string $current = '' ): void {
		$nav = array(
			'dfmg'          => array( __( 'Dashboard', 'underworld-empire' ), 'overview' ),
			'dfmg-modules'  => array( __( 'Modules', 'underworld-empire' ), 'module' ),
			'dfmg-data'     => array( __( 'Game data', 'underworld-empire' ), 'data' ),
			'dfmg-settings' => array( __( 'Settings', 'underworld-empire' ), 'settings' ),
		);
		$layout = add_query_arg(
			array(
				'autofocus[section]' => 'dfmg_game_layout',
				'url'                => rawurlencode( Game::page_url() ),
			),
			admin_url( 'customize.php' )
		);
		?>
		<header class="dfmg-admin-hero">
			<div class="dfmg-admin-hero__top">
				<div class="dfmg-admin-brand">
					<span class="dfmg-admin-brand__mark"><?php echo Icons::svg( 'shield', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="dfmg-admin-brand__text">
						<strong>Underworld Empire</strong>
						<span><?php echo esc_html( 'v' . DFMG_VERSION . ' · ' . (string) Settings::get( 'round_name' ) ); ?></span>
					</span>
				</div>
				<div class="dfmg-admin-hero__actions">
					<a class="dfmg-admin-btn dfmg-admin-btn--glass" href="<?php echo esc_url( $layout ); ?>"><?php echo Icons::svg( 'layout', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Game layout', 'underworld-empire' ); ?></a>
					<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open the game', 'underworld-empire' ); ?> <?php echo Icons::svg( 'external', 15 ); // phpcs:ignore ?></a>
				</div>
			</div>
			<div class="dfmg-admin-hero__title">
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( $subtitle ) : ?>
					<p><?php echo wp_kses_post( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="dfmg-admin-nav" aria-label="<?php esc_attr_e( 'Underworld Empire', 'underworld-empire' ); ?>">
				<?php foreach ( $nav as $page => $item ) : ?>
					<a class="<?php echo $page === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page ) ); ?>"<?php echo $page === $current ? ' aria-current="page"' : ''; ?>><?php echo Icons::svg( $item[1], 16 ); // phpcs:ignore ?> <?php echo esc_html( $item[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
		</header>
		<hr class="wp-header-end">
		<?php
		self::notices();
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
		$top     = DB::results( 'SELECT name, user_id, exp, rank_id, money + bank AS wealth FROM {characters} WHERE status = 1 ORDER BY exp DESC, id ASC LIMIT 5' );
		$feed    = DB::results( 'SELECT a.action, a.success, a.created_at, c.name FROM {activity} a LEFT JOIN {characters} c ON c.id = a.character_id ORDER BY a.id DESC LIMIT 8' );
		$stats   = array(
			array( 'players', __( 'Living players', 'underworld-empire' ), Format::number( $alive ) ),
			array( 'activity', __( 'Online now', 'underworld-empire' ), Format::number( $online ) ),
			array( 'murder', __( 'Murdered', 'underworld-empire' ), Format::number( $dead ) ),
			array( 'cash', __( 'Money in circulation', 'underworld-empire' ), Format::money( $money ) ),
			array( 'crimes', __( 'Actions (24 hours)', 'underworld-empire' ), Format::number( $actions ) ),
		);
		$customize = admin_url( 'customize.php?url=' . rawurlencode( Game::page_url() ) );
		$links     = array(
			array( 'layout', __( 'Game layout', 'underworld-empire' ), __( 'Drag game elements into place', 'underworld-empire' ), add_query_arg( 'autofocus[section]', 'dfmg_game_layout', $customize ) ),
			array( 'palette', __( 'Theme & colours', 'underworld-empire' ), __( 'Header, footer, light & dark mode', 'underworld-empire' ), $customize ),
			array( 'module', __( 'Modules', 'underworld-empire' ), __( 'Switch game features on or off', 'underworld-empire' ), admin_url( 'admin.php?page=dfmg-modules' ) ),
			array( 'data', __( 'Game data', 'underworld-empire' ), __( 'Players, ranks, cities and items', 'underworld-empire' ), admin_url( 'admin.php?page=dfmg-data' ) ),
			array( 'settings', __( 'Settings', 'underworld-empire' ), __( 'Round, money and appearance', 'underworld-empire' ), admin_url( 'admin.php?page=dfmg-settings' ) ),
			array( 'book', __( 'Documentation', 'underworld-empire' ), __( 'Build your own modules', 'underworld-empire' ), 'https://github.com/DigiFalk/Underworld_Empire/blob/main/docs/MODULES.md' ),
		);
		?>
		<div class="wrap dfmg-admin">
			<?php self::header( __( 'Dashboard', 'underworld-empire' ), __( 'How your underworld is doing right now.', 'underworld-empire' ), 'dfmg' ); ?>

			<div class="dfmg-admin-stats">
				<?php foreach ( $stats as $stat ) : ?>
					<div class="dfmg-admin-stat">
						<span class="dfmg-admin-stat__icon"><?php echo Icons::svg( $stat[0], 20 ); // phpcs:ignore ?></span>
						<span class="dfmg-admin-stat__label"><?php echo esc_html( $stat[1] ); ?></span>
						<strong class="dfmg-admin-stat__value"><?php echo esc_html( $stat[2] ); ?></strong>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dfmg-admin-grid dfmg-admin-grid--wide">
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'statistics', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Player actions, last 7 days', 'underworld-empire' ); ?></h2>
					<?php self::activity_chart(); ?>
				</section>
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'leaderboards', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Top players', 'underworld-empire' ); ?></h2>
					<?php if ( ! $top ) : ?>
						<p class="dfmg-admin-empty"><?php esc_html_e( 'No players yet. Share the game page to get started.', 'underworld-empire' ); ?></p>
					<?php else : ?>
						<ol class="dfmg-admin-top">
							<?php foreach ( $top as $row ) : ?>
								<li>
									<?php $avatar = \DigiFalk\UnderworldEmpire\Avatar::url( (int) $row['user_id'] ); ?>
									<?php if ( $avatar ) : ?>
										<img class="dfmg-admin-top__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="" width="34" height="34">
									<?php else : ?>
										<span class="dfmg-admin-top__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( (string) $row['name'], 0, 1 ) ) ); ?></span>
									<?php endif; ?>
									<span class="dfmg-admin-top__who"><strong><?php echo esc_html( (string) $row['name'] ); ?></strong><small><?php echo esc_html( (string) ( Ranks::get( (int) $row['rank_id'] )['name'] ?? '' ) ); ?></small></span>
									<span class="dfmg-admin-top__num"><?php echo esc_html( Format::money( (int) $row['wealth'] ) ); ?><small><?php /* translators: %s: experience */ echo esc_html( sprintf( __( '%s XP', 'underworld-empire' ), Format::number( (int) $row['exp'] ) ) ); ?></small></span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</section>
			</div>

			<div class="dfmg-admin-grid">
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'activity', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Live feed', 'underworld-empire' ); ?></h2>
					<?php if ( ! $feed ) : ?>
						<p class="dfmg-admin-empty"><?php esc_html_e( 'Nothing has happened yet.', 'underworld-empire' ); ?></p>
					<?php else : ?>
						<ul class="dfmg-admin-feed">
							<?php foreach ( $feed as $row ) : ?>
								<li class="<?php echo $row['success'] ? 'is-success' : 'is-fail'; ?>">
									<span class="dfmg-admin-feed__dot" aria-hidden="true"></span>
									<span class="dfmg-admin-feed__text"><strong><?php echo esc_html( (string) ( $row['name'] ?: __( 'Unknown player', 'underworld-empire' ) ) ); ?></strong> <?php echo esc_html( self::activity_label( (string) $row['action'] ) ); ?> <em><?php echo $row['success'] ? esc_html__( 'succeeded', 'underworld-empire' ) : esc_html__( 'failed', 'underworld-empire' ); ?></em></span>
									<time><?php echo esc_html( Format::ago( (int) $row['created_at'] ) ); ?></time>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'module', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Quick links', 'underworld-empire' ); ?></h2>
					<div class="dfmg-admin-links">
						<?php foreach ( $links as $link ) : ?>
							<a class="dfmg-admin-link" href="<?php echo esc_url( $link[3] ); ?>"<?php echo 0 === strpos( $link[3], 'http' ) && false === strpos( $link[3], admin_url() ) ? ' target="_blank" rel="noopener"' : ''; ?>>
								<span class="dfmg-admin-link__icon"><?php echo Icons::svg( $link[0], 18 ); // phpcs:ignore ?></span>
								<span><strong><?php echo esc_html( $link[1] ); ?></strong><small><?php echo esc_html( $link[2] ); ?></small></span>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="dfmg-admin-shortcode">
						<?php esc_html_e( 'Game page', 'underworld-empire' ); ?>:
						<a href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( Game::page_url() ); ?></a>
						<code>[underworld_empire]</code>
					</p>
				</section>
			</div>

			<details class="dfmg-admin-panel dfmg-admin-danger">
				<summary><?php echo Icons::svg( 'alert', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Start a new round', 'underworld-empire' ); ?> <small><?php esc_html_e( 'Erases all characters and player data', 'underworld-empire' ); ?></small></summary>
				<p><?php esc_html_e( 'Erases all characters and player data (money, cars, families, messages, ...). Game data like crimes, cities and items is kept.', 'underworld-empire' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'All player data will be erased. Continue?', 'underworld-empire' ) ); ?>');">
					<input type="hidden" name="action" value="dfmg_new_round">
					<?php wp_nonce_field( 'dfmg_new_round' ); ?>
					<p><label><?php esc_html_e( 'New round name', 'underworld-empire' ); ?> <input type="text" name="round_name" value="<?php echo esc_attr( (string) Settings::get( 'round_name' ) ); ?>"></label></p>
					<p><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I understand this can\'t be undone', 'underworld-empire' ); ?></label></p>
					<?php submit_button( __( 'Start new round', 'underworld-empire' ), 'delete' ); ?>
				</form>
			</details>
		</div>
		<?php
	}

	/**
	 * Readable name of an activity log action ("car_theft" → "Car theft").
	 */
	private static function activity_label( string $action ): string {
		$labels = apply_filters( 'dfmg_activity_labels', array() );
		return (string) ( $labels[ $action ] ?? ucfirst( str_replace( array( '_', '-' ), ' ', $action ) ) );
	}

	/**
	 * Column chart of player actions per day (last 7 days, site time zone).
	 * One series: the panel title names it, so there is no legend. Each column
	 * has a hover tooltip and the numbers are also in a screen reader table.
	 */
	private static function activity_chart(): void {
		$tz    = wp_timezone();
		$start = ( new \DateTimeImmutable( 'today', $tz ) )->modify( '-6 days' )->getTimestamp();
		$rows  = DB::results( 'SELECT FLOOR((created_at - %d) / 86400) AS d, COUNT(*) AS n FROM {activity} WHERE created_at >= %d GROUP BY d', $start, $start );
		$days  = array_fill( 0, 7, 0 );
		foreach ( $rows as $row ) {
			$d = (int) $row['d'];
			if ( $d >= 0 && $d < 7 ) {
				$days[ $d ] = (int) $row['n'];
			}
		}
		$max  = max( $days );
		// Four gridlines at clean numbers (1, 2, 5, 10, 20, …).
		$step = max( 1, (int) ceil( $max / 4 ) );
		$mag  = 10 ** max( 0, (int) floor( log10( $step ) ) );
		$step = (int) ( ceil( $step / $mag ) * $mag );
		$top  = $step * 4;

		$w      = 760;
		$h      = 230;
		$left   = 40;
		$bottom = 28;
		$plot_h = $h - $bottom - 12;
		$slot   = ( $w - $left - 8 ) / 7;
		$bar_w  = min( 24, $slot * .5 );
		$labels = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$labels[] = wp_date( 'D j', $start + $i * DAY_IN_SECONDS + 3600, $tz );
		}
		echo '<figure class="dfmg-admin-chart">';
		echo '<svg viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . esc_attr__( 'Player actions per day', 'underworld-empire' ) . '">';
		for ( $g = 0; $g <= 4; $g++ ) {
			$y = 12 + $plot_h - ( $plot_h * $g / 4 );
			echo '<line class="dfmg-admin-chart__grid" x1="' . $left . '" x2="' . ( $w - 4 ) . '" y1="' . $y . '" y2="' . $y . '"/>';
			echo '<text class="dfmg-admin-chart__tick" x="' . ( $left - 8 ) . '" y="' . ( $y + 4 ) . '" text-anchor="end">' . esc_html( Format::number( $step * $g ) ) . '</text>';
		}
		foreach ( $days as $i => $n ) {
			$x   = $left + $slot * $i + ( $slot - $bar_w ) / 2;
			$bh  = $top ? $plot_h * $n / $top : 0;
			$y   = 12 + $plot_h - $bh;
			$r   = min( 4, $bh );
			$cx  = $x + $bar_w / 2;
			echo '<g class="dfmg-admin-chart__col' . ( $n === $max && $max > 0 ? ' is-max' : '' ) . '" tabindex="0">';
			echo '<title>' . esc_html( $labels[ $i ] . ': ' . Format::number( $n ) ) . '</title>';
			echo '<rect class="dfmg-admin-chart__hit" x="' . ( $left + $slot * $i ) . '" y="12" width="' . $slot . '" height="' . $plot_h . '"/>';
			if ( $bh > 0 ) {
				// Rounded data end, square at the baseline.
				$path = sprintf(
					'M%1$.1f %2$.1fV%3$.1fQ%1$.1f %4$.1f %5$.1f %4$.1fH%6$.1fQ%7$.1f %4$.1f %7$.1f %3$.1fV%2$.1fZ',
					$x, 12 + $plot_h, $y + $r, $y, $x + $r, $x + $bar_w - $r, $x + $bar_w
				);
				echo '<path class="dfmg-admin-chart__bar" d="' . esc_attr( $path ) . '"/>';
			}
			if ( $n === $max && $max > 0 ) {
				echo '<text class="dfmg-admin-chart__value" x="' . $cx . '" y="' . ( $y - 7 ) . '" text-anchor="middle">' . esc_html( Format::number( $n ) ) . '</text>';
			}
			echo '<text class="dfmg-admin-chart__day" x="' . $cx . '" y="' . ( $h - 8 ) . '" text-anchor="middle">' . esc_html( $labels[ $i ] ) . '</text>';
			echo '</g>';
		}
		echo '<line class="dfmg-admin-chart__base" x1="' . $left . '" x2="' . ( $w - 4 ) . '" y1="' . ( 12 + $plot_h ) . '" y2="' . ( 12 + $plot_h ) . '"/>';
		echo '</svg>';
		echo '<table class="screen-reader-text"><caption>' . esc_html__( 'Player actions per day', 'underworld-empire' ) . '</caption><tbody>';
		foreach ( $days as $i => $n ) {
			echo '<tr><th scope="row">' . esc_html( $labels[ $i ] ) . '</th><td>' . esc_html( Format::number( $n ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		/* translators: %s: number of actions */
		echo '<figcaption>' . esc_html( sprintf( __( '%s actions this week', 'underworld-empire' ), Format::number( array_sum( $days ) ) ) ) . '</figcaption>';
		echo '</figure>';
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
		$registry  = Plugin::instance()->modules;
		$available = $registry->available();
		$active    = 0;
		foreach ( $available as $id => $info ) {
			$active += $registry->is_enabled( $id ) ? 1 : 0;
		}
		$sources = array(
			'bundled' => __( 'Bundled', 'underworld-empire' ),
			'custom'  => __( 'Custom module', 'underworld-empire' ),
			'plugin'  => __( 'Other plugin', 'underworld-empire' ),
		);
		$subtitle = sprintf(
			/* translators: %s: directory */
			esc_html__( 'Switch game features on or off. Place custom modules in %s (one folder per module containing a module.php).', 'underworld-empire' ),
			'<code>' . esc_html( str_replace( ABSPATH, '', DFMG_CUSTOM_MODULES_DIR ) ) . '</code>'
		);
		?>
		<div class="wrap dfmg-admin">
			<?php self::header( __( 'Modules', 'underworld-empire' ), $subtitle, 'dfmg-modules' ); ?>

			<?php self::render_premium(); ?>

			<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'module', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Installed modules', 'underworld-empire' ); ?></h2>
			<div class="dfmg-admin-toolbar" data-dfmg-modules-toolbar>
				<div class="dfmg-admin-segment" role="group" aria-label="<?php esc_attr_e( 'Filter modules', 'underworld-empire' ); ?>">
					<button type="button" class="is-active" data-filter="all"><?php esc_html_e( 'All', 'underworld-empire' ); ?> <span><?php echo (int) count( $available ); ?></span></button>
					<button type="button" data-filter="on"><?php esc_html_e( 'Active', 'underworld-empire' ); ?> <span><?php echo (int) $active; ?></span></button>
					<button type="button" data-filter="off"><?php esc_html_e( 'Inactive', 'underworld-empire' ); ?> <span><?php echo (int) ( count( $available ) - $active ); ?></span></button>
				</div>
				<label class="dfmg-admin-search-field">
					<?php echo Icons::svg( 'detectives', 16 ); // phpcs:ignore ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Search modules', 'underworld-empire' ); ?></span>
					<input type="search" placeholder="<?php esc_attr_e( 'Search modules…', 'underworld-empire' ); ?>" data-dfmg-module-search>
				</label>
			</div>

			<div class="dfmg-admin-modules">
				<?php foreach ( $available as $id => $info ) : ?>
					<?php
					$on     = $registry->is_enabled( $id );
					$module = $registry->get( $id );
					$search = strtolower( $info['name'] . ' ' . $id . ' ' . $info['description'] );
					?>
					<article class="dfmg-admin-module <?php echo $on ? 'is-on' : 'is-off'; ?>" data-state="<?php echo $on ? 'on' : 'off'; ?>" data-search="<?php echo esc_attr( $search ); ?>">
						<header class="dfmg-admin-module__head">
							<span class="dfmg-admin-module__icon"><?php echo Icons::svg( Icons::has( $id ) ? $id : 'module', 22 ); // phpcs:ignore ?></span>
							<div class="dfmg-admin-module__name">
								<h2><?php echo esc_html( $info['name'] ); ?></h2>
								<span><?php echo esc_html( 'v' . $info['version'] . ' · ' . ( ! empty( $info['premium'] ) ? __( 'Premium', 'underworld-empire' ) : ( $sources[ $info['source'] ] ?? $info['source'] ) ) . ( $info['author'] ? ' · ' . $info['author'] : '' ) ); ?></span>
							</div>
							<?php if ( $info['required'] ) : ?>
								<span class="dfmg-admin-badge"><?php esc_html_e( 'Required', 'underworld-empire' ); ?></span>
							<?php elseif ( ! empty( $info['premium'] ) && ! Licenses::is_licensed( $id ) ) : ?>
								<a class="dfmg-admin-badge dfmg-admin-badge--premium" href="#premium-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'License needed', 'underworld-empire' ); ?></a>
							<?php else : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="dfmg_module">
									<input type="hidden" name="module" value="<?php echo esc_attr( $id ); ?>">
									<input type="hidden" name="state" value="<?php echo $on ? 'off' : 'on'; ?>">
									<?php wp_nonce_field( 'dfmg_module_' . $id ); ?>
									<button type="submit" class="dfmg-admin-switch" role="switch" aria-checked="<?php echo $on ? 'true' : 'false'; ?>" title="<?php echo $on ? esc_attr__( 'Disable', 'underworld-empire' ) : esc_attr__( 'Enable', 'underworld-empire' ); ?>">
										<span class="screen-reader-text"><?php echo esc_html( ( $on ? __( 'Disable', 'underworld-empire' ) : __( 'Enable', 'underworld-empire' ) ) . ' ' . $info['name'] ); ?></span>
									</button>
								</form>
							<?php endif; ?>
						</header>
						<p class="dfmg-admin-module__desc"><?php echo esc_html( $info['description'] ); ?></p>
						<footer class="dfmg-admin-module__foot">
							<?php if ( $info['requires'] ) : ?>
								<span class="dfmg-admin-module__requires"><?php esc_html_e( 'Requires', 'underworld-empire' ); ?>
									<?php foreach ( $info['requires'] as $dep ) : ?>
										<span class="dfmg-admin-chip"><?php echo esc_html( $dep ); ?></span>
									<?php endforeach; ?>
								</span>
							<?php endif; ?>
							<?php if ( $module && ( $module->settings_fields() || $module->admin_tables() ) ) : ?>
								<a class="dfmg-admin-btn dfmg-admin-btn--ghost dfmg-configure" href="<?php echo esc_url( self::module_url( $id ) ); ?>"><?php echo Icons::svg( 'settings', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Configure', 'underworld-empire' ); ?></a>
							<?php endif; ?>
						</footer>
					</article>
				<?php endforeach; ?>
			</div>
			<p class="dfmg-admin-empty" hidden data-dfmg-no-modules><?php esc_html_e( 'No modules match your search.', 'underworld-empire' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Premium modules from the DigiFalk store: locked placeholders with a buy button and a
	 * license key field, or the license of an installed premium module.
	 */
	private static function render_premium(): void {
		$catalog  = Licenses::catalog();
		$licenses = Licenses::all();
		$registry = Plugin::instance()->modules;
		$products = array_unique( array_merge( array_keys( $catalog ), array_keys( $licenses ) ) );
		?>
		<section class="dfmg-admin-premium">
			<div class="dfmg-admin-premium__head">
				<div>
					<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'membership', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Premium modules', 'underworld-empire' ); ?></h2>
					<p><?php esc_html_e( 'Buy a module in the DigiFalk store, paste the license key from your email here and it is downloaded, installed and switched on. One payment, lifetime updates.', 'underworld-empire' ); ?></p>
				</div>
				<div class="dfmg-admin-premium__actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="dfmg_license_refresh">
						<?php wp_nonce_field( 'dfmg_license_refresh' ); ?>
						<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--ghost"><?php echo Icons::svg( 'activity', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Check for updates', 'underworld-empire' ); ?></button>
					</form>
					<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( Licenses::store_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit the store', 'underworld-empire' ); ?> <?php echo Icons::svg( 'external', 15 ); // phpcs:ignore ?></a>
				</div>
			</div>
			<?php if ( ! $products ) : ?>
				<p class="dfmg-admin-empty"><?php esc_html_e( 'No premium modules available yet. New modules appear here automatically as soon as they are in the store.', 'underworld-empire' ); ?></p>
			<?php else : ?>
				<div class="dfmg-admin-modules">
					<?php foreach ( $products as $slug ) : ?>
						<?php
						$product   = $catalog[ $slug ] ?? array( 'product' => $slug, 'name' => (string) ( $registry->info( $slug )['name'] ?? $slug ), 'description' => '', 'price' => '', 'buy_url' => Licenses::store_url(), 'icon' => '', 'requires' => '' );
						$license   = $licenses[ $slug ] ?? null;
						$info      = $registry->info( $slug );
						$licensed  = Licenses::is_licensed( $slug );
						$too_old   = $product['requires'] && version_compare( DFMG_VERSION, $product['requires'], '<' );
						$icon      = $product['icon'] && Icons::has( $product['icon'] ) ? $product['icon'] : ( Icons::has( $slug ) ? $slug : 'membership' );
						$state     = $licensed ? 'is-licensed' : ( $license ? 'is-revoked' : 'is-locked' );
						?>
						<article class="dfmg-admin-module dfmg-admin-module--premium <?php echo esc_attr( $state ); ?>" id="premium-<?php echo esc_attr( $slug ); ?>">
							<header class="dfmg-admin-module__head">
								<span class="dfmg-admin-module__icon"><?php echo Icons::svg( $icon, 22 ); // phpcs:ignore ?></span>
								<div class="dfmg-admin-module__name">
									<h2><?php echo esc_html( $product['name'] ); ?></h2>
									<span>
										<?php
										if ( $info ) {
											/* translators: %s: version */
											echo esc_html( sprintf( __( 'Installed: v%s', 'underworld-empire' ), $info['version'] ) );
										} elseif ( $product['version'] ) {
											echo esc_html( 'v' . $product['version'] );
										}
										?>
									</span>
								</div>
								<?php if ( $licensed ) : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--ok"><?php echo Icons::svg( 'check', 13 ); // phpcs:ignore ?> <?php esc_html_e( 'Licensed', 'underworld-empire' ); ?></span>
								<?php elseif ( $license ) : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--bad"><?php esc_html_e( 'License revoked', 'underworld-empire' ); ?></span>
								<?php else : ?>
									<span class="dfmg-admin-badge dfmg-admin-badge--premium"><?php echo Icons::svg( 'jail', 13 ); // phpcs:ignore ?> <?php echo esc_html( $product['price'] ?: __( 'Premium', 'underworld-empire' ) ); ?></span>
								<?php endif; ?>
							</header>
							<?php if ( $product['description'] ) : ?>
								<p class="dfmg-admin-module__desc"><?php echo esc_html( $product['description'] ); ?></p>
							<?php endif; ?>
							<?php if ( $too_old ) : ?>
								<?php /* translators: %s: version */ ?>
								<p class="dfmg-admin-note"><?php echo esc_html( sprintf( __( 'Needs Underworld Empire %s or newer. Update the plugin first.', 'underworld-empire' ), $product['requires'] ) ); ?></p>
							<?php endif; ?>

							<?php if ( $license ) : ?>
								<dl class="dfmg-admin-license">
									<div><dt><?php esc_html_e( 'Key', 'underworld-empire' ); ?></dt><dd><code><?php echo esc_html( Licenses::mask( (string) $license['key'] ) ); ?></code></dd></div>
									<div><dt><?php esc_html_e( 'License', 'underworld-empire' ); ?></dt><dd><?php echo 'lifetime' === ( $license['type'] ?? 'lifetime' ) ? esc_html__( 'Lifetime', 'underworld-empire' ) : esc_html( ucfirst( (string) $license['type'] ) ); ?></dd></div>
									<?php if ( ! empty( $license['checked_at'] ) ) : ?>
										<div><dt><?php esc_html_e( 'Checked', 'underworld-empire' ); ?></dt><dd><?php echo esc_html( Format::ago( (int) $license['checked_at'] ) ); ?></dd></div>
									<?php endif; ?>
								</dl>
								<footer class="dfmg-admin-module__foot">
									<?php if ( $licensed && ( ! $info || Licenses::update_available( $slug ) ) ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
											<input type="hidden" name="action" value="dfmg_license_update">
											<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
											<?php wp_nonce_field( 'dfmg_license_update_' . $slug ); ?>
											<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--gold">
												<?php
												/* translators: %s: version */
												echo esc_html( $info ? sprintf( __( 'Update to v%s', 'underworld-empire' ), $license['latest'] ) : __( 'Download again', 'underworld-empire' ) );
												?>
											</button>
										</form>
									<?php endif; ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Deactivate the license on this site? The module is switched off and removed; its game data is kept. You can then use the key on another site.', 'underworld-empire' ) ); ?>');">
										<input type="hidden" name="action" value="dfmg_license_deactivate">
										<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
										<?php wp_nonce_field( 'dfmg_license_deactivate_' . $slug ); ?>
										<button type="submit" class="dfmg-admin-link-btn"><?php esc_html_e( 'Deactivate license', 'underworld-empire' ); ?></button>
									</form>
								</footer>
							<?php else : ?>
								<footer class="dfmg-admin-module__foot dfmg-admin-module__foot--key">
									<form class="dfmg-admin-keyform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="dfmg_license_activate">
										<input type="hidden" name="product" value="<?php echo esc_attr( $slug ); ?>">
										<?php wp_nonce_field( 'dfmg_license_activate_' . $slug ); ?>
										<label class="screen-reader-text" for="dfmg-key-<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'License key', 'underworld-empire' ); ?></label>
										<input type="text" id="dfmg-key-<?php echo esc_attr( $slug ); ?>" name="license_key" placeholder="<?php esc_attr_e( 'Paste your license key', 'underworld-empire' ); ?>" autocomplete="off" spellcheck="false" required pattern="[A-Za-z0-9\-]{8,64}" <?php disabled( $too_old ); ?>>
										<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--dark" <?php disabled( $too_old ); ?>><?php esc_html_e( 'Activate', 'underworld-empire' ); ?></button>
									</form>
									<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( $product['buy_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy', 'underworld-empire' ); ?> <?php echo Icons::svg( 'external', 14 ); // phpcs:ignore ?></a>
								</footer>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
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
			<?php self::header( __( 'Settings', 'underworld-empire' ), __( 'General game settings. The settings of each module are on its own page: Modules → Configure.', 'underworld-empire' ), 'dfmg-settings' ); ?>
			<form method="post" class="dfmg-admin-panel dfmg-admin-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="core">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php self::render_fields( Settings::core_fields() ); ?>
				<div class="dfmg-admin-form__foot"><?php submit_button( __( 'Save settings', 'underworld-empire' ), 'primary', 'submit', false ); ?></div>
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
			self::header( __( 'Configure module', 'underworld-empire' ), esc_html__( 'This module is not enabled.', 'underworld-empire' ), 'dfmg-modules' );
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

		self::header(
			/* translators: %s: module name */
			sprintf( __( 'Configure: %s', 'underworld-empire' ), $module->name() ),
			'<a class="dfmg-admin-back" href="' . esc_url( admin_url( 'admin.php?page=dfmg-modules' ) ) . '">&larr; ' . esc_html__( 'Back to modules', 'underworld-empire' ) . '</a> ' . esc_html( (string) $module->info( 'description' ) ),
			'dfmg-modules'
		);

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
			<form method="post" class="dfmg-admin-panel dfmg-admin-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php self::render_fields( $fields ); ?>
				<div class="dfmg-admin-form__foot"><?php submit_button( __( 'Save settings', 'underworld-empire' ), 'primary', 'submit', false ); ?></div>
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
		$success = get_transient( 'dfmg_admin_success_' . get_current_user_id() );
		if ( $success ) {
			delete_transient( 'dfmg_admin_success_' . get_current_user_id() );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
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
