<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        Yadore Monetizer Pro Settings
        <span class="version-badge">v2.9.7</span>
    </h1>

    <?php
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['yadore_nonce'], 'yadore_settings')) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    $market_options = isset($available_markets) && is_array($available_markets) ? $available_markets : array();
    $default_market_code = isset($default_market) ? strtoupper($default_market) : 'DE';
    $current_market = isset($options['yadore_market'])
        ? $options['yadore_market']
        : get_option('yadore_market', $default_market_code);
    $current_market = strtoupper((string) $current_market);
    if ($current_market !== '' && !isset($market_options[$current_market])) {
        $market_options[$current_market] = esc_html__('Manuell hinterlegt', 'yadore-monetizer');
    }
    ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=yadore-settings')); ?>" class="yadore-settings-form">
        <?php wp_nonce_field('yadore_settings', 'yadore_nonce'); ?>

        <div class="yadore-settings-container">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <div class="nav-tabs">
                    <button type="button" class="nav-tab nav-tab-active" data-tab="general">
                        <span class="dashicons dashicons-admin-generic"></span>
                        General
                    </button>
                    <button type="button" class="nav-tab" data-tab="ai">
                        <span class="dashicons dashicons-admin-customizer"></span>
                        AI Settings
                    </button>
                    <button type="button" class="nav-tab" data-tab="display">
                        <span class="dashicons dashicons-visibility"></span>
                        Display
                    </button>
                    <button type="button" class="nav-tab" data-tab="performance">
                        <span class="dashicons dashicons-performance"></span>
                        Performance
                    </button>
                    <button type="button" class="nav-tab" data-tab="advanced">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Advanced
                    </button>
                </div>
            </div>

            <!-- General Settings -->
            <div class="settings-panel active" id="panel-general">
                <div class="yadore-card">
                    <div class="card-header">
                        <h2><span class="dashicons dashicons-admin-network"></span> API Configuration</h2>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label for="yadore_api_key" class="form-label">
                                <strong>Yadore API Key</strong>
                                <span class="required">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       name="yadore_api_key" 
                                       id="yadore_api_key"
                                       value="<?php echo esc_attr(get_option('yadore_api_key', '')); ?>" 
                                       class="form-input"
                                       placeholder="Enter your Yadore API key">
                                <button type="button" class="button button-secondary" id="test-yadore-api">
                                    <span class="dashicons dashicons-admin-network"></span> Test Connection
                                </button>
                            </div>
                            <p class="form-description">
                                Your Yadore API key is required to fetch product data. 
                                <a href="https://yadore.com/api" target="_blank">Get your API key here</a>.
                            </p>
                            <div id="yadore-api-test-results" class="api-test-results"></div>
                        </div>

                        <div class="form-group">
                            <label for="yadore_market" class="form-label">
                                <strong><?php esc_html_e('Default Market', 'yadore-monetizer'); ?></strong>
                                <span class="required">*</span>
                            </label>
                            <?php if (!empty($market_options)) : ?>
                                <select name="yadore_market" id="yadore_market" class="form-select">
                                    <?php foreach ($market_options as $market_id => $market_label) :
                                        $market_id = is_string($market_id) ? strtoupper($market_id) : '';
                                        if ($market_id === '') {
                                            continue;
                                        }
                                    ?>
                                        <option value="<?php echo esc_attr($market_id); ?>" <?php selected($current_market, $market_id); ?>>
                                            <?php echo esc_html($market_id . ' – ' . $market_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="form-description">
                                    <?php esc_html_e('Choose the market that matches your approved Yadore account. Only markets returned by the Yadore API are listed.', 'yadore-monetizer'); ?>
                                </p>
                            <?php else : ?>
                                <input type="text"
                                       name="yadore_market"
                                       id="yadore_market"
                                       value="<?php echo esc_attr($current_market); ?>"
                                       class="form-input"
                                       placeholder="<?php echo esc_attr($default_market_code); ?>"
                                       pattern="[A-Za-z]{2}"
                                       title="<?php esc_attr_e('Use the two-letter uppercase market code, e.g. DE, AT, FR.', 'yadore-monetizer'); ?>">
                                <p class="form-description">
                                    <?php esc_html_e('Enter the two-letter market code (ISO 3166-1 alpha-2) you are approved for, such as DE or AT. The value must match a market enabled for your API key.', 'yadore-monetizer'); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="yadore_overlay_enabled" 
                                       value="1" 
                                       <?php checked(get_option('yadore_overlay_enabled', true)); ?>>
                                <strong>Enable Product Overlay</strong>
                            </label>
                            <p class="form-description">
                                Display floating product recommendations on single posts.
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="yadore_auto_detection" 
                                       value="1" 
                                       <?php checked(get_option('yadore_auto_detection', true)); ?>>
                                <strong>Enable Auto Content Detection</strong>
                            </label>
                            <p class="form-description">
                                Automatically inject relevant products into post content.
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="yadore_debug_mode" 
                                       value="1" 
                                       <?php checked(get_option('yadore_debug_mode', false)); ?>>
                                <strong>Enable Debug Mode</strong>
                            </label>
                            <p class="form-description">
                                Enable detailed logging for troubleshooting. Disable in production.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Settings -->
            <div class="settings-panel" id="panel-ai">
                <div class="yadore-card">
                    <div class="card-header">
                        <h2><span class="dashicons dashicons-admin-generic"></span> Gemini AI Configuration</h2>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="yadore_ai_enabled" 
                                       id="yadore_ai_enabled"
                                       value="1" 
                                       <?php checked(get_option('yadore_ai_enabled', false)); ?>>
                                <strong>Enable AI Content Analysis</strong>
                            </label>
                            <p class="form-description">
                                Use Google Gemini AI to analyze post content and extract relevant product keywords.
                            </p>
                        </div>

                        <div id="ai-settings" style="<?php echo get_option('yadore_ai_enabled', false) ? '' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="yadore_gemini_api_key" class="form-label">
                                    <strong>Gemini API Key</strong>
                                    <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           name="yadore_gemini_api_key"
                                           id="yadore_gemini_api_key"
                                           value="<?php echo esc_attr(get_option('yadore_gemini_api_key', '')); ?>"
                                           class="form-input"
                                           placeholder="Enter your Gemini API key"
                                           autocomplete="new-password">
                                    <button type="button" class="button button-secondary" id="test-gemini-api">
                                        <span class="dashicons dashicons-admin-generic"></span> Test AI
                                    </button>
                                </div>
                                <p class="form-description">
                                    Get your free Gemini API key from
                                    <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.
                                </p>
                                <?php if (get_option('yadore_gemini_api_key')) : ?>
                                    <p class="form-description">
                                        <?php esc_html_e('A Gemini API key is currently stored securely. Leave the field blank to keep the existing key.', 'yadore-monetizer'); ?>
                                    </p>
                                <?php else : ?>
                                    <p class="form-description">
                                        <?php esc_html_e('Enter your Gemini API key and save the settings to activate AI features.', 'yadore-monetizer'); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="form-description form-checkbox-inline">
                                    <label>
                                        <input type="checkbox"
                                               name="yadore_gemini_api_key_remove"
                                               value="1">
                                        <?php esc_html_e('Remove the stored key when saving', 'yadore-monetizer'); ?>
                                    </label>
                                </p>
                                <div id="gemini-api-test-results" class="api-test-results"></div>
                            </div>

                            <div class="form-group">
                                <label for="yadore_gemini_model" class="form-label">
                                    <strong>AI Model</strong>
                                </label>
                                <div class="model-selection">
                                    <?php
                                    $available_models = isset($gemini_models) && is_array($gemini_models) ? $gemini_models : array(
                                        'gemini-2.0-flash' => array('label' => 'Gemini 2.0 Flash - Fastest'),
                                        'gemini-2.0-flash-lite' => array('label' => 'Gemini 2.0 Flash Lite - Efficient'),
                                        'gemini-2.0-pro-exp' => array('label' => 'Gemini 2.0 Pro (Experimental) - Highest quality'),
                                        'gemini-2.0-flash-exp' => array('label' => 'Gemini 2.0 Flash (Experimental) - Latest features'),
                                        'gemini-1.5-pro' => array('label' => 'Gemini 1.5 Pro - Most capable'),
                                        'gemini-1.5-flash' => array('label' => 'Gemini 1.5 Flash - Balanced'),
                                        'gemini-1.5-flash-8b' => array('label' => 'Gemini 1.5 Flash 8B - Lightweight'),
                                    );
                                    $current_model = isset($selected_gemini_model)
                                        ? $selected_gemini_model
                                        : get_option('yadore_gemini_model', 'gemini-2.0-flash');
                                    ?>
                                    <select name="yadore_gemini_model" id="yadore_gemini_model" class="form-select">
                                        <?php foreach ($available_models as $model_key => $model_info) : ?>
                                            <option value="<?php echo esc_attr($model_key); ?>" <?php selected($current_model, $model_key); ?>>
                                                <?php echo esc_html($model_info['label'] ?? $model_key); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php
                                    $preset_buttons = array(
                                        'gemini-2.0-flash' => __('Flash 2.0', 'yadore-monetizer'),
                                        'gemini-2.0-pro-exp' => __('Pro 2.0 (Exp)', 'yadore-monetizer'),
                                        'gemini-1.5-pro' => __('1.5 Pro', 'yadore-monetizer'),
                                        'gemini-1.5-flash-8b' => __('1.5 Flash 8B', 'yadore-monetizer'),
                                    );
                                    ?>
                                    <div class="model-presets">
                                        <?php foreach ($preset_buttons as $model_key => $label) : ?>
                                            <button type="button"
                                                    class="button button-small model-preset <?php echo $current_model === $model_key ? 'button-primary' : 'button-secondary'; ?>"
                                                    data-model="<?php echo esc_attr($model_key); ?>">
                                                <?php echo esc_html($label); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <p class="form-description">
                                    Choose from the latest Gemini models. Gemini 2.0 provides the newest capabilities, while Gemini 1.5 models balance performance and cost.
                                </p>
                            </div>

                            <div class="form-group">
                                <label for="yadore_ai_prompt" class="form-label">
                                    <strong>AI Analysis Prompt</strong>
                                </label>
                                <textarea name="yadore_ai_prompt" 
                                          id="yadore_ai_prompt" 
                                          class="form-textarea" 
                                          rows="4"
                                          placeholder="Enter the prompt for AI content analysis"><?php echo esc_textarea(get_option('yadore_ai_prompt', 'Analyze this content and identify the main product category that readers would be interested in purchasing. Return only the product keyword.')); ?></textarea>
                                <p class="form-description">
                                    Customize the prompt sent to the AI for content analysis. Use {title} and {content} as placeholders.
                                </p>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="yadore_ai_temperature" class="form-label">
                                        <strong>AI Temperature</strong>
                                    </label>
                                    <input type="range" 
                                           name="yadore_ai_temperature" 
                                           id="yadore_ai_temperature"
                                           min="0" 
                                           max="2" 
                                           step="0.1"
                                           value="<?php echo esc_attr(get_option('yadore_ai_temperature', '0.3')); ?>">
                                    <div class="range-labels">
                                        <span>Conservative (0)</span>
                                        <span id="temperature-value"><?php echo get_option('yadore_ai_temperature', '0.3'); ?></span>
                                        <span>Creative (2)</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="yadore_ai_max_tokens" class="form-label">
                                        <strong>Max Tokens</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_ai_max_tokens" 
                                           id="yadore_ai_max_tokens"
                                           min="10" 
                                           max="1000"
                                           value="<?php echo esc_attr(get_option('yadore_ai_max_tokens', '50')); ?>"
                                           class="form-input small">
                                    <p class="form-description">Maximum tokens for AI response</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="settings-panel" id="panel-display">
                <div class="yadore-card">
                    <div class="card-header">
                        <h2><span class="dashicons dashicons-visibility"></span> Display Options</h2>
                    </div>
                    <div class="card-content">
                        <!-- Overlay Settings -->
                        <div class="form-section">
                            <h3><span class="dashicons dashicons-visibility"></span> Product Overlay</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="yadore_overlay_delay" class="form-label">
                                        <strong>Overlay Delay (ms)</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_overlay_delay" 
                                           id="yadore_overlay_delay"
                                           min="0" 
                                           max="10000"
                                           value="<?php echo esc_attr(get_option('yadore_overlay_delay', '2000')); ?>"
                                           class="form-input small">
                                    <p class="form-description">Delay before showing overlay (milliseconds)</p>
                                </div>

                                <div class="form-group">
                                    <label for="yadore_overlay_scroll_threshold" class="form-label">
                                        <strong>Scroll Threshold (px)</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_overlay_scroll_threshold" 
                                           id="yadore_overlay_scroll_threshold"
                                           min="0" 
                                           max="1000"
                                           value="<?php echo esc_attr(get_option('yadore_overlay_scroll_threshold', '300')); ?>"
                                           class="form-input small">
                                    <p class="form-description">Pixels user must scroll before overlay shows</p>
                                </div>

                                <div class="form-group">
                                    <label for="yadore_overlay_limit" class="form-label">
                                        <strong>Products in Overlay</strong>
                                    </label>
                                    <select name="yadore_overlay_limit" id="yadore_overlay_limit" class="form-select small">
                                        <option value="1" <?php selected(get_option('yadore_overlay_limit', '3'), '1'); ?>>1 Product</option>
                                        <option value="3" <?php selected(get_option('yadore_overlay_limit', '3'), '3'); ?>>3 Products</option>
                                        <option value="6" <?php selected(get_option('yadore_overlay_limit', '3'), '6'); ?>>6 Products</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="yadore_overlay_position" class="form-label">
                                        <strong>Overlay Position</strong>
                                    </label>
                                    <select name="yadore_overlay_position" id="yadore_overlay_position" class="form-select">
                                        <option value="center" <?php selected(get_option('yadore_overlay_position', 'center'), 'center'); ?>>Center</option>
                                        <option value="bottom-right" <?php selected(get_option('yadore_overlay_position', 'center'), 'bottom-right'); ?>>Bottom Right</option>
                                        <option value="bottom-left" <?php selected(get_option('yadore_overlay_position', 'center'), 'bottom-left'); ?>>Bottom Left</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="yadore_overlay_animation" class="form-label">
                                        <strong>Animation</strong>
                                    </label>
                                    <select name="yadore_overlay_animation" id="yadore_overlay_animation" class="form-select">
                                        <option value="fade" <?php selected(get_option('yadore_overlay_animation', 'fade'), 'fade'); ?>>Fade In</option>
                                        <option value="slide" <?php selected(get_option('yadore_overlay_animation', 'fade'), 'slide'); ?>>Slide Up</option>
                                        <option value="zoom" <?php selected(get_option('yadore_overlay_animation', 'fade'), 'zoom'); ?>>Zoom In</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Injection Settings -->
                        <div class="form-section">
                            <h3><span class="dashicons dashicons-editor-insertmore"></span> Content Auto-Injection</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="yadore_injection_method" class="form-label">
                                        <strong>Injection Method</strong>
                                    </label>
                                    <select name="yadore_injection_method" id="yadore_injection_method" class="form-select">
                                        <option value="after_paragraph" <?php selected(get_option('yadore_injection_method', 'after_paragraph'), 'after_paragraph'); ?>>After Paragraph</option>
                                        <option value="end_of_content" <?php selected(get_option('yadore_injection_method', 'after_paragraph'), 'end_of_content'); ?>>End of Content</option>
                                        <option value="before_content" <?php selected(get_option('yadore_injection_method', 'after_paragraph'), 'before_content'); ?>>Before Content</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="yadore_injection_position" class="form-label">
                                        <strong>Paragraph Position</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_injection_position" 
                                           id="yadore_injection_position"
                                           min="1" 
                                           max="10"
                                           value="<?php echo esc_attr(get_option('yadore_injection_position', '2')); ?>"
                                           class="form-input small">
                                    <p class="form-description">After which paragraph to inject products</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Settings -->
            <div class="settings-panel" id="panel-performance">
                <div class="yadore-card">
                    <div class="card-header">
                        <h2><span class="dashicons dashicons-performance"></span> Performance & Caching</h2>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label for="yadore_cache_duration" class="form-label">
                                <strong>Product Cache Duration</strong>
                            </label>
                            <select name="yadore_cache_duration" id="yadore_cache_duration" class="form-select">
                                <option value="300" <?php selected(get_option('yadore_cache_duration', '3600'), '300'); ?>>5 minutes</option>
                                <option value="1800" <?php selected(get_option('yadore_cache_duration', '3600'), '1800'); ?>>30 minutes</option>
                                <option value="3600" <?php selected(get_option('yadore_cache_duration', '3600'), '3600'); ?>>1 hour</option>
                                <option value="43200" <?php selected(get_option('yadore_cache_duration', '3600'), '43200'); ?>>12 hours</option>
                                <option value="86400" <?php selected(get_option('yadore_cache_duration', '3600'), '86400'); ?>>24 hours</option>
                            </select>
                            <p class="form-description">How long to cache product data to improve performance.</p>
                        </div>

                        <div class="form-group">
                            <label for="yadore_ai_cache_duration" class="form-label">
                                <strong>AI Analysis Cache Duration</strong>
                            </label>
                            <select name="yadore_ai_cache_duration" id="yadore_ai_cache_duration" class="form-select">
                                <option value="86400" <?php selected(get_option('yadore_ai_cache_duration', '157680000'), '86400'); ?>>1 day</option>
                                <option value="604800" <?php selected(get_option('yadore_ai_cache_duration', '157680000'), '604800'); ?>>1 week</option>
                                <option value="2592000" <?php selected(get_option('yadore_ai_cache_duration', '157680000'), '2592000'); ?>>30 days</option>
                                <option value="157680000" <?php selected(get_option('yadore_ai_cache_duration', '157680000'), '157680000'); ?>>5 years (permanent)</option>
                            </select>
                            <p class="form-description">AI analysis results are cached to avoid repeated API calls.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" 
                                       name="yadore_performance_mode" 
                                       value="1" 
                                       <?php checked(get_option('yadore_performance_mode', false)); ?>>
                                <strong>Enable Performance Mode</strong>
                            </label>
                            <p class="form-description">
                                Optimize for high-traffic sites by reducing API calls and enabling aggressive caching.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="settings-panel" id="panel-advanced">
                <div class="yadore-card">
                    <div class="card-header">
                        <h2><span class="dashicons dashicons-admin-tools"></span> Advanced Options</h2>
                    </div>
                    <div class="card-content">
                        <!-- Logging Settings -->
                        <div class="form-section">
                            <h3><span class="dashicons dashicons-media-text"></span> Logging & Analytics</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <input type="checkbox" 
                                               name="yadore_api_logging_enabled" 
                                               value="1" 
                                               <?php checked(get_option('yadore_api_logging_enabled', true)); ?>>
                                        <strong>Enable API Logging</strong>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <input type="checkbox" 
                                               name="yadore_analytics_enabled" 
                                               value="1" 
                                               <?php checked(get_option('yadore_analytics_enabled', true)); ?>>
                                        <strong>Enable Analytics</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="yadore_log_retention_days" class="form-label">
                                        <strong>Log Retention (Days)</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_log_retention_days" 
                                           id="yadore_log_retention_days"
                                           min="1" 
                                           max="365"
                                           value="<?php echo esc_attr(get_option('yadore_log_retention_days', '30')); ?>"
                                           class="form-input small">
                                </div>

                                <div class="form-group">
                                    <label for="yadore_error_retention_days" class="form-label">
                                        <strong>Error Log Retention (Days)</strong>
                                    </label>
                                    <input type="number" 
                                           name="yadore_error_retention_days" 
                                           id="yadore_error_retention_days"
                                           min="1" 
                                           max="365"
                                           value="<?php echo esc_attr(get_option('yadore_error_retention_days', '90')); ?>"
                                           class="form-input small">
                                </div>
                            </div>
                        </div>

                        <!-- Export/Import -->
                        <div class="form-section">
                            <h3><span class="dashicons dashicons-migrate"></span> Data Management</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <input type="checkbox" 
                                               name="yadore_export_enabled" 
                                               value="1" 
                                               <?php checked(get_option('yadore_export_enabled', true)); ?>>
                                        <strong>Enable Data Export</strong>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <input type="checkbox" 
                                               name="yadore_backup_enabled" 
                                               value="1" 
                                               <?php checked(get_option('yadore_backup_enabled', false)); ?>>
                                        <strong>Enable Auto Backup</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Multisite -->
                        <?php if (is_multisite()): ?>
                        <div class="form-section">
                            <h3><span class="dashicons dashicons-networking"></span> Multisite</h3>

                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" 
                                           name="yadore_multisite_sync" 
                                           value="1" 
                                           <?php checked(get_option('yadore_multisite_sync', false)); ?>>
                                    <strong>Sync Settings Across Network</strong>
                                </label>
                                <p class="form-description">
                                    Automatically synchronize plugin settings across all sites in the network.
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="settings-footer">
            <div class="settings-actions">
                <input type="submit" name="submit" class="button button-primary button-hero" value="Save All Settings">
                <button type="button" class="button button-secondary" id="reset-settings">
                    <span class="dashicons dashicons-update"></span> Reset to Defaults
                </button>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function() {
        const tab = $(this).data('tab');

        // Update nav
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Update panels
        $('.settings-panel').removeClass('active');
        $('#panel-' + tab).addClass('active');
    });

    // AI settings toggle
    $('#yadore_ai_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ai-settings').slideDown();
        } else {
            $('#ai-settings').slideUp();
        }
    });

    // Model presets
    $('.model-preset').on('click', function() {
        const model = $(this).data('model');
        $('#yadore_gemini_model').val(model);

        $('.model-preset').removeClass('button-primary').addClass('button-secondary');
        $(this).removeClass('button-secondary').addClass('button-primary');
    });

    // Temperature slider
    $('#yadore_ai_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });

    // API testing
    $('#test-gemini-api').on('click', yadoreTestGeminiApi);
    $('#test-yadore-api').on('click', yadoreTestYadoreApi);

    console.log('Yadore Monetizer Pro v2.9 Settings - Initialized');
});

