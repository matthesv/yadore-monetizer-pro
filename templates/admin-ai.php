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
$current_model_label = $available_models[$current_model]['label'] ?? $current_model;
$ai_default_prompt = YadoreMonetizer::DEFAULT_AI_PROMPT;
$ai_current_prompt = (string) get_option('yadore_ai_prompt', $ai_default_prompt);
if (trim($ai_current_prompt) === '') {
    $ai_current_prompt = $ai_default_prompt;
}
?>
<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-admin-generic"></span>
        AI Management & Analysis
        <span class="version-badge">v2.9.21</span>
    </h1>

    <div class="yadore-ai-container">
        <!-- AI Status Overview -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-dashboard"></span> AI System Status</h2>
                <div class="card-actions">
                    <button class="button button-secondary" id="refresh-ai-status">
                        <span class="dashicons dashicons-update"></span> Refresh Status
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="ai-status-grid">
                    <div class="status-item <?php echo get_option('yadore_ai_enabled', false) ? 'status-active' : 'status-inactive'; ?>">
                        <div class="status-icon">
                            <span class="dashicons <?php echo get_option('yadore_ai_enabled', false) ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
                        </div>
                        <div class="status-details">
                            <h3>AI Analysis</h3>
                            <p><?php echo get_option('yadore_ai_enabled', false) ? 'Active' : 'Disabled'; ?></p>
                        </div>
                    </div>

                    <div class="status-item <?php echo get_option('yadore_gemini_api_key') ? 'status-active' : 'status-warning'; ?>">
                        <div class="status-icon">
                            <span class="dashicons <?php echo get_option('yadore_gemini_api_key') ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                        </div>
                        <div class="status-details">
                            <h3>Gemini API</h3>
                            <p><?php echo get_option('yadore_gemini_api_key') ? 'Connected' : 'Not configured'; ?></p>
                        </div>
                    </div>

                    <div class="status-item status-active">
                        <div class="status-icon">
                            <span class="dashicons dashicons-database"></span>
                        </div>
                        <div class="status-details">
                            <h3>AI Cache</h3>
                            <p id="cache-count">Loading...</p>
                        </div>
                    </div>

                    <div class="status-item status-info">
                        <div class="status-icon">
                            <span class="dashicons dashicons-chart-area"></span>
                        </div>
                        <div class="status-details">
                            <h3>Processing Speed</h3>
                            <p id="avg-processing-time">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Configuration -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-settings"></span> AI Configuration</h2>
            </div>
            <div class="card-content">
                <div class="ai-config-tabs">
                    <div class="tab-nav">
                        <button class="tab-button active" data-tab="model">Model Settings</button>
                        <button class="tab-button" data-tab="prompts">Prompt Management</button>
                        <button class="tab-button" data-tab="testing">AI Testing</button>
                    </div>

                    <!-- Model Settings Tab -->
                    <div class="tab-content active" id="tab-model">
                        <div class="model-configuration">
                            <div class="config-row">
                                <div class="config-group">
                                    <label>Current Model</label>
                                    <div class="model-display">
                                        <span class="model-name"><?php echo esc_html($current_model_label); ?></span>
                                        <span class="model-status active">Active</span>
                                    </div>
                                </div>

                                <div class="config-group">
                                    <label>Temperature</label>
                                    <div class="temperature-display">
                                        <span class="temp-value"><?php echo get_option('yadore_ai_temperature', '0.3'); ?></span>
                                        <span class="temp-description"><?php echo floatval(get_option('yadore_ai_temperature', '0.3')) < 0.5 ? 'Conservative' : (floatval(get_option('yadore_ai_temperature', '0.3')) < 1 ? 'Balanced' : 'Creative'); ?></span>
                                    </div>
                                </div>

                                <div class="config-group">
                                    <label>Max Tokens</label>
                                    <div class="tokens-display">
                                        <span class="token-value"><?php echo get_option('yadore_ai_max_tokens', '50'); ?></span>
                                        <span class="token-description">tokens per response (max. 10,000)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="model-performance">
                                <h3><span class="dashicons dashicons-performance"></span> Model Performance Comparison</h3>
                                <div class="performance-grid">
                                    <div class="performance-card">
                                        <h4>Gemini 2.0 Flash</h4>
                                        <div class="performance-metrics">
                                            <div class="metric">
                                                <span class="metric-label">Speed</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 95%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Accuracy</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 88%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Cost</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 25%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="performance-card">
                                        <h4>Gemini 2.0 Pro (Experimental)</h4>
                                        <div class="performance-metrics">
                                            <div class="metric">
                                                <span class="metric-label">Speed</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 75%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Accuracy</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 99%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Cost</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 80%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="performance-card">
                                        <h4>Gemini 1.5 Flash 8B</h4>
                                        <div class="performance-metrics">
                                            <div class="metric">
                                                <span class="metric-label">Speed</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 85%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Accuracy</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 70%"></div>
                                                </div>
                                            </div>
                                            <div class="metric">
                                                <span class="metric-label">Cost</span>
                                                <div class="metric-bar">
                                                    <div class="metric-fill" style="width: 15%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prompt Management Tab -->
                    <div class="tab-content" id="tab-prompts">
                        <div class="prompt-management">
                            <div class="prompt-section">
                                <h3><span class="dashicons dashicons-editor-code"></span> Current Analysis Prompt</h3>
                                <div class="prompt-editor">
                                    <textarea id="ai-prompt-editor" rows="6"><?php echo esc_textarea($ai_current_prompt); ?></textarea>
                                    <div class="prompt-actions">
                                        <button class="button button-primary" id="save-prompt">
                                            <span class="dashicons dashicons-saved"></span> Save Prompt
                                        </button>
                                        <button class="button button-secondary" id="test-prompt">
                                            <span class="dashicons dashicons-admin-generic"></span> Test Prompt
                                        </button>
                                        <button class="button button-secondary" id="reset-prompt">
                                            <span class="dashicons dashicons-update"></span> Reset to Default
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="prompt-section">
                                <h3><span class="dashicons dashicons-admin-page"></span> Prompt Templates</h3>
                                <div class="prompt-templates">
                                    <div class="template-item">
                                        <h4>E-commerce Focus</h4>
                                        <p>Identify specific products mentioned in this content and suggest the most relevant product category for affiliate marketing.</p>
                                        <button class="button button-small" onclick="yadoreLoadPromptTemplate('ecommerce')">Use Template</button>
                                    </div>

                                    <div class="template-item">
                                        <h4>Technology Reviews</h4>
                                        <p>Analyze this technology content and determine the main electronic product category that readers would be interested in purchasing.</p>
                                        <button class="button button-small" onclick="yadoreLoadPromptTemplate('technology')">Use Template</button>
                                    </div>

                                    <div class="template-item">
                                        <h4>Lifestyle & Fashion</h4>
                                        <p>Examine this lifestyle content and identify fashion or lifestyle products that align with the content theme.</p>
                                        <button class="button button-small" onclick="yadoreLoadPromptTemplate('lifestyle')">Use Template</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Testing Tab -->
                    <div class="tab-content" id="tab-testing">
                        <div class="ai-testing">
                            <div class="test-section">
                                <h3><span class="dashicons dashicons-admin-generic"></span> Content Analysis Testing</h3>
                                <div class="test-input">
                                    <label for="test-content-title">Post Title</label>
                                    <input type="text" id="test-content-title" placeholder="Enter a test post title..." value="Best Smartphones 2024 - Complete Review">

                                    <label for="test-content-body">Post Content</label>
                                    <textarea id="test-content-body" rows="8" placeholder="Enter test content to analyze...">In this comprehensive review, we'll explore the top smartphones of 2024, including the latest iPhone, Samsung Galaxy, and Google Pixel models. We'll compare their cameras, performance, battery life, and overall value to help you choose the perfect device for your needs.</textarea>

                                    <div class="test-actions">
                                        <button class="button button-primary" id="run-ai-test">
                                            <span class="dashicons dashicons-admin-generic"></span> Analyze Content
                                        </button>
                                        <button class="button button-secondary" id="load-sample-content">
                                            <span class="dashicons dashicons-media-document"></span> Load Sample Content
                                        </button>
                                    </div>
                                </div>

                                <div class="test-results" id="ai-test-results" style="display: none;">
                                    <h4>Analysis Results</h4>
                                    <div class="result-content"></div>
                                </div>
                            </div>

                            <div class="test-section">
                                <h3><span class="dashicons dashicons-chart-line"></span> Batch Testing</h3>
                                <div class="batch-test">
                                    <p>Test AI analysis against multiple recent posts to evaluate consistency and accuracy.</p>
                                    <div class="batch-options">
                                        <label>
                                            <input type="checkbox" value="published" checked> Published Posts
                                        </label>
                                        <label>
                                            <input type="checkbox" value="drafts"> Draft Posts
                                        </label>
                                        <label>
                                            Number of posts: 
                                            <select id="batch-count">
                                                <option value="5">5 posts</option>
                                                <option value="10" selected>10 posts</option>
                                                <option value="25">25 posts</option>
                                            </select>
                                        </label>
                                    </div>
                                    <button class="button button-primary" id="run-batch-test">
                                        <span class="dashicons dashicons-admin-generic"></span> Run Batch Test
                                    </button>
                                </div>

                                <div class="batch-results" id="batch-test-results" style="display: none;">
                                    <h4>Batch Test Results</h4>
                                    <div class="result-content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Usage Statistics -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-chart-area"></span> AI Usage Statistics</h2>
            </div>
            <div class="card-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" id="ai-requests-today">Loading...</div>
                        <div class="stat-label">Requests Today</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number" id="ai-requests-month">Loading...</div>
                        <div class="stat-label">Requests This Month</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number" id="ai-cache-hits">Loading...</div>
                        <div class="stat-label">Cache Hit Rate</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number" id="ai-avg-confidence">Loading...</div>
                        <div class="stat-label">Avg. Confidence</div>
                    </div>
                </div>

                <div class="usage-chart">
                    <h3>AI Usage Over Time</h3>
                    <canvas id="ai-usage-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize AI management
    yadoreInitializeAiManagement();

    // Load AI statistics
    yadoreLoadAiStats();
});

