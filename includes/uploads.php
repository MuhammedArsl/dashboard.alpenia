<?php
if (!defined('ABSPATH')) exit;

function alpenia_allowed_mimes_by_field($field_name) {
    $field_name = (string) $field_name;

    if (strpos($field_name, 'photo_file') !== false || strpos($field_name, 'visa_photo_file') !== false) {
        return [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
    }

    return [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];
}

function alpenia_get_max_upload_size_by_field($field_name) {
    $field_name = (string) $field_name;

    if (
        strpos($field_name, 'photo_file') !== false &&
        strpos($field_name, 'visa_photo_file') === false
    ) {
        return 2 * 1024 * 1024;
    }

    if (strpos($field_name, 'visa_photo_file') !== false) {
        return 2 * 1024 * 1024;
    }

    if (
        strpos($field_name, 'passport_file') !== false ||
        strpos($field_name, 'meldezettel_file') !== false
    ) {
        return 5 * 1024 * 1024;
    }

    return 5 * 1024 * 1024;
}

function alpenia_format_bytes($bytes) {
    $bytes = (int) $bytes;
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 0) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0) . ' KB';
    }
    return $bytes . ' B';
}

function alpenia_validate_upload_size($field_name) {
    if (empty($_FILES[$field_name]['name'])) {
        return true;
    }

    $max_size = alpenia_get_max_upload_size_by_field($field_name);
    $file_size = (int) ($_FILES[$field_name]['size'] ?? 0);

    return $file_size > 0 && $file_size <= $max_size;
}

function alpenia_validate_upload_type($field_name) {
    $tmp_name = $_FILES[$field_name]['tmp_name'] ?? '';
    if ($tmp_name === '' || !file_exists($tmp_name)) {
        return new WP_Error('upload_error', 'Upload konnte nicht verarbeitet werden.');
    }

    $allowed_mimes = array_keys(alpenia_allowed_mimes_by_field($field_name));
    $filetype = wp_check_filetype_and_ext($tmp_name, $_FILES[$field_name]['name'] ?? 'upload.bin');
    $mime = (string) ($filetype['type'] ?? '');

    if ($mime === '' || !in_array($mime, $allowed_mimes, true)) {
        return new WP_Error('invalid_file_type', 'Ungültiger Dateityp. Nur erlaubte Formate sind zulässig.');
    }

    return $mime;
}

function alpenia_randomize_upload_filename($filename, $ext, $dir) {
    return wp_generate_password(24, false, false) . strtolower((string) $ext);
}

function alpenia_handle_file_upload($field_name) {
    if (empty($_FILES[$field_name]['name'])) {
        return 0;
    }

    if (!alpenia_validate_upload_size($field_name)) {
        $max_size = alpenia_get_max_upload_size_by_field($field_name);
        return new WP_Error('file_too_large', 'Die Datei ist zu groß. Maximal erlaubt: ' . alpenia_format_bytes($max_size));
    }

    $mime_validation = alpenia_validate_upload_type($field_name);
    if (is_wp_error($mime_validation)) {
        return $mime_validation;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $upload_dir = wp_upload_dir();
    $private_base = trailingslashit($upload_dir['basedir']) . 'alpenia-private';
    alpenia_create_private_upload_guard($private_base);

    add_filter('upload_dir', 'alpenia_upload_dir_filter');
    add_filter('wp_unique_filename', 'alpenia_randomize_upload_filename', 10, 3);

    $uploaded = wp_handle_upload($_FILES[$field_name], [
        'test_form' => false,
        'mimes' => alpenia_allowed_mimes_by_field($field_name),
    ]);

    remove_filter('upload_dir', 'alpenia_upload_dir_filter');
    remove_filter('wp_unique_filename', 'alpenia_randomize_upload_filename', 10);

    if (isset($uploaded['error'])) {
        return new WP_Error('upload_error', $uploaded['error']);
    }

    $attachment = [
        'post_mime_type' => $uploaded['type'],
        'post_title'     => sanitize_file_name(pathinfo($uploaded['file'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'private',
    ];

    $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);
    if (!$attachment_id || is_wp_error($attachment_id)) {
        return new WP_Error('attachment_error', 'Datei konnte nicht registriert werden.');
    }

    return (int) $attachment_id;
}

function alpenia_attachment_link($attachment_id) {
    if (!$attachment_id) return '';
    return esc_url(alpenia_get_secure_download_url((int) $attachment_id));
}
