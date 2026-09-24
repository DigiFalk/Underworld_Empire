<?php
/**
 * A player character. A WordPress user can own several characters over time
 * (after being murdered a new one is started), but only one alive at once.
 *
 * @package DigiFalk\UnderworldEmpire
 */

namespace DigiFalk\UnderworldEmpire;

defined( 'ABSPATH' ) || exit;

/**
 * @property-read int    $id
 * @property-read int    $user_id
 * @property-read string $name
 * @property-read int    $status
 * @property-read int    $money
 * @property-read int    $bank
 * @property-read int    $bullets
 * @property-read int    $exp
 * @property-read int    $points
 * @property-read int    $damage
 * @property-read int    $rank_id
 * @property-read int    $location_id
 * @property-read int    $shot_by
 * @property-read string $bio
 * @property-read int    $created_at
 * @property-read int    $last_active
 * @property-read int    $died_at
 */
final class Character {

	const ALIVE = 1;
	const DEAD  = 0;

	/** Numeric columns that may be changed with add() / spend(). */
	const COUNTERS = array( 'money', 'bank', 'bullets', 'exp', 'points', 'damage' );

	/** Columns that may be written with set(). */
	const WRITABLE = array( 'name', 'status', 'money', 'bank', 'bullets', 'exp', 'points', 'damage', 'rank_id', 'location_id', 'shot_by', 'bio', 'last_active', 'died_at' );

	/** @var Character[] */
	private static $cache = array();

	/** @var Character|null|false false = not resolved yet */
	private static $current = false;

	/** @var array */
	private $row;

	/** @var array|null */
	private $timers = null;

	private function __construct( array $row ) {
		$this->row = $row;
	}

	/* ------------------------------------------------------------------ */
	/* Lookup                                                               */
	/* ------------------------------------------------------------------ */

	public static function find( int $id ): ?self {
		if ( $id <= 0 ) {
			return null;
		}
		if ( ! isset( self::$cache[ $id ] ) ) {
			$row = DB::row( 'SELECT * FROM {characters} WHERE id = %d', $id );
			if ( ! $row ) {
				return null;
			}
			self::$cache[ $id ] = new self( $row );
		}
		return self::$cache[ $id ];
	}

	public static function find_by_name( string $name ): ?self {
		$id = (int) DB::value( 'SELECT id FROM {characters} WHERE name = %s', trim( $name ) );
		return $id ? self::find( $id ) : null;
	}

	/**
	 * The alive character of a user, or the most recent dead one.
	 */
	public static function for_user( int $user_id ): ?self {
		$id = (int) DB::value(
			'SELECT id FROM {characters} WHERE user_id = %d ORDER BY status DESC, id DESC LIMIT 1',
			$user_id
		);
		return $id ? self::find( $id ) : null;
	}

	/**
	 * Character of the currently logged in user.
	 */
	public static function current(): ?self {
		if ( false === self::$current ) {
			self::$current = is_user_logged_in() ? self::for_user( get_current_user_id() ) : null;
		}
		return self::$current;
	}

