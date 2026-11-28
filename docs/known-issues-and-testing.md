# Known Issues, Backlog & Testing Guide

> **Last updated:** 2026-11-28 for the Free CSV Migration & Importer suite on `licenser` (`main` + uncommitted working tree). The 2026-11-26 verification records below are historical and do not establish live verification of the migration suite.

---

## 1. Resolved in Phase 1–2 Stabilization & WP.org Prep (2026-11-26)

- **Tooling & Coding Standards:** Installed PHPCS `3.13.6` + WPCS `3.4.1` globally in `$HOME/.config/composer`, added [`phpcs.xml`](../phpcs.xml) (renamed from `.phpcs.xml.dist` so WordPress Plugin Check reports 0 hidden/application errors on local checkouts), and brought `licenser/` from **6,716 errors / 853 warnings** down to **0 errors / 0 warnings** across all 36 first-party PHP files.
- **WordPress.org Plugin Check (PCP) & Auto-Deploy Readiness:**
  - Resolved all Plugin Check warnings across templates (prefixed all template variables with `$licenser_` for `PrefixAllGlobals`) and DB models/list tables (`$wpdb->licenser_*`, `sanitize_sql_orderby()`, pure prepared `FIND_IN_SET` / static `WHERE` clauses with MySQL 8 strict-mode `DATETIME` defaults).
  - Kept `composer.json` included in release packages alongside `vendor/autoload.php` per `File_Type_Check::look_for_composer_files`.
  - Added WordPress.org branding & listing assets under `.wordpress/` (`icon.svg`, `icon-256x256.png`, `icon-128x128.png`, `banner-1544x500.png`, `banner-772x250.png`, and 6 PII-free `screenshot-1.png`–`screenshot-6.png`) and synced `== Screenshots ==` in [`readme.txt`](../readme.txt).
  - Added GridMaster-style deterministic WP.org GitHub Actions auto-deploy workflow ([`.github/workflows/wporg.yml`](../.github/workflows/wporg.yml), [`scripts/release.py`](../scripts/release.py), [`scripts/lint_package.py`](../scripts/lint_package.py), [`scripts/smoke_test.py`](../scripts/smoke_test.py), [`tests/smoke.php`](../tests/smoke.php), [`tests/test_release.py`](../tests/test_release.py)).
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
  - Migrated `WooLite` order metadata to HPOS-safe CRUD (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`), fixed blank license keys in order emails (+ DB fallback & plain-text support), fixed multi-item `generate_license()` loop (`continue`), fixed `display_item_data_cart()` return value, and buffered `Shortcode::output()`.
  - Wired live counts on Dashboard, real pagination & `column_end_date`/`column_domain_limit` on list tables, fixed `licenses/new.php` edit/status/meta-delete controls, fixed SDK Generator (`submenu` + single-slash API URL), fixed `GET /products`, `GET /licenses`, `GET /settings`, and cleaned Addons page.
- **Local Portal DB Migration (`http://portal-addonmaster.local`):**
  - Migrated all 4 products (IDs `1`–`4`, exact production UUIDs recovered from release ZIPs and cached transients) and all 15 license packages (IDs `1`–`15`, matching WooCommerce variations and existing 87 licenses) into `wp_licenser_products` and `wp_licenser_license_packages`.
  - Migrated `lmfwppt_settings` -> `licenser_settings`, cleared legacy `lwp_cache_*` transients, dropped legacy `wp_lmfwppt_*` tables, and deleted legacy `lmfwppt*` options.

---

## 2. Remaining Backlog

1. **Client SDK `Insights` Endpoints:** `LicenserWP/client`'s optional `Insights.php` posts to `track` and uninstall routes, which remain reserved for `licenser-pro` or a future release.
2. **Local/Staging Domain Exemption UI:** Core already exposes the `licenser_should_enforce_domain_limit` filter; UI policy rules remain reserved for `licenser-pro`.
3. **Automated PHPUnit Suite:** No PHPUnit suite is checked in yet (release boundary tests live in `tests/test_release.py`, import-controller PHP harness tests in `tests/test_import.py`, and WordPress activation smoke tests in `tests/smoke.php`); consider adding PHPUnit WP integration tests before major v2 expansion.
4. **Migration live integration & visual QA:** The 2026-11-28 session could not access the LocalWP MySQL socket (`Operation not permitted`) or connect to the browser automation host. The new Migration screen and importer still need the local-only manual scenarios below. These environmental failures do not constitute a completed WordPress, Plugin Check, or visual verification.

*(Resolved in the Cupertino × WP-Native UI/UX & Feature Completion pass: WooCommerce `quantity > 1` configurable policy, customer self-service domain deactivation with order-ownership verification, safe product archive/cascade delete modal, full release history CRUD, bulk license generation + CSV export, API Diagnostics Tester, WooCommerce Order Admin Meta Box, and refund auto-suspend/restore.)*

---

## 3. Automated Checks

Run from `/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/plugins/licenser`:

```sh
# 1. PHP syntax lint (all first-party files)
find . -maxdepth 4 -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l

# 2. Admin JS syntax check
node --check assets/js/admin.js

# 3. WordPress Coding Standards (WPCS 3.4.1 / PHPCS 3.13.6)
"$HOME/.config/composer/vendor/bin/phpcs" --parallel=1 --report=summary

# 4. All unit tests (release boundary tests and isolated PHP import harness)
python3 -B -m unittest discover -s tests -p 'test_*.py' -v

# 5. WordPress Plugin Check (PCP) via LocalWP MySQL socket
php -d mysqli.default_socket="/Users/akshuvo/Library/Application Support/Local/run/vq5NUpMTM/mysql/mysqld.sock" \
  "$(which wp)" --path="/Users/akshuvo/Local Sites/addonmaster-portal/app/public" \
  --url="http://portal-addonmaster.local" plugin check licenser --include-experimental
```

---

## 4. CSV Migration Verification Scope (2026-11-28)

Implemented in Free: [`Api/Import.php`](../includes/Api/Import.php), the `licenser-migration` admin screen, [`Admin/MigrationData.php`](../includes/Admin/MigrationData.php), shared admin JS/CSS, and [migration samples and conversion instructions](migrations/README.md). No database schema or public SDK wire contract change is required. Screen capability context is `migration`; REST base/action are `import` / `create`. Both default to `manage_options`.

`python3 -B -m unittest discover -s tests -p 'test_import.py' -v` passed **31 tests** in the isolated PHP harness during this session. These run the actual `Licenser\Api\Import` class with WordPress/model doubles, track mutation calls, and cover:

- Plugin/theme metadata, optional initial release creation/update, derived slugs, defaults, aliases, duplicate skip/update, URL/type/status validation, and zero-write dry runs.
- Product ID/slug/default resolution; package identifier/label duplicates; lifetime/unlimited parsing; preservation of package identifiers through label updates; rejected invalid supplied relationships.
- Generic, EDD SL, Appsero, Freemius, and automatic license mappings; explicit column-map overrides/Ignore; package-label lookup; customer metadata; `source_id`; expiry; domains; report-only formula neutralization; cross-product duplicate guards; intra-CSV simulation.
- 2 MB / 1,000-row bounds, BOM/quoted/multiline CSV, normalized duplicate or blank headers, row-width errors, invalid request/mapping values, only-explicit-false writes, denied non-admin permissions, and simulated persistence/partial-write failures.

The final mandatory checks passed: PHP syntax (including direct lint of the three changed templates), `node --check assets/js/admin.js`, PHPCS **0 errors / 0 warnings** across 40 PHP files, and full Python discovery **46/46 tests** (31 importer + 15 release tests). Release tests verified a reproducible 60-file runtime package; docs remain excluded.

CSV examples were separately parsed with Python's `csv` module: Products **2 rows / 19 columns**, Packages **5 rows / 5 columns**, Licenses **5 rows / 12 columns**, with unique headers and consistent row widths. The suite also verifies the complete Products → Packages → Licenses sample migration, repeat-skip behavior, full explicit UI mappings with blank optional cells, and exact runtime sample/prompt synchronization with the GitHub documentation.

The requested `-maxdepth 4` PHP command does not reach every nested admin template. Also lint the Migration template directly after editing it:

```sh
php -l includes/Admin/templates/migration/migration.php
```

The following scenarios remain **unverified in live WordPress/browser** in this session. Run them only against `http://portal-addonmaster.local/` with disposable demonstration records, then clean up those records:

| Scenario | Required observation |
|---|---|
| Three-step migration | Load and dry-run each sample; confirm no database/transient changes. Actually import Products, then Packages, then Licenses; confirm release/package/customer/domain relationships and deduplicated sites. Repeat in Skip and Update modes. |
| Mapping and errors | Upload/paste CSV, switch entities/presets, change/ignore mappings, inspect first-row samples, and submit malformed rows. Confirm counts/errors and copied report text without formula execution. |
| Navigation and visual behavior | Open product/license Import CSV links, direct `entity`/`tab` links, keyboard tab navigation, mobile layout, empty/error states, confirmation modal, and screen-reader labels. Confirm no browser alerts, console errors, or PHP warnings; check templates for inline script/style. |
| Samples and clipboard | Download all CSVs, load them into the editor, copy the AI prompt, and open GitHub links. Confirm downloads work when documentation is absent from the built ZIP. |
| Access and REST authentication | Verify administrator access with REST nonce; unauthenticated, missing/invalid nonce, and Pro Support Agent requests must be denied. Confirm both menu capability and direct REST boundary. |

Whole-file imports are not transactional. If a release, customer metadata, or domain write fails after the parent record saved, the row report instructs retry with Update. Counts/preview describe completed rows, so review row errors even after an HTTP success. A dry-run of dependencies does not make those products/packages available to a later request.

---

## 5. Historical Verification Record (2026-11-26)

### Static, Plugin Check & WP.org Release Packaging Checks (Post UI/UX Redesign & Feature Pass)
- `php -l` across all first-party PHP files (including `tests/smoke.php`): **PASS** (0 errors).
- `node --check` across all first-party JS files (`assets/js/admin.js`, `addons/WooLite/assets/js/admin.js`, `addons/WooLite/assets/js/scripts.js`): **PASS** (0 errors).
- `phpcs` with [`phpcs.xml`](../phpcs.xml): **PASS** (`0` errors, `0` warnings).
- **WordPress Plugin Check (`--include-experimental` on exact `.github/release-files.txt` 58-file production package):** **PASS** (`0` errors, `0` warnings — `Success: Checks complete. No errors found.`).
- `tests/test_release.py` (15 disposable Git/SVN boundary tests including `.wordpress/` listing asset staging, MIME types, initial launch null-baseline support, immutable tags, and reproducible 58-file ZIP hashes): **15/15 PASS**.
- WP.org & UX static checks: **0** PHP files missing `ABSPATH` guard, **0** inline `<script>`/`<style>` tags in PHP templates, **0** browser `alert()`/`confirm()`/`console.log()` calls in first-party JS, **0** `.DS_Store` files, **0** diffs in `vendor/`.

### Browser QA on `http://portal-addonmaster.local/` (Cupertino × WP-Native Redesign)
Verified across desktop (`1440x900`), tablet (`1024x900` / `1024x768`), and narrow (`768x900`) viewports (**0 PHP notices/warnings/fatals**, **0 Licenser JS console errors**):
- **Dashboard (`page=licenser`):** 5 KPI cards in a balanced single row (Plugins: `4`, Themes: `0`, Active Licenses: `2`, Expired & Inactive: `85`, Active Domains: `86`), 2-column Bento grid (Recent Licenses with single-line key pills + Copy, Recent Domain Activations, Quick Actions, Setup & Server Status with Public REST API Copy & Clear Cache).
- **Plugins & Themes (`page=licenser-plugins`, `page=licenser-themes`):** Enriched catalog with 40×40 icon thumbnails/monograms, latest release version badges, package/license counts, UUID Copy + toast feedback, status filter pills, search, bulk actions, and centered Cupertino empty state on Themes (`Add Your First Theme`). Proportional column widths verified at `1440px` and `1024px`.
- **Product Editor (`page=licenser-plugins&action=edit&id=4`):** Frosted sticky topbar, scroll-spy sidebar + Product Snapshot card (collapses to horizontal pill tabs at `1024px`), UUID Copy, full Release History (`v1.2.2 Stable`, `v1.2.0 Previous` with Edit/Delete icons), all 3 License Packages with live `365 Days • X Sites` summary pills and confirmation-guarded deletion, live Icon/Banner previews, and Public API endpoints.
- **Licenses List & Editor (`page=licenser-licenses`):** Status filter pills (`All 87`, `Active 2`, `Expiring Soon 0`, `Expired 85`, `Inactive 0`), Product filter, Search, `Export CSV`, `Bulk Generate` modal (solid elevated card, Lifetime switch disabling Expiry Override), single-line ellipsis key pills + Copy, human-readable Product & Package names, domain usage meters, clickable `WC #...` order links. License Editor 2-column layout with License Snapshot panel, inline Add Domain, confirmation-guarded Activate/Deactivate domain modal, installed version badges, and inline Add Meta.
- **SDK Studio & API Diagnostics (`page=licenser-sdk-generator`):** Side-by-side 2-column studio with dark-chrome CodeMirror PHP output, Copy PHP Code button, contextual mode help, and Step 1 setup guide (`<code>licenser</code>`). API Diagnostics tab tested live against `GridMaster Pro` Product Update Info returning `HTTP 200` + formatted JSON.
- **Settings & Addons (`page=licenser-settings`, `page=licenser-addons`):** 3 structured Cupertino cards with 24px gaps, Key Generation radio cards + live Sample Key Preview, WooCommerce Multi-Quantity radio cards + tactile iOS switches, clean single-line Public Routes, and Clear Product Cache; modular 2-section Addons & Integrations screen (**Store Integrations** with live confirmation-guarded enable/disable toggle for WooCommerce Lite + Easy Digital Downloads Coming Soon slot, and **SDK & Extensions**).
- **Modular Store Integrations Toggle (`POST /settings/integrations`):** Verified live in a fresh PHP/WordPress bootstrap that disabling `woocommerce` (while WooCommerce plugin remains installed & active) results in **0** `addons/WooLite*` files in `get_included_files()`, `LICENSER_WCLITE_PLUGIN_URL` undefined, `[licenser_wc_manager]` unregistered, and `0` `WooLite` hooks attached — while saving general settings preserves the disabled state and Dashboard/Settings display accurate `Disabled` / `Disabled in Addons` states. Unauthenticated calls return HTTP `401` and unavailable integrations (`edd`) return HTTP `400`.
- **WooCommerce Admin & Customer Portal:** Variation `#189` on Product `#188` renders clean `Licenser Settings` card with `1 Year, 1 Site` pre-selected and zero CSS bleed; Order `#388` displays the **Licenser Licenses** side meta box with status badge, Copy Key, sites counter, and `Manage →` link; Customer My Account (`/?wc_tab=license_manager`) highlights `License Manager`, hides the redundant dashboard greeting, and renders License `#74` (`Load More Anything Pro`, `v1.2.4`, `Lifetime`, `Download v1.2.4`, expandable `Manage Sites`) cleanly even with `package_id = 0`.
- **Fresh PII-Free WP.org Screenshots:** Re-captured `.wordpress/screenshot-1.png`–`screenshot-6.png` matching the redesigned high-contrast WCAG 2.1 AA UI (with Dashboard sample key pills masked to demo tokens) and updated `readme.txt`.
- **Accessibility & Contrast Audit (WCAG 2.1 AA):** Upgraded canvas/card hierarchy (`#eceef2` vs `#ffffff` + `#cfd5df` border), muted text (`#4b5563`, `7.56:1` on white), placeholders (`#59616e`, `6.25:1`), interactive input/button borders (`#858b94`, `3.43:1`), iOS switch tracks (`#787e88` OFF `4.09:1` / `#19873d` ON `4.59:1`), readable `12.5px`–`14px` typography (`0` rules `< 12px`, `0` `outline: none` traps), `:focus-visible` outlines, `prefers-reduced-motion` + `forced-colors` support, modal `Tab`/`Shift+Tab` focus trapping + focus restoration, WAI-ARIA `ArrowLeft`/`ArrowRight` tablist navigation, proper `<nav aria-label>` filter landmarks, accessible checkbox labels, and desktop/mobile `.check-column` alignment (`480px` & `1440px`).

### WordPress Integration & Security Suite (LocalWP PHP + MySQL Socket)
Verified live against `wp_licenser_*` tables (`DB_VERSION = 29`, `licenser_settings`, 4 plugins, 15 packages, 87 licenses, 86 domains) using disposable test records (cleaned up with exact live row counts preserved):
- Public updater `POST /public/products/da399f0f-c005-4b5f-80b8-df3222e79169` returns live GridMaster Pro `1.2.2` metadata (100% `LicenserWP/client` v2.0.1 wire-compatible).
- Unauthenticated requests to all new admin endpoints (`POST /products/releases`, `POST /licenses/bulk`, `POST /settings/clear-cache`, `POST /licenses/{id}/domains`, `POST /licenses/{id}/meta`) blocked with HTTP `401`.
- Full authenticated CRUD verified for independent release create/delete, bulk license creation, manual domain add & status toggle, custom meta creation, cache clear, dashboard stats aggregation, and `WooLite\Shortcode::output()` hydration.
- Final live DB counts verified intact: **4 products, 15 packages, 87 licenses, 86 domains**.
