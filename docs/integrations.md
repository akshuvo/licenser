# Integrations, REST API & Client SDK Contract

## 1. REST API Surface (`licenser/v1`)

All routes are registered in [`includes/Api.php`](../includes/Api.php) on `rest_api_init` under namespace `licenser/v1` (controllers in `includes/Api/` under `namespace Licenser\Api;`).

### Public Endpoints (Unauthenticated — Customer Plugin/Theme SDKs)

Handled by [`includes/Api/Public_Apis.php`](../includes/Api/Public_Apis.php) (`permission_callback => '__return_true'`).

| Method & Route | Purpose | Key Request Params | Response Shape |
|---|---|---|---|
| `POST /wp-json/licenser/v1/public/products/{uuid}` | Update check & WP `plugins_api` info | `license_key`, `url`, `version`, `name`, `slug`, `basename`, `client` | WP update object: `id`, `name`, `slug`, `plugin`/`theme`, `url`, `icons` (`1x`,`2x`), `banners` (`low`,`high`), `tested`, `requires_php`, `requires`, `sections` (`description`,`changelog`), `new_version`, `last_updated`, `package`, `download_link`, `author` |
| `POST /wp-json/licenser/v1/public/license/{uuid}/{action}` | `action` enum: `check`, `activate`, `deactivate` | `license_key`, `url`, `is_local`, `version`, `client` | `check`/`activate`: `{ success, status, remaining, activation_count, activation_limit, expiry_days, expiry_date, title }`<br>`deactivate`: `{ success: true, message }`<br>Error: `{ success: false, error }` |
| `GET /wp-json/licenser/v1/public/products/download/{uuid}` | License-authenticated ZIP download for WP updater & customer portal | Query: `license_key` (required), `refurl` (optional calling site URL) | Streams local ZIP (`application/octet-stream`) or redirects validated external URL; returns `WP_Error` (HTTP `400`/`403`/`404`) on missing/invalid/expired/wrong-product license or missing file |

> **Contract Stability:** `package` and `download_link` returned by `POST /public/products/{uuid}` point to `GET /public/products/download/{uuid}?license_key={key}&refurl={url}` (generated via `licenser_product_download_url()`). `expiry_days` is an integer for expiring licenses and `false` for lifetime licenses; `remaining` is always `>= 0` (`0` when `activation_limit === 0` unlimited). Changing query parameter names or JSON fields will break installed customer plugins.

### Admin Endpoints (`manage_options` via `RestController`)

| Method & Route | Controller | Behavior |
|---|---|---|
| `GET /licenser/v1/products` | `Api/Products.php` | Lists Licenser products (`per_page`, `page`, `product_type`, `status`, `inc_packages`) |
| `POST /licenser/v1/products` | `Api/Products.php` | Creates/updates Licenser product, optional release, and packages; returns fresh product object |
| `POST /licenser/v1/products/{id}/status` | `Api/Products.php` | Updates product status (`active` \| `draft` \| `trash`) for safe archiving/restoration |
| `DELETE /licenser/v1/products/{id}` | `Api/Products.php` | Deletes product row (optional `cascade=true` removes linked releases & packages) and clears product transient caches |
| `POST /licenser/v1/products/releases` | `Api/Products.php` | Creates or updates a single product release independently and returns refreshed release history |
| `DELETE /licenser/v1/products/releases/{id}` | `Api/Products.php` | Deletes a single product release by integer `id` and clears parent product cache |
| `GET /licenser/v1/products/packages?product_id={id}` | `Api/Products.php` | Lists all packages for a product (used by License form & `WooLite` product editor) |
| `POST /licenser/v1/products/packages` | `Api/Products.php` | Creates/updates a package and returns the saved package object |
| `DELETE /licenser/v1/products/packages/{id}` | `Api/Products.php` | Deletes a package by integer `id` and clears parent product cache |
| `GET /licenser/v1/licenses` | `Api/Licenses.php` | Lists Licenser licenses (`per_page`, `page`, `product_id`, `source`, `inc_domains`) |
| `GET /licenser/v1/licenses/{id}` | `Api/Licenses.php` | Returns a single license record (`404` if not found) |
| `POST /licenser/v1/licenses` | `Api/Licenses.php` | Creates/updates a license; returns `License::get($id)` |
| `POST /licenser/v1/licenses/bulk` | `Api/Licenses.php` | Bulk-generates 1–100 license keys sharing product/package/limit/expiry/source settings |
| `POST /licenser/v1/licenses/generate-key` | `Api/Licenses.php` | Generates and returns a formatted license key string |
| `DELETE /licenser/v1/licenses/{id}` | `Api/Licenses.php` | Deletes license row and cascades its domains & metadata |
| `POST /licenser/v1/licenses/{id}/domains` | `Api/Licenses.php` | Manually normalizes and adds an activated domain row to a license |
| `POST /licenser/v1/licenses/domains/{id}` | `Api/Licenses.php` | Toggles an activated domain row `status` (`0` \| `1`) |
| `DELETE /licenser/v1/licenses/domains/{id}` | `Api/Licenses.php` | Deletes an activated domain row by integer `id` |
| `POST /licenser/v1/licenses/{id}/meta` | `Api/Licenses.php` | Adds or updates a custom key/value metadata entry for a license |
| `DELETE /licenser/v1/licenses/meta/{id}` | `Api/Licenses.php` | Deletes a license meta row by integer `id` |
| `GET /licenser/v1/settings` | `Api/Settings.php` | Returns saved Licenser settings array merged with defaults |
| `POST /licenser/v1/settings` | `Api/Settings.php` | Sanitizes & saves settings to `licenser_settings` option; returns saved array |
| `POST /licenser/v1/settings/clear-cache` | `Api/Settings.php` | Clears all `licenser_cache_*` transients |
| `POST /licenser/v1/settings/integrations` | `Api/Settings.php` | Toggles a registered store integration (`integration`, `enabled`) in `licenser_settings['integrations']` |