	public static function reset_cache(): void {
		self::$cache   = array();
		self::$current = false;
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function validate_name( string $name ) {
		$name = trim( $name );
		if ( ! preg_match( '/^[A-Za-z0-9_\-]{3,20}$/', $name ) ) {
			return new \WP_Error( 'name', __( 'A name consists of 3 to 20 letters, digits, - or _.', 'underworld-empire' ) );
		}
		if ( self::find_by_name( $name ) ) {
			return new \WP_Error( 'name', __( 'This name is already taken.', 'underworld-empire' ) );
		}
		$blocked = apply_filters( 'dfmg_blocked_names', array( 'admin', 'administrator', 'moderator', 'system', 'systeem' ) );
		if ( in_array( strtolower( $name ), $blocked, true ) ) {
			return new \WP_Error( 'name', __( 'This name is not allowed.', 'underworld-empire' ) );
		}
		return true;
	}

	/**
	 * Create a new character for a user.
	 *
	 * @return Character|\WP_Error
	 */
	public static function create( int $user_id, string $name ) {
		$valid = self::validate_name( $name );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$alive = (int) DB::value( 'SELECT COUNT(*) FROM {characters} WHERE user_id = %d AND status = %d', $user_id, self::ALIVE );
		if ( $alive ) {
			return new \WP_Error( 'alive', __( 'You already have a living character.', 'underworld-empire' ) );
		}

		// Premium points belong to the player, not the character: carry them over.
		$points = (int) DB::value( 'SELECT COALESCE(SUM(points), 0) FROM {characters} WHERE user_id = %d', $user_id );
		if ( $points ) {
			DB::query( 'UPDATE {characters} SET points = 0 WHERE user_id = %d', $user_id );
		}
		// Points saved when a new round started.
		$carried = (int) get_user_meta( $user_id, 'dfmg_carry_points', true );
		if ( $carried ) {
			$points += $carried;
			delete_user_meta( $user_id, 'dfmg_carry_points' );
		}

		$data = apply_filters(
			'dfmg_new_character_data',
			array(
				'user_id'     => $user_id,
				'name'        => trim( $name ),
				'status'      => self::ALIVE,
				'money'       => Settings::int( 'start_money', 250 ),
				'bank'        => 0,
				'bullets'     => Settings::int( 'start_bullets', 100 ),
				'exp'         => 0,
				'points'      => $points,
				'damage'      => 0,
				'rank_id'     => (int) Ranks::first()['id'],
				'location_id' => Locations::first_id(),
				'bio'         => '',
				'created_at'  => time(),
				'last_active' => time(),
			),
			$user_id
		);

		$id = DB::insert( 'characters', $data );
		if ( ! $id ) {
			return new \WP_Error( 'db', __( 'Character could not be created.', 'underworld-empire' ) );
		}
		self::$current = false;
		$character     = self::find( $id );
		do_action( 'dfmg_character_created', $character );
		return $character;
	}

	/* ------------------------------------------------------------------ */
	/* Data access                                                          */
	/* ------------------------------------------------------------------ */

	public function __get( $key ) {
		if ( ! array_key_exists( $key, $this->row ) ) {
			return null;
		}
		$value = $this->row[ $key ];
		return is_numeric( $value ) && ! in_array( $key, array( 'name', 'bio' ), true ) ? (int) $value : $value;
	}

	public function __isset( $key ) {
		return isset( $this->row[ $key ] );
	}

	public function to_array(): array {
		return $this->row;
	}

	public function id(): int {
		return (int) $this->row['id'];
	}

	public function is_alive(): bool {
		return (int) $this->row['status'] === self::ALIVE;
	}

	public function is_current(): bool {
		return is_user_logged_in() && (int) $this->row['user_id'] === get_current_user_id();
	}

	public function refresh(): void {
		$row = DB::row( 'SELECT * FROM {characters} WHERE id = %d', $this->id() );
		if ( $row ) {
			$this->row = $row;
		}
		$this->timers = null;
	}

	/**
	 * @param mixed $value
	 */
	public function set( string $field, $value ): void {
		if ( ! in_array( $field, self::WRITABLE, true ) ) {
			throw new \InvalidArgumentException( 'Field not writable: ' . $field );
		}
		DB::update( 'characters', array( $field => $value ), array( 'id' => $this->id() ) );
		$this->row[ $field ] = $value;
		if ( 'exp' === $field ) {
			$this->check_rank();
		}
	}

	/**
	 * Atomically add (or with a negative amount, remove) from a counter.
	 */
	public function add( string $field, int $amount ): void {
		if ( ! in_array( $field, self::COUNTERS, true ) || 0 === $amount ) {
			return;
		}
		DB::query( "UPDATE {characters} SET `$field` = `$field` + %d WHERE id = %d", $amount, $this->id() );
		$this->row[ $field ] = (int) DB::value( "SELECT `$field` FROM {characters} WHERE id = %d", $this->id() );
		if ( 'exp' === $field && $amount > 0 ) {
			$this->check_rank();
		}
	}

	/**
	 * Atomically remove an amount, only when enough is available.
	 */
	public function spend( string $field, int $amount ): bool {
		if ( ! in_array( $field, self::COUNTERS, true ) || $amount < 0 ) {
			return false;
		}
		if ( 0 === $amount ) {
			return true;
		}
		$done = DB::query(
			"UPDATE {characters} SET `$field` = `$field` - %d WHERE id = %d AND `$field` >= %d",
			$amount,
			$this->id(),
			$amount
		);
		$this->row[ $field ] = (int) DB::value( "SELECT `$field` FROM {characters} WHERE id = %d", $this->id() );
		return (bool) $done;
	}

	/**
	 * Move money/bullets from this character to another in one step.
	 */
	public function transfer_to( Character $other, string $field, int $amount ): bool {
		if ( ! $this->spend( $field, $amount ) ) {
			return false;
		}
		$other->add( $field, $amount );
		return true;
	}

	/* ------------------------------------------------------------------ */
	/* Timers                                                               */
	/* ------------------------------------------------------------------ */

	private function load_timers(): void {
		if ( null !== $this->timers ) {
			return;
		}
		$this->timers = array();
		foreach ( DB::results( 'SELECT name, expires_at FROM {timers} WHERE character_id = %d', $this->id() ) as $row ) {
			$this->timers[ $row['name'] ] = (int) $row['expires_at'];
		}
	}

	/**
	 * Unix time a timer expires (0 when never set).
	 */
	public function timer( string $name ): int {
		$this->load_timers();
		return $this->timers[ $name ] ?? 0;
	}

	public function timer_active( string $name ): bool {
		return $this->timer( $name ) > time();
	}

	public function timer_left( string $name ): int {
		return max( 0, $this->timer( $name ) - time() );
	}

	public function set_timer( string $name, int $expires_at ): void {
		DB::query(
			'INSERT INTO {timers} (character_id, name, expires_at) VALUES (%d, %s, %d) ON DUPLICATE KEY UPDATE expires_at = %d',
			$this->id(),
			$name,
			$expires_at,
			$expires_at
		);
		$this->load_timers();
		$old                   = $this->timers[ $name ] ?? 0;
		$this->timers[ $name ] = $expires_at;
		do_action( 'dfmg_timer_updated', $this, $name, $expires_at, $old );
	}

	public function clear_timer( string $name ): void {
		$this->set_timer( $name, 0 );
	}

	/**
	 * Cooldown length after filters (for example premium membership reductions).
	 */
	public function cooldown_seconds( string $name, int $seconds ): int {
		return max( 0, (int) apply_filters( 'dfmg_cooldown_seconds', $seconds, $name, $this ) );
	}

	/**
	 * Start a cooldown only when it is not running. Atomic, so double submits
	 * cannot perform an action twice.
	 *
	 * @return bool True when the cooldown was claimed.
	 */
	public function claim_cooldown( string $name, int $seconds ): bool {
		$now     = time();
		$expires = $now + $this->cooldown_seconds( $name, $seconds );
		$result  = DB::query(
			'INSERT INTO {timers} (character_id, name, expires_at) VALUES (%d, %s, %d)
			 ON DUPLICATE KEY UPDATE expires_at = IF(expires_at <= %d, %d, expires_at)',
			$this->id(),
			$name,
			$expires,
			$now,
			$expires
		);
		$this->timers = null;
		if ( $result ) {
			do_action( 'dfmg_timer_updated', $this, $name, $expires, 0 );
		}
		return (bool) $result;
	}

	public function is_jailed(): bool {
		return $this->timer_active( 'jail' );
	}

	public function is_in_supermax(): bool {
		return $this->is_jailed() && $this->timer( 'supermax' ) >= $this->timer( 'jail' );
	}

	public function is_hospitalized(): bool {
		return $this->timer_active( 'hospital' );
	}

	/**
	 * Put the character in jail for $seconds (from now).
	 */
	public function jail( int $seconds ): void {
		$this->set_timer( 'jail', time() + max( 1, $seconds ) );
		do_action( 'dfmg_character_jailed', $this, $seconds );
	}

	/* ------------------------------------------------------------------ */
	/* Rank, health and combat                                              */
	/* ------------------------------------------------------------------ */

	public function rank(): array {
		return Ranks::get( (int) $this->row['rank_id'] );
	}

	public function rank_name(): string {
		return $this->rank()['name'];
	}

	/**
	 * Percentage of experience towards the next rank.
	 */
	public function rank_progress(): float {
		$rank = $this->rank();
		$next = Ranks::next( (int) $rank['id'] );
		if ( ! $next ) {
			return 100.0;
		}
		$span = max( 1, (int) $next['exp_required'] - (int) $rank['exp_required'] );
		$done = (int) $this->row['exp'] - (int) $rank['exp_required'];
		return max( 0, min( 100, round( $done / $span * 100, 1 ) ) );
	}

	/**
	 * Promote the character while it has enough experience.
	 */
	public function check_rank(): void {
		$guard = 0;
		while ( $guard++ < 50 ) {
			$next = Ranks::next( (int) $this->row['rank_id'] );
			if ( ! $next || (int) $this->row['exp'] < (int) $next['exp_required'] ) {
				return;
			}
			if ( (int) $next['max_players'] > 0 ) {
				$taken = (int) DB::value(
					'SELECT COUNT(*) FROM {characters} WHERE rank_id = %d AND status = %d',
					$next['id'],
					self::ALIVE
				);
				if ( $taken >= (int) $next['max_players'] ) {
					return;
				}
			}
			DB::update( 'characters', array( 'rank_id' => $next['id'] ), array( 'id' => $this->id() ) );
			$this->row['rank_id'] = $next['id'];
			$this->add( 'money', (int) $next['cash_reward'] );
			$this->add( 'bullets', (int) $next['bullet_reward'] );

			$rewards = array();
			if ( (int) $next['cash_reward'] ) {
				$rewards[] = Format::money( $next['cash_reward'] );
			}
			if ( (int) $next['bullet_reward'] ) {
				/* translators: %s: number of bullets */
				$rewards[] = sprintf( __( '%s bullets', 'underworld-empire' ), Format::number( $next['bullet_reward'] ) );
			}
			$message = sprintf(
				/* translators: %s: rank name */
				__( 'Congratulations! You have been promoted to %s.', 'underworld-empire' ),
				$next['name']
			);
			if ( $rewards ) {
				/* translators: %s: list of rewards */
				$message .= ' ' . sprintf( __( 'Reward: %s.', 'underworld-empire' ), implode( ', ', $rewards ) );
			}
			$this->notify( $message );
			$this->log( 'rank.up', true, (int) $next['id'] );
			do_action( 'dfmg_rank_up', $this, $next );
		}
	}

	public function max_health(): int {
		return max( 1, (int) apply_filters( 'dfmg_max_health', (int) $this->rank()['max_health'], $this ) );
	}

	public function health(): int {
		return max( 0, $this->max_health() - (int) $this->row['damage'] );
	}

	public function health_percent(): float {
		return round( $this->health() / $this->max_health() * 100, 1 );
	}

	/**
	 * Attack power, 100 = base. Modified by equipment and modules.
	 */
	public function attack_power(): float {
		return max( 1.0, (float) apply_filters( 'dfmg_attack_power', 100.0, $this ) );
	}

	public function defense_power(): float {
		return max( 1.0, (float) apply_filters( 'dfmg_defense_power', 100.0, $this ) );
	}

	/**
	 * Mark as murdered.
	 */
	public function kill( ?Character $killer = null ): void {
		DB::update(
			'characters',
			array(
				'status'  => self::DEAD,
				'shot_by' => $killer ? $killer->id() : 0,
				'died_at' => time(),
			),
			array( 'id' => $this->id() )
		);
		$this->row['status']  = self::DEAD;
		$this->row['shot_by'] = $killer ? $killer->id() : 0;
		$this->row['died_at'] = time();
		do_action( 'dfmg_character_killed', $this, $killer );
	}

	/* ------------------------------------------------------------------ */
	/* Misc                                                                 */
	/* ------------------------------------------------------------------ */

	public function location(): array {
		return Locations::get( (int) $this->row['location_id'] ) ?? array(
			'id'   => 0,
			'name' => __( 'Unknown', 'underworld-empire' ),
		);
	}

	public function location_name(): string {
		return $this->location()['name'];
	}

	public function wealth(): int {
		return (int) $this->row['money'] + (int) $this->row['bank'];
	}

	public function wealth_title(): string {
		return Ranks::wealth_title( $this->wealth() );
	}

	public function is_online(): bool {
		return (int) $this->row['last_active'] > time() - 60 * Settings::int( 'online_minutes', 15 );
	}

	public function touch(): void {
		if ( (int) $this->row['last_active'] < time() - 30 ) {
			$this->set( 'last_active', time() );
		}
	}

	public function notify( string $message ): void {
		DB::insert(
			'notifications',
			array(
				'character_id' => $this->id(),
				'message'      => $message,
				'created_at'   => time(),
				'is_read'      => 0,
			)
		);
		do_action( 'dfmg_notification', $this, $message );
	}

	/**
	 * Record an action for statistics and fire the dfmg_action hook.
	 */
	public function log( string $action, bool $success = true, int $amount = 0, int $ref_id = 0 ): void {
		DB::insert(
			'activity',
			array(
				'character_id' => $this->id(),
				'action'       => $action,
				'ref_id'       => $ref_id,
				'success'      => $success ? 1 : 0,
				'amount'       => $amount,
				'created_at'   => time(),
			)
		);
		do_action( 'dfmg_action', $this, $action, $success, $amount, $ref_id );
	}

	public function profile_url(): string {
		return Frontend\Game::url( 'profile', array( 'name' => $this->row['name'] ) );
	}

	/**
	 * Linked character name.
	 */
	public function link(): string {
		$class = $this->is_alive() ? 'dfmg-player' : 'dfmg-player dfmg-dead';
		return sprintf(
			'<a class="%1$s" href="%2$s">%3$s</a>',
			esc_attr( $class ),
			esc_url( $this->profile_url() ),
			esc_html( $this->row['name'] )
		);
	}

	public static function link_by_id( int $id ): string {
		$c = self::find( $id );
		return $c ? $c->link() : '<em>' . esc_html__( 'Unknown', 'underworld-empire' ) . '</em>';
	}
}
