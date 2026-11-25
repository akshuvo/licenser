# Known Issues, Backlog & Testing Guide

> **Last updated:** 2026-11-26 after Phase 1–2 stabilization, local DB migration, and WordPress.org Plugin Repository compliance prep on `licenser` (`main` + uncommitted working tree).

---

## 1. Resolved in Phase 1–2 Stabilization & WP.org Prep (2026-11-26)

- **Tooling & Coding Standards:** Installed PHPCS `3.13.6` + WPCS `3.4.1` globally in `$HOME/.config/composer`, added [`.phpcs.xml.dist`](../.phpcs.xml.dist), and brought `licenser/` from **6,716 errors / 853 warnings** down to **0 errors / 0 warnings** across all 35 PHP files.
- **WordPress.org Plugin Repository Readiness:**
  - Added `if ( ! defined( 'ABSPATH' ) ) { exit; }` direct-access guards to every first-party PHP file.
  - Eliminated all inline `<script>` and `<style>` tags across PHP templates by consolidating admin JS into [`assets/js/admin.js`](../assets/js/admin.js), moving SDK Generator CSS into [`assets/css/admin.css`](../assets/css/admin.css), and replacing `<script type="text/html">` with HTML5 `<template>` tags.
  - Migrated settings option `lmfwppt_settings` -> `licenser_settings` and transient cache prefix `lwp_cache_` -> `licenser_cache_` with automatic upgrade migration in [`Installer.php`](../includes/Installer.php) (`DB_VERSION = 29`).
  - Cleaned [`readme.txt`](../readme.txt) (contributors `mdshuvo, addonmaster`, 5 tags, `Stable tag: 1.0.8`, `Tested up to: 7.1`, `Requires PHP: 7.4`, short description <= 150 chars, removed duplicate sections), added [`.distignore`](../.distignore), removed `.DS_Store`, and removed `console.log()` from JS.
- **Structure, Naming & DB Upgrades:**
  - Normalized REST namespaces in `includes/Api/*.php` to `namespace Licenser\Api;` (case-safe on Linux).
  - Replaced legacy `lmfwppt` text domains with `licenser`; aligned `Licenser::VERSION` / `LICENSER_VERSION` to `1.0.8`.
  - Upgraded `Installer::DB_VERSION` to `29` (`banners` -> `text`, indexed `varchar(191)` for `utf8mb4` safety, `Licenser::maybe_upgrade()` on `plugins_loaded`).
  - Declared WooCommerce HPOS (`custom_order_tables`) compatibility in [`licenser.php`](../licenser.php).
  - Scoped admin assets strictly to Licenser screens (CodeMirror only on SDK Generator) and WooCommerce `product` edit screens.
