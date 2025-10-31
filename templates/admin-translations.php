<?php
if (!defined('ABSPATH')) {
    exit;
}

$locales = isset($translation_locales) && is_array($translation_locales) ? $translation_locales : array();

if (empty($locales)) {
    $locales = array(
        'de_DE' => __('German (de_DE)', 'yadore-monetizer'),
        'en_US' => __('English (en_US)', 'yadore-monetizer'),
    );
}

$pagination = isset($translation_pagination) && is_array($translation_pagination) ? $translation_pagination : array();
$current_page = isset($pagination['current_page']) ? max(1, (int) $pagination['current_page']) : 1;
$per_page = isset($pagination['per_page']) ? max(1, (int) $pagination['per_page']) : 1;
$total_items = isset($pagination['total_items']) ? max(0, (int) $pagination['total_items']) : 0;
$total_pages = isset($pagination['total_pages']) ? max(1, (int) $pagination['total_pages']) : 1;
$offset = isset($pagination['offset']) ? max(0, (int) $pagination['offset']) : ($current_page - 1) * $per_page;

$entries = array();

if (isset($translation_entries) && is_array($translation_entries)) {
    $entries = array_values($translation_entries);
} elseif (isset($custom_translations) && is_array($custom_translations)) {
    foreach ($custom_translations as $key => $values) {
        if (!is_string($key)) {
            continue;
        }

        $locale_entries = array();

        if (is_array($values)) {
            foreach ($values as $locale_key => $value) {
                if (!is_string($locale_key)) {
                    continue;
                }

                $locale_entries[$locale_key] = array(
                    'default' => '',
                    'value' => is_string($value) ? $value : '',
                );
            }
        }

        $entries[] = array(
            'key' => $key,
            'locales' => $locale_entries,
        );
    }
}

if (empty($entries)) {
    $blank_locales = array();

    foreach ($locales as $locale => $label) {
        $blank_locales[$locale] = array(
            'default' => '',
            'value' => '',
        );
    }

    $entries[] = array(
        'key' => '',
        'locales' => $blank_locales,
    );
}

$notices = isset($translation_notices) && is_array($translation_notices) ? $translation_notices : array();

$displayed_items = count($entries);
$start_item = $total_items > 0 ? $offset + 1 : 0;
$end_item = $total_items > 0 ? min($offset + $displayed_items, $total_items) : 0;
$base_url = add_query_arg(array('page' => 'yadore-translations'), admin_url('admin.php'));
$pagination_links = array();

