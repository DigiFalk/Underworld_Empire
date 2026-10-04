# Underworld Empire

A complete, modular mafia browser game (PBBG) as a WordPress plugin, by **DigiFalk**.

Players log in with their WordPress account, pick a gangster name and work their way up
from *Street Rat* to *Godfather*: committing crimes, stealing cars, building a family,
buying businesses, gambling at the casino and taking out rivals.

## Two plugins

The game comes as two plugins. This repository holds both, each in its own folder:

| Folder | Plugin | Contents |
| --- | --- | --- |
| [`lite/`](lite) | **Underworld Empire** (`underworld-empire.zip`) | The game engine and 16 modules: a complete game with crimes, car theft, jail, hospital, travel, bank, inventory, messages and leaderboards. |
| [`extended/`](extended) | **Underworld Empire Extended** (`underworld-empire-extended.zip`, free) | Families, murders, detectives, bounties, the bullet factory, black market, blackjack, police chases, properties, the forum, the **Underworld Empire theme** and premium modules (license keys). Needs Underworld Empire. |

Both zips are attached to every [GitHub release](https://github.com/DigiFalk/Underworld_Empire/releases).
Sites that update from 1.10/1.11 get Extended installed and activated automatically, so the
game keeps all its modules and data.

## Installation

1. Upload `underworld-empire.zip` and `underworld-empire-extended.zip` via *Plugins → Add New
   → Upload Plugin* (or install only Underworld Empire and click *Install Extended* on the
   Modules screen later).
2. Activate **Underworld Empire** and **Underworld Empire Extended**.
3. On activation the tables are created, starting data is loaded and a page
   **Underworld Empire** is created containing the shortcode `[underworld_empire]`.
4. Enable *Settings → General → Anyone can register* if players may create their own account.
5. Manage everything under the **Underworld Empire** menu in the WordPress admin.

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

Modules marked *Extended* come with Underworld Empire Extended.

| Module | Id | Plugin | Description |
| --- | --- | --- | --- |
| Overview | `overview` | Underworld Empire | Home page with status, timers and notifications (required). |
| Crimes | `crimes` | Underworld Empire | Crimes with a success chance that grows per player. |
| Car Theft | `car-theft` | Underworld Empire | Steal cars at spots with different odds (requires `garage`). |
| Garage | `garage` | Underworld Empire | Sell, repair, ship or crush cars into bullets. |
| Police Chase | `police-chase` | Extended | Escape the police for a reward. |
| Jail | `jail` | Underworld Empire | Jail time, breakouts, bail and solitary confinement. |
| Hospital | `hospital` | Underworld Empire | Heal for money and time. |
| Travel | `travel` | Underworld Empire | Fly between cities. |
| Bullet Factory | `bullet-factory` | Extended | Buy bullets; the factory can be owned. Hourly production via WP-Cron. |
| Black Market | `black-market` | Extended | Buy items (requires `inventory`). |
| Inventory | `inventory` | Underworld Empire | Equip, use and sell items. |
| Bank | `bank` | Underworld Empire | Deposit (with laundering fee), withdraw, transfer. |
| Properties | `properties` | Extended | Manage owned businesses; taken over on murder. |
| Blackjack | `blackjack` | Extended | Blackjack; the table can be owned. |
| Detectives | `detectives` | Extended | Track down players. |
| Murder | `murder` | Extended | Shoot players (requires `detectives`). |
| Bounties | `bounties` | Extended | Bounties on players, paid to the killer. |
| Families | `families` | Extended | Families with roles, permissions, vault, invitations and log. |
| Messages | `messages` | Underworld Empire | Private messages. |
| Notifications | `notifications` | Underworld Empire | Events around your character. |
| Profile | `profile` | Underworld Empire | Public profiles and your own profile text. |
| Players | `players` | Underworld Empire | Who's online and search. |
| Leaderboards | `leaderboards` | Underworld Empire | Top 25 per category. |
| Statistics | `statistics` | Underworld Empire | Numbers about the game world. |
| News | `news` | Underworld Empire | Game news. |
| Forum | `forum` | Extended | Forum with moderation. |

## Premium modules

Extra modules can be bought in the DigiFalk store. They need Underworld Empire Extended. They show on the Modules screen as locked
cards with a **Buy** button; paste the license key from your email and the module is
downloaded, verified, installed and switched on. One payment, lifetime updates. Details and
the store API: [`docs/PREMIUM.md`](docs/PREMIUM.md).

## Custom modules

The game is fully modular. A module is a folder containing a `module.php`. Put your own
modules in **`wp-content/underworld-modules/<module-id>/`**: that folder survives plugin
updates. Then enable the module under *Underworld Empire → Modules*.

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

* **Game layout** (*Appearance → Customize → Game layout*, works with any theme): drag
  elements into the game header (left, center, right), the sidebar, above or below the page
  content, or the game footer (left, center, right). The preview opens the game and updates
  immediately. With an empty sidebar the game uses the full width; the game menu can then go
  in the header as a dropdown (a bottom sheet on phones).
* **Theme header and footer**: with the Underworld Empire theme every game element is also available
  in the header and footer builders (*Game: Cash*, *Game: Game menu*, ...), on desktop,
  mobile and in the mobile menu panel – for example the player's money in the top bar of
  every page of the site.
* **Widgets**: the *Underworld Empire: game element* widget shows one element in any widget
  area.
* **Shortcode**: `[ue_hud element="cash"]`, several at once with
  `[ue_hud element="cash,bank,bullets"]`, and `layout="bar|stack|inline"`.

Elements that need a character are hidden for visitors; *Play / log in* and *Players
online* are always shown. Modules and themes can add their own elements with the
`dfmg_hud_elements` filter (see `docs/MODULES.md`).

### Colours and theme

* **Light and dark mode** (bundled theme, *Customize → Global → Light & dark mode*): keep one
  colour scheme, or let visitors switch between dark and light with the *Light/dark switch*
  element (header builder, footer builder, game layout or `[ue_hud element="mode-toggle"]`).
  Start dark, start light, or follow the visitor's device. The choice is remembered in the
  browser and applied before the page is drawn, so there is no flash of the wrong colours.
  The *Colours* section holds the dark mode colours; light mode has its own palette and
  colours. The game follows the mode (with *Appearance: follow the theme*).

* **Appearance** (Underworld Empire → Settings): by default the game follows the colours and
  font of your WordPress theme, in light and dark themes alike. Choose *Built-in dark look* for
  the original dark style regardless of the theme.
* **Underworld Empire theme**: Underworld Empire Extended ships the theme **Underworld
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
  * core templates: `<theme>/underworld-empire/layout.php`, `login.php`, `create-character.php`, `dead.php`, `closed.php`, `messages.php`
  * module templates: `<theme>/underworld-empire/<module-id>/<template>.php`

## Updates

Both plugins update themselves through the normal WordPress update screen. They check the
[GitHub releases](https://github.com/DigiFalk/Underworld_Empire/releases) of this repository
(every 12 hours, or immediately via the *Check for updates* link on the Plugins screen)
and install the `underworld-empire.zip` and `underworld-empire-extended.zip` assets of the
newest release.

Optional settings in `wp-config.php`:

```php
define( 'DFMG_UPDATE_REPO', 'DigiFalk/Underworld_Empire' ); // repository to take releases from
define( 'DFMG_GITHUB_TOKEN', 'ghp_...' );                // only needed for a private repository
```

### Publishing a release

Every version is released automatically by the GitHub Actions workflow
`.github/workflows/release.yml`:

1. Raise the version (the same number everywhere) in the `Version:` header and `DFMG_VERSION`
   of `lite/underworld-empire.php`, and in the `Version:` header and `DFMG_EXTENDED_VERSION`
   of `extended/underworld-empire-extended.php`.
2. Add a `## x.y.z` section to `CHANGELOG.md` (used as the release notes).
3. Push to the `main` branch. The workflow builds `underworld-empire.zip` and
   `underworld-empire-extended.zip`, tags `vx.y.z` and publishes the release. Pushes without
   a version change don't create a release.

## Translations

Translating Underworld Empire (with translation files, translation plugins or otherwise) is
not allowed by the license. Other languages are reserved for a premium module from DigiFalk.
The strings use the text domain `underworld-empire`; your own modules may use their own text
domain and translations.

## License

Copyright © DigiFalk. See [LICENSE.md](LICENSE.md) for the full text. In short:

- Free to use on any number of websites, private and commercial.
- Do not modify the plugin, the theme or the bundled modules, and do not distribute them.
  Settings, the Customizer and Additional CSS are fine. Translating the game is not allowed;
  other languages come with a DigiFalk premium module.
- Extend the game with your own modules. They are yours to use, share or sell, as long as
  they are your own work and do not copy or imitate a DigiFalk premium module.
- With Underworld Empire Extended the "Underworld Empire by DigiFalk" line stays, unless you
  use the White Label premium module.
