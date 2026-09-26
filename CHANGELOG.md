# Changelog

All notable changes to Underworld Empire. Each `## x.y.z` section is used as the notes of the
GitHub release for that version.

## 1.10.5

- Theme 3.4.0: the WooCommerce checkout, cart and My Account now use the colours, fields
  and buttons of the theme, in dark and light mode. This covers the block checkout (fields,
  country selector, payment methods, order summary, totals) and the classic pages (orders
  table, account forms, password fields, notices).

## 1.10.4

- The public signing key of the DigiFalk store is now built in: every premium module
  download from digifalk.com must be signed by the store, without anything in
  `wp-config.php`. `DFMG_STORE_PUBLIC_KEY` still overrides it (for another store or after a
  key rotation).

## 1.10.3

- Premium modules: when the download failed right after activating a license, **Download
  again** now also switches the module on (before, it only installed it).
- License keys are saved and shown in capitals, like in the purchase email, however they
  were typed.
- Tested end to end against the DigiFalk Licenses store plugin: catalogue, activation,
  signed download, updates, activation limit and deactivation.

## 1.10.2

- Fixed: the picture next to your name in the game header (and in the theme's player
  account element and the admin top players) was not the same as on your profile. Without
  an uploaded avatar the header showed your first letter while the profile showed your
  Gravatar. Everywhere now uses the same rule: uploaded avatar, otherwise the WordPress
  avatar, and the first letter only when avatars are switched off in WordPress.
- New helper `dfmg_player_avatar_url( $user_id, $size )`.

## 1.10.1

- Fixed: clicking a player name (Players, leaderboards, messages, forum, …) showed a
  WordPress "page not found". Profile links used `?name=`, a query var reserved by
  WordPress; they now use `?player=`.
- Page numbers in the forum, messages and notifications now use `?pg=` instead of the
  WordPress `paged` var, so the page title no longer says "Page 2".
- Developers: `Game::url()` warns (with `WP_DEBUG`) when a link uses a WordPress query var.

## 1.10.0

- **Premium Membership** and **premium points** are no longer part of the free plugin; they
  will return as a premium module in the DigiFalk store. Removed: the Membership module, the
  *Points* game element and header stat, the *Name of premium points* setting and the Points
  field on players. Existing point balances stay in the database for that premium module;
  saved game layouts simply drop the Points element.
- The core no longer carries points over to a new character or a new round (the premium
  module will take care of that through `dfmg_new_character_data` and
  `dfmg_before_new_round`).

## 1.9.0

- **Premium modules**: a new *Premium modules* section on the Modules screen shows modules
  from the DigiFalk store as locked cards with price and a **Buy** button. Paste the license
  key from your email and the module is activated for your site, downloaded, checked
  (SHA-256, plus Ed25519 signature when the store key is set) and installed in
  `wp-content/underworld-modules`, then switched on. Lifetime licenses: daily update check
  and one-click updates. **Deactivate license** removes the module and frees the key for
  another site. A premium module stops when its license is revoked.
- Modules can be marked premium with the header `Premium: yes`; they only run with an
  active license.
- Store contract and module packaging: `docs/PREMIUM.md`.
- Uninstall with "delete all game data" now also removes the game layout, migrations and
  licenses.

## 1.8.1

- Fixed: a Dutch round name from the very first version ("Ronde 2", "Ronde 3", ...) is now
  translated to "Round 2", "Round 3", ... The earlier update only translated "Ronde 1".
  Premium points still called "punten" become "Points".

## 1.8.0

A premium look for the game, the theme and the admin.

- **Game**: its own set of line icons for every page, stat and alert; stat chips with icon
  tiles; the player's avatar sits in a ring that fills up towards the next rank; menu with
  icons and a highlighted active page; page titles with an icon tile; refined cards, buttons
  (gradient, lift, focus ring), fields, progress bars with a soft sheen, alerts with icons,
  timers and "ready" as pills, framed tables and segmented tabs; subtle entrance motion that
  respects "reduce motion".
- **Overview** redesigned: hero with avatar ring, rank and health meters, stat tiles, timer
  list with icons and a "ready" counter, notification feed.
- Welcome, login, create character, dead and closed screens with an emblem and glow.
- **Theme 3.3.0**: glass header while scrolling, animated menu underline, refined buttons,
  cards and blog grid (lift and image zoom), comment cards, widget headings, footer hairline,
  selection colour and focus rings. Fixed: the sticky header didn't stick in the boxed layout,
  and the theme squeezed game tables inside page content.
- **Admin**: branded header with navigation and quick actions, a new dashboard (stat tiles,
  7-day activity chart, top players, live feed, quick links, new round in a danger zone),
  module cards with on/off switches, filter and search, settings in panels with a sticky save
  bar, restyled game data screens. Works on phones.
