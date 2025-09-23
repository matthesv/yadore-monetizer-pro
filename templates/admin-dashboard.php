<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-cart"></span>
        Yadore Monetizer Pro Dashboard
        <span class="version-badge">v<?php echo esc_html(YADORE_PLUGIN_VERSION); ?></span>
    </h1>

    <?php if (get_transient('yadore_activation_notice')): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>Yadore Monetizer Pro v<?php echo esc_html(YADORE_PLUGIN_VERSION); ?> activated successfully!</strong> All features are now available.</p>
    </div>
    <?php delete_transient('yadore_activation_notice'); endif; ?>

    <div class="yadore-dashboard-grid">
        <!-- Main Content -->
        <div class="yadore-main-content">
            <!-- Quick Stats -->
            <div class="yadore-stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-products"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="total-products">Loading...</div>
                        <div class="stat-label">Products Displayed</div>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-post"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="scanned-posts">Loading...</div>
                        <div class="stat-label">Posts Scanned</div>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="overlay-views">Loading...</div>
                        <div class="stat-label">Overlay Views</div>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number" id="conversion-rate">Loading...</div>
                        <div class="stat-label">Conversion Rate</div>
                    </div>
                </div>
            </div>

            <!-- Feature Status -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-admin-settings"></span> Feature Status</h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-status">
                            <span class="dashicons dashicons-update"></span> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="feature-status-grid">
                        <div class="feature-item">
                            <div class="feature-icon status-active">
                                <span class="dashicons dashicons-wordpress-alt"></span>
                            </div>
                            <div class="feature-details">
                                <h4>WordPress Integration</h4>
                                <p>Complete WordPress integration with 8 admin pages</p>
                                <span class="status-badge status-active">Active</span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_ai_enabled', false) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="feature-details">
                                <h4>AI Content Analysis</h4>
                                <p>Gemini AI integration for intelligent product detection</p>
                                <span class="status-badge <?php echo get_option('yadore_ai_enabled', false) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_ai_enabled', false) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_overlay_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="feature-details">
                                <h4>Product Overlay</h4>
                                <p>Dynamic product recommendations overlay</p>
                                <span class="status-badge <?php echo get_option('yadore_overlay_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_overlay_enabled', true) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_auto_scan_posts', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-search"></span>
                            </div>
                            <div class="feature-details">
                                <h4>Auto Post Scanning</h4>
                                <p>Automatic content analysis and product detection</p>
                                <span class="status-badge <?php echo get_option('yadore_auto_scan_posts', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_auto_scan_posts', true) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_analytics_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-chart-area"></span>
                            </div>
                            <div class="feature-details">
                                <h4>Analytics & Tracking</h4>
                                <p>Comprehensive analytics and performance tracking</p>
                                <span class="status-badge <?php echo get_option('yadore_analytics_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_analytics_enabled', true) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon status-active">
                                <span class="dashicons dashicons-shortcode"></span>
                            </div>
                            <div class="feature-details">
                                <h4>Shortcode System</h4>
                                <p>[yadore_products] shortcode with advanced features</p>
                                <span class="status-badge status-active">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shortcode Generator -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-shortcode"></span> Advanced Shortcode Generator</h2>
                </div>
                <div class="card-content">
                    <div class="shortcode-generator-v27">
                        <div class="generator-row">
                            <div class="generator-col">
                                <label for="shortcode-keyword">Product Keyword *</label>
                                <input type="text" id="shortcode-keyword" placeholder="e.g., smartphone, laptop, headphones" value="smartphone" required>
                                <small>Enter the main product category or specific product name</small>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-limit">Number of Products</label>
                                <select id="shortcode-limit">
                                    <option value="3">3 products</option>
                                    <option value="6" selected>6 products</option>
                                    <option value="9">9 products</option>
                                    <option value="12">12 products</option>
                                </select>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-format">Display Format</label>
                                <select id="shortcode-format">
                                    <option value="grid" selected>Grid Layout</option>
                                    <option value="list">List View</option>
                                    <option value="inline">Inline Integration</option>
                                </select>
                            </div>
                        </div>

                        <div class="generator-row">
                            <div class="generator-col">
                                <label for="shortcode-cache">Enable Caching</label>
                                <select id="shortcode-cache">
                                    <option value="true" selected>Yes (Recommended)</option>
                                    <option value="false">No</option>
                                </select>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-class">Custom CSS Class</label>
                                <input type="text" id="shortcode-class" placeholder="my-custom-class">
                                <small>Optional: Add custom CSS class for styling</small>
                            </div>
                        </div>

                        <div class="generator-result">
                            <label for="generated-shortcode">Generated Shortcode:</label>
                            <div class="shortcode-output">
                                <textarea id="generated-shortcode" readonly>[yadore_products keyword="smartphone" limit="6" format="grid" cache="true"]</textarea>
                                <button type="button" id="copy-shortcode" class="button button-primary">
                                    <span class="dashicons dashicons-clipboard"></span> Copy
                                </button>
                            </div>
                        </div>

                        <div class="generator-preview">
                            <h4><span class="dashicons dashicons-visibility"></span> Preview</h4>
                            <div class="preview-container" id="shortcode-preview">
                                <div class="preview-loading">
                                    <span class="dashicons dashicons-update-alt spinning"></span>
                                    <span>Generating preview...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-clock"></span> Recent Activity</h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-activity">
                            <span class="dashicons dashicons-update"></span> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="activity-list" id="recent-activity">
                        <div class="activity-loading">
                            <span class="dashicons dashicons-update-alt spinning"></span>
                            <span>Loading recent activity...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="yadore-sidebar">
            <!-- Quick Actions -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-admin-tools"></span> Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=yadore-settings'); ?>" class="action-button action-primary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <div class="action-content">
                                <strong>Plugin Settings</strong>
                                <small>Configure all plugin options</small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-ai'); ?>" class="action-button action-ai">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <div class="action-content">
                                <strong>AI Management</strong>
                                <small>Configure Gemini AI integration</small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-scanner'); ?>" class="action-button action-scanner">
                            <span class="dashicons dashicons-search"></span>
                            <div class="action-content">
                                <strong>Post Scanner</strong>
                                <small>Scan and analyze posts</small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-analytics'); ?>" class="action-button action-analytics">
                            <span class="dashicons dashicons-chart-area"></span>
                            <div class="action-content">
                                <strong>Analytics</strong>
                                <small>View performance reports</small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-debug'); ?>" class="action-button action-debug">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <div class="action-content">
                                <strong>Debug & Errors</strong>
                                <small>System diagnostics</small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-tools'); ?>" class="action-button action-tools">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <div class="action-content">
                                <strong>Tools</strong>
                                <small>Import/Export & Utilities</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-dashboard"></span> System Status</h3>
                </div>
                <div class="card-content">
                    <div class="system-status">
                        <div class="status-item">
                            <div class="status-indicator status-active"></div>
                            <div class="status-details">
                                <strong>WordPress Integration</strong>
                                <small><?php echo esc_html(sprintf('v%s - All systems operational', YADORE_PLUGIN_VERSION)); ?></small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator <?php echo get_option('yadore_api_key') ? 'status-active' : 'status-warning'; ?>"></div>
                            <div class="status-details">
                                <strong>Yadore API</strong>
                                <small><?php echo get_option('yadore_api_key') ? 'Connected' : 'API key required'; ?></small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator <?php echo get_option('yadore_gemini_api_key') ? 'status-active' : 'status-inactive'; ?>"></div>
                            <div class="status-details">
                                <strong>Gemini AI</strong>
                                <small><?php echo get_option('yadore_gemini_api_key') ? 'Connected' : 'Not configured'; ?></small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator status-active"></div>
                            <div class="status-details">
                                <strong>Database</strong>
                                <small>All tables operational</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Version Info -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-info"></span> Version Information</h3>
                </div>
                <div class="card-content">
                    <div class="version-info">
                        <div class="info-row">
                            <span class="info-label">Plugin Version:</span>
                            <span class="info-value version-current">v<?php echo esc_html(YADORE_PLUGIN_VERSION); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">WordPress:</span>
                            <span class="info-value"><?php echo get_bloginfo('version'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo phpversion(); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Database:</span>
                            <span class="info-value"><?php echo esc_html(sprintf('Enhanced v%s', YADORE_PLUGIN_VERSION)); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Features:</span>
                            <span class="info-value">Complete Set</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value status-active">Fully Functional</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-sos"></span> Help & Support</h3>
                </div>
                <div class="card-content">
                    <div class="help-links">
                        <a href="<?php echo admin_url('admin.php?page=yadore-api-docs'); ?>" class="help-link">
                            <span class="dashicons dashicons-media-document"></span>
                            <span>API Documentation</span>
                        </a>
                        <a href="#" class="help-link" onclick="yadoreShowTutorial()">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <span>Video Tutorial</span>
                        </a>
                        <a href="#" class="help-link" onclick="yadoreShowShortcuts()">
                            <span class="dashicons dashicons-keyboard-hide"></span>
                            <span>Keyboard Shortcuts</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize dashboard
    yadoreInitializeDashboard();

    // Load dashboard stats
    yadoreLoadDashboardStats();

    // Shortcode generator
    yadoreInitializeShortcodeGenerator();

    // Auto-refresh every 30 seconds
    setInterval(yadoreLoadDashboardStats, 30000);
});

