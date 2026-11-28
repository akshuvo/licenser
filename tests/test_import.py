"""Exercise the real importer in PHP with disposable in-memory model boundaries.

No WordPress installation, customer data, network, or live database is used. The
model doubles retain the public model contracts and record *every* mutation so
preview safety and the data handed to the production models can be asserted.
"""

import base64
import csv
from datetime import datetime, timezone
import io
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[1]

PHP_HARNESS = r'''<?php
namespace {
    error_reporting(E_ALL);
    ini_set('display_errors', 'stderr');
    define('ABSPATH', __DIR__ . '/');
    define('DAY_IN_SECONDS', 86400);
    date_default_timezone_set('UTC');
    class WP_REST_Controller {}
    class WP_REST_Server { const CREATABLE = 'POST'; }
    class WP_Error {
        public $code; public $message; public $data;
        public function __construct($code, $message, $data = null) {
            $this->code = $code; $this->message = $message; $this->data = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
    class WP_REST_Request {
        private $params;
        public function __construct($params) { $this->params = $params; }
        public function get_param($name) { return $this->params[$name] ?? null; }
        public function has_param($name) { return array_key_exists($name, $this->params); }
        public function get_params() { return $this->params; }
    }
    function __($text, $domain = '') { return $text; }
    function esc_html__($text, $domain = '') { return $text; }
    function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
    function sanitize_text_field($value) { return trim(preg_replace('/\s+/', ' ', strip_tags((string) $value))); }
    function sanitize_textarea_field($value) { return trim(strip_tags((string) $value)); }
    function sanitize_title($value) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-'); }
    function sanitize_email($value) { return filter_var($value, FILTER_SANITIZE_EMAIL); }
    function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
    function wp_kses_post($value) { return $value; }
    function esc_url_raw($value) { return $value; }
    function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
    function absint($value) { return abs((int) $value); }
    function wp_parse_url($value, $component = -1) { return parse_url($value, $component); }
    function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
    function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value); }
    function wp_parse_args($value, $defaults = []) { return array_merge($defaults, $value); }
    function wp_check_invalid_utf8($value) { return preg_match('//u', $value) ? $value : ''; }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    function rest_ensure_response($value) { return $value; }
    function get_current_user_id() { return 1; }
    function current_time($format) { return gmdate($format === 'mysql' ? 'Y-m-d H:i:s' : $format); }
    function licenser_date($format, $timestamp = null) { return gmdate($format, $timestamp ?? time()); }
    function add_action(...$args) {}
    function register_rest_route($namespace, $route, $args) { $GLOBALS['routes'][] = [$namespace, $route, $args]; }
    function apply_filters($name, $value, ...$args) {
        $GLOBALS['filters'][] = [$name, $value, $args]; return $value;
    }
    function current_user_can($capability) { return in_array($capability, $GLOBALS['capabilities'], true); }
    function licenser_get_clean_url($url) {
        $parts = parse_url(trim($url));
        return rtrim(preg_replace('/^www\./i', '', strtolower($parts['host'] ?? '')) . ($parts['path'] ?? ''), '/');
    }
}
namespace Licenser\Models {
    class Store {
        public static $tables = []; public static $writes = []; private static $instances = [];
        public static function instance() {
            $class = static::class;
            if (!isset(self::$instances[$class])) { self::$instances[$class] = new static(); }
            return self::$instances[$class];
        }
        public function get($id, $args = []) {
            foreach (self::$tables[static::class] ?? [] as $row) {
                $field = ($args['get_by'] ?? '') === 'key' ? 'license_key' : (($args['get_by'] ?? '') === 'product_id' ? 'product_id' : 'id');
                if ((string) ($row[$field] ?? '') === (string) $id && (empty($args['version']) || $args['version'] === ($row['version'] ?? ''))) { return (object) $row; }
            }
            return false;
        }
        public function get_all($args = []) {
            $rows = array_filter(self::$tables[static::class] ?? [], static function($row) use ($args) {
                foreach (['product_id', 'slug', 'package_id', 'license_key'] as $key) {
                    if (isset($args[$key]) && '' !== $args[$key] && (string) ($row[$key] ?? '') !== (string) $args[$key]) { return false; }
                }
                return !isset($args['status']) || $args['status'] === 'all' || (string) ($row['status'] ?? '') === (string) $args['status'];
            });
            if (isset($args['number']) && $args['number'] > -1) { $rows = array_slice($rows, 0, $args['number']); }
            return $rows ? array_map(static function($row) { return (object) $row; }, array_values($rows)) : false;
        }
        public function create($data) {
            $class = static::class; $rows = self::$tables[$class] ?? [];
            $id = !empty($data['id']) ? (int) $data['id'] : (count($rows) ? max(array_keys($rows)) + 1 : 1);
            self::$writes[] = ['model' => $class, 'method' => 'create', 'data' => $data];
            if (($GLOBALS['failures'][substr(strrchr($class, '\\'), 1)] ?? '') === 'no_write') { return $id; }
            self::$tables[$class][$id] = array_merge($rows[$id] ?? [], $data, ['id' => $id]);
            return $id;
        }
    }
    class Product extends Store {
        public function create($data) {
            $id = parent::create($data);
            // Product::create owns its optional initial stable release in Free.
            if (!empty($data['version']) && !empty($data['download_link']) && !empty($data['release_date'])) {
                ProductRelease::instance()->create(array_intersect_key(array_merge($data, ['product_id' => $id]), array_flip(['product_id', 'version', 'changelog', 'file_name', 'download_link', 'release_date'])));
            }
            return $id;
        }
    }
    class ProductRelease extends Store {
        public function get_stable($id) { return $this->get($id, ['get_by' => 'product_id']); }
        public function create($data) {
            // Production derives these values even when explicitly blank CSV
            // cells reach the model through the UI's complete column map.
            $data = array_merge(['changelog' => '', 'file_name' => '', 'release_date' => ''], $data);
            $data['file_name'] = !empty($data['file_name']) ? $data['file_name'] : basename((string) $data['download_link']);
            $timestamp = !empty($data['release_date']) ? strtotime($data['release_date']) : false;
            $data['release_date'] = gmdate('Y-m-d H:i:s', $timestamp ?: time());
            return parent::create($data);
        }
    }
    class LicensePackage extends Store {
        public function create($data) {
            if (empty($data['package_id'])) {
                $existing = !empty($data['id']) ? $this->get($data['id']) : false;
                $data['package_id'] = $existing ? $existing->package_id : 'generated-' . count(self::$writes);
            }
            return parent::create($data);
        }
    }
    class License extends Store {
        public static $domains = [];
        public function create($data) {
            $data['package_id'] = isset($data['package_id']) && '' !== $data['package_id'] ? (int) $data['package_id'] : null;
            return parent::create($data);
        }
        public function domain_exists($domain, $id) { return in_array($domain, self::$domains[$id] ?? [], true); }
        public function add_domain($data) {
            Store::$writes[] = ['model' => static::class, 'method' => 'add_domain', 'data' => $data];
            if (isset($GLOBALS['failures']['domains'])) { return false; }
            self::$domains[$data['license_id']][] = $data['domain']; return count(self::$domains[$data['license_id']]);
        }
    }
    class License_Meta extends Store {
        public static $meta = [];
        public function update($id, $key, $value) {
            Store::$writes[] = ['model' => static::class, 'method' => 'update', 'data' => [$id, $key, $value]];
            if (isset($GLOBALS['failures']['meta'])) { return false; }
            self::$meta[$id][$key] = $value; return true;
        }
    }
}
namespace {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $GLOBALS['capabilities'] = $input['capabilities'] ?? ['manage_options'];
    $GLOBALS['failures'] = $input['failures'] ?? [];
    $GLOBALS['routes'] = []; $GLOBALS['filters'] = [];
    foreach ($input['seed'] ?? [] as $model => $rows) {
        foreach ($rows as $row) { \Licenser\Models\Store::$tables['Licenser\\Models\\' . $model][$row['id']] = $row; }
    }
    require $input['root'] . '/includes/controllers/RestController.php';
    require $input['root'] . '/includes/Api/Import.php';
    $controller = new \Licenser\Api\Import();
    $controller->register_routes();
    $migration_data = null;
    if (!empty($input['migration_data'])) {
        require $input['root'] . '/includes/Admin/MigrationData.php';
        $migration_data = ['samples' => \Licenser\Admin\MigrationData::samples(), 'prompt' => \Licenser\Admin\MigrationData::prompt()];
    }
    $reports = [];
    foreach ($input['requests'] as $params) {
        if (isset($params['_csv_base64'])) {
            $params['csv'] = base64_decode($params['_csv_base64']); unset($params['_csv_base64']);
        }
        $request = new WP_REST_Request($params);
        if ($input['permission_only'] ?? false) {
            $result = $controller->create_item_permissions_check($request);
        } else {
            $result = $controller->create_item($request);
        }
        if ($result instanceof WP_Error) { $result = ['error' => $result->code, 'message' => $result->message, 'data' => $result->data]; }
        if (is_object($result) && method_exists($result, 'get_data')) { $result = $result->get_data(); }
        $reports[] = $result;
    }
    $route_report = [];
    foreach ($GLOBALS['routes'] as $route) {
        $args = $route[2]; if (isset($args[0])) { $args = $args[0]; }
        $route_report[] = ['namespace' => $route[0], 'route' => $route[1], 'methods' => $args['methods'], 'permission' => $args['permission_callback'][1] ?? null];
    }
    echo json_encode(['reports' => $reports, 'tables' => \Licenser\Models\Store::$tables, 'writes' => \Licenser\Models\Store::$writes,
        'domains' => \Licenser\Models\License::$domains, 'meta' => \Licenser\Models\License_Meta::$meta, 'routes' => $route_report,
        'filters' => $GLOBALS['filters'], 'migration_data' => $migration_data], JSON_THROW_ON_ERROR);
}
'''


