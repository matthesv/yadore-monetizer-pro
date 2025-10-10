<div class="wrap yadore-admin-wrap">
    <?php
    require_once __DIR__ . '/partials/admin-analytics-translations.php';

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
                <h2><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html(yadore_get_analytics_text('analytics_overview_title')); ?></h2>
                <div class="card-actions">
                    <select id="analytics-period">
                        <option value="7"><?php echo esc_html(yadore_get_analytics_text('analytics_period_7')); ?></option>
                        <option value="30" selected><?php echo esc_html(yadore_get_analytics_text('analytics_period_30')); ?></option>
                        <option value="90"><?php echo esc_html(yadore_get_analytics_text('analytics_period_90')); ?></option>
                        <option value="365"><?php echo esc_html(yadore_get_analytics_text('analytics_period_365')); ?></option>
                    </select>
                    <button class="button button-secondary" id="refresh-analytics">
                        <span class="dashicons dashicons-update"></span> <?php echo esc_html(yadore_get_analytics_text('analytics_refresh')); ?>
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="analytics-summary">
                    <div class="yadore-card-grid summary-stats">
                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3><?php echo esc_html(yadore_get_analytics_text('analytics_views_title')); ?></h3>
                                <span class="stat-trend positive" id="views-trend">+15.3%</span>
                            </div>
                            <div class="stat-number" id="total-product-views"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            <div class="stat-subtitle"><?php echo esc_html(yadore_get_analytics_text('analytics_views_subtitle')); ?></div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3><?php echo esc_html(yadore_get_analytics_text('analytics_overlays_title')); ?></h3>
                                <span class="stat-trend positive" id="overlays-trend">+8.7%</span>
                            </div>
                            <div class="stat-number" id="total-overlays"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            <div class="stat-subtitle"><?php echo esc_html(yadore_get_analytics_text('analytics_overlays_subtitle')); ?></div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3><?php echo esc_html(yadore_get_analytics_text('analytics_ctr_title')); ?></h3>
                                <span class="stat-trend negative" id="ctr-trend">-2.1%</span>
                            </div>
                            <div class="stat-number" id="average-ctr"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            <div class="stat-subtitle"><?php echo esc_html(yadore_get_analytics_text('analytics_ctr_subtitle')); ?></div>
                        </div>

                        <div class="stat-card stat-compact">
                            <div class="stat-header">
                                <h3><?php echo esc_html(yadore_get_analytics_text('analytics_ai_title')); ?></h3>
                                <span class="stat-trend positive" id="ai-trend">+24.5%</span>
                            </div>
                            <div class="stat-number" id="ai-analyses"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            <div class="stat-subtitle"><?php echo esc_html(yadore_get_analytics_text('analytics_ai_subtitle')); ?></div>
                        </div>
                    </div>

                    <div class="performance-chart">
                        <h3><?php echo esc_html(yadore_get_analytics_text('analytics_performance_title')); ?></h3>
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
                    <h2><span class="dashicons dashicons-visibility"></span> <?php echo esc_html(yadore_get_analytics_text('traffic_analysis_title')); ?></h2>
                </div>
                <div class="card-content">
                    <div class="traffic-metrics">
                        <div class="metric-row">
                            <span class="metric-label"><?php echo esc_html(yadore_get_analytics_text('traffic_metric_daily_visitors')); ?></span>
                            <span class="metric-value" id="daily-visitors"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label"><?php echo esc_html(yadore_get_analytics_text('traffic_metric_product_views')); ?></span>
                            <span class="metric-value" id="product-pages"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label"><?php echo esc_html(yadore_get_analytics_text('traffic_metric_bounce_rate')); ?></span>
                            <span class="metric-value" id="bounce-rate"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label"><?php echo esc_html(yadore_get_analytics_text('traffic_metric_session_duration')); ?></span>
                            <span class="metric-value" id="session-duration"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                        </div>
                    </div>

                    <div class="traffic-chart">
                        <h4><?php echo esc_html(yadore_get_analytics_text('traffic_chart_title')); ?></h4>
                        <canvas id="traffic-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Conversion Metrics -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-chart-pie"></span> <?php echo esc_html(yadore_get_analytics_text('conversion_metrics_title')); ?></h2>
                </div>
                <div class="card-content">
                    <div class="conversion-stats">
                        <div class="yadore-card-grid conversion-funnel">
                            <div class="funnel-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4><?php echo esc_html(yadore_get_analytics_text('conversion_step_page_views')); ?></h4>
                                    <span class="step-count" id="funnel-views"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                </div>
                                <div class="step-rate">100%</div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4><?php echo esc_html(yadore_get_analytics_text('conversion_step_displays')); ?></h4>
                                    <span class="step-count" id="funnel-displays"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                </div>
                                <div class="step-rate" id="display-rate"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4><?php echo esc_html(yadore_get_analytics_text('conversion_step_clicks')); ?></h4>
                                    <span class="step-count" id="funnel-clicks"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                </div>
                                <div class="step-rate" id="click-rate"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            </div>

                            <div class="funnel-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h4><?php echo esc_html(yadore_get_analytics_text('conversion_step_conversions')); ?></h4>
                                    <span class="step-count" id="funnel-conversions"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                </div>
                                <div class="step-rate" id="conversion-rate"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Content -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html(yadore_get_analytics_text('top_content_title')); ?></h2>
                <div class="card-actions">
                    <select id="performance-metric">
                        <option value="views"><?php echo esc_html(yadore_get_analytics_text('metric_option_views')); ?></option>
                        <option value="clicks"><?php echo esc_html(yadore_get_analytics_text('metric_option_clicks')); ?></option>
                        <option value="ctr"><?php echo esc_html(yadore_get_analytics_text('metric_option_ctr')); ?></option>
                        <option value="revenue"><?php echo esc_html(yadore_get_analytics_text('metric_option_revenue')); ?></option>
                    </select>
                </div>
            </div>
            <div class="card-content">
                <div class="performance-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col" class="col-title"><?php echo esc_html(yadore_get_analytics_text('table_header_post_title')); ?></th>
                                <th scope="col" class="col-keyword"><?php echo esc_html(yadore_get_analytics_text('table_header_keywords')); ?></th>
                                <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('table_header_views')); ?></th>
                                <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('table_header_clicks')); ?></th>
                                <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('table_header_ctr')); ?></th>
                                <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('table_header_revenue')); ?></th>
                            </tr>
                        </thead>
                        <tbody id="performance-table-body">
                            <tr>
                                <td colspan="6" class="loading-row">
                                    <span class="dashicons dashicons-update-alt spinning"></span> <?php echo esc_html(yadore_get_analytics_text('loading_performance_data')); ?>
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
                <h2><span class="dashicons dashicons-tag"></span> <?php echo esc_html(yadore_get_analytics_text('keyword_analytics_title')); ?></h2>
            </div>
            <div class="card-content">
                <div class="keyword-analytics">
                    <div class="keyword-overview">
                        <div class="yadore-card-grid keyword-stats">
                            <div class="keyword-stat">
                                <span class="stat-number" id="total-keywords"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                <span class="stat-label"><?php echo esc_html(yadore_get_analytics_text('keyword_stat_total')); ?></span>
                            </div>
                            <div class="keyword-stat">
                                <span class="stat-number" id="active-keywords"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                <span class="stat-label"><?php echo esc_html(yadore_get_analytics_text('keyword_stat_active')); ?></span>
                            </div>
                            <div class="keyword-stat">
                                <span class="stat-number" id="ai-keywords"><?php echo esc_html(yadore_get_analytics_text('loading')); ?></span>
                                <span class="stat-label"><?php echo esc_html(yadore_get_analytics_text('keyword_stat_ai')); ?></span>
                            </div>
                        </div>

                        <div class="keyword-cloud">
                            <h4><?php echo esc_html(yadore_get_analytics_text('keyword_cloud_title')); ?></h4>
                            <p class="cloud-subtitle"><?php echo esc_html(yadore_get_analytics_text('keyword_cloud_subtitle')); ?></p>
                            <div class="yadore-card-grid cloud-container" id="keyword-cloud" aria-live="polite">
                                <div class="cloud-loading">
                                    <span class="dashicons dashicons-update-alt spinning"></span> <?php echo esc_html(yadore_get_analytics_text('keyword_cloud_loading')); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="keyword-performance">
                        <h4><?php echo esc_html(yadore_get_analytics_text('keyword_performance_title')); ?></h4>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="col-keyword"><?php echo esc_html(yadore_get_analytics_text('keyword_column_keyword')); ?></th>
                                    <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('keyword_column_usage')); ?></th>
                                    <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('keyword_column_avg_ctr')); ?></th>
                                    <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('keyword_column_total_clicks')); ?></th>
                                    <th scope="col" class="col-metric"><?php echo esc_html(yadore_get_analytics_text('keyword_column_confidence')); ?></th>
                                    <th scope="col" class="col-source"><?php echo esc_html(yadore_get_analytics_text('keyword_column_source')); ?></th>
                                </tr>
                            </thead>
                            <tbody id="keyword-performance-body">
                                <tr>
                                    <td colspan="6" class="loading-row">
                                        <span class="dashicons dashicons-update-alt spinning"></span> <?php echo esc_html(yadore_get_analytics_text('loading_keyword_data')); ?>
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
                <h2><span class="dashicons dashicons-money-alt"></span> <?php echo esc_html(yadore_get_analytics_text('revenue_analytics_title')); ?></h2>
            </div>
            <div class="card-content">
                <div class="revenue-analytics">
                    <div class="revenue-summary">
                        <div class="yadore-card-grid revenue-cards">
                            <div class="revenue-card">
                                <h4><?php echo esc_html(yadore_get_analytics_text('revenue_summary_title')); ?></h4>
                                <div class="revenue-amount" id="monthly-revenue"><?php echo esc_html(yadore_get_analytics_text('revenue_amount_placeholder')); ?></div>
                                <div class="revenue-change positive" id="revenue-change"><?php echo esc_html(yadore_get_analytics_text('revenue_change_placeholder')); ?></div>
                            </div>

                            <div class="revenue-card">
                                <h4><?php echo esc_html(yadore_get_analytics_text('revenue_rpc_title')); ?></h4>
                                <div class="revenue-amount" id="rpc"><?php echo esc_html(yadore_get_analytics_text('revenue_amount_placeholder')); ?></div>
                                <div class="revenue-subtitle"><?php echo esc_html(yadore_get_analytics_text('revenue_rpc_subtitle')); ?></div>
                            </div>

                            <div class="revenue-card">
                                <h4><?php echo esc_html(yadore_get_analytics_text('revenue_top_category_title')); ?></h4>
                                <div class="revenue-category" id="top-category"><?php echo esc_html(yadore_get_analytics_text('revenue_category_placeholder')); ?></div>
                                <div class="revenue-subtitle" id="category-earnings"><?php echo esc_html(yadore_get_analytics_text('revenue_category_earnings_placeholder')); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="revenue-chart">
                        <h4><?php echo esc_html(yadore_get_analytics_text('revenue_trend_title')); ?></h4>
                        <canvas id="revenue-chart" width="600" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
