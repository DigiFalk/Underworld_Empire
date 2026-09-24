# Modules bouwen voor WP Maffia Game

Alles wat spelers in het spel zien is een **module**. De kern levert alleen de basis:
personages, timers, rangen, steden, items, bezittingen, routing, beheer en opmaak.
Door modules toe te voegen breid je het spel uit zonder de plugin zelf aan te passen.

## Waar komen modules vandaan?

| Locatie | Wanneer gebruiken |
| --- | --- |
| `wp-content/plugins/wp-maffia-game/modules/<id>/` | Meegeleverde modules. Niet aanpassen: gaat verloren bij een update. |
| `wp-content/maffia-modules/<id>/` | **Jouw eigen modules.** Blijft bewaard bij updates. Een module met dezelfde id als een meegeleverde module vervangt die. De map is aan te passen met `define( 'DFMG_CUSTOM_MODULES_DIR', '/pad' );` in `wp-config.php`. |
| Een andere plugin | `add_action( 'dfmg_register_modules', fn( $registry ) => $registry->add( __DIR__ . '/mijn-module/module.php' ) );` |

De naam van de map is het **id** van de module (bijv. `slot-machine`). Dat id wordt
ook gebruikt in de URL (`?mg=slot-machine`). Nieuwe modules zet je aan onder
*Maffia Game → Modules*.

## De kleinste module

`wp-content/maffia-modules/hallo/module.php`:

```php
<?php
/**
 * Module Name: Hallo
 * Description: Zegt hallo.
 * Version: 1.0.0
 * Author: DigiFalk
 */

use DigiFalk\MaffiaGame\Character;
use DigiFalk\MaffiaGame\Module\Module;

defined( 'ABSPATH' ) || exit;

return new class() extends Module {

	public function menu( Character $c ): array {
		return array( array( 'label' => 'Hallo', 'group' => 'general', 'order' => 90 ) );
	}

	public function render( Character $c, array $query ): string {
		return '<p>Hallo ' . esc_html( $c->name ) . '!</p>'
			. $this->button( 'geef', 'Geef me € 100' );
	}

	public function action_geef( Character $c, array $input ): void {
		if ( $c->claim_cooldown( 'hallo', 3600 ) ) {
			$c->add( 'money', 100 );
			$this->success( 'Alsjeblieft!' );
		} else {
			$this->error( 'Eén keer per uur.' );
		}
	}
};
```

Het bestand moet een object teruggeven dat `DigiFalk\MaffiaGame\Module\Module` uitbreidt.
Een benoemde klasse mag ook (gebruik dan een eigen namespace om botsingen te voorkomen).

### Header-velden

| Veld | Betekenis |
| --- | --- |
| `Module Name` | Verplicht. Naam in het beheer en standaard paginatitel. |
| `Description`, `Version`, `Author` | Informatie in het beheer. Verhoog `Version` als je `schema()` wijzigt: tabellen worden dan bijgewerkt. |
| `Requires` | Kommagescheiden ids van modules die eerst aan moeten staan (bijv. `garage, bank`). |
| `Default` | `no` = bij een verse installatie niet automatisch aan. |
| `Required` | `yes` = kan niet uitgeschakeld worden. |

## Methodes die je kunt overschrijven

| Methode | Doel |
| --- | --- |
| `boot()` | Draait bij elke request als de module aan staat. Registreer hier je WordPress-hooks. |
| `schema(): array` | Eigen tabellen: `'korte_naam' => "kolomdefinities"` (dbDelta-formaat, twee spaties na `PRIMARY KEY`). Tabel wordt `wp_dfmg_korte_naam`. |
| `seed()` | Startdata, draait één keer bij de eerste installatie. |
| `round_tables(): array` | Tabellen met spelersdata die geleegd worden bij een nieuwe ronde. |
| `reset_round()` | Extra werk bij een nieuwe ronde. |
| `menu( Character $c ): array` | Menu-items: `label`, `group`, `order`, optioneel `timer` (live aftellen), `badge`, `args`, `route`. |
| `title(): string` | Paginatitel. |
| `render( Character $c, array $query ): string` | De pagina. `$query` bevat de (opgeschoonde) GET-parameters. |
| `action_<naam>( Character $c, array $input )` | Formulieractie. Geef optioneel een array met query-args terug voor de redirect (bijv. `array( 'view' => 'home' )`, of `'mg' => 'andere-module'`). |
| `allowed_in_jail()`, `allowed_in_hospital()` | `true` = bereikbaar in de cel / het ziekenhuis. |
| `admin_tables(): array` | Tabellen die in *Spelgegevens* bewerkt kunnen worden (zie `includes/Admin/DataTable.php`). |
| `settings_fields(): array` | Instellingen op de instellingenpagina: `key => [ label, type, default, description ]`. Types: `text`, `int`, `checkbox`, `textarea`, `select` (+`options`), `datetime`. Gebruik unieke keys met je module als prefix. |

