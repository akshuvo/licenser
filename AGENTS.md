# Licenser — AI Agent Development Guide

This repository owns **Licenser (Free)**, the self-hosted WordPress license & update server plugin bootstrapped by [`licenser.php`](licenser.php) (`https://github.com/akshuvo/licenser`). It powers licensing and automatic updates for AddonMaster products (such as Post Grid Master Pro) on `https://portal.addonmaster.com/`.

Companion repositories and plugins are **separate projects** — never assume a shared Git root:

| Component | Local Path | Git Repo / Role |
|---|---|---|
| **Licenser (Free)** *(this repo)* | `.../addonmaster-portal/app/public/wp-content/plugins/licenser` | `akshuvo/licenser` — core DB tables, REST API, admin UI, `WooLite` addon |
| **Licenser Pro** | `.../addonmaster-portal/app/public/wp-content/plugins/licenser-pro` | `LicenserWP/licenser-pro` — separate extension repo (currently scaffolded) |
| **Paddle Payment Gateway** | `.../addonmaster-portal/app/public/wp-content/plugins/wc-paddle-payment-gateway` | No `.git` root here — WooCommerce Paddle Classic checkout/webhook gateway |
| **Licenser Client SDK** | `https://github.com/LicenserWP/client` (bundled in `gridmaster-pro/licenser/`) | Appsero-forked SDK embedded inside sold plugins/themes (`Licenser\Client`) |
| **GridMaster Sibling Site** | `/Users/akshuvo/Local Sites/grid-master/app/public/wp-content/plugins` | `ajax-filter-posts` + `gridmaster-pro` (primary live consumer of Licenser) |

Local development portal clone: `http://portal-addonmaster.local/` (WP Admin auto-login: `http://portal-addonmaster.local/wp-admin/?localwp_auto_login=1`).

---

## Read Before Changing Code

- [Architecture](docs/architecture.md) — bootstrap, PSR-4 autoloading, 6 custom DB tables (`DB_VERSION = 29`), models, controllers, caching, admin UI, and `WooLite` (HPOS-compatible).
- [Integrations & API Contract](docs/integrations.md) — public & admin REST endpoints, `LicenserWP/client` wire contract, WooCommerce/Paddle order-to-license flow, hooks, and Free/Pro boundary.
- [Development Workflow](docs/development.md) — Git roots/branches, deployment side effects, WordPress.org GitHub Actions auto-deploy (`wporg.yml` + `.wordpress/`), WPCS/PHPCS tooling (`phpcs.xml`), local environment, and coding conventions.
- [Known Issues & Testing](docs/known-issues-and-testing.md) — completed stabilization record, remaining backlog items, automated WPCS/lint commands, and manual/integration verification matrices.

---

## Mandatory Working Rules

1. **Verify Git root, branch, and worktree state first.** Run `git rev-parse --show-toplevel`, `git branch --show-current`, and `git status --short` in each affected plugin (`licenser`, `licenser-pro`, etc.). Never assume one Git operation covers sibling plugins.
2. **Never commit, push, or deploy unless explicitly requested.** Keep changes uncommitted in the working tree for review. **Warning:** pushing to `licenser`'s `deploy` branch triggers [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml), which FTP-deploys directly to `./licenser/`.
3. **Preserve live client SDK wire compatibility.** Installed customer plugins (e.g., `gridmaster-pro` using `Licenser\Client` v2.0.1) depend on exact routes under `/wp-json/licenser/v1/public/` and exact JSON response keys (`success`, `status`, `remaining`, `activation_count`, `activation_limit`, `expiry_days`, `expiry_date`, `title`, and WP updater fields). Never break existing clients without versioned compatibility or coordinated SDK updates.
4. **Maintain strict PSR-4 case matching & WPCS compliance.** `composer.json` maps `Licenser\` to `includes/`. All REST controllers under `includes/Api/` use `namespace Licenser\Api;`. Run `"$HOME/.config/composer/vendor/bin/phpcs"` against [`phpcs.xml`](phpcs.xml) after PHP edits and keep violations at **0 errors / 0 warnings** (and **0 PHP errors / 0 PHP warnings** in WordPress Plugin Check). Never commit dev tools into `licenser/vendor/`.
5. **Follow security & WordPress/WooCommerce standards.** Always:
   - Unslash (`wp_unslash()`) and sanitize/validate input.
   - Use `$wpdb->prepare()` for values and strict allowlists for SQL identifiers (`ORDER BY`, columns, `IN (...)` integer lists).
   - Enforce `current_user_can()` and nonce verification independently on admin/AJAX/REST mutations.
   - Use WooCommerce CRUD order metadata (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`) for HPOS compatibility.
   - Escape all output by context (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
   - Keep real customer license keys, Paddle credentials, FTP secrets, and customer PII out of logs, commits, and docs.
6. **Keep Free/Pro boundaries clean.** Free (`licenser`) owns base tables, core licensing/update endpoints, lifecycle hooks (`licenser_loaded`, `licenser_addons_register`, `licenser_license_*`, `licenser_product_saved`, etc.), and `WooLite`. Pro (`licenser-pro`) must extend Free via those hooks and guard against Free being inactive/missing.
7. **WordPress.org repository & schema standards.**
   - Settings option key is `licenser_settings` (migrated automatically from legacy `lmfwppt_settings` in DB v29); transient cache prefix is `licenser_cache_`.
   - Every PHP file must include an `ABSPATH` direct-access guard; keep all admin JS/CSS in enqueued files (`assets/js/admin.js`, `assets/css/admin.css`) with zero inline `<script>`/`<style>` tags in PHP templates.
   - `licenser_licenses.package_id` stores integer `licenser_license_packages.id`, **not** the string `licenser_license_packages.package_id` UUID.
   - Schema changes must bump `Installer::DB_VERSION` (currently `29`) and remain `dbDelta()`-safe (`Licenser::maybe_upgrade()` auto-runs upgrades on `plugins_loaded`).
   - Keep `readme.txt` `Stable tag` synchronized with `licenser.php` `Version` / `Licenser::VERSION`, keep `.github/release-files.txt` aligned with runtime files, keep WordPress.org listing assets in `.wordpress/`, and maintain `.distignore` for clean WP.org release builds.
   - Use text domain `'licenser'` for all strings.
8. **Verify changes honestly.** Run `php -l`, `node --check`, and `phpcs`, then verify affected flows against `http://portal-addonmaster.local/` (never against live `https://portal.addonmaster.com/`). Report exact commands, test scenarios, and anything left untested.
9. **Update docs as behavior evolves.** Keep `docs/` synchronized whenever routes, DB columns, hooks, Pro boundaries, or known issues change.
