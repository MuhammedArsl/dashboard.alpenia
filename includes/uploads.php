<?php
if (!defined('ABSPATH')) exit;

/**
 * Upload Limits
 */
function alpenia_get_max_upload_size_by_field($field_name) {
    $field_name = (string) $field_name;

    if (
        strpos($field_name, 'photo_file') !== false &&
        strpos($field_name, 'visa_photo_file') === false
    ) {
        return 2 * 1024 * 1024; // 2 MB
    }

    if (strpos($field_name, 'visa_photo_file') !== false) {
        return 2 * 1024 * 1024; // 2 MB
    }

    if (
        strpos($field_name, 'passport_file') !== false ||
        strpos($field_name, 'meldezettel_file') !== false
    ) {
        return 5 * 1024 * 1024; // 5 MB
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

/**
 * Datei Upload
 */
function alpenia_handle_file_upload($field_name) {
    if (empty($_FILES[$field_name]['name'])) {
        return 0;
    }

    if (!alpenia_validate_upload_size($field_name)) {
        $max_size = alpenia_get_max_upload_size_by_field($field_name);
        return new WP_Error('file_too_large', 'Die Datei ist zu groß. Maximal erlaubt: ' . alpenia_format_bytes($max_size));
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $uploaded = wp_handle_upload($_FILES[$field_name], ['test_form' => false]);

    if (isset($uploaded['error'])) {
        return new WP_Error('upload_error', $uploaded['error']);
    }

    $attachment = [
        'post_mime_type' => $uploaded['type'],
        'post_title'     => sanitize_file_name(basename($uploaded['file'])),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);

    if ($attachment_id && !is_wp_error($attachment_id)) {
        $attach_data = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
    }

    return $attachment_id;
}

function alpenia_attachment_link($attachment_id) {
    if (!$attachment_id) return '';
    $url = wp_get_attachment_url($attachment_id);
    if (!$url) return '';
    return esc_url($url);
}

