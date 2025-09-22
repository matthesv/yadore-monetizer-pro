<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-chart-area"></span>
        Analytics & Performance Reports
        <span class="version-badge">v2.9.2</span>
    </h1>

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
                    <div class="summary-stats">
                        <div class="stat-card">
                            <div class="stat-header">
                                <h3>Product Views</h3>
                                <span class="stat-trend positive" id="views-trend">+15.3%</span>
                            </div>
                            <div class="stat-number" id="total-product-views">Loading...</div>
                            <div class="stat-subtitle">Total product impressions</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <h3>Overlay Displays</h3>
                                <span class="stat-trend positive" id="overlays-trend">+8.7%</span>
                            </div>
                            <div class="stat-number" id="total-overlays">Loading...</div>
                            <div class="stat-subtitle">Overlay activations</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <h3>Click-Through Rate</h3>
                                <span class="stat-trend negative" id="ctr-trend">-2.1%</span>
                            </div>
                            <div class="stat-number" id="average-ctr">Loading...</div>
                            <div class="stat-subtitle">Average CTR</div>
                        </div>

                        <div class="stat-card">
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
        <div class="analytics-grid">
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
                        <div class="conversion-funnel">
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
                                <th>Post Title</th>
                                <th>Keywords</th>
                                <th>Views</th>
                                <th>Clicks</th>
                                <th>CTR</th>
                                <th>Est. Revenue</th>
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
                        <div class="keyword-stats">
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
                            <div class="cloud-container" id="keyword-cloud">
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
                                    <th>Keyword</th>
                                    <th>Usage Count</th>
                                    <th>Avg. CTR</th>
                                    <th>Total Clicks</th>
                                    <th>Confidence</th>
                                    <th>Source</th>
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
                        <div class="revenue-cards">
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

<script>
jQuery(document).ready(function($) {
    // Initialize analytics
    yadoreInitializeAnalytics();

    // Load analytics data
    yadoreLoadAnalyticsData();

    // Set up charts
    yadoreInitializeCharts();
});

function yadoreInitializeAnalytics() {
    const $ = jQuery;

    // Period selector
    $('#analytics-period').on('change', function() {
        yadoreLoadAnalyticsData($(this).val());
    });

    // Refresh button
    $('#refresh-analytics').on('click', function() {
        yadoreLoadAnalyticsData();
    });

    // Performance metric selector
    $('#performance-metric').on('change', function() {
        yadoreLoadPerformanceTable($(this).val());
    });

    console.log('Yadore Analytics v2.9 - Initialized');
}

function yadoreLoadAnalyticsData(period = 30) {
    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_get_analytics_data',
        nonce: yadore_admin.nonce,
        period: period
    }, function(response) {
        if (response.success) {
            const data = response.data;

            // Update summary stats
            jQuery('#total-product-views').text(data.summary.product_views.toLocaleString());
            jQuery('#total-overlays').text(data.summary.overlay_displays.toLocaleString());
            jQuery('#average-ctr').text(data.summary.average_ctr + '%');
            jQuery('#ai-analyses').text(data.summary.ai_analyses.toLocaleString());

            // Update traffic metrics
            jQuery('#daily-visitors').text(data.traffic.daily_average.toLocaleString());
            jQuery('#product-pages').text(data.traffic.product_pages.toLocaleString());
            jQuery('#bounce-rate').text(data.traffic.bounce_rate + '%');
            jQuery('#session-duration').text(data.traffic.session_duration);

            // Update conversion funnel
            jQuery('#funnel-views').text(data.funnel.page_views.toLocaleString());
            jQuery('#funnel-displays').text(data.funnel.product_displays.toLocaleString());
            jQuery('#funnel-clicks').text(data.funnel.product_clicks.toLocaleString());
            jQuery('#funnel-conversions').text(data.funnel.conversions.toLocaleString());

            // Update revenue
            jQuery('#monthly-revenue').text('$' + data.revenue.monthly_estimate.toFixed(2));
            jQuery('#rpc').text('$' + data.revenue.revenue_per_click.toFixed(4));
            jQuery('#top-category').text(data.revenue.top_category);
            jQuery('#category-earnings').text('$' + data.revenue.category_earnings.toFixed(2) + ' earned');

            // Update charts
            yadoreUpdateCharts(data);
        }
    });
}

function yadoreInitializeCharts() {
    // Initialize Chart.js charts
    const ctx = document.getElementById('performance-chart');
    if (ctx) {
        window.performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Product Views',
                    data: [],
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Clicks',
                    data: [],
                    borderColor: '#00a32a',
                    backgroundColor: 'rgba(0, 163, 42, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

function yadoreUpdateCharts(data) {
    // Update performance chart
    if (window.performanceChart && data.charts && data.charts.performance) {
        window.performanceChart.data.labels = data.charts.performance.labels;
        window.performanceChart.data.datasets[0].data = data.charts.performance.views;
        window.performanceChart.data.datasets[1].data = data.charts.performance.clicks;
        window.performanceChart.update();
    }
}
</script>