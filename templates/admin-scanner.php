<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-search"></span>
        Post Scanner & Analysis
        <span class="version-badge">v2.9.22</span>
    </h1>

    <div class="yadore-scanner-container">
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
                <div class="scanner-stats">
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

        <!-- Scanner Actions -->
        <div class="scanner-grid">
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
                            <p class="description">Only scan posts with at least this many words</p>
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
                <div class="card-actions">
                    <select id="results-filter">
                        <option value="all">All Results</option>
                        <option value="successful">Successful Only</option>
                        <option value="failed">Failed Only</option>
                        <option value="ai_analyzed">AI Analyzed</option>
                    </select>
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
            </div>
        </div>

        <!-- Scan Statistics -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-chart-pie"></span> Scan Analytics</h2>
            </div>
            <div class="card-content">
                <div class="analytics-grid">
                    <div class="analytics-chart">
                        <h3>Keyword Categories</h3>
                        <canvas id="keywords-chart" width="300" height="200"></canvas>
                    </div>

                    <div class="analytics-chart">
                        <h3>Scan Success Rate</h3>
                        <canvas id="success-chart" width="300" height="200"></canvas>
                    </div>

                    <div class="analytics-stats">
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
</div>

<script>
jQuery(function() {
    if (typeof yadoreInitializeScanner === 'function') {
        yadoreInitializeScanner();
    }
});
</script>