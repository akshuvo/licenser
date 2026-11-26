# Development Workflow & Environment

## 1. Local Workspace & Multi-Repo Topology

The active workspace is the LocalWP portal clone's plugins directory:
`/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/plugins`

| Directory | Git Root? | Remote | Current Branch (2026-11-26) | Notes |
|---|---|---|---|---|
| `licenser/` | Yes | `https://github.com/akshuvo/licenser.git` | `main` (`1401cee` + uncommitted changes) | Also has remote branches `dev`, `deploy`, `lwpdb-to-wpdb`, `migrate-from-old`, `vue` |
| `licenser-pro/` | Yes | `https://github.com/LicenserWP/licenser-pro.git` | `main` (`e613593`) | Initial Pro scaffold |
| `wc-paddle-payment-gateway/` | No | — | — | Has `package.json` + `gulpfile.js` for `assets/src` -> `assets/dist` |
| `plugins/` (parent dir) | No | — | — | Contains legacy `.cpanel.yml` and `.github/workflows/deploy.yml` from site backup; ignore for `licenser` Git ops |

Related sibling workspace (for testing end-to-end client activation & updates):
- `/Users/akshuvo/Local Sites/grid-master/app/public/wp-content/plugins/gridmaster-pro` (embeds `licenser/` client SDK).

Always verify Git state inside the specific plugin before starting work:
```sh
git -C "/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/plugins/licenser" rev-parse --show-toplevel
git -C "/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/plugins/licenser" status -sb
```

## 2. Local Portal & Tooling

- **Local site URL:** `http://portal-addonmaster.local/`
- **WP Admin auto-login:** `http://portal-addonmaster.local/wp-admin/?localwp_auto_login=1`
- **LocalWP MySQL socket (for CLI PHP/WP integration checks):** `/Users/akshuvo/Library/Application Support/Local/run/vq5NUpMTM/mysql/mysqld.sock` (pass `-d mysqli.default_socket=...` to host PHP CLI if running non-destructive read/REST checks).
- **Release ZIP storage:** `/Users/akshuvo/Local Sites/addonmaster-portal/app/public/wp-content/uploads/license-manager/`
- **Host CLI tools verified (2026-11-26):**
  - `php` (`/opt/homebrew/bin/php`, PHP 8.4.11)
  - `composer` (`/usr/local/bin/composer`)
  - `wp` (`/opt/homebrew/bin/wp`)
  - `node` / `npm` (`/opt/homebrew/bin/node`, `/opt/homebrew/bin/npm`)
  - `qit` (`/Users/akshuvo/.local/bin/qit`, `v1.3.4` — requires `qit connect` and running OrbStack/Docker before executing WooCommerce QIT suites)
- **WPCS / PHPCS (Installed Globally to Keep `licenser/vendor/` Production-Clean):**
  - Installed in `$HOME/.config/composer` (`squizlabs/php_codesniffer` `3.13.6`, `wp-coding-standards/wpcs` `3.4.1`, `dealerdirect/phpcodesniffer-composer-installer` `1.2.1`). Note: `$HOME/.composer` on this machine is root-owned, so always set `COMPOSER_HOME="$HOME/.config/composer"` when updating global Composer tools.
  - Ruleset lives at [`phpcs.xml`](../phpcs.xml) (non-hidden `.xml` so WordPress Plugin Check reports 0 hidden/application errors on local checkouts; checks `WordPress-Core`, `WordPress-Extra`, `WordPress-Docs`, text domain `licenser`, prefixes `licenser`/`Licenser`/`LICENSER`, PSR-4 filenames and short array `[]` allowed).
  - Run from `licenser/` root:
    ```sh
    "$HOME/.config/composer/vendor/bin/phpcs"
    ```
- **Composer autoloader:** `vendor/` is tracked in Git and deployed via FTP/WP.org. Keep `composer.json` included in release packages (required by WordPress Plugin Check when `vendor/autoload.php` is present). Do **not** install dev tools into `licenser/vendor/`. Only run `composer dump-autoload` when changing PSR-4 mappings in `composer.json`.

## 3. Deployment & WordPress.org Auto-Deploy Guardrails

