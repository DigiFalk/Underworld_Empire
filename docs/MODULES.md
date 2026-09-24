# Building modules for WP Mafia Game

Everything players see in the game is a **module**. The core only provides the foundation:
characters, timers, ranks, cities, items, properties, routing, administration and styling.
Adding modules extends the game without touching the plugin itself.

## Where do modules come from?

| Location | When to use |
| --- | --- |
| `wp-content/plugins/wp-mafia-game/modules/<id>/` | Bundled modules. Don't edit: changes are lost on update. |
| `wp-content/mafia-modules/<id>/` | **Your own modules.** Survives updates. A module with the same id as a bundled module replaces it. Change the folder with `define( 'DFMG_CUSTOM_MODULES_DIR', '/path' );` in `wp-config.php`. |
| Another plugin | `add_action( 'dfmg_register_modules', fn( $registry ) => $registry->add( __DIR__ . '/my-module/module.php' ) );` |

The folder name is the module **id** (e.g. `slot-machine`). It is also used in the URL
(`?mg=slot-machine`). Enable new modules under *Mafia Game → Modules*.

## The smallest module

`wp-content/mafia-modules/hello/module.php`:

```php
<?php
/**
 * Module Name: Hello
 * Description: Says hello.
 * Version: 1.0.0
 * Author: DigiFalk
 */

use DigiFalk\MafiaGame\Character;
use DigiFalk\MafiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

return new class() extends Module {

	public function menu( Character $c ): array {
		return array( array( 'label' => 'Hello', 'group' => 'general', 'order' => 90 ) );
	}

	public function render( Character $c, array $query ): string {
		return '<p>Hello ' . esc_html( $c->name ) . '!</p>'
			. $this->button( 'give', 'Give me $100' );
	}

	public function action_give( Character $c, array $input ): void {
		if ( $c->claim_cooldown( 'hello', 3600 ) ) {
			$c->add( 'money', 100 );
			$this->success( 'There you go!' );
		} else {
			$this->error( 'Once per hour.' );
		}
	}
};
```

The file must return an object that extends `DigiFalk\MafiaGame\Module\Module`.
A named class works too (use your own namespace to avoid collisions).

### Header fields

| Field | Meaning |
| --- | --- |
| `Module Name` | Required. Name in the admin and default page title. |
| `Description`, `Version`, `Author` | Shown in the admin. Bump `Version` when you change `schema()`: tables are then upgraded. |
| `Requires` | Comma separated ids of modules that must be enabled first (e.g. `garage, bank`). |
| `Default` | `no` = not enabled automatically on a fresh install. |
| `Required` | `yes` = can't be disabled. |

## Methods you can override

| Method | Purpose |
| --- | --- |
| `boot()` | Runs on every request while the module is enabled. Register your WordPress hooks here. |
| `schema(): array` | Own tables: `'short_name' => "column definitions"` (dbDelta format, two spaces after `PRIMARY KEY`). The table becomes `wp_dfmg_short_name`. |
| `seed()` | Starting data, runs once on first install. |
| `round_tables(): array` | Tables with player data that are emptied when a new round starts. |
| `reset_round()` | Extra work when a new round starts. |
| `menu( Character $c ): array` | Menu items: `label`, `group`, `order`, optional `timer` (live countdown), `badge`, `args`, `route`. |
| `title(): string` | Page title. |
| `render( Character $c, array $query ): string` | The page. `$query` holds the (sanitized) GET parameters. |
| `action_<name>( Character $c, array $input )` | Form action. Optionally return an array of query args for the redirect (e.g. `array( 'view' => 'home' )`, or `'mg' => 'other-module'`). |
| `allowed_in_jail()`, `allowed_in_hospital()` | `true` = reachable while in jail / hospital. |
| `admin_tables(): array` | Tables editable under *Game data* (see `includes/Admin/DataTable.php`). |
| `settings_fields(): array` | Fields on the settings page: `key => [ label, type, default, description ]`. Types: `text`, `int`, `checkbox`, `textarea`, `select` (+`options`), `datetime`. Prefix keys with your module name. |