Menugroepen: `general`, `crime`, `city`, `casino`, `murder`, `family`, `money`, `premium`, `community`
(uit te breiden met de filter `dfmg_menu_groups`).

## Hulpmiddelen in een module

| Code | Wat |
| --- | --- |
| `$this->view( 'bestand', $vars )` | Rendert `views/bestand.php`. Binnen het template is `$this` de module. Thema's kunnen het overschrijven in `<thema>/wp-maffia-game/<id>/bestand.php`. |
| `$this->form( 'actie', $hidden )` … `</form>` | Formulier naar `action_actie` met nonce. |
| `$this->button( 'actie', 'Label', $hidden, $class )` | Formulier met één knop. |
| `$this->url( $args, $route )` | Link naar (een andere) spelpagina. |
| `$this->setting( 'key' )` | Instelling lezen (valt terug op de default uit `settings_fields()`). |
| `$this->success()`, `$this->error()`, `$this->notice()` | Meldingen na de redirect. |
| `UI::cooldown()`, `UI::bar()`, `UI::pager()`, `UI::property()` | Kant-en-klare onderdelen (`DigiFalk\MaffiaGame\Frontend\UI`). |
| `Format::money()`, `Format::number()`, `Format::duration()`, `Format::countdown()`, `Format::parse_amount()` | Opmaak en invoer. |
| `DB::row()`, `DB::results()`, `DB::value()`, `DB::query()`, `DB::insert()`, `DB::update()` | Database. Schrijf tabellen als `{korte_naam}`; placeholders zoals bij `$wpdb->prepare()`. |

### Het personage (`DigiFalk\MaffiaGame\Character`)

```php
$c->name; $c->money; $c->bank; $c->bullets; $c->exp; $c->points; $c->location_id;
$c->add( 'money', 500 );              // atomair ophogen (of verlagen met een negatief getal)
$c->spend( 'money', 500 );            // atomair afschrijven, false als er te weinig is
$c->transfer_to( $ander, 'bank', 1000 );
$c->claim_cooldown( 'mijn_timer', 60 ); // start timer alleen als hij niet loopt (veilig tegen dubbelklikken)
$c->timer_active( 'jail' ); $c->timer( 'jail' ); $c->set_timer( 'x', time() + 60 );
$c->jail( 120 );                       // 2 minuten de cel in
$c->rank(); $c->rank_name(); $c->max_health(); $c->health_percent();
$c->attack_power(); $c->defense_power();
$c->notify( 'Tekst' );                 // melding
$c->log( 'mijn-module.actie', true, $bedrag ); // statistiek + hook dfmg_action
$c->link();                            // HTML-link naar het profiel
Character::find( $id ); Character::find_by_name( 'naam' ); Character::current();
```

Ervaring toevoegen met `$c->add( 'exp', 5 )` controleert automatisch of het personage promoveert.

### Bezittingen

Registreer een eigen bedrijfstype en gebruik `Property`:

```php
add_filter( 'dfmg_property_types', function ( $types ) {
	$types['nachtclub'] = array( 'label' => 'Nachtclub', 'price' => 2000000, 'setting_label' => 'Entreeprijs', 'setting_min' => 10 );
	return $types;
} );

$club = Property::get( 'nachtclub', (int) $c->location_id );
echo UI::property( $c, $club, $this->id() ); // eigenaar tonen of "kopen"-knop
$club->owner(); $club->price(); $club->add_profit( 100 );
```

Kopen, beheren (prijs, overdragen, opgeven) en overname na een moord regelt de kern / module *Bezittingen*.

### Items

Items beheer je in *Spelgegevens → Items*. Effecten staan per regel als `effect=waarde`.
Eigen types, vakken en effecten voeg je toe met de filters `dfmg_item_types`, `dfmg_equip_slots`
en `dfmg_item_effects`:

```php
add_filter( 'dfmg_item_effects', function ( $effects ) {
	$effects['give_points'] = array(
		'label' => 'Geeft premium punten',
		'usage' => 'use',
		'apply' => function ( Character $c, $value ) { $c->add( 'points', (int) $value ); },
	);
	return $effects;
} );
```

## Hooks

### Acties

