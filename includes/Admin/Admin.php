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
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=dfmg' ) ) . '">' . esc_html__( 'Beheer', 'wp-maffia-game' ) . '</a>' );
		return $links;
	}

	public static function assets( string $hook ): void {
		if ( false !== strpos( $hook, 'dfmg' ) ) {
			wp_enqueue_style( 'dfmg-admin', DFMG_URL . 'assets/css/admin.css', array(), DFMG_VERSION );
		}
	}

	public static function menu(): void {
		$cap = self::cap();
		add_menu_page( __( 'Maffia Game', 'wp-maffia-game' ), __( 'Maffia Game', 'wp-maffia-game' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ), 'dashicons-shield-alt', 58 );
		add_submenu_page( 'dfmg', __( 'Dashboard', 'wp-maffia-game' ), __( 'Dashboard', 'wp-maffia-game' ), $cap, 'dfmg', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'dfmg', __( 'Modules', 'wp-maffia-game' ), __( 'Modules', 'wp-maffia-game' ), $cap, 'dfmg-modules', array( __CLASS__, 'page_modules' ) );
		add_submenu_page( 'dfmg', __( 'Spelgegevens', 'wp-maffia-game' ), __( 'Spelgegevens', 'wp-maffia-game' ), $cap, 'dfmg-data', array( __CLASS__, 'page_data' ) );
		add_submenu_page( 'dfmg', __( 'Instellingen', 'wp-maffia-game' ), __( 'Instellingen', 'wp-maffia-game' ), $cap, 'dfmg-settings', array( __CLASS__, 'page_settings' ) );
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
				'label'      => __( 'Spelers', 'wp-maffia-game' ),
				'table'      => 'characters',
				'order'      => 'id DESC',
				'can_create' => false,
				'search'     => 'name',
				'help'       => __( 'Personages van spelers. Hier kun je o.a. premium punten toekennen.', 'wp-maffia-game' ),
				'columns'    => array(
					'name'        => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'type' => 'text', 'required' => true ),
					'status'      => array(
						'label'   => __( 'Status', 'wp-maffia-game' ),
						'type'    => 'select',
						'options' => array(
							1 => __( 'Levend', 'wp-maffia-game' ),
							0 => __( 'Dood', 'wp-maffia-game' ),
						),
					),
					'money'       => array( 'label' => __( 'Contant', 'wp-maffia-game' ), 'type' => 'int' ),
					'bank'        => array( 'label' => __( 'Bank', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullets'     => array( 'label' => __( 'Kogels', 'wp-maffia-game' ), 'type' => 'int' ),
					'exp'         => array( 'label' => __( 'Ervaring', 'wp-maffia-game' ), 'type' => 'int' ),
					'points'      => array( 'label' => __( 'Punten', 'wp-maffia-game' ), 'type' => 'int' ),
					'damage'      => array( 'label' => __( 'Schade', 'wp-maffia-game' ), 'type' => 'int', 'list' => false ),
					'rank_id'     => array( 'label' => __( 'Rang', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Ranks::class, 'options' ) ),
					'location_id' => array( 'label' => __( 'Stad', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Locations::class, 'options' ) ),
					'bio'         => array( 'label' => __( 'Profieltekst', 'wp-maffia-game' ), 'type' => 'textarea' ),
				),
			),
			'ranks'       => array(
				'label'   => __( 'Rangen', 'wp-maffia-game' ),
				'table'   => 'ranks',
				'order'   => 'exp_required ASC',
				'columns' => array(
					'name'          => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'exp_required'  => array( 'label' => __( 'Benodigde ervaring', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_players'   => array( 'label' => __( 'Max. spelers (0 = onbeperkt)', 'wp-maffia-game' ), 'type' => 'int' ),
					'cash_reward'   => array( 'label' => __( 'Geldbeloning', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_reward' => array( 'label' => __( 'Kogelbeloning', 'wp-maffia-game' ), 'type' => 'int' ),
					'max_health'    => array( 'label' => __( 'Gezondheid', 'wp-maffia-game' ), 'type' => 'int', 'default' => 1000 ),
				),
			),
			'money_ranks' => array(
				'label'   => __( 'Rijkdomtitels', 'wp-maffia-game' ),
				'table'   => 'money_ranks',
				'order'   => 'min_money ASC',
				'columns' => array(
					'name'      => array( 'label' => __( 'Titel', 'wp-maffia-game' ), 'required' => true ),
					'min_money' => array( 'label' => __( 'Vanaf bedrag', 'wp-maffia-game' ), 'type' => 'int' ),
				),
			),
			'locations'   => array(
				'label'   => __( 'Steden', 'wp-maffia-game' ),
				'table'   => 'locations',
				'columns' => array(
					'name'         => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'travel_cost'  => array( 'label' => __( 'Reiskosten', 'wp-maffia-game' ), 'type' => 'int' ),
					'travel_time'  => array( 'label' => __( 'Wachttijd na reis (sec)', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_stock' => array( 'label' => __( 'Kogelvoorraad', 'wp-maffia-game' ), 'type' => 'int' ),
					'bullet_price' => array( 'label' => __( 'Standaard kogelprijs', 'wp-maffia-game' ), 'type' => 'int' ),
				),
			),
			'items'       => array(
				'label'   => __( 'Items', 'wp-maffia-game' ),
				'table'   => 'items',
				'search'  => 'name',
				'columns' => array(
					'name'        => array( 'label' => __( 'Naam', 'wp-maffia-game' ), 'required' => true ),
					'type'        => array( 'label' => __( 'Soort', 'wp-maffia-game' ), 'type' => 'select', 'options' => array( Items::class, 'type_options' ) ),
					'price'       => array( 'label' => __( 'Prijs', 'wp-maffia-game' ), 'type' => 'int' ),
					'buyable'     => array( 'label' => __( 'Te koop op zwarte markt', 'wp-maffia-game' ), 'type' => 'checkbox', 'default' => 1 ),
					'description' => array( 'label' => __( 'Omschrijving', 'wp-maffia-game' ), 'type' => 'textarea' ),
					'effects'     => array(
						'label'       => __( 'Effecten', 'wp-maffia-game' ),
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
		return __( 'Eén effect per regel.', 'wp-maffia-game' ) . '<br>' . implode( '<br>', $lines );
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
		echo '<div class="wrap dfmg-admin"><h1>' . esc_html__( 'Spelgegevens', 'wp-maffia-game' ) . '</h1>';
		self::notices();
		echo '<div class="dfmg-admin-data"><ul class="dfmg-admin-tabs">';
		$group = null;
		foreach ( $tables as $key => $def ) {
			$g = $def['module'] ?? __( 'Kern', 'wp-maffia-game' );
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
			wp_die( esc_html__( 'Geen toegang.', 'wp-maffia-game' ) );
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
			wp_die( esc_html__( 'Geen toegang.', 'wp-maffia-game' ) );
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
			<h1><?php esc_html_e( 'Maffia Game', 'wp-maffia-game' ); ?></h1>
			<?php self::notices(); ?>
			<div class="dfmg-admin-cards">
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $alive ) ); ?></strong><span><?php esc_html_e( 'Levende spelers', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $online ) ); ?></strong><span><?php esc_html_e( 'Nu online', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $dead ) ); ?></strong><span><?php esc_html_e( 'Vermoord', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::money( $money ) ); ?></strong><span><?php esc_html_e( 'Geld in omloop', 'wp-maffia-game' ); ?></span></div>
				<div class="dfmg-admin-card"><strong><?php echo esc_html( Format::number( $actions ) ); ?></strong><span><?php esc_html_e( 'Acties (24 uur)', 'wp-maffia-game' ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Spelpagina', 'wp-maffia-game' ); ?></h2>
			<p>
				<?php esc_html_e( 'Het spel draait op de pagina met de shortcode', 'wp-maffia-game' ); ?> <code>[maffia_game]</code>:
				<a href="<?php echo esc_url( Game::page_url() ); ?>" target="_blank"><?php echo esc_html( Game::page_url() ); ?></a>
			</p>

			<h2><?php esc_html_e( 'Nieuwe ronde', 'wp-maffia-game' ); ?></h2>
			<p><?php esc_html_e( 'Wist alle personages en spelersdata (geld, auto\'s, families, berichten, ...). Spelgegevens zoals misdaden, steden en items blijven bewaard.', 'wp-maffia-game' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Alle spelersdata wordt gewist. Doorgaan?', 'wp-maffia-game' ) ); ?>');">
				<input type="hidden" name="action" value="dfmg_new_round">
				<?php wp_nonce_field( 'dfmg_new_round' ); ?>
				<p><label><?php esc_html_e( 'Naam nieuwe ronde', 'wp-maffia-game' ); ?> <input type="text" name="round_name" value="<?php echo esc_attr( (string) Settings::get( 'round_name' ) ); ?>"></label></p>
				<p><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'Ik begrijp dat dit niet ongedaan kan worden', 'wp-maffia-game' ); ?></label></p>
				<?php submit_button( __( 'Start nieuwe ronde', 'wp-maffia-game' ), 'delete' ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_new_round(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Geen toegang.', 'wp-maffia-game' ) );
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
			'bundled' => __( 'Meegeleverd', 'wp-maffia-game' ),
			'custom'  => __( 'Eigen module', 'wp-maffia-game' ),
			'plugin'  => __( 'Andere plugin', 'wp-maffia-game' ),
		);
		?>
		<div class="wrap dfmg-admin">
			<h1><?php esc_html_e( 'Modules', 'wp-maffia-game' ); ?></h1>
			<?php self::notices(); ?>
			<p>
				<?php
				printf(
					/* translators: %s: directory */
					esc_html__( 'Eigen modules plaats je in %s (één map per module met een module.php). Zie docs/MODULES.md in de plugin.', 'wp-maffia-game' ),
					'<code>' . esc_html( str_replace( ABSPATH, '', DFMG_CUSTOM_MODULES_DIR ) ) . '</code>'
				);
				?>
			</p>
			<table class="widefat striped dfmg-modules">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Module', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Omschrijving', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Vereist', 'wp-maffia-game' ); ?></th>
						<th><?php esc_html_e( 'Bron', 'wp-maffia-game' ); ?></th>
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
									<em><?php esc_html_e( 'Verplicht', 'wp-maffia-game' ); ?></em>
								<?php else : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="dfmg_module">
										<input type="hidden" name="module" value="<?php echo esc_attr( $id ); ?>">
										<input type="hidden" name="state" value="<?php echo $on ? 'off' : 'on'; ?>">
										<?php wp_nonce_field( 'dfmg_module_' . $id ); ?>
										<button class="button <?php echo $on ? '' : 'button-primary'; ?>"><?php echo $on ? esc_html__( 'Uitschakelen', 'wp-maffia-game' ) : esc_html__( 'Inschakelen', 'wp-maffia-game' ); ?></button>
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
			wp_die( esc_html__( 'Geen toegang.', 'wp-maffia-game' ) );
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
				'label'  => __( 'Algemeen', 'wp-maffia-game' ),
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
			<h1><?php esc_html_e( 'Instellingen', 'wp-maffia-game' ); ?></h1>
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
				<?php submit_button( __( 'Instellingen opslaan', 'wp-maffia-game' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_settings(): void {
		if ( ! self::can() ) {
			wp_die( esc_html__( 'Geen toegang.', 'wp-maffia-game' ) );
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
			'saved'   => __( 'Opgeslagen.', 'wp-maffia-game' ),
			'deleted' => __( 'Verwijderd.', 'wp-maffia-game' ),
			'round'   => __( 'Een nieuwe ronde is gestart.', 'wp-maffia-game' ),
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