def csv_text(headers, *rows):
    stream = io.StringIO(newline='')
    writer = csv.writer(stream, lineterminator='\n')
    writer.writerow(headers)
    writer.writerows(rows)
    return stream.getvalue()


class ImportTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if shutil.which('php') is None:
            raise RuntimeError('PHP CLI is required to exercise the actual importer.')
        cls.temporary = tempfile.TemporaryDirectory(prefix='licenser-import-')
        cls.harness = Path(cls.temporary.name) / 'import.php'
        cls.harness.write_text(PHP_HARNESS)

    @classmethod
    def tearDownClass(cls):
        cls.temporary.cleanup()

    def run_import(self, *requests, seed=None, **options):
        if seed is None:
            seed = {'Product': [{'id': 1, 'name': 'Grid Master Pro', 'slug': 'grid-master-pro', 'status': 'active', 'product_type': 'plugin'},
                                {'id': 2, 'name': 'Studio Theme', 'slug': 'studio-theme', 'status': 'draft', 'product_type': 'theme'}],
                    'LicensePackage': [{'id': 10, 'product_id': 1, 'label': 'Annual', 'package_id': 'annual-uuid', 'domain_limit': 1, 'update_period': 365},
                                       {'id': 20, 'product_id': 2, 'label': 'Annual', 'package_id': 'theme-uuid', 'domain_limit': 1, 'update_period': 365}]}
        process = subprocess.run(['php', str(self.harness)], input=json.dumps(dict(root=str(ROOT), requests=requests, seed=seed, **options)),
                                 text=True, capture_output=True, cwd=ROOT, check=False)
        self.assertEqual(process.returncode, 0, process.stderr + process.stdout)
        self.assertEqual(process.stderr, '', process.stderr)
        try:
            return json.loads(process.stdout)
        except ValueError as error:
            self.fail(f'Importer emitted non-JSON output: {process.stdout!r}: {error}')

    def report(self, result, created=0, updated=0, skipped=0, errors=0, index=0):
        report = result['reports'][index]
        self.assertNotIn('error', report, report)
        self.assertEqual((report['created'], report['updated'], report['skipped'], len(report['errors'])),
                         (created, updated, skipped, errors), report)
        self.assertEqual(report['total_rows'], created + updated + skipped + errors)
        for row in report['preview']:
            self.assertTrue({'row', 'operation', 'identifier', 'summary', 'details'} <= row.keys(), row)
        return report

    def table(self, result, model):
        return list(result['tables'].get('Licenser\\Models\\' + model, {}).values())

    def test_products_plugin_theme_metadata_initial_release_and_derived_slug(self):
        data = csv_text(['name', 'slug', 'product_type', 'status', 'author_name', 'homepage_url', 'demo_url', 'icon_url', 'requires', 'tested', 'requires_php', 'description', 'banner_low', 'banner_high', 'version', 'download_link', 'file_name', 'release_date', 'changelog'],
                        ['Plugin Deluxe', 'plugin-deluxe', 'plugin', 'active', 'AddonMaster', 'https://example.com', 'https://example.com/demo', 'https://example.com/icon.png', '6.0', '6.8', '7.4', 'Useful plugin', 'https://example.com/low.png', 'https://example.com/high.png', '1.2.3', 'https://example.com/plugin.zip', 'plugin.zip', '2026-01-01', 'First release'],
                        ['Clean Studio', '', 'theme', 'draft', '', '', '', '', '', '', '', 'Minimal theme', '', '', '', '', '', '', ''])
        result = self.run_import({'entity': 'products', 'csv': data, 'dry_run': False})
        self.report(result, created=2)
        products = self.table(result, 'Product')
        plugin = next(row for row in products if row['slug'] == 'plugin-deluxe')
        theme = next(row for row in products if row['slug'] == 'clean-studio')
        self.assertEqual((plugin['product_type'], plugin['requires_php'], plugin['author_name']), ('plugin', '7.4', 'AddonMaster'))
        self.assertEqual((theme['product_type'], theme['status']), ('theme', 'draft'))
        releases = self.table(result, 'ProductRelease')
        self.assertEqual(len(releases), 1)
        self.assertEqual((releases[0]['product_id'], releases[0]['version'], releases[0]['download_link']),
                         (plugin['id'], '1.2.3', 'https://example.com/plugin.zip'))

    def test_products_aliases_defaults_duplicate_skip_and_update(self):
        data = csv_text(['title', 'slug', 'type'], ['Replacement', 'grid-master-pro', 'plugin'], ['Another', 'new-product', 'theme'])
        result = self.run_import({'entity': 'products', 'csv': data, 'dry_run': False},
                                 {'entity': 'products', 'csv': data, 'dry_run': False, 'duplicate_mode': 'update'})
        self.report(result, created=1, skipped=1)
        self.report(result, updated=2, index=1)
        self.assertEqual(next(row for row in self.table(result, 'Product') if row['id'] == 1)['name'], 'Replacement')
        default = self.run_import({'entity': 'products', 'csv': 'name\nDefault Product\n', 'dry_run': False})
        self.report(default, created=1)
        self.assertEqual(self.table(default, 'Product')[-1]['product_type'], 'plugin')

    def test_products_invalid_rows_are_validated_before_any_write(self):
        for fields, values in [(['product_type'], ['extension']), (['status'], ['published']),
                               (['homepage_url'], ['ftp://example.com/file']), (['banner_high'], ['javascript:bad()']),
                               (['download_link'], ['file:///private/file.zip'])]:
            with self.subTest(fields=fields):
                result = self.run_import({'entity': 'products', 'csv': csv_text(['name'] + fields, ['Bad product'] + values), 'dry_run': False})
                self.report(result, errors=1)
                self.assertEqual(result['writes'], [])

    def test_products_dry_run_simulates_intra_csv_duplicates_without_releases(self):
        data = csv_text(['name', 'slug', 'version', 'download_link', 'release_date'],
                        ['First', 'new', '1.0', 'https://example.com/new.zip', '2026-01-01'],
                        ['Second', 'new', '2.0', 'https://example.com/new.zip', '2026-01-02'])
        for mode, updated, skipped in [('skip', 0, 1), ('update', 1, 0)]:
            with self.subTest(mode=mode):
                result = self.run_import({'entity': 'products', 'csv': data, 'duplicate_mode': mode})
                self.report(result, created=1, updated=updated, skipped=skipped)
                self.assertTrue(result['reports'][0]['dry_run'])
                self.assertEqual(result['writes'], [])

    def test_initial_release_update_reuses_version_instead_of_duplicating(self):
        headers = ['name', 'slug', 'version', 'download_link', 'release_date', 'changelog']
        initial = csv_text(headers, ['New Product', 'new-product', '1.0', 'https://example.com/old.zip', '2026-01-01', 'Original'])
        replacement = csv_text(headers, ['New Product', 'new-product', '1.0', 'https://example.com/new.zip', '2026-02-01', 'Corrected'])
        result = self.run_import({'entity': 'products', 'csv': initial, 'dry_run': False},
                                 {'entity': 'products', 'csv': replacement, 'dry_run': False, 'duplicate_mode': 'update'})
        self.report(result, created=1)
        self.report(result, updated=1, index=1)
        releases = self.table(result, 'ProductRelease')
        self.assertEqual(len(releases), 1)
        self.assertEqual((releases[0]['download_link'], releases[0]['changelog']), ('https://example.com/new.zip', 'Corrected'))

    def test_blank_product_options_and_release_date_defaults(self):
        headers = next(csv.reader(io.StringIO((ROOT / 'docs/migrations/sample-products.csv').read_text())))
        no_release = dict.fromkeys(headers, '')
        no_release['name'] = 'No Release'
        automatic_release = dict(no_release, name='Dated Automatically', version='1.0', download_link='https://example.com/new.zip')
        data = csv_text(headers, [no_release[field] for field in headers], [automatic_release[field] for field in headers])
        for mapping in ({}, {field: field for field in headers}):
            with self.subTest(explicit_map=bool(mapping)):
                result = self.run_import({'entity': 'products', 'csv': data, 'column_map': mapping, 'dry_run': False})
                self.report(result, created=2)
                products = self.table(result, 'Product')
                blank = next(row for row in products if row['slug'] == 'no-release')
                automatic = next(row for row in products if row['slug'] == 'dated-automatically')
                self.assertEqual((blank['product_type'], blank['status']), ('plugin', 'active'))
                releases = self.table(result, 'ProductRelease')
                self.assertEqual(len(releases), 1)
                self.assertEqual(releases[0]['product_id'], automatic['id'])
                self.assertEqual(releases[0]['file_name'], 'new.zip')
                self.assertRegex(releases[0]['release_date'], r'^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$')

    def test_blank_optional_fields_with_complete_ui_license_and_package_maps(self):
        license_fields = ['license_key', 'product_id', 'product_slug', 'package_id', 'package_label', 'status',
                          'domain_limit', 'end_date', 'is_lifetime', 'is_cancelled', 'customer_email', 'customer_name',
                          'domains', 'source', 'source_id']
        license_row = dict.fromkeys(license_fields, '')
        license_row.update(license_key='BLANK-OPTIONALS', product_slug='grid-master-pro')
        package_fields = ['product_id', 'product_slug', 'label', 'package_id', 'update_period', 'domain_limit']
        package_row = dict.fromkeys(package_fields, '')
        package_row.update(product_slug='grid-master-pro', label='Blank Optionals')
        result = self.run_import(
            {'entity': 'packages', 'csv': csv_text(package_fields, [package_row[field] for field in package_fields]),
             'column_map': dict(zip(package_fields, package_fields)), 'dry_run': False},
            {'entity': 'licenses', 'csv': csv_text(license_fields, [license_row[field] for field in license_fields]),
             'column_map': dict(zip(license_fields, license_fields)), 'dry_run': False})
        self.report(result, created=1)
        self.report(result, created=1, index=1)
        package = next(row for row in self.table(result, 'LicensePackage') if row['label'] == 'Blank Optionals')
        self.assertEqual((package['update_period'], package['domain_limit']), (0, 0))
        license = self.table(result, 'License')[0]
        self.assertEqual((license['source_id'], license['status'], license['domain_limit'], license['is_lifetime']), (0, 1, 0, 0))
        self.assertIsNone(license['end_date'])
        self.assertEqual(result['domains'], [])

    def test_packages_product_slug_id_default_and_lifetime_unlimited_aliases(self):
        result = self.run_import(
            {'entity': 'packages', 'dry_run': False, 'csv': csv_text(['product_slug', 'plan_name', 'validity_days', 'sites'], ['grid-master-pro', 'Lifetime', 'lifetime', 'unlimited'])},
            {'entity': 'packages', 'dry_run': False, 'csv': csv_text(['product_id', 'label', 'update_period', 'domain_limit', 'package_id'], [2, 'Studio Lifetime', 'never', -1, 'custom-studio'])},
            {'entity': 'packages', 'dry_run': False, 'default_product_id': 1, 'csv': csv_text(['label', 'period', 'activation_limit'], ['Agency', 365, 25])})
        for index in range(3):
            self.report(result, created=1, index=index)
        packages = self.table(result, 'LicensePackage')
        lifetime = next(row for row in packages if row['label'] == 'Lifetime')
        studio = next(row for row in packages if row['label'] == 'Studio Lifetime')
        agency = next(row for row in packages if row['label'] == 'Agency')
        self.assertEqual((lifetime['product_id'], lifetime['domain_limit'], lifetime['update_period']), (1, 0, 0))
        self.assertEqual((studio['product_id'], studio['package_id'], studio['domain_limit'], studio['update_period']), (2, 'custom-studio', 0, 0))
        self.assertEqual((agency['product_id'], agency['domain_limit'], agency['update_period']), (1, 25, 365))

    def test_packages_duplicates_by_label_case_and_custom_identifier(self):
        data = csv_text(['product_id', 'label', 'package_id', 'domain_limit'],
                        [1, 'ANNUAL', '', 7], [1, 'Renamed Annual', 'annual-uuid', 8])
        skipped = self.run_import({'entity': 'packages', 'csv': data, 'dry_run': False})
        self.report(skipped, skipped=2)
        self.assertEqual(skipped['writes'], [])
        updated = self.run_import({'entity': 'packages', 'csv': data, 'dry_run': False, 'duplicate_mode': 'update'})
        self.report(updated, updated=2)
        package = next(row for row in self.table(updated, 'LicensePackage') if row['id'] == 10)
        self.assertEqual((package['label'], package['domain_limit']), ('Renamed Annual', 8))

    def test_packages_dry_run_duplicates_on_different_identity_fields(self):
        data = csv_text(['product_id', 'label', 'package_id'], [1, 'Brand New', 'new-uuid'], [1, 'brand new', ''], [1, 'Renamed', 'new-uuid'])
        result = self.run_import({'entity': 'packages', 'csv': data})
        self.report(result, created=1, skipped=2)
        self.assertEqual(result['writes'], [])

    def test_package_update_through_label_retains_uuid_for_later_duplicate(self):
        data = csv_text(['product_id', 'label', 'package_id', 'domain_limit'],
                        [1, 'Brand New', 'new-uuid', 1], [1, 'brand new', '', 2], [1, 'Renamed', 'new-uuid', 3])
        for dry_run in (True, False):
            with self.subTest(dry_run=dry_run):
                result = self.run_import({'entity': 'packages', 'csv': data, 'dry_run': dry_run, 'duplicate_mode': 'update'})
                self.report(result, created=1, updated=2)
                if dry_run:
                    self.assertEqual(result['writes'], [])
                else:
                    package = next(row for row in self.table(result, 'LicensePackage') if row['package_id'] == 'new-uuid')
                    self.assertEqual((package['label'], package['domain_limit']), ('Renamed', 3))

    def test_packages_reject_invalid_supplied_product_even_with_default(self):
        for headers, values in [(['product_id'], ['999']), (['product_slug'], ['missing']), (['product_id'], ['1.5']),
                                (['update_period'], ['-1']), (['domain_limit'], ['1.2']), (['label'], [''])]:
            with self.subTest(headers=headers):
                base = {'product_id': '1'} if headers == ['label'] else {'label': 'Bad Package'}
                base.update(zip(headers, values))
                result = self.run_import({'entity': 'packages', 'default_product_id': 1, 'dry_run': False,
                                          'csv': csv_text(list(base), list(base.values()))})
                self.report(result, errors=1)
                self.assertEqual(result['writes'], [])

    def test_licenses_presets_and_auto_detection(self):
        cases = [
            ('generic', ['license_key', 'product_id', 'domain_limit', 'customer_email'], ['GENERIC-KEY', 1, 3, 'buyer@example.com']),
            ('edd_sl', ['key', 'download', 'limit', 'expiration', 'email', 'sites'], ['EDD-KEY', 1, 4, '2030-01-01', 'edd@example.com', 'https://www.example.com/']),
            ('appsero', ['code', 'product_id', 'activation_limit', 'expires_at', 'email'], ['APPSERO-KEY', 1, 5, '2030-02-01', 'appsero@example.com']),
            ('freemius', ['secret_key', 'product_id', 'quota', 'expiration', 'user_email', 'plan_id', 'is_cancelled'], ['FREEMIUS-KEY', 1, 6, '2030-03-01', 'freemius@example.com', 10, 'true']),
        ]
        for preset, headers, values in cases:
            for selection in (preset, 'auto'):
                with self.subTest(preset=preset, selection=selection):
                    result = self.run_import({'csv': csv_text(headers, values), 'preset': selection, 'dry_run': False})
                    self.report(result, created=1)
                    record = self.table(result, 'License')[0]
                    self.assertEqual((record['license_key'], record['product_id'], record['domain_limit']), (values[0], 1, values[2]))
                    if preset == 'freemius':
                        self.assertEqual((record['package_id'], record['status']), (10, 0))

    def test_licenses_customer_metadata_package_label_domains_and_source(self):
        data = csv_text(['license_key', 'product_slug', 'package_label', 'customer_email', 'customer_name', 'domains', 'source', 'source_id', 'domain_limit', 'end_date'],
                        ['TEST-KEY', 'grid-master-pro', 'annual', 'BUYER@EXAMPLE.COM', 'Jane Buyer', 'https://www.Example.com/;example.com|https://shop.example.com/path/;shop.example.com/path', 'woocommerce', 42, 'unlimited', 'lifetime'])
        result = self.run_import({'csv': data, 'dry_run': False})
        self.report(result, created=1)
        license = self.table(result, 'License')[0]
        self.assertEqual((license['product_id'], license['package_id'], license['domain_limit'], license['is_lifetime'], license['source_id']), (1, 10, 0, 1, 42))
        self.assertEqual(result['meta'][str(license['id'])], {'customer_email': 'buyer@example.com', 'customer_name': 'Jane Buyer'})
        self.assertEqual(result['domains'][str(license['id'])], ['example.com', 'shop.example.com/path'])

    def test_column_map_overrides_generic_and_preset_and_can_ignore_optional(self):
        data = csv_text(['license_key', 'code', 'Secret Token', 'Store Product', 'email'], ['WRONG', 'ALSO-WRONG', 'RIGHT-KEY', 1, 'invalid-email'])
        mapping = {'license_key': 'Secret Token', 'product_id': 'Store Product', 'customer_email': ''}
        for column_map in (mapping, json.dumps(mapping)):
            with self.subTest(column_map=column_map):
                result = self.run_import({'csv': data, 'preset': 'appsero', 'column_map': column_map, 'dry_run': False})
                self.report(result, created=1)
                self.assertEqual(self.table(result, 'License')[0]['license_key'], 'RIGHT-KEY')
                self.assertNotIn('invalid-email', json.dumps(result['meta']))
        required = self.run_import({'csv': 'license_key,product_id\nKEY,1\n', 'column_map': {'license_key': ''}, 'dry_run': False})
        self.report(required, errors=1)
        self.assertEqual(required['writes'], [])

    def test_explicit_unmapped_fields_do_not_reappear_through_aliases(self):
        products = self.run_import({'entity': 'products', 'csv': 'name,slug\nDerived Product,wrong-slug\n',
                                    'column_map': {'slug': ''}, 'dry_run': False})
        self.report(products, created=1)
        self.assertEqual(self.table(products, 'Product')[-1]['slug'], 'derived-product')
        licenses = self.run_import({'csv': 'license_key,product_id,download,product_slug,email,sites\nKEY,999,999,missing,bad-email,ftp://bad.example\n',
                                    'preset': 'edd_sl', 'default_product_id': 1, 'dry_run': False,
                                    'column_map': {'product_id': '', 'product_slug': '', 'customer_email': '', 'domains': ''}})
        self.report(licenses, created=1)
        self.assertEqual(self.table(licenses, 'License')[0]['product_id'], 1)
        self.assertEqual(licenses['domains'], [])

    def test_invalid_mappings_and_default_product_parameters_are_rejected(self):
        invalid = [{'column_map': {'made_up_field': 'license_key'}},
                   {'column_map': {'license_key': 'missing'}},
                   {'column_map': {'license_key': ['license_key']}},
                   {'column_map': ['license_key']}, {'column_map': '17'},
                   {'default_product_id': -1}, {'default_product_id': '1.5'}, {'default_product_id': [1]},
                   {'entity': ['licenses']}, {'entity': 'products', 'preset': 'freemius'}]
        for options in invalid:
            with self.subTest(options=options):
                result = self.run_import(dict(csv='license_key,product_id\nKEY,1\n', dry_run=False, **options))
                self.assertIn('error', result['reports'][0])
                self.assertEqual(result['reports'][0]['data']['status'], 400)
                self.assertEqual(result['writes'], [])

    def test_only_explicit_boolean_false_permits_database_writes(self):
        for value in [None, True, 0, '', 'false', '0', [], {}]:
            with self.subTest(value=value):
                result = self.run_import({'csv': 'license_key,product_id\nKEY,1\n', 'dry_run': value})
                self.report(result, created=1)
                self.assertTrue(result['reports'][0]['dry_run'])
                self.assertEqual(result['writes'], [])

    def test_readback_catches_model_updates_that_return_id_without_persisting(self):
        cases = [('products', 'Product', 'name,slug\nChanged,grid-master-pro\n'),
                 ('packages', 'LicensePackage', 'product_id,label,domain_limit\n1,Annual,9\n')]
        for entity, model, data in cases:
            with self.subTest(entity=entity):
                result = self.run_import({'entity': entity, 'csv': data, 'dry_run': False, 'duplicate_mode': 'update'}, failures={model: 'no_write'})
                self.report(result, errors=1)
                self.assertEqual(len(result['writes']), 1)
        result = self.run_import({'csv': 'license_key,product_id\nKEY,1\n', 'dry_run': False}, failures={'License': 'no_write'})
        self.report(result, errors=1)
        self.assertEqual(self.table(result, 'License'), [])

    def test_partial_release_metadata_and_domain_failures_are_reported(self):
        release = self.run_import({'entity': 'products', 'csv': 'name,version,download_link,release_date\nNew,1.0,https://example.com/new.zip,2026-01-01\n',
                                   'dry_run': False}, failures={'ProductRelease': 'no_write'})
        report = self.report(release, errors=1)
        self.assertIn('release', report['errors'][0]['message'].lower())
        self.assertTrue(any(row['slug'] == 'new' for row in self.table(release, 'Product')))
        self.assertEqual(self.table(release, 'ProductRelease'), [])
        data = 'license_key,product_id,customer_email,domains\nKEY,1,buyer@example.com,example.com\n'
        for failure, expected in [('meta', 'metadata'), ('domains', 'domain')]:
            with self.subTest(failure=failure):
                result = self.run_import({'csv': data, 'dry_run': False}, failures={failure: True})
                report = self.report(result, errors=1)
                self.assertIn(expected, report['errors'][0]['message'].lower())
                self.assertEqual(len(self.table(result, 'License')), 1)
                self.assertEqual(result['domains'], [])

    def test_licenses_duplicate_skip_update_and_cross_product_guard(self):
        seed = {'Product': [{'id': 1, 'slug': 'one'}, {'id': 2, 'slug': 'two'}],
                'License': [{'id': 7, 'license_key': 'EXISTING', 'product_id': 1, 'domain_limit': 1, 'status': 1}]}
        data = csv_text(['license_key', 'product_id', 'domain_limit'], ['EXISTING', 1, 9], ['EXISTING', 2, 9])
        skip = self.run_import({'csv': data, 'dry_run': False}, seed=seed)
        self.report(skip, skipped=1, errors=1)
        self.assertEqual(skip['writes'], [])
        update = self.run_import({'csv': data, 'dry_run': False, 'duplicate_mode': 'update'}, seed=seed)
        self.report(update, updated=1, errors=1)
        self.assertEqual(self.table(update, 'License')[0]['domain_limit'], 9)

    def test_licenses_dry_run_default_and_intra_csv_duplicate_simulation(self):
        data = csv_text(['license_key', 'product_id', 'customer_name', 'domains'], ['NEW', 1, 'Buyer', 'example.com'], ['NEW', 1, 'Changed', 'second.example.com'])
        for mode, updated, skipped in [('skip', 0, 1), ('update', 1, 0)]:
            with self.subTest(mode=mode):
                result = self.run_import({'csv': data, 'duplicate_mode': mode})
                self.report(result, created=1, updated=updated, skipped=skipped)
                self.assertTrue(result['reports'][0]['dry_run'])
                self.assertEqual(result['writes'], [])
                self.assertEqual(result['domains'], [])
                self.assertEqual(result['meta'], [])

    def test_formula_neutralization_is_report_only_for_all_preview_strings(self):
        for key in ['=SUM(1)', '+FORMULA', '-FORMULA', '@FORMULA']:
            with self.subTest(key=key):
                result = self.run_import({'csv': csv_text(['license_key', 'product_id', 'customer_name'], [key, 1, '=CUSTOMER']), 'dry_run': False})
                report = self.report(result, created=1)
                self.assertEqual(report['preview'][0]['identifier'], "'" + key)
                self.assertEqual(self.table(result, 'License')[0]['license_key'], key)
                self.assertEqual(next(iter(result['meta'].values()))['customer_name'], '=CUSTOMER')

                def check(value):
                    if isinstance(value, str):
                        self.assertFalse(value.lstrip().startswith(('=', '+', '-', '@')), value)
                    elif isinstance(value, dict):
                        for item in value.values():
                            check(item)
                    elif isinstance(value, list):
                        for item in value:
                            check(item)
                check(report['preview'])

    def test_invalid_licenses_fail_before_metadata_or_domain_writes(self):
        cases = [('customer_email', 'bad-email'), ('domains', 'ftp://example.com'), ('domains', 'https://bad host.example'),
                 ('package_id', '20'), ('package_label', 'Missing Package'), ('source_id', '-1'), ('source_id', '1.5'),
                 ('domain_limit', '-2'), ('domain_limit', '2147483648'), ('status', 'mystery'), ('end_date', '2026-02-30'),
                 ('license_key', '<script>bad</script>'), ('license_key', 'x' * 192), ('product_id', '999')]
        for field, value in cases:
            with self.subTest(field=field, value=value):
                row = {'license_key': 'INVALID', 'product_id': '1', field: value}
                result = self.run_import({'csv': csv_text(list(row), list(row.values())), 'dry_run': False})
                self.report(result, errors=1)
                self.assertEqual(result['writes'], [])

    def test_expired_status_and_timestamp_expiry_and_default_product(self):
        data = csv_text(['license_key', 'status', 'end_date'], ['EXPIRED', 'expired', 'lifetime'], ['TIMESTAMP', 'active', '1893456000'])
        result = self.run_import({'csv': data, 'default_product_id': 1, 'dry_run': False})
        self.report(result, created=2)
        expired, future = self.table(result, 'License')
        self.assertEqual((expired['status'], expired['is_lifetime']), (1, 0))
        self.assertLess(expired['end_date'], datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M:%S'))
        self.assertEqual(future['end_date'], '2030-01-01 00:00:00')

    def test_bom_quotes_multiline_csv_and_row_error_numbers(self):
        data = '\ufeff' + csv_text(['license_key', 'product_id', 'customer_name'], ['KEY,WITH,COMMAS', 1, 'Jane "JJ" Buyer'], ['SECOND', 1, 'Jane\nBuyer'])
        result = self.run_import({'csv': data})
        self.report(result, created=2)
        malformed = self.run_import({'csv': 'license_key,product_id\nKEY\nGOOD,1\n'})
        report = self.report(malformed, created=1, errors=1)
        self.assertEqual(report['errors'][0]['row'], 2)
        self.assertEqual(report['preview'][0]['row'], 3)

    def test_bounds_headers_and_invalid_request_parameters(self):
        invalid = [
            {'csv': 'x' * (2 * 1024 * 1024 + 1)},
            {'csv': 'license_key,product_id\n' + ''.join(f'KEY-{i},1\n' for i in range(1001))},
            {'csv': 'license_key,license_key\nA,B\n'},
            {'csv': 'License Key,license_key\nA,B\n'},
            {'csv': 'license_key,\nKEY,1\n'}, {'csv': ''}, {'csv': ['bad']},
            {'csv': 'license_key\nKEY\n', 'preset': 'unknown'},
            {'csv': 'license_key\nKEY\n', 'entity': 'users'},
            {'csv': 'license_key\nKEY\n', 'duplicate_mode': 'replace'},
            {'csv': 'license_key\nKEY\n', 'column_map': '{bad json'},
            {'_csv_base64': base64.b64encode(b'license_key,product_id\n\xff,1\n').decode('ascii')},
        ]
        for request in invalid:
            with self.subTest(request={key: (value[:80] if isinstance(value, str) else value) for key, value in request.items()}):
                result = self.run_import(request)
                self.assertIn('error', result['reports'][0])
                self.assertEqual(result['reports'][0]['data']['status'], 400)
                self.assertEqual(result['writes'], [])

    def test_exact_row_limit_is_accepted_without_writes(self):
        result = self.run_import({'csv': 'license_key,product_id\n' + ''.join(f'KEY-{i},1\n' for i in range(1000))})
        self.report(result, created=1000)
        self.assertEqual(result['writes'], [])

    def test_admin_route_and_support_agent_permission_boundary(self):
        result = self.run_import({'csv': 'license_key,product_id\nKEY,1\n'}, permission_only=True, capabilities=['edit_posts', 'licenser_support_agent'])
        self.assertEqual(result['reports'], [False])
        self.assertEqual(result['writes'], [])
        self.assertIn({'namespace': 'licenser/v1', 'route': '/import', 'methods': 'POST', 'permission': 'create_item_permissions_check'}, result['routes'])
        permission_filter = next(item for item in result['filters'] if item[0] == 'licenser_rest_capability')
        self.assertEqual(permission_filter[1], 'manage_options')
        self.assertEqual(permission_filter[2][:2], ['import', 'create'])
        admin = self.run_import({}, permission_only=True)
        self.assertEqual(admin['reports'], [True])

    def test_full_sample_migration_from_empty_store_and_repeat_skip(self):
        requests = [{'entity': entity, 'csv': (ROOT / 'docs/migrations' / f'sample-{entity}.csv').read_text(),
                     'preset': 'generic', 'dry_run': False}
                    for entity in ('products', 'packages', 'licenses')]
        result = self.run_import(*(requests + requests), seed={})
        for index, count in enumerate((2, 5, 5)):
            self.report(result, created=count, index=index)
            self.report(result, skipped=count, index=index + 3)
        self.assertEqual(len(self.table(result, 'Product')), 2)
        self.assertEqual(len(self.table(result, 'ProductRelease')), 2)
        self.assertEqual(len(self.table(result, 'LicensePackage')), 5)
        self.assertEqual(len(self.table(result, 'License')), 5)
        products = {row['id']: row for row in self.table(result, 'Product')}
        packages = {row['id']: row for row in self.table(result, 'LicensePackage')}
        for license in self.table(result, 'License'):
            self.assertIn(license['product_id'], products)
            self.assertEqual(packages[license['package_id']]['product_id'], license['product_id'])
            self.assertEqual(result['meta'][str(license['id'])]['customer_email'].split('@')[1], 'example.com')
        agency = next(row for row in self.table(result, 'License') if 'AGENCY' in row['license_key'])
        self.assertEqual(result['domains'][str(agency['id'])], ['agency.example.com', 'client.example.com/shop'])

    def test_shipped_runtime_examples_and_prompt_exactly_match_github_docs(self):
        result = self.run_import(seed={}, migration_data=True)
        runtime = result['migration_data']
        self.assertEqual(set(runtime['samples']), {'products', 'packages', 'licenses'})
        for entity, sample in runtime['samples'].items():
            with self.subTest(entity=entity):
                self.assertEqual(sample, (ROOT / 'docs/migrations' / f'sample-{entity}.csv').read_text())
        prompt_doc = (ROOT / 'docs/migrations/ai-agent-migration-prompt.md').read_text()
        prompt = prompt_doc.split('```text\n', 1)[1].rsplit('\n```', 1)[0]
        self.assertEqual(runtime['prompt'], prompt)
        self.assertIn('https://github.com/akshuvo/licenser', prompt)


if __name__ == '__main__':
    unittest.main()
