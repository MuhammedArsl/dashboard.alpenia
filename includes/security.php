<?php
if (!defined('ABSPATH')) exit;

function alpenia_send_strict_no_cache_headers() {
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        header('Vary: Cookie', false);
    }

    nocache_headers();
}

/**
 * Handle explicit logout via POST to avoid accidental GET prefetch logout.
 */
function alpenia_handle_logout_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!isset($_POST['alpenia_logout']) || (string) $_POST['alpenia_logout'] !== '1') {
        return;
    }

    if (!isset($_POST['alpenia_logout_intent']) || (string) $_POST['alpenia_logout_intent'] !== 'dashboard_logout') {
        return;
    }

    if (!isset($_POST['alpenia_logout_nonce']) || !wp_verify_nonce($_POST['alpenia_logout_nonce'], 'alpenia_logout_action')) {
        return;
    }

    wp_logout();
    wp_safe_redirect(alpenia_get_login_url());
    exit;
}
add_action('template_redirect', 'alpenia_handle_logout_request', 1);

/**
 * Disable caching on pages that contain auth-related shortcodes.
 */
function alpenia_disable_cache_for_auth_shortcodes() {
    if (!is_page()) {
        return;
    }

    global $post;
    if (!$post || empty($post->post_content)) {
        return;
    }

    if (has_shortcode($post->post_content, 'alpenia_dashboard') || has_shortcode($post->post_content, 'alpenia_login')) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (!defined('DONOTCACHEDB')) {
            define('DONOTCACHEDB', true);
        }
        if (!defined('DONOTMINIFY')) {
            define('DONOTMINIFY', true);
        }

        alpenia_send_strict_no_cache_headers();
    }
}
add_action('template_redirect', 'alpenia_disable_cache_for_auth_shortcodes', 1);

/**
 * Login Sperre für deaktivierte User
 */
add_filter('authenticate', 'alpenia_block_disabled_users_login', 30, 3);

function alpenia_block_disabled_users_login($user, $username, $password) {
    if (empty($username) || empty($password)) {
        return $user;
    }

    if (is_wp_error($user)) {
        return $user;
    }

    if (!$user || !is_object($user) || empty($user->ID)) {
        $found_user = get_user_by('login', $username);

        if (!$found_user && is_email($username)) {
            $found_user = get_user_by('email', $username);
        }

        if (!$found_user) {
            return $user;
        }

        $user = $found_user;
    }

    $disabled = get_user_meta($user->ID, 'alpenia_disabled', true);

    if ($disabled) {
        return new WP_Error('alpenia_disabled_user', 'Dieser Benutzer wurde deaktiviert.');
    }

    return $user;
}


/**
 * Inactivity timeout for frontend sessions.
 */
function alpenia_enforce_idle_session_timeout() {
    if (!is_user_logged_in()) {
        return;
    }

    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $timeout_seconds = 15 * MINUTE_IN_SECONDS;
    $user_id = get_current_user_id();
    $now = time();
    $last_activity = (int) get_user_meta($user_id, 'alpenia_last_activity', true);

    if ($last_activity > 0 && ($now - $last_activity) >= $timeout_seconds) {
        wp_logout();
        wp_safe_redirect(add_query_arg('session_expired', '1', alpenia_get_login_url()));
        exit;
    }

    update_user_meta($user_id, 'alpenia_last_activity', $now);
}
add_action('template_redirect', 'alpenia_enforce_idle_session_timeout', 2);


function alpenia_set_initial_activity_timestamp($user_login, $user) {
    if (!$user || empty($user->ID)) {
        return;
    }

    update_user_meta($user->ID, 'alpenia_last_activity', time());
}
add_action('wp_login', 'alpenia_set_initial_activity_timestamp', 10, 2);

function alpenia_clear_activity_timestamp_on_logout() {
    $user_id = get_current_user_id();

    if ($user_id) {
        delete_user_meta($user_id, 'alpenia_last_activity');
    }
}
add_action('wp_logout', 'alpenia_clear_activity_timestamp_on_logout');
