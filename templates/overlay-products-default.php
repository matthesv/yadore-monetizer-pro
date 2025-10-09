<?php
if (!defined('ABSPATH')) {
    exit;
}

$products = isset($products) && is_array($products) ? $products : array();
$button_label = isset($button_label) ? (string) $button_label : __('Zum Angebot →', 'yadore-monetizer');
$color_style = '';
if (class_exists('YadoreMonetizer')) {
    $instance = YadoreMonetizer::get_instance();
    if ($instance instanceof YadoreMonetizer) {
        $color_style = $instance->get_template_color_style('overlay');
    }
}

if (empty($products)) :
    ?>
    <div class="overlay-no-products">
        <div class="no-products-icon">🔍</div>
        <h3><?php echo esc_html__('No products found', 'yadore-monetizer'); ?></h3>
        <p><?php echo esc_html__('We couldn\'t find any relevant products for this content.', 'yadore-monetizer'); ?></p>
    </div>
    <?php
    return;
endif;
?>
<div class="overlay-products" <?php if ($color_style !== '') : ?>style="<?php echo esc_attr($color_style); ?>"<?php endif; ?>>
    <?php foreach ($products as $product) :
        $price_parts = yadore_get_formatted_price_parts($product['price'] ?? array());
        $price_amount = $price_parts['amount'] !== '' ? $price_parts['amount'] : 'N/A';
        $price_currency = $price_parts['currency'];
        if ($price_amount === 'N/A') {
            $price_currency = '';
        }

        $click_url = esc_url($product['clickUrl'] ?? '#');
        $product_id = esc_attr($product['id'] ?? '');
        $title = esc_html($product['title'] ?? __('Product', 'yadore-monetizer'));
        $merchant_name = esc_html($product['merchant']['name'] ?? __('Online Store', 'yadore-monetizer'));
        $promo_text = esc_html($product['promoText'] ?? '');

        $image_url = '';
        if (!empty($product['image']['url'])) {
            $image_url = esc_url($product['image']['url']);
        } elseif (!empty($product['thumbnail']['url'])) {
            $image_url = esc_url($product['thumbnail']['url']);
        }
        ?>
        <div class="overlay-product"
             data-product-id="<?php echo $product_id; ?>"
             data-click-url="<?php echo $click_url; ?>"
             data-yadore-click="<?php echo $product_id; ?>"
             role="link"
             tabindex="0">
            <div class="overlay-product-image">
                <?php if ($image_url !== '') : ?>
                    <img src="<?php echo $image_url; ?>" alt="<?php echo $title; ?>" loading="lazy">
                <?php else : ?>
                    <div class="overlay-product-image-placeholder" aria-hidden="true">📦</div>
                <?php endif; ?>

                <?php if ($promo_text !== '') : ?>
                    <div class="overlay-product-badge"><?php echo $promo_text; ?></div>
                <?php endif; ?>
            </div>
            <div class="overlay-product-content">
                <h4 class="overlay-product-title"><?php echo $title; ?></h4>
                <div class="overlay-product-price">
                    <span class="overlay-price-amount"><?php echo esc_html($price_amount); ?></span>
                    <?php if ($price_currency !== '') : ?>
                        <span class="overlay-price-currency"><?php echo esc_html($price_currency); ?></span>
                    <?php endif; ?>
                </div>
                <div class="overlay-product-merchant">
                    <span class="overlay-merchant-name"><?php echo sprintf(esc_html__('Available at %s', 'yadore-monetizer'), $merchant_name); ?></span>
                </div>
                <a href="<?php echo $click_url; ?>"
                   class="overlay-product-button yadore-cta-button"
                   target="_blank"
                   rel="nofollow noopener"
                   data-yadore-click="<?php echo $product_id; ?>">
                    <?php echo esc_html($button_label); ?>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
