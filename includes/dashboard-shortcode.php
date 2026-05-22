<?php
if (!defined('ABSPATH')) exit;

/**
 * Dashboard Shortcode
 */
function alpenia_dashboard_logout_button() {
    ob_start();
    ?>
    <form method="post" class="alpenia-logout-form" style="display:inline;">
        <?php wp_nonce_field('alpenia_logout_action', 'alpenia_logout_nonce'); ?>
        <input type="hidden" name="alpenia_logout" value="1">
        <input type="hidden" name="alpenia_logout_intent" value="dashboard_logout">
        <button type="submit" class="btn-primary btn-logout"><?php echo esc_html(alpenia_travel_t("Logout")); ?></button>
    </form>
    <?php
    return ob_get_clean();
}



function alpenia_dashboard_sidebar_nav() {
    $items = [
        [
            'label' => alpenia_travel_t('Startseite'),
            'url' => alpenia_dashboard_link(),
            'active' => !isset($_GET['create_trip'], $_GET['add_participant'], $_GET['manage_users'], $_GET['view_trip'], $_GET['edit_participant'], $_GET['edit_trip']),
            'icon' => '⌂',
            'meta' => alpenia_travel_t('Übersicht'),
        ],
    ];

    if (alpenia_user_can_create_trip()) {
        $items[] = [
            'label' => alpenia_travel_t('Neue Reise erstellen'),
            'url' => alpenia_dashboard_link(['create_trip' => 1]),
            'active' => isset($_GET['create_trip']) || isset($_GET['edit_trip']),
            'icon' => '+',
            'meta' => alpenia_travel_t('Planung'),
        ];
    }

    $items[] = [
        'label' => alpenia_travel_t('Teilnehmer hinzufügen'),
        'url' => alpenia_dashboard_link(['add_participant' => 1]),
        'active' => isset($_GET['add_participant']) || isset($_GET['edit_participant']) || isset($_GET['view_trip']),
        'icon' => '👤',
        'meta' => alpenia_travel_t('Erfassung'),
    ];

    $items[] = [
        'label' => alpenia_travel_t('Papierkorb'),
        'url' => alpenia_dashboard_link(['trash_bin' => 1]),
        'active' => isset($_GET['trash_bin']),
        'icon' => '🗑',
        'meta' => alpenia_travel_t('Archiv'),
    ];

    if (alpenia_user_can_manage_users()) {
        $items[] = [
            'label' => alpenia_travel_t('Benutzerverwaltung'),
            'url' => alpenia_dashboard_link(['manage_users' => 1]),
            'active' => isset($_GET['manage_users']) || isset($_GET['dashboard_edit_user']),
            'icon' => '⚙',
            'meta' => alpenia_travel_t('Team'),
        ];
    }

    ob_start();
    ?>
    <aside class="dashboard-sidebar" aria-label="<?php echo esc_attr(alpenia_travel_t('Dashboard Navigation')); ?>">
        <div class="dashboard-sidebar__header dashboard-sidebar__header--compact">
            <span class="dashboard-sidebar__eyebrow"><?php echo esc_html(alpenia_travel_t('Menü')); ?></span>
        </div>
        <nav class="dashboard-sidebar__nav">
            <?php foreach ($items as $item) : ?>
                <a class="dashboard-sidebar__link <?php echo $item['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
                    <span class="dashboard-sidebar__icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                    <span class="dashboard-sidebar__copy">
                        <span><?php echo esc_html($item['meta']); ?></span>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dashboard-sidebar__support" aria-label="<?php echo esc_attr(alpenia_travel_t('Hilfe & Kontaktstelle')); ?>">
            <span class="dashboard-sidebar__support-eyebrow"><?php echo esc_html(alpenia_travel_t('Support')); ?></span>
            <strong><?php echo esc_html(alpenia_travel_t('Hilfe & Kontaktstelle')); ?></strong>
            <div class="dashboard-sidebar__support-actions">
                <a class="table-btn" href="https://wa.me/" target="_blank" rel="noopener noreferrer"><?php echo esc_html(alpenia_travel_t('WhatsApp')); ?></a>
                <a class="table-btn table-btn--ghost" href="mailto:support@alpeniatravel.com"><?php echo esc_html(alpenia_travel_t('E-Mail')); ?></a>
            </div>
        </div>
    </aside>
    <?php
    return ob_get_clean();
}

function alpenia_dashboard_language_switcher() {
    $de_url = alpenia_dashboard_link(array_merge($_GET, ['ui_lang' => 'de']));
    $tr_url = alpenia_dashboard_link(array_merge($_GET, ['ui_lang' => 'tr']));

    ob_start();
    ?>
    <div class="dashboard-language-switch" role="group" aria-label="<?php echo esc_attr(alpenia_travel_t('Plugin language switch')); ?>">
        <a class="lang-link <?php echo alpenia_travel_get_language() === 'de' ? 'active' : ''; ?>" href="<?php echo esc_url($de_url); ?>" title="DE" aria-label="DE">DE</a>
        <a class="lang-link <?php echo alpenia_travel_get_language() === 'tr' ? 'active' : ''; ?>" href="<?php echo esc_url($tr_url); ?>" title="TR" aria-label="TR">TR</a>
    </div>
    <?php
    return ob_get_clean();
}

function alpenia_dashboard_get_per_page($key, $default = 20, $allowed = [10, 20, 50, 100]) {
    $value = isset($_GET[$key]) ? (int) $_GET[$key] : (int) $default;
    return in_array($value, $allowed, true) ? $value : (int) $default;
}


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

function alpenia_dashboard_trip_days_left($trip_id) {
    $start_date = (string) get_post_meta((int) $trip_id, 'start_date', true);
    if ($start_date === '') {
        return null;
    }

    $start_timestamp = strtotime($start_date);
    if ($start_timestamp === false) {
        return null;
    }

    $today_timestamp = strtotime(wp_date('Y-m-d'));
    return (int) floor(($start_timestamp - $today_timestamp) / DAY_IN_SECONDS);
}

function alpenia_dashboard_trip_timeline_badge($trip_id) {
    $days_left = alpenia_dashboard_trip_days_left($trip_id);
    if ($days_left === null) {
        return '';
    }

    if ($days_left < 0) {
        return '<span class="timeline-badge timeline-badge--overdue">' . esc_html(alpenia_travel_t('Gestartet')) . '</span>';
    }

    if ($days_left <= 14) {
        return '<span class="timeline-badge timeline-badge--urgent">' . esc_html(sprintf(alpenia_travel_t('%d Tage übrig'), $days_left)) . '</span>';
    }

    return '<span class="timeline-badge timeline-badge--planned">' . esc_html(sprintf(alpenia_travel_t('%d Tage bis Reise'), $days_left)) . '</span>';
}

function alpenia_dashboard_render_pagination($current_page, $max_pages, $base_args, $page_arg) {
    $current_page = max(1, (int) $current_page);
    $max_pages = max(1, (int) $max_pages);

    if ($max_pages <= 1) {
        return '';
    }

    $base_args = array_filter((array) $base_args, function($value) {
        return $value !== '' && $value !== null;
    });

    $window_start = max(1, $current_page - 2);
    $window_end = min($max_pages, $current_page + 2);

    ob_start();
    ?>
    <nav class="alpenia-pagination" aria-label="<?php echo esc_attr(alpenia_travel_t('Seitennavigation')); ?>">
        <?php if ($current_page > 1) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $current_page - 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Zurück')); ?></a>
        <?php endif; ?>

        <?php if ($window_start > 1) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => 1]))); ?>">1</a>
            <?php if ($window_start > 2) : ?><span>…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($page = $window_start; $page <= $window_end; $page++) : ?>
            <?php if ($page === $current_page) : ?>
                <span class="is-active"><?php echo esc_html($page); ?></span>
            <?php else : ?>
                <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $page]))); ?>"><?php echo esc_html($page); ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($window_end < $max_pages) : ?>
            <?php if ($window_end < $max_pages - 1) : ?><span>…</span><?php endif; ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $max_pages]))); ?>"><?php echo esc_html($max_pages); ?></a>
        <?php endif; ?>

        <?php if ($current_page < $max_pages) : ?>
            <a href="<?php echo esc_url(alpenia_dashboard_link(array_merge($base_args, [$page_arg => $current_page + 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Weiter')); ?></a>
        <?php endif; ?>
    </nav>
    <?php
    return ob_get_clean();
}

function alpenia_get_participant_full_name($participant_id) {
    $first_name = alpenia_get_secure_meta($participant_id, 'first_name', true);
    $second_first_name = alpenia_get_secure_meta($participant_id, 'second_first_name', true);
    $last_name = alpenia_get_secure_meta($participant_id, 'last_name', true);

    return trim(implode(' ', array_filter([$first_name, $second_first_name, $last_name])));
}

