<div class="wrap yadore-admin-wrap">
    <?php
    $analytics_actions = array(
        array(
            'label' => esc_html__('Live-Daten aktualisieren', 'yadore-monetizer'),
            'url' => '#analytics-period',
            'type' => 'primary',
            'icon' => 'dashicons-update',
        ),
        array(
            'label' => esc_html__('Zu den Tools', 'yadore-monetizer'),
            'url' => admin_url('admin.php?page=yadore-tools'),
            'type' => 'ghost',
            'icon' => 'dashicons-admin-tools',
        ),
    );

    $analytics_meta = array(
        array(
            'label' => esc_html__('Berichtsfenster', 'yadore-monetizer'),
            'value' => esc_html__('30 Tage Standard', 'yadore-monetizer'),
            'description' => esc_html__('Wechsle oben zwischen 7, 30, 90 oder 365 Tagen.', 'yadore-monetizer'),
            'icon' => 'dashicons-calendar-alt',
            'state' => 'info',
        ),
        array(
            'label' => esc_html__('Aktive Tracking-Punkte', 'yadore-monetizer'),
            'value' => esc_html__('Views · Overlays · CTR', 'yadore-monetizer'),
            'description' => esc_html__('Alle Kernmetriken werden minütlich synchronisiert.', 'yadore-monetizer'),
            'icon' => 'dashicons-chart-line',
            'state' => 'success',
        ),
        array(
            'label' => esc_html__('Exportoptionen', 'yadore-monetizer'),
            'value' => esc_html__('CSV & JSON', 'yadore-monetizer'),
            'description' => esc_html__('Weitere Formate findest du in den Tools.', 'yadore-monetizer'),
            'icon' => 'dashicons-download',
            'state' => 'neutral',
        ),
    );

    $page_header = array(
        'slug' => 'analytics',
        'eyebrow' => esc_html__('Insights & KPIs', 'yadore-monetizer'),
        'icon' => 'dashicons-chart-area',
        'title' => esc_html__('Analytics & Leistungsberichte', 'yadore-monetizer'),
        'subtitle' => esc_html__('Verfolge Impressionen, Klicks und Umsatztrends in Echtzeit mit KI-Korrelationen.', 'yadore-monetizer'),
        'version' => YADORE_PLUGIN_VERSION,
        'actions' => $analytics_actions,
        'meta' => $analytics_meta,
    );
    ?>

    <div class="yadore-admin-shell">
        <?php include __DIR__ . '/partials/admin-page-header.php'; ?>

        <div class="yadore-admin-content">
            <div class="yadore-analytics-container">
        <!-- Analytics Overview -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-dashboard"></span> Analytics Overview</h2>
                <div class="card-actions">
                    <select id="analytics-period">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                    </select>
                    <button class="button button-secondary" id="refresh-analytics">
                        <span class="dashicons dashicons-update"></span> Refresh Data
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="analytics-summary">
                    <div class="yadore-card-grid summary-stats">
                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3>Product Views</h3>
                                <span class="stat-trend positive" id="views-trend">+15.3%</span>
                            </div>
                            <div class="stat-number" id="total-product-views">Loading...</div>
                            <div class="stat-subtitle">Total product impressions</div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3>Overlay Displays</h3>
                                <span class="stat-trend positive" id="overlays-trend">+8.7%</span>
                            </div>
                            <div class="stat-number" id="total-overlays">Loading...</div>
                            <div class="stat-subtitle">Overlay activations</div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3>Click-Through Rate</h3>
                                <span class="stat-trend negative" id="ctr-trend">-2.1%</span>
                            </div>
                            <div class="stat-number" id="average-ctr">Loading...</div>
                            <div class="stat-subtitle">Average CTR</div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3>AI Analysis</h3>
                                <span class="stat-trend positive" id="ai-trend">+24.5%</span>
                            </div>
                            <div class="stat-number" id="ai-analyses">Loading...</div>
                            <div class="stat-subtitle">Content analyzed</div>
                        </div>
                    </div>

                    <div class="performance-chart">
                        <h3>Performance Over Time</h3>
                        <canvas id="performance-chart" width="800" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="yadore-card-grid analytics-grid" data-variant="spacious">
            <!-- Traffic Analysis -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-visibility"></span> Traffic Analysis</h2>
                </div>
                <div class="card-content">
                    <div class="traffic-metrics">
                        <div class="metric-row">
                            <span class="metric-label">Daily Average Visitors:</span>
                            <span class="metric-value" id="daily-visitors">Loading...</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Product Page Views:</span>
                            <span class="metric-value" id="product-pages">Loading...</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Bounce Rate:</span>
                            <span class="metric-value" id="bounce-rate">Loading...</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Session Duration:</span>
                            <span class="metric-value" id="session-duration">Loading...</span>
                        </div>
                    </div>

                    <div class="traffic-chart">
                        <h4>Daily Traffic</h4>
                        <canvas id="traffic-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Conversion Metrics -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-chart-pie"></span> Conversion Metrics</h2>
                </div>
                <div class="card-content">
                    <div class="conversion-stats">
                        <div class="yadore-card-grid conversion-funnel">
                            <div class="funnel-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Page Views</h4>
                                    <span class="step-count" id="funnel-views">Loading...</span>
                                </div>
                                <div class="step-rate">100%</div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Product Displays</h4>
                                    <span class="step-count" id="funnel-displays">Loading...</span>
                                </div>
                                <div class="step-rate" id="display-rate">Loading...</div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Product Clicks</h4>
                                    <span class="step-count" id="funnel-clicks">Loading...</span>
                                </div>
                                <div class="step-rate" id="click-rate">Loading...</div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4>Conversions</h4>
                                    <span class="step-count" id="funnel-conversions">Loading...</span>
                                </div>
                                <div class="step-rate" id="conversion-rate">Loading...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Content -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-star-filled"></span> Top Performing Content</h2>
                <div class="card-actions">
                    <select id="performance-metric">
                        <option value="views">Most Viewed</option>
                        <option value="clicks">Most Clicked</option>
                        <option value="ctr">Highest CTR</option>
                        <option value="revenue">Highest Revenue</option>
                    </select>
                </div>
            </div>
            <div class="card-content">
                <div class="performance-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="col-title">Post Title</th>
                                <th scope="col" class="col-keyword">Keywords</th>
                                <th scope="col" class="col-metric">Views</th>
                                <th scope="col" class="col-metric">Clicks</th>
                                <th scope="col" class="col-metric">CTR</th>
                                <th scope="col" class="col-metric">Est. Revenue</th>
                            </tr>
                        </thead>
                        <tbody id="performance-table-body">
                            <tr>
                                <td colspan="6" class="loading-row">
                                    <span class="dashicons dashicons-update-alt spinning"></span> Loading performance data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Keyword Analytics -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-tag"></span> Keyword Analytics</h2>
            </div>
            <div class="card-content">
                <div class="keyword-analytics">
                    <div class="keyword-overview">
                        <div class="yadore-card-grid keyword-stats">
                            <div class="keyword-stat">
                                <span class="stat-number" id="total-keywords">Loading...</span>
                                <span class="stat-label">Total Keywords</span>
                            </div>
                            <div class="keyword-stat">
                                <span class="stat-number" id="active-keywords">Loading...</span>
                                <span class="stat-label">Active Keywords</span>
                            </div>
                            <div class="keyword-stat">
                                <span class="stat-number" id="ai-keywords">Loading...</span>
                                <span class="stat-label">AI Detected</span>
                            </div>
                        </div>

                        <div class="keyword-cloud">
                            <h4>Most Popular Keywords</h4>
                            <p class="cloud-subtitle">Track which search terms attract the most engagement at a glance.</p>
                            <div class="yadore-card-grid cloud-container" id="keyword-cloud" aria-live="polite">
                                <div class="cloud-loading">
                                    <span class="dashicons dashicons-update-alt spinning"></span> Generating keyword cloud...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="keyword-performance">
                        <h4>Keyword Performance</h4>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="col-keyword">Keyword</th>
                                    <th scope="col" class="col-metric">Usage Count</th>
                                    <th scope="col" class="col-metric">Avg. CTR</th>
                                    <th scope="col" class="col-metric">Total Clicks</th>
                                    <th scope="col" class="col-metric">Confidence</th>
                                    <th scope="col" class="col-source">Source</th>
                                </tr>
                            </thead>
                            <tbody id="keyword-performance-body">
                                <tr>
                                    <td colspan="6" class="loading-row">
                                        <span class="dashicons dashicons-update-alt spinning"></span> Loading keyword data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Analytics -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-money-alt"></span> Revenue Analytics</h2>
            </div>
            <div class="card-content">
                <div class="revenue-analytics">
                    <div class="revenue-summary">
                        <div class="yadore-card-grid revenue-cards">
                            <div class="revenue-card">
                                <h4>Estimated Monthly Revenue</h4>
                                <div class="revenue-amount" id="monthly-revenue">$0.00</div>
                                <div class="revenue-change positive" id="revenue-change">+0%</div>
                            </div>

                            <div class="revenue-card">
                                <h4>Revenue Per Click</h4>
                                <div class="revenue-amount" id="rpc">$0.00</div>
                                <div class="revenue-subtitle">Average RPC</div>
                            </div>

                            <div class="revenue-card">
                                <h4>Top Earning Category</h4>
                                <div class="revenue-category" id="top-category">Loading...</div>
                                <div class="revenue-subtitle" id="category-earnings">$0.00 earned</div>
                            </div>
                        </div>
                    </div>

                    <div class="revenue-chart">
                        <h4>Revenue Trend</h4>
                        <canvas id="revenue-chart" width="600" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
