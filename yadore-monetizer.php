<?php
/*
Plugin Name: Yadore Monetizer Pro
Description: Professional Affiliate Marketing Plugin with Complete Feature Set
Version: 2.9.17
Author: Yadore AI
Text Domain: yadore-monetizer
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
*/

if (!defined('ABSPATH')) { exit; }

define('YADORE_PLUGIN_VERSION', '2.9.17');
define('YADORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YADORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YADORE_PLUGIN_FILE', __FILE__);

class YadoreMonetizer {

    private $debug_log = [];
    private $error_log = [];
    private $api_cache = [];
    private $keyword_candidate_cache = [];
    private $last_product_keyword = '';
    private $latest_error_notice = null;
    private $latest_error_notice_checked = false;

    public function __construct() {
        try {
            // Core WordPress hooks
            add_action('init', array($this, 'init'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));

            // Plugin lifecycle hooks
            register_activation_hook(YADORE_PLUGIN_FILE, array($this, 'activate_plugin'));
            register_deactivation_hook(YADORE_PLUGIN_FILE, array($this, 'deactivate_plugin'));

            // v2.7: Complete AJAX endpoints (no duplicates)
            add_action('wp_ajax_yadore_get_overlay_products', array($this, 'ajax_get_overlay_products'));
            add_action('wp_ajax_nopriv_yadore_get_overlay_products', array($this, 'ajax_get_overlay_products'));
            add_action('wp_ajax_yadore_test_gemini_api', array($this, 'ajax_test_gemini_api'));
            add_action('wp_ajax_yadore_test_yadore_api', array($this, 'ajax_test_yadore_api'));
            add_action('wp_ajax_yadore_scan_posts', array($this, 'ajax_scan_posts'));
            add_action('wp_ajax_yadore_bulk_scan_posts', array($this, 'ajax_bulk_scan_posts'));
            add_action('wp_ajax_yadore_start_bulk_scan', array($this, 'ajax_start_bulk_scan'));
            add_action('wp_ajax_yadore_get_scan_progress', array($this, 'ajax_get_scan_progress'));
            add_action('wp_ajax_yadore_get_scanner_overview', array($this, 'ajax_get_scanner_overview'));
            add_action('wp_ajax_yadore_get_scan_results', array($this, 'ajax_get_scan_results'));
            add_action('wp_ajax_yadore_search_posts', array($this, 'ajax_search_posts'));
            add_action('wp_ajax_yadore_get_post_stats', array($this, 'ajax_get_post_stats'));
            add_action('wp_ajax_yadore_scan_single_post', array($this, 'ajax_scan_single_post'));
            add_action('wp_ajax_yadore_get_scanner_analytics', array($this, 'ajax_get_scanner_analytics'));
            add_action('wp_ajax_yadore_get_api_logs', array($this, 'ajax_get_api_logs'));
            add_action('wp_ajax_yadore_clear_api_logs', array($this, 'ajax_clear_api_logs'));
            add_action('wp_ajax_yadore_get_posts_data', array($this, 'ajax_get_posts_data'));
            add_action('wp_ajax_yadore_get_debug_info', array($this, 'ajax_get_debug_info'));
            add_action('wp_ajax_yadore_clear_error_log', array($this, 'ajax_clear_error_log'));
            add_action('wp_ajax_yadore_get_error_logs', array($this, 'ajax_get_error_logs'));
            add_action('wp_ajax_yadore_resolve_error', array($this, 'ajax_resolve_error'));
            add_action('wp_ajax_yadore_test_system_component', array($this, 'ajax_test_system_component'));
            add_action('wp_ajax_yadore_export_data', array($this, 'ajax_export_data'));
            add_action('wp_ajax_yadore_import_data', array($this, 'ajax_import_data'));

            // Content integration
            add_shortcode('yadore_products', array($this, 'shortcode_products'));
            add_filter('the_content', array($this, 'auto_inject_products'), 20);

            // Post save hook for auto-scanning
            add_action('save_post', array($this, 'auto_scan_post_on_save'), 10, 2);

            // Admin notices for errors
            add_action('admin_notices', array($this, 'admin_notices'));

            // Footer hook for overlay
            add_action('wp_footer', array($this, 'render_overlay'));

            // Maintenance
            add_action('yadore_cleanup_logs', array($this, 'cleanup_logs'));

            // Settings link on plugins page
            add_filter('plugin_action_links_' . plugin_basename(YADORE_PLUGIN_FILE), array($this, 'plugin_action_links'));

            // v2.7: Advanced hooks
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
            add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 999);

            $this->log('Plugin v2.9.17 initialized successfully with complete feature set', 'info');

        } catch (Exception $e) {
            $this->log_error('Plugin initialization failed', $e, 'critical');
            add_action('admin_notices', array($this, 'show_initialization_error'));
        }
    }

    public function init() {
        try {
            load_plugin_textdomain('yadore-monetizer', false, dirname(plugin_basename(YADORE_PLUGIN_FILE)) . '/languages');

            // v2.7: Register custom post types for advanced features
            $this->register_custom_post_types();

            // v2.7: Initialize cron jobs
            $this->setup_cron_jobs();

            $this->log('Plugin text domain loaded and custom features initialized', 'info');
        } catch (Exception $e) {
            $this->log_error('Failed to initialize plugin features', $e);
        }
    }

    public function admin_init() { $this->set_default_options(); $this->setup_initial_data(); try {
            // Register settings
            $this->register_settings();

            // Handle settings form submission
            if (isset($_POST['yadore_nonce']) && wp_verify_nonce($_POST['yadore_nonce'], 'yadore_settings')) {
                $this->handle_settings_save();
            }

            // v2.7: Advanced admin features
            $this->setup_advanced_admin_features();

            $this->log('Admin initialized successfully with advanced features', 'info');

        } catch (Exception $e) {
            $this->log_error('Admin initialization failed', $e);
        }
    }

    public function activate_plugin() {
        try {
            $this->create_tables();
            $this->set_default_options();
            $this->setup_initial_data();

            // v2.7: Advanced activation procedures
            $this->setup_advanced_features();

            $this->log('Plugin activated successfully with complete feature set', 'info');

            // Add activation notice
            set_transient('yadore_activation_notice', true, 30);

        } catch (Exception $e) {
            $this->log_error('Plugin activation failed', $e, 'critical');
        }
    }

    public function deactivate_plugin() {
        try {
            // Clean up scheduled events
            wp_clear_scheduled_hook('yadore_cleanup_logs');
            wp_clear_scheduled_hook('yadore_daily_maintenance');
            wp_clear_scheduled_hook('yadore_weekly_reports');

            $this->log('Plugin deactivated successfully', 'info');

        } catch (Exception $e) {
            $this->log_error('Plugin deactivation failed', $e);
        }
    }

    private function get_default_options() {
        return array(
            'yadore_api_key' => '',
            'yadore_market' => $this->get_default_market(),
            'yadore_overlay_enabled' => 1,
            'yadore_auto_detection' => 1,
            'yadore_cache_duration' => 3600,
            'yadore_debug_mode' => 0,
            'yadore_ai_enabled' => 0,
            'yadore_gemini_api_key' => '',
            'yadore_gemini_model' => $this->get_default_gemini_model(),
            'yadore_ai_cache_duration' => 157680000,
            'yadore_ai_prompt' => 'Analyze the title and content to find the most relevant purchase-ready product keyword (brand + model when available). Provide up to three alternate keywords for backup searches and return JSON that matches the schema (keyword, alternate_keywords, confidence, rationale).',
            'yadore_ai_temperature' => '0.3',
            'yadore_ai_max_tokens' => 50,
            'yadore_auto_scan_posts' => 1,
            'yadore_bulk_scan_completed' => 0,
            'yadore_min_content_words' => 300,
            'yadore_scan_frequency' => 'daily',
            'yadore_api_logging_enabled' => 1,
            'yadore_log_retention_days' => 30,
            'yadore_error_logging_enabled' => 1,
            'yadore_error_retention_days' => 90,
            'yadore_overlay_delay' => 2000,
            'yadore_overlay_scroll_threshold' => 300,
            'yadore_overlay_limit' => 3,
            'yadore_overlay_position' => 'center',
            'yadore_overlay_animation' => 'fade',
            'yadore_injection_method' => 'after_paragraph',
            'yadore_injection_position' => 2,
            'yadore_performance_mode' => 0,
            'yadore_analytics_enabled' => 1,
            'yadore_export_enabled' => 1,
            'yadore_backup_enabled' => 0,
            'yadore_multisite_sync' => 0,
        );
    }

    private function get_default_market() {
        $locale = function_exists('get_locale') ? get_locale() : 'de_DE';
        if (is_string($locale) && strlen($locale) >= 2) {
            $candidate = strtoupper(substr($locale, 0, 2));
            if (preg_match('/^[A-Z]{2}$/', $candidate)) {
                return $candidate;
            }
        }

        return 'DE';
    }

    private function set_default_options() {
        try {
            $defaults = $this->get_default_options();

            foreach ($defaults as $option => $default) {
                $current = get_option($option, null);

                if ($current === null) {
                    add_option($option, $default);
                }
            }

            $this->log('Default options verified', 'debug');

        } catch (Exception $e) {
            $this->log_error('Failed to initialize default options', $e);
        }
    }

    private function setup_initial_data() {
        try {
            $this->set_default_options();

            if (get_option('yadore_plugin_version') !== YADORE_PLUGIN_VERSION) {
                update_option('yadore_plugin_version', YADORE_PLUGIN_VERSION);
            }

            $legacy_prompt = 'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.';
            $stored_prompt = get_option('yadore_ai_prompt', '');
            if ($stored_prompt === $legacy_prompt) {
                update_option('yadore_ai_prompt', 'Analyze the title and content to find the most relevant purchase-ready product keyword (brand + model when available). Provide up to three alternate keywords for backup searches and return JSON that matches the schema (keyword, alternate_keywords, confidence, rationale).');
            }

            if (false === get_option('yadore_install_timestamp', false)) {
                add_option('yadore_install_timestamp', current_time('mysql'));
            }

            if (false === get_option('yadore_stats_cache', false)) {
                add_option('yadore_stats_cache', array(
                    'total_products' => 0,
                    'scanned_posts' => 0,
                    'overlay_views' => 0,
                    'conversion_rate' => '0%',
                ), '', 'no');
            }

        } catch (Exception $e) {
            $this->log_error('Failed to setup initial data', $e);
        }
    }

    private function setup_advanced_features() {
        try {
            $this->setup_cron_jobs();

            $schedule_offset = defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 3600;
            if (!wp_next_scheduled('yadore_cleanup_logs')) {
                wp_schedule_event(time() + $schedule_offset, 'daily', 'yadore_cleanup_logs');
            }

        } catch (Exception $e) {
            $this->log_error('Failed to setup advanced features', $e);
        }
    }

    private function handle_settings_save() {
        try {
            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            $flat_post = array();
            if (!empty($_POST['yadore_settings']) && is_array($_POST['yadore_settings'])) {
                foreach (wp_unslash($_POST['yadore_settings']) as $key => $value) {
                    $flat_post[$key] = $value;
                }
            }

            foreach ($_POST as $key => $value) {
                if ($key === 'yadore_settings') {
                    continue;
                }

                $flat_post[$key] = is_array($value) ? $value : wp_unslash($value);
            }

            if (isset($flat_post['yadore_gemini_enabled'])) {
                $flat_post['yadore_ai_enabled'] = $flat_post['yadore_gemini_enabled'];
            }

            $boolean_options = array(
                'yadore_overlay_enabled',
                'yadore_auto_detection',
                'yadore_debug_mode',
                'yadore_ai_enabled',
                'yadore_auto_scan_posts',
                'yadore_bulk_scan_completed',
                'yadore_api_logging_enabled',
                'yadore_error_logging_enabled',
                'yadore_analytics_enabled',
                'yadore_export_enabled',
                'yadore_backup_enabled',
                'yadore_multisite_sync',
                'yadore_performance_mode',
            );

            foreach ($boolean_options as $option) {
                $value = !empty($flat_post[$option]);
                update_option($option, $this->sanitize_bool($value));
            }

            $int_options = array(
                'yadore_overlay_delay',
                'yadore_overlay_scroll_threshold',
                'yadore_overlay_limit',
                'yadore_injection_position',
                'yadore_cache_duration',
                'yadore_ai_cache_duration',
                'yadore_log_retention_days',
                'yadore_error_retention_days',
                'yadore_ai_max_tokens',
                'yadore_min_content_words',
            );

            foreach ($int_options as $option) {
                if (isset($flat_post[$option])) {
                    $value = max(0, intval($flat_post[$option]));

                    if ($option === 'yadore_ai_max_tokens') {
                        $value = min(10000, $value);
                    }

                    update_option($option, $value);
                }
            }

            if (isset($flat_post['yadore_ai_temperature'])) {
                $temperature = floatval($flat_post['yadore_ai_temperature']);
                $temperature = max(0, min(2, $temperature));
                update_option('yadore_ai_temperature', (string)$temperature);
            }

            if (isset($flat_post['yadore_api_key'])) {
                $sanitized_key = $this->sanitize_api_key($flat_post['yadore_api_key']);
                $previous_key = get_option('yadore_api_key', '');
                update_option('yadore_api_key', $sanitized_key);

                if ($sanitized_key !== $previous_key) {
                    delete_transient('yadore_available_markets');
                }
            }

            if (isset($flat_post['yadore_market'])) {
                $market_value = $this->sanitize_market($flat_post['yadore_market']);
                if ($market_value === '') {
                    $market_value = $this->get_default_market();
                }

                update_option('yadore_market', $market_value);
            }

            if (!empty($flat_post['yadore_gemini_api_key_remove'])) {
                delete_option('yadore_gemini_api_key');
            } elseif (isset($flat_post['yadore_gemini_api_key'])) {
                $submitted_key = trim((string) $flat_post['yadore_gemini_api_key']);
                if ($submitted_key !== '') {
                    update_option('yadore_gemini_api_key', $this->sanitize_api_key($submitted_key));
                }
            }

            if (isset($flat_post['yadore_gemini_model'])) {
                update_option('yadore_gemini_model', $this->sanitize_model($flat_post['yadore_gemini_model']));
            }

            if (isset($flat_post['yadore_ai_prompt'])) {
                update_option('yadore_ai_prompt', sanitize_textarea_field($flat_post['yadore_ai_prompt']));
            }

            $select_mappings = array(
                'yadore_overlay_position' => array('center', 'bottom-right', 'bottom-left'),
                'yadore_overlay_animation' => array('fade', 'slide', 'zoom'),
                'yadore_injection_method' => array('after_paragraph', 'end_of_content', 'before_content'),
                'yadore_scan_frequency' => array('hourly', 'twicedaily', 'daily', 'weekly'),
            );

            foreach ($select_mappings as $option => $allowed) {
                if (isset($flat_post[$option])) {
                    $value = $this->sanitize_text($flat_post[$option]);
                    if (!in_array($value, $allowed, true)) {
                        $value = reset($allowed);
                    }
                    update_option($option, $value);
                }
            }

            $this->log('Settings saved', 'info');

        } catch (Exception $e) {
            $this->log_error('Settings save failed', $e);
        }
    }

    private function get_page_data($page) {
        $options = array(
            'yadore_gemini_enabled' => (bool) get_option('yadore_ai_enabled', false),
            'yadore_gemini_api_key' => get_option('yadore_gemini_api_key', ''),
            'yadore_gemini_model' => get_option('yadore_gemini_model', $this->get_default_gemini_model()),
            'yadore_market' => get_option('yadore_market', $this->get_default_market()),
        );

        $data = array(
            'options' => $options,
            'version' => YADORE_PLUGIN_VERSION,
            'selected_gemini_model' => $this->sanitize_model($options['yadore_gemini_model']),
            'gemini_models' => $this->get_supported_gemini_models(),
        );

        if ($page === 'dashboard') {
            $stats = get_option('yadore_stats_cache', array());
            if (!is_array($stats)) {
                $stats = array();
            }

            $data['stats'] = wp_parse_args($stats, array(
                'total_products' => 0,
                'scanned_posts' => 0,
                'overlay_views' => 0,
                'conversion_rate' => '0%',
            ));
        }

        if ($page === 'debug') {
            $data['debug_log'] = $this->get_debug_log();
        }

        if ($page === 'settings') {
            $data['available_markets'] = $this->get_available_markets();
            $data['default_market'] = $this->get_default_market();
        }

        return $data;
    }

    private function get_available_markets($force_refresh = false) {
        $transient_key = 'yadore_available_markets';

        if (!$force_refresh) {
            $cached = get_transient($transient_key);
            if ($cached !== false) {
                return is_array($cached) ? $cached : array();
            }
        }

        $api_key = trim((string) get_option('yadore_api_key'));
        if ($api_key === '') {
            return array();
        }

        $endpoint = 'https://api.yadore.com/v2/markets';
        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'API-Key' => $api_key,
                'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            ),
            'timeout' => 15,
        );

        $response = wp_remote_get($endpoint, $args);

        if (is_wp_error($response)) {
            $this->log_error('Failed to load Yadore markets: ' . $response->get_error_message());
            set_transient($transient_key, array(), HOUR_IN_SECONDS);
            return array();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status >= 200 && $status < 300 && is_array($decoded)) {
            $markets = array();

            if (isset($decoded['markets']) && is_array($decoded['markets'])) {
                foreach ($decoded['markets'] as $market) {
                    if (!is_array($market)) {
                        continue;
                    }

                    $id = isset($market['id']) ? $this->sanitize_market($market['id']) : '';
                    if ($id === '') {
                        continue;
                    }

                    $label = isset($market['name']) ? sanitize_text_field((string) $market['name']) : strtoupper($id);
                    $markets[$id] = $label;
                }
            }

            if (!empty($markets)) {
                set_transient($transient_key, $markets, DAY_IN_SECONDS);
                return $markets;
            }
        } else {
            $this->log_api_call('yadore', $endpoint, 'error', array(
                'status' => $status,
                'response' => $decoded,
            ));
        }

        set_transient($transient_key, array(), HOUR_IN_SECONDS);
        return array();
    }

    public function add_dashboard_widgets() {
        try {
            if (!current_user_can('manage_options')) {
                return;
            }

            wp_add_dashboard_widget(
                'yadore_monetizer_overview',
                __('Yadore Monetizer Pro', 'yadore-monetizer'),
                array($this, 'render_dashboard_widget')
            );

        } catch (Exception $e) {
            $this->log_error('Dashboard widget registration failed', $e);
        }
    }

    public function render_dashboard_widget() {
        $stats = get_option('yadore_stats_cache', array());
        if (!is_array($stats)) {
            $stats = array();
        }

        $stats = wp_parse_args($stats, array(
            'total_products' => 0,
            'scanned_posts' => 0,
            'overlay_views' => 0,
            'conversion_rate' => '0%',
        ));

        echo '<p>' . esc_html__('Quick overview of your monetization activity.', 'yadore-monetizer') . '</p>';
        echo '<ul class="yadore-dashboard-widget">';
        echo '<li><strong>' . esc_html__('Products displayed:', 'yadore-monetizer') . '</strong> ' . esc_html((string) $stats['total_products']) . '</li>';
        echo '<li><strong>' . esc_html__('Posts scanned:', 'yadore-monetizer') . '</strong> ' . esc_html((string) $stats['scanned_posts']) . '</li>';
        echo '<li><strong>' . esc_html__('Overlay views:', 'yadore-monetizer') . '</strong> ' . esc_html((string) $stats['overlay_views']) . '</li>';
        echo '<li><strong>' . esc_html__('Conversion rate:', 'yadore-monetizer') . '</strong> ' . esc_html((string) $stats['conversion_rate']) . '</li>';
        echo '</ul>';
    }

    public function add_admin_bar_menu($admin_bar) {
        try {
            if (!current_user_can('manage_options') || !$admin_bar instanceof WP_Admin_Bar) {
                return;
            }

            $admin_bar->add_node(array(
                'id' => 'yadore-monetizer',
                'title' => __('Yadore Monetizer', 'yadore-monetizer'),
                'href' => admin_url('admin.php?page=yadore-monetizer'),
                'meta' => array('class' => 'yadore-admin-bar'),
            ));

            $admin_bar->add_node(array(
                'id' => 'yadore-monetizer-settings',
                'parent' => 'yadore-monetizer',
                'title' => __('Settings', 'yadore-monetizer'),
                'href' => admin_url('admin.php?page=yadore-settings'),
            ));

            $admin_bar->add_node(array(
                'id' => 'yadore-monetizer-debug',
                'parent' => 'yadore-monetizer',
                'title' => __('Debug', 'yadore-monetizer'),
                'href' => admin_url('admin.php?page=yadore-debug'),
            ));

        } catch (Exception $e) {
            $this->log_error('Admin bar menu registration failed', $e);
        }
    }

    public function add_contextual_help() {
        try {
            if (!function_exists('get_current_screen')) {
                return;
            }

            $screen = get_current_screen();
            if (!$screen || strpos($screen->id, 'yadore') === false) {
                return;
            }

            $screen->add_help_tab(array(
                'id' => 'yadore_overview',
                'title' => __('Overview', 'yadore-monetizer'),
                'content' => '<p>' . esc_html__('Manage monetization settings, AI analysis and product display options from the tabs on this screen.', 'yadore-monetizer') . '</p>',
            ));

            $screen->add_help_tab(array(
                'id' => 'yadore_support',
                'title' => __('Support', 'yadore-monetizer'),
                'content' => '<p>' . esc_html__('Need help? Review the documentation in the API Docs section or contact support through your Yadore account.', 'yadore-monetizer') . '</p>',
            ));

            $screen->set_help_sidebar('<p><strong>' . esc_html__('Helpful Resources', 'yadore-monetizer') . '</strong></p><p><a href="https://yadore.com" target="_blank" rel="noopener">Yadore.com</a></p>');

        } catch (Exception $e) {
            $this->log_error('Contextual help registration failed', $e);
        }
    }

    public function add_screen_options() {
        try {
            if (!function_exists('get_current_screen') || !function_exists('add_screen_option')) {
                return;
            }

            $screen = get_current_screen();
            if (!$screen || strpos($screen->id, 'yadore') === false) {
                return;
            }

            add_screen_option('per_page', array(
                'label' => __('Items per page', 'yadore-monetizer'),
                'default' => 20,
                'option' => 'yadore_items_per_page',
            ));

        } catch (Exception $e) {
            $this->log_error('Screen options registration failed', $e);
        }
    }

    public function cleanup_logs() {
        global $wpdb;

        try {
            if (!$wpdb instanceof wpdb) {
                return;
            }

            $log_retention = max(1, intval(get_option('yadore_log_retention_days', 30)));
            $error_retention = max(1, intval(get_option('yadore_error_retention_days', 90)));

            $logs_table = $wpdb->prefix . 'yadore_api_logs';
            $error_table = $wpdb->prefix . 'yadore_error_logs';

            $day_in_seconds = defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400;
            $log_threshold = gmdate('Y-m-d H:i:s', time() - ($log_retention * $day_in_seconds));
            $error_threshold = gmdate('Y-m-d H:i:s', time() - ($error_retention * $day_in_seconds));

            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $logs_table))) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$logs_table} WHERE created_at < %s", $log_threshold));
            }

            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $error_table))) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$error_table} WHERE created_at < %s", $error_threshold));
            }

            $this->log('Scheduled log cleanup executed', 'debug');

        } catch (Exception $e) {
            $this->log_error('Log cleanup failed', $e);
        }
    }

    // v2.7: Complete WordPress Admin Menu Integration
    public function admin_menu() {
        try {
            // Main menu page with enhanced dashboard
            add_menu_page(
                'Yadore Monetizer Pro', // Page title
                'Yadore Monetizer', // Menu title
                'manage_options',   // Capability
                'yadore-monetizer', // Menu slug
                array($this, 'admin_dashboard_page'), // Callback
                'dashicons-cart',   // Icon
                30                  // Position
            );

            // Dashboard (enhanced)
            add_submenu_page(
                'yadore-monetizer',
                'Dashboard',
                'Dashboard',
                'manage_options',
                'yadore-monetizer',
                array($this, 'admin_dashboard_page')
            );

            // Settings page (complete)
            add_submenu_page(
                'yadore-monetizer',
                'Settings',
                'Settings',
                'manage_options',
                'yadore-settings',
                array($this, 'admin_settings_page')
            );

            // AI Management page (enhanced)
            add_submenu_page(
                'yadore-monetizer',
                'AI Management',
                'AI Management',
                'manage_options',
                'yadore-ai',
                array($this, 'admin_ai_page')
            );

            // Post Scanner page (complete)
            add_submenu_page(
                'yadore-monetizer',
                'Post Scanner',
                'Post Scanner',
                'manage_options',
                'yadore-scanner',
                array($this, 'admin_scanner_page')
            );

            // API Documentation page (enhanced)
            add_submenu_page(
                'yadore-monetizer',
                'API Documentation',
                'API Documentation',
                'manage_options',
                'yadore-api-docs',
                array($this, 'admin_api_docs_page')
            );

            // Debug & Error Analysis page (complete)
            add_submenu_page(
                'yadore-monetizer',
                'Debug & Error Analysis',
                'Debug & Errors',
                'manage_options',
                'yadore-debug',
                array($this, 'admin_debug_page')
            );

            // v2.7: Analytics page
            add_submenu_page(
                'yadore-monetizer',
                'Analytics & Reports',
                'Analytics',
                'manage_options',
                'yadore-analytics',
                array($this, 'admin_analytics_page')
            );

            // v2.7: Tools page
            add_submenu_page(
                'yadore-monetizer',
                'Tools & Utilities',
                'Tools',
                'manage_options',
                'yadore-tools',
                array($this, 'admin_tools_page')
            );

            $this->log('Admin menu registered successfully with complete feature set', 'info');

        } catch (Exception $e) {
            $this->log_error('Admin menu registration failed', $e);
        }
    }

    // v2.7: Complete WordPress Admin Page Callbacks
    public function admin_dashboard_page() {
        $this->render_admin_page('dashboard');
    }

    public function admin_settings_page() {
        $this->render_admin_page('settings');
    }

    public function admin_ai_page() {
        $this->render_admin_page('ai');
    }

    public function admin_scanner_page() {
        $this->render_admin_page('scanner');
    }

    public function admin_api_docs_page() {
        $this->render_admin_page('api-docs');
    }

    public function admin_debug_page() {
        $this->render_admin_page('debug');
    }

    public function admin_analytics_page() {
        $this->render_admin_page('analytics');
    }

    public function admin_tools_page() {
        $this->render_admin_page('tools');
    }

    private function render_admin_page($page) {
        try {
            $template_file = YADORE_PLUGIN_DIR . "templates/admin-{$page}.php";

            if (file_exists($template_file)) {
                // v2.7: Pass data to templates
                $data = $this->get_page_data($page);
                extract($data);
                include $template_file;
            } else {
                echo '<div class="wrap"><h1>Page Not Found</h1><p>The requested admin page could not be loaded.</p></div>';
                $this->log_error("Admin template not found: {$template_file}");
            }

        } catch (Exception $e) {
            $this->log_error("Failed to render admin page: {$page}", $e);
            echo '<div class="wrap"><h1>Error</h1><p>An error occurred while loading this page.</p></div>';
        }
    }

    // v2.7: Enhanced Script and Style Enqueuing
    public function admin_enqueue_scripts($hook) {
        try {
            $is_plugin_screen = strpos($hook, 'yadore') !== false;
            $recent_error = $this->get_latest_unresolved_error();
            $needs_notice_assets = $recent_error !== null;

            if (!$is_plugin_screen && !$needs_notice_assets) {
                return;
            }

            if ($is_plugin_screen) {
                wp_enqueue_style(
                    'yadore-admin-css',
                    YADORE_PLUGIN_URL . 'assets/css/admin.css',
                    array(),
                    YADORE_PLUGIN_VERSION
                );
            }

            wp_enqueue_script(
                'yadore-admin-js',
                YADORE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-util'),
                YADORE_PLUGIN_VERSION,
                true
            );

            if ($is_plugin_screen) {
                wp_enqueue_script(
                    'yadore-charts',
                    YADORE_PLUGIN_URL . 'assets/js/chart.min.js',
                    array(),
                    '3.9.1',
                    true
                );
            }

            wp_localize_script('yadore-admin-js', 'yadore_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yadore_admin_nonce'),
                'debug' => get_option('yadore_debug_mode', false),
                'version' => YADORE_PLUGIN_VERSION,
                'plugin_url' => YADORE_PLUGIN_URL,
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'yadore-monetizer'),
                    'processing' => __('Processing...', 'yadore-monetizer'),
                    'error' => __('An error occurred. Please try again.', 'yadore-monetizer'),
                    'success' => __('Operation completed successfully.', 'yadore-monetizer')
                )
            ));

            $this->log('Admin scripts enqueued successfully', 'info');

        } catch (Exception $e) {
            $this->log_error('Admin script enqueuing failed', $e);
        }
    }

    public function frontend_enqueue_scripts() {
        try {
            // Don't load on admin pages
            if (is_admin()) {
                return;
            }

            // Frontend CSS
            wp_enqueue_style(
                'yadore-frontend-css',
                YADORE_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                YADORE_PLUGIN_VERSION
            );

            // Frontend JavaScript (only if overlay is enabled)
            if (get_option('yadore_overlay_enabled', true)) {
                wp_enqueue_script(
                    'yadore-frontend-js',
                    YADORE_PLUGIN_URL . 'assets/js/frontend.js',
                    array('jquery'),
                    YADORE_PLUGIN_VERSION,
                    true
                );

                // Localize script for AJAX
                wp_localize_script('yadore-frontend-js', 'yadore_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('yadore_frontend_nonce'),
                    'overlay_enabled' => get_option('yadore_overlay_enabled', true),
                    'delay' => get_option('yadore_overlay_delay', 2000),
                    'scroll_threshold' => get_option('yadore_overlay_scroll_threshold', 300),
                    'limit' => get_option('yadore_overlay_limit', 3),
                    'auto_detection' => get_option('yadore_auto_detection', true),
                    'post_id' => get_queried_object_id(),
                ));
            }

            $this->log('Frontend scripts enqueued successfully', 'info');

        } catch (Exception $e) {
            $this->log_error('Frontend script enqueuing failed', $e);
        }
    }

    // v2.7: Complete Settings Registration
    private function register_settings() {
        $settings = array(
            // Basic settings
            'yadore_api_key',
            'yadore_market',
            'yadore_overlay_enabled',
            'yadore_auto_detection',
            'yadore_cache_duration',
            'yadore_debug_mode',

            // AI settings
            'yadore_ai_enabled',
            'yadore_gemini_api_key',
            'yadore_gemini_model',
            'yadore_ai_cache_duration',
            'yadore_ai_prompt',
            'yadore_ai_temperature',
            'yadore_ai_max_tokens',

            // Scanner settings
            'yadore_auto_scan_posts',
            'yadore_bulk_scan_completed',
            'yadore_min_content_words',
            'yadore_scan_frequency',

            // Logging settings
            'yadore_api_logging_enabled',
            'yadore_log_retention_days',
            'yadore_error_logging_enabled',
            'yadore_error_retention_days',

            // Overlay settings
            'yadore_overlay_delay',
            'yadore_overlay_scroll_threshold',
            'yadore_overlay_limit',
            'yadore_overlay_position',
            'yadore_overlay_animation',

            // v2.7: Advanced settings
            'yadore_performance_mode',
            'yadore_analytics_enabled',
            'yadore_export_enabled',
            'yadore_backup_enabled',
            'yadore_multisite_sync'
        );

        foreach ($settings as $setting) {
            register_setting('yadore_settings_group', $setting);
        }
    }

    // v2.7: Complete AJAX Endpoints (no duplicates, all implemented)
    public function ajax_get_overlay_products() {
        try {
            check_ajax_referer('yadore_frontend_nonce', 'nonce');

            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 3;
            if ($limit <= 0) {
                $limit = 3;
            }
            $limit = min(50, $limit);

            $page_content = isset($_POST['page_content'])
                ? wp_kses_post(wp_unslash($_POST['page_content']))
                : '';
            $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
            $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

            if (!$post_id && $page_url) {
                $post_id = url_to_postid($page_url);
            }

            if (!$post_id && !empty($_SERVER['HTTP_REFERER'])) {
                $post_id = url_to_postid(esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])));
            }

            $keyword = $this->resolve_product_keyword($post_id, $page_content);

            if (empty($keyword)) {
                wp_send_json_success(array(
                    'products' => array(),
                    'keyword' => '',
                    'count' => 0,
                    'post_id' => $post_id,
                    'message' => __('No suitable keyword detected for this page.', 'yadore-monetizer'),
                ));
            }

            $products = $this->get_products($keyword, $limit, $post_id);

            // v2.7: Track overlay views
            $this->track_overlay_view($post_id, $keyword, count($products));

            wp_send_json_success(array(
                'products' => $products,
                'keyword' => $keyword,
                'count' => count($products),
                'post_id' => $post_id
            ));

        } catch (Exception $e) {
            $this->log_error('Overlay products AJAX failed', $e);
            wp_send_json_error('Failed to load products');
        }
    }

    public function ajax_test_gemini_api() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            $api_key = get_option('yadore_gemini_api_key');
            if (empty($api_key)) {
                throw new Exception('Gemini API key not configured');
            }

            $model = $this->sanitize_model(get_option('yadore_gemini_model', $this->get_default_gemini_model()));
            $test_content = 'This is a comprehensive test post about smartphone reviews, mobile technology, and the latest iPhone features.';

            $result = $this->call_gemini_api('Test Post - Smartphone Review', $test_content, false);

            if (is_array($result) && isset($result['error'])) {
                throw new Exception($result['error']);
            }

            if (is_string($result) && trim($result) !== '') {
                $result = array('keyword' => sanitize_text_field($result));
            }

            if (!is_array($result) || empty($result['keyword'])) {
                throw new Exception(__('Gemini API did not return a keyword.', 'yadore-monetizer'));
            }

            // v2.7: Log API test
            $this->log_api_call('gemini', 'test', 'success', array('model' => $model, 'result' => $result));

            wp_send_json_success(array(
                'message' => 'Gemini API connection successful',
                'result' => $result,
                'model' => $model,
                'timestamp' => current_time('mysql')
            ));

        } catch (Exception $e) {
            $this->log_error('Gemini API test failed', $e);
            wp_send_json_error('API test failed: ' . $e->getMessage());
        }
    }

    public function ajax_test_yadore_api() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception('Insufficient permissions');
            }

            $keyword = 'Kopfkissen';
            $limit = 3;

            $api_key = trim((string) get_option('yadore_api_key'));
            if ($api_key === '') {
                throw new Exception(__('No Yadore API key configured. Please add your key in the settings.', 'yadore-monetizer'));
            }

            $products = $this->get_products($keyword, $limit);
            if (!is_array($products)) {
                $products = array();
            }

            $product_count = count($products);
            $log_data = array(
                'product_count' => $product_count,
                'mode' => 'live',
                'keyword' => $keyword,
            );

            if ($product_count === 0) {
                $log_data['no_results'] = true;
                $this->log_api_call('yadore', 'test', 'success', $log_data);

                wp_send_json_success(array(
                    'message' => __('Yadore API connection successful, but no products were returned for the test keyword. Try another keyword or verify your account configuration.', 'yadore-monetizer'),
                    'product_count' => 0,
                    'sample_product' => null,
                    'timestamp' => current_time('mysql'),
                    'mode' => 'live',
                ));
            }

            $this->log_api_call('yadore', 'test', 'success', $log_data);

            wp_send_json_success(array(
                'message' => __('Yadore API connection successful', 'yadore-monetizer'),
                'product_count' => $product_count,
                'sample_product' => $products[0],
                'timestamp' => current_time('mysql'),
                'mode' => 'live',
            ));

        } catch (Exception $e) {
            $this->log_error('Yadore API test failed', $e);
            wp_send_json_error('API test failed: ' . $e->getMessage());
        }
    }

    public function ajax_get_scanner_overview() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $overview = $this->get_scanner_overview_stats();

            wp_send_json_success($overview);
        } catch (Exception $e) {
            $this->log_error('Failed to load scanner overview', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_scan_posts() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $request = isset($_POST) ? wp_unslash($_POST) : array();
            $scan_request = $this->prepare_bulk_scan_request($request);
            $results = $this->execute_bulk_scan_immediately($scan_request['post_ids'], $scan_request['options']);

            wp_send_json_success($results);
        } catch (Exception $e) {
            $this->log_error('Legacy scan posts request failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_bulk_scan_posts() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $request = isset($_POST) ? wp_unslash($_POST) : array();
            $scan_request = $this->prepare_bulk_scan_request($request);
            $results = $this->execute_bulk_scan_immediately($scan_request['post_ids'], $scan_request['options']);

            wp_send_json_success($results);
        } catch (Exception $e) {
            $this->log_error('Legacy bulk scan request failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_start_bulk_scan() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $post_types = $this->sanitize_post_types(isset($_POST['post_types']) ? (array) wp_unslash($_POST['post_types']) : array('post'));
            $post_status = $this->sanitize_post_statuses(isset($_POST['post_status']) ? (array) wp_unslash($_POST['post_status']) : array('publish'));
            $scan_options = isset($_POST['scan_options']) ? (array) wp_unslash($_POST['scan_options']) : array();
            $min_words = isset($_POST['min_words']) ? intval($_POST['min_words']) : 0;

            if (empty($post_types)) {
                throw new Exception(__('No valid post types supplied for scanning.', 'yadore-monetizer'));
            }

            if (empty($post_status)) {
                throw new Exception(__('No valid post status supplied for scanning.', 'yadore-monetizer'));
            }

            $post_ids = $this->find_posts_for_scanning($post_types, $post_status, $min_words);

            if (empty($post_ids)) {
                throw new Exception(__('No posts matched the selected criteria.', 'yadore-monetizer'));
            }

            $scan_id = uniqid('yadore_scan_', true);

            $state = array(
                'scan_id' => $scan_id,
                'post_ids' => array_values($post_ids),
                'current_index' => 0,
                'total' => count($post_ids),
                'completed' => 0,
                'options' => array(
                    'force_rescan' => in_array('force_rescan', $scan_options, true),
                    'use_ai' => in_array('use_ai', $scan_options, true),
                    'validate_products' => in_array('validate_products', $scan_options, true),
                    'min_words' => max(0, $min_words),
                ),
                'last_updated' => time(),
            );

            $this->store_bulk_scan_state($scan_id, $state);

            wp_send_json_success(array(
                'scan_id' => $scan_id,
                'total' => $state['total'],
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to start bulk scan', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_scan_progress() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $scan_id = isset($_POST['scan_id']) ? sanitize_text_field(wp_unslash($_POST['scan_id'])) : '';
            if ($scan_id === '') {
                throw new Exception(__('Invalid scan identifier.', 'yadore-monetizer'));
            }

            $state = $this->get_bulk_scan_state($scan_id);
            if (empty($state)) {
                wp_send_json_success(array(
                    'total' => 0,
                    'completed' => 0,
                    'percentage' => 100,
                    'results' => array(),
                ));
            }

            $chunk_size = 3;
            $results = array();

            for ($i = 0; $i < $chunk_size; $i++) {
                if ($state['current_index'] >= $state['total']) {
                    break;
                }

                $post_id = (int) $state['post_ids'][$state['current_index']];
                $state['current_index']++;
                $state['completed']++;

                $scan_result = $this->process_post_scan($post_id, $state['options']);
                $results[] = $scan_result;
            }

            $percentage = $state['total'] > 0
                ? min(100, (int) round(($state['completed'] / $state['total']) * 100))
                : 100;

            if ($state['completed'] >= $state['total']) {
                $this->delete_bulk_scan_state($scan_id);
                $this->update_scanned_posts_stat();
                update_option('yadore_bulk_scan_completed', current_time('mysql'));
            } else {
                $state['last_updated'] = time();
                $this->store_bulk_scan_state($scan_id, $state);
            }

            wp_send_json_success(array(
                'total' => $state['total'],
                'completed' => min($state['completed'], $state['total']),
                'percentage' => $percentage,
                'results' => $results,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to process bulk scan progress', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_scan_results() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $filter = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : 'all';
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
            $export = !empty($_POST['export']);

            if ($per_page <= 0) {
                $per_page = 10;
            }

            if ($export) {
                $per_page = 500;
                $page = 1;
            }

            $per_page = min(1000, max(5, $per_page));

            $results = $this->get_scan_results($filter, $page, $per_page);

            if ($export) {
                wp_send_json_success(array(
                    'results' => $results['results'],
                ));
            }

            wp_send_json_success($results);
        } catch (Exception $e) {
            $this->log_error('Failed to load scan results', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_search_posts() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';

            if ($query === '') {
                wp_send_json_success(array('results' => array()));
            }

            $results = $this->search_posts_for_scanner($query);

            wp_send_json_success(array('results' => $results));
        } catch (Exception $e) {
            $this->log_error('Failed to search posts for scanner', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_scan_single_post() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            if ($post_id <= 0) {
                throw new Exception(__('Invalid post selected for scanning.', 'yadore-monetizer'));
            }

            $options = array(
                'force_rescan' => !empty($_POST['force_rescan']),
                'use_ai' => !empty($_POST['use_ai']),
                'validate_products' => !empty($_POST['validate_products']),
                'min_words' => isset($_POST['min_words']) ? max(0, intval($_POST['min_words'])) : 0,
            );

            $result = $this->process_post_scan($post_id, $options);
            $this->update_scanned_posts_stat();

            if ($result['status'] === 'failed') {
                throw new Exception($result['message']);
            }

            wp_send_json_success(array('result' => $result));
        } catch (Exception $e) {
            $this->log_error('Single post scan failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_scanner_analytics() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $analytics = $this->get_scanner_analytics();

            wp_send_json_success($analytics);
        } catch (Exception $e) {
            $this->log_error('Failed to load scanner analytics', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    // Continue with more AJAX endpoints...
    // (I'll implement more in the next parts to avoid hitting token limits)

    // v2.7: Enhanced Database Table Creation
    public function create_tables() {
        global $wpdb;

        try {
            $charset_collate = $wpdb->get_charset_collate();

            // AI Cache table (enhanced)
            $cache_table = $wpdb->prefix . 'yadore_ai_cache';
            $sql1 = "CREATE TABLE $cache_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                content_hash varchar(64) NOT NULL,
                post_id bigint(20),
                ai_keywords text,
                extracted_keywords text,
                ai_confidence decimal(5,4) DEFAULT 0.0000,
                api_cost decimal(10,6) DEFAULT 0.000000,
                token_count int DEFAULT 0,
                model_used varchar(100),
                processing_time_ms int DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                expires_at datetime,
                PRIMARY KEY (id),
                UNIQUE KEY content_hash (content_hash),
                KEY post_id (post_id),
                KEY expires_at (expires_at),
                KEY model_used (model_used)
            ) $charset_collate;";

            // Post Keywords table (enhanced)
            $posts_table = $wpdb->prefix . 'yadore_post_keywords';
            $sql2 = "CREATE TABLE $posts_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                post_title varchar(500),
                primary_keyword varchar(200),
                fallback_keyword varchar(200),
                keyword_confidence decimal(5,4) DEFAULT 0.0000,
                product_validated tinyint(1) DEFAULT 0,
                product_count int DEFAULT 0,
                word_count int DEFAULT 0,
                content_hash varchar(64),
                last_scanned datetime DEFAULT CURRENT_TIMESTAMP,
                scan_status varchar(50) DEFAULT 'pending',
                scan_error text,
                scan_attempts int DEFAULT 0,
                scan_duration_ms int DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY post_id (post_id),
                KEY last_scanned (last_scanned),
                KEY scan_status (scan_status),
                KEY product_validated (product_validated),
                KEY keyword_confidence (keyword_confidence)
            ) $charset_collate;";

            // API Logs table (enhanced)
            $logs_table = $wpdb->prefix . 'yadore_api_logs';
            $sql3 = "CREATE TABLE $logs_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                api_type varchar(50) NOT NULL,
                endpoint_url varchar(500),
                request_method varchar(10) DEFAULT 'POST',
                request_headers text,
                request_body text,
                response_code int,
                response_headers text,
                response_body text,
                response_time_ms int,
                success tinyint(1) DEFAULT 0,
                error_message text,
                post_id bigint(20),
                user_id bigint(20),
                ip_address varchar(45),
                user_agent text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY api_type (api_type),
                KEY created_at (created_at),
                KEY success (success),
                KEY post_id (post_id),
                KEY response_time_ms (response_time_ms)
            ) $charset_collate;";

            // Error Logs table (enhanced)
            $error_logs_table = $wpdb->prefix . 'yadore_error_logs';
            $sql4 = "CREATE TABLE $error_logs_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                error_type varchar(100) NOT NULL,
                error_message text NOT NULL,
                error_code varchar(50),
                stack_trace text,
                context_data text,
                post_id bigint(20),
                user_id bigint(20),
                ip_address varchar(45),
                user_agent text,
                request_uri varchar(500),
                severity enum('low','medium','high','critical') DEFAULT 'medium',
                resolved tinyint(1) DEFAULT 0,
                resolution_notes text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                resolved_at datetime,
                PRIMARY KEY (id),
                KEY error_type (error_type),
                KEY created_at (created_at),
                KEY severity (severity),
                KEY resolved (resolved),
                KEY post_id (post_id)
            ) $charset_collate;";

            // v2.7: Analytics table
            $analytics_table = $wpdb->prefix . 'yadore_analytics';
            $sql5 = "CREATE TABLE $analytics_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                event_type varchar(50) NOT NULL,
                event_data text,
                post_id bigint(20),
                user_id bigint(20),
                session_id varchar(32),
                ip_address varchar(45),
                user_agent text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY created_at (created_at),
                KEY post_id (post_id),
                KEY session_id (session_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);
            dbDelta($sql4);
            dbDelta($sql5);

            $this->log('Enhanced database tables created successfully for v2.9', 'info');

        } catch (Exception $e) {
            $this->log_error('Database table creation failed', $e, 'critical');
            throw $e;
        }
    }

    // v2.7: Advanced helper methods
    private function register_custom_post_types() {
        // Register custom post type for product templates
        register_post_type('yadore_template', array(
            'labels' => array(
                'name' => __('Product Templates', 'yadore-monetizer'),
                'singular_name' => __('Product Template', 'yadore-monetizer')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'yadore-monetizer',
            'supports' => array('title', 'editor', 'custom-fields')
        ));
    }

    private function setup_cron_jobs() {
        if (!wp_next_scheduled('yadore_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'yadore_daily_maintenance');
        }

        if (!wp_next_scheduled('yadore_weekly_reports')) {
            wp_schedule_event(time(), 'weekly', 'yadore_weekly_reports');
        }
    }

    private function setup_advanced_admin_features() {
        // Add contextual help
        add_action('load-toplevel_page_yadore-monetizer', array($this, 'add_contextual_help'));

        // Add screen options
        add_action('load-toplevel_page_yadore-monetizer', array($this, 'add_screen_options'));
    }

    // Additional methods will continue...

    private function resolve_product_keyword($post_id, $page_content = '') {
        global $wpdb;

        if ($post_id > 0) {
            $posts_table = $wpdb->prefix . 'yadore_post_keywords';
            $post_data = $wpdb->get_row($wpdb->prepare(
                "SELECT primary_keyword, fallback_keyword FROM $posts_table WHERE post_id = %d AND product_validated = 1",
                $post_id
            ));

            if ($post_data) {
                $stored_candidates = array();

                if (!empty($post_data->primary_keyword)) {
                    $stored_candidates[] = $post_data->primary_keyword;
                }

                if (!empty($post_data->fallback_keyword)) {
                    $stored_candidates[] = $post_data->fallback_keyword;
                }

                if (!empty($stored_candidates)) {
                    return $this->finalize_resolved_keyword($stored_candidates, $post_id, $page_content);
                }
            }
        }

        $post_title = '';
        $post_content = '';

        if ($post_id > 0) {
            $post = get_post($post_id);
            if ($post && is_object($post)) {
                $post_title = isset($post->post_title) ? $post->post_title : '';
                $post_content = isset($post->post_content) ? $post->post_content : '';
            }
        }

        $combined_content = trim($post_title . "\n" . $post_content);
        if (!empty($page_content)) {
            $combined_content .= "\n" . $page_content;
        }
        $combined_content = trim($combined_content);

        if ($combined_content === '') {
            return '';
        }

        $fallback_keyword = $this->sanitize_single_keyword($this->normalize_keyword_case(wp_trim_words($post_title, 6, '')));
        $heuristic_keyword = $this->sanitize_single_keyword($this->extract_keyword_from_text($combined_content, $post_title));

        $candidate_keywords = array();
        $ai_candidates = array();

        if (get_option('yadore_ai_enabled', false)) {
            $ai_result = $this->call_gemini_api($post_title, $combined_content, true, $post_id);

            if (is_array($ai_result)) {
                if (isset($ai_result['keyword']) && trim((string) $ai_result['keyword']) !== '') {
                    $ai_candidates[] = $ai_result['keyword'];
                }

                if (!empty($ai_result['alternate_keywords']) && is_array($ai_result['alternate_keywords'])) {
                    $ai_candidates = array_merge($ai_candidates, $ai_result['alternate_keywords']);
                } elseif (!empty($ai_result['alternates']) && is_array($ai_result['alternates'])) {
                    $ai_candidates = array_merge($ai_candidates, $ai_result['alternates']);
                }
            } elseif (is_string($ai_result) && trim($ai_result) !== '') {
                // Backward compatibility for cached string responses
                $ai_candidates[] = $ai_result;
            }
        }

        if (!empty($ai_candidates)) {
            $candidate_keywords = array_merge($candidate_keywords, $ai_candidates);
        }

        if ($heuristic_keyword !== '') {
            $candidate_keywords[] = $heuristic_keyword;
        }

        if ($fallback_keyword !== '') {
            $candidate_keywords[] = $fallback_keyword;
        }

        if (!empty($candidate_keywords)) {
            return $this->finalize_resolved_keyword($candidate_keywords, $post_id, $page_content);
        }

        return '';
    }

    private function finalize_resolved_keyword(array $candidates, $post_id, $page_content = '') {
        $sanitized = $this->sanitize_keyword_list($candidates);
        if (empty($sanitized)) {
            return '';
        }

        $primary = $sanitized[0];
        $filtered_primary = apply_filters('yadore_resolved_keyword', $primary, $post_id, $page_content);
        $filtered_primary = $this->sanitize_single_keyword($filtered_primary);

        if ($filtered_primary === '') {
            array_shift($sanitized);
            if (empty($sanitized)) {
                return '';
            }
            $filtered_primary = $sanitized[0];
        } else {
            $sanitized[0] = $filtered_primary;
        }

        $this->remember_keyword_candidates($post_id, $sanitized);

        return $filtered_primary;
    }

    private function extract_keyword_from_text($text, $title = '') {
        $text = wp_strip_all_tags((string) $text);
        $title = wp_strip_all_tags((string) $title);

        $combined = trim($title . ' ' . $text);
        if ($combined === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $normalized = mb_strtolower($combined, 'UTF-8');
        } else {
            $normalized = strtolower($combined);
        }

        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $words = explode(' ', trim($normalized));

        $stop_words = array(
            'und', 'oder', 'aber', 'dass', 'nicht', 'sein', 'sind', 'sich', 'mit', 'eine', 'einer', 'eines', 'ein', 'der',
            'die', 'das', 'den', 'dem', 'des', 'auf', 'für', 'von', 'zum', 'zur', 'ist', 'im', 'am', 'the', 'and', 'for',
            'with', 'this', 'that', 'from', 'your', 'have', 'has', 'are', 'was', 'were', 'will', 'best', 'guide', 'review',
            'reviews', 'test', 'tests', '2024', '2025', '2026', '2023', 'latest', 'top', 'complete', 'ultimate', 'check',
            'update', 'news', 'new', 'edition', 'insights', 'tips', 'tricks', 'vergleich', 'kaufen', 'preis', 'erfahrungen',
            'bester', 'beste', 'bieten', 'unser', 'ihre', 'seine', 'meine', 'deine', 'falls', 'auch', 'noch', 'heute'
        );
        $stop_map = array_flip($stop_words);

        $word_counts = array();
        $bigram_counts = array();
        $previous_word = '';

        foreach ($words as $word) {
            $word = trim($word);

            if ($word === '' || is_numeric($word)) {
                $previous_word = '';
                continue;
            }

            $length = function_exists('mb_strlen') ? mb_strlen($word, 'UTF-8') : strlen($word);
            if ($length < 3 || isset($stop_map[$word])) {
                $previous_word = '';
                continue;
            }

            $word_counts[$word] = ($word_counts[$word] ?? 0) + 1;

            if ($previous_word !== '') {
                $bigram = $previous_word . ' ' . $word;
                $bigram_counts[$bigram] = ($bigram_counts[$bigram] ?? 0) + 1;
            }

            $previous_word = $word;
        }

        if (!empty($bigram_counts)) {
            arsort($bigram_counts);
            $best_bigram = array_key_first($bigram_counts);
            if (!empty($best_bigram)) {
                return $this->normalize_keyword_case($best_bigram);
            }
        }

        if (!empty($word_counts)) {
            arsort($word_counts);
            $best_word = array_key_first($word_counts);
            if (!empty($best_word)) {
                return $this->normalize_keyword_case($best_word);
            }
        }

        if ($title !== '') {
            return $this->normalize_keyword_case(wp_trim_words($title, 6, ''));
        }

        return '';
    }

    private function normalize_keyword_case($keyword) {
        $keyword = preg_replace('/\s+/', ' ', trim((string) $keyword));
        if ($keyword === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($keyword, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords($keyword);
    }

    private function sanitize_single_keyword($keyword) {
        $keyword = sanitize_text_field((string) $keyword);
        $keyword = preg_replace('/\s+/u', ' ', $keyword);
        return trim($keyword);
    }

    private function sanitize_keyword_list($keywords) {
        if (!is_array($keywords)) {
            $keywords = array($keywords);
        }

        $sanitized = array();
        $seen = array();

        foreach ($keywords as $keyword) {
            $clean = $this->sanitize_single_keyword($keyword);
            if ($clean === '') {
                continue;
            }

            $key = function_exists('mb_strtolower') ? mb_strtolower($clean, 'UTF-8') : strtolower($clean);
            if (isset($seen[$key])) {
                continue;
            }

            $sanitized[] = $clean;
            $seen[$key] = true;

            if (count($sanitized) >= 8) {
                break;
            }
        }

        return $sanitized;
    }

    private function get_products($keyword, $limit = 6, $post_id = 0, $use_cache = true) {
        $candidates = $this->compile_keyword_candidates($keyword, $post_id);

        if (empty($candidates)) {
            $this->set_last_product_keyword('');
            return array();
        }

        $selected_keyword = '';
        $products = $this->fetch_products_from_candidates($candidates, $limit, $post_id, $use_cache, $selected_keyword);

        if ($selected_keyword === '' && !empty($candidates)) {
            $selected_keyword = $candidates[0];
        }

        $this->set_last_product_keyword($selected_keyword);

        if (!empty($products)) {
            return $products;
        }

        return array();
    }

    private function compile_keyword_candidates($keyword, $post_id) {
        $raw_candidates = array();

        if (is_array($keyword)) {
            $raw_candidates = $keyword;
        } elseif ($keyword !== null && $keyword !== '') {
            $raw_candidates[] = $keyword;
        }

        $raw_candidates = $this->sanitize_keyword_list($raw_candidates);

        $remembered = $this->get_remembered_keyword_candidates($post_id);
        if (!empty($remembered)) {
            $raw_candidates = array_merge($raw_candidates, $remembered);
        }

        $filtered_candidates = apply_filters('yadore_product_keyword_candidates', $raw_candidates, $post_id, $keyword);

        $candidates = $this->sanitize_keyword_list($filtered_candidates);

        if (!empty($candidates)) {
            $this->remember_keyword_candidates($post_id, $candidates);
        }

        return $candidates;
    }

    private function fetch_products_from_candidates(array $candidates, $limit, $post_id, $use_cache, &$selected_keyword) {
        $selected_keyword = '';
        $attempt = 0;
        $debug_enabled = (bool) get_option('yadore_debug_mode', false);

        foreach ($candidates as $candidate) {
            $attempt++;
            $result = $this->fetch_products_for_single_keyword($candidate, $limit, $post_id, $use_cache);

            if ($result === false) {
                return array();
            }

            $resolved_keyword = $candidate;
            $products = array();

            if (is_array($result) && array_key_exists('products', $result)) {
                $products = is_array($result['products']) ? $result['products'] : array();
                if (!empty($result['keyword'])) {
                    $resolved_keyword = $result['keyword'];
                }
            } elseif (is_array($result)) {
                $products = $result;
            }

            if ($selected_keyword === '') {
                $selected_keyword = $resolved_keyword;
            }

            if (!empty($products)) {
                if ($debug_enabled) {
                    $candidate_clean = $this->sanitize_single_keyword($candidate);
                    $resolved_clean = $this->sanitize_single_keyword($resolved_keyword);

                    if ($attempt > 1 || $candidate_clean !== $resolved_clean) {
                        $this->log(sprintf(
                            'Alternate keyword "%s" used after %d attempt(s) for post %d.',
                            $resolved_keyword,
                            $attempt,
                            (int) $post_id
                        ), 'debug');
                    }
                }

                $selected_keyword = $resolved_keyword;
                return $products;
            }
        }

        if ($debug_enabled && !empty($candidates)) {
            $this->log(sprintf(
                'No offers returned for keywords [%s] (post %d).',
                implode(', ', $candidates),
                (int) $post_id
            ), 'debug');
        }

        return array();
    }

    private function fetch_products_for_single_keyword($keyword, $limit, $post_id, $use_cache) {
        $keyword = $this->sanitize_single_keyword($keyword);
        if ($keyword === '') {
            return array('products' => array(), 'keyword' => '');
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 6;
        }
        $limit = min(50, max(1, $limit));

        $use_cache = (bool) $use_cache;

        $queue = array(
            array('keyword' => $keyword, 'precision' => null),
        );

        $attempted = array();
        $variation_added = array();
        $results_template = array('products' => array(), 'keyword' => $keyword);

        while (!empty($queue)) {
            $attempt = array_shift($queue);
            $attempt_keyword = $this->sanitize_single_keyword($attempt['keyword']);
            $precision_override = $attempt['precision'];

            if ($attempt_keyword === '') {
                continue;
            }

            $attempt_hash_input = function_exists('mb_strtolower')
                ? mb_strtolower($attempt_keyword, 'UTF-8')
                : strtolower($attempt_keyword);
            $attempt_hash = md5($attempt_hash_input . '|' . ($precision_override === null ? 'auto' : $precision_override));
            if (isset($attempted[$attempt_hash])) {
                continue;
            }
            $attempted[$attempt_hash] = true;

            $query_result = $this->query_yadore_products($attempt_keyword, $limit, $post_id, $use_cache, $precision_override);
            if ($query_result === false) {
                return false;
            }

            $resolved_keyword = isset($query_result['keyword']) ? $this->sanitize_single_keyword($query_result['keyword']) : $attempt_keyword;
            $used_precision = isset($query_result['precision']) ? $query_result['precision'] : ($precision_override !== null ? $precision_override : 'fuzzy');
            $products = isset($query_result['products']) && is_array($query_result['products'])
                ? $query_result['products']
                : array();

            if (!empty($products)) {
                return array(
                    'products' => $products,
                    'keyword' => $resolved_keyword,
                );
            }

            if ($precision_override === null && $used_precision === 'strict') {
                $queue[] = array(
                    'keyword' => $resolved_keyword,
                    'precision' => 'fuzzy',
                );
            }

            if ($resolved_keyword === $keyword && !isset($variation_added[$resolved_keyword])) {
                $variations = $this->generate_keyword_variations($resolved_keyword);
                if (!empty($variations)) {
                    foreach ($variations as $variant) {
                        $queue[] = array(
                            'keyword' => $variant,
                            'precision' => null,
                        );
                    }
                }
                $variation_added[$resolved_keyword] = true;
            }
        }

        return $results_template;
    }

    private function query_yadore_products($keyword, $limit, $post_id, $use_cache, $precision_override = null) {
        $keyword = $this->sanitize_single_keyword($keyword);
        if ($keyword === '') {
            return array('products' => array(), 'keyword' => '', 'precision' => '');
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 6;
        }
        $limit = min(100, max(1, $limit));

        $market = $this->sanitize_market(get_option('yadore_market', ''));
        if ($market === '') {
            $market = $this->get_default_market();
        }

        $filtered_market = apply_filters('yadore_products_country', $market, $keyword, $limit, $post_id);
        if (is_string($filtered_market) && $filtered_market !== '') {
            $candidate_market = $this->sanitize_market($filtered_market);
            if ($candidate_market !== '') {
                $market = $candidate_market;
            }
        }

        $filtered_market = apply_filters('yadore_products_market', $market, $keyword, $limit, $post_id);
        if (is_string($filtered_market) && $filtered_market !== '') {
            $candidate_market = $this->sanitize_market($filtered_market);
            if ($candidate_market !== '') {
                $market = $candidate_market;
            }
        }

        if ($market === '') {
            $market = $this->get_default_market();
        }

        $precision = apply_filters('yadore_products_precision', 'fuzzy', $keyword, $post_id);
        if ($precision_override !== null) {
            $precision = $precision_override;
        }
        if (!in_array($precision, array('strict', 'fuzzy'), true)) {
            $precision = 'fuzzy';
        }

        $sort = apply_filters('yadore_products_sort', 'rel_desc', $keyword, $post_id);
        if (!in_array($sort, array('rel_desc', 'price_asc', 'price_desc'), true)) {
            $sort = 'rel_desc';
        }

        $is_couponing = apply_filters('yadore_products_is_couponing', false, $keyword, $post_id);
        $is_couponing = $is_couponing ? 'true' : 'false';

        $cache_components = array(
            function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword),
            $limit,
            strtolower($market),
            $precision,
            $sort,
            $is_couponing,
        );
        $cache_key = 'yadore_products_' . md5(implode('|', $cache_components));

        if ($use_cache && isset($this->api_cache[$cache_key])) {
            $memory_cached = $this->api_cache[$cache_key];

            if (is_array($memory_cached) && !empty($memory_cached)) {
                return array(
                    'products' => $memory_cached,
                    'keyword' => $keyword,
                    'precision' => $precision,
                );
            }

            unset($this->api_cache[$cache_key]);
        }

        if ($use_cache && !get_option('yadore_debug_mode', false)) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                if (is_array($cached) && !empty($cached)) {
                    $this->api_cache[$cache_key] = $cached;
                    return array(
                        'products' => $cached,
                        'keyword' => $keyword,
                        'precision' => $precision,
                    );
                }

                delete_transient($cache_key);
            }
        }

        $api_key = trim((string) get_option('yadore_api_key'));
        if ($api_key === '') {
            $this->log_error('Yadore API key not configured for product request', null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return false;
        }

        $endpoint = 'https://api.yadore.com/v2/offer';

        $request_params = array(
            'keyword' => $keyword,
            'limit' => $limit,
            'market' => $market,
            'precision' => $precision,
            'sort' => $sort,
            'isCouponing' => $is_couponing,
        );

        $request_params = apply_filters('yadore_products_request_body', $request_params, $keyword, $limit, $post_id);
        $request_params = $this->normalize_yadore_offer_params($request_params, $keyword, $limit, $market, $precision, $sort, $is_couponing);

        if (empty($request_params['keyword'])) {
            return array('products' => array(), 'keyword' => $keyword, 'precision' => $precision);
        }

        $query_string = http_build_query($request_params, '', '&', PHP_QUERY_RFC3986);
        $request_url = $endpoint;
        if ($query_string !== '') {
            $request_url .= (strpos($endpoint, '?') === false ? '?' : '&') . $query_string;
        }

        $keyword_header = isset($request_params['keyword']) ? (string) $request_params['keyword'] : '';
        $limit_header = isset($request_params['limit']) ? (string) $request_params['limit'] : '';

        $required_headers = array(
            'Accept' => 'application/json',
            'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            'API-Key' => $api_key,
        );

        if ($keyword_header !== '') {
            $required_headers['Keyword'] = $keyword_header;
        }

        if ($limit_header !== '') {
            $required_headers['Limit'] = $limit_header;
        }

        $args = array(
            'headers' => $required_headers,
            'timeout' => 20,
        );

        $args = apply_filters('yadore_products_request_args', $args, $keyword, $limit, $post_id);

        if (!isset($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = array();
        }

        $args['headers'] = array_merge($args['headers'], $required_headers);

        $start_time = microtime(true);
        $response = wp_remote_get($request_url, $args);
        $duration_ms = (int) round((microtime(true) - $start_time) * 1000);

        if (is_wp_error($response)) {
            $this->log_api_call('yadore', $endpoint, 'error', array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'message' => $response->get_error_message(),
                'duration_ms' => $duration_ms,
                'precision' => $precision,
            ));

            $this->log_error('Yadore API request failed: ' . $response->get_error_message(), null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return false;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $body = is_string($body) ? $body : '';
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            $decompressed_body = $this->maybe_decompress_yadore_body($response, $body);
            if ($decompressed_body !== $body) {
                $body = $decompressed_body;
                $decoded = json_decode($body, true);
            }
        }

        if ($status < 200 || $status >= 300) {
            $error_details = $this->extract_yadore_error_messages($decoded);
            $log_context = array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'status' => $status,
                'response' => is_array($decoded) ? $decoded : $body,
                'duration_ms' => $duration_ms,
                'precision' => $precision,
            );

            if ($error_details !== '') {
                $log_context['error_details'] = $error_details;
                $this->queue_admin_notice(sprintf(__('Yadore API error: %s', 'yadore-monetizer'), $error_details));
            }

            $this->log_api_call('yadore', $endpoint, 'error', $log_context);

            $error_message = sprintf('Yadore API returned an unexpected status: %s', $status);
            if ($error_details !== '') {
                $error_message .= ' - ' . $error_details;
            }

            $this->log_error($error_message, null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
                'response' => is_array($decoded) ? $decoded : $body,
                'url' => $request_url,
                'status' => $status,
            ));

            return false;
        }

        if (!is_array($decoded)) {
            $this->log_api_call('yadore', $endpoint, 'error', array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'status' => $status,
                'response' => $body,
                'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : '',
                'duration_ms' => $duration_ms,
                'precision' => $precision,
            ));

            $this->log_error('Yadore API returned an invalid response format.', null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
                'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : '',
            ));

            return false;
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $message = isset($decoded['message']) ? $decoded['message'] : __('Unknown API error', 'yadore-monetizer');

            $this->log_api_call('yadore', $endpoint, 'error', array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'response' => $decoded,
                'duration_ms' => $duration_ms,
                'precision' => $precision,
            ));

            $this->log_error('Yadore API error: ' . $message, null, 'medium', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return false;
        }

        $products = array();

        $possible_collections = array('data', 'products', 'offers', 'items');

        foreach ($possible_collections as $collection_key) {
            if (isset($decoded[$collection_key]) && is_array($decoded[$collection_key])) {
                $products = $decoded[$collection_key];
                break;
            }
        }

        if (empty($products) && isset($decoded['data']) && is_array($decoded['data'])) {
            foreach (array('offers', 'items', 'products') as $nested_key) {
                if (isset($decoded['data'][$nested_key]) && is_array($decoded['data'][$nested_key])) {
                    $products = $decoded['data'][$nested_key];
                    break;
                }
            }
        }

        if (!is_array($products)) {
            $products = array();
        }

        if (!empty($products)) {
            $products = array_map(array($this, 'sanitize_product_payload'), array_slice($products, 0, $limit));
        }

        $log_payload = array(
            'keyword' => $keyword,
            'limit' => $limit,
            'url' => $request_url,
            'count' => count($products),
            'duration_ms' => $duration_ms,
            'request' => $request_params,
            'precision' => $precision,
            'market' => strtolower($market),
        );

        if (empty($products)) {
            $trace_context = array(
                'keyword' => $keyword,
                'post_id' => $post_id,
                'request_url' => $request_url,
                'request_params' => $request_params,
                'response' => $decoded,
                'raw_response' => $body,
                'status' => $status,
                'duration_ms' => $duration_ms,
                'precision' => $precision,
                'market' => strtolower($market),
                'limit' => $limit,
            );

            $log_payload['response'] = $decoded;
            $log_payload['raw_response'] = $body;
            $log_payload['trace'] = true;

            $this->log_error(
                sprintf('Yadore API returned no products for keyword "%s"', $keyword),
                null,
                'low',
                $trace_context
            );

            $this->log(
                'Yadore API empty response trace: ' . (
                    function_exists('wp_json_encode')
                        ? wp_json_encode($trace_context)
                        : json_encode($trace_context)
                ),
                'warning'
            );
        }

        $this->log_api_call('yadore', $endpoint, 'success', $log_payload);

        $cache_duration = intval(get_option('yadore_cache_duration', 3600));
        if (!empty($products)) {
            if ($use_cache && $cache_duration > 0) {
                set_transient($cache_key, $products, $cache_duration);
            }

            $this->api_cache[$cache_key] = $products;
        } else {
            if ($use_cache) {
                delete_transient($cache_key);
            }

            if (isset($this->api_cache[$cache_key])) {
                unset($this->api_cache[$cache_key]);
            }
        }

        return array(
            'products' => $products,
            'keyword' => $keyword,
            'precision' => $precision,
        );
    }

    private function generate_keyword_variations($keyword) {
        $keyword = $this->sanitize_single_keyword($keyword);
        if ($keyword === '') {
            return array();
        }

        $parts = preg_split('/\s+/u', $keyword);
        if (!is_array($parts) || count($parts) <= 1) {
            return array();
        }

        $stop_words = array(
            'test', 'tests', 'review', 'reviews', 'guide', 'vergleich', 'preis', 'preise', 'kaufen', 'angebote', 'angebot',
            'check', 'update', 'erfahrungen', 'erfahrung', 'tipps', 'tricks', '2024', '2025', '2026', '2023', '2022', '2021',
            '2020', 'beste', 'bester', 'besten', 'top', 'neu', 'neue', 'neues', 'ratgeber', 'kaufberatung', 'handbuch',
        );

        $filtered_parts = array();

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $normalized = function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part);
            $normalized = preg_replace('/[^\p{L}\p{N}\-]/u', '', $normalized);
            if ($normalized === '' || in_array($normalized, $stop_words, true)) {
                continue;
            }

            $length = function_exists('mb_strlen') ? mb_strlen($normalized, 'UTF-8') : strlen($normalized);
            if ($length < 3) {
                continue;
            }

            $filtered_parts[] = $part;
        }

        if (empty($filtered_parts)) {
            return array();
        }

        $variations = array();
        $seen = array();
        $original_key = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);

        if (count($filtered_parts) !== count($parts)) {
            $this->append_keyword_variant($variations, $seen, implode(' ', $filtered_parts), $original_key);
        }

        if (count($filtered_parts) >= 2) {
            $this->append_keyword_variant($variations, $seen, implode(' ', array_slice($filtered_parts, -2)), $original_key);
            $this->append_keyword_variant($variations, $seen, implode(' ', array_slice($filtered_parts, 0, 2)), $original_key);
        }

        $last_part = end($filtered_parts);
        if ($last_part !== false) {
            $this->append_keyword_variant($variations, $seen, $last_part, $original_key);
        }
        reset($filtered_parts);
        $first_part = current($filtered_parts);
        if ($first_part !== false) {
            $this->append_keyword_variant($variations, $seen, $first_part, $original_key);
        }

        $count = 0;
        foreach ($filtered_parts as $part) {
            if ($count >= 2) {
                break;
            }
            $this->append_keyword_variant($variations, $seen, $part, $original_key);
            $count++;
        }

        if (count($variations) > 5) {
            $variations = array_slice($variations, 0, 5);
        }

        return $variations;
    }

    private function append_keyword_variant(array &$collection, array &$seen, $candidate, $original_key) {
        $candidate = $this->sanitize_single_keyword($candidate);
        if ($candidate === '') {
            return;
        }

        $key = function_exists('mb_strtolower') ? mb_strtolower($candidate, 'UTF-8') : strtolower($candidate);
        if ($key === '' || $key === $original_key || isset($seen[$key])) {
            return;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($candidate, 'UTF-8') : strlen($candidate);
        if ($length < 3) {
            return;
        }

        $seen[$key] = true;
        $collection[] = $candidate;
    }

    private function remember_keyword_candidates($post_id, array $keywords) {
        $keywords = $this->sanitize_keyword_list($keywords);
        if (empty($keywords)) {
            return;
        }

        $key = $post_id > 0 ? (int) $post_id : 0;
        $this->keyword_candidate_cache[$key] = array(
            'keywords' => $keywords,
            'timestamp' => time(),
        );

        if (count($this->keyword_candidate_cache) > 50) {
            $this->keyword_candidate_cache = array_slice($this->keyword_candidate_cache, -50, null, true);
        }
    }

    private function get_remembered_keyword_candidates($post_id) {
        $key = $post_id > 0 ? (int) $post_id : 0;

        if (isset($this->keyword_candidate_cache[$key]['keywords'])) {
            return $this->keyword_candidate_cache[$key]['keywords'];
        }

        return array();
    }

    private function set_last_product_keyword($keyword) {
        $this->last_product_keyword = $this->sanitize_single_keyword($keyword);
    }

    private function get_last_product_keyword() {
        return $this->last_product_keyword;
    }

    private function sanitize_product_payload($product) {
        if (!is_array($product)) {
            return array();
        }

        $sanitized = $product;

        $sanitized['id'] = isset($product['id']) ? sanitize_text_field((string) $product['id']) : '';
        if (empty($sanitized['id']) && isset($product['offerId'])) {
            $sanitized['id'] = sanitize_text_field((string) $product['offerId']);
        }

        $sanitized['title'] = isset($product['title']) ? sanitize_text_field((string) $product['title']) : '';
        if ($sanitized['title'] === '' && isset($product['name'])) {
            $sanitized['title'] = sanitize_text_field((string) $product['name']);
        }
        if ($sanitized['title'] === '' && isset($product['productName'])) {
            $sanitized['title'] = sanitize_text_field((string) $product['productName']);
        }

        if (isset($product['price']) && is_array($product['price'])) {
            $sanitized['price']['amount'] = isset($product['price']['amount'])
                ? sanitize_text_field((string) $product['price']['amount'])
                : '';
            $sanitized['price']['currency'] = isset($product['price']['currency'])
                ? sanitize_text_field((string) $product['price']['currency'])
                : '';
            if ($sanitized['price']['amount'] === '' && isset($product['price']['value'])) {
                $sanitized['price']['amount'] = sanitize_text_field((string) $product['price']['value']);
            }
            if ($sanitized['price']['currency'] === '' && isset($product['price']['currencyCode'])) {
                $sanitized['price']['currency'] = sanitize_text_field((string) $product['price']['currencyCode']);
            }
        } else {
            $sanitized['price'] = array(
                'amount' => isset($product['price']) ? sanitize_text_field((string) $product['price']) : '',
                'currency' => '',
            );
            if ($sanitized['price']['currency'] === '' && isset($product['currency'])) {
                $sanitized['price']['currency'] = sanitize_text_field((string) $product['currency']);
            }
        }

        if (isset($product['merchant']) && is_array($product['merchant'])) {
            $sanitized['merchant']['name'] = isset($product['merchant']['name'])
                ? sanitize_text_field((string) $product['merchant']['name'])
                : '';

            if (isset($product['merchant']['logo'])) {
                $sanitized['merchant']['logo'] = esc_url_raw($product['merchant']['logo']);
            }
            if (isset($product['merchant']['logoUrl'])) {
                $sanitized['merchant']['logo'] = esc_url_raw($product['merchant']['logoUrl']);
            }
        } elseif (!empty($product['merchant'])) {
            $sanitized['merchant'] = array(
                'name' => sanitize_text_field((string) $product['merchant']),
            );
        } else {
            $sanitized['merchant'] = array();
        }

        if (empty($sanitized['merchant']['name']) && isset($product['merchantName'])) {
            $sanitized['merchant']['name'] = sanitize_text_field((string) $product['merchantName']);
        }

        if (isset($product['thumbnail']) && is_array($product['thumbnail'])) {
            $sanitized['thumbnail']['url'] = isset($product['thumbnail']['url'])
                ? esc_url_raw($product['thumbnail']['url'])
                : '';
        }

        if (isset($product['image']) && is_array($product['image'])) {
            $sanitized['image']['url'] = isset($product['image']['url'])
                ? esc_url_raw($product['image']['url'])
                : '';
        } elseif (isset($product['image']) && !is_array($product['image'])) {
            $sanitized['image'] = array('url' => esc_url_raw($product['image']));
        } elseif (isset($product['images']) && is_array($product['images']) && !empty($product['images'][0]['url'])) {
            $sanitized['image'] = array('url' => esc_url_raw($product['images'][0]['url']));
        } elseif (isset($product['imageUrl'])) {
            $sanitized['image'] = array('url' => esc_url_raw($product['imageUrl']));
        } elseif (isset($product['imageUrls']) && is_array($product['imageUrls']) && !empty($product['imageUrls'][0])) {
            $sanitized['image'] = array('url' => esc_url_raw($product['imageUrls'][0]));
        }

        if (isset($product['clickUrl'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['clickUrl']);
        } elseif (isset($product['url'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['url']);
        } elseif (isset($product['deeplink'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['deeplink']);
        } elseif (isset($product['deepLink'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['deepLink']);
        } elseif (isset($product['trackingUrl'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['trackingUrl']);
        } elseif (isset($product['redirectUrl'])) {
            $sanitized['clickUrl'] = esc_url_raw($product['redirectUrl']);
        }

        if (isset($product['description'])) {
            $sanitized['description'] = sanitize_textarea_field((string) $product['description']);
        }

        if (empty($sanitized['description']) && isset($product['summary'])) {
            $sanitized['description'] = sanitize_textarea_field((string) $product['summary']);
        }

        if (isset($product['promoText'])) {
            $sanitized['promoText'] = sanitize_text_field((string) $product['promoText']);
        }

        if (empty($sanitized['thumbnail']['url']) && !empty($sanitized['image']['url'])) {
            $sanitized['thumbnail']['url'] = $sanitized['image']['url'];
        }

        return apply_filters('yadore_sanitized_product', $sanitized, $product);
    }

    private function normalize_yadore_offer_params($params, $keyword, $limit, $market, $precision, $sort, $is_couponing) {
        if (!is_array($params)) {
            $params = array();
        }

        $normalized = array();

        $normalized['keyword'] = $this->sanitize_single_keyword(isset($params['keyword']) ? $params['keyword'] : $keyword);

        $limit_value = isset($params['limit']) ? (int) $params['limit'] : (int) $limit;
        if ($limit_value <= 0) {
            $limit_value = (int) $limit;
        }
        $normalized['limit'] = min(100, max(1, $limit_value));

        $market_value = isset($params['market']) ? $this->sanitize_market($params['market']) : $market;
        if ($market_value === '') {
            $market_value = $this->get_default_market();
        }
        $normalized['market'] = strtolower($market_value);

        $precision_value = isset($params['precision']) ? strtolower((string) $params['precision']) : $precision;
        if (!in_array($precision_value, array('strict', 'fuzzy'), true)) {
            $precision_value = 'fuzzy';
        }
        $normalized['precision'] = $precision_value;

        $sort_value = isset($params['sort']) ? strtolower((string) $params['sort']) : $sort;
        if (!in_array($sort_value, array('rel_desc', 'price_asc', 'price_desc'), true)) {
            $sort_value = 'rel_desc';
        }
        $normalized['sort'] = $sort_value;

        $coupon_flag = ($is_couponing === 'true');
        if (isset($params['isCouponing'])) {
            $coupon_flag = $this->interpret_boolean_flag($params['isCouponing']);
        }
        $normalized['isCouponing'] = $coupon_flag ? 'true' : 'false';

        if (!empty($params['ean'])) {
            $ean = preg_replace('/[^0-9]/', '', (string) $params['ean']);
            if (strlen($ean) === 8 || strlen($ean) === 13) {
                $normalized['ean'] = $ean;
            }
        }

        if (!empty($params['merchantId'])) {
            $normalized['merchantId'] = sanitize_text_field((string) $params['merchantId']);
        }

        if (!empty($params['offerId'])) {
            $normalized['offerId'] = sanitize_text_field((string) $params['offerId']);
        }

        if (!empty($params['placementId'])) {
            $placement = sanitize_text_field((string) $params['placementId']);
            if (strlen($placement) > 128) {
                $placement = substr($placement, 0, 128);
            }
            $normalized['placementId'] = $placement;
        }

        foreach ($params as $key => $value) {
            if (isset($normalized[$key])) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_string($key) && preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                $normalized[$key] = sanitize_text_field((string) $value);
            }
        }

        return array_filter($normalized, static function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function maybe_decompress_yadore_body($response, $body) {
        if (!is_string($body) || $body === '') {
            return $body;
        }

        $encoding = '';

        if (function_exists('wp_remote_retrieve_header')) {
            $encoding = wp_remote_retrieve_header($response, 'content-encoding');
        } elseif (is_array($response)) {
            if (isset($response['headers']['content-encoding'])) {
                $encoding = $response['headers']['content-encoding'];
            } elseif (isset($response['headers']) && is_array($response['headers'])) {
                $headers = $response['headers'];
                foreach ($headers as $header_key => $header_value) {
                    if (strtolower((string) $header_key) === 'content-encoding') {
                        $encoding = $header_value;
                        break;
                    }
                }
            }
        }

        if (is_array($encoding)) {
            $encoding = implode(',', $encoding);
        }

        $encoding = strtolower(trim((string) $encoding));

        if ($encoding === '') {
            return $body;
        }

        $attempts = array();

        if (strpos($encoding, 'gzip') !== false && function_exists('gzdecode')) {
            $attempts[] = 'gzdecode';
        }

        if (strpos($encoding, 'deflate') !== false) {
            if (function_exists('gzuncompress')) {
                $attempts[] = 'gzuncompress';
            }
            if (function_exists('gzinflate')) {
                $attempts[] = 'gzinflate';
            }
        }

        foreach ($attempts as $function) {
            $decoded = @$function($body);
            if ($decoded !== false && $decoded !== '' && is_string($decoded)) {
                return $decoded;
            }
        }

        return $body;
    }

    private function call_gemini_api($title, $content, $use_cache = true, $post_id = 0) {
        try {
            $api_key = trim((string) get_option('yadore_gemini_api_key'));
            if (empty($api_key)) {
                return array('error' => __('Gemini API key not configured', 'yadore-monetizer'));
            }

            $model = $this->sanitize_model(get_option('yadore_gemini_model', $this->get_default_gemini_model()));

            $prompt_template = (string) get_option(
                'yadore_ai_prompt',
                'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.'
            );
            if (trim($prompt_template) === '') {
                $prompt_template = 'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.';
            }

            $prompt = str_replace(
                array('{title}', '{content}'),
                array($title, $content),
                $prompt_template
            );
            $prompt = trim($prompt);

            $cache_key = 'yadore_ai_' . md5($model . '|' . $prompt);
            if ($use_cache) {
                $cached = get_transient($cache_key);
                if ($cached !== false) {
                    return $cached;
                }
            }

            $temperature = floatval(get_option('yadore_ai_temperature', '0.3'));
            $temperature = max(0, min(2, $temperature));
            $max_tokens = intval(get_option('yadore_ai_max_tokens', 50));
            if ($max_tokens <= 0) {
                $max_tokens = 50;
            }
            if ($max_tokens > 10000) {
                $max_tokens = 10000;
            }

            $endpoint_base = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                rawurlencode($model)
            );
            $request_url = add_query_arg(array('key' => $api_key), $endpoint_base);

            $request_body = array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => $prompt,
                            ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'temperature' => $temperature,
                    'maxOutputTokens' => $max_tokens,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => array(
                        'type' => 'OBJECT',
                        'properties' => array(
                            'keyword' => array(
                                'type' => 'STRING',
                                'description' => 'Primary product keyword describing the best affiliate opportunity.',
                            ),
                            'alternate_keywords' => array(
                                'type' => 'ARRAY',
                                'description' => 'Up to three alternate keyword candidates for backup product searches.',
                                'items' => array(
                                    'type' => 'STRING',
                                ),
                                'minItems' => 0,
                                'maxItems' => 3,
                            ),
                            'confidence' => array(
                                'type' => 'NUMBER',
                                'minimum' => 0,
                                'maximum' => 1,
                                'description' => 'Confidence score between 0 and 1 for the extracted keyword.',
                            ),
                            'rationale' => array(
                                'type' => 'STRING',
                                'description' => 'Optional short explanation for the keyword choice.',
                            ),
                        ),
                        'required' => array('keyword'),
                        'propertyOrdering' => array('keyword', 'alternate_keywords', 'confidence', 'rationale'),
                    ),
                ),
            );

            $request_body = apply_filters('yadore_gemini_request_body', $request_body, $title, $content, $model);

            $response = wp_remote_post($request_url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($request_body),
                'timeout' => 20,
            ));

            if (is_wp_error($response)) {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'model' => $model,
                    'message' => $response->get_error_message(),
                ));
                return array('error' => sprintf(__('Gemini API request failed: %s', 'yadore-monetizer'), $response->get_error_message()));
            }

            $status = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);

            if ($status < 200 || $status >= 300) {
                $message = __('Unexpected response from Gemini API.', 'yadore-monetizer');
                if (is_array($decoded) && isset($decoded['error']['message'])) {
                    $message = $decoded['error']['message'];
                }

                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $decoded,
                ));

                return array('error' => $message);
            }

            if (!is_array($decoded)) {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $body,
                ));
                return array('error' => __('Gemini API returned an invalid response.', 'yadore-monetizer'));
            }

            if (isset($decoded['promptFeedback']['blockReason'])) {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $decoded,
                ));

                return array('error' => sprintf(__('Gemini API blocked the request: %s', 'yadore-monetizer'), $decoded['promptFeedback']['blockReason']));
            }

            $raw_structured_text = '';
            $structured_data = $this->extract_gemini_structured_payload($decoded, $raw_structured_text);

            if (!is_array($structured_data)) {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $decoded,
                    'raw' => is_string($raw_structured_text) ? substr($raw_structured_text, 0, 500) : $raw_structured_text,
                    'parse_error' => 'Unable to extract structured keyword data',
                ));
                return array('error' => __('Gemini API returned data that could not be parsed as JSON.', 'yadore-monetizer'));
            }

            $lower_keys = array_change_key_case($structured_data, CASE_LOWER);

            if (!isset($structured_data['keyword']) && isset($lower_keys['keyword'])) {
                $structured_data['keyword'] = $lower_keys['keyword'];
            }

            if (!isset($structured_data['keyword']) || $structured_data['keyword'] === '') {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $structured_data,
                ));
                return array('error' => __('Gemini API returned data that did not match the expected schema.', 'yadore-monetizer'));
            }

            $keyword = sanitize_text_field((string) $structured_data['keyword']);
            if ($keyword === '') {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $structured_data,
                ));
                return array('error' => __('Gemini API did not return a usable keyword.', 'yadore-monetizer'));
            }

            $result = array('keyword' => $keyword);

            $alternate_source = null;
            $alternate_keys = array('alternate_keywords', 'alternatekeywords', 'alternates', 'alternate');

            foreach ($alternate_keys as $alt_key) {
                if (isset($structured_data[$alt_key])) {
                    $alternate_source = $structured_data[$alt_key];
                    break;
                }

                if (isset($lower_keys[$alt_key])) {
                    $alternate_source = $lower_keys[$alt_key];
                    break;
                }
            }

            if ($alternate_source !== null) {
                $alternate_candidates = array();

                if (is_string($alternate_source)) {
                    $alternate_candidates = preg_split('/[,;\r\n]+/', $alternate_source);
                    if ($alternate_candidates === false) {
                        $alternate_candidates = array($alternate_source);
                    }
                } elseif (is_array($alternate_source)) {
                    foreach ($alternate_source as $entry) {
                        if (is_string($entry) || is_numeric($entry)) {
                            $alternate_candidates[] = $entry;
                        } elseif (is_array($entry)) {
                            if (isset($entry['keyword'])) {
                                $alternate_candidates[] = $entry['keyword'];
                            } elseif (isset($entry['value'])) {
                                $alternate_candidates[] = $entry['value'];
                            }
                        }
                    }

                    if (empty($alternate_candidates)) {
                        $alternate_candidates = $alternate_source;
                    }
                } else {
                    $alternate_candidates = array($alternate_source);
                }

                if (!empty($alternate_candidates)) {
                    $alternate_keywords = $this->sanitize_keyword_list($alternate_candidates);

                    if (!empty($alternate_keywords)) {
                        $primary_lower = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);
                        $filtered_alternates = array();

                        foreach ($alternate_keywords as $alternate_keyword) {
                            $alt_lower = function_exists('mb_strtolower') ? mb_strtolower($alternate_keyword, 'UTF-8') : strtolower($alternate_keyword);
                            if ($alt_lower === $primary_lower) {
                                continue;
                            }

                            $filtered_alternates[] = $alternate_keyword;

                            if (count($filtered_alternates) >= 3) {
                                break;
                            }
                        }

                        if (!empty($filtered_alternates)) {
                            $result['alternate_keywords'] = $filtered_alternates;
                            $result['alternates'] = $filtered_alternates;
                        }
                    }
                }
            }

            if (isset($structured_data['confidence'])) {
                $confidence = floatval($structured_data['confidence']);
                $result['confidence'] = max(0, min(1, $confidence));
            }

            if (!empty($structured_data['rationale'])) {
                $result['rationale'] = sanitize_textarea_field((string) $structured_data['rationale']);
            }

            if ($use_cache) {
                $cache_duration = intval(get_option('yadore_ai_cache_duration', 157680000));
                if ($cache_duration > 0) {
                    set_transient($cache_key, $result, $cache_duration);
                }
            }

            $this->log_api_call('gemini', $endpoint_base, 'success', array(
                'model' => $model,
                'post_id' => $post_id,
                'result' => $result,
            ));

            return $result;
        } catch (Exception $e) {
            $this->log_error('Gemini API request failed', $e);
            return array('error' => $e->getMessage());
        }
    }

    private function extract_gemini_structured_payload($response, &$raw_text = '') {
        $raw_text = '';

        if (!is_array($response)) {
            return null;
        }

        if (!empty($response['candidates']) && is_array($response['candidates'])) {
            foreach ($response['candidates'] as $candidate) {
                if (!empty($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        if (isset($part['functionCall']['args']) && is_array($part['functionCall']['args'])) {
                            $raw_text = wp_json_encode($part['functionCall']['args']);
                            return $part['functionCall']['args'];
                        }

                        if (isset($part['structValue']) && is_array($part['structValue'])) {
                            $raw_text = wp_json_encode($part['structValue']);
                            return $part['structValue'];
                        }

                        if (isset($part['text'])) {
                            $raw_text = (string) $part['text'];
                            $parsed = $this->decode_gemini_json_string($raw_text);
                            if (is_array($parsed)) {
                                return $parsed;
                            }
                        }
                    }
                }

                if (isset($candidate['content']) && is_string($candidate['content'])) {
                    $raw_text = (string) $candidate['content'];
                    $parsed = $this->decode_gemini_json_string($raw_text);
                    if (is_array($parsed)) {
                        return $parsed;
                    }
                }
            }
        }

        if (isset($response['result']) && is_array($response['result'])) {
            $raw_text = wp_json_encode($response['result']);
            return $response['result'];
        }

        if (isset($response['text']) && is_string($response['text'])) {
            $raw_text = (string) $response['text'];
            $parsed = $this->decode_gemini_json_string($raw_text);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    private function decode_gemini_json_string($text) {
        if (!is_string($text)) {
            return null;
        }

        $normalized = trim($text);
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^```[a-zA-Z0-9_-]*\s*(.+?)\s*```$/s', $normalized, $matches)) {
            $normalized = trim($matches[1]);
        }

        $decoded = json_decode($normalized, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $candidates = array();

        $object_start = strpos($normalized, '{');
        $object_end = strrpos($normalized, '}');
        if ($object_start !== false && $object_end !== false && $object_end > $object_start) {
            $candidates[] = substr($normalized, $object_start, $object_end - $object_start + 1);
        }

        $array_start = strpos($normalized, '[');
        $array_end = strrpos($normalized, ']');
        if ($array_start !== false && $array_end !== false && $array_end > $array_start) {
            $candidates[] = substr($normalized, $array_start, $array_end - $array_start + 1);
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (strpos($normalized, '{') === false && strpos($normalized, '[') === false) {
            return array('keyword' => $normalized);
        }

        return null;
    }

    // Scanner helper methods
    private function get_scanner_overview_stats() {
        $total_posts = $this->count_scannable_posts();
        $scanned_posts = $this->get_scanned_post_count();
        $validated_keywords = $this->get_validated_keyword_count();

        return array(
            'total_posts' => $total_posts,
            'scanned_posts' => $scanned_posts,
            'pending_posts' => max(0, $total_posts - $scanned_posts),
            'validated_keywords' => $validated_keywords,
        );
    }

    private function get_scannable_post_types() {
        $types = array('post', 'page');
        $custom = get_post_types(array('public' => true, '_builtin' => false));

        if (is_array($custom)) {
            $types = array_merge($types, array_keys($custom));
        }

        $types = array_unique(array_filter(array_map('sanitize_key', $types)));

        return array_values($types);
    }

    private function count_scannable_posts($post_types = array()) {
        $types = empty($post_types) ? $this->get_scannable_post_types() : $post_types;
        $statuses = array('publish', 'future', 'draft', 'pending', 'private');
        $total = 0;

        foreach ($types as $type) {
            $counts = wp_count_posts($type);
            if (!$counts) {
                continue;
            }

            foreach ($statuses as $status) {
                if (isset($counts->$status)) {
                    $total += (int) $counts->$status;
                }
            }
        }

        return $total;
    }

    private function get_scanned_post_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE scan_status IN ('completed', 'completed_manual', 'completed_ai')"
        );
    }

    private function get_validated_keyword_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE product_validated = 1");
    }

    private function sanitize_post_types($post_types) {
        if (!is_array($post_types)) {
            $post_types = array($post_types);
        }

        $valid_types = $this->get_scannable_post_types();
        $filtered = array();

        foreach ($post_types as $type) {
            $type = sanitize_key((string) $type);
            if ($type !== '' && in_array($type, $valid_types, true)) {
                $filtered[] = $type;
            }
        }

        return array_values(array_unique($filtered));
    }

    private function sanitize_post_statuses($post_statuses) {
        $allowed = array('publish', 'draft', 'pending', 'future', 'private');

        if (!is_array($post_statuses)) {
            $post_statuses = array($post_statuses);
        }

        $filtered = array();

        foreach ($post_statuses as $status) {
            $status = sanitize_key((string) $status);
            if ($status !== '' && in_array($status, $allowed, true)) {
                $filtered[] = $status;
            }
        }

        if (empty($filtered)) {
            $filtered[] = 'publish';
        }

        return array_values(array_unique($filtered));
    }

    private function find_posts_for_scanning($post_types, $post_statuses, $min_words) {
        $args = array(
            'post_type' => $post_types,
            'post_status' => $post_statuses,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => false,
        );

        $ids = get_posts($args);
        if (!is_array($ids)) {
            $ids = array();
        }

        $filtered = array();

        foreach ($ids as $post_id) {
            $post_id = (int) $post_id;
            if ($post_id <= 0) {
                continue;
            }

            if ($min_words > 0) {
                $post = get_post($post_id);
                if (!$post) {
                    continue;
                }

                $word_count = $this->count_words_in_text($post->post_content);
                if ($word_count < $min_words) {
                    continue;
                }
            }

            $filtered[] = $post_id;
        }

        return $filtered;
    }

    private function count_words_in_text($text) {
        $clean = wp_strip_all_tags((string) $text);
        $clean = preg_replace('/\s+/u', ' ', $clean);
        $clean = trim($clean);

        if ($clean === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $clean);
        if (!is_array($words)) {
            $words = explode(' ', $clean);
        }

        $words = array_filter($words, static function ($word) {
            return $word !== '';
        });

        return count($words);
    }

    private function get_bulk_scan_transient_key($scan_id) {
        return 'yadore_bulk_scan_' . md5((string) $scan_id);
    }

    private function store_bulk_scan_state($scan_id, $state) {
        set_transient($this->get_bulk_scan_transient_key($scan_id), $state, HOUR_IN_SECONDS);
    }

    private function get_bulk_scan_state($scan_id) {
        $state = get_transient($this->get_bulk_scan_transient_key($scan_id));
        return is_array($state) ? $state : array();
    }

    private function delete_bulk_scan_state($scan_id) {
        delete_transient($this->get_bulk_scan_transient_key($scan_id));
    }

    private function prepare_bulk_scan_request($request) {
        if (!is_array($request)) {
            $request = array();
        }

        list($post_types, $post_status, $options) = $this->parse_scan_request($request);

        $post_ids = array();
        foreach (array('post_ids', 'posts', 'ids') as $key) {
            if (isset($request[$key])) {
                $post_ids = $this->sanitize_post_id_list($request[$key]);
                if (!empty($post_ids)) {
                    break;
                }
            }
        }

        if (empty($post_ids)) {
            $post_ids = $this->find_posts_for_scanning($post_types, $post_status, $options['min_words']);
        }

        if (empty($post_ids)) {
            throw new Exception(__('No posts matched the selected criteria.', 'yadore-monetizer'));
        }

        return array(
            'post_ids' => $post_ids,
            'options' => $options,
        );
    }

    private function parse_scan_request($request) {
        if (!is_array($request)) {
            $request = array();
        }

        $post_types = isset($request['post_types'])
            ? $this->sanitize_post_types($request['post_types'])
            : $this->sanitize_post_types(array('post'));

        if (empty($post_types)) {
            throw new Exception(__('No valid post types supplied for scanning.', 'yadore-monetizer'));
        }

        $post_status = isset($request['post_status'])
            ? $this->sanitize_post_statuses($request['post_status'])
            : $this->sanitize_post_statuses(array('publish'));

        if (empty($post_status)) {
            throw new Exception(__('No valid post status supplied for scanning.', 'yadore-monetizer'));
        }

        $min_words = isset($request['min_words']) ? intval($request['min_words']) : 0;

        $option_flags = array();
        if (isset($request['scan_options'])) {
            $option_flags = array_merge($option_flags, $this->normalize_scan_options($request['scan_options']));
        }
        if (isset($request['options'])) {
            $option_flags = array_merge($option_flags, $this->normalize_scan_options($request['options']));
        }

        $option_flags = array_map('sanitize_key', $option_flags);

        $force_rescan = in_array('force_rescan', $option_flags, true);

        $scan_options_provided = array_key_exists('scan_options', $request) || array_key_exists('options', $request);
        $default_use_ai = (bool) apply_filters('yadore_default_scan_use_ai', get_option('yadore_ai_enabled', false), $request);
        $use_ai = $scan_options_provided ? in_array('use_ai', $option_flags, true) : $default_use_ai;

        $validate_products_flag = in_array('validate_products', $option_flags, true);
        $validate_products = $validate_products_flag;

        if (isset($request['force_rescan'])) {
            $force_rescan = $this->interpret_boolean_flag($request['force_rescan']);
        }

        if (isset($request['use_ai'])) {
            $use_ai = $this->interpret_boolean_flag($request['use_ai']);
        }

        if (isset($request['validate_products'])) {
            $validate_products = $this->interpret_boolean_flag($request['validate_products']);
        } elseif (!$validate_products_flag) {
            $validate_products = true;
        }

        $options = array(
            'force_rescan' => $force_rescan,
            'use_ai' => $use_ai,
            'validate_products' => $validate_products,
            'min_words' => max(0, $min_words),
        );

        return array($post_types, $post_status, $options);
    }

    private function normalize_scan_options($raw) {
        $options = array();

        if (is_array($raw)) {
            foreach ($raw as $key => $value) {
                if (is_int($key)) {
                    if ($value !== null && $value !== '' && $value !== false) {
                        $options[] = $value;
                    }
                } elseif ($this->interpret_boolean_flag($value)) {
                    $options[] = $key;
                }
            }
        } elseif ($raw !== null && $raw !== '') {
            $options[] = $raw;
        }

        return $options;
    }

    private function interpret_boolean_flag($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return intval($value) === 1;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, array('1', 'true', 'yes', 'on'), true);
    }

    private function sanitize_post_id_list($ids) {
        if (is_string($ids)) {
            $ids = preg_split('/[\s,]+/', $ids);
        } elseif (!is_array($ids)) {
            $ids = array($ids);
        }

        $clean = array();

        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            $int_id = intval($id);

            if ($int_id > 0) {
                $clean[$int_id] = $int_id;
            }
        }

        return array_values($clean);
    }

    private function execute_bulk_scan_immediately($post_ids, $options) {
        $results = array();
        $summary = array(
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'ai_used' => 0,
            'validated_keywords' => 0,
        );

        foreach ($post_ids as $post_id) {
            $result = $this->process_post_scan($post_id, $options);

            if (!is_array($result)) {
                continue;
            }

            $status = isset($result['status']) ? $result['status'] : '';

            if ($status === 'success') {
                $summary['success']++;
            } elseif ($status === 'skipped') {
                $summary['skipped']++;
            } else {
                $summary['failed']++;
            }

            if (!empty($result['ai_used'])) {
                $summary['ai_used']++;
            }

            if (!empty($result['product_validated'])) {
                $summary['validated_keywords'] += (int) $result['product_validated'];
            }

            $results[] = $result;
        }

        $total = count($post_ids);
        $completed = count($results);
        $percentage = $total > 0 ? min(100, (int) round(($completed / $total) * 100)) : 100;

        $this->update_scanned_posts_stat();
        update_option('yadore_bulk_scan_completed', current_time('mysql'));

        $this->log(sprintf('Processed %d posts via immediate bulk scan', $completed), 'debug');

        return array(
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
            'results' => $results,
            'summary' => $summary,
        );
    }

    private function process_post_scan($post_id, $options = array()) {
        $defaults = array(
            'force_rescan' => false,
            'use_ai' => false,
            'validate_products' => true,
            'min_words' => 0,
        );
        $options = wp_parse_args($options, $defaults);

        $post = get_post($post_id);
        if (!$post || $post->post_status === 'trash') {
            return array(
                'status' => 'failed',
                'message' => __('Post could not be found.', 'yadore-monetizer'),
                'post_id' => (int) $post_id,
                'post_title' => '',
                'primary_keyword' => '',
                'keyword_confidence' => 0,
                'product_validated' => 0,
                'product_count' => 0,
                'scan_status' => 'failed',
                'status_label' => $this->get_scan_status_label('failed'),
                'last_scanned' => current_time('mysql'),
                'ai_used' => false,
                'word_count' => 0,
                'duration_ms' => 0,
            );
        }

        $word_count = $this->count_words_in_text($post->post_content);

        if ($options['min_words'] > 0 && $word_count < $options['min_words']) {
            return array(
                'status' => 'skipped',
                'message' => sprintf(__('Skipped (%d words required).', 'yadore-monetizer'), $options['min_words']),
                'post_id' => (int) $post_id,
                'post_title' => get_the_title($post_id),
                'primary_keyword' => '',
                'keyword_confidence' => 0,
                'product_validated' => 0,
                'product_count' => 0,
                'scan_status' => 'skipped',
                'status_label' => $this->get_scan_status_label('skipped'),
                'last_scanned' => current_time('mysql'),
                'ai_used' => false,
                'word_count' => $word_count,
                'duration_ms' => 0,
            );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';
        $existing = $this->table_exists($table)
            ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d", $post_id), ARRAY_A)
            : null;

        if (!$options['force_rescan'] && $existing && in_array($existing['scan_status'], array('completed', 'completed_manual', 'completed_ai'), true)) {
            $formatted = $this->format_scan_result_row($existing);
            $formatted['status'] = 'skipped';
            $formatted['message'] = __('Scan skipped (already up to date).', 'yadore-monetizer');
            return $formatted;
        }

        $start_time = microtime(true);
        $ai_used = false;
        $confidence = 0.0;

        $ai_candidates = array();
        $candidate_keywords = array();
        $sanitized_ai_candidates = array();
        $gemini_confidence = 0.0;

        if ($options['use_ai']) {
            $ai_result = $this->call_gemini_api($post->post_title, wp_strip_all_tags($post->post_content), true, $post_id);

            if (is_array($ai_result) && empty($ai_result['error'])) {
                if (isset($ai_result['keyword']) && trim((string) $ai_result['keyword']) !== '') {
                    $ai_candidates[] = $ai_result['keyword'];
                }

                if (!empty($ai_result['alternate_keywords']) && is_array($ai_result['alternate_keywords'])) {
                    $ai_candidates = array_merge($ai_candidates, $ai_result['alternate_keywords']);
                } elseif (!empty($ai_result['alternates']) && is_array($ai_result['alternates'])) {
                    $ai_candidates = array_merge($ai_candidates, $ai_result['alternates']);
                }

                if (isset($ai_result['confidence'])) {
                    $gemini_confidence = max(0, min(1, (float) $ai_result['confidence']));
                }

                if (!empty($ai_candidates)) {
                    $ai_used = true;
                }
            } elseif (is_string($ai_result) && trim($ai_result) !== '') {
                $ai_candidates[] = $ai_result;
                $gemini_confidence = 0.85;
                $ai_used = true;
            }
        }

        $heuristic_keyword = $this->sanitize_single_keyword($this->extract_keyword_from_text($post->post_content, $post->post_title));
        $fallback_keyword = $this->sanitize_single_keyword($this->normalize_keyword_case(wp_trim_words($post->post_title, 6, '')));

        if (!empty($ai_candidates)) {
            $candidate_keywords = array_merge($candidate_keywords, $ai_candidates);
        }

        if ($heuristic_keyword !== '') {
            $candidate_keywords[] = $heuristic_keyword;
        }

        if ($fallback_keyword !== '') {
            $candidate_keywords[] = $fallback_keyword;
        }

        $candidate_keywords = $this->sanitize_keyword_list($candidate_keywords);
        $sanitized_ai_candidates = $this->sanitize_keyword_list($ai_candidates);

        if ($ai_used && empty($sanitized_ai_candidates)) {
            $ai_used = false;
        }

        $timestamp = current_time('mysql');

        if (empty($candidate_keywords)) {
            $message = __('No keyword could be detected for this post.', 'yadore-monetizer');
            $this->record_scan_failure($post_id, $existing, $message);

            return array(
                'status' => 'failed',
                'message' => $message,
                'post_id' => (int) $post_id,
                'post_title' => get_the_title($post_id),
                'primary_keyword' => '',
                'keyword_confidence' => 0,
                'product_validated' => 0,
                'product_count' => 0,
                'scan_status' => 'failed',
                'status_label' => $this->get_scan_status_label('failed'),
                'last_scanned' => $timestamp,
                'ai_used' => $ai_used,
                'word_count' => $word_count,
                'duration_ms' => (int) round((microtime(true) - $start_time) * 1000),
            );
        }

        $this->remember_keyword_candidates($post_id, $candidate_keywords);

        $keyword = $candidate_keywords[0];
        if ($ai_used) {
            $confidence = $gemini_confidence > 0 ? $gemini_confidence : 0.85;
        }

        $product_count = 0;
        $product_validated = 0;

        if ($options['validate_products']) {
            $products = $this->get_products($candidate_keywords, 3, $post_id);
            if (!is_array($products)) {
                $products = array();
            }
            $product_count = count($products);

            $selected_keyword = $this->get_last_product_keyword();
            if ($selected_keyword !== '') {
                $keyword = $selected_keyword;
            }

            if ($product_count > 0) {
                $product_validated = 1;
            }
        } elseif ($existing) {
            $product_count = isset($existing['product_count']) ? (int) $existing['product_count'] : 0;
            $product_validated = isset($existing['product_validated']) ? (int) $existing['product_validated'] : 0;
        }

        $keyword = $this->sanitize_single_keyword($keyword);

        if ($keyword === '' && $fallback_keyword !== '') {
            $keyword = $fallback_keyword;
        }

        if ($keyword === '') {
            $message = __('No keyword could be detected for this post.', 'yadore-monetizer');
            $this->record_scan_failure($post_id, $existing, $message);

            return array(
                'status' => 'failed',
                'message' => $message,
                'post_id' => (int) $post_id,
                'post_title' => get_the_title($post_id),
                'primary_keyword' => '',
                'keyword_confidence' => 0,
                'product_validated' => 0,
                'product_count' => 0,
                'scan_status' => 'failed',
                'status_label' => $this->get_scan_status_label('failed'),
                'last_scanned' => $timestamp,
                'ai_used' => $ai_used,
                'word_count' => $word_count,
                'duration_ms' => (int) round((microtime(true) - $start_time) * 1000),
            );
        }

        $ordered_candidates = $this->sanitize_keyword_list(array_merge(array($keyword), $candidate_keywords));
        $this->remember_keyword_candidates($post_id, $ordered_candidates);

        if (!empty($sanitized_ai_candidates) && in_array($keyword, $sanitized_ai_candidates, true)) {
            $ai_used = true;
            $confidence = $confidence > 0 ? $confidence : ($gemini_confidence > 0 ? $gemini_confidence : 0.85);
        } elseif ($heuristic_keyword !== '' && $keyword === $heuristic_keyword) {
            $ai_used = false;
            $confidence = max($confidence, 0.65);
        } elseif ($fallback_keyword !== '' && $keyword === $fallback_keyword) {
            $ai_used = false;
            $confidence = max($confidence, 0.4);
        } else {
            if ($ai_used && !empty($sanitized_ai_candidates) && !in_array($keyword, $sanitized_ai_candidates, true)) {
                $ai_used = false;
            }

            if ($confidence <= 0) {
                $confidence = $ai_used ? 0.75 : 0.5;
            }
        }

        $confidence = max(0, min(1, $confidence));
        $duration_ms = (int) round((microtime(true) - $start_time) * 1000);
        $scan_status = $ai_used ? 'completed_ai' : 'completed_manual';

        if ($this->table_exists($table)) {
            $data = array(
                'post_title' => $post->post_title,
                'primary_keyword' => $keyword,
                'fallback_keyword' => $fallback_keyword,
                'keyword_confidence' => $confidence,
                'product_validated' => $product_validated,
                'product_count' => $product_count,
                'word_count' => $word_count,
                'content_hash' => md5((string) $post->post_content),
                'last_scanned' => $timestamp,
                'scan_status' => $scan_status,
                'scan_error' => '',
                'scan_attempts' => ($existing ? (int) $existing['scan_attempts'] : 0) + 1,
                'scan_duration_ms' => $duration_ms,
            );

            if ($existing) {
                $wpdb->update(
                    $table,
                    $data,
                    array('post_id' => $post_id),
                    array('%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d'),
                    array('%d')
                );
            } else {
                $data['post_id'] = $post_id;
                $wpdb->insert(
                    $table,
                    $data,
                    array('%d', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d')
                );
            }
        }

        if ($ai_used) {
            update_post_meta($post_id, '_yadore_scan_ai_used', '1');
        } else {
            delete_post_meta($post_id, '_yadore_scan_ai_used');
        }

        $this->record_scan_event($post_id, $scan_status, array(
            'keyword' => $keyword,
            'confidence' => $confidence,
            'product_count' => $product_count,
            'ai_used' => $ai_used,
            'duration_ms' => $duration_ms,
        ));

        return array(
            'status' => 'success',
            'message' => __('Scan completed successfully.', 'yadore-monetizer'),
            'post_id' => (int) $post_id,
            'post_title' => get_the_title($post_id),
            'primary_keyword' => $keyword,
            'keyword_confidence' => $confidence,
            'product_validated' => $product_validated,
            'product_count' => $product_count,
            'scan_status' => $scan_status,
            'status_label' => $this->get_scan_status_label($scan_status),
            'last_scanned' => $timestamp,
            'ai_used' => $ai_used,
            'word_count' => $word_count,
            'duration_ms' => $duration_ms,
        );
    }

    public function auto_scan_post_on_save($post_id, $post) {
        try {
            if (!get_option('yadore_auto_scan_posts', 1)) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
                return;
            }

            if (!$post instanceof WP_Post) {
                $post = get_post($post_id);
            }

            if (!$post || $post->post_status === 'auto-draft' || $post->post_status === 'trash') {
                return;
            }

            if (!in_array($post->post_type, $this->get_scannable_post_types(), true)) {
                return;
            }

            $should_run = apply_filters('yadore_auto_scan_should_run', true, $post_id, $post);
            if (!$should_run) {
                return;
            }

            $min_words = intval(get_option('yadore_min_content_words', 0));
            if ($min_words < 0) {
                $min_words = 0;
            }

            $word_count = $this->count_words_in_text($post->post_content);
            if ($min_words > 0 && $word_count < $min_words) {
                $this->log(
                    sprintf(
                        'Auto scan skipped for post %d due to minimum word requirement (%d < %d).',
                        $post_id,
                        $word_count,
                        $min_words
                    ),
                    'debug'
                );
                return;
            }

            $use_ai_default = (bool) get_option('yadore_ai_enabled', false);
            $use_ai = (bool) apply_filters('yadore_auto_scan_use_ai', $use_ai_default, $post_id, $post);

            $options = array(
                'force_rescan' => false,
                'use_ai' => $use_ai,
                'validate_products' => true,
                'min_words' => 0,
            );

            $result = $this->process_post_scan($post_id, $options);

            if (is_array($result) && isset($result['status'])) {
                if ($result['status'] === 'success') {
                    $this->update_scanned_posts_stat();
                    $this->log(
                        sprintf('Auto scan completed for post %d (%s).', $post_id, $post->post_title),
                        'debug'
                    );
                } elseif (!empty($result['message'])) {
                    $this->log(
                        sprintf('Auto scan note for post %d: %s', $post_id, $result['message']),
                        'debug'
                    );
                }
            }
        } catch (Exception $e) {
            $this->log_error('Auto scan on save failed', $e, 'medium', array('post_id' => $post_id));
        }
    }

    private function record_scan_failure($post_id, $existing, $message) {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return;
        }

        $timestamp = current_time('mysql');
        $attempts = ($existing ? (int) $existing['scan_attempts'] : 0) + 1;

        if ($existing) {
            $wpdb->update(
                $table,
                array(
                    'scan_status' => 'failed',
                    'scan_error' => $message,
                    'last_scanned' => $timestamp,
                    'scan_attempts' => $attempts,
                ),
                array('post_id' => $post_id),
                array('%s', '%s', '%s', '%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                    'primary_keyword' => '',
                    'fallback_keyword' => '',
                    'keyword_confidence' => 0,
                    'product_validated' => 0,
                    'product_count' => 0,
                    'word_count' => 0,
                    'content_hash' => '',
                    'last_scanned' => $timestamp,
                    'scan_status' => 'failed',
                    'scan_error' => $message,
                    'scan_attempts' => $attempts,
                    'scan_duration_ms' => 0,
                ),
                array('%d', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
    }

    private function record_scan_event($post_id, $status, $data = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($table)) {
            return;
        }

        $payload = array_merge(
            array(
                'status' => $status,
            ),
            $data
        );

        $wpdb->insert(
            $table,
            array(
                'event_type' => 'post_scan',
                'event_data' => wp_json_encode($payload),
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%d', '%s')
        );
    }

    private function update_scanned_posts_stat() {
        $stats = get_option('yadore_stats_cache', array());
        if (!is_array($stats)) {
            $stats = array();
        }

        $defaults = array(
            'total_products' => 0,
            'scanned_posts' => 0,
            'overlay_views' => 0,
            'conversion_rate' => '0%',
        );

        $stats = wp_parse_args($stats, $defaults);
        $stats['scanned_posts'] = $this->get_scanned_post_count();

        update_option('yadore_stats_cache', $stats);
    }

    private function get_scan_results($filter, $page, $per_page) {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return array(
                'results' => array(),
                'pagination' => array(
                    'total' => 0,
                    'per_page' => $per_page,
                    'current_page' => $page,
                    'total_pages' => 1,
                ),
            );
        }

        $filter = in_array($filter, array('all', 'successful', 'failed', 'ai_analyzed', 'skipped'), true) ? $filter : 'all';

        $clauses = array('1=1');
        if ($filter === 'successful') {
            $clauses[] = "scan_status IN ('completed', 'completed_manual', 'completed_ai')";
        } elseif ($filter === 'failed') {
            $clauses[] = "scan_status = 'failed'";
        } elseif ($filter === 'ai_analyzed') {
            $clauses[] = "scan_status = 'completed_ai'";
        } elseif ($filter === 'skipped') {
            $clauses[] = "scan_status = 'skipped'";
        }

        $where = 'WHERE ' . implode(' AND ', $clauses);
        $offset = ($page - 1) * $per_page;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where} ORDER BY last_scanned DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            $rows = array();
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");

        $results = array();
        foreach ($rows as $row) {
            $results[] = $this->format_scan_result_row($row);
        }

        return array(
            'results' => $results,
            'pagination' => array(
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => max(1, (int) ceil($total / $per_page)),
            ),
        );
    }

    private function format_scan_result_row($row) {
        $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
        $confidence = isset($row['keyword_confidence']) ? (float) $row['keyword_confidence'] : 0;
        $status = isset($row['scan_status']) && $row['scan_status'] !== '' ? $row['scan_status'] : 'pending';

        return array(
            'status' => 'success',
            'message' => '',
            'post_id' => $post_id,
            'post_title' => !empty($row['post_title']) ? $row['post_title'] : get_the_title($post_id),
            'primary_keyword' => isset($row['primary_keyword']) ? $row['primary_keyword'] : '',
            'keyword_confidence' => $confidence,
            'product_validated' => isset($row['product_validated']) ? (int) $row['product_validated'] : 0,
            'product_count' => isset($row['product_count']) ? (int) $row['product_count'] : 0,
            'scan_status' => $status,
            'status_label' => $this->get_scan_status_label($status),
            'last_scanned' => isset($row['last_scanned']) ? $row['last_scanned'] : '',
            'ai_used' => $status === 'completed_ai',
            'word_count' => isset($row['word_count']) ? (int) $row['word_count'] : 0,
            'duration_ms' => isset($row['scan_duration_ms']) ? (int) $row['scan_duration_ms'] : 0,
        );
    }

    private function get_scan_status_label($status) {
        $map = array(
            'completed_ai' => __('Completed (AI)', 'yadore-monetizer'),
            'completed_manual' => __('Completed', 'yadore-monetizer'),
            'completed' => __('Completed', 'yadore-monetizer'),
            'failed' => __('Failed', 'yadore-monetizer'),
            'skipped' => __('Skipped', 'yadore-monetizer'),
            'pending' => __('Pending', 'yadore-monetizer'),
        );

        if (isset($map[$status])) {
            return $map[$status];
        }

        return ucwords(str_replace('_', ' ', (string) $status));
    }

    private function search_posts_for_scanner($query) {
        $args = array(
            's' => $query,
            'post_type' => $this->get_scannable_post_types(),
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $posts = get_posts($args);
        if (!is_array($posts)) {
            return array();
        }

        $results = array();
        foreach ($posts as $post) {
            $record = $this->fetch_scan_record($post->ID);
            $results[] = $this->prepare_post_for_scanner($post, $record);
        }

        return $results;
    }

    private function fetch_scan_record($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return null;
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE post_id = %d", $post_id), ARRAY_A);

        return $row ? $row : null;
    }

    private function prepare_post_for_scanner($post, $record = null) {
        $post_id = isset($post->ID) ? (int) $post->ID : (int) $post;

        if (!($post instanceof WP_Post)) {
            $post = get_post($post_id);
        }

        if (!$post) {
            return array();
        }

        $status_object = get_post_status_object($post->post_status);
        $status_label = $status_object ? $status_object->label : ucfirst($post->post_status);

        $result = array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'date' => mysql2date(get_option('date_format'), $post->post_date),
            'status' => $status_label,
            'raw_status' => $post->post_status,
            'excerpt' => wp_trim_words($post->post_content, 25),
            'word_count' => $this->count_words_in_text($post->post_content),
        );

        if ($record) {
            $result['primary_keyword'] = $record['primary_keyword'];
            $result['last_scanned'] = $record['last_scanned'];
            $result['scan_status'] = $record['scan_status'];
            $result['status_label'] = $this->get_scan_status_label($record['scan_status']);
            $result['keyword_confidence'] = isset($record['keyword_confidence']) ? (float) $record['keyword_confidence'] : 0;
            $result['product_validated'] = (int) $record['product_validated'];
            $result['product_count'] = (int) $record['product_count'];
        } else {
            $result['primary_keyword'] = '';
            $result['last_scanned'] = '';
            $result['scan_status'] = 'pending';
            $result['status_label'] = $this->get_scan_status_label('pending');
            $result['keyword_confidence'] = 0;
            $result['product_validated'] = 0;
            $result['product_count'] = 0;
        }

        return $result;
    }

    private function get_scanner_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return array(
                'charts' => array(
                    'keywords' => array(
                        'labels' => array(),
                        'counts' => array(),
                    ),
                    'success' => array(
                        'labels' => array(
                            __('Successful', 'yadore-monetizer'),
                            __('Failed', 'yadore-monetizer'),
                            __('Skipped', 'yadore-monetizer'),
                        ),
                        'counts' => array(0, 0, 0),
                    ),
                ),
                'stats' => array(
                    'top_keyword' => '',
                    'average_confidence' => 0,
                    'ai_usage_rate' => 0,
                    'success_rate' => 0,
                    'validated_keywords' => 0,
                    'total_scans' => 0,
                ),
            );
        }

        $top_keywords = $wpdb->get_results(
            "SELECT primary_keyword, COUNT(*) AS total FROM {$table} WHERE primary_keyword <> '' GROUP BY primary_keyword ORDER BY total DESC LIMIT 5",
            ARRAY_A
        );

        if (!is_array($top_keywords)) {
            $top_keywords = array();
        }

        $success_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE scan_status IN ('completed', 'completed_manual', 'completed_ai')");
        $failed_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE scan_status = 'failed'");
        $skipped_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE scan_status = 'skipped'");
        $total_scans = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $ai_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE scan_status = 'completed_ai'");
        $avg_confidence = (float) $wpdb->get_var("SELECT AVG(keyword_confidence) FROM {$table} WHERE keyword_confidence > 0");

        $labels = array();
        $counts = array();
        foreach ($top_keywords as $keyword_row) {
            $labels[] = $keyword_row['primary_keyword'];
            $counts[] = (int) $keyword_row['total'];
        }

        $stats = array(
            'top_keyword' => isset($labels[0]) ? $labels[0] : '',
            'average_confidence' => $avg_confidence > 0 ? round($avg_confidence * 100, 1) : 0,
            'ai_usage_rate' => $success_count > 0 ? round(($ai_count / $success_count) * 100, 1) : 0,
            'success_rate' => $total_scans > 0 ? round(($success_count / $total_scans) * 100, 1) : 0,
            'validated_keywords' => $this->get_validated_keyword_count(),
            'total_scans' => $total_scans,
        );

        return array(
            'charts' => array(
                'keywords' => array(
                    'labels' => $labels,
                    'counts' => $counts,
                ),
                'success' => array(
                    'labels' => array(
                        __('Successful', 'yadore-monetizer'),
                        __('Failed', 'yadore-monetizer'),
                        __('Skipped', 'yadore-monetizer'),
                    ),
                    'counts' => array($success_count, $failed_count, $skipped_count),
                ),
            ),
            'stats' => $stats,
        );
    }

    private function table_exists($table_name) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        return $result === $table_name;
    }

    // v2.7: Shortcode Implementation (Enhanced)
    public function shortcode_products($atts) {
        $atts = shortcode_atts(array(
            'keyword' => '',
            'limit' => 6,
            'format' => 'grid',
            'cache' => 'true',
            'class' => ''
        ), $atts);

        try {
            $keyword = sanitize_text_field((string) $atts['keyword']);
            $limit = intval($atts['limit']);
            if ($limit <= 0) {
                $limit = 6;
            }
            $limit = min(50, $limit);

            $format = sanitize_key($atts['format']);
            if ($format === '') {
                $format = 'grid';
            }

            $use_cache = true;
            if (array_key_exists('cache', $atts)) {
                $use_cache = $this->interpret_boolean_flag($atts['cache']);
            }

            global $post;
            $post_id = 0;
            $post_content = '';

            if ($post instanceof WP_Post) {
                $post_id = (int) $post->ID;
                $post_content = (string) $post->post_content;
            }

            if ($keyword === '') {
                $keyword = $this->resolve_product_keyword($post_id, $post_content);
            }

            $keyword = sanitize_text_field((string) $keyword);

            if ($keyword === '') {
                return '<div class="yadore-error">' . esc_html__(
                    'No keyword available for product search. Run the scanner or provide a keyword attribute.',
                    'yadore-monetizer'
                ) . '</div>';
            }

            $products = $this->get_products($keyword, $limit, $post_id, $use_cache);

            if (empty($products)) {
                return '<div class="yadore-no-results">' . sprintf(
                    esc_html__('No products found for "%s".', 'yadore-monetizer'),
                    esc_html($keyword)
                ) . '</div>';
            }

            $template_file = YADORE_PLUGIN_DIR . "templates/products-{$format}.php";

            if (!file_exists($template_file)) {
                $template_file = YADORE_PLUGIN_DIR . "templates/products-grid.php";
            }

            ob_start();
            $offers = $products; // For template compatibility
            $additional_classes = !empty($atts['class']) ? ' ' . sanitize_html_class($atts['class']) : '';
            include $template_file;
            $output = ob_get_clean();

            // v2.7: Track shortcode usage
            $this->track_shortcode_usage($keyword, $limit, $format);

            return $output;

        } catch (Exception $e) {
            $this->log_error('Shortcode rendering failed', $e);
            return '<div class="yadore-error">' . esc_html__(
                'Error loading products. Please try again later.',
                'yadore-monetizer'
            ) . '</div>';
        }
    }

    // v2.7: Enhanced Auto-Injection
    public function auto_inject_products($content) {
        if (!get_option('yadore_auto_detection', true) || !is_single() || is_admin()) {
            return $content;
        }

        try {
            global $post;
            if (!$post) {
                return $content;
            }

            // Get post keyword
            global $wpdb;
            $posts_table = $wpdb->prefix . 'yadore_post_keywords';
            $post_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $posts_table WHERE post_id = %d AND product_validated = 1",
                $post->ID
            ));

            if (!$post_data || empty($post_data->primary_keyword)) {
                return $content;
            }

            $keyword = $post_data->primary_keyword ?: $post_data->fallback_keyword;
            if (empty($keyword)) {
                return $content;
            }

            // v2.7: Intelligent content injection
            $injection_method = get_option('yadore_injection_method', 'after_paragraph');
            $injection_position = get_option('yadore_injection_position', 2);

            // Generate products shortcode
            $shortcode = "[yadore_products keyword=\"{$keyword}\" limit=\"3\" format=\"inline\"]";

            switch ($injection_method) {
                case 'after_paragraph':
                    $paragraphs = explode('</p>', $content);
                    if (count($paragraphs) > $injection_position) {
                        $paragraphs[$injection_position - 1] .= '</p>' . do_shortcode($shortcode);
                        $content = implode('</p>', $paragraphs);
                    } else {
                        $content .= do_shortcode($shortcode);
                    }
                    break;

                case 'end_of_content':
                    $content .= do_shortcode($shortcode);
                    break;

                case 'before_content':
                    $content = do_shortcode($shortcode) . $content;
                    break;
            }

            // v2.7: Track auto-injection
            $this->track_auto_injection($post->ID, $keyword);

            return $content;

        } catch (Exception $e) {
            $this->log_error('Auto-injection failed', $e);
            return $content;
        }
    }

    // v2.7: Tracking methods
    private function track_overlay_view($post_id, $keyword, $product_count) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if ( $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $analytics_table) ) != $analytics_table ) { return; }
