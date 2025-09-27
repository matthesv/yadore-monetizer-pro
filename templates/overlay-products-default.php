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
                   class="overlay-product-button"
                   target="_blank"
                   rel="nofollow noopener"
                   data-yadore-click="<?php echo $product_id; ?>">
                    <?php echo esc_html($button_label); ?>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<style>
.overlay-products {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    max-width: 420px;
    margin: 0 auto;
    background-color: var(--yadore-background, transparent);
}

.overlay-product {
    background: var(--yadore-card-bg, #f9f9f9);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid var(--yadore-border, #e9ecef);
    cursor: pointer;
}

.overlay-product:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.overlay-product:focus-within,
.overlay-product:focus {
    outline: 2px solid var(--yadore-primary, #3498db);
    outline-offset: 3px;
}

.overlay-product-image img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.overlay-product-image {
    position: relative;
}

.overlay-product-image-placeholder {
    width: 100%;
    height: 160px;
    background: var(--yadore-placeholder, #ecf0f1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: var(--yadore-placeholder-text, #95a5a6);
}

.overlay-product-content {
    padding: 18px 20px;
}

.overlay-product-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--yadore-text, #2c3e50);
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
    color: var(--yadore-accent, #27ae60);
}

.overlay-price-currency {
    font-size: 14px;
    font-weight: 600;
    margin-left: 6px;
    color: var(--yadore-accent, #27ae60);
    text-transform: uppercase;
}

.overlay-product-merchant {
    font-size: 13px;
    color: var(--yadore-muted, #7f8c8d);
    margin-bottom: 16px;
}

.overlay-product-button {
    display: block;
    width: 100%;
    padding: 12px 16px;
    background: linear-gradient(135deg, var(--yadore-primary-light, #5dade2), var(--yadore-primary-dark, #2980b9));
    color: var(--yadore-primary-contrast, #ffffff);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
    transition: background 0.3s ease, transform 0.3s ease;
}

.overlay-product-button:hover {
    background: linear-gradient(135deg, var(--yadore-primary-dark, #2980b9), var(--yadore-primary-darker, #21618c));
    transform: translateY(-2px);
    color: var(--yadore-primary-contrast, #ffffff);
    text-decoration: none;
}

.overlay-product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--yadore-badge-rgba, rgba(52, 152, 219, 0.95));
    color: var(--yadore-badge-text, #ffffff);
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
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
