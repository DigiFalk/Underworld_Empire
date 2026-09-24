# WP Mafia Game

A complete, modular mafia browser game (PBBG) as a WordPress plugin, by **DigiFalk**.

Players log in with their WordPress account, pick a gangster name and work their way up
from *Street Rat* to *Godfather*: committing crimes, stealing cars, building a family,
buying businesses, gambling at the casino and taking out rivals.

## Installation

1. Upload the folder to `wp-content/plugins/` (or install the zip via *Plugins → Add New*).
2. Activate **WP Mafia Game**.
3. On activation the tables are created, starting data is loaded and a page
   **Mafia Game** is created containing the shortcode `[mafia_game]`.
4. Enable *Settings → General → Anyone can register* if players may create their own account.
5. Manage everything under the **Mafia Game** menu in the WordPress admin.

Requirements: WordPress 6.0+, PHP 7.4+, MySQL 5.7+/MariaDB 10.3+.

## Administration

| Screen | Purpose |
| --- | --- |
| **Dashboard** | Numbers, link to the game page and *Start new round* (wipes player data, keeps game data and premium points). |
| **Modules** | Enable and disable modules. Dependencies are checked. |
| **Game data** | Edit players, ranks, wealth titles, cities, items and module data (crimes, cars, theft spots, memberships, forum boards, families). |
| **Settings** | General settings plus the settings of every active module. |
| **Game news** | News items (custom post type) shown in the game and on the login page. |

## Bundled modules

| Module | Id | Description |
| --- | --- | --- |
| Overview | `overview` | Home page with status, timers and notifications (required). |
| Crimes | `crimes` | Crimes with a success chance that grows per player. |
| Car Theft | `car-theft` | Steal cars at spots with different odds (requires `garage`). |
| Garage | `garage` | Sell, repair, ship or crush cars into bullets. |
| Police Chase | `police-chase` | Escape the police for a reward. |
| Jail | `jail` | Jail time, breakouts, bail and solitary confinement. |
| Hospital | `hospital` | Heal for money and time. |
| Travel | `travel` | Fly between cities. |
| Bullet Factory | `bullet-factory` | Buy bullets; the factory can be owned. Hourly production via WP-Cron. |
| Black Market | `black-market` | Buy items (requires `inventory`). |
| Inventory | `inventory` | Equip, use and sell items. |
| Bank | `bank` | Deposit (with laundering fee), withdraw, transfer. |
| Properties | `properties` | Manage owned businesses; taken over on murder. |
| Blackjack | `blackjack` | Blackjack; the table can be owned. |
| Detectives | `detectives` | Track down players. |
| Murder | `murder` | Shoot players (requires `detectives`). |
| Bounties | `bounties` | Bounties on players, paid to the killer. |
| Families | `families` | Families with roles, permissions, vault, invitations and log. |
| Premium Membership | `membership` | Shorter cooldowns in exchange for premium points. |
| Messages | `messages` | Private messages. |
| Notifications | `notifications` | Events around your character. |
| Profile | `profile` | Public profiles and your own profile text. |
| Players | `players` | Who's online and search. |
| Leaderboards | `leaderboards` | Top 25 per category. |
| Statistics | `statistics` | Numbers about the game world. |
| News | `news` | Game news. |
| Forum | `forum` | Forum with moderation. |

## Custom modules

The game is fully modular. A module is a folder containing a `module.php`. Put your own
modules in **`wp-content/mafia-modules/<module-id>/`**: that folder survives plugin
updates. Then enable the module under *Mafia Game → Modules*.

See **[docs/MODULES.md](docs/MODULES.md)** for the full guide and
[`docs/example-module/slot-machine`](docs/example-module/slot-machine) for a complete example.

## Customising the look

* All colours are CSS custom properties on `.dfmg` (see `assets/css/game.css`) and can be overridden in your theme.
* Every template can be overridden from your theme:
  * core templates: `<theme>/wp-mafia-game/layout.php`, `login.php`, `create-character.php`, `dead.php`, `closed.php`, `messages.php`
  * module templates: `<theme>/wp-mafia-game/<module-id>/<template>.php`

## Updates

The plugin updates itself through the normal WordPress update screen. It checks the
[GitHub releases](https://github.com/DigiFalk/WP_Maffia_Game/releases) of this repository
(every 12 hours, or immediately via the *Check for updates* link on the Plugins screen)
and installs the `wp-mafia-game.zip` asset of the newest release.

Optional settings in `wp-config.php`:

```php
define( 'DFMG_UPDATE_REPO', 'DigiFalk/WP_Maffia_Game' ); // repository to take releases from
define( 'DFMG_GITHUB_TOKEN', 'ghp_...' );                // only needed for a private repository
```

### Publishing a release

Every version is released automatically by the GitHub Actions workflow
`.github/workflows/release.yml`:

1. Raise the version in **both** the `Version:` header and `DFMG_VERSION` in `wp-mafia-game.php`.
2. Add a `## x.y.z` section to `CHANGELOG.md` (used as the release notes).
3. Push to the default branch. The workflow builds `wp-mafia-game.zip`, tags `vx.y.z` and
   publishes the release. Pushes without a version change don't create a release.

## Translations

All strings use the text domain `wp-mafia-game` and can be translated with the usual
WordPress tools (for example Loco Translate) into the `languages/` folder.

## License

Copyright © DigiFalk. See [LICENSE.md](LICENSE.md).