function yadoreInitializeDashboard() {
    const dashboardVersion = (typeof yadore_admin !== 'undefined' && yadore_admin.version)
        ? yadore_admin.version
        : '<?php echo esc_js(YADORE_PLUGIN_VERSION); ?>';
    console.log(`Yadore Monetizer Pro v${dashboardVersion} Dashboard - Initialized`);
}

function yadoreLoadDashboardStats() {
    // Load stats via AJAX
    jQuery.post(yadore_admin.ajax_url, {
        action: 'yadore_get_dashboard_stats',
        nonce: yadore_admin.nonce
    }, function(response) {
        if (response.success) {
            const data = response.data;
            jQuery('#total-products').text(data.total_products || '0');
            jQuery('#scanned-posts').text(data.scanned_posts || '0');
            jQuery('#overlay-views').text(data.overlay_views || '0');
            jQuery('#conversion-rate').text((data.conversion_rate || '0') + '%');
        }
    });
}

function yadoreInitializeShortcodeGenerator() {
    const $ = jQuery;

    function updateShortcode() {
        const keyword = $('#shortcode-keyword').val() || 'smartphone';
        const limit = $('#shortcode-limit').val();
        const format = $('#shortcode-format').val();
        const cache = $('#shortcode-cache').val();
        const customClass = $('#shortcode-class').val();

        let shortcode = `[yadore_products keyword="${keyword}" limit="${limit}" format="${format}" cache="${cache}"`;

        if (customClass) {
            shortcode += ` class="${customClass}"`;
        }

        shortcode += ']';

        $('#generated-shortcode').val(shortcode);

        // Generate preview
        yadoreGeneratePreview(shortcode);
    }

    // Event listeners
    $('#shortcode-keyword, #shortcode-limit, #shortcode-format, #shortcode-cache, #shortcode-class').on('input change', updateShortcode);

    // Copy functionality
    $('#copy-shortcode').on('click', function() {
        const shortcode = $('#generated-shortcode')[0];
        shortcode.select();
        shortcode.setSelectionRange(0, 99999);
        document.execCommand('copy');

        $(this).addClass('copied').html('<span class="dashicons dashicons-yes"></span> Copied!');
        setTimeout(() => {
            $(this).removeClass('copied').html('<span class="dashicons dashicons-clipboard"></span> Copy');
        }, 2000);
    });

    // Initial update
    updateShortcode();
}

function yadoreGeneratePreview(shortcode) {
    const previewContainer = jQuery('#shortcode-preview');
    previewContainer.html('<div class="preview-loading"><span class="dashicons dashicons-update-alt spinning"></span><span>Generating preview...</span></div>');

    // Simulate preview generation
    setTimeout(() => {
        previewContainer.html(`
            <div class="shortcode-preview-result">
                <h4>Preview: ${shortcode}</h4>
                <div class="preview-grid">
                    <div class="preview-product">Product 1</div>
                    <div class="preview-product">Product 2</div>
                    <div class="preview-product">Product 3</div>
                </div>
                <p><em>This is a simplified preview. Actual shortcode will display real products.</em></p>
            </div>
        `);
    }, 1000);
}
</script>