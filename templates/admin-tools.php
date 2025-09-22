<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-admin-tools"></span>
        Tools & Utilities
        <span class="version-badge">v2.9.4</span>
    </h1>

    <div class="yadore-tools-container">
        <!-- Data Management Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-migrate"></span> Data Management</h2>
            </div>
            <div class="card-content">
                <div class="tools-grid">
                    <!-- Export Tools -->
                    <div class="tool-section">
                        <div class="tool-header">
                            <h3><span class="dashicons dashicons-download"></span> Export Data</h3>
                        </div>
                        <div class="tool-content">
                            <p>Export your plugin data for backup or migration purposes.</p>

                            <div class="export-options">
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
                                    <label><input type="radio" name="export_format" value="json" checked> JSON</label>
                                    <label><input type="radio" name="export_format" value="csv"> CSV</label>
                                    <label><input type="radio" name="export_format" value="xml"> XML</label>
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

                                    <div id="custom-date-range" style="display: none;">
                                        <input type="date" id="export-start-date">
                                        <input type="date" id="export-end-date">
                                    </div>
                                </div>
                            </div>

                            <div class="tool-actions">
                                <button class="button button-primary" id="start-export">
                                    <span class="dashicons dashicons-download"></span> Export Data
                                </button>
                                <button class="button button-secondary" id="schedule-export">
                                    <span class="dashicons dashicons-clock"></span> Schedule Export
                                </button>
                            </div>

                            <div class="export-results" id="export-results" style="display: none;">
                                <div class="export-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="export-progress"></div>
                                    </div>
                                    <div class="progress-text" id="export-status">Preparing export...</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Import Tools -->
                    <div class="tool-section">
                        <div class="tool-header">
                            <h3><span class="dashicons dashicons-upload"></span> Import Data</h3>
                        </div>
                        <div class="tool-content">
                            <p>Import data from backup files or other sources.</p>

                            <div class="import-options">
                                <div class="file-upload">
                                    <div class="upload-area" id="import-upload-area">
                                        <div class="upload-icon">
                                            <span class="dashicons dashicons-upload"></span>
                                        </div>
                                        <div class="upload-text">
                                            <strong>Drop files here or click to upload</strong>
                                            <p>Supported formats: JSON, CSV, XML</p>
                                        </div>
                                        <input type="file" id="import-file" accept=".json,.csv,.xml" multiple style="display: none;">
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
                                <button class="button button-primary" id="start-import" disabled>
                                    <span class="dashicons dashicons-upload"></span> Import Data
                                </button>
                                <button class="button button-secondary" id="validate-import" disabled>
                                    <span class="dashicons dashicons-yes-alt"></span> Validate Only
                                </button>
                            </div>

                            <div class="import-results" id="import-results" style="display: none;">
                                <div class="import-summary"></div>
                            </div>
                        </div>
                    </div>
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
                    <div class="maintenance-grid">
                        <!-- Cache Management -->
                        <div class="maintenance-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="tool-info">
                                <h3>Cache Management</h3>
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
                                <button class="button button-primary" id="clear-cache">
                                    <span class="dashicons dashicons-trash"></span> Clear Cache
                                </button>
                                <button class="button button-secondary" id="optimize-cache">
                                    <span class="dashicons dashicons-performance"></span> Optimize
                                </button>
                            </div>
                        </div>

                        <!-- Database Maintenance -->
                        <div class="maintenance-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-database"></span>
                            </div>
                            <div class="tool-info">
                                <h3>Database Maintenance</h3>
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
                                <button class="button button-primary" id="optimize-database">
                                    <span class="dashicons dashicons-database"></span> Optimize DB
                                </button>
                                <button class="button button-secondary" id="cleanup-old-data">
                                    <span class="dashicons dashicons-trash"></span> Clean Old Data
                                </button>
                            </div>
                        </div>

                        <!-- Log Management -->
                        <div class="maintenance-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-media-text"></span>
                            </div>
                            <div class="tool-info">
                                <h3>Log Management</h3>
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
                                <button class="button button-primary" id="archive-logs">
                                    <span class="dashicons dashicons-archive"></span> Archive Logs
                                </button>
                                <button class="button button-secondary" id="clear-old-logs">
                                    <span class="dashicons dashicons-trash"></span> Clear Old Logs
                                </button>
                            </div>
                        </div>

                        <!-- System Cleanup -->
                        <div class="maintenance-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="tool-info">
                                <h3>System Cleanup</h3>
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
                                <button class="button button-primary" id="system-cleanup">
                                    <span class="dashicons dashicons-admin-generic"></span> Full Cleanup
                                </button>
                                <button class="button button-secondary" id="schedule-cleanup">
                                    <span class="dashicons dashicons-clock"></span> Schedule Cleanup
                                </button>
                            </div>
                        </div>
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
                    <div class="config-section">
                        <h3><span class="dashicons dashicons-update"></span> Reset Options</h3>
                        <div class="reset-options">
                            <div class="reset-option">
                                <h4>Reset to Defaults</h4>
                                <p>Reset all plugin settings to their default values while preserving data.</p>
                                <button class="button button-secondary" id="reset-settings">
                                    <span class="dashicons dashicons-update"></span> Reset Settings
                                </button>
                            </div>

                            <div class="reset-option">
                                <h4>Clear All Data</h4>
                                <p>Remove all plugin data including settings, logs, and cache. This cannot be undone.</p>
                                <button class="button button-link-delete" id="clear-all-data">
                                    <span class="dashicons dashicons-trash"></span> Clear All Data
                                </button>
                            </div>

                            <div class="reset-option">
                                <h4>Factory Reset</h4>
                                <p>Complete plugin reset - removes all data and returns to initial state.</p>
                                <button class="button button-link-delete" id="factory-reset">
                                    <span class="dashicons dashicons-warning"></span> Factory Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Migration Tools -->
                    <div class="config-section">
                        <h3><span class="dashicons dashicons-migrate"></span> Migration Tools</h3>
                        <div class="migration-tools">
                            <div class="migration-option">
                                <h4>Export Configuration</h4>
                                <p>Export plugin configuration for deployment to other sites.</p>
                                <button class="button button-primary" id="export-config">
                                    <span class="dashicons dashicons-download"></span> Export Config
                                </button>
                            </div>

                            <div class="migration-option">
                                <h4>Clone Settings</h4>
                                <p>Copy settings from another WordPress site running this plugin.</p>
                                <input type="url" id="source-site-url" placeholder="https://source-site.com" class="regular-text">
                                <button class="button button-secondary" id="clone-settings">
                                    <span class="dashicons dashicons-admin-site-alt3"></span> Clone Settings
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Optimization Tools -->
                    <div class="config-section">
                        <h3><span class="dashicons dashicons-performance"></span> Optimization Tools</h3>
                        <div class="optimization-tools">
                            <div class="optimization-option">
                                <h4>Performance Scan</h4>
                                <p>Analyze plugin performance and get optimization recommendations.</p>
                                <button class="button button-primary" id="performance-scan">
                                    <span class="dashicons dashicons-performance"></span> Run Scan
                                </button>
                                <div class="scan-results" id="performance-scan-results" style="display: none;"></div>
                            </div>

                            <div class="optimization-option">
                                <h4>Auto-Optimization</h4>
                                <p>Apply recommended optimizations automatically.</p>
                                <button class="button button-primary" id="auto-optimize">
                                    <span class="dashicons dashicons-admin-generic"></span> Auto-Optimize
                                </button>
                            </div>
                        </div>
                    </div>
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
                    <div class="utility-grid">
                        <!-- Shortcode Generator -->
                        <div class="utility-tool">
                            <h3><span class="dashicons dashicons-shortcode"></span> Advanced Shortcode Generator</h3>
                            <p>Generate shortcodes with advanced parameters and preview functionality.</p>
                            <button class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-monetizer'); ?>'">
                                <span class="dashicons dashicons-shortcode"></span> Open Generator
                            </button>
                        </div>

                        <!-- Keyword Analyzer -->
                        <div class="utility-tool">
                            <h3><span class="dashicons dashicons-tag"></span> Keyword Analyzer</h3>
                            <p>Analyze text content and get AI-powered keyword suggestions.</p>
                            <button class="button button-primary" id="open-keyword-analyzer">
                                <span class="dashicons dashicons-tag"></span> Open Analyzer
                            </button>
                        </div>

                        <!-- Bulk Operations -->
                        <div class="utility-tool">
                            <h3><span class="dashicons dashicons-editor-ul"></span> Bulk Operations</h3>
                            <p>Perform bulk operations on posts, keywords, and data.</p>
                            <button class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-scanner'); ?>'">
                                <span class="dashicons dashicons-editor-ul"></span> Open Scanner
                            </button>
                        </div>

                        <!-- API Tester -->
                        <div class="utility-tool">
                            <h3><span class="dashicons dashicons-admin-network"></span> API Tester</h3>
                            <p>Test API connections and debug API-related issues.</p>
                            <button class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-api-docs'); ?>'">
                                <span class="dashicons dashicons-admin-network"></span> Open Tester
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keyword Analyzer Modal -->
<div id="keyword-analyzer-modal" class="yadore-modal" style="display: none;">
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Keyword Analyzer</h2>
                <button class="modal-close">&times;</button>
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
                <div class="analyzer-results" id="analyzer-results" style="display: none;">
                    <h4>Suggested Keywords:</h4>
                    <div class="keyword-suggestions"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="button button-primary" id="analyze-keywords">
                    <span class="dashicons dashicons-search"></span> Analyze Keywords
                </button>
                <button class="button button-secondary modal-close">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize tools
    yadoreInitializeTools();

    // Load tool statistics
    yadoreLoadToolStats();
});