function alpenia_dashboard_shortcode() {
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (function_exists('alpenia_send_strict_no_cache_headers')) {
        alpenia_send_strict_no_cache_headers();
    } else {
        nocache_headers();
    }
    $debug_mode = isset($_GET['alpenia_debug_auth']) && $_GET['alpenia_debug_auth'] == '1';

    if (!is_user_logged_in()) {
        $login_url = alpenia_get_login_url();

        if (!headers_sent()) {
            wp_safe_redirect($login_url);
            exit;
        }

        return '<script>window.location.replace(' . wp_json_encode($login_url) . ');</script>';
    }

    if (!alpenia_user_can_access_dashboard()) {
        return '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff.')) . '</div>';
    }

    $current_user = wp_get_current_user();
    $message = '';
    $logo_url = 'HIER_DEINE_LOGO_URL_EINFÜGEN';

    if (isset($_GET['participant_saved']) && $_GET['participant_saved'] === '1') {
        $saved_count = isset($_GET['saved_count']) ? max(1, (int) $_GET['saved_count']) : 1;
        $saved_message = $saved_count === 1
            ? alpenia_travel_t('1 Teilnehmer erfolgreich gespeichert.')
            : sprintf(alpenia_travel_t('%d Teilnehmer erfolgreich gespeichert.'), $saved_count);
        $message = '<div class="alpenia-success" data-alpenia-success-message="1" data-alpenia-feedback-popup="participant_created">' . esc_html($saved_message) . '</div>';
    }

    if (isset($_GET['export_trip_csv'])) {
        alpenia_export_trip_csv((int) $_GET['export_trip_csv']);
    }

    if (isset($_GET['print_trip'])) {
        alpenia_render_print_view((int) $_GET['print_trip'], $logo_url);
    }

    $trip_search = isset($_GET['trip_search']) ? sanitize_text_field($_GET['trip_search']) : '';
    $trip_type_filter = isset($_GET['trip_type_filter']) ? sanitize_text_field($_GET['trip_type_filter']) : '';
    $trip_status_filter = isset($_GET['trip_status_filter']) ? sanitize_text_field($_GET['trip_status_filter']) : '';
    $trip_country_filter = isset($_GET['trip_country_filter']) ? sanitize_text_field($_GET['trip_country_filter']) : '';
    $trip_city_filter = isset($_GET['trip_city_filter']) ? sanitize_text_field($_GET['trip_city_filter']) : '';
    $guide_filter = isset($_GET['guide_filter']) ? (int) $_GET['guide_filter'] : '';
    $trip_page = isset($_GET['trip_page']) ? max(1, (int) $_GET['trip_page']) : 1;
    $trips_per_page = alpenia_dashboard_get_per_page('trips_per_page', 20, [10, 20, 50, 100]);
    $create_trip_requested = isset($_GET['create_trip']) && $_GET['create_trip'] == '1';
    $edit_trip_id = isset($_GET['edit_trip']) ? (int) $_GET['edit_trip'] : 0;
    $edit_trip_requested = $edit_trip_id > 0;

    if ($create_trip_requested && !alpenia_user_can_create_trip()) {
        $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Reiseleiter dürfen keine neuen Reisen erstellen. Bitte füge Teilnehmer zu bestehenden Reisen hinzu.')) . '</div>';
    }

    if ($edit_trip_requested && !alpenia_user_can_edit_trip($edit_trip_id)) {
        $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff zum Bearbeiten dieser Reise.')) . '</div>';
        $edit_trip_requested = false;
        $edit_trip_id = 0;
    }

    if (isset($_POST['save_trip'])) {
        $submitted_trip_id = (int) ($_POST['trip_id'] ?? 0);
        $is_editing_trip = $submitted_trip_id > 0;
        $nonce_action = $is_editing_trip ? 'alpenia_edit_trip_' . $submitted_trip_id : 'alpenia_save_trip';

        if ($is_editing_trip && !alpenia_user_can_edit_trip($submitted_trip_id)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff zum Bearbeiten dieser Reise.')) . '</div>';
        } elseif (!$is_editing_trip && !alpenia_user_can_create_trip()) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Reiseleiter dürfen keine neuen Reisen erstellen. Bitte füge Teilnehmer zu bestehenden Reisen hinzu.')) . '</div>';
        } elseif (!isset($_POST['alpenia_trip_nonce']) || !wp_verify_nonce($_POST['alpenia_trip_nonce'], $nonce_action)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.')) . '</div>';
        } else {
            $trip_title      = sanitize_text_field($_POST['trip_title'] ?? '');
            $trip_type       = sanitize_text_field($_POST['trip_type'] ?? '');
            $trip_status     = sanitize_text_field($_POST['trip_status'] ?? 'open');
            $destination     = sanitize_text_field($_POST['destination'] ?? '');
            $country         = sanitize_text_field($_POST['country'] ?? '');
            $city            = sanitize_text_field($_POST['city'] ?? '');
            $departure_city  = sanitize_text_field($_POST['departure_city'] ?? '');
            $departure_airport = sanitize_text_field($_POST['departure_airport'] ?? '');
            $start_date      = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date        = sanitize_text_field($_POST['end_date'] ?? '');
            $max_people      = (int) ($_POST['max_people'] ?? 0);
            $price           = sanitize_text_field($_POST['price'] ?? '');
            $assigned_guide  = (int) ($_POST['assigned_guide'] ?? 0);
            $whatsapp_link   = esc_url_raw($_POST['whatsapp_link'] ?? '');
            $zoom_link       = esc_url_raw($_POST['zoom_link'] ?? '');
            $internal_notes  = sanitize_textarea_field($_POST['internal_notes'] ?? '');

            if (empty($trip_title) || empty($trip_type) || empty($destination) || empty($country) || empty($city) || empty($start_date) || empty($end_date)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte alle Pflichtfelder ausfüllen.')) . '</div>';
            } else {
                $today_timestamp = strtotime(wp_date('Y-m-d'));
                $start_timestamp = strtotime($start_date);
                $end_timestamp = strtotime($end_date);

                if ($start_timestamp === false || $end_timestamp === false) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte ein gültiges Datum eingeben.')) . '</div>';
                } elseif ($start_timestamp < $today_timestamp || $end_timestamp < $today_timestamp) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Start- und Enddatum dürfen nicht in der Vergangenheit liegen.')) . '</div>';
                } elseif ($end_timestamp < $start_timestamp) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Enddatum darf nicht vor dem Startdatum liegen.')) . '</div>';
                } else {
                    if ($is_editing_trip) {
                    $trip_id = wp_update_post([
                        'ID'           => $submitted_trip_id,
                        'post_title'   => $trip_title,
                        'post_type'    => 'group_trip',
                        'post_status'  => 'publish',
                        'post_content' => '',
                    ], true);
                } else {
                    $trip_id = wp_insert_post([
                        'post_title'   => $trip_title,
                        'post_type'    => 'group_trip',
                        'post_status'  => 'publish',
                        'post_author'  => get_current_user_id(),
                        'post_content' => '',
                    ]);
                }

                if ($trip_id && !is_wp_error($trip_id)) {
                    update_post_meta($trip_id, 'trip_type', $trip_type);
                    update_post_meta($trip_id, 'trip_status', $trip_status);
                    update_post_meta($trip_id, 'destination', $destination);
                    update_post_meta($trip_id, 'country', $country);
                    update_post_meta($trip_id, 'city', $city);
                    update_post_meta($trip_id, 'departure_city', $departure_city);
                    update_post_meta($trip_id, 'departure_airport', $departure_airport);
                    update_post_meta($trip_id, 'start_date', $start_date);
                    update_post_meta($trip_id, 'end_date', $end_date);
                    update_post_meta($trip_id, 'max_people', $max_people);
                    update_post_meta($trip_id, 'price', $price);
                    update_post_meta($trip_id, 'assigned_guide', $assigned_guide);
                    update_post_meta($trip_id, 'whatsapp_link', $whatsapp_link);
                    update_post_meta($trip_id, 'zoom_link', $zoom_link);
                    alpenia_update_secure_meta($trip_id, 'internal_notes', $internal_notes);
                    alpenia_public_participant_get_trip_registration_token($trip_id, true);

                    if ($is_editing_trip) {
                        $message = '<div class="alpenia-success" data-alpenia-feedback-popup="trip_updated">' . esc_html(alpenia_travel_t('Reise erfolgreich aktualisiert.')) . '</div>';
                    } else {
                        alpenia_send_notification('Neue Reise erstellt', 'Eine neue Reise wurde erstellt: ' . $trip_title, [
                            'event_label' => 'Neue Reise angelegt',
                            'trip' => $trip_title,
                            'trip_id' => $trip_id,
                            'user' => wp_get_current_user()->display_name,
                        ]);
                        $message = '<div class="alpenia-success" data-alpenia-feedback-popup="trip_created">' . esc_html(alpenia_travel_t('Reise erfolgreich erstellt.')) . '</div>';
                    }
                    } else {
                        $message = '<div class="alpenia-message">' . esc_html($is_editing_trip ? alpenia_travel_t('Fehler beim Aktualisieren der Reise.') : alpenia_travel_t('Fehler beim Erstellen der Reise.')) . '</div>';
                    }
                }
                }
            }
        }

    if (isset($_GET['delete_trip']) && isset($_GET['_delete_trip_nonce'])) {
        $trip_id = (int) $_GET['delete_trip'];

        if (!alpenia_user_can_delete_trip($trip_id)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff zum Löschen dieser Reise.')) . '</div>';
        } elseif (wp_verify_nonce($_GET['_delete_trip_nonce'], 'alpenia_delete_trip_' . $trip_id)) {
            $delete_mode = (isset($_GET['delete_mode']) && $_GET['delete_mode'] === 'hard') ? 'hard' : 'trash';
            $force_delete = $delete_mode === 'hard';

            $trip_participants = get_posts([
                'post_type'   => 'trip_participant',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_key'    => 'trip_id',
                'meta_value'  => $trip_id,
            ]);

            foreach ($trip_participants as $participant) {
                if ($force_delete) {
                    wp_delete_post($participant->ID, true);
                } else {
                    wp_trash_post($participant->ID);
                }
            }

            if ($force_delete) {
                wp_delete_post($trip_id, true);
                $message = '<div class="alpenia-success" data-alpenia-delete-popup="trip">' . esc_html(alpenia_travel_t('Reise wurde dauerhaft gelöscht.')) . '</div>';
            } else {
                wp_trash_post($trip_id);
                $message = '<div class="alpenia-success" data-alpenia-delete-popup="trip">' . esc_html(alpenia_travel_t('Reise wurde in den Papierkorb verschoben.')) . '</div>';
            }
        } else {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Löschen nicht erlaubt.')) . '</div>';
        }
    }

    if (isset($_POST['save_participants_batch'])) {
        if (!isset($_POST['alpenia_participant_batch_nonce']) || !wp_verify_nonce($_POST['alpenia_participant_batch_nonce'], 'alpenia_save_participants_batch')) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Teilnehmerformular.')) . '</div>';
        } else {
            $trip_id = (int) ($_POST['trip_id'] ?? 0);
            $participant_count = (int) ($_POST['participant_count'] ?? 0);

            if (!$trip_id || $participant_count < 1 || !alpenia_user_can_access_trip($trip_id)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Ungültige Reise oder kein Zugriff.')) . '</div>';
            } else {
                $trip_capacity = (int) get_post_meta($trip_id, 'max_people', true);
                $existing_participants_count = alpenia_count_trip_participants($trip_id);

                if ($trip_capacity > 0 && ($existing_participants_count + $participant_count) > $trip_capacity) {
                    $available_spots = max(0, $trip_capacity - $existing_participants_count);
                    $message = '<div class="alpenia-message">' . esc_html(sprintf(
                        alpenia_travel_t('Bu geziye en fazla %d katılımcı daha eklenebilir. / Für diese Reise können maximal noch %d Teilnehmer hinzugefügt werden.'),
                        $available_spots,
                        $available_spots
                    )) . '</div>';
                    return $message;
                }

                $all_ok = true;
                $saved_count = 0;
                $is_pilgrimage_trip = alpenia_is_pilgrimage_trip($trip_id);

                for ($i = 1; $i <= $participant_count; $i++) {
                    $gender             = sanitize_text_field($_POST["gender_$i"] ?? '');
                    $first_name         = sanitize_text_field($_POST["first_name_$i"] ?? '');
                    $second_first_name  = sanitize_text_field($_POST["second_first_name_$i"] ?? '');
                    $last_name          = sanitize_text_field($_POST["last_name_$i"] ?? '');
                    $birth_date         = sanitize_text_field($_POST["birth_date_$i"] ?? '');
                    $street_address     = sanitize_text_field($_POST["street_address_$i"] ?? '');
                    $postal_city        = sanitize_text_field($_POST["postal_city_$i"] ?? '');
                    $nationality        = sanitize_text_field($_POST["nationality_$i"] ?? '');
                    $passport_no        = sanitize_text_field($_POST["passport_no_$i"] ?? '');
                    $phone_number       = sanitize_text_field($_POST["phone_number_$i"] ?? '');
                    $email_address      = sanitize_email($_POST["email_address_$i"] ?? '');
                    $emergency_contact_name = sanitize_text_field($_POST["emergency_contact_name_$i"] ?? '');
                    $emergency_contact_phone = sanitize_text_field($_POST["emergency_contact_phone_$i"] ?? '');
                    $passport_valid_from = sanitize_text_field($_POST["passport_valid_from_$i"] ?? '');
                    $passport_expiry     = sanitize_text_field($_POST["passport_expiry_date_$i"] ?? '');
                    $residence_permit_start_date = sanitize_text_field($_POST["residence_permit_start_date_$i"] ?? '');
                    $residence_permit_number = sanitize_text_field($_POST["residence_permit_number_$i"] ?? '');
                    $residence_permit_valid_until = sanitize_text_field($_POST["residence_permit_valid_until_$i"] ?? '');
                    $visa_entry_country = sanitize_text_field($_POST["visa_entry_country_$i"] ?? '');
                    $visa_number        = sanitize_text_field($_POST["visa_number_$i"] ?? '');
                    $visa_expiry_date   = sanitize_text_field($_POST["visa_expiry_date_$i"] ?? '');
                    $participant_status = sanitize_text_field($_POST["participant_status_$i"] ?? 'neu');
                    $room_assignment    = sanitize_text_field($_POST["room_assignment_$i"] ?? '');
                    $subgroup           = sanitize_text_field($_POST["subgroup_$i"] ?? '');
                    $payment_total      = (float) ($_POST["payment_total_$i"] ?? 0);
                    $payment_deposit    = (float) ($_POST["payment_deposit_$i"] ?? 0);
                    $payment_paid       = (float) ($_POST["payment_paid_$i"] ?? 0);
                    $check_passport     = !empty($_POST["check_passport_$i"]) ? 1 : 0;
                    $check_photo        = !empty($_POST["check_photo_$i"]) ? 1 : 0;
                    $check_visa         = !empty($_POST["check_visa_$i"]) ? 1 : 0;
                    $check_payment      = !empty($_POST["check_payment_$i"]) ? 1 : 0;

                    $passport_missing   = empty($_FILES["passport_file_$i"]['name']);
                    $photo_missing      = empty($_FILES["photo_file_$i"]['name']);
                    $visa_photo_missing = empty($_FILES["visa_photo_file_$i"]['name']);
                    $is_eu_or_schengen_citizen = alpenia_is_eu_or_schengen_nationality($nationality);

                    if (
                        empty($gender) ||
                        empty($first_name) ||
                        empty($last_name) ||
                        empty($nationality) ||
                        empty($phone_number) ||
                        empty($email_address) ||
                        empty($emergency_contact_name) ||
                        empty($emergency_contact_phone) ||
                        empty($passport_valid_from) ||
                        empty($passport_expiry) ||
                        $passport_missing ||
                        $photo_missing
                    ) {
                        $all_ok = false;
                        break;
                    }

                    if (!$is_eu_or_schengen_citizen) {
                        if (empty($residence_permit_start_date) || empty($residence_permit_number) || empty($residence_permit_valid_until) || $visa_photo_missing) {
                            $all_ok = false;
                            break;
                        }
                    }

                    if ($is_pilgrimage_trip && (empty($visa_entry_country) || empty($visa_number) || empty($visa_expiry_date))) {
                        $all_ok = false;
                        break;
                    }

                    if ($check_passport !== 1 || $check_photo !== 1 || $check_payment !== 1 || (!$is_eu_or_schengen_citizen && $check_visa !== 1)) {
                        $all_ok = false;
                        break;
                    }

                    $participant_id = wp_insert_post([
                        'post_title'  => trim($first_name . ' ' . $last_name),
                        'post_type'   => 'trip_participant',
                        'post_status' => 'publish',
                        'post_author' => get_current_user_id(),
                    ]);

                    if (!$participant_id || is_wp_error($participant_id)) {
                        $all_ok = false;
                        break;
                    }

                    update_post_meta($participant_id, 'trip_id', $trip_id);
                    update_post_meta($participant_id, 'gender', $gender);
                    alpenia_update_secure_meta($participant_id, 'first_name', $first_name);
                    alpenia_update_secure_meta($participant_id, 'second_first_name', $second_first_name);
                    alpenia_update_secure_meta($participant_id, 'last_name', $last_name);
                    alpenia_update_secure_meta($participant_id, 'birth_date', $birth_date);
                    alpenia_update_secure_meta($participant_id, 'street_address', $street_address);
                    alpenia_update_secure_meta($participant_id, 'postal_city', $postal_city);
                    alpenia_update_secure_meta($participant_id, 'nationality', $nationality);
                    alpenia_update_secure_meta($participant_id, 'passport_no', $passport_no);
                    alpenia_update_secure_meta($participant_id, 'phone_number', $phone_number);
                    alpenia_update_secure_meta($participant_id, 'email_address', $email_address);
                    alpenia_update_secure_meta($participant_id, 'emergency_contact_name', $emergency_contact_name);
                    alpenia_update_secure_meta($participant_id, 'emergency_contact_phone', $emergency_contact_phone);
                    alpenia_update_secure_meta($participant_id, 'passport_valid_from_date', $passport_valid_from);
                    alpenia_update_secure_meta($participant_id, 'passport_expiry_date', $passport_expiry);
                    alpenia_update_secure_meta($participant_id, 'residence_permit_start_date', $residence_permit_start_date);
                    alpenia_update_secure_meta($participant_id, 'residence_permit_number', $residence_permit_number);
                    alpenia_update_secure_meta($participant_id, 'residence_permit_valid_until', $residence_permit_valid_until);
                    alpenia_update_secure_meta($participant_id, 'visa_entry_country', $visa_entry_country);
                    alpenia_update_secure_meta($participant_id, 'visa_number', $visa_number);
                    alpenia_update_secure_meta($participant_id, 'visa_expiry_date', $visa_expiry_date);
                    update_post_meta($participant_id, 'participant_status', $participant_status);
                        alpenia_update_secure_meta($participant_id, 'subgroup', $subgroup);
                    update_post_meta($participant_id, 'payment_total', $payment_total);
                    update_post_meta($participant_id, 'payment_deposit', $payment_deposit);
                    update_post_meta($participant_id, 'payment_paid', $payment_paid);
                    update_post_meta($participant_id, 'check_passport', $check_passport);
                    update_post_meta($participant_id, 'check_photo', $check_photo);
                    update_post_meta($participant_id, 'check_visa', $check_visa);
                    update_post_meta($participant_id, 'check_payment', $check_payment);

                    $passport_file_id    = alpenia_handle_file_upload("passport_file_$i");
                    $photo_file_id       = alpenia_handle_file_upload("photo_file_$i");
                    $visa_photo_file_id  = alpenia_handle_file_upload("visa_photo_file_$i");
                    $meldezettel_file_id = alpenia_handle_file_upload("meldezettel_file_$i");

                    if (is_wp_error($passport_file_id) || is_wp_error($photo_file_id) || is_wp_error($visa_photo_file_id) || is_wp_error($meldezettel_file_id)) {
                        $all_ok = false;

                        $error_text = '' . esc_html(alpenia_travel_t('Eine oder mehrere Dateien sind zu groß.')) . '';
                        if (is_wp_error($passport_file_id)) $error_text = alpenia_travel_t($passport_file_id->get_error_message());
                        elseif (is_wp_error($photo_file_id)) $error_text = alpenia_travel_t($photo_file_id->get_error_message());
                        elseif (is_wp_error($visa_photo_file_id)) $error_text = alpenia_travel_t($visa_photo_file_id->get_error_message());
                        elseif (is_wp_error($meldezettel_file_id)) $error_text = alpenia_travel_t($meldezettel_file_id->get_error_message());

                        wp_delete_post($participant_id, true);
                        $message = '<div class="alpenia-message">' . esc_html($error_text) . '</div>';
                        break;
                    }

                    update_post_meta($participant_id, 'passport_file_id', $passport_file_id);
                    update_post_meta($participant_id, 'photo_file_id', $photo_file_id);

                    if ($visa_photo_file_id) update_post_meta($participant_id, 'visa_photo_file_id', $visa_photo_file_id);
                    if ($meldezettel_file_id) update_post_meta($participant_id, 'meldezettel_file_id', $meldezettel_file_id);

                    $saved_count++;
                }

                if ($all_ok) {
                    alpenia_send_notification('Neue Teilnehmer erfasst', $saved_count . ' Teilnehmer wurden für eine Reise gespeichert.', [
                        'event_label' => 'Teilnehmer wurden erfasst',
                        'trip' => get_the_title($trip_id),
                        'trip_id' => $trip_id,
                        'user' => wp_get_current_user()->display_name,
                    ]);
                    $redirect_url = alpenia_dashboard_link([
                        'participant_saved' => 1,
                        'saved_count' => (int) $saved_count,
                    ]);
                    wp_safe_redirect($redirect_url);
                    exit;
                } elseif ($message === '') {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte alle Pflichtfelder ausfüllen.')) . ' ' . esc_html(alpenia_travel_t('Pflicht sind Anrede, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von, Reisepass gültig bis, Reisepass, Porträtfoto und die komplette Checkliste. Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind zusätzlich Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht. Bei Umrah-/Hajj-Reisen sind zusätzlich Vize-Einreiseland, Vize Nummer und Vize gültig bis Pflicht.')) . '</div>';
                }
            }
        }
    }

    if (isset($_GET['delete_participant']) && isset($_GET['_delete_nonce'])) {
        $participant_id = (int) $_GET['delete_participant'];

        if (!alpenia_user_can_access_participant($participant_id)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff.')) . '</div>';
        } elseif (wp_verify_nonce($_GET['_delete_nonce'], 'alpenia_delete_participant_' . $participant_id)) {
            $delete_mode = (isset($_GET['delete_mode']) && $_GET['delete_mode'] === 'hard') ? 'hard' : 'trash';
            if ($delete_mode === 'hard') {
                wp_delete_post($participant_id, true);
                $message = '<div class="alpenia-success" data-alpenia-delete-popup="participant">' . esc_html(alpenia_travel_t('Teilnehmer wurde dauerhaft gelöscht.')) . '</div>';
            } else {
                wp_trash_post($participant_id);
                $message = '<div class="alpenia-success" data-alpenia-delete-popup="participant">' . esc_html(alpenia_travel_t('Teilnehmer wurde in den Papierkorb verschoben.')) . '</div>';
            }
        } else {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Löschen nicht erlaubt.')) . '</div>';
        }
    }

    if (isset($_GET['restore_item']) && isset($_GET['_restore_nonce'])) {
        $item_id = (int) $_GET['restore_item'];
        if (wp_verify_nonce($_GET['_restore_nonce'], 'alpenia_restore_item_' . $item_id)) {
            wp_untrash_post($item_id);
            $message = '<div class="alpenia-success" data-alpenia-feedback-popup="item_restored">' . esc_html(alpenia_travel_t('Eintrag wurde wiederhergestellt.')) . '</div>';
        } else {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.')) . '</div>';
        }
    }

    if (isset($_GET['delete_item_permanently']) && isset($_GET['_hard_delete_nonce'])) {
        $item_id = (int) $_GET['delete_item_permanently'];
        if (wp_verify_nonce($_GET['_hard_delete_nonce'], 'alpenia_hard_delete_item_' . $item_id)) {
            wp_delete_post($item_id, true);
            $message = '<div class="alpenia-success" data-alpenia-feedback-popup="item_deleted">' . esc_html(alpenia_travel_t('Eintrag wurde dauerhaft gelöscht.')) . '</div>';
        } else {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.')) . '</div>';
        }
    }

    if (isset($_POST['update_participant'])) {
        $participant_id = (int) ($_POST['participant_id'] ?? 0);

        if (!alpenia_user_can_access_participant($participant_id)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff.')) . '</div>';
        } elseif (!isset($_POST['alpenia_edit_participant_nonce']) || !wp_verify_nonce($_POST['alpenia_edit_participant_nonce'], 'alpenia_edit_participant_' . $participant_id)) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Bearbeiten.')) . '</div>';
        } else {
            $gender             = sanitize_text_field($_POST['gender'] ?? '');
            $first_name         = sanitize_text_field($_POST['first_name'] ?? '');
            $second_first_name  = sanitize_text_field($_POST['second_first_name'] ?? '');
            $last_name          = sanitize_text_field($_POST['last_name'] ?? '');
            $birth_date         = sanitize_text_field($_POST['birth_date'] ?? '');
            $street_address     = sanitize_text_field($_POST['street_address'] ?? '');
            $postal_city        = sanitize_text_field($_POST['postal_city'] ?? '');
            $nationality        = sanitize_text_field($_POST['nationality'] ?? '');
            $passport_no        = sanitize_text_field($_POST['passport_no'] ?? '');
            $phone_number       = sanitize_text_field($_POST['phone_number'] ?? '');
            $email_address      = sanitize_email($_POST['email_address'] ?? '');
            $emergency_contact_name = sanitize_text_field($_POST['emergency_contact_name'] ?? '');
            $emergency_contact_phone = sanitize_text_field($_POST['emergency_contact_phone'] ?? '');
            $passport_valid_from = sanitize_text_field($_POST['passport_valid_from_date'] ?? '');
            $passport_expiry     = sanitize_text_field($_POST['passport_expiry_date'] ?? '');
            $residence_permit_start_date = sanitize_text_field($_POST['residence_permit_start_date'] ?? '');
            $residence_permit_number = sanitize_text_field($_POST['residence_permit_number'] ?? '');
            $residence_permit_valid_until = sanitize_text_field($_POST['residence_permit_valid_until'] ?? '');
            $visa_entry_country = sanitize_text_field($_POST['visa_entry_country'] ?? '');
            $visa_number        = sanitize_text_field($_POST['visa_number'] ?? '');
            $visa_expiry_date   = sanitize_text_field($_POST['visa_expiry_date'] ?? '');
            $participant_status = sanitize_text_field($_POST['participant_status'] ?? '');
            $payment_total      = (float) ($_POST['payment_total'] ?? 0);
            $payment_deposit    = (float) ($_POST['payment_deposit'] ?? 0);
            $payment_paid       = (float) ($_POST['payment_paid'] ?? 0);
            $check_passport     = !empty($_POST['check_passport']) ? 1 : 0;
            $check_photo        = !empty($_POST['check_photo']) ? 1 : 0;
            $check_visa         = !empty($_POST['check_visa']) ? 1 : 0;
            $check_payment      = !empty($_POST['check_payment']) ? 1 : 0;
            $is_eu_or_schengen_citizen = alpenia_is_eu_or_schengen_nationality($nationality);
            $trip_id_for_validation = (int) get_post_meta($participant_id, 'trip_id', true);
            $is_pilgrimage_trip = alpenia_is_pilgrimage_trip($trip_id_for_validation);

            if (empty($gender) || empty($first_name) || empty($last_name) || empty($nationality) || empty($passport_valid_from) || empty($passport_expiry)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte Herr/Frau, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von und Reisepass gültig bis ausfüllen.')) . '</div>';
            } elseif (!$is_eu_or_schengen_citizen && (empty($residence_permit_start_date) || empty($residence_permit_number) || empty($residence_permit_valid_until))) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht.')) . '</div>';
            } elseif ($is_pilgrimage_trip && (empty($visa_entry_country) || empty($visa_number) || empty($visa_expiry_date))) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bei Umrah-/Hajj-Reisen sind Vize-Einreiseland, Vize Nummer und Vize gültig bis Pflicht.')) . '</div>';
            } else {
                wp_update_post([
                    'ID'         => $participant_id,
                    'post_title' => trim($first_name . ' ' . $last_name),
                ]);

                update_post_meta($participant_id, 'gender', $gender);
                alpenia_update_secure_meta($participant_id, 'first_name', $first_name);
                alpenia_update_secure_meta($participant_id, 'second_first_name', $second_first_name);
                alpenia_update_secure_meta($participant_id, 'last_name', $last_name);
                alpenia_update_secure_meta($participant_id, 'birth_date', $birth_date);
                alpenia_update_secure_meta($participant_id, 'street_address', $street_address);
                alpenia_update_secure_meta($participant_id, 'postal_city', $postal_city);
                alpenia_update_secure_meta($participant_id, 'nationality', $nationality);
                alpenia_update_secure_meta($participant_id, 'passport_no', $passport_no);
                alpenia_update_secure_meta($participant_id, 'phone_number', $phone_number);
                alpenia_update_secure_meta($participant_id, 'email_address', $email_address);
                alpenia_update_secure_meta($participant_id, 'emergency_contact_name', $emergency_contact_name);
                alpenia_update_secure_meta($participant_id, 'emergency_contact_phone', $emergency_contact_phone);
                alpenia_update_secure_meta($participant_id, 'passport_valid_from_date', $passport_valid_from);
                alpenia_update_secure_meta($participant_id, 'passport_expiry_date', $passport_expiry);
                alpenia_update_secure_meta($participant_id, 'residence_permit_start_date', $residence_permit_start_date);
                alpenia_update_secure_meta($participant_id, 'residence_permit_number', $residence_permit_number);
                alpenia_update_secure_meta($participant_id, 'residence_permit_valid_until', $residence_permit_valid_until);
                alpenia_update_secure_meta($participant_id, 'visa_entry_country', $visa_entry_country);
                alpenia_update_secure_meta($participant_id, 'visa_number', $visa_number);
                alpenia_update_secure_meta($participant_id, 'visa_expiry_date', $visa_expiry_date);
                update_post_meta($participant_id, 'participant_status', $participant_status);
                update_post_meta($participant_id, 'payment_total', $payment_total);
                update_post_meta($participant_id, 'payment_deposit', $payment_deposit);
                update_post_meta($participant_id, 'payment_paid', $payment_paid);
                update_post_meta($participant_id, 'check_passport', $check_passport);
                update_post_meta($participant_id, 'check_photo', $check_photo);
                update_post_meta($participant_id, 'check_visa', $check_visa);
                update_post_meta($participant_id, 'check_payment', $check_payment);

                $passport_file_id    = alpenia_handle_file_upload('passport_file');
                $photo_file_id       = alpenia_handle_file_upload('photo_file');
                $visa_photo_file_id  = alpenia_handle_file_upload('visa_photo_file');
                $meldezettel_file_id = alpenia_handle_file_upload('meldezettel_file');

                if (is_wp_error($passport_file_id) || is_wp_error($photo_file_id) || is_wp_error($visa_photo_file_id) || is_wp_error($meldezettel_file_id)) {
                    if (is_wp_error($passport_file_id)) $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t($passport_file_id->get_error_message())) . '</div>';
                    elseif (is_wp_error($photo_file_id)) $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t($photo_file_id->get_error_message())) . '</div>';
                    elseif (is_wp_error($visa_photo_file_id)) $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t($visa_photo_file_id->get_error_message())) . '</div>';
                    elseif (is_wp_error($meldezettel_file_id)) $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t($meldezettel_file_id->get_error_message())) . '</div>';
                } else {
                    if ($passport_file_id) update_post_meta($participant_id, 'passport_file_id', $passport_file_id);
                    if ($photo_file_id) update_post_meta($participant_id, 'photo_file_id', $photo_file_id);
                    if ($visa_photo_file_id) update_post_meta($participant_id, 'visa_photo_file_id', $visa_photo_file_id);
                    if ($meldezettel_file_id) update_post_meta($participant_id, 'meldezettel_file_id', $meldezettel_file_id);

                    $message = '<div class="alpenia-success" data-alpenia-feedback-popup="participant_updated">' . esc_html(alpenia_travel_t('Teilnehmer erfolgreich aktualisiert.')) . '</div>';
                }
            }
        }
    }

    if (isset($_POST['create_reiseleiter']) && alpenia_user_can_manage_users()) {
        if (!isset($_POST['alpenia_create_user_nonce']) || !wp_verify_nonce($_POST['alpenia_create_user_nonce'], 'alpenia_create_user')) {
            $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Anlegen des Benutzers.')) . '</div>';
        } else {
            $display_name = sanitize_text_field($_POST['display_name'] ?? '');
            $email        = sanitize_email($_POST['email'] ?? '');
            $password     = $_POST['password'] ?? '';
            $role         = sanitize_text_field($_POST['role'] ?? 'reiseleiter');

            if (empty($display_name) || empty($email) || empty($password)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte Name, E-Mail und Passwort ausfüllen.')) . '</div>';
            } elseif (email_exists($email)) {
                $message = '<div class="alpenia-message" data-alpenia-feedback-popup="user_email_exists">' . esc_html(alpenia_travel_t('Diese E-Mail existiert bereits.')) . '</div>';
            } else {
                $allowed_roles = ['reiseleiter', 'backoffice', 'administrator'];
                if (!in_array($role, $allowed_roles, true)) {
                    $role = 'reiseleiter';
                }

                $email_user_part = sanitize_user((string) current(explode('@', $email)), true);
                $username_base = !empty($email_user_part) ? $email_user_part : 'user';
                $username = $username_base;
                $counter = 1;

                while (username_exists($username)) {
                    $username = $username_base . $counter;
                    $counter++;
                }

                $user_id = wp_create_user($username, $password, $email);

                if (is_wp_error($user_id)) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Benutzer konnte nicht erstellt werden.')) . '</div>';
                } else {
                    wp_update_user([
                        'ID'           => $user_id,
                        'display_name' => $display_name,
                    ]);

                    $user_obj = new WP_User($user_id);
                    $user_obj->set_role($role);

                    $reloaded_user = get_user_by('id', $user_id);
                    if ($reloaded_user && in_array($role, (array) $reloaded_user->roles, true)) {
                        $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_created">' . esc_html(alpenia_travel_t('Benutzer erfolgreich erstellt.')) . '</div>';
                    } else {
                        $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Benutzer wurde erstellt, aber die Rolle konnte nicht korrekt gesetzt werden.')) . '</div>';
                    }
                }
            }
        }
    }

    if (alpenia_user_can_manage_users()) {

        if (isset($_POST['dashboard_user_action'])) {
            $dashboard_user_action = sanitize_key((string) ($_POST['dashboard_user_action'] ?? ''));
            $target_id = (int) ($_POST['dashboard_user_id'] ?? 0);
            $nonce = sanitize_text_field(wp_unslash($_POST['_dashboard_user_nonce'] ?? ''));

            if ($dashboard_user_action === 'deactivate') {
                if (!wp_verify_nonce($nonce, 'alpenia_dashboard_deactivate_user_' . $target_id)) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Deaktivieren des Benutzers.')) . '</div>';
                } elseif ($target_id > 0 && $target_id !== get_current_user_id()) {
                    update_user_meta($target_id, 'alpenia_disabled', 1);
                    $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_deactivated">' . esc_html(alpenia_travel_t('Benutzer deaktiviert.')) . '</div>';
                }
            } elseif ($dashboard_user_action === 'activate') {
                if (!wp_verify_nonce($nonce, 'alpenia_dashboard_activate_user_' . $target_id)) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Aktivieren des Benutzers.')) . '</div>';
                } elseif ($target_id > 0) {
                    delete_user_meta($target_id, 'alpenia_disabled');
                    $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_activated">' . esc_html(alpenia_travel_t('Benutzer aktiviert.')) . '</div>';
                }
            } elseif ($dashboard_user_action === 'delete') {
                if (!wp_verify_nonce($nonce, 'alpenia_dashboard_delete_user_' . $target_id)) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Löschen des Benutzers.')) . '</div>';
                } elseif (
                    $target_id > 0 &&
                    $target_id !== get_current_user_id() &&
                    get_user_meta($target_id, 'alpenia_disabled', true)
                ) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user($target_id);
                    $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_deleted">' . esc_html(alpenia_travel_t('Benutzer gelöscht.')) . '</div>';
                }
            }
        }

        if (isset($_GET['dashboard_deactivate_user'])) {
            $target_id = (int) $_GET['dashboard_deactivate_user'];
            $nonce = sanitize_text_field(wp_unslash($_GET['_dashboard_user_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'alpenia_dashboard_deactivate_user_' . $target_id)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Deaktivieren des Benutzers.')) . '</div>';
            } elseif ($target_id > 0 && $target_id !== get_current_user_id()) {
                update_user_meta($target_id, 'alpenia_disabled', 1);
                $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_deactivated">' . esc_html(alpenia_travel_t('Benutzer deaktiviert.')) . '</div>';
            }
        }

        if (isset($_GET['dashboard_activate_user'])) {
            $target_id = (int) $_GET['dashboard_activate_user'];
            $nonce = sanitize_text_field(wp_unslash($_GET['_dashboard_user_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'alpenia_dashboard_activate_user_' . $target_id)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Aktivieren des Benutzers.')) . '</div>';
            } elseif ($target_id > 0) {
                delete_user_meta($target_id, 'alpenia_disabled');
                $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_activated">' . esc_html(alpenia_travel_t('Benutzer aktiviert.')) . '</div>';
            }
        }

        if (isset($_GET['dashboard_delete_user'])) {
            $target_id = (int) $_GET['dashboard_delete_user'];
            $nonce = sanitize_text_field(wp_unslash($_GET['_dashboard_user_nonce'] ?? ''));

            if (!wp_verify_nonce($nonce, 'alpenia_dashboard_delete_user_' . $target_id)) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Löschen des Benutzers.')) . '</div>';
            } elseif (
                $target_id > 0 &&
                $target_id !== get_current_user_id() &&
                get_user_meta($target_id, 'alpenia_disabled', true)
            ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($target_id);
                $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_deleted">' . esc_html(alpenia_travel_t('Benutzer gelöscht.')) . '</div>';
            }
        }

        if (isset($_GET['dashboard_user_updated'])) {
            $message = '<div class="alpenia-success" data-alpenia-feedback-popup="user_updated">' . esc_html(alpenia_travel_t('Benutzer erfolgreich aktualisiert.')) . '</div>';
        }

        if (isset($_POST['dashboard_update_user']) || isset($_POST['alpenia_dashboard_edit_user_nonce'])) {
            if (
                !isset($_POST['alpenia_dashboard_edit_user_nonce']) ||
                !wp_verify_nonce($_POST['alpenia_dashboard_edit_user_nonce'], 'alpenia_dashboard_edit_user')
            ) {
                $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Sicherheitsfehler beim Bearbeiten des Benutzers.')) . '</div>';
            } else {
                $edit_user_id = (int) ($_POST['edit_user_id'] ?? 0);
                $edit_display_name = sanitize_text_field($_POST['edit_display_name'] ?? '');
                $edit_email = sanitize_email($_POST['edit_email'] ?? '');
                $edit_password = $_POST['edit_password'] ?? '';
                $edit_role = sanitize_text_field($_POST['edit_role'] ?? 'reiseleiter');

                $allowed_roles = ['reiseleiter', 'backoffice', 'administrator'];
                if (!in_array($edit_role, $allowed_roles, true)) {
                    $edit_role = 'reiseleiter';
                }

                $existing_email_owner = email_exists($edit_email);

                if (!$edit_user_id || empty($edit_display_name) || empty($edit_email)) {
                    $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Bitte alle Pflichtfelder ausfüllen.')) . '</div>';
                } elseif ($existing_email_owner && (int) $existing_email_owner !== $edit_user_id) {
                    $message = '<div class="alpenia-message" data-alpenia-feedback-popup="user_email_in_use">' . esc_html(alpenia_travel_t('Diese E-Mail wird bereits verwendet.')) . '</div>';
                } else {
                    $updated = wp_update_user([
                        'ID'           => $edit_user_id,
                        'display_name' => $edit_display_name,
                        'user_email'   => $edit_email,
                    ]);

                    if (is_wp_error($updated)) {
                        $message = '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Fehler beim Speichern.')) . '</div>';
                    } else {
                        $edited_user = new WP_User($edit_user_id);
                        $edited_user->set_role($edit_role);

                        if (!empty($edit_password)) {
                            wp_set_password($edit_password, $edit_user_id);
                        }

                        $redirect_url = alpenia_dashboard_link([
                            'manage_users' => 1,
                            'dashboard_user_updated' => 1,
                        ]);
                        wp_safe_redirect($redirect_url);
                        exit;
                    }
                }
            }
        }
    }

    $all_trips = alpenia_get_filtered_trips('', '', '', '', '', '');
    $filtered_trips_result = alpenia_get_paginated_filtered_trips(
        $trip_search,
        $trip_type_filter,
        $trip_status_filter,
        $trip_country_filter,
        $trip_city_filter,
        $guide_filter,
        $trip_page,
        $trips_per_page
    );
    $filtered_trips = $filtered_trips_result['posts'];
    $filtered_trips_total = (int) $filtered_trips_result['total'];
    $filtered_trips_max_pages = (int) $filtered_trips_result['max_pages'];

    if ($filtered_trips_total > 0 && $trip_page > $filtered_trips_max_pages) {
        $trip_page = $filtered_trips_max_pages;
        $filtered_trips_result = alpenia_get_paginated_filtered_trips(
            $trip_search,
            $trip_type_filter,
            $trip_status_filter,
            $trip_country_filter,
            $trip_city_filter,
            $guide_filter,
            $trip_page,
            $trips_per_page
        );
        $filtered_trips = $filtered_trips_result['posts'];
    }

    if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) {
        $participants = get_posts([
            'post_type'   => 'trip_participant',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        $latest_participants = get_posts([
            'post_type'   => 'trip_participant',
            'post_status' => 'publish',
            'numberposts' => 5,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);
    } else {
        $allowed_trip_ids = array_map(function($trip) {
            return $trip->ID;
        }, $all_trips);

        if (empty($allowed_trip_ids)) {
            $allowed_trip_ids = [0];
        }

        $participants = get_posts([
            'post_type'   => 'trip_participant',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'     => 'trip_id',
                    'value'   => $allowed_trip_ids,
                    'compare' => 'IN',
                ]
            ],
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        $latest_participants = get_posts([
            'post_type'   => 'trip_participant',
            'post_status' => 'publish',
            'numberposts' => 5,
            'meta_query'  => [
                [
                    'key'     => 'trip_id',
                    'value'   => $allowed_trip_ids,
                    'compare' => 'IN',
                ]
            ],
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);
    }

    $dashboard_users = get_users([
        'role__in' => ['administrator', 'reiseleiter', 'backoffice'],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ]);

    $dashboard_edit_mode = false;
    $dashboard_edit_user = null;

    if (alpenia_user_can_manage_users() && isset($_GET['dashboard_edit_user'])) {
        $dashboard_edit_user = get_user_by('id', (int) $_GET['dashboard_edit_user']);
        if ($dashboard_edit_user) $dashboard_edit_mode = true;
    }

    $guides = get_users([
        'role__in' => ['administrator', 'reiseleiter'],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ]);

    $countries = [];
    $cities = [];

    foreach ($all_trips as $trip_item) {
        $trip_country = trim((string) get_post_meta($trip_item->ID, 'country', true));
        $trip_city = trim((string) get_post_meta($trip_item->ID, 'city', true));

        if ($trip_country !== '') $countries[$trip_country] = $trip_country;
        if ($trip_city !== '') $cities[$trip_city] = $trip_city;
    }

    ksort($countries);
    ksort($cities);

    $total_trips = count($all_trips);
    $total_participants = count($participants);

    $missing_docs_count = 0;
    $missing_docs_total_count = 0;
    $open_payments_count = 0;
    $open_payments_total_amount = 0.0;
    $missing_docs_items = [];
    $open_payments_items = [];
    $priority_payment_items = [];
    $operations_task_items = [];
    $missing_docs_by_type = [];
    $participant_status_counts = [
        'neu' => 0,
        'in_pruefung' => 0,
        'vollstaendig' => 0,
        'other' => 0,
    ];
    $overview_item_limit = 6;
    $business_revenue_total = 0.0;
    $business_revenue_paid = 0.0;
    $business_revenue_open = 0.0;
    $growth_current_month_key = date('Y-m', current_time('timestamp'));
    $growth_previous_month_key = date('Y-m', strtotime('-1 month', current_time('timestamp')));
    $growth_current_month_participants = 0;
    $growth_previous_month_participants = 0;
    $growth_current_month_revenue = 0.0;
    $growth_previous_month_revenue = 0.0;
    $participants_in_review = 0;
    $total_capacity = 0;
    $occupied_capacity = 0;
    $trip_growth_stats = [];

    foreach ($all_trips as $trip_item) {
        $max_people = (int) get_post_meta($trip_item->ID, 'max_people', true);
        $trip_growth_stats[$trip_item->ID] = [
            'trip_id' => $trip_item->ID,
            'title' => $trip_item->post_title,
            'status' => get_post_meta($trip_item->ID, 'trip_status', true),
            'type' => get_post_meta($trip_item->ID, 'trip_type', true),
            'country' => get_post_meta($trip_item->ID, 'country', true),
            'city' => get_post_meta($trip_item->ID, 'city', true),
            'guide_id' => (int) get_post_meta($trip_item->ID, 'assigned_guide', true),
            'participants' => 0,
            'capacity' => max(0, $max_people),
            'revenue_total' => 0.0,
            'revenue_paid' => 0.0,
            'revenue_open' => 0.0,
            'missing_docs' => 0,
            'open_payments' => 0,
            'in_review' => 0,
            'new_participants' => 0,
            'complete_participants' => 0,
            'occupancy' => 0,
        ];

        if ($max_people > 0) {
            $total_capacity += $max_people;
        }
    }

    foreach ($participants as $participant) {
        $participant_id = $participant->ID;
        $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);

        if (!$trip_id || !alpenia_user_can_access_trip($trip_id)) continue;

        $payment_total = (float) get_post_meta($participant_id, 'payment_total', true);
        $payment_paid = (float) get_post_meta($participant_id, 'payment_paid', true);
        $payment_open = alpenia_get_participant_payment_open($participant_id);
        $participant_status = sanitize_key((string) get_post_meta($participant_id, 'participant_status', true));
        $score = alpenia_get_participant_doc_score($participant_id);
        $participant_month_key = !empty($participant->post_date) ? date('Y-m', strtotime($participant->post_date)) : '';

        if ($participant_month_key === $growth_current_month_key) {
            $growth_current_month_participants++;
            $growth_current_month_revenue += $payment_total;
        } elseif ($participant_month_key === $growth_previous_month_key) {
            $growth_previous_month_participants++;
            $growth_previous_month_revenue += $payment_total;
        }

        $business_revenue_total += $payment_total;
        $business_revenue_paid += $payment_paid;
        $business_revenue_open += $payment_open;

        if (isset($participant_status_counts[$participant_status])) {
            $participant_status_counts[$participant_status]++;
        } else {
            $participant_status_counts['other']++;
        }

        if ($participant_status === 'in_pruefung') {
            $participants_in_review++;
        }

        if (isset($trip_growth_stats[$trip_id])) {
            $trip_growth_stats[$trip_id]['participants']++;
            $trip_growth_stats[$trip_id]['revenue_total'] += $payment_total;
            $trip_growth_stats[$trip_id]['revenue_paid'] += $payment_paid;
            $trip_growth_stats[$trip_id]['revenue_open'] += $payment_open;
            if ($participant_status === 'in_pruefung') {
                $trip_growth_stats[$trip_id]['in_review']++;
            } elseif ($participant_status === 'neu') {
                $trip_growth_stats[$trip_id]['new_participants']++;
            } elseif ($participant_status === 'vollstaendig') {
                $trip_growth_stats[$trip_id]['complete_participants']++;
            }
        }

        $missing_details = [];
        if ($score !== 'complete') {
            $missing_details = alpenia_get_missing_docs_details($participant_id);
            $missing_docs_count++;
            $missing_docs_total_count += count($missing_details);
            foreach ($missing_details as $missing_detail) {
                if (!isset($missing_docs_by_type[$missing_detail])) {
                    $missing_docs_by_type[$missing_detail] = 0;
                }
                $missing_docs_by_type[$missing_detail]++;
            }
            if (isset($trip_growth_stats[$trip_id])) {
                $trip_growth_stats[$trip_id]['missing_docs']++;
            }
            if (count($missing_docs_items) < $overview_item_limit) {
                $missing_docs_items[] = [
                    'trip_id' => $trip_id,
                    'trip_title' => get_the_title($trip_id),
                    'participant_name' => alpenia_get_participant_full_name($participant_id),
                    'missing_docs' => $missing_details,
                    'participant_id' => $participant_id,
                ];
            }
        }

        if ($payment_open > 0) {
            $open_payments_count++;
            $open_payments_total_amount += $payment_open;
            if (isset($trip_growth_stats[$trip_id])) {
                $trip_growth_stats[$trip_id]['open_payments']++;
            }
            $payment_item = [
                'trip_id' => $trip_id,
                'trip_title' => get_the_title($trip_id),
                'participant_name' => alpenia_get_participant_full_name($participant_id),
                'payment_open' => $payment_open,
                'payment_status' => alpenia_get_payment_status($participant_id),
                'participant_id' => $participant_id,
            ];
            $priority_payment_items[] = $payment_item;
            if (count($open_payments_items) < $overview_item_limit) {
                $open_payments_items[] = $payment_item;
            }
        }

        $operation_reasons = [];
        if (!empty($missing_details)) {
            $operation_reasons[] = alpenia_travel_t('Unterlagen fehlen');
        }
        if ($payment_open > 0) {
            $operation_reasons[] = alpenia_travel_t('Zahlung offen');
        }
        if ($participant_status === 'in_pruefung') {
            $operation_reasons[] = alpenia_travel_t('In Prüfung');
        } elseif ($participant_status === 'neu') {
            $operation_reasons[] = alpenia_travel_t('Neu erfassen');
        }

        if (!empty($operation_reasons)) {
            $operation_priority = (count($missing_details) * 3) + ($payment_open > 0 ? 2 : 0) + ($participant_status === 'in_pruefung' ? 2 : 0);
            $operations_task_items[] = [
                'trip_id' => $trip_id,
                'trip_title' => get_the_title($trip_id),
                'participant_name' => alpenia_get_participant_full_name($participant_id),
                'participant_id' => $participant_id,
                'reasons' => $operation_reasons,
                'payment_open' => $payment_open,
                'missing_count' => count($missing_details),
                'participant_status' => $participant_status,
                'priority' => $operation_priority,
            ];
        }
    }

    foreach ($trip_growth_stats as $trip_id => $stats) {
        if ($stats['capacity'] > 0) {
            $trip_growth_stats[$trip_id]['occupancy'] = min(100, (int) round(($stats['participants'] / $stats['capacity']) * 100));
            $occupied_capacity += min($stats['participants'], $stats['capacity']);
        }
    }

    $average_occupancy = $total_capacity > 0 ? (int) round(($occupied_capacity / $total_capacity) * 100) : 0;
    $action_required_trips = array_values(array_filter($trip_growth_stats, function($stats) {
        $has_low_occupancy = $stats['capacity'] > 0 && $stats['occupancy'] < 50 && $stats['status'] === 'open';
        return $stats['missing_docs'] > 0 || $stats['open_payments'] > 0 || $stats['in_review'] > 0 || $has_low_occupancy;
    }));

    usort($action_required_trips, function($a, $b) {
        $score_a = ($a['open_payments'] * 3) + ($a['missing_docs'] * 2) + $a['in_review'];
        $score_b = ($b['open_payments'] * 3) + ($b['missing_docs'] * 2) + $b['in_review'];
        return $score_b <=> $score_a;
    });

    $top_revenue_trips = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['revenue_total'] > 0;
    }));
    usort($top_revenue_trips, function($a, $b) {
        return $b['revenue_total'] <=> $a['revenue_total'];
    });
    $top_revenue_trips = array_slice($top_revenue_trips, 0, 3);

    usort($priority_payment_items, function($a, $b) {
        return $b['payment_open'] <=> $a['payment_open'];
    });
    $priority_payment_items = array_slice($priority_payment_items, 0, 6);

    $finance_trip_summaries = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['revenue_total'] > 0 || $stats['revenue_open'] > 0;
    }));
    usort($finance_trip_summaries, function($a, $b) {
        return $b['revenue_open'] <=> $a['revenue_open'];
    });
    $finance_trip_summaries = array_slice($finance_trip_summaries, 0, 5);
    $payment_collection_rate = $business_revenue_total > 0 ? min(100, (int) round(($business_revenue_paid / $business_revenue_total) * 100)) : 0;
    $payment_open_rate = $business_revenue_total > 0 ? max(0, 100 - $payment_collection_rate) : 0;

    arsort($missing_docs_by_type);
    $operation_doc_bottlenecks = array_slice($missing_docs_by_type, 0, 6, true);

    $operation_trip_summaries = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['missing_docs'] > 0 || $stats['open_payments'] > 0 || $stats['in_review'] > 0 || $stats['new_participants'] > 0;
    }));
    usort($operation_trip_summaries, function($a, $b) {
        $score_a = ($a['missing_docs'] * 3) + ($a['open_payments'] * 2) + $a['in_review'] + $a['new_participants'];
        $score_b = ($b['missing_docs'] * 3) + ($b['open_payments'] * 2) + $b['in_review'] + $b['new_participants'];
        return $score_b <=> $score_a;
    });
    $operation_trip_summaries = array_slice($operation_trip_summaries, 0, 5);

    usort($operations_task_items, function($a, $b) {
        return $b['priority'] <=> $a['priority'];
    });
    $operations_task_items = array_slice($operations_task_items, 0, 6);
    $operations_open_work_count = $missing_docs_count + $open_payments_count + $participant_status_counts['neu'] + $participant_status_counts['in_pruefung'];

    $growth_type_breakdown = [];
    $growth_location_breakdown = [];
    $growth_guide_breakdown = [];
    $growth_focus_trips = [];

    foreach ($trip_growth_stats as $stats) {
        if ($stats['participants'] <= 0 && $stats['revenue_total'] <= 0) {
            continue;
        }

        $type_label = alpenia_display_value($stats['type']);
        if (!isset($growth_type_breakdown[$type_label])) {
            $growth_type_breakdown[$type_label] = ['label' => $type_label, 'participants' => 0, 'revenue' => 0.0, 'trips' => 0];
        }
        $growth_type_breakdown[$type_label]['participants'] += $stats['participants'];
        $growth_type_breakdown[$type_label]['revenue'] += $stats['revenue_total'];
        $growth_type_breakdown[$type_label]['trips']++;

        $location_label = trim(alpenia_display_value($stats['country']) . ' / ' . alpenia_display_value($stats['city']));
        if (!isset($growth_location_breakdown[$location_label])) {
            $growth_location_breakdown[$location_label] = ['label' => $location_label, 'participants' => 0, 'revenue' => 0.0];
        }
        $growth_location_breakdown[$location_label]['participants'] += $stats['participants'];
        $growth_location_breakdown[$location_label]['revenue'] += $stats['revenue_total'];

        $guide_label = $stats['guide_id'] > 0 ? get_the_author_meta('display_name', $stats['guide_id']) : alpenia_travel_t('Nicht zugewiesen');
        if (!isset($growth_guide_breakdown[$guide_label])) {
            $growth_guide_breakdown[$guide_label] = ['label' => $guide_label, 'participants' => 0, 'revenue' => 0.0, 'trips' => 0];
        }
        $growth_guide_breakdown[$guide_label]['participants'] += $stats['participants'];
        $growth_guide_breakdown[$guide_label]['revenue'] += $stats['revenue_total'];
        $growth_guide_breakdown[$guide_label]['trips']++;

        if ($stats['status'] === 'open' && $stats['capacity'] > 0 && $stats['occupancy'] < 65) {
            $growth_focus_trips[] = $stats;
        }
    }

    $growth_sort_by_participants = function($a, $b) {
        if ($b['participants'] === $a['participants']) {
            return $b['revenue'] <=> $a['revenue'];
        }
        return $b['participants'] <=> $a['participants'];
    };
    usort($growth_type_breakdown, $growth_sort_by_participants);
    usort($growth_location_breakdown, $growth_sort_by_participants);
    usort($growth_guide_breakdown, $growth_sort_by_participants);
    usort($growth_focus_trips, function($a, $b) {
        if ($a['occupancy'] === $b['occupancy']) {
            return $b['capacity'] <=> $a['capacity'];
        }
        return $a['occupancy'] <=> $b['occupancy'];
    });

    $growth_type_breakdown = array_slice($growth_type_breakdown, 0, 5);
    $growth_location_breakdown = array_slice($growth_location_breakdown, 0, 5);
    $growth_guide_breakdown = array_slice($growth_guide_breakdown, 0, 5);
    $growth_focus_trips = array_slice($growth_focus_trips, 0, 5);
    $growth_participant_delta = $growth_previous_month_participants > 0 ? (int) round((($growth_current_month_participants - $growth_previous_month_participants) / $growth_previous_month_participants) * 100) : ($growth_current_month_participants > 0 ? 100 : 0);
    $growth_revenue_delta = $growth_previous_month_revenue > 0 ? (int) round((($growth_current_month_revenue - $growth_previous_month_revenue) / $growth_previous_month_revenue) * 100) : ($growth_current_month_revenue > 0 ? 100 : 0);
    $growth_top_type_label = !empty($growth_type_breakdown) ? $growth_type_breakdown[0]['label'] : '-';
    $growth_top_location_label = !empty($growth_location_breakdown) ? $growth_location_breakdown[0]['label'] : '-';

    $average_occupancy = $total_capacity > 0 ? (int) round(($occupied_capacity / $total_capacity) * 100) : 0;
    $action_required_trips = array_values(array_filter($trip_growth_stats, function($stats) {
        $has_low_occupancy = $stats['capacity'] > 0 && $stats['occupancy'] < 50 && $stats['status'] === 'open';
        return $stats['missing_docs'] > 0 || $stats['open_payments'] > 0 || $stats['in_review'] > 0 || $has_low_occupancy;
    }));

    usort($action_required_trips, function($a, $b) {
        $score_a = ($a['open_payments'] * 3) + ($a['missing_docs'] * 2) + $a['in_review'];
        $score_b = ($b['open_payments'] * 3) + ($b['missing_docs'] * 2) + $b['in_review'];
        return $score_b <=> $score_a;
    });

    $top_revenue_trips = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['revenue_total'] > 0;
    }));
    usort($top_revenue_trips, function($a, $b) {
        return $b['revenue_total'] <=> $a['revenue_total'];
    });
    $top_revenue_trips = array_slice($top_revenue_trips, 0, 3);

    usort($priority_payment_items, function($a, $b) {
        return $b['payment_open'] <=> $a['payment_open'];
    });
    $priority_payment_items = array_slice($priority_payment_items, 0, 6);

    $finance_trip_summaries = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['revenue_total'] > 0 || $stats['revenue_open'] > 0;
    }));
    usort($finance_trip_summaries, function($a, $b) {
        return $b['revenue_open'] <=> $a['revenue_open'];
    });
    $finance_trip_summaries = array_slice($finance_trip_summaries, 0, 5);
    $payment_collection_rate = $business_revenue_total > 0 ? min(100, (int) round(($business_revenue_paid / $business_revenue_total) * 100)) : 0;
    $payment_open_rate = $business_revenue_total > 0 ? max(0, 100 - $payment_collection_rate) : 0;

    arsort($missing_docs_by_type);
    $operation_doc_bottlenecks = array_slice($missing_docs_by_type, 0, 6, true);

    $operation_trip_summaries = array_values(array_filter($trip_growth_stats, function($stats) {
        return $stats['missing_docs'] > 0 || $stats['open_payments'] > 0 || $stats['in_review'] > 0 || $stats['new_participants'] > 0;
    }));
    usort($operation_trip_summaries, function($a, $b) {
        $score_a = ($a['missing_docs'] * 3) + ($a['open_payments'] * 2) + $a['in_review'] + $a['new_participants'];
        $score_b = ($b['missing_docs'] * 3) + ($b['open_payments'] * 2) + $b['in_review'] + $b['new_participants'];
        return $score_b <=> $score_a;
    });
    $operation_trip_summaries = array_slice($operation_trip_summaries, 0, 5);

    usort($operations_task_items, function($a, $b) {
        return $b['priority'] <=> $a['priority'];
    });
    $operations_task_items = array_slice($operations_task_items, 0, 6);
    $operations_open_work_count = $missing_docs_count + $open_payments_count + $participant_status_counts['neu'] + $participant_status_counts['in_pruefung'];

    $latest_participants = array_slice($participants, 0, 5);

    ob_start();
    ?>
    <div class="alpenia-dashboard-shell">
        <div class="alpenia-dashboard">

            <?php if ($debug_mode) : ?>
                <div class="panel" style="margin-bottom:16px;">
                    <strong>Auth Debug</strong><br>
                    Logged in: <?php echo is_user_logged_in() ? 'yes' : 'no'; ?><br>
                    Current user id: <?php echo (int) get_current_user_id(); ?><br>
                    Cookie user id: <?php echo (int) wp_validate_auth_cookie('', 'logged_in'); ?><br>
                    Host: <?php echo esc_html(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''))); ?><br>
                    URI: <?php echo esc_html(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? ''))); ?>
                </div>
            <?php endif; ?>

            <?php echo $message; ?>
            <script>
                (function () {
                    const deleteMessage = document.querySelector('[data-alpenia-delete-popup], [data-alpenia-feedback-popup]');
                    if (!deleteMessage) {
                        return;
                    }

                    const popupType = deleteMessage.getAttribute('data-alpenia-delete-popup') || deleteMessage.getAttribute('data-alpenia-feedback-popup');
                    const titleMap = {
                        trip: <?php echo wp_json_encode(alpenia_travel_t('Reise gelöscht')); ?>,
                        participant: <?php echo wp_json_encode(alpenia_travel_t('Teilnehmer gelöscht')); ?>,
                        trip_created: <?php echo wp_json_encode(alpenia_travel_t('Reise erstellt')); ?>,
                        participant_created: <?php echo wp_json_encode(alpenia_travel_t('Teilnehmer gespeichert')); ?>,
                        trip_updated: <?php echo wp_json_encode(alpenia_travel_t('Reise aktualisiert')); ?>,
                        participant_updated: <?php echo wp_json_encode(alpenia_travel_t('Teilnehmer aktualisiert')); ?>,
                        item_restored: <?php echo wp_json_encode(alpenia_travel_t('Eintrag wiederhergestellt')); ?>,
                        item_deleted: <?php echo wp_json_encode(alpenia_travel_t('Eintrag dauerhaft gelöscht')); ?>,
                        user_created: <?php echo wp_json_encode(alpenia_travel_t('Benutzer erstellt')); ?>,
                        user_deleted: <?php echo wp_json_encode(alpenia_travel_t('Benutzer gelöscht')); ?>,
                        user_updated: <?php echo wp_json_encode(alpenia_travel_t('Benutzer aktualisiert')); ?>,
                        user_deactivated: <?php echo wp_json_encode(alpenia_travel_t('Benutzer deaktiviert')); ?>,
                        user_activated: <?php echo wp_json_encode(alpenia_travel_t('Benutzer aktiviert')); ?>,
                        user_email_exists: <?php echo wp_json_encode(alpenia_travel_t('E-Mail bereits vorhanden')); ?>,
                        user_email_in_use: <?php echo wp_json_encode(alpenia_travel_t('E-Mail bereits verwendet')); ?>
                    };

                    const overlay = document.createElement('div');
                    overlay.className = 'alpenia-delete-popup-overlay';
                    const dialog = document.createElement('div');
                    dialog.className = 'alpenia-delete-popup-dialog';
                    dialog.setAttribute('role', 'alertdialog');
                    dialog.setAttribute('aria-modal', 'true');

                    const badge = document.createElement('span');
                    badge.className = 'alpenia-delete-popup-badge';
                    badge.textContent = '✓';

                    const title = document.createElement('h3');
                    title.className = 'alpenia-delete-popup-title';
                    title.textContent = titleMap[popupType] || <?php echo wp_json_encode(alpenia_travel_t('Erfolgreich')); ?>;

                    const text = document.createElement('p');
                    text.className = 'alpenia-delete-popup-text';
                    text.textContent = deleteMessage.textContent.trim();

                    const closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'btn-primary';
                    closeButton.textContent = <?php echo wp_json_encode(alpenia_travel_t('Schließen')); ?>;

                    const shouldRedirectHome = ['trip_created', 'participant_created', 'trip_updated', 'participant_updated'].includes(popupType);
                    const closePopup = function () {
                        overlay.remove();
                        if (shouldRedirectHome) {
                            window.location.href = <?php echo wp_json_encode(alpenia_dashboard_link()); ?>;
                        }
                    };
                    closeButton.addEventListener('click', closePopup);

                    overlay.addEventListener('click', function (event) {
                        if (event.target === overlay) {
                            closePopup();
                        }
                    });

                    document.addEventListener('keydown', function onEsc(event) {
                        if (event.key === 'Escape') {
                            closePopup();
                            document.removeEventListener('keydown', onEsc);
                        }
                    });

                    dialog.appendChild(badge);
                    dialog.appendChild(title);
                    dialog.appendChild(text);
                    dialog.appendChild(closeButton);
                    overlay.appendChild(dialog);
                    document.body.appendChild(overlay);
                    deleteMessage.style.display = 'none';
                }());
            </script>
            <script>
                (function () {
                    const labels = {
                        title: <?php echo wp_json_encode(alpenia_travel_t('Löschoption wählen')); ?>,
                        description: <?php echo wp_json_encode(alpenia_travel_t('Möchtest du zuerst in den Papierkorb verschieben oder dauerhaft löschen?')); ?>,
                        trash: <?php echo wp_json_encode(alpenia_travel_t('In den Papierkorb verschieben')); ?>,
                        hard: <?php echo wp_json_encode(alpenia_travel_t('Dauerhaft löschen')); ?>,
                        cancel: <?php echo wp_json_encode(alpenia_travel_t('Abbrechen')); ?>
                    };

                    function openDeleteChooser(targetUrl) {
                        const overlay = document.createElement('div');
                        overlay.className = 'alpenia-delete-popup-overlay';
                        const dialog = document.createElement('div');
                        dialog.className = 'alpenia-delete-popup-dialog';

                        const title = document.createElement('h3');
                        title.className = 'alpenia-delete-popup-title';
                        title.textContent = labels.title;

                        const text = document.createElement('p');
                        text.className = 'alpenia-delete-popup-text';
                        text.textContent = labels.description;

                        const actions = document.createElement('div');
                        actions.className = 'alpenia-delete-popup-actions';

                        const trashBtn = document.createElement('button');
                        trashBtn.type = 'button';
                        trashBtn.className = 'btn-primary';
                        trashBtn.textContent = labels.trash;

                        const hardBtn = document.createElement('button');
                        hardBtn.type = 'button';
                        hardBtn.className = 'btn-secondary table-btn-danger';
                        hardBtn.textContent = labels.hard;

                        const cancelBtn = document.createElement('button');
                        cancelBtn.type = 'button';
                        cancelBtn.className = 'btn-secondary';
                        cancelBtn.textContent = labels.cancel;

                        const close = () => overlay.remove();
                        cancelBtn.addEventListener('click', close);
                        overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });

                        trashBtn.addEventListener('click', function () {
                            window.location.href = targetUrl + (targetUrl.includes('?') ? '&' : '?') + 'delete_mode=trash';
                        });
                        hardBtn.addEventListener('click', function () {
                            window.location.href = targetUrl + (targetUrl.includes('?') ? '&' : '?') + 'delete_mode=hard';
                        });

                        actions.appendChild(trashBtn);
                        actions.appendChild(hardBtn);
                        actions.appendChild(cancelBtn);
                        dialog.appendChild(title);
                        dialog.appendChild(text);
                        dialog.appendChild(actions);
                        overlay.appendChild(dialog);
                        document.body.appendChild(overlay);
                    }

                    function openTrashActionConfirm(targetUrl, actionType) {
                        const popupLabels = {
                            restore: {
                                title: <?php echo wp_json_encode(alpenia_travel_t('Wiederherstellen')); ?>,
                                description: <?php echo wp_json_encode(alpenia_travel_t('Soll dieser Eintrag wirklich wiederhergestellt werden?')); ?>,
                                confirm: <?php echo wp_json_encode(alpenia_travel_t('Wiederherstellen')); ?>
                            },
                            hardDelete: {
                                title: <?php echo wp_json_encode(alpenia_travel_t('Dauerhaft löschen')); ?>,
                                description: <?php echo wp_json_encode(alpenia_travel_t('Soll dieser Eintrag wirklich dauerhaft gelöscht werden?')); ?>,
                                confirm: <?php echo wp_json_encode(alpenia_travel_t('Dauerhaft löschen')); ?>
                            }
                        };

                        const selected = popupLabels[actionType];
                        if (!selected) {
                            window.location.href = targetUrl;
                            return;
                        }

                        const overlay = document.createElement('div');
                        overlay.className = 'alpenia-delete-popup-overlay';
                        const dialog = document.createElement('div');
                        dialog.className = 'alpenia-delete-popup-dialog';

                        const title = document.createElement('h3');
                        title.className = 'alpenia-delete-popup-title';
                        title.textContent = selected.title;

                        const text = document.createElement('p');
                        text.className = 'alpenia-delete-popup-text';
                        text.textContent = selected.description;

                        const actions = document.createElement('div');
                        actions.className = 'alpenia-delete-popup-actions';

                        const confirmBtn = document.createElement('button');
                        confirmBtn.type = 'button';
                        confirmBtn.className = actionType === 'hardDelete' ? 'btn-secondary table-btn-danger' : 'btn-primary';
                        confirmBtn.textContent = selected.confirm;

                        const cancelBtn = document.createElement('button');
                        cancelBtn.type = 'button';
                        cancelBtn.className = 'btn-secondary';
                        cancelBtn.textContent = labels.cancel;

                        const close = () => overlay.remove();
                        cancelBtn.addEventListener('click', close);
                        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

                        confirmBtn.addEventListener('click', function () {
                            window.location.href = targetUrl;
                        });

                        actions.appendChild(confirmBtn);
                        actions.appendChild(cancelBtn);
                        dialog.appendChild(title);
                        dialog.appendChild(text);
                        dialog.appendChild(actions);
                        overlay.appendChild(dialog);
                        document.body.appendChild(overlay);
                    }

                    document.addEventListener('click', function (event) {
                        const deleteLink = event.target.closest('a[data-delete-type]');
                        if (deleteLink) {
                            event.preventDefault();
                            openDeleteChooser(deleteLink.href);
                            return;
                        }

                        const trashLink = event.target.closest('a[data-trash-action]');
                        if (!trashLink) {
                            return;
                        }

                        event.preventDefault();
                        const action = trashLink.getAttribute('data-trash-action') === 'hard-delete' ? 'hardDelete' : 'restore';
                        openTrashActionConfirm(trashLink.href, action);
                    });
                }());
            </script>
            <form id="alpenia-idle-logout-form" method="post" style="display:none;">
                <?php wp_nonce_field('alpenia_logout_action', 'alpenia_logout_nonce'); ?>
                <input type="hidden" name="alpenia_logout" value="1">
                <input type="hidden" name="alpenia_logout_intent" value="dashboard_logout">
            </form>

            <div id="alpenia-idle-timeout-modal" class="alpenia-idle-timeout-modal" aria-hidden="true">
                <div class="alpenia-idle-timeout-card" role="dialog" aria-modal="true" aria-labelledby="alpenia-idle-timeout-title">
                    <h2 id="alpenia-idle-timeout-title">Hâlâ burada mısın?</h2>
                    <p>15 dakikadır işlem yapılmadı. “Devam et” butonuna tıklamazsan otomatik olarak çıkış yapılacak.</p>
                    <p class="alpenia-idle-timeout-countdown">Otomatik çıkışa kalan süre: <span id="alpenia-idle-timeout-seconds">60</span> saniye.</p>
                    <div class="alpenia-idle-timeout-actions">
                        <button type="button" id="alpenia-idle-stay-btn" class="btn-secondary">Devam et</button>
                        <button type="button" id="alpenia-idle-logout-btn" class="btn-primary btn-logout">Şimdi çıkış yap</button>
                    </div>
                </div>
            </div>

            <div class="alpenia-dashboard-layout">
                <?php echo alpenia_dashboard_sidebar_nav(); ?>
                <main class="alpenia-dashboard-content">

            <?php if (($create_trip_requested && alpenia_user_can_create_trip()) || ($edit_trip_requested && alpenia_user_can_edit_trip($edit_trip_id))) : ?>

                <?php
                $trip_form_is_edit = $edit_trip_requested && alpenia_user_can_edit_trip($edit_trip_id);
                $trip_form_id = $trip_form_is_edit ? $edit_trip_id : 0;
                $trip_form_post = $trip_form_is_edit ? get_post($trip_form_id) : null;
                $trip_form_values = [
                    'trip_title' => $trip_form_post ? $trip_form_post->post_title : '',
                    'trip_type' => $trip_form_id ? get_post_meta($trip_form_id, 'trip_type', true) : '',
                    'trip_status' => $trip_form_id ? get_post_meta($trip_form_id, 'trip_status', true) : 'open',
                    'destination' => $trip_form_id ? get_post_meta($trip_form_id, 'destination', true) : '',
                    'country' => $trip_form_id ? get_post_meta($trip_form_id, 'country', true) : '',
                    'city' => $trip_form_id ? get_post_meta($trip_form_id, 'city', true) : '',
                    'departure_city' => $trip_form_id ? get_post_meta($trip_form_id, 'departure_city', true) : '',
                    'departure_airport' => $trip_form_id ? get_post_meta($trip_form_id, 'departure_airport', true) : '',
                    'start_date' => $trip_form_id ? get_post_meta($trip_form_id, 'start_date', true) : '',
                    'end_date' => $trip_form_id ? get_post_meta($trip_form_id, 'end_date', true) : '',
                    'max_people' => $trip_form_id ? get_post_meta($trip_form_id, 'max_people', true) : '',
                    'price' => $trip_form_id ? get_post_meta($trip_form_id, 'price', true) : '',
                    'assigned_guide' => $trip_form_id ? (int) get_post_meta($trip_form_id, 'assigned_guide', true) : 0,
                    'whatsapp_link' => $trip_form_id ? get_post_meta($trip_form_id, 'whatsapp_link', true) : '',
                    'zoom_link' => $trip_form_id ? get_post_meta($trip_form_id, 'zoom_link', true) : '',
                    'internal_notes' => $trip_form_id ? alpenia_get_secure_meta($trip_form_id, 'internal_notes', true) : '',
                ];
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html($trip_form_is_edit ? alpenia_travel_t('Reise bearbeiten') : alpenia_travel_t("Neue Reise erstellen")); ?></h1>
                            <p><?php echo esc_html($trip_form_is_edit ? alpenia_travel_t('Aktualisiere hier die Reisedaten.') : alpenia_travel_t('Erstelle hier eine neue Kultur- oder Pilgerreise.')); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t("Zurück zum Dashboard")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" class="alpenia-form">
                        <?php wp_nonce_field($trip_form_is_edit ? 'alpenia_edit_trip_' . $trip_form_id : 'alpenia_save_trip', 'alpenia_trip_nonce'); ?>
                        <?php if ($trip_form_is_edit) : ?>
                            <input type="hidden" name="trip_id" value="<?php echo esc_attr($trip_form_id); ?>">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="trip_title"><?php echo esc_html(alpenia_travel_t('Reisetitel')); ?></label>
                                <input type="text" id="trip_title" name="trip_title" value="<?php echo esc_attr($trip_form_values['trip_title']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. Frankfurt – Umrah')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="trip_type"><?php echo esc_html(alpenia_travel_t('Reisetyp')); ?></label>
                                <select id="trip_type" name="trip_type" required>
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <option value="kultur" <?php selected($trip_form_values['trip_type'], 'kultur'); ?>><?php echo esc_html(alpenia_travel_t('Kulturreise')); ?></option>
                                    <option value="umrah" <?php selected($trip_form_values['trip_type'], 'umrah'); ?>><?php echo esc_html(alpenia_travel_t('Umrah')); ?></option>
                                    <option value="hajj" <?php selected($trip_form_values['trip_type'], 'hajj'); ?>><?php echo esc_html(alpenia_travel_t('Hadsch')); ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trip_status"><?php echo esc_html(alpenia_travel_t('Reisestatus')); ?></label>
                                <select id="trip_status" name="trip_status" required>
                                    <option value="draft" <?php selected($trip_form_values['trip_status'], 'draft'); ?>><?php echo esc_html(alpenia_travel_t('Entwurf')); ?></option>
                                    <option value="open" <?php selected($trip_form_values['trip_status'], 'open'); ?>><?php echo esc_html(alpenia_travel_t("Offen")); ?></option>
                                    <option value="full" <?php selected($trip_form_values['trip_status'], 'full'); ?>><?php echo esc_html(alpenia_travel_t('Voll')); ?></option>
                                    <option value="closed" <?php selected($trip_form_values['trip_status'], 'closed'); ?>><?php echo esc_html(alpenia_travel_t('Abgeschlossen')); ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="destination"><?php echo esc_html(alpenia_travel_t('Reiseziel')); ?></label>
                                <input type="text" id="destination" name="destination" value="<?php echo esc_attr($trip_form_values['destination']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. Mekka & Medina')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="country"><?php echo esc_html(alpenia_travel_t('Reiseveranstalter Land')); ?></label>
                                <select id="country" name="country" required>
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <?php foreach (alpenia_get_all_countries() as $country_name) : ?>
                                        <option value="<?php echo esc_attr($country_name); ?>" <?php selected($trip_form_values['country'], $country_name); ?>><?php echo esc_html($country_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="city"><?php echo esc_html(alpenia_travel_t('Reiseveranstalter Stadt')); ?></label>
                                <input type="text" id="city" name="city" value="<?php echo esc_attr($trip_form_values['city']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. Frankfurt')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="departure_city"><?php echo esc_html(alpenia_travel_t('Abflugstadt')); ?></label>
                                <input type="text" id="departure_city" name="departure_city" value="<?php echo esc_attr($trip_form_values['departure_city']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. Frankfurt')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="departure_airport"><?php echo esc_html(alpenia_travel_t('Flughafen (IATA-Code)')); ?></label>
                                <select id="departure_airport" name="departure_airport" data-selected="<?php echo esc_attr($trip_form_values['departure_airport']); ?>">
                                    <option value=""><?php echo esc_html(alpenia_travel_t('Lade Flughafenliste …')); ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="start_date"><?php echo esc_html(alpenia_travel_t('Startdatum')); ?></label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($trip_form_values['start_date']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="end_date"><?php echo esc_html(alpenia_travel_t('Enddatum')); ?></label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($trip_form_values['end_date']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="max_people"><?php echo esc_html(alpenia_travel_t('Max. Teilnehmer')); ?></label>
                                <input type="number" id="max_people" name="max_people" min="1" value="<?php echo esc_attr($trip_form_values['max_people']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. 40')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="price"><?php echo esc_html(alpenia_travel_t('Standardpreis (€)')); ?></label>
                                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo esc_attr($trip_form_values['price']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('z. B. 1499')); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="assigned_guide"><?php echo esc_html(alpenia_travel_t('Reiseleiter')); ?></label>
                                <select id="assigned_guide" name="assigned_guide">
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <?php foreach ($guides as $guide) : ?>
                                        <option value="<?php echo esc_attr($guide->ID); ?>" <?php selected($trip_form_values['assigned_guide'], $guide->ID); ?>><?php echo esc_html($guide->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full">
                                <label for="whatsapp_link"><?php echo esc_html(alpenia_travel_t('WhatsApp Gruppenlink')); ?></label>
                                <input type="url" id="whatsapp_link" name="whatsapp_link" value="<?php echo esc_attr($trip_form_values['whatsapp_link']); ?>" placeholder="https://chat.whatsapp.com/...">
                            </div>

                            <div class="form-group full">
                                <label for="zoom_link"><?php echo esc_html(alpenia_travel_t('Zoom Meeting Link')); ?></label>
                                <input type="url" id="zoom_link" name="zoom_link" value="<?php echo esc_attr($trip_form_values['zoom_link']); ?>" placeholder="https://zoom.us/j/...">
                            </div>

                            <div class="form-group full">
                                <label for="internal_notes"><?php echo esc_html(alpenia_travel_t('Interne Notizen')); ?></label>
                                <input type="text" id="internal_notes" name="internal_notes" value="<?php echo esc_attr($trip_form_values['internal_notes']); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('Interne Hinweise zur Reise')); ?>">
                            </div>
                        </div>

                        <button type="submit" name="save_trip" class="btn-primary"><?php echo esc_html($trip_form_is_edit ? alpenia_travel_t('Änderungen speichern') : alpenia_travel_t("Reise speichern")); ?></button>
                    </form>
                </div>

            <?php elseif (isset($_GET['add_participant']) && $_GET['add_participant'] == '1' && !isset($_POST['generate_participant_fields'])) : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t("Teilnehmer hinzufügen")); ?></h1>
                            <p><?php echo esc_html(alpenia_travel_t("Wähle die Reise und gib an, wie viele Teilnehmer du erfassen willst.")); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t("Zurück zum Dashboard")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" class="alpenia-form">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="trip_id"><?php echo esc_html(alpenia_travel_t("Reise auswählen")); ?></label>
                                <select id="trip_id" name="trip_id" required>
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte Reise wählen")); ?></option>
                                    <?php foreach ($filtered_trips as $trip) : ?>
                                        <option value="<?php echo esc_attr($trip->ID); ?>" data-capacity-left="<?php echo esc_attr(alpenia_get_trip_capacity_left($trip->ID)); ?>">
                                            <?php echo esc_html($trip->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="participant_count"><?php echo esc_html(alpenia_travel_t("Anzahl Teilnehmer")); ?></label>
                                <input type="number" id="participant_count" name="participant_count" min="1" value="1" required>
                                <small id="participant_count_hint" style="display:block;margin-top:6px;opacity:.8;"><?php echo esc_html(alpenia_travel_t('Bitte zuerst Reise wählen.')); ?></small>
                            </div>
                        </div>

                        <button type="submit" name="generate_participant_fields" class="btn-primary"><?php echo esc_html(alpenia_travel_t("Weiter")); ?></button>
                    </form>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var tripSelect = document.getElementById('trip_id');
                    var participantInput = document.getElementById('participant_count');
                    var hint = document.getElementById('participant_count_hint');
                    if (!tripSelect || !participantInput) return;

                    function updateParticipantLimit() {
                        var selectedOption = tripSelect.options[tripSelect.selectedIndex];
                        if (!selectedOption || !selectedOption.value) {
                            participantInput.removeAttribute('max');
                            participantInput.value = '1';
                            if (hint) hint.textContent = '<?php echo esc_js(alpenia_travel_t('Bitte zuerst Reise wählen.')); ?>';
                            return;
                        }
                        var capacityLeft = parseInt(selectedOption.getAttribute('data-capacity-left') || '0', 10);
                        if (!Number.isFinite(capacityLeft)) capacityLeft = 0;

                        if (capacityLeft <= 0) {
                            participantInput.value = '0';
                            participantInput.setAttribute('max', '0');
                            if (hint) hint.textContent = '<?php echo esc_js(alpenia_travel_t('Diese Reise ist voll. Es sind keine freien Plätze mehr verfügbar.')); ?>';
                            return;
                        }

                        participantInput.setAttribute('max', String(capacityLeft));
                        if (parseInt(participantInput.value || '1', 10) > capacityLeft) {
                            participantInput.value = String(capacityLeft);
                        } else if (parseInt(participantInput.value || '0', 10) < 1) {
                            participantInput.value = '1';
                        }
                        if (hint) hint.textContent = '<?php echo esc_js(alpenia_travel_t('Freie Plätze:')); ?> ' + capacityLeft;
                    }

                    tripSelect.addEventListener('change', updateParticipantLimit);
                    updateParticipantLimit();
                });
                </script>

            <?php elseif (isset($_GET['add_participant']) && $_GET['add_participant'] == '1' && isset($_POST['generate_participant_fields'])) : ?>

                <?php
                $selected_trip_id = (int) ($_POST['trip_id'] ?? 0);
                $participant_count = max(1, (int) ($_POST['participant_count'] ?? 1));

                if (!$selected_trip_id || !alpenia_user_can_access_trip($selected_trip_id)) {
                    return '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff auf diese Reise.')) . '</div>';
                }

                $all_countries_list = alpenia_get_all_countries();
                $selected_is_pilgrimage_trip = alpenia_is_pilgrimage_trip($selected_trip_id);
                $selected_trip_price = (float) get_post_meta($selected_trip_id, 'price', true);
                $selected_trip_capacity = (int) get_post_meta($selected_trip_id, 'max_people', true);
                $selected_trip_capacity_left = alpenia_get_trip_capacity_left($selected_trip_id);

                if ($selected_trip_capacity > 0 && $selected_trip_capacity_left < 1) {
                    return '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Diese Reise ist voll. Es sind keine freien Plätze mehr verfügbar.')) . '</div>';
                }

                if ($selected_trip_capacity > 0 && $participant_count > $selected_trip_capacity_left) {
                    return '<div class="alpenia-message">' . esc_html(sprintf(alpenia_travel_t('Es sind nur noch %d freie Plätze verfügbar.'), (int) $selected_trip_capacity_left)) . '</div>';
                }

                $max_form_participants = 25;
                if ($participant_count > $max_form_participants) {
                    return '<div class="alpenia-message">' . esc_html(sprintf(alpenia_travel_t('Aus Leistungsgründen können maximal %d Teilnehmer gleichzeitig erfasst werden.'), (int) $max_form_participants)) . '</div>';
                }
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t("Teilnehmerdaten erfassen")); ?></h1>
                            <p><?php echo esc_html(alpenia_travel_t("Bitte alle Pflichtfelder pro Person ausfüllen.")); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['add_participant' => 1])); ?>"><?php echo esc_html(alpenia_travel_t("Zurück")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" enctype="multipart/form-data" class="alpenia-form" data-pilgrimage-trip="<?php echo $selected_is_pilgrimage_trip ? '1' : '0'; ?>">
                        <?php wp_nonce_field('alpenia_save_participants_batch', 'alpenia_participant_batch_nonce'); ?>
                        <input type="hidden" name="trip_id" value="<?php echo esc_attr($selected_trip_id); ?>">
                        <input type="hidden" name="participant_count" value="<?php echo esc_attr($participant_count); ?>">

                        <div class="participant-wizard" data-participant-wizard>
                            <div class="participant-wizard__top">
                                <div>
                                    <p class="participant-wizard__eyebrow"><?php echo esc_html(alpenia_travel_t('Schrittweise Erfassung')); ?></p>
                                    <h2><?php echo esc_html(alpenia_travel_t('Teilnehmer einzeln bearbeiten')); ?></h2>
                                    <p><?php echo esc_html(alpenia_travel_t('Damit nichts durcheinandergerät, wird immer nur eine Person geöffnet. Die Übersicht zeigt dir, bei welchem Teilnehmer du gerade bist.')); ?></p>
                                </div>
                                <div class="participant-wizard__count">
                                    <span data-wizard-current>1</span> / <?php echo esc_html($participant_count); ?>
                                </div>
                            </div>

                            <div class="participant-wizard__tabs" role="tablist" aria-label="<?php echo esc_attr(alpenia_travel_t('Teilnehmer auswählen')); ?>">
                                <?php for ($tab_i = 1; $tab_i <= $participant_count; $tab_i++) : ?>
                                    <button type="button" class="participant-wizard__tab<?php echo $tab_i === 1 ? ' is-active' : ''; ?>" data-wizard-tab="<?php echo esc_attr($tab_i); ?>" role="tab" aria-selected="<?php echo $tab_i === 1 ? 'true' : 'false'; ?>" aria-controls="participant_step_<?php echo esc_attr($tab_i); ?>">
                                        <span><?php echo esc_html(alpenia_travel_t('Teilnehmer')); ?></span>
                                        <strong><?php echo esc_html($tab_i); ?></strong>
                                    </button>
                                <?php endfor; ?>
                            </div>

                        <?php for ($i = 1; $i <= $participant_count; $i++) : ?>
                            <div class="participant-box<?php echo $i === 1 ? ' is-active' : ''; ?>" id="participant_step_<?php echo esc_attr($i); ?>" data-participant-step="<?php echo esc_attr($i); ?>"<?php echo $i === 1 ? '' : ' hidden'; ?>>
                                <div class="participant-box__header">
                                    <div>
                                        <span class="participant-box__kicker"><?php echo esc_html(sprintf(alpenia_travel_t('Person %1$d von %2$d'), $i, $participant_count)); ?></span>
                                        <h3><?php echo esc_html(alpenia_travel_t('Teilnehmer')); ?> <?php echo $i; ?></h3>
                                    </div>
                                    <p><?php echo esc_html(alpenia_travel_t('Bitte zuerst diese Person fertig ausfüllen, dann zur nächsten Person wechseln.')); ?></p>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="gender_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Anrede')); ?> <span class="required-mark">*</span></label>
                                        <select id="gender_<?php echo $i; ?>" name="gender_<?php echo $i; ?>" required>
                                            <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                            <option value="Herr"><?php echo esc_html(alpenia_travel_t('Herr')); ?></option>
                                            <option value="Frau"><?php echo esc_html(alpenia_travel_t('Frau')); ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="first_name_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Vorname')); ?> <span class="required-mark">*</span></label>
                                        <input type="text" id="first_name_<?php echo $i; ?>" name="first_name_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="second_first_name_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('2. Vorname')); ?></label>
                                        <input type="text" id="second_first_name_<?php echo $i; ?>" name="second_first_name_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="last_name_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Nachname')); ?> <span class="required-mark">*</span></label>
                                        <input type="text" id="last_name_<?php echo $i; ?>" name="last_name_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="birth_date_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Geburtsdatum')); ?></label>
                                        <input type="date" id="birth_date_<?php echo $i; ?>" name="birth_date_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full">
                                        <strong><?php echo esc_html(alpenia_travel_t('Adresse & Kontakt')); ?></strong>
                                    </div>

                                    <div class="form-group">
                                        <label for="street_address_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Straße')); ?></label>
                                        <input type="text" id="street_address_<?php echo $i; ?>" name="street_address_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="postal_city_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Postleitzahl / Stadt')); ?></label>
                                        <input type="text" id="postal_city_<?php echo $i; ?>" name="postal_city_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="phone_number_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Telefonnummer')); ?></label>
                                        <input type="text" id="phone_number_<?php echo $i; ?>" name="phone_number_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email_address_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('E-Mail Adresse')); ?></label>
                                        <input type="email" id="email_address_<?php echo $i; ?>" name="email_address_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="emergency_contact_name_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Notfallkontakt Name')); ?></label>
                                        <input type="text" id="emergency_contact_name_<?php echo $i; ?>" name="emergency_contact_name_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="emergency_contact_phone_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Notfallkontakt Telefonnummer')); ?></label>
                                        <input type="text" id="emergency_contact_phone_<?php echo $i; ?>" name="emergency_contact_phone_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group full">
                                        <strong><?php echo esc_html(alpenia_travel_t('Reisepassdaten')); ?></strong>
                                    </div>

                                    <div class="form-group">
                                        <label for="nationality_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Staatsbürgerschaft')); ?> <span class="required-mark">*</span></label>
                                        <select id="nationality_<?php echo $i; ?>" name="nationality_<?php echo $i; ?>" required>
                                            <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                            <?php foreach ($all_countries_list as $country_name) : ?>
                                                <option value="<?php echo esc_attr($country_name); ?>"><?php echo esc_html($country_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_no_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Reisepassnummer')); ?></label>
                                        <input type="text" id="passport_no_<?php echo $i; ?>" name="passport_no_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_valid_from_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Reisepass gültig von')); ?> <span class="required-mark">*</span></label>
                                        <input type="date" id="passport_valid_from_<?php echo $i; ?>" name="passport_valid_from_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_expiry_date_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Reisepass gültig bis')); ?> <span class="required-mark">*</span></label>
                                        <input type="date" id="passport_expiry_date_<?php echo $i; ?>" name="passport_expiry_date_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group full residence-field residence-field-<?php echo $i; ?>">
                                        <strong><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel')); ?></strong>
                                    </div>

                                    <div class="form-group residence-field residence-field-<?php echo $i; ?>">
                                        <label for="residence_permit_start_date_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel gültig von')); ?></label>
                                        <input type="date" id="residence_permit_start_date_<?php echo $i; ?>" name="residence_permit_start_date_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group residence-field residence-field-<?php echo $i; ?>">
                                        <label for="residence_permit_number_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel Nummer')); ?></label>
                                        <input type="text" id="residence_permit_number_<?php echo $i; ?>" name="residence_permit_number_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group residence-field residence-field-<?php echo $i; ?>">
                                        <label for="residence_permit_valid_until_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel gültig bis')); ?></label>
                                        <input type="date" id="residence_permit_valid_until_<?php echo $i; ?>" name="residence_permit_valid_until_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full pilgrimage-visa-field pilgrimage-visa-field-<?php echo $i; ?>">
                                        <strong><?php echo esc_html(alpenia_travel_t('Visum')); ?></strong>
                                    </div>

                                    <div class="form-group pilgrimage-visa-field pilgrimage-visa-field-<?php echo $i; ?>">
                                        <label for="visa_entry_country_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Visum-Einreiseland')); ?></label>
                                        <select id="visa_entry_country_<?php echo $i; ?>" name="visa_entry_country_<?php echo $i; ?>">
                                            <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                            <?php foreach ($all_countries_list as $country_name) : ?>
                                                <option value="<?php echo esc_attr($country_name); ?>"><?php echo esc_html($country_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group pilgrimage-visa-field pilgrimage-visa-field-<?php echo $i; ?>">
                                        <label for="visa_number_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Visum Nummer')); ?></label>
                                        <input type="text" id="visa_number_<?php echo $i; ?>" name="visa_number_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group pilgrimage-visa-field pilgrimage-visa-field-<?php echo $i; ?>">
                                        <label for="visa_expiry_date_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Visum gültig bis')); ?></label>
                                        <input type="date" id="visa_expiry_date_<?php echo $i; ?>" name="visa_expiry_date_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full">
                                        <strong><?php echo esc_html(alpenia_travel_t('Status & Zahlung')); ?></strong>
                                    </div>

                                    <div class="form-group">
                                        <label for="participant_status_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Bearbeitungsstatus')); ?></label>
                                        <select id="participant_status_<?php echo $i; ?>" name="participant_status_<?php echo $i; ?>">
                                            <option value="neu"><?php echo esc_html(alpenia_travel_t('Neu')); ?></option>
                                            <option value="in_pruefung"><?php echo esc_html(alpenia_travel_t('In Prüfung')); ?></option>
                                            <option value="vollstaendig"><?php echo esc_html(alpenia_travel_t('Vollständig')); ?></option>
                                        </select>
                                    </div>


                                    <div class="form-group">
                                        <label for="payment_total_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Gesamtpreis (€)')); ?></label>
                                        <input type="number" step="0.01" min="0" id="payment_total_<?php echo $i; ?>" name="payment_total_<?php echo $i; ?>" value="<?php echo esc_attr(number_format((float) $selected_trip_price, 2, '.', '')); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="payment_deposit_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Anzahlung (€)')); ?></label>
                                        <input type="number" step="0.01" min="0" id="payment_deposit_<?php echo $i; ?>" name="payment_deposit_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full">
                                        <label for="payment_paid_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Bereits bezahlt (€)')); ?></label>
                                        <input type="number" step="0.01" min="0" id="payment_paid_<?php echo $i; ?>" name="payment_paid_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full">
                                        <strong><?php echo esc_html(alpenia_travel_t('Dokumente & Checkliste')); ?></strong>
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_file_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Reisepass hochladen')); ?> <span class="required-mark">*</span> <small>(max. 5 MB)</small></label>
                                        <input type="file" id="passport_file_<?php echo $i; ?>" name="passport_file_<?php echo $i; ?>" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('passport_file')); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="photo_file_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Porträt Foto hochladen')); ?> <span class="required-mark">*</span> <small>(max. 2 MB)</small></label>
                                        <input type="file" id="photo_file_<?php echo $i; ?>" name="photo_file_<?php echo $i; ?>" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('photo_file')); ?>" required>
                                    </div>

                                    <div class="form-group full residence-field residence-field-<?php echo $i; ?>">
                                        <label for="visa_photo_file_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel hochladen')); ?> <span class="required-mark">*</span> <small>(max. 2 MB)</small></label>
                                        <input type="file" id="visa_photo_file_<?php echo $i; ?>" name="visa_photo_file_<?php echo $i; ?>" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('visa_photo_file')); ?>">
                                    </div>

                                    <div class="form-group full">
                                        <label for="meldezettel_file_<?php echo $i; ?>"><?php echo esc_html(alpenia_travel_t('Meldezettel hochladen')); ?> <small>(max. 5 MB)</small></label>
                                        <input type="file" id="meldezettel_file_<?php echo $i; ?>" name="meldezettel_file_<?php echo $i; ?>" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('meldezettel_file')); ?>">
                                    </div>


                                    <div class="form-group full">
                                        <label><?php echo esc_html(alpenia_travel_t('Checkliste')); ?> <span class="required-mark">*</span></label>
                                        <div class="check-grid">
                                            <label class="checkbox-line"><input type="checkbox" name="check_passport_<?php echo $i; ?>" value="1" required> <?php echo esc_html(alpenia_travel_t('Reisepass geprüft')); ?></label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_photo_<?php echo $i; ?>" value="1" required> <?php echo esc_html(alpenia_travel_t('Foto geprüft')); ?></label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_visa_<?php echo $i; ?>" value="1"> <?php echo esc_html(alpenia_travel_t('Aufenthaltstitel geprüft')); ?></label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_payment_<?php echo $i; ?>" value="1" required> <?php echo esc_html(alpenia_travel_t('Zahlung geprüft')); ?></label>
                                        </div>
                                    </div>

                                </div>

                                <div class="participant-box__actions">
                                    <button type="button" class="btn-secondary" data-wizard-prev <?php echo $i === 1 ? 'disabled' : ''; ?>><?php echo esc_html(alpenia_travel_t('Vorheriger Teilnehmer')); ?></button>
                                    <?php if ($i < $participant_count) : ?>
                                        <button type="button" class="btn-primary" data-wizard-next><?php echo esc_html(alpenia_travel_t('Nächster Teilnehmer')); ?></button>
                                    <?php else : ?>
                                        <button type="button" class="btn-primary" data-wizard-submit><?php echo esc_html(alpenia_travel_t('Alle Teilnehmer prüfen und speichern')); ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                        </div>

                        <div class="participant-wizard__final-actions">
                            <p><?php echo esc_html(alpenia_travel_t('Du kannst jederzeit über die nummerierten Reiter zwischen den Teilnehmern wechseln.')); ?></p>
                            <button type="button" class="btn-primary" data-wizard-submit><?php echo esc_html(alpenia_travel_t('Alle Teilnehmer prüfen und speichern')); ?></button>
                            <button type="submit" name="save_participants_batch" value="1" class="participant-wizard__native-submit" hidden><?php echo esc_html(alpenia_travel_t('Alle Teilnehmer speichern')); ?></button>
                        </div>
                    </form>
                </div>

            <?php elseif (isset($_GET['edit_participant'])) : ?>

                <?php
                $participant_id = (int) $_GET['edit_participant'];

                if (!alpenia_user_can_access_participant($participant_id)) {
        return '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff.')) . '</div>';
                }

                $participant = get_post($participant_id);
                $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);

                $gender             = get_post_meta($participant_id, 'gender', true);
                $first_name         = alpenia_get_secure_meta($participant_id, 'first_name', true);
                $second_first_name  = alpenia_get_secure_meta($participant_id, 'second_first_name', true);
                $last_name          = alpenia_get_secure_meta($participant_id, 'last_name', true);
                $birth_date         = alpenia_get_secure_meta($participant_id, 'birth_date', true);
                $street_address     = alpenia_get_secure_meta($participant_id, 'street_address', true);
                $postal_city        = alpenia_get_secure_meta($participant_id, 'postal_city', true);
                $nationality        = alpenia_get_secure_meta($participant_id, 'nationality', true);
                $passport_no        = alpenia_get_secure_meta($participant_id, 'passport_no', true);
                $phone_number       = alpenia_get_secure_meta($participant_id, 'phone_number', true);
                $email_address      = alpenia_get_secure_meta($participant_id, 'email_address', true);
                $emergency_contact_name = alpenia_get_secure_meta($participant_id, 'emergency_contact_name', true);
                $emergency_contact_phone = alpenia_get_secure_meta($participant_id, 'emergency_contact_phone', true);
                $passport_valid_from = alpenia_get_secure_meta($participant_id, 'passport_valid_from_date', true);
                $passport_expiry     = alpenia_get_secure_meta($participant_id, 'passport_expiry_date', true);
                $residence_permit_start_date = alpenia_get_secure_meta($participant_id, 'residence_permit_start_date', true);
                $residence_permit_number = alpenia_get_secure_meta($participant_id, 'residence_permit_number', true);
                $residence_permit_valid_until = alpenia_get_secure_meta($participant_id, 'residence_permit_valid_until', true);
                $visa_entry_country = alpenia_get_visa_entry_country($participant_id);
                $visa_number        = alpenia_get_secure_meta($participant_id, 'visa_number', true);
                $visa_expiry_date   = alpenia_get_secure_meta($participant_id, 'visa_expiry_date', true);
                $participant_status = get_post_meta($participant_id, 'participant_status', true);
                $edit_is_pilgrimage_trip = alpenia_is_pilgrimage_trip($trip_id);
                $subgroup           = alpenia_get_secure_meta($participant_id, 'subgroup', true);
                $payment_total      = get_post_meta($participant_id, 'payment_total', true);
                $payment_deposit    = get_post_meta($participant_id, 'payment_deposit', true);
                $payment_paid       = get_post_meta($participant_id, 'payment_paid', true);
                $check_passport     = get_post_meta($participant_id, 'check_passport', true);
                $check_photo        = get_post_meta($participant_id, 'check_photo', true);
                $check_visa         = get_post_meta($participant_id, 'check_visa', true);
                $check_payment      = get_post_meta($participant_id, 'check_payment', true);
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t('Teilnehmer bearbeiten')); ?></h1>
                            <p><?php echo esc_html($participant ? $participant->post_title : ''); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $trip_id])); ?>"><?php echo esc_html(alpenia_travel_t('Zurück zur Reise')); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" enctype="multipart/form-data" class="alpenia-form" data-pilgrimage-trip="<?php echo $edit_is_pilgrimage_trip ? '1' : '0'; ?>">
                        <?php wp_nonce_field('alpenia_edit_participant_' . $participant_id, 'alpenia_edit_participant_nonce'); ?>
                        <input type="hidden" name="participant_id" value="<?php echo esc_attr($participant_id); ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="gender"><?php echo esc_html(alpenia_travel_t('Anrede')); ?> <span class="required-mark">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <option value="Herr" <?php selected($gender, 'Herr'); ?>><?php echo esc_html(alpenia_travel_t('Herr')); ?></option>
                                    <option value="Frau" <?php selected($gender, 'Frau'); ?>><?php echo esc_html(alpenia_travel_t('Frau')); ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="first_name"><?php echo esc_html(alpenia_travel_t('Vorname')); ?> <span class="required-mark">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="second_first_name"><?php echo esc_html(alpenia_travel_t('2. Vorname')); ?></label>
                                <input type="text" id="second_first_name" name="second_first_name" value="<?php echo esc_attr($second_first_name); ?>">
                            </div>

                            <div class="form-group">
                                <label for="last_name"><?php echo esc_html(alpenia_travel_t('Nachname')); ?> <span class="required-mark">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="birth_date"><?php echo esc_html(alpenia_travel_t('Geburtsdatum')); ?></label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo esc_attr($birth_date); ?>">
                            </div>

                            <div class="form-group full">
                                <strong><?php echo esc_html(alpenia_travel_t('Adresse & Kontakt')); ?></strong>
                            </div>

                            <div class="form-group">
                                <label for="street_address"><?php echo esc_html(alpenia_travel_t('Straße')); ?></label>
                                <input type="text" id="street_address" name="street_address" value="<?php echo esc_attr($street_address); ?>">
                            </div>

                            <div class="form-group">
                                <label for="postal_city"><?php echo esc_html(alpenia_travel_t('Postleitzahl / Stadt')); ?></label>
                                <input type="text" id="postal_city" name="postal_city" value="<?php echo esc_attr($postal_city); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone_number"><?php echo esc_html(alpenia_travel_t('Telefonnummer')); ?></label>
                                <input type="text" id="phone_number" name="phone_number" value="<?php echo esc_attr($phone_number); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email_address"><?php echo esc_html(alpenia_travel_t('E-Mail Adresse')); ?></label>
                                <input type="email" id="email_address" name="email_address" value="<?php echo esc_attr($email_address); ?>">
                            </div>

                            <div class="form-group">
                                <label for="emergency_contact_name"><?php echo esc_html(alpenia_travel_t('Notfallkontakt Name')); ?></label>
                                <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr($emergency_contact_name); ?>">
                            </div>

                            <div class="form-group">
                                <label for="emergency_contact_phone"><?php echo esc_html(alpenia_travel_t('Notfallkontakt Telefonnummer')); ?></label>
                                <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr($emergency_contact_phone); ?>">
                            </div>

                            <div class="form-group full">
                                <strong><?php echo esc_html(alpenia_travel_t('Reisepassdaten')); ?></strong>
                            </div>

                            <div class="form-group">
                                <label for="nationality"><?php echo esc_html(alpenia_travel_t('Staatsbürgerschaft')); ?> <span class="required-mark">*</span></label>
                                <select id="nationality" name="nationality" required>
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <?php foreach (alpenia_get_all_countries() as $country_name) : ?>
                                        <option value="<?php echo esc_attr($country_name); ?>" <?php selected($nationality, $country_name); ?>><?php echo esc_html($country_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="passport_no"><?php echo esc_html(alpenia_travel_t('Reisepassnummer')); ?></label>
                                <input type="text" id="passport_no" name="passport_no" value="<?php echo esc_attr($passport_no); ?>">
                            </div>

                            <div class="form-group">
                                <label for="passport_valid_from_date"><?php echo esc_html(alpenia_travel_t('Reisepass gültig von')); ?> <span class="required-mark">*</span></label>
                                <input type="date" id="passport_valid_from_date" name="passport_valid_from_date" value="<?php echo esc_attr($passport_valid_from); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="passport_expiry_date"><?php echo esc_html(alpenia_travel_t('Reisepass gültig bis')); ?> <span class="required-mark">*</span></label>
                                <input type="date" id="passport_expiry_date" name="passport_expiry_date" value="<?php echo esc_attr($passport_expiry); ?>" required>
                            </div>

                            <div class="form-group full edit-residence-field">
                                <strong><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel')); ?></strong>
                            </div>

                            <div class="form-group edit-residence-field">
                                <label for="residence_permit_start_date"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel gültig von')); ?></label>
                                <input type="date" id="residence_permit_start_date" name="residence_permit_start_date" value="<?php echo esc_attr($residence_permit_start_date); ?>">
                            </div>

                            <div class="form-group edit-residence-field">
                                <label for="residence_permit_number"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel Nummer')); ?></label>
                                <input type="text" id="residence_permit_number" name="residence_permit_number" value="<?php echo esc_attr($residence_permit_number); ?>">
                            </div>

                            <div class="form-group edit-residence-field">
                                <label for="residence_permit_valid_until"><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel gültig bis')); ?></label>
                                <input type="date" id="residence_permit_valid_until" name="residence_permit_valid_until" value="<?php echo esc_attr($residence_permit_valid_until); ?>">
                            </div>

                            <div class="form-group full edit-visa-field">
                                <strong><?php echo esc_html(alpenia_travel_t('Visum')); ?></strong>
                            </div>

                            <div class="form-group edit-visa-field">
                                <label for="visa_entry_country"><?php echo esc_html(alpenia_travel_t('Visum-Einreiseland')); ?></label>
                                <select id="visa_entry_country" name="visa_entry_country">
                                    <option value=""><?php echo esc_html(alpenia_travel_t("Bitte wählen")); ?></option>
                                    <?php foreach (alpenia_get_all_countries() as $country_name) : ?>
                                        <option value="<?php echo esc_attr($country_name); ?>" <?php selected($visa_entry_country, $country_name); ?>><?php echo esc_html($country_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group edit-visa-field">
                                <label for="visa_number"><?php echo esc_html(alpenia_travel_t('Visum Nummer')); ?></label>
                                <input type="text" id="visa_number" name="visa_number" value="<?php echo esc_attr($visa_number); ?>">
                            </div>

                            <div class="form-group edit-visa-field">
                                <label for="visa_expiry_date"><?php echo esc_html(alpenia_travel_t('Visum gültig bis')); ?></label>
                                <input type="date" id="visa_expiry_date" name="visa_expiry_date" value="<?php echo esc_attr($visa_expiry_date); ?>">
                            </div>

                            <div class="form-group full">
                                <strong><?php echo esc_html(alpenia_travel_t('Status & Zahlung')); ?></strong>
                            </div>

                            <div class="form-group">
                                <label for="participant_status"><?php echo esc_html(alpenia_travel_t('Bearbeitungsstatus')); ?></label>
                                <select id="participant_status" name="participant_status">
                                    <option value="neu" <?php selected($participant_status, 'neu'); ?>><?php echo esc_html(alpenia_travel_t('Neu')); ?></option>
                                    <option value="in_pruefung" <?php selected($participant_status, 'in_pruefung'); ?>><?php echo esc_html(alpenia_travel_t('In Prüfung')); ?></option>
                                    <option value="vollstaendig" <?php selected($participant_status, 'vollstaendig'); ?>><?php echo esc_html(alpenia_travel_t('Vollständig')); ?></option>
                                </select>
                            </div>


                            <div class="form-group">
                                <label for="payment_total"><?php echo esc_html(alpenia_travel_t('Gesamtpreis (€)')); ?></label>
                                <input type="number" step="0.01" min="0" id="payment_total" name="payment_total" value="<?php echo esc_attr($payment_total); ?>">
                            </div>

                            <div class="form-group">
                                <label for="payment_deposit"><?php echo esc_html(alpenia_travel_t('Anzahlung (€)')); ?></label>
                                <input type="number" step="0.01" min="0" id="payment_deposit" name="payment_deposit" value="<?php echo esc_attr($payment_deposit); ?>">
                            </div>

                            <div class="form-group full">
                                <label for="payment_paid"><?php echo esc_html(alpenia_travel_t('Bereits bezahlt (€)')); ?></label>
                                <input type="number" step="0.01" min="0" id="payment_paid" name="payment_paid" value="<?php echo esc_attr($payment_paid); ?>">
                            </div>

                            <div class="form-group full">
                                <strong><?php echo esc_html(alpenia_travel_t('Dokumente & Checkliste')); ?></strong>
                            </div>

                            <div class="form-group">
                                <label for="passport_file"><?php echo esc_html(alpenia_travel_t('Neuen Reisepass hochladen')); ?> <small>(max. 5 MB)</small></label>
                                <input type="file" id="passport_file" name="passport_file" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('passport_file')); ?>">
                            </div>

                            <div class="form-group">
                                <label for="photo_file"><?php echo esc_html(alpenia_travel_t('Neues Porträt Foto hochladen')); ?> <small>(max. 2 MB)</small></label>
                                <input type="file" id="photo_file" name="photo_file" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('photo_file')); ?>">
                            </div>

                            <div class="form-group full edit-residence-field">
                                <label for="visa_photo_file"><?php echo esc_html(alpenia_travel_t('Neuen Aufenthaltstitel hochladen')); ?> <small>(max. 2 MB)</small></label>
                                <input type="file" id="visa_photo_file" name="visa_photo_file" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('visa_photo_file')); ?>">
                            </div>

                            <div class="form-group full">
                                <label for="meldezettel_file"><?php echo esc_html(alpenia_travel_t('Neuen Meldezettel hochladen')); ?> <small>(max. 5 MB)</small></label>
                                <input type="file" id="meldezettel_file" name="meldezettel_file" accept="<?php echo esc_attr(alpenia_upload_accept_attribute('meldezettel_file')); ?>">
                            </div>


                            <div class="form-group full">
                                <label><?php echo esc_html(alpenia_travel_t('Checkliste')); ?></label>
                                <div class="check-grid">
                                    <label class="checkbox-line"><input type="checkbox" name="check_passport" value="1" <?php checked($check_passport, 1); ?>> <?php echo esc_html(alpenia_travel_t('Reisepass geprüft')); ?></label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_photo" value="1" <?php checked($check_photo, 1); ?>> <?php echo esc_html(alpenia_travel_t('Foto geprüft')); ?></label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_visa" value="1" <?php checked($check_visa, 1); ?>> <?php echo esc_html(alpenia_travel_t('Aufenthaltstitel geprüft')); ?></label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_payment" value="1" <?php checked($check_payment, 1); ?>> <?php echo esc_html(alpenia_travel_t('Zahlung geprüft')); ?></label>
                                </div>
                            </div>

                        </div>

                        <button type="submit" name="update_participant" class="btn-primary"><?php echo esc_html(alpenia_travel_t('Änderungen speichern')); ?></button>
                    </form>
                </div>

            <?php elseif (isset($_GET['view_trip'])) : ?>

                <?php
                $view_trip_id = (int) $_GET['view_trip'];

                if (!alpenia_user_can_access_trip($view_trip_id)) {
        return '<div class="alpenia-message">' . esc_html(alpenia_travel_t('Kein Zugriff.')) . '</div>';
                }

                $trip = get_post($view_trip_id);
                $participant_search = isset($_GET['participant_search']) ? sanitize_text_field(wp_unslash($_GET['participant_search'])) : '';
                $participant_doc_filter = isset($_GET['participant_doc_filter']) ? sanitize_key(wp_unslash($_GET['participant_doc_filter'])) : '';
                $participant_payment_filter = isset($_GET['participant_payment_filter']) ? sanitize_key(wp_unslash($_GET['participant_payment_filter'])) : '';
                $participant_status_filter = isset($_GET['participant_status_filter']) ? sanitize_key(wp_unslash($_GET['participant_status_filter'])) : '';
                $participant_page = isset($_GET['participant_page']) ? max(1, (int) $_GET['participant_page']) : 1;
                $participants_per_page = alpenia_dashboard_get_per_page('participants_per_page', 50, [25, 50, 100]);
                $trip_participants_result = alpenia_get_paginated_trip_participants($view_trip_id, [
                    'search' => $participant_search,
                    'doc_status' => $participant_doc_filter,
                    'payment_status' => $participant_payment_filter,
                    'participant_status' => $participant_status_filter,
                ], $participant_page, $participants_per_page);
                $trip_participants = $trip_participants_result['posts'];
                $trip_participants_total = (int) $trip_participants_result['total'];
                $trip_participants_max_pages = (int) $trip_participants_result['max_pages'];

                if ($trip_participants_total > 0 && $participant_page > $trip_participants_max_pages) {
                    $participant_page = $trip_participants_max_pages;
                    $trip_participants_result = alpenia_get_paginated_trip_participants($view_trip_id, [
                        'search' => $participant_search,
                        'doc_status' => $participant_doc_filter,
                        'payment_status' => $participant_payment_filter,
                        'participant_status' => $participant_status_filter,
                    ], $participant_page, $participants_per_page);
                    $trip_participants = $trip_participants_result['posts'];
                }

                $trip_participants_all_count = alpenia_count_trip_participants($view_trip_id);
                $assigned_guide_id = (int) get_post_meta($view_trip_id, 'assigned_guide', true);
                $assigned_guide_name = $assigned_guide_id ? get_the_author_meta('display_name', $assigned_guide_id) : '';
                $delete_trip_nonce = wp_create_nonce('alpenia_delete_trip_' . $view_trip_id);
                $view_is_pilgrimage_trip = alpenia_is_pilgrimage_trip($view_trip_id);
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html($trip ? $trip->post_title : alpenia_travel_t('Reise')); ?></h1>
                            <p><?php echo esc_html(alpenia_travel_t("Teilnehmerliste dieser Reise")); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['export_trip_csv' => $view_trip_id])); ?>"><?php echo esc_html(alpenia_travel_t("CSV Export")); ?></a>
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['print_trip' => $view_trip_id])); ?>" target="_blank"><?php echo esc_html(alpenia_travel_t("PDF / Drucken")); ?></a>
                        <?php if (alpenia_user_can_edit_trip($view_trip_id)) : ?>
                            <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['edit_trip' => $view_trip_id])); ?>"><?php echo esc_html(alpenia_travel_t('Reise bearbeiten')); ?></a>
                        <?php endif; ?>
                        <?php if (alpenia_user_can_delete_trip($view_trip_id)) : ?>
                            <a class="btn-secondary table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['delete_trip' => $view_trip_id, '_delete_trip_nonce' => $delete_trip_nonce])); ?>" data-delete-type="trip"><?php echo esc_html(alpenia_travel_t('Reise löschen')); ?></a>
                        <?php endif; ?>
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t("Zurück zum Dashboard")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="trip-meta-grid">
                        <?php $view_trip_type = (string) get_post_meta($view_trip_id, 'trip_type', true); ?>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Reisetyp')); ?></strong><span><?php echo esc_html($view_trip_type === 'umrah' ? alpenia_travel_t('Umrah') : $view_trip_type); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Status')); ?></strong><span><?php echo wp_kses_post(alpenia_trip_status_badge(get_post_meta($view_trip_id, 'trip_status', true))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Ziel')); ?></strong><span><?php echo esc_html(alpenia_display_value(alpenia_destination_to_display_language(get_post_meta($view_trip_id, 'destination', true)))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Reiseveranstalter Land')); ?></strong><span><?php echo esc_html(alpenia_display_value(alpenia_country_to_display_language(get_post_meta($view_trip_id, 'country', true)))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Reiseveranstalter Stadt')); ?></strong><span><?php echo esc_html(alpenia_display_value(get_post_meta($view_trip_id, 'city', true))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Abflugstadt')); ?></strong><span><?php echo esc_html(alpenia_display_value(get_post_meta($view_trip_id, 'departure_city', true))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Flughafen')); ?></strong><span><?php echo esc_html(alpenia_display_value(get_post_meta($view_trip_id, 'departure_airport', true))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Zeitraum')); ?></strong><span><?php echo esc_html(alpenia_date_range_display(get_post_meta($view_trip_id, 'start_date', true), get_post_meta($view_trip_id, 'end_date', true))); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Freie Plätze')); ?></strong><span><?php echo esc_html(alpenia_get_trip_capacity_left($view_trip_id)); ?></span></div>
                        <div class="trip-meta-box"><strong><?php echo esc_html(alpenia_travel_t('Reiseleiter')); ?></strong><span><?php echo esc_html(alpenia_display_value($assigned_guide_name)); ?></span></div>
                        <div class="trip-meta-box"><strong>WhatsApp</strong><span><?php $wa = get_post_meta($view_trip_id, 'whatsapp_link', true); if ($wa) : ?><div class="public-registration-link__actions"><button type="button" class="btn-secondary public-registration-link__copy" data-copy-value="<?php echo esc_attr($wa); ?>" data-copy-default="<?php echo esc_attr(alpenia_travel_t('Link kopieren')); ?>" data-copy-success="<?php echo esc_attr(alpenia_travel_t('Link kopiert')); ?>" aria-live="polite"><?php echo esc_html(alpenia_travel_t('Link kopieren')); ?></button><a class="btn-primary public-registration-link__open" href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a></div><?php else : ?>-<?php endif; ?></span></div>
                        <div class="trip-meta-box"><strong>Zoom</strong><span><?php $zoom = get_post_meta($view_trip_id, 'zoom_link', true); if ($zoom) : ?><div class="public-registration-link__actions"><button type="button" class="btn-secondary public-registration-link__copy" data-copy-value="<?php echo esc_attr($zoom); ?>" data-copy-default="<?php echo esc_attr(alpenia_travel_t('Link kopieren')); ?>" data-copy-success="<?php echo esc_attr(alpenia_travel_t('Link kopiert')); ?>" aria-live="polite"><?php echo esc_html(alpenia_travel_t('Link kopieren')); ?></button><a class="btn-primary public-registration-link__open" href="<?php echo esc_url($zoom); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a></div><?php else : ?>-<?php endif; ?></span></div>
                    </div>

                    <?php $notes = alpenia_get_secure_meta($view_trip_id, 'internal_notes', true); ?>
                    <?php if (!empty($notes)) : ?>
                        <div style="margin-top:20px;">
                            <strong><?php echo esc_html(alpenia_travel_t('Interne Notizen')); ?></strong>
                            <div class="notes-box"><?php echo esc_html($notes); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                $public_form_link = alpenia_public_participant_get_trip_form_url($view_trip_id);
                ?>
                <div class="panel public-registration-panel">
                    <div class="public-registration-panel__header">
                        <div>
                            <span class="public-registration-panel__eyebrow"><?php echo esc_html(alpenia_travel_t('Öffentlicher Zugang')); ?></span>
                            <h2><?php echo esc_html(alpenia_travel_t('Öffentliche Anmeldung')); ?></h2>
                        </div>
                        <span class="public-registration-panel__badge"><?php echo esc_html(alpenia_travel_t('Nur mit Link')); ?></span>
                    </div>
                    <p class="public-registration-panel__text"><?php echo esc_html(alpenia_travel_t('Teile ausschließlich diesen individuellen Anmeldelink mit Teilnehmern. Das öffentliche Formular ist ohne gültigen Link nicht zugänglich.')); ?></p>
                    <div class="public-registration-card">
                        <label class="public-registration-card__label" for="public-registration-link-<?php echo (int) $view_trip_id; ?>"><?php echo esc_html(alpenia_travel_t('Individueller Anmeldelink')); ?></label>
                        <div class="public-registration-link">
                            <input id="public-registration-link-<?php echo (int) $view_trip_id; ?>" class="public-registration-link__input" type="text" readonly value="<?php echo esc_attr($public_form_link); ?>" onclick="this.select();" aria-label="<?php echo esc_attr(alpenia_travel_t('Individueller Anmeldelink')); ?>">
                            <div class="public-registration-link__actions">
                                <button type="button" class="btn-secondary public-registration-link__copy" data-copy-value="<?php echo esc_attr($public_form_link); ?>" data-copy-default="<?php echo esc_attr(alpenia_travel_t('Link kopieren')); ?>" data-copy-success="<?php echo esc_attr(alpenia_travel_t('Link kopiert')); ?>" aria-live="polite"><?php echo esc_html(alpenia_travel_t('Link kopieren')); ?></button>
                                <a class="btn-primary public-registration-link__open" href="<?php echo esc_url($public_form_link); ?>" target="_blank" rel="noopener"><?php echo esc_html(alpenia_travel_t('Formular öffnen')); ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2><?php echo esc_html(alpenia_travel_t("Teilnehmer dieser Reise")); ?></h2>

                    <form method="get" class="filter-bar filter-bar--participants">
                        <input type="hidden" name="view_trip" value="<?php echo esc_attr($view_trip_id); ?>">
                        <input type="text" name="participant_search" value="<?php echo esc_attr($participant_search); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('Teilnehmer, Passnummer oder E-Mail suchen')); ?>">

                        <select name="participant_doc_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Dokumente')); ?></option>
                            <option value="missing" <?php selected($participant_doc_filter, 'missing'); ?>><?php echo esc_html(alpenia_travel_t('Unterlagen fehlen')); ?></option>
                            <option value="complete" <?php selected($participant_doc_filter, 'complete'); ?>><?php echo esc_html(alpenia_travel_t('Unterlagen komplett')); ?></option>
                        </select>

                        <select name="participant_payment_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Zahlungen')); ?></option>
                            <option value="open" <?php selected($participant_payment_filter, 'open'); ?>><?php echo esc_html(alpenia_travel_t('Offene Zahlungen')); ?></option>
                            <option value="partial" <?php selected($participant_payment_filter, 'partial'); ?>><?php echo esc_html(alpenia_travel_t('Teilweise bezahlt')); ?></option>
                            <option value="paid" <?php selected($participant_payment_filter, 'paid'); ?>><?php echo esc_html(alpenia_travel_t('Bezahlt')); ?></option>
                        </select>

                        <select name="participant_status_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Status')); ?></option>
                            <option value="neu" <?php selected($participant_status_filter, 'neu'); ?>><?php echo esc_html(alpenia_travel_t('neu')); ?></option>
                            <option value="offen" <?php selected($participant_status_filter, 'offen'); ?>><?php echo esc_html(alpenia_travel_t('offen')); ?></option>
                            <option value="aktiv" <?php selected($participant_status_filter, 'aktiv'); ?>><?php echo esc_html(alpenia_travel_t('aktiv')); ?></option>
                        </select>

                        <select name="participants_per_page">
                            <option value="25" <?php selected($participants_per_page, 25); ?>>25 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                            <option value="50" <?php selected($participants_per_page, 50); ?>>50 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                            <option value="100" <?php selected($participants_per_page, 100); ?>>100 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                        </select>

                        <button type="submit" class="btn-primary"><?php echo esc_html(alpenia_travel_t('Filtern')); ?></button>
                        <a href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $view_trip_id])); ?>" class="btn-secondary"><?php echo esc_html(alpenia_travel_t('Zurücksetzen')); ?></a>
                    </form>

                    <div class="list-summary">
                        <?php echo esc_html(sprintf(alpenia_travel_t('%1$d von %2$d Teilnehmern angezeigt'), $trip_participants_total, $trip_participants_all_count)); ?>
                    </div>

                    <?php if (!empty($trip_participants)) : ?>
                        <div class="table-wrap">
                            <table class="alpenia-table">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html(alpenia_travel_t('Anrede')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Name')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Dokumente')); ?></th>
                                        <?php if ($view_is_pilgrimage_trip) : ?>
                                            <th><?php echo esc_html(alpenia_travel_t('Visum-Einreiseland')); ?></th>
                                        <?php endif; ?>
                                        <th><?php echo esc_html(alpenia_travel_t('Staatsbürgerschaft')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Reisepass Nr.')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Reisepass gültig von')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Reisepass gültig bis')); ?></th>
                                        <?php if ($view_is_pilgrimage_trip) : ?>
                                            <th><?php echo esc_html(alpenia_travel_t('Visum Nummer')); ?></th>
                                            <th><?php echo esc_html(alpenia_travel_t('Visum gültig bis')); ?></th>
                                        <?php endif; ?>
                                        <th><?php echo esc_html(alpenia_travel_t('Status')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Zahlung')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Reisepass')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Foto')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Meldezettel')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel Nummer')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Aufenthaltstitel')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Aktionen')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trip_participants as $participant) :
                                        $gender = get_post_meta($participant->ID, 'gender', true);
                                        $participant_full_name = alpenia_get_participant_full_name($participant->ID);
                                        $passport_file_id = (int) get_post_meta($participant->ID, 'passport_file_id', true);
                                        $photo_file_id = (int) get_post_meta($participant->ID, 'photo_file_id', true);
                                        $visa_photo_file_id = (int) get_post_meta($participant->ID, 'visa_photo_file_id', true);
                                        $meldezettel_file_id = (int) get_post_meta($participant->ID, 'meldezettel_file_id', true);
                                        $residence_permit_number = alpenia_get_secure_meta($participant->ID, 'residence_permit_number', true);
                                        $delete_nonce = wp_create_nonce('alpenia_delete_participant_' . $participant->ID);
                                        $payment_open = alpenia_get_participant_payment_open($participant->ID);
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html(alpenia_display_value(alpenia_travel_t($gender))); ?></td>
                                            <td>
                                                <strong><?php echo esc_html(alpenia_display_value($participant_full_name)); ?></strong>
                                            </td>
                                            <td><?php echo wp_kses_post(alpenia_get_participant_doc_badge($participant->ID)); ?></td>
                                            <?php if ($view_is_pilgrimage_trip) : ?>
                                                <td><?php echo esc_html(alpenia_display_value(alpenia_country_to_display_language(alpenia_get_visa_entry_country($participant->ID)))); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo esc_html(alpenia_display_value(alpenia_country_to_display_language(alpenia_get_secure_meta($participant->ID, 'nationality', true)))); ?></td>
                                            <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'passport_no', true))); ?></td>
                                            <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'passport_valid_from_date', true))); ?></td>
                                            <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'passport_expiry_date', true))); ?></td>
                                            <?php if ($view_is_pilgrimage_trip) : ?>
                                                <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'visa_number', true))); ?></td>
                                                <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'visa_expiry_date', true))); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo esc_html(alpenia_travel_translate_label(get_post_meta($participant->ID, 'participant_status', true))); ?></td>
                                            <td>
                                                <?php echo esc_html(alpenia_travel_translate_label(alpenia_get_payment_status($participant->ID))); ?><br>
                                                <small><?php echo esc_html(alpenia_travel_t('Offen:')); ?> € <?php echo esc_html(number_format($payment_open, 2, ',', '.')); ?></small>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($passport_file_id, false)); ?>
                                                <?php if ($passport_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($passport_file_id); ?>" target="_blank"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a> · <a href="<?php echo alpenia_attachment_download_link($passport_file_id); ?>"><?php echo esc_html(alpenia_travel_t('İndir')); ?></a><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($photo_file_id, false)); ?>
                                                <?php if ($photo_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($photo_file_id); ?>" target="_blank"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a> · <a href="<?php echo alpenia_attachment_download_link($photo_file_id); ?>"><?php echo esc_html(alpenia_travel_t('İndir')); ?></a><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($meldezettel_file_id, false)); ?>
                                                <?php if ($meldezettel_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($meldezettel_file_id); ?>" target="_blank"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a> · <a href="<?php echo alpenia_attachment_download_link($meldezettel_file_id); ?>"><?php echo esc_html(alpenia_travel_t('İndir')); ?></a><?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html(alpenia_display_value($residence_permit_number)); ?></td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($visa_photo_file_id, true)); ?>
                                                <?php if ($visa_photo_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($visa_photo_file_id); ?>" target="_blank"><?php echo esc_html(alpenia_travel_t('Öffnen')); ?></a> · <a href="<?php echo alpenia_attachment_download_link($visa_photo_file_id); ?>"><?php echo esc_html(alpenia_travel_t('İndir')); ?></a><?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="row-actions">
                                                    <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $participant->ID])); ?>"><?php echo esc_html(alpenia_travel_t('Bearbeiten')); ?></a>
                                                    <a class="table-btn table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $view_trip_id, 'delete_participant' => $participant->ID, '_delete_nonce' => $delete_nonce])); ?>" data-delete-type="participant"><?php echo esc_html(alpenia_travel_t('Löschen')); ?></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo alpenia_dashboard_render_pagination($participant_page, $trip_participants_max_pages, [
                            'view_trip' => $view_trip_id,
                            'participant_search' => $participant_search,
                            'participant_doc_filter' => $participant_doc_filter,
                            'participant_payment_filter' => $participant_payment_filter,
                            'participant_status_filter' => $participant_status_filter,
                            'participants_per_page' => $participants_per_page,
                        ], 'participant_page'); ?>
                    <?php else : ?>
                        <p><?php echo esc_html(alpenia_travel_t('Keine Teilnehmer für diese Suche / Filter gefunden.')); ?></p>
                    <?php endif; ?>
                </div>

            <?php elseif (isset($_GET['trash_bin'])) : ?>

                <?php
                $trash_type_filter = isset($_GET['trash_type']) ? sanitize_key(wp_unslash((string) $_GET['trash_type'])) : 'all';
                $trash_post_types = ['group_trip', 'trip_participant'];
                if (in_array($trash_type_filter, $trash_post_types, true)) {
                    $trash_post_types = [$trash_type_filter];
                } else {
                    $trash_type_filter = 'all';
                }

                $trashed_items = get_posts([
                    'post_type' => $trash_post_types,
                    'post_status' => 'trash',
                    'numberposts' => -1,
                    'orderby' => 'modified',
                    'order' => 'DESC',
                ]);
                ?>
                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t("Papierkorb")); ?></h1>
                            <p><?php echo esc_html(alpenia_travel_t('Gelöschte Reisen und Teilnehmer verwalten')); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t("Zurück zum Dashboard")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel trash-panel">
                    <div class="trash-panel__header">
                        <div>
                            <h2><?php echo esc_html(alpenia_travel_t('Papierkorb Übersicht')); ?></h2>
                            <p><?php echo esc_html(alpenia_travel_t('Verwalte gelöschte Reisen und Teilnehmer an einem Ort.')); ?></p>
                        </div>
                        <div class="trash-panel__count">
                            <span><?php echo esc_html(count($trashed_items)); ?></span>
                            <small><?php echo esc_html(alpenia_travel_t('Einträge')); ?></small>
                        </div>
                    </div>
                    <div class="trash-filters">
                        <?php
                        $trash_filter_links = [
                            'all' => alpenia_travel_t('Alle'),
                            'trip_participant' => alpenia_travel_t('Teilnehmer'),
                            'group_trip' => alpenia_travel_t('Reisen'),
                        ];
                        foreach ($trash_filter_links as $trash_filter_key => $trash_filter_label) :
                            $trash_filter_url = alpenia_dashboard_link(['trash_bin' => 1], ['trash_type']);
                            if ($trash_filter_key !== 'all') {
                                $trash_filter_url = alpenia_dashboard_link(['trash_bin' => 1, 'trash_type' => $trash_filter_key], ['trash_type']);
                            }
                        ?>
                            <a class="trash-filter-chip<?php echo $trash_type_filter === $trash_filter_key ? ' is-active' : ''; ?>" href="<?php echo esc_url($trash_filter_url); ?>">
                                <?php echo esc_html($trash_filter_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($trashed_items) : ?>
                        <div class="table-wrap">
                            <table class="alpenia-table alpenia-table--trash">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html(alpenia_travel_t('Typ')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Name')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Gelöscht am')); ?></th>
                                        <th><?php echo esc_html(alpenia_travel_t('Aktion')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trashed_items as $item) : ?>
                                        <?php
                                        $item_label = $item->post_type === 'group_trip' ? alpenia_travel_t('Reise') : alpenia_travel_t('Teilnehmer');
                                        $restore_url = wp_nonce_url(
                                            alpenia_dashboard_link(['trash_bin' => 1, 'restore_item' => (int) $item->ID]),
                                            'alpenia_restore_item_' . (int) $item->ID,
                                            '_restore_nonce'
                                        );
                                        $hard_delete_url = wp_nonce_url(
                                            alpenia_dashboard_link(['trash_bin' => 1, 'delete_item_permanently' => (int) $item->ID]),
                                            'alpenia_hard_delete_item_' . (int) $item->ID,
                                            '_hard_delete_nonce'
                                        );
                                        ?>
                                        <tr>
                                            <td><span class="trash-type-badge trash-type-badge--<?php echo esc_attr($item->post_type); ?>"><?php echo esc_html($item_label); ?></span></td>
                                            <td><strong><?php echo esc_html($item->post_title); ?></strong></td>
                                            <td><?php echo esc_html(get_the_modified_date('d.m.Y H:i', $item->ID)); ?></td>
                                            <td class="trash-actions">
                                                <a class="table-btn" href="<?php echo esc_url($restore_url); ?>" data-trash-action="restore"><?php echo esc_html(alpenia_travel_t('Wiederherstellen')); ?></a>
                                                <a class="table-btn table-btn-danger" href="<?php echo esc_url($hard_delete_url); ?>" data-trash-action="hard-delete"><?php echo esc_html(alpenia_travel_t('Dauerhaft löschen')); ?></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p><?php echo esc_html(alpenia_travel_t('Papierkorb ist leer.')); ?></p>
                    <?php endif; ?>
                </div>

            <?php elseif (isset($_GET['manage_users']) && alpenia_user_can_manage_users()) : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t("Benutzerverwaltung")); ?></h1>
                            <p><?php echo esc_html(alpenia_travel_t('Reiseleiter und Backoffice verwalten')); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t("Zurück zum Dashboard")); ?></a>
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                    </div>
                </div>

                <div class="panel users-panel users-panel--create">
                    <?php if ($dashboard_edit_mode && $dashboard_edit_user) : ?>
                        <h2><?php echo esc_html(alpenia_travel_t("Benutzer bearbeiten")); ?></h2>

                        <form method="post" class="alpenia-form">
                            <?php wp_nonce_field('alpenia_dashboard_edit_user', 'alpenia_dashboard_edit_user_nonce'); ?>
                            <input type="hidden" name="edit_user_id" value="<?php echo (int) $dashboard_edit_user->ID; ?>">

                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="edit_display_name"><?php echo esc_html(alpenia_travel_t("Name")); ?></label>
                                    <input type="text" id="edit_display_name" name="edit_display_name" value="<?php echo esc_attr($dashboard_edit_user->display_name ?: $dashboard_edit_user->user_login); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label for="edit_email"><?php echo esc_html(alpenia_travel_t("E-Mail")); ?></label>
                                    <input type="email" id="edit_email" name="edit_email" value="<?php echo esc_attr($dashboard_edit_user->user_email); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label for="edit_password"><?php echo esc_html(alpenia_travel_t("Neues Passwort (leer lassen = unverändert)")); ?></label>
                                    <input type="text" id="edit_password" name="edit_password" placeholder="<?php echo esc_attr(alpenia_travel_t("Neues Passwort")); ?>">
                                </div>

                                <div class="form-group full">
                                    <label><?php echo esc_html(alpenia_travel_t("Rolle")); ?></label>
                                    <div class="role-select-grid">
                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="reiseleiter" <?php checked(in_array('reiseleiter', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span><?php echo esc_html(alpenia_travel_t("Reiseleiter")); ?></span>
                                        </label>

                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="administrator" <?php checked(in_array('administrator', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span>Admin</span>
                                        </label>

                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="backoffice" <?php checked(in_array('backoffice', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span><?php echo esc_html(alpenia_travel_t("Backoffice")); ?></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="role-action-grid">
                                <button type="submit" name="dashboard_update_user" class="btn-primary"><?php echo esc_html(alpenia_travel_t("Änderungen speichern")); ?></button>
                                <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1])); ?>"><?php echo esc_html(alpenia_travel_t("Abbrechen")); ?></a>
                            </div>
                        </form>

                    <?php else : ?>
                        <div class="users-panel__header">
                            <h2><?php echo esc_html(alpenia_travel_t("Neuen Benutzer anlegen")); ?></h2>
                            <p><?php echo esc_html(alpenia_travel_t('Reiseleiter und Backoffice verwalten')); ?></p>
                        </div>

                        <form method="post" class="alpenia-form">
                            <?php wp_nonce_field('alpenia_create_user', 'alpenia_create_user_nonce'); ?>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="display_name"><?php echo esc_html(alpenia_travel_t("Name")); ?></label>
                                    <input type="text" id="display_name" name="display_name" required>
                                </div>

                                <div class="form-group">
                                    <label for="email"><?php echo esc_html(alpenia_travel_t("E-Mail")); ?></label>
                                    <input type="email" id="email" name="email" required>
                                </div>

                                <div class="form-group">
                                    <label for="password"><?php echo esc_html(alpenia_travel_t("Passwort")); ?></label>
                                    <input type="text" id="password" name="password" required>
                                </div>

                                <div class="form-group">
                                    <label for="role"><?php echo esc_html(alpenia_travel_t("Rolle")); ?></label>
                                    <select id="role" name="role" required>
                                        <option value="reiseleiter"><?php echo esc_html(alpenia_travel_t('Reiseleiter')); ?></option>
                                        <option value="backoffice">Backoffice</option>
                                        <option value="administrator">Administrator</option>
                                    </select>
                                </div>
                            </div>

                            <div class="users-panel__footer">
                                <button type="submit" name="create_reiseleiter" class="btn-primary"><?php echo esc_html(alpenia_travel_t("Benutzer erstellen")); ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="panel users-panel users-panel--list" style="margin-top:20px;">
                    <div class="users-panel__header users-panel__header--inline">
                        <h2><?php echo esc_html(alpenia_travel_t("Benutzerliste")); ?></h2>
                        <span class="users-count-badge"><?php echo esc_html(count($dashboard_users)); ?></span>
                    </div>
                    <div class="table-wrap">
                        <table class="alpenia-table users-table">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html(alpenia_travel_t("Name")); ?></th>
                                    <th><?php echo esc_html(alpenia_travel_t("E-Mail")); ?></th>
                                    <th><?php echo esc_html(alpenia_travel_t("Rolle")); ?></th>
                                    <th><?php echo esc_html(alpenia_travel_t("Status")); ?></th>
                                    <th><?php echo esc_html(alpenia_travel_t('Aktion')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_users as $user) :
                                    $disabled = get_user_meta($user->ID, 'alpenia_disabled', true);
                                    $status = $disabled ? 'Deaktiviert' : 'Aktiv';

                                    $role_label = '-';
                                    $role_key = 'unknown';
                                    if (in_array('administrator', (array) $user->roles, true)) {
                                        $role_label = 'Admin';
                                        $role_key = 'admin';
                                    } elseif (in_array('reiseleiter', (array) $user->roles, true)) {
                                        $role_label = alpenia_travel_t('Reiseleiter');
                                        $role_key = 'reiseleiter';
                                    } elseif (in_array('backoffice', (array) $user->roles, true)) {
                                        $role_label = alpenia_travel_t('Backoffice');
                                        $role_key = 'backoffice';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($user->display_name); ?></td>
                                        <td><?php echo esc_html($user->user_email); ?></td>
                                        <td><span class="users-role-pill users-role-pill--<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_label); ?></span></td>
                                        <td><span class="users-status-pill <?php echo $disabled ? 'is-disabled' : 'is-active'; ?>"><?php echo esc_html(alpenia_travel_t($status)); ?></span></td>
                                        <td>
                                            <?php if ((int) $user->ID === (int) get_current_user_id()) : ?>
                                                <?php echo esc_html(alpenia_travel_t("Eigenes Account")); ?>
                                            <?php else : ?>
                                                <div class="user-action-links">
                                                    <a class="action-button edit-button" href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1, 'dashboard_edit_user' => (int) $user->ID])); ?>"><?php echo esc_html(alpenia_travel_t("Bearbeiten")); ?></a>

                                                    <?php if ($disabled) : ?>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="dashboard_user_action" value="activate">
                                                            <input type="hidden" name="dashboard_user_id" value="<?php echo (int) $user->ID; ?>">
                                                            <input type="hidden" name="_dashboard_user_nonce" value="<?php echo esc_attr(wp_create_nonce('alpenia_dashboard_activate_user_' . (int) $user->ID)); ?>">
                                                            <button type="submit" class="link-button action-button activate-button"><?php echo esc_html(alpenia_travel_t("Aktivieren")); ?></button>
                                                        </form>
                                                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(alpenia_travel_t('Benutzer wirklich löschen?')); ?>');">
                                                            <input type="hidden" name="dashboard_user_action" value="delete">
                                                            <input type="hidden" name="dashboard_user_id" value="<?php echo (int) $user->ID; ?>">
                                                            <input type="hidden" name="_dashboard_user_nonce" value="<?php echo esc_attr(wp_create_nonce('alpenia_dashboard_delete_user_' . (int) $user->ID)); ?>">
                                                            <button type="submit" class="link-button action-button delete-button"><?php echo esc_html(alpenia_travel_t("Löschen")); ?></button>
                                                        </form>
                                                    <?php else : ?>
                                                        <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(alpenia_travel_t('Benutzer wirklich deaktivieren?')); ?>');">
                                                            <input type="hidden" name="dashboard_user_action" value="deactivate">
                                                            <input type="hidden" name="dashboard_user_id" value="<?php echo (int) $user->ID; ?>">
                                                            <input type="hidden" name="_dashboard_user_nonce" value="<?php echo esc_attr(wp_create_nonce('alpenia_dashboard_deactivate_user_' . (int) $user->ID)); ?>">
                                                            <button type="submit" class="link-button action-button deactivate-button"><?php echo esc_html(alpenia_travel_t("Deaktivieren")); ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html(alpenia_travel_t("Alpenia Travel")); ?></h1>
                            <p class="dashboard-welcome-text"><?php echo esc_html(sprintf(alpenia_travel_t('Hallo, %s!'), $current_user->display_name ?: $current_user->user_login)); ?></p>
                        </div>
                    </div>

                    <div class="actions actions-main">
                        <?php echo alpenia_dashboard_language_switcher(); ?>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="cards">
                    <div class="card">
                        <h3><?php echo esc_html(alpenia_travel_t("Reisen")); ?></h3>
                        <span><?php echo esc_html($total_trips); ?></span>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html(alpenia_travel_t("Teilnehmer")); ?></h3>
                        <span><?php echo esc_html($total_participants); ?></span>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html(alpenia_travel_t("Fehlende Unterlagen")); ?></h3>
                        <span><?php echo esc_html($missing_docs_count); ?></span>
                    </div>

                    <div class="card">
                        <h3><?php echo esc_html(alpenia_travel_t("Offene Zahlungen")); ?></h3>
                        <span><?php echo esc_html($open_payments_count); ?></span>
                    </div>
                </div>

                <?php if (alpenia_user_is_admin()) : ?>
                    <?php echo alpenia_dashboard_render_business_overview($all_trips, $participants); ?>
                <?php endif; ?>

                <div class="panel">
                    <h2><?php echo esc_html(alpenia_travel_t('Reisen mit Teilnehmerliste')); ?></h2>

                    <div class="trip-quick-filters">
                        <a class="trip-quick-filter <?php echo $trip_status_filter === 'open' ? 'is-active' : ''; ?>" href="<?php echo esc_url(alpenia_dashboard_link(array_merge($_GET, ['trip_status_filter' => 'open', 'trip_page' => 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Nur offene Reisen')); ?></a>
                        <a class="trip-quick-filter <?php echo $trip_status_filter === 'full' ? 'is-active' : ''; ?>" href="<?php echo esc_url(alpenia_dashboard_link(array_merge($_GET, ['trip_status_filter' => 'full', 'trip_page' => 1]))); ?>"><?php echo esc_html(alpenia_travel_t('Nur volle Reisen')); ?></a>
                        <a class="trip-quick-filter" href="<?php echo esc_url(alpenia_dashboard_link()); ?>"><?php echo esc_html(alpenia_travel_t('Alles zurücksetzen')); ?></a>
                    </div>

                    <form method="get" class="filter-bar">
                        <input type="text" name="trip_search" value="<?php echo esc_attr($trip_search); ?>" placeholder="<?php echo esc_attr(alpenia_travel_t('Reise oder Ziel suchen')); ?>">

                        <select name="trip_type_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Reisearten')); ?></option>
                            <option value="kultur" <?php selected($trip_type_filter, 'kultur'); ?>><?php echo esc_html(alpenia_travel_t('Kulturreise')); ?></option>
                            <option value="umrah" <?php selected($trip_type_filter, 'umrah'); ?>><?php echo esc_html(alpenia_travel_t('Umrah')); ?></option>
                            <option value="hajj" <?php selected($trip_type_filter, 'hajj'); ?>><?php echo esc_html(alpenia_travel_t('Hadsch')); ?></option>
                        </select>

                        <select name="trip_status_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Status')); ?></option>
                            <option value="draft" <?php selected($trip_status_filter, 'draft'); ?>><?php echo esc_html(alpenia_travel_t('Entwurf')); ?></option>
                            <option value="open" <?php selected($trip_status_filter, 'open'); ?>><?php echo esc_html(alpenia_travel_t("Offen")); ?></option>
                            <option value="full" <?php selected($trip_status_filter, 'full'); ?>><?php echo esc_html(alpenia_travel_t('Voll')); ?></option>
                            <option value="closed" <?php selected($trip_status_filter, 'closed'); ?>><?php echo esc_html(alpenia_travel_t('Abgeschlossen')); ?></option>
                        </select>

                        <select name="trip_country_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Länder')); ?></option>
                            <?php foreach ($countries as $country_option) : ?>
                                <option value="<?php echo esc_attr($country_option); ?>" <?php selected($trip_country_filter, $country_option); ?>>
                                    <?php echo esc_html(alpenia_country_to_display_language($country_option)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="trip_city_filter">
                            <option value=""><?php echo esc_html(alpenia_travel_t('Alle Städte')); ?></option>
                            <?php foreach ($cities as $city_option) : ?>
                                <option value="<?php echo esc_attr($city_option); ?>" <?php selected($trip_city_filter, $city_option); ?>>
                                    <?php echo esc_html($city_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) : ?>
                            <select name="guide_filter">
                                <option value=""><?php echo esc_html(alpenia_travel_t('Alle Reiseleiter')); ?></option>
                                <?php foreach ($guides as $guide) : ?>
                                    <option value="<?php echo esc_attr($guide->ID); ?>" <?php selected($guide_filter, $guide->ID); ?>>
                                        <?php echo esc_html($guide->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <select name="trips_per_page">
                            <option value="10" <?php selected($trips_per_page, 10); ?>>10 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                            <option value="20" <?php selected($trips_per_page, 20); ?>>20 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                            <option value="50" <?php selected($trips_per_page, 50); ?>>50 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                            <option value="100" <?php selected($trips_per_page, 100); ?>>100 <?php echo esc_html(alpenia_travel_t('pro Seite')); ?></option>
                        </select>

                        <button type="submit" class="btn-primary"><?php echo esc_html(alpenia_travel_t('Filtern')); ?></button>
                        <a href="<?php echo esc_url(alpenia_dashboard_link()); ?>" class="btn-secondary"><?php echo esc_html(alpenia_travel_t('Zurücksetzen')); ?></a>
                    </form>

                    <div class="list-summary">
                        <?php echo esc_html(sprintf(alpenia_travel_t('%1$d Reisen gefunden – Seite %2$d von %3$d'), $filtered_trips_total, $trip_page, $filtered_trips_max_pages)); ?>
                    </div>

                    <?php if ($filtered_trips) : ?>
                        <ul class="list-table">
                            <?php foreach ($filtered_trips as $trip) :
                                $trip_participants_count = alpenia_count_trip_participants($trip->ID);
                                $guide_id = (int) get_post_meta($trip->ID, 'assigned_guide', true);
                                $guide_name = $guide_id ? get_the_author_meta('display_name', $guide_id) : '-';
                                $delete_trip_nonce = wp_create_nonce('alpenia_delete_trip_' . $trip->ID);
                                $trip_capacity = (int) get_post_meta($trip->ID, 'max_people', true);
                                $trip_occupancy = $trip_capacity > 0 ? min(100, (int) round(($trip_participants_count / $trip_capacity) * 100)) : null;
                            ?>
                                <li class="trip-list-card">
                                    <div class="trip-list-card__content">
                                        <div class="trip-list-card__header">
                                            <strong><?php echo esc_html($trip->post_title); ?></strong>
                                            <div class="trip-list-card__badges">
                                                <?php echo wp_kses_post(alpenia_trip_status_badge(get_post_meta($trip->ID, 'trip_status', true))); ?>
                                                <?php echo wp_kses_post(alpenia_dashboard_trip_timeline_badge($trip->ID)); ?>
                                            </div>
                                        </div>

                                        <div class="trip-info-grid">
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Ziel')); ?></span>
                                                <strong><?php echo esc_html(alpenia_display_value(alpenia_destination_to_display_language(get_post_meta($trip->ID, 'destination', true)))); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Land / Stadt')); ?></span>
                                                <strong><?php echo esc_html(alpenia_display_value(alpenia_country_to_display_language(get_post_meta($trip->ID, 'country', true)))); ?> / <?php echo esc_html(alpenia_display_value(get_post_meta($trip->ID, 'city', true))); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Reiseart')); ?></span>
                                                <?php $trip_type_value = (string) get_post_meta($trip->ID, 'trip_type', true); ?>
                                                <strong><?php echo esc_html($trip_type_value === 'umrah' ? alpenia_travel_t('Umrah') : alpenia_display_value($trip_type_value)); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Freie Plätze')); ?></span>
                                                <strong><?php echo esc_html(alpenia_get_trip_capacity_left($trip->ID)); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Reiseleiter')); ?></span>
                                                <strong><?php echo esc_html($guide_name); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Abflug')); ?></span>
                                                <strong><?php echo esc_html(alpenia_display_value(get_post_meta($trip->ID, 'departure_city', true))); ?> · <?php echo esc_html(alpenia_display_value(get_post_meta($trip->ID, 'departure_airport', true))); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Start')); ?></span>
                                                <strong><?php echo esc_html(alpenia_format_date_display(get_post_meta($trip->ID, 'start_date', true))); ?></strong>
                                            </div>
                                            <div class="trip-info-item">
                                                <span><?php echo esc_html(alpenia_travel_t('Ende')); ?></span>
                                                <strong><?php echo esc_html(alpenia_format_date_display(get_post_meta($trip->ID, 'end_date', true))); ?></strong>
                                            </div>
                                        </div>
                                        <?php if ($trip_occupancy !== null) : ?>
                                            <div class="trip-progress" aria-label="<?php echo esc_attr(alpenia_travel_t('Auslastung')); ?>">
                                                <div class="trip-progress__meta">
                                                    <span><?php echo esc_html(alpenia_travel_t('Auslastung')); ?></span>
                                                    <strong><?php echo esc_html($trip_occupancy); ?>%</strong>
                                                </div>
                                                <div class="trip-progress__track">
                                                    <span class="trip-progress__fill" style="width: <?php echo esc_attr($trip_occupancy); ?>%"></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="list-actions trip-list-card__actions">
                                        <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $trip->ID])); ?>"><?php echo esc_html(alpenia_travel_t('Zur Reise')); ?></a>
                                        <?php if (alpenia_user_can_edit_trip($trip->ID)) : ?>
                                            <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_trip' => $trip->ID])); ?>"><?php echo esc_html(alpenia_travel_t('Bearbeiten')); ?></a>
                                        <?php endif; ?>
                                        <?php if (alpenia_user_can_delete_trip($trip->ID)) : ?>
                                            <a class="table-btn table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['delete_trip' => $trip->ID, '_delete_trip_nonce' => $delete_trip_nonce])); ?>" data-delete-type="trip"><?php echo esc_html(alpenia_travel_t('Löschen')); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php echo alpenia_dashboard_render_pagination($trip_page, $filtered_trips_max_pages, [
                            'trip_search' => $trip_search,
                            'trip_type_filter' => $trip_type_filter,
                            'trip_status_filter' => $trip_status_filter,
                            'trip_country_filter' => $trip_country_filter,
                            'trip_city_filter' => $trip_city_filter,
                            'guide_filter' => $guide_filter,
                            'trips_per_page' => $trips_per_page,
                        ], 'trip_page'); ?>
                    <?php else : ?>
                        <p><?php echo esc_html(alpenia_travel_t('Keine Reisen für diese Suche / Filter gefunden.')); ?></p>
                    <?php endif; ?>
                </div>

                <div class="overview-card-grid" aria-label="<?php echo esc_attr(alpenia_travel_t('Dashboard Übersichten')); ?>">
                    <section class="overview-card overview-card--documents">
                        <div class="overview-card__header">
                            <div>
                                <span class="overview-card__eyebrow"><?php echo esc_html(alpenia_travel_t('Nachfassen')); ?></span>
                                <h2><?php echo esc_html(alpenia_travel_t('Fehlende Unterlagen im Blick')); ?></h2>
                                <p class="overview-card__subtitle">
                                    <?php echo esc_html(sprintf(
                                        alpenia_travel_t('%1$s · %2$s fehlen'),
                                        alpenia_dashboard_participant_count_label($missing_docs_count),
                                        alpenia_dashboard_count_label($missing_docs_total_count, 'Unterlage', 'Unterlagen')
                                    )); ?>
                                </p>
                            </div>
                            <div class="overview-card__metric" aria-label="<?php echo esc_attr(alpenia_travel_t('Fehlende Unterlagen')); ?>">
                                <span class="overview-card__count"><?php echo esc_html($missing_docs_total_count); ?></span>
                                <span><?php echo esc_html(alpenia_travel_t($missing_docs_total_count === 1 ? 'Unterlage' : 'Unterlagen')); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($missing_docs_items)) : ?>
                            <div class="overview-list overview-list--compact">
                                <?php foreach ($missing_docs_items as $item) : ?>
                                    <?php
                                    $missing_doc_labels = !empty($item['missing_docs']) ? array_map('alpenia_travel_t', $item['missing_docs']) : [];
                                    $visible_missing_doc_labels = array_slice($missing_doc_labels, 0, 3);
                                    $remaining_missing_doc_count = max(0, count($missing_doc_labels) - count($visible_missing_doc_labels));
                                    ?>
                                    <article class="overview-list-card overview-list-card--compact overview-list-card--actionable">
                                        <div class="overview-list-card__main">
                                            <span class="overview-list-card__trip"><?php echo esc_html($item['trip_title']); ?></span>
                                            <div class="overview-list-card__title-row">
                                                <h3><?php echo esc_html($item['participant_name']); ?></h3>
                                                <span class="overview-list-card__status"><?php echo esc_html(alpenia_dashboard_missing_docs_status_label(count($missing_doc_labels))); ?></span>
                                            </div>
                                            <div class="overview-chip-list" aria-label="<?php echo esc_attr(alpenia_travel_t('Fehlende Unterlagen')); ?>">
                                                <?php foreach ($visible_missing_doc_labels as $missing_doc_label) : ?>
                                                    <span class="overview-chip overview-chip--danger"><?php echo esc_html($missing_doc_label); ?></span>
                                                <?php endforeach; ?>
                                                <?php if ($remaining_missing_doc_count > 0) : ?>
                                                    <span class="overview-chip overview-chip--muted"><?php echo esc_html(sprintf(alpenia_travel_t('+%d weitere'), $remaining_missing_doc_count)); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="overview-list-card__actions">
                                            <a class="table-btn overview-list-card__primary-action" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $item['participant_id']])); ?>"><?php echo esc_html(alpenia_travel_t('Zum Teilnehmer')); ?></a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($missing_docs_count > count($missing_docs_items)) : ?>
                                <p class="overview-card__hint">
                                    <?php echo esc_html(sprintf(
                                        alpenia_travel_t('%1$s von %2$s sichtbar. Öffne den Teilnehmer direkt über die Karte.'),
                                        alpenia_dashboard_participant_count_label(count($missing_docs_items)),
                                        alpenia_dashboard_participant_count_label($missing_docs_count)
                                    )); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="overview-empty-state">
                                <strong><?php echo esc_html(alpenia_travel_t('Aktuell keine fehlenden Unterlagen.')); ?></strong>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="overview-card overview-card--payments">
                        <div class="overview-card__header">
                            <div>
                                <span class="overview-card__eyebrow"><?php echo esc_html(alpenia_travel_t('Nachfassen')); ?></span>
                                <h2><?php echo esc_html(alpenia_travel_t('Offene Zahlungen im Blick')); ?></h2>
                                <p class="overview-card__subtitle">
                                    <?php echo esc_html(sprintf(
                                        alpenia_travel_t('%1$s · %2$s offen'),
                                        alpenia_dashboard_participant_count_label($open_payments_count),
                                        alpenia_dashboard_money_label($open_payments_total_amount)
                                    )); ?>
                                </p>
                            </div>
                            <div class="overview-card__metric" aria-label="<?php echo esc_attr(alpenia_travel_t('Offene Zahlungen')); ?>">
                                <span class="overview-card__amount"><?php echo esc_html(alpenia_dashboard_money_label($open_payments_total_amount)); ?></span>
                                <span><?php echo esc_html(alpenia_travel_t('offen')); ?></span>
                            </div>
                        </div>

                        <?php if (!empty($open_payments_items)) : ?>
                            <div class="overview-list overview-list--compact">
                                <?php foreach ($open_payments_items as $item) : ?>
                                    <article class="overview-list-card overview-list-card--compact overview-list-card--actionable">
                                        <div class="overview-list-card__main">
                                            <span class="overview-list-card__trip"><?php echo esc_html($item['trip_title']); ?></span>
                                            <div class="overview-list-card__title-row">
                                                <h3><?php echo esc_html($item['participant_name']); ?></h3>
                                                <span class="overview-list-card__status overview-list-card__status--payment"><?php echo esc_html(alpenia_dashboard_money_label($item['payment_open'])); ?> <?php echo esc_html(alpenia_travel_t('offen')); ?></span>
                                            </div>
                                            <p class="overview-list-card__meta"><?php echo esc_html(alpenia_travel_t('Zahlungsstatus')); ?>: <?php echo esc_html(alpenia_travel_translate_label($item['payment_status'])); ?></p>
                                        </div>
                                        <div class="overview-list-card__actions">
                                            <a class="table-btn overview-list-card__primary-action" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $item['participant_id']])); ?>"><?php echo esc_html(alpenia_travel_t('Zum Teilnehmer')); ?></a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($open_payments_count > count($open_payments_items)) : ?>
                                <p class="overview-card__hint">
                                    <?php echo esc_html(sprintf(
                                        alpenia_travel_t('%1$s von %2$s sichtbar. Öffne den Teilnehmer direkt über die Karte.'),
                                        alpenia_dashboard_participant_count_label(count($open_payments_items)),
                                        alpenia_dashboard_participant_count_label($open_payments_count)
                                    )); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="overview-empty-state">
                                <strong><?php echo esc_html(alpenia_travel_t('Aktuell keine offenen Zahlungen.')); ?></strong>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="overview-card overview-card--participants">
                        <div class="overview-card__header">
                            <div>
                                <span class="overview-card__eyebrow"><?php echo esc_html(alpenia_travel_t('Aktivität')); ?></span>
                                <h2><?php echo esc_html(alpenia_travel_t('Letzte Teilnehmer')); ?></h2>
                            </div>
                            <span class="overview-card__count"><?php echo esc_html(count($latest_participants)); ?></span>
                        </div>

                        <?php if ($latest_participants) : ?>
                            <div class="participant-card-list">
                                <?php foreach ($latest_participants as $participant) :
                                    $trip_id = (int) get_post_meta($participant->ID, 'trip_id', true);
                                    $gender = get_post_meta($participant->ID, 'gender', true);
                                ?>
                                    <article class="participant-mini-card">
                                        <div class="participant-mini-card__avatar" aria-hidden="true"><?php echo esc_html(substr(trim($participant->post_title), 0, 1)); ?></div>
                                        <div class="participant-mini-card__content">
                                            <h3><?php echo esc_html(trim($gender . ' ' . $participant->post_title)); ?></h3>
                                            <p><?php echo $trip_id ? esc_html(get_the_title($trip_id)) : esc_html(alpenia_travel_t('Keine Reise')); ?></p>
                                            <?php echo wp_kses_post(alpenia_get_participant_doc_badge($participant->ID)); ?>
                                            <div class="participant-mini-card__actions">
                                                <?php if ($trip_id) : ?>
                                                    <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $trip_id])); ?>"><?php echo esc_html(alpenia_travel_t('Reise öffnen')); ?></a>
                                                <?php endif; ?>
                                                <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $participant->ID])); ?>"><?php echo esc_html(alpenia_travel_t('Teilnehmer öffnen')); ?></a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="overview-empty-state">
                                <strong><?php echo esc_html(alpenia_travel_t('Noch keine Teilnehmer vorhanden.')); ?></strong>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>


            <?php endif; ?>

                </main>
            </div>

        </div>
    </div>

    <style>
        html, body, .site, .site-main, .entry-content, .content-area, .elementor, .elementor-section, .elementor-container, .elementor-widget-wrap {
            background: linear-gradient(150deg, #f5f5f3 0%, #efefea 45%, #eaeae6 100%) !important;
            overflow-x: hidden !important;
        }

        body.page, body.logged-in {
            background: linear-gradient(150deg, #f5f5f3 0%, #efefea 45%, #eaeae6 100%) !important;
        }

        .alpenia-dashboard-shell {
            width: 100vw;
            max-width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            margin-top: 0 !important;
            padding-top: 0 !important;
            background: linear-gradient(150deg, #f5faf8 0%, #edf6f2 45%, #e8f2ee 100%);
            overflow-x: hidden;
            position: relative;
            isolation: isolate;
        }

        .alpenia-dashboard-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(circle at 8% 15%, rgba(31, 122, 99, 0.14), rgba(31, 122, 99, 0) 42%),
                radial-gradient(circle at 90% 8%, rgba(43, 212, 163, 0.14), rgba(43, 212, 163, 0) 38%);
            pointer-events: none;
        }

        .alpenia-dashboard {
            width: 100%;
            max-width: none;
            background: transparent;
            color: #fff;
            padding: 24px 40px 40px 40px;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }

        .alpenia-dashboard *,
        .alpenia-dashboard *::before,
        .alpenia-dashboard *::after {
            box-sizing: border-box;
        }

        .alpenia-message,
        .alpenia-success {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alpenia-message { background: #3a1212; color: #fff; }
        .alpenia-success { background: #123a24; color: #fff; }

        .alpenia-delete-popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 16, 26, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: alpeniaPopupFade 180ms ease-out;
        }

        .alpenia-delete-popup-dialog {
            background: linear-gradient(160deg, #ffffff 0%, #f5faf8 100%);
            color: #17362d;
            border: 1px solid rgba(30, 94, 73, 0.15);
            border-radius: 18px;
            padding: 24px;
            width: min(440px, 100%);
            box-shadow: 0 24px 60px rgba(7, 16, 26, 0.28);
            text-align: center;
            animation: alpeniaPopupRise 240ms ease-out;
        }

        .alpenia-delete-popup-badge {
            width: 44px;
            height: 44px;
            margin: 0 auto 12px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            background: linear-gradient(135deg, #1e7a5f, #2aa17d);
            box-shadow: 0 8px 18px rgba(30, 122, 95, 0.35);
        }

        .alpenia-delete-popup-title {
            margin: 0 0 8px;
            font-size: 24px;
            line-height: 1.2;
            color: #103d30;
        }

        .alpenia-delete-popup-text {
            margin: 0 0 18px;
            font-size: 15px;
            color: #355b50;
            font-weight: 500;
        }


        .alpenia-delete-popup-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }

        .alpenia-delete-popup-actions .btn-primary,
        .alpenia-delete-popup-actions .btn-secondary {
            min-width: 140px;
        }

        @keyframes alpeniaPopupFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes alpeniaPopupRise {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }


        .dashboard-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 0 !important;
            margin-bottom: 30px;
        }

        .dashboard-brand {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .dashboard-logo {
            width: 72px;
            height: auto;
            display: block;
            object-fit: contain;
            border-radius: 12px;
        }

        .dashboard-brand-text {
            display: block;
        }

        .dashboard-brand-text h1 {
            font-size: 42px;
            margin: 0;
            line-height: 1.1;
            color: #0f3d2e;
        }

        .dashboard-brand-text p {
            color: #1f4f3f;
            margin: 8px 0 0;
            font-size: 16px;
            font-weight: 700;
        }

        .alpenia-dashboard-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            align-items: flex-start;
        }

        .alpenia-dashboard-content {
            min-width: 0;
        }

        .dashboard-sidebar {
            position: sticky;
            top: 18px;
            overflow: hidden;
            background:
                radial-gradient(circle at 16% 0%, rgba(217, 154, 43, 0.2), transparent 30%),
                linear-gradient(145deg, rgba(255,255,255,0.96), rgba(244,251,248,0.95));
            border: 1px solid rgba(23, 75, 61, 0.14);
            border-radius: 28px;
            padding: 16px;
            box-shadow: 0 24px 58px rgba(16, 37, 31, 0.14);
        }

        .dashboard-sidebar::after {
            content: "";
            position: absolute;
            right: -42px;
            bottom: -52px;
            width: 150px;
            height: 150px;
            border: 1px solid rgba(217, 154, 43, 0.18);
            border-radius: 50%;
            pointer-events: none;
        }

        .dashboard-sidebar__header {
            position: relative;
            z-index: 1;
            margin-bottom: 14px;
            padding: 16px;
            border-radius: 22px;
            background: linear-gradient(135deg, #123f34 0%, #22634f 58%, #2f7d63 100%);
            color: #ffffff;
            box-shadow: 0 16px 34px rgba(16, 37, 31, 0.16);
        }

        .dashboard-sidebar__header strong {
            display: block;
            margin-top: 5px;
            color: #ffffff;
            font-size: 18px;
            line-height: 1.2;
        }

        .dashboard-sidebar__header--compact {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
            padding: 14px 16px;
        }

        .dashboard-sidebar__eyebrow {
            color: rgba(255,255,255,0.74);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .dashboard-sidebar__nav {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 10px;
        }
        .dashboard-sidebar__support {
            position: relative;
            z-index: 1;
            margin-top: 14px;
            padding: 12px;
            border-radius: 16px;
            border: 1px solid rgba(23, 75, 61, 0.14);
            background: rgba(255,255,255,0.86);
            display: grid;
            gap: 8px;
        }
        .dashboard-sidebar__support-eyebrow {
            color: #698176;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .dashboard-sidebar__support strong {
            color: #123f34;
            font-size: 14px;
            line-height: 1.2;
        }
        .dashboard-sidebar__support-actions {
            display: grid;
            gap: 6px;
        }

        .dashboard-sidebar__link {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr);
            align-items: center;
            gap: 12px;
            min-height: 64px;
            padding: 10px;
            border-radius: 18px;
            background: rgba(255,255,255,0.74);
            border: 1px solid rgba(23, 75, 61, 0.1);
            color: #123f34 !important;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(16, 37, 31, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }

        .dashboard-sidebar__icon {
            display: inline-grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: #f4fbf8;
            color: #1d4d3f;
            font-size: 20px;
            font-weight: 900;
            border: 1px solid rgba(47, 125, 99, 0.12);
        }

        .dashboard-sidebar__copy {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .dashboard-sidebar__copy span {
            color: #698176;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .dashboard-sidebar__copy strong {
            color: #123f34;
            font-size: 15px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .dashboard-sidebar__link:hover,
        .dashboard-sidebar__link:focus,
        .dashboard-sidebar__link.is-active {
            transform: translateY(-1px);
            background: linear-gradient(135deg, #fff9ed, #ffffff);
            border-color: rgba(217, 154, 43, 0.42);
            box-shadow: 0 16px 34px rgba(123, 90, 32, 0.12);
            color: #123f34 !important;
            outline: none;
        }

        .dashboard-sidebar__link.is-active .dashboard-sidebar__icon {
            background: linear-gradient(135deg, #d99a2b, #f1c66a);
            color: #123f34;
            border-color: rgba(217, 154, 43, 0.48);
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .actions-main {
            align-items: center;
        }

        .btn-primary,
        .btn-secondary,
        .table-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: 0.2s ease;
            line-height: 1.2;
            white-space: nowrap;
        }
        .alpenia-dashboard a,
        .alpenia-dashboard button {
            text-decoration: none !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1d4d3f, #2d6a57);
            color: #fff;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #245845, #2f7460);
            color: #fff;
            border: 1px solid rgba(167, 197, 184, 0.45);
        }

        .panel.public-registration-panel {
            margin-top: 20px;
            overflow: hidden;
            border: 1px solid rgba(167, 197, 184, 0.34);
            background:
                radial-gradient(circle at 100% 0%, rgba(217, 154, 43, 0.13), transparent 32%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(244, 251, 248, 0.96));
        }

        .users-panel {
            border: 1px solid rgba(132, 167, 154, 0.32);
            background: linear-gradient(135deg, #21453c, #214f41 50%, #1f483e);
            box-shadow: 0 14px 36px rgba(7, 30, 24, 0.25);
        }

        .users-panel__header {
            margin-bottom: 18px;
        }

        .users-panel__header h2 {
            margin: 0;
            color: #f3f8f6;
            font-size: 30px;
            line-height: 1.2;
        }

        .users-panel__header p {
            margin: 8px 0 0;
            color: rgba(223, 241, 234, 0.86);
            font-weight: 600;
            font-size: 15px;
        }

        .users-panel__header--inline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .users-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(184, 223, 206, 0.3);
            background: rgba(14, 54, 45, 0.6);
            color: #ecf8f4;
            font-weight: 800;
        }

        .users-panel__footer {
            margin-top: 12px;
        }

        .users-panel .form-group label {
            color: #f2f9f5;
            font-weight: 800;
        }

        .users-panel .form-group input:not([type="file"]),
        .users-panel .form-group select {
            background: linear-gradient(90deg, #072a23, #073127);
            color: #ecf8f4 !important;
            border: 1px solid rgba(93, 158, 137, 0.42);
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.02), 0 8px 16px rgba(5, 24, 20, 0.32);
        }

        .users-panel .form-group input:not([type="file"]):focus,
        .users-panel .form-group select:focus {
            border-color: #61b89a;
            box-shadow: 0 0 0 3px rgba(97, 184, 154, 0.25);
        }

        .users-panel .form-group input::placeholder {
            color: rgba(206, 226, 218, 0.55);
        }

        .users-panel .btn-primary {
            background: linear-gradient(135deg, #256b56, #2c7f66);
            box-shadow: 0 12px 20px rgba(7, 28, 22, 0.38);
        }

        .users-panel .btn-primary:hover,
        .users-panel .btn-primary:focus {
            transform: translateY(-1px);
        }

        .users-table thead th {
            background: rgba(15, 62, 50, 0.88);
            color: #f0f8f4;
            font-weight: 800;
            border-bottom: 1px solid rgba(146, 194, 176, 0.35);
        }

        .users-table tbody td {
            color: #f2f8f6;
            background: rgba(28, 78, 65, 0.42);
            border-bottom: 1px solid rgba(120, 169, 151, 0.2);
        }

        .users-table tbody tr:hover td {
            background: rgba(40, 95, 81, 0.56);
        }

        .users-role-pill,
        .users-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .users-role-pill {
            color: #e8eef8;
            background: rgba(76, 111, 174, 0.34);
            border: 1px solid rgba(125, 153, 205, 0.4);
        }

        .users-role-pill--admin {
            color: #fff2d7;
            background: rgba(161, 111, 36, 0.42);
            border: 1px solid rgba(214, 161, 79, 0.44);
        }

        .users-role-pill--reiseleiter {
            color: #d8f4ff;
            background: rgba(41, 106, 154, 0.42);
            border: 1px solid rgba(95, 152, 194, 0.44);
        }

        .users-role-pill--backoffice {
            color: #eee3ff;
            background: rgba(97, 67, 150, 0.42);
            border: 1px solid rgba(145, 113, 204, 0.44);
        }

        .users-status-pill.is-active {
            color: #d8f1ff;
            background: rgba(38, 111, 156, 0.35);
            border: 1px solid rgba(86, 150, 191, 0.4);
        }

        .users-status-pill.is-disabled {
            color: #ffd7d7;
            background: rgba(158, 55, 55, 0.44);
            border: 1px solid rgba(201, 111, 111, 0.36);
        }

        .user-action-links a,
        .user-action-links .link-button {
            color: #95e4c6;
            font-weight: 700;
        }

        .user-action-links .link-button {
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
            font: inherit;
        }

        .user-action-links .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            text-decoration: none !important;
            line-height: 1.2;
        }

        .user-action-links .edit-button {
            background: #2e5f9b;
            color: #eff7ff;
            border-color: #4a7bb9;
        }

        .user-action-links .activate-button {
            background: #1c8f6a;
            color: #e9fff8;
            border-color: #44b792;
        }

        .user-action-links .deactivate-button {
            background: #b43434;
            color: #fff0f0;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #d46a6a;
            text-decoration: none !important;
        }

        .user-action-links .delete-button {
            background: #b43434;
            color: #fff0f0;
            border-color: #d46a6a;
        }

        .user-action-links a:hover,
        .user-action-links a:focus,
        .user-action-links .link-button:hover,
        .user-action-links .link-button:focus {
            color: #b6f4db;
            text-decoration: underline !important;
        }

        .user-action-links .action-button:hover,
        .user-action-links .action-button:focus {
            text-decoration: none !important;
        }

        .user-action-links .activate-button:hover,
        .user-action-links .activate-button:focus {
            color: #fff;
            background: #157254;
            border-color: #359f7e;
            text-decoration: none !important;
            outline: none;
        }

        .user-action-links .edit-button:hover,
        .user-action-links .edit-button:focus {
            color: #fff;
            background: #244d80;
            border-color: #3d699f;
            text-decoration: none !important;
            outline: none;
        }

        .user-action-links .deactivate-button:hover,
        .user-action-links .deactivate-button:focus {
            color: #fff;
            background: #962a2a;
            border-color: #bd5353;
            text-decoration: none !important;
            outline: none;
        }

        .user-action-links .delete-button:hover,
        .user-action-links .delete-button:focus {
            color: #fff;
            background: #962a2a;
            border-color: #bd5353;
            text-decoration: none !important;
            outline: none;
        }

        .public-registration-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 10px;
        }

        .panel.public-registration-panel .public-registration-panel__header h2 {
            margin: 4px 0 0;
            color: #123f34;
        }

        .public-registration-panel__eyebrow,
        .public-registration-panel__badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .public-registration-panel__eyebrow {
            color: #123f34;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(47, 125, 99, 0.24);
            padding: 7px 11px;
        }

        .public-registration-panel__badge {
            flex: 0 0 auto;
            padding: 8px 12px;
            background: #fff3d6;
            color: #8a5a0a;
            border: 1px solid rgba(217, 154, 43, 0.35);
        }

        .public-registration-panel__text {
            max-width: 780px;
            margin: 0 0 16px;
            color: #123f34;
            font-weight: 700;
            line-height: 1.6;
        }

        .public-registration-card {
            padding: 16px;
            border: 1px solid rgba(167, 197, 184, 0.5);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(16, 37, 31, 0.07);
        }

        .public-registration-card__label {
            display: block;
            margin-bottom: 8px;
            color: #123f34;
            font-weight: 900;
        }

        .public-registration-link {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: stretch;
            min-width: 0;
        }

        .public-registration-link__input {
            width: 100%;
            min-width: 0;
            min-height: 52px;
            border-radius: 14px;
            border: 1px solid rgba(167, 197, 184, 0.7);
            padding: 0 16px;
            color: #123f34;
            background: #fbfdfc;
            font: inherit;
            font-weight: 700;
            box-shadow: inset 0 1px 0 rgba(16, 37, 31, 0.04);
        }

        .public-registration-link__input:focus {
            outline: none;
            border-color: #2f7460;
            box-shadow: 0 0 0 4px rgba(47, 116, 96, 0.16);
        }

        .public-registration-link__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .public-registration-link__copy,
        .public-registration-link__open {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            padding: 0 18px;
            border-radius: 14px;
            text-align: center;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .public-registration-link {
                grid-template-columns: 1fr;
            }

            .public-registration-link__actions {
                justify-content: stretch;
            }

            .public-registration-link__copy,
            .public-registration-link__open {
                flex: 1 1 180px;
            }
        }

        @media (max-width: 520px) {
            .public-registration-panel__header {
                flex-direction: column;
            }

            .public-registration-card {
                padding: 12px;
                border-radius: 16px;
            }

            .public-registration-link__actions {
                flex-direction: column;
            }

            .public-registration-link__copy,
            .public-registration-link__open {
                width: 100%;
                min-height: 50px;
            }
        }

        .dashboard-language-switch {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 56, 45, 0.88);
            border: 1px solid rgba(167, 197, 184, 0.4);
            border-radius: 999px;
            padding: 6px 8px;
            backdrop-filter: blur(6px);
        }

        .lang-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            height: 36px;
            padding: 0 10px;
            border-radius: 999px;
            color: #ffffff !important;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1;
            text-decoration: none;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
        }

        .lang-link:hover,
        .lang-link:focus,
        .lang-link:active {
            background: rgba(255,255,255,0.24);
            transform: translateY(-1px);
            outline: none;
        }

        
        .lang-link.active {
            box-shadow: 0 0 0 2px rgba(43,212,163,0.7);
        }

        @media print {
            a[href]::after { content: "" !important; }
        }
        .btn-logout {
            background: linear-gradient(135deg, #a12626, #c94a4a) !important;
            color: #fff !important;
        }

        .table-btn {
            background: linear-gradient(135deg, #1d4d3f, #2d6a57);
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            min-height: 40px;
        }

        .table-btn-danger {
            background: rgba(130, 25, 25, 0.7) !important;
            color: #fff !important;
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active,
        .table-btn:hover,
        .table-btn:focus,
        .table-btn:active,
        .btn-secondary:hover,
        .btn-secondary:focus,
        .btn-secondary:active {
            color: #fff !important;
            text-decoration: none !important;
            outline: none;
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active { background: linear-gradient(135deg, #163e32, #225a49); }
        .btn-secondary:hover,
        .btn-secondary:focus,
        .btn-secondary:active { background: linear-gradient(135deg, #1d4a3a, #275f4e); }
        .btn-logout:hover,
        .btn-logout:focus,
        .btn-logout:active,
        .table-btn-danger:hover,
        .table-btn-danger:focus,
        .table-btn-danger:active { background: linear-gradient(135deg, #8f1d1d, #af2f2f) !important; }
        a[href*="export_trip_csv"].btn-primary { background: linear-gradient(135deg, #0f7566, #15967f); }
        a[href*="export_trip_csv"].btn-primary:hover,
        a[href*="export_trip_csv"].btn-primary:focus { background: linear-gradient(135deg, #0c6356, #117a67); }
        a[href*="print_trip"].btn-primary { background: linear-gradient(135deg, #3557b7, #4f74db); }
        a[href*="print_trip"].btn-primary:hover,
        a[href*="print_trip"].btn-primary:focus { background: linear-gradient(135deg, #2c4aa0, #4366c8); }
        a[href*="delete_trip"].btn-secondary,
        a[href*="delete_participant"].table-btn-danger,
        .delete-link { color: #ffd9d9 !important; }
        a[href*="view_trip"] .table-btn,
        a[href*="edit_participant"] .table-btn { background: linear-gradient(135deg, #1d4d3f, #2d6a57); }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .card {
            min-width: 0;
            background: rgba(29,77,63,0.88);
            padding: 20px;
            border-radius: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(120,180,150,0.26);
        }

        .card h3 {
            margin: 0 0 10px;
            color: #ffffff;
        }

        .card span {
            font-size: 36px;
            font-weight: 700;
            color: #ffffff !important;
            opacity: 1 !important;
        }

        .growth-overview {
            position: relative;
            overflow: hidden;
            margin: 0 0 30px;
            padding: 24px;
            border: 1px solid rgba(217, 154, 43, 0.25);
            border-radius: 26px;
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.97), rgba(246, 251, 248, 0.98)),
                radial-gradient(circle at top right, rgba(217, 154, 43, 0.22), transparent 34%);
            box-shadow: 0 22px 55px rgba(10, 37, 30, 0.16);
        }

        .growth-overview::before {
            content: "";
            position: absolute;
            inset: auto -80px -120px auto;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(47, 125, 99, 0.13);
            pointer-events: none;
        }

        .growth-overview__header {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .growth-overview__header h2 {
            margin: 0;
            color: #0b3329;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 900;
            line-height: 1.08;
        }

        .growth-overview__header p {
            max-width: 620px;
            margin: 8px 0 0;
            color: #466357;
            font-weight: 700;
            line-height: 1.45;
        }

        .growth-kpi-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .growth-kpi {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(47, 125, 99, 0.14);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 12px 30px rgba(16, 37, 31, 0.07);
        }

        .growth-kpi span {
            color: #617a70;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .growth-kpi strong {
            color: #123f34;
            font-size: clamp(26px, 3vw, 36px);
            font-weight: 900;
            line-height: 1;
        }

        .growth-kpi small {
            color: #466357;
            font-weight: 800;
            line-height: 1.35;
        }

        .growth-kpi--positive {
            border-color: rgba(47, 125, 99, 0.24);
            background: #effaf5;
        }

        .growth-kpi--warning {
            border-color: rgba(217, 154, 43, 0.3);
            background: #fff8ea;
        }

        .growth-kpi--danger {
            border-color: rgba(176, 62, 62, 0.2);
            background: #fff3f1;
        }

        .growth-insight-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .growth-insight {
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(47, 125, 99, 0.14);
            border-radius: 20px;
            background: rgba(246, 252, 249, 0.9);
        }

        .growth-insight h3 {
            margin: 0 0 12px;
            color: #123f34;
            font-size: 20px;
            font-weight: 900;
        }

        .growth-insight p {
            margin: 0;
            color: #466357;
            font-weight: 800;
        }

        .growth-rank-list,
        .growth-action-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding-left: 20px;
        }

        .growth-action-list {
            padding-left: 0;
            list-style: none;
        }

        .growth-rank-list li,
        .growth-action-list li {
            display: grid;
            gap: 4px;
            color: #466357;
            font-weight: 800;
        }

        .growth-rank-list a,
        .growth-action-list a {
            color: #123f34;
            font-weight: 900;
            text-decoration: none;
        }

        .growth-rank-list a:hover,
        .growth-action-list a:hover {
            text-decoration: underline;
        }

        .finance-overview {
            position: relative;
            overflow: hidden;
            margin: 0 0 30px;
            padding: 24px;
            border: 1px solid rgba(120,180,150,0.24);
            border-radius: 26px;
            background:
                linear-gradient(145deg, rgba(15, 55, 45, 0.97), rgba(18, 75, 61, 0.95)),
                radial-gradient(circle at top right, rgba(217, 154, 43, 0.18), transparent 36%);
            color: #ffffff;
            box-shadow: 0 22px 55px rgba(10, 37, 30, 0.18);
        }

        .finance-overview__header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .finance-overview__header h2 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 900;
            line-height: 1.08;
        }

        .finance-overview__header p {
            max-width: 720px;
            margin: 8px 0 0;
            color: #d6eee4;
            font-weight: 700;
            line-height: 1.45;
        }

        .finance-rate {
            display: grid;
            place-items: center;
            min-width: 128px;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 22px;
            background: rgba(255,255,255,0.1);
            text-align: center;
        }

        .finance-rate strong {
            color: #ffffff;
            font-size: 38px;
            font-weight: 900;
            line-height: 1;
        }

        .finance-rate span {
            margin-top: 6px;
            color: #d6eee4;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
        }

        .finance-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .finance-summary-card {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 18px;
            background: rgba(255,255,255,0.09);
        }

        .finance-summary-card span {
            color: #d6eee4;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .finance-summary-card strong {
            color: #ffffff;
            font-size: clamp(22px, 2.2vw, 30px);
            font-weight: 900;
            line-height: 1.05;
        }

        .finance-summary-card--paid {
            background: rgba(81, 190, 137, 0.16);
        }

        .finance-summary-card--open {
            background: rgba(217, 154, 43, 0.18);
        }

        .finance-progress,
        .finance-mini-progress {
            overflow: hidden;
            height: 10px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
        }

        .finance-progress {
            margin-bottom: 18px;
        }

        .finance-progress span,
        .finance-mini-progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #7dd3a8, #d99a2b);
        }

        .finance-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 14px;
        }

        .finance-detail-card {
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 20px;
            background: rgba(255,255,255,0.92);
            color: #123f34;
        }

        .finance-detail-card h3 {
            margin: 0 0 14px;
            color: #123f34;
            font-size: 20px;
            font-weight: 900;
        }

        .finance-detail-card p {
            margin: 0;
            color: #466357;
            font-weight: 800;
        }

        .finance-trip-list,
        .finance-priority-list {
            display: grid;
            gap: 10px;
        }

        .finance-trip-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px 14px;
            align-items: center;
            padding: 14px;
            border: 1px solid rgba(47, 125, 99, 0.13);
            border-left: 5px solid #d99a2b;
            border-radius: 16px;
            background: #f8fcfa;
        }

        .finance-trip-row.is-paid { border-left-color: #2f7d63; }
        .finance-trip-row.is-watch { border-left-color: #d99a2b; }
        .finance-trip-row.is-risk { border-left-color: #b03e3e; }

        .finance-trip-row__main,
        .finance-trip-row__amount {
            display: grid;
            gap: 4px;
        }

        .finance-trip-row__main a,
        .finance-priority-item a {
            color: #123f34;
            font-weight: 900;
            text-decoration: none;
        }

        .finance-trip-row__main a:hover,
        .finance-priority-item a:hover {
            text-decoration: underline;
        }

        .finance-trip-row__main span,
        .finance-trip-row__amount span,
        .finance-priority-item span {
            color: #617a70;
            font-size: 13px;
            font-weight: 800;
        }

        .finance-trip-row__amount {
            text-align: right;
        }

        .finance-trip-row__amount strong,
        .finance-priority-item strong {
            color: #7a4b0c;
            font-size: 18px;
            font-weight: 900;
        }

        .finance-mini-progress {
            grid-column: 1 / -1;
            height: 8px;
            background: rgba(47, 125, 99, 0.11);
        }

        .finance-priority-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px solid rgba(217, 154, 43, 0.2);
            border-radius: 16px;
            background: #fff8ea;
        }

        .finance-priority-item > div {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .operations-overview {
            position: relative;
            overflow: hidden;
            margin: 0 0 30px;
            padding: 24px;
            border: 1px solid rgba(217, 154, 43, 0.22);
            border-radius: 26px;
            background:
                linear-gradient(145deg, rgba(255,255,255,0.98), rgba(249,251,247,0.98)),
                radial-gradient(circle at top left, rgba(47, 125, 99, 0.14), transparent 34%);
            color: #123f34;
            box-shadow: 0 22px 55px rgba(10, 37, 30, 0.14);
        }

        .operations-overview__header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .operations-overview__header h2 {
            margin: 0;
            color: #0b3329;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 900;
            line-height: 1.08;
        }

        .operations-overview__header p {
            max-width: 720px;
            margin: 8px 0 0;
            color: #466357;
            font-weight: 700;
            line-height: 1.45;
        }

        .operations-workload {
            display: grid;
            place-items: center;
            min-width: 150px;
            padding: 16px;
            border: 1px solid rgba(217, 154, 43, 0.24);
            border-radius: 22px;
            background: #fff8ea;
            text-align: center;
        }

        .operations-workload strong {
            color: #7a4b0c;
            font-size: 38px;
            font-weight: 900;
            line-height: 1;
        }

        .operations-workload span {
            margin-top: 6px;
            color: #5f3e05;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .operations-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .operations-status-grid article {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(47, 125, 99, 0.14);
            border-radius: 18px;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 12px 30px rgba(16, 37, 31, 0.06);
        }

        .operations-status-grid span {
            color: #617a70;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .operations-status-grid strong {
            color: #123f34;
            font-size: clamp(24px, 2.5vw, 34px);
            font-weight: 900;
            line-height: 1;
        }

        .operations-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .operations-card {
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(47, 125, 99, 0.14);
            border-radius: 20px;
            background: rgba(248,252,250,0.92);
        }

        .operations-card--wide {
            grid-column: 1 / -1;
        }

        .operations-card h3 {
            margin: 0 0 14px;
            color: #123f34;
            font-size: 20px;
            font-weight: 900;
        }

        .operations-card p {
            margin: 0;
            color: #466357;
            font-weight: 800;
            line-height: 1.45;
        }

        .operations-bottleneck-list,
        .operations-trip-list,
        .operations-task-list {
            display: grid;
            gap: 10px;
        }

        .operations-bottleneck-row,
        .operations-trip-row,
        .operations-task-item {
            padding: 14px;
            border: 1px solid rgba(47, 125, 99, 0.13);
            border-radius: 16px;
            background: #ffffff;
        }

        .operations-bottleneck-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .operations-bottleneck-row span,
        .operations-trip-row span,
        .operations-task-item span {
            color: #617a70;
            font-size: 13px;
            font-weight: 800;
        }

        .operations-bottleneck-row strong {
            display: inline-grid;
            place-items: center;
            min-width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #fff1f1;
            color: #8f2424;
            font-weight: 900;
        }

        .operations-trip-row {
            display: grid;
            gap: 5px;
        }

        .operations-trip-row a,
        .operations-task-item a:not(.table-btn) {
            color: #123f34;
            font-weight: 900;
            text-decoration: none;
        }

        .operations-trip-row a:hover,
        .operations-task-item a:not(.table-btn):hover {
            text-decoration: underline;
        }

        .operations-task-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
        }

        .operations-task-item > div:first-child {
            display: grid;
            gap: 4px;
        }

        .operations-task-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .analysis-overview {
            position: relative;
            overflow: hidden;
            margin: 0 0 30px;
            padding: 24px;
            border: 1px solid rgba(120,180,150,0.24);
            border-radius: 26px;
            background:
                linear-gradient(145deg, rgba(16,36,29,0.96), rgba(25,76,61,0.95)),
                radial-gradient(circle at top right, rgba(126, 211, 168, 0.18), transparent 38%);
            color: #ffffff;
            box-shadow: 0 22px 55px rgba(10, 37, 30, 0.18);
        }

        .analysis-overview__header {
            margin-bottom: 20px;
        }

        .analysis-overview__header h2 {
            margin: 0;
            color: #ffffff;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 900;
            line-height: 1.08;
        }

        .analysis-overview__header p {
            max-width: 760px;
            margin: 8px 0 0;
            color: #d6eee4;
            font-weight: 700;
            line-height: 1.45;
        }

        .analysis-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .analysis-kpi-grid article {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 18px;
            background: rgba(255,255,255,0.09);
        }

        .analysis-kpi-grid span,
        .analysis-kpi-grid small {
            color: #d6eee4;
            font-weight: 900;
            line-height: 1.35;
        }

        .analysis-kpi-grid span {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .analysis-kpi-grid strong {
            overflow-wrap: anywhere;
            color: #ffffff;
            font-size: clamp(22px, 2.2vw, 30px);
            font-weight: 900;
            line-height: 1.05;
        }

        .analysis-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .analysis-card {
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 20px;
            background: rgba(255,255,255,0.94);
            color: #123f34;
        }

        .analysis-card h3 {
            margin: 0 0 14px;
            color: #123f34;
            font-size: 20px;
            font-weight: 900;
        }

        .analysis-card p {
            margin: 0;
            color: #466357;
            font-weight: 800;
        }

        .analysis-rank-list,
        .analysis-focus-list {
            display: grid;
            gap: 10px;
        }

        .analysis-rank-list article,
        .analysis-focus-list article {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px solid rgba(47, 125, 99, 0.13);
            border-radius: 16px;
            background: #f8fcfa;
        }

        .analysis-rank-list article > div,
        .analysis-focus-list article > div {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .analysis-rank-list strong,
        .analysis-focus-list a:not(.table-btn) {
            color: #123f34;
            font-weight: 900;
            text-decoration: none;
        }

        .analysis-focus-list a:not(.table-btn):hover {
            text-decoration: underline;
        }

        .analysis-rank-list span,
        .analysis-focus-list span {
            color: #617a70;
            font-size: 13px;
            font-weight: 800;
        }

        .analysis-rank-list em {
            color: #7a4b0c;
            font-style: normal;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .analysis-card--focus {
            background: #fff8ea;
        }

        .panel {
            background: rgba(18,46,38,0.9);
            padding: 20px;
            border-radius: 14px;
            border: 1px solid rgba(120,180,150,0.22);
            overflow: hidden;
        }

        .panel h2 {
            margin-top: 0;
            font-size: 28px;
            color: #ffffff;
        }

        .overview-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .overview-card {
            position: relative;
            overflow: hidden;
            min-width: 0;
            padding: 24px;
            border: 1px solid rgba(16, 61, 49, 0.11);
            border-radius: 28px;
            background:
                linear-gradient(155deg, rgba(255,255,255,0.98) 0%, rgba(249,251,250,0.98) 62%, rgba(243,248,246,0.96) 100%);
            color: #123f34;
            box-shadow: 0 22px 54px rgba(17, 42, 35, 0.1);
        }

        .overview-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, #1f6d57, #d99a2b);
            opacity: .95;
        }

        .overview-card::after {
            content: "";
            position: absolute;
            right: -58px;
            top: -72px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(217,154,43,0.16) 0%, rgba(217,154,43,0.08) 44%, transparent 70%);
            pointer-events: none;
        }

        .overview-card--payments::before {
            background: linear-gradient(90deg, #1f6d57, #d99a2b);
        }

        .overview-card--participants {
            grid-column: 1 / -1;
        }

        .overview-card__header {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .overview-card__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding: 6px 10px;
            border: 1px solid rgba(31, 109, 87, 0.12);
            border-radius: 999px;
            background: #eef8f4;
            color: #1f6d57;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .overview-card__eyebrow::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #d99a2b;
        }

        .overview-card h2 {
            text-transform: none;
            letter-spacing: -0.02em;
            margin: 0;
            max-width: 460px;
            color: #0b3329;
            font-size: clamp(23px, 2.2vw, 30px);
            font-weight: 900;
            line-height: 1.12;
            text-wrap: balance;
        }

        .overview-card__subtitle {
            margin: 8px 0 0;
            color: #557266;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.45;
        }

        .overview-card__metric {
            display: grid;
            justify-items: end;
            gap: 5px;
            min-width: 118px;
            color: #557266;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .overview-card__count,
        .overview-card__amount {
            display: inline-grid;
            place-items: center;
            min-width: 56px;
            min-height: 56px;
            padding: 0 14px;
            border: 1px solid rgba(31, 109, 87, 0.16);
            border-radius: 20px;
            background: linear-gradient(145deg, #ffffff, #eef8f4);
            color: #123f34;
            font-size: 25px;
            font-weight: 900;
            line-height: 1;
            box-shadow: 0 14px 30px rgba(16, 61, 49, 0.1);
        }

        .overview-card__amount {
            min-width: 0;
            font-size: clamp(17px, 1.8vw, 22px);
            white-space: nowrap;
        }

        .overview-card__hint {
            position: relative;
            z-index: 1;
            margin: 14px 0 0;
            padding: 12px 14px;
            border-radius: 16px;
            background: #eef8f4;
            color: #174b3d;
            font-weight: 800;
            line-height: 1.45;
        }

        .overview-list,
        .participant-card-list {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 12px;
        }

        .overview-list--compact {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            align-items: start;
        }

        .overview-list-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 16px;
            border: 1px solid rgba(47, 125, 99, 0.13);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 10px 24px rgba(16, 37, 31, 0.055);
        }

        .overview-list-card--actionable {
            border-color: rgba(47, 125, 99, 0.16);
            background: linear-gradient(145deg, #ffffff 0%, #fbfdfc 100%);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .overview-list-card--actionable:hover {
            transform: translateY(-2px);
            border-color: rgba(31, 109, 87, 0.28);
            box-shadow: 0 16px 34px rgba(16, 37, 31, 0.1);
        }

        .overview-list-card--compact {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .overview-list-card__trip {
            display: inline-flex;
            margin-bottom: 6px;
            color: #698176;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .overview-list-card h3,
        .participant-mini-card h3 {
            margin: 0;
            color: #123f34;
            font-size: 18px;
            line-height: 1.2;
        }

        .overview-list-card__title-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px 12px;
        }

        .overview-list-card p,
        .participant-mini-card p {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin: 0;
            color: #466357;
            line-height: 1.45;
        }

        .overview-list-card__meta {
            margin-top: 10px !important;
            font-size: 13px;
            font-weight: 800;
        }

        .overview-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .overview-chip {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.2;
        }

        .overview-chip--danger {
            border: 1px solid rgba(176, 62, 62, 0.16);
            background: #fff4f4;
            color: #8f2424;
        }

        .overview-chip--muted {
            border: 1px solid rgba(47, 125, 99, 0.16);
            background: #eef8f4;
            color: #174b3d;
        }

        .overview-list-card__status {
            display: inline-flex;
            width: fit-content;
            margin-top: 2px;
            padding: 7px 10px;
            border: 1px solid rgba(176, 62, 62, 0.18);
            border-radius: 999px;
            background: #fff1f1;
            color: #8f2424;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.2;
        }

        .overview-list-card__status--payment {
            border-color: rgba(166, 104, 20, 0.22);
            background: #fff7e8;
            color: #7a4b0c;
        }

        .overview-info-panel {
            margin-top: 12px;
            border: 1px solid rgba(47, 125, 99, 0.18);
            border-radius: 16px;
            background: #f7fcfa;
            color: #123f34;
        }

        .overview-info-panel summary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 10px 10px 0;
            padding: 10px 14px;
            border: 1px solid rgba(16, 86, 68, 0.2);
            border-radius: 12px;
            background: #ffffff;
            color: #0f3a30;
            font-weight: 900;
            letter-spacing: 0.01em;
            list-style: none;
            box-shadow: 0 6px 16px rgba(10, 48, 38, 0.08);
            transition: background-color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .overview-info-panel summary::-webkit-details-marker { display: none; }

        .overview-info-panel summary::before {
            content: 'i';
            display: inline-grid;
            place-items: center;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: linear-gradient(135deg, #1f6d57 0%, #2d8b71 100%);
            color: #fff;
            font-size: 12px;
            font-weight: 900;
        }

        .overview-info-panel summary::after {
            content: '▾';
            margin-left: 2px;
            color: #1f6d57;
            font-size: 12px;
            transition: transform .18s ease;
        }

        .overview-info-panel summary:hover {
            background: #f3fbf7;
            border-color: rgba(32, 110, 88, 0.35);
            box-shadow: 0 10px 24px rgba(10, 48, 38, 0.12);
            transform: translateY(-1px);
        }

        .overview-info-panel[open] summary {
            background: #ebf8f2;
            border-color: rgba(32, 110, 88, 0.42);
            box-shadow: 0 10px 24px rgba(10, 48, 38, 0.13);
        }

        .overview-info-panel[open] summary::after {
            transform: rotate(180deg);
            gap: 8px;
            cursor: pointer;
            margin: 10px 10px 0;
            padding: 10px 14px;
            border: 1px solid rgba(47, 125, 99, 0.26);
            border-radius: 999px;
            background: linear-gradient(135deg, #ffffff 0%, #ecf8f3 100%);
            color: #0f3a30;
            font-weight: 900;
            letter-spacing: 0.02em;
            list-style: none;
            transition: all .18s ease;
        }

        .overview-info-panel summary::-webkit-details-marker { display: none; }

        .overview-info-panel summary::before {
            content: '▸';
            font-size: 12px;
            color: #1f6d57;
            transform: translateY(-1px);
            transition: transform .18s ease;
        }

        .overview-info-panel[open] summary {
            background: linear-gradient(135deg, #eaf7f1 0%, #ddf2ea 100%);
            border-color: rgba(47, 125, 99, 0.35);
            box-shadow: 0 8px 20px rgba(14, 61, 48, 0.12);
        }

        .overview-info-panel[open] summary::before {
            transform: rotate(90deg) translateY(0);
        }

        .overview-info-panel summary:focus-visible {
            outline: 3px solid rgba(47, 125, 99, 0.35);
            outline-offset: 2px;
            border-radius: 14px;
        }

        .overview-info-panel ul,
        .overview-info-panel dl {
            margin: 0;
            padding: 0 12px 12px 30px;
        }

        .overview-info-panel li + li {
            margin-top: 6px;
        }

        .overview-info-panel dl {
            display: grid;
            gap: 8px;
            padding-left: 12px;
        }

        .overview-info-panel dl div {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(47, 125, 99, 0.1);
        }

        .overview-info-panel dt {
            color: #557266;
            font-weight: 800;
        }

        .overview-info-panel dd {
            margin: 0;
            color: #123f34;
            font-weight: 900;
            text-align: right;
        }

        .overview-list-card__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .overview-list-card--compact .overview-list-card__actions {
            justify-content: flex-start;
        }

        .overview-list-card__actions .table-btn {
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 999px;
        }

        .overview-list-card__primary-action {
            width: 100%;
            justify-content: center;
            border-color: rgba(31, 109, 87, 0.24) !important;
            background: #123f34 !important;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(18, 63, 52, 0.16);
        }

        .overview-list-card__primary-action:hover {
            background: #1f6d57 !important;
            color: #ffffff !important;
        }

        .table-btn--ghost {
            background: #ffffff !important;
            color: #174b3d !important;
            border: 1px solid rgba(47, 125, 99, 0.2) !important;
        }

        .table-btn--ghost:hover,
        .table-btn--ghost:focus,
        .table-btn--ghost:active {
            background: #eef8f4 !important;
            color: #174b3d !important;
            border-color: rgba(47, 125, 99, 0.35) !important;
        }

        .overview-empty-state {
            position: relative;
            z-index: 1;
            padding: 18px;
            border: 1px dashed rgba(47, 125, 99, 0.25);
            border-radius: 20px;
            background: #f4fbf8;
            color: #123f34;
        }

        .participant-card-list {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .participant-mini-card {
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(47, 125, 99, 0.13);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.78);
            box-shadow: 0 10px 24px rgba(16, 37, 31, 0.06);
        }

        .participant-mini-card__avatar {
            display: inline-grid;
            place-items: center;
            width: 46px;
            height: 46px;
            margin-bottom: 12px;
            border-radius: 16px;
            background: linear-gradient(135deg, #123f34, #2f7d63);
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
        }

        .participant-mini-card .status-badge {
            margin-top: 10px;
            color: #123f34;
            border-color: rgba(18, 63, 52, 0.18);
            background: rgba(18, 63, 52, 0.12);
        }

        .participant-mini-card .status-green { background: rgba(39, 174, 96, 0.18); color: #145a32; border-color: rgba(39, 174, 96, 0.38); }
        .participant-mini-card .status-yellow { background: rgba(241, 196, 15, 0.25); color: #6f4e00; border-color: rgba(191, 147, 0, 0.45); }
        .participant-mini-card .status-red { background: rgba(231, 76, 60, 0.18); color: #8d2d1f; border-color: rgba(231, 76, 60, 0.4); }

        .participant-mini-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .participant-mini-card__actions .table-btn {
            min-height: 38px;
            padding: 9px 12px;
            border-radius: 999px;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 18px;
        }

        .filter-bar > * {
            flex: 1 1 220px;
        }

        .filter-bar--participants > * {
            flex-basis: 190px;
        }

        .trip-quick-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0 0 12px;
        }

        .trip-quick-filter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid rgba(125,211,168,0.3);
            color: #d6fbe7;
            background: rgba(16,36,29,0.56);
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .trip-quick-filter:hover,
        .trip-quick-filter:focus,
        .trip-quick-filter.is-active {
            background: rgba(125,211,168,0.24);
            border-color: rgba(125,211,168,0.7);
            color: #ffffff;
            outline: none;
        }

        .list-summary {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(142,224,184,0.12);
            color: #d9ffed;
            font-weight: 800;
        }

        .alpenia-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 18px;
        }

        .alpenia-pagination a,
        .alpenia-pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(142,224,184,0.28);
            background: rgba(16,36,29,0.82);
            color: #ffffff;
            text-decoration: none;
            font-weight: 800;
        }

        .alpenia-pagination a:hover,
        .alpenia-pagination a:focus,
        .alpenia-pagination .is-active {
            background: #8ee0b8;
            color: #10241d;
            outline: none;
        }

        .alpenia-form { margin-top: 10px; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            color: #f2f2f2;
        }

        .filter-bar input,
        .filter-bar select,
        .form-group input:not([type="file"]),
        .form-group select {
            width: 100%;
            min-width: 0;
            height: 56px;
            min-height: 56px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid rgba(120,180,150,0.35);
            background-color: rgba(16,36,29,0.9);
            color: #ffffff !important;
            outline: none;
            font-size: 16px;
            font-weight: 500;
            line-height: normal;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-shadow: none;
        }

        .filter-bar select,
        .form-group select {
            padding-right: 52px;
            background-image:
                linear-gradient(45deg, transparent 50%, #ffffff 50%),
                linear-gradient(135deg, #ffffff 50%, transparent 50%);
            background-position:
                calc(100% - 22px) calc(50% - 3px),
                calc(100% - 16px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        ::placeholder,
        .form-control::placeholder,
        input::placeholder,
        textarea::placeholder,
        .filter-bar input::placeholder,
        .form-group input::placeholder {
            color: #555;
            opacity: 1;
        }

        .filter-bar select option,
        .form-group select option {
            background: #10241d !important;
            color: #ffffff !important;
        }

        .filter-bar input:focus,
        .filter-bar select:focus,
        .form-group input:focus,
        .form-group select:focus {
            border-color: rgba(125, 211, 168, 0.55);
            box-shadow: 0 0 0 2px rgba(125, 211, 168, 0.15);
        }

        .form-group input[type="file"] {
            width: 100%;
            min-width: 0;
            min-height: 56px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid rgba(120,180,150,0.35);
            background: rgba(16,36,29,0.9);
            color: #fff;
            font-size: 15px;
        }

        .participant-wizard {
            display: grid;
            gap: 18px;
        }

        .participant-wizard__top {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            padding: 18px;
            border: 1px solid rgba(125,211,168,0.24);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(29,77,63,0.94), rgba(18,46,38,0.92));
        }

        .participant-wizard__eyebrow {
            margin: 0 0 6px;
            color: #9ff0c7;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .participant-wizard__top h2 {
            margin: 0 0 6px;
            color: #ffffff;
            font-size: 26px;
        }

        .participant-wizard__top p {
            margin: 0;
            color: rgba(255,255,255,0.78);
            line-height: 1.5;
        }

        .participant-wizard__count {
            flex: 0 0 auto;
            min-width: 104px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(125,211,168,0.14);
            color: #ffffff;
            font-size: 22px;
            font-weight: 900;
            text-align: center;
        }

        .participant-wizard__tabs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }

        .participant-wizard__tab {
            min-height: 58px;
            padding: 10px 12px;
            border: 1px solid rgba(120,180,150,0.28);
            border-radius: 14px;
            background: rgba(16,36,29,0.72);
            color: #ffffff;
            cursor: pointer;
            text-align: left;
        }

        .participant-wizard__tab span {
            display: block;
            font-size: 12px;
            opacity: 0.78;
        }

        .participant-wizard__tab strong {
            display: block;
            margin-top: 2px;
            font-size: 20px;
        }

        .participant-wizard__tab.is-active {
            border-color: rgba(125,211,168,0.82);
            background: linear-gradient(135deg, #1d4d3f, #2d6a57);
            box-shadow: 0 12px 24px rgba(5,18,14,0.22);
        }

        .participant-box {
            background: rgba(16,36,29,0.82);
            border: 1px solid rgba(120,180,150,0.22);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 0;
        }

        .participant-box__header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .participant-box__header p {
            max-width: 420px;
            margin: 0;
            color: rgba(255,255,255,0.72);
            line-height: 1.5;
        }

        .participant-box__kicker {
            display: inline-flex;
            margin-bottom: 7px;
            color: #9ff0c7;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .participant-box h3 {
            margin: 0;
            font-size: 24px;
            color: #ffffff;
        }

        .participant-box__actions,
        .participant-wizard__final-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            align-items: center;
            margin-top: 8px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .participant-wizard__final-actions {
            justify-content: space-between;
            margin-top: 0;
            padding: 16px;
            border: 1px solid rgba(120,180,150,0.22);
            border-radius: 16px;
            background: rgba(16,36,29,0.62);
        }

        .participant-wizard__final-actions p {
            margin: 0;
            color: rgba(255,255,255,0.76);
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            background: rgba(16,36,29,0.74);
            border: 1px solid rgba(120,180,150,0.2);
            padding: 16px;
            border-radius: 10px;
        }

        .checkbox-line {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-size: 16px;
            line-height: 1.4;
            cursor: pointer;
        }

        .checkbox-line input[type="checkbox"] {
            appearance: auto;
            -webkit-appearance: checkbox;
            width: 22px !important;
            min-width: 22px !important;
            height: 22px !important;
            min-height: 22px !important;
            margin: 0;
            padding: 0 !important;
            accent-color: #7dd3a8;
            cursor: pointer;
        }

        .required-mark { color: #7dd3a8; }

        .list-table {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .list-table li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .list-table li:last-child { border-bottom: none; }

        .trip-list-card {
            align-items: stretch !important;
            background: rgba(16,36,29,0.68);
            border: 1px solid rgba(120,180,150,0.18) !important;
            border-radius: 16px;
            padding: 18px !important;
            margin-bottom: 14px;
        }

        .trip-list-card__content {
            flex: 1 1 auto;
            min-width: 0;
        }

        .trip-list-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .trip-list-card__header strong {
            color: #ffffff;
            font-size: 20px;
        }

        .trip-list-card__badges {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .timeline-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
        }

        .timeline-badge--planned {
            color: #d7fff1;
            background: rgba(46, 204, 113, 0.2);
            border-color: rgba(46, 204, 113, 0.42);
        }

        .timeline-badge--urgent {
            color: #fff7d8;
            background: rgba(241, 196, 15, 0.22);
            border-color: rgba(241, 196, 15, 0.45);
        }

        .timeline-badge--overdue {
            color: #ffd8d8;
            background: rgba(231, 76, 60, 0.22);
            border-color: rgba(231, 76, 60, 0.45);
        }

        .trip-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .trip-info-item {
            min-width: 0;
            padding: 12px;
            border-radius: 12px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.09);
        }

        .trip-info-item span {
            display: block;
            color: rgba(255,255,255,0.68);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .trip-info-item strong {
            display: block;
            color: #ffffff;
            font-size: 15px;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .trip-list-card__actions {
            flex: 0 0 210px;
            align-content: flex-start;
            justify-content: flex-end;
        }

        .trip-progress {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid rgba(125,211,168,0.2);
            border-radius: 12px;
            background: rgba(12, 30, 24, 0.5);
        }

        .trip-progress__meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255,255,255,0.88);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .trip-progress__track {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
            overflow: hidden;
        }

        .trip-progress__fill {
            display: block;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #5ed89d 0%, #8ee0b8 100%);
        }

        .list-main {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .list-main strong { color: #ffffff; }
        .list-main span { color: #d6d6d6; }

        .list-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .badge {
            background: rgba(16,36,29,0.75);
            border: 1px solid rgba(120,180,150,0.2);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            color: #ffffff;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .status-green { background: rgba(39,174,96,0.18); color: #90f0b3; border: 1px solid rgba(39,174,96,0.35); }
        .status-yellow { background: rgba(241,196,15,0.16); color: #ffe28a; border: 1px solid rgba(241,196,15,0.32); }
        .status-red { background: rgba(231,76,60,0.16); color: #ffaea4; border: 1px solid rgba(231,76,60,0.35); }
        .status-gray { background: rgba(255,255,255,0.10); color: #e1e1e1; border: 1px solid rgba(255,255,255,0.18); }

        .trip-meta-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
        }

        .trip-meta-box {
            background: rgba(16,36,29,0.78);
            border: 1px solid rgba(120,180,150,0.2);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .trip-meta-box strong,
        .trip-meta-box span {
            color: #ffffff;
        }

        .notes-box {
            margin-top: 8px;
            background: rgba(16,36,29,0.78);
            border: 1px solid rgba(120,180,150,0.2);
            padding: 14px;
            border-radius: 10px;
            color: #fff;
        }

        .table-wrap {
            overflow-x: auto;
            overflow-y: hidden;
        }

        .alpenia-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1500px;
        }

        .alpenia-table th,
        .alpenia-table td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            vertical-align: top;
            color: #ffffff;
        }

        .alpenia-table th {
            color: #ffffff;
            font-size: 14px;
        }

        .alpenia-table td a {
            color: #8ee0b8;
            text-decoration: none;
        }
        .trash-panel {
            border: 1px solid rgba(47, 116, 96, 0.24);
            background: linear-gradient(150deg, #f5faf8 0%, #edf6f2 45%, #e8f2ee 100%);
        }
        .trash-panel__header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 16px;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255,255,255,0.68);
            border: 1px solid rgba(96, 133, 181, 0.2);
        }
        .trash-panel__header h2 { margin: 0 0 4px; font-size: 18px; color: #16314f; }
        .trash-panel__header p { margin: 0; opacity: .88; color: #32506f; }
        .trash-panel__count {
            min-width: 92px;
            text-align: center;
            border-radius: 12px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.82);
            border: 1px solid rgba(61, 129, 108, 0.32);
            color: #1b4f40;
        }
        .trash-panel__count span { display: block; font-size: 24px; font-weight: 800; line-height: 1; }
        .trash-panel__count small { opacity: .85; }
        .trash-panel > p { margin: 12px 0 0; color: #123f34; font-weight: 700; background: rgba(255,255,255,0.86); border: 1px solid rgba(47, 116, 96, 0.24); border-radius: 10px; padding: 12px 14px; }
        .trash-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .trash-filter-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 6px 14px;
            border-radius: 999px;
            text-decoration: none;
            color: #1b4f40;
            background: rgba(255,255,255,0.76);
            border: 1px solid rgba(61, 129, 108, 0.32);
            font-weight: 700;
            transition: all .2s ease;
        }
        .trash-filter-chip:hover,
        .trash-filter-chip:focus-visible {
            color: #123d31;
            border-color: rgba(47, 116, 96, 0.52);
            background: #ffffff;
        }
        .trash-filter-chip.is-active {
            color: #ffffff;
            background: linear-gradient(180deg, #2f7460, #245845);
            border-color: #1f4f40;
            box-shadow: 0 6px 16px rgba(25, 85, 68, 0.28);
        }
        .trash-type-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.06);
        }
        .trash-type-badge--group_trip {
            color: #0f375d;
            border-color: rgba(67, 136, 205, 0.55);
            background: rgba(143, 196, 242, 0.38);
        }
        .trash-type-badge--trip_participant {
            color: #12462c;
            border-color: rgba(56, 154, 98, 0.55);
            background: rgba(166, 230, 191, 0.4);
        }
        .alpenia-table--trash {
            min-width: 0;
            table-layout: fixed;
        }
        .alpenia-table--trash th,
        .alpenia-table--trash td {
            vertical-align: middle;
            word-break: break-word;
            color: #1f3f60;
        }
        .alpenia-table--trash th {
            color: #1a3c61;
        }
        .alpenia-table--trash th:nth-child(1),
        .alpenia-table--trash td:nth-child(1) { width: 120px; }
        .alpenia-table--trash th:nth-child(3),
        .alpenia-table--trash td:nth-child(3) { width: 170px; }
        .alpenia-table--trash th:nth-child(4),
        .alpenia-table--trash td:nth-child(4) { width: 220px; }
        .trash-actions {
            white-space: nowrap;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (max-width: 900px) {
            .trash-panel__header {
                flex-direction: column;
                align-items: flex-start;
            }
            .trash-panel__count {
                min-width: 0;
            }
            .alpenia-table--trash,
            .alpenia-table--trash thead,
            .alpenia-table--trash tbody,
            .alpenia-table--trash th,
            .alpenia-table--trash td,
            .alpenia-table--trash tr {
                display: block;
                width: 100%;
            }
            .alpenia-table--trash thead {
                display: none;
            }
            .alpenia-table--trash tr {
                border: 1px solid rgba(95, 135, 180, 0.2);
                border-radius: 12px;
                margin-bottom: 12px;
                padding: 10px 12px;
                background: rgba(255,255,255,0.74);
            }
            .alpenia-table--trash td {
                border-bottom: 0;
                padding: 8px 0;
            }
            .alpenia-table--trash td:last-child {
                padding-bottom: 0;
            }
            .trash-actions {
                white-space: normal;
            }
        }
        .alpenia-dashboard-shell h1.entry-title,
        .alpenia-dashboard-shell .page-title,
        .alpenia-dashboard-shell .elementor-heading-title,
        body .entry-header,
        body h1.entry-title,
        body .page-title,
        body .elementor-heading-title {
            display: none !important;
        }

        .doc-ok { color: #8ee0b8; }
        .doc-missing { color: #ff9e9e; }
        .doc-optional { color: #d6d6d6; }

        .row-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .role-select-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .role-select-box input {
            display: none;
        }

        .role-select-box span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 56px;
            padding: 14px 18px;
            border-radius: 12px;
            background: rgba(16,36,29,0.82);
            border: 1px solid rgba(120,180,150,0.3);
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
            text-align: center;
        }

        .role-select-box input:checked + span {
            border-color: gold;
            box-shadow: 0 0 0 2px rgba(255,215,0,0.12);
            background: linear-gradient(135deg, #1d4d3f, #2d6a57);
        }

        .role-select-box span:hover {
            border-color: gold;
        }

        .role-action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 10px;
        }

        .user-action-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .user-action-links a {
            color: #8ee0b8;
            text-decoration: none;
            font-weight: 600;
        }

        .user-action-links a:hover,
        .user-action-links a:focus {
            color: gold;
            outline: none;
        }

        .user-action-links .delete-link {
            color: #ff9d9d !important;
        }

        .user-action-links .delete-link:hover,
        .user-action-links .delete-link:focus {
            color: #ffc2c2 !important;
        }


        .alpenia-idle-timeout-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(6, 18, 14, 0.75);
            z-index: 9999;
            padding: 16px;
        }

        .alpenia-idle-timeout-modal.is-visible {
            display: flex;
        }

        .alpenia-idle-timeout-card {
            width: min(460px, 100%);
            background: #17382e;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            padding: 20px;
            color: #ffffff;
            box-shadow: 0 20px 42px rgba(0, 0, 0, 0.35);
        }

        .alpenia-idle-timeout-card h2 {
            margin: 0 0 10px;
            font-size: 24px;
            color: #ffffff;
        }

        .alpenia-idle-timeout-card p {
            margin: 0 0 10px;
            line-height: 1.5;
        }

        .alpenia-idle-timeout-countdown {
            font-weight: 700;
            color: #8ee0b8;
        }

        .alpenia-idle-timeout-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .filter-bar input:-webkit-autofill,
        .filter-bar select:-webkit-autofill,
        .form-group input:-webkit-autofill,
        .form-group select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px rgba(16,36,29,0.9) inset !important;
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 9999s ease-out 0s;
        }

        @media (max-width: 1200px) {
            .trip-meta-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .trip-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .participant-card-list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 1100px) {
            .alpenia-dashboard-layout { grid-template-columns: 1fr; }
            .dashboard-sidebar { position: static; }
            .dashboard-sidebar__nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .overview-card-grid { grid-template-columns: 1fr; }
            .overview-card--participants { grid-column: auto; }
            .growth-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .growth-insight-grid { grid-template-columns: 1fr; }
            .finance-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .finance-detail-grid { grid-template-columns: 1fr; }
            .operations-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .operations-detail-grid { grid-template-columns: 1fr; }
            .operations-task-item { grid-template-columns: 1fr; }
            .operations-task-actions { justify-content: flex-start; }
            .analysis-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .analysis-detail-grid { grid-template-columns: 1fr; }
            .cards { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .trip-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .alpenia-dashboard { padding: 14px 14px 28px; }
            .dashboard-top { gap: 14px; margin-bottom: 18px; }
            .dashboard-brand { width: 100%; gap: 12px; }
            .dashboard-brand-text h1 { font-size: 30px; }
            .dashboard-logo { width: 56px; }
            .dashboard-language-switch { margin-left: auto; }
            .actions { width: 100%; gap: 8px; justify-content: stretch; }
            .dashboard-sidebar { padding: 14px; border-radius: 22px; }
            .dashboard-sidebar__nav { grid-template-columns: 1fr; }
            .dashboard-sidebar__header { border-radius: 18px; }
            .overview-card-grid { gap: 14px; margin-top: 14px; }
            .overview-card { padding: 16px; border-radius: 20px; }
            .growth-overview { padding: 16px; border-radius: 20px; }
            .growth-overview__header { flex-direction: column; }
            .growth-kpi-grid { grid-template-columns: 1fr; }
            .growth-insight-grid { grid-template-columns: 1fr; }
            .finance-overview { padding: 16px; border-radius: 20px; }
            .finance-overview__header { flex-direction: column; }
            .finance-rate { width: 100%; }
            .finance-summary-grid { grid-template-columns: 1fr; }
            .finance-detail-grid { grid-template-columns: 1fr; }
            .finance-trip-row { grid-template-columns: 1fr; }
            .finance-trip-row__amount { text-align: left; }
            .finance-priority-item { align-items: flex-start; flex-direction: column; }
            .operations-overview { padding: 16px; border-radius: 20px; }
            .operations-overview__header { flex-direction: column; }
            .operations-workload { width: 100%; }
            .operations-status-grid { grid-template-columns: 1fr; }
            .operations-detail-grid { grid-template-columns: 1fr; }
            .operations-task-item { grid-template-columns: 1fr; }
            .operations-task-actions { justify-content: stretch; }
            .analysis-overview { padding: 16px; border-radius: 20px; }
            .analysis-kpi-grid { grid-template-columns: 1fr; }
            .analysis-detail-grid { grid-template-columns: 1fr; }
            .analysis-rank-list article,
            .analysis-focus-list article { align-items: flex-start; flex-direction: column; }
            .analysis-rank-list em { text-align: left; }
            .overview-card__header { align-items: center; }
            .overview-list-card { grid-template-columns: 1fr; border-radius: 16px; }
            .overview-list-card__actions { justify-content: stretch; }
            .participant-card-list { grid-template-columns: 1fr; }
            .participant-mini-card { display: flex; gap: 12px; align-items: flex-start; border-radius: 16px; }
            .participant-mini-card__avatar { flex: 0 0 46px; margin-bottom: 0; }
            .trip-list-card__header { flex-direction: column; align-items: flex-start; }
            .trip-info-grid { grid-template-columns: 1fr; }
            .trip-list-card__actions { flex: 1 1 auto; justify-content: stretch; }
            .btn-primary,
            .btn-secondary,
            .table-btn { width: 100%; min-height: 48px; text-align: center; white-space: normal; }
            .cards { grid-template-columns: 1fr; gap: 12px; margin: 18px 0; }
            .card { padding: 16px; border-radius: 12px; }
            .card span { font-size: 30px; }
            .panel { padding: 16px; border-radius: 12px; }
            .panel h2 { font-size: 23px; line-height: 1.2; }
            .form-grid { grid-template-columns: 1fr; gap: 14px; }
            .filter-bar { flex-direction: column; gap: 10px; }
            .filter-bar > * { flex: 1 1 100%; width: 100%; }
            .filter-bar input,
            .filter-bar select,
            .form-group input:not([type="file"]),
            .form-group select,
            .form-group input[type="file"] { font-size: 16px; }
            .participant-wizard__top,
            .participant-box__header,
            .participant-wizard__final-actions { flex-direction: column; align-items: stretch; }
            .participant-wizard__count { width: 100%; }
            .participant-wizard__tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .participant-box { padding: 16px; border-radius: 12px; }
            .participant-box h3 { font-size: 21px; }
            .participant-box__actions .btn-primary,
            .participant-box__actions .btn-secondary,
            .participant-wizard__final-actions .btn-primary { width: 100%; }
            .check-grid { grid-template-columns: 1fr; padding: 14px; }
            .list-table li { flex-direction: column; align-items: flex-start; gap: 10px; }
            .list-main,
            .list-actions { width: 100%; }
            .list-actions .table-btn,
            .list-actions .btn-primary,
            .list-actions .btn-secondary { flex: 1 1 100%; }
            .trip-meta-grid { grid-template-columns: 1fr; gap: 12px; }
            .table-wrap { margin: 0 -16px; padding: 0 16px 6px; -webkit-overflow-scrolling: touch; }
            .alpenia-table { min-width: 980px; }
            .role-select-grid,
            .role-action-grid { grid-template-columns: 1fr; }
            .alpenia-idle-timeout-card { padding: 16px; }
        }

        @media (max-width: 420px) {
            .alpenia-dashboard { padding: 10px 10px 24px; }
            .dashboard-top { align-items: stretch; }
            .dashboard-brand,
            .dashboard-language-switch,
            .alpenia-logout-form { width: 100%; }
            .dashboard-language-switch { justify-content: center; }
            .lang-link { flex: 1 1 0; }
            .panel,
            .participant-box,
            .card { padding: 14px; }
            .table-wrap { margin: 0 -14px; padding-left: 14px; padding-right: 14px; }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const euOrSchengenCountries = [
            'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich',
            'Griechenland','Irland','Italien','Kroatien','Lettland','Litauen','Luxemburg',
            'Malta','Niederlande','Österreich','Polen','Portugal','Rumänien','Schweden',
            'Slowakei','Slowenien','Spanien','Tschechien','Ungarn','Zypern','Island',
            'Liechtenstein','Norwegen','Schweiz'
        ];

        const uploadLimits = {
            passport: 5 * 1024 * 1024,
            photo: 2 * 1024 * 1024,
            visaPhoto: 2 * 1024 * 1024,
            meldezettel: 5 * 1024 * 1024
        };

        function normalize(value) {
            return (value || '').trim().toLowerCase();
        }

        document.querySelectorAll('.public-registration-link__copy').forEach(function(button) {
            button.addEventListener('click', function() {
                const value = button.getAttribute('data-copy-value') || '';
                const defaultLabel = button.getAttribute('data-copy-default') || button.textContent;
                const successLabel = button.getAttribute('data-copy-success') || defaultLabel;

                function showCopied() {
                    button.textContent = successLabel;
                    window.setTimeout(function() {
                        button.textContent = defaultLabel;
                    }, 1800);
                }

                function copyWithFallback() {
                    const textarea = document.createElement('textarea');
                    textarea.value = value;
                    textarea.setAttribute('readonly', 'readonly');
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();

                    try {
                        if (document.execCommand('copy')) {
                            showCopied();
                        } else {
                            alert('<?php echo esc_js(alpenia_travel_t('Link konnte nicht automatisch kopiert werden. Bitte den Link markieren und manuell kopieren.')); ?>');
                        }
                    } catch (error) {
                        alert('<?php echo esc_js(alpenia_travel_t('Link konnte nicht automatisch kopiert werden. Bitte den Link markieren und manuell kopieren.')); ?>');
                    }

                    document.body.removeChild(textarea);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(showCopied).catch(copyWithFallback);
                    return;
                }

                copyWithFallback();
            });
        });

        function isEuOrSchengenCountry(value) {
            return euOrSchengenCountries.map(normalize).includes(normalize(value));
        }

        function validateFileInput(input, maxBytes, labelText) {
            if (!input) return true;
            if (!input.files || !input.files[0]) return true;

            if (input.files[0].size > maxBytes) {
                alert(labelText + ' <?php echo esc_js(alpenia_travel_t('ist zu groß. Erlaubt sind maximal')); ?> ' + Math.round(maxBytes / 1024 / 1024) + ' MB.');
                input.value = '';
                return false;
            }
            return true;
        }

        const formPilgrimageFlag = document.querySelector('.alpenia-form[data-pilgrimage-trip]');
        const isPilgrimageTrip = formPilgrimageFlag && formPilgrimageFlag.dataset.pilgrimageTrip === '1';

        document.querySelectorAll('input[id^="nationality_"]').forEach(function (input) {
            const index = input.id.replace('nationality_', '');
            const residenceFields = document.querySelectorAll('.residence-field-' + index);
            const residenceStart = document.getElementById('residence_permit_start_date_' + index);
            const residenceNumber = document.getElementById('residence_permit_number_' + index);
            const residenceUntil = document.getElementById('residence_permit_valid_until_' + index);
            const residencePhoto = document.getElementById('visa_photo_file_' + index);
            const residenceCheck = document.querySelector('input[name="check_visa_' + index + '"]');
            const pilgrimageVisaFields = document.querySelectorAll('.pilgrimage-visa-field-' + index);
            const visaEntryCountry = document.getElementById('visa_entry_country_' + index);
            const visaNumber = document.getElementById('visa_number_' + index);
            const visaExpiry = document.getElementById('visa_expiry_date_' + index);

            function toggleResidenceFields() {
                const nationality = input.value.trim();
                const showResidence = nationality !== '' && !isEuOrSchengenCountry(nationality);

                residenceFields.forEach(function (field) {
                    field.style.display = showResidence ? '' : 'none';
                });

                if (residenceStart) residenceStart.required = showResidence;
                if (residenceNumber) residenceNumber.required = showResidence;
                if (residenceUntil) residenceUntil.required = showResidence;
                if (residencePhoto) residencePhoto.required = showResidence;
                if (residenceCheck) residenceCheck.required = showResidence;
            }

            function togglePilgrimageVisaFields() {
                pilgrimageVisaFields.forEach(function (field) {
                    field.style.display = isPilgrimageTrip ? '' : 'none';
                });

                if (visaEntryCountry) visaEntryCountry.required = isPilgrimageTrip;
                if (visaNumber) visaNumber.required = isPilgrimageTrip;
                if (visaExpiry) visaExpiry.required = isPilgrimageTrip;
            }

            input.addEventListener('input', toggleResidenceFields);
            input.addEventListener('change', toggleResidenceFields);
            toggleResidenceFields();
            togglePilgrimageVisaFields();
        });

        const nationalityEdit = document.getElementById('nationality');

        if (nationalityEdit) {
            const editResidenceFields = Array.from(document.querySelectorAll('.edit-residence-field'));
            const editVisaFields = Array.from(document.querySelectorAll('.edit-visa-field'));
            const residenceStartEdit = document.getElementById('residence_permit_start_date');
            const residenceNumberEdit = document.getElementById('residence_permit_number');
            const residenceUntilEdit = document.getElementById('residence_permit_valid_until');
            const residencePhotoEdit = document.getElementById('visa_photo_file');
            const visaEntryCountryEdit = document.getElementById('visa_entry_country');
            const visaNumberEdit = document.getElementById('visa_number');
            const visaExpiryEdit = document.getElementById('visa_expiry_date');

            function toggleEditResidenceFields() {
                const nationality = nationalityEdit.value.trim();
                const showResidence = nationality !== '' && !isEuOrSchengenCountry(nationality);

                editResidenceFields.forEach(function (field) {
                    if (field) field.style.display = showResidence ? '' : 'none';
                });

                if (residenceStartEdit) residenceStartEdit.required = showResidence;
                if (residenceNumberEdit) residenceNumberEdit.required = showResidence;
                if (residenceUntilEdit) residenceUntilEdit.required = showResidence;
                if (residencePhotoEdit) residencePhotoEdit.required = showResidence;
            }

            function toggleEditPilgrimageVisaFields() {
                editVisaFields.forEach(function (field) {
                    if (field) field.style.display = isPilgrimageTrip ? '' : 'none';
                });

                if (visaEntryCountryEdit) visaEntryCountryEdit.required = isPilgrimageTrip;
                if (visaNumberEdit) visaNumberEdit.required = isPilgrimageTrip;
                if (visaExpiryEdit) visaExpiryEdit.required = isPilgrimageTrip;
            }

            nationalityEdit.addEventListener('input', toggleEditResidenceFields);
            nationalityEdit.addEventListener('change', toggleEditResidenceFields);
            toggleEditResidenceFields();
            toggleEditPilgrimageVisaFields();
        }


        document.querySelectorAll('[data-participant-wizard]').forEach(function (wizard) {
            const form = wizard.closest('form');
            const steps = Array.from(wizard.querySelectorAll('[data-participant-step]'));
            const tabs = Array.from(wizard.querySelectorAll('[data-wizard-tab]'));
            const currentLabel = wizard.querySelector('[data-wizard-current]');
            const nativeSubmit = form ? form.querySelector('.participant-wizard__native-submit') : null;
            let activeIndex = 0;

            function setStep(index) {
                if (!steps.length) return;
                activeIndex = Math.max(0, Math.min(index, steps.length - 1));

                steps.forEach(function (step, stepIndex) {
                    const isActive = stepIndex === activeIndex;
                    step.hidden = !isActive;
                    step.classList.toggle('is-active', isActive);
                });

                tabs.forEach(function (tab, tabIndex) {
                    const isActive = tabIndex === activeIndex;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                if (currentLabel) {
                    currentLabel.textContent = String(activeIndex + 1);
                }

                const currentStep = steps[activeIndex];
                if (currentStep) {
                    currentStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            function showInvalidField() {
                if (!form) return false;
                const invalidField = form.querySelector(':invalid');
                if (!invalidField) return false;
                const invalidStep = invalidField.closest('[data-participant-step]');
                if (invalidStep) {
                    const invalidIndex = steps.indexOf(invalidStep);
                    if (invalidIndex !== -1) {
                        setStep(invalidIndex);
                    }
                }
                window.setTimeout(function () {
                    invalidField.reportValidity();
                    invalidField.focus({ preventScroll: true });
                }, 80);
                return true;
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const index = parseInt(tab.getAttribute('data-wizard-tab'), 10) - 1;
                    setStep(index);
                });
            });

            wizard.querySelectorAll('[data-wizard-prev]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setStep(activeIndex - 1);
                });
            });

            wizard.querySelectorAll('[data-wizard-next]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const currentStep = steps[activeIndex];
                    const invalidInCurrentStep = currentStep ? currentStep.querySelector(':invalid') : null;
                    if (invalidInCurrentStep) {
                        invalidInCurrentStep.reportValidity();
                        invalidInCurrentStep.focus({ preventScroll: true });
                        return;
                    }
                    setStep(activeIndex + 1);
                });
            });

            document.querySelectorAll('[data-wizard-submit]').forEach(function (button) {
                if (button.closest('form') !== form) return;
                button.addEventListener('click', function () {
                    if (!form) return;
                    if (!form.checkValidity()) {
                        showInvalidField();
                        return;
                    }
                    if (nativeSubmit && typeof nativeSubmit.click === 'function') {
                        nativeSubmit.click();
                    } else {
                        form.submit();
                    }
                });
            });

            setStep(0);
        });

        const validationMessages = {
            valueMissing: '<?php echo esc_js(alpenia_travel_t('Dieses Feld ist erforderlich.')); ?>',
            typeMismatchEmail: '<?php echo esc_js(alpenia_travel_t('Bitte eine gültige E-Mail-Adresse eingeben.')); ?>',
            typeMismatch: '<?php echo esc_js(alpenia_travel_t('Bitte einen gültigen Wert eingeben.')); ?>',
            badInputDate: '<?php echo esc_js(alpenia_travel_t('Bitte ein gültiges Datum eingeben.')); ?>',
            patternMismatchTel: '<?php echo esc_js(alpenia_travel_t('Bitte eine gültige Telefonnummer eingeben.')); ?>',
            fileMissing: '<?php echo esc_js(alpenia_travel_t('Bitte eine gültige Datei hochladen.')); ?>'
        };

        function getValidationMessage(input) {
            if (input.validity.valueMissing) {
                return input.type === 'file' ? validationMessages.fileMissing : validationMessages.valueMissing;
            }
            if (input.validity.typeMismatch && input.type === 'email') return validationMessages.typeMismatchEmail;
            if (input.validity.badInput && input.type === 'date') return validationMessages.badInputDate;
            if (input.validity.patternMismatch && input.type === 'tel') return validationMessages.patternMismatchTel;
            if (input.validity.typeMismatch) return validationMessages.typeMismatch;
            return '';
        }

        document.querySelectorAll('.alpenia-form input, .alpenia-form select, .alpenia-form textarea').forEach(function (input) {
            input.addEventListener('invalid', function () {
                input.setCustomValidity(getValidationMessage(input));
            });
            input.addEventListener('input', function () {
                input.setCustomValidity('');
            });
            input.addEventListener('change', function () {
                input.setCustomValidity('');
            });
        });

        document.querySelectorAll('input[id^="passport_file_"], #passport_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.passport, '<?php echo esc_js(alpenia_travel_t('Reisepass-Datei')); ?>');
            });
        });

        document.querySelectorAll('input[id^="photo_file_"], #photo_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.photo, '<?php echo esc_js(alpenia_travel_t('Foto')); ?>');
            });
        });

        document.querySelectorAll('input[id^="visa_photo_file_"], #visa_photo_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.visaPhoto, '<?php echo esc_js(alpenia_travel_t('Aufenthaltstitel')); ?>');
            });
        });

        document.querySelectorAll('input[id^="meldezettel_file_"], #meldezettel_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.meldezettel, '<?php echo esc_js(alpenia_travel_t('Meldezettel')); ?>');
            });
        });


        const idleModal = document.getElementById('alpenia-idle-timeout-modal');
        const stayButton = document.getElementById('alpenia-idle-stay-btn');
        const logoutButton = document.getElementById('alpenia-idle-logout-btn');
        const countdownSeconds = document.getElementById('alpenia-idle-timeout-seconds');
        const logoutForm = document.getElementById('alpenia-idle-logout-form');

        const idleTimeoutMs = 15 * 60 * 1000;
        const warningTimeoutMs = 60 * 1000;
        let idleTimer = null;
        let forcedLogoutTimer = null;
        let countdownTimer = null;
        let modalOpen = false;
        let warningDeadline = 0;

        function submitIdleLogout() {
            if (logoutForm) {
                logoutForm.submit();
            }
        }

        function hideIdleModal() {
            if (!idleModal) return;
            idleModal.classList.remove('is-visible');
            idleModal.setAttribute('aria-hidden', 'true');
            modalOpen = false;

            if (forcedLogoutTimer) {
                clearTimeout(forcedLogoutTimer);
                forcedLogoutTimer = null;
            }

            if (countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        }

        function resetIdleTimer() {
            if (modalOpen) {
                return;
            }

            if (idleTimer) {
                clearTimeout(idleTimer);
            }

            idleTimer = setTimeout(function() {
                if (!idleModal) {
                    submitIdleLogout();
                    return;
                }

                modalOpen = true;
                idleModal.classList.add('is-visible');
                idleModal.setAttribute('aria-hidden', 'false');
                warningDeadline = Date.now() + warningTimeoutMs;

                if (countdownSeconds) {
                    countdownSeconds.textContent = '60';
                }

                forcedLogoutTimer = setTimeout(submitIdleLogout, warningTimeoutMs);

                countdownTimer = setInterval(function() {
                    if (!countdownSeconds) return;
                    const secondsLeft = Math.max(0, Math.ceil((warningDeadline - Date.now()) / 1000));
                    countdownSeconds.textContent = String(secondsLeft);
                }, 250);
            }, idleTimeoutMs);
        }

        ['click', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function(eventName) {
            document.addEventListener(eventName, resetIdleTimer, { passive: true });
        });

        if (stayButton) {
            stayButton.addEventListener('click', function() {
                hideIdleModal();
                resetIdleTimer();
            });
        }

        if (logoutButton) {
            logoutButton.addEventListener('click', submitIdleLogout);
        }

        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                resetIdleTimer();
            }
        });

        (function initAirportDropdown() {
            const airportSelect = document.getElementById('departure_airport');
            if (!airportSelect) return;

            const selectedCode = (airportSelect.dataset.selected || '').trim().toUpperCase();
            const airportSourceUrl = 'https://raw.githubusercontent.com/datasets/airport-codes/master/data/airport-codes.csv';

            function parseCsvLine(line) {
                const cells = [];
                let cell = '';
                let inQuotes = false;

                for (let i = 0; i < line.length; i += 1) {
                    const char = line[i];
                    if (char === '"') {
                        if (inQuotes && line[i + 1] === '"') {
                            cell += '"';
                            i += 1;
                        } else {
                            inQuotes = !inQuotes;
                        }
                    } else if (char === ',' && !inQuotes) {
                        cells.push(cell);
                        cell = '';
                    } else {
                        cell += char;
                    }
                }
                cells.push(cell);
                return cells;
            }

            function populateOptions(options) {
                airportSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = <?php echo wp_json_encode(alpenia_travel_t('Bitte wählen')); ?>;
                airportSelect.appendChild(placeholder);

                options.forEach((airport) => {
                    const option = document.createElement('option');
                    option.value = airport.code;
                    option.textContent = `${airport.code} — ${airport.name}${airport.city ? `, ${airport.city}` : ''}${airport.country ? ` (${airport.country})` : ''}`;
                    if (airport.code === selectedCode) option.selected = true;
                    airportSelect.appendChild(option);
                });
            }

            fetch(airportSourceUrl)
                .then((response) => response.ok ? response.text() : Promise.reject())
                .then((csvText) => {
                    const lines = csvText.split(/\r?\n/).filter(Boolean);
                    const headers = parseCsvLine(lines.shift() || '');
                    const idxIata = headers.indexOf('iata_code');
                    const idxName = headers.indexOf('name');
                    const idxCity = headers.indexOf('municipality');
                    const idxCountry = headers.indexOf('iso_country');

                    const byCode = new Map();
                    lines.forEach((line) => {
                        const cells = parseCsvLine(line);
                        const code = (cells[idxIata] || '').trim().toUpperCase();
                        if (!code || byCode.has(code)) return;
                        byCode.set(code, {
                            code: code,
                            name: (cells[idxName] || '').trim(),
                            city: (cells[idxCity] || '').trim(),
                            country: (cells[idxCountry] || '').trim(),
                        });
                    });

                    const options = Array.from(byCode.values()).sort((a, b) => a.code.localeCompare(b.code));
                    populateOptions(options);
                })
                .catch(() => {
                    airportSelect.innerHTML = '';
                    const fallback = document.createElement('option');
                    fallback.value = selectedCode;
                    fallback.textContent = selectedCode || <?php echo wp_json_encode(alpenia_travel_t('Flughafenliste konnte nicht geladen werden')); ?>;
                    fallback.selected = true;
                    airportSelect.appendChild(fallback);
                });
        })();

        resetIdleTimer();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('alpenia_dashboard', 'alpenia_dashboard_shortcode');
