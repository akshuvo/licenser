# Licenser Architecture

> **Source baseline:** 2026-11-26, branch `main` (uncommitted Phase 1–2 stabilization & WP.org prep), plugin version `1.0.8` (`Licenser::VERSION = '1.0.8'`, `Installer::DB_VERSION = 29`), WPCS clean (`0` errors / `0` warnings).

## 1. Bootstrap & Initialization

Entrypoint: [`licenser.php`](../licenser.php).

1. Guards `ABSPATH` and loads Composer autoloader (`vendor/autoload.php`), which also auto-requires [`includes/functions.php`](../includes/functions.php).
2. Global helper `licenser()` runs at file load:
   - Attaches six table name properties onto global `$wpdb` (`$wpdb->licenser_products`, `$wpdb->licenser_product_releases`, `$wpdb->licenser_license_packages`, `$wpdb->licenser_licenses`, `$wpdb->licenser_license_meta`, `$wpdb->licenser_license_domains`).
   - Returns singleton `Licenser::init()`.
3. `Licenser::__construct()`:
   - Defines constants: `LICENSER_VERSION` (`1.0.8`), `LICENSER_FILE`, `LICENSER_PATH`, `LICENSER_URL`, `LICENSER_ASSETS`.
   - Registers activation hook `Licenser::activate()` -> `Licenser\Installer::run()`.
   - Hooks `plugins_loaded` -> `Licenser::init_plugin()`.
   - Hooks `before_woocommerce_init` -> `Licenser::declare_woocommerce_compatibility()` (declares HPOS `custom_order_tables` compatibility).
4. `Licenser::init_plugin()`:
   - Runs `maybe_upgrade()` to execute `Installer::run()` automatically whenever stored `licenser_db_version < Installer::DB_VERSION`.
   - If `is_admin()`: instantiates `new Licenser\Admin()`.
   - Always: instantiates `new Licenser\Api()` (hooks `rest_api_init`).
   - Always: initializes `Licenser\Controllers\Addons::instance()` (instantiates `\Licenser\Addons\Wc_Lite` and fires `licenser_addons_register`).
   - Fires `do_action( 'licenser_loaded' )`.

## 2. PSR-4 Autoloading & Directory Layout

Defined in [`composer.json`](../composer.json):

