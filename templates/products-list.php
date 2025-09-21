<div class="yadore-products-list" data-format="list">
    <?php if (!empty($offers)): ?>
        <?php foreach ($offers as $offer): ?>
            <div class="yadore-product-item" data-offer-id="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                <div class="product-image">
                    <img src="<?php echo esc_url($offer['thumbnail']['url'] ?? $offer['image']['url'] ?? 'https://via.placeholder.com/100x100'); ?>" 
                         alt="<?php echo esc_attr($offer['title'] ?? 'Product'); ?>" loading="lazy">
                </div>

                <div class="product-details">
                    <h3 class="product-title"><?php echo esc_html($offer['title'] ?? 'Product Title'); ?></h3>

                    <?php if (!empty($offer['description'])): ?>
                        <p class="product-description"><?php echo esc_html(wp_trim_words($offer['description'], 20)); ?></p>
                    <?php endif; ?>
                </div>

                <div class="product-pricing">
                    <div class="price-main"><?php echo esc_html($offer['price']['amount'] ?? 'N/A'); ?> <?php echo esc_html($offer['price']['currency'] ?? ''); ?></div>
                    <div class="merchant-info">at <?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></div>
                </div>

                <div class="product-action">
                    <a href="<?php echo esc_url($offer['clickUrl'] ?? '#'); ?>" 
                       class="list-cta-button" target="_blank" rel="nofollow noopener"
                       data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                        Buy Now
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="yadore-no-products-list">
            <p>No suitable products found for this content.</p>
        </div>
    <?php endif; ?>
</div>