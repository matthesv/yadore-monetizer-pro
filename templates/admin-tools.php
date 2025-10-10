<div class="wrap yadore-admin-wrap">
    <?php
    $tools_actions = array(
        array(
            'label' => esc_html__('Backup Wizard starten', 'yadore-monetizer'),
            'url' => '#tools-export',
            'type' => 'primary',
            'icon' => 'dashicons-migrate',
        ),
        array(
            'label' => esc_html__('Debug-Logs prüfen', 'yadore-monetizer'),
            'url' => admin_url('admin.php?page=yadore-debug'),
            'type' => 'ghost',
            'icon' => 'dashicons-admin-network',
        ),
    );

    $supported_formats = array();
    if (isset($data_formats) && is_array($data_formats)) {
        foreach ($data_formats as $format_key => $format_config) {
            $key = sanitize_key(is_string($format_key) ? $format_key : '');
            $extension = '';
            if (isset($format_config['extension'])) {
                $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $format_config['extension']));
            }

            if ($extension === '') {
                $extension = $key;
            }

            $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $extension));
            if ($extension === '') {
                continue;
            }

            if ($key === '') {
                $key = $extension;
            }

            $label = isset($format_config['label']) ? $format_config['label'] : strtoupper($extension);

            $supported_formats[$key] = array(
                'label' => $label,
                'extension' => $extension,
            );
        }
    }

    if (empty($supported_formats)) {
        $supported_formats = array(
            'json' => array(
                'label' => __('JSON', 'yadore-monetizer'),
                'extension' => 'json',
            ),
            'csv' => array(
                'label' => __('CSV', 'yadore-monetizer'),
                'extension' => 'csv',
            ),
            'xml' => array(
                'label' => __('XML', 'yadore-monetizer'),
                'extension' => 'xml',
            ),
        );
    }

    $format_extensions = array();
    $format_labels_display = array();
    foreach ($supported_formats as $supported) {
        $extension = isset($supported['extension']) ? $supported['extension'] : '';
        if ($extension !== '') {
            $format_extensions[] = $extension;
        }

        $label = isset($supported['label']) ? $supported['label'] : '';
        if ($label !== '') {
            $format_labels_display[] = $label;
        }
    }

    $default_format = array_key_exists('json', $supported_formats) ? 'json' : array_key_first($supported_formats);
    $import_extensions_attr = implode(',', $format_extensions);
    $import_accept_raw = $format_extensions ? '.' . implode(',.', $format_extensions) : '';
    $import_formats_text = $format_labels_display ? implode(', ', $format_labels_display) : __('JSON, CSV, XML', 'yadore-monetizer');

    $tools_meta = array(
        array(
            'label' => esc_html__('Datenexporte', 'yadore-monetizer'),
            'value' => esc_html($import_formats_text),
            'description' => esc_html__('Sichere Einstellungen, Keywords und Analytics.', 'yadore-monetizer'),
            'icon' => 'dashicons-download',
            'state' => 'info',
        ),
        array(
            'label' => esc_html__('Systemwartung', 'yadore-monetizer'),
            'value' => esc_html__('Cache · Logs · Reset', 'yadore-monetizer'),
            'description' => esc_html__('Optimierungs-Tools für saubere Installationen.', 'yadore-monetizer'),
            'icon' => 'dashicons-hammer',
            'state' => 'success',
        ),
        array(
            'label' => esc_html__('Zugriff', 'yadore-monetizer'),
            'value' => esc_html__('Nur Administrator:innen', 'yadore-monetizer'),
            'description' => esc_html__('Protokolliert jede kritische Aktion.', 'yadore-monetizer'),
            'icon' => 'dashicons-shield-alt',
            'state' => 'neutral',
        ),
    );

    $page_header = array(
        'slug' => 'tools',
        'eyebrow' => esc_html__('Maintenance Suite', 'yadore-monetizer'),
        'icon' => 'dashicons-admin-tools',
        'title' => esc_html__('Tools & Utilities', 'yadore-monetizer'),
        'subtitle' => esc_html__('Exportiere Daten, optimiere Caches und verwalte Integrationen mit modernen Sicherheitsnetzen.', 'yadore-monetizer'),
        'version' => YADORE_PLUGIN_VERSION,
        'actions' => $tools_actions,
        'meta' => $tools_meta,
    );
    ?>

    <div class="yadore-admin-shell">
        <?php include __DIR__ . '/partials/admin-page-header.php'; ?>

        <div class="yadore-admin-content">
            <div class="yadore-tools-container">
                <!-- Refactor note: the tools layout now relies on yadore-card-grid utilities for consistent spacing across breakpoints. -->
        <!-- Data Management Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-migrate"></span> <?php esc_html_e('Datenverwaltung', 'yadore-monetizer'); ?></h2>
            </div>
            <div class="card-content">
                <div class="yadore-card-grid tools-grid">
                    <!-- Export Tools -->
                    <article class="tool-section" id="tools-export" aria-labelledby="tools-export-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-download" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="tools-export-title"><?php esc_html_e('Daten exportieren', 'yadore-monetizer'); ?></h3>
                                <p class="tool-section__description"><?php esc_html_e('Exportiere deine Plugin-Daten für Backups oder Migrationen.', 'yadore-monetizer'); ?></p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="export-options yadore-card-grid" data-variant="compact">
                                <div class="option-group">
                                    <h4><?php esc_html_e('Datentypen', 'yadore-monetizer'); ?></h4>
                                    <label><input type="checkbox" name="export_data[]" value="settings" checked> <?php esc_html_e('Plugin-Einstellungen', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="export_data[]" value="keywords" checked> <?php esc_html_e('Beitrags-Keywords', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="export_data[]" value="analytics" checked> <?php esc_html_e('Analytics-Daten', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="export_data[]" value="logs"> <?php esc_html_e('API-Protokolle', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="export_data[]" value="cache"> <?php esc_html_e('KI-Cache', 'yadore-monetizer'); ?></label>
                                </div>

                                <div class="option-group">
                                    <h4><?php esc_html_e('Format', 'yadore-monetizer'); ?></h4>
                                    <?php foreach ($supported_formats as $format_key => $format_config) :
                                        $label = isset($format_config['label']) ? $format_config['label'] : strtoupper($format_key);
                                    ?>
                                        <label>
                                            <input type="radio" name="export_format" value="<?php echo esc_attr($format_key); ?>" <?php checked($format_key, $default_format); ?>>
                                            <?php echo esc_html($label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="option-group">
                                    <h4><?php esc_html_e('Zeitraum', 'yadore-monetizer'); ?></h4>
                                    <select id="export-date-range">
                                        <option value="all"><?php esc_html_e('Alle Daten', 'yadore-monetizer'); ?></option>
                                        <option value="30"><?php esc_html_e('Letzte 30 Tage', 'yadore-monetizer'); ?></option>
                                        <option value="90"><?php esc_html_e('Letzte 90 Tage', 'yadore-monetizer'); ?></option>
                                        <option value="365"><?php esc_html_e('Letztes Jahr', 'yadore-monetizer'); ?></option>
                                        <option value="custom"><?php esc_html_e('Benutzerdefinierter Zeitraum', 'yadore-monetizer'); ?></option>
                                    </select>

                                    <div id="custom-date-range" class="tool-date-range hidden" aria-hidden="true">
                                        <input type="date" id="export-start-date">
                                        <input type="date" id="export-end-date">
                                    </div>
                                </div>

                                <div class="option-group">
                                    <h4><?php esc_html_e('Planungsoptionen', 'yadore-monetizer'); ?></h4>
                                    <label for="export-schedule-interval"><?php esc_html_e('Häufigkeit', 'yadore-monetizer'); ?></label>
                                    <select id="export-schedule-interval">
                                        <option value="daily" selected><?php esc_html_e('Täglich', 'yadore-monetizer'); ?></option>
                                        <option value="twicedaily"><?php esc_html_e('Zweimal täglich', 'yadore-monetizer'); ?></option>
                                        <option value="weekly"><?php esc_html_e('Wöchentlich', 'yadore-monetizer'); ?></option>
                                        <option value="hourly"><?php esc_html_e('Stündlich', 'yadore-monetizer'); ?></option>
                                    </select>

                                    <label for="export-schedule-time"><?php esc_html_e('Tageszeit', 'yadore-monetizer'); ?></label>
                                    <input type="time" id="export-schedule-time" value="02:00">
                                    <p class="description"><?php esc_html_e('Plane wiederkehrende Exporte mit der gewählten Frequenz.', 'yadore-monetizer'); ?></p>
                                </div>
                            </div>
                        </div>
                        <footer class="tool-section__footer tool-actions">
                            <button type="button" class="button button-primary" id="start-export">
                                <span class="dashicons dashicons-download"></span> <?php esc_html_e('Daten exportieren', 'yadore-monetizer'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="schedule-export">
                                <span class="dashicons dashicons-clock"></span> <?php esc_html_e('Export planen', 'yadore-monetizer'); ?>
                            </button>
                        </footer>
                        <div class="tool-section__status">
                            <div class="export-results hidden" id="export-results" aria-live="polite">
                                <div class="export-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="export-progress"></div>
                                    </div>
                                    <div class="progress-text" id="export-status"><?php esc_html_e('Export wird vorbereitet …', 'yadore-monetizer'); ?></div>
                                </div>
                            </div>
                            <div class="export-schedule-status" id="export-schedule-status" aria-live="polite"></div>
                        </div>
                    </article>

                    <!-- Import Tools -->
                    <article class="tool-section" id="tools-import" aria-labelledby="tools-import-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-upload" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="tools-import-title"><?php esc_html_e('Daten importieren', 'yadore-monetizer'); ?></h3>
                                <p class="tool-section__description"><?php esc_html_e('Importiere Daten aus Backup-Dateien oder anderen Quellen.', 'yadore-monetizer'); ?></p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="import-options yadore-card-grid" data-variant="compact">
                                <div class="file-upload">
                                    <?php $upload_instructions_id = 'import-upload-instructions'; ?>
                                    <div
                                        class="upload-area"
                                        id="import-upload-area"
                                        role="button"
                                        tabindex="0"
                                        aria-label="<?php echo esc_attr__('Dateien zum Import auswählen', 'yadore-monetizer'); ?>"
                                        aria-describedby="<?php echo esc_attr($upload_instructions_id); ?>"
                                        data-import-extensions="<?php echo esc_attr($import_extensions_attr); ?>"
                                        data-import-labels="<?php echo esc_attr($import_formats_text); ?>"
                                    >
                                        <div class="upload-icon">
                                            <span class="dashicons dashicons-upload"></span>
                                        </div>
                                        <div class="upload-text">
                                            <strong><?php esc_html_e('Dateien hier ablegen oder zum Hochladen klicken', 'yadore-monetizer'); ?></strong>
                                            <p id="<?php echo esc_attr($upload_instructions_id); ?>"><?php printf(esc_html__('Unterstützte Formate: %s', 'yadore-monetizer'), esc_html($import_formats_text)); ?></p>
                                            <button
                                                type="button"
                                                class="button button-secondary upload-trigger"
                                                id="import-upload-button"
                                                aria-controls="import-file"
                                            >
                                                <span class="dashicons dashicons-media-default" aria-hidden="true"></span>
                                                <?php esc_html_e('Dateien auswählen', 'yadore-monetizer'); ?>
                                            </button>
                                        </div>
                                        <input
                                            type="file"
                                            id="import-file"
                                            class="yadore-hidden-file-input screen-reader-text"
                                            accept="<?php echo esc_attr($import_accept_raw); ?>"
                                            multiple
                                        >
                                    </div>
                                </div>

                                <div class="import-settings">
                                    <h4><?php esc_html_e('Importoptionen', 'yadore-monetizer'); ?></h4>
                                    <label><input type="checkbox" name="import_options[]" value="overwrite"> <?php esc_html_e('Bestehende Daten überschreiben', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="import_options[]" value="validate" checked> <?php esc_html_e('Daten vor dem Import validieren', 'yadore-monetizer'); ?></label>
                                    <label><input type="checkbox" name="import_options[]" value="backup" checked> <?php esc_html_e('Vor dem Import Sicherung erstellen', 'yadore-monetizer'); ?></label>
                                </div>
                            </div>
                        </div>
                        <footer class="tool-section__footer tool-actions">
                            <button type="button" class="button button-primary" id="start-import" disabled>
                                <span class="dashicons dashicons-upload"></span> <?php esc_html_e('Daten importieren', 'yadore-monetizer'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="validate-import" disabled>
                                <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Nur validieren', 'yadore-monetizer'); ?>
                            </button>
                        </footer>
                        <div class="tool-section__status">
                            <div class="import-results hidden" id="import-results" aria-live="polite">
                                <div class="import-summary"></div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <!-- Maintenance Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Wartungswerkzeuge', 'yadore-monetizer'); ?></h2>
            </div>
            <div class="card-content">
                <div class="maintenance-tools">
                    <div class="yadore-card-grid maintenance-grid">
                        <!-- Cache Management -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-cache-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-performance" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-cache-title"><?php esc_html_e('Cache-Verwaltung', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Verwalte den Plugin-Cache und optimiere die Performance.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Cache-Größe', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="cache-size"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Cache-Einträge', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="cache-entries"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Trefferquote', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="cache-hit-rate"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="clear-cache">
                                    <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Cache leeren', 'yadore-monetizer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="optimize-cache">
                                    <span class="dashicons dashicons-performance"></span> <?php esc_html_e('Optimieren', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                        <!-- Database Maintenance -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-database-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-database" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-database-title"><?php esc_html_e('Datenbankpflege', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Optimiere Datenbanktabellen und bereinige alte Daten.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Tabellengröße', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="db-size"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Datensätze gesamt', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="db-records"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Overhead', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="db-overhead"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="optimize-database">
                                    <span class="dashicons dashicons-database"></span> <?php esc_html_e('Datenbank optimieren', 'yadore-monetizer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="cleanup-old-data">
                                    <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Alte Daten bereinigen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                        <!-- Log Management -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-logs-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-media-text" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-logs-title"><?php esc_html_e('Protokollverwaltung', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Verwalte API-Protokolle, Fehlermeldungen und Debug-Informationen.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('API-Protokolle', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="api-log-count"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Fehlerprotokolle', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="error-log-count"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Protokollgröße', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="total-log-size"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="archive-logs">
                                    <span class="dashicons dashicons-archive"></span> <?php esc_html_e('Protokolle archivieren', 'yadore-monetizer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="clear-old-logs">
                                    <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Alte Protokolle löschen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                        <!-- Yadore Reports -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-optimizer-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-chart-line" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-optimizer-title"><?php esc_html_e('Yadore-Berichte', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Starte eine manuelle Synchronisierung des Yadore-Optimizer-Berichts, um Analytics-Daten bei Bedarf zu aktualisieren.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Letzte Ausführung', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="optimizer-last-run">—</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Matching-Rate', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="optimizer-match-rate">—</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Verarbeitete Tage', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="optimizer-dates-processed">—</dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Fehler', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="optimizer-errors">—</dd>
                                    </div>
                                </dl>
                                <div class="tool-option-group">
                                    <label for="optimizer-days"><?php esc_html_e('Tage für die Synchronisierung', 'yadore-monetizer'); ?></label>
                                    <select id="optimizer-days">
                                        <option value="1"><?php esc_html_e('Letzter Tag', 'yadore-monetizer'); ?></option>
                                        <option value="3"><?php esc_html_e('Letzte 3 Tage', 'yadore-monetizer'); ?></option>
                                        <option value="7" selected><?php esc_html_e('Letzte 7 Tage', 'yadore-monetizer'); ?></option>
                                        <option value="14"><?php esc_html_e('Letzte 14 Tage', 'yadore-monetizer'); ?></option>
                                        <option value="30"><?php esc_html_e('Letzte 30 Tage', 'yadore-monetizer'); ?></option>
                                    </select>

                                    <label for="optimizer-start-date"><?php esc_html_e('Startdatum (optional)', 'yadore-monetizer'); ?></label>
                                    <input type="date" id="optimizer-start-date">
                                    <p class="description"><?php esc_html_e('Lasse das Startdatum leer, um mit gestern zu beginnen.', 'yadore-monetizer'); ?></p>
                                </div>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="run-optimizer-sync">
                                    <span class="dashicons dashicons-update"></span> <?php esc_html_e('Berichte synchronisieren', 'yadore-monetizer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="refresh-optimizer-sync">
                                    <span class="dashicons dashicons-update-alt"></span> <?php esc_html_e('Status aktualisieren', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                            <div class="tool-section__status" id="optimizer-sync-status">
                                <div id="optimizer-sync-result" class="hidden" aria-live="polite"></div>
                            </div>
                        </article>

                        <!-- System Cleanup -->
                        <article class="tool-section tool-section--maintenance" aria-labelledby="maintenance-cleanup-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-admin-generic" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="maintenance-cleanup-title"><?php esc_html_e('Systembereinigung', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Bereinige temporäre Dateien und optimiere die Systemleistung.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <div class="tool-section__body">
                                <dl class="tool-metrics">
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Temporäre Dateien', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="temp-files"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Verwaiste Daten', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="orphaned-data"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                    <div class="tool-metric">
                                        <dt class="tool-metric__label"><?php esc_html_e('Belegter Speicher', 'yadore-monetizer'); ?></dt>
                                        <dd class="tool-metric__value" id="space-used"><?php esc_html_e('Lädt …', 'yadore-monetizer'); ?></dd>
                                    </div>
                                </dl>
                            </div>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="system-cleanup">
                                    <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Komplett bereinigen', 'yadore-monetizer'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="schedule-cleanup">
                                    <span class="dashicons dashicons-clock"></span> <?php esc_html_e('Bereinigung planen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Konfigurationswerkzeuge', 'yadore-monetizer'); ?></h2>
            </div>
            <div class="card-content">
                <div class="config-tools">
                    <!-- Reset Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-reset-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-update" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-reset-title"><?php esc_html_e('Zurücksetzen', 'yadore-monetizer'); ?></h3>
                                <p class="tool-section__description"><?php esc_html_e('Stelle bewährte Standardwerte wieder her oder starte saubere Installationen direkt im Tool-Bereich.', 'yadore-monetizer'); ?></p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group reset-options">
                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Auf Standardwerte zurücksetzen', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Setzt alle Plugin-Einstellungen auf ihre Standardwerte zurück und behält dabei vorhandene Daten.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-secondary" id="reset-settings">
                                            <span class="dashicons dashicons-update"></span> <?php esc_html_e('Einstellungen zurücksetzen', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Produktvorlagen wiederherstellen', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Stellt die Standard-Produktvorlagen wieder her, falls sie entfernt oder stark angepasst wurden.', 'yadore-monetizer'); ?></p>
                                        <label>
                                            <input type="checkbox" id="restore-reset-selection">
                                            <span><?php esc_html_e('Vorlagenauswahl auf Standard zurücksetzen', 'yadore-monetizer'); ?></span>
                                        </label>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="restore-default-templates">
                                            <span class="dashicons dashicons-layout"></span> <?php esc_html_e('Vorlagen wiederherstellen', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Alle Daten löschen', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Entfernt alle Plugin-Daten inklusive Einstellungen, Protokollen und Cache. Dies kann nicht rückgängig gemacht werden.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-link-delete" id="clear-all-data">
                                            <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Alle Daten löschen', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option reset-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Werkszustand wiederherstellen', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Vollständiger Plugin-Reset – entfernt alle Daten und stellt den Ausgangszustand wieder her.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-link-delete" id="factory-reset">
                                            <span class="dashicons dashicons-warning"></span> <?php esc_html_e('Werksreset ausführen', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <!-- Migration Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-migration-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-migrate" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-migration-title"><?php esc_html_e('Migrationswerkzeuge', 'yadore-monetizer'); ?></h3>
                                <p class="tool-section__description"><?php esc_html_e('Übertrage Konfigurationen zwischen Umgebungen mit nachvollziehbaren Ergebnissen.', 'yadore-monetizer'); ?></p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group migration-tools">
                                <article class="tool-option migration-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Konfiguration exportieren', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Exportiert die Plugin-Konfiguration für den Einsatz auf anderen Websites.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="export-config">
                                            <span class="dashicons dashicons-download"></span> <?php esc_html_e('Konfiguration exportieren', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>

                                <article class="tool-option migration-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Einstellungen klonen', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Kopiert Einstellungen von einer anderen WordPress-Website mit diesem Plugin.', 'yadore-monetizer'); ?></p>
                                        <input type="url" id="source-site-url" placeholder="<?php echo esc_attr__('https://quelle-seite.de', 'yadore-monetizer'); ?>" class="regular-text">
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-secondary" id="clone-settings">
                                            <span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e('Einstellungen klonen', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <!-- Optimization Tools -->
                    <section class="config-section tool-section" aria-labelledby="config-optimization-title" role="region">
                        <header class="tool-section__header">
                            <span class="tool-section__icon dashicons dashicons-performance" aria-hidden="true"></span>
                            <div class="tool-section__titles">
                                <h3 id="config-optimization-title"><?php esc_html_e('Optimierungswerkzeuge', 'yadore-monetizer'); ?></h3>
                                <p class="tool-section__description"><?php esc_html_e('Diagnostiziere und automatisiere Leistungsverbesserungen.', 'yadore-monetizer'); ?></p>
                            </div>
                        </header>
                        <div class="tool-section__body">
                            <div class="tool-option-group optimization-tools">
                                <article class="tool-option optimization-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Performance-Scan', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Analysiere die Plugin-Performance und erhalte Optimierungsempfehlungen.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="performance-scan">
                                            <span class="dashicons dashicons-performance"></span> <?php esc_html_e('Scan starten', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                    <div class="tool-option__status">
                                        <div class="scan-results hidden" id="performance-scan-results" aria-live="polite"></div>
                                    </div>
                                </article>

                                <article class="tool-option optimization-option">
                                    <div class="tool-option__content">
                                        <h4><?php esc_html_e('Auto-Optimierung', 'yadore-monetizer'); ?></h4>
                                        <p><?php esc_html_e('Wendet empfohlene Optimierungen automatisch an.', 'yadore-monetizer'); ?></p>
                                    </div>
                                    <div class="tool-option__footer">
                                        <button type="button" class="button button-primary" id="auto-optimize">
                                            <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Automatisch optimieren', 'yadore-monetizer'); ?>
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Utility Tools -->
        <div class="yadore-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Hilfswerkzeuge', 'yadore-monetizer'); ?></h2>
            </div>
            <div class="card-content">
                <div class="utility-tools">
                    <div class="yadore-card-grid utility-grid">
                        <!-- Shortcode Generator -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-shortcode-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-shortcode" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-shortcode-title"><?php esc_html_e('Erweiterter Shortcode-Generator', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Erstelle Shortcodes mit erweiterten Parametern und Vorschau.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-monetizer'); ?>'">
                                    <span class="dashicons dashicons-shortcode"></span> <?php esc_html_e('Generator öffnen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                        <!-- Keyword Analyzer -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-keyword-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-tag" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-keyword-title"><?php esc_html_e('Keyword-Analyzer', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Analysiere Textinhalte und erhalte KI-gestützte Keyword-Vorschläge.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" id="open-keyword-analyzer">
                                    <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Analyzer öffnen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                        <!-- Bulk Operations -->
                        <article class="tool-section tool-section--utility" aria-labelledby="utility-bulk-title">
                            <header class="tool-section__header">
                                <span class="tool-section__icon dashicons dashicons-editor-ul" aria-hidden="true"></span>
                                <div class="tool-section__titles">
                                    <h3 id="utility-bulk-title"><?php esc_html_e('Massenaktionen', 'yadore-monetizer'); ?></h3>
                                    <p class="tool-section__description"><?php esc_html_e('Führe Massenaktionen für Beiträge, Keywords und Daten aus.', 'yadore-monetizer'); ?></p>
                                </div>
                            </header>
                            <footer class="tool-section__footer tool-actions">
                                <button type="button" class="button button-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=yadore-scanner'); ?>'">
                                    <span class="dashicons dashicons-editor-ul"></span> <?php esc_html_e('Scanner öffnen', 'yadore-monetizer'); ?>
                                </button>
                            </footer>
                        </article>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keyword Analyzer Modal -->
<div
    id="keyword-analyzer-modal"
    class="yadore-modal hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="keyword-analyzer-title"
    aria-hidden="true"
>
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="keyword-analyzer-title"><?php esc_html_e('Keyword-Analyzer', 'yadore-monetizer'); ?></h2>
                <button class="modal-close" type="button" aria-label="<?php echo esc_attr__('Keyword-Analyzer schließen', 'yadore-monetizer'); ?>">&times;</button>
            </div>
            <div class="modal-body">
                <div class="analyzer-input">
                    <label for="analyzer-text"><?php esc_html_e('Zu analysierender Text:', 'yadore-monetizer'); ?></label>
                    <textarea id="analyzer-text" rows="8" placeholder="<?php echo esc_attr__('Füge hier deine Inhalte für die Keyword-Analyse ein …', 'yadore-monetizer'); ?>"></textarea>
                </div>
                <div class="analyzer-options">
                    <label>
                        <input type="checkbox" id="use-ai-analyzer" <?php checked(get_option('yadore_ai_enabled', false)); ?>> <?php esc_html_e('KI-Analyse verwenden', 'yadore-monetizer'); ?>
                    </label>
                    <label>
                        <?php esc_html_e('Maximale Keywords:', 'yadore-monetizer'); ?> <input type="number" id="max-keywords" min="1" max="20" value="5">
                    </label>
                </div>
                <div class="analyzer-results hidden" id="analyzer-results" aria-live="polite">
                    <h4><?php esc_html_e('Vorgeschlagene Keywords:', 'yadore-monetizer'); ?></h4>
                    <div class="keyword-suggestions"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button button-primary" id="analyze-keywords">
                    <span class="dashicons dashicons-search"></span> <?php esc_html_e('Keywords analysieren', 'yadore-monetizer'); ?>
                </button>
                <button type="button" class="button button-secondary modal-close"><?php esc_html_e('Schließen', 'yadore-monetizer'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function() {
    if (window.yadoreAdmin && typeof window.yadoreAdmin.initTools === 'function') {
        window.yadoreAdmin.initTools();
    }

    if (window.yadoreAdmin && typeof window.yadoreAdmin.loadToolStats === 'function') {
        window.yadoreAdmin.loadToolStats();
    }
});
</script>
