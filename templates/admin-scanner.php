<div class="wrap yadore-admin-wrap">
    <?php
    $auto_scan_enabled = (bool) get_option('yadore_auto_scan_posts', true);
    $scanner_actions = array(
        array(
            'label' => esc_html__('Bulk Scan starten', 'yadore-monetizer'),
            'url' => '#start-bulk-scan',
            'type' => 'primary',
            'icon' => 'dashicons-update-alt',
        ),
        array(
            'label' => esc_html__('Zur Übersicht', 'yadore-monetizer'),
            'url' => admin_url('admin.php?page=yadore-dashboard'),
            'type' => 'ghost',
            'icon' => 'dashicons-dashboard',
        ),
    );

    $scanner_meta = array(
        array(
            'label' => esc_html__('Scan-Abdeckung', 'yadore-monetizer'),
            'value_html' => '<span id="scanner-hero-coverage">0%</span>',
            'description' => esc_html__('Live-Aktualisierung bei jedem Durchlauf.', 'yadore-monetizer'),
            'icon' => 'dashicons-admin-site',
            'state' => 'info',
        ),
        array(
            'label' => esc_html__('Keyword-Erfolg', 'yadore-monetizer'),
            'value_html' => '<span id="scanner-hero-keyword-rate">0%</span>',
            'description' => esc_html__('Validierte Keywords basierend auf AI-Analysen.', 'yadore-monetizer'),
            'icon' => 'dashicons-tag',
            'state' => 'success',
        ),
        array(
            'label' => esc_html__('Automatisierung', 'yadore-monetizer'),
            'value' => $auto_scan_enabled
                ? esc_html__('Auto-Scan aktiv', 'yadore-monetizer')
                : esc_html__('Manuelle Ausführung', 'yadore-monetizer'),
            'description' => $auto_scan_enabled
                ? esc_html__('Neue Beiträge werden automatisch geprüft.', 'yadore-monetizer')
                : esc_html__('Aktiviere Auto-Scan für kontinuierliche Checks.', 'yadore-monetizer'),
            'icon' => 'dashicons-update',
            'state' => $auto_scan_enabled ? 'success' : 'warning',
        ),
    );

    $page_header = array(
        'slug' => 'scanner',
        'eyebrow' => esc_html__('Content Intelligence', 'yadore-monetizer'),
        'icon' => 'dashicons-search',
        'title' => esc_html__('Scanner & AI Analysis', 'yadore-monetizer'),
        'subtitle' => esc_html__('Überwache Inhalte, erkenne Chancen und starte Bulk-Analysen mit einem Klick.', 'yadore-monetizer'),
        'version' => YADORE_PLUGIN_VERSION,
        'actions' => $scanner_actions,
        'meta' => $scanner_meta,
    );
    ?>

    <div class="yadore-admin-shell">
        <?php include __DIR__ . '/partials/admin-page-header.php'; ?>

        <div class="yadore-admin-content">
            <div class="scanner-intro" aria-label="Post scanner guidance">
        <div class="intro-message">
            <p><strong>Plan your scans with confidence.</strong> Überwache Inhalte, erkenne Optimierungspotenzial und gleiche alle Ergebnisse mit einem Blick ab.</p>
            <ul class="intro-highlights">
                <li><span class="dashicons dashicons-yes"></span> Sofort-Überblick über gescannte und offene Beiträge</li>
                <li><span class="dashicons dashicons-visibility"></span> Preview-Funktion vor Bulk-Scans</li>
                <li><span class="dashicons dashicons-analytics"></span> Klar visualisierte Erfolgsquoten & Keyword-Treffer</li>
            </ul>
        </div>
        <div class="intro-actions">
            <div class="intro-card">
                <h3><span class="dashicons dashicons-lightbulb"></span> Schnelle Tipps</h3>
                <p>Wähle zuerst Beitrags-Typen und Status, passe Mindestwörter an und sichere dir mit AI-Checks mehr Kontext.</p>
                <p class="intro-hint">Aktive Einstellungen werden jetzt direkt unter dem Bulk Scanner zusammengefasst.</p>
            </div>
        </div>
    </div>

    <div class="yadore-scanner-container">
        <div class="yadore-card-grid overview-analytics-grid">
            <!-- Scanner Overview -->
            <div class="yadore-card scanner-overview">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-dashboard"></span> Scanner Overview</h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-overview">
                            <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="overview-enhancements">
                    <div class="overview-meta">
                        <div class="overview-pill" aria-live="polite">
                            <span class="pill-label">Abdeckung</span>
                            <span class="pill-value" id="scan-coverage">0%</span>
                        </div>
                        <div class="overview-pill" aria-live="polite">
                            <span class="pill-label">Keyword-Erfolg</span>
                            <span class="pill-value" id="keyword-success-rate">0%</span>
                        </div>
                        <div class="overview-refresh" aria-live="polite">
                            <span class="dashicons dashicons-update"></span>
                            <span>Aktualisiert: <span id="overview-refreshed">–</span></span>
                        </div>
                    </div>

                    <div class="overview-progress-tracker">
                        <div class="progress-top">
                            <span class="progress-label">Scan-Fortschritt</span>
                            <span class="progress-percent" id="overview-progress-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="overview-progress-fill" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                        </div>
                        <div class="progress-subtext" id="overview-progress-subtext">Noch <span id="scan-pending-count">0</span> Beiträge offen</div>
                    </div>
                </div>

                <div class="yadore-card-grid scanner-stats">
                    <div class="stat-card stat-total">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-post"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-posts">Loading...</div>
                            <div class="stat-label">Total Posts</div>
                        </div>
                    </div>

                    <div class="stat-card stat-scanned">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="scanned-posts">Loading...</div>
                            <div class="stat-label">Scanned Posts</div>
                        </div>
                    </div>

                    <div class="stat-card stat-pending">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="pending-posts">Loading...</div>
                            <div class="stat-label">Pending Scan</div>
                        </div>
                    </div>

                    <div class="stat-card stat-keywords">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-tag"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="validated-keywords">Loading...</div>
                            <div class="stat-label">Keywords Found</div>
                        </div>
                    </div>
                </div>

                <div class="scan-progress" id="scan-progress" style="display: none;">
                    <div class="progress-header">
                        <h3><span class="dashicons dashicons-update-alt spinning"></span> Scanning in Progress</h3>
                        <span class="progress-text" id="progress-text">0 / 0</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="progress-actions">
                        <button class="button button-secondary" id="pause-scan">Pause</button>
                        <button class="button button-link-delete" id="cancel-scan">Cancel</button>
                    </div>
                </div>
            </div>
            </div>

            <!-- Scan Statistics -->
            <div class="yadore-card scan-analytics-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-chart-pie"></span> Scan Analytics</h2>
                </div>
                <div class="card-content">
                    <div class="yadore-card-grid analytics-grid">
                        <div class="analytics-panel analytics-chart">
                            <h3>Keyword Categories</h3>
                            <canvas id="keywords-chart" width="300" height="200"></canvas>
                        </div>

                        <div class="analytics-panel analytics-chart">
                            <h3>Scan Success Rate</h3>
                            <canvas id="success-chart" width="300" height="200"></canvas>
                        </div>

                        <div class="analytics-panel analytics-stats">
                            <h3>Statistics</h3>
                            <div class="stats-list">
                                <div class="stat-row">
                                    <span class="stat-label">Most Common Keyword:</span>
                                    <span class="stat-value" id="top-keyword">Loading...</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Average Confidence:</span>
                                    <span class="stat-value" id="avg-confidence">Loading...</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">AI Usage Rate:</span>
                                    <span class="stat-value" id="ai-usage-rate">Loading...</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Success Rate:</span>
                                    <span class="stat-value" id="scan-success-rate">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanner Actions -->
        <div class="yadore-card-grid scanner-grid">
            <!-- Bulk Scanner -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-admin-post"></span> Bulk Post Scanner</h2>
                </div>
                <div class="card-content">
                    <div class="scanner-options">
                        <div class="option-group">
                            <label>
                                <strong>Post Types to Scan</strong>
                            </label>
                            <div class="option-actions" role="group" aria-label="Post type shortcuts">
                                <button type="button" class="button button-link option-toggle" data-target="post_types" data-action="select">Alle auswählen</button>
                                <button type="button" class="button button-link option-toggle" data-target="post_types" data-action="clear">Zurücksetzen</button>
                            </div>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="post_types[]" value="post" checked> Posts
                                </label>
                                <label>
                                    <input type="checkbox" name="post_types[]" value="page"> Pages
                                </label>
                                <?php
                                $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                                foreach ($custom_post_types as $post_type) {
                                    echo '<label><input type="checkbox" name="post_types[]" value="' . esc_attr($post_type->name) . '"> ' . esc_html($post_type->labels->name) . '</label>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="option-group">
                            <label>
                                <strong>Post Status</strong>
                            </label>
                            <div class="option-actions" role="group" aria-label="Status shortcuts">
                                <button type="button" class="button button-link option-toggle" data-target="post_status" data-action="select">Alle auswählen</button>
                                <button type="button" class="button button-link option-toggle" data-target="post_status" data-action="clear">Zurücksetzen</button>
                            </div>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="post_status[]" value="publish" checked> Published
                                </label>
                                <label>
                                    <input type="checkbox" name="post_status[]" value="draft"> Drafts
                                </label>
                                <label>
                                    <input type="checkbox" name="post_status[]" value="private"> Private
                                </label>
                            </div>
                        </div>

                        <div class="option-group">
                            <label for="min-words">
                                <strong>Minimum Word Count</strong>
                            </label>
                            <input type="number" id="min-words" min="0" max="10000" value="<?php echo esc_attr(get_option('yadore_min_content_words', '100')); ?>" class="small-text">
                            <p class="description">Only scan posts with at least this many words. Empfohlen für hochwertige Analysen: 250 Wörter.</p>
                        </div>

                        <div class="option-group">
                            <label>
                                <strong>Scan Options</strong>
                            </label>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="scan_options[]" value="force_rescan"> Force Re-scan (overwrite existing)
                                </label>
                                <label>
                                    <input type="checkbox" name="scan_options[]" value="use_ai" <?php checked(get_option('yadore_ai_enabled', false)); ?>> Use AI Analysis
                                </label>
                                <label>
                                    <input type="checkbox" name="scan_options[]" value="validate_products" checked> Validate Products
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bulk-scan-summary" id="bulk-scan-summary" aria-live="polite">
                        <h3><span class="dashicons dashicons-filter"></span> Aktive Scan-Einstellungen</h3>
                        <div class="yadore-card-grid summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Post-Typen</span>
                                <span class="summary-value summary-post-types">–</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Post-Status</span>
                                <span class="summary-value summary-post-status">–</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Mindestwörter</span>
                                <span class="summary-value summary-min-words">–</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Zusatzoptionen</span>
                                <span class="summary-value summary-scan-options">–</span>
                            </div>
                        </div>
                    </div>

                    <div class="scanner-actions">
                        <button class="button button-primary button-large" id="start-bulk-scan">
                            <span class="dashicons dashicons-search"></span> Start Bulk Scan
                        </button>
                        <button class="button button-secondary" id="preview-scan">
                            <span class="dashicons dashicons-visibility"></span> Preview Posts
                        </button>
                    </div>
                </div>
            </div>

            <!-- Single Post Scanner -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-edit"></span> Single Post Scanner</h2>
                </div>
                <div class="card-content">
                    <div class="single-scanner">
                        <div class="post-selector">
                            <label for="post-search">
                                <strong>Find Post to Scan</strong>
                            </label>
                            <div class="post-search-container">
                                <input type="text" id="post-search" placeholder="Search posts by title..." class="widefat">
                                <div class="post-suggestions" id="post-suggestions"></div>
                            </div>
                        </div>

                        <div class="selected-post" id="selected-post" style="display: none;">
                            <div class="post-preview">
                                <h4 class="post-title"></h4>
                                <div class="post-meta">
                                    <span class="post-date"></span>
                                    <span class="post-status"></span>
                                    <span class="post-word-count"></span>
                                </div>
                                <div class="post-excerpt"></div>
                                <div class="current-keywords"></div>
                            </div>

                            <div class="scan-options">
                                <label>
                                    <input type="checkbox" id="single-use-ai" <?php checked(get_option('yadore_ai_enabled', false)); ?>> Use AI Analysis
                                </label>
                                <label>
                                    <input type="checkbox" id="single-force-rescan"> Force Re-scan
                                </label>
                                <label>
                                    <input type="checkbox" id="single-validate-products" checked> Validate Products
                                </label>
                            </div>

                            <button class="button button-primary" id="scan-single-post">
                                <span class="dashicons dashicons-search"></span> Scan This Post
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan Results -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-list-view"></span> Recent Scan Results</h2>
                <div class="card-actions results-actions">
                    <div class="results-filter-group">
                        <label class="screen-reader-text" for="results-filter">Filter scan results</label>
                        <select id="results-filter">
                        <option value="all">All Results</option>
                        <option value="successful">Successful Only</option>
                        <option value="failed">Failed Only</option>
                        <option value="ai_analyzed">AI Analyzed</option>
                    </select>
                    </div>
                    <div class="results-quick-filters" id="results-quick-filters" role="group" aria-label="Quick result filters">
                        <button type="button" class="button button-secondary quick-filter" data-filter="all">Alle</button>
                        <button type="button" class="button button-secondary quick-filter" data-filter="successful">Erfolgreich</button>
                        <button type="button" class="button button-secondary quick-filter" data-filter="failed">Fehlgeschlagen</button>
                        <button type="button" class="button button-secondary quick-filter" data-filter="ai_analyzed">AI genutzt</button>
                    </div>
                    <button class="button button-secondary" id="export-results">
                        <span class="dashicons dashicons-download"></span> Export CSV
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="scan-results-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Post Title</th>
                                <th>Primary Keyword</th>
                                <th>Confidence</th>
                                <th>Status</th>
                                <th>Scan Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="scan-results-body">
                            <tr>
                                <td colspan="6" class="loading-row">
                                    <span class="dashicons dashicons-update-alt spinning"></span> Loading scan results...
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="table-pagination" id="results-pagination">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
                <div class="status-legend" aria-label="Status legend">
                    <span class="legend-item legend-success"><span class="legend-dot"></span> Erfolgreich validiert</span>
                    <span class="legend-item legend-pending"><span class="legend-dot"></span> Ausstehend oder in Prüfung</span>
                    <span class="legend-item legend-failed"><span class="legend-dot"></span> Fehlerhaft – benötigt Review</span>
                    <span class="legend-item legend-ai"><span class="legend-dot"></span> Mit AI Analyse</span>
                </div>
            </div>
        </div>

            </div>
        </div>

        </div>
    </div>
</div>

<script>
jQuery(function() {
    if (typeof yadoreInitializeScanner === 'function') {
        yadoreInitializeScanner();
    }
});
</script>
