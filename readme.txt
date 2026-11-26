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

* **Product & Release Management:** Register WordPress plugins and themes with changelogs, icons, banners, compatibility metadata, and release ZIP archives.
* **Flexible License Packages:** Define update periods (expiring or lifetime) and per-license domain activation limits (limited or unlimited).
* **Domain Activation & Enforcement:** REST API endpoints for activating, validating, and deactivating licenses with domain normalization and automatic slot management.
* **Automatic Updates & Protected Downloads:** Serve WordPress plugin and theme updates via the open-source [LicenserWP Client SDK](https://github.com/LicenserWP/client) with license-authenticated release downloads.
* **Built-in WooCommerce Integration (HPOS Compatible):** Map simple and variable WooCommerce products to Licenser products and packages, automatically generate license keys on order completion, include keys and download links in order emails, and display a License Manager tab in My Account.
* **Built-in SDK Generator:** Generate ready-to-paste PHP bootstrap code for your plugins or themes.

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

1. Overview dashboard showing total registered plugins, themes, and active licenses at a glance.
2. Plugins and themes product catalog with slugs, dates, sorting, and quick edit/delete actions.
3. Product editor for managing UUIDs, WordPress/PHP compatibility metadata, stable releases, license packages, icons/banners, and Public API URLs.
4. Built-in SDK Generator producing ready-to-paste PHP client integration code for plugins and themes.
5. License Manager settings for key prefixes, generation methods (Microtime, WP Password, UUID v4), character length, and WooCommerce visibility.
6. Addons screen showing the built-in WooCommerce Lite integration and extension status.

== Changelog ==

= 1.0.8 =
* Added full WordPress Coding Standards (WPCS) compliance and WooCommerce HPOS compatibility declaration.
* Implemented license deactivation endpoint to release domain activation slots.
* Enforced domain activation limits, license expiry, status, and product ownership checks across public check, activate, and download endpoints.
* Migrated settings storage to `licenser_settings` and transient caches to `licenser_cache_` with automatic upgrade migration.
* Fixed WooCommerce order email license key output, multi-item order license generation, and buffered shortcode output.
* Fixed Dashboard counters, list table pagination and expiry rendering, license edit form, and SDK Generator output.
* Hardened AJAX and REST endpoints with capability checks, nonces, settings allowlisting, and prepared SQL queries.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.8 =
Security hardening, WooCommerce HPOS compatibility, real domain-limit & deactivation enforcement, and automatic settings/schema migration.
