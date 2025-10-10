<?php
/**
 * Internal internationalization helpers for Yadore Monetizer Pro.
 */

if (!class_exists('Yadore_Monetizer_I18n')) {
    class Yadore_Monetizer_I18n {
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

            $file = YADORE_PLUGIN_DIR . 'languages/yadore-monetizer-' . $locale . '.php';
            if (!file_exists($file) && strpos($locale, 'de_') === 0) {
                $file = YADORE_PLUGIN_DIR . 'languages/yadore-monetizer-de_DE.php';
            }

            if (!file_exists($file)) {
                return;
            }

            $data = include $file;
            if (!is_array($data) || !isset($data['singular']) || !is_array($data['singular'])) {
                return;
            }

            self::$catalogue = array(
                'singular' => $data['singular'],
                'plural'   => isset($data['plural']) && is_array($data['plural']) ? $data['plural'] : array()
            );
            self::$locale = isset($data['locale']) ? $data['locale'] : $locale;

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
    }
}
