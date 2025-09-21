<div class="yadore-products-inline" data-format="inline">
    <div class="inline-header">
        <h3>Product Recommendations</h3>
        <div class="inline-subtitle">
            <?php if (get_option('yadore_ai_enabled', false)): ?>
                AI-powered product suggestions based on this content
            <?php else: ?>
                Curated products based on this post
            <?php endif; ?>
        </div>
    </div>

    <div class="inline-products">
        <?php if (!empty($offers)): ?>
            <?php foreach (array_slice($offers, 0, 3) as $offer): ?>
                <div class="inline-product" data-offer-id="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                    <div class="inline-image">
                        <img src="<?php echo esc_url($offer['thumbnail']['url'] ?? $offer['image']['url'] ?? 'https://via.placeholder.com/80x80'); ?>" 
                             alt="<?php echo esc_attr($offer['title'] ?? 'Product'); ?>" loading="lazy">
                    </div>

                    <div class="inline-details">
                        <h4 class="inline-title"><?php echo esc_html($offer['title'] ?? 'Product'); ?></h4>

                        <div class="inline-price-row">
                            <div class="inline-price"><?php echo esc_html($offer['price']['amount'] ?? 'N/A'); ?> <?php echo esc_html($offer['price']['currency'] ?? ''); ?></div>
                        </div>

                        <div class="inline-merchant">Available at <?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></div>

                        <a href="<?php echo esc_url($offer['clickUrl'] ?? '#'); ?>" 
                           class="inline-cta" target="_blank" rel="nofollow noopener"
                           data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                            View Product
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="inline-no-products">
                <p>No product recommendations available at this time.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="inline-disclaimer">
        <small>
            As an affiliate partner, we may earn from qualifying purchases. This helps support our content creation.
        </small>
    </div>
</div>