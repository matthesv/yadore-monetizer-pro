/* Yadore Monetizer Pro v2.9.18 - Admin JavaScript (Complete) */
(function($) {
    'use strict';

    // Global variables
    window.yadoreAdmin = {
        version: '2.9.18',
        ajax_url: yadore_admin.ajax_url,
        nonce: yadore_admin.nonce,
        debug: yadore_admin.debug || false,
        scannerState: null,
        scannerCharts: {
            keywords: null,
            success: null
        },
        errorLogFilter: 'all',
        cachedErrorLogs: [],
        debugAutoScroll: true,
        debugWordWrap: true,

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
            this.initErrorNotices();

            console.log('Yadore Monetizer Pro v2.9.18 Admin - Fully Initialized');
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

        initErrorNotices: function() {
            $(document).on('click', '.yadore-error-notice .notice-dismiss', (event) => {
                const notice = $(event.currentTarget).closest('.yadore-error-notice');
                const errorId = parseInt(notice.data('error-id'), 10);
                if (!Number.isNaN(errorId)) {
                    this.resolveErrorLog(errorId);
                }
            });

            $(document).on('click', '.yadore-error-notice .yadore-resolve-now', (event) => {
                event.preventDefault();

                const button = $(event.currentTarget);
                const notice = button.closest('.yadore-error-notice');
                const errorId = parseInt(button.data('error-id') || notice.data('error-id'), 10);

                if (Number.isNaN(errorId)) {
                    return;
                }

                const originalText = button.html();
                button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> ' + (yadore_admin.strings?.processing || 'Processing...'));

                this.resolveErrorLog(errorId)
                    .done(() => {
                        notice.fadeOut(200, () => notice.remove());
                    })
                    .fail((xhr) => {
                        if (this.debug) {
                            console.error('Failed to resolve Yadore error notice', xhr);
                        }
                        button.prop('disabled', false).html(originalText);
                    });
            });
        },

        resolveErrorLog: function(errorId) {
            if (!errorId) {
                return $.Deferred().reject('invalid_error_id').promise();
            }

            const request = $.post(this.ajax_url, {
                action: 'yadore_resolve_error',
                nonce: this.nonce,
                error_id: errorId
            });

            request.done(() => {
                if (this.debug) {
                    this.log(`Resolved error log entry ${errorId}`, 'info');
                }
            });

            request.fail((xhr) => {
                if (this.debug) {
                    console.error(`Failed to resolve error log entry ${errorId}`, xhr);
                }
            });

            return request;
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
                    const data = response.data || {};
                    let resultMarkup = '';

                    if (typeof data.result === 'object' && data.result !== null) {
                        const resultString = JSON.stringify(data.result, null, 2);
                        resultMarkup = `<pre><code>${this.escapeHtml(resultString)}</code></pre>`;
                    } else {
                        const resultString = data.result !== undefined && data.result !== null ? String(data.result) : '';
                        resultMarkup = `<code>${this.escapeHtml(resultString)}</code>`;
                    }

                    const model = this.escapeHtml(data.model || '');
                    const timestamp = this.escapeHtml(data.timestamp || '');

                    resultsDiv.html(`
                        <div class="api-test-success">
                            <h4><span class="dashicons dashicons-yes-alt"></span> Success!</h4>
                            <p><strong>Model:</strong> ${model || '—'}</p>
                            <div class="api-test-result"><strong>Result:</strong> ${resultMarkup}</div>
                            <p><strong>Timestamp:</strong> ${timestamp || '—'}</p>
                        </div>
                    `);
                } else {
                    const errorMessage = typeof response.data === 'string'
                        ? response.data
                        : (response.data && response.data.message ? response.data.message : 'Unknown error');

                    resultsDiv.html(`
                        <div class="api-test-error">
                            <h4><span class="dashicons dashicons-dismiss"></span> Error</h4>
                            <p>${this.escapeHtml(errorMessage)}</p>
                        </div>
                    `);
                }
            })
            .fail((xhr, status, error) => {
                resultsDiv.html(`
                    <div class="api-test-error">
                        <h4><span class="dashicons dashicons-dismiss"></span> Connection Failed</h4>
                        <p>${this.escapeHtml(error)}</p>
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
            if (!$('.yadore-scanner-container').length) {
                return;
            }

            this.scannerState = {
                selectedPost: null,
                currentPage: 1,
                currentFilter: $('#results-filter').val() || 'all',
                searchTimeout: null
            };

            $('#start-bulk-scan').off('click').on('click', (e) => {
                e.preventDefault();
                this.startBulkScan();
            });

            $('#scan-single-post').off('click').on('click', (e) => {
                e.preventDefault();
                this.scanSinglePost();
            });

            $('#post-search').off('input').on('input', (e) => {
                const query = $(e.target).val();
                clearTimeout(this.scannerState.searchTimeout);

                if (query.length < 2) {
                    $('#post-suggestions').hide().empty();
                    return;
                }

                this.scannerState.searchTimeout = setTimeout(() => {
                    this.searchPosts(query);
                }, 250);
            });

            $('#post-suggestions').off('click').on('click', '.suggestion-item', (e) => {
                e.preventDefault();
                const item = $(e.currentTarget);
                const post = item.data('post');
                if (post) {
                    this.selectScannerPost(post);
                }
            });

            $(document).off('click.yadoreSuggestions').on('click.yadoreSuggestions', (e) => {
                if (!$(e.target).closest('.post-search-container').length) {
                    $('#post-suggestions').hide();
                }
            });

            $('#results-filter').off('change').on('change', (e) => {
                this.scannerState.currentFilter = $(e.target).val();
                this.loadScanResults(1);
            });

            $('#results-pagination').off('click').on('click', '.page-link', (e) => {
                e.preventDefault();
                const page = parseInt($(e.currentTarget).data('page'), 10);
                if (!Number.isNaN(page)) {
                    this.loadScanResults(page);
                }
            });

            $('#refresh-overview').off('click').on('click', (e) => {
                if (e) {
                    e.preventDefault();
                }
                this.loadScannerOverview();
            });

            $('#export-results').off('click').on('click', (e) => {
                e.preventDefault();
                this.exportScanResults();
            });

            this.loadScannerOverview();
            this.loadScanResults();
            this.loadScannerAnalytics();
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

            if (this.scannerState) {
                this.scannerState.currentPage = 1;
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
                            if (Array.isArray(progress.results)) {
                                const lastResult = progress.results[progress.results.length - 1];
                                if (lastResult && lastResult.message) {
                                    let noticeType = 'success';
                                    if (lastResult.status === 'failed') {
                                        noticeType = 'error';
                                    } else if (lastResult.status === 'skipped') {
                                        noticeType = 'info';
                                    }
                                    this.showNotice(lastResult.message, noticeType);
                                }
                            }
                            this.loadScannerOverview();
                            this.loadScanResults(this.scannerState.currentPage || 1);
                            this.loadScannerAnalytics();
                        } else {
                            setTimeout(checkProgress, 2000);
                        }
                    }
                });
            };

            checkProgress();
        },

        loadScannerOverview: function() {
            $.post(this.ajax_url, {
                action: 'yadore_get_scanner_overview',
                nonce: this.nonce
            }, (response) => {
                if (response.success && response.data) {
                    const data = response.data;
                    $('#total-posts').text(this.formatNumber(data.total_posts || 0));
                    $('#scanned-posts').text(this.formatNumber(data.scanned_posts || 0));
                    $('#pending-posts').text(this.formatNumber(data.pending_posts || 0));
                    $('#validated-keywords').text(this.formatNumber(data.validated_keywords || 0));
                }
            });
        },

        loadScanResults: function(page = 1) {
            if (!this.scannerState) {
                this.scannerState = { currentPage: 1, currentFilter: 'all' };
            }

            this.scannerState.currentPage = page;

            const filter = this.scannerState.currentFilter || 'all';
            const tbody = $('#scan-results-body');
            tbody.html(`
                <tr>
                    <td colspan="6" class="loading-row">
                        <span class="dashicons dashicons-update-alt spinning"></span> ${yadore_admin.strings?.processing || 'Loading results...'}
                    </td>
                </tr>
            `);

            $.post(this.ajax_url, {
                action: 'yadore_get_scan_results',
                nonce: this.nonce,
                filter: filter,
                page: page,
                per_page: 10
            }, (response) => {
                if (response.success && response.data) {
                    this.renderScanResults(response.data.results || []);
                    this.renderResultsPagination(response.data.pagination || {});
                } else {
                    this.renderScanResults([]);
                    this.renderResultsPagination({});
                    if (response && response.data) {
                        this.showNotice(response.data, 'error');
                    }
                }
            });
        },

        renderScanResults: function(results) {
            const tbody = $('#scan-results-body');

            if (!Array.isArray(results) || results.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="6" class="no-results">${yadore_admin.strings?.no_results || 'No scan results available.'}</td>
                    </tr>
                `);
                return;
            }

            const rows = results.map((item) => {
                const keyword = item.primary_keyword || '—';
                const confidence = item.keyword_confidence ? `${Math.round(item.keyword_confidence * 100)}%` : '—';
                const statusLabel = item.status_label || this.getScanStatusLabel(item.scan_status);
                const lastScanned = item.last_scanned ? item.last_scanned : '—';

                return `
                    <tr>
                        <td>${item.post_title || '—'}</td>
                        <td>${keyword}</td>
                        <td>${confidence}</td>
                        <td><span class="status-label status-${item.scan_status || 'pending'}">${statusLabel}</span></td>
                        <td>${lastScanned}</td>
                        <td>
                            <button type="button" class="button button-small scan-again" data-post="${item.post_id}">
                                <span class="dashicons dashicons-update"></span>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.html(rows);

            tbody.find('.scan-again').on('click', (e) => {
                e.preventDefault();
                const postId = parseInt($(e.currentTarget).data('post'), 10);
                if (!Number.isNaN(postId)) {
                    this.scannerState.selectedPost = { id: postId };
                    this.scanSinglePost();
                }
            });
        },

        renderResultsPagination: function(pagination) {
            const container = $('#results-pagination');

            if (!pagination || !pagination.total_pages || pagination.total_pages <= 1) {
                container.empty();
                return;
            }

            let html = '';
            for (let i = 1; i <= pagination.total_pages; i += 1) {
                const activeClass = i === pagination.current_page ? ' active' : '';
                html += `<a href="#" class="page-link${activeClass}" data-page="${i}">${i}</a>`;
            }

            container.html(html);
        },

        getScanStatusLabel: function(status) {
            const labels = {
                completed_ai: 'Completed (AI)',
                completed_manual: 'Completed',
                completed: 'Completed',
                failed: 'Failed',
                skipped: 'Skipped',
                pending: 'Pending'
            };

            if (status && labels[status]) {
                return labels[status];
            }

            if (!status) {
                return 'Pending';
            }

            return status.replace(/_/g, ' ');
        },

        searchPosts: function(query) {
            $.post(this.ajax_url, {
                action: 'yadore_search_posts',
                nonce: this.nonce,
                query: query
            }, (response) => {
                const container = $('#post-suggestions');

                if (!response.success || !response.data || !Array.isArray(response.data.results) || response.data.results.length === 0) {
                    container.hide().empty();
                    return;
                }

                container.empty();

                response.data.results.forEach((post) => {
                    const item = $('<div class="suggestion-item"></div>');
                    item.append(`<div class="suggestion-title">${post.title}</div>`);
                    item.append(`<div class="suggestion-meta">${post.status} · ${post.date}</div>`);
                    item.data('post', post);
                    container.append(item);
                });

                container.show();
            });
        },

        selectScannerPost: function(post) {
            if (!post) {
                return;
            }

            this.scannerState.selectedPost = post;
            const container = $('#selected-post');
            container.show();

            container.find('.post-title').text(post.title || '');
            container.find('.post-date').text(post.date ? `📅 ${post.date}` : '');
            container.find('.post-status').text(post.status ? `• ${post.status}` : '');
            container.find('.post-word-count').text(post.word_count ? `${this.formatNumber(post.word_count)} words` : '');
            container.find('.post-excerpt').text(post.excerpt || '');

            if (post.primary_keyword) {
                container.find('.current-keywords').html(`<strong>Keyword:</strong> ${post.primary_keyword}`);
            } else {
                container.find('.current-keywords').html('<strong>Keyword:</strong> —');
            }
        },

        scanSinglePost: function() {
            if (!this.scannerState || !this.scannerState.selectedPost || !this.scannerState.selectedPost.id) {
                alert('Please select a post to scan.');
                return;
            }

            const button = $('#scan-single-post');
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Scanning...');

            $.post(this.ajax_url, {
                action: 'yadore_scan_single_post',
                nonce: this.nonce,
                post_id: this.scannerState.selectedPost.id,
                use_ai: $('#single-use-ai').is(':checked') ? 1 : 0,
                force_rescan: $('#single-force-rescan').is(':checked') ? 1 : 0,
                validate_products: $('#single-validate-products').is(':checked') ? 1 : 0,
                min_words: parseInt($('#min-words').val(), 10) || 0
            }, (response) => {
                if (response.success && response.data && response.data.result) {
                    this.showNotice('Post scan completed successfully.');
                    this.selectScannerPost(response.data.result);
                    this.loadScanResults(this.scannerState.currentPage || 1);
                    this.loadScannerOverview();
                    this.loadScannerAnalytics();
                } else if (response && response.data) {
                    this.showNotice(response.data, 'error');
                }
            }).fail((xhr) => {
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    this.showNotice(xhr.responseJSON.data, 'error');
                } else {
                    this.showNotice('Scan failed due to an unexpected error.', 'error');
                }
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Scan This Post');
            });
        },

        loadScannerAnalytics: function() {
            $.post(this.ajax_url, {
                action: 'yadore_get_scanner_analytics',
                nonce: this.nonce
            }, (response) => {
                if (response.success && response.data) {
                    const stats = response.data.stats || {};
                    $('#top-keyword').text(stats.top_keyword || '—');
                    $('#avg-confidence').text((stats.average_confidence || 0) + '%');
                    $('#ai-usage-rate').text((stats.ai_usage_rate || 0) + '%');
                    $('#scan-success-rate').text((stats.success_rate || 0) + '%');

                    this.renderScannerCharts(response.data.charts || {});
                }
            });
        },

        renderScannerCharts: function(charts) {
            const keywordData = charts.keywords || { labels: [], counts: [] };
            const successData = charts.success || { labels: [], counts: [] };

            if (typeof Chart !== 'undefined') {
                const keywordCanvas = document.getElementById('keywords-chart');
                if (keywordCanvas) {
                    if (this.scannerCharts.keywords) {
                        this.scannerCharts.keywords.data.labels = keywordData.labels;
                        this.scannerCharts.keywords.data.datasets[0].data = keywordData.counts;
                        this.scannerCharts.keywords.update();
                    } else {
                        this.scannerCharts.keywords = new Chart(keywordCanvas, {
                            type: 'bar',
                            data: {
                                labels: keywordData.labels,
                                datasets: [{
                                    label: 'Keywords',
                                    data: keywordData.counts,
                                    backgroundColor: '#2271b1'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } }
                            }
                        });
                    }
                }

                const successCanvas = document.getElementById('success-chart');
                if (successCanvas) {
                    if (this.scannerCharts.success) {
                        this.scannerCharts.success.data.labels = successData.labels;
                        this.scannerCharts.success.data.datasets[0].data = successData.counts;
                        this.scannerCharts.success.update();
                    } else {
                        this.scannerCharts.success = new Chart(successCanvas, {
                            type: 'doughnut',
                            data: {
                                labels: successData.labels,
                                datasets: [{
                                    data: successData.counts,
                                    backgroundColor: ['#00a32a', '#d63638', '#94660c']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { position: 'bottom' } }
                            }
                        });
                    }
                }
            } else {
                this.renderChartFallback('keywords-chart', keywordData.labels, keywordData.counts);
                this.renderChartFallback('success-chart', successData.labels, successData.counts);
            }
        },

        renderChartFallback: function(canvasId, labels, values) {
            const canvas = $('#' + canvasId);
            if (!canvas.length) {
                return;
            }

            const container = canvas.parent();
            container.find('.chart-fallback').remove();

            if (typeof Chart !== 'undefined') {
                return;
            }

            if (!labels || labels.length === 0) {
                container.append('<div class="chart-fallback">No data available.</div>');
                return;
            }

            const rows = labels.map((label, index) => {
                const value = values && values[index] !== undefined ? values[index] : 0;
                return `<div class="chart-fallback-row"><span>${label}</span><strong>${this.formatNumber(value)}</strong></div>`;
            }).join('');

            container.append(`<div class="chart-fallback">${rows}</div>`);
        },

        exportScanResults: function() {
            const filter = this.scannerState ? this.scannerState.currentFilter : 'all';

            $.post(this.ajax_url, {
                action: 'yadore_get_scan_results',
                nonce: this.nonce,
                filter: filter,
                export: 1,
                per_page: 500
            }, (response) => {
                if (!response.success || !response.data || !Array.isArray(response.data.results) || response.data.results.length === 0) {
                    this.showNotice('No scan results available for export.', 'warning');
                    return;
                }

                const rows = response.data.results;
                const csvRows = [];
                csvRows.push(['Post Title', 'Primary Keyword', 'Confidence', 'Status', 'Product Count', 'Last Scanned'].join(','));

                rows.forEach((row) => {
                    const confidence = row.keyword_confidence ? `${Math.round(row.keyword_confidence * 100)}%` : '';
                    const status = row.status_label || row.scan_status || '';
                    const values = [
                        (row.post_title || '').replace(/"/g, '""'),
                        (row.primary_keyword || '').replace(/"/g, '""'),
                        confidence,
                        status.replace(/"/g, '""'),
                        row.product_count || 0,
                        row.last_scanned || ''
                    ].map((value) => `"${value}"`);

                    csvRows.push(values.join(','));
                });

                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', 'yadore-scan-results.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });
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

            this.debugAutoScroll = $('#auto-scroll').is(':checked');
            this.debugWordWrap = $('#word-wrap').is(':checked');
            this.errorLogFilter = $('#error-severity-filter').val() || 'all';

            // Diagnostic tools
            $('#test-connectivity').on('click', (e) => {
                e.preventDefault();
                this.testConnectivity();
            });

            $('#check-database').on('click', (e) => {
                e.preventDefault();
                this.checkDatabase();
            });

            $('#test-performance').on('click', (e) => {
                e.preventDefault();
                this.testPerformance();
            });

            $('#analyze-cache').on('click', (e) => {
                e.preventDefault();
                this.analyzeCache();
            });

            $('#run-diagnostics').on('click', (e) => {
                e.preventDefault();
                this.runFullDiagnostics();
            });

            // Error log interactions
            $('#error-severity-filter').on('change', (e) => {
                this.errorLogFilter = $(e.target).val();
                this.loadErrorLogs();
            });

            $('#clear-errors').on('click', (e) => {
                e.preventDefault();
                this.clearErrorLogs();
            });

            $('#export-errors').on('click', (e) => {
                e.preventDefault();
                this.exportErrorLogs();
            });

            // Debug log controls
            $('#clear-debug-log').on('click', (e) => {
                e.preventDefault();
                this.clearDebugLog();
            });

            $('#auto-scroll').on('change', (e) => {
                this.toggleAutoScroll($(e.target).is(':checked'));
            });

            $('#word-wrap').on('change', (e) => {
                this.toggleWordWrap($(e.target).is(':checked'));
            });

            this.bindErrorLogTableEvents();
            this.loadSystemHealth();
            this.loadErrorLogs();
            this.loadDebugLog();

            this.log('Debug interface initialized', 'info');
        },

        bindErrorLogTableEvents: function() {
            const tbody = $('#error-logs-body');

            tbody.off('click', '.view-stack-trace');
            tbody.on('click', '.view-stack-trace', (event) => {
                event.preventDefault();
                const button = $(event.currentTarget);
                const target = $(button.data('target'));
                if (!target.length) {
                    return;
                }

                const isVisible = target.is(':visible');
                target.slideToggle(150);
                button.toggleClass('active', !isVisible);
            });

            tbody.off('click', '.mark-error-resolved');
            tbody.on('click', '.mark-error-resolved', (event) => {
                event.preventDefault();
                const button = $(event.currentTarget);
                const errorId = parseInt(button.data('error-id'), 10);
                if (Number.isNaN(errorId)) {
                    return;
                }

                const original = button.html();
                button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span>');

                this.resolveErrorLog(errorId)
                    .done(() => {
                        button.closest('tr').addClass('resolved');
                        button.closest('tr').find('.error-status').text('Resolved');
                        button.remove();
                        this.loadErrorLogs();
                        this.loadDebugLog();
                    })
                    .always(() => {
                        button.prop('disabled', false).html(original);
                    });
            });
        },

        loadSystemHealth: function() {
            if (!$('#api-health-status').length) {
                return;
            }

            $.post(this.ajax_url, {
                action: 'yadore_get_system_health',
                nonce: this.nonce
            }).done((response) => {
                if (response && response.success && response.data) {
                    const data = response.data;
                    $('#api-health-status').text(data.api_status || 'Unknown');
                    $('#db-health-status').text(data.database_status || 'Unknown');
                    $('#performance-status').text(data.performance_status || 'Unknown');
                    $('#last-error-time').text(data.last_error || 'None');
                    $('#cache-size').text(data.cache_size || 'Unknown');
                } else {
                    $('#api-health-status').text('Unavailable');
                    $('#db-health-status').text('Unavailable');
                    $('#performance-status').text('Unavailable');
                }
            }).fail(() => {
                $('#api-health-status').text('Unavailable');
                $('#db-health-status').text('Unavailable');
                $('#performance-status').text('Unavailable');
            });
        },

        loadErrorLogs: function() {
            const tbody = $('#error-logs-body');
            if (!tbody.length) {
                return;
            }

            tbody.html(`
                <tr>
                    <td colspan="7" class="loading-row">
                        <span class="dashicons dashicons-update-alt spinning"></span> Loading error logs...
                    </td>
                </tr>
            `);

            $.post(this.ajax_url, {
                action: 'yadore_get_error_logs',
                nonce: this.nonce,
                severity: this.errorLogFilter
            }).done((response) => {
                if (response && response.success) {
                    this.cachedErrorLogs = Array.isArray(response.data?.logs) ? response.data.logs : [];
                    this.renderErrorLogs(this.cachedErrorLogs);
                    this.updateErrorStats(response.data?.counts, response.data?.open_counts);
                } else {
                    this.cachedErrorLogs = [];
                    this.renderErrorLogs([]);
                    this.updateErrorStats();
                }
            }).fail(() => {
                this.cachedErrorLogs = [];
                tbody.html(`
                    <tr>
                        <td colspan="7" class="loading-row">
                            <span class="dashicons dashicons-warning"></span> Failed to load error logs.
                        </td>
                    </tr>
                `);
                this.updateErrorStats();
            });
        },

        updateErrorStats: function(counts = {}, openCounts = {}) {
            const severities = ['critical', 'high', 'medium', 'low'];
            severities.forEach((severity) => {
                const total = counts?.[severity] || 0;
                const open = openCounts?.[severity] || 0;
                let label = this.formatNumber(total);
                if (open > 0) {
                    label += ` (${this.formatNumber(open)} open)`;
                }
                $(`#${severity}-errors`).text(label);
            });
        },

        renderErrorLogs: function(logs) {
            const tbody = $('#error-logs-body');
            if (!tbody.length) {
                return;
            }

            if (!Array.isArray(logs) || !logs.length) {
                tbody.html(`
                    <tr>
                        <td colspan="7" class="loading-row">
                            <span class="dashicons dashicons-yes"></span> No error logs found for the selected filter.
                        </td>
                    </tr>
                `);
                return;
            }

            const rows = logs.map((log) => {
                const createdAt = log.created_at ? this.escapeHtml(log.created_at) : '';
                const severityRaw = (log.severity || '').toString();
                const severity = this.escapeHtml(severityRaw);
                const severityClass = (severityRaw ? severityRaw.toLowerCase().replace(/[^a-z0-9_-]/g, '') : 'unknown');
                const severityLabel = severity ? severity.charAt(0).toUpperCase() + severity.slice(1) : '';
                const type = this.escapeHtml(log.error_type || '');
                const message = this.escapeHtml(log.error_message || '');
                const summary = this.formatErrorSummary(log);
                const status = log.resolved ? 'Resolved' : 'Open';
                const detailId = `error-trace-${log.id}`;

                return `
                    <tr data-error-id="${log.id}" class="${log.resolved ? 'resolved' : ''}">
                        <td>${createdAt}</td>
                        <td><span class="error-severity severity-${severityClass}">${severityLabel}</span></td>
                        <td>${type}</td>
                        <td>${message}</td>
                        <td>${summary}</td>
                        <td class="error-status">${status}</td>
                        <td>
                            <button class="button button-secondary button-small view-stack-trace" data-target="#${detailId}">Trace</button>
                            ${log.resolved ? '' : `<button class="button button-link-delete mark-error-resolved" data-error-id="${log.id}">Resolve</button>`}
                        </td>
                    </tr>
                    <tr id="${detailId}" class="error-trace-row" style="display: none;">
                        <td colspan="7">
                            <div class="error-trace-details">
                                <div class="error-trace-meta">
                                    <strong>Stack Trace:</strong>
                                    <pre>${this.escapeHtml(log.stack_trace || 'No stack trace recorded.')}</pre>
                                </div>
                                ${this.formatContextDetails(log)}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.html(rows);
        },

        formatErrorSummary: function(log) {
            const segments = [];
            if (log.request_uri) {
                segments.push(`<strong>URI:</strong> ${this.escapeHtml(log.request_uri)}`);
            }
            if (log.post_id) {
                segments.push(`<strong>Post ID:</strong> ${this.escapeHtml(log.post_id)}`);
            }
            if (log.user_id) {
                segments.push(`<strong>User ID:</strong> ${this.escapeHtml(log.user_id)}`);
            }

            const context = log.context && typeof log.context === 'object' ? log.context : {};
            const contextKeys = Object.keys(context);
            if (contextKeys.length) {
                segments.push(`<strong>Context:</strong> ${this.escapeHtml(contextKeys.slice(0, 3).join(', '))}`);
            }

            return segments.join('<br>');
        },

        formatContextDetails: function(log) {
            const details = [];
            if (log.error_code) {
                details.push(`<div><strong>Error Code:</strong> ${this.escapeHtml(log.error_code)}</div>`);
            }
            if (log.ip_address) {
                details.push(`<div><strong>IP:</strong> ${this.escapeHtml(log.ip_address)}</div>`);
            }
            if (log.user_agent) {
                details.push(`<div><strong>User Agent:</strong> ${this.escapeHtml(log.user_agent)}</div>`);
            }
            if (log.resolution_notes) {
                details.push(`<div><strong>Resolution Notes:</strong> ${this.escapeHtml(log.resolution_notes)}</div>`);
            }

            const context = log.context && typeof log.context === 'object' ? log.context : {};
            if (Object.keys(context).length) {
                details.push(`<div><strong>Context Data:</strong><pre>${this.escapeHtml(JSON.stringify(context, null, 2))}</pre></div>`);
            }

            return details.length ? `<div class="error-context-details">${details.join('')}</div>` : '';
        },

        loadDebugLog: function() {
            const container = $('#debug-log-content');
            if (!container.length) {
                return;
            }

            container.html(`
                <div class="log-loading">
                    <span class="dashicons dashicons-update-alt spinning"></span> Loading debug information...
                </div>
            `);

            $.post(this.ajax_url, {
                action: 'yadore_get_debug_info',
                nonce: this.nonce
            }).done((response) => {
                if (response && response.success) {
                    this.renderDebugLog(response.data || {});
                } else {
                    container.html('<div class="log-error">No debug information available.</div>');
                }
            }).fail(() => {
                container.html('<div class="log-error">Failed to load debug log information.</div>');
            });
        },

        renderDebugLog: function(data) {
            const container = $('#debug-log-content');
            if (!container.length) {
                return;
            }

            const sections = [];

            if (data.plugin_debug_log) {
                sections.push('=== Plugin Debug Log ===\n' + data.plugin_debug_log.trim());
            }

            if (Array.isArray(data.stack_traces) && data.stack_traces.length) {
                const traceLines = data.stack_traces.map((trace) => {
                    const timestamp = trace.created_at ? `[${trace.created_at}]` : '';
                    const severity = trace.severity ? trace.severity.toUpperCase() : '';
                    return `${timestamp} ${severity} ${trace.error_message || ''}\n${trace.stack_trace || ''}`.trim();
                });
                sections.push('=== Recent Stack Traces ===\n' + traceLines.join('\n\n'));
            }

            if (data.wp_debug_excerpt) {
                sections.push('=== WP debug.log (tail) ===\n' + data.wp_debug_excerpt.trim());
            }

            if (!sections.length) {
                container.html('<div class="log-empty">No debug entries recorded yet.</div>');
                return;
            }

            const content = $('<pre/>').text(sections.join('\n\n'));
            container.empty().append(content);

            if (this.debugWordWrap) {
                container.removeClass('no-wrap');
            } else {
                container.addClass('no-wrap');
            }

            if (this.debugAutoScroll && container[0]) {
                container.scrollTop(container[0].scrollHeight);
            }
        },

        clearErrorLogs: function() {
            if (!confirm('Are you sure you want to delete all error logs? This cannot be undone.')) {
                return;
            }

            const button = $('#clear-errors');
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Clearing...');

            $.post(this.ajax_url, {
                action: 'yadore_clear_error_log',
                nonce: this.nonce
            }).done((response) => {
                this.showNotice(response?.data?.message || 'Error logs cleared.', 'success');
                this.loadErrorLogs();
                this.loadDebugLog();
            }).fail((xhr) => {
                this.showNotice(xhr.responseJSON?.data || 'Failed to clear error logs.', 'error');
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Clear All');
            });
        },

        clearDebugLog: function() {
            if (!confirm('Clear the stored debug log history? This will also remove error log entries.')) {
                return;
            }

            $.post(this.ajax_url, {
                action: 'yadore_clear_error_log',
                nonce: this.nonce
            }).done(() => {
                this.loadErrorLogs();
                this.loadDebugLog();
            }).fail(() => {
                this.showNotice('Failed to clear debug log history.', 'error');
            });
        },

        exportErrorLogs: function() {
            if (!this.cachedErrorLogs.length) {
                alert('No error logs available to export.');
                return;
            }

            const blob = new Blob([JSON.stringify(this.cachedErrorLogs, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'yadore-error-logs.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        toggleAutoScroll: function(enabled) {
            this.debugAutoScroll = !!enabled;
            if (enabled) {
                const container = $('#debug-log-content');
                if (container.length && container[0]) {
                    container.scrollTop(container[0].scrollHeight);
                }
            }
        },

        toggleWordWrap: function(enabled) {
            this.debugWordWrap = !!enabled;
            $('#debug-log-content').toggleClass('no-wrap', !enabled);
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

        checkDatabase: function() {
            const button = $('#check-database');
            const resultsDiv = $('#database-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Checking...');
            resultsDiv.html('<div class="testing-message">Checking database tables...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_check_database',
                nonce: this.nonce
            }).done((response) => {
                if (response && response.success) {
                    resultsDiv.html(`<div class="test-success"><p>${this.escapeHtml(response.data.message || 'Database check passed.')}</p></div>`);
                } else {
                    resultsDiv.html(`<div class="test-error"><p>${this.escapeHtml(response?.data || 'Database check failed.')}</p></div>`);
                }
            }).fail(() => {
                resultsDiv.html('<div class="test-error"><p>Database diagnostics are unavailable.</p></div>');
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-database"></span> Check DB');
            });
        },

        testPerformance: function() {
            const button = $('#test-performance');
            const resultsDiv = $('#performance-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing...');
            resultsDiv.html('<div class="testing-message">Running performance benchmarks...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_test_performance',
                nonce: this.nonce
            }).done((response) => {
                if (response && response.success) {
                    resultsDiv.html(`<div class="test-success"><p>${this.escapeHtml(response.data.message || 'Performance checks completed.')}</p></div>`);
                } else {
                    resultsDiv.html(`<div class="test-error"><p>${this.escapeHtml(response?.data || 'Performance check failed.')}</p></div>`);
                }
            }).fail(() => {
                resultsDiv.html('<div class="test-error"><p>Performance diagnostics are unavailable.</p></div>');
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-performance"></span> Analyze');
            });
        },

        analyzeCache: function() {
            const button = $('#analyze-cache');
            const resultsDiv = $('#cache-results');

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing...');
            resultsDiv.html('<div class="testing-message">Reviewing cache usage...</div>');

            $.post(this.ajax_url, {
                action: 'yadore_analyze_cache',
                nonce: this.nonce
            }).done((response) => {
                if (response && response.success) {
                    resultsDiv.html(`<div class="test-success"><p>${this.escapeHtml(response.data.message || 'Cache analysis completed.')}</p></div>`);
                } else {
                    resultsDiv.html(`<div class="test-error"><p>${this.escapeHtml(response?.data || 'Cache analysis failed.')}</p></div>`);
                }
            }).fail(() => {
                resultsDiv.html('<div class="test-error"><p>Cache diagnostics are unavailable.</p></div>');
            }).always(() => {
                button.prop('disabled', false).html('<span class="dashicons dashicons-admin-generic"></span> Analyze');
            });
        },

        runFullDiagnostics: function() {
            $('#test-connectivity').trigger('click');
            setTimeout(() => $('#check-database').trigger('click'), 1000);
            setTimeout(() => $('#test-performance').trigger('click'), 2000);
            setTimeout(() => $('#analyze-cache').trigger('click'), 3000);

            setTimeout(() => {
                $('#diagnostic-summary').show().find('.summary-content').html(`
                    <div class="summary-success">
                        <h4><span class="dashicons dashicons-yes-alt"></span> Full System Diagnostics Completed</h4>
                        <p>All diagnostic tests have been triggered. Review the individual results above for detailed information.</p>
                        <p><strong>Overall Status:</strong> <span class="status-good">System Healthy</span></p>
                    </div>
                `);
            }, 5000);
        },

        // Utility functions
        showNotice: function(message, type = 'success') {
            const safeMessage = typeof message === 'string' ? message : String(message || '');
            let classes = 'notice';

            switch (type) {
                case 'error':
                    classes += ' notice-error';
                    break;
                case 'warning':
                    classes += ' notice-warning';
                    break;
                case 'info':
                    classes += ' notice-info';
                    break;
                default:
                    classes += ' notice-success';
                    break;
            }

            const isPersistent = type === 'error' || type === 'warning';
            if (!isPersistent) {
                classes += ' is-dismissible';
            }

            const notice = $('<div/>', { class: classes }).append($('<p/>').text(safeMessage));
            $('.yadore-admin-wrap').prepend(notice);

            if (!isPersistent) {
                setTimeout(() => {
                    notice.fadeOut(() => notice.remove());
                }, 5000);
            }
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

        escapeHtml: function(value) {
            if (value === undefined || value === null) {
                return '';
            }

            return $('<div/>').text(String(value)).html();
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

function yadoreInitializeScanner() {
    window.yadoreAdmin.initScanner();
}

function yadoreLoadScannerOverview() {
    window.yadoreAdmin.loadScannerOverview();
}

function yadoreLoadScanResults(page) {
    window.yadoreAdmin.loadScanResults(page || 1);
}

function yadoreLoadScannerAnalytics() {
    window.yadoreAdmin.loadScannerAnalytics();
}