---

## 2. `LicenserWP/client` SDK Contract

Repository: `https://github.com/LicenserWP/client` (forked from Appsero Client; namespace `Licenser`, `Client::$version = '2.0.1'`).

### Real-World Reference Consumer (`gridmaster-pro`)
Inspected at `/Users/akshuvo/Local Sites/grid-master/app/public/wp-content/plugins/gridmaster-pro/gridmaster.php`:
```php
$client = new Licenser\Client(
	'da399f0f-c005-4b5f-80b8-df3222e79169', // Product UUID in licenser_products
	'Post Grid Master Pro',
	__FILE__,
	'https://portal.addonmaster.com'        // Note: NO trailing slash
);
$gm_license = $client->license();
$gm_license->add_settings_page( [ 'type' => 'section' ] );
$client->updater()->init( $client );
```

### How the Client SDK Calls This Server
1. **Endpoint Construction (`Client::endpoint()`):** `$this->api_url . '/wp-json/licenser/v1/public'` -> `trailingslashit()`, filterable via `licenser_endpoint`. All requests use `wp_remote_post()` with `'client' => '2.0.1'` merged into the body. The SDK Generator (`templates/tools/tools.php`) emits `untrailingslashit( licenser_api_url() )` and `'type' => 'submenu'` to match `Client` and `License::admin_menu()`.
2. **License Storage (`License`):**
   - Stores option `licenser_{md5($slug)}_manage_license` containing: `key`, `status` (`'activate'` | `'deactivate'`), `remaining`, `activation_count`, `activation_limit`, `expiry_days`, `expiry_date`, `title`.
   - Daily WP-Cron `{slug}_license_check_event` calls `POST /public/license/{uuid}/check`.
   - On `check` response: if `$response['success']` is truthy -> marks local status `'activate'`; otherwise -> marks `'deactivate'` and `expiry_days = 0`.
   - Legacy-migrated sites (migrated from `<slug>_key` without calling `/activate`) still pass `check` as long as the license is active, matches the product UUID, is not expired, and the domain isn't explicitly disabled.
3. **Automatic Updates (`Updater`):**
   - Hooks `pre_set_site_transient_update_plugins` + `plugins_api` (or `pre_set_site_transient_update_themes`).
   - POSTs to `/public/products/{uuid}`, expects JSON with `slug` and `new_version`.
   - Caches version object in transient `licenser_{md5($slug)}_version_info` for 3 hours (bypassed on `update-core.php`).
   - WordPress downloads the ZIP directly from the returned `package` URL.
4. **Insights (`Insights`, optional — not enabled in GridMaster Pro):**
   - Sends opt-in weekly telemetry to route `track` and uninstall feedback routes. **Not implemented on the Licenser server yet.**

---

## 3. Portal Checkout-to-License Flow (WooCommerce + Paddle)

On `portal.addonmaster.com` / `portal-addonmaster.local`:

```text
Customer Checkout (WooCommerce)
  └─▶ wc-paddle-payment-gateway (Ppgw_Gateway::process_payment)
        └─▶ Ppgw_API::get_pay_url_for_order() (Paddle Classic Pay Link API)
              └─▶ Customer pays in Paddle overlay
                    └─▶ Paddle webhook -> /index.php/wc-api/paddle_complete?order_id={id}
                          └─▶ Ppgw_Gateway::on_paddle_payment_webhook_response()
                                └─▶ Verifies RSA-SHA1 p_signature -> $order->payment_complete()
                                      └─▶ Order status -> processing / completed
                                            └─▶ Licenser WooLite (woocommerce_order_status_changed, prio 150)
                                                  └─▶ Order_Handler::generate_license($order_id)
                                                        ├─▶ Creates row in licenser_licenses (source='wc', source_id=$order_id)
                                                        ├─▶ Saves HPOS-safe order meta (license_generated_item_id/key_{item_id}, is_license_order)
                                                        └─▶ Triggers WC customer_invoice email (includes license key + download link)
```

Portal child theme glue (`/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/themes/addonmaster/functions.php`):
- Injects Paddle card badges on checkout (`li.payment_method_paddle label`).
- Captures `?coupon_code=` into WooCommerce session and auto-applies before cart table.
- Rounds non-`fixed_cart` coupon discounts upward via `ceil()`.

---

## 4. WordPress Hooks & Extension Points

