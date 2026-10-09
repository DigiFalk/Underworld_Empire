# Mafia PBBG Engine

A complete, modular mafia browser game (PBBG) as a WordPress plugin, by **DigiFalk**.

Players log in with their WordPress account, pick a gangster name and work their way up
from *Street Rat* to *Godfather*: committing crimes, stealing cars, building a family,
buying businesses, gambling at the casino and taking out rivals.

## Two plugins

The game comes as two plugins. This repository holds both, each in its own folder:

| Folder | Plugin | Contents |
| --- | --- | --- |
| [`lite/`](lite) | **Mafia PBBG Engine** (`mafia-pbbg-engine.zip`) | The game engine and 16 modules: a complete game with crimes, car theft, jail, hospital, travel, bank, inventory, messages and leaderboards. |
| [`extended/`](extended) | **Mafia PBBG Engine Extended** (`mafia-pbbg-engine-extended.zip`, free) | Families, murders, detectives, bounties, the bullet factory, black market, blackjack, police chases, properties, the forum, the **Mafia PBBG Engine theme** and premium modules (license keys). Needs Mafia PBBG Engine. |

Every [GitHub release](https://github.com/DigiFalk/Mafia_PBBG_engine/releases) carries three zips:

| Zip | What it is |
| --- | --- |
| `mafia-pbbg-engine.zip` | Mafia PBBG Engine, **GitHub edition**: updates itself from these releases and can install Extended with one click. |
| `mafia-pbbg-engine-extended.zip` | Mafia PBBG Engine Extended. Updates itself from these releases. |
| `mafia-pbbg-engine-wordpress-org.zip` | Mafia PBBG Engine, **WordPress.org edition** (slug `mafia-pbbg-engine`): the same plugin without the GitHub updater and without the Extended installer (`includes/Updater.php` and `includes/Bridge.php`), as the plugin directory requires. Built by `.github/scripts/build-wporg.sh`. |

### WordPress.org

The WordPress.org edition passes the official [Plugin Check](https://wordpress.org/plugins/plugin-check/)
without errors or warnings. To submit it:

1. Log in on WordPress.org with the DigiFalk account and set that username under
   `Contributors:` in `lite/readme.txt`.
2. Upload `mafia-pbbg-engine-wordpress-org.zip` of the newest release on
   https://wordpress.org/plugins/developers/add/.
3. After approval, publish new versions to the plugin's SVN repository. The slug is
   `mafia-pbbg-engine` (folder and main file `mafia-pbbg-engine/mafia-pbbg-engine.php`).

## Installation

1. Upload `mafia-pbbg-engine.zip` and `mafia-pbbg-engine-extended.zip` via *Plugins → Add New
   → Upload Plugin* (or install only Mafia PBBG Engine and click *Install Extended* on the
   Modules screen later).
2. Activate **Mafia PBBG Engine** and **Mafia PBBG Engine Extended**.
3. On activation the tables are created, starting data is loaded and a page
   **Mafia PBBG Engine** is created containing the shortcode `[mafia_pbbg_engine]`.
4. Enable *Settings → General → Anyone can register* if players may create their own account.
5. Manage everything under the **Mafia PBBG Engine** menu in the WordPress admin.

Requirements: WordPress 6.0+, PHP 7.4+, MySQL 5.7+/MariaDB 10.3+.

## Administration

All admin screens share a branded header with quick actions (*Game layout*, *Open the game*)
and section navigation. The **dashboard** shows player stats, a 7-day activity chart, the
top players, a live feed of what happens in the game and quick links; starting a new round
sits in a collapsed danger zone. **Modules** are cards with an on/off switch, filter
(all/active/inactive) and search.

| Screen | Purpose |
| --- | --- |
| **Dashboard** | Numbers, link to the game page and *Start new round* (wipes player data, keeps game data). |
| **Modules** | Enable and disable modules (dependencies are checked). **Configure** opens one page with all settings and game data of that module. |
| **Game data** | Core data: players, ranks, wealth titles, cities and items. |
| **Settings** | General game settings. |
| **Game news** | News items (custom post type) shown in the game and on the login page. |

## Bundled modules

Modules marked *Extended* come with Mafia PBBG Engine Extended.

| Module | Id | Plugin | Description |
| --- | --- | --- | --- |
| Overview | `overview` | Mafia PBBG Engine | Home page with status, timers and notifications (required). |
| Crimes | `crimes` | Mafia PBBG Engine | Crimes with a success chance that grows per player. |
| Car Theft | `car-theft` | Mafia PBBG Engine | Steal cars at spots with different odds (requires `garage`). |
| Garage | `garage` | Mafia PBBG Engine | Sell, repair, ship or crush cars into bullets. |
| Police Chase | `police-chase` | Extended | Escape the police for a reward. |
| Jail | `jail` | Mafia PBBG Engine | Jail time, breakouts, bail and solitary confinement. |
| Hospital | `hospital` | Mafia PBBG Engine | Heal for money and time. |
| Travel | `travel` | Mafia PBBG Engine | Fly between cities. |
| Bullet Factory | `bullet-factory` | Extended | Buy bullets; the factory can be owned. Hourly production via WP-Cron. |
| Black Market | `black-market` | Extended | Buy items (requires `inventory`). |
| Inventory | `inventory` | Mafia PBBG Engine | Equip, use and sell items. |
| Bank | `bank` | Mafia PBBG Engine | Deposit (with laundering fee), withdraw, transfer. |
| Properties | `properties` | Extended | Manage owned businesses; taken over on murder. |
| Blackjack | `blackjack` | Extended | Blackjack; the table can be owned. |
| Detectives | `detectives` | Extended | Track down players. |
| Murder | `murder` | Extended | Shoot players (requires `detectives`). |
| Bounties | `bounties` | Extended | Bounties on players, paid to the killer. |
| Families | `families` | Extended | Families with roles, permissions, vault, invitations and log. |
| Messages | `messages` | Mafia PBBG Engine | Private messages. |
| Notifications | `notifications` | Mafia PBBG Engine | Events around your character. |
| Profile | `profile` | Mafia PBBG Engine | Public profiles and your own profile text. |
| Players | `players` | Mafia PBBG Engine | Who's online and search. |
| Leaderboards | `leaderboards` | Mafia PBBG Engine | Top 25 per category. |
| Statistics | `statistics` | Mafia PBBG Engine | Numbers about the game world. |
| News | `news` | Mafia PBBG Engine | Game news. |
| Forum | `forum` | Extended | Forum with moderation. |

## Premium modules

Extra modules can be bought in the DigiFalk store. They need Mafia PBBG Engine Extended. They show on the Modules screen as locked
cards with a **Buy** button; paste the license key from your email and the module is
downloaded, verified, installed and switched on. One payment, lifetime updates. Details and
the store API: [`docs/PREMIUM.md`](docs/PREMIUM.md).

## Custom modules

The game is fully modular. A module is a folder containing a `module.php`. Put your own
modules in **`wp-content/mafia-pbbg-modules/<module-id>/`**: that folder survives plugin
updates. Then enable the module under *Mafia PBBG Engine → Modules*.

See **[docs/MODULES.md](docs/MODULES.md)** for the full guide and
[`docs/example-module/slot-machine`](docs/example-module/slot-machine) for a complete example.

## Player avatars

On *My profile* players can upload a picture (JPG, PNG, GIF or WebP). It is cropped to a
square, scaled down and saved as **.webp** in `wp-content/uploads/underworld-avatars/`, and
it replaces Gravatar as the **WordPress avatar** of that user everywhere on the site: game,
comments, admin bar, author boxes and the user's WordPress profile. Players can remove it
again. Settings in *Modules → Profile → Configure*: switch uploads on/off, maximum file size
and avatar size. Requires WebP support in GD or Imagick (standard on current PHP versions).
`dfmg_avatar_url( $user_id )` returns the URL; the `dfmg_avatar_updated` action fires after
an upload.

## Customising the look

### Game layout and game elements (drag & drop)

The game is made of small live **game elements**: player (name & avatar), rank, rank
progress bar, cash, bank, bullets, health, city, wealth title,
notifications and messages (with counters), active timers, the game menu (all pages or one
group such as *Crime*), players online, round name & end, a play / log in button and a log
out link. Place them anywhere:

* **Light/dark switch**: the game element *Light/dark switch* (in the game header by default)
  lets players choose between the theme's colours and the built-in dark look; the choice is
  kept in their browser.
* **Game layout** (*Appearance → Customize → Game layout*, works with any theme): drag
  elements into the game header (left, center, right), the sidebar, above or below the page
  content, or the game footer (left, center, right). The preview opens the game and updates
  immediately. With an empty sidebar the game uses the full width; the game menu can then go
  in the header as a dropdown (a bottom sheet on phones).
* **Theme header and footer**: with the Mafia PBBG Engine theme every game element is also available
  in the header and footer builders (*Game: Cash*, *Game: Game menu*, ...), on desktop,
  mobile and in the mobile menu panel – for example the player's money in the top bar of
  every page of the site.
* **Widgets**: the *Mafia PBBG Engine: game element* widget shows one element in any widget
  area.
* **Shortcode**: `[mpe_hud element="cash"]`, several at once with
  `[mpe_hud element="cash,bank,bullets"]`, and `layout="bar|stack|inline"`.

Elements that need a character are hidden for visitors; *Play / log in* and *Players
online* are always shown. Modules and themes can add their own elements with the
`dfmg_hud_elements` filter (see `docs/MODULES.md`).

### Colours and theme

* **Light and dark mode** (bundled theme, *Customize → Global → Light & dark mode*): keep one
  colour scheme, or let visitors switch between dark and light with the *Light/dark switch*
  element (header builder, footer builder, game layout or `[mpe_hud element="mode-toggle"]`).
  Start dark, start light, or follow the visitor's device. The choice is remembered in the
  browser and applied before the page is drawn, so there is no flash of the wrong colours.
  The *Colours* section holds the dark mode colours; light mode has its own palette and
  colours. The game follows the mode (with *Appearance: follow the theme*).

* **Appearance** (Mafia PBBG Engine → Settings): by default the game follows the colours and
  font of your WordPress theme, in light and dark themes alike. Choose *Built-in dark look* for
  the original dark style regardless of the theme.
* **Mafia PBBG Engine theme**: Mafia PBBG Engine Extended ships the theme **Underworld
  Empire**. Activate it under *Appearance → Themes*; it is available while Extended is active. Everything is set in
  *Appearance → Customize*, comparable to the free version of Astra:
  * **Global**: colour palettes (Underworld, Noir, Daylight or custom colours), typography
    (16 fonts, optional Google Fonts, sizes, weights, heading case), site layout (full width,
    boxed, content boxed), container widths, corner radius, sidebar position per page type,
    breadcrumbs, page titles and a scroll-to-top button.
  * **Header builder** (drag & drop): three rows (top, main, bottom) with left, center and
    right zones, separate layouts for desktop and for tablet/mobile, plus the contents of the
    mobile menu panel (dropdown or off-canvas). Elements: logo & title, primary and secondary
    menu, search, button, HTML/text, social icons, player account and menu toggle. Row
    heights and colours, sticky and transparent header.
  * **Footer builder** (drag & drop): three rows with 1-4 columns each. Elements: copyright,
    footer menu, social icons, HTML/text, logo and widget areas Footer 1-4. Alignment,
    padding and colours per row.
  * **Per device values**: font sizes, logo width, header row heights, side spacing and
    footer padding can be set separately for desktop, tablet and mobile.
  * **Live preview**: colours, typography, sizes, header and footer update instantly in the
    preview without reloading the page.
  * **Blog**: list or 2/3-column grid, featured images, date/author/categories/comments,
    excerpt length, read-more text; single posts with featured image, meta, tags, author box
    and previous/next navigation.
  * **Page options** box on every page and post: sidebar, content layout (normal, narrow,
    full width for page builders), transparent header, hide title/featured image/
    breadcrumbs/header/footer.
  * Menus (primary, top bar, footer), widget areas (sidebar, footer 1-4), custom logo,
    WooCommerce styling, block editor styles, starter content for new sites.
  * The palette is shared with WordPress blocks and with the game (in *Follow the theme* mode).
* Block themes can fine-tune the game with these optional palette slugs in `theme.json`:
  `accent`, `surface`, `surface-2`, `border`, `muted`, `button`, `button-text`.
* All styles are CSS custom properties on `.dfmg` and `.dfmg-hud` (see `assets/css/game.css`) and can be overridden in your theme.
* Every template can be overridden from your theme:
  * core templates: `<theme>/mafia-pbbg-engine/layout.php`, `login.php`, `create-character.php`, `dead.php`, `closed.php`, `messages.php`
  * module templates: `<theme>/mafia-pbbg-engine/<module-id>/<template>.php`

## Updates

Both plugins update themselves through the normal WordPress update screen. They check the
[GitHub releases](https://github.com/DigiFalk/Mafia_PBBG_engine/releases) of this repository
(every 12 hours, or immediately via the *Check for updates* link on the Plugins screen)
and install the `mafia-pbbg-engine.zip` and `mafia-pbbg-engine-extended.zip` assets of the
newest release.

Optional settings in `wp-config.php`:

```php
define( 'DFMG_UPDATE_REPO', 'DigiFalk/Mafia_PBBG_engine' ); // repository to take releases from
define( 'DFMG_GITHUB_TOKEN', 'ghp_...' );                // only needed for a private repository
```

### Publishing a release

Every version is released automatically by the GitHub Actions workflow
`.github/workflows/release.yml`:

1. Raise the version (the same number everywhere) in the `Version:` header and `DFMG_VERSION`
   of `lite/mafia-pbbg-engine.php`, and in the `Version:` header and `DFMG_EXTENDED_VERSION`
   of `extended/mafia-pbbg-engine-extended.php`.
2. Add a `## x.y.z` section to `CHANGELOG.md` (used as the release notes).
3. Push to the `main` branch. The workflow builds `mafia-pbbg-engine.zip` and
   `mafia-pbbg-engine-extended.zip`, tags `vx.y.z` and publishes the release. Pushes without
   a version change don't create a release.

## Translations

Mafia PBBG Engine (`lite/`) uses the text domain `mafia-pbbg-engine` and may be translated:
once it is in the WordPress.org plugin directory, translations are made on
translate.wordpress.org. Mafia PBBG Engine Extended and the premium modules may not be
translated (see `extended/LICENSE.md`); other languages for those come with a premium module
from DigiFalk.

## License

- **Mafia PBBG Engine** (`lite/`): GNU General Public License, version 2 or later
  ([`lite/LICENSE.txt`](lite/LICENSE.txt)).
- **Mafia PBBG Engine Extended** (`extended/`, including the theme) and DigiFalk premium
  modules: DigiFalk License ([`extended/LICENSE.md`](extended/LICENSE.md)). Free to use, also
  commercially; not to be modified, distributed or translated; with Extended the line
  "Mafia PBBG Engine by DigiFalk" stays unless you use the White Label premium module.

See [LICENSE.md](LICENSE.md). Copyright © DigiFalk.
