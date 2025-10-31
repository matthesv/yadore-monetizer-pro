/* Yadore Monetizer Pro v3.48.21 - Admin Translations Helper */

(function () {
    'use strict';

    var ready = function (callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }

        document.addEventListener('DOMContentLoaded', callback);
    };

    ready(function () {
        var rowsContainer = document.querySelector('#yadore-translation-rows');
        var addButton = document.querySelector('#yadore-add-translation');
        var template = document.querySelector('#yadore-translation-row-template');
        var removedContainer = document.querySelector('#yadore-translation-removed');

        if (!rowsContainer || !addButton || !template || !removedContainer) {
            return;
        }

        addButton.addEventListener('click', function (event) {
            event.preventDefault();

            var fragment;

            if ('content' in template) {
                fragment = document.importNode(template.content, true);
            } else {
                fragment = document.createDocumentFragment();
                var fallbackContainer = document.createElement('tbody');
                fallbackContainer.innerHTML = template.innerHTML;
                while (fallbackContainer.firstElementChild) {
                    fragment.appendChild(fallbackContainer.firstElementChild);
                }
            }

            if (!fragment) {
                return;
            }

            var row = fragment.querySelector ? fragment.querySelector('tr') : fragment.firstElementChild;

            rowsContainer.appendChild(fragment);

            if (row) {
                var focusTarget = row.querySelector('input, textarea');
                if (focusTarget) {
                    focusTarget.focus();
                }
            }
        });

        rowsContainer.addEventListener('click', function (event) {
            var trigger = event.target.closest('.yadore-remove-translation');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            var row = trigger.closest('tr');

            if (!row) {
                return;
            }

            var originalInput = row.querySelector('input[name="translation_original_keys[]"]');
            var originalValue = originalInput && originalInput.value ? originalInput.value.trim() : '';

            var rows = rowsContainer.querySelectorAll('tr');

            if (rows.length <= 1) {
                if (originalValue !== '') {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'translation_removed_keys[]';
                    hidden.value = originalValue;
                    removedContainer.appendChild(hidden);
                }

                Array.prototype.forEach.call(row.querySelectorAll('input, textarea'), function (field) {
                    field.value = '';
                });

                if (originalInput) {
                    originalInput.value = '';
                }
                return;
            }

            if (originalValue !== '') {
                var removedInput = document.createElement('input');
                removedInput.type = 'hidden';
                removedInput.name = 'translation_removed_keys[]';
                removedInput.value = originalValue;
                removedContainer.appendChild(removedInput);
            }

            row.parentNode.removeChild(row);
        });
    });
})();
