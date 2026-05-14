<?php
if (!defined('ABSPATH')) exit;

function alpenia_participant_drafts_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'participant_drafts';
}

define('ALPENIA_PARTICIPANT_DRAFTS_DB_VERSION', '1.0');

function alpenia_create_participant_drafts_table() {
    global $wpdb;

    $table_name = alpenia_participant_drafts_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        first_name varchar(191) NOT NULL,
        last_name varchar(191) NOT NULL,
        email varchar(191) NOT NULL,
        phone varchar(100) NOT NULL DEFAULT '',
        birthdate varchar(50) NOT NULL DEFAULT '',
        gender varchar(50) NOT NULL DEFAULT '',
        address text NULL,
        city varchar(191) NOT NULL DEFAULT '',
        zip varchar(50) NOT NULL DEFAULT '',
        country varchar(100) NOT NULL DEFAULT '',
        desired_trip varchar(191) NOT NULL DEFAULT '',
        program varchar(191) NOT NULL DEFAULT '',
        assigned_trip_id bigint(20) unsigned NULL DEFAULT NULL,
        status varchar(50) NOT NULL DEFAULT 'draft',
        source varchar(100) NOT NULL DEFAULT 'tally_zapier',
        notes text NULL,
        raw_payload longtext NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY email (email),
        KEY desired_trip (desired_trip),
        KEY program (program),
        KEY status (status),
        KEY assigned_trip_id (assigned_trip_id)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('alpenia_participant_drafts_db_version', ALPENIA_PARTICIPANT_DRAFTS_DB_VERSION);
}

function alpenia_maybe_create_participant_drafts_table() {
    if (get_option('alpenia_participant_drafts_db_version') !== ALPENIA_PARTICIPANT_DRAFTS_DB_VERSION) {
        alpenia_create_participant_drafts_table();
    }
}

add_action('admin_init', 'alpenia_maybe_create_participant_drafts_table');

register_activation_hook(ALPENIA_PLUGIN_FILE, 'alpenia_create_participant_drafts_table');

function alpenia_participant_draft_statuses() {
    return ['draft', 'reviewed', 'assigned', 'cancelled'];
}

function alpenia_get_participant_drafts_default_status() {
    $status = get_option('alpenia_participant_drafts_default_status', 'draft');
    return in_array($status, alpenia_participant_draft_statuses(), true) ? $status : 'draft';
}

function alpenia_get_participant_drafts_zapier_source() {
    $source = get_option('alpenia_participant_drafts_zapier_source', 'tally_zapier');
    $source = sanitize_key($source);
    return $source !== '' ? $source : 'tally_zapier';
}

function alpenia_get_participant_drafts_api_key() {
    return (string) get_option('alpenia_participant_drafts_api_key', '');
}

function alpenia_sanitize_participant_draft_data($data, $source = 'tally_zapier') {
    $status = isset($data['status']) ? sanitize_key($data['status']) : alpenia_get_participant_drafts_default_status();
    if (!in_array($status, alpenia_participant_draft_statuses(), true)) {
        $status = alpenia_get_participant_drafts_default_status();
    }

    $assigned_trip_id = null;
    if (isset($data['assigned_trip_id']) && $data['assigned_trip_id'] !== '') {
        $assigned_trip_id = absint($data['assigned_trip_id']);
        if ($assigned_trip_id < 1) {
            $assigned_trip_id = null;
        }
    }

    $sanitized_source = sanitize_key($source);
    if (isset($data['source'])) {
        $sanitized_source = sanitize_key($data['source']);
    }
    if ($sanitized_source === '') {
        $sanitized_source = 'tally_zapier';
    }

    return [
        'first_name'       => sanitize_text_field($data['first_name'] ?? ''),
        'last_name'        => sanitize_text_field($data['last_name'] ?? ''),
        'email'            => sanitize_email($data['email'] ?? ''),
        'phone'            => sanitize_text_field($data['phone'] ?? ''),
        'birthdate'        => sanitize_text_field($data['birthdate'] ?? ''),
        'gender'           => sanitize_text_field($data['gender'] ?? ''),
        'address'          => sanitize_textarea_field($data['address'] ?? ''),
        'city'             => sanitize_text_field($data['city'] ?? ''),
        'zip'              => sanitize_text_field($data['zip'] ?? ''),
        'country'          => sanitize_text_field($data['country'] ?? ''),
        'desired_trip'     => sanitize_text_field($data['desired_trip'] ?? ''),
        'program'          => sanitize_text_field($data['program'] ?? ''),
        'assigned_trip_id' => $assigned_trip_id,
        'status'           => $status,
        'source'           => $sanitized_source,
        'notes'            => sanitize_textarea_field($data['notes'] ?? ''),
    ];
}