| Hook | Argumenten |
| --- | --- |
| `dfmg_register_modules` | `Registry $registry` |
| `dfmg_modules_booted` | `Registry $registry` |
| `dfmg_character_created` | `Character $c` |
| `dfmg_character_killed` | `Character $slachtoffer, ?Character $dader` |
| `dfmg_character_jailed` | `Character $c, int $seconden` |
| `dfmg_rank_up` | `Character $c, array $rang` |
| `dfmg_action` | `Character $c, string $actie, bool $gelukt, int $bedrag, int $ref_id` (bij elke `$c->log()`) |
| `dfmg_after_action` | `Character $c, string $module, string $actie` (na elke formulieractie) |
| `dfmg_timer_updated` | `Character $c, string $timer, int $verloopt, int $oud` |
| `dfmg_notification` | `Character $c, string $tekst` |
| `dfmg_travelled` | `Character $c, int $stad` |
| `dfmg_item_used` | `Character $c, array $item` |
| `dfmg_property_transferred` | `Property $p, int $nieuwe_eigenaar` |
| `dfmg_message_sent` | `Character $van, Character $aan` |
| `dfmg_family_dissolved` | `int $familie_id` |
| `dfmg_hourly_tick` | – (elk uur via WP-Cron) |
| `dfmg_before_new_round`, `dfmg_new_round` | – |
| `dfmg_module_enabled`, `dfmg_module_disabled` | `string $id` |
| `dfmg_login_page` | – (extra inhoud op de inlogpagina) |
| `dfmg_admin_data_saved`, `dfmg_admin_data_deleted` | `string $tabel, $id, ...` |

### Filters

| Filter | Waarde / argumenten |
| --- | --- |
| `dfmg_route` | `string $module_id, Character $c, Module $module` – stuur spelers om (zo werken cel en ziekenhuis). |
| `dfmg_cooldown_seconds` | `int $seconden, string $timer, Character $c` |
| `dfmg_attack_power`, `dfmg_defense_power`, `dfmg_max_health` | `$waarde, Character $c` |
| `dfmg_can_attack` | `true\|WP_Error, Character $aanvaller, Character $doelwit` |
| `dfmg_murder_damage` | `int $schade, Character $aanvaller, Character $doelwit, int $kogels` |
| `dfmg_jail_bust_chance`, `dfmg_detective_chance` | kans in % |
| `dfmg_can_buy_item`, `dfmg_can_equip` | `true\|WP_Error` |
| `dfmg_module_data` | `array $rijen, string $module, Character $c` – pas misdaden, steelplekken, bestemmingen of marktitems aan. |
| `dfmg_menu_groups`, `dfmg_menu_items` | menu |
| `dfmg_header_stats` | statistieken in de kopbalk |
| `dfmg_overview_panels`, `dfmg_overview_timers` | extra blokken op het overzicht |
| `dfmg_profile_fields`, `dfmg_profile_actions` | profielpagina |
| `dfmg_property_types` | bedrijfstypes |
| `dfmg_item_types`, `dfmg_equip_slots`, `dfmg_item_effects` | items |
| `dfmg_family_permissions` | rechten binnen families |
| `dfmg_membership_benefits` | voordelen op de lidmaatschapspagina |
| `dfmg_leaderboards`, `dfmg_statistics` | ranglijsten en statistieken |
| `dfmg_new_character_data`, `dfmg_blocked_names` | nieuwe personages |
| `dfmg_round_open` | `bool` |
| `dfmg_view_vars` | `array $vars, string $module, string $template` |
| `dfmg_admin_tables`, `dfmg_admin_save_data` | beheer |

## Premium punten koppelen aan een webshop

Punten zijn een kolom op het personage. Bijvoorbeeld na een WooCommerce-betaling:

```php
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	$c     = \DigiFalk\MaffiaGame\Character::for_user( $order->get_user_id() );
	if ( $c ) {
		$c->add( 'points', 100 );
		$c->notify( 'Bedankt! Je hebt 100 punten ontvangen.' );
	}
} );
```

## Tips

* Gebruik altijd `spend()` of een voorwaardelijke `UPDATE ... WHERE x >= %d` bij afschrijven; zo kan niemand onder nul komen door snel te klikken.
* Gebruik `claim_cooldown()` voor acties met een wachttijd.
* Escape alle uitvoer (`esc_html`, `esc_attr`, `esc_url`); acties krijgen ruwe (unslashed) `$_POST`-data binnen.
* Kijk bij twijfel naar een meegeleverde module: `crimes` (eenvoudig), `bullet-factory` (bezit + cron), `families` (groot).
