<?php
if (!defined('ABSPATH')) exit;

/**
 * WordPress admin + REST integration for Teilnehmer.
 *
 * The frontend dashboard stores participant details as post meta, with selected
 * fields going through alpenia_update_secure_meta(). Exposing the same field
 * list here lets admins create/edit participants in wp-admin and lets tools
 * such as Zapier discover/write those fields through the REST API.
 */
function alpenia_participant_field_definitions() {
    $fields = [
        'trip_id' => [
            'label' => 'Reise',
            'type' => 'integer',
            'sanitize' => 'int',
            'secure' => false,
            'section' => 'Zuordnung',
        ],
        'gender' => [
            'label' => 'Anrede',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => false,
            'section' => 'Persönliche Daten',
            'options' => ['' => 'Bitte wählen', 'Herr' => 'Herr', 'Frau' => 'Frau'],
        ],
        'first_name' => [
            'label' => 'Vorname',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Persönliche Daten',
        ],
        'second_first_name' => [
            'label' => '2. Vorname',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Persönliche Daten',
        ],
        'last_name' => [
            'label' => 'Nachname',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Persönliche Daten',
        ],
        'birth_date' => [
            'label' => 'Geburtsdatum',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Persönliche Daten',
            'input_type' => 'date',
        ],
        'nationality' => [
            'label' => 'Staatsbürgerschaft',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Reisedokumente',
        ],
        'residence_country' => [
            'label' => 'Wohnsitzland',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Reisedokumente',
        ],
        'passport_no' => [
            'label' => 'Reisepassnummer',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Reisedokumente',
        ],
        'passport_valid_from_date' => [
            'label' => 'Reisepass gültig von',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Reisedokumente',
            'input_type' => 'date',
        ],
        'passport_expiry_date' => [
            'label' => 'Reisepass gültig bis',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Reisedokumente',
            'input_type' => 'date',
        ],
        'residence_permit_start_date' => [
            'label' => 'Aufenthaltstitel gültig von',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
            'input_type' => 'date',
        ],
        'residence_permit_number' => [
            'label' => 'Aufenthaltstitel Nummer',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
        ],
        'residence_permit_valid_until' => [
            'label' => 'Aufenthaltstitel gültig bis',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
            'input_type' => 'date',
        ],
        'visa_entry_country' => [
            'label' => 'Visum-Einreiseland',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
        ],
        'visa_number' => [
            'label' => 'Visum Nummer',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
        ],
        'visa_expiry_date' => [
            'label' => 'Visum gültig bis',
            'type' => 'string',
            'sanitize' => 'date',
            'secure' => true,
            'section' => 'Aufenthaltstitel / Visum',
            'input_type' => 'date',
        ],
        'street_address' => [
            'label' => 'Straße',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Kontakt',
        ],
        'postal_city' => [
            'label' => 'PLZ / Ort',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Kontakt',
        ],
        'phone_number' => [
            'label' => 'Telefonnummer',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Kontakt',
        ],
        'email_address' => [
            'label' => 'E-Mail',
            'type' => 'string',
            'sanitize' => 'email',
            'secure' => true,
            'section' => 'Kontakt',
            'input_type' => 'email',
        ],
        'emergency_contact_name' => [
            'label' => 'Notfallkontakt Name',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Kontakt',
        ],
        'emergency_contact_phone' => [
            'label' => 'Notfallkontakt Telefon',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Kontakt',
        ],
        'participant_status' => [
            'label' => 'Bearbeitungsstatus',
            'type' => 'string',
            'sanitize' => 'key',
            'secure' => false,
            'section' => 'Status / Organisation',
            'options' => ['neu' => 'Neu', 'in_pruefung' => 'In Prüfung', 'vollstaendig' => 'Vollständig'],
        ],
        'room_assignment' => [
            'label' => 'Zimmer',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Status / Organisation',
        ],
        'subgroup' => [
            'label' => 'Untergruppe / Busgruppe',
            'type' => 'string',
            'sanitize' => 'text',
            'secure' => true,
            'section' => 'Status / Organisation',
        ],
        'payment_total' => [
            'label' => 'Gesamtpreis (€)',
            'type' => 'number',
            'sanitize' => 'float',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'number',
            'step' => '0.01',
        ],
        'payment_deposit' => [
            'label' => 'Anzahlung (€)',
            'type' => 'number',
            'sanitize' => 'float',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'number',
            'step' => '0.01',
        ],
        'payment_paid' => [
            'label' => 'Bezahlt (€)',
            'type' => 'number',
            'sanitize' => 'float',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'number',
            'step' => '0.01',
        ],
        'check_passport' => [
            'label' => 'Reisepass geprüft',
            'type' => 'boolean',
            'sanitize' => 'bool',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'checkbox',
        ],
        'check_photo' => [
            'label' => 'Foto geprüft',
            'type' => 'boolean',
            'sanitize' => 'bool',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'checkbox',
        ],
        'check_visa' => [
            'label' => 'Visum geprüft',
            'type' => 'boolean',
            'sanitize' => 'bool',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'checkbox',
        ],
        'check_payment' => [
            'label' => 'Zahlung geprüft',
            'type' => 'boolean',
            'sanitize' => 'bool',
            'secure' => false,
            'section' => 'Zahlungen / Checkliste',
            'input_type' => 'checkbox',
        ],
        'passport_file_id' => [
            'label' => 'Reisepass Datei-ID',
            'type' => 'integer',
            'sanitize' => 'int',
            'secure' => false,
            'section' => 'Dokumente',
            'input_type' => 'number',
            'step' => '1',
        ],
        'photo_file_id' => [
            'label' => 'Porträtfoto Datei-ID',
            'type' => 'integer',
            'sanitize' => 'int',
            'secure' => false,
            'section' => 'Dokumente',
            'input_type' => 'number',
            'step' => '1',
        ],
        'visa_photo_file_id' => [
            'label' => 'Visumfoto Datei-ID',
            'type' => 'integer',
            'sanitize' => 'int',
            'secure' => false,
            'section' => 'Dokumente',
            'input_type' => 'number',
            'step' => '1',
        ],
        'meldezettel_file_id' => [
            'label' => 'Meldezettel Datei-ID',
            'type' => 'integer',
            'sanitize' => 'int',
            'secure' => false,
            'section' => 'Dokumente',
            'input_type' => 'number',
            'step' => '1',
        ],
    ];

    foreach ($fields as &$field) {
        $field['label'] = alpenia_travel_t($field['label']);
        $field['section'] = alpenia_travel_t($field['section']);

        if (!empty($field['options']) && is_array($field['options'])) {
            foreach ($field['options'] as $option_value => $option_label) {
                $field['options'][$option_value] = alpenia_travel_t($option_label);
            }
        }
    }
    unset($field);

    return $fields;
}

