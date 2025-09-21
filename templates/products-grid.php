<div class="yadore-products-grid" data-format="grid">
    <?php if (!empty($offers)): ?>
        <?php foreach ($offers as $offer): ?>
            <div class="yadore-product-card" data-offer-id="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                <div class="product-image">
                    <img src="<?php echo esc_url($offer['thumbnail']['url'] ?? $offer['image']['url'] ?? 'https://via.placeholder.com/200x150'); ?>" 
                         alt="<?php echo esc_attr($offer['title'] ?? 'Product'); ?>" loading="lazy">

                    <?php if (!empty($offer['promoText'])): ?>
                        <div class="product-badge"><?php echo esc_html($offer['promoText']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="product-content">
                    <h3 class="product-title"><?php echo esc_html($offer['title'] ?? 'Product Title'); ?></h3>

                    <div class="product-price-section">
                        <div class="product-price">
                            <span class="price-amount"><?php echo esc_html($offer['price']['amount'] ?? 'N/A'); ?></span>
                            <span class="price-currency"><?php echo esc_html($offer['price']['currency'] ?? ''); ?></span>
                        </div>
                    </div>

                    <div class="product-merchant">
                        <span class="merchant-name"><?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></span>
                    </div>

                    <a href="<?php echo esc_url($offer['clickUrl'] ?? '#'); ?>" 
                       class="product-cta-button" target="_blank" rel="nofollow noopener"
                       data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                        View Product →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="yadore-no-products">
            <div class="no-products-icon">🔍</div>
            <h3>No products found</h3>
            <p>Please try different search terms or check back later.</p>
        </div>
    <?php endif; ?>
</div>