function yadoreInitializeTools() {
    const $ = jQuery;

    // Data management
    $('#start-export').on('click', yadoreStartExport);
    $('#start-import').on('click', yadoreStartImport);

    // Maintenance tools
    $('#clear-cache').on('click', yadoreClearCache);
    $('#optimize-database').on('click', yadoreOptimizeDatabase);
    $('#system-cleanup').on('click', yadoreSystemCleanup);

    // Configuration tools
    $('#reset-settings').on('click', yadoreResetSettings);
    $('#performance-scan').on('click', yadorePerformanceScan);

    // Utility tools
    $('#open-keyword-analyzer').on('click', function() {
        $('#keyword-analyzer-modal').show();
    });

    $('#analyze-keywords').on('click', yadoreAnalyzeKeywords);

    // Modal functionality
    $('.modal-close').on('click', function() {
        $(this).closest('.yadore-modal').hide();
    });

    // File upload
    $('#import-upload-area').on('click', function() {
        $('#import-file').click();
    }).on('dragover dragenter', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }).on('dragleave dragend drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        if (e.type === 'drop') {
            yadoreHandleFileUpload(e.originalEvent.dataTransfer.files);
        }
    });

    $('#import-file').on('change', function() {
        yadoreHandleFileUpload(this.files);
    });

    console.log('Yadore Tools v2.9 - Initialized');
}

function yadoreLoadToolStats() {
    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_get_tool_stats',
        nonce: yadore_admin.nonce
    }, function(response) {
        if (response.success) {
            const data = response.data;

            // Cache stats
            jQuery('#cache-size').text(data.cache.size);
            jQuery('#cache-entries').text(data.cache.entries.toLocaleString());
            jQuery('#cache-hit-rate').text(data.cache.hit_rate + '%');

            // Database stats
            jQuery('#db-size').text(data.database.size);
            jQuery('#db-records').text(data.database.records.toLocaleString());
            jQuery('#db-overhead').text(data.database.overhead);

            // Log stats
            jQuery('#api-log-count').text(data.logs.api_logs.toLocaleString());
            jQuery('#error-log-count').text(data.logs.error_logs.toLocaleString());
            jQuery('#total-log-size').text(data.logs.total_size);

            // Cleanup stats
            jQuery('#temp-files').text(data.cleanup.temp_files.toLocaleString());
            jQuery('#orphaned-data').text(data.cleanup.orphaned_data.toLocaleString());
            jQuery('#space-used').text(data.cleanup.space_used);
        }
    });
}
</script>