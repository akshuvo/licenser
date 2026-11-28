# CSV migration to Licenser

Licenser (Free) imports plugins, themes, license packages, license keys, customer metadata, and activated domains from **Licenser → Migration**. The importer is part of [akshuvo/licenser](https://github.com/akshuvo/licenser); it does not require Licenser Pro.

## Recommended migration order

1. **Products (Plugins & Themes):** import [sample-products.csv](sample-products.csv) as a starting point. Keep a stable, unique product `slug` to link the next files.
2. **License Packages:** import [sample-packages.csv](sample-packages.csv), using `product_slug` to attach packages to the products created in step 1.
3. **Licenses:** import [sample-licenses.csv](sample-licenses.csv), using `product_slug` and `package_label` to attach existing keys to the correct product and package.

For each step, upload a CSV or paste its contents, choose a preset, review **every column mapping and its first-row sample**, then run **Dry-Run Preview**. Correct all reported errors and review the counts before choosing **Run Import**. A dry run reads existing records and simulates earlier rows in the same CSV; it never creates or updates records. Products and packages must actually be imported before a later file can resolve them. A preview does not reserve IDs or commit its simulated records.

**Load Sample into Editor** prepares a sample without changing records. **Download CSV** downloads the same template directly from the admin screen. The samples contain demonstration keys, fictional customer information, and example.com URLs; replace them before importing real data. Example download URLs are placeholders for your own release ZIP URLs.

For exports from EDD Software Licensing, Appsero, Freemius, WooCommerce, or a custom database, copy the [AI agent migration prompt](ai-agent-migration-prompt.md) into your preferred AI assistant with the source export. Review its output before importing. Source platform product, plan, and order IDs are not automatically Licenser IDs.

## CSV and mapping rules

- Use UTF-8, comma-separated CSV with one unique, nonblank header per column. A UTF-8 BOM is accepted. Double-quote values containing commas, quotes, or line breaks, and escape embedded double quotes by doubling them.
- Each request accepts at most **2 MB** and **1,000 data rows**. Split larger files while preserving the header in each file. Every row must have the same number of columns as the header.
- Use the canonical headers below for predictable imports. The mapping review can map a differently named CSV column to a target field. An explicit **Ignore** choice suppresses that field's preset aliases; it does not fall back to another column.
- `auto` and `generic` work for all entities. Licenses also offer `edd_sl`, `appsero`, and `freemius`. Presets provide aliases; they do not translate external database relationships. In particular, Freemius `plan_id` must map to a real Licenser package row ID or be ignored in favor of `package_label`.
- **Skip Existing** leaves duplicates unchanged. **Update Existing** updates matching records using the mapped values and importer defaults. Review a dry run before updating; an omitted field is not a promise to preserve its old value. New license domains are added without deleting existing activations.
- Duplicate identity is the product `slug`, package `package_id` on its product (or case-insensitive `label` when no identifier is supplied), and license `license_key`. A duplicate license key cannot be reassigned to another product.
- The report neutralizes leading spreadsheet formula markers (`=`, `+`, `-`, `@`) in text with a leading apostrophe. This protects copied preview text and does not alter the stored key or other stored values. Keep source key strings intact when preparing CSVs.

## Products column reference

| Column | Rules |
|---|---|
| `name` | Required product name. |
| `slug` | Unique product slug; derived from `name` if omitted. Use an explicit stable slug for cross-file links. |
| `product_type` | `plugin` or `theme`; defaults to `plugin`. |
| `status` | `active` or `draft`; defaults to `active`. |
| `author_name` | Optional author name. |
| `homepage_url`, `demo_url`, `icon_url` | Optional HTTP or HTTPS URLs. |
| `requires`, `tested`, `requires_php` | Optional minimum WordPress, tested WordPress, and minimum PHP versions. Keep versions as text. |
| `description` | Optional description; permitted HTML is sanitized by WordPress. |
| `banner_low`, `banner_high` | Optional HTTP or HTTPS banner image URLs. |
| `version`, `download_link`, `file_name`, `release_date`, `changelog` | Optional initial stable release. Supply both `version` and `download_link` when providing any release data. Use an HTTP or HTTPS ZIP URL for `download_link` and ISO `YYYY-MM-DD HH:MM:SS` for `release_date`. |

Common aliases include `title` → `name` and `type` → `product_type`. A product CSV does not import historic release lists or preserve an external product UUID.

## Packages column reference

| Column | Rules |
|---|---|
| `product_id` | Optional positive **Licenser** product row ID. |
| `product_slug` | Resolve an existing product by its slug. Prefer this for migration portability. |
| `label` | Required package name, such as `Single Site Annual`. |
| `package_id` | Optional custom identifier/UUID for the package template. A new identifier is generated when omitted. |
| `update_period` | Nonnegative integer days. `0`, `lifetime`, or `never` means lifetime. |
| `domain_limit` | Nonnegative integer sites. `0`, `-1`, or `unlimited` means unlimited. |

Resolve products by `product_id`, then `product_slug`, then **Default Product** when neither is present. An invalid supplied ID or slug is an error; the default does not hide invalid relationships. Aliases include `plan_name` → `label`, `sites` / `activation_limit` → `domain_limit`, and `validity_days` / `period` → `update_period`.

## Licenses column reference

| Column | Rules |
|---|---|
| `license_key` | Required existing key; preserve it exactly. Maximum 191 characters; invalid text is rejected. |
| `product_id`, `product_slug` | Resolve the product as described for packages; **Default Product** is available when both are absent. |
| `package_id` | Optional positive **integer Licenser package row ID**, belonging to the resolved product. This is not the package template's string identifier/UUID. |
| `package_label` | When `package_id` is omitted, resolve a package by its case-insensitive label on the product. Use unambiguous labels. |
| `status` | `active`, `valid`, `1`, `expired`, `inactive`, `disabled`, `revoked`, `cancelled`, `canceled`, or `0`; defaults to `active`. Inactive/revoked/cancelled values disable the license. |
| `domain_limit` | Nonnegative integer sites; `0`, `-1`, or `unlimited` means unlimited. Supply the actual license entitlement; selecting a package does not infer it. |
| `end_date` | ISO `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS` recommended. `lifetime`, `never`, or `0` means lifetime. Preserve actual expiry dates. |
| `is_lifetime` | Boolean `1` / `0` recommended; `1` makes the license lifetime regardless of `end_date`. |
| `customer_email`, `customer_name` | Optional customer metadata; nonblank email must be valid. These do not create WordPress users. |
| `domains` | Optional activated HTTP/HTTPS domains or URLs, separated with `;` (`|` also accepted). Scheme, `www.`, case in hosts, and trailing slash are normalized; duplicate normalized domains are imported once. Paths are retained. |
| `source` | Optional sanitized source label; defaults to `imported`. This does not create or link store order metadata. |
| `source_id` | Optional nonnegative integer source record/order ID. Keep `0` or omit it when unknown. |
| `is_cancelled` | Optional boolean compatibility field, used by Freemius exports; true disables the license. |

The `expired` status is represented by an active license with a past `end_date`. If it has no past date, the importer assigns one. Do not label an active future-dated entitlement `expired`. For deterministic migrations, supply the real past date. A package's update period is not used to invent an expiration date.

## REST usage

Authenticated administrators can call `POST /wp-json/licenser/v1/import` with `entity`, `csv`, `preset`, `column_map`, `default_product_id`, `duplicate_mode`, and `dry_run`. `dry_run` defaults to `true`; an actual import requires the explicit JSON boolean `false`. Cookie-authenticated REST requests must include a valid `X-WP-Nonce`.

```json
{
  "entity": "licenses",
  "csv": "license_key,product_slug,package_label,domain_limit,end_date\nEXISTING-KEY,northstar-blocks,Single Site Annual,1,2035-11-01\n",
  "preset": "generic",
  "column_map": { "customer_email": "", "package_id": "" },
  "duplicate_mode": "skip",
  "dry_run": true
}
```

The response contains `dry_run`, `entity`, `total_rows`, `created`, `updated`, `skipped`, row-specific `errors: [{ row, message }]`, and `preview: [{ row, operation, identifier, summary, details }]`. Counts in a dry run describe what would happen. Review row errors even when the request succeeds: other valid rows can be imported, and a later metadata/domain failure can leave the license already saved. Correct the cause and retry using **Update Existing**.

The screen uses `licenser_admin_capability` with context `migration`, while the REST endpoint uses `licenser_rest_capability` with base `import` and action `create`; both default to `manage_options`. Pro's Support Agent role does not receive migration access by default. See [the integration contract](../integrations.md) for extension points.
