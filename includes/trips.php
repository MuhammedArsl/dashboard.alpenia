<?php
if (!defined('ABSPATH')) exit;

/**
 * Rechte auf Reise / Teilnehmer
 */
function alpenia_user_can_access_trip($trip_id) {
    if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) {
        return true;
    }

    $assigned_guide = (int) get_post_meta($trip_id, 'assigned_guide', true);
    $author_id = (int) get_post_field('post_author', $trip_id);

    return get_current_user_id() === $assigned_guide || get_current_user_id() === $author_id;
}

function alpenia_user_can_access_participant($participant_id) {
    $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);
    return $trip_id ? alpenia_user_can_access_trip($trip_id) : false;
}

/**
 * Reisen laden je nach Rechte + Filter
 */
function alpenia_get_trip_query_args($search = '', $type = '', $status = '', $country = '', $city = '', $assigned_guide = '') {
    $meta_query = [];

    if (!empty($type)) {
        $meta_query[] = [
            'key'   => 'trip_type',
            'value' => $type,
        ];
    }

    if (!empty($status)) {
        $meta_query[] = [
            'key'   => 'trip_status',
            'value' => $status,
        ];
    }

    if (!empty($country)) {
        $meta_query[] = [
            'key'   => 'country',
            'value' => $country,
        ];
    }

    if (!empty($city)) {
        $meta_query[] = [
            'key'   => 'city',
            'value' => $city,
        ];
    }

    if (!empty($assigned_guide)) {
        $meta_query[] = [
            'key'   => 'assigned_guide',
            'value' => (int) $assigned_guide,
        ];
    }

    $args = [
        'post_type'   => 'group_trip',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ];

    if (!empty($search)) {
        $args['s'] = $search;
    }

    if (!empty($meta_query)) {
        if (count($meta_query) > 1) {
            $args['meta_query'] = array_merge(['relation' => 'AND'], $meta_query);
        } else {
            $args['meta_query'] = $meta_query;
        }
    }

    return $args;
}

function alpenia_get_filtered_trips($search = '', $type = '', $status = '', $country = '', $city = '', $assigned_guide = '') {
    $trips = get_posts(alpenia_get_trip_query_args($search, $type, $status, $country, $city, $assigned_guide));

    if (!alpenia_is_admin_user() && !alpenia_is_backoffice_user()) {
        $trips = array_filter($trips, function($trip) {
            return alpenia_user_can_access_trip($trip->ID);
        });
    }

    return $trips;
}

function alpenia_get_paginated_filtered_trips($search = '', $type = '', $status = '', $country = '', $city = '', $assigned_guide = '', $page = 1, $per_page = 20) {
    $page = max(1, (int) $page);
    $per_page = max(1, min(100, (int) $per_page));

    if (!alpenia_is_admin_user() && !alpenia_is_backoffice_user()) {
        $trips = array_values(alpenia_get_filtered_trips($search, $type, $status, $country, $city, $assigned_guide));
        $total = count($trips);

        return [
            'posts' => array_slice($trips, ($page - 1) * $per_page, $per_page),
            'total' => $total,
            'max_pages' => max(1, (int) ceil($total / $per_page)),
        ];
    }

    $args = alpenia_get_trip_query_args($search, $type, $status, $country, $city, $assigned_guide);
    unset($args['numberposts']);
    $args['posts_per_page'] = $per_page;
    $args['paged'] = $page;
    $args['no_found_rows'] = false;

    $query = new WP_Query($args);

    return [
        'posts' => $query->posts,
        'total' => (int) $query->found_posts,
        'max_pages' => max(1, (int) $query->max_num_pages),
    ];
}

/**
 * Teilnehmer einer Reise
 */
