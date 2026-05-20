<?php
if (!defined('ABSPATH')) exit;

function alpenia_dashboard_count_label($count, $singular_label, $plural_label = null) {
    $count = (int) $count;
    $label = ($count === 1 || $plural_label === null) ? $singular_label : $plural_label;

    return sprintf('%d %s', $count, alpenia_travel_t($label));
}

function alpenia_dashboard_participant_count_label($count) {
    $count = (int) $count;
    $label = alpenia_travel_get_language() === 'tr' ? 'katılımcı' : alpenia_travel_t('Teilnehmer');

    return sprintf('%d %s', $count, $label);
}

function alpenia_dashboard_missing_docs_status_label($count) {
    $count = (int) $count;
    $template = $count === 1 ? '%d Unterlage fehlt' : '%d Unterlagen fehlen';

    return sprintf(alpenia_travel_t($template), $count);
}

function alpenia_dashboard_money_label($amount) {
    return '€ ' . number_format((float) $amount, 2, ',', '.');
}

function alpenia_get_participant_full_name($participant_id) {
    $first_name = alpenia_get_secure_meta($participant_id, 'first_name', true);
    $second_first_name = alpenia_get_secure_meta($participant_id, 'second_first_name', true);
    $last_name = alpenia_get_secure_meta($participant_id, 'last_name', true);

    return trim(implode(' ', array_filter([$first_name, $second_first_name, $last_name])));
}
