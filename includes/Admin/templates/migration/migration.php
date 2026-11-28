<?php
/**
 * CSV migration workspace.
 *
 * @package Licenser
 */

use Licenser\Admin\MigrationData;
use Licenser\Api\Import;
use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_entities = [
	'products' => __( 'Products (Plugins & Themes)', 'licenser' ),
	'packages' => __( 'License Packages', 'licenser' ),
	'licenses' => __( 'Licenses', 'licenser' ),
];
$licenser_tabs     = array_merge( $licenser_entities, [ 'ai' => __( 'AI Prompt & Samples', 'licenser' ) ] );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only workspace tab selection.
$licenser_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only workspace entity selection.
$licenser_entity = isset( $_GET['entity'] ) ? sanitize_key( wp_unslash( $_GET['entity'] ) ) : 'products';
$licenser_tab    = isset( $licenser_tabs[ $licenser_tab ] ) ? $licenser_tab : ( isset( $licenser_entities[ $licenser_entity ] ) ? $licenser_entity : 'products' );
$licenser_docs   = (string) apply_filters( 'licenser_migration_docs_url', 'https://github.com/akshuvo/licenser/tree/main/docs/migrations' );
$licenser_github = 'https://github.com/akshuvo/licenser/blob/main/docs/migrations/';
$licenser_items  = Product::instance()->get_all(
	[
		'status'  => 'all',
		'number'  => -1,
		'columns' => 'id, name',
		'orderby' => 'name',
		'order'   => 'ASC',
	]
);
$licenser_items  = is_array( $licenser_items ) ? $licenser_items : [];
$licenser_data   = [
	'samples' => MigrationData::samples(),
	'fields'  => [],
	'aliases' => [],
	'labels'  => [
		'ignore'       => __( '— Ignore —', 'licenser' ),
		'required'     => __( 'Required', 'licenser' ),
		'empty'        => __( 'No value', 'licenser' ),
		'invalid_csv'  => __( 'Use UTF-8 CSV with unique named headers, at most 1,000 rows and a maximum size of 2 MB.', 'licenser' ),
		'bad_quotes'   => __( 'The CSV has an unclosed quoted field. Check the file before continuing.', 'licenser' ),
		'bad_columns'  => __( 'A CSV row has a different number of columns than the header.', 'licenser' ),
		'utf8'         => __( 'The file is not valid UTF-8. Export it as UTF-8 CSV and try again.', 'licenser' ),
		'required_map' => __( 'Map every required field before continuing.', 'licenser' ),
		'no_errors'    => __( 'No import errors.', 'licenser' ),
		'no_preview'   => __( 'No rows were imported. Review the errors for details.', 'licenser' ),
		'dry_run'      => __( 'Dry run complete. No data was saved.', 'licenser' ),
		'imported'     => __( 'Import complete.', 'licenser' ),
		'created'      => __( 'Created', 'licenser' ),
		'updated'      => __( 'Updated', 'licenser' ),
		'skipped'      => __( 'Skipped', 'licenser' ),
		'errors'       => __( 'Errors', 'licenser' ),
		'rows'         => __( 'Rows', 'licenser' ),
		'preview'      => __( 'Dry run', 'licenser' ),
		'details'      => __( 'View details', 'licenser' ),
		'run_title'    => __( 'Run CSV Import', 'licenser' ),
		'run_body'     => __( 'This saves valid CSV rows to your site using the reviewed column mappings. Rows with errors will be reported separately. Run a dry-run preview first to review the proposed changes.', 'licenser' ),
		'run_button'   => __( 'Run Import', 'licenser' ),
		'cancel'       => __( 'Cancel', 'licenser' ),
	],
];
foreach ( array_keys( $licenser_entities ) as $licenser_key ) {
	$licenser_data['fields'][ $licenser_key ] = Import::fields( $licenser_key );
	foreach ( [ 'generic', 'edd_sl', 'appsero', 'freemius' ] as $licenser_preset ) {
		$licenser_data['aliases'][ $licenser_key ][ $licenser_preset ] = Import::aliases( $licenser_key, $licenser_preset, [] );
	}
}
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app licenser-migration" data-licenser-migration="<?php echo esc_attr( wp_json_encode( $licenser_data ) ); ?>">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true"><span class="dashicons dashicons-upload"></span></span>
				<div class="licenser-topbar__heading"><h1><?php esc_html_e( 'Migration & Import', 'licenser' ); ?></h1></div>
			</div>
			<div class="licenser-topbar__actions"><a class="licenser-btn licenser-btn--secondary" href="<?php echo esc_url( $licenser_docs ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-book" aria-hidden="true"></span><?php esc_html_e( 'Migration Guide on GitHub', 'licenser' ); ?></a></div>
		</div>
		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>
			<div class="licenser-settings-tabs-bar">
				<div class="licenser-pills" role="tablist" data-licenser-tab-sync="1" aria-label="<?php esc_attr_e( 'Migration steps', 'licenser' ); ?>">
					<?php $licenser_step = 0; ?>
					<?php foreach ( $licenser_tabs as $licenser_key => $licenser_label ) : ?>
						<?php ++$licenser_step; ?>
						<button type="button" id="licenser-tab-btn-<?php echo esc_attr( $licenser_key ); ?>" class="licenser-pill <?php echo $licenser_tab === $licenser_key ? 'is-active' : ''; ?>" tabindex="<?php echo $licenser_tab === $licenser_key ? '0' : '-1'; ?>" data-licenser-tab="<?php echo esc_attr( $licenser_key ); ?>" data-licenser-tab-sync="1" role="tab" aria-controls="licenser-tab-<?php echo esc_attr( $licenser_key ); ?>" aria-selected="<?php echo $licenser_tab === $licenser_key ? 'true' : 'false'; ?>"><span class="dashicons <?php echo 'ai' === $licenser_key ? 'dashicons-editor-code' : 'dashicons-upload'; ?>" aria-hidden="true"></span><?php echo esc_html( $licenser_step . '. ' . $licenser_label ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="licenser-migration-intro"><?php esc_html_e( 'Move your catalog and customers in three steps: products, then packages, then licenses. Review mappings and run a dry run before saving.', 'licenser' ); ?></p>
			<?php foreach ( $licenser_entities as $licenser_key => $licenser_label ) : ?>
				<div id="licenser-tab-<?php echo esc_attr( $licenser_key ); ?>" class="licenser-tab-panel <?php echo $licenser_tab === $licenser_key ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-<?php echo esc_attr( $licenser_key ); ?>">
					<form class="licenser-import-form" data-entity="<?php echo esc_attr( $licenser_key ); ?>">
						<section class="licenser-card">
							<div class="licenser-card__header"><div class="licenser-card__title-group"><h2><?php echo esc_html( $licenser_label ); ?></h2><p class="licenser-card__subtitle"><?php esc_html_e( 'Step-by-step import · Choose your source, review columns, preview, then import.', 'licenser' ); ?></p></div></div>
							<div class="licenser-card__body licenser-import-sections">
								<div>
									<h3><?php esc_html_e( 'Source & Options', 'licenser' ); ?></h3>
									<div class="licenser-form-grid licenser-form-grid--3">
										<div class="licenser-field"><label for="licenser-preset-<?php echo esc_attr( $licenser_key ); ?>"><?php esc_html_e( 'Preset', 'licenser' ); ?></label><select id="licenser-preset-<?php echo esc_attr( $licenser_key ); ?>" name="preset"><option value="auto"><?php esc_html_e( 'Auto Detect', 'licenser' ); ?></option><option value="generic"><?php esc_html_e( 'Generic / Licenser', 'licenser' ); ?></option>
										<?php
										if ( 'licenses' === $licenser_key ) :
											?>
											<option value="edd_sl">EDD Software Licensing</option><option value="appsero">Appsero</option><option value="freemius">Freemius</option><?php endif; ?></select></div>
										<?php if ( 'products' !== $licenser_key ) : ?>
											<div class="licenser-field"><label for="licenser-default-<?php echo esc_attr( $licenser_key ); ?>"><?php esc_html_e( 'Default Product', 'licenser' ); ?></label><select id="licenser-default-<?php echo esc_attr( $licenser_key ); ?>" name="default_product_id"><option value="0"><?php esc_html_e( 'Use CSV product ID or slug', 'licenser' ); ?></option>
											<?php
											foreach ( $licenser_items as $licenser_product ) :
												?>
												<option value="<?php echo esc_attr( (string) $licenser_product->id ); ?>"><?php echo esc_html( $licenser_product->name ); ?></option><?php endforeach; ?></select></div>
										<?php endif; ?>
										<div class="licenser-field"><label for="licenser-duplicates-<?php echo esc_attr( $licenser_key ); ?>"><?php esc_html_e( 'Duplicate Mode', 'licenser' ); ?></label><select id="licenser-duplicates-<?php echo esc_attr( $licenser_key ); ?>" name="duplicate_mode"><option value="skip"><?php esc_html_e( 'Skip Existing', 'licenser' ); ?></option><option value="update"><?php esc_html_e( 'Update Existing', 'licenser' ); ?></option></select></div>
									</div>
								</div>
								<div class="licenser-import-sample-bar"><div><strong><?php esc_html_e( 'Start with a sample', 'licenser' ); ?></strong><p><?php esc_html_e( 'Use the exact schema, or load an example to explore the preview.', 'licenser' ); ?></p></div><div class="licenser-import-actions"><button type="button" class="licenser-btn licenser-btn--secondary" data-import-download="<?php echo esc_attr( $licenser_key ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Download Sample CSV', 'licenser' ); ?></button><button type="button" class="licenser-btn licenser-btn--secondary" data-import-load><?php esc_html_e( 'Load Sample into Editor', 'licenser' ); ?></button><a href="<?php echo esc_url( $licenser_github . 'sample-' . $licenser_key . '.csv' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on GitHub', 'licenser' ); ?></a></div></div>
								<div class="licenser-field"><label for="licenser-file-<?php echo esc_attr( $licenser_key ); ?>"><?php esc_html_e( 'CSV Upload & Paste', 'licenser' ); ?></label><input type="file" id="licenser-file-<?php echo esc_attr( $licenser_key ); ?>" class="licenser-file-input" accept=".csv,text/csv" aria-describedby="licenser-csv-help-<?php echo esc_attr( $licenser_key ); ?>" /><span id="licenser-csv-help-<?php echo esc_attr( $licenser_key ); ?>" class="description"><?php esc_html_e( 'UTF-8 · Maximum 2 MB · Up to 1,000 data rows · Your file stays in this editor until you preview or import.', 'licenser' ); ?></span></div>
								<div class="licenser-field"><label class="screen-reader-text" for="licenser-csv-<?php echo esc_attr( $licenser_key ); ?>"><?php esc_html_e( 'CSV contents', 'licenser' ); ?></label><textarea id="licenser-csv-<?php echo esc_attr( $licenser_key ); ?>" class="licenser-import-editor" name="csv" rows="10" spellcheck="false" placeholder="<?php esc_attr_e( 'Paste your CSV, upload a file, or load the sample above…', 'licenser' ); ?>"></textarea></div>
								<p class="licenser-import-validation" role="status" aria-live="polite" hidden></p>
								<section class="licenser-card licenser-import-mapping" hidden><div class="licenser-card__header"><div class="licenser-card__title-group"><h3><?php esc_html_e( 'Review Column Mapping', 'licenser' ); ?></h3><p class="licenser-card__subtitle"><?php esc_html_e( 'Check each match against the first data row. Choose Ignore to leave an optional field unmapped. Product references must identify an existing product.', 'licenser' ); ?></p></div></div><div class="licenser-card__body--flush licenser-import-table-scroll"><table class="licenser-mini-table"><thead><tr><th scope="col"><?php esc_html_e( 'Licenser Field', 'licenser' ); ?></th><th scope="col"><?php esc_html_e( 'Mapped CSV Column', 'licenser' ); ?></th><th scope="col"><?php esc_html_e( 'Sample Value (Row 1)', 'licenser' ); ?></th></tr></thead><tbody></tbody></table></div></section>
							</div>
							<div class="licenser-card__footer licenser-import-actions"><span class="licenser-import-footer-note"><?php esc_html_e( 'Dry runs validate and simulate changes without saving.', 'licenser' ); ?></span><button type="button" class="licenser-btn licenser-btn--secondary" data-import-run="preview"><?php esc_html_e( 'Dry-Run Preview', 'licenser' ); ?></button><button type="button" class="licenser-btn licenser-btn--primary" data-import-run="save"><?php esc_html_e( 'Run Import', 'licenser' ); ?></button></div>
						</section>
						<div class="licenser-import-results" hidden aria-live="polite"><div class="licenser-import-summary" role="status"></div><div class="licenser-form-grid licenser-form-grid--2"><section class="licenser-card"><div class="licenser-card__header"><h2><?php esc_html_e( 'Preview', 'licenser' ); ?></h2></div><div class="licenser-card__body--flush licenser-import-table-scroll"><table class="licenser-mini-table licenser-import-preview"><thead><tr><th scope="col"><?php esc_html_e( 'Row', 'licenser' ); ?></th><th scope="col"><?php esc_html_e( 'Operation', 'licenser' ); ?></th><th scope="col"><?php esc_html_e( 'Identifier & Summary', 'licenser' ); ?></th></tr></thead><tbody></tbody></table></div></section><section class="licenser-card"><div class="licenser-card__header"><h2><?php esc_html_e( 'Import Errors', 'licenser' ); ?></h2></div><div class="licenser-card__body--flush licenser-import-table-scroll"><table class="licenser-mini-table licenser-import-errors"><thead><tr><th scope="col"><?php esc_html_e( 'Row', 'licenser' ); ?></th><th scope="col"><?php esc_html_e( 'Message', 'licenser' ); ?></th></tr></thead><tbody></tbody></table></div></section></div></div>
					</form>
				</div>
			<?php endforeach; ?>
			<div id="licenser-tab-ai" class="licenser-tab-panel <?php echo 'ai' === $licenser_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-ai">
				<section class="licenser-card"><div class="licenser-card__header"><div class="licenser-card__title-group"><h2><?php esc_html_e( 'Convert Any CSV with an AI Agent', 'licenser' ); ?></h2><p class="licenser-card__subtitle"><?php esc_html_e( 'Copy this prompt into Claude, ChatGPT, Codex, Cursor or Antigravity alongside your existing export. Review the converted files here before importing.', 'licenser' ); ?></p></div><a href="<?php echo esc_url( $licenser_github . 'ai-agent-migration-prompt.md' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Prompt on GitHub', 'licenser' ); ?></a></div><div class="licenser-card__body"><div class="licenser-code-window"><div class="licenser-code-chrome"><div class="licenser-code-chrome__left"><span class="licenser-window-dots" aria-hidden="true"><span></span><span></span><span></span></span><span class="licenser-code-chrome__file">migration-prompt.md</span></div><button type="button" class="licenser-dark-btn licenser-copy-btn" data-licenser-copy-target="#licenser-migration-prompt"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="licenser-copy-label"><?php esc_html_e( 'Copy Prompt', 'licenser' ); ?></span></button></div><pre id="licenser-migration-prompt" class="licenser-migration-prompt"><?php echo esc_html( MigrationData::prompt() ); ?></pre></div></div></section>
				<h2 class="licenser-import-samples-heading"><?php esc_html_e( 'Sample CSV Templates', 'licenser' ); ?></h2>
				<div class="licenser-form-grid licenser-form-grid--3">
					<?php foreach ( $licenser_entities as $licenser_key => $licenser_label ) : ?>
						<section class="licenser-card"><div class="licenser-card__header"><h3><?php echo esc_html( $licenser_label ); ?></h3></div><div class="licenser-card__body"><code><?php echo esc_html( 'sample-' . $licenser_key . '.csv' ); ?></code></div><div class="licenser-card__footer licenser-import-actions"><button type="button" class="licenser-btn licenser-btn--secondary" data-import-download="<?php echo esc_attr( $licenser_key ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Download CSV', 'licenser' ); ?></button><a href="<?php echo esc_url( $licenser_github . 'sample-' . $licenser_key . '.csv' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View on GitHub', 'licenser' ); ?></a></div></section>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
