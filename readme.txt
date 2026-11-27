=== Licenser ===
Contributors: mdshuvo, addonmaster
Tags: license manager, software licensing, woocommerce, plugin updates, license key
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted license manager & automatic update server for WordPress plugins and themes — with domain limits, client SDK & WooCommerce delivery.

== Description ==

**Licenser** is a 100% self-hosted WordPress license manager and automatic update server for your commercial WordPress plugins, themes, and digital products — with zero third-party SaaS fees or external tracking.

Manage products, versioned ZIP releases, license packages, and active customer site domains directly from your WordPress dashboard, with modular eCommerce integrations (starting with built-in WooCommerce Lite) and an open-source client SDK for one-click WordPress updates.

= Key Features =

* **Overview Dashboard & Live KPIs:** Monitor plugins, themes, active/expired/expiring-soon licenses, active site domains, recent activations, and REST API status from a clean, accessible WordPress-native dashboard.
* **Product & Full Release Management:** Register WordPress plugins and themes with live icon and banner previews, full release history (add, edit, and delete versioned releases with changelogs), compatibility metadata, safe archive/delete protection, and protected release ZIP archives.
* **Flexible License Packages & Bulk Generation:** Define update periods (expiring or lifetime) and per-license domain limits, bulk-generate batches of 1–100 keys, filter and search licenses, manage activated domains (add, toggle active/inactive, view installed version), add custom metadata, and export formula-safe filtered CSVs.
* **Domain Activation & Enforcement:** REST API endpoints for activating, validating, and deactivating licenses with domain normalization, idempotency, and automatic slot management.
* **Automatic Updates & Protected Downloads:** Serve native WordPress plugin and theme updates via the open-source [LicenserWP Client SDK](https://github.com/LicenserWP/client) with license-authenticated release downloads.
* **Modular Store Integrations (Zero Overhead When Disabled):** Enable or disable integrations from **Licenser > Addons**. Disabled integrations never load their PHP files, hooks, or assets — even when the store plugin is active. Built-in **WooCommerce Lite** (HPOS-compatible) supports simple & variable products, configurable multi-quantity policies (separate keys, multiplied domain limits, or single key), automatic suspension/restoration on refund or cancellation, an Order Admin Meta Box, order email key delivery, and a modern My Account License Manager portal with optional customer self-service domain deactivation.
* **Dark SDK Studio & Live API Diagnostics:** Copy the Client SDK install command, generate ready-to-paste PHP bootstrap code with live dark CodeMirror output, and test live public REST endpoints directly inside wp-admin.

= Privacy & Self-Hosted Guarantee =

Licenser runs entirely on your own WordPress site. It does not phone home, collect telemetry, or send license or customer data to external third-party servers.

== Installation ==

1. Upload the `licenser` directory to `/wp-content/plugins/`, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Licenser** in your WordPress admin menu to add your products, release archives, and license packages.
4. Open **Licenser > SDK Studio** to bundle the open-source Client SDK and copy your ready-to-paste PHP integration snippet.

== Frequently Asked Questions ==

= How do I connect my plugin or theme to Licenser? =

Clone or download the open-source client library from [github.com/LicenserWP/client](https://github.com/LicenserWP/client), place it inside your plugin or theme folder as `licenser`, and paste the PHP snippet generated under **Licenser > SDK Studio**.

= Can I use Licenser without WooCommerce (or disable WooCommerce integration)? =

Yes. WooCommerce is completely optional. You can issue individual or bulk keys directly inside **Licenser > Licenses**, or disable the built-in WooCommerce Lite integration at any time under **Licenser > Addons** — when disabled, none of Licenser's WooCommerce PHP files or hooks are loaded, even if WooCommerce itself is active.

= How does WooCommerce handle multi-quantity orders and refunds? =

Under **Licenser > Settings > WooCommerce**, you can choose whether purchasing `quantity > 1` generates separate individual license keys (default), multiplies the domain limit on a single key, or issues a single key. You can also enable automatic license suspension when an order is refunded or cancelled (with automatic reactivation if the order is restored).

= Can customers deactivate their own site activations? =

Yes. When enabled under **Licenser > Settings > WooCommerce**, logged-in customers can view their licenses, download the latest release ZIP, and deactivate activated domains directly inside **My Account > License Manager** (also embeddable via `[licenser_wc_manager]`).

= Is Licenser compatible with WooCommerce HPOS? =

Yes. Licenser declares full compatibility with WooCommerce High-Performance Order Storage (`custom_order_tables`) and uses WooCommerce CRUD order APIs across order processing, emails, the Order Admin Meta Box, and the customer portal.

= Where are uploaded release archives stored? =

When uploading release ZIP files through the Licenser product editor media frame, files are stored inside `wp-content/uploads/license-manager/` and delivered through license-authenticated download endpoints.

= Does Licenser send any data to external servers? =

No. Licenser is 100% self-hosted. All products, license keys, activations, and release ZIPs stay inside your own WordPress database and uploads directory.

== Screenshots ==

1. Overview dashboard with 5 live KPI cards, recent licenses, recent domain activations, quick actions, and REST API server status.
2. Plugins and themes catalog featuring icon thumbnails, latest release versions, package and license counts, one-click UUID copy, search, status filters, and bulk actions.
3. Product editor with sticky navigation, product snapshot, full release history management, license package cards, live icon/banner previews, and public REST endpoints.
4. Built-in dark SDK Studio with a 2-step Client SDK install bar, live syntax-highlighted PHP bootstrap generator, one-click copy, and an integrated API Diagnostics Tester.
5. Tabbed Settings (`General`, dedicated `WooCommerce` tab, and `API & Maintenance`) with live sample key preview, multi-quantity & refund policies, and transient cache controls.
6. Addons & Integrations screen featuring modular enable/disable toggles for WooCommerce Lite and upcoming store integrations (such as Easy Digital Downloads), plus the open-source Licenser Client SDK.

== Changelog ==

= 1.0.8 =
* Redesigned the complete Admin UI and WooCommerce Customer My Account portal with a unified, WCAG 2.1 AA–accessible WordPress-native `licenser-*` design system (zero inline scripts/styles, accessible confirmation modals with focus traps, toast feedback, and one-click copy controls).
* Upgraded the Dashboard Overview with 5 KPI cards (including active domains and expiring-soon count), Recent Licenses, Recent Domain Activations, Quick Actions, and Setup/API status.
* Enhanced Products & Themes catalogs and editor with icon/banner live previews, full release history CRUD, package summaries, safe-delete archive guard, search, status filters, and bulk actions.
* Enhanced Licenses management with human-readable product/package columns, status badges (`Active`, `Expiring Soon`, `Expired`, `Inactive`), domain usage meters, clickable WooCommerce order links, search/product/status filters, bulk actions, Bulk Key Generator modal, formula-safe CSV export, manual domain add/toggle, installed version display, and custom metadata creation.
* Upgraded SDK Generator into **SDK Studio** with a unified dark 2-step Code Studio (`Bundle the Client SDK` + live dark CodeMirror PHP output) and an integrated dark **API Diagnostics** tester.
* Organized Settings into 3 clean tabs (`General`, `WooCommerce`, and `API & Maintenance`) with live sample key preview and cache controls.
* Added modular Store Integrations toggle support in **Addons & Integrations** (enable or disable WooCommerce Lite without loading any WooCommerce files even when WooCommerce is active, extensible via `licenser_integrations` for future integrations like Easy Digital Downloads).
* Added WooCommerce multi-quantity licensing modes (`separate_keys`, `multiply_domains`, `single_legacy`), auto-suspend/restore on refund/cancellation, HPOS-compatible Order Admin Meta Box, and customer self-service domain deactivation in My Account.
* Added full WordPress Coding Standards (WPCS) compliance and WooCommerce HPOS compatibility declaration.
* Implemented license deactivation endpoint to release domain activation slots and enforced domain limits, expiry, status, and product ownership checks.
* Migrated settings storage to `licenser_settings` and transient caches to `licenser_cache_` with automatic upgrade migration.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.8 =
Major release: redesigned accessible Admin UI & dark SDK Studio, modular toggleable WooCommerce integration (HPOS-compatible, multi-quantity, refund auto-suspend, self-service portal), bulk key generator, CSV export, security hardening, and automatic settings/schema migration.
