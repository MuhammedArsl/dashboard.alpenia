(function () {
    'use strict';

    function normalize(value) {
        return (value || '').toString().trim().toLocaleLowerCase('de-DE');
    }

    function getEuSchengenCountries() {
        if (!window.AlpeniaPublicParticipantForm || !Array.isArray(window.AlpeniaPublicParticipantForm.euSchengenCountries)) {
            return [];
        }

        return window.AlpeniaPublicParticipantForm.euSchengenCountries.map(normalize);
    }

    function toggleResidencePermit(form, countryList) {
        var nationalityField = form.querySelector('[data-alpenia-nationality]');
        if (!nationalityField) {
            return;
        }

        var value = normalize(nationalityField.value);
        var requiresResidencePermit = value !== '' && countryList.indexOf(value) === -1;
        var conditionalSections = form.querySelectorAll('[data-alpenia-residence-section]');
        var conditionalInputs = form.querySelectorAll('[data-alpenia-residence-field] input, [data-alpenia-residence-field] select');
        var uploadInput = form.querySelector('[data-alpenia-residence-upload]');

        conditionalSections.forEach(function (section) {
            section.hidden = !requiresResidencePermit;
        });

        conditionalInputs.forEach(function (input) {
            input.disabled = !requiresResidencePermit;
            input.required = requiresResidencePermit;
        });

        if (uploadInput) {
            uploadInput.disabled = !requiresResidencePermit;
            uploadInput.required = requiresResidencePermit;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var publicForm = document.querySelector('.alpenia-public-form');
        if (publicForm && document.body) {
            document.body.classList.add('alpenia-public-form-active');
        }

        var countryList = getEuSchengenCountries();
        document.querySelectorAll('.alpenia-public-form form').forEach(function (form) {
            var nationalityField = form.querySelector('[data-alpenia-nationality]');
            if (!nationalityField) {
                return;
            }

            toggleResidencePermit(form, countryList);
            nationalityField.addEventListener('input', function () {
                toggleResidencePermit(form, countryList);
            });
            nationalityField.addEventListener('change', function () {
                toggleResidencePermit(form, countryList);
            });
        });
    });
}());
