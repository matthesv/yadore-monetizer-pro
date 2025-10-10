<?php
if (!function_exists('yadore_get_analytics_language')) {
    function yadore_get_analytics_language() {
        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } elseif (function_exists('determine_locale')) {
            $locale = determine_locale();
        } elseif (function_exists('get_locale')) {
            $locale = get_locale();
        } else {
            $locale = 'en_US';
        }

        if (!is_string($locale)) {
            $locale = 'en_US';
        }

        return (strpos($locale, 'de') === 0) ? 'de' : 'en';
    }
}

if (!function_exists('yadore_get_analytics_text')) {
    function yadore_get_analytics_text($key) {
        static $translations = null;

        if ($translations === null) {
            $translations = array(
                'analytics_overview_title' => array(
                    'en' => 'Analytics Overview',
                    'de' => 'Analyseüberblick',
                ),
                'analytics_period_7' => array(
                    'en' => 'Last 7 Days',
                    'de' => 'Letzte 7 Tage',
                ),
                'analytics_period_30' => array(
                    'en' => 'Last 30 Days',
                    'de' => 'Letzte 30 Tage',
                ),
                'analytics_period_90' => array(
                    'en' => 'Last 90 Days',
                    'de' => 'Letzte 90 Tage',
                ),
                'analytics_period_365' => array(
                    'en' => 'Last Year',
                    'de' => 'Letztes Jahr',
                ),
                'analytics_refresh' => array(
                    'en' => 'Refresh Data',
                    'de' => 'Daten aktualisieren',
                ),
                'loading' => array(
                    'en' => 'Loading...',
                    'de' => 'Wird geladen …',
                ),
                'analytics_views_title' => array(
                    'en' => 'Product Views',
                    'de' => 'Produktaufrufe',
                ),
                'analytics_views_subtitle' => array(
                    'en' => 'Total product impressions',
                    'de' => 'Gesamte Produktimpressionen',
                ),
                'analytics_overlays_title' => array(
                    'en' => 'Overlay Displays',
                    'de' => 'Overlay-Einblendungen',
                ),
                'analytics_overlays_subtitle' => array(
                    'en' => 'Overlay activations',
                    'de' => 'Overlay-Aktivierungen',
                ),
                'analytics_ctr_title' => array(
                    'en' => 'Click-Through Rate',
                    'de' => 'Klickrate',
                ),
                'analytics_ctr_subtitle' => array(
                    'en' => 'Average CTR',
                    'de' => 'Durchschnittliche Klickrate',
                ),
                'analytics_ai_title' => array(
                    'en' => 'AI Analysis',
                    'de' => 'KI-Analyse',
                ),
                'analytics_ai_subtitle' => array(
                    'en' => 'Content analyzed',
                    'de' => 'Analysierte Inhalte',
                ),
                'analytics_performance_title' => array(
                    'en' => 'Performance Over Time',
                    'de' => 'Performance im Zeitverlauf',
                ),
                'traffic_analysis_title' => array(
                    'en' => 'Traffic Analysis',
                    'de' => 'Traffic-Analyse',
                ),
                'traffic_metric_daily_visitors' => array(
                    'en' => 'Daily Average Visitors:',
                    'de' => 'Durchschnittliche Besucher pro Tag:',
                ),
                'traffic_metric_product_views' => array(
                    'en' => 'Product Page Views:',
                    'de' => 'Produktseitenaufrufe:',
                ),
                'traffic_metric_bounce_rate' => array(
                    'en' => 'Bounce Rate:',
                    'de' => 'Absprungrate:',
                ),
                'traffic_metric_session_duration' => array(
                    'en' => 'Session Duration:',
                    'de' => 'Sitzungsdauer:',
                ),
                'traffic_chart_title' => array(
                    'en' => 'Daily Traffic',
                    'de' => 'Täglicher Traffic',
                ),
                'conversion_metrics_title' => array(
                    'en' => 'Conversion Metrics',
                    'de' => 'Conversion-Kennzahlen',
                ),
                'conversion_step_page_views' => array(
                    'en' => 'Page Views',
                    'de' => 'Seitenaufrufe',
                ),
                'conversion_step_displays' => array(
                    'en' => 'Product Displays',
                    'de' => 'Produktanzeigen',
                ),
                'conversion_step_clicks' => array(
                    'en' => 'Product Clicks',
                    'de' => 'Produktklicks',
                ),
                'conversion_step_conversions' => array(
                    'en' => 'Conversions',
                    'de' => 'Conversions',
                ),
                'top_content_title' => array(
                    'en' => 'Top Performing Content',
                    'de' => 'Top-Inhalte',
                ),
                'metric_option_views' => array(
                    'en' => 'Most Viewed',
                    'de' => 'Meiste Aufrufe',
                ),
                'metric_option_clicks' => array(
                    'en' => 'Most Clicked',
                    'de' => 'Meiste Klicks',
                ),
                'metric_option_ctr' => array(
                    'en' => 'Highest CTR',
                    'de' => 'Höchste Klickrate',
                ),
                'metric_option_revenue' => array(
                    'en' => 'Highest Revenue',
                    'de' => 'Höchster Umsatz',
                ),
                'table_header_post_title' => array(
                    'en' => 'Post Title',
                    'de' => 'Beitragstitel',
                ),
                'table_header_keywords' => array(
                    'en' => 'Keywords',
                    'de' => 'Schlüsselwörter',
                ),
                'table_header_views' => array(
                    'en' => 'Views',
                    'de' => 'Aufrufe',
                ),
                'table_header_clicks' => array(
                    'en' => 'Clicks',
                    'de' => 'Klicks',
                ),
                'table_header_ctr' => array(
                    'en' => 'CTR',
                    'de' => 'CTR',
                ),
                'table_header_revenue' => array(
                    'en' => 'Est. Revenue',
                    'de' => 'Geschätzter Umsatz',
                ),
                'loading_performance_data' => array(
                    'en' => 'Loading performance data...',
                    'de' => 'Leistungsdaten werden geladen …',
                ),
                'keyword_analytics_title' => array(
                    'en' => 'Keyword Analytics',
                    'de' => 'Keyword-Analyse',
                ),
                'keyword_stat_total' => array(
                    'en' => 'Total Keywords',
                    'de' => 'Schlüsselwörter gesamt',
                ),
                'keyword_stat_active' => array(
                    'en' => 'Active Keywords',
                    'de' => 'Aktive Schlüsselwörter',
                ),
                'keyword_stat_ai' => array(
                    'en' => 'AI Detected',
                    'de' => 'Von KI erkannt',
                ),
                'keyword_cloud_title' => array(
                    'en' => 'Most Popular Keywords',
                    'de' => 'Beliebteste Schlüsselwörter',
                ),
                'keyword_cloud_subtitle' => array(
                    'en' => 'Track which search terms attract the most engagement at a glance.',
                    'de' => 'Erkenne auf einen Blick, welche Suchbegriffe das meiste Engagement erzielen.',
                ),
                'keyword_cloud_loading' => array(
                    'en' => 'Generating keyword cloud...',
                    'de' => 'Schlüsselwortwolke wird erstellt …',
                ),
                'keyword_performance_title' => array(
                    'en' => 'Keyword Performance',
                    'de' => 'Keyword-Leistung',
                ),
                'keyword_column_keyword' => array(
                    'en' => 'Keyword',
                    'de' => 'Schlüsselwort',
                ),
                'keyword_column_usage' => array(
                    'en' => 'Usage Count',
                    'de' => 'Verwendungshäufigkeit',
                ),
                'keyword_column_avg_ctr' => array(
                    'en' => 'Avg. CTR',
                    'de' => 'Durchschn. CTR',
                ),
                'keyword_column_total_clicks' => array(
                    'en' => 'Total Clicks',
                    'de' => 'Klicks gesamt',
                ),
                'keyword_column_confidence' => array(
                    'en' => 'Confidence',
                    'de' => 'Vertrauensniveau',
                ),
                'keyword_column_source' => array(
                    'en' => 'Source',
                    'de' => 'Quelle',
                ),
                'loading_keyword_data' => array(
                    'en' => 'Loading keyword data...',
                    'de' => 'Schlüsselwortdaten werden geladen …',
                ),
                'revenue_analytics_title' => array(
                    'en' => 'Revenue Analytics',
                    'de' => 'Umsatzanalyse',
                ),
                'revenue_summary_title' => array(
                    'en' => 'Estimated Monthly Revenue',
                    'de' => 'Geschätzter Monatsumsatz',
                ),
                'revenue_amount_placeholder' => array(
                    'en' => '$0.00',
                    'de' => '0,00 $',
                ),
                'revenue_change_placeholder' => array(
                    'en' => '+0%',
                    'de' => '+0 %',
                ),
                'revenue_rpc_title' => array(
                    'en' => 'Revenue Per Click',
                    'de' => 'Umsatz pro Klick',
                ),
                'revenue_rpc_subtitle' => array(
                    'en' => 'Average RPC',
                    'de' => 'Durchschnittlicher Umsatz pro Klick',
                ),
                'revenue_top_category_title' => array(
                    'en' => 'Top Earning Category',
                    'de' => 'Umsatzstärkste Kategorie',
                ),
                'revenue_category_placeholder' => array(
                    'en' => 'Loading...',
                    'de' => 'Wird geladen …',
                ),
                'revenue_category_earnings_placeholder' => array(
                    'en' => '$0.00 earned',
                    'de' => '0,00 $ erzielt',
                ),
                'revenue_trend_title' => array(
                    'en' => 'Revenue Trend',
                    'de' => 'Umsatztrend',
                ),
            );
        }

        $language = yadore_get_analytics_language();
        $entry = isset($translations[$key]) ? $translations[$key] : array();

        if (isset($entry[$language])) {
            return $entry[$language];
        }

        if (isset($entry['en'])) {
            return $entry['en'];
        }

        return '';
    }
}
