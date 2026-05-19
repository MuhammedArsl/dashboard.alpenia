<?php
if (!defined('ABSPATH')) exit;

function alpenia_env($key, $default = '') {
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return is_string($value) ? trim($value) : $value;
}

function alpenia_get_crypto_key() {
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $raw = alpenia_env('ALPENIA_FIELD_ENCRYPTION_KEY', '');
    if ($raw === '') {
        $key = '';
        return $key;
    }

    $decoded = base64_decode($raw, true);
    if ($decoded === false || strlen($decoded) !== 32) {
        $key = '';
        return $key;
    }

    $key = $decoded;
    return $key;
}

function alpenia_get_signing_key() {
    $key = alpenia_env('ALPENIA_DOWNLOAD_SIGNING_KEY', '');
    return $key !== '' ? $key : wp_salt('auth');
}

function alpenia_sensitive_meta_fields() {
    return [
        'first_name',
        'second_first_name',
        'last_name',
        'birth_date',
        'nationality',
        'passport_no',
        'passport_valid_from_date',
        'passport_expiry_date',
        'residence_permit_start_date',
        'residence_permit_number',
        'residence_permit_valid_until',
        'visa_entry_country',
        'visa_number',
        'visa_expiry_date',
        'internal_notes',
        'room_assignment',
        'subgroup',
    ];
}

function alpenia_encrypt_value($plaintext) {
    $plaintext = (string) $plaintext;
    if ($plaintext === '') {
        return '';
    }

    $key = alpenia_get_crypto_key();
    if ($key === '') {
        return $plaintext;
    }

    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    if (!is_string($ciphertext)) {
        return $plaintext;
    }

    return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
}

function alpenia_decrypt_value($stored) {
    $stored = (string) $stored;
    if ($stored === '') {
        return '';
    }

    if (strpos($stored, 'enc:v1:') !== 0) {
        return $stored;
    }

    $key = alpenia_get_crypto_key();
    if ($key === '') {
        return '';
    }

    $raw = base64_decode(substr($stored, 7), true);
    if ($raw === false || strlen($raw) < 29) {
        return '';
    }

    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return is_string($plaintext) ? $plaintext : '';
}

function alpenia_update_secure_meta($post_id, $meta_key, $value) {
    if (in_array($meta_key, alpenia_sensitive_meta_fields(), true)) {
        return update_post_meta($post_id, $meta_key, alpenia_encrypt_value((string) $value));
    }

    return update_post_meta($post_id, $meta_key, $value);
}

function alpenia_get_secure_meta($post_id, $meta_key, $single = true) {
    $value = get_post_meta($post_id, $meta_key, $single);
    if (!$single || !in_array($meta_key, alpenia_sensitive_meta_fields(), true)) {
        return $value;
    }

    return alpenia_decrypt_value((string) $value);
}

function alpenia_security_log($event, $context = []) {
    $safe_context = [];
    foreach ((array) $context as $key => $value) {
        if (in_array($key, ['password', 'token', 'secret', 'plaintext'], true)) {
            continue;
        }

        if (is_scalar($value) || $value === null) {
            $safe_context[$key] = $value;
        }
    }

    $safe_context['event'] = $event;
    $safe_context['timestamp'] = gmdate('c');
    $safe_context['user_id'] = get_current_user_id();
    $safe_context['ip_hash'] = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    error_log('[alpenia_security] ' . wp_json_encode($safe_context));
}

function alpenia_harden_security_headers() {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'");

    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
add_action('send_headers', 'alpenia_harden_security_headers', 20);

function alpenia_force_https() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    if (is_ssl()) {
        return;
    }

    if (alpenia_env('ALPENIA_FORCE_HTTPS', '1') !== '1') {
        return;
    }

    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';

    if ($host !== '') {
        wp_safe_redirect('https://' . $host . $uri, 301);
        exit;
    }
}
add_action('template_redirect', 'alpenia_force_https', 0);

function alpenia_secure_auth_cookies($secure) {
    return is_ssl() ? true : $secure;
}
add_filter('secure_auth_cookie', 'alpenia_secure_auth_cookies');
add_filter('secure_logged_in_cookie', 'alpenia_secure_auth_cookies');

function alpenia_login_session_regeneration($user_login, $user) {
    if (!$user || empty($user->ID)) {
        return;
    }

    if (class_exists('WP_Session_Tokens')) {
        $current_token = wp_get_session_token();
        if (is_string($current_token) && $current_token !== '') {
            $manager = WP_Session_Tokens::get_instance($user->ID);
            $manager->destroy_others($current_token);
        }
    }

    update_user_meta($user->ID, 'alpenia_last_activity', time());
    alpenia_security_log('login_success', ['login' => $user_login]);
}
add_action('wp_login', 'alpenia_login_session_regeneration', 20, 2);