function yadoreTestGeminiApi() {
    const button = jQuery('#test-gemini-api');
    const resultsDiv = jQuery('#gemini-api-test-results');

    button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
    resultsDiv.show().html('<div class="testing-message"><span class="dashicons dashicons-update-alt spinning"></span> Testing Gemini AI connection...</div>');

    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_test_gemini_api',
        nonce: yadore_admin.nonce
    })
    .done(function(response) {
        if (response.success) {
            resultsDiv.html(`
                <div class="api-test-success">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Success!</h4>
                    <p><strong>Model:</strong> ${response.data.model}</p>
                    <p><strong>Result:</strong> ${response.data.result}</p>
                    <p><strong>Timestamp:</strong> ${response.data.timestamp}</p>
                </div>
            `);
        } else {
            resultsDiv.html(`
                <div class="api-test-error">
                    <h4><span class="dashicons dashicons-dismiss"></span> Error</h4>
                    <p>${response.data}</p>
                </div>
            `);
        }
    })
    .fail(function(xhr, status, error) {
        resultsDiv.html(`
            <div class="api-test-error">
                <h4><span class="dashicons dashicons-dismiss"></span> Connection Failed</h4>
                <p>${error}</p>
            </div>
        `);
    })
    .always(function() {
        button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Test AI');
    });
}

