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

$translations = array();

if (isset($custom_translations) && is_array($custom_translations) && !empty($custom_translations)) {
    foreach ($custom_translations as $key => $values) {
        $translations[] = array(
            'key' => $key,
            'values' => is_array($values) ? $values : array(),
        );
    }
}

if (empty($translations)) {
    $translations[] = array(
        'key' => '',
        'values' => array(),
    );
}

$notices = isset($translation_notices) && is_array($translation_notices) ? $translation_notices : array();
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
                <?php foreach ($translations as $index => $translation) :
                    $row_key = isset($translation['key']) ? (string) $translation['key'] : '';
                    $row_values = isset($translation['values']) && is_array($translation['values']) ? $translation['values'] : array();
                    ?>
                    <tr>
                        <td class="column-primary">
                            <label class="screen-reader-text" for="yadore-translation-key-<?php echo (int) $index; ?>">
                                <?php esc_html_e('Original string', 'yadore-monetizer'); ?>
                            </label>
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
                            $value = isset($row_values[$locale]) ? (string) $row_values[$locale] : '';
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
                                ><?php echo esc_textarea($value); ?></textarea>
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
