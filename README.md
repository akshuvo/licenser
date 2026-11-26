# Licenser – Self-Hosted License & Update Server

Self-hosted WordPress license manager and automatic update server for commercial WordPress plugins, themes, and digital products — with domain activation limits, modular WooCommerce integration (HPOS-compatible), and an open-source Client SDK.

## Highlights

- **Overview Dashboard & Live KPIs:** Track plugins, themes, active/expired/expiring-soon licenses, active customer site domains, recent activations, and public REST API status.
- **Product & Release Management:** Manage WordPress plugins and themes with live icon & banner previews, full release history CRUD (changelogs + protected ZIP archives), reusable license packages, and safe archive/delete protection.
- **License Lifecycle & Bulk Tools:** Issue individual keys or bulk-generate 1–100 keys, filter/search by product/status/key/order ID, export formula-safe filtered CSVs, manage activated domains (add, activate/deactivate, view installed version), and store custom key/value metadata.
- **Dark SDK Studio & API Diagnostics:** Generate ready-to-paste PHP bootstrap snippets for [LicenserWP/client](https://github.com/LicenserWP/client) and test live public REST endpoints directly from wp-admin.
- **Modular Store Integrations (`addons/WooLite`):** Toggle integrations on or off under **Licenser > Addons** — disabled integrations never load their PHP files or hooks even when the store plugin is active. Built-in **WooCommerce Lite** supports simple & variable products, HPOS (`custom_order_tables`), configurable multi-quantity licensing (`separate_keys`, `multiply_domains`, `single_legacy`), auto-suspend/restore on refund/cancellation, an Order Admin Meta Box, order email delivery, and a responsive My Account License Manager portal (`[licenser_wc_manager]`) with optional customer self-service domain deactivation.
- **100% Self-Hosted & Standards-Compliant:** Zero external tracking, zero inline `<script>`/`<style>` tags in PHP templates, WCAG 2.1 AA contrast & keyboard accessibility, 0 WPCS errors/warnings, and 0 WordPress Plugin Check errors/warnings.

## Quick Start (Client SDK)

1. Bundle the open-source client library inside your plugin or theme folder:
   ```sh
   git clone https://github.com/LicenserWP/client.git licenser
   ```
2. Paste the bootstrap snippet from **Licenser > SDK Studio**:
   ```php
   <?php
   function my_plugin_licenser_client_init() {
   	if ( ! class_exists( 'Licenser\\Client' ) ) {
   		require_once __DIR__ . '/licenser/src/Client.php';
   	}

   	$client = new Licenser\Client(
   		'YOUR-PRODUCT-UUID',
   		'My Plugin',
   		__FILE__,
   		'https://your-license-server.com'
   	);

   	$license = $client->license();
   	$license->add_settings_page( [
   		'type'        => 'submenu',
   		'menu_title'  => 'License',
   		'page_title'  => 'My Plugin License',
   		'menu_slug'   => 'my_plugin_settings',
   		'parent_slug' => 'options-general.php',
   	] );

   	$client->updater()->init( $client );
   }
   add_action( 'init', 'my_plugin_licenser_client_init' );
   ```

## Public REST API (`licenser/v1`)

Compatible with `LicenserWP/client` v2.0.1:

| Method & Route | Purpose |
| --- | --- |
| `POST /wp-json/licenser/v1/public/products/{uuid}` | WordPress plugin/theme update & info check |
| `POST /wp-json/licenser/v1/public/license/{uuid}/{check\|activate\|deactivate}` | License validation, activation, and slot release |
| `GET /wp-json/licenser/v1/public/products/download/{uuid}?license_key=...` | License-authenticated release ZIP download |

## Development & Verification

```sh
# 1. PHP & JS syntax lint
find . -type f -name '*.php' -not -path './.git/*' -not -path './vendor/*' -exec php -l {} \;
find . -type f -name '*.js' -not -path './.git/*' -not -path './vendor/*' -exec node --check {} \;

# 2. WordPress Coding Standards (WPCS)
"$HOME/.config/composer/vendor/bin/phpcs" --standard=phpcs.xml

# 3. Release manifest & packaging unit tests
python3 -B -m unittest discover -s tests -p 'test_release.py' -v

# 4. WordPress Plugin Check (from your WP install)
wp plugin check licenser --include-experimental
```

See [`docs/`](docs/) for full architecture, integration contracts, development guidelines, and testing records.

## License

GPLv2 or later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
