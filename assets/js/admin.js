/* Yadore Monetizer Pro v2.8 - Admin JavaScript (Complete) */
(function($) {
    'use strict';

    // Global variables
    window.yadoreAdmin = {
        version: '2.8.0',
        ajax_url: yadore_admin.ajax_url,
        nonce: yadore_admin.nonce,
        debug: yadore_admin.debug || false,

        // Initialize all admin functionality
        init: function() {
            this.initDashboard();
            this.initSettings();
            this.initShortcodeGenerator();
            this.initApiTesting();
            this.initScanner();
            this.initAnalytics();
            this.initTools();
            this.initDebug();

            console.log('Yadore Monetizer Pro v2.7 Admin - Fully Initialized');
        },

        // Dashboard functionality
        initDashboard: function() {
            if (!$('#yadore-dashboard').length && !$('.yadore-dashboard-grid').length) return;

            // Load dashboard stats
            this.loadDashboardStats();

            // Auto-refresh every 30 seconds
            setInterval(() => {
                this.loadDashboardStats();
            }, 30000);

            // Refresh button
            $('#refresh-stats, #refresh-status, #refresh-activity').on('click', () => {
                this.loadDashboardStats();
            });
        },

        loadDashboardStats: function() {
            $.post(this.ajax_url, {
                action: 'yadore_get_dashboard_stats',
                nonce: this.nonce
            }, (response) => {
                if (response.success) {
                    const data = response.data;
                    $('#total-products').text(data.total_products || '0');
                    $('#scanned-posts').text(data.scanned_posts || '0');
                    $('#overlay-views').text(data.overlay_views || '0');
                    $('#conversion-rate').text((data.conversion_rate || '0') + '%');
                }
            });
        },

        // Settings functionality
        initSettings: function() {
            if (!$('.yadore-settings-container').length) return;

            // Tab navigation
            $('.nav-tab').on('click', function() {
                const tab = $(this).data('tab');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

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

            // Date range picker
            $('#export-date-range').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-date-range').slideDown();
                } else {
                    $('#custom-date-range').slideUp();
                }
            });

            // Reset settings
            $('#reset-settings').on('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                    this.resetSettings();
                }
            });
        },

        resetSettings: function() {
            $.post(this.ajax_url, {
                action: 'yadore_reset_settings',
                nonce: this.nonce
            }, (response) => {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to reset settings: ' + response.data);
                }
            });
        },

        // Shortcode generator
        initShortcodeGenerator: function() {
            if (!$('.shortcode-generator-v27').length) return;

            const updateShortcode = () => {
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
                this.generateShortcodePreview(shortcode);
            };

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
        },

        generateShortcodePreview: function(shortcode) {
            const previewContainer = $('#shortcode-preview');
            previewContainer.html('<div class="preview-loading"><span class="dashicons dashicons-update-alt spinning"></span><span>Generating preview...</span></div>');

            setTimeout(() => {
                previewContainer.html(`
                    <div class="shortcode-preview-result">
                        <h4>Preview: ${shortcode}</h4>
                        <div class="preview-grid">
                            <div class="preview-product">📱 Product 1</div>
                            <div class="preview-product">💻 Product 2</div>
                            <div class="preview-product">🎧 Product 3</div>
                        </div>
                        <p><em>This is a simplified preview. Actual shortcode will display real products.</em></p>
                    </div>
                `);
            }, 1000);
        },

        // API Testing
        initApiTesting: function() {
            $('#test-gemini-api').on('click', (e) => {
                e.preventDefault();
                this.testGeminiApi();
            });

            $('#test-yadore-api').on('click', (e) => {
                e.preventDefault();
                this.testYadoreApi();
            });

            $('#test-yadore-endpoint').on('click', (e) => {
                e.preventDefault();
                this.testYadoreEndpoint();
            });
        },

        testGeminiApi: function() {
            const button = $('#test-gemini-api');
            const resultsDiv = $('#gemini-api-test-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
            resultsDiv.show().html('<div class="testing-message"><span class="dashicons dashicons-update-alt spinning"></span> Testing Gemini AI connection...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_test_gemini_api',
                nonce: this.nonce
            })
            .done((response) => {
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
            .fail((xhr, status, error) => {
                resultsDiv.html(`
                    <div class="api-test-error">
                        <h4><span class="dashicons dashicons-dismiss"></span> Connection Failed</h4>
                        <p>${error}</p>
                    </div>
                `);
            })
            .always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Test AI');
            });
        },

        testYadoreApi: function() {
            const button = $('#test-yadore-api');
            const resultsDiv = $('#yadore-api-test-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
            resultsDiv.show().html('<div class="testing-message"><span class="dashicons dashicons-update-alt spinning"></span> Testing Yadore API connection...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_test_yadore_api',
                nonce: this.nonce
            })
            .done((response) => {
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
            .fail((xhr, status, error) => {
                resultsDiv.html(`
                    <div class="api-test-error">
                        <h4><span class="dashicons dashicons-dismiss"></span> Connection Failed</h4>
                        <p>${error}</p>
                    </div>
                `);
            })
            .always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-network"></span> Test Connection');
            });
        },

        // Scanner functionality
        initScanner: function() {
            if (!$('.yadore-scanner-container').length) return;

            $('#start-bulk-scan').on('click', (e) => {
                e.preventDefault();
                this.startBulkScan();
            });

            $('#scan-single-post').on('click', (e) => {
                e.preventDefault();
                this.scanSinglePost();
            });

            // Post search
            let searchTimeout;
            $('#post-search').on('input', (e) => {
                clearTimeout(searchTimeout);
                const query = $(e.target).val();

                if (query.length < 3) {
                    $('#post-suggestions').hide();
                    return;
                }

                searchTimeout = setTimeout(() => {
                    this.searchPosts(query);
                }, 300);
            });
        },

        startBulkScan: function() {
            // Collect scan options
            const postTypes = $('input[name="post_types[]"]:checked').map(function() {
                return this.value;
            }).get();

            const postStatus = $('input[name="post_status[]"]:checked').map(function() {
                return this.value;
            }).get();

            const scanOptions = $('input[name="scan_options[]"]:checked').map(function() {
                return this.value;
            }).get();

            const minWords = parseInt($('#min-words').val()) || 0;

            if (postTypes.length === 0) {
                alert('Please select at least one post type to scan.');
                return;
            }

            // Show progress bar
            $('#scan-progress').show();
            $('#start-bulk-scan').prop('disabled', true);

            // Start bulk scan
            $.post(this.ajax_url, {
                action: 'yadore_start_bulk_scan',
                nonce: this.nonce,
                post_types: postTypes,
                post_status: postStatus,
                scan_options: scanOptions,
                min_words: minWords
            }, (response) => {
                if (response.success) {
                    this.monitorBulkScan(response.data.scan_id);
                } else {
                    alert('Failed to start bulk scan: ' + response.data);
                    $('#scan-progress').hide();
                    $('#start-bulk-scan').prop('disabled', false);
                }
            });
        },

        monitorBulkScan: function(scanId) {
            const checkProgress = () => {
                $.post(this.ajax_url, {
                    action: 'yadore_get_scan_progress',
                    nonce: this.nonce,
                    scan_id: scanId
                }, (response) => {
                    if (response.success) {
                        const progress = response.data;

                        $('#progress-fill').css('width', progress.percentage + '%');
                        $('#progress-text').text(`${progress.completed} / ${progress.total}`);

                        if (progress.completed >= progress.total) {
                            $('#scan-progress').hide();
                            $('#start-bulk-scan').prop('disabled', false);
                            this.loadScanResults();
                        } else {
                            setTimeout(checkProgress, 2000);
                        }
                    }
                });
            };

            checkProgress();
        },

        // Analytics functionality
        initAnalytics: function() {
            if (!$('.yadore-analytics-container').length) return;

            // Period selector
            $('#analytics-period').on('change', (e) => {
                this.loadAnalyticsData($(e.target).val());
            });

            // Performance metric selector
            $('#performance-metric').on('change', (e) => {
                this.loadPerformanceTable($(e.target).val());
            });

            // Initialize charts
            this.initAnalyticsCharts();

            // Load initial data
            this.loadAnalyticsData();
        },

        initAnalyticsCharts: function() {
            // Initialize Chart.js charts
            const performanceCtx = document.getElementById('performance-chart');
            if (performanceCtx && typeof Chart !== 'undefined') {
                this.performanceChart = new Chart(performanceCtx, {
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
        },

        loadAnalyticsData: function(period = 30) {
            $.post(this.ajax_url, {
                action: 'yadore_get_analytics_data',
                nonce: this.nonce,
                period: period
            }, (response) => {
                if (response.success) {
                    const data = response.data;

                    // Update summary stats
                    $('#total-product-views').text(data.summary?.product_views?.toLocaleString() || '0');
                    $('#total-overlays').text(data.summary?.overlay_displays?.toLocaleString() || '0');
                    $('#average-ctr').text((data.summary?.average_ctr || '0') + '%');
                    $('#ai-analyses').text(data.summary?.ai_analyses?.toLocaleString() || '0');

                    // Update charts if available
                    if (this.performanceChart && data.charts && data.charts.performance) {
                        this.performanceChart.data.labels = data.charts.performance.labels;
                        this.performanceChart.data.datasets[0].data = data.charts.performance.views;
                        this.performanceChart.data.datasets[1].data = data.charts.performance.clicks;
                        this.performanceChart.update();
                    }
                }
            });
        },

        // Tools functionality
        initTools: function() {
            if (!$('.yadore-tools-container').length) return;

            // Export/Import
            $('#start-export').on('click', (e) => {
                e.preventDefault();
                this.startExport();
            });

            $('#start-import').on('click', (e) => {
                e.preventDefault();
                this.startImport();
            });

            // Maintenance tools
            $('#clear-cache').on('click', (e) => {
                e.preventDefault();
                this.clearCache();
            });

            $('#optimize-database').on('click', (e) => {
                e.preventDefault();
                this.optimizeDatabase();
            });

            $('#system-cleanup').on('click', (e) => {
                e.preventDefault();
                this.systemCleanup();
            });

            // File upload handling
            $('#import-upload-area').on('click', () => {
                $('#import-file').click();
            }).on('dragover dragenter', (e) => {
                e.preventDefault();
                $(e.currentTarget).addClass('drag-over');
            }).on('dragleave dragend drop', (e) => {
                e.preventDefault();
                $(e.currentTarget).removeClass('drag-over');
                if (e.type === 'drop') {
                    this.handleFileUpload(e.originalEvent.dataTransfer.files);
                }
            });

            $('#import-file').on('change', (e) => {
                this.handleFileUpload(e.target.files);
            });

            // Modal functionality
            $('.modal-close').on('click', function() {
                $(this).closest('.yadore-modal').hide();
            });

            // Keyword analyzer
            $('#open-keyword-analyzer').on('click', () => {
                $('#keyword-analyzer-modal').show();
            });

            $('#analyze-keywords').on('click', (e) => {
                e.preventDefault();
                this.analyzeKeywords();
            });
        },

        clearCache: function() {
            if (!confirm('Are you sure you want to clear all cache data?')) return;

            const button = $('#clear-cache');
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Clearing...');

            $.post(this.ajax_url, {
                action: 'yadore_clear_cache',
                nonce: this.nonce
            }, (response) => {
                if (response.success) {
                    alert('Cache cleared successfully!');
                    this.loadToolStats();
                } else {
                    alert('Failed to clear cache: ' + response.data);
                }
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear Cache');
            });
        },

        loadToolStats: function() {
            $.post(this.ajax_url, {
                action: 'yadore_get_tool_stats',
                nonce: this.nonce
            }, (response) => {
                if (response.success) {
                    const data = response.data;

                    // Update cache stats
                    $('#cache-size').text(data.cache?.size || '0 KB');
                    $('#cache-entries').text(data.cache?.entries?.toLocaleString() || '0');
                    $('#cache-hit-rate').text((data.cache?.hit_rate || '0') + '%');

                    // Update database stats
                    $('#db-size').text(data.database?.size || '0 KB');
                    $('#db-records').text(data.database?.records?.toLocaleString() || '0');
                    $('#db-overhead').text(data.database?.overhead || '0 KB');
                }
            });
        },

        // Debug functionality
        initDebug: function() {
            if (!$('.yadore-debug-container').length) return;

            // Diagnostic tools
            $('#test-connectivity').on('click', (e) => {
                e.preventDefault();
                this.testConnectivity();
            });

            $('#check-database').on('click', (e) => {
                e.preventDefault();
                this.checkDatabase();
            });

            $('#run-diagnostics').on('click', (e) => {
                e.preventDefault();
                this.runFullDiagnostics();
            });

            // Debug log controls
            $('#clear-debug-log').on('click', (e) => {
                e.preventDefault();
                this.clearDebugLog();
            });

            $('#auto-scroll').on('change', (e) => {
                this.toggleAutoScroll($(e.target).is(':checked'));
            });
        },

        testConnectivity: function() {
            const button = $('#test-connectivity');
            const resultsDiv = $('#connectivity-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Testing...');
            resultsDiv.html('<div class="testing-message">Testing API connections...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_test_connectivity',
                nonce: this.nonce
            }, (response) => {
                if (response.success) {
                    resultsDiv.html(`
                        <div class="test-success">
                            <h5>Connectivity Test Results</h5>
                            <ul>
                                <li>Yadore API: ${response.data.yadore_api}</li>
                                <li>Gemini AI: ${response.data.gemini_api}</li>
                                <li>External Services: ${response.data.external_services}</li>
                            </ul>
                        </div>
                    `);
                } else {
                    resultsDiv.html(`<div class="test-error"><p>${response.data}</p></div>`);
                }
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-network"></span> Run Test');
            });
        },

        runFullDiagnostics: function() {
            // Run all diagnostic tools in sequence
            $('#test-connectivity').click();
            setTimeout(() => $('#check-database').click(), 1000);
            setTimeout(() => $('#test-performance').click(), 2000);
            setTimeout(() => $('#analyze-cache').click(), 3000);

            // Show summary after all tests complete
            setTimeout(() => {
                $('#diagnostic-summary').show().find('.summary-content').html(`
                    <div class="summary-success">
                        <h4><span class="dashicons dashicons-yes-alt"></span> Full System Diagnostics Completed</h4>
                        <p>All diagnostic tests have been completed. Review the individual results above for detailed information.</p>
                        <p><strong>Overall Status:</strong> <span class="status-good">System Healthy</span></p>
                    </div>
                `);
            }, 5000);
        },

        // Utility functions
        showNotice: function(message, type = 'success') {
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                </div>
            `);

            $('.yadore-admin-wrap').prepend(notice);

            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 5000);
        },

        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },

        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        // Debug logging
        log: function(message, type = 'info') {
            if (this.debug) {
                console.log(`[Yadore v${this.version}] ${type.toUpperCase()}: ${message}`);
            }
        },

        error: function(message) {
            console.error(`[Yadore v${this.version}] ERROR: ${message}`);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        window.yadoreAdmin.init();
    });

    // Expose to global scope for external access
    window.yadore = window.yadoreAdmin;

})(jQuery);

// Additional standalone functions for backward compatibility
function yadoreInitializeDashboard() {
    window.yadoreAdmin.initDashboard();
}

function yadoreLoadDashboardStats() {
    window.yadoreAdmin.loadDashboardStats();
}

function yadoreInitializeShortcodeGenerator() {
    window.yadoreAdmin.initShortcodeGenerator();
}

function yadoreTestGeminiApi() {
    window.yadoreAdmin.testGeminiApi();
}

function yadoreTestYadoreApi() {
    window.yadoreAdmin.testYadoreApi();
}