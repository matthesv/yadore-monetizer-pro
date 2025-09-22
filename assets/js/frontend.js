/* Yadore Monetizer Pro v2.9.22 - Frontend JavaScript (Complete) */
(function($) {
    'use strict';

    // Global Yadore Frontend object
    window.yadoreFrontend = {
        version: '2.9.22',
        settings: window.yadore_ajax || {},
        overlay: null,
        isOverlayVisible: false,
        lastScrollY: 0,
        scrollThreshold: 300,

        // Initialize frontend functionality
        init: function() {
            if (typeof this.settings.ajax_url === 'undefined') {
                console.warn('Yadore Monetizer: Frontend settings not loaded properly');
                return;
            }

            this.scrollThreshold = parseInt(this.settings.scroll_threshold) || 300;

            this.initOverlay();
            this.initProductTracking();
            this.initScrollTriggers();
            this.initResponsiveHandling();

            console.log('Yadore Monetizer Pro v2.9.22 Frontend - Initialized');
        },

        // Initialize product overlay
        initOverlay: function() {
            if (!this.settings.overlay_enabled || typeof this.settings.overlay_enabled === 'undefined') {
                return;
            }

            this.overlay = $('#yadore-overlay-banner');

            if (this.overlay.length === 0) {
                console.warn('Yadore Monetizer: Overlay element not found');
                return;
            }

            // Close overlay event
            $('#yadore-overlay-close, #yadore-overlay-backdrop').on('click', (e) => {
                e.preventDefault();
                this.hideOverlay();
            });

            // Escape key to close
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOverlayVisible) {
                    this.hideOverlay();
                }
            });

            // Prevent body scroll when overlay is open
            this.overlay.on('wheel touchmove', (e) => {
                if (this.isOverlayVisible) {
                    e.preventDefault();
                }
            });
        },

        // Initialize product click tracking
        initProductTracking: function() {
            $(document).on('click', '[data-yadore-click]', (e) => {
                const productId = $(e.currentTarget).data('yadore-click');
                this.trackProductClick(productId);
            });

            this.bindProductCardClicks();

            // Track product views
            this.trackProductViews();
        },

        // Make entire product cards behave like links
        bindProductCardClicks: function() {
            const clickableSelector = '.inline-product[data-click-url], .yadore-product-card[data-click-url], .yadore-product-item[data-click-url], .overlay-product[data-click-url]';

            $(document).on('click', clickableSelector, (event) => {
                const $target = $(event.target);

                if ($target.closest('a').length) {
                    return;
                }

                const $currentTarget = $(event.currentTarget);
                let url = $currentTarget.data('click-url');

                if (typeof url === 'string') {
                    url = url.trim();
                    url = this.decodeHtmlEntities(url);
                }

                if (!url || url === '#') {
                    return;
                }

                event.preventDefault();

                this.openProductInNewTab(url);

                const productId = $currentTarget.data('offer-id') ||
                    $currentTarget.data('product-id') ||
                    $currentTarget.data('yadore-click');

                if (productId) {
                    this.trackProductClick(productId);
                }
            });

            $(document).on('keydown', clickableSelector, (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    $(event.currentTarget).trigger('click');
                }
            });
        },

        openProductInNewTab: function(url) {
            const newWindow = window.open(url, '_blank', 'noopener,noreferrer');

            if (newWindow && typeof newWindow.focus === 'function') {
                newWindow.focus();
            }
        },

        formatPriceForDisplay: function(price, currency) {
            let amountValue = price;
            let currencyValue = currency || '';

            if (price && typeof price === 'object') {
                amountValue = price.amount ?? '';
                if (!currencyValue && price.currency) {
                    currencyValue = price.currency;
                }
            }

            currencyValue = (currencyValue || '').toString().trim().toUpperCase();
            let formattedAmount = '';

            if (amountValue !== undefined && amountValue !== null && amountValue !== '') {
                const rawAmount = amountValue.toString();
                const sanitizedAmount = rawAmount.replace(/[^0-9,.-]/g, '');
                const normalizedAmount = sanitizedAmount.replace(',', '.');
                const numericAmount = parseFloat(normalizedAmount);

                if (!Number.isNaN(numericAmount)) {
                    try {
                        formattedAmount = new Intl.NumberFormat('de-DE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(numericAmount);
                    } catch (err) {
                        formattedAmount = numericAmount.toFixed(2).replace('.', ',');
                    }
                } else {
                    formattedAmount = rawAmount;
                }
            }

            if (!formattedAmount) {
                formattedAmount = 'N/A';
            }

            if (formattedAmount === 'N/A') {
                currencyValue = '';
            }

            return {
                amount: formattedAmount,
                currency: currencyValue
            };
        },

        decodeHtmlEntities: function(value) {
            if (typeof value !== 'string' || value.indexOf('&') === -1) {
                return value;
            }

            const textarea = document.createElement('textarea');
            textarea.innerHTML = value;
            return textarea.value;
        },

        // Initialize scroll-based triggers
        initScrollTriggers: function() {
            let scrollTimeout;
            let hasTriggered = false;

            $(window).on('scroll', () => {
                if (scrollTimeout) clearTimeout(scrollTimeout);

                scrollTimeout = setTimeout(() => {
                    const scrollY = window.pageYOffset || document.documentElement.scrollTop;

                    // Trigger overlay on scroll threshold
                    if (!hasTriggered && scrollY > this.scrollThreshold && this.settings.overlay_enabled) {
                        hasTriggered = true;
                        this.triggerOverlay();
                    }

                    this.lastScrollY = scrollY;
                }, 100);
            });

            // Auto-trigger after delay if enabled
            if (this.settings.delay && parseInt(this.settings.delay) > 0) {
                setTimeout(() => {
                    if (!hasTriggered && this.settings.overlay_enabled) {
                        hasTriggered = true;
                        this.triggerOverlay();
                    }
                }, parseInt(this.settings.delay));
            }
        },

        // Initialize responsive handling
        initResponsiveHandling: function() {
            $(window).on('resize', () => {
                this.handleResponsiveChanges();
            });

            // Initial responsive setup
            this.handleResponsiveChanges();
        },

        // Trigger overlay display
        triggerOverlay: function() {
            if (!this.settings.overlay_enabled || this.isOverlayVisible) {
                return;
            }

            this.loadOverlayProducts();
        },

        // Load products for overlay
        loadOverlayProducts: function() {
            const overlayBody = this.overlay.find('.overlay-body');

            // Show loading state
            overlayBody.html(`
                <div class="overlay-loading">
                    <div class="loading-spinner"></div>
                    <p>Finding relevant products...</p>
                    <small>Analyzing page content...</small>
                </div>
            `);

            // Get page content for analysis
            const pageContent = this.extractPageContent();
            const requestedLimit = parseInt(this.settings.limit, 10);
            const productLimit = Number.isNaN(requestedLimit) || requestedLimit < 1 ? 1 : requestedLimit;

            $.post(this.settings.ajax_url, {
                action: 'yadore_get_overlay_products',
                nonce: this.settings.nonce,
                limit: productLimit,
                page_content: pageContent,
                page_url: window.location.href,
                post_id: this.settings.post_id || 0
            })
            .done((response) => {
                if (response.success) {
                    const payload = response.data || {};
                    const displayCount = parseInt(payload.display_count, 10) || 0;
                    const html = typeof payload.html === 'string' ? payload.html : '';

                    if (html) {
                        overlayBody.html(html);
                    } else if (payload.products && payload.products.length > 0) {
                        const fallbackCount = this.renderOverlayProducts(payload.products);
                        this.showOverlay();
                        this.trackOverlayView(payload.keyword, fallbackCount);
                        return;
                    } else {
                        overlayBody.html(`
                            <div class="overlay-no-products">
                                <div class="no-products-icon">🔍</div>
                                <h3>No products found</h3>
                                <p>We couldn't find any relevant products for this content.</p>
                            </div>
                        `);
                    }

                    if (displayCount > 0) {
                        this.showOverlay();
                        this.trackOverlayView(payload.keyword, displayCount);
                    }

                    return;
                }

                overlayBody.html(`
                    <div class="overlay-no-products">
                        <div class="no-products-icon">🔍</div>
                        <h3>No products found</h3>
                        <p>We couldn't find any relevant products for this content.</p>
                    </div>
                `);
            })
            .fail((xhr, status, error) => {
                console.error('Yadore Monetizer: Failed to load overlay products', error);
                overlayBody.html(`
                    <div class="overlay-error">
                        <div class="error-icon">⚠️</div>
                        <h3>Loading Error</h3>
                        <p>Unable to load product recommendations. Please try again later.</p>
                    </div>
                `);
            });
        },

        // Render products in overlay
        renderOverlayProducts: function(products) {
            const overlayBody = this.overlay.find('.overlay-body');
            const displayProducts = Array.isArray(products) ? products.slice(0, 1) : [];
            let productsHtml = '<div class="overlay-products">';

            const sanitize = (value) => $('<div/>').text(value || '').html();

            displayProducts.forEach((product) => {
                const imageUrl = product.thumbnail?.url || product.image?.url || '';
                const productId = sanitize(product.id || '');
                const clickUrlRaw = product.clickUrl || '#';
                const clickUrl = sanitize(clickUrlRaw);
                const priceParts = this.formatPriceForDisplay(product.price);
                const priceAmount = sanitize(priceParts.amount);
                const priceCurrency = sanitize(priceParts.currency);
                const title = sanitize(product.title || 'Produkt');
                const merchantName = sanitize(product.merchant?.name || 'Online Store');

                const imageMarkup = imageUrl
                    ? `<img src="${sanitize(imageUrl)}" alt="${title}" loading="lazy">`
                    : '<div class="overlay-product-image-placeholder" aria-hidden="true">📦</div>';

                productsHtml += `
                    <div class="overlay-product"
                         data-product-id="${productId}"
                         data-click-url="${clickUrl}"
                         role="link"
                         tabindex="0">
                        <div class="overlay-product-image">
                            ${imageMarkup}
                        </div>
                        <div class="overlay-product-content">
                            <h4 class="overlay-product-title">${title}</h4>
                            <div class="overlay-product-price">
                                <span class="overlay-price-amount">${priceAmount}</span>
                                ${priceCurrency ? `<span class="overlay-price-currency">${priceCurrency}</span>` : ''}
                            </div>
                            <div class="overlay-product-merchant">
                                <span class="overlay-merchant-name">Verfügbar bei ${merchantName}</span>
                            </div>
                            <a href="${clickUrl}"
                               class="overlay-product-button"
                               target="_blank"
                               rel="nofollow noopener"
                               data-yadore-click="${productId}">
                                Zum Angebot →
                            </a>
                        </div>
                    </div>
                `;
            });

            productsHtml += '</div>';

            productsHtml += `
                <style>
                .overlay-products {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 20px;
                    max-width: 420px;
                    margin: 0 auto;
                }

                .overlay-product {
                    background: #f9f9f9;
                    border-radius: 12px;
                    overflow: hidden;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    border: 1px solid #e9ecef;
                    cursor: pointer;
                }

                .overlay-product:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                }

                .overlay-product:focus-within,
                .overlay-product:focus {
                    outline: 2px solid #3498db;
                    outline-offset: 3px;
                }

                .overlay-product-image img {
                    width: 100%;
                    height: 160px;
                    object-fit: cover;
                }

                .overlay-product-image-placeholder {
                    width: 100%;
                    height: 160px;
                    background: #ecf0f1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    color: #95a5a6;
                }

                .overlay-product-content {
                    padding: 18px 20px;
                }

                .overlay-product-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin: 0 0 10px 0;
                    line-height: 1.4;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }

                .overlay-product-price {
                    margin-bottom: 10px;
                }

                .overlay-price-amount {
                    font-size: 20px;
                    font-weight: 700;
                    color: #27ae60;
                }

                .overlay-price-currency {
                    font-size: 14px;
                    font-weight: 600;
                    margin-left: 6px;
                    color: #27ae60;
                    text-transform: uppercase;
                }

                .overlay-product-merchant {
                    font-size: 13px;
                    color: #7f8c8d;
                    margin-bottom: 16px;
                }

                .overlay-product-button {
                    display: block;
                    width: 100%;
                    padding: 12px 16px;
                    background: linear-gradient(135deg, #3498db, #2980b9);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    text-align: center;
                    transition: background 0.3s ease, transform 0.3s ease;
                }

                .overlay-product-button:hover {
                    background: linear-gradient(135deg, #2980b9, #21618c);
                    transform: translateY(-2px);
                    color: white;
                    text-decoration: none;
                }

                .overlay-no-products,
                .overlay-error {
                    text-align: center;
                    padding: 40px 20px;
                    color: #7f8c8d;
                }

                .no-products-icon,
                .error-icon {
                    font-size: 48px;
                    margin-bottom: 16px;
                    opacity: 0.6;
                }

                .overlay-no-products h3,
                .overlay-error h3 {
                    margin: 0 0 8px 0;
                    color: #6c757d;
                }

                .overlay-error {
                    color: #d63638;
                }

                @media (max-width: 480px) {
                    .overlay-product-content {
                        padding: 16px;
                    }

                    .overlay-product-title {
                        font-size: 15px;
                    }
                }
                </style>
            `;

            overlayBody.html(productsHtml);

            return displayProducts.length;
        },

        // Show overlay
        showOverlay: function() {
            if (this.isOverlayVisible) return;

            $('body').addClass('yadore-overlay-active');
            this.overlay.show();

            // Trigger animation
            setTimeout(() => {
                this.overlay.addClass('active');
                this.isOverlayVisible = true;
            }, 10);

            // Prevent body scroll
            const scrollY = window.pageYOffset;
            $('body').css({
                'position': 'fixed',
                'top': `-${scrollY}px`,
                'width': '100%'
            });
        },

        // Hide overlay
        hideOverlay: function() {
            if (!this.isOverlayVisible) return;

            this.overlay.removeClass('active');

            setTimeout(() => {
                this.overlay.hide();
                this.isOverlayVisible = false;

                // Restore body scroll
                const scrollY = parseInt($('body').css('top')) || 0;
                $('body').removeClass('yadore-overlay-active').css({
                    'position': '',
                    'top': '',
                    'width': ''
                });

                window.scrollTo(0, Math.abs(scrollY));
            }, 300);
        },

        // Extract page content for analysis
        extractPageContent: function() {
            // Get main content selectors
            const contentSelectors = [
                '.entry-content',
                '.post-content', 
                '.content',
                'article',
                'main',
                '.single-post',
                '.post'
            ];

            let content = '';

            for (const selector of contentSelectors) {
                const element = $(selector).first();
                if (element.length) {
                    content = element.text().substring(0, 1000); // Limit to 1000 chars
                    break;
                }
            }

            // Fallback to title and meta description
            if (!content) {
                content = $(document).find('title').text() + ' ' + 
                         $('meta[name="description"]').attr('content');
            }

            return content.trim();
        },

        // Track product click
        trackProductClick: function(productId) {
            if (!productId) return;

            $.post(this.settings.ajax_url, {
                action: 'yadore_track_product_click',
                nonce: this.settings.nonce,
                product_id: productId,
                page_url: window.location.href
            }).done((response) => {
                console.log('Yadore Monetizer: Product click tracked', productId);
            }).fail((error) => {
                console.warn('Yadore Monetizer: Failed to track product click', error);
            });
        },

        // Track overlay view
        trackOverlayView: function(keyword, productCount) {
            $.post(this.settings.ajax_url, {
                action: 'yadore_track_overlay_view',
                nonce: this.settings.nonce,
                keyword: keyword,
                product_count: productCount,
                page_url: window.location.href
            }).done((response) => {
                console.log('Yadore Monetizer: Overlay view tracked');
            }).fail((error) => {
                console.warn('Yadore Monetizer: Failed to track overlay view', error);
            });
        },

        // Track product views
        trackProductViews: function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const productElement = $(entry.target);
                        const productId = productElement.data('offer-id') || productElement.data('product-id');

                        if (productId && !productElement.data('viewed')) {
                            productElement.data('viewed', true);
                            this.trackProductView(productId);
                        }
                    }
                });
            }, {
                threshold: 0.5,
                rootMargin: '0px'
            });

            // Observe all product elements
            $('.yadore-product-card, .yadore-product-item, .inline-product').each((index, element) => {
                observer.observe(element);
            });
        },

        // Track single product view
        trackProductView: function(productId) {
            $.post(this.settings.ajax_url, {
                action: 'yadore_track_product_view',
                nonce: this.settings.nonce,
                product_id: productId,
                page_url: window.location.href
            }).done((response) => {
                console.log('Yadore Monetizer: Product view tracked', productId);
            }).fail((error) => {
                console.warn('Yadore Monetizer: Failed to track product view', error);
            });
        },

        // Handle responsive changes
        handleResponsiveChanges: function() {
            const isMobile = window.innerWidth <= 768;

            if (isMobile) {
                // Adjust overlay for mobile
                $('#yadore-overlay-content').css({
                    'max-width': '95vw',
                    'max-height': '95vh'
                });

                // Adjust scroll threshold for mobile
                this.scrollThreshold = Math.min(this.scrollThreshold, 200);
            } else {
                // Reset for desktop
                $('#yadore-overlay-content').css({
                    'max-width': '90vw',
                    'max-height': '90vh'
                });
            }
        },

        // Utility functions
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Error handling
        handleError: function(error, context) {
            console.error(`Yadore Monetizer Error (${context}):`, error);

            if (this.settings.debug) {
                // Send error to admin for debugging
                $.post(this.settings.ajax_url, {
                    action: 'yadore_log_frontend_error',
                    nonce: this.settings.nonce,
                    error: error.toString(),
                    context: context,
                    url: window.location.href,
                    user_agent: navigator.userAgent
                });
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        try {
            window.yadoreFrontend.init();
        } catch (error) {
            console.error('Yadore Monetizer: Initialization failed', error);
        }
    });

    // Expose to global scope
    window.yadore = window.yadoreFrontend;

})(jQuery);

// Polyfills for older browsers
if (!window.IntersectionObserver) {
    // Simple fallback for IntersectionObserver
    window.IntersectionObserver = function(callback, options) {
        this.callback = callback;
        this.options = options || {};
        this.elements = [];

        this.observe = function(element) {
            this.elements.push(element);
            // Simple visibility check
            this.callback([{
                target: element,
                isIntersecting: this.isElementInViewport(element)
            }]);
        };

        this.unobserve = function(element) {
            const index = this.elements.indexOf(element);
            if (index > -1) {
                this.elements.splice(index, 1);
            }
        };

        this.isElementInViewport = function(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        };
    };
}