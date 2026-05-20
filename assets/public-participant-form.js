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

    function getMessages() {
        var fallback = {
            consentErrorIntro: 'Bitte bestätige vor dem Absenden die folgenden Pflichtzustimmungen:',
            privacyConsentMissing: 'Datenschutzerklärung akzeptieren',
            accuracyConsentMissing: 'Echtheit der Daten bestätigen',
            dateRangePassportError: 'Reisepass gültig bis muss am oder nach Reisepass gültig von liegen.',
            dateRangeResidenceError: 'Aufenthaltstitel gültig bis muss am oder nach Aufenthaltstitel gültig von liegen.',
            dateRangeTravelError: 'Reisedatum bis muss am oder nach Reisedatum von liegen.'
        };

        if (!window.AlpeniaPublicParticipantForm || !window.AlpeniaPublicParticipantForm.messages) {
            return fallback;
        }

        return Object.assign({}, fallback, window.AlpeniaPublicParticipantForm.messages);
    }


    function parseIsoDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
            return null;
        }

        var date = new Date(value + 'T00:00:00Z');
        if (Number.isNaN(date.getTime())) {
            return null;
        }

        return date;
    }

    function validateDateRanges(form, messages) {
        var dateChecks = [
            {
                start: 'passport_valid_from_date',
                end: 'passport_expiry_date',
                message: messages.dateRangePassportError
            },
            {
                start: 'residence_permit_start_date',
                end: 'residence_permit_valid_until',
                message: messages.dateRangeResidenceError
            }
        ];

        var isValid = true;

        dateChecks.forEach(function (check) {
            var startField = form.querySelector('[name="' + check.start + '"]');
            var endField = form.querySelector('[name="' + check.end + '"]');
            if (!startField || !endField || startField.disabled || endField.disabled) {
                return;
            }

            endField.setCustomValidity('');

            if (startField.value === '' || endField.value === '') {
                return;
            }

            var startDate = parseIsoDate(startField.value);
            var endDate = parseIsoDate(endField.value);
            if (!startDate || !endDate) {
                return;
            }

            if (startDate > endDate) {
                endField.setCustomValidity(check.message);
                isValid = false;
            }
        });

        var travelStart = form.querySelector('[name="trip_date_start"]');
        var travelEnd = form.querySelector('[name="trip_date_end"]');
        if (travelStart && travelEnd) {
            travelEnd.setCustomValidity('');
            if (travelStart.value !== '' && travelEnd.value !== '') {
                var tStart = parseIsoDate(travelStart.value);
                var tEnd = parseIsoDate(travelEnd.value);
                if (tStart && tEnd && tStart > tEnd) {
                    travelEnd.setCustomValidity(messages.dateRangeTravelError);
                    isValid = false;
                }
            }
        }

        return isValid;
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
        var forms = document.querySelectorAll('.alpenia-public-form form');

        forms.forEach(function (form) {
            var nationalityField = form.querySelector('[data-alpenia-nationality]');
            if (!nationalityField) {
                return;
            }

            toggleResidencePermit(form, countryList);
            ['input', 'change'].forEach(function (eventName) {
                nationalityField.addEventListener(eventName, function () {
                    toggleResidencePermit(form, countryList);
                });
            });
        });

        document.querySelectorAll('input[list="alpenia-public-country-list"], input[list="alpenia-country-list"], input[list="alpenia-country-list-edit"]').forEach(function (input) {
            input.addEventListener('focus', function () {
                input.select();
            });
        });

        var messages = getMessages();
        forms.forEach(function (form) {
            var privacyConsent = form.querySelector('input[name="privacy_consent"]');
            var accuracyConsent = form.querySelector('input[name="accuracy_consent"]');
            if (!privacyConsent || !accuracyConsent) {
                return;
            }

            var consentSection = privacyConsent.closest('.alpenia-public-section--consent');
            var errorBox = document.createElement('div');
            errorBox.className = 'alpenia-public-message alpenia-public-error';
            errorBox.hidden = true;
            if (consentSection) {
                consentSection.prepend(errorBox);
            }

            function clearConsentError() {
                errorBox.hidden = true;
                errorBox.textContent = '';
            }

            function validateConsents() {
                var missing = [];
                if (!privacyConsent.checked) {
                    missing.push(messages.privacyConsentMissing);
                }
                if (!accuracyConsent.checked) {
                    missing.push(messages.accuracyConsentMissing);
                }

                if (missing.length === 0) {
                    clearConsentError();
                    return true;
                }

                errorBox.textContent = messages.consentErrorIntro + ' ' + missing.join(' • ');
                errorBox.hidden = false;
                return false;
            }

            [privacyConsent, accuracyConsent].forEach(function (input) {
                input.addEventListener('change', validateConsents);
                input.addEventListener('input', validateConsents);
            });

            ['input', 'change'].forEach(function (eventName) {
                form.addEventListener(eventName, function () {
                    validateDateRanges(form, messages);
                });
            });

            form.addEventListener('submit', function (event) {
                var datesValid = validateDateRanges(form, messages);
                if (!datesValid || !validateConsents()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        });
    });
}());
