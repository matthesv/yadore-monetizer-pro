/* Yadore Monetizer Pro v2.9.17 - Frontend JavaScript (Complete) */
(function($) {
    'use strict';

    // Global Yadore Frontend object
    window.yadoreFrontend = {
        version: '2.9.17',
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

            console.log('Yadore Monetizer Pro v2.9.17 Frontend - Initialized');
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

            // Track product views
            this.trackProductViews();
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

            $.post(this.settings.ajax_url, {
                action: 'yadore_get_overlay_products',
                nonce: this.settings.nonce,
                limit: this.settings.limit || 3,
                page_content: pageContent,
                page_url: window.location.href,
                post_id: this.settings.post_id || 0
            })
            .done((response) => {
                if (response.success && response.data.products && response.data.products.length > 0) {
                    this.renderOverlayProducts(response.data.products);
                    this.showOverlay();

                    // Track overlay view
                    this.trackOverlayView(response.data.keyword, response.data.products.length);
                } else {
                    // No products found
                    overlayBody.html(`
                        <div class="overlay-no-products">
                            <div class="no-products-icon">🔍</div>
                            <h3>No products found</h3>
                            <p>We couldn't find any relevant products for this content.</p>
                        </div>
                    `);
                }
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
            let productsHtml = '<div class="overlay-products">';

            products.forEach((product) => {
                const imageUrl = product.thumbnail?.url || product.image?.url || '';
                const title = product.title || 'Product';
                const price = product.price?.amount || 'N/A';
                const currency = product.price?.currency || '';
                const merchantName = product.merchant?.name || 'Online Store';
                const clickUrl = product.clickUrl || '#';
                const productId = product.id || '';

                const imageMarkup = imageUrl
                    ? `<img src="${imageUrl}" alt="${title}" loading="lazy">`
                    : '<div class="overlay-product-image-placeholder" aria-hidden="true">📦</div>';

                productsHtml += `
                    <div class="overlay-product" data-product-id="${productId}">
                        <div class="overlay-product-image">
                            ${imageMarkup}
                        </div>
                        <div class="overlay-product-content">
                            <h4 class="overlay-product-title">${title}</h4>
                            <div class="overlay-product-price">
                                <span class="overlay-price-amount">${price}</span>
                                <span class="overlay-price-currency">${currency}</span>
                            </div>
                            <div class="overlay-product-merchant">
                                <span class="overlay-merchant-name">${merchantName}</span>
                            </div>
                            <a href="${clickUrl}" 
                               class="overlay-product-button" 
                               target="_blank" 
                               rel="nofollow noopener"
                               data-yadore-click="${productId}">
                                View Product →
                            </a>
                        </div>
                    </div>
                `;
            });

            productsHtml += '</div>';

            // Add CSS for overlay products
            productsHtml += `
                <style>
                .overlay-products {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    max-width: 600px;
                    margin: 0 auto;
                }

                .overlay-product {
                    background: #f9f9f9;
                    border-radius: 12px;
                    overflow: hidden;
                    transition: transform 0.3s ease;
                }

                .overlay-product:hover {
                    transform: translateY(-4px);
                }

                .overlay-product-image img {
                    width: 100%;
                    height: 150px;
                    object-fit: cover;
                }

                .overlay-product-image-placeholder {
                    width: 100%;
                    height: 150px;
                    background: #ecf0f1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 32px;
                    color: #95a5a6;
                }

                .overlay-product-content {
                    padding: 16px;
                }

                .overlay-product-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin: 0 0 8px 0;
                    line-height: 1.3;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }

                .overlay-product-price {
                    margin-bottom: 8px;
                }

                .overlay-price-amount {
                    font-size: 18px;
                    font-weight: 700;
                    color: #27ae60;
                }

                .overlay-price-currency {
                    font-size: 14px;
                    color: #27ae60;
                }

                .overlay-product-merchant {
                    font-size: 12px;
                    color: #7f8c8d;
                    margin-bottom: 12px;
                }

                .overlay-product-button {
                    display: block;
                    width: 100%;
                    padding: 10px;
                    background: #3498db;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 13px;
                    text-align: center;
                    transition: background-color 0.3s ease;
                }

                .overlay-product-button:hover {
                    background: #2980b9;
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
                    .overlay-products {
                        grid-template-columns: 1fr;
                    }
                }
                </style>
            `;

            overlayBody.html(productsHtml);
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