$wpdb->insert($analytics_table, array(
            'event_type' => 'overlay_view',
            'event_data' => json_encode(array(
                'keyword' => $keyword,
                'product_count' => $product_count
            )),
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
    }

    private function track_shortcode_usage($keyword, $limit, $format) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if ( $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $analytics_table) ) != $analytics_table ) { return; }
$wpdb->insert($analytics_table, array(
            'event_type' => 'shortcode_usage',
            'event_data' => json_encode(array(
                'keyword' => $keyword,
                'limit' => $limit,
                'format' => $format
            )),
            'post_id' => get_the_ID(),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }

    private function track_auto_injection($post_id, $keyword) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if ( $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $analytics_table) ) != $analytics_table ) { return; }
$wpdb->insert($analytics_table, array(
            'event_type' => 'auto_injection',
            'event_data' => json_encode(array(
                'keyword' => $keyword
            )),
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }

    private function log_api_call($api_type, $endpoint, $status, $data = array()) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'yadore_api_logs';

        $wpdb->insert($logs_table, array(
            'api_type' => $api_type,
            'endpoint_url' => $endpoint,
            'success' => $status === 'success' ? 1 : 0,
            'response_body' => json_encode($data),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }

    // Continue implementing all other methods...
    // (More methods will be added in subsequent parts)

    // Basic logging methods
    private function log_error($message, $exception = null, $severity = 'medium', $context = array()) {
        if (!get_option('yadore_error_logging_enabled', true)) {
            return;
        }

        global $wpdb;
        $error_logs_table = $wpdb->prefix . 'yadore_error_logs';

        try {
            $error_data = array(
                'error_type' => $exception ? get_class($exception) : 'YadoreError',
                'error_message' => $message,
                'error_code' => $exception ? $exception->getCode() : '',
                'stack_trace' => $exception ? $exception->getTraceAsString() : wp_debug_backtrace_summary(),
                'context_data' => json_encode($context),
                'post_id' => $context['post_id'] ?? 0,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'severity' => $severity
            );

            $wpdb->insert($error_logs_table, $error_data);

            // Also log to debug
            $this->error_log[] = '[' . current_time('Y-m-d H:i:s') . '] ' . $severity . ': ' . $message;

        } catch (Exception $e) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('Yadore Error Logging Failed: ' . $e->getMessage());
                error_log('Original Error: ' . $message);
            }
        }
    }

    private function log($message, $level = 'info') {
        if (get_option('yadore_debug_mode', false)) {
            $formatted_message = '[' . current_time('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message;
            $this->debug_log[] = $formatted_message;

            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('Yadore ' . strtoupper($level) . ': ' . $message);
            }
        }
    }

    public function get_debug_log() {
        return implode("\n", array_merge($this->debug_log, $this->error_log));
    }

    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (get_transient('yadore_activation_notice')) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('Yadore Monetizer Pro', 'yadore-monetizer') . '</strong> ' . esc_html__('was activated successfully. Configure your API keys to start monetizing.', 'yadore-monetizer') . '</p></div>';
            delete_transient('yadore_activation_notice');
        }

        $api_key = trim((string) get_option('yadore_api_key'));
        if ($api_key === '') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Yadore Monetizer Pro requires a valid Yadore API key. Please enter your key in the plugin settings.', 'yadore-monetizer') . '</p></div>';
        }

        if (get_option('yadore_ai_enabled', false) && trim((string) get_option('yadore_gemini_api_key')) === '') {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Gemini AI analysis is enabled but no API key is configured. Add a Gemini API key to use AI-powered keyword detection.', 'yadore-monetizer') . '</p></div>';
        }

        $recent_error = $this->get_latest_unresolved_error();
        if ($recent_error && !empty($recent_error->error_message)) {
            $severity_label = strtoupper($recent_error->severity ?? '');
            $timestamp_raw = $recent_error->created_at ? strtotime($recent_error->created_at) : false;
            $timestamp = $timestamp_raw ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp_raw)) : '';
            $message = esc_html($recent_error->error_message);
            $error_id = isset($recent_error->id) ? (int) $recent_error->id : 0;

            echo '<div class="notice notice-error yadore-error-notice is-dismissible" data-error-id="' . esc_attr($error_id) . '">';
            echo '<p><strong>' . esc_html__('Yadore Monetizer Pro Error', 'yadore-monetizer') . '</strong> ' . $message;
            if ($timestamp !== '') {
                echo ' <em>(' . esc_html($severity_label) . ' &ndash; ' . $timestamp . ')</em>';
            }
            echo '</p>';

            echo '<p class="yadore-error-actions">';
            echo '<button type="button" class="button button-secondary yadore-resolve-now" data-error-id="' . esc_attr($error_id) . '">';
            echo esc_html__('Mark as resolved', 'yadore-monetizer');
            echo '</button>';
            echo '<span class="yadore-error-hint">' . esc_html__('Dismiss this alert after confirming the issue is fixed. The error log history is available in the Tools panel.', 'yadore-monetizer') . '</span>';
            echo '</p>';

            echo '</div>';
        }

        $queued_notice = get_transient('yadore_admin_notice_queue');
        if (is_array($queued_notice) && !empty($queued_notice['message'])) {
            $type = isset($queued_notice['type']) && in_array($queued_notice['type'], array('error', 'warning', 'success', 'info'), true)
                ? $queued_notice['type']
                : 'info';

            $classes = 'notice notice-' . esc_attr($type);
            if (!in_array($type, array('error', 'warning'), true)) {
                $classes .= ' is-dismissible';
            }

            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                $classes,
                esc_html($queued_notice['message'])
            );

            delete_transient('yadore_admin_notice_queue');
        }
    }

    public function show_initialization_error() {
        echo '<div class="notice notice-error"><p><strong>Yadore Monetizer Pro Error:</strong> Plugin initialization failed. Please check the debug log for details.</p></div>';
    }

    private function get_latest_unresolved_error($severities = array('high', 'critical')) {
        if ($this->latest_error_notice_checked) {
            return $this->latest_error_notice;
        }

        $this->latest_error_notice_checked = true;

        global $wpdb;
        $table = $wpdb->prefix . 'yadore_error_logs';

        if (!$this->table_exists($table)) {
            $this->latest_error_notice = null;
            return null;
        }

        $allowed = array('critical', 'high', 'medium', 'low');
        $filtered = array();

        foreach ((array) $severities as $severity) {
            $severity = strtolower((string) $severity);
            if (in_array($severity, $allowed, true)) {
                $filtered[] = $severity;
            }
        }

        if (empty($filtered)) {
            $filtered = array('high', 'critical');
        }

        $placeholders = implode(',', array_fill(0, count($filtered), '%s'));
        $sql = "SELECT id, error_message, severity, created_at FROM {$table} WHERE resolved = 0 AND severity IN ({$placeholders}) ORDER BY created_at DESC LIMIT 1";
        $prepared = $wpdb->prepare($sql, $filtered);
        $this->latest_error_notice = $wpdb->get_row($prepared);

        return $this->latest_error_notice;
    }

    private function reset_error_notice_cache() {
        $this->latest_error_notice = null;
        $this->latest_error_notice_checked = false;
    }

    private function get_wp_debug_log_path() {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            if (is_string(WP_DEBUG_LOG) && WP_DEBUG_LOG !== '') {
                return WP_DEBUG_LOG;
            }

            if (defined('WP_CONTENT_DIR')) {
                return WP_CONTENT_DIR . '/debug.log';
            }
        }

        return '';
    }

    private function read_wp_debug_excerpt($length = 50000) {
        $path = $this->get_wp_debug_log_path();
        if ($path === '' || !@file_exists($path) || !@is_readable($path)) {
            return '';
        }

        $length = max(1024, (int) $length);
        $size = @filesize($path);

        if ($size === false || $size <= $length) {
            $contents = @file_get_contents($path);
            return is_string($contents) ? $contents : '';
        }

        $handle = @fopen($path, 'r');
        if (!$handle) {
            return '';
        }

        $offset = $size - $length;
        if ($offset < 0) {
            $offset = 0;
        }

        if ($offset > 0) {
            fseek($handle, $offset);
            fgets($handle);
        }

        $contents = stream_get_contents($handle);
        fclose($handle);

        if (!is_string($contents)) {
            return '';
        }

        $contents = ltrim($contents, "\r\n");

        if ($offset > 0) {
            $contents = "...(truncated)...\n" . $contents;
        }

        return $contents;
    }

    public function ajax_resolve_error() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions to resolve error logs.', 'yadore-monetizer'));
            }

            $error_id = isset($_POST['error_id']) ? absint($_POST['error_id']) : 0;
            if ($error_id <= 0) {
                throw new Exception(__('Invalid error reference supplied.', 'yadore-monetizer'));
            }

            global $wpdb;
            $table = $wpdb->prefix . 'yadore_error_logs';

            if (!$this->table_exists($table)) {
                throw new Exception(__('Error log table not found.', 'yadore-monetizer'));
            }

            $updated = $wpdb->update(
                $table,
                array(
                    'resolved' => 1,
                    'resolved_at' => current_time('mysql'),
                ),
                array(
                    'id' => $error_id,
                    'resolved' => 0,
                ),
                array('%d', '%s'),
                array('%d', '%d')
            );

            if ($updated === false) {
                throw new Exception(__('Failed to update error status. Please try again.', 'yadore-monetizer'));
            }

            $this->reset_error_notice_cache();

            wp_send_json_success(array(
                'message' => __('Error entry marked as resolved.', 'yadore-monetizer'),
                'error_id' => $error_id,
            ));

        } catch (Exception $e) {
            $this->log_error('Failed to resolve error log entry', $e, 'medium');
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_error_logs() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions to view error logs.', 'yadore-monetizer'));
            }

            global $wpdb;
            $table = $wpdb->prefix . 'yadore_error_logs';

            if (!$this->table_exists($table)) {
                wp_send_json_success(array(
                    'logs' => array(),
                    'counts' => array(
                        'critical' => 0,
                        'high' => 0,
                        'medium' => 0,
                        'low' => 0,
                    ),
                    'open_counts' => array(
                        'critical' => 0,
                        'high' => 0,
                        'medium' => 0,
                        'low' => 0,
                    ),
                ));
            }

            $severity = isset($_POST['severity']) ? sanitize_key(wp_unslash($_POST['severity'])) : 'all';
            $allowed = array('all', 'critical', 'high', 'medium', 'low');
            if (!in_array($severity, $allowed, true)) {
                $severity = 'all';
            }

            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 50;
            if ($limit <= 0) {
                $limit = 50;
            }
            $limit = min($limit, 200);

            $where = '';
            $params = array();
            if ($severity !== 'all') {
                $where = 'WHERE severity = %s';
                $params[] = $severity;
            }

            $query = "SELECT id, error_type, error_message, error_code, stack_trace, context_data, post_id, user_id, ip_address, user_agent, request_uri, severity, resolved, resolution_notes, created_at, resolved_at FROM {$table}";
            if ($where !== '') {
                $query .= ' ' . $where;
            }
            $query .= ' ORDER BY created_at DESC LIMIT %d';
            $params[] = $limit;

            $prepared = $wpdb->prepare($query, $params);
            $results = $wpdb->get_results($prepared, ARRAY_A);

            $logs = array();
            foreach ($results as $row) {
                $context = array();
                if (!empty($row['context_data'])) {
                    $decoded = json_decode($row['context_data'], true);
                    if (is_array($decoded)) {
                        $context = $decoded;
                    }
                }

                $logs[] = array(
                    'id' => isset($row['id']) ? (int) $row['id'] : 0,
                    'error_type' => isset($row['error_type']) ? (string) $row['error_type'] : '',
                    'error_message' => isset($row['error_message']) ? (string) $row['error_message'] : '',
                    'error_code' => isset($row['error_code']) ? (string) $row['error_code'] : '',
                    'stack_trace' => isset($row['stack_trace']) ? (string) $row['stack_trace'] : '',
                    'context' => $context,
                    'post_id' => isset($row['post_id']) ? (int) $row['post_id'] : 0,
                    'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                    'ip_address' => isset($row['ip_address']) ? (string) $row['ip_address'] : '',
                    'user_agent' => isset($row['user_agent']) ? (string) $row['user_agent'] : '',
                    'request_uri' => isset($row['request_uri']) ? (string) $row['request_uri'] : '',
                    'severity' => isset($row['severity']) ? (string) $row['severity'] : '',
                    'resolved' => !empty($row['resolved']),
                    'resolution_notes' => isset($row['resolution_notes']) ? (string) $row['resolution_notes'] : '',
                    'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
                    'resolved_at' => isset($row['resolved_at']) ? (string) $row['resolved_at'] : '',
                );
            }

            $counts = array('critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0);
            $totals = $wpdb->get_results("SELECT severity, COUNT(*) AS total FROM {$table} GROUP BY severity", ARRAY_A);
            foreach ($totals as $total) {
                $severity_key = isset($total['severity']) ? strtolower((string) $total['severity']) : '';
                if (isset($counts[$severity_key])) {
                    $counts[$severity_key] = (int) $total['total'];
                }
            }

            $open_counts = array('critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0);
            $open_totals = $wpdb->get_results("SELECT severity, COUNT(*) AS total FROM {$table} WHERE resolved = 0 GROUP BY severity", ARRAY_A);
            foreach ($open_totals as $total) {
                $severity_key = isset($total['severity']) ? strtolower((string) $total['severity']) : '';
                if (isset($open_counts[$severity_key])) {
                    $open_counts[$severity_key] = (int) $total['total'];
                }
            }

            wp_send_json_success(array(
                'logs' => $logs,
                'counts' => $counts,
                'open_counts' => $open_counts,
            ));

        } catch (Exception $e) {
            $this->log_error('Failed to load error logs', $e, 'medium');
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clear_error_log() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions to clear error logs.', 'yadore-monetizer'));
            }

            global $wpdb;
            $table = $wpdb->prefix . 'yadore_error_logs';

            if ($this->table_exists($table)) {
                $wpdb->query("DELETE FROM {$table}");
            }

            $this->reset_error_notice_cache();

            wp_send_json_success(array(
                'message' => __('Error logs cleared successfully.', 'yadore-monetizer'),
            ));

        } catch (Exception $e) {
            $this->log_error('Failed to clear error logs', $e, 'medium');
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_get_debug_info() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions to access debug information.', 'yadore-monetizer'));
            }

            $plugin_log = $this->get_debug_log();
            $stack_traces = array();

            global $wpdb;
            $table = $wpdb->prefix . 'yadore_error_logs';
            if ($this->table_exists($table)) {
                $trace_rows = $wpdb->get_results("SELECT id, error_message, severity, stack_trace, created_at FROM {$table} ORDER BY created_at DESC LIMIT 10", ARRAY_A);
                foreach ($trace_rows as $trace_row) {
                    $stack_traces[] = array(
                        'id' => isset($trace_row['id']) ? (int) $trace_row['id'] : 0,
                        'error_message' => isset($trace_row['error_message']) ? (string) $trace_row['error_message'] : '',
                        'severity' => isset($trace_row['severity']) ? (string) $trace_row['severity'] : '',
                        'stack_trace' => isset($trace_row['stack_trace']) ? (string) $trace_row['stack_trace'] : '',
                        'created_at' => isset($trace_row['created_at']) ? (string) $trace_row['created_at'] : '',
                    );
                }
            }

            $wp_debug_excerpt = $this->read_wp_debug_excerpt();

            wp_send_json_success(array(
                'plugin_debug_log' => $plugin_log,
                'stack_traces' => $stack_traces,
                'wp_debug_excerpt' => $wp_debug_excerpt,
            ));

        } catch (Exception $e) {
            $this->log_error('Failed to load debug information', $e, 'medium');
            wp_send_json_error($e->getMessage());
        }
    }

    private function queue_admin_notice($message, $type = 'error') {
        if (!is_string($message) || $message === '') {
            return;
        }

        $allowed_types = array('error', 'warning', 'success', 'info');
        if (!in_array($type, $allowed_types, true)) {
            $type = 'info';
        }

        set_transient(
            'yadore_admin_notice_queue',
            array(
                'type' => $type,
                'message' => wp_strip_all_tags($message),
                'timestamp' => time(),
            ),
            MINUTE_IN_SECONDS * 10
        );
    }

    private function extract_yadore_error_messages($response) {
        if (!is_array($response)) {
            return '';
        }

        if (isset($response['errors']) && is_array($response['errors'])) {
            $messages = array();

            foreach ($response['errors'] as $field => $errors) {
                if (is_array($errors)) {
                    $clean = array();
                    foreach ($errors as $error_message) {
                        $error_message = trim((string) $error_message);
                        if ($error_message !== '') {
                            $clean[] = $error_message;
                        }
                    }

                    if (!empty($clean)) {
                        $messages[] = ucfirst($field) . ': ' . implode(', ', $clean);
                    }
                } elseif (is_string($errors) && $errors !== '') {
                    $messages[] = ucfirst($field) . ': ' . $errors;
                }
            }

            if (!empty($messages)) {
                return implode(' | ', $messages);
            }
        }

        if (isset($response['message']) && is_string($response['message']) && $response['message'] !== '') {
            return $response['message'];
        }

        return '';
    }

    public function plugin_action_links($links) {
        $custom_links = array(
            '<a href="' . admin_url('admin.php?page=yadore-settings') . '">Settings</a>',
            '<a href="' . admin_url('admin.php?page=yadore-monetizer') . '">Dashboard</a>'
        );

        return array_merge($custom_links, $links);
    }

    public function render_overlay() {
        if (!get_option('yadore_overlay_enabled', true) || is_admin() || !is_single()) {
            return;
        }

        try {
            $template_file = YADORE_PLUGIN_DIR . 'templates/overlay-banner.php';
            if (file_exists($template_file)) {
                include $template_file;
            }
        } catch (Exception $e) {
            $this->log_error('Overlay rendering failed', $e);
        }
    }

    // More methods will be implemented in continuation...





    /** Basic sanitize helpers (single authoritative copy) */
    private function sanitize_bool($v){ return $v ? 1 : 0; }
    private function sanitize_text($v){ return sanitize_text_field( (string) $v ); }
    private function sanitize_api_key($value){
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', '', $value);

        // Preserve all characters allowed by the Yadore Publisher API key format (docs.yadore.com)
        if (!preg_match('/^[A-Za-z0-9_\-:.=+\/]+$/', $value)) {
            $value = preg_replace('/[^A-Za-z0-9_\-:.=+\/]/', '', $value);
        }

        return $value;
    }
    private function sanitize_market($value) {
        $value = strtoupper(trim((string) $value));
        if (preg_match('/^[A-Z]{2}$/', $value)) {
            return $value;
        }

        return '';
    }
    private function sanitize_model($v){
        $models = $this->get_supported_gemini_models();
        $v = trim((string)$v);
        $v = preg_replace('/[^a-zA-Z0-9._\-]/', '', $v);
        if ($v === '' || !isset($models[$v])) {
            return $this->get_default_gemini_model();
        }
        return $v;
    }

    private function get_supported_gemini_models() {
        return array(
            'gemini-2.0-flash' => array(
                'label' => __('Gemini 2.0 Flash - Fastest', 'yadore-monetizer'),
            ),
            'gemini-2.0-flash-lite' => array(
                'label' => __('Gemini 2.0 Flash Lite - Efficient', 'yadore-monetizer'),
            ),
            'gemini-2.0-pro-exp' => array(
                'label' => __('Gemini 2.0 Pro (Experimental) - Highest quality', 'yadore-monetizer'),
            ),
            'gemini-2.0-flash-exp' => array(
                'label' => __('Gemini 2.0 Flash (Experimental) - Latest features', 'yadore-monetizer'),
            ),
            'gemini-1.5-pro' => array(
                'label' => __('Gemini 1.5 Pro - Most capable', 'yadore-monetizer'),
            ),
            'gemini-1.5-flash' => array(
                'label' => __('Gemini 1.5 Flash - Balanced', 'yadore-monetizer'),
            ),
            'gemini-1.5-flash-8b' => array(
                'label' => __('Gemini 1.5 Flash 8B - Lightweight', 'yadore-monetizer'),
            ),
        );
    }

    private function get_default_gemini_model() {
        $models = $this->get_supported_gemini_models();
        $first = array_key_first($models);
        return $first ?: 'gemini-2.0-flash';
    }
}

// Plugin Update Checker laden (GitHub) - optional
$update_checker_file = YADORE_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';

if (file_exists($update_checker_file)) {
    require_once $update_checker_file;

    if (class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/matthesv/yadore-monetizer-pro/',
            __FILE__,
            'yadore-monetizer-pro'
        );
        $myUpdateChecker->setBranch('main');
    }
} else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Yadore Monetizer Pro: Plugin update checker file not found at ' . $update_checker_file);
    }
}
// Initialize the plugin
new YadoreMonetizer();
?>