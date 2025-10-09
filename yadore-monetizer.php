<?php
/*
Plugin Name: Yadore Monetizer Pro
Description: Professional Affiliate Marketing Plugin with Complete Feature Set
Version: 3.47.1
Author: Matthes Vogel
Text Domain: yadore-monetizer
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
*/

if (!defined('ABSPATH')) { exit; }

define('YADORE_PLUGIN_VERSION', '3.47.1');
define('YADORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YADORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YADORE_PLUGIN_FILE', __FILE__);

class YadoreMonetizer {

    private static $instance = null;

    public const PREVIOUS_DEFAULT_AI_PROMPT = 'Analyze the title and content to find the most relevant purchase-ready product keyword (brand + model when available). Provide up to three alternate keywords for backup searches and return JSON that matches the schema (keyword, alternate_keywords, confidence, rationale).';

    public const DEFAULT_AI_PROMPT = "You are an affiliate marketing assistant. Analyze the provided blog post details and return JSON matching the schema (keyword, alternate_keywords, confidence, rationale).\n\nTitle: {title}\n\nContent:\n{content}\n\nFocus on purchase-ready product keywords (brand + model when available) and provide up to three alternates for backup searches.";

    public const LEGACY_AI_PROMPT = 'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.';

    private $debug_log = [];
    private $error_log = [];
    private $api_cache = [];
    private $keyword_candidate_cache = [];
    private $last_product_keyword = '';
    private $gemini_json_debug = array();
    private $latest_error_notice = null;
    private $latest_error_notice_checked = false;
    private $table_exists_cache = array();

