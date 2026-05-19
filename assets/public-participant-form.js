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
            accuracyConsentMissing: 'Echtheit der Daten bestätigen'
        };

        if (!window.AlpeniaPublicParticipantForm || !window.AlpeniaPublicParticipantForm.messages) {
            return fallback;
        }

        return Object.assign({}, fallback, window.AlpeniaPublicParticipantForm.messages);
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
            errorBox.setAttribute('role', 'alert');
            errorBox.setAttribute('aria-live', 'polite');
            errorBox.hidden = true;
            if (consentSection) {
                consentSection.prepend(errorBox);
            }

            function clearConsentError() {
                errorBox.hidden = true;
                errorBox.textContent = '';
                [privacyConsent, accuracyConsent].forEach(function (input) {
                    input.removeAttribute('aria-invalid');
                });
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
                [privacyConsent, accuracyConsent].forEach(function (input) {
                    if (!input.checked) {
                        input.setAttribute('aria-invalid', 'true');
                    } else {
                        input.removeAttribute('aria-invalid');
                    }
                });
                return false;
            }

            [privacyConsent, accuracyConsent].forEach(function (input) {
                input.addEventListener('change', validateConsents);
                input.addEventListener('input', validateConsents);
            });

            form.addEventListener('submit', function (event) {
                if (!validateConsents()) {
                    event.preventDefault();
                    event.stopPropagation();
                    var firstMissing = !privacyConsent.checked ? privacyConsent : (!accuracyConsent.checked ? accuracyConsent : null);
                    if (firstMissing) {
                        firstMissing.focus();
                    }
                }
            });
        });
    });
}());
