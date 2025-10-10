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
                    <section class="tool-section" id="tools-export" aria-labelledby="tools-export-title" role="region">
                        <div class="tool-header">
                            <h3 id="tools-export-title"><span class="dashicons dashicons-download"></span> Export Data</h3>
                        </div>
                        <div class="tool-content">
                            <p>Export your plugin data for backup or migration purposes.</p>

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

                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="start-export">
                                    <span class="dashicons dashicons-download"></span> Export Data
                                </button>
                                <button type="button" class="button button-secondary" id="schedule-export">
                                    <span class="dashicons dashicons-clock"></span> Schedule Export
                                </button>
                            </div>

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
                    </section>

                    <!-- Import Tools -->
                    <section class="tool-section" id="tools-import" aria-labelledby="tools-import-title" role="region">
                        <div class="tool-header">
                            <h3 id="tools-import-title"><span class="dashicons dashicons-upload"></span> Import Data</h3>
                        </div>
                        <div class="tool-content">
                            <p>Import data from backup files or other sources.</p>

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

                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="start-import" disabled>
                                    <span class="dashicons dashicons-upload"></span> Import Data
                                </button>
                                <button type="button" class="button button-secondary" id="validate-import" disabled>
                                    <span class="dashicons dashicons-yes-alt"></span> Validate Only
                                </button>
                            </div>

                            <div class="import-results hidden" id="import-results" aria-live="polite">
                                <div class="import-summary"></div>
                            </div>
                        </div>
                    </section>
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
                        <article class="maintenance-tool" aria-labelledby="maintenance-cache-title">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="tool-info">
                                <h3 id="maintenance-cache-title">Cache Management</h3>
                                <p>Manage plugin cache and optimize performance</p>
                                <div class="cache-stats">
                                    <div class="cache-stat">
                                        <span class="stat-label">Cache Size:</span>
                                        <span class="stat-value" id="cache-size">Loading...</span>
                                    </div>
                                    <div class="cache-stat">
                                        <span class="stat-label">Cache Entries:</span>
                                        <span class="stat-value" id="cache-entries">Loading...</span>
                                    </div>
                                    <div class="cache-stat">
                                        <span class="stat-label">Hit Rate:</span>
                                        <span class="stat-value" id="cache-hit-rate">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="clear-cache">
                                    <span class="dashicons dashicons-trash"></span> Clear Cache
                                </button>
                                <button type="button" class="button button-secondary" id="optimize-cache">
                                    <span class="dashicons dashicons-performance"></span> Optimize
                                </button>
                            </div>
                        </article>

                        <!-- Database Maintenance -->
                        <article class="maintenance-tool" aria-labelledby="maintenance-database-title">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-database"></span>
                            </div>
                            <div class="tool-info">
                                <h3 id="maintenance-database-title">Database Maintenance</h3>
                                <p>Optimize database tables and clean up old data</p>
                                <div class="db-stats">
                                    <div class="db-stat">
                                        <span class="stat-label">Table Size:</span>
                                        <span class="stat-value" id="db-size">Loading...</span>
                                    </div>
                                    <div class="db-stat">
                                        <span class="stat-label">Total Records:</span>
                                        <span class="stat-value" id="db-records">Loading...</span>
                                    </div>
                                    <div class="db-stat">
                                        <span class="stat-label">Overhead:</span>
                                        <span class="stat-value" id="db-overhead">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="optimize-database">
                                    <span class="dashicons dashicons-database"></span> Optimize DB
                                </button>
                                <button type="button" class="button button-secondary" id="cleanup-old-data">
                                    <span class="dashicons dashicons-trash"></span> Clean Old Data
                                </button>
                            </div>
                        </article>

                        <!-- Log Management -->
                        <article class="maintenance-tool" aria-labelledby="maintenance-logs-title">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-media-text"></span>
                            </div>
                            <div class="tool-info">
                                <h3 id="maintenance-logs-title">Log Management</h3>
                                <p>Manage API logs, error logs, and debug information</p>
                                <div class="log-stats">
                                    <div class="log-stat">
                                        <span class="stat-label">API Logs:</span>
                                        <span class="stat-value" id="api-log-count">Loading...</span>
                                    </div>
                                    <div class="log-stat">
                                        <span class="stat-label">Error Logs:</span>
                                        <span class="stat-value" id="error-log-count">Loading...</span>
                                    </div>
                                    <div class="log-stat">
                                        <span class="stat-label">Log Size:</span>
                                        <span class="stat-value" id="total-log-size">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="archive-logs">
                                    <span class="dashicons dashicons-archive"></span> Archive Logs
                                </button>
                                <button type="button" class="button button-secondary" id="clear-old-logs">
                                    <span class="dashicons dashicons-trash"></span> Clear Old Logs
                                </button>
                            </div>
                        </article>

                        <!-- System Cleanup -->
                        <article class="maintenance-tool" aria-labelledby="maintenance-cleanup-title">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="tool-info">
                                <h3 id="maintenance-cleanup-title">System Cleanup</h3>
                                <p>Clean temporary files and optimize system performance</p>
                                <div class="cleanup-stats">
                                    <div class="cleanup-stat">
                                        <span class="stat-label">Temp Files:</span>
                                        <span class="stat-value" id="temp-files">Loading...</span>
                                    </div>
                                    <div class="cleanup-stat">
                                        <span class="stat-label">Orphaned Data:</span>
                                        <span class="stat-value" id="orphaned-data">Loading...</span>
                                    </div>
                                    <div class="cleanup-stat">
                                        <span class="stat-label">Space Used:</span>
                                        <span class="stat-value" id="space-used">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="tool-actions">
                                <button type="button" class="button button-primary" id="system-cleanup">
                                    <span class="dashicons dashicons-admin-generic"></span> Full Cleanup
                                </button>
                                <button type="button" class="button button-secondary" id="schedule-cleanup">
                                    <span class="dashicons dashicons-clock"></span> Schedule Cleanup
                                </button>
                            </div>
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
                    <section class="config-section" aria-labelledby="config-reset-title" role="region">
                        <h3 id="config-reset-title"><span class="dashicons dashicons-update"></span> Reset Options</h3>
                        <div class="reset-options">
                            <div class="reset-option">
                                <h4>Reset to Defaults</h4>
                                <p>Reset all plugin settings to their default values while preserving data.</p>
                                <button type="button" class="button button-secondary" id="reset-settings">
                                    <span class="dashicons dashicons-update"></span> Reset Settings
                                </button>
                            </div>

                            <div class="reset-option">
                                <h4>Restore Product Templates</h4>
                                <p>Recreate the default product templates if they were removed or heavily modified.</p>
                                <button type="button" class="button button-primary" id="restore-default-templates">
                                    <span class="dashicons dashicons-layout"></span> Restore Templates
                                </button>
                                <label>
                                    <input type="checkbox" id="restore-reset-selection">
                                    <span>Reset template selection to defaults</span>
                                </label>
                            </div>

                            <div class="reset-option">
                                <h4>Clear All Data</h4>
                                <p>Remove all plugin data including settings, logs, and cache. This cannot be undone.</p>
                                <button type="button" class="button button-link-delete" id="clear-all-data">
                                    <span class="dashicons dashicons-trash"></span> Clear All Data
                                </button>
                            </div>

                            <div class="reset-option">
                                <h4>Factory Reset</h4>
                                <p>Complete plugin reset - removes all data and returns to initial state.</p>
                                <button type="button" class="button button-link-delete" id="factory-reset">
                                    <span class="dashicons dashicons-warning"></span> Factory Reset
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Migration Tools -->
                    <section class="config-section" aria-labelledby="config-migration-title" role="region">
                        <h3 id="config-migration-title"><span class="dashicons dashicons-migrate"></span> Migration Tools</h3>
                        <div class="migration-tools">
                            <div class="migration-option">
                                <h4>Export Configuration</h4>
                                <p>Export plugin configuration for deployment to other sites.</p>
                                <button type="button" class="button button-primary" id="export-config">
                                    <span class="dashicons dashicons-download"></span> Export Config
                                </button>
                            </div>

                            <div class="migration-option">
                                <h4>Clone Settings</h4>
                                <p>Copy settings from another WordPress site running this plugin.</p>
                                <input type="url" id="source-site-url" placeholder="https://source-site.com" class="regular-text">
                                <button type="button" class="button button-secondary" id="clone-settings">
                                    <span class="dashicons dashicons-admin-site-alt3"></span> Clone Settings
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Optimization Tools -->
                    <section class="config-section" aria-labelledby="config-optimization-title" role="region">
                        <h3 id="config-optimization-title"><span class="dashicons dashicons-performance"></span> Optimization Tools</h3>
                        <div class="optimization-tools">
                            <div class="optimization-option">
                                <h4>Performance Scan</h4>
                                <p>Analyze plugin performance and get optimization recommendations.</p>
                                <button type="button" class="button button-primary" id="performance-scan">
                                    <span class="dashicons dashicons-performance"></span> Run Scan
                                </button>
                                <div class="scan-results hidden" id="performance-scan-results" aria-live="polite"></div>
                            </div>

                            <div class="optimization-option">
                                <h4>Auto-Optimization</h4>
                                <p>Apply recommended optimizations automatically.</p>
                                <button type="button" class="button button-primary" id="auto-optimize">
                                    <span class="dashicons dashicons-admin-generic"></span> Auto-Optimize
                                </button>
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
                        <article class="utility-tool" aria-labelledby="utility-shortcode-title">
                            <h3 id="utility-shortcode-title"><span class="dashicons dashicons-shortcode"></span> Advanced Shortcode Generator</h3>
                            <p>Generate shortcodes with advanced parameters and preview functionality.</p>
                            <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-monetizer'); ?>'">
                                <span class="dashicons dashicons-shortcode"></span> Open Generator
                            </button>
                        </article>

                        <!-- Keyword Analyzer -->
                        <article class="utility-tool" aria-labelledby="utility-keyword-title">
                            <h3 id="utility-keyword-title"><span class="dashicons dashicons-tag"></span> Keyword Analyzer</h3>
                            <p>Analyze text content and get AI-powered keyword suggestions.</p>
                            <button type="button" class="button button-primary" id="open-keyword-analyzer">
                                <span class="dashicons dashicons-tag"></span> Open Analyzer
                            </button>
                        </article>

                        <!-- Bulk Operations -->
                        <article class="utility-tool" aria-labelledby="utility-bulk-title">
                            <h3 id="utility-bulk-title"><span class="dashicons dashicons-editor-ul"></span> Bulk Operations</h3>
                            <p>Perform bulk operations on posts, keywords, and data.</p>
                            <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-scanner'); ?>'">
                                <span class="dashicons dashicons-editor-ul"></span> Open Scanner
                            </button>
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
