<?php
if (!defined('ABSPATH')) exit;

/**
 * CSV Export
 */
function alpenia_export_trip_csv($trip_id) {
    if (!alpenia_user_can_access_trip($trip_id)) {
        wp_die('Kein Zugriff.');
    }

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') {
        wp_die('Reise nicht gefunden.');
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
        'Residence Permit Number',
        'Residence Permit Valid From',
        'Residence Permit Valid Until',
        'Visum Note',
        'Visum Status (Entry Country)',
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
        'Registration File',
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
            get_post_meta($participant->ID, 'first_name', true),
            get_post_meta($participant->ID, 'last_name', true),
            get_post_meta($participant->ID, 'birth_date', true),
            get_post_meta($participant->ID, 'nationality', true),
            get_post_meta($participant->ID, 'passport_no', true),
            get_post_meta($participant->ID, 'passport_valid_from_date', true),
            get_post_meta($participant->ID, 'passport_expiry_date', true),
            get_post_meta($participant->ID, 'visa_number', true),
            get_post_meta($participant->ID, 'visa_valid_from_date', true),
            get_post_meta($participant->ID, 'visa_expiry_date', true),
            get_post_meta($participant->ID, 'visa_note', true),
            get_post_meta($participant->ID, 'visa_status', true),
            get_post_meta($participant->ID, 'participant_status', true),
            get_post_meta($participant->ID, 'room_assignment', true),
            get_post_meta($participant->ID, 'subgroup', true),
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
        wp_die('Kein Zugriff.');
    }

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') {
        wp_die('Reise nicht gefunden.');
    }

    $participants = alpenia_get_trip_participants($trip_id);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo esc_html($trip->post_title); ?> - Participant List</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 28px; color: #111; background: #fff; }
            .header { display:flex; align-items:center; gap:16px; margin-bottom:24px; }
            .logo { width:72px; height:auto; }
            .meta { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin:20px 0 24px; }
            .box { border:1px solid #ddd; padding:12px; border-radius:10px; }
            table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:10px; text-align:left; font-size:14px; vertical-align:top; }
            th { background:#f2f2f2; }
            .actions { margin-bottom:20px; }
            @media print { .actions { display:none; } body { padding:0; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()">Print / Save as PDF</button>
        </div>

        <div class="header">
            <?php if (!empty($logo_url) && $logo_url !== 'HIER_DEINE_LOGO_URL_EINFÜGEN') : ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <div>
                <h1 style="margin:0;">Alpenia Travel Dashboard</h1>
                <div><?php echo esc_html($trip->post_title); ?> – Participant List</div>
            </div>
        </div>

        <div class="meta">
            <div class="box"><strong>Trip Type</strong><br><?php echo esc_html(get_post_meta($trip_id, 'trip_type', true)); ?></div>
            <div class="box"><strong>Status</strong><br><?php echo esc_html(get_post_meta($trip_id, 'trip_status', true)); ?></div>
            <div class="box"><strong>Destination</strong><br><?php echo esc_html(get_post_meta($trip_id, 'destination', true)); ?></div>
            <div class="box"><strong>Country</strong><br><?php echo esc_html(get_post_meta($trip_id, 'country', true)); ?></div>
            <div class="box"><strong>City</strong><br><?php echo esc_html(get_post_meta($trip_id, 'city', true)); ?></div>
            <div class="box"><strong>Travel Dates</strong><br><?php echo esc_html(get_post_meta($trip_id, 'start_date', true)); ?> – <?php echo esc_html(get_post_meta($trip_id, 'end_date', true)); ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Nationality</th>
                    <th>Passport Number</th>
                    <th>Passport Valid From</th>
                    <th>Passport Valid Until</th>
                    <th>Residence Permit Number</th>
                    <th>Residence Permit Valid From</th>
                    <th>Residence Permit Valid Until</th>
                    <th>Visum Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($participants) : foreach ($participants as $participant) : ?>
                    <tr>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'first_name', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'last_name', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'birth_date', true)); ?></td>
                        <td><?php echo esc_html(alpenia_gender_code(get_post_meta($participant->ID, 'gender', true))); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'nationality', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'passport_no', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'passport_valid_from_date', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'passport_expiry_date', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_number', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_valid_from_date', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_expiry_date', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($participant->ID, 'visa_note', true)); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="12">No participants available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}
