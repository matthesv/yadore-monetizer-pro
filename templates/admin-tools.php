<div class="wrap yadore-admin-wrap">
    <?php
    $tools_actions = array(
        array(
            'label' => esc_html__('Backup Wizard starten', 'yadore-monetizer'),
            'url' => '#tools-export',
            'type' => 'primary',
            'icon' => 'dashicons-migrate',
        ),
        array(
            'label' => esc_html__('Debug-Logs prüfen', 'yadore-monetizer'),
            'url' => admin_url('admin.php?page=yadore-debug'),
            'type' => 'ghost',
            'icon' => 'dashicons-admin-network',
        ),
    );

    $supported_formats = array();
    if (isset($data_formats) && is_array($data_formats)) {
        foreach ($data_formats as $format_key => $format_config) {
            $key = sanitize_key(is_string($format_key) ? $format_key : '');
            $extension = '';
            if (isset($format_config['extension'])) {
                $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $format_config['extension']));
            }

            if ($extension === '') {
                $extension = $key;
            }

            $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $extension));
            if ($extension === '') {
                continue;
            }

            if ($key === '') {
                $key = $extension;
            }

            $label = isset($format_config['label']) ? $format_config['label'] : strtoupper($extension);

            $supported_formats[$key] = array(
                'label' => $label,
                'extension' => $extension,
            );
        }
    }

    if (empty($supported_formats)) {
        $supported_formats = array(
            'json' => array(
                'label' => __('JSON', 'yadore-monetizer'),
                'extension' => 'json',
            ),
            'csv' => array(
                'label' => __('CSV', 'yadore-monetizer'),
                'extension' => 'csv',
            ),
            'xml' => array(
                'label' => __('XML', 'yadore-monetizer'),
                'extension' => 'xml',
            ),
        );
    }

    $format_extensions = array();
    $format_labels_display = array();
    foreach ($supported_formats as $supported) {
        $extension = isset($supported['extension']) ? $supported['extension'] : '';
        if ($extension !== '') {
            $format_extensions[] = $extension;
        }

        $label = isset($supported['label']) ? $supported['label'] : '';
        if ($label !== '') {
            $format_labels_display[] = $label;
        }
    }

    $default_format = array_key_exists('json', $supported_formats) ? 'json' : array_key_first($supported_formats);
    $import_extensions_attr = implode(',', $format_extensions);
    $import_accept_raw = $format_extensions ? '.' . implode(',.', $format_extensions) : '';
    $import_formats_text = $format_labels_display ? implode(', ', $format_labels_display) : __('JSON, CSV, XML', 'yadore-monetizer');

    $tools_meta = array(
        array(
            'label' => esc_html__('Datenexporte', 'yadore-monetizer'),
            'value' => esc_html($import_formats_text),
            'description' => esc_html__('Sichere Einstellungen, Keywords und Analytics.', 'yadore-monetizer'),
            'icon' => 'dashicons-download',
            'state' => 'info',
        ),
        array(
            'label' => esc_html__('Systemwartung', 'yadore-monetizer'),
            'value' => esc_html__('Cache · Logs · Reset', 'yadore-monetizer'),
            'description' => esc_html__('Optimierungs-Tools für saubere Installationen.', 'yadore-monetizer'),
            'icon' => 'dashicons-hammer',
            'state' => 'success',
        ),
        array(
            'label' => esc_html__('Zugriff', 'yadore-monetizer'),
            'value' => esc_html__('Nur Administrator:innen', 'yadore-monetizer'),
            'description' => esc_html__('Protokolliert jede kritische Aktion.', 'yadore-monetizer'),
            'icon' => 'dashicons-shield-alt',
            'state' => 'neutral',
        ),
    );

    $page_header = array(
        'slug' => 'tools',
        'eyebrow' => esc_html__('Maintenance Suite', 'yadore-monetizer'),
        'icon' => 'dashicons-admin-tools',
        'title' => esc_html__('Tools & Utilities', 'yadore-monetizer'),
        'subtitle' => esc_html__('Exportiere Daten, optimiere Caches und verwalte Integrationen mit modernen Sicherheitsnetzen.', 'yadore-monetizer'),
        'version' => YADORE_PLUGIN_VERSION,
        'actions' => $tools_actions,
        'meta' => $tools_meta,
    );
    ?>

    <div class="yadore-admin-shell">
        <?php include __DIR__ . '/partials/admin-page-header.php'; ?>

        <div class="yadore-admin-content">
            <div class="yadore-tools-container">
                <!-- Refactor note: the tools layout now relies on yadore-card-grid utilities for consistent spacing across breakpoints. -->
        <!-- Data Management Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-migrate"></span> Data Management</h2>
            </div>
            <div class="card-content">
                <div class="yadore-card-grid tools-grid">
                    <!-- Export Tools -->
                    <article class="tool-section" id="tools-export" aria-labelledby="tools-export-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-download" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="tools-export-title">Export Data</h3>
                                <p class="tool-section__description">Export your plugin data for backup or migration purposes.</p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="export-options yadore-card-grid" data-variant="compact">
                                <div class="option-group">
                                    <h4>Data Types</h4>
                                    <label><input type="checkbox" name="export_data[]" value="settings" checked> Plugin Settings</label>
                                    <label><input type="checkbox" name="export_data[]" value="keywords" checked> Post Keywords</label>
                                    <label><input type="checkbox" name="export_data[]" value="analytics" checked> Analytics Data</label>
                                    <label><input type="checkbox" name="export_data[]" value="logs"> API Logs</label>
                                    <label><input type="checkbox" name="export_data[]" value="cache"> AI Cache</label>
                                </div>

                                <div class="option-group">
                                    <h4>Format</h4>
                                    <?php foreach ($supported_formats as $format_key => $format_config) :
                                        $label = isset($format_config['label']) ? $format_config['label'] : strtoupper($format_key);
                                    ?>
                                        <label>
                                            <input type="radio" name="export_format" value="<?php echo esc_attr($format_key); ?>" <?php checked($format_key, $default_format); ?>>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="option-group">
                                    <h4>Date Range</h4>
                                    <select id="export-date-range">
                                        <option value="all">All Data</option>
                                        <option value="30">Last 30 Days</option>
                                        <option value="90">Last 90 Days</option>
                                        <option value="365">Last Year</option>
                                        <option value="custom">Custom Range</option>
                                    </select>

                                    <div id="custom-date-range" class="tool-date-range hidden" aria-hidden="true">
                                        <input type="date" id="export-start-date">
                                        <input type="date" id="export-end-date">
                                    </div>
                                </div>

                                <div class="option-group">
                                    <h4>Schedule Options</h4>
                                    <label for="export-schedule-interval">Frequency</label>
                                    <select id="export-schedule-interval">
                                        <option value="daily" selected>Daily</option>
                                        <option value="twicedaily">Twice Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="hourly">Hourly</option>
                                    </select>

                                    <label for="export-schedule-time">Time of day</label>
                                    <input type="time" id="export-schedule-time" value="02:00">
                                    <p class="description">Schedule recurring exports with the selected cadence.</p>
                                </div>
                            </div>
                        </div>
                        <footer class="tool-section__footer tool-actions">
                            <button type="button" class="button button-primary" id="start-export">
                                <span class="dashicons dashicons-download"></span> Export Data
                            </button>
                            <button type="button" class="button button-secondary" id="schedule-export">
                                <span class="dashicons dashicons-clock"></span> Schedule Export
                            </button>
                        </footer>
                        <div class="tool-section__status">
                            <div class="export-results hidden" id="export-results" aria-live="polite">
                                <div class="export-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="export-progress"></div>
                                    </div>
                                    <div class="progress-text" id="export-status">Preparing export...</div>
                                </div>
                            </div>
                            <div class="export-schedule-status" id="export-schedule-status" aria-live="polite"></div>
                        </div>
                    </article>

                    <!-- Import Tools -->
                    <article class="tool-section" id="tools-import" aria-labelledby="tools-import-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-upload" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="tools-import-title">Import Data</h3>
                                <p class="tool-section__description">Import data from backup files or other sources.</p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="import-options yadore-card-grid" data-variant="compact">
                                <div class="file-upload">
                                    <?php $upload_instructions_id = 'import-upload-instructions'; ?>
                                    <div
                                        class="upload-area"
                                        id="import-upload-area"
                                        role="button"
                                        tabindex="0"
                                        aria-label="<?php echo esc_attr__('Select files to import', 'yadore-monetizer'); ?>"
                                        aria-describedby="<?php echo esc_attr($upload_instructions_id); ?>"
                                        data-import-extensions="<?php echo esc_attr($import_extensions_attr); ?>"
                                        data-import-labels="<?php echo esc_attr($import_formats_text); ?>"
                                    >
                                        <div class="upload-icon">
                                            <span class="dashicons dashicons-upload"></span>
                                        </div>
                                        <div class="upload-text">
                                            <strong><?php esc_html_e('Drop files here or click to upload', 'yadore-monetizer'); ?></strong>
                                            <p id="<?php echo esc_attr($upload_instructions_id); ?>"><?php printf(esc_html__('Supported formats: %s', 'yadore-monetizer'), esc_html($import_formats_text)); ?></p>
                                            <button
                                                type="button"
                                                class="button button-secondary upload-trigger"
                                                id="import-upload-button"
                                                aria-controls="import-file"
                                            >
                                                <span class="dashicons dashicons-media-default" aria-hidden="true"></span>
                                                <?php esc_html_e('Choose files', 'yadore-monetizer'); ?>
                                            </button>
                                        </div>
                                        <input
                                            type="file"
                                            id="import-file"
                                            class="yadore-hidden-file-input screen-reader-text"
                                            accept="<?php echo esc_attr($import_accept_raw); ?>"
                                            multiple
                                        >
                                    </div>
                                </div>

                                <div class="import-settings">
                                    <h4>Import Options</h4>
                                    <label><input type="checkbox" name="import_options[]" value="overwrite"> Overwrite existing data</label>
                                    <label><input type="checkbox" name="import_options[]" value="validate" checked> Validate data before import</label>
                                    <label><input type="checkbox" name="import_options[]" value="backup" checked> Create backup before import</label>
                                </div>
                            </div>
                        </div>
                        <footer class="tool-section__footer tool-actions">
                            <button type="button" class="button button-primary" id="start-import" disabled>
                                <span class="dashicons dashicons-upload"></span> Import Data
                            </button>
                            <button type="button" class="button button-secondary" id="validate-import" disabled>
                                <span class="dashicons dashicons-yes-alt"></span> Validate Only
                            </button>
                        </footer>
                        <div class="tool-section__status">
                            <div class="import-results hidden" id="import-results" aria-live="polite">
                                <div class="import-summary"></div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <!-- Maintenance Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> Maintenance Tools</h2>
            </div>
            <div class="card-content">
                <div class="maintenance-tools">
                    <div class="yadore-card-grid maintenance-grid">
                        <!-- Cache Management -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-cache-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-performance" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-cache-title">Cache Management</h3>
                                    <p class="tool-section__description">Manage plugin cache and optimize performance.</p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Cache Size</dt>
                                        <dd class="tool-metric__value" id="cache-size">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Cache Entries</dt>
                                        <dd class="tool-metric__value" id="cache-entries">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Hit Rate</dt>
                                        <dd class="tool-metric__value" id="cache-hit-rate">Loading...</dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="clear-cache">
                                    <span class="dashicons dashicons-trash"></span> Clear Cache
                                </button>
                                <button type="button" class="button button-secondary" id="optimize-cache">
                                    <span class="dashicons dashicons-performance"></span> Optimize
                                </button>
                            </footer>
                        </article>

                        <!-- Database Maintenance -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-database-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-database" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-database-title">Database Maintenance</h3>
                                    <p class="tool-section__description">Optimize database tables and clean up old data.</p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Table Size</dt>
                                        <dd class="tool-metric__value" id="db-size">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Total Records</dt>
                                        <dd class="tool-metric__value" id="db-records">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Overhead</dt>
                                        <dd class="tool-metric__value" id="db-overhead">Loading...</dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="optimize-database">
                                    <span class="dashicons dashicons-database"></span> Optimize DB
                                </button>
                                <button type="button" class="button button-secondary" id="cleanup-old-data">
                                    <span class="dashicons dashicons-trash"></span> Clean Old Data
                                </button>
                            </footer>
                        </article>

                        <!-- Log Management -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-logs-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-media-text" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-logs-title">Log Management</h3>
                                    <p class="tool-section__description">Manage API logs, error logs, and debug information.</p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">API Logs</dt>
                                        <dd class="tool-metric__value" id="api-log-count">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Error Logs</dt>
                                        <dd class="tool-metric__value" id="error-log-count">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Log Size</dt>
                                        <dd class="tool-metric__value" id="total-log-size">Loading...</dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="archive-logs">
                                    <span class="dashicons dashicons-archive"></span> Archive Logs
                                </button>
                                <button type="button" class="button button-secondary" id="clear-old-logs">
                                    <span class="dashicons dashicons-trash"></span> Clear Old Logs
                                </button>
                            </footer>
                        </article>

                        <!-- System Cleanup -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-cleanup-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-admin-generic" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-cleanup-title">System Cleanup</h3>
                                    <p class="tool-section__description">Clean temporary files and optimize system performance.</p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Temp Files</dt>
                                        <dd class="tool-metric__value" id="temp-files">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Orphaned Data</dt>
                                        <dd class="tool-metric__value" id="orphaned-data">Loading...</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label">Space Used</dt>
                                        <dd class="tool-metric__value" id="space-used">Loading...</dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="system-cleanup">
                                    <span class="dashicons dashicons-admin-generic"></span> Full Cleanup
                                </button>
                                <button type="button" class="button button-secondary" id="schedule-cleanup">
                                    <span class="dashicons dashicons-clock"></span> Schedule Cleanup
                                </button>
                            </footer>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-settings"></span> Configuration Tools</h2>
            </div>
            <div class="card-content">
                <div class="config-tools">
                    <!-- Reset Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-reset-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-update" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-reset-title">Reset Options</h3>
                                <p class="tool-section__description">Return to known-good defaults or clean installations without leaving the tools screen.</p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group reset-options">
                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4>Reset to Defaults</h4>
                                        <p>Reset all plugin settings to their default values while preserving data.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-secondary" id="reset-settings">
                                            <span class="dashicons dashicons-update"></span> Reset Settings
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4>Restore Product Templates</h4>
                                        <p>Recreate the default product templates if they were removed or heavily modified.</p>
                                        <label>
                                            <input type="checkbox" id="restore-reset-selection">
                                            <span>Reset template selection to defaults</span>
                                        </label>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="restore-default-templates">
                                            <span class="dashicons dashicons-layout"></span> Restore Templates
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4>Clear All Data</h4>
                                        <p>Remove all plugin data including settings, logs, and cache. This cannot be undone.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-link-delete" id="clear-all-data">
                                            <span class="dashicons dashicons-trash"></span> Clear All Data
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4>Factory Reset</h4>
                                        <p>Complete plugin reset - removes all data and returns to initial state.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-link-delete" id="factory-reset">
                                            <span class="dashicons dashicons-warning"></span> Factory Reset
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <!-- Migration Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-migration-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-migrate" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-migration-title">Migration Tools</h3>
                                <p class="tool-section__description">Move configurations between environments with predictable outcomes.</p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group migration-tools">
                                <article class="tool-option migration-option">
                                    <div class="tool-option__content">
                                        <h4>Export Configuration</h4>
                                        <p>Export plugin configuration for deployment to other sites.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="export-config">
                                            <span class="dashicons dashicons-download"></span> Export Config
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option migration-option">
                                    <div class="tool-option__content">
                                        <h4>Clone Settings</h4>
                                        <p>Copy settings from another WordPress site running this plugin.</p>
                                        <input type="url" id="source-site-url" placeholder="https://source-site.com" class="regular-text">
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-secondary" id="clone-settings">
                                            <span class="dashicons dashicons-admin-site-alt3"></span> Clone Settings
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <!-- Optimization Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-optimization-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-performance" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-optimization-title">Optimization Tools</h3>
                                <p class="tool-section__description">Diagnose and automate performance improvements.</p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group optimization-tools">
                                <article class="tool-option optimization-option">
                                    <div class="tool-option__content">
                                        <h4>Performance Scan</h4>
                                        <p>Analyze plugin performance and get optimization recommendations.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="performance-scan">
                                            <span class="dashicons dashicons-performance"></span> Run Scan
                                        </button>
                                    </div>
                                    <div class="tool-option__status">
                                        <div class="scan-results hidden" id="performance-scan-results" aria-live="polite"></div>
                                    </div>
                                </article>

                                <article class="tool-option optimization-option">
                                    <div class="tool-option__content">
                                        <h4>Auto-Optimization</h4>
                                        <p>Apply recommended optimizations automatically.</p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="auto-optimize">
                                            <span class="dashicons dashicons-admin-generic"></span> Auto-Optimize
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Utility Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> Utility Tools</h2>
            </div>
            <div class="card-content">
                <div class="utility-tools">
                    <div class="yadore-card-grid utility-grid">
                        <!-- Shortcode Generator -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-shortcode-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-shortcode-title">Advanced Shortcode Generator</h3>
                                    <p class="tool-section__description">Generate shortcodes with advanced parameters and preview functionality.</p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-monetizer'); ?>'">
                                    <span class="dashicons dashicons-shortcode"></span> Open Generator
                                </button>
                            </footer>
                        </article>

                        <!-- Keyword Analyzer -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-keyword-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-tag" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-keyword-title">Keyword Analyzer</h3>
                                    <p class="tool-section__description">Analyze text content and get AI-powered keyword suggestions.</p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="open-keyword-analyzer">
                                    <span class="dashicons dashicons-tag"></span> Open Analyzer
                                </button>
                            </footer>
                        </article>

                        <!-- Bulk Operations -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-bulk-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-editor-ul" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-bulk-title">Bulk Operations</h3>
                                    <p class="tool-section__description">Perform bulk operations on posts, keywords, and data.</p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-scanner'); ?>'">
                                    <span class="dashicons dashicons-editor-ul"></span> Open Scanner
                                </button>
                            </footer>
                        </article>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keyword Analyzer Modal -->
