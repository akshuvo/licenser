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
   - Always: initializes `Licenser\Controllers\Addons::instance()` (evaluates registered integrations such as `woocommerce` and conditionally instantiates only enabled integrations whose host plugin is active — disabled integrations never load their PHP files — then fires `licenser_addons_register`).
   - Fires `do_action( 'licenser_loaded' )`.

## 2. PSR-4 Autoloading & Directory Layout

Defined in [`composer.json`](../composer.json):

| Namespace Prefix | Directory | Notes |
|---|---|---|
| `Licenser\` | `includes/` | Core (`Admin`, `Api`, `Installer`), `Admin\*`, `Api\*` (all REST controllers use `namespace Licenser\Api;`) |
| `Licenser\Controllers\` | `includes/controllers/` | Business logic & abstract `RestController` |
| `Licenser\Models\` | `includes/models/` | Direct `$wpdb` persistence classes with strict SQL column/order allowlists |
| `Licenser\Traits\` | `includes/traits/` | `SingletonTrait` (`new static()`), `SingletonTraitSelf` (`new self()`) |
| `Licenser\Addons\` | `addons/` | Built-in `WooLite` WooCommerce integration |

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
- [`Product.php`](../includes/models/Product.php) — `get_types()`, `get()`, `get_all()` (supports `search` and `status = 'all'`), `count()`, `get_dependency_counts()` (batch release/package/license counts), `create()` (upsert product + optional new release + upsert packages; sanitizes description with `wp_kses_post()` and banners URLs with `esc_url_raw()`), `clear_cache()`, `delete( $id, $cascade )`, `update_status()`. Uses 1-hour transient caching (`licenser_cache_product_*`), invalidated on product, release, and package mutations.
- [`ProductRelease.php`](../includes/models/ProductRelease.php) — `get()`, `get_stable()`, `get_all()`, `create()`, `delete()` (clears parent product cache).
- [`LicensePackage.php`](../includes/models/LicensePackage.php) — `get()`, `get_all()`, `create()`, `delete()` (clears parent product cache).
- [`License.php`](../includes/models/License.php) — `get()`, `get_all()` (supports `search`, `license_status`, array `source` & `source_id`), `count()`, `get_computed_status()`, `get_dashboard_stats()`, `get_active_domain_counts()`, `get_recent_domains()`, `create()`, `generate_key()`, `delete()` (cascades domains & meta), `bulk_update_status()`, `bulk_delete()`, `get_domains()`, `get_domain_by_id()`, `update_domain_status()`, `delete_domains()`, `delete_domain()`, `domain_exists()`, `get_domain()`, `add_domain()`.
- [`License_Meta.php`](../includes/models/License_Meta.php) — `get_value()`, `get_row()`, `get_installed_versions_for_license()`, `get_all()`, `update()`, `delete()`, `delete_by_id()`, `delete_all()`.
- [`Settings.php`](../includes/models/Settings.php) — `get_defaults()`, `get_all()`, `get()`, `update()` (preserving/sanitizing `integrations`), and `set_integration_enabled()` storing settings in `wp_options` under `licenser_settings` (`licenser_sanitize_settings` filter).

### Controllers (`includes/controllers/`)
- [`RestController.php`](../includes/controllers/RestController.php) — extends `WP_REST_Controller`, sets `$namespace = 'licenser/v1'`, defaults all permission callbacks to `current_user_can( 'manage_options' )`.
- [`License.php`](../includes/controllers/License.php) — `check()` (validates key, status, product match, expiry, inactive domain, and optional domain limit), `check_public()`, `activate()` (enforces `domain_limit`, idempotent on active domain), `refresh()` (builds public status payload with integer `expiry_days` and non-negative `remaining`), `deactivate()` (validates ownership and deletes the domain row to free the slot).
- [`Product.php`](../includes/controllers/Product.php) — `download()` (validates license + product ownership, records `installed_version_{domain_id}`, validates remote URL or local path within `ABSPATH`, streams local ZIP or redirects remote URL; returns `WP_Error` with proper HTTP 4xx status on failures).
- [`Addons.php`](../includes/controllers/Addons.php) — singleton integrations registry and conditional loader (`get_integrations()`, `is_enabled()`, `is_dependency_active()`, `is_active()`, `is_loaded()`). Filterable via `licenser_integrations` so future integrations (e.g., Easy Digital Downloads) share the same toggle & lazy PSR-4 loading mechanism. Disabled integrations are never instantiated and zero files under `addons/` are included. Fires `licenser_addons_register`.

## 5. Admin UI & Assets

- **Unified Design System (`assets/css/admin.css` & `assets/js/admin.js`):** Strictly scoped under `.licenser-app` / `body[class*="licenser"]` (Cupertino × WordPress-Native Hybrid respecting `--wp-admin-theme-color`). Zero legacy `lmfwppt-*`/`lmwppt-*`/`lwp-*`/`twp-*` template classes, zero inline `<script>`/`<style>` tags in PHP templates, zero browser `alert()`/`confirm()` popups (replaced by accessible confirmation modals, bottom-right frosted toasts, and universal clipboard copy buttons).
- [`includes/Admin.php`](../includes/Admin.php) — instantiates `Admin\Menu`, adds plugin action links, registers `admin_post_licenser_export_licenses` (nonce- & capability-verified, formula-safe CSV export), enqueues `wp_enqueue_media()`, `jquery-ui-datepicker`, `assets/css/admin.css`, and `assets/js/admin.js` **strictly on Licenser admin screens** (`strpos( $screen->id, 'licenser' ) !== false`), and CodeMirror strictly on `licenser-sdk-generator`.
- [`includes/Admin/Menu.php`](../includes/Admin/Menu.php) — registers top-level menu `licenser`, nonce-verified `load-*` bulk action redirect handlers for Products and Licenses, and submenus:
  - `licenser` -> `templates/dashboard/dashboard.php` (5 KPI cards, Recent Licenses, Recent Domain Activations, Quick Actions, Setup & Server Status with Public API Copy & Clear Cache)
  - `licenser-plugins` / `licenser-themes` -> `templates/products/{list,new}.php` (enriched list table with icon thumbnails, release versions, package/license counts, UUID copy, search, status filters, safe-delete/archive modal, empty state; editor with scroll-spy nav, full release history CRUD, package cards with live summaries, icon/banner live previews, and public API URLs)
  - `licenser-licenses` -> `templates/licenses/{list,new}.php` (status filter pills including `Expiring Soon`, product filter, search, Bulk Generate modal, CSV export, human-readable product/package names, domain usage meters, clickable WC order links; 2-column editor with License Snapshot panel, manual domain add/toggle + installed version badges, and custom meta creation)
  - `licenser-settings` -> `templates/settings/settings.php` (tabbed Cupertino settings: `General` with live sample key preview, dedicated `WooCommerce` tab with status indicator and quick-enable callout when disabled in Addons, and `API & Maintenance` tab with Public Routes & Clear Product Cache)
  - `licenser-sdk-generator` -> `templates/tools/tools.php` (2-column SDK Studio with live CodeMirror + Copy + contextual mode help, plus built-in API & License Diagnostics Tester)
  - `licenser-addons` -> `templates/addons/addons.php` (modular Store Integrations section with live enable/disable toggles for WooCommerce Lite and future integrations like Easy Digital Downloads, plus SDK & Extensions cards)
- List tables: [`ProductsListTable.php`](../includes/Admin/ProductsListTable.php) and [`LicensesListTable.php`](../includes/Admin/LicensesListTable.php) extend `WP_List_Table` with batch-hydrated lookups, allowlisted SQL sorting, search/status/product filtering, and bulk actions.

## 6. Uploads, Options & Caching

- **Settings (`licenser_settings`):** `license_code_prefix`, `license_generate_method` (`microtime` | `wp_generate` | `uuid`), `license_code_character_limit` (`8`–`64`), `hide_wclm_info_from_cart` (`"on"` | `""`), `hide_wclm_info_from_ordermeta` (`"on"` | `""`), `wc_quantity_mode` (`separate_keys` | `multiply_domains` | `single_legacy`), `allow_customer_domain_deactivation` (`"on"` | `""`), `auto_suspend_on_refund` (`"on"` | `""`), and `integrations` (`['woocommerce' => 'on'|'off', ...]`). Legacy `lmfwppt_settings` is automatically migrated by `Installer` v29.
- **Release ZIP Upload Redirect:** Clicking `.trigger_media_frame[data-media-scope="release"]` sends nonce-protected AJAX `licenser_media_frame_state` (`open`/`close`, requires `manage_options` + `licenser_media_frame_state` nonce). When `'open'` and user has `manage_options`, uploads route to `wp-content/uploads/license-manager`.
- **Transient Cache (`includes/functions.php`):** Prefix `licenser_cache_`. `Models\Product::get()` caches rows for 1 hour (`HOUR_IN_SECONDS`) keyed with `md5(wp_json_encode($args))` and invalidates via prepared `LIKE` queries in `licenser_delete_cache()` (also manually clearable via `POST /licenser/v1/settings/clear-cache`).

## 7. Built-in WooCommerce Addon (`addons/WooLite`)

- [`addons/WooLite.php`](../addons/WooLite.php) — defines `LICENSER_WCLITE_PLUGIN_URL` and `LICENSER_WCLITE_PLUGIN_PATH`. Loads `Admin_Handler` in admin, `Order_Handler` on frontend, hooks `woocommerce_order_status_changed` (priority `150`, 3 args for issuance, refund/cancel auto-suspend, and reactivation), `woocommerce_order_item_meta_end` (priority `20`, 4 args), and `wp_ajax_licenser_customer_deactivate_domain` for logged-in customer self-service domain deactivation.
- [`Admin_Handler.php`](../addons/WooLite/Admin_Handler.php) — adds "Licenser" product data tab (simple products), polished variation settings cards (`addons/WooLite/assets/css/admin.css` + `admin.js` scoped strictly to WC product & order screens), and an HPOS + legacy CPT compatible **Licenser Licenses** side meta box on WooCommerce Edit Order screens.
- [`Order_Handler.php`](../addons/WooLite/Order_Handler.php) — HPOS-compatible order metadata (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`), configurable multi-quantity licensing (`separate_keys`, `multiply_domains`, `single_legacy`), auto-suspend/restore on refund/cancel, multi-key order email rendering (HTML + plain text), My Account active tab routing (`?wc_tab=license_manager`), and nonce + customer-order-ownership-verified self-service domain deactivation AJAX.
- [`Shortcode.php`](../addons/WooLite/Shortcode.php) — enqueues `addons/WooLite/assets/css/styles.css` & `assets/js/scripts.js` and renders responsive Cupertino license cards with one-click key copy, release downloads, installed versions, and collapsible self-service Activated Sites management (resolving products directly by `product_id` so licenses without a `package_id` still display properly).
