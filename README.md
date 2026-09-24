# WP Maffia Game

Een complete, modulaire maffia-browsergame (PBBG) als WordPress-plugin, door **DigiFalk**.

Spelers loggen in met hun WordPress-account, kiezen een gangsternaam en werken zich
omhoog van *Straatschoffie* tot *Peetvader*: misdaden plegen, auto's stelen, een familie
opbouwen, bedrijven kopen, gokken in het casino en rivalen uit de weg ruimen.

## Installatie

1. Upload de map naar `wp-content/plugins/` (of installeer de zip via *Plugins → Nieuwe plugin*).
2. Activeer **WP Maffia Game**.
3. Bij activatie worden de tabellen aangemaakt, startgegevens geladen en een pagina
   **Maffia Game** aangemaakt met de shortcode `[maffia_game]`.
4. Zet onder *Instellingen → Algemeen* "Iedereen kan zich registreren" aan als spelers zelf een account mogen maken.
5. Beheer alles onder het menu **Maffia Game** in de WordPress-admin.

Vereisten: WordPress 6.0+, PHP 7.4+, MySQL 5.7+/MariaDB 10.3+.

## Beheer

| Scherm | Wat |
| --- | --- |
| **Dashboard** | Cijfers, link naar de spelpagina en *Nieuwe ronde starten* (wist spelersdata, bewaart spelgegevens en premium punten). |
| **Modules** | Modules in- en uitschakelen. Afhankelijkheden worden gecontroleerd. |
| **Spelgegevens** | Bewerk spelers, rangen, rijkdomtitels, steden, items en de data van modules (misdaden, auto's, steelplekken, lidmaatschappen, forumcategorieën, families). |
| **Instellingen** | Algemene instellingen plus de instellingen van elke actieve module. |
| **Spelnieuws** | Nieuwsberichten (eigen berichttype) die in het spel en op de inlogpagina verschijnen. |

## Meegeleverde modules

| Module | Id | Omschrijving |
| --- | --- | --- |
| Overzicht | `overview` | Startpagina met status, timers en meldingen (verplicht). |
| Misdaden | `crimes` | Misdaden met groeiende slagingskans per speler. |
| Auto stelen | `car-theft` | Auto's stelen op plekken met verschillende kansen (vereist `garage`). |
| Garage | `garage` | Auto's verkopen, repareren, verschepen of persen tot kogels. |
| Politieachtervolging | `police-chase` | Ontsnap aan de politie voor een beloning. |
| Gevangenis | `jail` | Celstraf, uitbreken, borg en isoleercel. |
| Ziekenhuis | `hospital` | Genezen tegen betaling en opnametijd. |
| Reizen | `travel` | Vliegen tussen steden. |
| Kogelfabriek | `bullet-factory` | Kogels kopen; fabriek is als bezit te kopen. Productie per uur via WP-Cron. |
| Zwarte markt | `black-market` | Items kopen (vereist `inventory`). |
| Inventaris | `inventory` | Uitrusten, gebruiken en verkopen van items. |
| Bank | `bank` | Storten (met witwaskosten), opnemen, overmaken. |
| Bezittingen | `properties` | Beheer van gekochte bedrijven; overname bij moord. |
| Blackjack | `blackjack` | Blackjack; tafel is als bezit te kopen. |
| Detectives | `detectives` | Spelers opsporen. |
| Moord | `murder` | Spelers neerschieten (vereist `detectives`). |
| Premies | `bounties` | Premies op spelers, uitbetaald aan de moordenaar. |
| Families | `families` | Families met rollen, rechten, kas, uitnodigingen en logboek. |
| Premium lidmaatschap | `membership` | Kortere wachttijden in ruil voor premium punten. |
| Berichten | `messages` | Privéberichten. |
| Meldingen | `notifications` | Gebeurtenissen rond je personage. |
| Profiel | `profile` | Openbare profielen en eigen profieltekst. |
| Spelers | `players` | Wie is online en zoeken. |
| Ranglijsten | `leaderboards` | Top 25 per categorie. |
| Statistieken | `statistics` | Cijfers over de spelwereld. |
| Nieuws | `news` | Spelnieuws. |
| Forum | `forum` | Forum met moderatie. |

## Eigen modules

Het spel is volledig modulair. Een module is een map met een `module.php`. Plaats
eigen modules in **`wp-content/maffia-modules/<module-id>/`**: die map blijft bij
updates van de plugin bewaard. Schakel de module daarna in onder *Maffia Game → Modules*.

Zie **[docs/MODULES.md](docs/MODULES.md)** voor de volledige handleiding en
[`docs/example-module/slot-machine`](docs/example-module/slot-machine) voor een compleet voorbeeld.

## Uiterlijk aanpassen

* Alle kleuren zijn CSS-variabelen op `.dfmg` (zie `assets/css/game.css`) en kunnen in je thema overschreven worden.
* Elk template kan vanuit je thema overschreven worden:
  * kern-templates: `<thema>/wp-maffia-game/layout.php`, `login.php`, `create-character.php`, `dead.php`, `closed.php`, `messages.php`
  * module-templates: `<thema>/wp-maffia-game/<module-id>/<template>.php`

## Licentie

Copyright © DigiFalk. Zie [LICENSE.md](LICENSE.md).
