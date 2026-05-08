<?php
if (!defined('ABSPATH')) exit;

/**
 * CSV Export
 */
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

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reise-' . sanitize_title($trip->post_title) . '-teilnehmer.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'Trip',
        'Trip Type',
        'Status',
        'Destination',
        'Country',
        'City',
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
            $trip->post_title,
            get_post_meta($trip_id, 'trip_type', true),
            get_post_meta($trip_id, 'trip_status', true),
            get_post_meta($trip_id, 'destination', true),
            get_post_meta($trip_id, 'country', true),
            get_post_meta($trip_id, 'city', true),
            alpenia_gender_code(get_post_meta($participant->ID, 'gender', true)),
            alpenia_get_secure_meta($participant->ID, 'first_name', true),
            alpenia_get_secure_meta($participant->ID, 'last_name', true),
            alpenia_get_secure_meta($participant->ID, 'birth_date', true),
            alpenia_get_secure_meta($participant->ID, 'nationality', true),
            alpenia_get_secure_meta($participant->ID, 'passport_no', true),
            alpenia_get_secure_meta($participant->ID, 'passport_valid_from_date', true),
            alpenia_get_secure_meta($participant->ID, 'passport_expiry_date', true),
            alpenia_get_visa_entry_country($participant->ID),
            alpenia_get_secure_meta($participant->ID, 'visa_number', true),
            alpenia_get_secure_meta($participant->ID, 'visa_expiry_date', true),
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
            get_post_meta($participant->ID, 'meldezettel_file_id', true) ? 'Available' : 'Optional / Missing',
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
    <html lang="<?php echo esc_attr(alpenia_travel_get_language()); ?>">
    <head>
        <meta charset="utf-8">
        <title><?php echo esc_html($trip->post_title); ?> - <?php echo esc_html(alpenia_travel_t('Teilnehmerliste dieser Reise')); ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 28px; color: #17211d; background: #fff; }
            .header { display:flex; align-items:center; gap:16px; margin-bottom:24px; }
            .logo { width:72px; height:auto; }
            .meta { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin:20px 0 24px; }
            .box { border:1px solid #ddd; padding:12px; border-radius:10px; }
            table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:10px; text-align:left; font-size:13px; vertical-align:top; text-decoration:none; }
            th { background:#e8f1ed; color:#103a2d; font-size:13px; white-space:nowrap; }
            h1 { margin:0; color:#103a2d; font-size:28px; }
            a { color:#103a2d; text-decoration:none; }
            .actions { margin-bottom:20px; }
            @media print { .actions { display:none; } body { padding:0; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()"><?php echo esc_html(alpenia_travel_t('PDF erstellen')); ?></button>
        </div>

        <div class="header">
            <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <div>
                <h1>Alpenia Travel Dashboard</h1>
                <div><?php echo esc_html($trip->post_title); ?> – <?php echo esc_html(alpenia_travel_t('Teilnehmerliste dieser Reise')); ?></div>
            </div>
        </div>

        <div class="meta">
            <div class="box"><strong><?php echo esc_html(alpenia_travel_t('Reisetyp')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'trip_type', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_t('Status')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'trip_status', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('destination')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'destination', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_t('Land')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'country', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_t('Stadt')); ?></strong><br><?php echo esc_html(alpenia_display_value(get_post_meta($trip_id, 'city', true))); ?></div>
            <div class="box"><strong><?php echo esc_html(alpenia_travel_pdf_label('travel_dates')); ?></strong><br><?php echo esc_html(alpenia_date_range_display(get_post_meta($trip_id, 'start_date', true), get_post_meta($trip_id, 'end_date', true))); ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><?php echo esc_html(alpenia_travel_t('Vorname')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_t('Nachname')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_t('Geburtsdatum')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_t('Anrede')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_t('Staatsbürgerschaft')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('passport_number')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('passport_issue_date')); ?></th>
                    <th><?php echo esc_html(alpenia_travel_pdf_label('passport_expiry_date')); ?></th>
                    <?php if ($is_pilgrimage_trip) : ?>
                        <th colspan="3"><?php echo esc_html(alpenia_travel_pdf_label('visa_information')); ?></th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th colspan="8"></th>
                    <?php if ($is_pilgrimage_trip) : ?>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_entry_country')); ?></th>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_number')); ?></th>
                        <th><?php echo esc_html(alpenia_travel_pdf_label('visa_expiry_date')); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($participants) : foreach ($participants as $participant) : ?>
                    <tr>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'first_name', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'last_name', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'birth_date', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_gender_code(get_post_meta($participant->ID, 'gender', true)))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'nationality', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'passport_no', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'passport_valid_from_date', true))); ?></td>
                        <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'passport_expiry_date', true))); ?></td>
                        <?php if ($is_pilgrimage_trip) : ?>
                            <td><?php echo esc_html(alpenia_display_value(alpenia_get_visa_entry_country($participant->ID))); ?></td>
                            <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'visa_number', true))); ?></td>
                            <td><?php echo esc_html(alpenia_display_value(alpenia_get_secure_meta($participant->ID, 'visa_expiry_date', true))); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="<?php echo $is_pilgrimage_trip ? 11 : 8; ?>"><?php echo esc_html(alpenia_travel_t('Noch keine Teilnehmer vorhanden.')); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
