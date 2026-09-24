<?php
/**
 * Base class for every game module.
 *
 * A module is a directory containing a module.php file. That file starts with a
 * header comment and returns an instance of a Module subclass:
 *
 *   <?php
 *   /**
 *    * Module Name: My module
 *    * Description: What it does.
 *    * Version: 1.0.0
 *    * Author: DigiFalk
 *    * Requires: bank, garage
 *    * /
 *   return new class extends \DigiFalk\MaffiaGame\Module\Module { ... };
 *
 * See docs/MODULES.md for the full guide.
 *
 * @package DigiFalk\MaffiaGame
 */

namespace DigiFalk\MaffiaGame\Module;

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Flash;
use DigiFalk\MaffiaGame\Frontend\Game;
use DigiFalk\MaffiaGame\Settings;

defined( 'ABSPATH' ) || exit;

abstract class Module {

	/** @var string */
	private $id = '';

	/** @var string */
	private $dir = '';

	/** @var array */
	private $info = array();

	/**
	 * Called by the registry right after loading.
	 */
	final public function setup( string $id, string $dir, array $info ): void {
		$this->id   = $id;
		$this->dir  = trailingslashit( $dir );
		$this->info = $info;
	}

	final public function id(): string {
		return $this->id;
	}

	final public function dir(): string {
		return $this->dir;
	}

	/**
	 * Header value (name, description, version, author, requires).
	 *
	 * @return mixed
	 */
	final public function info( string $key ) {
		return $this->info[ $key ] ?? '';
	}

	public function name(): string {
		return (string) $this->info( 'name' );
	}

	/**
	 * Page title inside the game.
	 */
	public function title(): string {
		return $this->name();
	}

	/* ------------------------------------------------------------------ */
	/* Lifecycle – override what you need                                   */
	/* ------------------------------------------------------------------ */

	/**
	 * Runs on every request while the module is enabled. Register hooks here.
	 */
	public function boot(): void {}

	/**
	 * Tables: short name => column definition body (dbDelta format).
	 * Tables are prefixed automatically: 'crimes' becomes wp_dfmg_crimes.
	 */
	public function schema(): array {
		return array();
	}

	/**
	 * Fill tables with starting data. Runs once, the first time the module is enabled.
	 */
	public function seed(): void {}

	/**
	 * Tables with player data that are emptied when a new round starts.
	 */
	public function round_tables(): array {
		return array();
	}

	/**
	 * Extra work when a new round starts.
	 */
	public function reset_round(): void {}

	/* ------------------------------------------------------------------ */
	/* Frontend                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Menu entries: list of [ 'label', 'group', 'order', 'timer' (optional), 'badge' (optional), 'args' (optional) ].
	 */
	public function menu( Character $c ): array {
		return array();
	}

	/**
	 * Whether the page can be opened while in jail.
	 */
	public function allowed_in_jail(): bool {
		return false;
	}

	/**
	 * Whether the page can be opened while in hospital.
	 */
	public function allowed_in_hospital(): bool {
		return false;
	}

	/**
	 * Whether the page is visible without an alive character.
	 */
	public function public_page(): bool {
		return false;
	}

	/**
	 * Output of the module page. $query holds sanitized GET parameters.
	 */
	public function render( Character $c, array $query ): string {
		return '';
	}

	/* ------------------------------------------------------------------ */
	/* Admin                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Game data editable in the admin. See Admin\DataTable for the format.
	 */
	public function admin_tables(): array {
		return array();
	}

	/**
	 * Settings fields: key => [ label, type (text|int|checkbox|textarea|select|datetime), default, description, options ].
	 */
	public function settings_fields(): array {
		return array();
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	public function setting( string $key, $default = null ) {
		if ( null === $default ) {
			$fields  = $this->settings_fields();
			$default = $fields[ $key ]['default'] ?? null;
		}
		return Settings::get( $key, $default );
	}

	public function url( array $args = array(), string $route = '' ): string {
		return Game::url( $route ?: $this->id, $args );
	}

	/**
	 * Opening <form> tag posting to one of this module's action_* methods.
	 * Close it with </form>.
	 */
	public function form( string $action, array $hidden = array(), string $class = 'dfmg-form' ): string {
		return Game::form_open( $this->id, $action, $hidden, $class );
	}

	/**
	 * A one-button form.
	 */
	public function button( string $action, string $label, array $hidden = array(), string $class = 'dfmg-button' ): string {
		return $this->form( $action, $hidden, 'dfmg-inline-form' )
			. '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	}

	/**
	 * Render a template from views/ in the module directory. Themes can override it
	 * by placing a file in <theme>/wp-maffia-game/<module-id>/<template>.php.
	 */
	public function view( string $template, array $vars = array() ): string {
		$file = locate_template( 'wp-maffia-game/' . $this->id . '/' . $template . '.php' );
		if ( ! $file ) {
			$file = $this->dir . 'views/' . $template . '.php';
		}
		if ( ! is_readable( $file ) ) {
			return '';
		}
		$vars = apply_filters( 'dfmg_view_vars', $vars, $this->id, $template );
		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		include $file;
		return (string) ob_get_clean();
	}

	protected function success( string $message ): void {
		Flash::success( $message );
	}

	protected function error( string $message ): void {
		Flash::error( $message );
	}

	protected function notice( string $message ): void {
		Flash::info( $message );
	}
}
