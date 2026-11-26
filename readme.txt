=== Licenser - Self-Hosted License Manager ===
Contributors: mdshuvo, addonmaster
Tags: license manager, software licensing, woocommerce, plugin updates, digital products
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted license key management, domain activation limits, WooCommerce license delivery, and automatic updates for WordPress plugins and themes.

== Description ==

**Licenser** is a self-hosted WordPress license manager and automatic update server for your WordPress plugins, themes, and digital products.

Manage products, versioned releases, license packages, and active site domains directly from your WordPress dashboard — with built-in WooCommerce integration and a lightweight client SDK for delivering automatic one-click updates to your customers.

= Key Features =

* **Overview Dashboard & Live KPIs:** Monitor plugins, themes, active/expired/expiring-soon licenses, active site domains, recent activations, and REST API status from a clean WordPress-native dashboard.
* **Product & Release Management:** Register WordPress plugins and themes with icon and banner live previews, full release history (add, edit, delete releases with changelogs), compatibility metadata, and release ZIP archives.
* **Flexible License Packages & Bulk Generation:** Define update periods (expiring or lifetime) and per-license domain limits, bulk-generate batches of keys, filter/search licenses, manage domains (add, toggle active/inactive, view installed version), add custom metadata, and export filtered CSVs.
* **Domain Activation & Enforcement:** REST API endpoints for activating, validating, and deactivating licenses with domain normalization and automatic slot management.
* **Automatic Updates & Protected Downloads:** Serve WordPress plugin and theme updates via the open-source [LicenserWP Client SDK](https://github.com/LicenserWP/client) with license-authenticated release downloads.
* **Built-in WooCommerce Integration (HPOS Compatible):** Map simple and variable WooCommerce products to Licenser products and packages, configure multi-quantity policies (separate keys, multiplied domain limits, or single key), auto-suspend licenses on refund/cancellation, view linked keys in the Order Admin Meta Box, include keys and download links in order emails, and give customers a modern My Account License Manager portal with optional self-service domain deactivation.
* **SDK Studio & Built-in API Diagnostics:** Generate ready-to-paste PHP bootstrap code for plugins or themes with live CodeMirror output and test public REST endpoints directly from wp-admin.

== Installation ==

1. Upload the `licenser` directory to `/wp-content/plugins/`, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Licenser** in your WordPress admin menu to add your products, release archives, and license packages.
4. Use **Licenser > SDK Generator** to integrate automatic updates and license activation into your plugin or theme.

== Frequently Asked Questions ==

= How do I connect my plugin or theme to Licenser? =

Clone or download the open-source client library from [github.com/LicenserWP/client](https://github.com/LicenserWP/client), place it in your plugin or theme folder, and paste the snippet generated under **Licenser > SDK Generator**.

= Does Licenser work with WooCommerce? =

Yes. Licenser includes a built-in WooCommerce Lite addon that supports both simple and variable products, WooCommerce High-Performance Order Storage (HPOS), automatic key generation on `processing`/`completed` orders, order emails, and a My Account License Manager tab (also available via the `[licenser_wc_manager]` shortcode).

= Where are uploaded release archives stored? =

When uploading release ZIP files through the Licenser product editor media frame, files are stored inside `wp-content/uploads/license-manager/` and delivered through license-authenticated download endpoints.

== Screenshots ==

1. Overview dashboard with 5 live KPI cards, recent licenses, recent domain activations, quick actions, and REST API server status.
2. Plugins and themes catalog featuring icon thumbnails, latest release versions, package and license counts, one-click UUID copy, search, status filters, and bulk actions.
3. Product editor with sticky navigation, product snapshot, full release history management, license package cards, live icon/banner previews, and public REST endpoints.
4. Built-in SDK Studio with live CodeMirror PHP code generation, one-click copy, contextual output mode guidance, and an integrated API Diagnostics Tester.
5. Structured settings for license key generation (with live sample key preview), WooCommerce multi-quantity and refund/self-service policies, and transient cache controls.
6. Addons & Integrations screen featuring modular enable/disable toggles for WooCommerce Lite and upcoming store integrations (such as Easy Digital Downloads), plus the open-source Licenser Client SDK.

== Changelog ==

= 1.0.8 =
* Redesigned the complete Admin UI and WooCommerce Customer My Account portal with a unified, WordPress-native `licenser-*` design system (zero inline scripts/styles, accessible confirmation modals, toast feedback, and one-click copy controls).
* Upgraded the Dashboard Overview with 5 KPI cards (including active domains and expiring-soon count), Recent Licenses, Recent Domain Activations, Quick Actions, and Setup/API status.
* Enhanced Products & Themes catalogs and editor with icon/banner live previews, full release history CRUD, package summaries, safe-delete archive guard, search, status filters, and bulk actions.
* Enhanced Licenses management with human-readable product/package columns, status badges (`Active`, `Expiring Soon`, `Expired`, `Inactive`), domain usage meters, clickable WooCommerce order links, search/product/status filters, bulk actions, Bulk Key Generator modal, formula-safe CSV export, manual domain add/toggle, installed version display, and custom metadata creation.
* Upgraded SDK Generator into a 2-column SDK Studio with live CodeMirror preview, one-click Copy, and a built-in API & License Diagnostics Tester.
* Added modular Store Integrations toggle support in Addons & Integrations (enable or disable WooCommerce Lite without loading its files even when WooCommerce is active, extensible to future integrations like Easy Digital Downloads).
* Added WooCommerce multi-quantity licensing modes (`separate_keys`, `multiply_domains`, `single_legacy`), auto-suspend/restore on refund/cancellation, HPOS-compatible Order Admin Meta Box, and customer self-service domain deactivation in My Account.
* Added full WordPress Coding Standards (WPCS) compliance and WooCommerce HPOS compatibility declaration.
* Implemented license deactivation endpoint to release domain activation slots and enforced domain limits, expiry, status, and product ownership checks.
* Migrated settings storage to `licenser_settings` and transient caches to `licenser_cache_` with automatic upgrade migration.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.8 =
Security hardening, WooCommerce HPOS compatibility, real domain-limit & deactivation enforcement, and automatic settings/schema migration.