function alpenia_validate_participant_draft_data($data) {
    $errors = [];

    if (empty($data['first_name'])) {
        $errors[] = 'first_name is required.';
    }

    if (empty($data['last_name'])) {
        $errors[] = 'last_name is required.';
    }

    if (empty($data['email'])) {
        $errors[] = 'email is required.';
    } elseif (!is_email($data['email'])) {
        $errors[] = 'email must be a valid email address.';
    }

    return $errors;
}

function alpenia_find_existing_participant_draft($email, $desired_trip = '', $program = '') {
    global $wpdb;

    $table_name = alpenia_participant_drafts_table_name();
    $email = sanitize_email($email);
    $desired_trip = sanitize_text_field($desired_trip);
    $program = sanitize_text_field($program);

    if ($email === '' || ($desired_trip === '' && $program === '')) {
        return null;
    }

    $conditions = [];
    $params = [$email];

    if ($desired_trip !== '') {
        $conditions[] = 'desired_trip = %s';
        $params[] = $desired_trip;
    }

    if ($program !== '') {
        $conditions[] = 'program = %s';
        $params[] = $program;
    }

    $where = implode(' OR ', $conditions);
    $params[] = 1;

    $sql = "SELECT * FROM {$table_name} WHERE email = %s AND ({$where}) ORDER BY id DESC LIMIT %d";

    return $wpdb->get_row($wpdb->prepare($sql, $params), ARRAY_A);
}

function alpenia_save_participant_draft($data, $raw_payload = null) {
    global $wpdb;

    $table_name = alpenia_participant_drafts_table_name();
    $now = current_time('mysql');
    $raw_json = null;

    if ($raw_payload !== null) {
        $raw_json = wp_json_encode($raw_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $existing = alpenia_find_existing_participant_draft($data['email'], $data['desired_trip'], $data['program']);

    $db_data = [
        'first_name'       => $data['first_name'],
        'last_name'        => $data['last_name'],
        'email'            => $data['email'],
        'phone'            => $data['phone'],
        'birthdate'        => $data['birthdate'],
        'gender'           => $data['gender'],
        'address'          => $data['address'],
        'city'             => $data['city'],
        'zip'              => $data['zip'],
        'country'          => $data['country'],
        'desired_trip'     => $data['desired_trip'],
        'program'          => $data['program'],
        'assigned_trip_id' => $data['assigned_trip_id'],
        'status'           => $data['status'],
        'source'           => $data['source'],
        'notes'            => $data['notes'],
        'updated_at'       => $now,
    ];

    $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'];

    if ($raw_json !== null) {
        $db_data['raw_payload'] = $raw_json;
        $formats[] = '%s';
    }

    if ($existing) {
        $updated = $wpdb->update(
            $table_name,
            $db_data,
            ['id' => (int) $existing['id']],
            $formats,
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('participant_draft_update_failed', 'Could not update participant draft.', ['status' => 500]);
        }

        return [
            'id' => (int) $existing['id'],
            'updated' => true,
        ];
    }

    $db_data['created_at'] = $now;
    $formats[] = '%s';

    $inserted = $wpdb->insert($table_name, $db_data, $formats);
    if (!$inserted) {
        return new WP_Error('participant_draft_insert_failed', 'Could not create participant draft.', ['status' => 500]);
    }

    return [
        'id' => (int) $wpdb->insert_id,
        'updated' => false,
    ];
}
