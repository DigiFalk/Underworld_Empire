<?php
/**
 * WordPress admin screens.
 *
 * @package DigiFalk\MafiaPBBGEngine
 */

namespace DigiFalk\MafiaPBBGEngine\Admin;

use DigiFalk\MafiaPBBGEngine\DB;
use DigiFalk\MafiaPBBGEngine\Extended;
use DigiFalk\MafiaPBBGEngine\Format;
use DigiFalk\MafiaPBBGEngine\Icons;
use DigiFalk\MafiaPBBGEngine\Frontend\Game;
use DigiFalk\MafiaPBBGEngine\Items;
use DigiFalk\MafiaPBBGEngine\Locations;
use DigiFalk\MafiaPBBGEngine\Plugin;
use DigiFalk\MafiaPBBGEngine\Ranks;
use DigiFalk\MafiaPBBGEngine\Settings;

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
		add_action( 'admin_post_dfmg_credit_choice', array( __CLASS__, 'handle_credit_choice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'module_errors_notice' ) );
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
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=dfmg' ) ) . '">' . esc_html__( 'Manage', 'mafia-pbbg-engine' ) . '</a>' );
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
		add_menu_page( __( 'Mafia PBBG Engine', 'mafia-pbbg-engine' ), __( 'Mafia PBBG Engine', 'mafia-pbbg-engine' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ), 'dashicons-shield-alt', 58 );
		add_submenu_page( 'dfmg', __( 'Dashboard', 'mafia-pbbg-engine' ), __( 'Dashboard', 'mafia-pbbg-engine' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'dfmg', __( 'Modules', 'mafia-pbbg-engine' ), __( 'Modules', 'mafia-pbbg-engine' ), $cap, 'dfmg-modules', array( __CLASS__, 'page_modules' ) );
		add_submenu_page( 'dfmg', __( 'Game data', 'mafia-pbbg-engine' ), __( 'Game data', 'mafia-pbbg-engine' ), $cap, 'dfmg-data', array( __CLASS__, 'page_data' ) );
		add_submenu_page( 'dfmg', __( 'Settings', 'mafia-pbbg-engine' ), __( 'Settings', 'mafia-pbbg-engine' ), $cap, 'dfmg-settings', array( __CLASS__, 'page_settings' ) );
		// Per module configuration page, reached through the Modules screen (not shown in the menu).
		add_submenu_page( 'dfmg', __( 'Configure module', 'mafia-pbbg-engine' ), __( 'Configure module', 'mafia-pbbg-engine' ), $cap, 'dfmg-module', array( __CLASS__, 'page_module' ) );
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
				'label'      => __( 'Players', 'mafia-pbbg-engine' ),
				'table'      => 'characters',
				'order'      => 'id DESC',
				'can_create' => false,
				'search'     => 'name',
				'help'       => __( 'Player characters.', 'mafia-pbbg-engine' ),
				'columns'    => array(
					'name'        => array( 'label' => __( 'Name', 'mafia-pbbg-engine' ), 'type' => 'text', 'required' => true ),
					'status'      => array(
						'label'   => __( 'Status', 'mafia-pbbg-engine' ),
						'type'    => 'select',
						'options' => array(
							1 => __( 'Alive', 'mafia-pbbg-engine' ),
							0 => __( 'Dead', 'mafia-pbbg-engine' ),
						),
					),
					'money'       => array( 'label' => __( 'Cash', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'bank'        => array( 'label' => __( 'Bank', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'bullets'     => array( 'label' => __( 'Bullets', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'exp'         => array( 'label' => __( 'Experience', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'damage'      => array( 'label' => __( 'Damage', 'mafia-pbbg-engine' ), 'type' => 'int', 'list' => false ),
					'rank_id'     => array( 'label' => __( 'Rank', 'mafia-pbbg-engine' ), 'type' => 'select', 'options' => array( Ranks::class, 'options' ) ),
					'location_id' => array( 'label' => __( 'City', 'mafia-pbbg-engine' ), 'type' => 'select', 'options' => array( Locations::class, 'options' ) ),
					'bio'         => array( 'label' => __( 'Profile text', 'mafia-pbbg-engine' ), 'type' => 'textarea' ),
				),
			),
			'ranks'       => array(
				'label'   => __( 'Ranks', 'mafia-pbbg-engine' ),
				'table'   => 'ranks',
				'order'   => 'exp_required ASC',
				'columns' => array(
					'name'          => array( 'label' => __( 'Name', 'mafia-pbbg-engine' ), 'required' => true ),
					'exp_required'  => array( 'label' => __( 'Required experience', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'max_players'   => array( 'label' => __( 'Max. players (0 = unlimited)', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'cash_reward'   => array( 'label' => __( 'Cash reward', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'bullet_reward' => array( 'label' => __( 'Bullet reward', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'max_health'    => array( 'label' => __( 'Health', 'mafia-pbbg-engine' ), 'type' => 'int', 'default' => 1000 ),
				),
			),
			'money_ranks' => array(
				'label'   => __( 'Wealth titles', 'mafia-pbbg-engine' ),
				'table'   => 'money_ranks',
				'order'   => 'min_money ASC',
				'columns' => array(
					'name'      => array( 'label' => __( 'Title', 'mafia-pbbg-engine' ), 'required' => true ),
					'min_money' => array( 'label' => __( 'From amount', 'mafia-pbbg-engine' ), 'type' => 'int' ),
				),
			),
			'locations'   => array(
				'label'   => __( 'Cities', 'mafia-pbbg-engine' ),
				'table'   => 'locations',
				'columns' => array(
					'name'         => array( 'label' => __( 'Name', 'mafia-pbbg-engine' ), 'required' => true ),
					'travel_cost'  => array( 'label' => __( 'Travel cost', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'travel_time'  => array( 'label' => __( 'Cooldown after travelling (sec)', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'bullet_stock' => array( 'label' => __( 'Bullet stock', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'bullet_price' => array( 'label' => __( 'Default bullet price', 'mafia-pbbg-engine' ), 'type' => 'int' ),
				),
			),
			'items'       => array(
				'label'   => __( 'Items', 'mafia-pbbg-engine' ),
				'table'   => 'items',
				'search'  => 'name',
				'columns' => array(
					'name'        => array( 'label' => __( 'Name', 'mafia-pbbg-engine' ), 'required' => true ),
					'type'        => array( 'label' => __( 'Type', 'mafia-pbbg-engine' ), 'type' => 'select', 'options' => array( Items::class, 'type_options' ) ),
					'price'       => array( 'label' => __( 'Price', 'mafia-pbbg-engine' ), 'type' => 'int' ),
					'buyable'     => array( 'label' => __( 'For sale on the black market', 'mafia-pbbg-engine' ), 'type' => 'checkbox', 'default' => 1 ),
					'description' => array( 'label' => __( 'Description', 'mafia-pbbg-engine' ), 'type' => 'textarea' ),
					'effects'     => array(
						'label'       => __( 'Effects', 'mafia-pbbg-engine' ),
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
		return __( 'One effect per line.', 'mafia-pbbg-engine' ) . '<br>' . implode( '<br>', $lines );
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
		self::header( __( 'Game data', 'mafia-pbbg-engine' ), __( 'Core game data. The data of each module is managed on its own page: Modules → Configure.', 'mafia-pbbg-engine' ), 'dfmg-data' );
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
			echo '<li class="dfmg-admin-tabs__group">' . esc_html__( 'Game data', 'mafia-pbbg-engine' ) . '</li>';
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
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
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
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
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
	 * Header shown on every Mafia PBBG Engine admin screen: brand, page title,
	 * quick actions and the section navigation.
	 */
	private static function header( string $title, string $subtitle = '', string $current = '' ): void {
		$nav = array(
			'dfmg'          => array( __( 'Dashboard', 'mafia-pbbg-engine' ), 'overview' ),
			'dfmg-modules'  => array( __( 'Modules', 'mafia-pbbg-engine' ), 'module' ),
			'dfmg-data'     => array( __( 'Game data', 'mafia-pbbg-engine' ), 'data' ),
			'dfmg-settings' => array( __( 'Settings', 'mafia-pbbg-engine' ), 'settings' ),
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
						<strong>Mafia PBBG Engine</strong>
						<span><?php echo esc_html( 'v' . DFMG_VERSION . ' · ' . (string) Settings::get( 'round_name' ) ); ?></span>
					</span>
				</div>
				<div class="dfmg-admin-hero__actions">
					<a class="dfmg-admin-btn dfmg-admin-btn--glass" href="<?php echo esc_url( $layout ); ?>"><?php echo Icons::svg( 'layout', 16 ); // phpcs:ignore ?> <?php esc_html_e( 'Game layout', 'mafia-pbbg-engine' ); ?></a>
					<a class="dfmg-admin-btn dfmg-admin-btn--gold" href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open the game', 'mafia-pbbg-engine' ); ?> <?php echo Icons::svg( 'external', 15 ); // phpcs:ignore ?></a>
				</div>
			</div>
			<div class="dfmg-admin-hero__title">
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( $subtitle ) : ?>
					<p><?php echo wp_kses_post( $subtitle ); ?></p>
				<?php endif; ?>
			</div>
			<nav class="dfmg-admin-nav" aria-label="<?php esc_attr_e( 'Mafia PBBG Engine', 'mafia-pbbg-engine' ); ?>">
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
			array( 'players', __( 'Living players', 'mafia-pbbg-engine' ), Format::number( $alive ) ),
			array( 'activity', __( 'Online now', 'mafia-pbbg-engine' ), Format::number( $online ) ),
			array( 'murder', __( 'Murdered', 'mafia-pbbg-engine' ), Format::number( $dead ) ),
			array( 'cash', __( 'Money in circulation', 'mafia-pbbg-engine' ), Format::money( $money ) ),
			array( 'crimes', __( 'Actions (24 hours)', 'mafia-pbbg-engine' ), Format::number( $actions ) ),
		);
		$customize = admin_url( 'customize.php?url=' . rawurlencode( Game::page_url() ) );
		$links     = array(
			array( 'layout', __( 'Game layout', 'mafia-pbbg-engine' ), __( 'Drag game elements into place', 'mafia-pbbg-engine' ), add_query_arg( 'autofocus[section]', 'dfmg_game_layout', $customize ) ),
			array( 'palette', __( 'Theme & colours', 'mafia-pbbg-engine' ), __( 'Header, footer, light & dark mode', 'mafia-pbbg-engine' ), $customize ),
			array( 'module', __( 'Modules', 'mafia-pbbg-engine' ), __( 'Switch game features on or off', 'mafia-pbbg-engine' ), admin_url( 'admin.php?page=dfmg-modules' ) ),
			array( 'data', __( 'Game data', 'mafia-pbbg-engine' ), __( 'Players, ranks, cities and items', 'mafia-pbbg-engine' ), admin_url( 'admin.php?page=dfmg-data' ) ),
			array( 'settings', __( 'Settings', 'mafia-pbbg-engine' ), __( 'Round, money and appearance', 'mafia-pbbg-engine' ), admin_url( 'admin.php?page=dfmg-settings' ) ),
			array( 'book', __( 'Documentation', 'mafia-pbbg-engine' ), __( 'Build your own modules', 'mafia-pbbg-engine' ), 'https://github.com/DigiFalk/Mafia_PBBG_engine/blob/main/docs/MODULES.md' ),
		);
		?>
		<div class="wrap dfmg-admin">
			<?php self::header( __( 'Dashboard', 'mafia-pbbg-engine' ), __( 'How your underworld is doing right now.', 'mafia-pbbg-engine' ), 'dfmg' ); ?>
			<?php self::render_credit_question(); ?>

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
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'statistics', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Player actions, last 7 days', 'mafia-pbbg-engine' ); ?></h2>
					<?php self::activity_chart(); ?>
				</section>
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'leaderboards', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Top players', 'mafia-pbbg-engine' ); ?></h2>
					<?php if ( ! $top ) : ?>
						<p class="dfmg-admin-empty"><?php esc_html_e( 'No players yet. Share the game page to get started.', 'mafia-pbbg-engine' ); ?></p>
					<?php else : ?>
						<ol class="dfmg-admin-top">
							<?php foreach ( $top as $row ) : ?>
								<li>
									<?php $avatar = \DigiFalk\MafiaPBBGEngine\Avatar::display_url( (int) $row['user_id'], 34 ); ?>
									<?php if ( $avatar ) : ?>
										<img class="dfmg-admin-top__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="" width="34" height="34">
									<?php else : ?>
										<span class="dfmg-admin-top__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( (string) $row['name'], 0, 1 ) ) ); ?></span>
									<?php endif; ?>
									<span class="dfmg-admin-top__who"><strong><?php echo esc_html( (string) $row['name'] ); ?></strong><small><?php echo esc_html( (string) ( Ranks::get( (int) $row['rank_id'] )['name'] ?? '' ) ); ?></small></span>
									<span class="dfmg-admin-top__num"><?php echo esc_html( Format::money( (int) $row['wealth'] ) ); ?><small><?php /* translators: %s: experience */ echo esc_html( sprintf( __( '%s XP', 'mafia-pbbg-engine' ), Format::number( (int) $row['exp'] ) ) ); ?></small></span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</section>
			</div>

			<div class="dfmg-admin-grid">
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'activity', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Live feed', 'mafia-pbbg-engine' ); ?></h2>
					<?php if ( ! $feed ) : ?>
						<p class="dfmg-admin-empty"><?php esc_html_e( 'Nothing has happened yet.', 'mafia-pbbg-engine' ); ?></p>
					<?php else : ?>
						<ul class="dfmg-admin-feed">
							<?php foreach ( $feed as $row ) : ?>
								<li class="<?php echo $row['success'] ? 'is-success' : 'is-fail'; ?>">
									<span class="dfmg-admin-feed__dot" aria-hidden="true"></span>
									<span class="dfmg-admin-feed__text"><strong><?php echo esc_html( (string) ( $row['name'] ?: __( 'Unknown player', 'mafia-pbbg-engine' ) ) ); ?></strong> <?php echo esc_html( self::activity_label( (string) $row['action'] ) ); ?> <em><?php echo $row['success'] ? esc_html__( 'succeeded', 'mafia-pbbg-engine' ) : esc_html__( 'failed', 'mafia-pbbg-engine' ); ?></em></span>
									<time><?php echo esc_html( Format::ago( (int) $row['created_at'] ) ); ?></time>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
				<section class="dfmg-admin-panel">
					<h2 class="dfmg-admin-panel__title"><?php echo Icons::svg( 'module', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Quick links', 'mafia-pbbg-engine' ); ?></h2>
					<div class="dfmg-admin-links">
						<?php foreach ( $links as $link ) : ?>
							<a class="dfmg-admin-link" href="<?php echo esc_url( $link[3] ); ?>"<?php echo 0 === strpos( $link[3], 'http' ) && false === strpos( $link[3], admin_url() ) ? ' target="_blank" rel="noopener"' : ''; ?>>
								<span class="dfmg-admin-link__icon"><?php echo Icons::svg( $link[0], 18 ); // phpcs:ignore ?></span>
								<span><strong><?php echo esc_html( $link[1] ); ?></strong><small><?php echo esc_html( $link[2] ); ?></small></span>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="dfmg-admin-shortcode">
						<?php esc_html_e( 'Game page', 'mafia-pbbg-engine' ); ?>:
						<a href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( Game::page_url() ); ?></a>
						<code>[mafia_pbbg_engine]</code>
					</p>
				</section>
			</div>

			<details class="dfmg-admin-panel dfmg-admin-danger">
				<summary><?php echo Icons::svg( 'alert', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Start a new round', 'mafia-pbbg-engine' ); ?> <small><?php esc_html_e( 'Erases all characters and player data', 'mafia-pbbg-engine' ); ?></small></summary>
				<p><?php esc_html_e( 'Erases all characters and player data (money, cars, families, messages, ...). Game data like crimes, cities and items is kept.', 'mafia-pbbg-engine' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'All player data will be erased. Continue?', 'mafia-pbbg-engine' ) ); ?>');">
					<input type="hidden" name="action" value="dfmg_new_round">
					<?php wp_nonce_field( 'dfmg_new_round' ); ?>
					<p><label><?php esc_html_e( 'New round name', 'mafia-pbbg-engine' ); ?> <input type="text" name="round_name" value="<?php echo esc_attr( (string) Settings::get( 'round_name' ) ); ?>"></label></p>
					<p><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I understand this can\'t be undone', 'mafia-pbbg-engine' ); ?></label></p>
					<?php submit_button( __( 'Start new round', 'mafia-pbbg-engine' ), 'delete' ); ?>
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
		echo '<svg viewBox="0 0 ' . esc_attr( $w . ' ' . $h ) . '" role="img" aria-label="' . esc_attr__( 'Player actions per day', 'mafia-pbbg-engine' ) . '">';
		for ( $g = 0; $g <= 4; $g++ ) {
			$y = 12 + $plot_h - ( $plot_h * $g / 4 );
			echo '<line class="dfmg-admin-chart__grid" x1="' . esc_attr( $left ) . '" x2="' . esc_attr( $w - 4 ) . '" y1="' . esc_attr( $y ) . '" y2="' . esc_attr( $y ) . '"/>';
			echo '<text class="dfmg-admin-chart__tick" x="' . esc_attr( $left - 8 ) . '" y="' . esc_attr( $y + 4 ) . '" text-anchor="end">' . esc_html( Format::number( $step * $g ) ) . '</text>';
		}
		foreach ( $days as $i => $n ) {
			$x   = $left + $slot * $i + ( $slot - $bar_w ) / 2;
			$bh  = $top ? $plot_h * $n / $top : 0;
			$y   = 12 + $plot_h - $bh;
			$r   = min( 4, $bh );
			$cx  = $x + $bar_w / 2;
			echo '<g class="dfmg-admin-chart__col' . ( $n === $max && $max > 0 ? ' is-max' : '' ) . '" tabindex="0">';
			echo '<title>' . esc_html( $labels[ $i ] . ': ' . Format::number( $n ) ) . '</title>';
			echo '<rect class="dfmg-admin-chart__hit" x="' . esc_attr( $left + $slot * $i ) . '" y="12" width="' . esc_attr( $slot ) . '" height="' . esc_attr( $plot_h ) . '"/>';
			if ( $bh > 0 ) {
				// Rounded data end, square at the baseline.
				$path = sprintf(
					'M%1$.1f %2$.1fV%3$.1fQ%1$.1f %4$.1f %5$.1f %4$.1fH%6$.1fQ%7$.1f %4$.1f %7$.1f %3$.1fV%2$.1fZ',
					$x, 12 + $plot_h, $y + $r, $y, $x + $r, $x + $bar_w - $r, $x + $bar_w
				);
				echo '<path class="dfmg-admin-chart__bar" d="' . esc_attr( $path ) . '"/>';
			}
			if ( $n === $max && $max > 0 ) {
				echo '<text class="dfmg-admin-chart__value" x="' . esc_attr( $cx ) . '" y="' . esc_attr( $y - 7 ) . '" text-anchor="middle">' . esc_html( Format::number( $n ) ) . '</text>';
			}
			echo '<text class="dfmg-admin-chart__day" x="' . esc_attr( $cx ) . '" y="' . esc_attr( $h - 8 ) . '" text-anchor="middle">' . esc_html( $labels[ $i ] ) . '</text>';
			echo '</g>';
		}
		echo '<line class="dfmg-admin-chart__base" x1="' . esc_attr( $left ) . '" x2="' . esc_attr( $w - 4 ) . '" y1="' . esc_attr( 12 + $plot_h ) . '" y2="' . esc_attr( 12 + $plot_h ) . '"/>';
		echo '</svg>';
		echo '<table class="screen-reader-text"><caption>' . esc_html__( 'Player actions per day', 'mafia-pbbg-engine' ) . '</caption><tbody>';
		foreach ( $days as $i => $n ) {
			echo '<tr><th scope="row">' . esc_html( $labels[ $i ] ) . '</th><td>' . esc_html( Format::number( $n ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		/* translators: %s: number of actions */
		echo '<figcaption>' . esc_html( sprintf( __( '%s actions this week', 'mafia-pbbg-engine' ), Format::number( array_sum( $days ) ) ) ) . '</figcaption>';
		echo '</figure>';
	}

	/**
	 * Asks once whether the game may show "Mafia PBBG Engine by DigiFalk". Nothing is shown
	 * until the site owner says yes (also later under Settings).
	 */
	private static function render_credit_question(): void {
		if ( Extended::active() || get_option( 'dfmg_credit_asked' ) || Settings::get( 'show_credit', 0 ) ) {
			return;
		}
		$action = admin_url( 'admin-post.php' );
		?>
		<section class="dfmg-admin-panel dfmg-admin-credit-question">
			<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'membership', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Support Mafia PBBG Engine?', 'mafia-pbbg-engine' ); ?></h2>
			<p><?php esc_html_e( 'May the game show a small line "Mafia PBBG Engine by DigiFalk" with a link to the maker at the bottom of game pages? It helps others find the game. It stays off unless you choose yes, and you can change it any time under Settings.', 'mafia-pbbg-engine' ); ?></p>
			<div class="dfmg-admin-premium__actions">
				<form method="post" action="<?php echo esc_url( $action ); ?>">
					<input type="hidden" name="action" value="dfmg_credit_choice">
					<input type="hidden" name="choice" value="yes">
					<?php wp_nonce_field( 'dfmg_credit_choice' ); ?>
					<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--gold"><?php esc_html_e( 'Yes, show the line', 'mafia-pbbg-engine' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( $action ); ?>">
					<input type="hidden" name="action" value="dfmg_credit_choice">
					<input type="hidden" name="choice" value="no">
					<?php wp_nonce_field( 'dfmg_credit_choice' ); ?>
					<button type="submit" class="dfmg-admin-btn dfmg-admin-btn--ghost"><?php esc_html_e( 'No thanks', 'mafia-pbbg-engine' ); ?></button>
				</form>
			</div>
		</section>
		<?php
	}

	/**
	 * Modules that could not be loaded (for example made for an older version) are skipped;
	 * tell the admin which ones and why.
	 */
	public static function module_errors_notice(): void {
		if ( ! self::can() ) {
			return;
		}
		$registry = Plugin::instance()->modules;
		foreach ( \DigiFalk\MafiaPBBGEngine\Module\Registry::errors() as $id => $message ) {
			$info = $registry->info( (string) $id );
			if ( ! $info || ! $registry->is_enabled( (string) $id ) ) {
				continue;
			}
			echo '<div class="notice notice-error"><p><strong>' . esc_html(
				/* translators: %s: module name */
				sprintf( __( 'Mafia PBBG Engine: the module "%s" could not be loaded and is switched off for now.', 'mafia-pbbg-engine' ), $info['name'] )
			) . '</strong> ' . esc_html__( 'It may be made for another version of Mafia PBBG Engine. Update the module (premium modules: Modules screen, Update or Download again), or switch it off.', 'mafia-pbbg-engine' )
				. '</p><p><code>' . esc_html( (string) $message ) . '</code></p></div>';
		}
	}

	public static function handle_credit_choice(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
		}
		check_admin_referer( 'dfmg_credit_choice' );
		$yes = 'yes' === sanitize_key( wp_unslash( $_POST['choice'] ?? '' ) );
		Settings::set( 'show_credit', $yes ? 1 : 0 );
		update_option( 'dfmg_credit_asked', 1 );
		if ( $yes ) {
			set_transient( 'dfmg_admin_success_' . get_current_user_id(), __( 'Thank you! The line is shown at the bottom of game pages.', 'mafia-pbbg-engine' ), 60 );
		}
		self::redirect( admin_url( 'admin.php?page=dfmg' ) );
	}

	public static function handle_new_round(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
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
			'bundled' => __( 'Bundled', 'mafia-pbbg-engine' ),
			'custom'  => __( 'Custom module', 'mafia-pbbg-engine' ),
			'plugin'  => __( 'Other plugin', 'mafia-pbbg-engine' ),
		);
		$subtitle = sprintf(
			/* translators: %s: directory */
			esc_html__( 'Switch game features on or off. Place custom modules in %s (one folder per module containing a module.php).', 'mafia-pbbg-engine' ),
			'<code>' . esc_html( str_replace( ABSPATH, '', DFMG_CUSTOM_MODULES_DIR ) ) . '</code>'
		);
		?>
		<div class="wrap dfmg-admin">
			<?php self::header( __( 'Modules', 'mafia-pbbg-engine' ), $subtitle, 'dfmg-modules' ); ?>

			<?php
			if ( has_action( 'dfmg_admin_premium_modules' ) ) {
				/**
				 * The premium modules section of the Modules screen (Mafia PBBG Engine Extended).
				 */
				do_action( 'dfmg_admin_premium_modules' );
			} else {
				self::render_extended_box();
			}
			?>

			<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'module', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Installed modules', 'mafia-pbbg-engine' ); ?></h2>
			<div class="dfmg-admin-toolbar" data-dfmg-modules-toolbar>
				<div class="dfmg-admin-segment" role="group" aria-label="<?php esc_attr_e( 'Filter modules', 'mafia-pbbg-engine' ); ?>">
					<button type="button" class="is-active" data-filter="all"><?php esc_html_e( 'All', 'mafia-pbbg-engine' ); ?> <span><?php echo (int) count( $available ); ?></span></button>
					<button type="button" data-filter="on"><?php esc_html_e( 'Active', 'mafia-pbbg-engine' ); ?> <span><?php echo (int) $active; ?></span></button>
					<button type="button" data-filter="off"><?php esc_html_e( 'Inactive', 'mafia-pbbg-engine' ); ?> <span><?php echo (int) ( count( $available ) - $active ); ?></span></button>
				</div>
				<label class="dfmg-admin-search-field">
					<?php echo Icons::svg( 'detectives', 16 ); // phpcs:ignore ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Search modules', 'mafia-pbbg-engine' ); ?></span>
					<input type="search" placeholder="<?php esc_attr_e( 'Search modules…', 'mafia-pbbg-engine' ); ?>" data-dfmg-module-search>
				</label>
			</div>

			<div class="dfmg-admin-modules" data-dfmg-installed>
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
								<span><?php echo esc_html( 'v' . $info['version'] . ' · ' . ( ! empty( $info['premium'] ) ? __( 'Premium', 'mafia-pbbg-engine' ) : ( $sources[ $info['source'] ] ?? $info['source'] ) ) . ( $info['author'] ? ' · ' . $info['author'] : '' ) ); ?></span>
							</div>
							<?php if ( $info['required'] ) : ?>
								<span class="dfmg-admin-badge"><?php esc_html_e( 'Required', 'mafia-pbbg-engine' ); ?></span>
							<?php elseif ( ! empty( $info['premium'] ) && ! $registry->runnable( $id ) ) : ?>
								<?php if ( Extended::active() ) : ?>
									<a class="dfmg-admin-badge dfmg-admin-badge--premium" href="#premium-<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'License needed', 'mafia-pbbg-engine' ); ?></a>
								<?php else : ?>
									<a class="dfmg-admin-badge dfmg-admin-badge--premium" href="#dfmg-extended"><?php esc_html_e( 'Needs Extended', 'mafia-pbbg-engine' ); ?></a>
								<?php endif; ?>
							<?php else : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="dfmg_module">
									<input type="hidden" name="module" value="<?php echo esc_attr( $id ); ?>">
									<input type="hidden" name="state" value="<?php echo $on ? 'off' : 'on'; ?>">
									<?php wp_nonce_field( 'dfmg_module_' . $id ); ?>
									<button type="submit" class="dfmg-admin-switch" role="switch" aria-checked="<?php echo $on ? 'true' : 'false'; ?>" title="<?php echo $on ? esc_attr__( 'Disable', 'mafia-pbbg-engine' ) : esc_attr__( 'Enable', 'mafia-pbbg-engine' ); ?>">
										<span class="screen-reader-text"><?php echo esc_html( ( $on ? __( 'Disable', 'mafia-pbbg-engine' ) : __( 'Enable', 'mafia-pbbg-engine' ) ) . ' ' . $info['name'] ); ?></span>
									</button>
								</form>
							<?php endif; ?>
						</header>
						<p class="dfmg-admin-module__desc"><?php echo esc_html( $info['description'] ); ?></p>
						<footer class="dfmg-admin-module__foot">
							<?php if ( $info['requires'] ) : ?>
								<span class="dfmg-admin-module__requires"><?php esc_html_e( 'Requires', 'mafia-pbbg-engine' ); ?>
									<?php foreach ( $info['requires'] as $dep ) : ?>
										<span class="dfmg-admin-chip"><?php echo esc_html( $dep ); ?></span>
									<?php endforeach; ?>
								</span>
							<?php endif; ?>
							<?php if ( $module && ( $module->settings_fields() || $module->admin_tables() ) ) : ?>
								<a class="dfmg-admin-btn dfmg-admin-btn--ghost dfmg-configure" href="<?php echo esc_url( self::module_url( $id ) ); ?>"><?php echo Icons::svg( 'settings', 15 ); // phpcs:ignore ?> <?php esc_html_e( 'Configure', 'mafia-pbbg-engine' ); ?></a>
							<?php endif; ?>
						</footer>
					</article>
				<?php endforeach; ?>
			</div>
			<p class="dfmg-admin-empty" hidden data-dfmg-no-modules><?php esc_html_e( 'No modules match your search.', 'mafia-pbbg-engine' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Shown instead of the premium section while Mafia PBBG Engine Extended is not active.
	 */
	private static function render_extended_box(): void {
		?>
		<section class="dfmg-admin-premium" id="dfmg-extended">
			<div class="dfmg-admin-premium__head">
				<div>
					<h2 class="dfmg-admin-section-title"><?php echo Icons::svg( 'membership', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Mafia PBBG Engine Extended (free)', 'mafia-pbbg-engine' ); ?></h2>
					<p><?php esc_html_e( 'Extended adds families, murders, detectives, bounties, the bullet factory, the black market, blackjack, police chases, properties, the forum and the Mafia PBBG Engine theme. It is also needed for premium modules.', 'mafia-pbbg-engine' ); ?></p>
				</div>
				<div class="dfmg-admin-premium__actions">
					<?php echo Extended::button(); // phpcs:ignore ?>
				</div>
			</div>
		</section>
		<?php
	}

	public static function handle_module(): void {
		$id = sanitize_key( wp_unslash( $_POST['module'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
		}
		check_admin_referer( 'dfmg_module_' . $id );
		$registry = Plugin::instance()->modules;
		$result   = 'on' === sanitize_key( wp_unslash( $_POST['state'] ?? '' ) ) ? $registry->enable( $id ) : $registry->disable( $id );
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
				'label'  => __( 'General', 'mafia-pbbg-engine' ),
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
			<?php self::header( __( 'Settings', 'mafia-pbbg-engine' ), __( 'General game settings. The settings of each module are on its own page: Modules → Configure.', 'mafia-pbbg-engine' ), 'dfmg-settings' ); ?>
			<form method="post" class="dfmg-admin-panel dfmg-admin-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="core">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php self::render_fields( Settings::core_fields() ); ?>
				<div class="dfmg-admin-form__foot"><?php submit_button( __( 'Save settings', 'mafia-pbbg-engine' ), 'primary', 'submit', false ); ?></div>
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
		return ( 'dfmg-module' === sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) ) ? 'dfmg-modules' : $submenu_file;
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
			self::header( __( 'Configure module', 'mafia-pbbg-engine' ), esc_html__( 'This module is not enabled.', 'mafia-pbbg-engine' ), 'dfmg-modules' );
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=dfmg-modules' ) ) . '">&larr; ' . esc_html__( 'Back to modules', 'mafia-pbbg-engine' ) . '</a></p></div>';
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
			sprintf( __( 'Configure: %s', 'mafia-pbbg-engine' ), $module->name() ),
			'<a class="dfmg-admin-back" href="' . esc_url( admin_url( 'admin.php?page=dfmg-modules' ) ) . '">&larr; ' . esc_html__( 'Back to modules', 'mafia-pbbg-engine' ) . '</a> ' . esc_html( (string) $module->info( 'description' ) ),
			'dfmg-modules'
		);

		$extra = array();
		if ( $fields ) {
			$extra['settings'] = array(
				'label'  => __( 'Settings', 'mafia-pbbg-engine' ),
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
			<h2><?php esc_html_e( 'Settings', 'mafia-pbbg-engine' ); ?></h2>
			<form method="post" class="dfmg-admin-panel dfmg-admin-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dfmg_settings">
				<input type="hidden" name="section" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'dfmg_settings' ); ?>
				<?php self::render_fields( $fields ); ?>
				<div class="dfmg-admin-form__foot"><?php submit_button( __( 'Save settings', 'mafia-pbbg-engine' ), 'primary', 'submit', false ); ?></div>
			</form>
			<?php
		}
		echo '</div></div></div>';
	}

	public static function handle_settings(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
		}
		check_admin_referer( 'dfmg_settings' );
		$input    = (array) wp_unslash( $_POST['settings'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$section  = sanitize_key( wp_unslash( $_POST['section'] ?? 'core' ) );
		$sections = self::settings_sections();
		if ( ! isset( $sections[ $section ] ) ) {
			wp_die( esc_html__( 'Access denied.', 'mafia-pbbg-engine' ) );
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
			'saved'   => __( 'Saved.', 'mafia-pbbg-engine' ),
			'deleted' => __( 'Deleted.', 'mafia-pbbg-engine' ),
			'round'   => __( 'A new round has started.', 'mafia-pbbg-engine' ),
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