function alpenia_security_logout_log() {
    alpenia_security_log('logout');
}
add_action('wp_logout', 'alpenia_security_logout_log');

function alpenia_audit_sensitive_meta_update($meta_id, $post_id, $meta_key, $_meta_value) {
    if (in_array($meta_key, alpenia_sensitive_meta_fields(), true)) {
        alpenia_security_log('sensitive_meta_updated', ['post_id' => (int) $post_id, 'meta_key' => $meta_key]);
    }
}
add_action('updated_post_meta', 'alpenia_audit_sensitive_meta_update', 10, 4);
add_action('added_post_meta', 'alpenia_audit_sensitive_meta_update', 10, 4);

function alpenia_audit_role_change($user_id, $old_user_data) {
    $user = get_user_by('id', $user_id);
    if ($user) {
        alpenia_security_log('role_or_profile_change', ['target_user_id' => (int) $user_id, 'roles' => implode(',', (array) $user->roles)]);
    }
}
add_action('profile_update', 'alpenia_audit_role_change', 10, 2);

function alpenia_audit_post_delete($post_id) {
    $post = get_post($post_id);
    if (!$post) return;

    if (in_array($post->post_type, ['group_trip', 'trip_participant'], true)) {
        alpenia_security_log('record_deleted', ['post_id' => (int) $post_id, 'post_type' => $post->post_type]);
    }
}
add_action('before_delete_post', 'alpenia_audit_post_delete');

function alpenia_can_use_argon2id() {
    if (!defined('PASSWORD_ARGON2ID') || !function_exists('password_algos')) {
        return false;
    }

    $algorithms = password_algos();
    if (!is_array($algorithms)) {
        return false;
    }

    return in_array('argon2id', $algorithms, true);
}

function alpenia_password_hash_algorithm($algo) {
    if (alpenia_can_use_argon2id()) {
        return PASSWORD_ARGON2ID;
    }

    return $algo;
}
add_filter('wp_hash_password_algorithm', 'alpenia_password_hash_algorithm');

function alpenia_password_hash_options($options) {
    if (alpenia_can_use_argon2id()) {
        return [
            // Keep values conservative to avoid runtime failures on low-memory hosts.
            'memory_cost' => 1 << 15,
            'time_cost' => 2,
            'threads' => 1,
        ];
    }

    return $options;
}
add_filter('wp_hash_password_options', 'alpenia_password_hash_options');

function alpenia_generate_password_reset_token($user_id) {
    $token = wp_generate_password(48, false, false);
    $hashed = hash('sha256', $token);

    update_user_meta($user_id, 'alpenia_reset_token_hash', $hashed);
    update_user_meta($user_id, 'alpenia_reset_token_exp', time() + HOUR_IN_SECONDS);

    return $token;
}

function alpenia_validate_password_reset_token($user_id, $token) {
    $stored = (string) get_user_meta($user_id, 'alpenia_reset_token_hash', true);
    $exp = (int) get_user_meta($user_id, 'alpenia_reset_token_exp', true);

    if ($stored === '' || $exp < time()) {
        return false;
    }

    return hash_equals($stored, hash('sha256', (string) $token));
}

function alpenia_consume_password_reset_token($user_id) {
    delete_user_meta($user_id, 'alpenia_reset_token_hash');
    delete_user_meta($user_id, 'alpenia_reset_token_exp');
}

function alpenia_upload_dir_filter($dirs) {
    $dirs['subdir'] = '/alpenia-private' . $dirs['subdir'];
    $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
    $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];

    return $dirs;
}

function alpenia_create_private_upload_guard($base_dir) {
    wp_mkdir_p($base_dir);

    $index_file = trailingslashit($base_dir) . 'index.php';
    if (!file_exists($index_file)) {
        file_put_contents($index_file, "<?php\nhttp_response_code(403);\nexit;\n");
    }

    $htaccess = trailingslashit($base_dir) . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }

    $web_config = trailingslashit($base_dir) . 'web.config';
    if (!file_exists($web_config)) {
        file_put_contents($web_config, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n");
    }
}

function alpenia_get_secure_download_url($attachment_id, $force_download = false) {
    $attachment_id = (int) $attachment_id;
    if ($attachment_id <= 0) {
        return '';
    }

    $exp = time() + (5 * MINUTE_IN_SECONDS);
    $uid = get_current_user_id();
    $dl = $force_download ? 1 : 0;
    $payload = $attachment_id . '|' . $exp . '|' . $uid . '|' . $dl;
    $sig = hash_hmac('sha256', $payload, alpenia_get_signing_key());

    return add_query_arg([
        'alpenia_download' => $attachment_id,
        'exp' => $exp,
        'uid' => $uid,
        'sig' => $sig,
        'dl' => $dl,
    ], home_url('/'));
}

