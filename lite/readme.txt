=== Mafia PBBG Engine ===
Contributors: digifalk
Tags: game, browser game, mafia, rpg, multiplayer
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete mafia browser game (PBBG) for WordPress. Players commit crimes, steal cars, travel, bank and climb from Street Rat to Godfather.

== Description ==

Mafia PBBG Engine turns your WordPress site into a multiplayer mafia browser game. Players log in with their WordPress account, pick a gangster name and work their way up from *Street Rat* to *Godfather*.

The game runs on a shortcode page that the plugin creates for you, works with any theme and looks good on phones.

= What players can do =

* **Crimes**: crimes with a success chance that grows the more a player does them.
* **Car theft and garage**: steal cars at spots with different odds, then sell, repair, ship or crush them.
* **Jail**: jail time, breakouts, bail and solitary confinement.
* **Hospital**: heal for money and time.
* **Travel**: fly between cities.
* **Bank**: deposit (with a laundering fee), withdraw and transfer money.
* **Inventory**: equip, use and sell items.
* **Messages and notifications**: private messages and a feed of everything that happens to your character.
* **Profiles, players and leaderboards**: public profiles, who's online, search and top 25 lists.
* **Statistics and news**: numbers about the game world and game news.

= What you can do as the game owner =

* Switch every game feature (module) on or off.
* Edit all game data: ranks, wealth titles, cities, crimes, cars, items and more.
* Start a new round: player data is wiped, game data is kept.
* Arrange the game screen with the **Game layout** in the Customizer: drag money, timers, the game menu and more into the header, sidebar or footer.
* Follow the colours and font of your theme, or use the built-in dark look. Players can switch between light and dark with one click.
* Build your own modules: a module is a folder with a `module.php`. Your own modules survive plugin updates.

= Mafia PBBG Engine Extended (free) =

The game in this plugin is complete on its own. The free add-on **Mafia PBBG Engine Extended**, available on [digifalk.com](https://digifalk.com/en/product/mafia-pbbg-engine-extended/), adds families, murders, detectives, bounties, the bullet factory, the black market, blackjack, police chases, properties, a forum and the matching Mafia PBBG Engine theme. Extended is also needed for premium modules from DigiFalk. Extended is not hosted on WordPress.org and has its own license.

= Source code =

The source code, documentation for module developers and the development history are on [GitHub](https://github.com/DigiFalk/Mafia_PBBG_engine).

== Installation ==

1. Install and activate **Mafia PBBG Engine** from *Plugins → Add New*.
2. On activation the plugin creates its tables, loads starting data and creates a page **Mafia PBBG Engine** with the shortcode `[mafia_pbbg_engine]`.
3. Enable *Settings → General → Anyone can register* if players may create their own account.
4. Manage the game under the **Mafia PBBG Engine** menu.

Requirements: WordPress 6.0 or newer, PHP 7.4 or newer, MySQL 5.7+ or MariaDB 10.3+.

== Frequently Asked Questions ==

= Do players need a WordPress account? =

Yes. Players log in with a normal WordPress account. Turn on *Anyone can register* under *Settings → General* so they can sign up themselves.

= Can I add my own game features? =

Yes. Put a folder with a `module.php` in `wp-content/mafia-pbbg-modules/` and switch it on under *Mafia PBBG Engine → Modules*. See the module documentation on GitHub.

= Does the plugin show a link to its maker? =

Only if you want it to. On the dashboard of the plugin you can choose to show a small line "Mafia PBBG Engine by DigiFalk" at the bottom of game pages. It is off until you choose yes, and you can change it under *Settings*.

= Does the plugin contact other servers? =

No. This plugin does not send data to external services.

= What happens to player data when I delete the plugin? =

Nothing, unless you turn on *Delete all game data when the plugin is deleted* under *Settings*.

== Changelog ==

= 2.1.0 =
* With a theme that switches light and dark for the whole site, the game follows that switch.
* New for developers: the filter dfmg_profile_own_actions adds buttons to the player's own profile.

= 2.0.2 =
* Links point to the new GitHub repository.

= 2.0.1 =
* A module that can't be loaded no longer breaks the site: it is skipped and the admin sees why.

= 2.0.0 =
* Mafia PBBG Engine carries its new name everywhere: folders, shortcodes ([mafia_pbbg_engine]), the folder for your own modules (wp-content/mafia-pbbg-modules) and more.

= 1.14.0 =
* New name: Mafia PBBG Engine (was Underworld Empire).

= 1.13.3 =
* The link to Mafia PBBG Engine Extended opens the English product page.

= 1.13.2 =
* New: a light/dark switch in the game. Players choose between the colours of your theme and the built-in dark look.

= 1.13.1 =
* Fixed: rank names, labels and icons are readable with themes that have a very light accent colour.

= 1.13.0 =
* Mafia PBBG Engine is now available under the GPL (version 2 or later).
* New: on the dashboard you choose whether game pages show "Mafia PBBG Engine by DigiFalk". It is off until you choose yes.

= 1.12.0 =
* Families, murders, detectives, bounties, the bullet factory, black market, blackjack, police chases, properties, the forum and the theme moved to the free add-on Mafia PBBG Engine Extended.

The full changelog is on [GitHub](https://github.com/DigiFalk/Mafia_PBBG_engine/blob/main/CHANGELOG.md).
