<?php
/*
Plugin Name: Yadore Monetizer Pro
Description: Professional Affiliate Marketing Plugin with Complete Feature Set
Version: 2.9.5
Author: Yadore AI
Text Domain: yadore-monetizer
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
*/

if (!defined('ABSPATH')) { exit; }

define('YADORE_PLUGIN_VERSION', '2.9.5');
define('YADORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YADORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YADORE_PLUGIN_FILE', __FILE__);

class YadoreMonetizer {

    private $debug_log = [];
    private $error_log = [];
    private $api_cache = [];

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
            add_action('wp_ajax_yadore_get_post_stats', array($this, 'ajax_get_post_stats'));
            add_action('wp_ajax_yadore_scan_single_post', array($this, 'ajax_scan_single_post'));
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

            $this->log('Plugin v2.9.5 initialized successfully with complete feature set', 'info');

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
            'yadore_ai_prompt' => 'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.',
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
            $candidate = strtolower(substr($locale, 0, 2));
            if (preg_match('/^[a-z]{2}$/', $candidate)) {
                return $candidate;
            }
        }

        return 'de';
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
                    update_option($option, max(0, intval($flat_post[$option])));
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
            // Only load on our admin pages
            if (strpos($hook, 'yadore') === false) {
                return;
            }

            // Admin CSS
            wp_enqueue_style(
                'yadore-admin-css',
                YADORE_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                YADORE_PLUGIN_VERSION
            );

            // Admin JavaScript
            wp_enqueue_script(
                'yadore-admin-js',
                YADORE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-util'),
                YADORE_PLUGIN_VERSION,
                true
            );

            // v2.7: Chart.js for analytics
            wp_enqueue_script(
                'yadore-charts',
                YADORE_PLUGIN_URL . 'assets/js/chart.min.js',
                array(),
                '3.9.1',
                true
            );

            // Localize script for AJAX
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

            $keyword = 'smartphone';
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

        $keyword = '';

        if ($post_id > 0) {
            $posts_table = $wpdb->prefix . 'yadore_post_keywords';
            $post_data = $wpdb->get_row($wpdb->prepare(
                "SELECT primary_keyword, fallback_keyword FROM $posts_table WHERE post_id = %d AND product_validated = 1",
                $post_id
            ));

            if ($post_data) {
                if (!empty($post_data->primary_keyword)) {
                    $keyword = $post_data->primary_keyword;
                } elseif (!empty($post_data->fallback_keyword)) {
                    $keyword = $post_data->fallback_keyword;
                }
            }
        }

        if (!empty($keyword)) {
            $filtered_keyword = apply_filters('yadore_resolved_keyword', $keyword, $post_id, $page_content);
            return sanitize_text_field($filtered_keyword);
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

        if (get_option('yadore_ai_enabled', false)) {
            $ai_result = $this->call_gemini_api($post_title, $combined_content, true, $post_id);

            if (is_array($ai_result)) {
                if (isset($ai_result['keyword']) && trim((string) $ai_result['keyword']) !== '') {
                    $ai_keyword = sanitize_text_field((string) $ai_result['keyword']);
                    $filtered_ai_keyword = apply_filters('yadore_resolved_keyword', $ai_keyword, $post_id, $page_content);
                    return sanitize_text_field($filtered_ai_keyword);
                }
            } elseif (is_string($ai_result) && trim($ai_result) !== '') {
                // Backward compatibility for cached string responses
                $ai_keyword = sanitize_text_field($ai_result);
                $filtered_ai_keyword = apply_filters('yadore_resolved_keyword', $ai_keyword, $post_id, $page_content);
                return sanitize_text_field($filtered_ai_keyword);
            }
        }

        $heuristic_keyword = $this->extract_keyword_from_text($combined_content, $post_title);
        $resolved = apply_filters('yadore_resolved_keyword', $heuristic_keyword, $post_id, $page_content);

        return sanitize_text_field($resolved);
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

    private function get_products($keyword, $limit = 6, $post_id = 0) {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return array();
        }

        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 6;
        }
        $limit = min(50, $limit);

        $market = $this->sanitize_market(get_option('yadore_market', ''));
        if ($market === '') {
            $market = $this->get_default_market();
        }

        $filtered_market = apply_filters('yadore_products_country', $market, $keyword, $limit, $post_id);
        if (is_string($filtered_market) && $filtered_market !== '') {
            $market = $filtered_market;
        }

        $filtered_market = apply_filters('yadore_products_market', $market, $keyword, $limit, $post_id);
        if (is_string($filtered_market) && $filtered_market !== '') {
            $market = $filtered_market;
        }

        $market = $this->sanitize_market($market);
        if ($market === '') {
            $market = $this->get_default_market();
        }

        $cache_key = 'yadore_products_' . md5(strtolower($keyword) . '|' . $limit . '|' . $market);
        if (isset($this->api_cache[$cache_key])) {
            return $this->api_cache[$cache_key];
        }

        if (!get_option('yadore_debug_mode', false)) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                $this->api_cache[$cache_key] = $cached;
                return $cached;
            }
        }

        $api_key = trim((string) get_option('yadore_api_key'));
        if ($api_key === '') {
            $this->log_error('Yadore API key not configured for product request', null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));
            return array();
        }

        $endpoint = 'https://api.yadore.com/v2/offer';

        $request_params = array(
            'keyword' => $keyword,
            'limit' => $limit,
        );

        if ($market !== '') {
            $request_params['market'] = $market;
        }

        $request_params = apply_filters('yadore_products_request_body', $request_params, $keyword, $limit, $post_id);

        if (!is_array($request_params)) {
            $request_params = array();
        }

        $request_params = array_filter(
            $request_params,
            static function ($value) {
                return $value !== null && $value !== '';
            }
        );

        $request_url = add_query_arg($request_params, $endpoint);

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
                'API-Key' => $api_key,
            ),
            'timeout' => 20,
        );

        $args = apply_filters('yadore_products_request_args', $args, $keyword, $limit, $post_id);

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
            ));

            $this->log_error('Yadore API request failed: ' . $response->get_error_message(), null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return array();
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            $error_details = $this->extract_yadore_error_messages($decoded);
            $log_context = array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'status' => $status,
                'response' => $decoded,
                'duration_ms' => $duration_ms,
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
                'response' => $decoded,
                'url' => $request_url,
                'status' => $status,
            ));

            return array();
        }

        if (!is_array($decoded)) {
            $this->log_api_call('yadore', $endpoint, 'error', array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'status' => $status,
                'response' => $body,
                'duration_ms' => $duration_ms,
            ));

            $this->log_error('Yadore API returned an invalid response format.', null, 'high', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return array();
        }

        if (isset($decoded['success']) && $decoded['success'] === false) {
            $message = isset($decoded['message']) ? $decoded['message'] : __('Unknown API error', 'yadore-monetizer');

            $this->log_api_call('yadore', $endpoint, 'error', array(
                'keyword' => $keyword,
                'limit' => $limit,
                'url' => $request_url,
                'response' => $decoded,
                'duration_ms' => $duration_ms,
            ));

            $this->log_error('Yadore API error: ' . $message, null, 'medium', array(
                'keyword' => $keyword,
                'post_id' => $post_id,
            ));

            return array();
        }

        $products = array();

        $possible_collections = array(
            'data',
            'products',
            'offers',
            'items',
        );

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

        $this->log_api_call('yadore', $endpoint, 'success', array(
            'keyword' => $keyword,
            'limit' => $limit,
            'url' => $request_url,
            'count' => count($products),
            'duration_ms' => $duration_ms,
        ));

        $cache_duration = intval(get_option('yadore_cache_duration', 3600));
        if ($cache_duration > 0) {
            set_transient($cache_key, $products, $cache_duration);
        }

        $this->api_cache[$cache_key] = $products;

        return $products;
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
                ),
                'responseMimeType' => 'application/json',
                'responseSchema' => array(
                    'type' => 'object',
                    'properties' => array(
                        'keyword' => array(
                            'type' => 'string',
                            'description' => 'Primary product keyword describing the best affiliate opportunity.',
                        ),
                        'confidence' => array(
                            'type' => 'number',
                            'minimum' => 0,
                            'maximum' => 1,
                            'description' => 'Confidence score between 0 and 1 for the extracted keyword.',
                        ),
                        'rationale' => array(
                            'type' => 'string',
                            'description' => 'Optional short explanation for the keyword choice.',
                        ),
                    ),
                    'required' => array('keyword'),
                    'additionalProperties' => false,
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

            $structured_text = '';
            if (!empty($decoded['candidates']) && is_array($decoded['candidates'])) {
                foreach ($decoded['candidates'] as $candidate) {
                    if (!empty($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                        foreach ($candidate['content']['parts'] as $part) {
                            if (!empty($part['text'])) {
                                $structured_text = trim((string) $part['text']);
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($structured_text === '') {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $decoded,
                ));
                return array('error' => __('Gemini API returned an empty response.', 'yadore-monetizer'));
            }

            $structured_data = json_decode($structured_text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $structured_text,
                    'error' => json_last_error_msg(),
                ));
                return array('error' => __('Gemini API returned data that could not be parsed as JSON.', 'yadore-monetizer'));
            }

            if (!is_array($structured_data) || empty($structured_data['keyword'])) {
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
            if (empty($atts['keyword'])) {
                return '<div class="yadore-error">Please specify a keyword for the product search.</div>';
            }

            $use_cache = $atts['cache'] === 'true';
            $products = $this->get_products($atts['keyword'], intval($atts['limit']));

            if (empty($products)) {
                return '<div class="yadore-no-results">No products found for "' . esc_html($atts['keyword']) . '".</div>';
            }

            $template_file = YADORE_PLUGIN_DIR . "templates/products-{$atts['format']}.php";

            if (!file_exists($template_file)) {
                $template_file = YADORE_PLUGIN_DIR . "templates/products-grid.php";
            }

            ob_start();
            $offers = $products; // For template compatibility
            $additional_classes = !empty($atts['class']) ? ' ' . sanitize_html_class($atts['class']) : '';
            include $template_file;
            $output = ob_get_clean();

            // v2.7: Track shortcode usage
            $this->track_shortcode_usage($atts['keyword'], $atts['limit'], $atts['format']);

            return $output;

        } catch (Exception $e) {
            $this->log_error('Shortcode rendering failed', $e);
            return '<div class="yadore-error">Error loading products. Please try again later.</div>';
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

        global $wpdb;
        $error_logs_table = $wpdb->prefix . 'yadore_error_logs';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $error_logs_table));

        if ($table_exists === $error_logs_table) {
            $recent_error = $wpdb->get_row(
                "SELECT error_message, severity, created_at FROM {$error_logs_table} WHERE resolved = 0 AND severity IN ('high','critical') ORDER BY created_at DESC LIMIT 1"
            );

            if ($recent_error && !empty($recent_error->error_message)) {
                $severity_label = strtoupper($recent_error->severity ?? '');
                $timestamp = $recent_error->created_at ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($recent_error->created_at))) : '';
                $message = esc_html($recent_error->error_message);

                echo '<div class="notice notice-error"><p><strong>' . esc_html__('Yadore Monetizer Pro Error', 'yadore-monetizer') . '</strong> ' . $message;
                if ($timestamp !== '') {
                    echo ' <em>(' . esc_html($severity_label) . ' &ndash; ' . $timestamp . ')</em>';
                }
                echo '</p></div>';
            }
        }

        $queued_notice = get_transient('yadore_admin_notice_queue');
        if (is_array($queued_notice) && !empty($queued_notice['message'])) {
            $type = isset($queued_notice['type']) && in_array($queued_notice['type'], array('error', 'warning', 'success', 'info'), true)
                ? $queued_notice['type']
                : 'info';

            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($type),
                esc_html($queued_notice['message'])
            );

            delete_transient('yadore_admin_notice_queue');
        }
    }

    public function show_initialization_error() {
        echo '<div class="notice notice-error"><p><strong>Yadore Monetizer Pro Error:</strong> Plugin initialization failed. Please check the debug log for details.</p></div>';
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
        $value = strtolower(trim((string) $value));
        if (preg_match('/^[a-z]{2}$/', $value)) {
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