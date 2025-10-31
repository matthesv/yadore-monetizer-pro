<?php
/**
 * Internal internationalization helpers for Yadore Monetizer Pro.
 */

if (!class_exists('Yadore_Monetizer_I18n')) {
    class Yadore_Monetizer_I18n {
        private const OPTION_NAME = 'yadore_custom_translations';

        /** @var array<string, mixed> */
        private static $catalogue = array();

        /** @var string|null */
        private static $locale = null;

        /** @var bool */
        private static $filters_registered = false;

        /**
         * Bootstraps the i18n helper by loading manual translations for the current locale.
         *
         * WordPress normally loads compiled MO files. Because the plugin ships only with text-based
         * resources we mirror that behaviour manually by exposing the translations via filters.
         */
        public static function boot() {
            $locale = determine_locale();
            if (!$locale) {
                return;
            }

            $catalogue = array(
                'singular' => array(),
                'plural' => array(),
            );

            $file = YADORE_PLUGIN_DIR . 'languages/yadore-monetizer-' . $locale . '.php';
            if (!file_exists($file) && strpos($locale, 'de_') === 0) {
                $file = YADORE_PLUGIN_DIR . 'languages/yadore-monetizer-de_DE.php';
            }

            if (file_exists($file)) {
                $data = include $file;

                if (is_array($data)) {
                    if (isset($data['singular']) && is_array($data['singular'])) {
                        $catalogue['singular'] = $data['singular'];
                    }

                    if (isset($data['plural']) && is_array($data['plural'])) {
                        $catalogue['plural'] = $data['plural'];
                    }

                    if (isset($data['locale']) && is_string($data['locale']) && $data['locale'] !== '') {
                        self::$locale = $data['locale'];
                    }
                }
            }

            if (self::$locale === null) {
                self::$locale = $locale;
            }

            $custom = self::load_custom_catalogue($locale);

            if (!empty($custom['singular'])) {
                $catalogue['singular'] = array_merge($catalogue['singular'], $custom['singular']);
            }

            if (!empty($custom['plural'])) {
                foreach ($custom['plural'] as $key => $forms) {
                    $catalogue['plural'][$key] = $forms;
                }
            }

            if (empty($catalogue['singular']) && empty($catalogue['plural'])) {
                return;
            }

            self::$catalogue = $catalogue;

            if (!self::$filters_registered) {
                add_filter('gettext_yadore-monetizer', array(__CLASS__, 'translate'), 10, 3);
                add_filter('ngettext_yadore-monetizer', array(__CLASS__, 'translate_plural'), 10, 5);
                self::$filters_registered = true;
            }
        }

        /**
         * Filters singular translations.
         *
         * @param string $translation
         * @param string $text
         * @param string $domain
         *
         * @return string
         */
        public static function translate($translation, $text, $domain) {
            if ($domain !== 'yadore-monetizer' || empty(self::$catalogue['singular'])) {
                return $translation;
            }

            if (isset(self::$catalogue['singular'][$text]) && self::$catalogue['singular'][$text] !== '') {
                return self::$catalogue['singular'][$text];
            }

            return $translation;
        }

        /**
         * Filters plural translations.
         *
         * @param string $translation
         * @param string $single
         * @param string $plural
         * @param int    $number
         * @param string $domain
         *
         * @return string
         */
        public static function translate_plural($translation, $single, $plural, $number, $domain) {
            if ($domain !== 'yadore-monetizer' || empty(self::$catalogue['plural'])) {
                return $translation;
            }

            if (!isset(self::$catalogue['plural'][$single]) || !is_array(self::$catalogue['plural'][$single])) {
                return $translation;
            }

            $forms = self::$catalogue['plural'][$single];
            $index = self::get_plural_index($number);

            if (isset($forms[$index]) && $forms[$index] !== '') {
                return $forms[$index];
            }

            return $translation;
        }

        /**
         * Calculates the plural form index for the current locale.
         *
         * @param int $number
         *
         * @return int
         */
        private static function get_plural_index($number) {
            if (self::$locale === 'de_DE') {
                return ($number == 1) ? 0 : 1;
            }

            return ($number == 1) ? 0 : 1;
        }

        /**
         * Loads custom translations stored via the admin UI and prepares them for the active locale.
         *
         * @param string $locale
         *
         * @return array{singular: array<string, string>, plural: array<string, array<int, string>>}
         */
        private static function load_custom_catalogue($locale) {
            $option = get_option(self::OPTION_NAME, array());

            if (isset($option['entries']) && is_array($option['entries'])) {
                $entries = $option['entries'];
            } elseif (isset($option['singular']) && is_array($option['singular'])) {
                $entries = $option['singular'];
            } elseif (is_array($option)) {
                $entries = $option;
            } else {
                $entries = array();
            }

            $plural_entries = array();

            if (isset($option['plural']) && is_array($option['plural'])) {
                $plural_entries = $option['plural'];
            }

            $variants = self::expand_locale_variants($locale);

            $singular = array();

            foreach ($entries as $key => $locales) {
                if (!is_string($key) || $key === '') {
                    continue;
                }

                if (is_array($locales)) {
                    foreach ($variants as $variant) {
                        if (isset($locales[$variant]) && is_string($locales[$variant]) && $locales[$variant] !== '') {
                            $singular[$key] = $locales[$variant];
                            break;
                        }
                    }
                } elseif (is_string($locales) && $locales !== '') {
                    $singular[$key] = $locales;
                }
            }

            $prepared_plural = array();

            foreach ($plural_entries as $key => $forms_by_locale) {
                if (!is_string($key) || $key === '') {
                    continue;
                }

                if (!is_array($forms_by_locale)) {
                    continue;
                }

                foreach ($variants as $variant) {
                    if (!isset($forms_by_locale[$variant]) || !is_array($forms_by_locale[$variant])) {
                        continue;
                    }

                    $forms = array();

                    foreach ($forms_by_locale[$variant] as $form) {
                        if (!is_scalar($form)) {
                            continue;
                        }

                        $form_value = (string) $form;

                        if ($form_value === '') {
                            continue;
                        }

                        $forms[] = $form_value;
                    }

                    if (!empty($forms)) {
                        $prepared_plural[$key] = $forms;
                        break;
                    }
                }
            }

            return array(
                'singular' => $singular,
                'plural' => $prepared_plural,
            );
        }

        /**
         * Returns possible locale variants (e.g. `de_DE`, `de`) for matching stored translations.
         *
         * @param string $locale
         *
         * @return array<int, string>
         */
        private static function expand_locale_variants($locale) {
            $variants = array();

            if (is_string($locale) && $locale !== '') {
                $variants[] = $locale;

                if (strpos($locale, '_') !== false) {
                    $segments = explode('_', $locale);

                    while (count($segments) > 1) {
                        array_pop($segments);

                        $variant = implode('_', $segments);

                        if ($variant !== '') {
                            $variants[] = $variant;
                        }
                    }
                }
            }

            $variants[] = 'default';
            $variants[] = 'all';

            $variants = array_values(array_unique(array_filter($variants)));

            return $variants;
        }
    }
}