function yadoreInitializeAiManagement() {
    const $ = jQuery;

    // Tab switching
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');

        $('.tab-button').removeClass('active');
        $(this).addClass('active');

        $('.tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // AI testing
    $('#run-ai-test').on('click', yadoreRunAiTest);
    $('#run-batch-test').on('click', yadoreRunBatchTest);

    console.log('Yadore AI Management v2.9.21 - Initialized');
}

function yadoreLoadAiStats() {
    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_get_ai_stats',
        nonce: yadore_admin.nonce
    }, function(response) {
        if (response.success) {
            const data = response.data;
            jQuery('#cache-count').text(data.cache_entries + ' entries');
            jQuery('#avg-processing-time').text(data.avg_processing_time + 'ms');
            jQuery('#ai-requests-today').text(data.requests_today);
            jQuery('#ai-requests-month').text(data.requests_month);
            jQuery('#ai-cache-hits').text(data.cache_hit_rate + '%');
            jQuery('#ai-avg-confidence').text(data.avg_confidence + '%');
        }
    });
}

function yadoreRunAiTest() {
    const $ = jQuery;
    const button = $('#run-ai-test');
    const resultsDiv = $('#ai-test-results');

    const title = $('#test-content-title').val();
    const content = $('#test-content-body').val();

    if (!title || !content) {
        alert('Please enter both title and content to test.');
        return;
    }

    button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing...');
    resultsDiv.show().find('.result-content').html('<div class="analyzing"><span class="dashicons dashicons-update-alt spinning"></span> AI is analyzing the content...</div>');

    $.post(yadore_admin.ajax_url, {
        action: 'yadore_test_ai_analysis',
        nonce: yadore_admin.nonce,
        title: title,
        content: content
    }, function(response) {
        if (response.success) {
            resultsDiv.find('.result-content').html(`
                <div class="test-success">
                    <h5><span class="dashicons dashicons-yes-alt"></span> Analysis Complete</h5>
                    <div class="result-data">
                        <div class="result-row">
                            <span class="result-label">Detected Keyword:</span>
                            <span class="result-value keyword">${response.data.keyword}</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Confidence:</span>
                            <span class="result-value">${response.data.confidence}%</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Processing Time:</span>
                            <span class="result-value">${response.data.processing_time}ms</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Model Used:</span>
                            <span class="result-value">${response.data.model}</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Token Count:</span>
                            <span class="result-value">${response.data.tokens} tokens</span>
                        </div>
                    </div>
                </div>
            `);
        } else {
            resultsDiv.find('.result-content').html(`
                <div class="test-error">
                    <h5><span class="dashicons dashicons-dismiss"></span> Analysis Failed</h5>
                    <p>${response.data}</p>
                </div>
            `);
        }
    }).fail(function() {
        resultsDiv.find('.result-content').html(`
            <div class="test-error">
                <h5><span class="dashicons dashicons-dismiss"></span> Connection Error</h5>
                <p>Unable to connect to AI service. Please try again.</p>
            </div>
        `);
    }).always(function() {
        button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Analyze Content');
    });
}
</script>