- **Core Licensing, Security, WooCommerce & Admin UI:**
  - Implemented `License::deactivate()` to release domain slots; enabled `domain_limit` enforcement with `www.`/trailing-slash idempotency; hardened public `action=check` and `Product::download()` (product ownership, expiry, status, remote URL validation, `ABSPATH` local file readability checks).
  - Secured `wp_ajax_licenser_media_frame_state` (`manage_options` + nonce), allowlisted SQL identifiers, prepared `IN (...)` and `licenser_delete_cache()` `LIKE` queries, sanitized settings, and escaped list-table/template outputs.
  - Migrated `Wc_Lite` order metadata to HPOS-safe CRUD (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`), fixed blank license keys in order emails (+ DB fallback & plain-text support), fixed multi-item `generate_license()` loop (`continue`), fixed `display_item_data_cart()` return value, and buffered `Shortcode::output()`.
  - Wired live counts on Dashboard, real pagination & `column_end_date`/`column_domain_limit` on list tables, fixed `licenses/new.php` edit/status/meta-delete controls, fixed SDK Generator (`submenu` + single-slash API URL), fixed `GET /products`, `GET /licenses`, `GET /settings`, and cleaned Addons page.
- **Local Portal DB Migration (`http://portal-addonmaster.local`):**
  - Migrated all 4 products (IDs `1`–`4`, exact production UUIDs recovered from release ZIPs and cached transients) and all 15 license packages (IDs `1`–`15`, matching WooCommerce variations and existing 87 licenses) into `wp_licenser_products` and `wp_licenser_license_packages`.
  - Migrated `lmfwppt_settings` -> `licenser_settings`, cleared legacy `lwp_cache_*` transients, dropped legacy `wp_lmfwppt_*` tables, and deleted legacy `lmfwppt*` options.

---

## 2. Remaining Backlog

1. **WooCommerce Quantity `> 1`:** `Order_Handler::generate_license()` creates one license per order item regardless of quantity (`quantity > 1` policy — multiply domain limit vs multiple keys — to be decided).
2. **Customer Self-Service Domain Deactivation:** My Account (`Wc_Lite\Shortcode`) currently displays activated domains read-only (candidate for Free or `licenser-pro`).
3. **Product Delete Cascade:** `Models\Product::delete()` deletes the product row and clears cache, leaving child releases/packages/licenses intact until a trash/cascade policy is decided.
4. **Client SDK `Insights` Endpoints:** `LicenserWP/client`'s optional `Insights.php` posts to `track` and uninstall routes, which are not implemented in `licenser` yet.
5. **Automated Test Suite:** No PHPUnit suite is checked in yet; consider adding WordPress integration tests before major feature expansion.

---

## 3. Automated Checks

Run from `/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/plugins/licenser`:

```sh
# 1. PHP syntax lint (all first-party files)
find . -type f -name '*.php' -not -path './.git/*' -not -path './vendor/*' -exec php -l {} \;

# 2. JS syntax check (all first-party files)
find . -type f -name '*.js' -not -path './.git/*' -not -path './vendor/*' -exec node --check {} \;

# 3. WordPress Coding Standards (WPCS 3.4.1 / PHPCS 3.13.6)
"$HOME/.config/composer/vendor/bin/phpcs" --report=full --report=summary
```

---

## 4. Verification Record (2026-11-26)

### Static & WP.org Compliance Checks
- `php -l` across all 35 first-party PHP files: **PASS** (0 errors).
- `node --check` across all first-party JS files: **PASS** (0 errors).
- `phpcs` with `.phpcs.xml.dist`: **PASS** (`0` errors, `0` warnings).
- WP.org static checks: **0** PHP files missing `ABSPATH` guard, **0** inline `<script>`/`<style>` tags in PHP templates, **0** `console.log()` in JS, **0** `.DS_Store` files, **0** diffs in `vendor/`.

### Browser QA on `http://portal-addonmaster.local/` (Post-Migration)
Verified across two live browser passes (`wp-admin/?localwp_auto_login=1`, **0 PHP notices/warnings/fatals**, **0 JS console errors**):
- **Dashboard (`page=licenser`):** Displays live counts — **Total Plugins: 4**, **Total Themes: 0**, **Total Licenses: 87**.
- **Plugins List & Edit (`page=licenser-plugins`):** All 4 plugins render (`4 items` pagination). Editing **GridMaster Pro (`id=4`)** displays UUID `da399f0f-c005-4b5f-80b8-df3222e79169`, all 5 sidebar anchors, stable release `1.2.2`, all 3 packages hydrated via `assets/js/admin.js`, and `<template>` `Add License Package` working cleanly.
- **Licenses List & Edit (`page=licenser-licenses`):** `87 items` pagination (`1 of 5`), formatted `Expires` dates & `Lifetime`, `Unlimited` / numeric `Domain Limit`. Editing license `id=91` pre-selects `Status = Active`, `Product Type = Plugin`, `Product = Load More Anything Pro`, and auto-populates/selects `Package = 1 website - Yearly` via REST.
- **SDK Generator (`page=licenser-sdk-generator`):** Selecting `Plugin` -> `GridMaster Pro` -> `Sub Menu` generates valid `<?php` code with `'da399f0f-c005-4b5f-80b8-df3222e79169'`, `'http://portal-addonmaster.local'` (no trailing slash), and `'type'        => 'submenu'`.
- **Settings & Addons:** Microtime / WP Password / UUID v4 options render; `WooCommerce Lite (Built-in)` shows `Active` and `Licenser Pro` card renders cleanly.
- **Asset Scoping & WooCommerce Product `#188` (`Gridmaster Pro`):** `licenser-admin-script` confirmed absent on `wp-admin/edit.php`; WooCommerce variation `#189` loads `GridMaster Pro` + `1 Year, 1 Site` cleanly via `licenser-wc-admin`.
- **Security checks:** Unauthenticated `GET /licenser/v1/products` returns `401`; nonce-less `admin-ajax.php?action=licenser_media_frame_state&state=open` returns `403` (`Invalid nonce.`).

### WordPress Integration Suite (LocalWP PHP + MySQL Socket)
Verified live against migrated `wp_licenser_*` tables (`DB_VERSION = 29`, `licenser_settings`, 4 plugins, 15 packages) using disposable test records (cleaned up with exact row counts restored):
- Public updater `POST /public/products/da399f0f-c005-4b5f-80b8-df3222e79169` returns live GridMaster Pro `1.2.2` metadata.
- Full license lifecycle (`activate`, `www.`/trailing-slash idempotency, `domain_limit=1` rejection, `check` rejecting wrong product/expired/inactive keys, `deactivate` freeing slot, 2nd site activation) passed.
- Cross-product download (`GridMaster Pro` key requesting `Load More Anything Pro` download) rejected with HTTP `403`.
- Settings allowlist/sanitization, meta deletion endpoint, `Wc_Lite` hidden cart filter preservation, and buffered `Shortcode::output()` all passed.
