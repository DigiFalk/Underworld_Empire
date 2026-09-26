# Premium modules

Premium modules are sold in the DigiFalk store (https://digifalk.com), which runs the
**DigiFalk Licenses** plugin on top of WooCommerce. This document is the contract between
that store and Underworld Empire.

## How it works for the buyer

1. On **Underworld Empire → Modules** the section *Premium modules* shows every premium
   module as a locked card with its price, a **Buy** button and a field for the license key.
2. The buyer buys the module in the store and receives a license key by email.
3. The buyer pastes the key and clicks **Activate**. Underworld Empire activates the key for
   this site, downloads the module, checks it and installs it in
   `wp-content/underworld-modules/<product>/`, then switches it on.
4. Updates: the plugin checks the store daily (or on *Check for updates*). When a newer
   version exists the card shows **Update to vX**. Licenses are lifetime: updates forever.
5. **Deactivate license** switches the module off, removes its files (game data is kept)
   and frees the activation, so the key can be used on another site.

A premium module only runs while its license is active. When the store reports the license
as revoked (for example after a refund) the module stops.

## Writing a premium module

A premium module is a normal module (see `MODULES.md`) with one extra header line:

```php
/**
 * Module Name: Heists
 * Description: Plan big heists with your family.
 * Version: 1.0.0
 * Author: DigiFalk
 * Premium: yes
 */
```

Package it as a zip with **one root folder named after the product slug**, which is also the
module id:

```
ue-heists.zip
└── ue-heists/
    ├── module.php
    └── views/…
```

Upload the zip to the WooCommerce product in the store (see the DigiFalk Licenses plugin).

## Placeholders

Cards come from the store catalogue (cached 12 hours) merged over `premium/catalog.php`,
which ships with the plugin. Add entries there to show a module even when the store can't be
reached. New products in the store appear automatically.

## Configuration

| | |
|---|---|
| `DFMG_STORE_URL` (constant) / `dfmg_store_url` (filter) | Store URL, default `https://digifalk.com`. HTTPS is required. |
| `DFMG_STORE_PUBLIC_KEY` (constant) / `dfmg_store_public_key` (filter) | Base64 Ed25519 public key of the store; every package must carry a valid signature. The key of the DigiFalk store is built in (`Licenses::STORE_PUBLIC_KEY`), so sites need nothing in `wp-config.php`. Only set this for another store, or temporarily after rotating the store's keys. |
| `dfmg_premium_catalog` (filter) | Change the list of premium cards. |
| `dfmg_premium_is_licensed` (filter) | `bool $ok, string $product, ?array $license`. |
| `dfmg_premium_installed` (action) | `string $product, string $version` after install or update. |

### Rotating the store's signing keys

The public key is built into the plugin. Before you rotate the keys in DigiFalk Licenses,
release an Underworld Empire update with the new public key in `Licenses::STORE_PUBLIC_KEY`,
otherwise sites refuse the newly signed downloads. Sites that can't update yet can set
`DFMG_STORE_PUBLIC_KEY` in `wp-config.php`.

## Store API

Base: `https://digifalk.com/wp-json/digifalk-licenses/v1/`. JSON in and out. Errors use a
non-2xx status with `{ "code": "…", "message": "…" }`; the message is shown to the admin.

### `GET /catalog?client=underworld-empire`

```json
{ "products": [ {
  "product": "ue-heists", "name": "Heists", "description": "…", "version": "1.0.0",
  "price": "€ 19", "buy_url": "https://digifalk.com/product/heists/", "icon": "bank",
  "requires": "1.9.0"
} ] }
```

`icon` is an Underworld Empire icon name (optional), `requires` the minimum plugin version.

### `POST /activate`

Request: `license_key`, `product`, `site_url`, `client`, `client_version`, `wp_version`,
`php_version`.

```json
{ "activation_id": "act_…", "activation_secret": "…",
  "license": { "status": "active", "type": "lifetime", "activations_limit": 1, "activations_used": 1 },
  "product": { "product": "ue-heists", "name": "Heists" } }
```

Activating the same key again on the same site URL returns the existing activation (with a
new secret) instead of using another activation slot.

Errors: `license_not_found` (404), `product_mismatch` (403), `license_revoked` (403),
`activation_limit_reached` (403), `invalid_request` (400), `rate_limited` (429).

### `POST /check`

Request: `license_key`, `product`, `activation_id`, `activation_secret`, `installed_version`
and the site fields.

```json
{ "license": { "status": "active", "type": "lifetime" },
  "latest": { "version": "1.1.0", "requires": "1.9.0", "changelog": "…" },
  "package": { "url": "https://digifalk.com/wp-json/digifalk-licenses/v1/download?token=…",
               "expires": 1760000000, "sha256": "<64 hex>", "size": 12345,
               "signature": "<base64 Ed25519 signature of the sha256 hex string>" } }
```

Errors: `activation_not_found` (404), `license_revoked` (403), `license_not_found` (404).
After one of these three the module is switched off on the site.

### `GET /download?token=…`

Streams the zip (`application/zip`). The token is short-lived (10 minutes) and bound to the
activation and version. The client only accepts download URLs on the store's own host and
checks the SHA-256 (and the signature when a public key is configured).

### `POST /deactivate`

Request: `license_key`, `product`, `activation_id`, `activation_secret`. Response
`{ "deactivated": true }`. `activation_not_found` is treated as already deactivated.

## Privacy

Only when an administrator activates or checks a license does the site send its URL,
WordPress, PHP and plugin version and the key to the store. Nothing is sent for visitors or
players.