function alpenia_participant_sanitize_field_value($value, $field) {
    switch ($field['sanitize'] ?? 'text') {
        case 'int':
            return absint($value);
        case 'float':
            return max(0, (float) $value);
        case 'bool':
            return !empty($value) && $value !== 'false' ? 1 : 0;
        case 'email':
            return sanitize_email((string) $value);
        case 'key':
            return sanitize_key((string) $value);
        case 'date':
            $value = sanitize_text_field((string) $value);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
        case 'text':
        default:
            return sanitize_text_field((string) $value);
    }
}

function alpenia_participant_read_field($participant_id, $meta_key, $field) {
    if (!empty($field['secure'])) {
        return alpenia_get_secure_meta($participant_id, $meta_key, true);
    }

    $value = get_post_meta($participant_id, $meta_key, true);
    if (($field['sanitize'] ?? '') === 'bool') {
        return !empty($value);
    }
    if (($field['sanitize'] ?? '') === 'int') {
        return $value === '' ? 0 : (int) $value;
    }
    if (($field['sanitize'] ?? '') === 'float') {
        return $value === '' ? 0 : (float) $value;
    }

    return $value;
}

function alpenia_participant_write_field($participant_id, $meta_key, $value, $field) {
    $value = alpenia_participant_sanitize_field_value($value, $field);

    if (!empty($field['secure'])) {
        return alpenia_update_secure_meta($participant_id, $meta_key, $value);
    }

    return update_post_meta($participant_id, $meta_key, $value);
}

function alpenia_participant_can_edit_rest_field($allowed, $meta_key, $post_id) {
    unset($allowed, $meta_key);
    return current_user_can('edit_post', $post_id);
}

function alpenia_register_participant_rest_fields() {
    foreach (alpenia_participant_field_definitions() as $meta_key => $field) {
        register_rest_field('trip_participant', $meta_key, [
            'get_callback' => function ($object) use ($meta_key, $field) {
                return alpenia_participant_read_field((int) $object['id'], $meta_key, $field);
            },
            'update_callback' => function ($value, $object) use ($meta_key, $field) {
                if (!$object || empty($object->ID) || !current_user_can('edit_post', $object->ID)) {
                    return false;
                }

                alpenia_participant_write_field((int) $object->ID, $meta_key, $value, $field);
                return true;
            },
            'schema' => [
                'description' => $field['label'],
                'type' => $field['type'],
                'context' => ['view', 'edit'],
            ],
        ]);
    }
}
add_action('rest_api_init', 'alpenia_register_participant_rest_fields');

function alpenia_register_participant_meta_for_tools() {
    foreach (alpenia_participant_field_definitions() as $meta_key => $field) {
        register_post_meta('trip_participant', $meta_key, [
            'single' => true,
            'type' => $field['type'],
            'show_in_rest' => false,
            'auth_callback' => 'alpenia_participant_can_edit_rest_field',
            'sanitize_callback' => function ($value) use ($field) {
                return alpenia_participant_sanitize_field_value($value, $field);
            },
        ]);
    }
}
add_action('init', 'alpenia_register_participant_meta_for_tools', 20);