Menu groups: `general`, `crime`, `city`, `casino`, `murder`, `family`, `money`, `premium`, `community`
(extend with the `dfmg_menu_groups` filter).

## Helpers inside a module

| Code | What |
| --- | --- |
| `$this->view( 'file', $vars )` | Renders `views/file.php`. Inside the template `$this` is the module. Themes can override it in `<theme>/wp-mafia-game/<id>/file.php`. |
| `$this->form( 'action', $hidden )` … `</form>` | Form posting to `action_action`, including a nonce. |
| `$this->button( 'action', 'Label', $hidden, $class )` | Form with a single button. |
| `$this->url( $args, $route )` | Link to a (different) game page. |
| `$this->setting( 'key' )` | Read a setting (falls back to the default from `settings_fields()`). |
| `$this->success()`, `$this->error()`, `$this->notice()` | Messages shown after the redirect. |
| `UI::cooldown()`, `UI::bar()`, `UI::pager()`, `UI::property()` | Ready-made components (`DigiFalk\MafiaGame\Frontend\UI`). |
| `Format::money()`, `Format::number()`, `Format::duration()`, `Format::countdown()`, `Format::parse_amount()` | Formatting and input parsing. |
| `DB::row()`, `DB::results()`, `DB::value()`, `DB::query()`, `DB::insert()`, `DB::update()` | Database. Write tables as `{short_name}`; placeholders as in `$wpdb->prepare()`. |

### The character (`DigiFalk\MafiaGame\Character`)

```php
$c->name; $c->money; $c->bank; $c->bullets; $c->exp; $c->points; $c->location_id;
$c->add( 'money', 500 );              // atomic increase (or decrease with a negative number)
$c->spend( 'money', 500 );            // atomic deduction, false when there isn't enough
$c->transfer_to( $other, 'bank', 1000 );
$c->claim_cooldown( 'my_timer', 60 ); // only starts the timer when it isn't running (safe against double clicks)
$c->timer_active( 'jail' ); $c->timer( 'jail' ); $c->set_timer( 'x', time() + 60 );
$c->jail( 120 );                       // 2 minutes in jail
$c->rank(); $c->rank_name(); $c->max_health(); $c->health_percent();
$c->attack_power(); $c->defense_power();
$c->notify( 'Text' );                  // notification
$c->log( 'my-module.action', true, $amount ); // statistics + dfmg_action hook
$c->link();                            // HTML link to the profile
Character::find( $id ); Character::find_by_name( 'name' ); Character::current();
```

Adding experience with `$c->add( 'exp', 5 )` automatically checks for promotions.

### Properties

Register your own business type and use `Property`:

```php
add_filter( 'dfmg_property_types', function ( $types ) {
	$types['nightclub'] = array( 'label' => 'Nightclub', 'price' => 2000000, 'setting_label' => 'Entry fee', 'setting_min' => 10 );
	return $types;
} );

$club = Property::get( 'nightclub', (int) $c->location_id );
echo UI::property( $c, $club, $this->id() ); // shows the owner or a "buy" button
$club->owner(); $club->price(); $club->add_profit( 100 );
```

Buying, managing (price, transfer, give up) and takeover after a murder are handled by the core and the *Properties* module.

### Items

Items are managed under *Game data → Items*. Effects are stored one per line as `effect=value`.
Add your own types, slots and effects with the filters `dfmg_item_types`, `dfmg_equip_slots`
and `dfmg_item_effects`:

```php
add_filter( 'dfmg_item_effects', function ( $effects ) {
	$effects['give_points'] = array(
		'label' => 'Gives premium points',
		'usage' => 'use',
		'apply' => function ( Character $c, $value ) { $c->add( 'points', (int) $value ); },
	);
	return $effects;
} );
```

## Hooks

### Actions

