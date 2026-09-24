# Changelog

All notable changes to Underworld Empire. Each `## x.y.z` section is used as the notes of the
GitHub release for that version.

## 1.2.1

- Every module with settings or game data now has a **Configure** button on the Modules
  screen. It opens one page with all settings and data tables of that module.
- The general *Settings* and *Game data* screens now only show core items.
- The repository moved to `DigiFalk/Underworld_Empire`; updates and links point there.

## 1.2.0

- New name: **Underworld Empire**. Plugin file `underworld-empire.php`, text domain
  `underworld-empire`, shortcode `[underworld_empire]`, release asset `underworld-empire.zip`,
  custom modules folder `wp-content/underworld-modules/`, theme override folder
  `underworld-empire/` and PHP namespace `DigiFalk\UnderworldEmpire`.
- Releases are now published from the `main` branch.
- Game data and settings are kept (database tables and options are unchanged).
- Upgrading from 1.1.x: because the main plugin file was renamed, WordPress deactivates the
  plugin after the update. Activate **Underworld Empire** again on the Plugins screen.

## 1.1.1

- Updates now also appear when the server can't reach WordPress.org (WordPress only asks
  the GitHub updater after a successful WordPress.org check, so a fallback was added).

## 1.1.0

- Whole plugin translated to English: game, admin, starting data and documentation.
- Renamed internally from "maffia" to "mafia" (plugin file, text domain, shortcode,
  custom modules folder, theme override folder and PHP namespace).
- Automatic updates: the plugin checks the GitHub releases and updates through the
  standard WordPress plugin updater, with a *Check for updates* link on the Plugins screen.
- Every version is published as a GitHub release with a ready-to-install zip.

## 1.0.0

- First version: core engine, module system, admin screens and 27 game modules.
