<div class="yadore-products-inline" data-format="inline">
    <div class="inline-header">
        <h3>Empfehlung</h3>
        <div class="inline-subtitle">
            <?php if (get_option('yadore_ai_enabled', false)): ?>
                Persönliche Produktempfehlung basierend auf diesem Inhalt
            <?php else: ?>
                Sorgfältig ausgewähltes Angebot zu diesem Beitrag
            <?php endif; ?>
        </div>
    </div>

    <div class="inline-products">
        <?php if (!empty($offers)): ?>
            <?php foreach (array_slice($offers, 0, 1) as $offer): ?>
                <?php
                $price_parts = yadore_get_formatted_price_parts($offer['price'] ?? []);
                $price_amount = $price_parts['amount'] !== '' ? $price_parts['amount'] : 'N/A';
                $price_currency = $price_parts['currency'];
                if ($price_amount === 'N/A') {
                    $price_currency = '';
                }
                $click_url = esc_url($offer['clickUrl'] ?? '#');
                ?>
                <div class="inline-product"
                     data-offer-id="<?php echo esc_attr($offer['id'] ?? ''); ?>"
                     data-click-url="<?php echo $click_url; ?>"
                     role="link"
                     tabindex="0">
                    <div class="inline-image">
                        <?php
                        $image_url = $offer['thumbnail']['url'] ?? $offer['image']['url'] ?? '';
                        if (!empty($image_url)) :
                        ?>
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php echo esc_attr($offer['title'] ?? 'Product'); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="yadore-product-image-placeholder" aria-hidden="true">📦</div>
                        <?php endif; ?>
                    </div>

                    <div class="inline-details">
                        <h4 class="inline-title"><?php echo esc_html($offer['title'] ?? 'Product'); ?></h4>

                        <div class="inline-price-row">
                            <div class="inline-price">
                                <span class="inline-price-amount"><?php echo esc_html($price_amount); ?></span>
                                <?php if (!empty($price_currency)): ?>
                                    <span class="inline-price-currency"><?php echo esc_html($price_currency); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="inline-merchant">Verfügbar bei <?php echo esc_html($offer['merchant']['name'] ?? 'Online Store'); ?></div>

                        <a href="<?php echo $click_url; ?>"
                           class="inline-cta" target="_blank" rel="nofollow noopener"
                           data-yadore-click="<?php echo esc_attr($offer['id'] ?? ''); ?>">
                            Zum Angebot
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="inline-no-products">
                <p>Aktuell keine Empfehlung verfügbar.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="inline-disclaimer">
        <small>
            As an affiliate partner, we may earn from qualifying purchases. This helps support our content creation.
        </small>
    </div>
</div>