| Hook | Arguments |
| --- | --- |
| `dfmg_register_modules` | `Registry $registry` |
| `dfmg_modules_booted` | `Registry $registry` |
| `dfmg_character_created` | `Character $c` |
| `dfmg_character_killed` | `Character $victim, ?Character $killer` |
| `dfmg_character_jailed` | `Character $c, int $seconds` |
| `dfmg_rank_up` | `Character $c, array $rank` |
| `dfmg_action` | `Character $c, string $action, bool $success, int $amount, int $ref_id` (on every `$c->log()`) |
| `dfmg_after_action` | `Character $c, string $module, string $action` (after every form action) |
| `dfmg_timer_updated` | `Character $c, string $timer, int $expires, int $old` |
| `dfmg_notification` | `Character $c, string $text` |
| `dfmg_travelled` | `Character $c, int $city` |
| `dfmg_item_used` | `Character $c, array $item` |
| `dfmg_property_transferred` | `Property $p, int $new_owner` |
| `dfmg_message_sent` | `Character $from, Character $to` |
| `dfmg_family_dissolved` | `int $family_id` |
| `dfmg_hourly_tick` | – (hourly via WP-Cron) |
| `dfmg_before_new_round`, `dfmg_new_round` | – |
| `dfmg_module_enabled`, `dfmg_module_disabled` | `string $id` |
| `dfmg_login_page` | – (extra content on the login page) |
| `dfmg_admin_data_saved`, `dfmg_admin_data_deleted` | `string $table, $id, ...` |

### Filters

| Filter | Value / arguments |
| --- | --- |
| `dfmg_route` | `string $module_id, Character $c, Module $module` – redirect players (this is how jail and hospital work). |
| `dfmg_cooldown_seconds` | `int $seconds, string $timer, Character $c` |
| `dfmg_attack_power`, `dfmg_defense_power`, `dfmg_max_health` | `$value, Character $c` |
| `dfmg_can_attack` | `true\|WP_Error, Character $attacker, Character $target` |
| `dfmg_murder_damage` | `int $damage, Character $attacker, Character $target, int $bullets` |
| `dfmg_jail_bust_chance`, `dfmg_detective_chance` | chance in % |
| `dfmg_can_buy_item`, `dfmg_can_equip` | `true\|WP_Error` |
| `dfmg_module_data` | `array $rows, string $module, Character $c` – alter crimes, theft spots, destinations or market items. |
| `dfmg_menu_groups`, `dfmg_menu_items` | menu |
| `dfmg_header_stats` | stats in the header bar |
| `dfmg_overview_panels`, `dfmg_overview_timers` | extra blocks on the overview |
| `dfmg_profile_fields`, `dfmg_profile_actions` | profile page |
| `dfmg_property_types` | business types |
| `dfmg_item_types`, `dfmg_equip_slots`, `dfmg_item_effects` | items |
| `dfmg_family_permissions` | family permissions |
| `dfmg_membership_benefits` | benefits on the membership page |
| `dfmg_leaderboards`, `dfmg_statistics` | leaderboards and statistics |
| `dfmg_new_character_data`, `dfmg_blocked_names` | new characters |
| `dfmg_round_open` | `bool` |
| `dfmg_view_vars` | `array $vars, string $module, string $template` |
| `dfmg_admin_tables`, `dfmg_admin_save_data` | administration |

## Connecting premium points to a web shop

Points are a column on the character. For example after a WooCommerce payment:

```php
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	$c     = \DigiFalk\MafiaGame\Character::for_user( $order->get_user_id() );
	if ( $c ) {
		$c->add( 'points', 100 );
		$c->notify( 'Thank you! You received 100 points.' );
	}
} );
```

## Tips

* Always use `spend()` or a conditional `UPDATE ... WHERE x >= %d` when deducting, so nobody can go below zero by clicking fast.
* Use `claim_cooldown()` for actions with a cooldown.
* Escape all output (`esc_html`, `esc_attr`, `esc_url`); actions receive raw (unslashed) `$_POST` data.
* When in doubt, look at a bundled module: `crimes` (simple), `bullet-factory` (property + cron), `families` (large).