- The default game header now shows the player (with rank ring) and cash, bank, bullets,
  health, city and points. Existing layouts are kept.
- Developers: `dfmg_icons` and `dfmg_activity_labels` filters, `'icon'` on menu items.

## 1.7.0

- **Light and dark mode** (theme 3.2.0, *Customize → Global → Light & dark mode*): one
  colour scheme, or a switch for visitors that starts dark, starts light or follows the
  device. Light mode has its own palette (Daylight by default) and custom colours with live
  preview. The visitor's choice is remembered and applied before the page is drawn. New
  *Light/dark switch* element for the header and footer builders, the game layout, the
  widget and the `[ue_hud]` shortcode. The game switches along.
- **Player avatars**: upload a picture on *My profile*. It is cropped to a square, saved as
  WebP and becomes the user's WordPress avatar on the whole site (instead of Gravatar). It
  is shown in the game header, on profiles and in the theme's player account element.
  Settings for uploads on/off, maximum size and avatar size under Profile → Configure.
- Game elements can be kept out of the theme builders with `'theme' => false`.
- Fixed: stacked game forms no longer split into two columns on phones.

## 1.6.0

- **Game layout builder** (*Appearance → Customize → Game layout*): drag & drop game
  elements into the game header (left/center/right), sidebar, above or below the content
  and the game footer (left/center/right). Changes show instantly in the preview, which
  opens the game automatically. Works with any theme.
- New **game elements**: player, rank, rank progress, cash, bank, bullets, health, premium
  points, city, wealth title, notifications and messages with counters, active timers, the
  game menu (complete or per group), players online, round, play / log in button, log out.
- Game elements in the **theme header and footer builders** (theme 3.1.0), in a new
  **widget** and with the **`[ue_hud]` shortcode**, so the player's money, timers or the game
  menu can be shown on every page of the site.
- Game menu as a **dropdown** when placed in a header or footer, shown as a bottom sheet on
  phones. Without a sidebar the game uses the full width.
- Live countdowns now also run in game elements outside the game.
- Developers: `dfmg_hud_elements` filter for custom elements. It replaces
  `dfmg_header_stats`; the header is now built from game elements.
- Theme 3.1.0: fixed the size of the "+" buttons in the header and footer builders.

## 1.5.0

- Theme 3.0.0: **drag & drop header builder** (three rows × left/center/right, separate
  desktop and tablet/mobile layouts, mobile menu panel as dropdown or off-canvas) and
  **footer builder** (three rows with 1-4 columns). New elements: secondary menu, HTML/text,
  social icons, player account (links to the game profile) and menu toggle.
- **Per device values** (desktop, tablet, mobile) for font sizes, logo width, header row
  heights, side spacing and footer padding, with device buttons that switch the preview.
- **Live preview**: colours, typography, sizes, header and footer update without reloading.
- Existing header and footer settings of theme 2.0 are converted into the builders.
- Mobile polish for the theme: full width content on phones (no more clipped content),
  submenu toggles in the mobile menu, off-canvas panel below the admin bar.
- Mobile polish for the game: tables become cards with labels, full width forms and
  buttons, compact two-column game menu with an animated menu button, stats bar that no
  longer breaks words, no horizontal scrolling at 360, 390, 768 and 1024 px.

## 1.4.0

- The bundled **Underworld Empire** theme (2.0.0) is rebuilt as a fully customisable theme
  with options comparable to the free version of Astra, all in *Appearance → Customize*:
  colour palettes and custom colours, typography with optional Google Fonts, site layouts,
  container widths, sidebars per page type, three header layouts, logo, sticky and
  transparent header, header button and search, top bar, mobile menu, footer widget columns
  and copyright, blog list/grid and post options, breadcrumbs and scroll to top.
- New *Page options* box per page and post (sidebar, content layout, transparent header,
  hide title/featured image/breadcrumbs/header/footer).
- Menus, widget areas, custom logo, WooCommerce styling, editor styles and starter content.
- The theme's colours are shared with WordPress blocks and with the game.
- The theme URL is now built from the plugin URL, so it also works for symlinked plugins.

## 1.3.0

- The game now follows the colours and font of the active WordPress theme. A new
  *Appearance* setting switches back to the built-in dark look.
- Bundled block theme **Underworld Empire** (Appearance → Themes) in the game's colours,
  with a wide *Game* template that is applied to the game page.
- The player name in the top left is now a button to your own profile.
- Fixed the last Dutch label (the *Cars* table of the Garage module).
- Installs that still have Dutch starting data (ranks, crimes, cars, items, ...) are
  translated to English automatically; renamed rows are left alone. Old game pages with
  `[maffia_game]` or `[mafia_game]` are switched to `[underworld_empire]`.

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