function yadoreTestYadoreApi() {
    const button = jQuery('#test-yadore-api');
    const resultsDiv = jQuery('#yadore-api-test-results');

    button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
    resultsDiv.show().html('<div class="testing-message"><span class="dashicons dashicons-update-alt spinning"></span> Testing Yadore API connection...</div>');

    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_test_yadore_api',
        nonce: yadore_admin.nonce
    })
    .done(function(response) {
        if (response.success) {
            resultsDiv.html(`
                <div class="api-test-success">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Success!</h4>
                    <p><strong>Products found:</strong> ${response.data.product_count}</p>
                    <p><strong>Sample product:</strong> ${response.data.sample_product ? response.data.sample_product.title : 'N/A'}</p>
                    <p><strong>Timestamp:</strong> ${response.data.timestamp}</p>
                </div>
            `);
        } else {
            resultsDiv.html(`
                <div class="api-test-error">
                    <h4><span class="dashicons dashicons-dismiss"></span> Error</h4>
                    <p>${response.data}</p>
                </div>
            `);
        }
    })
    .fail(function(xhr, status, error) {
        resultsDiv.html(`
            <div class="api-test-error">
                <h4><span class="dashicons dashicons-dismiss"></span> Connection Failed</h4>
                <p>${error}</p>
            </div>
        `);
    })
    .always(function() {
        button.prop('disabled', false).html('<span class="dashicons dashicons-admin-network"></span> Test Connection');
    });
}
</script>