function alpenia_get_trip_participants($trip_id) {
    if (!alpenia_user_can_access_trip($trip_id)) {
        return [];
    }

    return get_posts([
        'post_type'   => 'trip_participant',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'numberposts' => -1,
        'meta_key'    => 'trip_id',
        'meta_value'  => $trip_id,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);
}

function alpenia_count_trip_participants($trip_id) {
    if (!alpenia_user_can_access_trip($trip_id)) {
        return 0;
    }

    $query = new WP_Query([
        'post_type'      => 'trip_participant',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => 'trip_id',
        'meta_value'     => (int) $trip_id,
        'no_found_rows'  => false,
    ]);

    return (int) $query->found_posts;
}

function alpenia_participant_matches_dashboard_filters($participant_id, $filters = []) {
    $search = strtolower(trim((string) ($filters['search'] ?? '')));

    if ($search !== '') {
        $searchable_values = [
            get_the_title($participant_id),
            alpenia_get_secure_meta($participant_id, 'first_name', true),
            alpenia_get_secure_meta($participant_id, 'last_name', true),
            alpenia_get_secure_meta($participant_id, 'passport_no', true),
            alpenia_get_secure_meta($participant_id, 'email', true),
        ];
        $haystack = strtolower(implode(' ', array_map('strval', $searchable_values)));

        if (strpos($haystack, $search) === false) {
            return false;
        }
    }

    $doc_status = sanitize_key((string) ($filters['doc_status'] ?? ''));
    if ($doc_status === 'missing' && alpenia_get_participant_doc_score($participant_id) === 'complete') {
        return false;
    }
    if ($doc_status === 'complete' && alpenia_get_participant_doc_score($participant_id) !== 'complete') {
        return false;
    }

    $payment_status = sanitize_key((string) ($filters['payment_status'] ?? ''));
    if ($payment_status !== '') {
        $payment_open = alpenia_get_participant_payment_open($participant_id);
        $current_payment_status = alpenia_get_payment_status($participant_id);

        if ($payment_status === 'open' && $payment_open <= 0) {
            return false;
        }
        if ($payment_status === 'paid' && $current_payment_status !== 'bezahlt') {
            return false;
        }
        if ($payment_status === 'partial' && $current_payment_status !== 'teilweise bezahlt') {
            return false;
        }
    }

    $participant_status = sanitize_key((string) ($filters['participant_status'] ?? ''));
    if ($participant_status !== '' && sanitize_key((string) get_post_meta($participant_id, 'participant_status', true)) !== $participant_status) {
        return false;
    }

    return true;
}

function alpenia_get_paginated_trip_participants($trip_id, $filters = [], $page = 1, $per_page = 50) {
    $page = max(1, (int) $page);
    $per_page = max(1, min(100, (int) $per_page));
    $participants = array_values(alpenia_get_trip_participants($trip_id));

    if (!empty($filters)) {
        $participants = array_values(array_filter($participants, function($participant) use ($filters) {
            return alpenia_participant_matches_dashboard_filters($participant->ID, $filters);
        }));
    }

    $total = count($participants);

    return [
        'posts' => array_slice($participants, ($page - 1) * $per_page, $per_page),
        'total' => $total,
        'max_pages' => max(1, (int) ceil($total / $per_page)),
    ];
}

/**
 * Zahlungen / Status
 */
function alpenia_get_participant_payment_open($participant_id) {
    $total = (float) get_post_meta($participant_id, 'payment_total', true);
    $paid  = (float) get_post_meta($participant_id, 'payment_paid', true);
    return max(0, $total - $paid);
}

function alpenia_get_payment_status($participant_id) {
    $total = (float) get_post_meta($participant_id, 'payment_total', true);
    $paid  = (float) get_post_meta($participant_id, 'payment_paid', true);

    if ($total <= 0 && $paid <= 0) return 'offen';
    if ($paid <= 0) return 'offen';
    if ($paid < $total) return 'teilweise bezahlt';
    return 'bezahlt';
}

function alpenia_get_trip_capacity_left($trip_id) {
    $max_people = (int) get_post_meta($trip_id, 'max_people', true);
    $count = alpenia_count_trip_participants($trip_id);

    if ($max_people <= 0) return '-';
    return max(0, $max_people - $count);
}

/**
 * Dokumentstatus
 */
function alpenia_doc_status_label($attachment_id, $optional = false) {
    if ($attachment_id) {
        return '<span class="doc-ok">' . esc_html(alpenia_travel_t('Vorhanden')) . '</span>';
    }
    return $optional
        ? '<span class="doc-optional">' . esc_html(alpenia_travel_t('Optional')) . '</span>'
        : '<span class="doc-missing">' . esc_html(alpenia_travel_t('Nicht vorhanden')) . '</span>';
}

function alpenia_travel_translate_label($value) {
    $labels = [
        'offen' => alpenia_travel_t('offen'),
        'neu' => alpenia_travel_t('neu'),
        'in_pruefung' => alpenia_travel_t('In Prüfung'),
        'in prüfung' => alpenia_travel_t('In Prüfung'),
        'bezahlt' => alpenia_travel_t('bezahlt'),
        'nicht bezahlt' => alpenia_travel_t('nicht bezahlt'),
        'teilweise bezahlt' => alpenia_travel_t('teilweise bezahlt'),
        'vollstaendig' => alpenia_travel_t('Vollständig'),
        'umrah' => alpenia_travel_t('Umrah'),
        'hajj' => alpenia_travel_t('Hadsch'),
        'kultur' => alpenia_travel_t('Kulturreise'),
    ];

    $normalized = strtolower(trim((string) $value));

    return $labels[$normalized] ?? $value;
}

/**
 * Reise Status Badge
 */
function alpenia_trip_status_badge($status) {
    $status = sanitize_text_field($status);

    switch ($status) {
        case 'draft':
            return '<span class="status-badge status-gray">' . esc_html(alpenia_travel_t('Entwurf')) . '</span>';
        case 'open':
            return '<span class="status-badge status-green">' . esc_html(alpenia_travel_t('Offen')) . '</span>';
        case 'full':
            return '<span class="status-badge status-yellow">' . esc_html(alpenia_travel_t('Voll')) . '</span>';
        case 'closed':
            return '<span class="status-badge status-red">' . esc_html(alpenia_travel_t('Abgeschlossen')) . '</span>';
        default:
            return '<span class="status-badge status-gray">-</span>';
    }
}

function alpenia_get_participant_doc_score($participant_id) {
    $passport_file   = (int) get_post_meta($participant_id, 'passport_file_id', true);
    $photo_file      = (int) get_post_meta($participant_id, 'photo_file_id', true);
    $visa_photo_file = (int) get_post_meta($participant_id, 'visa_photo_file_id', true);

    $passport_valid_from = trim((string) alpenia_get_secure_meta($participant_id, 'passport_valid_from_date', true));
    $passport_expiry = trim((string) alpenia_get_secure_meta($participant_id, 'passport_expiry_date', true));
    $passport_no     = trim((string) alpenia_get_secure_meta($participant_id, 'passport_no', true));
    $nationality     = trim((string) alpenia_get_secure_meta($participant_id, 'nationality', true));
    $residence_permit_number = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_number', true));
    $residence_permit_start = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_start_date', true));
    $residence_permit_until = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_valid_until', true));

    $check_passport  = (int) get_post_meta($participant_id, 'check_passport', true);
    $check_photo     = (int) get_post_meta($participant_id, 'check_photo', true);
    $check_visa      = (int) get_post_meta($participant_id, 'check_visa', true);
    $check_payment   = (int) get_post_meta($participant_id, 'check_payment', true);

    $required_items = [];
    $filled_items   = 0;

    // Immer Pflicht
    $required_items[] = 'passport_file';
    if ($passport_file) $filled_items++;

    $required_items[] = 'photo_file';
    if ($photo_file) $filled_items++;

    $required_items[] = 'passport_valid_from';
    if ($passport_valid_from !== '') $filled_items++;

    $required_items[] = 'passport_expiry';
    if ($passport_expiry !== '') $filled_items++;

    $required_items[] = 'nationality';
    if ($nationality !== '') $filled_items++;

    $required_items[] = 'check_passport';
    if ($check_passport === 1) $filled_items++;

    $required_items[] = 'check_photo';
    if ($check_photo === 1) $filled_items++;

    $required_items[] = 'check_payment';
    if ($check_payment === 1) $filled_items++;

    // Nicht-EU-/Nicht-Schengen zusätzlich Pflicht
    if (!alpenia_is_eu_or_schengen_nationality($nationality)) {
        $required_items[] = 'residence_permit_number';
        if ($residence_permit_number !== '') $filled_items++;

        $required_items[] = 'residence_permit_start';
        if ($residence_permit_start !== '') $filled_items++;

        $required_items[] = 'residence_permit_until';
        if ($residence_permit_until !== '') $filled_items++;

        $required_items[] = 'visa_photo_file';
        if ($visa_photo_file) $filled_items++;

        $required_items[] = 'check_visa';
        if ($check_visa === 1) $filled_items++;
    }

    $required_count = count($required_items);

    if ($required_count > 0 && $filled_items === $required_count) {
        return 'complete';
    }

    if ($filled_items > 0) {
        return 'partial';
    }

    return 'missing';
}

function alpenia_get_participant_doc_badge($participant_id) {
    $score = alpenia_get_participant_doc_score($participant_id);

    if ($score === 'complete') {
        return '<span class="status-badge status-green">' . esc_html(alpenia_travel_t('Unterlagen komplett')) . '</span>';
    }

    if ($score === 'partial') {
        return '<span class="status-badge status-yellow">' . esc_html(alpenia_travel_t('Unterlagen teilweise')) . '</span>';
    }

    return '<span class="status-badge status-red">' . esc_html(alpenia_travel_t('Unterlagen fehlen')) . '</span>';
}

function alpenia_get_missing_docs_details($participant_id) {
    $missing = [];

    $passport_file   = (int) get_post_meta($participant_id, 'passport_file_id', true);
    $photo_file      = (int) get_post_meta($participant_id, 'photo_file_id', true);
    $visa_photo_file = (int) get_post_meta($participant_id, 'visa_photo_file_id', true);

    $passport_valid_from = trim((string) alpenia_get_secure_meta($participant_id, 'passport_valid_from_date', true));
    $passport_expiry = trim((string) alpenia_get_secure_meta($participant_id, 'passport_expiry_date', true));
    $nationality     = trim((string) alpenia_get_secure_meta($participant_id, 'nationality', true));
    $residence_permit_number = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_number', true));
    $residence_permit_start = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_start_date', true));
    $residence_permit_until = trim((string) alpenia_get_secure_meta($participant_id, 'residence_permit_valid_until', true));

    $check_passport  = (int) get_post_meta($participant_id, 'check_passport', true);
    $check_photo     = (int) get_post_meta($participant_id, 'check_photo', true);
    $check_visa      = (int) get_post_meta($participant_id, 'check_visa', true);
    $check_payment   = (int) get_post_meta($participant_id, 'check_payment', true);

    if (!$passport_file) $missing[] = 'Reisepass Datei';
    if (!$photo_file) $missing[] = 'Foto Datei';
    if ($passport_valid_from === '') $missing[] = 'Reisepass gültig von';
    if ($passport_expiry === '') $missing[] = 'Reisepass gültig bis';
    if ($nationality === '') $missing[] = 'Staatsbürgerschaft';
    if ($check_passport !== 1) $missing[] = 'Reisepass nicht geprüft';
    if ($check_photo !== 1) $missing[] = 'Foto nicht geprüft';
    if ($check_payment !== 1) $missing[] = 'Zahlung nicht geprüft';

    if (!alpenia_is_eu_or_schengen_nationality($nationality)) {
        if ($residence_permit_number === '') $missing[] = 'Aufenthaltstitel Nummer';
        if ($residence_permit_start === '') $missing[] = 'Aufenthaltstitel gültig von';
        if ($residence_permit_until === '') $missing[] = 'Aufenthaltstitel gültig bis';
        if (!$visa_photo_file) $missing[] = 'Aufenthaltstitel';
        if ($check_visa !== 1) $missing[] = 'Aufenthaltstitel nicht geprüft';
    }

    return $missing;
}

/**
 * Mail Helfer
 */
function alpenia_send_notification($subject, $message, $context = []) {
    $admin_email = get_option('admin_email');
    if (!$admin_email) {
        return;
    }

    $context = is_array($context) ? $context : [];
    $event_label = trim((string) ($context['event_label'] ?? 'Systembenachrichtigung'));
    $trip_label = trim((string) ($context['trip'] ?? ''));
    $participant_label = trim((string) ($context['participant'] ?? ''));
    $user_label = trim((string) ($context['user'] ?? ''));
    $trip_id = absint($context['trip_id'] ?? 0);
    $participant_id = absint($context['participant_id'] ?? 0);

    $rows = [];
    if ($trip_label !== '') {
        $rows[] = ['label' => 'Reise', 'value' => $trip_label];
    }
    if ($participant_label !== '') {
        $rows[] = ['label' => 'Teilnehmer', 'value' => $participant_label];
    }
    if ($user_label !== '') {
        $rows[] = ['label' => 'Erstellt von', 'value' => $user_label];
    }
    $rows[] = ['label' => 'Zeitpunkt', 'value' => wp_date('d.m.Y H:i')];

    $details_html = '';
    foreach ($rows as $row) {
        $details_html .= '<tr>';
        $details_html .= '<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;width:180px;">' . esc_html($row['label']) . '</td>';
        $details_html .= '<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#111827;font-size:13px;font-weight:600;">' . esc_html($row['value']) . '</td>';
        $details_html .= '</tr>';
    }

    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $dashboard_url = home_url('/');
    $trip_url = $trip_id > 0 && function_exists('alpenia_dashboard_link') ? alpenia_dashboard_link(['view_trip' => $trip_id]) : '';
    $participant_url = $participant_id > 0 && function_exists('alpenia_dashboard_link') ? alpenia_dashboard_link(['edit_participant' => $participant_id]) : '';

    $cta_buttons = '';
    if ($trip_url !== '') {
        $cta_buttons .= '<a href="' . esc_url($trip_url) . '" style="display:inline-block;padding:11px 16px;background:#0f7566;color:#ffffff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;margin-right:8px;">' . 'Zur Reise' . '</a>';
    }
    if ($participant_url !== '') {
        $cta_buttons .= '<a href="' . esc_url($participant_url) . '" style="display:inline-block;padding:11px 16px;background:#1b4d93;color:#ffffff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;">' . 'Zum Teilnehmer' . '</a>';
    }
    if ($cta_buttons === '') {
        $cta_buttons = '<a href="' . esc_url($dashboard_url) . '" style="display:inline-block;padding:11px 16px;background:#0f7566;color:#ffffff;text-decoration:none;border-radius:8px;font-size:13px;font-weight:600;">' . 'Dashboard öffnen' . '</a>';
    }

    $html_message = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">';
    $html_message .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f3f4f6;padding:28px 12px;"><tr><td align="center">';
    $html_message .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:680px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.08);">';
    $html_message .= '<tr><td style="padding:24px 28px;background:linear-gradient(135deg,#0f7566,#1b4d93);color:#ffffff;">';
    $html_message .= '<p style="margin:0 0 8px 0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.9;">Alpenia Travel</p>';
    $html_message .= '<h1 style="margin:0;font-size:24px;line-height:1.3;">' . esc_html($subject) . '</h1>';
    $html_message .= '<p style="margin:10px 0 0 0;font-size:14px;line-height:1.6;opacity:.95;">' . esc_html($event_label) . '</p>';
    $html_message .= '</td></tr>';
    $html_message .= '<tr><td style="padding:24px 28px 10px 28px;color:#111827;">';
    $html_message .= '<p style="margin:0 0 16px 0;font-size:15px;line-height:1.7;color:#374151;">' . nl2br(esc_html($message)) . '</p>';
    $html_message .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:10px;border-collapse:separate;border-spacing:0;overflow:hidden;">' . $details_html . '</table>';
    $html_message .= '</td></tr>';
    $html_message .= '<tr><td style="padding:18px 28px 28px 28px;">';
    $html_message .= $cta_buttons;
    $html_message .= '</td></tr>';
    $html_message .= '<tr><td style="padding:14px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">';
    $html_message .= esc_html($site_name) . ' · office@alpeniatravel.com';
    $html_message .= '</td></tr>';
    $html_message .= '</table></td></tr></table></body></html>';

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($admin_email, $subject, $html_message, $headers);
}
