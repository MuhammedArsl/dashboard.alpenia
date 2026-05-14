<?php
if (!defined('ABSPATH')) exit;

/**
 * Public participant self-registration form.
 *
 * Usage: [alpenia_participant_form trip_id="123"] or append ?trip_id=123 to the public registration page.
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

    alpenia_public_participant_enqueue_assets();

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
            $message = alpenia_public_participant_message('Sicherheitsfehler. Bitte erneut versuchen.', 'error');
        } elseif ($honeypot !== '') {
            $message = alpenia_public_participant_message('Das Formular konnte nicht gesendet werden.', 'error');
        } elseif (!alpenia_public_participant_trip_is_available($selected_trip_id)) {
            $message = alpenia_public_participant_message('Dieser Anmeldelink ist ungültig oder die Reise ist nicht öffentlich anmeldbar.', 'error');
        } else {
            $result = alpenia_public_participant_create($selected_trip_id, $values);

            if (is_wp_error($result)) {
                $message = alpenia_public_participant_message($result->get_error_message(), 'error');
            } else {
                $message = alpenia_public_participant_message('Vielen Dank. Deine Teilnehmerdaten wurden erfolgreich übermittelt.', 'success');
                $values = [];
            }
        }
    }

    $trip_available = alpenia_public_participant_trip_is_available($selected_trip_id);

    ob_start();
    ?>
    <div class="alpenia-public-form">
        <div class="alpenia-public-form__hero">
            <p class="alpenia-public-form__eyebrow"><?php echo esc_html(alpenia_travel_t('Alpenia Group Trips')); ?></p>
            <h2><?php echo esc_html(alpenia_travel_t('Teilnehmerdaten übermitteln')); ?></h2>
            <p class="alpenia-public-intro"><?php echo esc_html(alpenia_travel_t('Bitte fülle deine persönlichen Daten vollständig aus. Deine Angaben werden sicher als Teilnehmerdatensatz gespeichert.')); ?></p>
        </div>

        <?php echo wp_kses_post($message); ?>

        <?php if (!$selected_trip_id) : ?>
            <?php echo wp_kses_post(alpenia_public_participant_message('Bitte verwenden Sie den individuellen Anmeldelink Ihrer Reise.', 'info')); ?>
        <?php elseif (!$trip_available) : ?>
            <?php echo wp_kses_post(alpenia_public_participant_message('Dieser Anmeldelink ist ungültig oder die Reise ist nicht öffentlich anmeldbar.', 'error')); ?>
        <?php else : ?>
            <div class="alpenia-public-trip-card">
                <span><?php echo esc_html(alpenia_travel_t('Anmeldung für')); ?></span>
                <strong><?php echo esc_html(get_the_title($selected_trip_id)); ?></strong>
            </div>

            <form method="post" enctype="multipart/form-data" novalidate>
                <?php wp_nonce_field('alpenia_public_participant_form', 'alpenia_public_participant_nonce'); ?>
                <input type="hidden" name="alpenia_public_participant_submit" value="1">
                <input type="hidden" name="trip_id" value="<?php echo esc_attr($selected_trip_id); ?>">
                <div class="alpenia-public-hidden" aria-hidden="true">
                    <label for="alpenia_company_website">Website</label>
                    <input type="text" id="alpenia_company_website" name="alpenia_company_website" tabindex="-1" autocomplete="off">
                </div>

                <?php alpenia_public_participant_render_fields($values); ?>
                <?php alpenia_public_participant_render_upload_fields(); ?>

                <div class="alpenia-public-actions">
                    <p><?php echo esc_html(alpenia_travel_t('Mit * markierte Felder sind Pflichtfelder.')); ?></p>
                    <button type="submit" class="alpenia-public-submit"><?php echo esc_html(alpenia_travel_t('Daten absenden')); ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('alpenia_participant_form', 'alpenia_public_participant_form_shortcode');

function alpenia_public_participant_message($text, $type = 'info') {
    $class = $type === 'success' ? 'alpenia-public-success' : ($type === 'error' ? 'alpenia-public-error' : 'alpenia-public-info');
    return '<div class="alpenia-public-message ' . esc_attr($class) . '">' . esc_html(alpenia_travel_t($text)) . '</div>';
}

function alpenia_public_participant_enqueue_assets() {
    $version = defined('WP_DEBUG') && WP_DEBUG ? time() : '4.4';
    wp_enqueue_style(
        'alpenia-public-participant-form',
        plugin_dir_url(ALPENIA_PLUGIN_FILE) . 'assets/public-participant-form.css',
        [],
        $version
    );
    wp_enqueue_script(
        'alpenia-public-participant-form',
        plugin_dir_url(ALPENIA_PLUGIN_FILE) . 'assets/public-participant-form.js',
        [],
        $version,
        true
    );
    wp_localize_script('alpenia-public-participant-form', 'AlpeniaPublicParticipantForm', [
        'euSchengenCountries' => array_values(alpenia_get_eu_schengen_countries()),
    ]);
}

function alpenia_public_participant_maybe_enqueue_assets() {
    if (!is_singular()) {
        return;
    }

    global $post;
    if ($post && !empty($post->post_content) && has_shortcode($post->post_content, 'alpenia_participant_form')) {
        alpenia_public_participant_enqueue_assets();
    }
}
add_action('wp_enqueue_scripts', 'alpenia_public_participant_maybe_enqueue_assets');

function alpenia_public_participant_get_form_fields() {
    $allowed_keys = [
        'gender',
        'first_name',
        'second_first_name',
        'last_name',
        'birth_date',
        'street_address',
        'postal_city',
        'phone_number',
        'email_address',
        'nationality',
        'passport_no',
        'passport_valid_from_date',
        'passport_expiry_date',
        'residence_permit_number',
        'residence_permit_start_date',
        'residence_permit_valid_until',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    $fields = array_intersect_key(alpenia_participant_field_definitions(), array_flip($allowed_keys));

    foreach (['street_address', 'postal_city', 'phone_number', 'email_address'] as $key) {
        if (isset($fields[$key])) {
            $fields[$key]['section'] = 'Adresse & Kontakt';
        }
    }

    foreach (['nationality', 'passport_no', 'passport_valid_from_date', 'passport_expiry_date'] as $key) {
        if (isset($fields[$key])) {
            $fields[$key]['section'] = 'Reisepassdaten';
        }
    }

    foreach (alpenia_public_participant_get_residence_permit_field_keys() as $key) {
        if (isset($fields[$key])) {
            $fields[$key]['section'] = 'Aufenthaltstitel';
        }
    }

    foreach (['emergency_contact_name', 'emergency_contact_phone'] as $key) {
        if (isset($fields[$key])) {
            $fields[$key]['section'] = 'Notfallkontakt';
        }
    }

    return $fields;
}

function alpenia_public_participant_get_required_fields() {
    return ['gender', 'first_name', 'last_name', 'nationality', 'passport_no', 'passport_valid_from_date', 'passport_expiry_date', 'email_address'];
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

    $requires_residence_permit = alpenia_nationality_requires_residence_permit($values['nationality'] ?? '');
    $upload_validation = alpenia_public_participant_validate_upload_requirements($requires_residence_permit);
    if (is_wp_error($upload_validation)) {
        return $upload_validation;
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

    $upload_result = alpenia_public_participant_save_uploads($participant_id, $requires_residence_permit);
    if (is_wp_error($upload_result)) {
        wp_delete_post($participant_id, true);
        return $upload_result;
    }

    alpenia_send_notification('Neue Teilnehmerdaten übermittelt', 'Ein Teilnehmer hat öffentliche Formulardaten gesendet: ' . $title);
    return $participant_id;
}

function alpenia_public_participant_upload_is_present($field_name) {
    return !empty($_FILES[$field_name]['name']) && (int) ($_FILES[$field_name]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE;
}

function alpenia_public_participant_validate_upload_requirements($requires_residence_permit) {
    if (!alpenia_public_participant_upload_is_present('passport_file')) {
        return new WP_Error('missing_passport_file', alpenia_travel_t('Bitte den Reisepass hochladen.'));
    }

    if (!alpenia_public_participant_upload_is_present('photo_file')) {
        return new WP_Error('missing_photo_file', alpenia_travel_t('Bitte ein Porträtfoto hochladen.'));
    }

    if ($requires_residence_permit && !alpenia_public_participant_upload_is_present('visa_photo_file')) {
        return new WP_Error('missing_residence_permit_file', alpenia_travel_t('Bitte den Aufenthaltstitel hochladen.'));
    }

    return true;
}

function alpenia_public_participant_save_uploads($participant_id, $requires_residence_permit = false) {
    $upload_fields = [
        'passport_file' => 'passport_file_id',
        'photo_file' => 'photo_file_id',
    ];

    if ($requires_residence_permit) {
        $upload_fields['visa_photo_file'] = 'visa_photo_file_id';
    }

    foreach ($upload_fields as $file_field => $meta_key) {
        $file_id = alpenia_handle_file_upload($file_field);
        if (is_wp_error($file_id)) {
            return $file_id;
        }
        if ($file_id) {
            update_post_meta($participant_id, $meta_key, $file_id);
            wp_update_post([
                'ID' => $file_id,
                'post_parent' => $participant_id,
            ]);
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
    $residence_fields = alpenia_public_participant_get_residence_permit_field_keys();

    foreach ($sections as $section_label => $section_fields) {
        $section_contains_residence = !empty(array_intersect(array_map(static function ($pair) { return $pair[0]; }, $section_fields), $residence_fields));
        $section_class = $section_contains_residence ? ' alpenia-public-section--conditional' : '';
        $section_attrs = $section_contains_residence ? ' data-alpenia-residence-section hidden' : '';

        echo '<section class="alpenia-public-section' . esc_attr($section_class) . '"' . $section_attrs . '>';
        echo '<div class="alpenia-public-section__header"><h3>' . esc_html(alpenia_travel_t($section_label === 'Kontakt' ? 'Adresse & Kontakt' : $section_label)) . '</h3></div>';
        echo '<div class="alpenia-public-grid">';

        foreach ($section_fields as $field_pair) {
            [$meta_key, $field] = $field_pair;
            $input_id = 'alpenia_public_' . $meta_key;
            $value = $values[$meta_key] ?? '';
            $required = in_array($meta_key, $required_fields, true);
            $field_attrs = in_array($meta_key, $residence_fields, true) ? ' data-alpenia-residence-field' : '';
            $extra_input_attrs = $meta_key === 'nationality' ? ' data-alpenia-nationality' : '';

            echo '<div class="alpenia-public-field"' . $field_attrs . '>';
            echo '<label for="' . esc_attr($input_id) . '">' . esc_html(alpenia_travel_t($field['label']));
            if ($required) {
                echo ' <span class="alpenia-public-required" aria-label="' . esc_attr(alpenia_travel_t('Pflichtfeld')) . '">*</span>';
            }
            echo '</label>';

            if (!empty($field['options'])) {
                echo '<select id="' . esc_attr($input_id) . '" name="' . esc_attr($meta_key) . '"' . ($required ? ' required' : '') . $extra_input_attrs . '>';
                foreach ($field['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected((string) $value, (string) $option_value, false) . '>' . esc_html(alpenia_travel_t($option_label)) . '</option>';
                }
                echo '</select>';
            } else {
                $input_type = $field['input_type'] ?? 'text';
                $list = $meta_key === 'nationality' ? ' list="alpenia-public-country-list"' : '';
                echo '<input type="' . esc_attr($input_type) . '" id="' . esc_attr($input_id) . '" name="' . esc_attr($meta_key) . '" value="' . esc_attr($value) . '"' . $list . ($required ? ' required' : '') . $extra_input_attrs . '>';
            }

            echo '</div>';
        }

        echo '</div>';
        echo '</section>';
    }

    echo '<datalist id="alpenia-public-country-list">';
    foreach (alpenia_get_all_countries() as $country_name) {
        echo '<option value="' . esc_attr($country_name) . '">';
    }
    echo '</datalist>';
}

function alpenia_public_participant_get_residence_permit_field_keys() {
    return ['residence_permit_number', 'residence_permit_start_date', 'residence_permit_valid_until'];
}

function alpenia_public_participant_render_upload_fields() {
    ?>
    <section class="alpenia-public-section">
        <div class="alpenia-public-section__header">
            <h3><?php echo esc_html(alpenia_travel_t('Dokumente')); ?></h3>
            <p><?php echo esc_html(alpenia_travel_t('Bitte lade gut lesbare Dateien in den angegebenen Formaten hoch.')); ?></p>
        </div>
        <div class="alpenia-public-grid alpenia-public-grid--uploads">
            <?php alpenia_public_participant_render_upload_field('passport_file', 'Reisepass hochladen', 'PDF, JPG oder PNG, maximal 5 MB.', '.pdf,.jpg,.jpeg,.png', true); ?>
            <?php alpenia_public_participant_render_upload_field('photo_file', 'Porträtfoto hochladen', 'JPG, PNG oder WEBP, maximal 2 MB.', '.jpg,.jpeg,.png,.webp', true); ?>
            <div data-alpenia-residence-section hidden>
                <?php alpenia_public_participant_render_upload_field('visa_photo_file', 'Aufenthaltstitel hochladen', 'JPG, PNG oder WEBP, maximal 2 MB.', '.jpg,.jpeg,.png,.webp', true, 'data-alpenia-residence-upload'); ?>
            </div>
        </div>
    </section>
    <?php
}

function alpenia_public_participant_render_upload_field($name, $label, $hint, $accept, $required = false, $input_attrs = '') {
    $input_id = 'alpenia_public_' . $name;
    ?>
    <div class="alpenia-public-upload">
        <label for="<?php echo esc_attr($input_id); ?>">
            <span><?php echo esc_html(alpenia_travel_t($label)); ?><?php if ($required) : ?> <span class="alpenia-public-required">*</span><?php endif; ?></span>
            <small><?php echo esc_html(alpenia_travel_t($hint)); ?></small>
        </label>
        <input type="file" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>" accept="<?php echo esc_attr($accept); ?>" <?php echo $required ? 'required' : ''; ?> <?php echo esc_attr($input_attrs); ?>>
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

/**
 * Central list helper for citizenship decisions. EU countries and Schengen states
 * are maintained in includes/core.php and combined here so future rule changes only
 * need to update the country-list helpers, not form rendering or validation code.
 */
function alpenia_get_eu_schengen_countries() {
    return array_values(array_unique(array_merge(alpenia_get_eu_countries(), alpenia_get_schengen_countries())));
}

function alpenia_nationality_requires_residence_permit($nationality) {
    $nationality = trim((string) $nationality);
    if ($nationality === '') {
        return false;
    }

    return !alpenia_is_eu_or_schengen_nationality($nationality);
}

function alpenia_public_participant_get_form_page_url() {
    $page_url = home_url('/anmeldung/');
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 1,
        's' => '[alpenia_participant_form',
    ]);

    foreach ($pages as $page) {
        if (has_shortcode($page->post_content, 'alpenia_participant_form')) {
            $page_url = get_permalink($page);
            break;
        }
    }

    return apply_filters('alpenia_public_participant_form_page_url', $page_url);
}

function alpenia_public_participant_get_trip_form_url($trip_id) {
    return add_query_arg('trip_id', absint($trip_id), alpenia_public_participant_get_form_page_url());
}

function alpenia_public_participant_get_trip_shortcode($trip_id) {
    return '[alpenia_participant_form trip_id="' . absint($trip_id) . '"]';
}
