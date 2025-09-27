<?php
$color_style = '';
if (class_exists('YadoreMonetizer')) {
    $instance = YadoreMonetizer::get_instance();
    if ($instance instanceof YadoreMonetizer) {
        $color_style = $instance->get_template_color_style('shortcode');
    }
}
?>
<div class="yadore-products-list" data-format="list" <?php if ($color_style !== '') : ?>style="<?php echo esc_attr($color_style); ?>"<?php endif; ?>>
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
            <div class="yadore-product-item"
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
                </div>

                <div class="product-details">
                    <h3 class="product-title"><?php echo esc_html($offer['title'] ?? 'Product Title'); ?></h3>

                    <?php if (!empty($offer['description'])): ?>
                        <p class="product-description"><?php echo esc_html(wp_trim_words($offer['description'], 20)); ?></p>
                    <?php endif; ?>
                </div>

                <div class="product-pricing">
                    <div class="price-main">
                        <span class="list-price-amount"><?php echo esc_html($price_amount); ?></span>
                        <?php if (!empty($price_currency)): ?>
                            <span class="list-price-currency"><?php echo esc_html($price_currency); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="merchant-info">Verfügbar bei <?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></div>
                </div>

                <div class="product-action">
                    <a href="<?php echo $click_url; ?>"
                       class="list-cta-button" target="_blank" rel="nofollow noopener"
                       data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                        Zum Angebot
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