- **Do NOT commit or push** unless the user explicitly asks. Leave changes uncommitted in the working tree for review.
- **Dangerous push trigger:** [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) runs `SamKirkland/FTP-Deploy-Action@v4.3.5` automatically on every push to the `deploy` branch, syncing files directly to `./licenser/` on the remote server. Never push to `deploy` as a test or without explicit authorization.
- **WordPress.org SVN Auto-Deploy ([`.github/workflows/wporg.yml`](../.github/workflows/wporg.yml)):**
  - Modeled after GridMaster's deterministic release pipeline using [`scripts/release.py`](../scripts/release.py), [`scripts/lint_package.py`](../scripts/lint_package.py), [`scripts/smoke_test.py`](../scripts/smoke_test.py), [`tests/smoke.php`](../tests/smoke.php), and [`tests/test_release.py`](../tests/test_release.py).
  - **Triggers:**
    - `pull_request` / `push` to `main`/`dev`: runs release boundary unit tests, builds deterministic `licenser.zip` from [`.github/release-files.txt`](../.github/release-files.txt), lints packaged PHP, runs disposable WordPress activation smoke test, and dry-run validates SVN (skips gracefully before WP.org approves the slug).
    - `release: published` (non-prerelease tag `vX.Y.Z`): requires clean git tag matching `licenser.php` `Version`, `Licenser::VERSION`, and `readme.txt` `Stable tag`, commits validated `trunk/`, immutable `tags/X.Y.Z`, and `.wordpress/` listing assets (`icon.svg`, `icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`, `screenshot-*.png` with proper `svn:mime-type`) to WordPress.org SVN, verifies the published WP.org ZIP/API/assets, and uploads `licenser.zip` to the GitHub Release.
    - `workflow_dispatch` with `assets_only: true`: syncs only `.wordpress/` icons, banners, and screenshots to WordPress.org SVN `/assets` without cutting a code release.
  - **Required GitHub repository secrets (after WP.org approves the plugin):** `SVN_USERNAME` and `SVN_PASSWORD`. Optional repo variable: `WPORG_PLUGIN_SLUG` (defaults to `licenser`).
  - **Post-first-release baseline pinning:** [`.github/release-baseline.json`](../.github/release-baseline.json) starts with `"version": null` for the initial empty WP.org SVN launch. After the first release succeeds, pin `"version"` and `"files"` from the release evidence `manifest.json` to enable unexplained-deletion protection on future releases.
  - **Updating runtime files:** Whenever adding or removing files under `addons/`, `assets/`, `includes/`, `vendor/`, or root runtime files, regenerate `.github/release-files.txt` and run `python3 -B -m unittest discover -s tests -p 'test_release.py' -v`.
- **Do not test against production (`https://portal.addonmaster.com/`).** When testing the client SDK from `gridmaster-pro` locally, temporarily filter `licenser_endpoint` to point to `http://portal-addonmaster.local/wp-json/licenser/v1/public` instead of hitting live customer data.

## 4. Coding & Architecture Conventions

1. **Namespaces & Autoloading:**
   - Place models in `includes/models/` (`namespace Licenser\Models;`), controllers in `includes/controllers/` (`namespace Licenser\Controllers;`), REST APIs in `includes/Api/` (`namespace Licenser\Api;`), admin UI in `includes/Admin/` (`namespace Licenser\Admin;`), addons in `addons/` (`namespace Licenser\Addons;`).
   - Prefer `SingletonTrait` (`new static()`) for new extensible singletons; existing models use `SingletonTraitSelf`.
2. **Database Access & Migrations:**
   - Use `$wpdb->licenser_*` properties (or `licenser_table('...')`).
   - Always use `$wpdb->prepare()` for dynamic values. Allowlist any dynamic `ORDER BY`, `ORDER`, or column names.
   - Bump `Installer::DB_VERSION` (integer, never float) for schema changes; `Licenser::maybe_upgrade()` auto-runs `Installer::run()` on `plugins_loaded`.
3. **WordPress Security Baseline:**
   - Unslash `$_GET` / `$_POST` / `$_REQUEST` with `wp_unslash()` before sanitizing.
   - Check `current_user_can()` and `check_ajax_referer()` / REST `permission_callback` on every privileged action.
   - Validate object ownership (e.g., verify a license belongs to the requested `product_id` before activating, checking, deactivating, or downloading).
   - Use `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()` on all rendered output.
4. **WooCommerce Compatibility (`Wc_Lite`):**
   - `licenser.php` declares HPOS (`custom_order_tables`) compatibility via `FeaturesUtil::declare_compatibility()`.
   - Always use WooCommerce CRUD methods (`$order->get_meta()`, `$order->update_meta_data()`, `$order->save()`) rather than raw `get_post_meta()`/`update_post_meta()` on orders.
5. **Internationalization, WP.org Readiness & Naming:**
   - Use text domain `'licenser'`, option key `licenser_settings`, and transient prefix `licenser_cache_`.
   - Include `if ( ! defined( 'ABSPATH' ) ) { exit; }` in every PHP file and avoid inline `<script>`/`<style>` tags in PHP templates.
   - Keep `readme.txt` (`Stable tag`, `Tested up to`, `Requires PHP`) aligned with `licenser.php` and keep [`.distignore`](../.distignore) updated for WP.org release builds.
   - Keep admin assets scoped strictly to Licenser screens (`Admin::enqueue_scripts()`) and WooCommerce `product` edit screens (`Wc_Lite\Admin_Handler::admin_scripts()`).
