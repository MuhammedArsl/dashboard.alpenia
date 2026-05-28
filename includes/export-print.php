<?php
if (!defined('ABSPATH')) exit;

/**
 * CSV Export
 */
function alpenia_maybe_handle_trip_csv_export() {
    if (!isset($_GET['export_trip_csv'])) {
        return;
    }

    $trip_id = (int) $_GET['export_trip_csv'];
    if ($trip_id <= 0) {
        wp_die(esc_html(alpenia_travel_t('Reise nicht gefunden.')));
    }

    alpenia_export_trip_csv($trip_id);
}
add_action('template_redirect', 'alpenia_maybe_handle_trip_csv_export', 3);

function alpenia_export_trip_csv($trip_id) {
    if (!alpenia_user_can_access_trip($trip_id)) {
        alpenia_security_log('trip_export_denied', ['trip_id' => (int) $trip_id]);
        wp_die(esc_html(alpenia_travel_t('Kein Zugriff.')));
    }
    alpenia_security_log('trip_export_csv', ['trip_id' => (int) $trip_id]);

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') {
        wp_die(esc_html(alpenia_travel_t('Reise nicht gefunden.')));
    }

    $participants = alpenia_get_trip_participants($trip_id);

    while (ob_get_level() > 0) {
        $buffer = ob_get_status();
        if (empty($buffer['del'])) {
            break;
        }
        ob_end_clean();
    }

    $filename = 'reise-' . sanitize_title($trip->post_title) . '-teilnehmer.csv';

    status_header(200);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'Trip ID',
        'Participant ID',
        'Trip',
        'Trip Type',
        'Status',
        'Destination',
        'Country',
        'City',
        'Departure City',
        'Airport',
        'Gender',
        'First Name',
        'Last Name',
        'Date of Birth',
        'Nationality',
        'Passport Number',
        'Passport Valid From',
        'Passport Valid Until',
        'Visa Entry Country',
        'Visa Number',
        'Visa Expiry Date',
        'Processing Status',
        'Room',
        'Group',
        'Price',
        'Deposit',
        'Paid',
        'Open',
        'Payment Status',
        'Passport File',
        'Photo File',
        'Residence Permit File',
        'Registration Certificate File',
    ], ';');

    foreach ($participants as $participant) {
        $total = (float) get_post_meta($participant->ID, 'payment_total', true);
        $deposit = (float) get_post_meta($participant->ID, 'payment_deposit', true);
        $paid = (float) get_post_meta($participant->ID, 'payment_paid', true);
        $open = alpenia_get_participant_payment_open($participant->ID);

        fputcsv($output, [
            alpenia_get_trip_display_id($trip_id),
            alpenia_get_participant_display_id($participant->ID),
            $trip->post_title,
            get_post_meta($trip_id, 'trip_type', true),
            get_post_meta($trip_id, 'trip_status', true),
            get_post_meta($trip_id, 'destination', true),
            get_post_meta($trip_id, 'country', true),
            get_post_meta($trip_id, 'city', true),
            get_post_meta($trip_id, 'departure_city', true),
            get_post_meta($trip_id, 'departure_airport', true),
            alpenia_gender_code(get_post_meta($participant->ID, 'gender', true)),
            alpenia_get_secure_meta($participant->ID, 'first_name', true),
            alpenia_get_secure_meta($participant->ID, 'last_name', true),
            alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'birth_date', true)),
            alpenia_country_to_english(alpenia_get_secure_meta($participant->ID, 'nationality', true)),
            alpenia_get_secure_meta($participant->ID, 'passport_no', true),
            alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'passport_valid_from_date', true)),
            alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'passport_expiry_date', true)),
            alpenia_get_visa_entry_country($participant->ID),
            alpenia_get_secure_meta($participant->ID, 'visa_number', true),
            alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'visa_expiry_date', true)),
            get_post_meta($participant->ID, 'participant_status', true),
            alpenia_get_secure_meta($participant->ID, 'room_assignment', true),
            alpenia_get_secure_meta($participant->ID, 'subgroup', true),
            $total,
            $deposit,
            $paid,
            $open,
            alpenia_get_payment_status($participant->ID),
            get_post_meta($participant->ID, 'passport_file_id', true) ? 'Available' : 'Missing',
            get_post_meta($participant->ID, 'photo_file_id', true) ? 'Available' : 'Missing',
            get_post_meta($participant->ID, 'visa_photo_file_id', true) ? 'Available' : 'Missing',
            get_post_meta($participant->ID, 'meldezettel_file_id', true) ? 'Available' : 'Missing',
        ], ';');
    }

    fclose($output);
    exit;
}

/**
 * Print View
 */