if ($total_pages > 1) {
    $pagination_links = paginate_links(array(
        'base' => esc_url_raw(add_query_arg('paged', '%#%', $base_url)),
        'format' => '',
        'current' => $current_page,
        'total' => $total_pages,
        'type' => 'array',
        'prev_text' => __('« Previous', 'yadore-monetizer'),
        'next_text' => __('Next »', 'yadore-monetizer'),
    ));
}
?>
<div class="wrap yadore-admin-wrap">
    <h1><?php esc_html_e('Custom Translation Manager', 'yadore-monetizer'); ?></h1>
    <p class="description">
        <?php
        printf(
            /* translators: %s: Option name used to store the translations. */
            esc_html__('Manage custom key-to-locale mappings. All entries are stored in the %s option and merged with the bundled catalogue before filters run.', 'yadore-monetizer'),
            '<code>' . esc_html(YadoreMonetizer::CUSTOM_TRANSLATIONS_OPTION) . '</code>'
        );
        ?>
    </p>

    <?php if (!empty($notices)) : ?>
        <?php foreach ($notices as $notice) :
            $type = isset($notice['type']) ? $notice['type'] : 'info';
            $message = isset($notice['message']) ? $notice['message'] : '';

            if ($message === '') {
                continue;
            }

            $class = 'notice';

            if ($type === 'success') {
                $class .= ' notice-success';
            } elseif ($type === 'error') {
                $class .= ' notice-error';
            } else {
                $class .= ' notice-info';
            }
            ?>
            <div class="<?php echo esc_attr($class); ?>">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=yadore-translations')); ?>" class="yadore-translations-form">
        <?php wp_nonce_field('yadore_save_translations', 'yadore_translations_nonce'); ?>
        <input type="hidden" name="paged" value="<?php echo (int) $current_page; ?>" />
        <div id="yadore-translation-removed" aria-hidden="true"></div>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary">
                        <?php esc_html_e('Original string', 'yadore-monetizer'); ?>
                    </th>
                    <?php foreach ($locales as $locale => $label) : ?>
                        <th scope="col" class="manage-column">
                            <?php echo esc_html($label); ?>
                        </th>
                    <?php endforeach; ?>
                    <th scope="col" class="manage-column" style="width: 120px;">
                        <?php esc_html_e('Actions', 'yadore-monetizer'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="yadore-translation-rows">
                <?php foreach ($entries as $index => $translation) :
                    $row_key = isset($translation['key']) ? (string) $translation['key'] : '';
                    $row_locales = isset($translation['locales']) && is_array($translation['locales']) ? $translation['locales'] : array();
                    ?>
                    <tr>
                        <td class="column-primary">
                            <label class="screen-reader-text" for="yadore-translation-key-<?php echo (int) $index; ?>">
                                <?php esc_html_e('Original string', 'yadore-monetizer'); ?>
                            </label>
                            <input type="hidden" name="translation_original_keys[]" value="<?php echo esc_attr($row_key); ?>" />
                            <input
                                type="text"
                                id="yadore-translation-key-<?php echo (int) $index; ?>"
                                name="translation_keys[]"
                                value="<?php echo esc_attr($row_key); ?>"
                                class="regular-text"
                                placeholder="<?php esc_attr_e('Source text', 'yadore-monetizer'); ?>"
                            />
                        </td>
                        <?php foreach ($locales as $locale => $label) :
                            $locale_data = isset($row_locales[$locale]) && is_array($row_locales[$locale]) ? $row_locales[$locale] : array();
                            $default_text = isset($locale_data['default']) ? (string) $locale_data['default'] : '';
                            $custom_value = isset($locale_data['value']) ? (string) $locale_data['value'] : '';
                            $textarea_value = $custom_value !== '' ? $custom_value : $default_text;
                            ?>
                            <td>
                                <label class="screen-reader-text" for="yadore-translation-value-<?php echo esc_attr($locale); ?>-<?php echo (int) $index; ?>">
                                    <?php
                                    printf(
                                        /* translators: %s: Locale code. */
                                        esc_html__('Translation for %s', 'yadore-monetizer'),
                                        esc_html($locale)
                                    );
                                    ?>
                                </label>
                                <textarea
                                    id="yadore-translation-value-<?php echo esc_attr($locale); ?>-<?php echo (int) $index; ?>"
                                    name="translation_values[<?php echo esc_attr($locale); ?>][]"
                                    rows="2"
                                    class="widefat"
                                    placeholder="<?php echo esc_attr(sprintf(/* translators: %s: Locale code. */ __('Translation (%s)', 'yadore-monetizer'), $locale)); ?>"
                                ><?php echo esc_textarea($textarea_value); ?></textarea>
                                <?php if ($default_text !== '') : ?>
                                    <p class="description yadore-default-translation">
                                        <strong><?php esc_html_e('Default:', 'yadore-monetizer'); ?></strong>
                                        <span><?php echo esc_html($default_text); ?></span>
                                    </p>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="yadore-translation-actions">
                            <button type="button" class="button link-delete yadore-remove-translation">
                                <?php esc_html_e('Remove', 'yadore-monetizer'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="tablenav-paging-text">
            <?php
            if ($total_items > 0) {
                printf(
                    /* translators: 1: first item number on the page, 2: last item number on the page, 3: total items. */
                    esc_html__('Displaying %1$s–%2$s of %3$s entries', 'yadore-monetizer'),
                    esc_html(number_format_i18n($start_item)),
                    esc_html(number_format_i18n($end_item)),
                    esc_html(number_format_i18n($total_items))
                );
            } else {
                esc_html_e('No translation entries found yet.', 'yadore-monetizer');
            }
            ?>
        </p>

        <?php if (!empty($pagination_links)) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php foreach ($pagination_links as $link) : ?>
                            <?php echo wp_kses_post($link); ?>
                        <?php endforeach; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <p>
            <button type="button" class="button button-secondary" id="yadore-add-translation">
                <?php esc_html_e('Add translation row', 'yadore-monetizer'); ?>
            </button>
        </p>

        <?php submit_button(__('Save translations', 'yadore-monetizer')); ?>
    </form>

    <template id="yadore-translation-row-template">
        <tr>
            <td class="column-primary">
                <label class="screen-reader-text" for="yadore-translation-key-new"><?php esc_html_e('Original string', 'yadore-monetizer'); ?></label>
                <input type="hidden" name="translation_original_keys[]" value="" />
                <input
                    type="text"
                    id="yadore-translation-key-new"
                    name="translation_keys[]"
                    value=""
                    class="regular-text"
                    placeholder="<?php esc_attr_e('Source text', 'yadore-monetizer'); ?>"
                />
            </td>
            <?php foreach ($locales as $locale => $label) : ?>
                <td>
                    <label class="screen-reader-text" for="yadore-translation-value-<?php echo esc_attr($locale); ?>-new">
                        <?php
                        printf(
                            /* translators: %s: Locale code. */
                            esc_html__('Translation for %s', 'yadore-monetizer'),
                            esc_html($locale)
                        );
                        ?>
                    </label>
                    <textarea
                        id="yadore-translation-value-<?php echo esc_attr($locale); ?>-new"
                        name="translation_values[<?php echo esc_attr($locale); ?>][]"
                        rows="2"
                        class="widefat"
                        placeholder="<?php echo esc_attr(sprintf(/* translators: %s: Locale code. */ __('Translation (%s)', 'yadore-monetizer'), $locale)); ?>"
                    ></textarea>
                </td>
            <?php endforeach; ?>
            <td class="yadore-translation-actions">
                <button type="button" class="button link-delete yadore-remove-translation">
                    <?php esc_html_e('Remove', 'yadore-monetizer'); ?>
                </button>
            </td>
        </tr>
    </template>
</div>
