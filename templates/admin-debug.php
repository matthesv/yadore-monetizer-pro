<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-admin-tools"></span>
        Debug & Error Analysis
        <span class="version-badge">v2.9.16</span>
    </h1>

    <div class="yadore-debug-container">
        <!-- System Health Overview -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-dashboard"></span> System Health Overview</h2>
                <div class="card-actions">
                    <button class="button button-secondary" id="refresh-health">
                        <span class="dashicons dashicons-update"></span> Refresh Status
                    </button>
                    <button class="button button-primary" id="run-diagnostics">
                        <span class="dashicons dashicons-admin-tools"></span> Run Full Diagnostics
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="health-status-grid">
                    <div class="health-item">
                        <div class="health-icon status-active">
                            <span class="dashicons dashicons-wordpress-alt"></span>
                        </div>
                        <div class="health-details">
                            <h3>WordPress Core</h3>
                            <p class="health-status">All systems operational</p>
                            <p class="health-info">Version: <?php echo get_bloginfo('version'); ?> | PHP: <?php echo phpversion(); ?></p>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-icon <?php echo get_option('yadore_api_key') ? 'status-active' : 'status-warning'; ?>">
                            <span class="dashicons dashicons-admin-network"></span>
                        </div>
                        <div class="health-details">
                            <h3>API Connections</h3>
                            <p class="health-status" id="api-health-status">Checking...</p>
                            <p class="health-info">Yadore & Gemini API connectivity</p>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-icon status-active">
                            <span class="dashicons dashicons-database"></span>
                        </div>
                        <div class="health-details">
                            <h3>Database</h3>
                            <p class="health-status" id="db-health-status">Checking...</p>
                            <p class="health-info">Plugin tables and data integrity</p>
                        </div>
                    </div>

                    <div class="health-item">
                        <div class="health-icon <?php echo get_option('yadore_debug_mode', false) ? 'status-warning' : 'status-active'; ?>">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </div>
                        <div class="health-details">
                            <h3>Performance</h3>
                            <p class="health-status" id="performance-status">Checking...</p>
                            <p class="health-info">Cache, memory usage, and optimization</p>
                        </div>
                    </div>
                </div>

                <div class="health-metrics">
                    <div class="metric-row">
                        <div class="metric-item">
                            <span class="metric-label">Memory Usage:</span>
                            <span class="metric-value" id="memory-usage"><?php echo size_format(memory_get_usage(true)); ?> / <?php echo ini_get('memory_limit'); ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Plugin Tables:</span>
                            <span class="metric-value" id="db-tables-count">5 tables</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Last Error:</span>
                            <span class="metric-value" id="last-error-time">None</span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label">Cache Size:</span>
                            <span class="metric-value" id="cache-size">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Logs -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-warning"></span> Error Logs & Issues</h2>
                <div class="card-actions">
                    <select id="error-severity-filter">
                        <option value="all">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                    <button class="button button-secondary" id="clear-errors">
                        <span class="dashicons dashicons-trash"></span> Clear All
                    </button>
                    <button class="button button-secondary" id="export-errors">
                        <span class="dashicons dashicons-download"></span> Export Log
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="error-summary">
                    <div class="error-stats">
                        <div class="error-stat critical">
                            <span class="error-count" id="critical-errors">0</span>
                            <span class="error-label">Critical</span>
                        </div>
                        <div class="error-stat high">
                            <span class="error-count" id="high-errors">0</span>
                            <span class="error-label">High</span>
                        </div>
                        <div class="error-stat medium">
                            <span class="error-count" id="medium-errors">0</span>
                            <span class="error-label">Medium</span>
                        </div>
                        <div class="error-stat low">
                            <span class="error-count" id="low-errors">0</span>
                            <span class="error-label">Low</span>
                        </div>
                    </div>
                </div>

                <div class="error-logs-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Severity</th>
                                <th>Error Type</th>
                                <th>Message</th>
                                <th>Context</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="error-logs-body">
                            <tr>
                                <td colspan="7" class="loading-row">
                                    <span class="dashicons dashicons-update-alt spinning"></span> Loading error logs...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Debug Information -->
        <div class="debug-grid">
            <!-- System Information -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-info"></span> System Information</h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="copy-system-info">
                            <span class="dashicons dashicons-clipboard"></span> Copy All
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="system-info-grid" id="system-info-content">
                        <div class="info-section">
                            <h3>WordPress</h3>
                            <div class="info-items">
                                <div class="info-item">
                                    <span class="info-label">Version:</span>
                                    <span class="info-value"><?php echo get_bloginfo('version'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Multisite:</span>
                                    <span class="info-value"><?php echo is_multisite() ? 'Yes' : 'No'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Debug Mode:</span>
                                    <span class="info-value"><?php echo WP_DEBUG ? 'Enabled' : 'Disabled'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Language:</span>
                                    <span class="info-value"><?php echo get_locale(); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="info-section">
                            <h3>Server</h3>
                            <div class="info-items">
                                <div class="info-item">
                                    <span class="info-label">PHP Version:</span>
                                    <span class="info-value"><?php echo phpversion(); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">MySQL Version:</span>
                                    <span class="info-value"><?php global $wpdb; echo $wpdb->db_version(); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Server Software:</span>
                                    <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Max Execution Time:</span>
                                    <span class="info-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Memory Limit:</span>
                                    <span class="info-value"><?php echo ini_get('memory_limit'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="info-section">
                            <h3>Plugin</h3>
                            <div class="info-items">
                                <div class="info-item">
                                    <span class="info-label">Version:</span>
                                    <span class="info-value">2.9.16</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Debug Mode:</span>
                                    <span class="info-value"><?php echo get_option('yadore_debug_mode', false) ? 'Enabled' : 'Disabled'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">AI Enabled:</span>
                                    <span class="info-value"><?php echo get_option('yadore_ai_enabled', false) ? 'Yes' : 'No'; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Cache Duration:</span>
                                    <span class="info-value"><?php echo get_option('yadore_cache_duration', '3600'); ?>s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Log -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-media-text"></span> Debug Log</h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="clear-debug-log">
                            <span class="dashicons dashicons-trash"></span> Clear Log
                        </button>
                        <button class="button button-secondary" id="download-debug-log">
                            <span class="dashicons dashicons-download"></span> Download
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="debug-log-container">
                        <div class="log-controls">
                            <label>
                                <input type="checkbox" id="auto-scroll" checked> Auto-scroll to bottom
                            </label>
                            <label>
                                <input type="checkbox" id="word-wrap" checked> Word wrap
                            </label>
                            <select id="log-level-filter">
                                <option value="all">All Levels</option>
                                <option value="error">Errors Only</option>
                                <option value="warning">Warnings</option>
                                <option value="info">Info</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>

                        <div class="debug-log-viewer" id="debug-log-content">
                            <div class="log-loading">
                                <span class="dashicons dashicons-update-alt spinning"></span> Loading debug log...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diagnostic Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> Diagnostic Tools</h2>
            </div>
            <div class="card-content">
                <div class="diagnostic-tools">
                    <div class="tool-grid">
                        <div class="diagnostic-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-admin-network"></span>
                            </div>
                            <div class="tool-content">
                                <h3>Connectivity Test</h3>
                                <p>Test connections to external APIs and services</p>
                                <button class="button button-primary" id="test-connectivity">
                                    <span class="dashicons dashicons-admin-network"></span> Run Test
                                </button>
                            </div>
                            <div class="tool-results" id="connectivity-results"></div>
                        </div>

                        <div class="diagnostic-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-database"></span>
                            </div>
                            <div class="tool-content">
                                <h3>Database Check</h3>
                                <p>Verify database tables and data integrity</p>
                                <button class="button button-primary" id="check-database">
                                    <span class="dashicons dashicons-database"></span> Check DB
                                </button>
                            </div>
                            <div class="tool-results" id="database-results"></div>
                        </div>

                        <div class="diagnostic-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="tool-content">
                                <h3>Performance Test</h3>
                                <p>Analyze plugin performance and optimization</p>
                                <button class="button button-primary" id="test-performance">
                                    <span class="dashicons dashicons-performance"></span> Analyze
                                </button>
                            </div>
                            <div class="tool-results" id="performance-results"></div>
                        </div>

                        <div class="diagnostic-tool">
                            <div class="tool-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="tool-content">
                                <h3>Cache Analysis</h3>
                                <p>Review cache usage and optimization</p>
                                <button class="button button-primary" id="analyze-cache">
                                    <span class="dashicons dashicons-admin-generic"></span> Analyze
                                </button>
                            </div>
                            <div class="tool-results" id="cache-results"></div>
                        </div>
                    </div>
                </div>

                <div class="diagnostic-results" id="diagnostic-summary" style="display: none;">
                    <h3>Diagnostic Summary</h3>
                    <div class="summary-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>
