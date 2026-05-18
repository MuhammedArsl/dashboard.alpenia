<?php
if (!defined('ABSPATH')) exit;

/**
 * Public participant self-registration form.
 *
 * The shortcode stays registered for backwards compatibility, but renders the
 * form only when a valid trip-specific token is supplied via the generated
 * registration link.
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
        'trip_token' => '',
    ], $atts, 'alpenia_participant_form');

    $request_token = alpenia_public_participant_sanitize_registration_token($atts['trip_token']);
    if ($request_token === '') {
        $request_token = alpenia_public_participant_get_request_token();
    }

    $selected_trip_id = alpenia_public_participant_get_trip_id_by_token($request_token);
    $message = '';
    $values = [];
    $consents = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alpenia_public_participant_submit'])) {
        $nonce = isset($_POST['alpenia_public_participant_nonce']) ? sanitize_text_field(wp_unslash($_POST['alpenia_public_participant_nonce'])) : '';
        $honeypot = isset($_POST['alpenia_company_website']) ? trim((string) wp_unslash($_POST['alpenia_company_website'])) : '';
        $request_token = isset($_POST['trip_token']) ? alpenia_public_participant_sanitize_registration_token(wp_unslash($_POST['trip_token'])) : '';
        $selected_trip_id = alpenia_public_participant_get_trip_id_by_token($request_token);

        $values = alpenia_public_participant_get_submitted_values();
        $consents = alpenia_public_participant_get_submitted_consents();

        if ($nonce === '' || !wp_verify_nonce($nonce, 'alpenia_public_participant_form')) {
            $message = alpenia_public_participant_message('Sicherheitsfehler. Bitte erneut versuchen.', 'error');
        } elseif ($honeypot !== '') {
            $message = alpenia_public_participant_message('Das Formular konnte nicht gesendet werden.', 'error');
        } elseif ($request_token === '' || !alpenia_public_participant_trip_is_available($selected_trip_id)) {
            $message = alpenia_public_participant_message('Dieser Anmeldelink ist ungültig oder die Reise ist nicht öffentlich anmeldbar.', 'error');
        } elseif (empty($consents['privacy_consent']) || empty($consents['accuracy_consent'])) {
            $message = alpenia_public_participant_message('Bitte bestätige die Datenschutzerklärung und die Echtheit deiner Angaben.', 'error');
        } else {
            $result = alpenia_public_participant_create($selected_trip_id, $values);

            if (is_wp_error($result)) {
                $message = alpenia_public_participant_message($result->get_error_message(), 'error');
            } else {
                $message = alpenia_public_participant_message('Vielen Dank. Deine Teilnehmerdaten wurden erfolgreich übermittelt.', 'success');
                $values = [];
                $consents = [];
            }
        }
    }

    $trip_available = alpenia_public_participant_trip_is_available($selected_trip_id);

    ob_start();
    ?>
    <div class="alpenia-public-form">
        <div class="alpenia-public-form__hero">
            <div class="alpenia-public-form__hero-top">
                <p class="alpenia-public-form__eyebrow"><?php echo esc_html(alpenia_travel_t('Alpenia Group Trips')); ?></p>
                <?php echo alpenia_public_participant_language_switcher($request_token); ?>
            </div>
            <h2><?php echo esc_html(alpenia_travel_t('Anmeldeformular')); ?></h2>
            <p class="alpenia-public-intro"><?php echo esc_html(alpenia_travel_t('Bitte fülle deine persönlichen Daten vollständig aus. Deine Angaben werden sicher als Teilnehmerdatensatz gespeichert.')); ?></p>
        </div>

        <?php echo wp_kses_post($message); ?>

        <?php if (!$selected_trip_id && $request_token === '') : ?>
            <?php echo wp_kses_post(alpenia_public_participant_message('Bitte verwenden Sie den individuellen Anmeldelink Ihrer Reise.', 'info')); ?>
        <?php elseif (!$trip_available) : ?>
            <?php echo wp_kses_post(alpenia_public_participant_message('Dieser Anmeldelink ist ungültig oder die Reise ist nicht öffentlich anmeldbar.', 'error')); ?>
        <?php else : ?>
            <?php alpenia_public_participant_render_trip_overview($selected_trip_id); ?>

            <form method="post" enctype="multipart/form-data" novalidate>
                <?php wp_nonce_field('alpenia_public_participant_form', 'alpenia_public_participant_nonce'); ?>
                <input type="hidden" name="alpenia_public_participant_submit" value="1">
                <input type="hidden" name="trip_token" value="<?php echo esc_attr($request_token); ?>">
                <div class="alpenia-public-hidden" aria-hidden="true">
                    <label for="alpenia_company_website">Website</label>
                    <input type="text" id="alpenia_company_website" name="alpenia_company_website" tabindex="-1" autocomplete="off">
                </div>

                <?php alpenia_public_participant_render_fields($values); ?>
                <?php alpenia_public_participant_render_upload_fields(); ?>
                <?php alpenia_public_participant_render_consent_fields($consents); ?>

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

function alpenia_public_participant_language_switcher($request_token = '') {
    $current_url = home_url(add_query_arg([], isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : ''));
    $args = ['ui_lang' => 'de'];
    if ($request_token !== '') {
        $args['trip_token'] = $request_token;
    }
    $de_url = add_query_arg($args, $current_url);

    $args['ui_lang'] = 'tr';
    $tr_url = add_query_arg($args, $current_url);
    $current_lang = alpenia_travel_get_language();

    ob_start();
    ?>
    <div class="alpenia-public-language-switch" role="group" aria-label="<?php echo esc_attr(alpenia_travel_t('Formularsprache wechseln')); ?>">
        <span><?php echo esc_html(alpenia_travel_t('Sprache')); ?></span>
        <a class="<?php echo $current_lang === 'de' ? 'is-active' : ''; ?>" href="<?php echo esc_url($de_url); ?>" aria-label="Deutsch">DE</a>
        <a class="<?php echo $current_lang === 'tr' ? 'is-active' : ''; ?>" href="<?php echo esc_url($tr_url); ?>" aria-label="Türkçe">TR</a>
    </div>
    <?php
    return ob_get_clean();
}

function alpenia_public_participant_get_token_meta_key() {
    return '_alpenia_registration_token';
}

function alpenia_public_participant_sanitize_registration_token($token) {
    $token = sanitize_text_field((string) $token);
    return preg_replace('/[^A-Za-z0-9]/', '', $token);
}

function alpenia_public_participant_get_request_token() {
    if (!isset($_GET['trip_token'])) {
        return '';
    }

    return alpenia_public_participant_sanitize_registration_token(wp_unslash($_GET['trip_token']));
}

function alpenia_public_participant_generate_registration_token() {
    do {
        $token = wp_generate_password(32, false, false);
        $existing = get_posts([
            'post_type' => 'group_trip',
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_key' => alpenia_public_participant_get_token_meta_key(),
            'meta_value' => $token,
        ]);
    } while (!empty($existing));

    return $token;
}

function alpenia_public_participant_get_trip_registration_token($trip_id, $create_if_missing = false) {
    $trip_id = absint($trip_id);
    if (!$trip_id) {
        return '';
    }

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') {
        return '';
    }

    $token = alpenia_public_participant_sanitize_registration_token(get_post_meta($trip_id, alpenia_public_participant_get_token_meta_key(), true));
    if ($token === '' && $create_if_missing) {
        $token = alpenia_public_participant_generate_registration_token();
        update_post_meta($trip_id, alpenia_public_participant_get_token_meta_key(), $token);
    }

    return $token;
}

function alpenia_public_participant_get_trip_id_by_token($token) {
    $token = alpenia_public_participant_sanitize_registration_token($token);
    if ($token === '') {
        return 0;
    }

    $trip_ids = get_posts([
        'post_type' => 'group_trip',
        'post_status' => 'publish',
        'numberposts' => 1,
        'fields' => 'ids',
        'meta_key' => alpenia_public_participant_get_token_meta_key(),
        'meta_value' => $token,
    ]);

    return empty($trip_ids) ? 0 : (int) $trip_ids[0];
}

function alpenia_public_participant_message($text, $type = 'info') {
    $class = $type === 'success' ? 'alpenia-public-success' : ($type === 'error' ? 'alpenia-public-error' : 'alpenia-public-info');
    return '<div class="alpenia-public-message ' . esc_attr($class) . '">' . esc_html(alpenia_travel_t($text)) . '</div>';
}

function alpenia_public_participant_enqueue_assets() {
    $version = defined('WP_DEBUG') && WP_DEBUG ? time() : '5.1';
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

function alpenia_public_participant_get_trip_type_label($trip_type) {
    $labels = [
        'kultur' => 'Kulturreise',
        'umrah' => 'Umrah',
        'hajj' => 'Hajj',
    ];

    return $labels[$trip_type] ?? $trip_type;
}

function alpenia_public_participant_format_trip_date_range($start_date, $end_date) {
    if (function_exists('alpenia_date_range_display')) {
        return alpenia_date_range_display($start_date, $end_date);
    }

    $start_date = trim((string) $start_date);
    $end_date = trim((string) $end_date);

    if ($start_date !== '' && $end_date !== '') {
        return alpenia_public_participant_format_date_fallback($start_date) . ' – ' . alpenia_public_participant_format_date_fallback($end_date);
    }

    return $start_date !== '' ? alpenia_public_participant_format_date_fallback($start_date) : alpenia_public_participant_format_date_fallback($end_date);
}

function alpenia_public_participant_format_date_fallback($date) {
    if (function_exists('alpenia_format_date_display')) {
        return alpenia_format_date_display($date);
    }

    $date = trim((string) $date);
    $date_time = DateTime::createFromFormat('!Y-m-d', $date);
    if ($date_time instanceof DateTime && $date_time->format('Y-m-d') === $date) {
        return $date_time->format('d.m.Y');
    }

    return $date !== '' ? $date : '-';
}

function alpenia_public_participant_get_capacity_left($trip_id) {
    $max_people = (int) get_post_meta($trip_id, 'max_people', true);
    if ($max_people <= 0) {
        return '-';
    }

    $participants = get_posts([
        'post_type' => 'trip_participant',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_key' => 'trip_id',
        'meta_value' => absint($trip_id),
    ]);

    return max(0, $max_people - count($participants));
}

function alpenia_public_participant_get_trip_detail_items($trip_id) {
    $trip_type = alpenia_public_participant_get_trip_type_label((string) get_post_meta($trip_id, 'trip_type', true));
    $destination = (string) get_post_meta($trip_id, 'destination', true);
    $country = (string) get_post_meta($trip_id, 'country', true);
    $city = (string) get_post_meta($trip_id, 'city', true);
    $location = trim($country . ($country !== '' && $city !== '' ? ' / ' : '') . $city);
    $departure_city = trim((string) get_post_meta($trip_id, 'departure_city', true));
    $departure_airport = trim((string) get_post_meta($trip_id, 'departure_airport', true));
    $date_range = alpenia_public_participant_format_trip_date_range(
        get_post_meta($trip_id, 'start_date', true),
        get_post_meta($trip_id, 'end_date', true)
    );
    $assigned_guide = (int) get_post_meta($trip_id, 'assigned_guide', true);
    $guide_name = '';

    if ($assigned_guide > 0) {
        $guide = get_userdata($assigned_guide);
        $guide_name = $guide ? $guide->display_name : '';
    }

    $items = [
        ['label' => 'Reisetitel', 'value' => get_the_title($trip_id)],
        ['label' => 'Reisetyp', 'value' => $trip_type],
        ['label' => 'Reiseziel', 'value' => $destination],
        ['label' => 'Land / Stadt', 'value' => $location],
        ['label' => 'Abflugstadt', 'value' => $departure_city],
        ['label' => 'Flughafen', 'value' => $departure_airport],
        ['label' => 'Reisezeitraum', 'value' => $date_range],
        ['label' => 'Freie Plätze', 'value' => (string) alpenia_public_participant_get_capacity_left($trip_id)],
    ];

    if ($guide_name !== '') {
        $items[] = ['label' => 'Reiseleitung', 'value' => $guide_name];
    }

    return array_values(array_filter($items, static function ($item) {
        return trim((string) $item['value']) !== '';
    }));
}

function alpenia_public_participant_render_trip_overview($trip_id) {
    $items = alpenia_public_participant_get_trip_detail_items($trip_id);
    ?>
    <section class="alpenia-public-trip-card" aria-labelledby="alpenia-public-trip-title">
        <div class="alpenia-public-trip-card__content">
            <span class="alpenia-public-trip-card__label"><?php echo esc_html(alpenia_travel_t('Anmeldung für')); ?></span>
            <h3 id="alpenia-public-trip-title"><?php echo esc_html(get_the_title($trip_id)); ?></h3>
            <p><?php echo esc_html(alpenia_travel_t('Prüfe bitte vor dem Absenden die wichtigsten Reisedetails und halte die benötigten Dokumente bereit.')); ?></p>
        </div>
        <dl class="alpenia-public-trip-details">
            <?php foreach ($items as $item) : ?>
                <div>
                    <dt><?php echo esc_html(alpenia_travel_t($item['label'])); ?></dt>
                    <dd><?php echo esc_html($item['value']); ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
        <div class="alpenia-public-prep-card">
            <strong><?php echo esc_html(alpenia_travel_t('Für die Anmeldung erforderlich')); ?></strong>
            <ul>
                <li><?php echo esc_html(alpenia_travel_t('Vollständige Kontaktdaten und Notfallkontakt')); ?></li>
                <li><?php echo esc_html(alpenia_travel_t('Gültige Reisepassdaten mit gut lesbarer Datei')); ?></li>
                <li><?php echo esc_html(alpenia_travel_t('Porträtfoto und ggf. Aufenthaltstitel')); ?></li>
            </ul>
        </div>
    </section>
    <?php
}

function alpenia_public_participant_get_submitted_consents() {
    return [
        'privacy_consent' => !empty($_POST['privacy_consent']) ? 1 : 0,
        'accuracy_consent' => !empty($_POST['accuracy_consent']) ? 1 : 0,
    ];
}

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

    $field_definitions = alpenia_participant_field_definitions();
    $fields = [];

    foreach ($allowed_keys as $key) {
        if (isset($field_definitions[$key])) {
            $fields[$key] = $field_definitions[$key];
        }
    }

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
    $residence_fields = alpenia_public_participant_get_residence_permit_field_keys();

    return array_values(array_filter(array_keys(alpenia_public_participant_get_form_fields()), static function ($field_key) use ($residence_fields) {
        if ($field_key === 'second_first_name') {
            return false;
        }

        return !in_array($field_key, $residence_fields, true);
    }));
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
    if ($requires_residence_permit) {
        foreach (alpenia_public_participant_get_residence_permit_field_keys() as $required_key) {
            if (empty($values[$required_key])) {
                return new WP_Error('missing_residence_permit_fields', alpenia_travel_t('Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht.'));
            }
        }
    }

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

function alpenia_public_participant_render_consent_fields($consents = []) {
    $privacy_checked = !empty($consents['privacy_consent']);
    $accuracy_checked = !empty($consents['accuracy_consent']);
    ?>
    <section class="alpenia-public-section alpenia-public-section--consent">
        <div class="alpenia-public-section__header">
            <h3><?php echo esc_html(alpenia_travel_t('Bestätigungen')); ?></h3>
            <p><?php echo esc_html(alpenia_travel_t('Zum Schutz deiner Daten benötigen wir vor dem Absenden zwei Zustimmungen.')); ?></p>
        </div>
        <div class="alpenia-public-consent-list">
            <label class="alpenia-public-consent" for="alpenia_public_privacy_consent">
                <input type="checkbox" id="alpenia_public_privacy_consent" name="privacy_consent" value="1" required <?php checked($privacy_checked); ?>>
                <span>
                    <strong><?php echo esc_html(alpenia_travel_t('Datenschutzerklärung akzeptieren')); ?> <span class="alpenia-public-required">*</span></strong>
                    <?php echo esc_html(alpenia_travel_t('Ich habe die Datenschutzerklärung gelesen und bin mit der Verarbeitung meiner Daten zur Reiseanmeldung einverstanden.')); ?>
                </span>
            </label>
            <label class="alpenia-public-consent" for="alpenia_public_accuracy_consent">
                <input type="checkbox" id="alpenia_public_accuracy_consent" name="accuracy_consent" value="1" required <?php checked($accuracy_checked); ?>>
                <span>
                    <strong><?php echo esc_html(alpenia_travel_t('Echtheit der Daten bestätigen')); ?> <span class="alpenia-public-required">*</span></strong>
                    <?php echo esc_html(alpenia_travel_t('Ich bestätige, dass alle Angaben wahrheitsgemäß, vollständig und anhand meiner gültigen Reisedokumente eingetragen wurden.')); ?>
                </span>
            </label>
        </div>
    </section>
    <?php
}

function alpenia_public_participant_render_upload_fields() {
    ?>
    <section class="alpenia-public-section">
        <div class="alpenia-public-section__header">
            <h3><?php echo esc_html(alpenia_travel_t('Dokumente')); ?></h3>
            <p><?php echo esc_html(alpenia_travel_t('Bitte lade gut lesbare Dateien in den angegebenen Formaten hoch.')); ?></p>
        </div>
        <div class="alpenia-public-grid alpenia-public-grid--uploads">
            <?php alpenia_public_participant_render_upload_field('passport_file', 'Reisepass hochladen', alpenia_upload_formats_label('passport_file') . ', maximal 5 MB.', true); ?>
            <?php alpenia_public_participant_render_upload_field('photo_file', 'Porträtfoto hochladen', alpenia_upload_formats_label('photo_file') . ', maximal 2 MB.', true); ?>
            <div data-alpenia-residence-section hidden>
                <?php alpenia_public_participant_render_upload_field('visa_photo_file', 'Aufenthaltstitel hochladen', alpenia_upload_formats_label('visa_photo_file') . ', maximal 2 MB.', true, 'data-alpenia-residence-upload'); ?>
            </div>
        </div>
    </section>
    <?php
}

function alpenia_public_participant_render_upload_field($name, $label, $hint, $required = false, $input_attrs = '') {
    $input_id = 'alpenia_public_' . $name;
    ?>
    <div class="alpenia-public-upload">
        <label for="<?php echo esc_attr($input_id); ?>">
            <span><?php echo esc_html(alpenia_travel_t($label)); ?><?php if ($required) : ?> <span class="alpenia-public-required">*</span><?php endif; ?></span>
            <small><?php echo esc_html(alpenia_travel_t($hint)); ?></small>
        </label>
        <input type="file" id="<?php echo esc_attr($input_id); ?>" name="<?php echo esc_attr($name); ?>" accept="<?php echo esc_attr(alpenia_upload_accept_attribute($name)); ?>" <?php echo $required ? 'required' : ''; ?> <?php echo esc_attr($input_attrs); ?>>
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
    $token = alpenia_public_participant_get_trip_registration_token($trip_id, true);

    if ($token === '') {
        return alpenia_public_participant_get_form_page_url();
    }

    return add_query_arg('trip_token', rawurlencode($token), alpenia_public_participant_get_form_page_url());
}

function alpenia_public_participant_get_trip_shortcode($trip_id) {
    return '[alpenia_participant_form]';
}