### Filters in Licenser (Free)
| Hook | Location | Purpose |
|---|---|---|
| `licenser_api_url` | `includes/functions.php` | Filter base URL used in download URLs & SDK Generator (defaults to `home_url('/')`) |
| `licenser_product_types` | `includes/models/Product.php` | Add/modify product types (`plugin`, `theme`) |
| `licenser_refresh_license_public_response` | `includes/controllers/License.php` | Modify public `check`/`activate` JSON payload (`$response, $license`) |
| `licenser_should_enforce_domain_limit` | `includes/controllers/License.php` | Override domain limit enforcement (`$enforce, $license, $args`) — e.g., Pro local/staging domain exemption |
| `licenser_active_domain_count` | `includes/controllers/License.php`, `includes/Admin/templates/licenses/new.php`, `addons/WooLite/Shortcode.php`, `addons/WooLite/Order_Handler.php` | Filter counted active domains for a single license (`$count, $license, $context, $args`) — e.g., exclude staging domains |
| `licenser_active_domain_counts` | `includes/models/License.php` | Filter batch active domain counts keyed by license ID (`$counts, $ids`) |
| `licenser_sanitize_settings` | `includes/models/Settings.php` | Extend settings allowlist/sanitization (`$sanitized, $data`) |
| `licenser_settings_tabs` | `includes/Admin/templates/settings/settings.php` | Register additional tabs on Licenser Settings (`$tabs`) |
| `licenser_integrations` | `includes/controllers/Addons.php` | Register or customize toggleable store integrations (`$integrations`, e.g., WooCommerce, Easy Digital Downloads) |
| `licenser_wclite_should_generate_license_for_item` | `addons/WooLite/Order_Handler.php` | Short-circuit new key issuance per WC order item (`$should_generate, $item, $item_id, $order`) — e.g., Pro renewals/upgrades |
| `licenser_wclite_products_page_url` | `addons/WooLite/Shortcode.php` | Filter "Browse products" button URL in empty My Account table |
| `licenser_allowed_download_dirs` | `includes/controllers/Product.php` | Filter local directories permitted for release ZIP streaming (`$dirs`) |

### Actions in Licenser (Free)
| Hook | Location | Arguments |
|---|---|---|
| `licenser_loaded` | `licenser.php` | None — fires after core initialization (`licenser-pro` bootstrap entrypoint) |
| `licenser_addons_register` | `includes/controllers/Addons.php` | `$addons` — register Pro/custom addons |
| `licenser_product_saved` | `includes/models/Product.php` | `$product_id, $data` |
| `licenser_license_created` | `includes/models/License.php` | `$license_id, $data` |
| `licenser_license_updated` | `includes/models/License.php` | `$license_id, $data` |
| `licenser_license_deleted` | `includes/models/License.php` | `$license_id` |
| `licenser_license_activated` | `includes/controllers/License.php` | `$license, $args` |
| `licenser_license_checked` | `includes/controllers/License.php` | `$license, $args` |
| `licenser_license_check_failed` | `includes/controllers/License.php` | `$error, $args, $action` (`check` \| `activate`) |
| `licenser_license_deactivated` | `includes/controllers/License.php`, `addons/WooLite/Order_Handler.php` | `$license, $url` |
| `licenser_before_product_download` | `includes/controllers/Product.php` | `$release, $license, $args` |
| `licenser_product_download_failed` | `includes/controllers/Product.php` | `$error, $args` |
| `licenser_admin_menu` | `includes/Admin/Menu.php` | `$parent_slug, $capability` |
| `licenser_settings_tab_panels` | `includes/Admin/templates/settings/settings.php` | `$active_tab, $settings` |
| `licenser_license_domain_row_badges` | `includes/Admin/templates/licenses/new.php` | `$domain_item, $license` |
| `licenser_license_edit_after_cards` | `includes/Admin/templates/licenses/new.php` | `$license, $license_id` |
| `licenser_license_edit_sidebar` | `includes/Admin/templates/licenses/new.php` | `$license, $license_id` |
| `licenser_portal_license_actions` | `addons/WooLite/Shortcode.php` | `$license, $product, $item` |
| `licenser_portal_domain_badges` | `addons/WooLite/Shortcode.php` | `$domain, $license` |

### Free ↔ `licenser-pro` Boundary Guidelines
`licenser-pro` (`LicenserWP/licenser-pro`) is the companion Pro extension (`licenser-pro.php`, `LicenserPro\` PSR-4 namespace, `LICENSER_PRO_*` constants, `licenser_pro_settings` option, and `licenser_pro_activity_logs` / `licenser_pro_email_logs` tables). Free checks `defined('LICENSER_PRO_VERSION')` only for non-breaking UI indicators (`PRO` menu badge and Addons card).

When developing `licenser-pro`:
1. Hook into `licenser_loaded` (or check `class_exists('Licenser')` on `plugins_loaded`) and show a clean admin notice if Free is missing.
2. Extend behavior via the actions/filters above rather than duplicating core classes or mutating Free schema unexpectedly.
3. Prefix Pro-only tables/options with `licenser_pro_` so deactivating Pro leaves Free data intact.