function alpenia_add_participant_admin_metabox() {
    add_meta_box(
        'alpenia_participant_details',
        alpenia_travel_t('Teilnehmerdaten'),
        'alpenia_render_participant_admin_metabox',
        'trip_participant',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_trip_participant', 'alpenia_add_participant_admin_metabox');

function alpenia_render_participant_admin_metabox($post) {
    wp_nonce_field('alpenia_save_participant_admin_meta', 'alpenia_participant_admin_nonce');

    $fields = alpenia_participant_field_definitions();
    $sections = [];
    foreach ($fields as $meta_key => $field) {
        $sections[$field['section']][] = [$meta_key, $field];
    }

    echo '<style>.alpenia-admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 18px}.alpenia-admin-field label{display:block;font-weight:600;margin-bottom:4px}.alpenia-admin-field input:not([type=checkbox]),.alpenia-admin-field select{width:100%}.alpenia-admin-section{border-top:1px solid #dcdcde;margin-top:18px;padding-top:12px}.alpenia-admin-section:first-of-type{border-top:0;margin-top:0;padding-top:0}.alpenia-admin-help{color:#646970;margin-top:6px}</style>';
    echo '<p class="alpenia-admin-help"><strong>' . esc_html(alpenia_travel_t('Teilnehmer ID')) . ':</strong> ' . esc_html(alpenia_get_participant_display_id($post->ID)) . '</p>';
    echo '<p class="alpenia-admin-help">' . esc_html(alpenia_travel_t('Diese Felder entsprechen dem Teilnehmerformular im Dashboard und werden zusätzlich in der REST API für Automationen wie Zapier bereitgestellt.')) . '</p>';

    foreach ($sections as $section_label => $section_fields) {
        echo '<div class="alpenia-admin-section">';
        echo '<h3>' . esc_html($section_label) . '</h3>';
        echo '<div class="alpenia-admin-grid">';

        foreach ($section_fields as $field_pair) {
            [$meta_key, $field] = $field_pair;
            $value = alpenia_participant_read_field($post->ID, $meta_key, $field);
            $input_id = 'alpenia_participant_' . $meta_key;
            $input_name = 'alpenia_participant_meta[' . esc_attr($meta_key) . ']';

            echo '<div class="alpenia-admin-field">';
            echo '<label for="' . esc_attr($input_id) . '">' . esc_html($field['label']) . '</label>';

            if ($meta_key === 'trip_id') {
                $trips = get_posts([
                    'post_type' => 'group_trip',
                    'post_status' => ['publish', 'draft', 'pending', 'private'],
                    'numberposts' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]);

                echo '<select id="' . esc_attr($input_id) . '" name="' . $input_name . '">';
                echo '<option value="0">' . esc_html(alpenia_travel_t('Keine Reise zugeordnet')) . '</option>';
                foreach ($trips as $trip) {
                    echo '<option value="' . esc_attr($trip->ID) . '" ' . selected((int) $value, (int) $trip->ID, false) . '>' . esc_html($trip->post_title) . ' (' . esc_html(alpenia_get_trip_display_id($trip->ID)) . ')</option>';
                }
                echo '</select>';
            } elseif (!empty($field['options'])) {
                echo '<select id="' . esc_attr($input_id) . '" name="' . $input_name . '">';
                foreach ($field['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html($option_label) . '</option>';
                }
                echo '</select>';
            } elseif (($field['input_type'] ?? '') === 'checkbox') {
                echo '<input type="hidden" name="' . $input_name . '" value="0">';
                echo '<label><input type="checkbox" id="' . esc_attr($input_id) . '" name="' . $input_name . '" value="1" ' . checked((bool) $value, true, false) . '> ' . esc_html(alpenia_travel_t('Erledigt')) . '</label>';
            } else {
                $input_type = $field['input_type'] ?? 'text';
                $step = isset($field['step']) ? ' step="' . esc_attr($field['step']) . '"' : '';
                $min = $input_type === 'number' ? ' min="0"' : '';
                echo '<input type="' . esc_attr($input_type) . '" id="' . esc_attr($input_id) . '" name="' . $input_name . '" value="' . esc_attr($value) . '"' . $step . $min . '>';
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}

function alpenia_save_participant_admin_meta($post_id, $post) {
    static $updating_title = false;

    if ($updating_title || !$post || $post->post_type !== 'trip_participant') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['alpenia_participant_admin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['alpenia_participant_admin_nonce'])), 'alpenia_save_participant_admin_meta')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $submitted = isset($_POST['alpenia_participant_meta']) && is_array($_POST['alpenia_participant_meta']) ? wp_unslash($_POST['alpenia_participant_meta']) : [];
    $fields = alpenia_participant_field_definitions();

    foreach ($fields as $meta_key => $field) {
        if (array_key_exists($meta_key, $submitted)) {
            alpenia_participant_write_field($post_id, $meta_key, $submitted[$meta_key], $field);
        }
    }

    $first_name = alpenia_get_secure_meta($post_id, 'first_name', true);
    $last_name = alpenia_get_secure_meta($post_id, 'last_name', true);
    $generated_title = trim($first_name . ' ' . $last_name);

    if ($generated_title !== '' && $post->post_title !== $generated_title) {
        $updating_title = true;
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $generated_title,
        ]);
        $updating_title = false;
    }
}
add_action('save_post_trip_participant', 'alpenia_save_participant_admin_meta', 10, 2);