function alpenia_user_can_access_attachment($attachment_id) {
    $attachment_id = (int) $attachment_id;
    if ($attachment_id <= 0 || !is_user_logged_in()) {
        return false;
    }

    if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) {
        return true;
    }

    $participants = get_posts([
        'post_type' => 'trip_participant',
        'post_status' => 'publish',
        'numberposts' => 1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'OR',
            ['key' => 'passport_file_id', 'value' => $attachment_id],
            ['key' => 'photo_file_id', 'value' => $attachment_id],
            ['key' => 'visa_photo_file_id', 'value' => $attachment_id],
            ['key' => 'meldezettel_file_id', 'value' => $attachment_id],
        ],
    ]);

    if (empty($participants)) {
        return false;
    }

    return alpenia_user_can_access_participant((int) $participants[0]);
}

function alpenia_handle_secure_download() {
    if (!isset($_GET['alpenia_download'], $_GET['exp'], $_GET['uid'], $_GET['sig'])) {
        return;
    }

    $attachment_id = (int) $_GET['alpenia_download'];
    $exp = (int) $_GET['exp'];
    $uid = (int) $_GET['uid'];
    $sig = sanitize_text_field(wp_unslash($_GET['sig']));
    $dl = isset($_GET['dl']) ? (int) $_GET['dl'] : 0;

    if ($attachment_id <= 0 || $exp < time() || $uid !== get_current_user_id()) {
        status_header(403);
        exit('Access denied');
    }

    $expected = hash_hmac('sha256', $attachment_id . '|' . $exp . '|' . $uid . '|' . $dl, alpenia_get_signing_key());
    if (!hash_equals($expected, $sig) || !alpenia_user_can_access_attachment($attachment_id)) {
        alpenia_security_log('download_denied', ['attachment_id' => $attachment_id]);
        status_header(403);
        exit('Access denied');
    }

    $file = get_attached_file($attachment_id);
    if (!$file || !file_exists($file)) {
        status_header(404);
        exit('Not found');
    }

    alpenia_security_log('document_download', ['attachment_id' => $attachment_id]);
    nocache_headers();
    header('Content-Type: ' . (string) get_post_mime_type($attachment_id));
    header('Content-Length: ' . filesize($file));
    $disposition = $dl === 1 ? 'attachment' : 'inline';
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($file) . '"');
    readfile($file);
    exit;
}
add_action('init', 'alpenia_handle_secure_download', 1);

function alpenia_roles_require_mfa($roles) {
    $roles = is_array($roles) ? $roles : [];
    $requires_mfa = array_intersect($roles, ['administrator', 'backoffice', 'manager', 'superadmin', 'staff']);
    return !empty($requires_mfa);
}

function alpenia_validate_mfa_for_privileged($user) {
    if (!$user || !($user instanceof WP_User)) {
        return $user;
    }

    if (!alpenia_roles_require_mfa((array) $user->roles)) {
        return $user;
    }

    $mfa_enabled = get_user_meta($user->ID, 'alpenia_mfa_enabled', true);
    if ($mfa_enabled === '' || $mfa_enabled === null) {
        update_user_meta($user->ID, 'alpenia_mfa_enabled', 1);
        alpenia_security_log('mfa_auto_enabled', ['target_user_id' => (int) $user->ID, 'trigger' => 'login_backfill']);
        return $user;
    }

    if ((int) $mfa_enabled !== 1) {
        return new WP_Error('alpenia_mfa_required', alpenia_travel_t('MFA ist für privilegierte Konten erforderlich.'));
    }

    return $user;
}
add_filter('wp_authenticate_user', 'alpenia_validate_mfa_for_privileged', 20);

function alpenia_auto_enable_mfa_for_privileged_user($user_id) {
    $user = get_user_by('id', (int) $user_id);
    if (!$user || !($user instanceof WP_User)) {
        return;
    }

    $created_by_admin = is_user_logged_in() && current_user_can('create_users');
    $requires_mfa_by_role = alpenia_roles_require_mfa((array) $user->roles);

    if (!$created_by_admin && !$requires_mfa_by_role) {
        return;
    }

    update_user_meta($user->ID, 'alpenia_mfa_enabled', 1);
    alpenia_security_log('mfa_auto_enabled', [
        'target_user_id' => (int) $user->ID,
        'trigger' => $created_by_admin ? 'user_register_admin_created' : 'user_register_privileged_role',
    ]);
}
add_action('user_register', 'alpenia_auto_enable_mfa_for_privileged_user', 20);

function alpenia_auto_enable_mfa_on_role_change($user_id, $role, $old_roles) {
    if (!alpenia_roles_require_mfa([$role])) {
        return;
    }

    update_user_meta((int) $user_id, 'alpenia_mfa_enabled', 1);
    alpenia_security_log('mfa_auto_enabled', ['target_user_id' => (int) $user_id, 'role' => (string) $role]);
}
add_action('set_user_role', 'alpenia_auto_enable_mfa_on_role_change', 20, 3);
