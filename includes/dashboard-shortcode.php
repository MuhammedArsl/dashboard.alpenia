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
        <button type="submit" class="btn-primary btn-logout">Logout</button>
    </form>
    <?php
    return ob_get_clean();
}

function alpenia_dashboard_shortcode() {
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();
    $debug_mode = isset($_GET['alpenia_debug_auth']) && $_GET['alpenia_debug_auth'] == '1';

    if (!is_user_logged_in()) {
        return '<div class="alpenia-message">Bitte zuerst einloggen. <a href="' . esc_url(alpenia_get_login_url()) . '" style="color:#8ee0b8;font-weight:bold;">Zum Login</a></div>';
    }

    if (!alpenia_user_can_access_dashboard()) {
        return '<div class="alpenia-message">Kein Zugriff.</div>';
    }

    $current_user = wp_get_current_user();
    $message = '';
    $logo_url = 'HIER_DEINE_LOGO_URL_EINFÜGEN';

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

    if (isset($_POST['save_trip'])) {
        if (!isset($_POST['alpenia_trip_nonce']) || !wp_verify_nonce($_POST['alpenia_trip_nonce'], 'alpenia_save_trip')) {
            $message = '<div class="alpenia-message">Sicherheitsfehler. Bitte erneut versuchen.</div>';
        } else {
            $trip_title      = sanitize_text_field($_POST['trip_title'] ?? '');
            $trip_type       = sanitize_text_field($_POST['trip_type'] ?? '');
            $trip_status     = sanitize_text_field($_POST['trip_status'] ?? 'open');
            $destination     = sanitize_text_field($_POST['destination'] ?? '');
            $country         = sanitize_text_field($_POST['country'] ?? '');
            $city            = sanitize_text_field($_POST['city'] ?? '');
            $start_date      = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date        = sanitize_text_field($_POST['end_date'] ?? '');
            $max_people      = (int) ($_POST['max_people'] ?? 0);
            $price           = sanitize_text_field($_POST['price'] ?? '');
            $assigned_guide  = (int) ($_POST['assigned_guide'] ?? 0);
            $meeting_date    = sanitize_text_field($_POST['meeting_date'] ?? '');
            $whatsapp_link   = esc_url_raw($_POST['whatsapp_link'] ?? '');
            $internal_notes  = sanitize_textarea_field($_POST['internal_notes'] ?? '');

            if (empty($trip_title) || empty($trip_type) || empty($destination) || empty($country) || empty($city) || empty($start_date) || empty($end_date)) {
                $message = '<div class="alpenia-message">Bitte alle Pflichtfelder ausfüllen.</div>';
            } else {
                $trip_id = wp_insert_post([
                    'post_title'   => $trip_title,
                    'post_type'    => 'group_trip',
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                    'post_content' => '',
                ]);

                if ($trip_id && !is_wp_error($trip_id)) {
                    update_post_meta($trip_id, 'trip_type', $trip_type);
                    update_post_meta($trip_id, 'trip_status', $trip_status);
                    update_post_meta($trip_id, 'destination', $destination);
                    update_post_meta($trip_id, 'country', $country);
                    update_post_meta($trip_id, 'city', $city);
                    update_post_meta($trip_id, 'start_date', $start_date);
                    update_post_meta($trip_id, 'end_date', $end_date);
                    update_post_meta($trip_id, 'max_people', $max_people);
                    update_post_meta($trip_id, 'price', $price);
                    update_post_meta($trip_id, 'assigned_guide', $assigned_guide);
                    update_post_meta($trip_id, 'meeting_date', $meeting_date);
                    update_post_meta($trip_id, 'whatsapp_link', $whatsapp_link);
                    update_post_meta($trip_id, 'internal_notes', $internal_notes);

                    alpenia_send_notification('Neue Reise erstellt', 'Eine neue Reise wurde erstellt: ' . $trip_title);
                    $message = '<div class="alpenia-success">Reise erfolgreich erstellt.</div>';
                } else {
                    $message = '<div class="alpenia-message">Fehler beim Erstellen der Reise.</div>';
                }
            }
        }
    }

    if (isset($_GET['delete_trip']) && isset($_GET['_delete_trip_nonce'])) {
        $trip_id = (int) $_GET['delete_trip'];

        if (!alpenia_user_can_delete_trip($trip_id)) {
            $message = '<div class="alpenia-message">Kein Zugriff zum Löschen dieser Reise.</div>';
        } elseif (wp_verify_nonce($_GET['_delete_trip_nonce'], 'alpenia_delete_trip_' . $trip_id)) {
            $trip_participants = get_posts([
                'post_type'   => 'trip_participant',
                'post_status' => 'publish',
                'numberposts' => -1,
                'meta_key'    => 'trip_id',
                'meta_value'  => $trip_id,
            ]);

            foreach ($trip_participants as $participant) {
                wp_delete_post($participant->ID, true);
            }

            wp_delete_post($trip_id, true);
            $message = '<div class="alpenia-success">Reise wurde gelöscht.</div>';
        } else {
            $message = '<div class="alpenia-message">Löschen nicht erlaubt.</div>';
        }
    }

    if (isset($_POST['save_participants_batch'])) {
        if (!isset($_POST['alpenia_participant_batch_nonce']) || !wp_verify_nonce($_POST['alpenia_participant_batch_nonce'], 'alpenia_save_participants_batch')) {
            $message = '<div class="alpenia-message">Sicherheitsfehler beim Teilnehmerformular.</div>';
        } else {
            $trip_id = (int) ($_POST['trip_id'] ?? 0);
            $participant_count = (int) ($_POST['participant_count'] ?? 0);

            if (!$trip_id || $participant_count < 1 || !alpenia_user_can_access_trip($trip_id)) {
                $message = '<div class="alpenia-message">Ungültige Reise oder kein Zugriff.</div>';
            } else {
                $all_ok = true;
                $saved_count = 0;

                for ($i = 1; $i <= $participant_count; $i++) {
                    $gender             = sanitize_text_field($_POST["gender_$i"] ?? '');
                    $first_name         = sanitize_text_field($_POST["first_name_$i"] ?? '');
                    $last_name          = sanitize_text_field($_POST["last_name_$i"] ?? '');
                    $birth_date         = sanitize_text_field($_POST["birth_date_$i"] ?? '');
                    $nationality        = sanitize_text_field($_POST["nationality_$i"] ?? '');
                    $passport_no        = sanitize_text_field($_POST["passport_no_$i"] ?? '');
                    $passport_expiry    = sanitize_text_field($_POST["passport_expiry_date_$i"] ?? '');
                    $visa_number        = sanitize_text_field($_POST["visa_number_$i"] ?? '');
                    $visa_expiry_date   = sanitize_text_field($_POST["visa_expiry_date_$i"] ?? '');
                    $visa_note          = sanitize_text_field($_POST["visa_note_$i"] ?? '');
                    $participant_status = sanitize_text_field($_POST["participant_status_$i"] ?? 'neu');
                    $visa_status        = sanitize_text_field($_POST["visa_status_$i"] ?? 'nicht begonnen');
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
                    $is_eu_citizen      = alpenia_is_eu_nationality($nationality);

                    if (
                        empty($gender) ||
                        empty($first_name) ||
                        empty($last_name) ||
                        empty($nationality) ||
                        empty($passport_expiry) ||
                        $passport_missing ||
                        $photo_missing
                    ) {
                        $all_ok = false;
                        break;
                    }

                    if (!$is_eu_citizen) {
                        if (empty($visa_number) || empty($visa_expiry_date) || $visa_photo_missing) {
                            $all_ok = false;
                            break;
                        }
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
                    update_post_meta($participant_id, 'first_name', $first_name);
                    update_post_meta($participant_id, 'last_name', $last_name);
                    update_post_meta($participant_id, 'birth_date', $birth_date);
                    update_post_meta($participant_id, 'nationality', $nationality);
                    update_post_meta($participant_id, 'passport_no', $passport_no);
                    update_post_meta($participant_id, 'passport_expiry_date', $passport_expiry);
                    update_post_meta($participant_id, 'visa_number', $visa_number);
                    update_post_meta($participant_id, 'visa_expiry_date', $visa_expiry_date);
                    update_post_meta($participant_id, 'visa_note', $visa_note);
                    update_post_meta($participant_id, 'participant_status', $participant_status);
                    update_post_meta($participant_id, 'visa_status', $visa_status);
                    update_post_meta($participant_id, 'room_assignment', $room_assignment);
                    update_post_meta($participant_id, 'subgroup', $subgroup);
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

                        $error_text = 'Eine oder mehrere Dateien sind zu groß.';
                        if (is_wp_error($passport_file_id)) $error_text = $passport_file_id->get_error_message();
                        elseif (is_wp_error($photo_file_id)) $error_text = $photo_file_id->get_error_message();
                        elseif (is_wp_error($visa_photo_file_id)) $error_text = $visa_photo_file_id->get_error_message();
                        elseif (is_wp_error($meldezettel_file_id)) $error_text = $meldezettel_file_id->get_error_message();

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
                    alpenia_send_notification('Neue Teilnehmer erfasst', $saved_count . ' Teilnehmer wurden für eine Reise gespeichert.');
                    $message = '<div class="alpenia-success">' . (int) $saved_count . ' Teilnehmer erfolgreich gespeichert.</div>';
                } elseif ($message === '') {
                    $message = '<div class="alpenia-message">Bitte alle Pflichtfelder ausfüllen. Pflicht sind Geschlecht, Vorname, Nachname, Staatsbürgerschaft, Pass-Enddatum, Pass und Foto. Bei Nicht-EU-Staatsbürgern sind zusätzlich Visa Nummer, Visa Ablaufdatum und Visa Foto Pflicht.</div>';
                }
            }
        }
    }

    if (isset($_GET['delete_participant']) && isset($_GET['_delete_nonce'])) {
        $participant_id = (int) $_GET['delete_participant'];

        if (!alpenia_user_can_access_participant($participant_id)) {
            $message = '<div class="alpenia-message">Kein Zugriff.</div>';
        } elseif (wp_verify_nonce($_GET['_delete_nonce'], 'alpenia_delete_participant_' . $participant_id)) {
            wp_delete_post($participant_id, true);
            $message = '<div class="alpenia-success">Teilnehmer wurde gelöscht.</div>';
        } else {
            $message = '<div class="alpenia-message">Löschen nicht erlaubt.</div>';
        }
    }

    if (isset($_POST['update_participant'])) {
        $participant_id = (int) ($_POST['participant_id'] ?? 0);

        if (!alpenia_user_can_access_participant($participant_id)) {
            $message = '<div class="alpenia-message">Kein Zugriff.</div>';
        } elseif (!isset($_POST['alpenia_edit_participant_nonce']) || !wp_verify_nonce($_POST['alpenia_edit_participant_nonce'], 'alpenia_edit_participant_' . $participant_id)) {
            $message = '<div class="alpenia-message">Sicherheitsfehler beim Bearbeiten.</div>';
        } else {
            $gender             = sanitize_text_field($_POST['gender'] ?? '');
            $first_name         = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name          = sanitize_text_field($_POST['last_name'] ?? '');
            $birth_date         = sanitize_text_field($_POST['birth_date'] ?? '');
            $nationality        = sanitize_text_field($_POST['nationality'] ?? '');
            $passport_no        = sanitize_text_field($_POST['passport_no'] ?? '');
            $passport_expiry    = sanitize_text_field($_POST['passport_expiry_date'] ?? '');
            $visa_number        = sanitize_text_field($_POST['visa_number'] ?? '');
            $visa_expiry_date   = sanitize_text_field($_POST['visa_expiry_date'] ?? '');
            $visa_note          = sanitize_text_field($_POST['visa_note'] ?? '');
            $participant_status = sanitize_text_field($_POST['participant_status'] ?? '');
            $visa_status        = sanitize_text_field($_POST['visa_status'] ?? '');
            $room_assignment    = sanitize_text_field($_POST['room_assignment'] ?? '');
            $subgroup           = sanitize_text_field($_POST['subgroup'] ?? '');
            $payment_total      = (float) ($_POST['payment_total'] ?? 0);
            $payment_deposit    = (float) ($_POST['payment_deposit'] ?? 0);
            $payment_paid       = (float) ($_POST['payment_paid'] ?? 0);
            $check_passport     = !empty($_POST['check_passport']) ? 1 : 0;
            $check_photo        = !empty($_POST['check_photo']) ? 1 : 0;
            $check_visa         = !empty($_POST['check_visa']) ? 1 : 0;
            $check_payment      = !empty($_POST['check_payment']) ? 1 : 0;
            $is_eu_citizen      = alpenia_is_eu_nationality($nationality);

            if (empty($gender) || empty($first_name) || empty($last_name) || empty($nationality) || empty($passport_expiry)) {
                $message = '<div class="alpenia-message">Bitte Herr/Frau, Vorname, Nachname, Staatsbürgerschaft und Pass-Enddatum ausfüllen.</div>';
            } elseif (!$is_eu_citizen && (empty($visa_number) || empty($visa_expiry_date))) {
                $message = '<div class="alpenia-message">Bei Nicht-EU-Staatsbürgern sind Visa Nummer und Visa Ablaufdatum Pflicht.</div>';
            } else {
                wp_update_post([
                    'ID'         => $participant_id,
                    'post_title' => trim($first_name . ' ' . $last_name),
                ]);

                update_post_meta($participant_id, 'gender', $gender);
                update_post_meta($participant_id, 'first_name', $first_name);
                update_post_meta($participant_id, 'last_name', $last_name);
                update_post_meta($participant_id, 'birth_date', $birth_date);
                update_post_meta($participant_id, 'nationality', $nationality);
                update_post_meta($participant_id, 'passport_no', $passport_no);
                update_post_meta($participant_id, 'passport_expiry_date', $passport_expiry);
                update_post_meta($participant_id, 'visa_number', $visa_number);
                update_post_meta($participant_id, 'visa_expiry_date', $visa_expiry_date);
                update_post_meta($participant_id, 'visa_note', $visa_note);
                update_post_meta($participant_id, 'participant_status', $participant_status);
                update_post_meta($participant_id, 'visa_status', $visa_status);
                update_post_meta($participant_id, 'room_assignment', $room_assignment);
                update_post_meta($participant_id, 'subgroup', $subgroup);
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
                    if (is_wp_error($passport_file_id)) $message = '<div class="alpenia-message">' . esc_html($passport_file_id->get_error_message()) . '</div>';
                    elseif (is_wp_error($photo_file_id)) $message = '<div class="alpenia-message">' . esc_html($photo_file_id->get_error_message()) . '</div>';
                    elseif (is_wp_error($visa_photo_file_id)) $message = '<div class="alpenia-message">' . esc_html($visa_photo_file_id->get_error_message()) . '</div>';
                    elseif (is_wp_error($meldezettel_file_id)) $message = '<div class="alpenia-message">' . esc_html($meldezettel_file_id->get_error_message()) . '</div>';
                } else {
                    if ($passport_file_id) update_post_meta($participant_id, 'passport_file_id', $passport_file_id);
                    if ($photo_file_id) update_post_meta($participant_id, 'photo_file_id', $photo_file_id);
                    if ($visa_photo_file_id) update_post_meta($participant_id, 'visa_photo_file_id', $visa_photo_file_id);
                    if ($meldezettel_file_id) update_post_meta($participant_id, 'meldezettel_file_id', $meldezettel_file_id);

                    $message = '<div class="alpenia-success">Teilnehmer erfolgreich aktualisiert.</div>';
                }
            }
        }
    }

    if (isset($_POST['create_reiseleiter']) && alpenia_user_can_manage_users()) {
        if (!isset($_POST['alpenia_create_user_nonce']) || !wp_verify_nonce($_POST['alpenia_create_user_nonce'], 'alpenia_create_user')) {
            $message = '<div class="alpenia-message">Sicherheitsfehler beim Anlegen des Benutzers.</div>';
        } else {
            $display_name = sanitize_text_field($_POST['display_name'] ?? '');
            $email        = sanitize_email($_POST['email'] ?? '');
            $password     = $_POST['password'] ?? '';
            $role         = sanitize_text_field($_POST['role'] ?? 'reiseleiter');

            if (empty($display_name) || empty($email) || empty($password)) {
                $message = '<div class="alpenia-message">Bitte Name, E-Mail und Passwort ausfüllen.</div>';
            } elseif (email_exists($email)) {
                $message = '<div class="alpenia-message">Diese E-Mail existiert bereits.</div>';
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
                    $message = '<div class="alpenia-message">Benutzer konnte nicht erstellt werden.</div>';
                } else {
                    wp_update_user([
                        'ID'           => $user_id,
                        'display_name' => $display_name,
                    ]);

                    $user_obj = new WP_User($user_id);
                    $user_obj->set_role($role);

                    $reloaded_user = get_user_by('id', $user_id);
                    if ($reloaded_user && in_array($role, (array) $reloaded_user->roles, true)) {
                        $message = '<div class="alpenia-success">Benutzer erfolgreich erstellt.</div>';
                    } else {
                        $message = '<div class="alpenia-message">Benutzer wurde erstellt, aber die Rolle konnte nicht korrekt gesetzt werden.</div>';
                    }
                }
            }
        }
    }

    if (alpenia_user_can_manage_users()) {

        if (isset($_GET['dashboard_deactivate_user'])) {
            $target_id = (int) $_GET['dashboard_deactivate_user'];

            if ($target_id > 0 && $target_id !== get_current_user_id()) {
                update_user_meta($target_id, 'alpenia_disabled', 1);
                $message = '<div class="alpenia-success">Benutzer deaktiviert.</div>';
            }
        }

        if (isset($_GET['dashboard_activate_user'])) {
            $target_id = (int) $_GET['dashboard_activate_user'];

            if ($target_id > 0) {
                delete_user_meta($target_id, 'alpenia_disabled');
                $message = '<div class="alpenia-success">Benutzer aktiviert.</div>';
            }
        }

        if (isset($_GET['dashboard_delete_user'])) {
            $target_id = (int) $_GET['dashboard_delete_user'];

            if (
                $target_id > 0 &&
                $target_id !== get_current_user_id() &&
                get_user_meta($target_id, 'alpenia_disabled', true)
            ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($target_id);
                $message = '<div class="alpenia-success">Benutzer gelöscht.</div>';
            }
        }

        if (isset($_POST['dashboard_update_user'])) {
            if (
                !isset($_POST['alpenia_dashboard_edit_user_nonce']) ||
                !wp_verify_nonce($_POST['alpenia_dashboard_edit_user_nonce'], 'alpenia_dashboard_edit_user')
            ) {
                $message = '<div class="alpenia-message">Sicherheitsfehler beim Bearbeiten des Benutzers.</div>';
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
                    $message = '<div class="alpenia-message">Bitte alle Pflichtfelder ausfüllen.</div>';
                } elseif ($existing_email_owner && (int) $existing_email_owner !== $edit_user_id) {
                    $message = '<div class="alpenia-message">Diese E-Mail wird bereits verwendet.</div>';
                } else {
                    $updated = wp_update_user([
                        'ID'           => $edit_user_id,
                        'display_name' => $edit_display_name,
                        'user_email'   => $edit_email,
                    ]);

                    if (is_wp_error($updated)) {
                        $message = '<div class="alpenia-message">Fehler beim Speichern.</div>';
                    } else {
                        $edited_user = new WP_User($edit_user_id);
                        $edited_user->set_role($edit_role);

                        if (!empty($edit_password)) {
                            wp_set_password($edit_password, $edit_user_id);
                        }

                        $message = '<div class="alpenia-success">Benutzer erfolgreich aktualisiert.</div>';
                    }
                }
            }
        }
    }

    $all_trips = alpenia_get_filtered_trips('', '', '', '', '', '');
    $filtered_trips = alpenia_get_filtered_trips(
        $trip_search,
        $trip_type_filter,
        $trip_status_filter,
        $trip_country_filter,
        $trip_city_filter,
        $guide_filter
    );

    if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) {
        $participants = get_posts([
            'post_type'   => 'trip_participant',
            'post_status' => 'publish',
            'numberposts' => -1,
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
    $open_payments_count = 0;
    $missing_docs_items = [];
    $open_payments_items = [];

    foreach ($participants as $participant) {
        $participant_id = $participant->ID;
        $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);

        if (!$trip_id || !alpenia_user_can_access_trip($trip_id)) continue;

        $score = alpenia_get_participant_doc_score($participant_id);
        $payment_open = alpenia_get_participant_payment_open($participant_id);

        if ($score !== 'complete') {
            $missing_docs_count++;
            $missing_docs_items[] = [
                'trip_id' => $trip_id,
                'trip_title' => get_the_title($trip_id),
                'participant_name' => trim(
                    get_post_meta($participant_id, 'first_name', true) . ' ' .
                    get_post_meta($participant_id, 'last_name', true)
                ),
                'missing_docs' => alpenia_get_missing_docs_details($participant_id),
                'participant_id' => $participant_id,
            ];
        }

        if ($payment_open > 0) {
            $open_payments_count++;
            $open_payments_items[] = [
                'trip_id' => $trip_id,
                'trip_title' => get_the_title($trip_id),
                'participant_name' => trim(
                    get_post_meta($participant_id, 'first_name', true) . ' ' .
                    get_post_meta($participant_id, 'last_name', true)
                ),
                'payment_open' => $payment_open,
                'payment_status' => alpenia_get_payment_status($participant_id),
                'participant_id' => $participant_id,
            ];
        }
    }

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

            <?php if (isset($_GET['create_trip']) && $_GET['create_trip'] == '1') : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1>Neue Reise erstellen</h1>
                            <p>Erstelle hier eine neue Kultur- oder Pilgerreise.</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>">Zurück zum Dashboard</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" class="alpenia-form">
                        <?php wp_nonce_field('alpenia_save_trip', 'alpenia_trip_nonce'); ?>

                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="trip_title">Reisetitel</label>
                                <input type="text" id="trip_title" name="trip_title" placeholder="z. B. Frankfurt – Umrah – 13.12.2026 bis 25.12.2026" required>
                            </div>

                            <div class="form-group">
                                <label for="trip_type">Reisetyp</label>
                                <select id="trip_type" name="trip_type" required>
                                    <option value="">Bitte wählen</option>
                                    <option value="kultur">Kulturtourismus</option>
                                    <option value="umrah">Hajj & Umrah</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trip_status">Reisestatus</label>
                                <select id="trip_status" name="trip_status" required>
                                    <option value="draft">Entwurf</option>
                                    <option value="open" selected>Offen</option>
                                    <option value="full">Voll</option>
                                    <option value="closed">Abgeschlossen</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="destination">Reiseziel</label>
                                <input type="text" id="destination" name="destination" placeholder="z. B. Mekka & Medina" required>
                            </div>

                            <div class="form-group">
                                <label for="country">Land</label>
                                <input type="text" id="country" name="country" placeholder="z. B. Deutschland" required>
                            </div>

                            <div class="form-group">
                                <label for="city">Stadt</label>
                                <input type="text" id="city" name="city" placeholder="z. B. Frankfurt" required>
                            </div>

                            <div class="form-group">
                                <label for="start_date">Startdatum</label>
                                <input type="date" id="start_date" name="start_date" required>
                            </div>

                            <div class="form-group">
                                <label for="end_date">Enddatum</label>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>

                            <div class="form-group">
                                <label for="meeting_date">Treffpunkt / Meeting Datum</label>
                                <input type="date" id="meeting_date" name="meeting_date">
                            </div>

                            <div class="form-group">
                                <label for="max_people">Max. Teilnehmer</label>
                                <input type="number" id="max_people" name="max_people" min="1" placeholder="z. B. 40" required>
                            </div>

                            <div class="form-group">
                                <label for="price">Standardpreis (€)</label>
                                <input type="number" id="price" name="price" min="0" step="0.01" placeholder="z. B. 1499" required>
                            </div>

                            <div class="form-group">
                                <label for="assigned_guide">Reiseleiter</label>
                                <select id="assigned_guide" name="assigned_guide">
                                    <option value="">Bitte wählen</option>
                                    <?php foreach ($guides as $guide) : ?>
                                        <option value="<?php echo esc_attr($guide->ID); ?>"><?php echo esc_html($guide->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full">
                                <label for="whatsapp_link">WhatsApp Gruppenlink</label>
                                <input type="url" id="whatsapp_link" name="whatsapp_link" placeholder="https://chat.whatsapp.com/...">
                            </div>

                            <div class="form-group full">
                                <label for="internal_notes">Interne Notizen</label>
                                <input type="text" id="internal_notes" name="internal_notes" placeholder="Interne Hinweise zur Reise">
                            </div>
                        </div>

                        <button type="submit" name="save_trip" class="btn-primary">Reise speichern</button>
                    </form>
                </div>

            <?php elseif (isset($_GET['add_participant']) && $_GET['add_participant'] == '1' && !isset($_POST['generate_participant_fields'])) : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1>Teilnehmer hinzufügen</h1>
                            <p>Wähle die Reise und gib an, wie viele Teilnehmer du erfassen willst.</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>">Zurück zum Dashboard</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" class="alpenia-form">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label for="trip_id">Reise auswählen</label>
                                <select id="trip_id" name="trip_id" required>
                                    <option value="">Bitte Reise wählen</option>
                                    <?php foreach ($filtered_trips as $trip) : ?>
                                        <option value="<?php echo esc_attr($trip->ID); ?>">
                                            <?php echo esc_html($trip->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="participant_count">Anzahl Teilnehmer</label>
                                <input type="number" id="participant_count" name="participant_count" min="1" value="1" required>
                            </div>
                        </div>

                        <button type="submit" name="generate_participant_fields" class="btn-primary">Weiter</button>
                    </form>
                </div>

            <?php elseif (isset($_GET['add_participant']) && $_GET['add_participant'] == '1' && isset($_POST['generate_participant_fields'])) : ?>

                <?php
                $selected_trip_id = (int) ($_POST['trip_id'] ?? 0);
                $participant_count = max(1, (int) ($_POST['participant_count'] ?? 1));

                if (!$selected_trip_id || !alpenia_user_can_access_trip($selected_trip_id)) {
                    return '<div class="alpenia-message">Kein Zugriff auf diese Reise.</div>';
                }

                $all_countries_list = alpenia_get_all_countries();
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1>Teilnehmerdaten erfassen</h1>
                            <p>Bitte alle Pflichtfelder pro Person ausfüllen.</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['add_participant' => 1])); ?>">Zurück</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <datalist id="alpenia-country-list">
                        <?php foreach ($all_countries_list as $country_name) : ?>
                            <option value="<?php echo esc_attr($country_name); ?>">
                        <?php endforeach; ?>
                    </datalist>

                    <form method="post" enctype="multipart/form-data" class="alpenia-form">
                        <?php wp_nonce_field('alpenia_save_participants_batch', 'alpenia_participant_batch_nonce'); ?>
                        <input type="hidden" name="trip_id" value="<?php echo esc_attr($selected_trip_id); ?>">
                        <input type="hidden" name="participant_count" value="<?php echo esc_attr($participant_count); ?>">

                        <?php for ($i = 1; $i <= $participant_count; $i++) : ?>
                            <div class="participant-box">
                                <h3>Teilnehmer <?php echo $i; ?></h3>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="gender_<?php echo $i; ?>">Anrede <span class="required-mark">*</span></label>
                                        <select id="gender_<?php echo $i; ?>" name="gender_<?php echo $i; ?>" required>
                                            <option value="">Bitte wählen</option>
                                            <option value="Herr">Herr</option>
                                            <option value="Frau">Frau</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="birth_date_<?php echo $i; ?>">Geburtsdatum</label>
                                        <input type="date" id="birth_date_<?php echo $i; ?>" name="birth_date_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="nationality_<?php echo $i; ?>">Staatsbürgerschaft <span class="required-mark">*</span></label>
                                        <input type="text" id="nationality_<?php echo $i; ?>" name="nationality_<?php echo $i; ?>" list="alpenia-country-list" placeholder="z. B. Deutschland" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="first_name_<?php echo $i; ?>">Vorname <span class="required-mark">*</span></label>
                                        <input type="text" id="first_name_<?php echo $i; ?>" name="first_name_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="last_name_<?php echo $i; ?>">Nachname <span class="required-mark">*</span></label>
                                        <input type="text" id="last_name_<?php echo $i; ?>" name="last_name_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_no_<?php echo $i; ?>">Passnummer</label>
                                        <input type="text" id="passport_no_<?php echo $i; ?>" name="passport_no_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_expiry_date_<?php echo $i; ?>">Pass Enddatum <span class="required-mark">*</span></label>
                                        <input type="date" id="passport_expiry_date_<?php echo $i; ?>" name="passport_expiry_date_<?php echo $i; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="participant_status_<?php echo $i; ?>">Bearbeitungsstatus</label>
                                        <select id="participant_status_<?php echo $i; ?>" name="participant_status_<?php echo $i; ?>">
                                            <option value="neu">Neu</option>
                                            <option value="in_pruefung">In Prüfung</option>
                                            <option value="vollstaendig">Vollständig</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="visa_status_<?php echo $i; ?>">Visa Status</label>
                                        <select id="visa_status_<?php echo $i; ?>" name="visa_status_<?php echo $i; ?>">
                                            <option value="nicht begonnen">Nicht begonnen</option>
                                            <option value="beantragt">Beantragt</option>
                                            <option value="genehmigt">Genehmigt</option>
                                            <option value="abgelehnt">Abgelehnt</option>
                                        </select>
                                    </div>

                                    <div class="form-group visa-field visa-field-<?php echo $i; ?>">
                                        <label for="visa_number_<?php echo $i; ?>">Visa Nummer</label>
                                        <input type="text" id="visa_number_<?php echo $i; ?>" name="visa_number_<?php echo $i; ?>" placeholder="Visa number for entry country">
                                    </div>

                                    <div class="form-group visa-field visa-field-<?php echo $i; ?>">
                                        <label for="visa_expiry_date_<?php echo $i; ?>">Visa Gültig bis</label>
                                        <input type="date" id="visa_expiry_date_<?php echo $i; ?>" name="visa_expiry_date_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full visa-field visa-field-<?php echo $i; ?>">
                                        <label for="visa_note_<?php echo $i; ?>">Visa Bemerkung</label>
                                        <input type="text" id="visa_note_<?php echo $i; ?>" name="visa_note_<?php echo $i; ?>" placeholder="z. B. Entry visa for Saudi Arabia">
                                    </div>

                                    <div class="form-group">
                                        <label for="room_assignment_<?php echo $i; ?>">Zimmer</label>
                                        <input type="text" id="room_assignment_<?php echo $i; ?>" name="room_assignment_<?php echo $i; ?>" placeholder="z. B. Zimmer 204">
                                    </div>

                                    <div class="form-group">
                                        <label for="subgroup_<?php echo $i; ?>">Untergruppe / Busgruppe</label>
                                        <input type="text" id="subgroup_<?php echo $i; ?>" name="subgroup_<?php echo $i; ?>" placeholder="z. B. Bus A">
                                    </div>

                                    <div class="form-group">
                                        <label for="payment_total_<?php echo $i; ?>">Gesamtpreis (€)</label>
                                        <input type="number" step="0.01" min="0" id="payment_total_<?php echo $i; ?>" name="payment_total_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="payment_deposit_<?php echo $i; ?>">Anzahlung (€)</label>
                                        <input type="number" step="0.01" min="0" id="payment_deposit_<?php echo $i; ?>" name="payment_deposit_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group full">
                                        <label for="payment_paid_<?php echo $i; ?>">Bereits bezahlt (€)</label>
                                        <input type="number" step="0.01" min="0" id="payment_paid_<?php echo $i; ?>" name="payment_paid_<?php echo $i; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="passport_file_<?php echo $i; ?>">Pass hochladen <span class="required-mark">*</span> <small>(max. 5 MB)</small></label>
                                        <input type="file" id="passport_file_<?php echo $i; ?>" name="passport_file_<?php echo $i; ?>" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="photo_file_<?php echo $i; ?>">Foto hochladen <span class="required-mark">*</span> <small>(max. 2 MB)</small></label>
                                        <input type="file" id="photo_file_<?php echo $i; ?>" name="photo_file_<?php echo $i; ?>" accept=".jpg,.jpeg,.png" required>
                                    </div>

                                    <div class="form-group full visa-field visa-field-<?php echo $i; ?>">
                                        <label for="visa_photo_file_<?php echo $i; ?>">Visa Foto hochladen <small>(max. 2 MB)</small></label>
                                        <input type="file" id="visa_photo_file_<?php echo $i; ?>" name="visa_photo_file_<?php echo $i; ?>" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>

                                    <div class="form-group full">
                                        <label for="meldezettel_file_<?php echo $i; ?>">Meldezettel hochladen <small>(max. 5 MB)</small></label>
                                        <input type="file" id="meldezettel_file_<?php echo $i; ?>" name="meldezettel_file_<?php echo $i; ?>" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>

                                    <div class="form-group full">
                                        <label>Checkliste</label>
                                        <div class="check-grid">
                                            <label class="checkbox-line"><input type="checkbox" name="check_passport_<?php echo $i; ?>" value="1"> Pass geprüft</label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_photo_<?php echo $i; ?>" value="1"> Foto geprüft</label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_visa_<?php echo $i; ?>" value="1"> Visa geprüft</label>
                                            <label class="checkbox-line"><input type="checkbox" name="check_payment_<?php echo $i; ?>" value="1"> Zahlung geprüft</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <button type="submit" name="save_participants_batch" class="btn-primary">Alle Teilnehmer speichern</button>
                    </form>
                </div>

            <?php elseif (isset($_GET['edit_participant'])) : ?>

                <?php
                $participant_id = (int) $_GET['edit_participant'];

                if (!alpenia_user_can_access_participant($participant_id)) {
                    return '<div class="alpenia-message">Kein Zugriff.</div>';
                }

                $participant = get_post($participant_id);
                $trip_id = (int) get_post_meta($participant_id, 'trip_id', true);

                $gender             = get_post_meta($participant_id, 'gender', true);
                $first_name         = get_post_meta($participant_id, 'first_name', true);
                $last_name          = get_post_meta($participant_id, 'last_name', true);
                $birth_date         = get_post_meta($participant_id, 'birth_date', true);
                $nationality        = get_post_meta($participant_id, 'nationality', true);
                $passport_no        = get_post_meta($participant_id, 'passport_no', true);
                $passport_expiry    = get_post_meta($participant_id, 'passport_expiry_date', true);
                $visa_number        = get_post_meta($participant_id, 'visa_number', true);
                $visa_expiry_date   = get_post_meta($participant_id, 'visa_expiry_date', true);
                $visa_note          = get_post_meta($participant_id, 'visa_note', true);
                $participant_status = get_post_meta($participant_id, 'participant_status', true);
                $visa_status        = get_post_meta($participant_id, 'visa_status', true);
                $room_assignment    = get_post_meta($participant_id, 'room_assignment', true);
                $subgroup           = get_post_meta($participant_id, 'subgroup', true);
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
                            <h1>Teilnehmer bearbeiten</h1>
                            <p><?php echo esc_html($participant ? $participant->post_title : ''); ?></p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $trip_id])); ?>">Zurück zur Reise</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <form method="post" enctype="multipart/form-data" class="alpenia-form">
                        <?php wp_nonce_field('alpenia_edit_participant_' . $participant_id, 'alpenia_edit_participant_nonce'); ?>
                        <input type="hidden" name="participant_id" value="<?php echo esc_attr($participant_id); ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="gender">Anrede <span class="required-mark">*</span></label>
                                <select id="gender" name="gender" required>
                                    <option value="">Bitte wählen</option>
                                    <option value="Herr" <?php selected($gender, 'Herr'); ?>>Herr</option>
                                    <option value="Frau" <?php selected($gender, 'Frau'); ?>>Frau</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="birth_date">Geburtsdatum</label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo esc_attr($birth_date); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nationality">Staatsbürgerschaft <span class="required-mark">*</span></label>
                                <input type="text" id="nationality" name="nationality" value="<?php echo esc_attr($nationality); ?>" list="alpenia-country-list-edit" required>
                                <datalist id="alpenia-country-list-edit">
                                    <?php foreach (alpenia_get_all_countries() as $country_name) : ?>
                                        <option value="<?php echo esc_attr($country_name); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="form-group">
                                <label for="first_name">Vorname <span class="required-mark">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Nachname <span class="required-mark">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="passport_no">Passnummer</label>
                                <input type="text" id="passport_no" name="passport_no" value="<?php echo esc_attr($passport_no); ?>">
                            </div>

                            <div class="form-group">
                                <label for="passport_expiry_date">Pass Enddatum <span class="required-mark">*</span></label>
                                <input type="date" id="passport_expiry_date" name="passport_expiry_date" value="<?php echo esc_attr($passport_expiry); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="participant_status">Bearbeitungsstatus</label>
                                <select id="participant_status" name="participant_status">
                                    <option value="neu" <?php selected($participant_status, 'neu'); ?>>Neu</option>
                                    <option value="in_pruefung" <?php selected($participant_status, 'in_pruefung'); ?>>In Prüfung</option>
                                    <option value="vollstaendig" <?php selected($participant_status, 'vollstaendig'); ?>>Vollständig</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="visa_status">Visa Status</label>
                                <select id="visa_status" name="visa_status">
                                    <option value="nicht begonnen" <?php selected($visa_status, 'nicht begonnen'); ?>>Nicht begonnen</option>
                                    <option value="beantragt" <?php selected($visa_status, 'beantragt'); ?>>Beantragt</option>
                                    <option value="genehmigt" <?php selected($visa_status, 'genehmigt'); ?>>Genehmigt</option>
                                    <option value="abgelehnt" <?php selected($visa_status, 'abgelehnt'); ?>>Abgelehnt</option>
                                </select>
                            </div>

                            <div class="form-group edit-visa-field">
                                <label for="visa_number">Visa Nummer</label>
                                <input type="text" id="visa_number" name="visa_number" value="<?php echo esc_attr($visa_number); ?>">
                            </div>

                            <div class="form-group edit-visa-field">
                                <label for="visa_expiry_date">Visa Ablaufdatum</label>
                                <input type="date" id="visa_expiry_date" name="visa_expiry_date" value="<?php echo esc_attr($visa_expiry_date); ?>">
                            </div>

                            <div class="form-group full edit-visa-field">
                                <label for="visa_note">Visa Bemerkung</label>
                                <input type="text" id="visa_note" name="visa_note" value="<?php echo esc_attr($visa_note); ?>" placeholder="z. B. Entry visa for Saudi Arabia">
                            </div>

                            <div class="form-group">
                                <label for="room_assignment">Zimmer</label>
                                <input type="text" id="room_assignment" name="room_assignment" value="<?php echo esc_attr($room_assignment); ?>">
                            </div>

                            <div class="form-group">
                                <label for="subgroup">Untergruppe / Busgruppe</label>
                                <input type="text" id="subgroup" name="subgroup" value="<?php echo esc_attr($subgroup); ?>">
                            </div>

                            <div class="form-group">
                                <label for="payment_total">Gesamtpreis (€)</label>
                                <input type="number" step="0.01" min="0" id="payment_total" name="payment_total" value="<?php echo esc_attr($payment_total); ?>">
                            </div>

                            <div class="form-group">
                                <label for="payment_deposit">Anzahlung (€)</label>
                                <input type="number" step="0.01" min="0" id="payment_deposit" name="payment_deposit" value="<?php echo esc_attr($payment_deposit); ?>">
                            </div>

                            <div class="form-group full">
                                <label for="payment_paid">Bereits bezahlt (€)</label>
                                <input type="number" step="0.01" min="0" id="payment_paid" name="payment_paid" value="<?php echo esc_attr($payment_paid); ?>">
                            </div>

                            <div class="form-group">
                                <label for="passport_file">Neuen Pass hochladen <small>(max. 5 MB)</small></label>
                                <input type="file" id="passport_file" name="passport_file" accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <div class="form-group">
                                <label for="photo_file">Neues Foto hochladen <small>(max. 2 MB)</small></label>
                                <input type="file" id="photo_file" name="photo_file" accept=".jpg,.jpeg,.png">
                            </div>

                            <div class="form-group full edit-visa-field">
                                <label for="visa_photo_file">Neues Visa Foto hochladen <small>(max. 2 MB)</small></label>
                                <input type="file" id="visa_photo_file" name="visa_photo_file" accept=".jpg,.jpeg,.png,.pdf">
                            </div>

                            <div class="form-group full">
                                <label for="meldezettel_file">Neuen Meldezettel hochladen <small>(max. 5 MB)</small></label>
                                <input type="file" id="meldezettel_file" name="meldezettel_file" accept=".pdf,.jpg,.jpeg,.png">
                            </div>

                            <div class="form-group full">
                                <label>Checkliste</label>
                                <div class="check-grid">
                                    <label class="checkbox-line"><input type="checkbox" name="check_passport" value="1" <?php checked($check_passport, 1); ?>> Pass geprüft</label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_photo" value="1" <?php checked($check_photo, 1); ?>> Foto geprüft</label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_visa" value="1" <?php checked($check_visa, 1); ?>> Visa geprüft</label>
                                    <label class="checkbox-line"><input type="checkbox" name="check_payment" value="1" <?php checked($check_payment, 1); ?>> Zahlung geprüft</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_participant" class="btn-primary">Änderungen speichern</button>
                    </form>
                </div>

            <?php elseif (isset($_GET['view_trip'])) : ?>

                <?php
                $view_trip_id = (int) $_GET['view_trip'];

                if (!alpenia_user_can_access_trip($view_trip_id)) {
                    return '<div class="alpenia-message">Kein Zugriff.</div>';
                }

                $trip = get_post($view_trip_id);
                $trip_participants = alpenia_get_trip_participants($view_trip_id);
                $assigned_guide_id = (int) get_post_meta($view_trip_id, 'assigned_guide', true);
                $assigned_guide_name = $assigned_guide_id ? get_the_author_meta('display_name', $assigned_guide_id) : '—';
                $delete_trip_nonce = wp_create_nonce('alpenia_delete_trip_' . $view_trip_id);
                ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1><?php echo esc_html($trip ? $trip->post_title : 'Reise'); ?></h1>
                            <p>Teilnehmerliste dieser Reise</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['export_trip_csv' => $view_trip_id])); ?>">CSV Export</a>
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['print_trip' => $view_trip_id])); ?>" target="_blank">PDF / Drucken</a>
                        <?php if (alpenia_user_can_delete_trip($view_trip_id)) : ?>
                            <a class="btn-secondary table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['delete_trip' => $view_trip_id, '_delete_trip_nonce' => $delete_trip_nonce])); ?>" onclick="return confirm('Reise wirklich löschen? Alle zugehörigen Teilnehmer werden ebenfalls gelöscht.');">Reise löschen</a>
                        <?php endif; ?>
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>">Zurück zum Dashboard</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="trip-meta-grid">
                        <div class="trip-meta-box"><strong>Reisetyp</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'trip_type', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>Status</strong><span><?php echo wp_kses_post(alpenia_trip_status_badge(get_post_meta($view_trip_id, 'trip_status', true))); ?></span></div>
                        <div class="trip-meta-box"><strong>Ziel</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'destination', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>Land</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'country', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>Stadt</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'city', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>Zeitraum</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'start_date', true)); ?> – <?php echo esc_html(get_post_meta($view_trip_id, 'end_date', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>Freie Plätze</strong><span><?php echo esc_html(alpenia_get_trip_capacity_left($view_trip_id)); ?></span></div>
                        <div class="trip-meta-box"><strong>Reiseleiter</strong><span><?php echo esc_html($assigned_guide_name); ?></span></div>
                        <div class="trip-meta-box"><strong>Treffpunkt</strong><span><?php echo esc_html(get_post_meta($view_trip_id, 'meeting_date', true)); ?></span></div>
                        <div class="trip-meta-box"><strong>WhatsApp</strong><span><?php $wa = get_post_meta($view_trip_id, 'whatsapp_link', true); echo $wa ? '<a href="'.esc_url($wa).'" target="_blank">Öffnen</a>' : '—'; ?></span></div>
                    </div>

                    <?php $notes = get_post_meta($view_trip_id, 'internal_notes', true); ?>
                    <?php if (!empty($notes)) : ?>
                        <div style="margin-top:20px;">
                            <strong>Interne Notizen</strong>
                            <div class="notes-box"><?php echo esc_html($notes); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2>Teilnehmer dieser Reise</h2>

                    <?php if (!empty($trip_participants)) : ?>
                        <div class="table-wrap">
                            <table class="alpenia-table">
                                <thead>
                                    <tr>
                                        <th>Anrede</th>
                                        <th>Name</th>
                                        <th>Dokumente</th>
                                        <th>Visa</th>
                                        <th>Staatsbürgerschaft</th>
                                        <th>Pass Nr.</th>
                                        <th>Pass gültig bis</th>
                                        <th>Visa Nummer</th>
                                        <th>Visa gültig bis</th>
                                        <th>Visa Bemerkung</th>
                                        <th>Visa Foto</th>
                                        <th>Status</th>
                                        <th>Zahlung</th>
                                        <th>Zimmer / Gruppe</th>
                                        <th>Pass</th>
                                        <th>Foto</th>
                                        <th>Meldezettel</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trip_participants as $participant) :
                                        $gender = get_post_meta($participant->ID, 'gender', true);
                                        $first_name = get_post_meta($participant->ID, 'first_name', true);
                                        $last_name = get_post_meta($participant->ID, 'last_name', true);
                                        $passport_file_id = (int) get_post_meta($participant->ID, 'passport_file_id', true);
                                        $photo_file_id = (int) get_post_meta($participant->ID, 'photo_file_id', true);
                                        $visa_photo_file_id = (int) get_post_meta($participant->ID, 'visa_photo_file_id', true);
                                        $meldezettel_file_id = (int) get_post_meta($participant->ID, 'meldezettel_file_id', true);
                                        $delete_nonce = wp_create_nonce('alpenia_delete_participant_' . $participant->ID);
                                        $payment_open = alpenia_get_participant_payment_open($participant->ID);
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html($gender); ?></td>
                                            <td>
                                                <strong><?php echo esc_html(trim($first_name . ' ' . $last_name)); ?></strong><br>
                                                <small><?php echo esc_html(get_post_meta($participant->ID, 'passport_no', true)); ?></small>
                                            </td>
                                            <td><?php echo wp_kses_post(alpenia_get_participant_doc_badge($participant->ID)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_status', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'nationality', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'passport_no', true) ?: '—'); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'passport_expiry_date', true) ?: '—'); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_number', true) ?: '—'); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_expiry_date', true) ?: '—'); ?></td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_note', true) ?: '—'); ?></td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($visa_photo_file_id, true)); ?>
                                                <?php if ($visa_photo_file_id) : ?>
                                                    <br><a href="<?php echo alpenia_attachment_link($visa_photo_file_id); ?>" target="_blank">Öffnen</a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html(get_post_meta($participant->ID, 'participant_status', true)); ?></td>
                                            <td>
                                                <?php echo esc_html(alpenia_get_payment_status($participant->ID)); ?><br>
                                                <small>Offen: € <?php echo esc_html(number_format($payment_open, 2, ',', '.')); ?></small>
                                            </td>
                                            <td>
                                                Zimmer: <?php echo esc_html(get_post_meta($participant->ID, 'room_assignment', true) ?: '—'); ?><br>
                                                Gruppe: <?php echo esc_html(get_post_meta($participant->ID, 'subgroup', true) ?: '—'); ?>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($passport_file_id, false)); ?>
                                                <?php if ($passport_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($passport_file_id); ?>" target="_blank">Öffnen</a><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($photo_file_id, false)); ?>
                                                <?php if ($photo_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($photo_file_id); ?>" target="_blank">Öffnen</a><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo wp_kses_post(alpenia_doc_status_label($meldezettel_file_id, true)); ?>
                                                <?php if ($meldezettel_file_id) : ?><br><a href="<?php echo alpenia_attachment_link($meldezettel_file_id); ?>" target="_blank">Öffnen</a><?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="row-actions">
                                                    <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $participant->ID])); ?>">Bearbeiten</a>
                                                    <a class="table-btn table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $view_trip_id, 'delete_participant' => $participant->ID, '_delete_nonce' => $delete_nonce])); ?>" onclick="return confirm('Teilnehmer wirklich löschen?');">Löschen</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p>Noch keine Teilnehmer für diese Reise vorhanden.</p>
                    <?php endif; ?>
                </div>

            <?php elseif (isset($_GET['manage_users']) && alpenia_user_can_manage_users()) : ?>

                <div class="dashboard-top">
                    <div class="dashboard-brand">
                        <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Alpenia Travel Logo" class="dashboard-logo">
                        <?php endif; ?>
                        <div class="dashboard-brand-text">
                            <h1>Benutzerverwaltung</h1>
                            <p>Reiseleiter und Backoffice verwalten</p>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link()); ?>">Zurück zum Dashboard</a>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="panel">
                    <?php if ($dashboard_edit_mode && $dashboard_edit_user) : ?>
                        <h2>Benutzer bearbeiten</h2>

                        <form method="post" class="alpenia-form">
                            <?php wp_nonce_field('alpenia_dashboard_edit_user', 'alpenia_dashboard_edit_user_nonce'); ?>
                            <input type="hidden" name="edit_user_id" value="<?php echo (int) $dashboard_edit_user->ID; ?>">

                            <div class="form-grid">
                                <div class="form-group full">
                                    <label for="edit_display_name">Name</label>
                                    <input type="text" id="edit_display_name" name="edit_display_name" value="<?php echo esc_attr($dashboard_edit_user->display_name ?: $dashboard_edit_user->user_login); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label for="edit_email">E-Mail</label>
                                    <input type="email" id="edit_email" name="edit_email" value="<?php echo esc_attr($dashboard_edit_user->user_email); ?>" required>
                                </div>

                                <div class="form-group full">
                                    <label for="edit_password">Neues Passwort (leer lassen = unverändert)</label>
                                    <input type="text" id="edit_password" name="edit_password" placeholder="Neues Passwort">
                                </div>

                                <div class="form-group full">
                                    <label>Rolle</label>
                                    <div class="role-select-grid">
                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="reiseleiter" <?php checked(in_array('reiseleiter', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span>Reiseleiter</span>
                                        </label>

                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="administrator" <?php checked(in_array('administrator', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span>Admin</span>
                                        </label>

                                        <label class="role-select-box">
                                            <input type="radio" name="edit_role" value="backoffice" <?php checked(in_array('backoffice', (array) $dashboard_edit_user->roles, true)); ?>>
                                            <span>Backoffice</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="role-action-grid">
                                <button type="submit" name="dashboard_update_user" class="btn-primary">Änderungen speichern</button>
                                <a class="btn-secondary" href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1])); ?>">Abbrechen</a>
                            </div>
                        </form>

                    <?php else : ?>
                        <h2>Neuen Benutzer anlegen</h2>

                        <form method="post" class="alpenia-form">
                            <?php wp_nonce_field('alpenia_create_user', 'alpenia_create_user_nonce'); ?>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="display_name">Name</label>
                                    <input type="text" id="display_name" name="display_name" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">E-Mail</label>
                                    <input type="email" id="email" name="email" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Passwort</label>
                                    <input type="text" id="password" name="password" required>
                                </div>

                                <div class="form-group">
                                    <label for="role">Rolle</label>
                                    <select id="role" name="role" required>
                                        <option value="reiseleiter">Reiseleiter</option>
                                        <option value="backoffice">Backoffice</option>
                                        <option value="administrator">Administrator</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" name="create_reiseleiter" class="btn-primary">Benutzer erstellen</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2>Benutzerliste</h2>
                    <div class="table-wrap">
                        <table class="alpenia-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>E-Mail</th>
                                    <th>Rolle</th>
                                    <th>Status</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard_users as $user) :
                                    $disabled = get_user_meta($user->ID, 'alpenia_disabled', true);
                                    $status = $disabled ? 'Deaktiviert' : 'Aktiv';

                                    $role_label = '-';
                                    if (in_array('administrator', (array) $user->roles, true)) {
                                        $role_label = 'Admin';
                                    } elseif (in_array('reiseleiter', (array) $user->roles, true)) {
                                        $role_label = 'Reiseleiter';
                                    } elseif (in_array('backoffice', (array) $user->roles, true)) {
                                        $role_label = 'Backoffice';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($user->display_name); ?></td>
                                        <td><?php echo esc_html($user->user_email); ?></td>
                                        <td><?php echo esc_html($role_label); ?></td>
                                        <td><?php echo esc_html($status); ?></td>
                                        <td>
                                            <?php if ((int) $user->ID === (int) get_current_user_id()) : ?>
                                                Eigener Account
                                            <?php else : ?>
                                                <div class="user-action-links">
                                                    <a href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1, 'dashboard_edit_user' => (int) $user->ID])); ?>">Bearbeiten</a>

                                                    <?php if ($disabled) : ?>
                                                        <a href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1, 'dashboard_activate_user' => (int) $user->ID])); ?>">Aktivieren</a>
                                                        <a class="delete-link" href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1, 'dashboard_delete_user' => (int) $user->ID])); ?>" onclick="return confirm('Benutzer wirklich löschen?');">Löschen</a>
                                                    <?php else : ?>
                                                        <a href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1, 'dashboard_deactivate_user' => (int) $user->ID])); ?>" onclick="return confirm('Benutzer wirklich deaktivieren?');">Deaktivieren</a>
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
                            <h1>Alpenia Travel Dashboard</h1>
                            <p><?php echo esc_html($current_user->display_name); ?>, willkommen im Dashboard</p>
                        </div>
                    </div>

                    <div class="actions">
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['create_trip' => 1])); ?>">Neue Reise erstellen</a>
                        <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['add_participant' => 1])); ?>">Teilnehmer hinzufügen</a>
                        <?php if (alpenia_user_can_manage_users()) : ?>
                            <a class="btn-primary" href="<?php echo esc_url(alpenia_dashboard_link(['manage_users' => 1])); ?>">Benutzerverwaltung</a>
                        <?php endif; ?>
                        <?php echo alpenia_dashboard_logout_button(); ?>
                    </div>
                </div>

                <div class="cards">
                    <div class="card">
                        <h3>Reisen</h3>
                        <span><?php echo esc_html($total_trips); ?></span>
                    </div>

                    <div class="card">
                        <h3>Teilnehmer</h3>
                        <span><?php echo esc_html($total_participants); ?></span>
                    </div>

                    <div class="card">
                        <h3>Fehlende Unterlagen</h3>
                        <span><?php echo esc_html($missing_docs_count); ?></span>
                    </div>

                    <div class="card">
                        <h3>Offene Zahlungen</h3>
                        <span><?php echo esc_html($open_payments_count); ?></span>
                    </div>
                </div>

                <div class="panel">
                    <h2>Reisen mit Teilnehmerliste</h2>

                    <form method="get" class="filter-bar">
                        <input type="text" name="trip_search" value="<?php echo esc_attr($trip_search); ?>" placeholder="Reise oder Ziel suchen">

                        <select name="trip_type_filter">
                            <option value="">Alle Reisearten</option>
                            <option value="kultur" <?php selected($trip_type_filter, 'kultur'); ?>>Kulturtourismus</option>
                            <option value="umrah" <?php selected($trip_type_filter, 'umrah'); ?>>Hajj & Umrah</option>
                        </select>

                        <select name="trip_status_filter">
                            <option value="">Alle Status</option>
                            <option value="draft" <?php selected($trip_status_filter, 'draft'); ?>>Entwurf</option>
                            <option value="open" <?php selected($trip_status_filter, 'open'); ?>>Offen</option>
                            <option value="full" <?php selected($trip_status_filter, 'full'); ?>>Voll</option>
                            <option value="closed" <?php selected($trip_status_filter, 'closed'); ?>>Abgeschlossen</option>
                        </select>

                        <select name="trip_country_filter">
                            <option value="">Alle Länder</option>
                            <?php foreach ($countries as $country_option) : ?>
                                <option value="<?php echo esc_attr($country_option); ?>" <?php selected($trip_country_filter, $country_option); ?>>
                                    <?php echo esc_html($country_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="trip_city_filter">
                            <option value="">Alle Städte</option>
                            <?php foreach ($cities as $city_option) : ?>
                                <option value="<?php echo esc_attr($city_option); ?>" <?php selected($trip_city_filter, $city_option); ?>>
                                    <?php echo esc_html($city_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) : ?>
                            <select name="guide_filter">
                                <option value="">Alle Reiseleiter</option>
                                <?php foreach ($guides as $guide) : ?>
                                    <option value="<?php echo esc_attr($guide->ID); ?>" <?php selected($guide_filter, $guide->ID); ?>>
                                        <?php echo esc_html($guide->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                        <button type="submit" class="btn-primary">Filtern</button>
                        <a href="<?php echo esc_url(alpenia_dashboard_link()); ?>" class="btn-secondary">Zurücksetzen</a>
                    </form>

                    <?php if ($filtered_trips) : ?>
                        <ul class="list-table">
                            <?php foreach ($filtered_trips as $trip) :
                                $trip_participants = alpenia_get_trip_participants($trip->ID);
                                $guide_id = (int) get_post_meta($trip->ID, 'assigned_guide', true);
                                $guide_name = $guide_id ? get_the_author_meta('display_name', $guide_id) : '—';
                                $delete_trip_nonce = wp_create_nonce('alpenia_delete_trip_' . $trip->ID);
                            ?>
                                <li>
                                    <div class="list-main">
                                        <strong><?php echo esc_html($trip->post_title); ?></strong>
                                        <span>
                                            <?php echo esc_html(get_post_meta($trip->ID, 'destination', true)); ?>
                                            ·
                                            <?php echo esc_html(get_post_meta($trip->ID, 'country', true)); ?>
                                            / <?php echo esc_html(get_post_meta($trip->ID, 'city', true)); ?>
                                            ·
                                            <?php echo esc_html(get_post_meta($trip->ID, 'trip_type', true)); ?>
                                            ·
                                            <?php echo wp_kses_post(alpenia_trip_status_badge(get_post_meta($trip->ID, 'trip_status', true))); ?>
                                        </span>
                                        <span>
                                            Freie Plätze: <?php echo esc_html(alpenia_get_trip_capacity_left($trip->ID)); ?>
                                            · Reiseleiter: <?php echo esc_html($guide_name); ?>
                                        </span>
                                    </div>
                                    <div class="list-actions">
                                        <span class="badge"><?php echo count($trip_participants); ?> Teilnehmer</span>
                                        <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $trip->ID])); ?>">Teilnehmer ansehen</a>
                                        <?php if (alpenia_user_can_delete_trip($trip->ID)) : ?>
                                            <a class="table-btn table-btn-danger" href="<?php echo esc_url(alpenia_dashboard_link(['delete_trip' => $trip->ID, '_delete_trip_nonce' => $delete_trip_nonce])); ?>" onclick="return confirm('Reise wirklich löschen? Alle zugehörigen Teilnehmer werden ebenfalls gelöscht.');">Löschen</a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>Keine Reisen für diese Suche / Filter gefunden.</p>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2>Fehlende Unterlagen im Überblick</h2>

                    <?php if (!empty($missing_docs_items)) : ?>
                        <div class="table-wrap">
                            <table class="alpenia-table">
                                <thead>
                                    <tr>
                                        <th>Reise</th>
                                        <th>Teilnehmer</th>
                                        <th>Fehlende Unterlagen</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($missing_docs_items as $item) : ?>
                                        <tr>
                                            <td><?php echo esc_html($item['trip_title']); ?></td>
                                            <td><?php echo esc_html($item['participant_name']); ?></td>
                                            <td><?php echo esc_html(!empty($item['missing_docs']) ? implode(', ', $item['missing_docs']) : '—'); ?></td>
                                            <td>
                                                <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $item['trip_id']])); ?>">Reise öffnen</a>
                                                <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $item['participant_id']])); ?>">Teilnehmer öffnen</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p>Aktuell keine fehlenden Unterlagen.</p>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2>Offene Zahlungen im Überblick</h2>

                    <?php if (!empty($open_payments_items)) : ?>
                        <div class="table-wrap">
                            <table class="alpenia-table">
                                <thead>
                                    <tr>
                                        <th>Reise</th>
                                        <th>Teilnehmer</th>
                                        <th>Offener Betrag</th>
                                        <th>Zahlungsstatus</th>
                                        <th>Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($open_payments_items as $item) : ?>
                                        <tr>
                                            <td><?php echo esc_html($item['trip_title']); ?></td>
                                            <td><?php echo esc_html($item['participant_name']); ?></td>
                                            <td>€ <?php echo esc_html(number_format($item['payment_open'], 2, ',', '.')); ?></td>
                                            <td><?php echo esc_html($item['payment_status']); ?></td>
                                            <td>
                                                <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['view_trip' => $item['trip_id']])); ?>">Reise öffnen</a>
                                                <a class="table-btn" href="<?php echo esc_url(alpenia_dashboard_link(['edit_participant' => $item['participant_id']])); ?>">Teilnehmer öffnen</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p>Aktuell keine offenen Zahlungen.</p>
                    <?php endif; ?>
                </div>

                <div class="panel" style="margin-top:20px;">
                    <h2>Letzte Teilnehmer</h2>
                    <?php if ($participants) : ?>
                        <ul class="list-table">
                            <?php foreach (array_slice($participants, 0, 5) as $participant) :
                                $trip_id = (int) get_post_meta($participant->ID, 'trip_id', true);
                                $gender = get_post_meta($participant->ID, 'gender', true);
                            ?>
                                <li>
                                    <div class="list-main">
                                        <strong><?php echo esc_html(trim($gender . ' ' . $participant->post_title)); ?></strong>
                                        <span><?php echo $trip_id ? esc_html(get_the_title($trip_id)) : 'Keine Reise'; ?></span>
                                        <span><?php echo wp_kses_post(alpenia_get_participant_doc_badge($participant->ID)); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>Noch keine Teilnehmer vorhanden.</p>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <style>
        html, body, .site, .site-main, .entry-content, .content-area, .elementor, .elementor-section, .elementor-container, .elementor-widget-wrap {
            background: #0b0b0b !important;
            overflow-x: hidden !important;
        }

        body.page, body.logged-in {
            background: #0b0b0b !important;
        }

        .alpenia-dashboard-shell {
            width: 100vw;
            max-width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            margin-top: 0 !important;
            padding-top: 0 !important;
            background: #0b0b0b;
            overflow-x: hidden;
        }

        .alpenia-dashboard {
            width: 100%;
            max-width: none;
            background: #0b0b0b;
            color: #fff;
            padding: 20px 40px 40px 40px;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
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

        .dashboard-brand-text h1 {
            font-size: 42px;
            margin: 0;
            line-height: 1.1;
            color: #ffffff;
        }

        .dashboard-brand-text p {
            color: #d6d6d6;
            margin: 8px 0 0;
            font-size: 16px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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

        .btn-primary {
            background: linear-gradient(135deg, #1d4d3f, #2d6a57);
            color: #fff;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.10);
            color: #fff;
        }

        .btn-logout {
            background: linear-gradient(135deg, #a12626, #c94a4a) !important;
            color: #fff !important;
        }

        .table-btn {
            background: rgba(255,255,255,0.12);
            color: #fff;
            padding: 10px 14px;
            font-size: 14px;
            min-height: 40px;
        }

        .table-btn-danger {
            background: rgba(130, 25, 25, 0.7) !important;
            color: #fff !important;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .card {
            min-width: 0;
            background: rgba(29,77,63,0.35);
            padding: 20px;
            border-radius: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(120,180,150,0.1);
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

        .panel {
            background: rgba(18,46,38,0.6);
            padding: 20px;
            border-radius: 14px;
            border: 1px solid rgba(120,180,150,0.1);
            overflow: hidden;
        }

        .panel h2 {
            margin-top: 0;
            font-size: 28px;
            color: #ffffff;
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
            border: 1px solid rgba(255,255,255,0.18);
            background-color: rgba(255,255,255,0.12);
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

        .filter-bar input::placeholder,
        .form-group input::placeholder {
            color: #f2f2f2;
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
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-size: 15px;
        }

        .participant-box {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 22px;
        }

        .participant-box h3 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 24px;
            color: #ffffff;
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
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
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.08);
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
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
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
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
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
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.18);
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

        .filter-bar input:-webkit-autofill,
        .filter-bar select:-webkit-autofill,
        .form-group input:-webkit-autofill,
        .form-group select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px rgba(255,255,255,0.12) inset !important;
            -webkit-text-fill-color: #ffffff !important;
            transition: background-color 9999s ease-out 0s;
        }

        @media (max-width: 1200px) {
            .trip-meta-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 1100px) {
            .cards { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .trip-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .alpenia-dashboard { padding: 18px 20px 30px 20px; }
            .dashboard-brand-text h1 { font-size: 30px; }
            .form-grid { grid-template-columns: 1fr; }
            .cards { grid-template-columns: 1fr; }
            .list-table li { flex-direction: column; align-items: flex-start; gap: 10px; }
            .trip-meta-grid { grid-template-columns: 1fr; }
            .actions { width: 100%; }
            .btn-primary, .btn-secondary { width: 100%; text-align: center; }
            .dashboard-logo { width: 58px; }
            .filter-bar { flex-direction: column; }
            .filter-bar > * { flex: 1 1 100%; width: 100%; }
            .check-grid { grid-template-columns: 1fr; }
            .role-select-grid,
            .role-action-grid { grid-template-columns: 1fr; }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const euCountries = [
            'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich',
            'Griechenland','Irland','Italien','Kroatien','Lettland','Litauen','Luxemburg',
            'Malta','Niederlande','Österreich','Polen','Portugal','Rumänien','Schweden',
            'Slowakei','Slowenien','Spanien','Tschechien','Ungarn','Zypern'
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

        function isEuCountry(value) {
            return euCountries.map(normalize).includes(normalize(value));
        }

        function validateFileInput(input, maxBytes, labelText) {
            if (!input) return true;
            if (!input.files || !input.files[0]) return true;

            if (input.files[0].size > maxBytes) {
                alert(labelText + ' ist zu groß. Erlaubt sind maximal ' + Math.round(maxBytes / 1024 / 1024) + ' MB.');
                input.value = '';
                return false;
            }
            return true;
        }

        document.querySelectorAll('input[id^="nationality_"]').forEach(function (input) {
            const index = input.id.replace('nationality_', '');
            const visaFields = document.querySelectorAll('.visa-field-' + index);
            const visaNumber = document.getElementById('visa_number_' + index);
            const visaExpiry = document.getElementById('visa_expiry_date_' + index);
            const visaPhoto = document.getElementById('visa_photo_file_' + index);

            function toggleVisaFields() {
                const nationality = input.value.trim();
                const show = nationality !== '' && !isEuCountry(nationality);

                visaFields.forEach(function (field) {
                    field.style.display = show ? '' : 'none';
                });

                if (visaNumber) visaNumber.required = show;
                if (visaExpiry) visaExpiry.required = show;
                if (visaPhoto) visaPhoto.required = show;
            }

            input.addEventListener('input', toggleVisaFields);
            input.addEventListener('change', toggleVisaFields);
            toggleVisaFields();
        });

        const nationalityEdit = document.getElementById('nationality');
        const visaNumberEdit = document.getElementById('visa_number');
        const visaExpiryEdit = document.getElementById('visa_expiry_date');
        const visaPhotoEdit = document.getElementById('visa_photo_file');
        const visaNoteEdit = document.getElementById('visa_note');

        if (nationalityEdit) {
            const editVisaFields = [];

            if (visaNumberEdit && visaNumberEdit.closest('.edit-visa-field')) editVisaFields.push(visaNumberEdit.closest('.edit-visa-field'));
            if (visaExpiryEdit && visaExpiryEdit.closest('.edit-visa-field')) editVisaFields.push(visaExpiryEdit.closest('.edit-visa-field'));
            if (visaPhotoEdit && visaPhotoEdit.closest('.edit-visa-field')) editVisaFields.push(visaPhotoEdit.closest('.edit-visa-field'));
            if (visaNoteEdit && visaNoteEdit.closest('.edit-visa-field')) editVisaFields.push(visaNoteEdit.closest('.edit-visa-field'));

            function toggleEditVisaFields() {
                const nationality = nationalityEdit.value.trim();
                const show = nationality !== '' && !isEuCountry(nationality);

                editVisaFields.forEach(function (field) {
                    if (field) field.style.display = show ? '' : 'none';
                });

                if (visaNumberEdit) visaNumberEdit.required = show;
                if (visaExpiryEdit) visaExpiryEdit.required = show;
            }

            nationalityEdit.addEventListener('input', toggleEditVisaFields);
            nationalityEdit.addEventListener('change', toggleEditVisaFields);
            toggleEditVisaFields();
        }

        document.querySelectorAll('input[id^="passport_file_"], #passport_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.passport, 'Pass-Datei');
            });
        });

        document.querySelectorAll('input[id^="photo_file_"], #photo_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.photo, 'Foto');
            });
        });

        document.querySelectorAll('input[id^="visa_photo_file_"], #visa_photo_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.visaPhoto, 'Visa Foto');
            });
        });

        document.querySelectorAll('input[id^="meldezettel_file_"], #meldezettel_file').forEach(function(input) {
            input.addEventListener('change', function() {
                validateFileInput(input, uploadLimits.meldezettel, 'Meldezettel');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('alpenia_dashboard', 'alpenia_dashboard_shortcode');