| Namespace Prefix | Directory | Notes |
|---|---|---|
| `Licenser\` | `includes/` | Core (`Admin`, `Api`, `Installer`), `Admin\*`, `Api\*` (all REST controllers use `namespace Licenser\Api;`) |
| `Licenser\Controllers\` | `includes/controllers/` | Business logic & abstract `RestController` |
| `Licenser\Models\` | `includes/models/` | Direct `$wpdb` persistence classes with strict SQL column/order allowlists |
| `Licenser\Traits\` | `includes/traits/` | `SingletonTrait` (`new static()`), `SingletonTraitSelf` (`new self()`) |
| `Licenser\Addons\` | `addons/` | Built-in `Wc_Lite` WooCommerce integration |

## 3. Database Schema

Managed by [`includes/Installer.php`](../includes/Installer.php) via `dbDelta()` whenever option `licenser_db_version` is `< 29` (auto-runs on activation and on plugin upgrade via `Licenser::maybe_upgrade()`). DB v29 also automatically migrates legacy `lmfwppt_settings` -> `licenser_settings` and clears legacy `lwp_cache_*` transients, and narrows indexed string columns (`uuid`, `package_id`, `license_key`, `meta_key`) to `varchar(191)` for cross-MySQL `utf8mb4` index safety.

### `{$prefix}licenser_products`
Stores managed plugins/themes.
- Key columns: `id` (PK), `name`, `slug`, `uuid` (`UNIQUE KEY`, indexed — public API identifier), `product_type` (`plugin` | `theme`), `tested`, `requires`, `requires_php`, `banners` (`text` JSON `{"low":"...","high":"..."}` — upgraded from `varchar(250)` in DB v28), `description`, `author_name`, `homepage_url`, `demo_url`, `icon_url`, `created_by`, `status` (`active` | `trash` | `draft`), `dated`.

### `{$prefix}licenser_product_releases`
Versioned release records for each product.
- Key columns: `id` (PK), `product_id` (indexed), `version`, `changelog`, `file_name`, `download_link`, `release_date`.
- Latest "stable" release is selected via `ORDER BY id DESC, release_date DESC LIMIT 1`.

### `{$prefix}licenser_license_packages`
Pricing/tier templates attached to a product (e.g., 1-site 1-year, unlimited lifetime).
- Key columns: `id` (PK), `product_id` (indexed), `label`, `package_id` (`UNIQUE KEY` UUID string), `update_period` (days; `0` = lifetime), `domain_limit` (`0` = unlimited).

### `{$prefix}licenser_licenses`
Issued license keys.
- Key columns: `id` (PK), `status` (`tinyint(1)`, `1` = active), `license_key` (indexed), `product_id` (indexed), `package_id` (indexed — stores integer `licenser_license_packages.id`, **not** the string UUID), `source` (e.g., `'wc'`), `source_id` (indexed — e.g., WooCommerce order ID), `end_date` (`datetime`, `NULL` for lifetime), `is_lifetime` (`tinyint(1)`), `domain_limit` (`int`, `0` = unlimited), `dated`.

### `{$prefix}licenser_license_meta`
Arbitrary key/value metadata per license.
- Key columns: `id` (PK), `license_id` (indexed), `meta_key` (indexed), `meta_value` (`longtext`).
- Written during downloads as `installed_version_{domain_id}` -> `$release->version`.

### `{$prefix}licenser_license_domains`
Domains activated against a license.
- Key columns: `id` (PK), `license_id` (indexed), `domain` (normalized via `licenser_get_clean_url()`: lowercases host, strips scheme, `www.`, trailing slash; keeps `host` + `path`), `status` (`tinyint(1)`, default `1`), `dated`.

## 4. Models & Controllers

### Models (`includes/models/`)
All models use `Licenser\Traits\SingletonTraitSelf` (`::instance()`) and allowlist SQL columns / `ORDER BY` identifiers:
- [`Product.php`](../includes/models/Product.php) — `get_types()`, `get()`, `get_all()`, `count()`, `create()` (upsert product + optional new release + upsert packages; sanitizes description with `wp_kses_post()` and banners URLs with `esc_url_raw()`), `clear_cache()`, `delete()`, `update_status()`. Uses 1-hour transient caching (`lwp_cache_product_*`), invalidated on product, release, and package mutations.
- [`ProductRelease.php`](../includes/models/ProductRelease.php) — `get()`, `get_stable()`, `get_all()`, `create()` (clears parent product cache).
- [`LicensePackage.php`](../includes/models/LicensePackage.php) — `get()`, `get_all()`, `create()`, `delete()` (clears parent product cache).
- [`License.php`](../includes/models/License.php) — `get()`, `get_all()`, `count()`, `create()` (stores `NULL` for `end_date` on lifetime/empty date), `generate_key()`, `delete()` (cascades domains & meta), `get_domains()` (supports `status` filter), `delete_domains()`, `delete_domain()`, `domain_exists()`, `get_domain()`, `add_domain()`.
- [`License_Meta.php`](../includes/models/License_Meta.php) — `get_value()`, `get_all()`, `update()`, `delete()`, `delete_by_id()`, `delete_all()`.
- [`Settings.php`](../includes/models/Settings.php) — allowlists and sanitizes settings stored in `wp_options` under `licenser_settings` (`licenser_sanitize_settings` filter).

### Controllers (`includes/controllers/`)
- [`RestController.php`](../includes/controllers/RestController.php) — extends `WP_REST_Controller`, sets `$namespace = 'licenser/v1'`, defaults all permission callbacks to `current_user_can( 'manage_options' )`.
- [`License.php`](../includes/controllers/License.php) — `check()` (validates key, status, product match, expiry, inactive domain, and optional domain limit), `check_public()`, `activate()` (enforces `domain_limit`, idempotent on active domain), `refresh()` (builds public status payload with integer `expiry_days` and non-negative `remaining`), `deactivate()` (validates ownership and deletes the domain row to free the slot).
- [`Product.php`](../includes/controllers/Product.php) — `download()` (validates license + product ownership, records `installed_version_{domain_id}`, validates remote URL or local path within `ABSPATH`, streams local ZIP or redirects remote URL; returns `WP_Error` with proper HTTP 4xx status on failures).
- [`Addons.php`](../includes/controllers/Addons.php) — singleton loader that instantiates `\Licenser\Addons\Wc_Lite` and fires `licenser_addons_register`.

## 5. Admin UI & Assets

- [`includes/Admin.php`](../includes/Admin.php) — instantiates `Admin\Menu`, adds plugin action link, enqueues `wp_enqueue_media()`, `jquery-ui-datepicker`, `assets/css/admin.css`, and `assets/js/admin.js` **strictly on Licenser admin screens** (`strpos( $screen->id, 'licenser' ) !== false`), and CodeMirror strictly on `licenser-sdk-generator`. Localizes global `Licenser` (`rest_url`, `root`, `nonce`, `ajaxurl`, `admin_url`, `media_state_nonce`, `sdk_api_url`, `i18n`). All admin JS/CSS lives in enqueued assets (zero inline `<script>`/`<style>` tags in PHP templates; product editor uses HTML5 `<template>` + `data-packages`).
- [`includes/Admin/Menu.php`](../includes/Admin/Menu.php) — registers top-level menu `licenser` (shows a `PRO` badge if `defined('LICENSER_PRO_VERSION')`) and submenus:
  - `licenser` -> `templates/dashboard/dashboard.php` (live plugin, theme, and license counts)
  - `licenser-plugins` -> `templates/products/{list,new}.php` (`?action=new|edit&id=`)
  - `licenser-themes` -> `templates/products/{list,new}.php`
  - `licenser-licenses` -> `templates/licenses/{list,new}.php`
  - `licenser-settings` -> `templates/settings/settings.php`
  - `licenser-sdk-generator` -> `templates/tools/tools.php`
  - `licenser-addons` -> `templates/addons/addons.php`
- List tables: [`ProductsListTable.php`](../includes/Admin/ProductsListTable.php) and [`LicensesListTable.php`](../includes/Admin/LicensesListTable.php) extend `WP_List_Table` with allowlisted SQL sorting, real `total_items` pagination, `column_end_date` / `column_domain_limit` renderers, and context-escaped output.

## 6. Uploads, Options & Caching

- **Settings (`licenser_settings`):** `license_code_prefix`, `license_generate_method` (`microtime` | `wp_generate` | `uuid`), `license_code_character_limit` (`>= 8`), `hide_wclm_info_from_cart` (`"on"` | `""`), `hide_wclm_info_from_ordermeta` (`"on"` | `""`). Legacy `lmfwppt_settings` is automatically migrated by `Installer` v29.
- **Release ZIP Upload Redirect:** Clicking `.trigger_media_frame` sends nonce-protected AJAX `licenser_media_frame_state` (`open`/`close`, requires `manage_options` + `licenser_media_frame_state` nonce). When `'open'` and user has `manage_options`, uploads route to `wp-content/uploads/license-manager`.
- **Transient Cache (`includes/functions.php`):** Prefix `licenser_cache_`. `Models\Product::get()` caches rows for 1 hour (`HOUR_IN_SECONDS`) keyed with `md5(wp_json_encode($args))` and invalidates via prepared `LIKE` queries in `licenser_delete_cache()`.

## 7. Built-in WooCommerce Addon (`addons/Wc_Lite`)

- [`addons/Wc_Lite.php`](../addons/Wc_Lite.php) — defines `LICENSER_WCLITE_PLUGIN_URL` and `LICENSER_WCLITE_PLUGIN_PATH`. Loads `Admin_Handler` in admin, `Order_Handler` on frontend, and hooks `woocommerce_order_status_changed` (priority `150`) and `woocommerce_order_item_meta_end` (priority `20`, 4 args).
- [`Admin_Handler.php`](../addons/Wc_Lite/Admin_Handler.php) — adds "Licenser" product data tab (simple products) and variation fields (variable products), enqueues & localizes `licenser-wc-admin` strictly on WooCommerce `product` edit screens (`post.php`/`post-new.php`).
- [`Order_Handler.php`](../addons/Wc_Lite/Order_Handler.php) — HPOS-compatible order metadata (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`), cart/checkout item metadata, multi-item order license generation, order email key/download rendering (HTML + plain text, with DB fallback for historical orders), My Account tab (`?wc_tab=license_manager`), and `[licenser_wc_manager]` shortcode.
- [`Shortcode.php`](../addons/Wc_Lite/Shortcode.php) — returns buffered HTML rendering the customer's licenses table, download link, and read-only activated domains modal.