    public function __construct() {
        self::$instance = $this;
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
            add_action('wp_ajax_yadore_track_product_click', array($this, 'ajax_track_product_click'));
            add_action('wp_ajax_nopriv_yadore_track_product_click', array($this, 'ajax_track_product_click'));
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
            add_action('wp_ajax_yadore_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
            add_action('wp_ajax_yadore_get_analytics_data', array($this, 'ajax_get_analytics_data'));
            add_action('wp_ajax_yadore_test_system_component', array($this, 'ajax_test_system_component'));
            add_action('wp_ajax_yadore_export_data', array($this, 'ajax_export_data'));
            add_action('wp_ajax_yadore_schedule_export', array($this, 'ajax_schedule_export'));
            add_action('wp_ajax_yadore_import_data', array($this, 'ajax_import_data'));
            add_action('wp_ajax_yadore_clear_cache', array($this, 'ajax_clear_cache'));
            add_action('wp_ajax_yadore_restore_default_templates', array($this, 'ajax_restore_default_templates'));
            add_action('wp_ajax_yadore_get_tool_stats', array($this, 'ajax_get_tool_stats'));
            add_action('wp_ajax_yadore_test_connectivity', array($this, 'ajax_test_connectivity'));
            add_action('wp_ajax_yadore_check_database', array($this, 'ajax_check_database'));
            add_action('wp_ajax_yadore_test_performance', array($this, 'ajax_test_performance'));
            add_action('wp_ajax_yadore_analyze_cache', array($this, 'ajax_analyze_cache'));
            add_action('wp_ajax_yadore_optimize_cache', array($this, 'ajax_optimize_cache'));
            add_action('wp_ajax_yadore_optimize_database', array($this, 'ajax_optimize_database'));
            add_action('wp_ajax_yadore_cleanup_old_data', array($this, 'ajax_cleanup_old_data'));
            add_action('wp_ajax_yadore_archive_logs', array($this, 'ajax_archive_logs'));
            add_action('wp_ajax_yadore_clear_old_logs', array($this, 'ajax_clear_old_logs'));
            add_action('wp_ajax_yadore_system_cleanup', array($this, 'ajax_system_cleanup'));
            add_action('wp_ajax_yadore_schedule_cleanup', array($this, 'ajax_schedule_cleanup'));
            add_action('wp_ajax_yadore_reset_settings', array($this, 'ajax_reset_settings'));
            add_action('wp_ajax_yadore_clear_all_data', array($this, 'ajax_clear_all_data'));
            add_action('wp_ajax_yadore_factory_reset', array($this, 'ajax_factory_reset'));
            add_action('wp_ajax_yadore_export_config', array($this, 'ajax_export_config'));
            add_action('wp_ajax_yadore_clone_settings', array($this, 'ajax_clone_settings'));
            add_action('wp_ajax_yadore_auto_optimize', array($this, 'ajax_auto_optimize'));
            add_action('wp_ajax_yadore_analyze_keywords', array($this, 'ajax_analyze_keywords'));

            // Content integration
            add_shortcode('yadore_products', array($this, 'shortcode_products'));
            add_filter('the_content', array($this, 'auto_inject_products'), 20);

            // Post save hook for auto-scanning
            add_action('save_post', array($this, 'auto_scan_post_on_save'), 10, 2);
            add_action('save_post', array($this, 'save_keyword_meta_box'), 20, 2);

            // Admin notices for errors
            add_action('admin_notices', array($this, 'admin_notices'));

            // Footer hook for overlay
            add_action('wp_footer', array($this, 'render_overlay'));

            // Template management
            add_action('add_meta_boxes', array($this, 'register_template_meta_boxes'));
            add_action('add_meta_boxes', array($this, 'register_keyword_meta_box'), 20, 2);
            add_action('save_post_yadore_template', array($this, 'save_template_meta'));
            add_filter('manage_yadore_template_posts_columns', array($this, 'register_template_columns'));
            add_action('manage_yadore_template_posts_custom_column', array($this, 'render_template_columns'), 10, 2);

            // Maintenance
            add_action('yadore_cleanup_logs', array($this, 'cleanup_logs'));

            // Settings link on plugins page
            add_filter('plugin_action_links_' . plugin_basename(YADORE_PLUGIN_FILE), array($this, 'plugin_action_links'));

            // v2.7: Advanced hooks
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
            add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 999);
            add_action('yadore_run_scheduled_export', array($this, 'run_scheduled_export'), 10, 1);
            add_action('yadore_run_system_cleanup', array($this, 'run_system_cleanup'), 10, 0);

            $this->log(sprintf('Plugin v%s initialized successfully with complete feature set', YADORE_PLUGIN_VERSION), 'info');

        } catch (Exception $e) {
            $this->log_error('Plugin initialization failed', $e, 'critical');
            add_action('admin_notices', array($this, 'show_initialization_error'));
        }
    }

    public static function get_instance() {
        return self::$instance;
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
            $this->register_custom_post_types();
            $this->setup_initial_data();

            // v2.7: Advanced activation procedures
            $this->setup_advanced_features();

            $this->reset_cache_metrics();

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
        $default_colors = $this->get_default_template_colors();

        return array(
            'yadore_api_key' => '',
            'yadore_market' => $this->get_default_market(),
            'yadore_overlay_enabled' => 1,
            'yadore_auto_detection' => 1,
            'yadore_shortcode_enabled' => 1,
            'yadore_cache_duration' => 3600,
            'yadore_debug_mode' => 0,
            'yadore_ai_enabled' => 0,
            'yadore_gemini_api_key' => '',
            'yadore_gemini_model' => $this->get_default_gemini_model(),
            'yadore_ai_cache_duration' => 157680000,
            'yadore_ai_prompt' => self::DEFAULT_AI_PROMPT,
            'yadore_ai_temperature' => '0.3',
            'yadore_ai_max_tokens' => 2000,
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
            'yadore_overlay_template' => 'default-overlay',
            'yadore_auto_injection_template' => 'default-inline',
            'yadore_default_shortcode_template' => 'default-grid',
            'yadore_shortcode_colors' => $default_colors['shortcode'],
            'yadore_overlay_colors' => $default_colors['overlay'],
            'yadore_performance_mode' => 0,
            'yadore_analytics_enabled' => 1,
            'yadore_export_enabled' => 1,
            'yadore_backup_enabled' => 0,
            'yadore_multisite_sync' => 0,
        );
    }

    private function get_default_cache_metrics() {
        $timestamp = function_exists('current_time') ? current_time('timestamp') : time();

        return array(
            'products' => array(
                'hits' => 0,
                'misses' => 0,
            ),
            'ai' => array(
                'hits' => 0,
                'misses' => 0,
            ),
            'analytics' => array(
                'hits' => 0,
                'misses' => 0,
            ),
            'last_cleared' => $timestamp,
            'last_updated' => $timestamp,
        );
    }

    private function get_default_template_colors() {
        return array(
            'shortcode' => array(
                'primary' => '#3498DB',
                'button_text' => '#FFFFFF',
                'accent' => '#27AE60',
                'text' => '#2C3E50',
                'muted' => '#7F8C8D',
                'border' => '#E9ECEF',
                'background' => '#FFFFFF',
                'card_background' => '#FFFFFF',
                'placeholder' => '#ECF0F1',
                'placeholder_text' => '#95A5A6',
                'badge' => '#FF6B6B',
                'badge_text' => '#FFFFFF',
            ),
            'overlay' => array(
                'primary' => '#3498DB',
                'button_text' => '#FFFFFF',
                'accent' => '#27AE60',
                'text' => '#2C3E50',
                'muted' => '#7F8C8D',
                'border' => '#E9ECEF',
                'background' => '#F9F9F9',
                'card_background' => '#F9F9F9',
                'placeholder' => '#ECF0F1',
                'placeholder_text' => '#95A5A6',
                'badge' => '#3498DB',
                'badge_text' => '#FFFFFF',
            ),
        );
    }

    public function get_color_palette() {
        return array(
            '#3498DB' => __('Ocean Blue', 'yadore-monetizer'),
            '#2980B9' => __('Deep Blue', 'yadore-monetizer'),
            '#1ABC9C' => __('Teal Breeze', 'yadore-monetizer'),
            '#2ECC71' => __('Fresh Green', 'yadore-monetizer'),
            '#27AE60' => __('Emerald', 'yadore-monetizer'),
            '#F1C40F' => __('Golden Glow', 'yadore-monetizer'),
            '#E67E22' => __('Sunset Orange', 'yadore-monetizer'),
            '#E74C3C' => __('Crimson Red', 'yadore-monetizer'),
            '#9B59B6' => __('Royal Purple', 'yadore-monetizer'),
            '#8E44AD' => __('Plum', 'yadore-monetizer'),
            '#FF6B6B' => __('Flamingo', 'yadore-monetizer'),
            '#ECF0F1' => __('Cloud', 'yadore-monetizer'),
            '#BDC3C7' => __('Silver', 'yadore-monetizer'),
            '#7F8C8D' => __('Slate Gray', 'yadore-monetizer'),
            '#2C3E50' => __('Midnight', 'yadore-monetizer'),
            '#1D2731' => __('Deep Slate', 'yadore-monetizer'),
            '#FFFFFF' => __('Pure White', 'yadore-monetizer'),
            '#000000' => __('Jet Black', 'yadore-monetizer'),
        );
    }

    public function get_template_colors($type = 'shortcode') {
        $type = $type === 'overlay' ? 'overlay' : 'shortcode';
        $option_name = $type === 'overlay' ? 'yadore_overlay_colors' : 'yadore_shortcode_colors';
        $stored = get_option($option_name, array());

        if (!is_array($stored)) {
            $stored = array();
        }

        return $this->sanitize_color_settings($stored, $type);
    }

    public function get_template_color_style($type = 'shortcode') {
        $colors = $this->get_template_colors($type);

        $primary = $colors['primary'];
        $card_background = $colors['card_background'];
        $border = $colors['border'];

        $style_map = array(
            '--yadore-primary' => $primary,
            '--yadore-primary-light' => $this->adjust_color_brightness($primary, 18),
            '--yadore-primary-dark' => $this->adjust_color_brightness($primary, -15),
            '--yadore-primary-darker' => $this->adjust_color_brightness($primary, -30),
            '--yadore-primary-contrast' => $colors['button_text'],
            '--yadore-primary-shadow' => $this->hex_to_rgba($primary, 0.35),
            '--yadore-accent' => $colors['accent'],
            '--yadore-text' => $colors['text'],
            '--yadore-muted' => $colors['muted'],
            '--yadore-border' => $border,
            '--yadore-border-strong' => $this->adjust_color_brightness($border, -10),
            '--yadore-background' => $colors['background'],
            '--yadore-card-bg' => $card_background,
            '--yadore-card-bg-muted' => $this->adjust_color_brightness($card_background, 10),
            '--yadore-placeholder' => $colors['placeholder'],
            '--yadore-placeholder-text' => $colors['placeholder_text'],
            '--yadore-badge' => $colors['badge'],
            '--yadore-badge-text' => $colors['badge_text'],
            '--yadore-badge-rgba' => $this->hex_to_rgba($colors['badge'], 0.95),
            '--yadore-badge-shadow' => $this->hex_to_rgba($colors['badge'], 0.3),
            '--yadore-badge-light' => $this->adjust_color_brightness($colors['badge'], 12),
            '--yadore-badge-dark' => $this->adjust_color_brightness($colors['badge'], -12),
        );

        $style = '';

        foreach ($style_map as $variable => $value) {
            if ($value !== '') {
                $style .= $variable . ':' . $value . ';';
            }
        }

        return $style;
    }

    private function sanitize_color_settings($colors, $type = 'shortcode') {
        $defaults = $this->get_default_template_colors();
        $type = $type === 'overlay' ? 'overlay' : 'shortcode';

        $sanitized = $defaults[$type];

        foreach ($sanitized as $key => $default) {
            if (isset($colors[$key])) {
                $value = $this->sanitize_color_value($colors[$key]);
                if ($value !== '') {
                    $sanitized[$key] = $value;
                }
            }
        }

        return $sanitized;
    }

    private function sanitize_color_value($color) {
        $color = trim((string) $color);

        if ($color === '') {
            return '';
        }

        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        $hex = substr($color, 1);
        $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6) {
            return '';
        }

        return '#' . strtoupper($hex);
    }

    private function adjust_color_brightness($hex, $steps) {
        $color = $this->sanitize_color_value($hex);

        if ($color === '') {
            return '';
        }

        $steps = (int) $steps;
        $steps = max(-255, min(255, $steps));

        $hex = substr($color, 1);
        $components = str_split($hex, 2);
        $adjusted = '#';

        foreach ($components as $component) {
            $value = hexdec($component);
            $value = max(0, min(255, $value + $steps));
            $adjusted .= strtoupper(str_pad(dechex($value), 2, '0', STR_PAD_LEFT));
        }

        return $adjusted;
    }

    private function hex_to_rgba($hex, $alpha = 1.0) {
        $color = $this->sanitize_color_value($hex);

        if ($color === '') {
            return '';
        }

        $alpha = max(0, min(1, (float) $alpha));
        $hex = substr($color, 1);

        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $alpha_string = rtrim(rtrim(sprintf('%.3f', $alpha), '0'), '.');

        return sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, $alpha_string);
    }

    private function get_contrast_color($hex) {
        $color = $this->sanitize_color_value($hex);

        if ($color === '') {
            return '#FFFFFF';
        }

        $hex = substr($color, 1);
        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $red + 0.587 * $green + 0.114 * $blue) / 255;

        return $luminance > 0.55 ? '#000000' : '#FFFFFF';
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

            $stored_version = (string) get_option('yadore_plugin_version', '');
            if ($stored_version !== YADORE_PLUGIN_VERSION) {
                $this->maybe_upgrade_database($stored_version);
                update_option('yadore_plugin_version', YADORE_PLUGIN_VERSION);
            }

            $legacy_prompt = self::LEGACY_AI_PROMPT;
            $previous_default_prompt = self::PREVIOUS_DEFAULT_AI_PROMPT;
            $stored_prompt = get_option('yadore_ai_prompt', '');
            if ($stored_prompt === $legacy_prompt || $stored_prompt === $previous_default_prompt) {
                update_option('yadore_ai_prompt', self::DEFAULT_AI_PROMPT);
            }

            $stored_max_tokens = intval(get_option('yadore_ai_max_tokens', 0));
            if ($stored_max_tokens === 50 || $stored_max_tokens <= 0) {
                update_option('yadore_ai_max_tokens', 2000);
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

            if (false === get_option('yadore_cache_metrics', false)) {
                add_option('yadore_cache_metrics', $this->get_default_cache_metrics(), '', 'no');
            }

            $this->ensure_default_templates_editable();

        } catch (Exception $e) {
            $this->log_error('Failed to setup initial data', $e);
        }
    }

    private function get_default_template_definitions() {
        return array(
            'default-grid' => array(
                'slug' => 'default-grid-template',
                'title' => __('Product Grid (Editable)', 'yadore-monetizer'),
                'type' => 'shortcode',
                'option' => 'yadore_default_shortcode_template',
                'option_default' => 'default-grid',
                'content' => <<<'HTML'
<div class="yadore-products-grid" data-format="grid">
    [yadore_product_loop]
    <div class="yadore-product-card" data-offer-id="{{id}}" data-click-url="{{click_url}}" data-yadore-click="{{id}}" role="link" tabindex="0">
        <div class="product-image">
            {{image_tag}}
            <div class="yadore-product-image-placeholder" aria-hidden="true">📦</div>
            <div class="product-badge">{{promo_text}}</div>
        </div>
        <div class="product-content">
            <h3 class="product-title">{{title}}</h3>
            <div class="product-price-section">
                <div class="product-price">
                    <span class="price-amount">{{price_amount}}</span>
                    <span class="price-currency">{{price_currency}}</span>
                </div>
            </div>
            <div class="product-merchant">
                <span class="merchant-name">{{merchant_name}}</span>
            </div>
            <a href="{{click_url}}" class="product-cta-button" target="_blank" rel="nofollow noopener" data-yadore-click="{{id}}">
                {{button_label}}
            </a>
        </div>
    </div>
    [/yadore_product_loop]
</div>
HTML
            ),
            'default-list' => array(
                'slug' => 'default-list-template',
                'title' => __('Product List (Editable)', 'yadore-monetizer'),
                'type' => 'shortcode',
                'option' => null,
                'option_default' => 'default-list',
                'content' => <<<'HTML'
<div class="yadore-products-list" data-format="list">
    [yadore_product_loop]
    <div class="yadore-product-item" data-offer-id="{{id}}" data-click-url="{{click_url}}" data-yadore-click="{{id}}" role="link" tabindex="0">
        <div class="product-image">
            {{image_tag}}
            <div class="yadore-product-image-placeholder" aria-hidden="true">📦</div>
        </div>
        <div class="product-details">
            <h3 class="product-title">{{title}}</h3>
            <p class="product-description">{{description}}</p>
        </div>
        <div class="product-pricing">
            <div class="price-main">
                <span class="list-price-amount">{{price_amount}}</span>
                <span class="list-price-currency">{{price_currency}}</span>
            </div>
            <div class="merchant-info">Verfügbar bei {{merchant_name}}</div>
        </div>
        <div class="product-action">
            <a href="{{click_url}}" class="list-cta-button" target="_blank" rel="nofollow noopener" data-yadore-click="{{id}}">
                {{button_label}}
            </a>
        </div>
    </div>
    [/yadore_product_loop]
</div>
HTML
            ),
            'default-inline' => array(
                'slug' => 'default-inline-template',
                'title' => __('Inline Highlight (Editable)', 'yadore-monetizer'),
                'type' => 'shortcode',
                'option' => 'yadore_auto_injection_template',
                'option_default' => 'default-inline',
                'content' => <<<'HTML'
<div class="yadore-products-inline" data-format="inline">
    <div class="inline-header">
        <h3>Empfehlung</h3>
        <div class="inline-subtitle">Sorgfältig ausgewähltes Angebot zu diesem Beitrag</div>
    </div>
    <div class="inline-products">
        [yadore_product_loop]
        <div class="inline-product" data-item-index="{{index}}" data-offer-id="{{id}}" data-click-url="{{click_url}}" data-yadore-click="{{id}}">
            <div class="inline-image">
                {{image_tag}}
                <div class="yadore-product-image-placeholder" aria-hidden="true">📦</div>
            </div>
            <div class="inline-details">
                <h4 class="inline-title">{{title}}</h4>
                <div class="inline-price-row">
                    <div class="inline-price">
                        <span class="inline-price-amount">{{price_amount}}</span>
                        <span class="inline-price-currency">{{price_currency}}</span>
                    </div>
                </div>
                <div class="inline-merchant">{{merchant_name}}</div>
                <a href="{{click_url}}" class="inline-cta" target="_blank" rel="nofollow noopener" data-yadore-click="{{id}}">
                    {{button_label}}
                </a>
            </div>
        </div>
        [/yadore_product_loop]
    </div>
    <div class="inline-disclaimer">
        <small>Als Affiliate-Partner verdienen wir ggf. an qualifizierten Käufen.</small>
    </div>
</div>
HTML
            ),
            'default-overlay' => array(
                'slug' => 'default-overlay-template',
                'title' => __('Modern Overlay (Editable)', 'yadore-monetizer'),
                'type' => 'overlay',
                'option' => 'yadore_overlay_template',
                'option_default' => 'default-overlay',
                'content' => <<<'HTML'
<div class="overlay-products">
    [yadore_product_loop]
    <div class="overlay-product" data-product-id="{{id}}" data-click-url="{{click_url}}" data-yadore-click="{{id}}" role="link" tabindex="0">
        <div class="overlay-product-image">
            {{image_tag}}
            <div class="overlay-product-image-placeholder" aria-hidden="true">📦</div>
            <div class="overlay-product-badge">{{promo_text}}</div>
        </div>
        <div class="overlay-product-content">
            <h4 class="overlay-product-title">{{title}}</h4>
            <div class="overlay-product-price">
                <span class="overlay-price-amount">{{price_amount}}</span>
                <span class="overlay-price-currency">{{price_currency}}</span>
            </div>
            <div class="overlay-product-merchant">
                <span class="overlay-merchant-name">{{merchant_name}}</span>
            </div>
            <a href="{{click_url}}" class="overlay-product-button" target="_blank" rel="nofollow noopener" data-yadore-click="{{id}}">
                {{button_label}}
            </a>
        </div>
    </div>
    [/yadore_product_loop]
</div>
HTML
            ),
        );
    }

    private function ensure_default_templates_editable() {
        try {
            if (!post_type_exists('yadore_template')) {
                $this->register_custom_post_types();
            }

            if (!post_type_exists('yadore_template')) {
                return;
            }

            $defaults = $this->get_default_template_definitions();

            foreach ($defaults as $key => $template) {
                $this->sync_default_template($key, $template, false, false);
            }
        } catch (Exception $e) {
            $this->log_error('Failed to ensure editable templates', $e);
        }
    }

    private function sync_default_template($key, array $template, $update_content = false, $force_option_reset = false) {
        $result = array(
            'post' => null,
            'created' => false,
            'updated' => false,
            'option_updated' => false,
        );

        $slug = sanitize_title($template['slug'] ?? $key);
        $type = $template['type'] ?? 'shortcode';
        $type = in_array($type, array('overlay', 'shortcode'), true) ? $type : 'shortcode';

        if ($slug === '') {
            return $result;
        }

        $title = isset($template['title']) ? (string) $template['title'] : ucfirst($key);
        $content = isset($template['content']) ? (string) $template['content'] : '';

        $existing = $this->get_template_post_by_slug($slug, $type, true);

        if ($existing instanceof WP_Post && $existing->post_status === 'trash') {
            wp_untrash_post($existing->ID);
            $existing = get_post($existing->ID);
        }

        if (!($existing instanceof WP_Post)) {
            $post_id = wp_insert_post(array(
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'yadore_template',
                'meta_input' => array(
                    '_yadore_template_type' => $type,
                ),
            ), true);

            if (is_wp_error($post_id)) {
                return $result;
            }

            $existing = get_post($post_id);
            if (!($existing instanceof WP_Post)) {
                return $result;
            }

            $result['created'] = true;
        } else {
            if ($update_content) {
                $update_args = array(
                    'ID' => $existing->ID,
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_status' => 'publish',
                );

                if ($existing->post_name !== $slug) {
                    $update_args['post_name'] = $slug;
                }

                $update_result = wp_update_post($update_args, true);
                if (is_wp_error($update_result)) {
                    return $result;
                }

                $existing = get_post($existing->ID);
                if (!($existing instanceof WP_Post)) {
                    return $result;
                }

                $result['updated'] = true;
            } elseif ($existing->post_status !== 'publish') {
                $status_result = wp_update_post(array(
                    'ID' => $existing->ID,
                    'post_status' => 'publish',
                ), true);

                if (!is_wp_error($status_result)) {
                    $existing = get_post($existing->ID);
                }
            }
        }

        if (!($existing instanceof WP_Post)) {
            return $result;
        }

        update_post_meta($existing->ID, '_yadore_template_type', $type);

        $result['post'] = $existing;

        $option_name = $template['option'] ?? '';
        $option_default = $template['option_default'] ?? $key;

        if ($option_name) {
            $storage_key = $this->get_template_storage_key($existing);
            if ($storage_key !== '') {
                $current = get_option($option_name, $option_default);
                $should_update = false;

                if ($force_option_reset) {
                    $should_update = ($current !== $storage_key);
                } else {
                    if ($current === $option_default || $current === '' || $current === $key) {
                        $should_update = ($current !== $storage_key);
                    } elseif (is_string($current) && strpos($current, 'custom:') === 0) {
                        $identifier = substr($current, 7);
                        if ($identifier === (string) $existing->ID || $identifier === $slug) {
                            $should_update = ($current !== $storage_key);
                        }
                    }
                }

                if ($should_update && update_option($option_name, $storage_key)) {
                    $result['option_updated'] = true;
                }
            }
        }

        return $result;
    }

    private function restore_default_templates($force_option_reset = false) {
        $summary = array(
            'created' => 0,
            'updated' => 0,
            'options' => 0,
        );

        if (!post_type_exists('yadore_template')) {
            $this->register_custom_post_types();
        }

        if (!post_type_exists('yadore_template')) {
            return $summary;
        }

        $defaults = $this->get_default_template_definitions();

        foreach ($defaults as $key => $template) {
            $result = $this->sync_default_template($key, $template, true, $force_option_reset);

            if (!is_array($result)) {
                continue;
            }

            if (!empty($result['created'])) {
                $summary['created']++;
            }

            if (!empty($result['updated'])) {
                $summary['updated']++;
            }

            if (!empty($result['option_updated'])) {
                $summary['options']++;
            }
        }

        return $summary;
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
                'yadore_shortcode_enabled',
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

            $template_settings = array(
                'yadore_overlay_template' => array('type' => 'overlay', 'default' => 'default-overlay'),
                'yadore_auto_injection_template' => array('type' => 'shortcode', 'default' => 'default-inline'),
                'yadore_default_shortcode_template' => array('type' => 'shortcode', 'default' => 'default-grid'),
            );

            foreach ($template_settings as $option => $config) {
                if (isset($flat_post[$option])) {
                    $type = $config['type'];
                    $default = $config['default'];
                    $value = $this->sanitize_template_selection($flat_post[$option], $type, $default);
                    update_option($option, $value);
                }
            }

            $color_settings = array(
                'yadore_shortcode_colors' => 'shortcode',
                'yadore_overlay_colors' => 'overlay',
            );

            foreach ($color_settings as $option => $type) {
                if (isset($flat_post[$option]) && is_array($flat_post[$option])) {
                    $raw_colors = wp_unslash($flat_post[$option]);
                    if (!is_array($raw_colors)) {
                        continue;
                    }
                    $sanitized_colors = $this->sanitize_color_settings($raw_colors, $type);
                    update_option($option, $sanitized_colors);
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
            $data['overlay_template_choices'] = $this->get_template_choices('overlay');
            $data['shortcode_template_choices'] = $this->get_template_choices('shortcode');
            $data['shortcode_colors'] = $this->get_template_colors('shortcode');
            $data['overlay_colors'] = $this->get_template_colors('overlay');
            $data['color_palette'] = $this->get_color_palette();
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

            if ($this->table_exists($logs_table)) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$logs_table} WHERE created_at < %s", $log_threshold));
            }

            if ($this->table_exists($error_table)) {
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

            // Post Scanner page (complete)
            add_submenu_page(
                'yadore-monetizer',
                'Post Scanner',
                'Post Scanner',
                'manage_options',
                'yadore-scanner',
                array($this, 'admin_scanner_page')
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

            // v3.12: Design system styleguide
            add_submenu_page(
                'yadore-monetizer',
                'Design System & Styleguide',
                'Styleguide',
                'manage_options',
                'yadore-styleguide',
                array($this, 'admin_styleguide_page')
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

    public function admin_scanner_page() {
        $this->render_admin_page('scanner');
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

    public function admin_styleguide_page() {
        $this->render_admin_page('styleguide');
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
                wp_register_style(
                    'yadore-admin-design-system',
                    YADORE_PLUGIN_URL . 'assets/css/admin-design-system.css',
                    array(),
                    YADORE_PLUGIN_VERSION
                );
                wp_style_add_data('yadore-admin-design-system', 'path', YADORE_PLUGIN_DIR . 'assets/css/admin-design-system.css');

                wp_register_style(
                    'yadore-admin-css',
                    YADORE_PLUGIN_URL . 'assets/css/admin.css',
                    array('yadore-admin-design-system'),
                    YADORE_PLUGIN_VERSION
                );
                wp_style_add_data('yadore-admin-css', 'path', YADORE_PLUGIN_DIR . 'assets/css/admin.css');

                wp_enqueue_style('yadore-admin-design-system');
                wp_enqueue_style('yadore-admin-css');
            }

            $script_dependencies = array('jquery', 'wp-util');

            if ($is_plugin_screen) {
                wp_register_script(
                    'yadore-charts',
                    YADORE_PLUGIN_URL . 'assets/js/chart.min.js',
                    array(),
                    '3.9.1',
                    true
                );

                $script_dependencies[] = 'yadore-charts';
            }

            wp_enqueue_script(
                'yadore-admin-js',
                YADORE_PLUGIN_URL . 'assets/js/admin.js',
                $script_dependencies,
                YADORE_PLUGIN_VERSION,
                true
            );

            if ($is_plugin_screen) {
                wp_enqueue_script('yadore-charts');
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
                    'success' => __('Operation completed successfully.', 'yadore-monetizer'),
                    'copied' => __('Copied!', 'yadore-monetizer'),
                    'copy_button_default' => __('Copy shortcode', 'yadore-monetizer'),
                    'copy_button_loading' => __('Copying…', 'yadore-monetizer'),
                    'copy_button_success' => __('Copied!', 'yadore-monetizer'),
                    'copy_button_error' => __('Copy failed', 'yadore-monetizer'),
                    'copy_feedback_success' => __('Shortcode copied to clipboard.', 'yadore-monetizer'),
                    'copy_feedback_error' => __('Copy failed. Press Ctrl+C to copy manually.', 'yadore-monetizer'),
                    'show_secret' => __('Show key', 'yadore-monetizer'),
                    'hide_secret' => __('Hide key', 'yadore-monetizer'),
                    'refreshing' => __('Aktualisierung läuft...', 'yadore-monetizer'),
                    'no_data' => __('Noch keine Daten geladen', 'yadore-monetizer'),
                    'just_now' => __('Gerade eben', 'yadore-monetizer'),
                    'relative_seconds' => __('vor %s Sekunden', 'yadore-monetizer'),
                    'relative_minutes' => __('vor %s Minuten', 'yadore-monetizer'),
                    'relative_hours' => __('vor %s Stunden', 'yadore-monetizer'),
                    'relative_days' => __('vor %s Tagen', 'yadore-monetizer'),
                    'activity_empty' => __('Keine Aktivitäten vorhanden.', 'yadore-monetizer')
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
                    'version' => YADORE_PLUGIN_VERSION,
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
            'yadore_shortcode_enabled',
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
            'yadore_overlay_template',
            'yadore_auto_injection_template',
            'yadore_default_shortcode_template',
            'yadore_shortcode_colors',
            'yadore_overlay_colors',

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

            $template_key = $this->sanitize_template_selection(
                get_option('yadore_overlay_template', 'default-overlay'),
                'overlay',
                'default-overlay'
            );

            if ($template_key === '') {
                $template_key = 'default-overlay';
            }

            $display_count = is_array($products) ? count($products) : 0;
            $html = $this->render_products_with_template($template_key, $products, array(
                'type' => 'overlay',
                'keyword' => $keyword,
                'limit' => $limit,
            ));

            // v2.7: Track overlay views
            $this->track_overlay_view($post_id, $keyword, count($products));

            wp_send_json_success(array(
                'products' => $products,
                'keyword' => $keyword,
                'count' => count($products),
                'display_count' => $display_count,
                'post_id' => $post_id,
                'html' => $html,
                'template' => $template_key,
            ));

        } catch (Exception $e) {
            $this->log_error('Overlay products AJAX failed', $e);
            wp_send_json_error('Failed to load products');
        }
    }

    public function ajax_track_product_click() {
        try {
            check_ajax_referer('yadore_frontend_nonce', 'nonce');

            $product_id = isset($_POST['product_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['product_id']))
                : '';

            if ($product_id === '') {
                throw new Exception(__('Invalid product identifier received.', 'yadore-monetizer'));
            }

            $page_url = isset($_POST['page_url'])
                ? esc_url_raw(wp_unslash((string) $_POST['page_url']))
                : '';

            $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
            if ($post_id <= 0 && $page_url !== '') {
                $post_id = url_to_postid($page_url);
            }

            $this->record_product_click_event($product_id, $post_id, $page_url);

            wp_send_json_success(array('tracked' => true));
        } catch (Exception $e) {
            $context = array(
                'product_id' => isset($product_id) ? $product_id : '',
                'page_url' => isset($page_url) ? $page_url : '',
            );
            $this->log_error('Product click tracking request failed', $e, 'warning', $context);
            wp_send_json_error(array('message' => $e->getMessage()));
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

    public function ajax_get_dashboard_stats() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $stats = $this->get_dashboard_statistics();

            wp_send_json_success($stats);
        } catch (Exception $e) {
            $this->log_error('Failed to load dashboard statistics', $e);
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function ajax_get_analytics_data() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $period = isset($_POST['period']) ? intval($_POST['period']) : 30;
            $report = $this->get_analytics_report($period);

            wp_send_json_success($report);
        } catch (Exception $e) {
            $this->log_error('Failed to load analytics data', $e);
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    public function ajax_get_tool_stats() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $stats = array(
                'cache' => $this->get_cache_statistics(),
                'database' => $this->get_database_statistics(),
                'logs' => $this->get_log_statistics(),
                'cleanup' => $this->get_cleanup_statistics(),
                'schedule' => $this->get_schedule_overview(),
            );

            wp_send_json_success($stats);
        } catch (Exception $e) {
            $this->log_error('Failed to load tool statistics', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_export_data() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            if (!get_option('yadore_export_enabled', true)) {
                throw new Exception(__('Data exports are disabled in the plugin settings.', 'yadore-monetizer'));
            }

            $config = $this->sanitize_export_request($_POST);
            $payload = $this->prepare_export_payload($config);

            $record_count = isset($payload['meta']['records']) ? (int) $payload['meta']['records'] : 0;
            if ($record_count <= 0) {
                throw new Exception(__('No data available for the selected export criteria.', 'yadore-monetizer'));
            }

            $content = $this->convert_export_payload($payload, $config['format']);
            if ($content === '') {
                throw new Exception(__('Failed to generate export file content.', 'yadore-monetizer'));
            }

            $filename = $this->generate_export_filename($config['format']);
            $mime_type = $this->get_export_mime_type($config['format']);

            wp_send_json_success(array(
                'filename' => $filename,
                'format' => $config['format'],
                'mime_type' => $mime_type,
                'content' => base64_encode($content),
                'records' => $record_count,
                'meta' => $payload['meta'],
            ));
        } catch (Exception $e) {
            $this->log_error('Export data request failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_schedule_export() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            if (!get_option('yadore_export_enabled', true)) {
                throw new Exception(__('Data exports are disabled in the plugin settings.', 'yadore-monetizer'));
            }

            $config = $this->sanitize_export_request($_POST);

            $interval = isset($_POST['interval']) ? sanitize_key(wp_unslash($_POST['interval'])) : 'daily';
            $allowed_intervals = array('hourly', 'twicedaily', 'daily', 'weekly');
            if (!in_array($interval, $allowed_intervals, true)) {
                $interval = 'daily';
            }

            $time = isset($_POST['time']) ? sanitize_text_field(wp_unslash($_POST['time'])) : '02:00';
            if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                throw new Exception(__('Please provide a valid schedule time (HH:MM).', 'yadore-monetizer'));
            }

            $timestamp = $this->calculate_schedule_timestamp($interval, $time);

            $schedules = get_option('yadore_export_schedules', array());
            if (!is_array($schedules)) {
                $schedules = array();
            }

            $schedule_id = uniqid('yadore_export_', true);

            $schedules[$schedule_id] = array(
                'id' => $schedule_id,
                'created_at' => current_time('mysql'),
                'interval' => $interval,
                'time' => $time,
                'config' => $config,
                'next_run' => $timestamp,
                'last_run' => null,
                'last_error' => '',
                'last_file' => array(),
            );

            update_option('yadore_export_schedules', $schedules, false);

            if (function_exists('wp_clear_scheduled_hook')) {
                wp_clear_scheduled_hook('yadore_run_scheduled_export', array($schedule_id));
            }

            wp_schedule_event($timestamp, $interval, 'yadore_run_scheduled_export', array($schedule_id));

            $next_run = wp_next_scheduled('yadore_run_scheduled_export', array($schedule_id));
            if (!$next_run) {
                $next_run = $timestamp;
            }

            wp_send_json_success(array(
                'schedule_id' => $schedule_id,
                'next_run' => $next_run,
                'next_run_human' => $this->format_timestamp_for_display($next_run),
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to schedule export', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function run_scheduled_export($schedule_id) {
        if (!is_string($schedule_id) || $schedule_id === '') {
            return;
        }

        if (!get_option('yadore_export_enabled', true)) {
            return;
        }

        $schedules = get_option('yadore_export_schedules', array());
        if (!is_array($schedules) || empty($schedules[$schedule_id])) {
            return;
        }

        $schedule = $schedules[$schedule_id];

        try {
            $config = isset($schedule['config']) && is_array($schedule['config']) ? $schedule['config'] : array();
            $payload = $this->prepare_export_payload($config);
            $record_count = isset($payload['meta']['records']) ? (int) $payload['meta']['records'] : 0;

            if ($record_count > 0) {
                $format = isset($config['format']) ? $config['format'] : 'json';
                $content = $this->convert_export_payload($payload, $format);

                if ($content !== '') {
                    $stored = $this->store_export_file($schedule_id, $format, $content);
                    $schedule['last_file'] = $stored;
                }
            }

            $schedule['last_run'] = current_time('mysql');
            $schedule['last_error'] = '';

            $this->log(sprintf('Scheduled export %s completed with %d records.', $schedule_id, $record_count));
        } catch (Exception $e) {
            $schedule['last_run'] = current_time('mysql');
            $schedule['last_error'] = $e->getMessage();
            $this->log_error('Scheduled export failed', $e);
        }

        $next = wp_next_scheduled('yadore_run_scheduled_export', array($schedule_id));
        if ($next) {
            $schedule['next_run'] = $next;
        }

        $schedules[$schedule_id] = $schedule;
        update_option('yadore_export_schedules', $schedules, false);
    }

    public function ajax_import_data() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'import';
            $options = isset($_POST['options']) ? (array) wp_unslash($_POST['options']) : array();

            $flags = array(
                'overwrite' => in_array('overwrite', $options, true),
                'validate' => in_array('validate', $options, true) || $mode === 'validate',
                'backup' => in_array('backup', $options, true),
            );

            $files = $this->normalize_uploaded_files('files');
            if (empty($files)) {
                throw new Exception(__('No import files provided.', 'yadore-monetizer'));
            }

            $result = $this->process_import_files($files, $flags, $mode);

            wp_send_json_success($result);
        } catch (Exception $e) {
            $this->log_error('Import data request failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clear_cache() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $removed = $this->clear_plugin_caches();
            $metrics = $this->reset_cache_metrics();

            wp_send_json_success(array(
                'message' => __('Cache cleared successfully.', 'yadore-monetizer'),
                'removed' => $removed,
                'metrics' => $metrics,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to clear cache', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_restore_default_templates() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions to restore templates.', 'yadore-monetizer'));
            }

            $force_reset = false;
            if (isset($_POST['reset_selection'])) {
                $force_reset = filter_var(wp_unslash($_POST['reset_selection']), FILTER_VALIDATE_BOOLEAN);
            }

            $summary = $this->restore_default_templates($force_reset);

            wp_send_json_success(array(
                'message' => __('Default templates restored successfully.', 'yadore-monetizer'),
                'created' => isset($summary['created']) ? (int) $summary['created'] : 0,
                'updated' => isset($summary['updated']) ? (int) $summary['updated'] : 0,
                'options_updated' => isset($summary['options']) ? (int) $summary['options'] : 0,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to restore default templates', $e);
            wp_send_json_error(array(
                'message' => $e->getMessage(),
            ));
        }
    }

    public function ajax_analyze_cache() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $stats = $this->get_cache_statistics();
            $entries = isset($stats['entries']) ? (int) $stats['entries'] : 0;
            $size = isset($stats['size']) ? $stats['size'] : '0 KB';
            $hit_rate = isset($stats['hit_rate']) ? (int) $stats['hit_rate'] : 0;

            $last_cleared = $stats['metrics']['last_cleared'] ?? 0;
            $now = function_exists('current_time') ? current_time('timestamp') : time();
            if ($last_cleared > 0 && $last_cleared <= $now && function_exists('human_time_diff')) {
                $diff = human_time_diff($last_cleared, $now);
                $cleared_message = sprintf(__('last cleared %s ago', 'yadore-monetizer'), $diff);
            } else {
                $cleared_message = __('no recent clear recorded', 'yadore-monetizer');
            }

            $message = sprintf(
                __('Cache health check: %1$d entries (~%2$s) with a %3$d%% hit rate, %4$s.', 'yadore-monetizer'),
                $entries,
                $size,
                $hit_rate,
                $cleared_message
            );

            $status = 'healthy';
            if ($entries === 0) {
                $status = 'warning';
            } elseif ($hit_rate < 10) {
                $status = 'critical';
            } elseif ($hit_rate < 30) {
                $status = 'warning';
            }

            wp_send_json_success(array(
                'status' => $status,
                'message' => $message,
                'stats' => $stats,
            ));
        } catch (Exception $e) {
            $this->log_error('Cache analysis failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_optimize_cache() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $summary = $this->optimize_plugin_cache();
            $stats = $this->get_cache_statistics();

            wp_send_json_success(array(
                'message' => __('Cache optimization completed successfully.', 'yadore-monetizer'),
                'summary' => $summary,
                'stats' => $stats,
            ));
        } catch (Exception $e) {
            $this->log_error('Cache optimization failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_optimize_database() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $result = $this->optimize_database_tables();

            wp_send_json_success(array(
                'message' => __('Database optimization completed.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('Database optimization failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_cleanup_old_data() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $result = $this->cleanup_old_data_records();

            wp_send_json_success(array(
                'message' => __('Old data cleanup completed.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('Old data cleanup failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_archive_logs() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $archive = $this->archive_logs_to_file();

            wp_send_json_success(array(
                'message' => __('Logs archived successfully.', 'yadore-monetizer'),
                'archive' => $archive,
            ));
        } catch (Exception $e) {
            $this->log_error('Log archiving failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clear_old_logs() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $result = $this->delete_old_logs();

            wp_send_json_success(array(
                'message' => __('Old logs removed successfully.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('Clearing old logs failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_system_cleanup() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $result = $this->run_system_cleanup();

            wp_send_json_success(array(
                'message' => __('System cleanup completed successfully.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('System cleanup failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_schedule_cleanup() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $interval = isset($_POST['interval']) ? sanitize_key(wp_unslash($_POST['interval'])) : 'daily';
            $schedule = $this->schedule_cleanup_event($interval);

            wp_send_json_success(array(
                'message' => __('Cleanup schedule updated.', 'yadore-monetizer'),
                'schedule' => $schedule,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to schedule cleanup', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_reset_settings() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $updated = $this->reset_plugin_settings_to_defaults();

            wp_send_json_success(array(
                'message' => __('Settings reset to defaults.', 'yadore-monetizer'),
                'updated' => $updated,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to reset settings', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clear_all_data() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $summary = $this->remove_all_plugin_data();

            wp_send_json_success(array(
                'message' => __('All plugin data removed.', 'yadore-monetizer'),
                'details' => $summary,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to remove plugin data', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_factory_reset() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $summary = $this->perform_factory_reset();

            wp_send_json_success(array(
                'message' => __('Factory reset completed successfully.', 'yadore-monetizer'),
                'details' => $summary,
            ));
        } catch (Exception $e) {
            $this->log_error('Factory reset failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_export_config() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $package = $this->generate_config_export_package();

            wp_send_json_success($package);
        } catch (Exception $e) {
            $this->log_error('Configuration export failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_clone_settings() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $source = isset($_POST['source']) ? esc_url_raw(wp_unslash($_POST['source'])) : '';
            if ($source === '') {
                throw new Exception(__('Please provide a valid source URL.', 'yadore-monetizer'));
            }

            $payload = $this->fetch_remote_settings_package($source);
            if (empty($payload)) {
                throw new Exception(__('The remote site did not provide any settings.', 'yadore-monetizer'));
            }

            $result = $this->import_payload($payload, array('overwrite' => true, 'validate' => false, 'backup' => true));

            wp_send_json_success(array(
                'message' => __('Settings cloned successfully.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('Failed to clone settings', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_auto_optimize() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $result = $this->perform_auto_optimize();

            wp_send_json_success(array(
                'message' => __('Auto optimization completed.', 'yadore-monetizer'),
                'details' => $result,
            ));
        } catch (Exception $e) {
            $this->log_error('Auto optimization failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_analyze_keywords() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $text = isset($_POST['text']) ? wp_unslash($_POST['text']) : '';
            if (trim($text) === '') {
                throw new Exception(__('Please provide text to analyze.', 'yadore-monetizer'));
            }

            $max = isset($_POST['max']) ? max(1, (int) $_POST['max']) : 5;
            $use_ai = isset($_POST['use_ai']) ? (bool) $_POST['use_ai'] : false;

            $keywords = $this->analyze_keywords_locally($text, $max);
            $summary = '';

            if ($use_ai && get_option('yadore_ai_enabled')) {
                try {
                    $ai = $this->analyze_keywords_with_ai($text, $max);
                    if (!empty($ai['keywords'])) {
                        $keywords = $ai['keywords'];
                    }
                    if (!empty($ai['summary'])) {
                        $summary = $ai['summary'];
                    }
                } catch (Exception $ai_error) {
                    $this->log_error('AI keyword analysis failed', $ai_error, 'warning');
                    $summary = __('AI analysis unavailable, showing local suggestions.', 'yadore-monetizer');
                }
            }

            wp_send_json_success(array(
                'keywords' => $keywords,
                'summary' => $summary,
            ));
        } catch (Exception $e) {
            $this->log_error('Keyword analysis failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_test_connectivity() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $results = $this->run_connectivity_checks();

            $message = __('Connectivity diagnostics completed successfully.', 'yadore-monetizer');
            if (!empty($results['services'])) {
                $critical = array_filter($results['services'], function ($service) {
                    return isset($service['status']) && $service['status'] === 'critical';
                });
                $warnings = array_filter($results['services'], function ($service) {
                    return isset($service['status']) && $service['status'] === 'warning';
                });

                if (!empty($critical)) {
                    $message = __('Connectivity issues detected. Review the service breakdown below.', 'yadore-monetizer');
                } elseif (!empty($warnings)) {
                    $message = __('Connectivity checks completed with warnings. Review the service breakdown below.', 'yadore-monetizer');
                }
            }

            wp_send_json_success(array(
                'status' => $results['status'],
                'message' => $message,
                'services' => $results['services'],
            ));
        } catch (Exception $e) {
            $this->log_error('Connectivity diagnostics failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_check_database() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $results = $this->run_database_diagnostics();
            $message = isset($results['message']) && $results['message'] !== ''
                ? $results['message']
                : __('Database diagnostics completed.', 'yadore-monetizer');

            wp_send_json_success(array(
                'status' => $results['status'],
                'message' => $message,
                'details' => $results['details'],
            ));
        } catch (Exception $e) {
            $this->log_error('Database diagnostics failed', $e);
            wp_send_json_error($e->getMessage());
        }
    }

    public function ajax_test_performance() {
        try {
            check_ajax_referer('yadore_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'yadore-monetizer'));
            }

            $results = $this->run_performance_diagnostics();
            $message = isset($results['message']) && $results['message'] !== ''
                ? $results['message']
                : __('Performance diagnostics completed.', 'yadore-monetizer');

            wp_send_json_success(array(
                'status' => $results['status'],
                'message' => $message,
                'details' => $results['details'],
            ));
        } catch (Exception $e) {
            $this->log_error('Performance diagnostics failed', $e);
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

            $api_clicks_table = $wpdb->prefix . 'yadore_api_clicks';
            $sql6 = "CREATE TABLE $api_clicks_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                click_id varchar(128) NOT NULL,
                clicked_at datetime NOT NULL,
                market varchar(10),
                merchant_id varchar(190),
                merchant_name varchar(255),
                placement_id varchar(190),
                sales_amount decimal(12,2) DEFAULT 0.00,
                raw_payload longtext,
                synced_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY click_id (click_id),
                KEY clicked_at (clicked_at),
                KEY market (market),
                KEY merchant_id (merchant_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql1);
            dbDelta($sql2);
            dbDelta($sql3);
            dbDelta($sql4);
            dbDelta($sql5);
            dbDelta($sql6);

            $this->reset_table_exists_cache();

            $this->log('Enhanced database tables created successfully for v3.32', 'info');

        } catch (Exception $e) {
            $this->log_error('Database table creation failed', $e, 'critical');
            throw $e;
        }
    }

    private function maybe_upgrade_database($previous_version) {
        try {
            $baseline_version = is_string($previous_version) && $previous_version !== ''
                ? $previous_version
                : '0';

            if (version_compare($baseline_version, '3.0', '<')) {
                $this->create_tables();
            }
        } catch (Exception $e) {
            $this->log_error('Database upgrade routine failed', $e, 'high');
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

        $this->ensure_scheduled_exports();
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
                $this->record_cache_hit('products');
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
                    $this->record_cache_hit('products');
                    return array(
                        'products' => $cached,
                        'keyword' => $keyword,
                        'precision' => $precision,
                    );
                }

                delete_transient($cache_key);
            }
        }

        if ($use_cache) {
            $this->record_cache_miss('products');
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
                $logo_value = $product['merchant']['logo'];
                if (is_array($logo_value) && isset($logo_value['url'])) {
                    $logo_value = $logo_value['url'];
                }

                if (is_string($logo_value) && $logo_value !== '') {
                    $sanitized['merchant']['logo'] = esc_url_raw($logo_value);
                }
            }

            if (isset($product['merchant']['logoUrl']) && is_string($product['merchant']['logoUrl'])) {
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

        if (isset($product['thumbnail']) && is_array($product['thumbnail'])) {
            $sanitized['thumbnail']['url'] = isset($product['thumbnail']['url'])
                ? esc_url_raw($product['thumbnail']['url'])
                : '';
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

        if (empty($sanitized['image']['url']) && !empty($sanitized['thumbnail']['url'])) {
            $sanitized['image']['url'] = $sanitized['thumbnail']['url'];
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
                self::DEFAULT_AI_PROMPT
            );
            if (trim($prompt_template) === '') {
                $prompt_template = self::DEFAULT_AI_PROMPT;
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
                $this->record_cache_hit('ai');
                return $cached;
            }
        }

        if ($use_cache) {
            $this->record_cache_miss('ai');
        }

        $temperature = floatval(get_option('yadore_ai_temperature', '0.3'));
            $temperature = max(0, min(2, $temperature));
            $max_tokens = intval(get_option('yadore_ai_max_tokens', 2000));
            if ($max_tokens <= 0) {
                $max_tokens = 2000;
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

            $this->gemini_json_debug = array();

            if (!empty($decoded['candidates']) && is_array($decoded['candidates'])) {
                $all_truncated = true;
                $any_candidate_content = false;

                foreach ($decoded['candidates'] as $candidate) {
                    $finish_reason = '';
                    if (isset($candidate['finishReason'])) {
                        $finish_reason = strtoupper(trim((string) $candidate['finishReason']));
                    }

                    if ($finish_reason !== 'MAX_TOKENS') {
                        $all_truncated = false;
                    }

                    if (!$any_candidate_content && !empty($candidate['content'])) {
                        if (is_array($candidate['content'])) {
                            if (!empty($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                                foreach ($candidate['content']['parts'] as $part) {
                                    if (!empty($part) && (is_array($part) || (is_string($part) && trim($part) !== ''))) {
                                        $any_candidate_content = true;
                                        break;
                                    }
                                }
                            } elseif (!empty($candidate['content']['text']) && is_string($candidate['content']['text'])) {
                                if (trim((string) $candidate['content']['text']) !== '') {
                                    $any_candidate_content = true;
                                }
                            }
                        } elseif (is_string($candidate['content']) && trim($candidate['content']) !== '') {
                            $any_candidate_content = true;
                        }
                    }
                }

                if ($all_truncated && !$any_candidate_content) {
                    $this->log_api_call('gemini', $endpoint_base, 'error', array(
                        'status' => $status,
                        'model' => $model,
                        'response' => $decoded,
                        'parse_error' => 'Response truncated (MAX_TOKENS)',
                    ));

                    return array(
                        'error' => __('Gemini API truncated the response before any data could be generated. Increase the maximum output tokens or choose a different model.', 'yadore-monetizer'),
                    );
                }
            }

            $raw_structured_text = '';
            $structured_data = $this->extract_gemini_structured_payload($decoded, $raw_structured_text);

            if (!is_array($structured_data)) {
                $diagnosis = $this->diagnose_gemini_parse_failure($decoded, $raw_structured_text);
                $error_message = __('Gemini API returned data that could not be parsed as JSON.', 'yadore-monetizer');
                $log_context = array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $decoded,
                    'raw' => is_string($raw_structured_text) ? substr($raw_structured_text, 0, 500) : $raw_structured_text,
                    'parse_error' => 'Unable to extract structured keyword data',
                    'parse_debug' => $this->gemini_json_debug,
                );

                if (is_array($diagnosis)) {
                    if (!empty($diagnosis['message'])) {
                        $error_message = $diagnosis['message'];
                    }

                    if (!empty($diagnosis['log_reason'])) {
                        $log_context['parse_error'] = $diagnosis['log_reason'];
                    }

                    if (!empty($diagnosis['details'])) {
                        $log_context['parse_details'] = $diagnosis['details'];
                    }
                }

                $this->log_api_call('gemini', $endpoint_base, 'error', $log_context);
                return array('error' => $error_message);
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
                    'parse_debug' => $this->gemini_json_debug,
                ));
                return array('error' => __('Gemini API returned data that did not match the expected schema.', 'yadore-monetizer'));
            }

            $keyword = sanitize_text_field((string) $structured_data['keyword']);
            if ($keyword === '') {
                $this->log_api_call('gemini', $endpoint_base, 'error', array(
                    'status' => $status,
                    'model' => $model,
                    'response' => $structured_data,
                    'parse_debug' => $this->gemini_json_debug,
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
                'parse_debug' => $this->gemini_json_debug,
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
            foreach ($response['candidates'] as $candidate_index => $candidate) {
                if (!empty($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part_index => $part) {
                        $normalized = $this->extract_gemini_payload_from_part($part, $raw_text, $candidate_index, $part_index);
                        if (is_array($normalized)) {
                            return $normalized;
                        }
                    }
                }

                if (isset($candidate['content']) && is_string($candidate['content'])) {
                    $raw_text = (string) $candidate['content'];
                    $parsed = $this->decode_gemini_json_string($raw_text, 'candidate_content');
                    if (is_array($parsed)) {
                        return $parsed;
                    }
                }
            }
        }

        if (isset($response['result']) && is_array($response['result'])) {
            $raw_text = wp_json_encode($response['result']);
            return $this->normalize_gemini_structured_array($response['result']);
        }

        if (isset($response['text']) && is_string($response['text'])) {
            $raw_text = (string) $response['text'];
            $parsed = $this->decode_gemini_json_string($raw_text, 'response_text');
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    private function decode_gemini_json_string($text, $context = 'text') {
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
            $this->record_gemini_parse_debug($context . '_full', true, 'Parsed JSON payload');
            return $this->normalize_gemini_structured_array($decoded);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->record_gemini_parse_debug($context . '_full', false, json_last_error_msg());
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

        foreach ($candidates as $candidate_index => $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->record_gemini_parse_debug($context . '_substring_' . $candidate_index, true, 'Parsed JSON fragment');
                return $this->normalize_gemini_structured_array($decoded);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->record_gemini_parse_debug($context . '_substring_' . $candidate_index, false, json_last_error_msg());
            }
        }

        if (strpos($normalized, '{') === false && strpos($normalized, '[') === false) {
            $this->record_gemini_parse_debug($context . '_fallback', true, 'Treated plain text as keyword');
            return array('keyword' => $normalized);
        }

        $this->record_gemini_parse_debug($context . '_fallback', false, 'No JSON structure detected');

        return null;
    }

    private function extract_gemini_payload_from_part($part, &$raw_text, $candidate_index, $part_index) {
        if (!is_array($part)) {
            return null;
        }

        $context = sprintf('candidate_%d_part_%d', (int) $candidate_index, (int) $part_index);

        if (isset($part['functionCall']) && is_array($part['functionCall'])) {
            $call = $part['functionCall'];
            $payload = null;

            if (isset($call['args'])) {
                $payload = $call['args'];
            } elseif (isset($call['arguments'])) {
                $payload = $call['arguments'];
            }

            if (is_string($payload)) {
                $raw_text = $payload;
                $parsed = $this->decode_gemini_json_string($payload, $context . '_function_call');
                if (is_array($parsed)) {
                    return $parsed;
                }
            } elseif (is_array($payload)) {
                $raw_text = wp_json_encode($payload);
                $this->record_gemini_parse_debug($context . '_function_call_args', true, 'Used structured args array');
                return $this->normalize_gemini_structured_array($payload);
            }
        }

        if (isset($part['functionResponse']) && is_array($part['functionResponse'])) {
            $response = $part['functionResponse'];

            if (isset($response['response']) && is_array($response['response'])) {
                if (isset($response['response']['content']) && is_array($response['response']['content'])) {
                    foreach ($response['response']['content'] as $response_content) {
                        if (isset($response_content['parts']) && is_array($response_content['parts'])) {
                            foreach ($response_content['parts'] as $nested_part) {
                                $normalized = $this->extract_gemini_payload_from_part($nested_part, $raw_text, $candidate_index, $part_index);
                                if (is_array($normalized)) {
                                    return $normalized;
                                }
                            }
                        }
                    }
                }

                $raw_candidate = wp_json_encode($response['response']);
                $parsed = $this->decode_gemini_json_string($raw_candidate, $context . '_function_response');
                if (is_array($parsed)) {
                    $raw_text = $raw_candidate;
                    return $parsed;
                }
            }

            if (isset($response['result'])) {
                $raw_candidate = wp_json_encode($response['result']);
                $parsed = $this->decode_gemini_json_string($raw_candidate, $context . '_function_result');
                if (is_array($parsed)) {
                    $raw_text = $raw_candidate;
                    return $parsed;
                }
            }
        }

        if (isset($part['jsonValue'])) {
            $raw_text = wp_json_encode($part['jsonValue']);
            $this->record_gemini_parse_debug($context . '_json_value', true, 'Used jsonValue payload');
            return $this->normalize_gemini_structured_array($part['jsonValue']);
        }

        if (isset($part['structValue'])) {
            $raw_text = wp_json_encode($part['structValue']);
            $this->record_gemini_parse_debug($context . '_struct_value', true, 'Used structValue payload');
            return $this->normalize_gemini_structured_array($part['structValue']);
        }

        if (isset($part['inlineData']['data'])) {
            $decoded_inline = $this->decode_gemini_inline_data($part['inlineData']['data']);
            if (is_array($decoded_inline)) {
                $raw_text = wp_json_encode($decoded_inline);
                $this->record_gemini_parse_debug($context . '_inline_data', true, 'Decoded inline data payload');
                return $decoded_inline;
            }
        }

        if (isset($part['text'])) {
            $raw_text = (string) $part['text'];
            $parsed = $this->decode_gemini_json_string($raw_text, $context . '_text');
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    private function decode_gemini_inline_data($data) {
        if (!is_string($data) || $data === '') {
            return null;
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false || $decoded === '') {
            $this->record_gemini_parse_debug('inline_data', false, 'Unable to base64 decode inline data');
            return null;
        }

        $parsed = $this->decode_gemini_json_string($decoded, 'inline_data');
        if (is_array($parsed)) {
            return $parsed;
        }

        return null;
    }

    private function normalize_gemini_structured_array($value) {
        if (!is_array($value)) {
            return $value;
        }

        if (isset($value['fields']) && is_array($value['fields'])) {
            $normalized = array();
            foreach ($value['fields'] as $key => $field_value) {
                $normalized[$key] = $this->normalize_gemini_value($field_value);
            }
            return $normalized;
        }

        $normalized = array();
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalize_gemini_value($item);
        }

        return $normalized;
    }

    private function normalize_gemini_value($value) {
        if (!is_array($value)) {
            return $value;
        }

        if (array_key_exists('stringValue', $value)) {
            return (string) $value['stringValue'];
        }

        if (array_key_exists('numberValue', $value)) {
            return $value['numberValue'] + 0;
        }

        if (array_key_exists('boolValue', $value)) {
            return (bool) $value['boolValue'];
        }

        if (isset($value['listValue']['values']) && is_array($value['listValue']['values'])) {
            $list = array();
            foreach ($value['listValue']['values'] as $list_item) {
                $list[] = $this->normalize_gemini_value($list_item);
            }
            return $list;
        }

        if (isset($value['structValue'])) {
            return $this->normalize_gemini_structured_array($value['structValue']);
        }

        if (isset($value['fields']) && is_array($value['fields'])) {
            return $this->normalize_gemini_structured_array($value);
        }

        $normalized = array();
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalize_gemini_value($item);
        }

        return $normalized;
    }

    private function record_gemini_parse_debug($strategy, $success, $detail = '') {
        $this->gemini_json_debug[] = array(
            'strategy' => (string) $strategy,
            'success' => (bool) $success,
            'detail'  => (string) $detail,
        );
    }

    private function diagnose_gemini_parse_failure($response, $raw_text = '') {
        if (!is_array($response)) {
            return null;
        }

        $details = array();
        $candidates = array();

        if (isset($response['candidates']) && is_array($response['candidates'])) {
            $candidates = $response['candidates'];
        }

        if (empty($candidates)) {
            return array(
                'message' => __('Gemini API response did not contain any candidates to parse.', 'yadore-monetizer'),
                'log_reason' => 'No candidates returned',
                'details' => array(),
            );
        }

        $finish_reasons = array();
        $all_candidates_empty = true;
        $all_max_tokens = true;
        $any_max_tokens = false;

        foreach ($candidates as $candidate) {
            $finish_reason = '';

            if (isset($candidate['finishReason'])) {
                $finish_reason = strtoupper(trim((string) $candidate['finishReason']));
                if ($finish_reason !== '') {
                    $finish_reasons[] = $finish_reason;
                }
            }

            if ($finish_reason !== 'MAX_TOKENS') {
                $all_max_tokens = false;
            } else {
                $any_max_tokens = true;
            }

            if ($this->gemini_candidate_has_payload($candidate)) {
                $all_candidates_empty = false;
            }
        }

        if (!empty($finish_reasons)) {
            $details['finish_reasons'] = array_values(array_unique($finish_reasons));
        }

        if ($all_candidates_empty && $all_max_tokens) {
            return array(
                'message' => __('Gemini API truncated the response before returning any structured data. Increase the output token limit or reduce the prompt length.', 'yadore-monetizer'),
                'log_reason' => 'Response truncated before content',
                'details' => $details,
            );
        }

        if (is_string($raw_text) && $raw_text !== '') {
            $raw_text = (string) $raw_text;
            $open_curly = substr_count($raw_text, '{');
            $close_curly = substr_count($raw_text, '}');
            $open_square = substr_count($raw_text, '[');
            $close_square = substr_count($raw_text, ']');

            if ($open_curly !== $close_curly || $open_square !== $close_square) {
                $details['unbalanced_json'] = true;

                if ($any_max_tokens) {
                    return array(
                        'message' => __('Gemini API cut off the JSON payload mid-response. Try requesting fewer fields or increasing the maximum output tokens.', 'yadore-monetizer'),
                        'log_reason' => 'Truncated JSON payload',
                        'details' => $details,
                    );
                }
            }

            $details['raw_preview'] = substr($raw_text, 0, 200);
        }

        if ($all_candidates_empty) {
            return array(
                'message' => __('Gemini API returned a response without any structured content. Please try again or adjust the prompt settings.', 'yadore-monetizer'),
                'log_reason' => 'Empty candidate content',
                'details' => $details,
            );
        }

        if ($any_max_tokens) {
            return array(
                'message' => __('Gemini API stopped generating before the structured JSON was completed. Increase the token limit and retry.', 'yadore-monetizer'),
                'log_reason' => 'Incomplete JSON due to MAX_TOKENS',
                'details' => $details,
            );
        }

        return array(
            'message' => __('Gemini API response could not be parsed. Enable JSON schema mode or review the raw output in the debug logs.', 'yadore-monetizer'),
            'log_reason' => 'Unparseable structured response',
            'details' => $details,
        );
    }

    private function gemini_candidate_has_payload($candidate) {
        if (!is_array($candidate)) {
            return false;
        }

        if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
            foreach ($candidate['content']['parts'] as $part) {
                if ($this->gemini_part_has_payload($part)) {
                    return true;
                }
            }
        }

        if (isset($candidate['content']['text']) && is_string($candidate['content']['text'])) {
            if (trim((string) $candidate['content']['text']) !== '') {
                return true;
            }
        }

        if (isset($candidate['content']) && is_string($candidate['content'])) {
            if (trim((string) $candidate['content']) !== '') {
                return true;
            }
        }

        return false;
    }

    private function gemini_part_has_payload($part) {
        if (is_string($part)) {
            return trim($part) !== '';
        }

        if (!is_array($part)) {
            return false;
        }

        if (isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
            return true;
        }

        if (isset($part['jsonValue']) && !empty($part['jsonValue'])) {
            return true;
        }

        if (isset($part['structValue']) && !empty($part['structValue'])) {
            return true;
        }

        if (isset($part['inlineData']['data']) && is_string($part['inlineData']['data']) && trim($part['inlineData']['data']) !== '') {
            return true;
        }

        if (isset($part['functionCall']) && is_array($part['functionCall'])) {
            $call = $part['functionCall'];
            if ((isset($call['args']) && !empty($call['args'])) || (isset($call['arguments']) && !empty($call['arguments']))) {
                return true;
            }
        }

        if (isset($part['functionResponse']) && is_array($part['functionResponse']) && !empty($part['functionResponse'])) {
            return true;
        }

        return false;
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

    private function get_dashboard_statistics() {
        $defaults = array(
            'total_products' => 0,
            'scanned_posts' => 0,
            'overlay_views' => 0,
            'conversion_rate' => 0,
        );

        $this->maybe_sync_recent_clicks(3);

        $cached = get_option('yadore_stats_cache', array());
        if (!is_array($cached)) {
            $cached = array();
        }

        $stats = wp_parse_args($cached, $defaults);

        $computed_products = $this->get_total_products_displayed();
        if ($computed_products > 0) {
            $stats['total_products'] = max((int) $stats['total_products'], $computed_products);
        } else {
            $stats['total_products'] = (int) $stats['total_products'];
        }

        $stats['scanned_posts'] = $this->get_scanned_post_count();
        $stats['overlay_views'] = $this->get_overlay_view_count();
        $stats['conversion_rate'] = $this->calculate_conversion_rate($stats);

        $activity = $this->get_recent_activity_entries();

        $cache_payload = array(
            'total_products' => (int) $stats['total_products'],
            'scanned_posts' => (int) $stats['scanned_posts'],
            'overlay_views' => (int) $stats['overlay_views'],
            'conversion_rate' => $this->format_conversion_rate_for_storage($stats['conversion_rate']),
        );

        update_option('yadore_stats_cache', $cache_payload);

        return array(
            'total_products' => (int) $stats['total_products'],
            'scanned_posts' => (int) $stats['scanned_posts'],
            'overlay_views' => (int) $stats['overlay_views'],
            'conversion_rate' => (float) $stats['conversion_rate'],
            'activity' => $activity,
        );
    }

    private function get_total_products_displayed() {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return 0;
        }

        $total = $wpdb->get_var("SELECT SUM(product_count) FROM {$table} WHERE product_count > 0 AND product_validated = 1");

        if ($total === null) {
            return 0;
        }

        return max(0, (int) $total);
    }

    private function get_overlay_view_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($table)) {
            return 0;
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE event_type = %s",
                'overlay_view'
            )
        );

        if ($count === null) {
            return 0;
        }

        return (int) $count;
    }

    private function calculate_conversion_rate($stats) {
        $base_rate = 0.0;

        if (isset($stats['conversion_rate'])) {
            if (is_numeric($stats['conversion_rate'])) {
                $base_rate = (float) $stats['conversion_rate'];
            } elseif (is_string($stats['conversion_rate'])) {
                $base_rate = (float) preg_replace('/[^0-9\.,]/', '', $stats['conversion_rate']);
            }
        }

        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if ($this->table_exists($analytics_table)) {
            $clicks = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$analytics_table} WHERE event_type = %s",
                    'product_click'
                )
            );
            $conversions = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$analytics_table} WHERE event_type = %s",
                    'conversion'
                )
            );
            $views = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$analytics_table} WHERE event_type IN (%s, %s)",
                    'product_view',
                    'overlay_view'
                )
            );

            $clicks = is_numeric($clicks) ? (int) $clicks : 0;
            $conversions = is_numeric($conversions) ? (int) $conversions : 0;
            $views = is_numeric($views) ? (int) $views : 0;

            $reference = $conversions > 0 ? $conversions : $clicks;

            if ($views > 0 && $reference > 0) {
                return round(($reference / $views) * 100, 2);
            }
        }

        return round($base_rate, 2);
    }

    private function format_conversion_rate_for_storage($rate) {
        if (!is_numeric($rate)) {
            $rate = (float) preg_replace('/[^0-9\.,]/', '', (string) $rate);
        }

        $value = round((float) $rate, 2);

        if (function_exists('number_format_i18n')) {
            return number_format_i18n($value, 2) . '%';
        }

        return sprintf('%.2f%%', $value);
    }

    private function get_analytics_report($period_days) {
        $period = max(1, min(365, (int) $period_days));
        $use_cache = !get_option('yadore_debug_mode', false);
        $cache_key = 'yadore_analytics_' . $period;

        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false && is_array($cached)) {
                $this->record_cache_hit('analytics');
                return $cached;
            }

            $this->record_cache_miss('analytics');
        }

        $report = $this->build_analytics_report($period);

        if ($use_cache) {
            $duration = $this->get_analytics_cache_duration();
            if ($duration > 0) {
                set_transient($cache_key, $report, $duration);
            }
        }

        return $report;
    }

    private function get_analytics_cache_duration() {
        $duration = (int) get_option('yadore_cache_duration', 3600);
        if ($duration <= 0) {
            return 0;
        }

        $day_in_seconds = defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400;

        return max(300, min($duration, $day_in_seconds));
    }

    private function build_analytics_report($period_days) {
        global $wpdb;

        $period = max(1, min(365, (int) $period_days));

        $timezone = $this->get_wp_timezone_object();
        $end = new \DateTime('now', $timezone);
        $end->setTime(23, 59, 59);

        $start = clone $end;
        $start->setTime(0, 0, 0);
        if ($period > 1) {
            $start->modify(sprintf('-%d days', $period - 1));
        }

        $this->maybe_sync_clicks_from_api(clone $start, clone $end);

        $start_string = $start->format('Y-m-d H:i:s');
        $end_string = $end->format('Y-m-d H:i:s');
        $start_timestamp = $start->getTimestamp();
        $end_timestamp = $end->getTimestamp();

        $previous_end = (clone $start);
        $previous_end->modify('-1 second');
        $previous_start = clone $previous_end;
        $previous_start->setTime(0, 0, 0);
        if ($period > 1) {
            $previous_start->modify(sprintf('-%d days', $period - 1));
        }

        $previous_start_string = $previous_start->format('Y-m-d H:i:s');
        $previous_end_string = $previous_end->format('Y-m-d H:i:s');
        $previous_start_timestamp = $previous_start->getTimestamp();
        $previous_end_timestamp = $previous_end->getTimestamp();

        $date_map = $this->generate_analytics_date_map($start, $end);
        $labels = array();
        $date_index = array();
        $position = 0;
        foreach ($date_map as $key => $date) {
            $date_index[$key] = $position++;
            $labels[] = $this->format_chart_date_label($date);
        }

        $label_count = count($labels);

        $summary = array(
            'product_views' => 0,
            'overlay_displays' => 0,
            'average_ctr' => 0,
            'ai_analyses' => 0,
        );

        $previous_summary = array(
            'product_views' => 0,
            'overlay_displays' => 0,
            'average_ctr' => 0,
            'ai_analyses' => 0,
        );

        $traffic = array(
            'daily_average' => 0,
            'product_pages' => 0,
            'bounce_rate' => 0,
            'session_duration' => $this->format_duration_label(0),
            'chart' => array(
                'labels' => $labels,
                'visitors' => $label_count > 0 ? array_fill(0, $label_count, 0) : array(),
                'views' => $label_count > 0 ? array_fill(0, $label_count, 0) : array(),
            ),
        );

        $funnel = array(
            'page_views' => 0,
            'product_displays' => 0,
            'product_clicks' => 0,
            'conversions' => 0,
            'display_rate' => 0,
            'click_rate' => 0,
            'conversion_rate' => 0,
        );

        $revenue = array(
            'monthly_estimate' => 0.0,
            'revenue_per_click' => 0.0,
            'top_category' => __('No data', 'yadore-monetizer'),
            'category_earnings' => 0.0,
            'trend' => array(
                'labels' => $labels,
                'values' => $label_count > 0 ? array_fill(0, $label_count, 0.0) : array(),
            ),
        );

        $performance_chart = array(
            'labels' => $labels,
            'views' => $label_count > 0 ? array_fill(0, $label_count, 0) : array(),
            'clicks' => $label_count > 0 ? array_fill(0, $label_count, 0) : array(),
        );

        $performance_table = array(
            'views' => array(),
            'clicks' => array(),
            'ctr' => array(),
            'revenue' => array(),
        );

        $keyword_overview = array(
            'total' => 0,
            'active' => 0,
            'ai' => 0,
        );
        $keyword_cloud = array();
        $keyword_performance = array();

        $page_views_total = 0;
        $clicks_total = 0;
        $conversions_total = 0;
        $previous_page_views_total = 0;
        $previous_clicks_total = 0;
        $session_count = 0;
        $session_duration_sum = 0;
        $revenue_total = 0.0;
        $category_totals = array();
        $revenue_by_post = array();
        $post_metrics = array();
        $keyword_map = array();

        $analytics_table = $wpdb->prefix . 'yadore_analytics';
        $keywords_table = $wpdb->prefix . 'yadore_post_keywords';

        if ($this->table_exists($analytics_table)) {
            $event_totals = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT event_type, COUNT(*) AS total FROM {$analytics_table} WHERE created_at BETWEEN %s AND %s GROUP BY event_type",
                    $start_string,
                    $end_string
                ),
                ARRAY_A
            );

            $previous_event_totals = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT event_type, COUNT(*) AS total FROM {$analytics_table} WHERE created_at BETWEEN %s AND %s GROUP BY event_type",
                    $previous_start_string,
                    $previous_end_string
                ),
                ARRAY_A
            );

            if (is_array($event_totals)) {
                foreach ($event_totals as $row) {
                    $type = isset($row['event_type']) ? sanitize_key((string) $row['event_type']) : '';
                    $total = isset($row['total']) ? (int) $row['total'] : 0;

                    switch ($type) {
                        case 'product_view':
                            $summary['product_views'] += $total;
                            break;
                        case 'overlay_view':
                            $summary['overlay_displays'] += $total;
                            break;
                        case 'product_click':
                            $clicks_total += $total;
                            break;
                        case 'conversion':
                            $conversions_total += $total;
                            break;
                    }
                }
            }

            if (is_array($previous_event_totals)) {
                foreach ($previous_event_totals as $row) {
                    $type = isset($row['event_type']) ? sanitize_key((string) $row['event_type']) : '';
                    $total = isset($row['total']) ? (int) $row['total'] : 0;

                    switch ($type) {
                        case 'product_view':
                            $previous_summary['product_views'] += $total;
                            break;
                        case 'overlay_view':
                            $previous_summary['overlay_displays'] += $total;
                            break;
                        case 'product_click':
                            $previous_clicks_total += $total;
                            break;
                        case 'conversion':
                            // Conversion totals are tracked for current period funnel metrics only.
                            break;
                    }
                }
            }

            $daily_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) AS event_day,
                            SUM(CASE WHEN event_type IN ('product_view','overlay_view') THEN 1 ELSE 0 END) AS views,
                            SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) AS clicks
                     FROM {$analytics_table}
                     WHERE created_at BETWEEN %s AND %s
                     GROUP BY DATE(created_at)
                     ORDER BY DATE(created_at) ASC",
                    $start_string,
                    $end_string
                ),
                ARRAY_A
            );

            if (is_array($daily_rows)) {
                foreach ($daily_rows as $row) {
                    $day = isset($row['event_day']) ? $row['event_day'] : '';
                    if (!isset($date_index[$day])) {
                        continue;
                    }

                    $index = $date_index[$day];
                    $views = isset($row['views']) ? (int) $row['views'] : 0;
                    $clicks = isset($row['clicks']) ? (int) $row['clicks'] : 0;

                    if (isset($performance_chart['views'][$index])) {
                        $performance_chart['views'][$index] = $views;
                    }
                    if (isset($performance_chart['clicks'][$index])) {
                        $performance_chart['clicks'][$index] = $clicks;
                    }
                    if (isset($traffic['chart']['views'][$index])) {
                        $traffic['chart']['views'][$index] = $views;
                    }

                    $page_views_total += $views;
                }
            }

            $session_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT session_id, MIN(created_at) AS first_event, MAX(created_at) AS last_event
                     FROM {$analytics_table}
                     WHERE created_at BETWEEN %s AND %s AND session_id IS NOT NULL AND session_id <> ''
                     GROUP BY session_id",
                    $start_string,
                    $end_string
                ),
                ARRAY_A
            );

            if (is_array($session_rows)) {
                foreach ($session_rows as $session) {
                    $session_id = isset($session['session_id']) ? (string) $session['session_id'] : '';
                    if ($session_id === '') {
                        continue;
                    }

                    $first = isset($session['first_event']) ? strtotime($session['first_event']) : 0;
                    $last = isset($session['last_event']) ? strtotime($session['last_event']) : 0;

                    if ($first > 0 && $last >= $first) {
                        $session_duration_sum += ($last - $first);
                    }

                    $session_count++;
                }
            }

            if (!empty($date_index)) {
                $visitor_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE(created_at) AS event_day, COUNT(DISTINCT session_id) AS sessions
                         FROM {$analytics_table}
                         WHERE created_at BETWEEN %s AND %s AND session_id IS NOT NULL AND session_id <> ''
                         GROUP BY DATE(created_at)",
                        $start_string,
                        $end_string
                    ),
                    ARRAY_A
                );

                if (is_array($visitor_rows)) {
                    foreach ($visitor_rows as $row) {
                        $day = isset($row['event_day']) ? $row['event_day'] : '';
                        if (!isset($date_index[$day])) {
                            continue;
                        }

                        $index = $date_index[$day];
                        $sessions = isset($row['sessions']) ? (int) $row['sessions'] : 0;

                        if (isset($traffic['chart']['visitors'][$index])) {
                            $traffic['chart']['visitors'][$index] = $sessions;
                        }
                    }
                }
            }

            $product_pages = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) FROM {$analytics_table} WHERE created_at BETWEEN %s AND %s AND post_id IS NOT NULL AND post_id > 0",
                    $start_string,
                    $end_string
                )
            );
            $traffic['product_pages'] = is_numeric($product_pages) ? (int) $product_pages : 0;

            $conversion_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) AS event_day, post_id, event_data FROM {$analytics_table} WHERE created_at BETWEEN %s AND %s AND event_type = %s",
                    $start_string,
                    $end_string,
                    'conversion'
                ),
                ARRAY_A
            );

            if (is_array($conversion_rows)) {
                foreach ($conversion_rows as $row) {
                    $amount = $this->extract_amount_from_event_data($row['event_data']);
                    if ($amount !== 0.0) {
                        $revenue_total += $amount;
                    }

                    $day = isset($row['event_day']) ? $row['event_day'] : '';
                    if (isset($date_index[$day], $revenue['trend']['values'][$date_index[$day]])) {
                        $revenue['trend']['values'][$date_index[$day]] += round($amount, 2);
                    }

                    $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                    if ($post_id > 0) {
                        if (!isset($revenue_by_post[$post_id])) {
                            $revenue_by_post[$post_id] = 0.0;
                        }
                        $revenue_by_post[$post_id] += $amount;
                    }

                    $category = $this->extract_category_from_event_data($row['event_data']);
                    if ($category !== '') {
                        if (!isset($category_totals[$category])) {
                            $category_totals[$category] = 0.0;
                        }
                        $category_totals[$category] += $amount;
                    }
                }
            }

            $performance_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id,
                            SUM(CASE WHEN event_type IN ('product_view','overlay_view') THEN 1 ELSE 0 END) AS views,
                            SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) AS clicks,
                            SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) AS conversions
                     FROM {$analytics_table}
                     WHERE created_at BETWEEN %s AND %s AND post_id IS NOT NULL AND post_id > 0
                     GROUP BY post_id",
                    $start_string,
                    $end_string
                ),
                ARRAY_A
            );

            if (is_array($performance_rows)) {
                foreach ($performance_rows as $row) {
                    $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                    if ($post_id <= 0) {
                        continue;
                    }

                    $views = isset($row['views']) ? (int) $row['views'] : 0;
                    $clicks = isset($row['clicks']) ? (int) $row['clicks'] : 0;
                    $conversions = isset($row['conversions']) ? (int) $row['conversions'] : 0;
                    $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0.0;
                    $revenue_amount = isset($revenue_by_post[$post_id]) ? (float) $revenue_by_post[$post_id] : 0.0;

                    $post_metrics[$post_id] = array(
                        'post_id' => $post_id,
                        'views' => $views,
                        'clicks' => $clicks,
                        'conversions' => $conversions,
                        'ctr' => $ctr,
                        'revenue' => $revenue_amount,
                        'title' => '',
                        'keyword' => '',
                    );
                }
            }
        }

        if ($this->table_exists($keywords_table)) {
            $keyword_rows = $wpdb->get_results(
                "SELECT post_id, primary_keyword, keyword_confidence, scan_status, product_count, product_validated, last_scanned
                 FROM {$keywords_table}
                 WHERE primary_keyword <> ''",
                ARRAY_A
            );

            if (is_array($keyword_rows)) {
                foreach ($keyword_rows as $row) {
                    $keyword = isset($row['primary_keyword']) ? sanitize_text_field((string) $row['primary_keyword']) : '';
                    if ($keyword === '') {
                        continue;
                    }

                    $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                    $scan_status = isset($row['scan_status']) ? sanitize_key((string) $row['scan_status']) : '';
                    $confidence = isset($row['keyword_confidence']) ? (float) $row['keyword_confidence'] : 0.0;
                    $product_count = isset($row['product_count']) ? (int) $row['product_count'] : 0;
                    $validated = isset($row['product_validated']) ? (int) $row['product_validated'] : 0;
                    $last_scanned = isset($row['last_scanned']) ? strtotime($row['last_scanned']) : 0;
                    $is_completed_scan = in_array($scan_status, array('completed', 'completed_ai', 'completed_manual'), true);

                    $keyword_map[$post_id] = array(
                        'keyword' => $keyword,
                        'confidence' => $confidence,
                        'scan_status' => $scan_status,
                        'validated' => $validated,
                    );

                    if (!isset($keyword_cloud[$keyword])) {
                        $keyword_cloud[$keyword] = 0;
                    }
                    $keyword_cloud[$keyword]++;

                    $keyword_overview['total']++;

                    if ($scan_status === 'completed_ai') {
                        $keyword_overview['ai']++;
                    }

                    if ($validated || $product_count > 0 || $is_completed_scan) {
                        $keyword_overview['active']++;
                    }

                    if ($last_scanned && $last_scanned >= $start_timestamp && $last_scanned <= $end_timestamp && $is_completed_scan) {
                        $summary['ai_analyses']++;
                    }

                    if ($last_scanned && $last_scanned >= $previous_start_timestamp && $last_scanned <= $previous_end_timestamp && $is_completed_scan) {
                        $previous_summary['ai_analyses']++;
                    }
                }
            }
        }

        if (!empty($post_metrics)) {
            foreach ($post_metrics as $post_id => $metrics) {
                $title = get_the_title($post_id);
                if (!is_string($title) || $title === '') {
                    $title = sprintf(__('Post #%d', 'yadore-monetizer'), $post_id);
                } else {
                    $title = wp_strip_all_tags($title);
                }

                $keyword = isset($keyword_map[$post_id]['keyword']) ? $keyword_map[$post_id]['keyword'] : '';

                $post_metrics[$post_id]['title'] = $title;
                $post_metrics[$post_id]['keyword'] = $keyword;
            }

            $performance_table['views'] = $this->prepare_performance_table($post_metrics, 'views');
            $performance_table['clicks'] = $this->prepare_performance_table($post_metrics, 'clicks');
            $performance_table['ctr'] = $this->prepare_performance_table($post_metrics, 'ctr');
            $performance_table['revenue'] = $this->prepare_performance_table($post_metrics, 'revenue');
        }

        if (!empty($keyword_map)) {
            $keyword_stats = array();

            foreach ($keyword_map as $post_id => $meta) {
                $keyword = $meta['keyword'];
                if ($keyword === '') {
                    continue;
                }

                if (!isset($keyword_stats[$keyword])) {
                    $keyword_stats[$keyword] = array(
                        'keyword' => $keyword,
                        'usage' => 0,
                        'confidence_sum' => 0.0,
                        'ai_count' => 0,
                        'views' => 0,
                        'clicks' => 0,
                        'conversions' => 0,
                    );
                }

                $keyword_stats[$keyword]['usage']++;

                if ($meta['confidence'] > 0) {
                    $keyword_stats[$keyword]['confidence_sum'] += (float) $meta['confidence'];
                }

                if ($meta['scan_status'] === 'completed_ai') {
                    $keyword_stats[$keyword]['ai_count']++;
                }

                if (isset($post_metrics[$post_id])) {
                    $keyword_stats[$keyword]['views'] += (int) $post_metrics[$post_id]['views'];
                    $keyword_stats[$keyword]['clicks'] += (int) $post_metrics[$post_id]['clicks'];
                    $keyword_stats[$keyword]['conversions'] += (int) $post_metrics[$post_id]['conversions'];
                }
            }

            foreach ($keyword_stats as $keyword => $info) {
                $usage = max(1, (int) $info['usage']);
                $avg_confidence = $info['confidence_sum'] > 0 ? round(($info['confidence_sum'] / $usage) * 100, 1) : 0;
                $ctr = $info['views'] > 0 ? round(($info['clicks'] / $info['views']) * 100, 2) : 0.0;
                $source = $info['ai_count'] >= ($usage / 2)
                    ? __('AI', 'yadore-monetizer')
                    : __('Manual', 'yadore-monetizer');

                $keyword_performance[] = array(
                    'keyword' => $keyword,
                    'usage' => $usage,
                    'ctr' => $ctr,
                    'clicks' => (int) $info['clicks'],
                    'confidence' => $avg_confidence,
                    'source' => $source,
                );
            }
        }

        $previous_page_views_total = $previous_summary['product_views'] + $previous_summary['overlay_displays'];

        $view_reference = $summary['product_views'] > 0 ? $summary['product_views'] : $page_views_total;
        if ($view_reference > 0 && $clicks_total > 0) {
            $summary['average_ctr'] = round(($clicks_total / $view_reference) * 100, 2);
        } else {
            $summary['average_ctr'] = 0;
        }

        $summary['product_views'] = max($summary['product_views'], $page_views_total);

        $previous_view_reference = $previous_summary['product_views'] > 0
            ? $previous_summary['product_views']
            : $previous_page_views_total;
        if ($previous_view_reference > 0 && $previous_clicks_total > 0) {
            $previous_summary['average_ctr'] = round(($previous_clicks_total / $previous_view_reference) * 100, 2);
        } else {
            $previous_summary['average_ctr'] = 0;
        }

        $previous_summary['product_views'] = max($previous_summary['product_views'], $previous_page_views_total);

        $funnel['page_views'] = $page_views_total;
        $funnel['product_displays'] = $summary['overlay_displays'];
        $funnel['product_clicks'] = $clicks_total;
        $funnel['conversions'] = $conversions_total;
        $funnel['display_rate'] = ($funnel['page_views'] > 0 && $funnel['product_displays'] > 0)
            ? min(100, round(($funnel['product_displays'] / $funnel['page_views']) * 100, 2))
            : 0;
        $funnel['click_rate'] = ($funnel['product_displays'] > 0 && $funnel['product_clicks'] > 0)
            ? min(100, round(($funnel['product_clicks'] / $funnel['product_displays']) * 100, 2))
            : 0;
        $funnel['conversion_rate'] = ($funnel['product_clicks'] > 0 && $funnel['conversions'] > 0)
            ? min(100, round(($funnel['conversions'] / $funnel['product_clicks']) * 100, 2))
            : 0;

        $sessions_for_average = $session_count > 0 ? $session_count : $page_views_total;
        $traffic['daily_average'] = $period > 0 ? round($sessions_for_average / $period, 1) : 0;
        $traffic['session_duration'] = $this->format_duration_label(
            $session_count > 0 ? ($session_duration_sum / max(1, $session_count)) : 0
        );

        if ($session_count > 0) {
            $sessions_with_click = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT session_id) FROM {$analytics_table} WHERE created_at BETWEEN %s AND %s AND session_id IS NOT NULL AND session_id <> '' AND event_type = %s",
                    $start_string,
                    $end_string,
                    'product_click'
                )
            );
            $sessions_with_click = is_numeric($sessions_with_click) ? (int) $sessions_with_click : 0;

            $reference = $sessions_with_click > 0 ? $sessions_with_click : ($clicks_total > 0 ? $clicks_total : 0);
            if ($reference > 0) {
                $traffic['bounce_rate'] = max(0, min(100, round(100 - (($reference / $session_count) * 100), 2)));
            } else {
                $traffic['bounce_rate'] = 100;
            }

            if (!empty($traffic['chart']['visitors'])) {
                foreach ($traffic['chart']['visitors'] as $index => $value) {
                    if ($value === 0 && isset($traffic['chart']['views'][$index])) {
                        $traffic['chart']['visitors'][$index] = $traffic['chart']['views'][$index];
                    }
                }
            }
        } elseif ($view_reference > 0 && $clicks_total > 0) {
            $traffic['bounce_rate'] = max(0, min(100, round(100 - (($clicks_total / $view_reference) * 100), 2)));
        } else {
            $traffic['bounce_rate'] = 0;
        }

        if (!empty($traffic['chart']['views'])) {
            $has_visitors = false;
            foreach ($traffic['chart']['visitors'] as $value) {
                if ($value > 0) {
                    $has_visitors = true;
                    break;
                }
            }
            if (!$has_visitors) {
                $traffic['chart']['visitors'] = $traffic['chart']['views'];
            }
        }

        if ($revenue_total > 0) {
            $revenue['monthly_estimate'] = round(($revenue_total / $period) * 30, 2);
            $revenue['revenue_per_click'] = $clicks_total > 0 ? round($revenue_total / $clicks_total, 2) : 0.0;
        }

        if (!empty($category_totals)) {
            arsort($category_totals);
            $top_category = key($category_totals);
            $revenue['top_category'] = sanitize_text_field((string) $top_category);
            $revenue['category_earnings'] = round((float) current($category_totals), 2);
        }

        $summary['trends'] = array(
            'views' => $this->calculate_trend_percentage($summary['product_views'], $previous_summary['product_views']),
            'overlays' => $this->calculate_trend_percentage($summary['overlay_displays'], $previous_summary['overlay_displays']),
            'ctr' => $this->calculate_trend_percentage($summary['average_ctr'], $previous_summary['average_ctr']),
            'ai_analyses' => $this->calculate_trend_percentage($summary['ai_analyses'], $previous_summary['ai_analyses']),
        );

        if (!empty($keyword_cloud)) {
            $cloud_items = array();
            foreach ($keyword_cloud as $keyword => $count) {
                $cloud_items[] = array(
                    'keyword' => $keyword,
                    'count' => (int) $count,
                );
            }
            usort($cloud_items, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });
            $keyword_cloud = array_slice($cloud_items, 0, 30);
        } else {
            $keyword_cloud = array();
        }

        if (!empty($keyword_performance)) {
            usort($keyword_performance, function ($a, $b) {
                return $b['clicks'] <=> $a['clicks'];
            });
            $keyword_performance = array_slice($keyword_performance, 0, 20);
        }

        return array(
            'summary' => $summary,
            'traffic' => $traffic,
            'funnel' => $funnel,
            'revenue' => $revenue,
            'performance' => array(
                'chart' => $performance_chart,
                'table' => $performance_table,
            ),
            'keywords' => array(
                'overview' => $keyword_overview,
                'cloud' => $keyword_cloud,
                'performance' => $keyword_performance,
            ),
        );
    }

    private function prepare_performance_table($metrics, $metric_key, $limit = 10) {
        if (!is_array($metrics) || empty($metrics)) {
            return array();
        }

        $items = array_values($metrics);
        usort($items, function ($a, $b) use ($metric_key) {
            $value_a = isset($a[$metric_key]) ? $a[$metric_key] : 0;
            $value_b = isset($b[$metric_key]) ? $b[$metric_key] : 0;

            if ($value_a === $value_b) {
                return 0;
            }

            return ($value_b <=> $value_a);
        });

        $items = array_slice($items, 0, $limit);
        $rows = array();

        foreach ($items as $item) {
            $rows[] = array(
                'title' => isset($item['title']) ? $item['title'] : '',
                'keyword' => isset($item['keyword']) ? $item['keyword'] : '',
                'views' => isset($item['views']) ? (int) $item['views'] : 0,
                'clicks' => isset($item['clicks']) ? (int) $item['clicks'] : 0,
                'ctr' => isset($item['ctr']) ? (float) $item['ctr'] : 0,
                'revenue' => isset($item['revenue']) ? round((float) $item['revenue'], 2) : 0.0,
            );
        }

        return $rows;
    }

    private function get_wp_timezone_object() {
        if (function_exists('wp_timezone')) {
            return wp_timezone();
        }

        $timezone_string = get_option('timezone_string');
        if (!empty($timezone_string)) {
            try {
                return new \DateTimeZone($timezone_string);
            } catch (Exception $e) {
                // Fallback to offset handling below.
            }
        }

        $offset = get_option('gmt_offset', 0);
        $hours = (int) $offset;
        $minutes = (int) round(abs($offset - $hours) * 60);
        $sign = $offset >= 0 ? '+' : '-';
        $formatted = sprintf('%s%02d:%02d', $sign, abs($hours), $minutes);

        try {
            return new \DateTimeZone($formatted);
        } catch (Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }

    private function generate_analytics_date_map(\DateTime $start, \DateTime $end) {
        $dates = array();
        $period = new \DatePeriod(
            clone $start,
            new \DateInterval('P1D'),
            (clone $end)->modify('+1 day')
        );

        foreach ($period as $date) {
            $dates[$date->format('Y-m-d')] = clone $date;
        }

        return $dates;
    }

    private function format_chart_date_label(\DateTime $date) {
        $timestamp = $date->getTimestamp();

        if (function_exists('wp_date')) {
            return wp_date('M j', $timestamp);
        }

        if (function_exists('date_i18n')) {
            return date_i18n('M j', $timestamp);
        }

        return date('M j', $timestamp);
    }

    private function format_duration_label($seconds) {
        $seconds = max(0, (int) round($seconds));
        $minutes = (int) floor($seconds / 60);
        $remaining = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remaining);
        }

        return sprintf('%ds', $remaining);
    }

    private function calculate_trend_percentage($current, $previous) {
        $current_value = is_numeric($current) ? (float) $current : 0.0;
        $previous_value = is_numeric($previous) ? (float) $previous : 0.0;

        if ($previous_value <= 0.0) {
            if ($current_value <= 0.0) {
                return array(
                    'direction' => 'neutral',
                    'change' => 0.0,
                    'current' => $current_value,
                    'previous' => 0.0,
                );
            }

            return array(
                'direction' => 'up',
                'change' => 100.0,
                'current' => $current_value,
                'previous' => 0.0,
            );
        }

        $difference = $current_value - $previous_value;
        $percent_change = ($difference / $previous_value) * 100;
        $rounded_change = round($percent_change, 1);

        $direction = 'neutral';
        if ($rounded_change > 0.1) {
            $direction = 'up';
        } elseif ($rounded_change < -0.1) {
            $direction = 'down';
        }

        return array(
            'direction' => $direction,
            'change' => $rounded_change,
            'current' => $current_value,
            'previous' => $previous_value,
        );
    }

    private function extract_amount_from_event_data($event_data) {
        if (is_array($event_data)) {
            $data = $event_data;
        } else {
            $decoded = json_decode((string) $event_data, true);
            $data = is_array($decoded) ? $decoded : null;
        }

        if (is_array($data)) {
            foreach (array('amount', 'value', 'revenue', 'total') as $key) {
                if (isset($data[$key])) {
                    $value = $data[$key];
                    if (is_numeric($value)) {
                        return (float) $value;
                    }
                    if (is_string($value)) {
                        $numeric = preg_replace('/[^0-9\.\-]/', '', $value);
                        if ($numeric !== '' && is_numeric($numeric)) {
                            return (float) $numeric;
                        }
                    }
                }
            }
        }

        if (is_string($event_data)) {
            if (preg_match('/-?[0-9]+(?:\.[0-9]+)?/', $event_data, $matches)) {
                return (float) $matches[0];
            }
        }

        return 0.0;
    }

    private function extract_category_from_event_data($event_data) {
        if (is_array($event_data)) {
            $data = $event_data;
        } else {
            $decoded = json_decode((string) $event_data, true);
            $data = is_array($decoded) ? $decoded : null;
        }

        if (is_array($data)) {
            foreach (array('category', 'segment', 'keyword', 'label') as $key) {
                if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                    return sanitize_text_field($data[$key]);
                }
            }
        }

        return '';
    }


    private function get_recent_activity_entries($limit = 6) {
        global $wpdb;

        $activities = array();
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if ($this->table_exists($analytics_table)) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT event_type, event_data, post_id, user_id, created_at FROM {$analytics_table} ORDER BY created_at DESC LIMIT %d",
                    $limit
                ),
                ARRAY_A
            );

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $activities[] = $this->format_activity_entry($row);
                }
            }
        }

        if (count($activities) < $limit) {
            $remaining = $limit - count($activities);
            $keywords_table = $wpdb->prefix . 'yadore_post_keywords';

            if ($remaining > 0 && $this->table_exists($keywords_table)) {
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT post_title, last_scanned, scan_status FROM {$keywords_table} WHERE last_scanned IS NOT NULL AND last_scanned <> '' ORDER BY last_scanned DESC LIMIT %d",
                        $remaining
                    ),
                    ARRAY_A
                );

                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $activities[] = $this->format_scan_activity_fallback($row);
                    }
                }
            }
        }

        $activities = array_filter($activities, function ($entry) {
            return is_array($entry) && !empty($entry['title']);
        });

        usort($activities, function ($a, $b) {
            $time_a = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $time_b = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;

            return $time_b <=> $time_a;
        });

        if ($limit > 0) {
            $activities = array_slice($activities, 0, $limit);
        }

        return array_values($activities);
    }

    private function format_activity_entry($row) {
        $type = isset($row['event_type']) ? sanitize_key((string) $row['event_type']) : '';
        $timestamp = isset($row['created_at']) && $row['created_at'] !== '' ? $row['created_at'] : current_time('mysql');
        $post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
        $post_title = $post_id > 0 ? get_the_title($post_id) : '';

        $data = array();
        if (!empty($row['event_data'])) {
            $decoded = json_decode($row['event_data'], true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $entry = array(
            'type' => $type !== '' ? $type : 'info',
            'icon' => 'dashicons-info',
            'title' => '',
            'description' => '',
            'timestamp' => $timestamp,
            'time' => $this->format_activity_time_absolute($timestamp),
            'relative_time' => $this->format_activity_time_relative($timestamp),
        );

        switch ($type) {
            case 'overlay_view':
                $keyword = isset($data['keyword']) ? sanitize_text_field((string) $data['keyword']) : '';
                $product_count = isset($data['product_count']) ? (int) $data['product_count'] : 0;
                $entry['icon'] = 'dashicons-visibility';
                $entry['title'] = __('Overlay ausgeliefert', 'yadore-monetizer');
                if ($keyword !== '') {
                    $entry['description'] = sprintf(
                        __('Produkt-Overlay für „%1$s“ mit %2$d Angeboten angezeigt.', 'yadore-monetizer'),
                        $keyword,
                        $product_count
                    );
                } else {
                    $entry['description'] = __('Produkt-Overlay erfolgreich ausgeliefert.', 'yadore-monetizer');
                }
                break;

            case 'shortcode_usage':
                $keyword = isset($data['keyword']) ? sanitize_text_field((string) $data['keyword']) : '';
                $format = isset($data['format']) ? sanitize_text_field((string) $data['format']) : '';
                $entry['icon'] = 'dashicons-media-code';
                $entry['title'] = __('Shortcode ausgeliefert', 'yadore-monetizer');
                if ($keyword !== '') {
                    $entry['description'] = sprintf(
                        __('Shortcode-Ausgabe für „%1$s“ im %2$s-Layout generiert.', 'yadore-monetizer'),
                        $keyword,
                        $format !== '' ? $format : __('Standard', 'yadore-monetizer')
                    );
                } else {
                    $entry['description'] = __('Shortcode-Ausgabe erfolgreich generiert.', 'yadore-monetizer');
                }
                break;

            case 'auto_injection':
                $keyword = isset($data['keyword']) ? sanitize_text_field((string) $data['keyword']) : '';
                $entry['icon'] = 'dashicons-admin-page';
                $entry['title'] = __('Automatische Produktempfehlung', 'yadore-monetizer');
                if ($keyword !== '') {
                    $entry['description'] = sprintf(
                        __('Produktempfehlung für „%s“ wurde automatisch eingefügt.', 'yadore-monetizer'),
                        $keyword
                    );
                } else {
                    $entry['description'] = __('Automatische Produktempfehlung wurde eingefügt.', 'yadore-monetizer');
                }
                $entry['type'] = 'success';
                break;

            case 'post_scan':
                $status = isset($data['status']) ? sanitize_key((string) $data['status']) : '';
                $entry['icon'] = 'dashicons-update';
                $entry['title'] = __('Beitrag gescannt', 'yadore-monetizer');
                $status_label = $this->translate_scan_status($status);
                if ($post_title !== '') {
                    $entry['description'] = sprintf(
                        __('Scan von „%1$s“ abgeschlossen (%2$s).', 'yadore-monetizer'),
                        $post_title,
                        $status_label
                    );
                } else {
                    $entry['description'] = sprintf(
                        __('Scan abgeschlossen (%s).', 'yadore-monetizer'),
                        $status_label
                    );
                }
                $entry['type'] = $status === 'failed' ? 'error' : 'success';
                break;

            case 'product_click':
                $keyword = isset($data['keyword']) ? sanitize_text_field((string) $data['keyword']) : '';
                $entry['icon'] = 'dashicons-migrate';
                $entry['title'] = __('Produktklick erfasst', 'yadore-monetizer');
                if ($keyword !== '') {
                    $entry['description'] = sprintf(
                        __('Ein Klick auf ein Angebot zu „%s“ wurde registriert.', 'yadore-monetizer'),
                        $keyword
                    );
                } else {
                    $entry['description'] = __('Ein Produktklick wurde registriert.', 'yadore-monetizer');
                }
                $entry['type'] = 'success';
                break;

            case 'conversion':
                $entry['icon'] = 'dashicons-chart-line';
                $entry['title'] = __('Conversion erfasst', 'yadore-monetizer');
                $amount = isset($data['amount']) ? sanitize_text_field((string) $data['amount']) : '';
                if ($amount !== '') {
                    $entry['description'] = sprintf(__('Neue Conversion im Wert von %s.', 'yadore-monetizer'), $amount);
                } else {
                    $entry['description'] = __('Neue Conversion registriert.', 'yadore-monetizer');
                }
                $entry['type'] = 'success';
                break;

            default:
                $entry['icon'] = 'dashicons-info';
                $entry['title'] = __('Systemaktivität', 'yadore-monetizer');
                if ($post_title !== '') {
                    $entry['description'] = sprintf(
                        __('Aktivität zu „%s“ aufgezeichnet.', 'yadore-monetizer'),
                        $post_title
                    );
                } else {
                    $entry['description'] = __('Systemaktivität aufgezeichnet.', 'yadore-monetizer');
                }
                break;
        }

        if ($entry['title'] === '') {
            $entry['title'] = __('Systemaktivität', 'yadore-monetizer');
        }

        $entry['title'] = sanitize_text_field($entry['title']);
        $entry['description'] = sanitize_text_field($entry['description']);

        return $entry;
    }

    private function format_scan_activity_fallback($row) {
        $timestamp = isset($row['last_scanned']) && $row['last_scanned'] !== '' ? $row['last_scanned'] : current_time('mysql');
        $post_title = isset($row['post_title']) ? sanitize_text_field((string) $row['post_title']) : '';
        $status = isset($row['scan_status']) ? sanitize_key((string) $row['scan_status']) : '';

        return array(
            'type' => 'scan',
            'icon' => 'dashicons-search',
            'title' => __('Automatischer Scan', 'yadore-monetizer'),
            'description' => sprintf(
                __('Beitrag „%1$s“ wurde gescannt (%2$s).', 'yadore-monetizer'),
                $post_title !== '' ? $post_title : __('Unbekannter Beitrag', 'yadore-monetizer'),
                $this->translate_scan_status($status)
            ),
            'timestamp' => $timestamp,
            'time' => $this->format_activity_time_absolute($timestamp),
            'relative_time' => $this->format_activity_time_relative($timestamp),
        );
    }

    private function format_activity_time_absolute($timestamp) {
        $time = strtotime($timestamp);
        if ($time === false || $time <= 0) {
            return '';
        }

        $format = get_option('date_format', 'd.m.Y') . ' ' . get_option('time_format', 'H:i');

        if (function_exists('date_i18n')) {
            return date_i18n($format, $time);
        }

        return date('d.m.Y H:i', $time);
    }

    private function format_activity_time_relative($timestamp) {
        $time = strtotime($timestamp);
        if ($time === false || $time <= 0) {
            return '';
        }

        if (function_exists('human_time_diff')) {
            $now = current_time('timestamp');
            $diff = human_time_diff($time, $now);
            return sprintf(__('vor %s', 'yadore-monetizer'), $diff);
        }

        return '';
    }

    private function translate_scan_status($status) {
        switch ($status) {
            case 'completed':
            case 'completed_manual':
            case 'completed_ai':
                return __('erfolgreich', 'yadore-monetizer');

            case 'failed':
                return __('fehlgeschlagen', 'yadore-monetizer');

            case 'skipped':
                return __('übersprungen', 'yadore-monetizer');

            case 'pending':
                return __('ausstehend', 'yadore-monetizer');

            default:
                return __('in Bearbeitung', 'yadore-monetizer');
        }
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

    private function reset_table_exists_cache() {
        $this->table_exists_cache = array();
    }

    private function escape_like_value($value) {
        $value = (string) $value;

        if (function_exists('esc_like')) {
            return esc_like($value);
        }

        return addcslashes($value, '_%');
    }

    private function table_exists($table_name) {
        if (!is_string($table_name) || $table_name === '') {
            return false;
        }

        if (isset($this->table_exists_cache[$table_name])) {
            return $this->table_exists_cache[$table_name];
        }

        global $wpdb;

        if (!isset($wpdb) || !($wpdb instanceof wpdb)) {
            return false;
        }

        $like = $this->escape_like_value($table_name);
        $result = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
        $exists = is_string($result) && $result === $table_name;

        $this->table_exists_cache[$table_name] = $exists;

        return $exists;
    }

    // v2.7: Shortcode Implementation (Enhanced)
    public function shortcode_products($atts) {
        if (!get_option('yadore_shortcode_enabled', true)) {
            return '';
        }

        $atts = shortcode_atts(array(
            'keyword' => '',
            'limit' => 6,
            'format' => 'grid',
            'cache' => 'true',
            'class' => '',
            'template' => ''
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

            $template_key = '';
            if (!empty($atts['template'])) {
                $template_key = $this->sanitize_template_selection($atts['template'], 'shortcode');
            }

            $format_map = array(
                'grid' => 'default-grid',
                'list' => 'default-list',
                'inline' => 'default-inline',
            );

            if ($template_key === '' && isset($format_map[$format])) {
                $template_key = $format_map[$format];
            }

            if ($template_key === '') {
                $template_key = $this->sanitize_template_selection(
                    get_option('yadore_default_shortcode_template', 'default-grid'),
                    'shortcode',
                    'default-grid'
                );
            }

            if ($template_key === '') {
                $template_key = 'default-grid';
            }

            $additional_classes = !empty($atts['class']) ? ' ' . sanitize_html_class($atts['class']) : '';
            $output = $this->render_products_with_template($template_key, $products, array(
                'type' => 'shortcode',
                'additional_classes' => $additional_classes,
                'attributes' => $atts,
            ));

            $format_for_tracking = $format;
            if (strpos($template_key, 'default-') !== 0) {
                $format_for_tracking = 'custom';
            }

            // v2.7: Track shortcode usage
            $this->track_shortcode_usage($keyword, $limit, $format_for_tracking, $template_key);

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

            $template_key = $this->sanitize_template_selection(
                get_option('yadore_auto_injection_template', 'default-inline'),
                'shortcode',
                'default-inline'
            );

            if ($template_key === '') {
                $template_key = 'default-inline';
            }

            $injected_markup = $this->shortcode_products(array(
                'keyword' => $keyword,
                'limit' => 3,
                'template' => $template_key,
            ));

            switch ($injection_method) {
                case 'after_paragraph':
                    $paragraphs = explode('</p>', $content);
                    if (count($paragraphs) > $injection_position) {
                        $paragraphs[$injection_position - 1] .= '</p>' . $injected_markup;
                        $content = implode('</p>', $paragraphs);
                    } else {
                        $content .= $injected_markup;
                    }
                    break;

                case 'end_of_content':
                    $content .= $injected_markup;
                    break;

                case 'before_content':
                    $content = $injected_markup . $content;
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

        if (!$this->table_exists($analytics_table)) {
            return;
        }

        $payload = array(
            'keyword' => $keyword,
            'product_count' => $product_count,
        );

        $wpdb->insert($analytics_table, array(
            'event_type' => 'overlay_view',
            'event_data' => function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload),
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'session_id' => function_exists('session_id') ? session_id() : '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ));
    }

    private function track_shortcode_usage($keyword, $limit, $format, $template_key) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($analytics_table)) {
            return;
        }

        $payload = array(
            'keyword' => $keyword,
            'limit' => $limit,
            'format' => $format,
            'template' => $template_key,
        );

        $wpdb->insert($analytics_table, array(
            'event_type' => 'shortcode_usage',
            'event_data' => function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload),
            'post_id' => get_the_ID(),
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ));
    }

    private function track_auto_injection($post_id, $keyword) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($analytics_table)) {
            return;
        }

        $payload = array(
            'keyword' => $keyword,
        );

        $wpdb->insert($analytics_table, array(
            'event_type' => 'auto_injection',
            'event_data' => function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload),
            'post_id' => $post_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ));
    }

    private function record_product_click_event($product_id, $post_id, $page_url) {
        global $wpdb;

        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($analytics_table)) {
            return false;
        }

        $payload = array(
            'source' => 'frontend',
            'product_id' => $product_id,
            'page_url' => $page_url,
        );

        $encoded_payload = function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload);

        $inserted = $wpdb->insert(
            $analytics_table,
            array(
                'event_type' => 'product_click',
                'event_data' => $encoded_payload,
                'post_id' => $post_id > 0 ? $post_id : 0,
                'user_id' => get_current_user_id(),
                'session_id' => $this->get_current_session_identifier(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            $this->log('Failed to record product click event for analytics table', 'warning');
            return false;
        }

        return true;
    }

    private function get_current_session_identifier() {
        if (function_exists('wp_get_session_token')) {
            $token = wp_get_session_token();
            if (is_string($token) && $token !== '') {
                return substr($token, 0, 32);
            }
        }

        if (function_exists('session_id')) {
            $session = session_id();
            if (is_string($session) && $session !== '') {
                return substr($session, 0, 32);
            }
        }

        return '';
    }

    private function maybe_sync_recent_clicks($days = 3) {
        try {
            $days = max(1, (int) $days);
            $timezone = $this->get_wp_timezone_object();

            $end = new \DateTime('now', $timezone);
            $end->setTime(23, 59, 59);

            $start = clone $end;
            $start->setTime(0, 0, 0);

            if ($days > 1) {
                $start->modify(sprintf('-%d days', $days - 1));
            }

            $this->maybe_sync_clicks_from_api($start, $end);
        } catch (Exception $e) {
            $this->log_error('Recent click synchronization failed', $e, 'warning');
        }
    }

    private function maybe_sync_clicks_from_api(\DateTime $start_date, \DateTime $end_date) {
        global $wpdb;

        $api_key = $this->sanitize_api_key(get_option('yadore_api_key', ''));
        if ($api_key === '') {
            return;
        }

        $analytics_table = $wpdb->prefix . 'yadore_analytics';
        $clicks_table = $wpdb->prefix . 'yadore_api_clicks';

        if (!$this->table_exists($analytics_table) || !$this->table_exists($clicks_table)) {
            return;
        }

        $market_setting = $this->sanitize_market(get_option('yadore_market', ''));
        $market_param = $market_setting !== '' ? strtolower($market_setting) : '';

        $utc = new \DateTimeZone('UTC');

        $start = clone $start_date;
        $start->setTimezone($utc);
        $start->setTime(0, 0, 0);

        $end = clone $end_date;
        $end->setTimezone($utc);
        $end->setTime(0, 0, 0);

        if ($start > $end) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }

        $sync_log = get_option('yadore_click_sync_log', array());
        if (!is_array($sync_log)) {
            $sync_log = array();
        }

        $today = new \DateTime('now', $utc);
        $today->setTime(0, 0, 0);

        $now = time();
        $updated = false;

        $current = clone $start;

        while ($current <= $end && $current <= $today) {
            $date_string = $current->format('Y-m-d');
            $last_sync = isset($sync_log[$date_string]) ? (int) $sync_log[$date_string] : 0;
            $needs_sync = ($last_sync === 0) || (($now - $last_sync) >= 12 * HOUR_IN_SECONDS);

            if ($needs_sync) {
                $response = $this->fetch_click_data_for_date($date_string, $api_key, $market_param);
                if ($response['success']) {
                    $this->store_click_data_from_api($response['data'], $date_string);
                    $sync_log[$date_string] = $now;
                    $updated = true;
                }
            }

            $current->modify('+1 day');
        }

        if ($updated) {
            if (count($sync_log) > 120) {
                ksort($sync_log);
                $sync_log = array_slice($sync_log, -120, null, true);
            }

            update_option('yadore_click_sync_log', $sync_log, false);
        }
    }

    private function fetch_click_data_for_date($date_string, $api_key, $market) {
        $endpoint = 'https://api.yadore.com/v2/conversion/detail';
        $query_args = array(
            'date' => $date_string,
            'format' => 'json',
        );

        if (is_string($market) && $market !== '') {
            $query_args['market'] = strtolower($market);
        }

        $url = add_query_arg($query_args, $endpoint);

        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'API-Key' => $api_key,
                'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            ),
            'timeout' => 20,
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->log_error(
                'Yadore API click fetch failed: ' . $response->get_error_message(),
                null,
                'warning',
                array('date' => $date_string)
            );
            $this->log_api_call('yadore', $url, 'error', array('message' => $response->get_error_message()));
            return array('success' => false);
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 200 && $code < 300 && is_array($decoded)) {
            $this->log_api_call('yadore', $url, 'success', array(
                'date' => $date_string,
                'total_clicks' => isset($decoded['totalClicks']) ? (int) $decoded['totalClicks'] : 0,
            ));

            return array('success' => true, 'data' => $decoded);
        }

        $this->log_error(
            'Unexpected Yadore API click response',
            null,
            'warning',
            array(
                'date' => $date_string,
                'status' => $code,
                'body' => is_string($body) ? $body : '',
            )
        );
        $this->log_api_call('yadore', $url, 'error', array('status' => $code, 'body' => $body));

        return array('success' => false);
    }

    private function store_click_data_from_api($payload, $default_date) {
        global $wpdb;

        if (!is_array($payload)) {
            return 0;
        }

        $clicks = isset($payload['clicks']) && is_array($payload['clicks']) ? $payload['clicks'] : array();

        if (empty($clicks)) {
            return 0;
        }

        $click_table = $wpdb->prefix . 'yadore_api_clicks';
        $analytics_table = $wpdb->prefix . 'yadore_analytics';

        if (!$this->table_exists($click_table) || !$this->table_exists($analytics_table)) {
            return 0;
        }

        $new_records = 0;

        foreach ($clicks as $click) {
            if (!is_array($click)) {
                continue;
            }

            $click_id = isset($click['clickId']) ? trim((string) $click['clickId']) : '';
            if ($click_id === '') {
                continue;
            }

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$click_table} WHERE click_id = %s",
                $click_id
            ));

            if ($existing) {
                continue;
            }

            $timestamp = $this->parse_click_timestamp($click['date'] ?? '', $default_date);

            $market = '';
            if (isset($click['market'])) {
                $market_candidate = $this->sanitize_market($click['market']);
                if ($market_candidate !== '') {
                    $market = strtolower($market_candidate);
                } else {
                    $market = strtolower(trim((string) $click['market']));
                }
            }

            $merchant = is_array($click['merchant']) ? $click['merchant'] : array();
            $merchant_id = isset($merchant['id']) ? sanitize_text_field((string) $merchant['id']) : '';
            $merchant_name = isset($merchant['name']) ? sanitize_text_field((string) $merchant['name']) : '';

            $placement_id = '';
            if (isset($click['placementId']) && $click['placementId'] !== false) {
                $placement_id = sanitize_text_field((string) $click['placementId']);
            }

            $sales_amount = 0.0;
            if (isset($click['sales']) && is_numeric($click['sales'])) {
                $sales_amount = (float) $click['sales'];
            }

            $raw_payload = function_exists('wp_json_encode') ? wp_json_encode($click) : json_encode($click);

            $wpdb->insert(
                $click_table,
                array(
                    'click_id' => $click_id,
                    'clicked_at' => gmdate('Y-m-d H:i:s', $timestamp),
                    'market' => $market,
                    'merchant_id' => $merchant_id,
                    'merchant_name' => $merchant_name,
                    'placement_id' => $placement_id,
                    'sales_amount' => $sales_amount,
                    'raw_payload' => $raw_payload,
                    'synced_at' => gmdate('Y-m-d H:i:s'),
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s')
            );

            $event_payload = array(
                'source' => 'yadore_api',
                'click_id' => $click_id,
                'market' => $market,
                'merchant_id' => $merchant_id,
                'merchant_name' => $merchant_name,
                'sales' => $sales_amount,
            );

            $wpdb->insert(
                $analytics_table,
                array(
                    'event_type' => 'product_click',
                    'event_data' => function_exists('wp_json_encode') ? wp_json_encode($event_payload) : json_encode($event_payload),
                    'post_id' => 0,
                    'user_id' => 0,
                    'session_id' => '',
                    'ip_address' => '',
                    'user_agent' => 'yadore-api',
                    'created_at' => gmdate('Y-m-d H:i:s', $timestamp),
                ),
                array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
            );

            $new_records++;
        }

        return $new_records;
    }

    private function parse_click_timestamp($raw_date, $fallback_date) {
        $timestamp = false;

        if (is_string($raw_date) && $raw_date !== '') {
            $timestamp = strtotime($raw_date);
        }

        if ($timestamp === false || $timestamp <= 0) {
            $timestamp = strtotime($fallback_date . ' 00:00:00 UTC');
        }

        if ($timestamp === false || $timestamp <= 0) {
            $timestamp = time();
        }

        return (int) $timestamp;
    }

    private function log_api_call($api_type, $endpoint, $status, $data = array()) {
        global $wpdb;
        if (!isset($wpdb) || !($wpdb instanceof wpdb)) {
            return;
        }

        $logs_table = $wpdb->prefix . 'yadore_api_logs';

        if (!$this->table_exists($logs_table)) {
            return;
        }

        $payload = function_exists('wp_json_encode') ? wp_json_encode($data) : json_encode($data);

        $wpdb->insert($logs_table, array(
            'api_type' => $api_type,
            'endpoint_url' => $endpoint,
            'success' => $status === 'success' ? 1 : 0,
            'response_body' => $payload,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ));
    }

    public function register_keyword_meta_box($post_type, $post = null) {
        if (!in_array($post_type, $this->get_scannable_post_types(), true)) {
            return;
        }

        add_meta_box(
            'yadore_post_keywords',
            __('Yadore Monetizer Keyword', 'yadore-monetizer'),
            array($this, 'render_keyword_meta_box'),
            $post_type,
            'side',
            'default'
        );
    }

    public function render_keyword_meta_box($post) {
        if (!($post instanceof WP_Post)) {
            $post = get_post($post);
        }

        if (!($post instanceof WP_Post)) {
            return;
        }

        wp_nonce_field('yadore_keyword_meta', 'yadore_keyword_meta_nonce');

        $primary_keyword = '';
        $fallback_keyword = '';
        $status_label = '';
        $validated = false;

        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if ($this->table_exists($table)) {
            $record = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT primary_keyword, fallback_keyword, scan_status, product_validated FROM {$table} WHERE post_id = %d",
                    $post->ID
                ),
                ARRAY_A
            );

            if (is_array($record)) {
                $primary_keyword = isset($record['primary_keyword']) ? (string) $record['primary_keyword'] : '';
                $fallback_keyword = isset($record['fallback_keyword']) ? (string) $record['fallback_keyword'] : '';
                $validated = !empty($record['product_validated']);

                $scan_status = isset($record['scan_status']) ? (string) $record['scan_status'] : '';
                $status_label = $this->get_scan_status_label($scan_status);
            }
        }

        echo '<p>' . esc_html__(
            'Set a manual product keyword for this content. Leave empty to keep using the automatic scanner.',
            'yadore-monetizer'
        ) . '</p>';

        echo '<p><label for="yadore_primary_keyword"><strong>' . esc_html__(
            'Primary keyword',
            'yadore-monetizer'
        ) . '</strong></label>';
        echo '<input type="text" id="yadore_primary_keyword" name="yadore_primary_keyword" class="widefat" value="' . esc_attr($primary_keyword) . '" placeholder="' . esc_attr__('e.g. Dyson V15 Vacuum', 'yadore-monetizer') . '" /></p>';

        echo '<p><label for="yadore_fallback_keyword"><strong>' . esc_html__(
            'Fallback keyword',
            'yadore-monetizer'
        ) . '</strong></label>';
        echo '<input type="text" id="yadore_fallback_keyword" name="yadore_fallback_keyword" class="widefat" value="' . esc_attr($fallback_keyword) . '" placeholder="' . esc_attr__('Used when the primary keyword has no results', 'yadore-monetizer') . '" /></p>';

        echo '<p class="description">' . esc_html__(
            'The fallback keyword is used if no products are found for the primary keyword.',
            'yadore-monetizer'
        ) . '</p>';

        if ($status_label !== '') {
            $status_text = $validated
                ? sprintf(
                    /* translators: %s: keyword status */
                    esc_html__('Current status: %s (validated)', 'yadore-monetizer'),
                    esc_html($status_label)
                )
                : sprintf(
                    /* translators: %s: keyword status */
                    esc_html__('Current status: %s', 'yadore-monetizer'),
                    esc_html($status_label)
                );

            echo '<p class="description">' . $status_text . '</p>';
        }
    }

    public function save_keyword_meta_box($post_id, $post) {
        if (!isset($_POST['yadore_keyword_meta_nonce']) || !wp_verify_nonce($_POST['yadore_keyword_meta_nonce'], 'yadore_keyword_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!($post instanceof WP_Post)) {
            $post = get_post($post_id);
        }

        if (!($post instanceof WP_Post)) {
            return;
        }

        if (!in_array($post->post_type, $this->get_scannable_post_types(), true)) {
            return;
        }

        $primary = isset($_POST['yadore_primary_keyword'])
            ? $this->sanitize_single_keyword(wp_unslash($_POST['yadore_primary_keyword']))
            : '';
        $fallback = isset($_POST['yadore_fallback_keyword'])
            ? $this->sanitize_single_keyword(wp_unslash($_POST['yadore_fallback_keyword']))
            : '';

        $validated = ($primary !== '' || $fallback !== '');

        global $wpdb;
        $table = $wpdb->prefix . 'yadore_post_keywords';

        if (!$this->table_exists($table)) {
            return;
        }

        $data = array(
            'post_title' => get_the_title($post_id),
            'primary_keyword' => $primary,
            'fallback_keyword' => $fallback,
            'product_validated' => $validated ? 1 : 0,
            'keyword_confidence' => $validated ? 100 : 0,
            'scan_status' => $validated ? 'completed_manual' : 'pending',
            'scan_error' => '',
        );
        $formats = array('%s', '%s', '%s', '%d', '%f', '%s', '%s');

        if ($validated) {
            $data['last_scanned'] = current_time('mysql');
            $formats[] = '%s';
        }

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM {$table} WHERE post_id = %d", $post_id),
            ARRAY_A
        );

        if ($existing) {
            $wpdb->update($table, $data, array('post_id' => $post_id), $formats, array('%d'));
        } else {
            $insert = array(
                'post_id' => $post_id,
                'post_title' => get_the_title($post_id),
                'primary_keyword' => $primary,
                'fallback_keyword' => $fallback,
                'keyword_confidence' => $validated ? 100 : 0,
                'product_validated' => $validated ? 1 : 0,
                'product_count' => 0,
                'word_count' => $this->count_words_in_text($post->post_content),
                'content_hash' => '',
                'scan_status' => $validated ? 'completed_manual' : 'pending',
                'scan_error' => '',
                'scan_attempts' => $validated ? 1 : 0,
                'scan_duration_ms' => 0,
            );
            $insert_formats = array('%d', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d');

            if ($validated) {
                $insert['last_scanned'] = current_time('mysql');
                $insert_formats[] = '%s';
            }

            $wpdb->insert($table, $insert, $insert_formats);
        }

        if ($validated) {
            $this->update_scanned_posts_stat();
        }
    }

    public function register_template_meta_boxes() {
        add_meta_box(
            'yadore_template_settings',
            __('Template Settings', 'yadore-monetizer'),
            array($this, 'render_template_meta_box'),
            'yadore_template',
            'side',
            'default'
        );
    }

    public function render_template_meta_box($post) {
        if (!($post instanceof WP_Post)) {
            return;
        }

        wp_nonce_field('yadore_template_meta', 'yadore_template_meta_nonce');

        $current_type = get_post_meta($post->ID, '_yadore_template_type', true);
        if (!in_array($current_type, array('overlay', 'shortcode'), true)) {
            $current_type = 'shortcode';
        }

        $options = array(
            'shortcode' => __('Shortcode Template', 'yadore-monetizer'),
            'overlay' => __('Overlay Template', 'yadore-monetizer'),
        );

        echo '<p><label for="yadore_template_type"><strong>' . esc_html__('Template Type', 'yadore-monetizer') . '</strong></label></p>';
        echo '<select name="yadore_template_type" id="yadore_template_type" class="widefat">';
        foreach ($options as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                selected($current_type, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        $description = __('Wrap repeatable markup with [yadore_product_loop]...[/yadore_product_loop]. Available placeholders: {{title}}, {{description}}, {{price}}, {{price_amount}}, {{price_currency}}, {{merchant_name}}, {{click_url}}, {{image_url}}, {{image_tag}}, {{promo_text}}, {{button_label}}, {{tracking_attributes}}, {{index}}, {{id}}.', 'yadore-monetizer');
        echo '<p class="description">' . esc_html($description) . '</p>';
    }

    public function save_template_meta($post_id) {
        if (!isset($_POST['yadore_template_meta_nonce']) || !wp_verify_nonce($_POST['yadore_template_meta_nonce'], 'yadore_template_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['yadore_template_type'])) {
            $type = sanitize_text_field(wp_unslash($_POST['yadore_template_type']));
            if (!in_array($type, array('overlay', 'shortcode'), true)) {
                $type = 'shortcode';
            }
            update_post_meta($post_id, '_yadore_template_type', $type);
        }
    }

    public function register_template_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ($key === 'title') {
                $new_columns['template_type'] = __('Template Type', 'yadore-monetizer');
                $new_columns['template_key'] = __('Template Key', 'yadore-monetizer');
            }
        }

        if (!isset($new_columns['template_type'])) {
            $new_columns['template_type'] = __('Template Type', 'yadore-monetizer');
        }

        if (!isset($new_columns['template_key'])) {
            $new_columns['template_key'] = __('Template Key', 'yadore-monetizer');
        }

        return $new_columns;
    }

    public function render_template_columns($column, $post_id) {
        if ($column === 'template_type') {
            $type = get_post_meta($post_id, '_yadore_template_type', true);

            if ($type === 'overlay') {
                echo esc_html__('Overlay', 'yadore-monetizer');
            } else {
                echo esc_html__('Shortcode', 'yadore-monetizer');
            }
        }

        if ($column === 'template_key') {
            $post = get_post($post_id);
            if ($post instanceof WP_Post) {
                echo esc_html($this->get_template_storage_key($post));
            } else {
                echo '&#8211;';
            }
        }
    }

    private function get_template_choices($type) {
        $choices = array();

        $builtin = $this->get_builtin_templates($type);
        foreach ($builtin as $key => $template) {
            $choices[$key] = $template['label'];
        }

        $custom = $this->get_custom_templates($type);
        foreach ($custom as $template) {
            $choices[$template['key']] = $template['label'];
        }

        return $choices;
    }

    private function get_builtin_templates($type) {
        $templates = array();

        if ($type === 'overlay') {
            $templates['default-overlay'] = array(
                'label' => __('Modern Overlay (Default)', 'yadore-monetizer'),
                'file' => YADORE_PLUGIN_DIR . 'templates/overlay-products-default.php',
                'source' => 'builtin',
            );
        } else {
            $templates['default-grid'] = array(
                'label' => __('Product Grid (Default)', 'yadore-monetizer'),
                'file' => YADORE_PLUGIN_DIR . 'templates/products-grid.php',
                'source' => 'builtin',
            );
            $templates['default-list'] = array(
                'label' => __('Product List (Default)', 'yadore-monetizer'),
                'file' => YADORE_PLUGIN_DIR . 'templates/products-list.php',
                'source' => 'builtin',
            );
            $templates['default-inline'] = array(
                'label' => __('Inline Highlight (Default)', 'yadore-monetizer'),
                'file' => YADORE_PLUGIN_DIR . 'templates/products-inline.php',
                'source' => 'builtin',
            );
        }

        return $templates;
    }

    private function get_custom_templates($type) {
        $type = $type === 'overlay' ? 'overlay' : 'shortcode';

        $posts = get_posts(array(
            'post_type' => 'yadore_template',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_key' => '_yadore_template_type',
            'meta_value' => $type,
        ));

        $templates = array();

        foreach ($posts as $post) {
            if (!($post instanceof WP_Post)) {
                continue;
            }

            $templates[] = array(
                'key' => $this->get_template_storage_key($post),
                'label' => $post->post_title !== '' ? $post->post_title : $post->post_name,
                'content' => $post->post_content,
                'id' => $post->ID,
                'slug' => $post->post_name,
                'type' => $type,
            );
        }

        return $templates;
    }

    private function get_template_definition($key, $type) {
        if (!is_string($key) || $key === '') {
            return null;
        }

        $type = $type === 'overlay' ? 'overlay' : 'shortcode';

        $builtin = $this->get_builtin_templates($type);
        if (isset($builtin[$key])) {
            $definition = $builtin[$key];
            $definition['key'] = $key;
            $definition['type'] = $type;
            $definition['source'] = 'builtin';
            return $definition;
        }

        if (strpos($key, 'custom:') === 0) {
            $identifier = substr($key, 7);
            $post = null;

            if ($identifier !== '' && ctype_digit($identifier)) {
                $candidate = get_post((int) $identifier);
                if ($candidate instanceof WP_Post && $candidate->post_type === 'yadore_template') {
                    $meta_type = get_post_meta($candidate->ID, '_yadore_template_type', true);
                    if ($meta_type === $type) {
                        $post = $candidate;
                    }
                }
            }

            if (!$post) {
                $post = $this->get_template_post_by_slug($identifier, $type);
            }

            if ($post instanceof WP_Post) {
                return array(
                    'key' => $this->get_template_storage_key($post),
                    'label' => $post->post_title !== '' ? $post->post_title : $post->post_name,
                    'source' => 'custom',
                    'type' => $type,
                    'id' => $post->ID,
                    'content' => $post->post_content,
                );
            }
        }

        return null;
    }

    private function get_template_post_by_slug($slug, $type, $include_trashed = false) {
        $slug = sanitize_title($slug);
        if ($slug === '') {
            return null;
        }

        $statuses = 'publish';
        if ($include_trashed) {
            $statuses = array('publish', 'draft', 'pending', 'future', 'private', 'trash');
        }

        $posts = get_posts(array(
            'name' => $slug,
            'post_type' => 'yadore_template',
            'post_status' => $statuses,
            'numberposts' => 1,
            'meta_key' => '_yadore_template_type',
            'meta_value' => $type,
        ));

        if (empty($posts)) {
            return null;
        }

        $post = $posts[0];
        return $post instanceof WP_Post ? $post : null;
    }

    private function get_template_storage_key(WP_Post $post) {
        $slug = $post->post_name;
        if (!is_string($slug) || $slug === '') {
            $slug = sanitize_title($post->post_title);
        }

        if ($slug === '') {
            $slug = 'template-' . $post->ID;
        }

        return 'custom:' . $slug;
    }

    private function sanitize_template_selection($value, $type, $fallback = '') {
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = is_string($value) ? trim($value) : '';

        if ($value !== '') {
            $value = strtolower($value);

            if (strpos($value, 'custom:') === 0) {
                $identifier = substr($value, 7);

                if ($identifier === '') {
                    $value = '';
                } elseif (ctype_digit($identifier)) {
                    $value = 'custom:' . $identifier;
                } else {
                    $slug = sanitize_title($identifier);
                    $value = $slug === '' ? '' : 'custom:' . $slug;
                }
            } else {
                $value = preg_replace('/[^a-z0-9\-]/', '', $value);
            }
        }

        $choices = array_keys($this->get_template_choices($type));

        if ($value !== '' && in_array($value, $choices, true)) {
            return $value;
        }

        if ($value !== '' && strpos($value, 'custom:') === 0) {
            $identifier = substr($value, 7);
            if ($identifier !== '' && ctype_digit($identifier)) {
                $candidate = get_post((int) $identifier);
                if ($candidate instanceof WP_Post && $candidate->post_type === 'yadore_template') {
                    $meta_type = get_post_meta($candidate->ID, '_yadore_template_type', true);
                    $resolved_key = $this->get_template_storage_key($candidate);
                    if ($meta_type === ($type === 'overlay' ? 'overlay' : 'shortcode') && in_array($resolved_key, $choices, true)) {
                        return $resolved_key;
                    }
                }
            }
        }

        if ($fallback !== '' && in_array($fallback, $choices, true)) {
            return $fallback;
        }

        $builtin = $this->get_builtin_templates($type);
        $default_key = array_key_first($builtin);

        return $default_key ? $default_key : '';
    }

    private function render_products_with_template($template_key, $products, $context = array()) {
        if (!is_array($products)) {
            $products = array();
        }

        $type = isset($context['type']) && $context['type'] === 'overlay' ? 'overlay' : 'shortcode';

        $definition = $this->get_template_definition($template_key, $type);
        if ($definition === null) {
            $fallback = $type === 'overlay' ? 'default-overlay' : 'default-grid';
            $definition = $this->get_template_definition($fallback, $type);
        }

        if ($definition === null) {
            return '';
        }

        if (($definition['source'] ?? '') === 'builtin') {
            $file = $definition['file'] ?? '';
            if (!is_string($file) || $file === '' || !file_exists($file)) {
                return '';
            }

            ob_start();

            if ($type === 'shortcode') {
                $offers = $products;
                $additional_classes = $context['additional_classes'] ?? '';
                $attributes = $context['attributes'] ?? array();
                include $file;
            } else {
                $button_label = $context['button_label'] ?? __('Zum Angebot →', 'yadore-monetizer');
                $limit = isset($context['limit']) ? (int) $context['limit'] : count($products);
                if ($limit > 0) {
                    $products = array_slice($products, 0, $limit);
                }
                $keyword = $context['keyword'] ?? '';
                include $file;
            }

            return (string) ob_get_clean();
        }

        $context['type'] = $type;
        return $this->render_custom_template_markup($definition, $products, $context);
    }

    private function render_custom_template_markup(array $template, array $products, array $context = array()) {
        $content = isset($template['content']) ? (string) $template['content'] : '';
        if ($content === '') {
            return $this->get_default_template_empty_message($context['type'] ?? 'shortcode');
        }

        $sanitized = $this->sanitize_template_content($content);
        $pattern = '/\[yadore_product_loop\](.*?)\[\/yadore_product_loop\]/is';
        $loop_markup = $sanitized;
        $before = '';
        $after = '';

        if (preg_match($pattern, $sanitized, $matches, PREG_OFFSET_CAPTURE)) {
            $loop_markup = $matches[1][0];
            $loop_start = $matches[0][1];
            $loop_end = $loop_start + strlen($matches[0][0]);
            $before = substr($sanitized, 0, $loop_start);
            $after = substr($sanitized, $loop_end);
        }

        $rendered = '';
        $index = 0;

        foreach ($products as $product) {
            $index++;
            $placeholders = $this->prepare_template_placeholders(is_array($product) ? $product : array(), $index, $context);
            $item_markup = $loop_markup;

            foreach ($placeholders as $token => $replacement) {
                $item_markup = str_replace('{{' . $token . '}}', $replacement, $item_markup);
            }

            $rendered .= $item_markup;
        }

        if ($rendered === '') {
            $empty_message = $context['empty_message'] ?? $this->get_default_template_empty_message($context['type'] ?? 'shortcode');
            return $before . $empty_message . $after;
        }

        return $before . $rendered . $after;
    }

    private function prepare_template_placeholders(array $product, $index, array $context = array()) {
        $price_parts = yadore_get_formatted_price_parts($product['price'] ?? array());
        $price_amount = $price_parts['amount'];
        $price_currency = $price_parts['currency'];

        $title = isset($product['title']) ? sanitize_text_field((string) $product['title']) : __('Product', 'yadore-monetizer');
        $description = isset($product['description']) ? sanitize_text_field((string) $product['description']) : '';
        $merchant = isset($product['merchant']['name']) ? sanitize_text_field((string) $product['merchant']['name']) : '';
        $promo = isset($product['promoText']) ? sanitize_text_field((string) $product['promoText']) : '';
        $id = isset($product['id']) ? sanitize_text_field((string) $product['id']) : '';

        $click_url_raw = isset($product['clickUrl']) ? (string) $product['clickUrl'] : '#';
        $click_url = esc_url($click_url_raw !== '' ? $click_url_raw : '#');

        $image_url_raw = '';
        if (isset($product['image']['url']) && $product['image']['url'] !== '') {
            $image_url_raw = (string) $product['image']['url'];
        } elseif (isset($product['thumbnail']['url']) && $product['thumbnail']['url'] !== '') {
            $image_url_raw = (string) $product['thumbnail']['url'];
        }
        $image_url = esc_url($image_url_raw);

        $button_label = $context['button_label'] ?? __('Zum Angebot →', 'yadore-monetizer');

        $price_combined = trim($price_amount . ($price_currency !== '' ? ' ' . $price_currency : ''));

        $image_tag = '';
        if ($image_url !== '') {
            $image_tag = '<img src="' . $image_url . '" alt="' . esc_attr($title) . '" loading="lazy">';
        }

        $tracking_attributes = sprintf(
            'data-yadore-click="%s" data-product-id="%s" data-click-url="%s"',
            esc_attr($id),
            esc_attr($id),
            esc_attr($click_url)
        );

        return array(
            'index' => (string) $index,
            'id' => esc_html($id),
            'title' => esc_html($title),
            'description' => esc_html($description),
            'merchant_name' => esc_html($merchant),
            'price_amount' => esc_html($price_amount),
            'price_currency' => esc_html($price_currency),
            'price' => esc_html($price_combined),
            'click_url' => $click_url,
            'image_url' => $image_url,
            'promo_text' => esc_html($promo),
            'button_label' => esc_html($button_label),
            'image_tag' => $image_tag,
            'tracking_attributes' => $tracking_attributes,
        );
    }

    private function get_default_template_empty_message($type) {
        if ($type === 'overlay') {
            return '<div class="overlay-no-products"><div class="no-products-icon">🔍</div><h3>'
                . esc_html__('No products found', 'yadore-monetizer')
                . '</h3><p>'
                . esc_html__('We couldn\'t find any relevant products for this content.', 'yadore-monetizer')
                . '</p></div>';
        }

        return '<div class="yadore-no-results">'
            . esc_html__('No products available at the moment.', 'yadore-monetizer')
            . '</div>';
    }

    private function sanitize_template_content($content) {
        return wp_kses_post($content);
    }

    // Continue implementing all other methods...
    // (More methods will be added in subsequent parts)

    // Basic logging methods
    private function log_error($message, $exception = null, $severity = 'medium', $context = array()) {
        if (!get_option('yadore_error_logging_enabled', true)) {
            return;
        }

        $timestamp = function_exists('current_time') ? current_time('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s');
        $this->error_log[] = '[' . $timestamp . '] ' . $severity . ': ' . $message;

        global $wpdb;

        if (!isset($wpdb) || !($wpdb instanceof wpdb)) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('Yadore Error Logging Skipped (database unavailable): ' . $message);
            }
            return;
        }

        $error_logs_table = $wpdb->prefix . 'yadore_error_logs';

        if (!$this->table_exists($error_logs_table)) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('Yadore Error Logging Skipped (table missing): ' . $message);
            }
            return;
        }

        try {
            $error_data = array(
                'error_type' => $exception ? get_class($exception) : 'YadoreError',
                'error_message' => $message,
                'error_code' => $exception ? $exception->getCode() : '',
                'stack_trace' => $exception ? $exception->getTraceAsString() : wp_debug_backtrace_summary(),
                'context_data' => function_exists('wp_json_encode') ? wp_json_encode($context) : json_encode($context),
                'post_id' => $context['post_id'] ?? 0,
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'severity' => $severity,
            );

            $wpdb->insert($error_logs_table, $error_data);

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
            $gemini_errors = array();

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

            $api_table = $wpdb->prefix . 'yadore_api_logs';
            if ($this->table_exists($api_table)) {
                $error_rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, endpoint_url, response_body, error_message, created_at FROM {$api_table} WHERE api_type = %s AND success = 0 ORDER BY created_at DESC LIMIT %d",
                        'gemini',
                        20
                    ),
                    ARRAY_A
                );

                foreach ((array) $error_rows as $error_row) {
                    $gemini_errors[] = array(
                        'id' => isset($error_row['id']) ? (int) $error_row['id'] : 0,
                        'endpoint' => isset($error_row['endpoint_url']) ? (string) $error_row['endpoint_url'] : '',
                        'error_message' => isset($error_row['error_message']) ? (string) $error_row['error_message'] : '',
                        'response_body' => isset($error_row['response_body']) ? (string) $error_row['response_body'] : '',
                        'created_at' => isset($error_row['created_at']) ? (string) $error_row['created_at'] : '',
                    );
                }
            }

            $wp_debug_excerpt = $this->read_wp_debug_excerpt();

            wp_send_json_success(array(
                'plugin_debug_log' => $plugin_log,
                'stack_traces' => $stack_traces,
                'gemini_errors' => $gemini_errors,
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

    private function get_cache_statistics() {
        global $wpdb;

        $prefixes = array('yadore_products_', 'yadore_ai_', 'yadore_analytics_');
        $placeholders = array();
        $values = array();

        foreach ($prefixes as $prefix) {
            $placeholders[] = 'option_name LIKE %s';
            $values[] = $wpdb->esc_like('_transient_' . $prefix) . '%';
        }

        $entries = 0;
        $bytes = 0;

        if (!empty($placeholders)) {
            $sql = 'SELECT option_name, option_value FROM ' . $wpdb->options . ' WHERE (' . implode(' OR ', $placeholders) . ')';
            $prepared = $wpdb->prepare($sql, $values);
            $rows = $wpdb->get_results($prepared);

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $entries++;
                    $value = maybe_unserialize($row->option_value);
                    if (is_string($value)) {
                        $bytes += strlen($value);
                    } else {
                        $bytes += strlen(maybe_serialize($value));
                    }
                }
            }
        }

        $metrics = $this->get_cache_metrics();
        $groups = array('products', 'ai', 'analytics');
        $hits = 0;
        $misses = 0;

        foreach ($groups as $group) {
            $hits += (int) ($metrics[$group]['hits'] ?? 0);
            $misses += (int) ($metrics[$group]['misses'] ?? 0);
        }

        $total_checks = max(0, $hits + $misses);
        $hit_rate = $total_checks > 0 ? (int) round(($hits / $total_checks) * 100) : 0;

        return array(
            'size' => $this->format_bytes($bytes),
            'entries' => $entries,
            'hit_rate' => $hit_rate,
            'metrics' => $metrics,
        );
    }

    private function clear_plugin_caches() {
        $removed = array(
            'transients' => $this->purge_transients_by_prefix(array('yadore_products_', 'yadore_ai_', 'yadore_analytics_')),
            'ai_cache_rows' => $this->clear_ai_cache_table(),
        );

        $this->api_cache = array();
        $this->keyword_candidate_cache = array();

        return $removed;
    }

    private function purge_transients_by_prefix(array $prefixes, $expired_only = false) {
        global $wpdb;

        $total_removed = 0;

        foreach ($prefixes as $prefix) {
            $like = $wpdb->esc_like('_transient_' . $prefix) . '%';
            $option_names = $wpdb->get_col($wpdb->prepare('SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE %s', $like));

            foreach ($option_names as $option_name) {
                $transient_key = substr($option_name, strlen('_transient_'));
                if ($transient_key === false) {
                    continue;
                }

                if ($expired_only && !$this->is_transient_expired($transient_key, false)) {
                    continue;
                }

                if (delete_transient($transient_key)) {
                    $total_removed++;
                }
            }

            if (is_multisite() && property_exists($wpdb, 'sitemeta')) {
                $site_like = $wpdb->esc_like('_site_transient_' . $prefix) . '%';
                $meta_keys = $wpdb->get_col($wpdb->prepare('SELECT meta_key FROM ' . $wpdb->sitemeta . ' WHERE meta_key LIKE %s', $site_like));

                foreach ($meta_keys as $meta_key) {
                    $transient_key = substr($meta_key, strlen('_site_transient_'));
                    if ($transient_key === false) {
                        continue;
                    }

                    if ($expired_only && !$this->is_transient_expired($transient_key, true)) {
                        continue;
                    }

                    if (delete_site_transient($transient_key)) {
                        $total_removed++;
                    }
                }
            }
        }

        return $total_removed;
    }

    private function is_transient_expired($transient_key, $network = false) {
        $timeout_option = ($network ? '_site_transient_timeout_' : '_transient_timeout_') . $transient_key;
        $timeout = $network ? get_site_option($timeout_option) : get_option($timeout_option);

        if (!is_numeric($timeout)) {
            return false;
        }

        return (int) $timeout <= $this->get_wp_timestamp();
    }

    private function clear_ai_cache_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'yadore_ai_cache';
        if (!$this->table_exists($table)) {
            return 0;
        }

        $deleted = $wpdb->query('DELETE FROM ' . $table);
        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    private function get_database_statistics() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'yadore_ai_cache',
            $wpdb->prefix . 'yadore_post_keywords',
            $wpdb->prefix . 'yadore_api_logs',
            $wpdb->prefix . 'yadore_error_logs',
            $wpdb->prefix . 'yadore_analytics',
            $wpdb->prefix . 'yadore_api_clicks',
        );

        $size_bytes = 0;
        $records = 0;
        $overhead_bytes = 0;

        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }

            $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $this->escape_like_value($table)));
            if (!$status) {
                continue;
            }

            $size_bytes += (int) ($status->Data_length ?? 0) + (int) ($status->Index_length ?? 0);
            $records += (int) ($status->Rows ?? 0);
            $overhead_bytes += (int) ($status->Data_free ?? 0);
        }

        return array(
            'size' => $this->format_bytes($size_bytes),
            'records' => $records,
            'overhead' => $this->format_bytes($overhead_bytes),
        );
    }

    private function run_connectivity_checks() {
        $services = array(
            $this->check_yadore_api_status(),
            $this->check_gemini_api_status(),
            $this->check_external_services_status(),
        );

        $overall = 'healthy';

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            $status = isset($service['status']) ? $service['status'] : 'healthy';
            if ($status === 'critical') {
                $overall = 'critical';
                break;
            }

            if ($status === 'warning' && $overall !== 'critical') {
                $overall = 'warning';
            }
        }

        return array(
            'status' => $overall,
            'services' => $services,
        );
    }

    private function check_yadore_api_status() {
        $label = __('Yadore API', 'yadore-monetizer');

        $service = array(
            'key' => 'yadore_api',
            'label' => $label,
            'status' => 'healthy',
            'message' => '',
        );

        $api_key = trim((string) get_option('yadore_api_key'));
        if ($api_key === '') {
            $service['status'] = 'warning';
            $service['message'] = __('API key missing. Add your Yadore publisher key in the settings.', 'yadore-monetizer');
            return $service;
        }

        $endpoint = 'https://api.yadore.com/v2/markets';
        $args = array(
            'headers' => array(
                'Accept' => 'application/json',
                'API-Key' => $api_key,
                'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            ),
            'timeout' => 12,
        );

        $response = wp_remote_get($endpoint, $args);
        if (is_wp_error($response)) {
            $service['status'] = 'critical';
            $service['message'] = sprintf(
                __('Connection failed: %s', 'yadore-monetizer'),
                $response->get_error_message()
            );

            return $service;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_message = wp_remote_retrieve_response_message($response);

        if ($status_code >= 200 && $status_code < 300) {
            $decoded = json_decode($body, true);
            $market_count = 0;

            if (is_array($decoded)) {
                if (isset($decoded['markets']) && is_array($decoded['markets'])) {
                    $market_count = count($decoded['markets']);
                } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
                    $market_count = count($decoded['data']);
                }
            }

            if ($market_count > 0) {
                $service['message'] = sprintf(
                    __('Online – %d markets available.', 'yadore-monetizer'),
                    $market_count
                );
            } else {
                $service['message'] = __('Online – response received successfully.', 'yadore-monetizer');
            }

            return $service;
        }

        if (in_array($status_code, array(401, 403), true)) {
            $service['status'] = 'warning';
        } else {
            $service['status'] = 'critical';
        }

        $error_detail = $this->extract_error_message_from_body($body);
        if ($error_detail !== '') {
            $service['message'] = sprintf(
                __('HTTP %1$d: %2$s', 'yadore-monetizer'),
                $status_code,
                $error_detail
            );
        } else {
            $service['message'] = sprintf(
                __('HTTP %1$d %2$s', 'yadore-monetizer'),
                $status_code,
                $response_message
            );
        }

        return $service;
    }

    private function check_gemini_api_status() {
        $label = __('Gemini AI API', 'yadore-monetizer');

        $service = array(
            'key' => 'gemini_api',
            'label' => $label,
            'status' => 'healthy',
            'message' => '',
        );

        $api_key = trim((string) get_option('yadore_gemini_api_key'));
        if ($api_key === '') {
            $service['status'] = 'warning';
            $service['message'] = __('API key missing. Configure your Gemini API key to enable AI features.', 'yadore-monetizer');
            return $service;
        }

        $model = $this->sanitize_model(get_option('yadore_gemini_model', $this->get_default_gemini_model()));
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s',
            rawurlencode($model)
        );
        $request_url = add_query_arg('key', $api_key, $endpoint);

        $response = wp_remote_get($request_url, array(
            'timeout' => 12,
            'headers' => array(
                'User-Agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            ),
        ));

        if (is_wp_error($response)) {
            $service['status'] = 'critical';
            $service['message'] = sprintf(
                __('Connection failed: %s', 'yadore-monetizer'),
                $response->get_error_message()
            );

            return $service;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 200 && $status_code < 300) {
            $decoded = json_decode($body, true);
            $display_name = '';

            if (is_array($decoded) && isset($decoded['displayName'])) {
                $display_name = sanitize_text_field((string) $decoded['displayName']);
            }

            if ($display_name !== '') {
                $service['message'] = sprintf(
                    __('Online – %1$s model reachable.', 'yadore-monetizer'),
                    $display_name
                );
            } else {
                $service['message'] = sprintf(
                    __('Online – %1$s model verified.', 'yadore-monetizer'),
                    $model
                );
            }

            return $service;
        }

        if (in_array($status_code, array(401, 403), true)) {
            $service['status'] = 'warning';
        } else {
            $service['status'] = 'critical';
        }

        $error_detail = $this->extract_error_message_from_body($body);
        if ($error_detail !== '') {
            $service['message'] = sprintf(
                __('HTTP %1$d: %2$s', 'yadore-monetizer'),
                $status_code,
                $error_detail
            );
        } else {
            $service['message'] = sprintf(
                __('HTTP %d response received.', 'yadore-monetizer'),
                $status_code
            );
        }

        return $service;
    }

    private function check_external_services_status() {
        $label = __('External Services', 'yadore-monetizer');

        $service = array(
            'key' => 'external_services',
            'label' => $label,
            'status' => 'healthy',
            'message' => '',
        );

        $targets = array(
            __('WordPress.org API', 'yadore-monetizer') => 'https://api.wordpress.org/core/version-check/1.7/',
            __('GitHub API', 'yadore-monetizer') => 'https://api.github.com/',
        );

        $reachable = 0;
        $issues = array();

        foreach ($targets as $target_label => $url) {
            $response = wp_remote_head($url, array(
                'timeout' => 10,
                'user-agent' => 'YadoreMonetizer/' . YADORE_PLUGIN_VERSION,
            ));

            if (is_wp_error($response)) {
                $issues[] = sprintf('%s: %s', $target_label, $response->get_error_message());
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code >= 200 && $code < 400) {
                $reachable++;
            } else {
                $issues[] = sprintf('%s: HTTP %d', $target_label, $code);
            }
        }

        $total_targets = count($targets);

        if (empty($issues)) {
            $service['message'] = sprintf(
                __('All monitored services reachable (%1$d/%2$d).', 'yadore-monetizer'),
                $reachable,
                $total_targets
            );

            return $service;
        }

        if ($reachable > 0) {
            $service['status'] = 'warning';
            $service['message'] = sprintf(
                __('Partial connectivity – %1$d of %2$d services reachable. %3$s', 'yadore-monetizer'),
                $reachable,
                $total_targets,
                implode(' | ', $issues)
            );
        } else {
            $service['status'] = 'critical';
            $service['message'] = sprintf(
                __('No external services reachable: %s', 'yadore-monetizer'),
                implode(' | ', $issues)
            );
        }

        return $service;
    }

    private function run_database_diagnostics() {
        global $wpdb;

        $tables = array(
            'yadore_ai_cache' => array('id', 'content_hash', 'post_id', 'ai_keywords', 'extracted_keywords', 'ai_confidence', 'api_cost', 'token_count', 'model_used', 'processing_time_ms', 'created_at', 'expires_at'),
            'yadore_post_keywords' => array('id', 'post_id', 'post_title', 'primary_keyword', 'fallback_keyword', 'keyword_confidence', 'product_validated', 'product_count', 'word_count', 'content_hash', 'last_scanned', 'scan_status', 'scan_error', 'scan_attempts', 'scan_duration_ms'),
            'yadore_api_logs' => array('id', 'api_type', 'endpoint_url', 'request_method', 'request_headers', 'request_body', 'response_code', 'response_headers', 'response_body', 'response_time_ms', 'success', 'error_message', 'post_id', 'user_id', 'ip_address', 'user_agent', 'created_at'),
            'yadore_error_logs' => array('id', 'error_type', 'error_message', 'error_code', 'stack_trace', 'context_data', 'post_id', 'user_id', 'ip_address', 'user_agent', 'request_uri', 'severity', 'resolved', 'resolution_notes', 'created_at', 'resolved_at'),
            'yadore_analytics' => array('id', 'event_type', 'event_data', 'post_id', 'user_id', 'session_id', 'ip_address', 'user_agent', 'created_at'),
            'yadore_api_clicks' => array('id', 'click_id', 'clicked_at', 'market', 'merchant_id', 'merchant_name', 'placement_id', 'sales_amount', 'raw_payload', 'synced_at'),
        );

        $details = array();
        $missing_tables = array();
        $column_issues = array();
        $totals = array(
            'records' => 0,
            'size_bytes' => 0,
            'overhead_bytes' => 0,
        );

        foreach ($tables as $suffix => $expected_columns) {
            $table_name = $wpdb->prefix . $suffix;
            $table_safe = str_replace('`', '``', $table_name);

            $table_detail = array(
                'name' => $table_name,
                'exists' => false,
                'records' => 0,
                'size_bytes' => 0,
                'overhead_bytes' => 0,
                'missing_columns' => array(),
            );

            if (!$this->table_exists($table_name)) {
                $missing_tables[] = $table_name;
                $details[] = $table_detail;
                continue;
            }

            $table_detail['exists'] = true;

            $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table_safe}`");
            if (is_array($columns)) {
                $missing = array_values(array_diff($expected_columns, $columns));
                if (!empty($missing)) {
                    $table_detail['missing_columns'] = $missing;
                    $column_issues[$table_name] = $missing;
                }
            }

            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table_safe}`");
            $table_detail['records'] = $count;
            $totals['records'] += $count;

            $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $this->escape_like_value($table_name)), ARRAY_A);
            if (is_array($status)) {
                $data_length = (int) ($status['Data_length'] ?? 0);
                $index_length = (int) ($status['Index_length'] ?? 0);
                $data_free = (int) ($status['Data_free'] ?? 0);

                $table_detail['size_bytes'] = $data_length + $index_length;
                $table_detail['overhead_bytes'] = $data_free;

                $totals['size_bytes'] += $table_detail['size_bytes'];
                $totals['overhead_bytes'] += $table_detail['overhead_bytes'];
            }

            $details[] = $table_detail;
        }

        $messages = array();
        $status_flag = 'healthy';

        if (!empty($missing_tables)) {
            $status_flag = 'critical';
            $messages[] = sprintf(
                __('Missing tables: %s.', 'yadore-monetizer'),
                implode(', ', $missing_tables)
            );
        }

        foreach ($column_issues as $table_name => $missing_columns) {
            if ($status_flag !== 'critical') {
                $status_flag = 'warning';
            }

            $messages[] = sprintf(
                __('Table %1$s missing columns: %2$s.', 'yadore-monetizer'),
                $table_name,
                implode(', ', $missing_columns)
            );
        }

        $totals['size'] = $this->format_bytes($totals['size_bytes']);
        $totals['overhead'] = $this->format_bytes($totals['overhead_bytes']);
        $totals['overhead_ratio'] = $totals['size_bytes'] > 0
            ? (int) round(($totals['overhead_bytes'] / max(1, $totals['size_bytes'])) * 100)
            : 0;

        if (empty($messages)) {
            $messages[] = sprintf(
                __('All %1$d plugin tables are healthy (%2$d records, %3$s storage).', 'yadore-monetizer'),
                count($tables),
                $totals['records'],
                $totals['size']
            );
        } else {
            $messages[] = sprintf(
                __('Current footprint: %1$d records using %2$s with %3$s overhead.', 'yadore-monetizer'),
                $totals['records'],
                $totals['size'],
                $totals['overhead']
            );
        }

        return array(
            'status' => $status_flag,
            'message' => implode(' ', $messages),
            'details' => array(
                'tables' => $details,
                'missing_tables' => $missing_tables,
                'totals' => $totals,
            ),
        );
    }

    private function run_performance_diagnostics() {
        $cache_stats = $this->get_cache_statistics();
        $database_results = $this->run_database_diagnostics();
        $cron_health = $this->get_cron_health_snapshot();

        $status_flag = 'healthy';
        $messages = array();

        $hit_rate = isset($cache_stats['hit_rate']) ? (int) $cache_stats['hit_rate'] : 0;
        $entries = isset($cache_stats['entries']) ? (int) $cache_stats['entries'] : 0;

        if ($entries === 0) {
            $status_flag = 'warning';
            $messages[] = __('Cache is empty – warm-up scans recommended for optimal performance.', 'yadore-monetizer');
        } elseif ($hit_rate < 20) {
            $status_flag = 'warning';
            $messages[] = sprintf(
                __('Cache hit rate is %1$d%% across %2$d entries. Consider clearing stale caches or running additional scans.', 'yadore-monetizer'),
                $hit_rate,
                $entries
            );
        } else {
            $messages[] = sprintf(
                __('Cache hit rate at %1$d%% across %2$d entries.', 'yadore-monetizer'),
                $hit_rate,
                $entries
            );
        }

        $totals = isset($database_results['details']['totals']) ? $database_results['details']['totals'] : array();
        $db_status = isset($database_results['status']) ? $database_results['status'] : 'healthy';
        if ($db_status === 'critical') {
            $status_flag = 'critical';
        } elseif ($db_status === 'warning' && $status_flag !== 'critical') {
            $status_flag = 'warning';
        }

        $records = isset($totals['records']) ? (int) $totals['records'] : 0;
        $size = isset($totals['size']) ? $totals['size'] : '0 KB';
        $overhead = isset($totals['overhead']) ? $totals['overhead'] : '0 KB';
        $overhead_ratio = isset($totals['overhead_ratio']) ? (int) $totals['overhead_ratio'] : 0;

        if ($db_status !== 'healthy' && !empty($database_results['message'])) {
            $messages[] = $database_results['message'];
        }

        if ($overhead_ratio > 25) {
            if ($status_flag !== 'critical') {
                $status_flag = 'warning';
            }
            $messages[] = sprintf(
                __('Database overhead is %1$s (%2$d%%). Run an optimize operation to reclaim space.', 'yadore-monetizer'),
                $overhead,
                $overhead_ratio
            );
        } elseif ($db_status === 'healthy') {
            $messages[] = sprintf(
                __('Database footprint %1$s across %2$d records with %3$s overhead.', 'yadore-monetizer'),
                $size,
                $records,
                $overhead
            );
        }

        if (!$cron_health['healthy']) {
            if (!empty($cron_health['missing'])) {
                $status_flag = 'critical';
            } elseif ($status_flag !== 'critical') {
                $status_flag = 'warning';
            }

            $messages[] = $cron_health['message'];
        } else {
            $messages[] = $cron_health['message'];
        }

        return array(
            'status' => $status_flag,
            'message' => implode(' ', $messages),
            'details' => array(
                'cache' => $cache_stats,
                'database' => $database_results['details'],
                'cron' => $cron_health,
            ),
        );
    }

    private function get_cron_health_snapshot() {
        $hooks = array(
            'yadore_daily_maintenance' => __('Daily maintenance', 'yadore-monetizer'),
            'yadore_weekly_reports' => __('Weekly reports', 'yadore-monetizer'),
        );

        $now = function_exists('current_time') ? current_time('timestamp') : time();
        $events = array();
        $missing = array();
        $overdue = array();
        $messages = array();

        foreach ($hooks as $hook => $label) {
            $timestamp = wp_next_scheduled($hook);

            if ($timestamp === false) {
                $missing[] = $label;
                $events[] = array(
                    'hook' => $hook,
                    'label' => $label,
                    'scheduled' => false,
                );
                continue;
            }

            $events[] = array(
                'hook' => $hook,
                'label' => $label,
                'scheduled' => $timestamp,
            );

            if ($timestamp < $now) {
                $overdue[] = $label;
                $diff = human_time_diff($timestamp, $now);
                $messages[] = sprintf(
                    __('%1$s is overdue by %2$s.', 'yadore-monetizer'),
                    $label,
                    $diff
                );
            } else {
                $diff = human_time_diff($now, $timestamp);
                $messages[] = sprintf(
                    __('Next %1$s run in %2$s.', 'yadore-monetizer'),
                    $label,
                    $diff
                );
            }
        }

        $healthy = empty($missing) && empty($overdue);

        if (!empty($missing)) {
            $messages[] = sprintf(
                __('Missing schedules: %s.', 'yadore-monetizer'),
                implode(', ', $missing)
            );
        }

        if (!empty($overdue)) {
            $messages[] = sprintf(
                __('Overdue schedules: %s.', 'yadore-monetizer'),
                implode(', ', $overdue)
            );
        }

        if ($healthy && empty($messages)) {
            $messages[] = __('All scheduled maintenance tasks are queued.', 'yadore-monetizer');
        }

        return array(
            'healthy' => $healthy,
            'message' => implode(' ', $messages),
            'events' => $events,
            'missing' => $missing,
            'overdue' => $overdue,
        );
    }

    private function extract_error_message_from_body($body) {
        if (!is_string($body) || trim($body) === '') {
            return '';
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            if (isset($decoded['message']) && is_string($decoded['message'])) {
                return trim($decoded['message']);
            }

            if (isset($decoded['error'])) {
                if (is_string($decoded['error'])) {
                    return trim($decoded['error']);
                }

                if (is_array($decoded['error'])) {
                    if (isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
                        return trim($decoded['error']['message']);
                    }

                    if (isset($decoded['error']['status']) && is_string($decoded['error']['status'])) {
                        return trim($decoded['error']['status']);
                    }
                }
            }
        }

        $stripper = function_exists('wp_strip_all_tags') ? 'wp_strip_all_tags' : 'strip_tags';
        $stripped = trim($stripper($body));
        if ($stripped !== '' && function_exists('mb_strlen') ? mb_strlen($stripped, 'UTF-8') <= 160 : strlen($stripped) <= 160) {
            return $stripped;
        }

        return '';
    }

    private function get_log_statistics() {
        global $wpdb;

        $api_table = $wpdb->prefix . 'yadore_api_logs';
        $error_table = $wpdb->prefix . 'yadore_error_logs';

        $api_logs = $this->table_exists($api_table) ? (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $api_table) : 0;
        $error_logs = $this->table_exists($error_table) ? (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . $error_table) : 0;

        $total_size = 0;
        foreach (array($api_table, $error_table) as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }

            $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s', $this->escape_like_value($table)));
            if ($status) {
                $total_size += (int) ($status->Data_length ?? 0) + (int) ($status->Index_length ?? 0);
            }
        }

        return array(
            'api_logs' => $api_logs,
            'error_logs' => $error_logs,
            'total_size' => $this->format_bytes($total_size),
        );
    }

    private function get_cleanup_statistics() {
        $stats = array(
            'temp_files' => 0,
            'orphaned_data' => 0,
            'space_used' => '0 KB',
        );

        if (!function_exists('wp_upload_dir')) {
            return $stats;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return $stats;
        }

        $base_dir = trailingslashit($uploads['basedir']) . 'yadore-monetizer';
        if (!is_dir($base_dir)) {
            return $stats;
        }

        $total_files = 0;
        $total_bytes = 0;
        $orphaned = 0;

        try {
            $directory = new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) {
                    continue;
                }

                $total_files++;
                $total_bytes += (int) $file->getSize();

                $extension = strtolower($file->getExtension());
                if (in_array($extension, array('tmp', 'log', 'bak'), true)) {
                    $orphaned++;
                }
            }
        } catch (Exception $e) {
            $this->log_error('Failed to calculate cleanup statistics', $e, 'debug');
        }

        $stats['temp_files'] = $total_files;
        $stats['orphaned_data'] = $orphaned;
        $stats['space_used'] = $this->format_bytes($total_bytes);

        return $stats;
    }

    private function get_cache_metrics() {
        $raw = get_option('yadore_cache_metrics', array());
        if (!is_array($raw)) {
            $raw = array();
        }

        $defaults = $this->get_default_cache_metrics();
        $metrics = wp_parse_args($raw, $defaults);

        foreach (array('products', 'ai', 'analytics') as $group) {
            if (!isset($metrics[$group]) || !is_array($metrics[$group])) {
                $metrics[$group] = $defaults[$group];
            }

            $metrics[$group]['hits'] = isset($metrics[$group]['hits']) ? (int) $metrics[$group]['hits'] : 0;
            $metrics[$group]['misses'] = isset($metrics[$group]['misses']) ? (int) $metrics[$group]['misses'] : 0;
        }

        $metrics['last_cleared'] = isset($metrics['last_cleared']) ? (int) $metrics['last_cleared'] : 0;
        $metrics['last_updated'] = isset($metrics['last_updated']) ? (int) $metrics['last_updated'] : 0;

        return $metrics;
    }

    private function reset_cache_metrics() {
        $metrics = $this->get_default_cache_metrics();
        update_option('yadore_cache_metrics', $metrics, false);
        return $metrics;
    }

    private function record_cache_hit($group) {
        $this->update_cache_counter($group, 'hits');
    }

    private function record_cache_miss($group) {
        $this->update_cache_counter($group, 'misses');
    }

    private function update_cache_counter($group, $field) {
        if (!in_array($group, array('products', 'ai', 'analytics'), true)) {
            return;
        }

        if (!in_array($field, array('hits', 'misses'), true)) {
            return;
        }

        $metrics = $this->get_cache_metrics();
        if (!isset($metrics[$group][$field])) {
            $metrics[$group][$field] = 0;
        }

        $metrics[$group][$field] = max(0, (int) $metrics[$group][$field]) + 1;
        $metrics['last_updated'] = function_exists('current_time') ? current_time('timestamp') : time();

        update_option('yadore_cache_metrics', $metrics, false);
    }

    private function format_bytes($bytes) {
        $bytes = (int) $bytes;

        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);

        if ($power === 0) {
            return $bytes . ' B';
        }

        return sprintf('%.2f %s', $value, $units[$power]);
    }

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
            'gemini-2.5-flash' => array(
                'label' => __('Gemini 2.5 Flash - Fastest next-gen', 'yadore-monetizer'),
            ),
            'gemini-2.5-pro' => array(
                'label' => __('Gemini 2.5 Pro - Highest quality', 'yadore-monetizer'),
            ),
            'gemini-2.5-flash-lite' => array(
                'label' => __('Gemini 2.5 Flash Lite - Efficient', 'yadore-monetizer'),
            ),
            'gemini-2.5-flash-preview-09-2025' => array(
                'label' => __('Gemini 2.5 Flash Preview (Sep 2025)', 'yadore-monetizer'),
            ),
            'gemini-2.5-flash-lite-preview-09-2025' => array(
                'label' => __('Gemini 2.5 Flash Lite Preview (Sep 2025)', 'yadore-monetizer'),
            ),
            'gemini-2.5-flash-native-audio-preview-09-2025' => array(
                'label' => __('Gemini 2.5 Flash Native Audio Preview (Sep 2025)', 'yadore-monetizer'),
            ),
            'gemini-2.5-flash-exp-native-audio-thinking-dialog' => array(
                'label' => __('Gemini 2.5 Flash Experimental Native Audio Thinking Dialog', 'yadore-monetizer'),
            ),
        );
    }

    private function get_default_gemini_model() {
        $models = $this->get_supported_gemini_models();
        $first = array_key_first($models);
        return $first ?: 'gemini-2.5-flash';
    }

    private function sanitize_export_request($request) {
        $allowed_types = array('settings', 'keywords', 'analytics', 'logs', 'cache');
        $selected = array();

        if (isset($request['data_types'])) {
            $selected = (array) $request['data_types'];
        } elseif (isset($request['export_data'])) {
            $selected = (array) $request['export_data'];
        }

        $data_types = array();
        foreach ($selected as $type) {
            $key = sanitize_key($type);
            if (in_array($key, $allowed_types, true) && !in_array($key, $data_types, true)) {
                $data_types[] = $key;
            }
        }

        if (empty($data_types)) {
            throw new Exception(__('Please select at least one dataset to export.', 'yadore-monetizer'));
        }

        $format = isset($request['format']) ? sanitize_key($request['format']) : 'json';
        $allowed_formats = array('json', 'csv', 'xml');
        if (!in_array($format, $allowed_formats, true)) {
            $format = 'json';
        }

        $date_range = isset($request['date_range']) ? sanitize_text_field($request['date_range']) : 'all';
        $custom_start = isset($request['start_date']) ? sanitize_text_field($request['start_date']) : '';
        $custom_end = isset($request['end_date']) ? sanitize_text_field($request['end_date']) : '';

        return array(
            'data_types' => $data_types,
            'format' => $format,
            'date_range' => $date_range,
            'custom_start' => $custom_start,
            'custom_end' => $custom_end,
        );
    }

    private function resolve_export_date_range($date_range, $custom_start = '', $custom_end = '') {
        $date_range = is_string($date_range) ? strtolower($date_range) : 'all';
        $timezone = $this->get_wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);
        $start = null;
        $end = null;

        switch ($date_range) {
            case '30':
            case '90':
            case '365':
                $days = (int) $date_range;
                $start = $now->modify(sprintf('-%d days', $days))->setTime(0, 0, 0);
                $end = $now;
                break;
            case 'custom':
                $start = $this->create_datetime_from_input($custom_start, $timezone, true);
                $end = $this->create_datetime_from_input($custom_end, $timezone, false);
                if ($start && $end && $start > $end) {
                    $temp = $start;
                    $start = $end;
                    $end = $temp;
                }
                break;
            case 'all':
            default:
                $start = null;
                $end = null;
                break;
        }

        return array(
            'start' => $start ? $start->format('Y-m-d H:i:s') : null,
            'end' => $end ? $end->format('Y-m-d H:i:s') : null,
            'requested_start' => $custom_start,
            'requested_end' => $custom_end,
        );
    }

    private function create_datetime_from_input($value, DateTimeZone $timezone, $is_start = true) {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value, $timezone);
            return $is_start ? $date->setTime(0, 0, 0) : $date->setTime(23, 59, 59);
        } catch (Exception $e) {
            return null;
        }
    }

    private function prepare_export_payload(array $config) {
        $data_types = isset($config['data_types']) && is_array($config['data_types']) ? $config['data_types'] : array();
        $date_range = $config['date_range'] ?? 'all';
        $custom_start = $config['custom_start'] ?? '';
        $custom_end = $config['custom_end'] ?? '';
        $format = $config['format'] ?? 'json';

        $range = $this->resolve_export_date_range($date_range, $custom_start, $custom_end);

        $data = array();
        $record_count = 0;

        if (in_array('settings', $data_types, true)) {
            $settings = $this->get_settings_export_records();
            $data['settings'] = $settings;
            $record_count += count($settings);
        }

        if (in_array('keywords', $data_types, true)) {
            $keywords = $this->get_keywords_export_records($range);
            $data['keywords'] = $keywords;
            $record_count += count($keywords);
        }

        if (in_array('analytics', $data_types, true)) {
            $analytics = $this->get_analytics_export_records($range);
            $data['analytics'] = $analytics;
            $record_count += count($analytics);
        }

        if (in_array('logs', $data_types, true)) {
            $logs = $this->get_logs_export_records($range);
            $data['logs'] = $logs;
            $record_count += count($logs);
        }

        if (in_array('cache', $data_types, true)) {
            $cache = $this->get_cache_export_records($range);
            $data['cache'] = $cache;
            $record_count += count($cache);
        }

        $meta = array(
            'site_url' => home_url('/'),
            'site_name' => get_bloginfo('name'),
            'plugin_version' => YADORE_PLUGIN_VERSION,
            'generated_at' => current_time('mysql'),
            'format' => $format,
            'data_types' => $data_types,
            'date_range' => $date_range,
            'range_start' => $range['start'],
            'range_end' => $range['end'],
            'records' => $record_count,
        );

        return array(
            'meta' => $meta,
            'data' => $data,
        );
    }

    private function get_settings_export_records() {
        $defaults = $this->get_default_options();
        $options = array_keys($defaults);
        $records = array();

        foreach ($options as $option) {
            $records[] = array(
                'key' => $option,
                'value' => get_option($option, null),
            );
        }

        $additional = array(
            'yadore_install_timestamp',
            'yadore_plugin_version',
            'yadore_cache_metrics',
        );

        foreach ($additional as $option) {
            $records[] = array(
                'key' => $option,
                'value' => get_option($option, null),
            );
        }

        return $records;
    }

    private function get_keywords_export_records(array $range) {
        return $this->fetch_table_rows('yadore_post_keywords', 'last_scanned', $range);
    }

    private function get_analytics_export_records(array $range) {
        return $this->fetch_table_rows('yadore_analytics', 'created_at', $range);
    }

    private function get_logs_export_records(array $range) {
        $records = array();

        $api_logs = $this->fetch_table_rows('yadore_api_logs', 'created_at', $range);
        foreach ($api_logs as $log) {
            $records[] = array_merge(array('log_type' => 'api'), $log);
        }

        $error_logs = $this->fetch_table_rows('yadore_error_logs', 'created_at', $range);
        foreach ($error_logs as $log) {
            $records[] = array_merge(array('log_type' => 'error'), $log);
        }

        return $records;
    }

    private function get_cache_export_records(array $range) {
        $records = array();
        $metrics = get_option('yadore_cache_metrics', array());

        if (!empty($metrics)) {
            $records[] = array(
                'entry_type' => 'metrics',
                'data' => $metrics,
            );
        }

        $cache_entries = $this->fetch_table_rows('yadore_ai_cache', 'created_at', $range);
        foreach ($cache_entries as $entry) {
            $records[] = array_merge(array('entry_type' => 'ai_cache'), $entry);
        }

        return $records;
    }

    private function fetch_table_rows($table_suffix, $date_column, array $range) {
        global $wpdb;

        if (!isset($wpdb) || !($wpdb instanceof wpdb)) {
            return array();
        }

        $table = $wpdb->prefix . $table_suffix;
        if (!$this->table_exists($table)) {
            return array();
        }

        $table_safe = str_replace('`', '``', $table);
        $column = is_string($date_column) ? preg_replace('/[^A-Za-z0-9_]/', '', $date_column) : '';

        $conditions = array();
        $params = array();

        if ($column !== '' && !empty($range['start'])) {
            $conditions[] = sprintf('`%s` >= %%s', $column);
            $params[] = $range['start'];
        }

        if ($column !== '' && !empty($range['end'])) {
            $conditions[] = sprintf('`%s` <= %%s', $column);
            $params[] = $range['end'];
        }

        $sql = sprintf('SELECT * FROM `%s`', $table_safe);
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($column !== '') {
            $sql .= sprintf(' ORDER BY `%s` DESC', $column);
        }

        $query = !empty($params) ? $wpdb->prepare($sql, $params) : $sql;
        $results = $wpdb->get_results($query, ARRAY_A);

        if (!is_array($results)) {
            return array();
        }

        foreach ($results as &$row) {
            foreach ($row as $key => $value) {
                if (is_string($value) && function_exists('is_serialized') && is_serialized($value)) {
                    $row[$key] = maybe_unserialize($value);
                }
            }
        }

        return $results;
    }

    private function convert_export_payload(array $payload, $format) {
        $format = is_string($format) ? strtolower($format) : 'json';

        if ($format === 'csv') {
            return $this->convert_export_to_csv($payload);
        }

        if ($format === 'xml') {
            return $this->convert_export_to_xml($payload);
        }

        return $this->convert_export_to_json($payload);
    }

    private function convert_export_to_json(array $payload) {
        return wp_json_encode($payload, JSON_PRETTY_PRINT);
    }

    private function convert_export_to_csv(array $payload) {
        $rows = array(array('section', 'field', 'value'));

        if (isset($payload['meta']) && is_array($payload['meta'])) {
            foreach ($payload['meta'] as $key => $value) {
                $rows[] = array('meta', $key, $this->stringify_export_value($value));
            }
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $section => $records) {
                if ($section === 'settings') {
                    foreach ($records as $record) {
                        $rows[] = array(
                            'settings',
                            $record['key'] ?? '',
                            $this->stringify_export_value($record['value'] ?? ''),
                        );
                    }
                } else {
                    foreach ($records as $record) {
                        $rows[] = array($section, '', $this->stringify_export_value($record));
                    }
                }
            }
        }

        $handle = fopen('php://temp', 'w+');
        if (!$handle) {
            return '';
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    private function convert_export_to_xml(array $payload) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><yadoreExport/>' );

        $metaNode = $xml->addChild('meta');
        if (isset($payload['meta']) && is_array($payload['meta'])) {
            foreach ($payload['meta'] as $key => $value) {
                $metaNode->addChild($this->sanitize_xml_tag($key), htmlspecialchars($this->stringify_export_value($value), ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
        }

        $dataNode = $xml->addChild('data');
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $section => $records) {
                $sectionNode = $dataNode->addChild($this->sanitize_xml_tag($section));
                if ($section === 'settings') {
                    foreach ($records as $record) {
                        $setting = $sectionNode->addChild('setting');
                        $setting->addChild('key', htmlspecialchars($this->stringify_export_value($record['key'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                        $setting->addChild('value', htmlspecialchars($this->stringify_export_value($record['value'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                    }
                } else {
                    foreach ($records as $record) {
                        $recordNode = $sectionNode->addChild('record');
                        if (is_array($record)) {
                            foreach ($record as $field => $value) {
                                $recordNode->addChild($this->sanitize_xml_tag($field), htmlspecialchars($this->stringify_export_value($value), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                            }
                        } else {
                            $recordNode->addChild('value', htmlspecialchars($this->stringify_export_value($record), ENT_QUOTES | ENT_XML1, 'UTF-8'));
                        }
                    }
                }
            }
        }

        return $xml->asXML();
    }

    private function stringify_export_value($value) {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return wp_json_encode($value);
    }

    private function sanitize_xml_tag($name) {
        $tag = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $name);
        if ($tag === '' || preg_match('/^[^A-Za-z_]/', $tag)) {
            $tag = 'field_' . $tag;
        }

        return $tag;
    }

    private function generate_export_filename($format) {
        $format = in_array($format, array('json', 'csv', 'xml'), true) ? $format : 'json';
        $timestamp = date_i18n('Ymd-His');

        return sprintf('yadore-export-%s.%s', $timestamp, $format);
    }

    private function get_export_mime_type($format) {
        switch ($format) {
            case 'csv':
                return 'text/csv';
            case 'xml':
                return 'application/xml';
            case 'json':
            default:
                return 'application/json';
        }
    }

    private function ensure_scheduled_exports() {
        $schedules = get_option('yadore_export_schedules', array());
        if (!is_array($schedules)) {
            return;
        }

        foreach ($schedules as $schedule_id => $schedule) {
            $interval = isset($schedule['interval']) ? sanitize_key($schedule['interval']) : 'daily';
            $allowed_intervals = array('hourly', 'twicedaily', 'daily', 'weekly');
            if (!in_array($interval, $allowed_intervals, true)) {
                $interval = 'daily';
            }

            $time = isset($schedule['time']) ? sanitize_text_field($schedule['time']) : '02:00';
            $args = array($schedule_id);

            if (!wp_next_scheduled('yadore_run_scheduled_export', $args)) {
                $next = isset($schedule['next_run']) ? (int) $schedule['next_run'] : 0;
                if ($next <= $this->get_wp_timestamp()) {
                    $next = $this->calculate_schedule_timestamp($interval, $time);
                }

                wp_schedule_event($next, $interval, 'yadore_run_scheduled_export', $args);
            }
        }
    }

    private function get_schedule_overview() {
        $schedules = get_option('yadore_export_schedules', array());
        if (!is_array($schedules) || empty($schedules)) {
            return array(
                'count' => 0,
                'next_run' => null,
                'next_run_human' => '',
            );
        }

        $next_timestamp = null;
        foreach ($schedules as $schedule_id => $schedule) {
            $next = isset($schedule['next_run']) ? (int) $schedule['next_run'] : 0;
            if ($next <= 0) {
                $next = wp_next_scheduled('yadore_run_scheduled_export', array($schedule_id));
            }

            if ($next && ($next_timestamp === null || $next < $next_timestamp)) {
                $next_timestamp = $next;
            }
        }

        return array(
            'count' => count($schedules),
            'next_run' => $next_timestamp,
            'next_run_human' => $next_timestamp ? $this->format_timestamp_for_display($next_timestamp) : '',
        );
    }

    private function calculate_schedule_timestamp($interval, $time) {
        $allowed_intervals = array('hourly', 'twicedaily', 'daily', 'weekly');
        if (!in_array($interval, $allowed_intervals, true)) {
            $interval = 'daily';
        }

        $timezone = $this->get_wp_timezone();
        $now = new DateTimeImmutable('now', $timezone);

        if ($interval === 'hourly') {
            return $now->modify('+1 hour')->getTimestamp();
        }

        $hours = 2;
        $minutes = 0;
        if (is_string($time) && preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)) {
            $hours = max(0, min(23, (int) $matches[1]));
            $minutes = max(0, min(59, (int) $matches[2]));
        }

        if ($interval === 'twicedaily') {
            $first = $now->setTime($hours, $minutes, 0);
            $second = $first->modify('+12 hours');

            if ($first > $now) {
                return $first->getTimestamp();
            }

            if ($second > $now) {
                return $second->getTimestamp();
            }

            return $first->modify('+1 day')->getTimestamp();
        }

        if ($interval === 'weekly') {
            $target = $now->setTime($hours, $minutes, 0);
            if ($target <= $now) {
                $target = $target->modify('+1 week');
            }

            return $target->getTimestamp();
        }

        $target = $now->setTime($hours, $minutes, 0);
        if ($target <= $now) {
            $target = $target->modify('+1 day');
        }

        return $target->getTimestamp();
    }

    private function normalize_uploaded_files($key) {
        if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
            return array();
        }

        $files = $_FILES[$key];
        $normalized = array();

        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if (empty($files['name'][$i])) {
                    continue;
                }

                $normalized[] = array(
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_OK,
                    'size' => $files['size'][$i] ?? 0,
                );
            }
        } else {
            if (!empty($files['name'])) {
                $normalized[] = $files;
            }
        }

        return array_filter($normalized, function ($file) {
            return isset($file['error']) && (int) $file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name']);
        });
    }

    private function process_import_files(array $files, array $flags, $mode) {
        $mode = ($mode === 'validate') ? 'validate' : 'import';

        $aggregate = array(
            'meta' => array(),
            'data' => array(
                'settings' => array(),
                'keywords' => array(),
                'analytics' => array(),
                'logs' => array(),
                'cache' => array(),
            ),
        );

        $messages = array();
        $stats = array(
            'files_processed' => 0,
            'records_detected' => 0,
        );

        foreach ($files as $file) {
            $parsed = $this->parse_import_file($file['tmp_name'], $file['name']);
            $stats['files_processed']++;

            if (!empty($parsed['meta']) && is_array($parsed['meta'])) {
                $aggregate['meta'] = array_merge($aggregate['meta'], $parsed['meta']);
            }

            if (!empty($parsed['data']) && is_array($parsed['data'])) {
                foreach ($parsed['data'] as $section => $records) {
                    if (!isset($aggregate['data'][$section]) || !is_array($aggregate['data'][$section])) {
                        $aggregate['data'][$section] = array();
                    }

                    if (is_array($records)) {
                        $aggregate['data'][$section] = array_merge($aggregate['data'][$section], $records);
                        $stats['records_detected'] += count($records);
                    }
                }
            }
        }

        $validation = $this->validate_import_payload($aggregate);
        $stats = array_merge($stats, $validation['stats']);
        $messages = array_merge($messages, $validation['messages']);

        if ($mode === 'validate') {
            return array(
                'mode' => 'validate',
                'stats' => $stats,
                'messages' => $messages,
            );
        }

        if (!empty($flags['backup'])) {
            try {
                $backup = $this->generate_config_export_package();
                $saved = $this->save_export_package($backup, 'backups');
                if (!empty($saved['url'])) {
                    $messages[] = sprintf(__('Backup stored at %s', 'yadore-monetizer'), esc_url($saved['url']));
                }
            } catch (Exception $backup_error) {
                $this->log_error('Failed to create import backup', $backup_error, 'warning');
                $messages[] = __('Backup could not be created before import.', 'yadore-monetizer');
            }
        }

        $import_summary = $this->import_payload($aggregate, $flags);
        $stats = array_merge($stats, $import_summary['stats']);
        $messages = array_merge($messages, $import_summary['messages']);

        return array(
            'mode' => 'import',
            'stats' => $stats,
            'messages' => $messages,
        );
    }

    private function parse_import_file($path, $filename) {
        if (!is_readable($path)) {
            throw new Exception(sprintf(__('Unable to read import file %s.', 'yadore-monetizer'), $filename));
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new Exception(sprintf(__('Unable to read import file %s.', 'yadore-monetizer'), $filename));
        }

        switch ($extension) {
            case 'json':
                $decoded = json_decode($contents, true);
                if (!is_array($decoded)) {
                    throw new Exception(sprintf(__('Import file %s does not contain valid JSON.', 'yadore-monetizer'), $filename));
                }
                return $this->normalize_import_payload($decoded);
            case 'xml':
                $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml === false) {
                    throw new Exception(sprintf(__('Import file %s does not contain valid XML.', 'yadore-monetizer'), $filename));
                }
                $decoded = json_decode(json_encode($xml), true);
                return $this->normalize_import_payload(is_array($decoded) ? $decoded : array());
            case 'csv':
                $parsed = $this->parse_import_csv($path);
                return $this->normalize_import_payload($parsed);
        }

        throw new Exception(sprintf(__('Unsupported import format: %s', 'yadore-monetizer'), $extension));
    }

    private function parse_import_csv($path) {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new Exception(__('Failed to open CSV import file.', 'yadore-monetizer'));
        }

        $headers = fgetcsv($handle);
        if (!$headers || count($headers) < 3) {
            fclose($handle);
            throw new Exception(__('CSV import file is missing expected headers.', 'yadore-monetizer'));
        }

        $headers = array_map('strtolower', $headers);
        $section_index = array_search('section', $headers, true);
        $field_index = array_search('field', $headers, true);
        $value_index = array_search('value', $headers, true);

        if ($section_index === false || $value_index === false) {
            fclose($handle);
            throw new Exception(__('CSV import file is missing required columns.', 'yadore-monetizer'));
        }

        $payload = array(
            'meta' => array(),
            'data' => array(),
        );

        while (($row = fgetcsv($handle)) !== false) {
            $section = strtolower(trim((string) ($row[$section_index] ?? '')));
            $field = isset($row[$field_index]) ? trim((string) $row[$field_index]) : '';
            $value = $this->convert_import_value($row[$value_index] ?? '');

            if ($section === 'meta') {
                if ($field !== '') {
                    $payload['meta'][$field] = $value;
                }
                continue;
            }

            if (!isset($payload['data'][$section]) || !is_array($payload['data'][$section])) {
                $payload['data'][$section] = array();
            }

            if ($section === 'settings') {
                $payload['data'][$section][] = array(
                    'key' => $field,
                    'value' => $value,
                );
                continue;
            }

            $payload['data'][$section][] = $value;
        }

        fclose($handle);

        return $payload;
    }

    private function normalize_import_payload($raw) {
        $payload = array(
            'meta' => array(),
            'data' => array(
                'settings' => array(),
                'keywords' => array(),
                'analytics' => array(),
                'logs' => array(),
                'cache' => array(),
            ),
        );

        if (isset($raw['meta']) && is_array($raw['meta'])) {
            $payload['meta'] = $raw['meta'];
            unset($raw['meta']);
        }

        if (isset($raw['data']) && is_array($raw['data'])) {
            foreach ($raw['data'] as $section => $records) {
                $payload['data'][$section] = is_array($records) ? $records : array($records);
            }
            unset($raw['data']);
        }

        foreach ($raw as $section => $records) {
            if (!is_string($section)) {
                continue;
            }

            if (!isset($payload['data'][$section]) || !is_array($payload['data'][$section])) {
                $payload['data'][$section] = array();
            }

            if (is_array($records)) {
                $payload['data'][$section] = array_merge($payload['data'][$section], $records);
            } else {
                $payload['data'][$section][] = $records;
            }
        }

        foreach ($payload['data'] as $section => $records) {
            if (!is_array($records)) {
                $payload['data'][$section] = array($records);
            }
        }

        return $payload;
    }

    private function convert_import_value($value) {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if ($trimmed === 'null') {
            return null;
        }

        if ($trimmed === 'true') {
            return true;
        }

        if ($trimmed === 'false') {
            return false;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (function_exists('is_serialized') && is_serialized($trimmed)) {
            return maybe_unserialize($trimmed);
        }

        return $value;
    }

    private function validate_import_payload(array $payload) {
        $stats = array();
        $messages = array();

        foreach ($payload['data'] as $section => $records) {
            $count = is_array($records) ? count($records) : 0;
            $stats[$section . '_records'] = $count;

            if ($count > 0) {
                $messages[] = sprintf(__('Detected %1$d records for %2$s.', 'yadore-monetizer'), $count, $section);
            }
        }

        if (!empty($payload['meta']['site_url'])) {
            $messages[] = sprintf(__('Export originated from %s.', 'yadore-monetizer'), $payload['meta']['site_url']);
        }

        return array(
            'stats' => $stats,
            'messages' => $messages,
        );
    }

    private function import_payload(array $payload, array $flags) {
        $stats = array(
            'settings_imported' => 0,
            'keywords_imported' => 0,
            'analytics_imported' => 0,
            'logs_imported' => 0,
            'cache_entries_imported' => 0,
        );

        $messages = array();

        if (!empty($payload['data']['settings'])) {
            $stats['settings_imported'] = $this->import_settings_records($payload['data']['settings'], !empty($flags['overwrite']));
            $messages[] = sprintf(__('Imported %d settings.', 'yadore-monetizer'), $stats['settings_imported']);
        }

        if (!empty($payload['data']['keywords'])) {
            $stats['keywords_imported'] = $this->import_table_records('yadore_post_keywords', $payload['data']['keywords']);
            $messages[] = sprintf(__('Imported %d keyword rows.', 'yadore-monetizer'), $stats['keywords_imported']);
        }

        if (!empty($payload['data']['analytics'])) {
            $stats['analytics_imported'] = $this->import_table_records('yadore_analytics', $payload['data']['analytics']);
            $messages[] = sprintf(__('Imported %d analytics records.', 'yadore-monetizer'), $stats['analytics_imported']);
        }

        if (!empty($payload['data']['logs'])) {
            $log_summary = $this->import_log_records($payload['data']['logs']);
            $stats['logs_imported'] = array_sum($log_summary);
            $messages[] = sprintf(__('Imported %d log entries.', 'yadore-monetizer'), $stats['logs_imported']);
        }

        if (!empty($payload['data']['cache'])) {
            $cache_summary = $this->import_cache_records($payload['data']['cache']);
            $stats['cache_entries_imported'] = $cache_summary['entries'];
            if ($cache_summary['metrics']) {
                $messages[] = __('Cache metrics restored.', 'yadore-monetizer');
            }
            if ($stats['cache_entries_imported'] > 0) {
                $messages[] = sprintf(__('Imported %d cache entries.', 'yadore-monetizer'), $stats['cache_entries_imported']);
            }
        }

        return array(
            'stats' => $stats,
            'messages' => $messages,
        );
    }

    private function import_settings_records(array $records, $overwrite) {
        $defaults = $this->get_default_options();
        $allowed_keys = array_merge(array_keys($defaults), array(
            'yadore_install_timestamp',
            'yadore_plugin_version',
            'yadore_cache_metrics',
        ));

        $updated = 0;

        foreach ($records as $record) {
            if (!is_array($record) || empty($record['key'])) {
                continue;
            }

            $key = sanitize_key($record['key']);
            if (!in_array($key, $allowed_keys, true)) {
                continue;
            }

            if (!$overwrite && get_option($key, null) !== null) {
                continue;
            }

            $value = $record['value'] ?? null;
            if (is_string($value)) {
                $converted = $this->convert_import_value($value);
                $value = $converted;
            }

            update_option($key, $value, false);
            $updated++;
        }

        return $updated;
    }

    private function import_log_records(array $records) {
        $api_logs = array();
        $error_logs = array();

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $type = isset($record['log_type']) ? strtolower((string) $record['log_type']) : 'api';

            if ($type === 'error') {
                $error_logs[] = $record;
            } else {
                $api_logs[] = $record;
            }
        }

        return array(
            'api' => $this->import_table_records('yadore_api_logs', $api_logs),
            'error' => $this->import_table_records('yadore_error_logs', $error_logs),
        );
    }

    private function import_cache_records(array $records) {
        $metrics_restored = false;
        $entries = array();

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $type = isset($record['entry_type']) ? strtolower((string) $record['entry_type']) : '';

            if ($type === 'metrics' && isset($record['data']) && is_array($record['data'])) {
                update_option('yadore_cache_metrics', $record['data'], false);
                $metrics_restored = true;
                continue;
            }

            if ($type === 'ai_cache') {
                unset($record['entry_type']);
            }

            $entries[] = $record;
        }

        $imported = $this->import_table_records('yadore_ai_cache', $entries);

        return array(
            'metrics' => $metrics_restored,
            'entries' => $imported,
        );
    }

    private function import_table_records($table_suffix, array $records) {
        global $wpdb;

        if (empty($records)) {
            return 0;
        }

        $table = $wpdb->prefix . $table_suffix;
        if (!$this->table_exists($table)) {
            return 0;
        }

        $columns = $this->get_table_columns($table);
        if (empty($columns)) {
            return 0;
        }

        $imported = 0;

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $row = array();
            foreach ($record as $column => $value) {
                if (!in_array($column, $columns, true)) {
                    continue;
                }

                if (is_array($value) || is_object($value)) {
                    $row[$column] = wp_json_encode($value);
                } else {
                    $row[$column] = $value;
                }
            }

            if (empty($row)) {
                continue;
            }

            $result = $wpdb->replace($table, $row);
            if ($result !== false) {
                $imported++;
            }
        }

        return $imported;
    }

    private function get_table_columns($table) {
        static $cache = array();

        if (isset($cache[$table])) {
            return $cache[$table];
        }

        global $wpdb;

        $columns = $wpdb->get_col('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $cache[$table] = is_array($columns) ? $columns : array();

        return $cache[$table];
    }

    private function optimize_plugin_cache() {
        $expired_transients = $this->purge_transients_by_prefix(array('yadore_products_', 'yadore_ai_', 'yadore_analytics_'), true);
        $expired_rows = $this->prune_ai_cache_table(true);

        return array(
            'expired_transients' => $expired_transients,
            'expired_ai_rows' => $expired_rows,
        );
    }

    private function prune_ai_cache_table($only_expired = false) {
        global $wpdb;

        $table = $wpdb->prefix . 'yadore_ai_cache';
        if (!$this->table_exists($table)) {
            return 0;
        }

        if ($only_expired) {
            $now = $this->get_wp_timestamp();
            $deleted = $wpdb->query($wpdb->prepare('DELETE FROM ' . $table . ' WHERE expires_at > 0 AND expires_at < %d', $now));
        } else {
            $deleted = $wpdb->query('DELETE FROM ' . $table);
        }

        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    private function optimize_database_tables() {
        global $wpdb;

        $tables = $this->get_plugin_tables();
        $results = array();

        foreach ($tables as $table) {
            if (!$this->table_exists($table)) {
                continue;
            }

            $optimized = $wpdb->query('OPTIMIZE TABLE `' . str_replace('`', '``', $table) . '`');
            $analyzed = $wpdb->query('ANALYZE TABLE `' . str_replace('`', '``', $table) . '`');

            $results[] = array(
                'table' => $table,
                'optimized' => $optimized !== false,
                'analyzed' => $analyzed !== false,
            );
        }

        return array(
            'tables' => $results,
        );
    }

    private function get_plugin_tables() {
        global $wpdb;

        return array(
            $wpdb->prefix . 'yadore_ai_cache',
            $wpdb->prefix . 'yadore_post_keywords',
            $wpdb->prefix . 'yadore_api_logs',
            $wpdb->prefix . 'yadore_error_logs',
            $wpdb->prefix . 'yadore_analytics',
            $wpdb->prefix . 'yadore_api_clicks',
        );
    }

    private function cleanup_old_data_records() {
        $log_summary = $this->delete_old_logs();
        $expired_cache = $this->optimize_plugin_cache();

        return array(
            'logs' => $log_summary,
            'cache' => $expired_cache,
        );
    }

    private function archive_logs_to_file() {
        $config = array(
            'data_types' => array('logs'),
            'date_range' => 'all',
            'format' => 'json',
        );

        $payload = $this->prepare_export_payload($config);
        $content = $this->convert_export_payload($payload, 'json');

        $package = array(
            'filename' => $this->generate_export_filename('json'),
            'format' => 'json',
            'mime_type' => $this->get_export_mime_type('json'),
            'content' => base64_encode($content),
            'meta' => $payload['meta'],
        );

        return $this->save_export_package($package, 'archives');
    }

    private function delete_old_logs() {
        global $wpdb;

        $summary = array(
            'api_logs' => 0,
            'error_logs' => 0,
        );

        $api_table = $wpdb->prefix . 'yadore_api_logs';
        $error_table = $wpdb->prefix . 'yadore_error_logs';

        $api_days = max(1, (int) get_option('yadore_log_retention_days', 30));
        $error_days = max(1, (int) get_option('yadore_error_retention_days', 90));

        $now = $this->get_wp_timestamp();

        if ($this->table_exists($api_table)) {
            $cutoff = gmdate('Y-m-d H:i:s', $now - ($api_days * DAY_IN_SECONDS));
            $deleted = $wpdb->query($wpdb->prepare('DELETE FROM ' . $api_table . ' WHERE created_at < %s', $cutoff));
            if (is_numeric($deleted)) {
                $summary['api_logs'] = (int) $deleted;
            }
        }

        if ($this->table_exists($error_table)) {
            $cutoff = gmdate('Y-m-d H:i:s', $now - ($error_days * DAY_IN_SECONDS));
            $deleted = $wpdb->query($wpdb->prepare('DELETE FROM ' . $error_table . ' WHERE created_at < %s', $cutoff));
            if (is_numeric($deleted)) {
                $summary['error_logs'] = (int) $deleted;
            }
        }

        return $summary;
    }

    public function run_system_cleanup() {
        $cleanup = $this->cleanup_old_data_records();
        $database = $this->optimize_database_tables();

        return array(
            'cleanup' => $cleanup,
            'database' => $database,
        );
    }

    private function schedule_cleanup_event($interval) {
        $allowed = array('hourly', 'twicedaily', 'daily', 'weekly');
        if (!in_array($interval, $allowed, true)) {
            $interval = 'daily';
        }

        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook('yadore_run_system_cleanup');
        }

        $timestamp = $this->calculate_schedule_timestamp($interval, '02:00');
        wp_schedule_event($timestamp, $interval, 'yadore_run_system_cleanup');

        return array(
            'interval' => $interval,
            'next_run' => $timestamp,
            'next_run_human' => $this->format_timestamp_for_display($timestamp),
        );
    }

    private function reset_plugin_settings_to_defaults() {
        $defaults = $this->get_default_options();
        $updated = 0;

        foreach ($defaults as $key => $value) {
            update_option($key, $value, false);
            $updated++;
        }

        return $updated;
    }

    private function remove_all_plugin_data() {
        global $wpdb;

        $tables = $this->get_plugin_tables();
        $dropped = 0;

        foreach ($tables as $table) {
            if ($this->table_exists($table)) {
                $wpdb->query('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
                $dropped++;
            }
        }

        $options = array_merge(array_keys($this->get_default_options()), array(
            'yadore_install_timestamp',
            'yadore_plugin_version',
            'yadore_cache_metrics',
            'yadore_export_schedules',
        ));

        foreach ($options as $option) {
            delete_option($option);
        }

        $this->api_cache = array();
        $this->keyword_candidate_cache = array();

        return array(
            'tables_dropped' => $dropped,
            'options_deleted' => count($options),
        );
    }

    private function perform_factory_reset() {
        $summary = $this->remove_all_plugin_data();

        $this->create_tables();
        $this->set_default_options();
        $this->setup_initial_data();
        $summary['templates'] = $this->restore_default_templates(true);

        return $summary;
    }

    private function generate_config_export_package() {
        $config = array(
            'data_types' => array('settings', 'keywords', 'analytics', 'logs', 'cache'),
            'date_range' => 'all',
            'format' => 'json',
        );

        $payload = $this->prepare_export_payload($config);
        $content = $this->convert_export_payload($payload, 'json');

        return array(
            'filename' => $this->generate_export_filename('json'),
            'format' => 'json',
            'mime_type' => $this->get_export_mime_type('json'),
            'content' => base64_encode($content),
            'meta' => $payload['meta'],
        );
    }

    private function save_export_package(array $package, $subdir = 'archives') {
        if (empty($package['content'])) {
            return array();
        }

        if (!function_exists('wp_upload_dir')) {
            return array();
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return array();
        }

        $directory = trailingslashit($uploads['basedir']) . 'yadore-monetizer/' . trim($subdir, '/');
        if (!wp_mkdir_p($directory)) {
            return array();
        }

        $filename = isset($package['filename']) ? $package['filename'] : $this->generate_export_filename('json');
        $path = trailingslashit($directory) . $filename;

        $binary = base64_decode($package['content'], true);
        if ($binary === false) {
            return array();
        }

        $bytes = file_put_contents($path, $binary);
        if ($bytes === false) {
            return array();
        }

        $url = trailingslashit($uploads['baseurl']) . 'yadore-monetizer/' . trim($subdir, '/') . '/' . $filename;

        return array(
            'path' => $path,
            'url' => $url,
            'size' => $bytes,
            'filename' => $filename,
        );
    }

    private function fetch_remote_settings_package($url) {
        $endpoints = array(
            trailingslashit($url) . 'wp-json/yadore/v1/config',
            $url,
        );

        foreach ($endpoints as $endpoint) {
            $response = wp_remote_get($endpoint, array('timeout' => 15));
            if (is_wp_error($response)) {
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            if (!is_string($body) || trim($body) === '') {
                continue;
            }

            $decoded = json_decode($body, true);
            if (!is_array($decoded)) {
                continue;
            }

            return $this->normalize_import_payload($decoded);
        }

        return array();
    }

    private function perform_auto_optimize() {
        $cache = $this->optimize_plugin_cache();
        $cleanup = $this->cleanup_old_data_records();
        $database = $this->optimize_database_tables();

        return array(
            'cache' => $cache,
            'cleanup' => $cleanup,
            'database' => $database,
        );
    }

    private function analyze_keywords_locally($text, $limit) {
        $text = wp_strip_all_tags((string) $text);
        if ($text === '') {
            return array();
        }

        $normalized = strtolower($text);
        $normalized = preg_replace('/[^a-z0-9\s]/i', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $words = explode(' ', trim($normalized));

        $stop_words = array('und','oder','aber','dass','nicht','sein','sind','sich','mit','eine','einer','eines','ein','der','die','das','den','dem','des','auf','für','von','zum','zur','ist','im','am','the','and','for','with','this','that','from','your','have','has','are','was','were','will','best','guide','review','reviews','test','tests','2024','2025','2026','2023','latest','top','complete','ultimate','check','update','news','new','edition','insights','tips','tricks','vergleich','kaufen','preis','erfahrungen','bester','beste','bieten','unser','ihre','seine','meine','deine','falls','auch','noch','heute');
        $stop_map = array_flip($stop_words);

        $counts = array();
        $bigrams = array();
        $previous = '';

        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '' || isset($stop_map[$word]) || is_numeric($word)) {
                $previous = '';
                continue;
            }

            $counts[$word] = ($counts[$word] ?? 0) + 1;

            if ($previous !== '') {
                $bigram = $previous . ' ' . $word;
                $bigrams[$bigram] = ($bigrams[$bigram] ?? 0) + 1;
            }

            $previous = $word;
        }

        $candidates = array();

        arsort($bigrams);
        foreach (array_keys($bigrams) as $phrase) {
            $candidates[] = $this->normalize_keyword_case($phrase);
        }

        arsort($counts);
        foreach (array_keys($counts) as $word) {
            $candidates[] = $this->normalize_keyword_case($word);
        }

        $sanitized = $this->sanitize_keyword_list($candidates);

        return array_slice($sanitized, 0, max(1, (int) $limit));
    }

    private function analyze_keywords_with_ai($text, $limit) {
        $result = $this->call_gemini_api(__('Keyword Analyzer', 'yadore-monetizer'), $text, false, 0);

        if (!is_array($result)) {
            throw new Exception(__('Unexpected response from AI service.', 'yadore-monetizer'));
        }

        if (isset($result['error'])) {
            throw new Exception($result['error']);
        }

        $keywords = array();

        if (!empty($result['keyword'])) {
            $keywords[] = $result['keyword'];
        }

        if (!empty($result['alternate_keywords']) && is_array($result['alternate_keywords'])) {
            $keywords = array_merge($keywords, $result['alternate_keywords']);
        }

        if (!empty($result['alternates']) && is_array($result['alternates'])) {
            $keywords = array_merge($keywords, $result['alternates']);
        }

        $keywords = $this->sanitize_keyword_list($keywords);

        $summary = '';
        if (!empty($result['rationale'])) {
            $summary = trim((string) $result['rationale']);
        }

        return array(
            'keywords' => array_slice($keywords, 0, max(1, (int) $limit)),
            'summary' => $summary,
        );
    }

    private function store_export_file($schedule_id, $format, $content) {
        if (!function_exists('wp_upload_dir')) {
            throw new Exception(__('Upload directory is not available.', 'yadore-monetizer'));
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            throw new Exception($uploads['error']);
        }

        $directory = trailingslashit($uploads['basedir']) . 'yadore-exports';
        if (!wp_mkdir_p($directory)) {
            throw new Exception(__('Failed to prepare export directory.', 'yadore-monetizer'));
        }

        $filename = sprintf('yadore-export-%s-%s.%s', preg_replace('/[^A-Za-z0-9]/', '', (string) $schedule_id), date_i18n('Ymd-His'), $format);
        $path = trailingslashit($directory) . $filename;

        $bytes = file_put_contents($path, $content);
        if ($bytes === false) {
            throw new Exception(__('Failed to write export file to disk.', 'yadore-monetizer'));
        }

        $url = trailingslashit($uploads['baseurl']) . 'yadore-exports/' . $filename;

        return array(
            'path' => $path,
            'url' => $url,
            'filename' => $filename,
            'size' => $bytes,
        );
    }

    private function format_timestamp_for_display($timestamp) {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        if (function_exists('date_i18n')) {
            $format = get_option('date_format', 'Y-m-d') . ' ' . get_option('time_format', 'H:i');
            return date_i18n($format, $timestamp);
        }

        $timezone = $this->get_wp_timezone();
        $date = new DateTimeImmutable('@' . $timestamp);
        $date = $date->setTimezone($timezone);

        return $date->format('Y-m-d H:i');
    }

    private function get_wp_timezone() {
        if (function_exists('wp_timezone')) {
            return wp_timezone();
        }

        $timezone_string = get_option('timezone_string');
        if ($timezone_string) {
            try {
                return new DateTimeZone($timezone_string);
            } catch (Exception $e) {
                // Fall back below.
            }
        }

        $offset = (float) get_option('gmt_offset', 0);
        $hours = (int) $offset;
        $minutes = (int) round(($offset - $hours) * 60);
        $sign = $offset >= 0 ? '+' : '-';
        $tz_name = sprintf('%s%02d:%02d', $sign, abs($hours), abs($minutes));

        try {
            return new DateTimeZone($tz_name);
        } catch (Exception $e) {
            return new DateTimeZone('UTC');
        }
    }

    private function get_wp_timestamp() {
        return function_exists('current_time') ? current_time('timestamp') : time();
    }

}
if (!function_exists('yadore_get_formatted_price_parts')) {
    /**
     * Format price information for consistent display across templates.
     *
     * @param mixed  $price    Price array or raw value from API response.
     * @param string $currency Optional explicit currency code.
     *
     * @return array{amount:string,currency:string}
     */
    function yadore_get_formatted_price_parts($price, $currency = '') {
        $amount = $price;

        if (is_array($price)) {
            $amount = $price['amount'] ?? '';
            if ($currency === '' && isset($price['currency'])) {
                $currency = $price['currency'];
            }
        }

        $currency = strtoupper(sanitize_text_field((string) $currency));
        $formatted_amount = '';

        if ($amount !== null && $amount !== '') {
            $raw_amount = preg_replace('/[^0-9,\.]/', '', (string) $amount);
            $normalized_amount = str_replace(',', '.', $raw_amount);

            if ($normalized_amount !== '' && is_numeric($normalized_amount)) {
                $formatted_amount = number_format((float) $normalized_amount, 2, ',', '.');
            } else {
                $formatted_amount = sanitize_text_field((string) $amount);
            }
        }

        if (strtoupper($formatted_amount) === 'N/A') {
            $currency = '';
        }

        return array(
            'amount' => $formatted_amount,
            'currency' => $currency,
        );
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