<?php
$color_style = '';
if (class_exists('YadoreMonetizer')) {
    $instance = YadoreMonetizer::get_instance();
    if ($instance instanceof YadoreMonetizer) {
        $color_style = $instance->get_template_color_style('shortcode');
    }
}
?>
<div class="yadore-products-grid" data-format="grid" <?php if ($color_style !== '') : ?>style="<?php echo esc_attr($color_style); ?>"<?php endif; ?>>
    <?php if (!empty($offers)): ?>
        <?php foreach ($offers as $offer): ?>
            <?php
            $price_parts = yadore_get_formatted_price_parts($offer['price'] ?? []);
            $price_amount = $price_parts['amount'] !== '' ? $price_parts['amount'] : 'N/A';
            $price_currency = $price_parts['currency'];
            if ($price_amount === 'N/A') {
                $price_currency = '';
            }
            $click_url = esc_url($offer['clickUrl'] ?? '#');
            ?>
            <div class="yadore-product-card"
                 data-offer-id="<?php echo esc_attr($offer['id'] ?? ''); ?>"
                 data-click-url="<?php echo $click_url; ?>"
                 role="link"
                 tabindex="0">
                <div class="product-image">
                    <?php
                    $image_url = $offer['image']['url'] ?? $offer['thumbnail']['url'] ?? '';
                    if (!empty($image_url)) :
                    ?>
                        <img src="<?php echo esc_url($image_url); ?>"
                             alt="<?php echo esc_attr($offer['title'] ?? 'Product'); ?>" loading="lazy">
                    <?php else : ?>
                        <div class="yadore-product-image-placeholder" aria-hidden="true">📦</div>
                    <?php endif; ?>

                    <?php if (!empty($offer['promoText'])): ?>
                        <div class="product-badge"><?php echo esc_html($offer['promoText']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="product-content">
                    <h3 class="product-title"><?php echo esc_html($offer['title'] ?? 'Product Title'); ?></h3>

                    <div class="product-price-section">
                        <div class="product-price">
                            <span class="price-amount"><?php echo esc_html($price_amount); ?></span>
                            <?php if (!empty($price_currency)): ?>
                                <span class="price-currency"><?php echo esc_html($price_currency); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="product-merchant">
                        <span class="merchant-name">Verfügbar bei <?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></span>
                    </div>

                    <a href="<?php echo $click_url; ?>"
                       class="product-cta-button yadore-cta-button" target="_blank" rel="nofollow noopener"
                       data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                        Zum Angebot →
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