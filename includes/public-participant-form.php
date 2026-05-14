<?php
if (!defined('ABSPATH')) exit;

/**
 * Public participant self-registration form.
 *
 * Usage: add the shortcode [alpenia_participant_form] to a public page.
 * Optional: [alpenia_participant_form trip_id="123"] or append ?trip_id=123 to the page URL.
 */
function alpenia_public_participant_form_shortcode($atts = []) {
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (function_exists('alpenia_send_strict_no_cache_headers')) {
        alpenia_send_strict_no_cache_headers();
    } else {
        nocache_headers();
    }

    $atts = shortcode_atts([
        'trip_id' => 0,
    ], $atts, 'alpenia_participant_form');

    $fixed_trip_id = absint($atts['trip_id']);
    $query_trip_id = isset($_GET['trip_id']) ? absint(wp_unslash($_GET['trip_id'])) : 0;
    $selected_trip_id = $fixed_trip_id > 0 ? $fixed_trip_id : $query_trip_id;
    $message = '';
    $values = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alpenia_public_participant_submit'])) {
        $nonce = isset($_POST['alpenia_public_participant_nonce']) ? sanitize_text_field(wp_unslash($_POST['alpenia_public_participant_nonce'])) : '';
        $honeypot = isset($_POST['alpenia_company_website']) ? trim((string) wp_unslash($_POST['alpenia_company_website'])) : '';
        $selected_trip_id = $fixed_trip_id > 0 ? $fixed_trip_id : absint($_POST['trip_id'] ?? 0);

        $values = alpenia_public_participant_get_submitted_values();

        if ($nonce === '' || !wp_verify_nonce($nonce, 'alpenia_public_participant_form')) {
            $message = '<div class="alpenia-public-message alpenia-public-error">' . esc_html(alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.')) . '</div>';
        } elseif ($honeypot !== '') {
            $message = '<div class="alpenia-public-message alpenia-public-error">' . esc_html(alpenia_travel_t('Das Formular konnte nicht gesendet werden.')) . '</div>';
        } elseif (!alpenia_public_participant_trip_is_available($selected_trip_id)) {
            $message = '<div class="alpenia-public-message alpenia-public-error">' . esc_html(alpenia_travel_t('Bitte eine gültige Reise auswählen.')) . '</div>';
        } else {
            $result = alpenia_public_participant_create($selected_trip_id, $values);

            if (is_wp_error($result)) {
                $message = '<div class="alpenia-public-message alpenia-public-error">' . esc_html(alpenia_travel_t($result->get_error_message())) . '</div>';
            } else {
                $message = '<div class="alpenia-public-message alpenia-public-success">' . esc_html(alpenia_travel_t('Vielen Dank. Deine Teilnehmerdaten wurden erfolgreich übermittelt.')) . '</div>';
                $values = [];
                if ($fixed_trip_id <= 0) {
                    $selected_trip_id = 0;
                }
            }
        }
    }

    ob_start();
    ?>
    <div class="alpenia-public-participant-form-wrap">
        <style>
            .alpenia-public-participant-form-wrap{max-width:980px;margin:24px auto;padding:28px;border-radius:22px;background:#ffffff;color:#10251f;box-shadow:0 18px 50px rgba(16,37,31,.12);font-family:inherit}.alpenia-public-participant-form-wrap *{box-sizing:border-box}.alpenia-public-participant-form-wrap h2{margin:0 0 8px;font-size:clamp(26px,4vw,38px);color:#123f32}.alpenia-public-intro{margin:0 0 24px;color:#47645b}.alpenia-public-section{border-top:1px solid #e4ece8;margin-top:24px;padding-top:22px}.alpenia-public-section h3{margin:0 0 16px;color:#1d4d3f;font-size:20px}.alpenia-public-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px 18px}.alpenia-public-field label{display:block;font-weight:700;margin-bottom:7px}.alpenia-public-field input,.alpenia-public-field select{width:100%;border:1px solid #cfded8;border-radius:12px;padding:12px 14px;font:inherit;background:#fbfdfc;color:#10251f}.alpenia-public-field input:focus,.alpenia-public-field select:focus{outline:2px solid rgba(45,106,87,.25);border-color:#2d6a57}.alpenia-public-field small{display:block;color:#6a7f77;margin-top:5px}.alpenia-public-required{color:#b42318}.alpenia-public-hidden{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}.alpenia-public-message{padding:14px 16px;border-radius:14px;margin:0 0 20px;font-weight:700}.alpenia-public-error{background:#fff1f0;color:#a8071a}.alpenia-public-success{background:#edf9f1;color:#17663b}.alpenia-public-submit{margin-top:24px;border:0;border-radius:999px;padding:14px 24px;background:linear-gradient(135deg,#1d4d3f,#2d6a57);color:#fff;font-weight:800;cursor:pointer}.alpenia-public-submit:hover{filter:brightness(1.05)}
        </style>

        <h2><?php echo esc_html(alpenia_travel_t('Teilnehmerdaten übermitteln')); ?></h2>
        <p class="alpenia-public-intro"><?php echo esc_html(alpenia_travel_t('Bitte fülle deine persönlichen Daten vollständig aus. Deine Angaben werden direkt als Teilnehmerdatensatz gespeichert.')); ?></p>
        <?php echo wp_kses_post($message); ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?php wp_nonce_field('alpenia_public_participant_form', 'alpenia_public_participant_nonce'); ?>
            <input type="hidden" name="alpenia_public_participant_submit" value="1">
            <div class="alpenia-public-hidden" aria-hidden="true">
                <label for="alpenia_company_website">Website</label>
                <input type="text" id="alpenia_company_website" name="alpenia_company_website" tabindex="-1" autocomplete="off">
            </div>

            <?php if ($fixed_trip_id > 0) : ?>
                <input type="hidden" name="trip_id" value="<?php echo esc_attr($fixed_trip_id); ?>">
            <?php else : ?>
                <div class="alpenia-public-section">
                    <h3><?php echo esc_html(alpenia_travel_t('Reise')); ?></h3>
                    <div class="alpenia-public-grid">
                        <div class="alpenia-public-field">
                            <label for="alpenia_public_trip_id"><?php echo esc_html(alpenia_travel_t('Reise auswählen')); ?> <span class="alpenia-public-required">*</span></label>
                            <select id="alpenia_public_trip_id" name="trip_id" required>
                                <option value="0"><?php echo esc_html(alpenia_travel_t('Bitte wählen')); ?></option>
                                <?php foreach (alpenia_public_participant_get_trips() as $trip) : ?>
                                    <option value="<?php echo esc_attr($trip->ID); ?>" <?php selected($selected_trip_id, $trip->ID); ?>><?php echo esc_html($trip->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php alpenia_public_participant_render_fields($values); ?>
            <?php alpenia_public_participant_render_upload_fields(); ?>

            <button type="submit" class="alpenia-public-submit"><?php echo esc_html(alpenia_travel_t('Daten absenden')); ?></button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('alpenia_participant_form', 'alpenia_public_participant_form_shortcode');

function alpenia_public_participant_get_form_fields() {
    $allowed_keys = [
        'gender',
        'first_name',
        'second_first_name',
        'last_name',
        'birth_date',
        'street_address',
        'postal_city',
        'nationality',
        'passport_no',
        'passport_valid_from_date',
        'passport_expiry_date',
        'residence_permit_start_date',
        'residence_permit_number',
        'residence_permit_valid_until',
        'visa_entry_country',
        'visa_number',
        'visa_expiry_date',
        'phone_number',
        'email_address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    return array_intersect_key(alpenia_participant_field_definitions(), array_flip($allowed_keys));
}

function alpenia_public_participant_get_required_fields() {
    return ['gender', 'first_name', 'last_name', 'nationality', 'passport_valid_from_date', 'passport_expiry_date'];
}

function alpenia_public_participant_get_submitted_values() {
    $values = [];
    foreach (alpenia_public_participant_get_form_fields() as $meta_key => $field) {
        $raw_value = $_POST[$meta_key] ?? '';
        $values[$meta_key] = alpenia_participant_sanitize_field_value(wp_unslash($raw_value), $field);
    }
    return $values;
}

function alpenia_public_participant_create($trip_id, $values) {
    foreach (alpenia_public_participant_get_required_fields() as $required_key) {
        if (empty($values[$required_key])) {
            return new WP_Error('missing_required_fields', alpenia_travel_t('Bitte alle Pflichtfelder ausfüllen.'));
        }
    }

    $title = trim(($values['first_name'] ?? '') . ' ' . ($values['last_name'] ?? ''));
    $participant_id = wp_insert_post([
        'post_title'  => $title !== '' ? $title : alpenia_travel_t('Neuer Teilnehmer'),
        'post_type'   => 'trip_participant',
        'post_status' => 'publish',
        'post_author' => 0,
    ], true);

    if (is_wp_error($participant_id)) {
        return $participant_id;
    }

    update_post_meta($participant_id, 'trip_id', absint($trip_id));
    update_post_meta($participant_id, 'participant_status', 'neu');

    foreach (alpenia_public_participant_get_form_fields() as $meta_key => $field) {
        alpenia_participant_write_field($participant_id, $meta_key, $values[$meta_key] ?? '', $field);
    }

    $upload_result = alpenia_public_participant_save_uploads($participant_id);
    if (is_wp_error($upload_result)) {
        wp_delete_post($participant_id, true);
        return $upload_result;
    }

    alpenia_send_notification('Neue Teilnehmerdaten übermittelt', 'Ein Teilnehmer hat öffentliche Formulardaten gesendet: ' . $title);
    return $participant_id;
}

function alpenia_public_participant_save_uploads($participant_id) {
    $upload_fields = [
        'passport_file' => 'passport_file_id',
        'photo_file' => 'photo_file_id',
        'visa_photo_file' => 'visa_photo_file_id',
        'meldezettel_file' => 'meldezettel_file_id',
    ];

    foreach ($upload_fields as $file_field => $meta_key) {
        $file_id = alpenia_handle_file_upload($file_field);
        if (is_wp_error($file_id)) {
            return $file_id;
        }
        if ($file_id) {
            update_post_meta($participant_id, $meta_key, $file_id);
        }
    }

    return true;
}

function alpenia_public_participant_render_fields($values) {
    $sections = [];
    foreach (alpenia_public_participant_get_form_fields() as $meta_key => $field) {
        $sections[$field['section']][] = [$meta_key, $field];
    }

    $required_fields = alpenia_public_participant_get_required_fields();

    foreach ($sections as $section_label => $section_fields) {
        echo '<div class="alpenia-public-section">';
        echo '<h3>' . esc_html(alpenia_travel_t($section_label)) . '</h3>';
        echo '<div class="alpenia-public-grid">';

        foreach ($section_fields as $field_pair) {
            [$meta_key, $field] = $field_pair;
            $input_id = 'alpenia_public_' . $meta_key;
            $value = $values[$meta_key] ?? '';
            $required = in_array($meta_key, $required_fields, true);

            echo '<div class="alpenia-public-field">';
            echo '<label for="' . esc_attr($input_id) . '">' . esc_html(alpenia_travel_t($field['label']));
            if ($required) {
                echo ' <span class="alpenia-public-required">*</span>';
            }
            echo '</label>';

            if (!empty($field['options'])) {
                echo '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($meta_key) . '"' . ($required ? ' required' : '') . '>';
                foreach ($field['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html(alpenia_travel_t($option_label)) . '</option>';
                }
                echo '</select>';
            } else {
                $input_type = $field['input_type'] ?? 'text';
                $list = $meta_key === 'nationality' ? ' list="alpenia-public-country-list"' : '';
                echo '<input type="' . esc_attr($input_type) . '" id="' . esc_attr($input_id) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '"' . $list . ($required ? ' required' : '') . '>';
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    echo '<datalist id="alpenia-public-country-list">';
    foreach (alpenia_get_all_countries() as $country_name) {
        echo '<option value="' . esc_attr($country_name) . '">';
    }
    echo '</datalist>';
}

function alpenia_public_participant_render_upload_fields() {
    ?>
    <div class="alpenia-public-section">
        <h3><?php echo esc_html(alpenia_travel_t('Dokumente')); ?></h3>
        <div class="alpenia-public-grid">
            <div class="alpenia-public-field">
                <label for="alpenia_public_passport_file"><?php echo esc_html(alpenia_travel_t('Reisepass hochladen')); ?></label>
                <input type="file" id="alpenia_public_passport_file" name="passport_file" accept=".pdf,.jpg,.jpeg,.png">
                <small><?php echo esc_html(alpenia_travel_t('PDF, JPG oder PNG, maximal 5 MB.')); ?></small>
            </div>
            <div class="alpenia-public-field">
                <label for="alpenia_public_photo_file"><?php echo esc_html(alpenia_travel_t('Porträtfoto hochladen')); ?></label>
                <input type="file" id="alpenia_public_photo_file" name="photo_file" accept=".jpg,.jpeg,.png,.webp">
                <small><?php echo esc_html(alpenia_travel_t('JPG, PNG oder WEBP, maximal 2 MB.')); ?></small>
            </div>
            <div class="alpenia-public-field">
                <label for="alpenia_public_visa_photo_file"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel hochladen')); ?></label>
                <input type="file" id="alpenia_public_visa_photo_file" name="visa_photo_file" accept=".jpg,.jpeg,.png,.webp">
                <small><?php echo esc_html(alpenia_travel_t('JPG, PNG oder WEBP, maximal 2 MB.')); ?></small>
            </div>
            <div class="alpenia-public-field">
                <label for="alpenia_public_meldezettel_file"><?php echo esc_html(alpenia_travel_t('Meldezettel hochladen')); ?></label>
                <input type="file" id="alpenia_public_meldezettel_file" name="meldezettel_file" accept=".pdf,.jpg,.jpeg,.png">
                <small><?php echo esc_html(alpenia_travel_t('PDF, JPG oder PNG, maximal 5 MB.')); ?></small>
            </div>
        </div>
    </div>
    <?php
}

function alpenia_public_participant_get_trips() {
    return get_posts([
        'post_type' => 'group_trip',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
}

function alpenia_public_participant_trip_is_available($trip_id) {
    if (!$trip_id) {
        return false;
    }

    $trip = get_post($trip_id);
    return $trip && $trip->post_type === 'group_trip' && $trip->post_status === 'publish';
}
