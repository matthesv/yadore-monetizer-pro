<?php
if (!defined('ABSPATH')) {
    exit;
}

$color_groups = array(
    array(
        'title'  => esc_html__('Primärpalette', 'yadore-monetizer'),
        'tokens' => array(
            array('token' => '--yadore-color-primary-500', 'label' => 'Primary 500'),
            array('token' => '--yadore-color-primary-600', 'label' => 'Primary 600'),
            array('token' => '--yadore-color-primary-700', 'label' => 'Primary 700'),
            array('token' => '--yadore-gradient-primary', 'label' => 'Gradient Primary', 'gradient' => true),
        ),
    ),
    array(
        'title'  => esc_html__('Status & Feedback', 'yadore-monetizer'),
        'tokens' => array(
            array('token' => '--yadore-color-success-500', 'label' => 'Success 500'),
            array('token' => '--yadore-color-warning-500', 'label' => 'Warning 500'),
            array('token' => '--yadore-color-danger-500', 'label' => 'Danger 500'),
            array('token' => '--yadore-color-text-default', 'label' => 'Text / Default'),
        ),
    ),
);

$spacing_tokens = array(
    array(
        'token' => '--yadore-space-1',
        'label' => 'Space 1',
        'description' => esc_html__('Micro padding & chip breathing room', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-space-2',
        'label' => 'Space 2',
        'description' => esc_html__('Compact gaps between icons and labels', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-space-3',
        'label' => 'Space 3',
        'description' => esc_html__('Default component gap (12px)', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-space-4',
        'label' => 'Space 4',
        'description' => esc_html__('Card content padding (16px)', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-space-5',
        'label' => 'Space 5',
        'description' => esc_html__('Primary container padding (20px)', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-space-6',
        'label' => 'Space 6',
        'description' => esc_html__('Section spacing & grid gaps (24px)', 'yadore-monetizer'),
    ),
);

$radius_tokens = array(
    array(
        'token' => '--yadore-radius-sm',
        'label' => 'Radius SM',
        'description' => esc_html__('Badges & compact pills', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-radius-md',
        'label' => 'Radius MD',
        'description' => esc_html__('List rows & color swatches', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-radius-lg',
        'label' => 'Radius LG',
        'description' => esc_html__('Cards & intro panels', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-radius-pill',
        'label' => 'Radius Pill',
        'description' => esc_html__('Filter pills & badges', 'yadore-monetizer'),
    ),
);

$shadow_tokens = array(
    array(
        'token' => '--yadore-shadow-xs',
        'label' => 'Shadow XS',
        'description' => esc_html__('Focus outlines & subtle elevations', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-shadow-sm',
        'label' => 'Shadow SM',
        'description' => esc_html__('Card hover and light layering', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-shadow-md',
        'label' => 'Shadow MD',
        'description' => esc_html__('Feature callouts & hero sections', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-shadow-lg',
        'label' => 'Shadow LG',
        'description' => esc_html__('Modal overlays & spotlight states', 'yadore-monetizer'),
    ),
);

$typography_tokens = array(
    array(
        'token' => '--yadore-font-size-xl',
        'weight' => '--yadore-font-weight-semibold',
        'label' => esc_html__('Headline', 'yadore-monetizer'),
        'sample' => esc_html__('Designsystem Referenz', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-font-size-lg',
        'weight' => '--yadore-font-weight-semibold',
        'label' => esc_html__('Section Title', 'yadore-monetizer'),
        'sample' => esc_html__('SoTA 2025 Komponenten', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-font-size-md',
        'weight' => '--yadore-font-weight-regular',
        'label' => esc_html__('Body Copy', 'yadore-monetizer'),
        'sample' => esc_html__('Lesbare Beschreibungstexte für Tools & Karten.', 'yadore-monetizer'),
    ),
    array(
        'token' => '--yadore-font-size-sm',
        'weight' => '--yadore-font-weight-medium',
        'label' => esc_html__('Meta & Labels', 'yadore-monetizer'),
        'sample' => esc_html__('Token Hinweise & Accessibility Labels.', 'yadore-monetizer'),
    ),
);

$component_samples = array(
    array(
        'title' => esc_html__('Stat Card', 'yadore-monetizer'),
        'kind'  => esc_html__('Analytics', 'yadore-monetizer'),
        'description' => esc_html__('Nutze für KPIs mit Icon, Kennzahl und Label.', 'yadore-monetizer'),
        'preview' => '<div class="stat-card stat-primary"><div class="stat-icon"><span class="dashicons dashicons-visibility"></span></div><div class="stat-content"><div class="stat-number">1.248</div><div class="stat-label">Overlay-Aufrufe</div></div></div>',
        'accessibility' => esc_html__('Semantik: role="group" + aria-label für Kennzahlen', 'yadore-monetizer'),
    ),
    array(
        'title' => esc_html__('Yadore Card', 'yadore-monetizer'),
        'kind'  => esc_html__('Layout', 'yadore-monetizer'),
        'description' => esc_html__('Setze für Einstellungen, Tabellen oder Listen ein.', 'yadore-monetizer'),
        'preview' => '<div class="yadore-card"><div class="card-header"><h3><span class="dashicons dashicons-admin-settings"></span> Komponentenrichtlinien</h3></div><div class="card-content"><p>' . esc_html__('Verwende Tokens für Abstände, Farben und Schatten, um Konsistenz sicherzustellen.', 'yadore-monetizer') . '</p><span class="status-badge status-active">' . esc_html__('Konform', 'yadore-monetizer') . '</span></div></div>',
        'accessibility' => esc_html__('Semantik: section oder article mit eindeutiger Überschrift', 'yadore-monetizer'),
    ),
    array(
        'title' => esc_html__('Status Badge', 'yadore-monetizer'),
        'kind'  => esc_html__('Feedback', 'yadore-monetizer'),
        'description' => esc_html__('Schnelle Statuskommunikation für Prozesse & Checks.', 'yadore-monetizer'),
        'preview' => '<div class="status-badge status-warning">' . esc_html__('Beobachten', 'yadore-monetizer') . '</div>',
        'accessibility' => esc_html__('Semantik: <span> mit aria-live oder aria-label für dynamische Updates', 'yadore-monetizer'),
    ),
);

$component_snippet = <<<HTML
<div class="yadore-card" role="group" aria-labelledby="styleguide-example-heading">
    <div class="card-header">
        <h3 id="styleguide-example-heading"><span class="dashicons dashicons-screenoptions"></span> Dashboard Modul</h3>
        <span class="status-badge status-active">Aktiv</span>
    </div>
    <div class="card-content">
        <p>Nutze <code>var(--yadore-space-*)</code> und <code>var(--yadore-color-primary-*)</code>, um Abstände & Farben konsistent zu halten.</p>
        <button class="button button-primary"><span class="dashicons dashicons-admin-customizer"></span> Einstellungen öffnen</button>
    </div>
        </div>
    </div>
</div>
HTML;
?>

<div class="wrap yadore-admin-wrap">
    <?php
    $total_color_tokens = 0;
    foreach ($color_groups as $group) {
        $total_color_tokens += count($group['tokens']);
    }

    $total_token_count = $total_color_tokens
        + count($spacing_tokens)
        + count($radius_tokens)
        + count($shadow_tokens)
        + count($typography_tokens);

    $component_count = count($component_samples);
    $design_tokens_url = plugins_url('assets/css/admin-design-system.css', YADORE_PLUGIN_FILE);
    $styleguide_doc_url = plugins_url('docs/STYLEGUIDE.md', YADORE_PLUGIN_FILE);

    $styleguide_actions = array(
        array(
            'label' => esc_html__('Design Tokens öffnen', 'yadore-monetizer'),
            'url' => $design_tokens_url,
            'type' => 'secondary',
            'icon' => 'dashicons-media-code',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ),
        array(
            'label' => esc_html__('Styleguide-Dokumentation', 'yadore-monetizer'),
            'url' => $styleguide_doc_url,
            'type' => 'ghost',
            'icon' => 'dashicons-media-text',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ),
    );

    $styleguide_meta = array(
        array(
            'label' => esc_html__('Design Tokens', 'yadore-monetizer'),
            'value' => sprintf(__('%d Tokens', 'yadore-monetizer'), (int) $total_token_count),
            'description' => esc_html__('Farben, Abstände, Typografie und Schatten.', 'yadore-monetizer'),
            'icon' => 'dashicons-art',
            'state' => 'success',
        ),
        array(
            'label' => esc_html__('Komponenten', 'yadore-monetizer'),
            'value' => sprintf(__('%d Referenzen', 'yadore-monetizer'), (int) $component_count),
            'description' => esc_html__('UI-Bausteine für alle Backend-Module.', 'yadore-monetizer'),
            'icon' => 'dashicons-layout',
            'state' => 'info',
        ),
        array(
            'label' => esc_html__('Dokumentation', 'yadore-monetizer'),
            'value' => esc_html__('STYLEGUIDE.md', 'yadore-monetizer'),
            'description' => esc_html__('Versioniertes Living Design System.', 'yadore-monetizer'),
            'icon' => 'dashicons-admin-appearance',
            'state' => 'neutral',
        ),
    );

    $page_header = array(
        'slug' => 'styleguide',
        'eyebrow' => esc_html__('Design System', 'yadore-monetizer'),
        'icon' => 'dashicons-admin-appearance',
        'title' => esc_html__('Yadore Monetizer Pro – Styleguide', 'yadore-monetizer'),
        'subtitle' => esc_html__('Token-basierte UI-Bibliothek für ein konsistentes 2025er Backend-Erlebnis.', 'yadore-monetizer'),
        'version' => YADORE_PLUGIN_VERSION,
        'actions' => $styleguide_actions,
        'meta' => $styleguide_meta,
    );
    ?>

    <div class="yadore-admin-shell">
        <?php include __DIR__ . '/partials/admin-page-header.php'; ?>

        <div class="yadore-admin-content">
            <div class="styleguide-meta">
                <span class="styleguide-chip"><?php esc_html_e('SoTA 2025 Ready', 'yadore-monetizer'); ?></span>
                <span><?php esc_html_e('Design Tokens, Komponenten und Accessibility-Guidelines für alle Backend-Ansichten.', 'yadore-monetizer'); ?></span>
            </div>

            <div class="yadore-styleguide">
        <section class="styleguide-section">
            <h2><?php esc_html_e('Designgrundsätze', 'yadore-monetizer'); ?></h2>
            <div class="styleguide-legend">
                <p><?php esc_html_e('Alle Admin-Oberflächen orientieren sich an einem token-basierten System mit Fokus auf Klarheit, Lesbarkeit und hoher Kontrastwirkung.', 'yadore-monetizer'); ?></p>
                <p><?php echo wp_kses(sprintf(__('Token-Definitionen: <code>%s</code>', 'yadore-monetizer'), 'assets/css/admin-design-system.css'), array('code' => array())); ?></p>
                <p><?php echo wp_kses(sprintf(__('Dokumentation & Änderungsprozess: <code>%s</code>', 'yadore-monetizer'), 'docs/STYLEGUIDE.md'), array('code' => array())); ?></p>
            </div>
        </section>

        <section class="styleguide-section">
            <h2><?php esc_html_e('Farb- und Status-Tokens', 'yadore-monetizer'); ?></h2>
            <div class="token-grid">
                <?php foreach ($color_groups as $group) : ?>
                    <div class="token-card">
                        <strong><?php echo esc_html($group['title']); ?></strong>
                        <div class="component-grid">
                            <?php foreach ($group['tokens'] as $token) : ?>
                                <?php
                                $is_gradient = !empty($token['gradient']);
                                $background = $is_gradient
                                    ? 'var(--yadore-gradient-primary)'
                                    : sprintf('var(%s)', sanitize_text_field($token['token']));
                                ?>
                                <div class="token-item" data-token="<?php echo esc_attr($token['token']); ?>">
                                    <div class="color-swatch" style="background: <?php echo esc_attr($background); ?>;">
                                        <span><?php echo esc_html($token['label']); ?></span>
                                    </div>
                                    <span class="token-value" data-token-value><?php echo esc_html($token['token']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="styleguide-section">
            <h2><?php esc_html_e('Spacing, Radien & Schatten', 'yadore-monetizer'); ?></h2>
            <div class="token-grid">
                <div class="token-card">
                    <strong><?php esc_html_e('Spacing-Skala', 'yadore-monetizer'); ?></strong>
                    <?php foreach ($spacing_tokens as $token) : ?>
                        <div class="token-item" data-token="<?php echo esc_attr($token['token']); ?>">
                            <div class="token-measure">
                                <span><?php echo esc_html($token['label']); ?></span>
                                <div class="measure-bar" style="--token-size: var(<?php echo esc_attr($token['token']); ?>);"></div>
                            </div>
                            <span class="token-value" data-token-value><?php echo esc_html($token['token']); ?></span>
                            <span class="token-value"><?php echo esc_html($token['description']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="token-card">
                    <strong><?php esc_html_e('Rundungen', 'yadore-monetizer'); ?></strong>
                    <?php foreach ($radius_tokens as $token) : ?>
                        <div class="token-item" data-token="<?php echo esc_attr($token['token']); ?>">
                            <div class="radius-preview" style="--token-radius: var(<?php echo esc_attr($token['token']); ?>);"></div>
                            <span class="token-value" data-token-value><?php echo esc_html($token['token']); ?></span>
                            <span class="token-value"><?php echo esc_html($token['description']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="token-card">
                    <strong><?php esc_html_e('Schatten', 'yadore-monetizer'); ?></strong>
                    <?php foreach ($shadow_tokens as $token) : ?>
                        <div class="token-item" data-token="<?php echo esc_attr($token['token']); ?>">
                            <div class="shadow-preview" style="--token-shadow: var(<?php echo esc_attr($token['token']); ?>);"></div>
                            <span class="token-value" data-token-value><?php echo esc_html($token['token']); ?></span>
                            <span class="token-value"><?php echo esc_html($token['description']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="styleguide-section">
            <h2><?php esc_html_e('Typografie', 'yadore-monetizer'); ?></h2>
            <div class="token-grid">
                <div class="token-card">
                    <strong><?php esc_html_e('Skala & Gewichte', 'yadore-monetizer'); ?></strong>
                    <?php foreach ($typography_tokens as $token) : ?>
                        <div class="token-item" data-token="<?php echo esc_attr($token['token']); ?>">
                            <div style="font-size: var(<?php echo esc_attr($token['token']); ?>); font-weight: var(<?php echo esc_attr($token['weight']); ?>);">
                                <?php echo esc_html($token['sample']); ?>
                            </div>
                            <span class="token-value" data-token-value><?php echo esc_html($token['token']); ?></span>
                            <span class="token-value"><?php echo esc_html($token['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="styleguide-section">
            <h2><?php esc_html_e('Komponentenbibliothek', 'yadore-monetizer'); ?></h2>
            <div class="component-grid">
                <?php foreach ($component_samples as $component) : ?>
                    <div class="styleguide-component-card">
                        <header>
                            <h3><?php echo esc_html($component['title']); ?></h3>
                            <span class="styleguide-chip"><?php echo esc_html($component['kind']); ?></span>
                        </header>
                        <div class="preview"><?php echo wp_kses_post($component['preview']); ?></div>
                        <p><?php echo esc_html($component['description']); ?></p>
                        <footer>
                            <span><?php echo esc_html__('Guideline:', 'yadore-monetizer'); ?> <?php echo esc_html($component['accessibility']); ?></span>
                            <span><?php esc_html_e('Nutze Tokens für Farben, Abstände & Schatten.', 'yadore-monetizer'); ?></span>
                        </footer>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="styleguide-section">
                <h2><?php esc_html_e('Code-Beispiel', 'yadore-monetizer'); ?></h2>
                <p><?php esc_html_e('Dieses Snippet zeigt den Aufbau einer Karte mit Buttons und Status-Badge. Kopiere den Code und ersetze Inhalte oder Icons entsprechend deiner Funktion.', 'yadore-monetizer'); ?></p>
                <div style="position: relative;">
                    <pre class="styleguide-code"><code id="styleguide-components-snippet"><?php echo esc_html(trim($component_snippet)); ?></code></pre>
                    <button type="button" class="styleguide-copy" data-yadore-copy="styleguide-components-snippet">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php esc_html_e('Code kopieren', 'yadore-monetizer'); ?>
                    </button>
                </div>
            </div>
        </section>
    </div>
        </div>
    </div>
</div>