function alpenia_render_print_view($trip_id, $logo_url = '') {
    if (!alpenia_user_can_access_trip($trip_id)) {
        alpenia_security_log('trip_print_denied', ['trip_id' => (int) $trip_id]);
        wp_die(esc_html(alpenia_travel_t('Kein Zugriff.')));
    }
    alpenia_security_log('trip_print_view', ['trip_id' => (int) $trip_id]);

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') {
        wp_die(esc_html(alpenia_travel_t('Reise nicht gefunden.')));
    }

    $participants = alpenia_get_trip_participants($trip_id);
    $is_pilgrimage_trip = alpenia_is_pilgrimage_trip($trip_id);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo esc_html($trip->post_title); ?> - <?php echo esc_html(alpenia_travel_pdf_label('trip_participant_list')); ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 24px; color: #17211d; background: #fff; font-size: 13px; line-height: 1.3; }
            .header { display:flex; align-items:center; gap:14px; margin-bottom:20px; }
            .logo { width:64px; height:auto; }
            .meta { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin:16px 0 20px; }
            .box { border:1px solid #ddd; padding:10px; border-radius:10px; font-size:11px; line-height:1.25; }
            table { width:100%; border-collapse:collapse; table-layout:fixed; page-break-inside:auto; }
            th, td { border:1px solid #ddd; padding:6px 4px; text-align:left; font-size:10px; vertical-align:top; text-decoration:none; overflow-wrap:anywhere; word-break:break-word; }
            th { background:#e8f1ed; color:#103a2d; font-size:10px; white-space:normal; }
            thead { display:table-header-group; }
            tr { page-break-inside:avoid; break-inside:avoid; }
            .visa-group-title { text-align:center; }
            .visa-col { width:9%; }
            h1 { margin:0; color:#103a2d; font-size:24px; }
            a { color:#103a2d; text-decoration:none; }
            .actions { margin-bottom:20px; }
            @media print {
                .actions { display:none; }
                body { padding:0; font-size:12px; line-height:1.25; }
                h1 { font-size:20px; }
                .box { font-size:10px; }
                th, td { padding:5px 3px; font-size:9px; }
                a[href]::after { content: "" !important; }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()"><?php echo esc_html(alpenia_travel_pdf_label('create_pdf')); ?></button>
        </div>

        <div class="header">
            <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <div>
                <div><?php echo esc_html($trip->post_title); ?> – <?php echo esc_html(alpenia_travel_pdf_label('trip_participant_list')); ?></div>
            </div>
        </div>

        <div class="meta">
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('trip_id')); ?></strong><br><?php echo esc_html(alpenia_get_trip_display_id($trip_id)); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('trip_type')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'trip_type', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('status')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'trip_status', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('destination')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'destination', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('country')); ?></strong><br><?php echo esc_html(alpenia_display_value(alpenia_country_to_english(get_post_meta($trip_id, 'country', true)))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('city')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'city', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('departure_city')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'departure_city', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('airport')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'departure_airport', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('travel_dates')); ?></strong><br><?php echo esc_html(alpenia_date_range_display(get_post_meta($trip_id, 'start_date', true), get_post_meta($trip_id, 'end_date', true))); ?></div>
        </div>

        <table>
            <?php if ($is_pilgrimage_trip) : ?>
                <colgroup>
                    <col span="8" style="width:9.25%;">
                    <col class="visa-col">
                    <col class="visa-col">
                    <col class="visa-col">
                </colgroup>
            <?php endif; ?>
            <thead>
                <tr>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('participant_id')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('first_name')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('last_name')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('birth_date')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('gender')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('nationality')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('passport_number')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('passport_expiry_date')); ?></th>
                    <?php if ($is_pilgrimage_trip) : ?>
                        <th colspan="3" class="visa-group-title"><?php echo esc_html(alpenia_travel_pdf_label('visa_information')); ?></th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th></th>
                    <th colspan="7"></th>
                    <?php if ($is_pilgrimage_trip) : ?>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_entry_country')); ?></th>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_number')); ?></th>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_expiry_date')); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($participants) : foreach ($participants as $participant) : ?>
                    <?php
                    $participant_first_name = trim(
                        alpenia_get_secure_meta($participant->ID, 'first_name', true) . ' ' .
                        alpenia_get_secure_meta($participant->ID, 'second_first_name', true)
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html(alpenia_get_participant_display_id($participant->ID)); ?></td>
                        <td><?php echo esc_html(alpenia_display_value($participant_first_name)); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'last_name', true))); ?></td>
                        <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'birth_date', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_gender_code(get_post_meta($participant->ID, 'gender', true)))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_country_to_english(alpenia_get_secure_meta($participant->ID, 'nationality', true)))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'passport_no', true))); ?></td>
                        <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'passport_expiry_date', true))); ?></td>
                        <?php if ($is_pilgrimage_trip) : ?>
                            <td><?php echo esc_html(alpenia_display_value(alpenia_country_to_english(alpenia_get_visa_entry_country($participant->ID)))); ?></td>
                            <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'visa_number', true))); ?></td>
                            <td><?php echo esc_html(alpenia_format_date_display(alpenia_get_secure_meta($participant->ID, 'visa_expiry_date', true))); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="<?php echo $is_pilgrimage_trip ? 10 : 7; ?>"><?php echo esc_html(alpenia_travel_pdf_label('no_participants')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
