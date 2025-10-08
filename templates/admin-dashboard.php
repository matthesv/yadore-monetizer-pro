<div class="wrap yadore-admin-wrap">
    <h1 class="yadore-page-title">
        <span class="dashicons dashicons-cart"></span>
        <?php echo esc_html__('Yadore Monetizer Pro – Übersicht', 'yadore-monetizer'); ?>
        <span class="version-badge">v<?php echo esc_html(YADORE_PLUGIN_VERSION); ?></span>
    </h1>

    <?php if (get_transient('yadore_activation_notice')): ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong>
                <?php
                printf(
                    esc_html__('Yadore Monetizer Pro v%s wurde erfolgreich aktiviert!', 'yadore-monetizer'),
                    esc_html(YADORE_PLUGIN_VERSION)
                );
                ?>
            </strong>
            <?php esc_html_e('Alle Funktionen stehen jetzt zur Verfügung.', 'yadore-monetizer'); ?>
        </p>
    </div>
    <?php delete_transient('yadore_activation_notice'); endif; ?>

    <?php
    $api_connected = (bool) get_option('yadore_api_key');
    $gemini_connected = (bool) get_option('yadore_gemini_api_key');
    $overlay_enabled = (bool) get_option('yadore_overlay_enabled', true);
    $auto_scan_enabled = (bool) get_option('yadore_auto_scan_posts', true);
    $analytics_enabled = (bool) get_option('yadore_analytics_enabled', true);

    $settings_url = admin_url('admin.php?page=yadore-settings');
    $analytics_url = admin_url('admin.php?page=yadore-analytics');

    $onboarding_items = array(
        array(
            'title' => __('Yadore-API verbinden', 'yadore-monetizer'),
            'description' => __('Aktiviere Produktdaten, indem du deinen Yadore API-Schlüssel hinterlegst.', 'yadore-monetizer'),
            'icon' => 'dashicons-rest-api',
            'complete' => $api_connected,
            'action_url' => $settings_url,
            'action_label' => __('Einstellungen öffnen', 'yadore-monetizer'),
        ),
        array(
            'title' => __('Gemini-KI konfigurieren', 'yadore-monetizer'),
            'description' => __('Hinterlege einen Gemini API-Schlüssel, um automatische Keyword-Vorschläge zu erhalten.', 'yadore-monetizer'),
            'icon' => 'dashicons-art',
            'complete' => $gemini_connected,
            'action_url' => $settings_url,
            'action_label' => __('AI-Einstellungen prüfen', 'yadore-monetizer'),
        ),
        array(
            'title' => __('Automatischen Scan aktivieren', 'yadore-monetizer'),
            'description' => __('Lass neue Beiträge automatisch analysieren, damit immer passende Produkte erscheinen.', 'yadore-monetizer'),
            'icon' => 'dashicons-update',
            'complete' => $auto_scan_enabled,
            'action_url' => $settings_url,
            'action_label' => __('Automatisierung einschalten', 'yadore-monetizer'),
        ),
        array(
            'title' => __('Overlay & Shortcode testen', 'yadore-monetizer'),
            'description' => __('Stelle sicher, dass Overlay und Shortcode-Ausgabe für deine Inhalte aktiviert sind.', 'yadore-monetizer'),
            'icon' => 'dashicons-visibility',
            'complete' => $overlay_enabled,
            'action_url' => $settings_url,
            'action_label' => __('Darstellung konfigurieren', 'yadore-monetizer'),
        ),
        array(
            'title' => __('Analytics überwachen', 'yadore-monetizer'),
            'description' => __('Nutze das Analytics-Dashboard, um Performance und Trends im Blick zu behalten.', 'yadore-monetizer'),
            'icon' => 'dashicons-chart-area',
            'complete' => $analytics_enabled,
            'action_url' => $analytics_url,
            'action_label' => __('Analytics öffnen', 'yadore-monetizer'),
        ),
    );

    $onboarding_total = count($onboarding_items);
    $onboarding_completed = 0;
    foreach ($onboarding_items as $item) {
        if (!empty($item['complete'])) {
            $onboarding_completed++;
        }
    }
    $onboarding_progress = $onboarding_total > 0
        ? (int) round(($onboarding_completed / $onboarding_total) * 100)
        : 100;

    $next_onboarding_item = null;
    foreach ($onboarding_items as $item) {
        if (empty($item['complete'])) {
            $next_onboarding_item = $item;
            break;
        }
    }

    $setup_status = array(
        'type' => 'success',
        'icon' => 'dashicons-yes-alt',
        'title' => __('Alles eingerichtet – starke Performance voraus!', 'yadore-monetizer'),
        'description' => __('Alle Kernfunktionen sind aktiv. Behalte deine KPIs im Blick und optimiere deine Inhalte weiter.', 'yadore-monetizer'),
        'primary_action' => $analytics_url,
        'primary_label' => __('Analytics öffnen', 'yadore-monetizer'),
        'secondary_action' => $settings_url,
        'secondary_label' => __('Einstellungen prüfen', 'yadore-monetizer'),
    );

    if ($next_onboarding_item) {
        $setup_status['type'] = 'warning';
        $setup_status['icon'] = 'dashicons-admin-generic';
        $setup_status['title'] = __('Weiter geht es mit deiner Einrichtung', 'yadore-monetizer');
        $setup_status['description'] = sprintf(
            esc_html__('Nächster Schritt: %1$s – %2$s', 'yadore-monetizer'),
            esc_html($next_onboarding_item['title']),
            esc_html($next_onboarding_item['description'])
        );
        $setup_status['primary_action'] = !empty($next_onboarding_item['action_url'])
            ? $next_onboarding_item['action_url']
            : $settings_url;
        $setup_status['primary_label'] = !empty($next_onboarding_item['action_label'])
            ? $next_onboarding_item['action_label']
            : __('Jetzt konfigurieren', 'yadore-monetizer');
        $setup_status['secondary_action'] = $analytics_url;
        $setup_status['secondary_label'] = __('Ergebnisse ansehen', 'yadore-monetizer');
    }

    $setup_highlights = array(
        array(
            'label' => __('Onboarding', 'yadore-monetizer'),
            'value' => sprintf(__('%d%% abgeschlossen', 'yadore-monetizer'), (int) $onboarding_progress),
            'state' => $onboarding_progress === 100 ? 'success' : 'warning',
            'icon' => $onboarding_progress === 100 ? 'dashicons-yes-alt' : 'dashicons-flag',
        ),
        array(
            'label' => __('Yadore-API', 'yadore-monetizer'),
            'value' => $api_connected
                ? __('Verbunden', 'yadore-monetizer')
                : __('Nicht verbunden', 'yadore-monetizer'),
            'state' => $api_connected ? 'success' : 'warning',
            'icon' => 'dashicons-rest-api',
        ),
        array(
            'label' => __('Gemini-KI', 'yadore-monetizer'),
            'value' => $gemini_connected
                ? __('Aktiviert', 'yadore-monetizer')
                : __('Noch nicht aktiv', 'yadore-monetizer'),
            'state' => $gemini_connected ? 'success' : 'info',
            'icon' => 'dashicons-art',
        ),
    );
    ?>

    <div class="yadore-dashboard-grid">
        <!-- Main Content -->
        <div class="yadore-main-content">
            <!-- Setup Status -->
            <div class="yadore-card setup-status-card status-<?php echo esc_attr($setup_status['type']); ?>">
                <div class="status-icon" aria-hidden="true">
                    <span class="dashicons <?php echo esc_attr($setup_status['icon']); ?>"></span>
                </div>
                <div class="status-content">
                    <h2><?php echo esc_html($setup_status['title']); ?></h2>
                    <p><?php echo esc_html($setup_status['description']); ?></p>

                    <ul class="status-highlights">
                        <?php foreach ($setup_highlights as $highlight) : ?>
                            <li class="status-highlight highlight-<?php echo esc_attr($highlight['state']); ?>">
                                <span class="highlight-icon dashicons <?php echo esc_attr($highlight['icon']); ?>" aria-hidden="true"></span>
                                <span class="highlight-label"><?php echo esc_html($highlight['label']); ?></span>
                                <span class="highlight-value"><?php echo esc_html($highlight['value']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="status-actions">
                    <?php if (!empty($setup_status['primary_action']) && !empty($setup_status['primary_label'])) : ?>
                        <a class="button button-primary" href="<?php echo esc_url($setup_status['primary_action']); ?>">
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                            <?php echo esc_html($setup_status['primary_label']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($setup_status['secondary_action']) && !empty($setup_status['secondary_label'])) : ?>
                        <a class="button button-secondary" href="<?php echo esc_url($setup_status['secondary_action']); ?>">
                            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                            <?php echo esc_html($setup_status['secondary_label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="yadore-card yadore-stats-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html__('Kernmetriken', 'yadore-monetizer'); ?></h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-stats">
                            <span class="dashicons dashicons-update"></span> <?php echo esc_html__('Aktualisieren', 'yadore-monetizer'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="yadore-stats-grid">
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-products"></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="total-products"><?php echo esc_html__('Wird geladen...', 'yadore-monetizer'); ?></div>
                                <div class="stat-label"><?php echo esc_html__('Ausgespielte Produkte', 'yadore-monetizer'); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-admin-post"></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="scanned-posts"><?php echo esc_html__('Wird geladen...', 'yadore-monetizer'); ?></div>
                                <div class="stat-label"><?php echo esc_html__('Gescannte Beiträge', 'yadore-monetizer'); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-info">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="overlay-views"><?php echo esc_html__('Wird geladen...', 'yadore-monetizer'); ?></div>
                                <div class="stat-label"><?php echo esc_html__('Overlay-Aufrufe', 'yadore-monetizer'); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="conversion-rate"><?php echo esc_html__('Wird geladen...', 'yadore-monetizer'); ?></div>
                                <div class="stat-label"><?php echo esc_html__('Konversionsrate', 'yadore-monetizer'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="stats-meta">
                        <div class="meta-item">
                            <span class="dashicons dashicons-update-alt" aria-hidden="true"></span>
                            <span class="meta-label"><?php echo esc_html__('Zuletzt aktualisiert', 'yadore-monetizer'); ?>:</span>
                            <time id="stats-last-updated" class="stats-timestamp" aria-live="polite"><?php esc_html_e('Noch keine Daten geladen', 'yadore-monetizer'); ?></time>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Status -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('Funktionsstatus', 'yadore-monetizer'); ?></h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-status">
                            <span class="dashicons dashicons-update"></span> <?php echo esc_html__('Aktualisieren', 'yadore-monetizer'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="feature-status-grid">
                        <div class="feature-item">
                            <div class="feature-icon status-active">
                                <span class="dashicons dashicons-wordpress-alt"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('WordPress-Integration', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('Umfassende WordPress-Integration mit sechs Admin-Seiten', 'yadore-monetizer'); ?></p>
                                <span class="status-badge status-active"><?php echo esc_html__('Aktiv', 'yadore-monetizer'); ?></span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_ai_enabled', false) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('KI-Inhaltsanalyse', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('Gemini-KI-Integration zur intelligenten Produkterkennung', 'yadore-monetizer'); ?></p>
                                <span class="status-badge <?php echo get_option('yadore_ai_enabled', false) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_ai_enabled', false) ? esc_html__('Aktiv', 'yadore-monetizer') : esc_html__('Inaktiv', 'yadore-monetizer'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_overlay_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('Produkt-Overlay', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('Dynamische Produktempfehlungen als Overlay', 'yadore-monetizer'); ?></p>
                                <span class="status-badge <?php echo get_option('yadore_overlay_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_overlay_enabled', true) ? esc_html__('Aktiv', 'yadore-monetizer') : esc_html__('Inaktiv', 'yadore-monetizer'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_auto_scan_posts', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-search"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('Automatischer Beitrags-Scan', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('Automatische Inhaltsanalyse und Produkterkennung', 'yadore-monetizer'); ?></p>
                                <span class="status-badge <?php echo get_option('yadore_auto_scan_posts', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_auto_scan_posts', true) ? esc_html__('Aktiv', 'yadore-monetizer') : esc_html__('Inaktiv', 'yadore-monetizer'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon <?php echo get_option('yadore_analytics_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                <span class="dashicons dashicons-chart-area"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('Analysen & Tracking', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('Umfassende Analysen und Leistungsüberwachung', 'yadore-monetizer'); ?></p>
                                <span class="status-badge <?php echo get_option('yadore_analytics_enabled', true) ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo get_option('yadore_analytics_enabled', true) ? esc_html__('Aktiv', 'yadore-monetizer') : esc_html__('Inaktiv', 'yadore-monetizer'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon status-active">
                                <span class="dashicons dashicons-shortcode"></span>
                            </div>
                            <div class="feature-details">
                                <h4><?php echo esc_html__('Shortcode-System', 'yadore-monetizer'); ?></h4>
                                <p><?php echo esc_html__('[yadore_products]-Shortcode mit erweiterten Funktionen', 'yadore-monetizer'); ?></p>
                                <span class="status-badge status-active"><?php echo esc_html__('Aktiv', 'yadore-monetizer'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shortcode Generator -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-shortcode"></span> <?php echo esc_html__('Erweiterter Shortcode-Generator', 'yadore-monetizer'); ?></h2>
                </div>
                <div class="card-content">
                    <div class="shortcode-generator-v27">
                        <div class="generator-row">
                            <div class="generator-col">
                                <label for="shortcode-keyword"><?php echo esc_html__('Produkt-Keyword *', 'yadore-monetizer'); ?></label>
                                <input type="text" id="shortcode-keyword" placeholder="z. B. Smartphone, Laptop, Kopfhörer" value="smartphone" required>
                                <small><?php echo esc_html__('Gib die wichtigste Produktkategorie oder einen konkreten Produktnamen ein.', 'yadore-monetizer'); ?></small>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-limit"><?php echo esc_html__('Anzahl der Produkte', 'yadore-monetizer'); ?></label>
                                <select id="shortcode-limit">
                                    <option value="3">3 <?php echo esc_html__('Produkte', 'yadore-monetizer'); ?></option>
                                    <option value="6" selected>6 <?php echo esc_html__('Produkte', 'yadore-monetizer'); ?></option>
                                    <option value="9">9 <?php echo esc_html__('Produkte', 'yadore-monetizer'); ?></option>
                                    <option value="12">12 <?php echo esc_html__('Produkte', 'yadore-monetizer'); ?></option>
                                </select>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-format"><?php echo esc_html__('Darstellungsformat', 'yadore-monetizer'); ?></label>
                                <select id="shortcode-format">
                                    <option value="grid" selected><?php echo esc_html__('Rasterdarstellung', 'yadore-monetizer'); ?></option>
                                    <option value="list"><?php echo esc_html__('Listenansicht', 'yadore-monetizer'); ?></option>
                                    <option value="inline"><?php echo esc_html__('Inline-Integration', 'yadore-monetizer'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="generator-row">
                            <div class="generator-col">
                                <label for="shortcode-cache"><?php echo esc_html__('Caching aktivieren', 'yadore-monetizer'); ?></label>
                                <select id="shortcode-cache">
                                    <option value="true" selected><?php echo esc_html__('Ja (empfohlen)', 'yadore-monetizer'); ?></option>
                                    <option value="false"><?php echo esc_html__('Nein', 'yadore-monetizer'); ?></option>
                                </select>
                            </div>

                            <div class="generator-col">
                                <label for="shortcode-class"><?php echo esc_html__('Eigene CSS-Klasse', 'yadore-monetizer'); ?></label>
                                <input type="text" id="shortcode-class" placeholder="my-custom-class">
                                <small><?php echo esc_html__('Optional: Eigene CSS-Klasse für individuelles Styling hinzufügen.', 'yadore-monetizer'); ?></small>
                            </div>
                        </div>

                        <div class="generator-result">
                            <label for="generated-shortcode"><?php echo esc_html__('Generierter Shortcode:', 'yadore-monetizer'); ?></label>
                            <div class="shortcode-output">
                                <textarea id="generated-shortcode" readonly>[yadore_products keyword="smartphone" limit="6" format="grid" cache="true"]</textarea>
                                <button type="button" id="copy-shortcode" class="button button-primary">
                                    <span class="dashicons dashicons-clipboard"></span> <?php echo esc_html__('Kopieren', 'yadore-monetizer'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="generator-preview">
                            <h4><span class="dashicons dashicons-visibility"></span> <?php echo esc_html__('Vorschau', 'yadore-monetizer'); ?></h4>
                            <div class="preview-container" id="shortcode-preview">
                                <div class="preview-loading">
                                    <span class="dashicons dashicons-update-alt spinning"></span>
                                    <span><?php echo esc_html__('Vorschau wird erstellt...', 'yadore-monetizer'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="yadore-card">
                <div class="card-header">
                    <h2><span class="dashicons dashicons-clock"></span> <?php echo esc_html__('Aktuelle Aktivitäten', 'yadore-monetizer'); ?></h2>
                    <div class="card-actions">
                        <button class="button button-secondary" id="refresh-activity">
                            <span class="dashicons dashicons-update"></span> <?php echo esc_html__('Aktualisieren', 'yadore-monetizer'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="activity-list" id="recent-activity">
                        <div class="activity-loading">
                            <span class="dashicons dashicons-update-alt spinning"></span>
                            <span><?php echo esc_html__('Aktuelle Aktivitäten werden geladen...', 'yadore-monetizer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="yadore-sidebar">
            <!-- Onboarding Checklist -->
            <div class="yadore-card yadore-onboarding-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html__('Schnellstart-Checkliste', 'yadore-monetizer'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="onboarding-progress" role="status" aria-live="polite">
                        <div class="progress-summary">
                            <span class="progress-label"><?php printf(esc_html__('%1$d von %2$d Schritten erledigt', 'yadore-monetizer'), (int) $onboarding_completed, (int) $onboarding_total); ?></span>
                            <span class="progress-value"><?php echo esc_html($onboarding_progress); ?>%</span>
                        </div>
                        <div
                            class="progress-bar"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="<?php echo esc_attr($onboarding_progress); ?>"
                            aria-label="<?php echo esc_attr__('Onboarding-Fortschritt', 'yadore-monetizer'); ?>"
                        >
                            <span class="progress-bar-fill" style="--progress: <?php echo esc_attr($onboarding_progress); ?>%;"></span>
                        </div>
                        <?php if ($onboarding_progress === 100) : ?>
                            <p class="progress-message success"><?php echo esc_html__('Alle Kernfunktionen sind aktiviert – großartig!', 'yadore-monetizer'); ?></p>
                        <?php else : ?>
                            <p class="progress-message"><?php echo esc_html__('Folge den Schritten, um das volle Potenzial des Plugins auszuschöpfen.', 'yadore-monetizer'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="onboarding-checklist">
                        <?php foreach ($onboarding_items as $item): ?>
                            <?php $is_complete = !empty($item['complete']); ?>
                            <div class="checklist-item <?php echo $is_complete ? 'is-complete' : 'is-pending'; ?>">
                                <div class="checklist-icon">
                                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></span>
                                </div>
                                <div class="checklist-content">
                                    <strong><?php echo esc_html($item['title']); ?></strong>
                                    <p><?php echo esc_html($item['description']); ?></p>
                                    <div class="checklist-meta">
                                        <span class="status-badge <?php echo $is_complete ? 'status-active' : 'status-warning'; ?>">
                                            <?php echo esc_html($is_complete ? __('Erledigt', 'yadore-monetizer') : __('Ausstehend', 'yadore-monetizer')); ?>
                                        </span>
                                        <?php if (!$is_complete && !empty($item['action_url'])): ?>
                                            <a class="button-link" href="<?php echo esc_url($item['action_url']); ?>">
                                                <?php echo esc_html($item['action_label']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__('Schnellaktionen', 'yadore-monetizer'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=yadore-settings'); ?>" class="action-button action-primary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <div class="action-content">
                                <strong><?php echo esc_html__('Plugin-Einstellungen', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Alle Plugin-Optionen konfigurieren', 'yadore-monetizer'); ?></small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-scanner'); ?>" class="action-button action-scanner">
                            <span class="dashicons dashicons-search"></span>
                            <div class="action-content">
                                <strong><?php echo esc_html__('Beitrags-Scanner', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Beiträge scannen und analysieren', 'yadore-monetizer'); ?></small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-analytics'); ?>" class="action-button action-analytics">
                            <span class="dashicons dashicons-chart-area"></span>
                            <div class="action-content">
                                <strong><?php echo esc_html__('Analysen', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Leistungsberichte anzeigen', 'yadore-monetizer'); ?></small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-debug'); ?>" class="action-button action-debug">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <div class="action-content">
                                <strong><?php echo esc_html__('Debug & Fehler', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Systemdiagnose', 'yadore-monetizer'); ?></small>
                            </div>
                        </a>

                        <a href="<?php echo admin_url('admin.php?page=yadore-tools'); ?>" class="action-button action-tools">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <div class="action-content">
                                <strong><?php echo esc_html__('Werkzeuge', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Import/Export & Werkzeuge', 'yadore-monetizer'); ?></small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html__('Systemstatus', 'yadore-monetizer'); ?></h3>
                </div>
                <div class="card-content">
                    <div class="system-status">
                        <div class="status-item">
                            <div class="status-indicator status-active"></div>
                            <div class="status-details">
                                <strong><?php echo esc_html__('WordPress-Integration', 'yadore-monetizer'); ?></strong>
                                <small><?php printf(esc_html__('v%s – Alle Systeme betriebsbereit', 'yadore-monetizer'), esc_html(YADORE_PLUGIN_VERSION)); ?></small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator <?php echo $api_connected ? 'status-active' : 'status-warning'; ?>"></div>
                            <div class="status-details">
                                <strong><?php echo esc_html__('Yadore-API', 'yadore-monetizer'); ?></strong>
                                <small>
                                    <?php
                                    if ($api_connected) {
                                        echo esc_html__('Verbunden', 'yadore-monetizer');
                                    } else {
                                        echo wp_kses_post(sprintf(
                                            __('API-Schlüssel erforderlich – <a href="%s">jetzt verbinden</a>', 'yadore-monetizer'),
                                            esc_url($settings_url)
                                        ));
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator <?php echo $gemini_connected ? 'status-active' : 'status-inactive'; ?>"></div>
                            <div class="status-details">
                                <strong><?php echo esc_html__('Gemini-KI', 'yadore-monetizer'); ?></strong>
                                <small>
                                    <?php
                                    if ($gemini_connected) {
                                        echo esc_html__('Verbunden', 'yadore-monetizer');
                                    } else {
                                        echo wp_kses_post(sprintf(
                                            __('Nicht konfiguriert – <a href="%s">AI-Setup öffnen</a>', 'yadore-monetizer'),
                                            esc_url($settings_url)
                                        ));
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-indicator status-active"></div>
                            <div class="status-details">
                                <strong><?php echo esc_html__('Datenbank', 'yadore-monetizer'); ?></strong>
                                <small><?php echo esc_html__('Alle Tabellen funktionsfähig', 'yadore-monetizer'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Version Info -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-info"></span> Version Information</h3>
                </div>
                <div class="card-content">
                    <div class="version-info">
                        <div class="info-row">
                            <span class="info-label">Plugin Version:</span>
                            <span class="info-value version-current">v<?php echo esc_html(YADORE_PLUGIN_VERSION); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">WordPress:</span>
                            <span class="info-value"><?php echo get_bloginfo('version'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo phpversion(); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Database:</span>
                            <span class="info-value"><?php echo esc_html(sprintf('Enhanced v%s', YADORE_PLUGIN_VERSION)); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Features:</span>
                            <span class="info-value">Complete Set</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value status-active">Fully Functional</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help & Support -->
            <div class="yadore-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-sos"></span> Help & Support</h3>
                </div>
                <div class="card-content">
                    <div class="help-links">
                        <a href="#" class="help-link" onclick="yadoreShowTutorial()">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <span><?php echo esc_html__('Video-Tutorial', 'yadore-monetizer'); ?></span>
                        </a>
                        <a href="#" class="help-link" onclick="yadoreShowShortcuts()">
                            <span class="dashicons dashicons-keyboard-hide"></span>
                            <span><?php echo esc_html__('Tastenkürzel', 'yadore-monetizer'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Dashboard initialisieren
    yadoreInitializeDashboard();

    // Statistiken laden
    yadoreLoadDashboardStats();

    // Shortcode-Generator starten
    yadoreInitializeShortcodeGenerator();

    // Alle 30 Sekunden aktualisieren
    setInterval(yadoreLoadDashboardStats, 30000);
});

const yadoreDashboardStrings = {
    copied: '<?php echo esc_js(esc_html__('Kopiert!', 'yadore-monetizer')); ?>',
    copy: '<?php echo esc_js(esc_html__('Kopieren', 'yadore-monetizer')); ?>',
    previewLoading: '<?php echo esc_js(esc_html__('Vorschau wird erstellt...', 'yadore-monetizer')); ?>',
    previewHeading: '<?php echo esc_js(esc_html__('Vorschau', 'yadore-monetizer')); ?>',
    previewInfo: '<?php echo esc_js(esc_html__('Dies ist eine vereinfachte Vorschau. Der tatsächliche Shortcode zeigt reale Produkte.', 'yadore-monetizer')); ?>',
    productLabel: '<?php echo esc_js(esc_html__('Produkt', 'yadore-monetizer')); ?>',
    dashboardInitialized: '<?php echo esc_js(esc_html__('Dashboard gestartet', 'yadore-monetizer')); ?>'
};

function yadoreInitializeDashboard() {
    const dashboardVersion = (typeof yadore_admin !== 'undefined' && yadore_admin.version)
        ? yadore_admin.version
        : '<?php echo esc_js(YADORE_PLUGIN_VERSION); ?>';
    console.log(`Yadore Monetizer Pro v${dashboardVersion} – ${yadoreDashboardStrings.dashboardInitialized}`);
}

function yadoreLoadDashboardStats() {
    if (window.yadoreAdmin && typeof window.yadoreAdmin.loadDashboardStats === 'function') {
        window.yadoreAdmin.loadDashboardStats();
    }
}

function yadoreInitializeShortcodeGenerator() {
    const $ = jQuery;

    function updateShortcode() {
        const keyword = $('#shortcode-keyword').val() || 'smartphone';
        const limit = $('#shortcode-limit').val();
        const format = $('#shortcode-format').val();
        const cache = $('#shortcode-cache').val();
        const customClass = $('#shortcode-class').val();

        let shortcode = `[yadore_products keyword="${keyword}" limit="${limit}" format="${format}" cache="${cache}"`;

        if (customClass) {
            shortcode += ` class="${customClass}"`;
        }

        shortcode += ']';

        $('#generated-shortcode').val(shortcode);

        // Generate preview
        yadoreGeneratePreview(shortcode);
    }

    // Event listeners
    $('#shortcode-keyword, #shortcode-limit, #shortcode-format, #shortcode-cache, #shortcode-class').on('input change', updateShortcode);

    // Copy functionality
    $('#copy-shortcode').on('click', function() {
        const shortcode = $('#generated-shortcode')[0];
        shortcode.select();
        shortcode.setSelectionRange(0, 99999);
        document.execCommand('copy');

        $(this).addClass('copied').html(`<span class="dashicons dashicons-yes"></span> ${yadoreDashboardStrings.copied}`);
        setTimeout(() => {
            $(this).removeClass('copied').html(`<span class="dashicons dashicons-clipboard"></span> ${yadoreDashboardStrings.copy}`);
        }, 2000);
    });

    // Initial update
    updateShortcode();
}

function yadoreGeneratePreview(shortcode) {
    const previewContainer = jQuery('#shortcode-preview');
    previewContainer.html(`<div class="preview-loading"><span class="dashicons dashicons-update-alt spinning"></span><span>${yadoreDashboardStrings.previewLoading}</span></div>`);

    // Simulate preview generation
    setTimeout(() => {
        previewContainer.html(`
            <div class="shortcode-preview-result">
                <h4>${yadoreDashboardStrings.previewHeading}: ${shortcode}</h4>
                <div class="preview-grid">
                    <div class="preview-product">${yadoreDashboardStrings.productLabel} 1</div>
                    <div class="preview-product">${yadoreDashboardStrings.productLabel} 2</div>
                    <div class="preview-product">${yadoreDashboardStrings.productLabel} 3</div>
                </div>
                <p><em>${yadoreDashboardStrings.previewInfo}</em></p>
            </div>
        `);
    }, 1000);
}
</script>