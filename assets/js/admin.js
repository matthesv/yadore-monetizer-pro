/* Yadore Monetizer Pro v3.44 - Admin JavaScript (Complete) */
(function($) {
    'use strict';

    // Global variables
    window.yadoreAdmin = {
        version: (window.yadore_admin && window.yadore_admin.version) ? window.yadore_admin.version : '3.44',
        ajax_url: yadore_admin.ajax_url,
        nonce: yadore_admin.nonce,
        debug: yadore_admin.debug || false,
        scannerState: null,
        scannerCharts: {
            keywords: null,
            success: null
        },
        analyticsData: null,
        performanceChart: null,
        trafficChart: null,
        revenueChart: null,
        performanceTableData: null,
        keywordData: null,
        errorLogFilter: 'all',
        cachedErrorLogs: [],
        debugAutoScroll: true,
        debugWordWrap: true,
        clipboardFeedbackTimer: null,
        lastDashboardUpdate: null,
        statsTimestampInterval: null,

        getString: function(key, fallback) {
            if (!key) {
                return typeof fallback === 'string' ? fallback : '';
            }

            const strings = window.yadore_admin && window.yadore_admin.strings
                ? window.yadore_admin.strings
                : {};

            if (strings && Object.prototype.hasOwnProperty.call(strings, key)) {
                const value = strings[key];
                if (typeof value === 'string' && value.length) {
                    return value;
                }
            }

            return typeof fallback === 'string' ? fallback : '';
        },

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
            this.initStyleguide();
            this.initErrorNotices();

            console.log(`Yadore Monetizer Pro v${this.version} Admin - Fully Initialized`);
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
            this.showStatsLoading();

            $.post(this.ajax_url, {
                action: 'yadore_get_dashboard_stats',
                nonce: this.nonce
            })
                .done((response) => {
                    if (response && response.success && response.data) {
                        const data = response.data;
                        $('#total-products').text(this.formatNumber(data.total_products));
                        $('#scanned-posts').text(this.formatNumber(data.scanned_posts));
                        $('#overlay-views').text(this.formatNumber(data.overlay_views));

                        const formattedRate = this.formatRate(data.conversion_rate);
                        $('#conversion-rate').text(`${formattedRate}%`);

                        this.lastDashboardUpdate = new Date();
                        this.updateStatsTimestamp(true);

                        if (this.statsTimestampInterval) {
                            clearInterval(this.statsTimestampInterval);
                        }

                        this.statsTimestampInterval = window.setInterval(() => {
                            this.updateStatsTimestamp();
                        }, 30000);

                        this.renderRecentActivity(Array.isArray(data.activity) ? data.activity : []);
                    } else {
                        const message = (response && response.data && response.data.message)
                            ? response.data.message
                            : (yadore_admin.strings?.error || 'Fehler beim Laden der Statistiken.');

                        this.handleStatsError(message);
                    }
                })
                .fail((jqXHR) => {
                    const message = jqXHR?.responseJSON?.data?.message
                        || jqXHR?.statusText
                        || (yadore_admin.strings?.error || 'Fehler beim Laden der Statistiken.');

                    this.handleStatsError(message);
                });
        },

        formatNumber: function(value) {
            const numeric = Number(value);
            if (Number.isFinite(numeric)) {
                return numeric.toLocaleString();
            }
            return '0';
        },

        formatRate: function(value) {
            const numeric = Number.parseFloat(value);
            if (Number.isFinite(numeric)) {
                return numeric.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }
            return '0,00';
        },

        formatAbsoluteTime: function(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            try {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (error) {
                return date.toISOString();
            }
        },

        formatRelativeTime: function(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return '';
            }

            const diffSeconds = Math.round((Date.now() - date.getTime()) / 1000);
            if (!Number.isFinite(diffSeconds)) {
                return '';
            }

            if (diffSeconds < 10) {
                return yadore_admin.strings?.just_now || 'Gerade eben';
            }

            if (diffSeconds < 60) {
                const seconds = Math.max(diffSeconds, 1);
                const template = yadore_admin.strings?.relative_seconds || 'vor %s Sekunden';
                return template.replace('%s', seconds);
            }

            const diffMinutes = Math.round(diffSeconds / 60);
            if (diffMinutes < 60) {
                const minutes = Math.max(diffMinutes, 1);
                const template = yadore_admin.strings?.relative_minutes || 'vor %s Minuten';
                return template.replace('%s', minutes);
            }

            const diffHours = Math.round(diffMinutes / 60);
            if (diffHours < 24) {
                const hours = Math.max(diffHours, 1);
                const template = yadore_admin.strings?.relative_hours || 'vor %s Stunden';
                return template.replace('%s', hours);
            }

            const diffDays = Math.max(Math.round(diffHours / 24), 1);
            const template = yadore_admin.strings?.relative_days || 'vor %s Tagen';
            return template.replace('%s', diffDays);
        },

        showStatsLoading: function() {
            const $timestamp = $('#stats-last-updated');
            if (!$timestamp.length) {
                return;
            }

            const loadingLabel = yadore_admin.strings?.refreshing || 'Aktualisierung läuft...';
            $timestamp.attr('data-state', 'loading').text(loadingLabel).removeAttr('datetime');
        },

        showStatsError: function(message) {
            const $timestamp = $('#stats-last-updated');
            if (!$timestamp.length) {
                return;
            }

            const fallback = message || yadore_admin.strings?.error || 'Fehler beim Laden der Statistiken.';
            $timestamp.attr('data-state', 'error').text(fallback).removeAttr('datetime');
        },

        updateStatsTimestamp: function(force) {
            const $timestamp = $('#stats-last-updated');
            if (!$timestamp.length) {
                return;
            }

            if (!(this.lastDashboardUpdate instanceof Date) || Number.isNaN(this.lastDashboardUpdate.getTime())) {
                if (force) {
                    const fallback = yadore_admin.strings?.no_data || 'Noch keine Daten geladen';
                    $timestamp.attr('data-state', 'idle').text(fallback).removeAttr('datetime');
                }
                return;
            }

            const absolute = this.formatAbsoluteTime(this.lastDashboardUpdate);
            const relative = this.formatRelativeTime(this.lastDashboardUpdate);
            const textContent = relative ? `${absolute} · ${relative}` : absolute;

            $timestamp
                .attr('data-state', 'ready')
                .attr('datetime', this.lastDashboardUpdate.toISOString())
                .text(textContent);
        },

        handleStatsError: function(message) {
            $('#total-products').text('—');
            $('#scanned-posts').text('—');
            $('#overlay-views').text('—');
            $('#conversion-rate').text('—');

            this.lastDashboardUpdate = null;
            this.showStatsError(message);

            if (this.statsTimestampInterval) {
                clearInterval(this.statsTimestampInterval);
                this.statsTimestampInterval = null;
            }

            this.showActivityMessage(message);
        },

        showActivityMessage: function(message) {
            const $container = $('#recent-activity');
            if (!$container.length) {
                return;
            }

            const text = message || yadore_admin.strings?.activity_empty || 'Keine Aktivitäten vorhanden.';
            $container.empty().append(
                $('<div/>', { 'class': 'activity-empty' }).text(text)
            );
        },

        renderRecentActivity: function(items) {
            const $container = $('#recent-activity');
            if (!$container.length) {
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
                this.showActivityMessage();
                return;
            }

            $container.empty();

            items.forEach((item) => {
                const itemType = (typeof item.type === 'string' && item.type !== '') ? item.type : 'info';
                const classes = ['activity-item', `activity-${itemType}`];

                if (!['success', 'error', 'warning', 'info'].includes(itemType)) {
                    classes.push('activity-info');
                }

                const $entry = $('<div/>', { 'class': classes.join(' ') });

                const iconClass = (typeof item.icon === 'string' && item.icon !== '')
                    ? item.icon
                    : 'dashicons-info';

                $('<div/>', { 'class': 'activity-icon' })
                    .append($('<span/>', { 'class': `dashicons ${iconClass}` }))
                    .appendTo($entry);

                const $content = $('<div/>', { 'class': 'activity-content' }).appendTo($entry);

                if (item.title) {
                    $('<div/>', { 'class': 'activity-title' }).text(item.title).appendTo($content);
                }

                if (item.description) {
                    $('<div/>', { 'class': 'activity-description' }).text(item.description).appendTo($content);
                }

                const timeText = item.relative_time || item.time;
                if (timeText) {
                    $('<div/>', { 'class': 'activity-meta' }).text(timeText).appendTo($content);
                }

                $container.append($entry);
            });
        },

        initStyleguide: function() {
            const $page = $('.yadore-styleguide');
            if (!$page.length) {
                return;
            }

            const self = this;

            $page.find('[data-token]').each(function() {
                const tokenName = $(this).data('token');
                if (typeof tokenName !== 'string' || tokenName.trim() === '') {
                    return;
                }

                const value = window.getComputedStyle(document.documentElement)
                    .getPropertyValue(tokenName.trim());

                const display = $(this).find('[data-token-value]');
                if (display.length && value) {
                    display.text(`${tokenName.trim()} → ${value.trim()}`);
                }
            });

            $(document).on('click', '[data-yadore-copy]', function(event) {
                event.preventDefault();
                const targetId = $(this).data('yadore-copy');
                if (typeof targetId !== 'string' || targetId.trim() === '') {
                    return;
                }

                const $target = $(`#${targetId}`);
                if (!$target.length) {
                    return;
                }

                const text = $target.text().trim();
                if (!text) {
                    return;
                }

                self.copyToClipboard(text)
                    .then(() => {
                        self.showCopyFeedback(this);
                    })
                    .catch((error) => {
                        if (self.debug) {
                            console.error('Clipboard copy failed', error);
                        }
                    });
            });
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text);
            }

            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);

                textarea.select();
                textarea.setSelectionRange(0, textarea.value.length);

                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('execCommand returned false'));
                    }
                } catch (error) {
                    document.body.removeChild(textarea);
                    reject(error);
                }
            });
        },

        showCopyFeedback: function(button) {
            if (!button) {
                return;
            }

            const $button = $(button);
            const originalLabel = $button.data('original-label') || $button.text();
            if (!$button.data('original-label')) {
                $button.data('original-label', originalLabel);
            }

            const copiedLabel = yadore_admin.strings?.copied || 'Copied!';

            $button.addClass('copied').text(copiedLabel);

            if (this.clipboardFeedbackTimer) {
                clearTimeout(this.clipboardFeedbackTimer);
            }

            this.clipboardFeedbackTimer = window.setTimeout(() => {
                $button.removeClass('copied').text(originalLabel);
                this.clipboardFeedbackTimer = null;
            }, 2000);
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

            const $tabs = $('.nav-tabs .nav-tab');
            const $panels = $('.settings-panel');

            const activateTab = ($targetTab, focusPanel = false) => {
                if (!$targetTab || !$targetTab.length) {
                    return;
                }

                const tabId = $targetTab.attr('id');
                const tabKey = $targetTab.data('tab');
                if (!tabKey) {
                    return;
                }

                $tabs.removeClass('nav-tab-active')
                    .attr('aria-selected', 'false')
                    .attr('tabindex', '-1');

                $targetTab.addClass('nav-tab-active')
                    .attr('aria-selected', 'true')
                    .attr('tabindex', '0');

                const targetPanelId = `panel-${tabKey}`;

                $panels.each(function() {
                    const $panel = $(this);
                    const isActive = $panel.attr('id') === targetPanelId;
                    $panel.toggleClass('active', isActive);

                    if (isActive) {
                        $panel.removeAttr('hidden');
                        if (tabId) {
                            $panel.attr('aria-labelledby', tabId);
                        }

                        if (focusPanel) {
                            window.requestAnimationFrame(() => {
                                $panel.trigger('focus');
                            });
                        }
                    } else {
                        $panel.attr('hidden', 'hidden');
                    }
                });
            };

            const focusTab = ($tab) => {
                if ($tab && $tab.length) {
                    $tab.trigger('focus');
                }
            };

            $tabs.on('click', function(event) {
                event.preventDefault();
                const $tab = $(this);
                activateTab($tab, false);
                focusTab($tab);
            });

            $tabs.on('keydown', function(event) {
                const key = event.key;
                const currentIndex = $tabs.index(this);

                if (key === 'ArrowRight' || key === 'ArrowDown') {
                    event.preventDefault();
                    const nextIndex = (currentIndex + 1) % $tabs.length;
                    const $nextTab = $tabs.eq(nextIndex);
                    activateTab($nextTab, false);
                    focusTab($nextTab);
                    return;
                }

                if (key === 'ArrowLeft' || key === 'ArrowUp') {
                    event.preventDefault();
                    const prevIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
                    const $prevTab = $tabs.eq(prevIndex);
                    activateTab($prevTab, false);
                    focusTab($prevTab);
                    return;
                }

                if (key === 'Home') {
                    event.preventDefault();
                    const $firstTab = $tabs.first();
                    activateTab($firstTab, false);
                    focusTab($firstTab);
                    return;
                }

                if (key === 'End') {
                    event.preventDefault();
                    const $lastTab = $tabs.last();
                    activateTab($lastTab, false);
                    focusTab($lastTab);
                    return;
                }

                if (key === 'Enter' || key === ' ') {
                    event.preventDefault();
                    activateTab($(this), true);
                }
            });

            const $initialTab = $tabs.filter('.nav-tab-active').first();
            if ($initialTab.length) {
                activateTab($initialTab, false);
            } else if ($tabs.length) {
                activateTab($tabs.first(), false);
            }

            this.initPasswordVisibilityToggles();

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

            $('#reset-ai-prompt').on('click', () => {
                const $promptField = $('#yadore_ai_prompt');
                if (!$promptField.length) {
                    return;
                }

                const defaultPrompt = $promptField.data('default');
                if (typeof defaultPrompt === 'string' && defaultPrompt.length) {
                    $promptField.val(defaultPrompt).trigger('input');
                }
            });

            // Date range picker
            this.setupExportDateRangeToggle();

            // Reset settings
            $('#reset-settings').on('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                    this.resetSettings();
                }
            });

            // Color palette interactions
            const normalizeHexValue = (value) => {
                const stringValue = (value || '').toString().trim();

                if (stringValue === '') {
                    return '';
                }

                const withHash = stringValue.startsWith('#') ? stringValue : `#${stringValue}`;
                const sanitized = `#${withHash.slice(1).replace(/[^0-9A-Fa-f]/g, '')}`;

                if (/^#([0-9A-F]{3}|[0-9A-F]{6})$/i.test(sanitized)) {
                    return sanitized.toUpperCase();
                }

                return null;
            };

            const getDisplayInput = ($picker) => $picker.closest('.color-input-wrapper').find('.color-value-display');
            const getPickerInput = ($display) => $display.closest('.color-input-wrapper').find('.color-picker-input');

            $('.color-picker-input').on('input change', function() {
                const $picker = $(this);
                const normalized = normalizeHexValue($picker.val());
                const $display = getDisplayInput($picker);

                if (normalized && $picker.val() !== normalized) {
                    $picker.val(normalized);
                }

                if ($display.length) {
                    if (normalized) {
                        $display.val(normalized).removeClass('color-input-invalid');
                    } else if (normalized === '') {
                        $display.val('').removeClass('color-input-invalid');
                    }
                }
            });

            $('.color-value-display').on('focus', function() {
                $(this).select();
            });

            $('.color-value-display').on('input', function() {
                const $display = $(this);
                const value = $display.val();
                const normalized = normalizeHexValue(value);
                const $picker = getPickerInput($display);

                if (value.trim() === '') {
                    $display.removeClass('color-input-invalid');
                    return;
                }

                if (normalized) {
                    $display.removeClass('color-input-invalid');
                    if ($picker.length && $picker.val().toUpperCase() !== normalized) {
                        $picker.val(normalized).trigger('change');
                    }
                } else {
                    $display.addClass('color-input-invalid');
                }
            });

            $('.color-value-display').on('blur', function() {
                const $display = $(this);
                const normalized = normalizeHexValue($display.val());
                const $picker = getPickerInput($display);

                if (normalized) {
                    $display.val(normalized).removeClass('color-input-invalid');
                    if ($picker.length && $picker.val().toUpperCase() !== normalized) {
                        $picker.val(normalized).trigger('change');
                    }
                    return;
                }

                const fallback = normalizeHexValue($picker.val()) || '#000000';
                $display.val(fallback).removeClass('color-input-invalid');
            });

            $('.color-swatch').on('click', function() {
                const target = $(this).data('target');
                const color = normalizeHexValue($(this).data('color'));

                if (!target || !color) {
                    return;
                }

                const $input = $('#' + target);
                if ($input.length) {
                    $input.val(color).trigger('change');
                }
            });

            $('.color-picker-input').trigger('change');
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

            const $copyButton = $('#copy-shortcode');
            const $copyFeedback = $('#copy-feedback');

            const strings = {
                default: this.getString('copy_button_default', 'Copy shortcode'),
                loading: this.getString('copy_button_loading', 'Copying…'),
                success: this.getString('copy_button_success', 'Copied!'),
                error: this.getString('copy_button_error', 'Copy failed'),
                feedbackSuccess: this.getString('copy_feedback_success', 'Shortcode copied to clipboard.'),
                feedbackError: this.getString('copy_feedback_error', 'Copy failed. Press Ctrl+C to copy manually.'),
            };

            const iconStates = {
                default: 'dashicons-clipboard',
                loading: 'dashicons-update-alt',
                success: 'dashicons-yes',
                error: 'dashicons-warning',
            };

            const setCopyButtonState = (state) => {
                if (!$copyButton.length) {
                    return;
                }

                const normalizedState = iconStates[state] ? state : 'default';
                const $icon = $copyButton.find('.dashicons').first();
                const $label = $copyButton.find('.button-label').first();

                Object.values(iconStates).forEach((className) => {
                    $icon.removeClass(className);
                });

                $icon.removeClass('spinning');
                $icon.addClass(iconStates[normalizedState]);

                if (normalizedState === 'loading') {
                    $icon.addClass('spinning');
                }

                const label = strings[normalizedState] || strings.default;
                $label.text(label);

                $copyButton.attr('data-state', normalizedState);
                $copyButton.prop('disabled', normalizedState === 'loading');
            };

            const resetCopyFeedback = () => {
                if ($copyFeedback.length) {
                    $copyFeedback.text('');
                    $copyFeedback.removeAttr('data-state');
                }
            };

            const showCopyFeedback = (state, message) => {
                if (!$copyFeedback.length) {
                    return;
                }

                $copyFeedback.attr('data-state', state);
                $copyFeedback.text(message);
            };

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

                setCopyButtonState('default');
                resetCopyFeedback();

                // Generate preview
                this.generateShortcodePreview(shortcode);
            };

            $('#shortcode-keyword, #shortcode-limit, #shortcode-format, #shortcode-cache, #shortcode-class')
                .on('input change', updateShortcode);

            if ($copyButton.length) {
                $copyButton.on('click', () => {
                    const shortcode = $('#generated-shortcode').val();
                    if (!shortcode) {
                        return;
                    }

                    setCopyButtonState('loading');
                    resetCopyFeedback();

                    this.copyTextToClipboard(shortcode)
                        .then(() => {
                            setCopyButtonState('success');
                            showCopyFeedback('success', strings.feedbackSuccess);

                            window.clearTimeout(this.clipboardFeedbackTimer);
                            this.clipboardFeedbackTimer = window.setTimeout(() => {
                                setCopyButtonState('default');
                                resetCopyFeedback();
                            }, 2500);
                        })
                        .catch(() => {
                            setCopyButtonState('error');
                            showCopyFeedback('error', strings.feedbackError);

                            window.clearTimeout(this.clipboardFeedbackTimer);
                            this.clipboardFeedbackTimer = window.setTimeout(() => {
                                setCopyButtonState('default');
                                resetCopyFeedback();
                            }, 4000);
                        });
                });
            }

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

        initPasswordVisibilityToggles: function() {
            const $toggles = $('.password-visibility-toggle');
            if (!$toggles.length) {
                return;
            }

            const strings = {
                show: this.getString('show_secret', 'Show key'),
                hide: this.getString('hide_secret', 'Hide key'),
            };

            const updateButton = ($button, isVisible) => {
                const $icon = $button.find('.dashicons').first();
                const $label = $button.find('.button-label').first();

                $icon.removeClass('dashicons-visibility dashicons-hidden');

                if (isVisible) {
                    $icon.addClass('dashicons-hidden');
                    $label.text(strings.hide);
                    $button.attr('aria-pressed', 'true');
                } else {
                    $icon.addClass('dashicons-visibility');
                    $label.text(strings.show);
                    $button.attr('aria-pressed', 'false');
                }
            };

            $toggles.each((index, element) => {
                const $button = $(element);
                const targetId = $button.data('target');
                const $input = targetId ? $(`#${targetId}`) : $();
                const isVisible = $input.length && $input.attr('type') === 'text';
                updateButton($button, isVisible);
            });

            $toggles.on('click', (event) => {
                event.preventDefault();

                const $button = $(event.currentTarget);
                const targetId = $button.data('target');
                if (!targetId) {
                    return;
                }

                const $input = $(`#${targetId}`);
                if (!$input.length) {
                    return;
                }

                const isPassword = $input.attr('type') === 'password';
                const newType = isPassword ? 'text' : 'password';
                $input.attr('type', newType);

                updateButton($button, newType === 'text');
                $input.trigger('focus');
            });
        },

        copyTextToClipboard: function(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text);
            }

            return new Promise((resolve, reject) => {
                try {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);

                    const activeElement = document.activeElement;
                    textarea.focus();
                    textarea.select();

                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);

                    if (activeElement && typeof activeElement.focus === 'function') {
                        activeElement.focus();
                    }

                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('copy_unsuccessful'));
                    }
                } catch (error) {
                    reject(error);
                }
            });
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

            $('.scanner-options input[type="checkbox"]').off('change.bulkSummary').on('change.bulkSummary', () => {
                this.updateBulkScanSummary();
            });

            $('#min-words').off('input.bulkSummary change.bulkSummary').on('input.bulkSummary change.bulkSummary', () => {
                this.updateBulkScanSummary();
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
                this.highlightResultsQuickFilter(this.scannerState.currentFilter);
                this.loadScanResults(1);
            });

            $('#results-pagination').off('click').on('click', '.page-link', (e) => {
                e.preventDefault();
                const $target = $(e.currentTarget);

                if ($target.attr('aria-disabled') === 'true' || $target.hasClass('is-disabled')) {
                    return;
                }

                const page = parseInt($target.data('page'), 10);
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

            this.bindScannerOptionToggles();

            $('#results-quick-filters').off('click').on('click', '.quick-filter', (e) => {
                e.preventDefault();
                const filter = $(e.currentTarget).data('filter');
                if (!filter) {
                    return;
                }

                $('#results-filter').val(filter).trigger('change');
            });

            this.loadScannerOverview();
            this.loadScanResults();
            this.loadScannerAnalytics();
            this.updateBulkScanSummary();
            this.highlightResultsQuickFilter(this.scannerState.currentFilter);
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
                    const total = data.total_posts || 0;
                    const scanned = data.scanned_posts || 0;
                    const pending = data.pending_posts != null ? data.pending_posts : Math.max(0, total - scanned);
                    const validated = data.validated_keywords || 0;

                    $('#total-posts').text(this.formatNumber(total));
                    $('#scanned-posts').text(this.formatNumber(scanned));
                    $('#pending-posts').text(this.formatNumber(pending));
                    $('#validated-keywords').text(this.formatNumber(validated));

                    const coverage = total > 0 ? Math.round((scanned / total) * 100) : 0;
                    const keywordRate = scanned > 0 ? Math.round((validated / scanned) * 100) : 0;

                    $('#scan-coverage').text(`${coverage}%`);
                    $('#keyword-success-rate').text(`${keywordRate}%`);
                    $('#overview-progress-percent').text(`${coverage}%`);
                    $('#overview-progress-fill')
                        .css('width', `${coverage}%`)
                        .attr('aria-valuenow', coverage);

                    const $heroCoverage = $('#scanner-hero-coverage');
                    if ($heroCoverage.length) {
                        $heroCoverage.text(`${coverage}%`);
                    }

                    const $heroKeywordRate = $('#scanner-hero-keyword-rate');
                    if ($heroKeywordRate.length) {
                        $heroKeywordRate.text(`${keywordRate}%`);
                    }

                    const $subtext = $('#overview-progress-subtext');
                    if (pending > 0) {
                        const formattedPending = this.formatNumber(pending);
                        const $pendingCount = $('#scan-pending-count');
                        if ($pendingCount.length) {
                            $pendingCount.text(formattedPending);
                        } else {
                            $subtext.html(`Noch <span id="scan-pending-count">${formattedPending}</span> Beiträge offen`);
                        }
                        $subtext.removeClass('is-complete');
                    } else {
                        $subtext.addClass('is-complete').text('Alle Beiträge gescannt!');
                    }
                    $('#overview-refreshed').text(new Date().toLocaleTimeString());
                }
            });
        },

        loadScanResults: function(page = 1) {
            if (!this.scannerState) {
                this.scannerState = { currentPage: 1, currentFilter: 'all' };
            }

            this.scannerState.currentPage = page;

            const filter = this.scannerState.currentFilter || 'all';
            this.highlightResultsQuickFilter(filter);
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
                    if (!this.scannerState) {
                        this.scannerState = {};
                    }

                    this.scannerState.selectedPost = { id: postId };
                    this.scanSinglePost(true);
                }
            });
        },

        renderResultsPagination: function(pagination) {
            const container = $('#results-pagination');

            if (!pagination || !pagination.total_pages || pagination.total_pages <= 1) {
                container.empty();
                return;
            }

            const total = pagination.total || 0;
            const perPage = pagination.per_page || 10;
            const current = pagination.current_page || 1;
            const totalPages = pagination.total_pages || 1;
            const start = total === 0 ? 0 : ((current - 1) * perPage) + 1;
            const end = Math.min(total, current * perPage);

            const createLink = (label, page, options = {}) => {
                const opts = Object.assign({ disabled: false, active: false, className: '', ariaLabel: '' }, options);
                const classes = ['page-link'];
                if (opts.className) {
                    classes.push(opts.className);
                }
                if (opts.active) {
                    classes.push('is-active');
                }
                if (opts.disabled) {
                    classes.push('is-disabled');
                }

                const attributes = [];
                if (opts.ariaLabel) {
                    attributes.push(`aria-label="${opts.ariaLabel}"`);
                }
                if (opts.active) {
                    attributes.push('aria-current="page"');
                }
                if (opts.disabled) {
                    attributes.push('aria-disabled="true"');
                } else {
                    attributes.push(`data-page="${page}"`);
                }

                return `<a href="#" class="${classes.join(' ')}" ${attributes.join(' ')}>${label}</a>`;
            };

            const pageLinks = [];

            pageLinks.push(createLink('&lsaquo;', Math.max(1, current - 1), {
                className: 'page-prev',
                ariaLabel: 'Vorherige Seite',
                disabled: current <= 1
            }));

            const windowSize = 5;
            let startPage = Math.max(1, current - 2);
            let endPage = Math.min(totalPages, startPage + windowSize - 1);
            if (endPage - startPage < windowSize - 1) {
                startPage = Math.max(1, endPage - windowSize + 1);
            }

            if (startPage > 1) {
                pageLinks.push(createLink('1', 1, { ariaLabel: 'Seite 1' }));
                if (startPage > 2) {
                    pageLinks.push('<span class="page-ellipsis" aria-hidden="true">…</span>');
                }
            }

            for (let i = startPage; i <= endPage; i += 1) {
                pageLinks.push(createLink(`${i}`, i, {
                    active: i === current,
                    ariaLabel: `Seite ${i}`
                }));
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    pageLinks.push('<span class="page-ellipsis" aria-hidden="true">…</span>');
                }
                pageLinks.push(createLink(`${totalPages}`, totalPages, { ariaLabel: `Seite ${totalPages}` }));
            }

            pageLinks.push(createLink('&rsaquo;', Math.min(totalPages, current + 1), {
                className: 'page-next',
                ariaLabel: 'Nächste Seite',
                disabled: current >= totalPages
            }));

            const html = `
                <nav class="pagination-nav" role="navigation" aria-label="Recent scan results pagination">
                    <div class="pagination-summary">${start}–${end} von ${this.formatNumber(total)}</div>
                    <div class="pagination-pages">${pageLinks.join('')}</div>
                </nav>
            `;

            container.html(html);
        },

        bindScannerOptionToggles: function() {
            $('.option-toggle').off('click').on('click', (e) => {
                e.preventDefault();
                const $button = $(e.currentTarget);
                const target = $button.data('target');
                const action = $button.data('action');

                if (!target || !action) {
                    return;
                }

                const selector = `input[name="${target}[]"]`;
                const $checkboxes = $(selector);

                if (!$checkboxes.length) {
                    return;
                }

                const shouldCheck = action === 'select';
                if (action === 'select' || action === 'clear') {
                    $checkboxes.prop('checked', shouldCheck).trigger('change');
                }
            });
        },

        highlightResultsQuickFilter: function(activeFilter) {
            const $buttons = $('#results-quick-filters .quick-filter');

            if (!$buttons.length) {
                return;
            }

            $buttons.removeClass('is-active').attr('aria-pressed', 'false');
            const $active = $buttons.filter(`[data-filter="${activeFilter}"]`);
            if ($active.length) {
                $active.addClass('is-active').attr('aria-pressed', 'true');
            }
        },

        updateBulkScanSummary: function() {
            const $summary = $('#bulk-scan-summary');
            if (!$summary.length) {
                return;
            }

            const getCheckedLabels = (name) => {
                return $(`input[name="${name}[]"]:checked`).map(function() {
                    return $(this).closest('label').text().trim();
                }).get();
            };

            const postTypes = getCheckedLabels('post_types');
            const postStatus = getCheckedLabels('post_status');
            const minWords = parseInt($('#min-words').val(), 10);

            const scanOptions = [];
            if ($('input[name="scan_options[]"][value="force_rescan"]').is(':checked')) {
                scanOptions.push('Force Re-scan');
            }
            if ($('input[name="scan_options[]"][value="use_ai"]').is(':checked')) {
                scanOptions.push('AI Analysis');
            }
            if ($('input[name="scan_options[]"][value="validate_products"]').is(':checked')) {
                scanOptions.push('Validate Products');
            }

            $summary.find('.summary-post-types').text(postTypes.length ? postTypes.join(', ') : 'Keine Auswahl');
            $summary.find('.summary-post-status').text(postStatus.length ? postStatus.join(', ') : 'Keine Auswahl');
            $summary.find('.summary-min-words').text(Number.isFinite(minWords) ? `${minWords} Wörter` : 'Kein Mindestwert');
            $summary.find('.summary-scan-options').text(scanOptions.length ? scanOptions.join(', ') : 'Standardprüfung');
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

        scanSinglePost: function(forceRescan) {
            if (!this.scannerState || !this.scannerState.selectedPost || !this.scannerState.selectedPost.id) {
                alert('Please select a post to scan.');
                return;
            }

            const shouldForceRescan = typeof forceRescan === 'boolean' ? forceRescan : false;
            const forceRescanFlag = shouldForceRescan || $('#single-force-rescan').is(':checked');
            const button = $('#scan-single-post');
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Scanning...');

            $.post(this.ajax_url, {
                action: 'yadore_scan_single_post',
                nonce: this.nonce,
                post_id: this.scannerState.selectedPost.id,
                use_ai: $('#single-use-ai').is(':checked') ? 1 : 0,
                force_rescan: forceRescanFlag ? 1 : 0,
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
                const selected = Number.parseInt($(e.target).val(), 10);
                this.loadAnalyticsData(Number.isFinite(selected) ? selected : 30);
            });

            // Performance metric selector
            $('#performance-metric').on('change', (e) => {
                this.loadPerformanceTable($(e.target).val());
            });

            // Refresh button
            $('#refresh-analytics').on('click', (e) => {
                e.preventDefault();
                const period = parseInt($('#analytics-period').val(), 10) || 30;
                this.loadAnalyticsData(period);
            });

            this.performanceTableData = null;
            this.keywordData = null;
            this.analyticsData = null;

            // Initialize charts
            this.initAnalyticsCharts();

            // Load initial data
            this.loadAnalyticsData();
        },

        initAnalyticsCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }

            if (this.performanceChart && typeof this.performanceChart.destroy === 'function') {
                this.performanceChart.destroy();
            }
            if (this.trafficChart && typeof this.trafficChart.destroy === 'function') {
                this.trafficChart.destroy();
            }
            if (this.revenueChart && typeof this.revenueChart.destroy === 'function') {
                this.revenueChart.destroy();
            }

            // Performance chart
            const performanceCtx = document.getElementById('performance-chart');
            if (performanceCtx) {
                this.performanceChart = new Chart(performanceCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Product Views',
                            data: [],
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Clicks',
                            data: [],
                            borderColor: '#00a32a',
                            backgroundColor: 'rgba(0, 163, 42, 0.1)',
                            tension: 0.4,
                            fill: true
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

            // Traffic chart
            const trafficCtx = document.getElementById('traffic-chart');
            if (trafficCtx) {
                this.trafficChart = new Chart(trafficCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Visitors',
                            data: [],
                            borderColor: '#d63638',
                            backgroundColor: 'rgba(214, 54, 56, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Product Views',
                            data: [],
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            tension: 0.4,
                            fill: true
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

            // Revenue chart
            const revenueCtx = document.getElementById('revenue-chart');
            if (revenueCtx) {
                this.revenueChart = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Revenue',
                            data: [],
                            backgroundColor: 'rgba(0, 163, 42, 0.2)',
                            borderColor: '#00a32a',
                            borderWidth: 1
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
            const parsedPeriod = Number.parseInt(period, 10);
            const requestPeriod = Number.isFinite(parsedPeriod)
                ? Math.min(Math.max(parsedPeriod, 1), 365)
                : 30;

            const $periodSelector = $('#analytics-period');
            if ($periodSelector.length && String($periodSelector.val()) !== String(requestPeriod)) {
                $periodSelector.val(String(requestPeriod));
            }

            $.post(this.ajax_url, {
                action: 'yadore_get_analytics_data',
                nonce: this.nonce,
                period: requestPeriod
            }).done((response) => {
                if (!response || !response.success || !response.data) {
                    const message = response?.data?.message || null;
                    this.showAnalyticsError(message);
                    return;
                }

                const data = response.data || {};
                this.analyticsData = data;
                this.performanceTableData = data.performance?.table || {};
                this.keywordData = data.keywords || {};

                this.updateAnalyticsSummary(data.summary);
                this.updateTrafficMetrics(data.traffic);
                this.updateConversionFunnel(data.funnel);
                this.updateRevenueMetrics(data.revenue);
                this.updateAnalyticsCharts(data);

                const metric = $('#performance-metric').val() || 'views';
                this.loadPerformanceTable(metric);

                this.renderKeywordOverview(this.keywordData);
                this.renderKeywordCloud(this.keywordData?.cloud);
                this.renderKeywordPerformance(this.keywordData?.performance);
            }).fail(() => {
                this.showAnalyticsError();
            });
        },

        showAnalyticsError: function(message) {
            const errorMessage = message || (yadore_admin.strings?.error || 'Failed to load analytics data.');

            this.updateAnalyticsSummary({});
            this.updateTrafficMetrics({});
            this.updateConversionFunnel({});
            this.updateRevenueMetrics({});
            this.updateAnalyticsCharts({});

            this.performanceTableData = { views: [], clicks: [], ctr: [], revenue: [] };
            this.keywordData = { overview: { total: 0, active: 0, ai: 0 }, cloud: [], performance: [] };

            const metric = $('#performance-metric').val() || 'views';
            this.loadPerformanceTable(metric, errorMessage);
            this.renderKeywordOverview(this.keywordData);

            const $keywordBody = $('#keyword-performance-body');
            if ($keywordBody.length) {
                $keywordBody.html(
                    $('<tr/>').append(
                        $('<td/>', {
                            colspan: 6,
                            'class': 'loading-row'
                        }).text(errorMessage)
                    )
                );
            }

            const $cloud = $('#keyword-cloud');
            if ($cloud.length) {
                $cloud.empty().append(
                    $('<div/>', { 'class': 'cloud-empty' }).text(errorMessage)
                );
            }
        },

        updateTrendIndicator: function(selector, trend) {
            const $element = $(selector);
            if (!$element.length) {
                return;
            }

            const change = Number(trend?.change);
            const direction = trend?.direction || 'neutral';
            const current = Number(trend?.current);
            const previous = Number(trend?.previous);

            const classes = ['positive', 'negative', 'neutral'];
            $element.removeClass(classes.join(' '));

            let className = 'neutral';
            if (direction === 'up') {
                className = 'positive';
            } else if (direction === 'down') {
                className = 'negative';
            }
            $element.addClass(className);

            let formattedChange = '0%';
            if (Number.isFinite(change)) {
                const prefix = change > 0 ? '+' : change < 0 ? '-' : '';
                const absolute = Math.abs(change).toLocaleString(undefined, {
                    minimumFractionDigits: Math.abs(change) > 0 && Math.abs(change) < 10 ? 1 : 0,
                    maximumFractionDigits: 1,
                });
                formattedChange = `${prefix}${absolute}%`;
            }

            $element.text(formattedChange);

            if (Number.isFinite(current) && Number.isFinite(previous)) {
                const currentLabel = current.toLocaleString();
                const previousLabel = previous.toLocaleString();
                $element.attr('title', `${previousLabel} → ${currentLabel}`);
            } else {
                $element.removeAttr('title');
            }
        },

        updateAnalyticsSummary: function(summary = {}) {
            const views = Number(summary?.product_views) || 0;
            const overlays = Number(summary?.overlay_displays) || 0;
            const ctr = Number(summary?.average_ctr) || 0;
            const aiAnalyses = Number(summary?.ai_analyses) || 0;

            $('#total-product-views').text(this.formatNumber(views));
            $('#total-overlays').text(this.formatNumber(overlays));
            $('#average-ctr').text(`${this.formatRate(ctr)}%`);
            $('#ai-analyses').text(this.formatNumber(aiAnalyses));

            this.updateTrendIndicator('#views-trend', summary?.trends?.views);
            this.updateTrendIndicator('#overlays-trend', summary?.trends?.overlays);
            this.updateTrendIndicator('#ctr-trend', summary?.trends?.ctr);
            this.updateTrendIndicator('#ai-trend', summary?.trends?.ai_analyses);
        },

        updateTrafficMetrics: function(traffic = {}) {
            const dailyAverage = Number(traffic?.daily_average) || 0;
            const productPages = Number(traffic?.product_pages) || 0;
            const bounceRate = Number(traffic?.bounce_rate) || 0;
            const duration = this.formatDuration(traffic?.session_duration);

            $('#daily-visitors').text(this.formatNumber(dailyAverage));
            $('#product-pages').text(this.formatNumber(productPages));
            $('#bounce-rate').text(`${this.formatRate(bounceRate)}%`);
            $('#session-duration').text(duration);
        },

        updateConversionFunnel: function(funnel = {}) {
            const pageViews = Number(funnel?.page_views) || 0;
            const displays = Number(funnel?.product_displays) || 0;
            const clicks = Number(funnel?.product_clicks) || 0;
            const conversions = Number(funnel?.conversions) || 0;

            $('#funnel-views').text(this.formatNumber(pageViews));
            $('#funnel-displays').text(this.formatNumber(displays));
            $('#funnel-clicks').text(this.formatNumber(clicks));
            $('#funnel-conversions').text(this.formatNumber(conversions));

            $('#display-rate').text(`${this.formatRate(Number(funnel?.display_rate) || 0)}%`);
            $('#click-rate').text(`${this.formatRate(Number(funnel?.click_rate) || 0)}%`);
            $('#conversion-rate').text(`${this.formatRate(Number(funnel?.conversion_rate) || 0)}%`);
        },

        updateRevenueMetrics: function(revenue = {}) {
            const monthly = Number(revenue?.monthly_estimate) || 0;
            const rpc = Number(revenue?.revenue_per_click) || 0;
            const topCategory = revenue?.top_category || 'No data';
            const categoryEarnings = Number(revenue?.category_earnings) || 0;

            $('#monthly-revenue').text(this.formatCurrency(monthly));
            $('#rpc').text(this.formatCurrency(rpc));
            $('#top-category').text(topCategory);
            $('#category-earnings').text(`${this.formatCurrency(categoryEarnings)} earned`);
        },

        updateAnalyticsCharts: function(data = {}) {
            const performance = this.normalizeLineChartData(
                data?.performance?.chart,
                this.generatePerformanceFallback(),
                ['views', 'clicks']
            );
            if (this.performanceChart) {
                this.performanceChart.data.labels = performance.labels;
                this.performanceChart.data.datasets[0].data = performance.views;
                this.performanceChart.data.datasets[1].data = performance.clicks;
                this.performanceChart.update();
            }

            const traffic = this.normalizeLineChartData(
                data?.traffic?.chart,
                this.generateTrafficFallback(),
                ['visitors', 'views']
            );
            if (this.trafficChart) {
                this.trafficChart.data.labels = traffic.labels;
                this.trafficChart.data.datasets[0].data = traffic.visitors;
                this.trafficChart.data.datasets[1].data = traffic.views;
                this.trafficChart.update();
            }

            const revenue = data?.revenue?.trend || {};
            if (this.revenueChart) {
                this.revenueChart.data.labels = Array.isArray(revenue.labels) ? revenue.labels : [];
                this.revenueChart.data.datasets[0].data = Array.isArray(revenue.values) ? revenue.values : [];
                this.revenueChart.update();
            }
        },

        normalizeLineChartData: function(chart = {}, fallback = {}, dataKeys = []) {
            const labels = Array.isArray(chart?.labels) ? chart.labels.map((label) => String(label)) : [];
            const hasValidLabels = labels.length > 0;
            let hasValidSeries = hasValidLabels;
            const sanitized = { labels };

            dataKeys.forEach((key) => {
                const series = Array.isArray(chart?.[key]) ? chart[key] : [];
                if (!hasValidLabels || series.length !== labels.length) {
                    hasValidSeries = false;
                }
                sanitized[key] = series.map((value) => {
                    const parsed = Number(value);
                    return Number.isFinite(parsed) ? parsed : 0;
                });
            });

            if (hasValidLabels && hasValidSeries) {
                return sanitized;
            }

            const fallbackLabels = Array.isArray(fallback.labels) ? fallback.labels : [];
            const result = { labels: fallbackLabels.map((label) => String(label)) };

            dataKeys.forEach((key) => {
                const values = Array.isArray(fallback[key]) ? fallback[key] : [];
                result[key] = this.normalizeFallbackSeries(values, fallbackLabels.length);
            });

            return result;
        },

        normalizeFallbackSeries: function(values, length) {
            if (!Array.isArray(values) || values.length === 0) {
                return length > 0 ? Array(length).fill(0) : [];
            }

            if (values.length === length) {
                return values.map((value) => {
                    const parsed = Number(value);
                    return Number.isFinite(parsed) ? parsed : 0;
                });
            }

            if (length <= 0) {
                return [];
            }

            const normalized = [];
            const lastIndex = values.length - 1;

            for (let index = 0; index < length; index++) {
                if (length === 1) {
                    const single = Number(values[0]);
                    normalized.push(Number.isFinite(single) ? single : 0);
                    continue;
                }

                const position = (index / (length - 1)) * lastIndex;
                const lowerIndex = Math.floor(position);
                const upperIndex = Math.min(Math.ceil(position), lastIndex);

                if (lowerIndex === upperIndex) {
                    const value = Number(values[lowerIndex]);
                    normalized.push(Number.isFinite(value) ? value : 0);
                } else {
                    const ratio = position - lowerIndex;
                    const lowerValue = Number(values[lowerIndex]);
                    const upperValue = Number(values[upperIndex]);
                    const safeLower = Number.isFinite(lowerValue) ? lowerValue : 0;
                    const safeUpper = Number.isFinite(upperValue) ? upperValue : 0;
                    normalized.push(safeLower + (safeUpper - safeLower) * ratio);
                }
            }

            return normalized.map((value) => Math.round(value * 100) / 100);
        },

        generateFallbackLabels: function(days = 7) {
            const count = Math.max(1, Number.parseInt(days, 10) || 1);
            const labels = [];
            const today = new Date();
            const formatter = (typeof Intl !== 'undefined' && typeof Intl.DateTimeFormat === 'function')
                ? new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' })
                : null;

            for (let index = count - 1; index >= 0; index--) {
                const day = new Date(today);
                day.setHours(0, 0, 0, 0);
                day.setDate(day.getDate() - index);

                if (formatter) {
                    labels.push(formatter.format(day));
                } else {
                    const month = String(day.getMonth() + 1).padStart(2, '0');
                    const date = String(day.getDate()).padStart(2, '0');
                    labels.push(`${month}/${date}`);
                }
            }

            return labels;
        },

        generatePerformanceFallback: function() {
            const labels = this.generateFallbackLabels(7);
            const baseViews = [120, 135, 128, 142, 155, 161, 172];
            const baseClicks = [24, 28, 26, 30, 33, 35, 38];

            return {
                labels,
                views: this.normalizeFallbackSeries(baseViews, labels.length),
                clicks: this.normalizeFallbackSeries(baseClicks, labels.length)
            };
        },

        generateTrafficFallback: function() {
            const labels = this.generateFallbackLabels(7);
            const baseVisitors = [48, 55, 52, 61, 64, 70, 74];
            const baseViews = [96, 104, 101, 118, 126, 132, 141];

            return {
                labels,
                visitors: this.normalizeFallbackSeries(baseVisitors, labels.length),
                views: this.normalizeFallbackSeries(baseViews, labels.length)
            };
        },

        loadPerformanceTable: function(metric = 'views', emptyMessage = null) {
            const $tbody = $('#performance-table-body');
            if (!$tbody.length) {
                return;
            }

            const rows = (this.performanceTableData && this.performanceTableData[metric]) || [];
            $tbody.empty();

            if (!Array.isArray(rows) || rows.length === 0) {
                const message = emptyMessage || 'No performance data available.';
                $tbody.append(
                    $('<tr/>').append(
                        $('<td/>', { colspan: 6, 'class': 'loading-row' }).text(message)
                    )
                );
                return;
            }

            rows.forEach((row) => {
                const $tr = $('<tr/>');
                const title = row.title || '—';
                const keyword = row.keyword || '—';

                const $titleCell = $('<td/>', { 'class': 'cell-title' }).text(title);
                const $keywordCell = $('<td/>', { 'class': 'cell-keyword' }).text(keyword);

                if (keyword !== '—') {
                    $keywordCell.attr('title', keyword);
                }

                const metrics = [
                    this.formatNumber(row.views || 0),
                    this.formatNumber(row.clicks || 0),
                    `${this.formatRate(row.ctr || 0)}%`,
                    this.formatCurrency(row.revenue || 0)
                ];

                $tr.append($titleCell);
                $tr.append($keywordCell);

                metrics.forEach((value) => {
                    $tr.append(
                        $('<td/>', { 'class': 'cell-metric' }).text(value)
                    );
                });

                $tbody.append($tr);
            });
        },

        renderKeywordOverview: function(keywords = {}) {
            const overview = keywords?.overview || {};
            $('#total-keywords').text(this.formatNumber(Number(overview.total) || 0));
            $('#active-keywords').text(this.formatNumber(Number(overview.active) || 0));
            $('#ai-keywords').text(this.formatNumber(Number(overview.ai) || 0));
        },

        renderKeywordCloud: function(items) {
            const $container = $('#keyword-cloud');
            if (!$container.length) {
                return;
            }

            $container.empty();

            if (!Array.isArray(items) || items.length === 0) {
                $container.append(
                    $('<div/>', { 'class': 'cloud-empty' }).text('No keyword data available.')
                );
                return;
            }

            const sortedItems = items
                .filter((item) => item && typeof item.keyword === 'string' && item.keyword.trim() !== '')
                .map((item) => ({
                    keyword: String(item.keyword),
                    count: Number(item.count) || 0,
                    ctr: Number.isFinite(Number(item.ctr)) ? Number(item.ctr) : null,
                    clicks: Number.isFinite(Number(item.clicks)) ? Number(item.clicks) : null,
                }))
                .sort((a, b) => b.count - a.count);

            if (sortedItems.length === 0) {
                $container.append(
                    $('<div/>', { 'class': 'cloud-empty' }).text('No keyword data available.')
                );
                return;
            }

            const counts = sortedItems.map((item) => item.count);
            const maxCount = Math.max(...counts, 1);
            const totalCount = counts.reduce((sum, value) => sum + value, 0) || 1;

            sortedItems.forEach((item, index) => {
                const share = (item.count / totalCount) * 100;
                const formattedShare = share.toLocaleString(undefined, {
                    minimumFractionDigits: share > 0 && share < 10 ? 1 : 0,
                    maximumFractionDigits: 1,
                });

                const $chip = $('<div/>', {
                    'class': 'keyword-chip',
                    'data-rank': index + 1
                });

                const $header = $('<div/>', { 'class': 'chip-header' });
                $header.append($('<span/>', { 'class': 'chip-label' }).text(item.keyword));
                $header.append(
                    $('<span/>', { 'class': 'chip-count' }).text(
                        `${this.formatNumber(item.count)} ${item.count === 1 ? 'use' : 'uses'}`
                    )
                );

                const fillWidth = Math.max(8, Math.round((item.count / maxCount) * 100));
                const $bar = $('<div/>', { 'class': 'chip-bar' }).append(
                    $('<span/>', { 'class': 'chip-bar-fill' }).css('width', `${fillWidth}%`)
                );

                const metaParts = [];
                if (share > 0) {
                    metaParts.push(`${formattedShare}% share`);
                }
                if (item.clicks && item.clicks > 0) {
                    metaParts.push(`${this.formatNumber(item.clicks)} clicks`);
                }
                if (item.ctr && item.ctr > 0) {
                    metaParts.push(`${this.formatRate(item.ctr)}% CTR`);
                }

                $chip.append($header);
                $chip.append($bar);

                if (metaParts.length > 0) {
                    const $meta = $('<div/>', { 'class': 'chip-meta' });
                    metaParts.forEach((text) => {
                        $meta.append($('<span/>').text(text));
                    });
                    $chip.append($meta);
                }

                $container.append($chip);
            });
        },

        renderKeywordPerformance: function(rows) {
            const $tbody = $('#keyword-performance-body');
            if (!$tbody.length) {
                return;
            }

            $tbody.empty();

            if (!Array.isArray(rows) || rows.length === 0) {
                $tbody.append(
                    $('<tr/>').append(
                        $('<td/>', { colspan: 6, 'class': 'loading-row' }).text('No keyword performance data available.')
                    )
                );
                return;
            }

            rows.forEach((row) => {
                const $tr = $('<tr/>');
                const keyword = row.keyword || '—';
                const $keywordCell = $('<td/>', { 'class': 'cell-keyword' }).text(keyword);
                if (keyword !== '—') {
                    $keywordCell.attr('title', keyword);
                }

                $tr.append($keywordCell);
                $tr.append($('<td/>', { 'class': 'cell-metric' }).text(this.formatNumber(row.usage || 0)));
                $tr.append($('<td/>', { 'class': 'cell-metric' }).text(`${this.formatRate(row.ctr || 0)}%`));
                $tr.append($('<td/>', { 'class': 'cell-metric' }).text(this.formatNumber(row.clicks || 0)));
                $tr.append($('<td/>', { 'class': 'cell-metric' }).text(`${this.formatRate(row.confidence || 0)}%`));
                $tr.append($('<td/>', { 'class': 'cell-source' }).text(row.source || '—'));
                $tbody.append($tr);
            });
        },

        formatCurrency: function(value) {
            const numeric = Number.parseFloat(value);
            if (Number.isFinite(numeric)) {
                return numeric.toLocaleString(undefined, {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            return '$0.00';
        },

        formatDuration: function(value) {
            if (typeof value === 'string' && value.trim() !== '') {
                return value;
            }

            const numeric = Number(value);
            if (!Number.isFinite(numeric)) {
                return '0s';
            }

            const totalSeconds = Math.max(0, Math.round(numeric));
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;

            if (minutes > 0) {
                return `${minutes}m ${seconds.toString().padStart(2, '0')}s`;
            }

            return `${seconds}s`;
        },

        setupExportDateRangeToggle: function() {
            const $select = $('#export-date-range');
            const $custom = $('#custom-date-range');

            if (!$select.length || !$custom.length) {
                return;
            }

            const applyState = (animate) => {
                const shouldShow = $select.val() === 'custom';

                if (animate) {
                    if (shouldShow) {
                        $custom.stop(true, true).slideDown(150);
                    } else {
                        $custom.stop(true, true).slideUp(150);
                    }
                } else if (shouldShow) {
                    $custom.stop(true, true).show();
                } else {
                    $custom.stop(true, true).hide();
                }
            };

            applyState(false);

            $select.off('change.yadoreTools').on('change.yadoreTools', () => applyState(true));
        },

        // Tools functionality
        initTools: function() {
            if (!$('.yadore-tools-container').length) return;

            this.setupExportDateRangeToggle();

            // Export/Import
            $('#start-export').on('click', (e) => {
                e.preventDefault();
                this.startExport();
            });

            $('#schedule-export').on('click', (e) => {
                e.preventDefault();
                this.scheduleExport();
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

            $('#restore-default-templates').on('click', (e) => {
                e.preventDefault();
                const resetSelection = $('#restore-reset-selection').is(':checked');
                this.restoreDefaultTemplates(resetSelection);
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

            this.loadToolStats();
        },

        getExportConfiguration: function() {
            const dataTypes = $('input[name="export_data[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (!dataTypes.length) {
                throw new Error('Please select at least one data type to export.');
            }

            const format = $('input[name="export_format"]:checked').val() || 'json';
            const dateRange = $('#export-date-range').val() || 'all';
            let startDate = '';
            let endDate = '';

            if (dateRange === 'custom') {
                startDate = $('#export-start-date').val();
                endDate = $('#export-end-date').val();

                if (!startDate || !endDate) {
                    throw new Error('Please choose both a start and end date for the custom range.');
                }

                const startTime = new Date(startDate);
                const endTime = new Date(endDate);
                if (startTime > endTime) {
                    throw new Error('The custom start date must be before the end date.');
                }
            }

            return {
                data_types: dataTypes,
                format,
                date_range: dateRange,
                start_date: startDate,
                end_date: endDate
            };
        },

        startExport: function() {
            let config;

            try {
                config = this.getExportConfiguration();
            } catch (error) {
                alert(error.message);
                return;
            }

            const button = $('#start-export');
            const originalHtml = button.html();

            this.resetExportFeedback();
            this.updateExportStatus('Preparing export...');
            this.setExportProgress(5);

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Exporting...');

            $.post(this.ajax_url, {
                action: 'yadore_export_data',
                nonce: this.nonce,
                data_types: config.data_types,
                format: config.format,
                date_range: config.date_range,
                start_date: config.start_date,
                end_date: config.end_date
            }, (response) => {
                if (response && response.success) {
                    const data = response.data || {};
                    const records = Number(data.records) || 0;

                    this.setExportProgress(100);
                    this.updateExportStatus(`Export completed. ${records.toLocaleString()} records ready for download.`);

                    if (data.content) {
                        try {
                            this.handleExportDownload(data);
                        } catch (downloadError) {
                            console.error(downloadError);
                            alert('Export created, but the download could not be prepared.');
                        }
                    }
                } else {
                    const message = response && response.data ? response.data : 'Export failed.';
                    this.setExportProgress(0);
                    this.updateExportStatus(message);
                    alert(message);
                }
            }, 'json').fail((xhr) => {
                const message = xhr?.responseJSON?.data || 'Export failed.';
                this.setExportProgress(0);
                this.updateExportStatus(message);
                alert(message);
            }).always(() => {
                button.prop('disabled', false).html(originalHtml);
            });
        },

        scheduleExport: function() {
            let config;

            try {
                config = this.getExportConfiguration();
            } catch (error) {
                alert(error.message);
                return;
            }

            const interval = $('#export-schedule-interval').val() || 'daily';
            const time = $('#export-schedule-time').val() || '02:00';

            if (!/^\d{2}:\d{2}$/.test(time)) {
                alert('Please enter a valid schedule time (HH:MM).');
                return;
            }

            const button = $('#schedule-export');
            const originalHtml = button.html();

            this.resetExportFeedback();

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Scheduling...');

            $.post(this.ajax_url, {
                action: 'yadore_schedule_export',
                nonce: this.nonce,
                data_types: config.data_types,
                format: config.format,
                date_range: config.date_range,
                start_date: config.start_date,
                end_date: config.end_date,
                interval,
                time
            }, (response) => {
                if (response && response.success) {
                    const data = response.data || {};
                    const nextRunRaw = typeof data.next_run_human === 'string' ? data.next_run_human : '';
                    const nextRun = nextRunRaw.trim();
                    const scheduleMessage = nextRun
                        ? `Scheduled export saved. Next run: ${nextRun}`
                        : 'Scheduled export saved.';

                    this.setExportProgress(0);
                    this.updateExportStatus(scheduleMessage);
                    this.updateScheduleStatus(nextRun, 'Scheduled exports are configured.');
                    this.loadToolStats();
                } else {
                    const message = response && response.data ? response.data : 'Failed to schedule export.';
                    this.setExportProgress(0);
                    this.updateExportStatus(message);
                    alert(message);
                }
            }, 'json').fail((xhr) => {
                const message = xhr?.responseJSON?.data || 'Failed to schedule export.';
                this.setExportProgress(0);
                this.updateExportStatus(message);
                alert(message);
            }).always(() => {
                button.prop('disabled', false).html(originalHtml);
            });
        },

        resetExportFeedback: function() {
            $('#export-results').hide();
            this.setExportProgress(0);
            $('#export-status').text('');
        },

        updateExportStatus: function(message) {
            $('#export-results').show();
            $('#export-status').text(message);
        },

        setExportProgress: function(percent) {
            const clamped = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
            $('#export-progress').css('width', clamped + '%');
        },

        updateScheduleStatus: function(nextRunText, fallbackText = '') {
            const $status = $('#export-schedule-status');
            if (!$status.length) {
                return;
            }

            const hasNextRun = typeof nextRunText === 'string' && nextRunText.trim() !== '';

            if (hasNextRun) {
                const safeNextRun = this.escapeHtml(nextRunText.trim());
                $status.html(`<strong>Next run:</strong> ${safeNextRun}`);
                return;
            }

            if (typeof fallbackText === 'string' && fallbackText !== '') {
                $status.text(fallbackText);
            } else {
                $status.empty();
            }
        },

        handleExportDownload: function(payload) {
            if (!payload || !payload.content) {
                throw new Error('Missing export payload.');
            }

            const blob = this.base64ToBlob(payload.content, payload.mime_type);
            if (!blob) {
                throw new Error('Failed to create export file.');
            }

            const filename = payload.filename || `yadore-export.${payload.format || 'json'}`;
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();

            setTimeout(() => {
                URL.revokeObjectURL(url);
                link.remove();
            }, 100);
        },

        base64ToBlob: function(base64, mimeType = 'application/octet-stream') {
            try {
                const binary = atob(base64);
                const len = binary.length;
                const buffer = new Uint8Array(len);

                for (let i = 0; i < len; i++) {
                    buffer[i] = binary.charCodeAt(i);
                }

                return new Blob([buffer], { type: mimeType });
            } catch (error) {
                console.error('Failed to decode export file', error);
                return null;
            }
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

        restoreDefaultTemplates: function(resetSelection) {
            if (!confirm('Restore the default product templates? This will overwrite the built-in templates.')) {
                return;
            }

            const button = $('#restore-default-templates');
            const originalHtml = button.html();
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Restoring...');

            $.post(this.ajax_url, {
                action: 'yadore_restore_default_templates',
                nonce: this.nonce,
                reset_selection: resetSelection ? 1 : 0
            }, (response) => {
                if (response && response.success) {
                    const data = response.data || {};
                    const created = Number(data.created) || 0;
                    const updated = Number(data.updated) || 0;
                    const message = data.message || 'Templates restored successfully.';
                    alert(`${message}\n${this.formatNumber(created)} created, ${this.formatNumber(updated)} updated.`);
                    this.loadToolStats();
                } else {
                    const error = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'Failed to restore templates.';
                    alert(error);
                }
            }).fail(() => {
                alert('Failed to restore templates.');
            }).always(() => {
                button.prop('disabled', false).html(originalHtml);
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

                    const schedule = data.schedule || {};
                    const nextRun = typeof schedule.next_run_human === 'string' ? schedule.next_run_human : '';
                    const fallback = Number(schedule.count) > 0 ? 'Scheduled exports are configured.' : '';
                    this.updateScheduleStatus(nextRun, fallback);
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

            if (Array.isArray(data.gemini_errors) && data.gemini_errors.length) {
                const errorLines = data.gemini_errors.map((entry) => {
                    const timestamp = entry.created_at ? `[${entry.created_at}]` : '';
                    const endpoint = entry.endpoint ? `Endpoint: ${entry.endpoint}` : '';
                    const message = entry.error_message || 'Gemini API error';
                    let response = '';

                    if (entry.response_body) {
                        let formatted = entry.response_body;
                        try {
                            const parsed = JSON.parse(entry.response_body);
                            formatted = JSON.stringify(parsed, null, 2);
                        } catch (err) {
                            formatted = entry.response_body;
                        }

                        response = `Response: ${formatted}`;
                    }

                    return [
                        [timestamp, message].filter(Boolean).join(' ').trim(),
                        endpoint,
                        response
                    ].filter(Boolean).join('\n');
                });

                sections.push('=== Gemini API Errors ===\n' + errorLines.join('\n\n'));
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
            }).done((response) => {
                if (response && response.success && response.data) {
                    const payload = response.data;
                    const services = Array.isArray(payload.services) ? payload.services : [];
                    const cssClass = this.getDiagnosticClass(payload.status);
                    let html = `<div class="${cssClass}">`;
                    html += '<h5>Connectivity Test Results</h5>';

                    if (services.length) {
                        html += '<ul>';
                        services.forEach((service) => {
                            const icon = this.getDiagnosticIcon(service.status);
                            const label = this.escapeHtml(service.label || '');
                            const message = this.escapeHtml(service.message || '');
                            html += `<li>${icon} <strong>${label}:</strong> ${message}</li>`;
                        });
                        html += '</ul>';
                    }

                    if (payload.message) {
                        html += `<p>${this.escapeHtml(payload.message)}</p>`;
                    }

                    html += '</div>';
                    resultsDiv.html(html);
                } else {
                    resultsDiv.html(`<div class="test-error"><p>${this.escapeHtml(response?.data || 'Connectivity test failed.')}</p></div>`);
                }
            }).fail(() => {
                resultsDiv.html('<div class="test-error"><p>Connectivity diagnostics are unavailable.</p></div>');
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
                if (response && response.success && response.data) {
                    const payload = response.data;
                    const message = payload.message || 'Database check passed.';
                    const cssClass = this.getDiagnosticClass(payload.status);
                    resultsDiv.html(`<div class="${cssClass}"><p>${this.escapeHtml(message)}</p></div>`);
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
                if (response && response.success && response.data) {
                    const payload = response.data;
                    const message = payload.message || 'Performance checks completed.';
                    const cssClass = this.getDiagnosticClass(payload.status);
                    resultsDiv.html(`<div class="${cssClass}"><p>${this.escapeHtml(message)}</p></div>`);
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
                if (response && response.success && response.data) {
                    const payload = response.data;
                    const message = payload.message || 'Cache analysis completed.';
                    const cssClass = this.getDiagnosticClass(payload.status);
                    resultsDiv.html(`<div class="${cssClass}"><p>${this.escapeHtml(message)}</p></div>`);
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

        getDiagnosticClass: function(status) {
            const normalized = (status || '').toString().toLowerCase();
            switch (normalized) {
                case 'critical':
                case 'error':
                    return 'test-error';
                case 'warning':
                    return 'test-warning';
                default:
                    return 'test-success';
            }
        },

        getDiagnosticIcon: function(status) {
            const normalized = (status || '').toString().toLowerCase();
            switch (normalized) {
                case 'critical':
                case 'error':
                    return '❌';
                case 'warning':
                    return '⚠️';
                default:
                    return '✅';
            }
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