/* Yadore Monetizer Pro v3.48.20 - Admin Translations Helper */

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

        if (!rowsContainer || !addButton || !template) {
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

            var rows = rowsContainer.querySelectorAll('tr');

            if (rows.length <= 1) {
                Array.prototype.forEach.call(row.querySelectorAll('input, textarea'), function (field) {
                    field.value = '';
                });
                return;
            }

            row.parentNode.removeChild(row);
        });
    });
})();