<div
    id="keyword-analyzer-modal"
    class="yadore-modal hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="keyword-analyzer-title"
    aria-hidden="true"
>
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="keyword-analyzer-title">Keyword Analyzer</h2>
                <button class="modal-close" type="button" aria-label="Close keyword analyzer">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analyzer-input">
                    <label for="analyzer-text">Text to Analyze:</label>
                    <textarea id="analyzer-text" rows="8" placeholder="Paste your content here for keyword analysis..."></textarea>
                </div>
                <div class="analyzer-options">
                    <label>
                        <input type="checkbox" id="use-ai-analyzer" <?php checked(get_option('yadore_ai_enabled', false)); ?>> Use AI Analysis
                    </label>
                    <label>
                        Max Keywords: <input type="number" id="max-keywords" min="1" max="20" value="5">
                    </label>
                </div>
                <div class="analyzer-results hidden" id="analyzer-results" aria-live="polite">
                    <h4>Suggested Keywords:</h4>
                    <div class="keyword-suggestions"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-primary" id="analyze-keywords">
                    <span class="dashicons dashicons-search"></span> Analyze Keywords
                </button>
                <button type="button" class="button button-secondary modal-close">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function() {
    if (window.yadoreAdmin && typeof window.yadoreAdmin.initTools === 'function') {
        window.yadoreAdmin.initTools();
    }

    if (window.yadoreAdmin && typeof window.yadoreAdmin.loadToolStats === 'function') {
        window.yadoreAdmin.loadToolStats();
    }
});
</script>
