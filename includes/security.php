<?php
if (!defined('ABSPATH')) exit;

/**
 * Ensure frontend current user is restored from auth cookie early.
 */
function alpenia_restore_current_user_from_cookie() {
    if (is_user_logged_in()) {
        return;
    }

    $cookie_user_id = wp_validate_auth_cookie('', 'logged_in');
    if ($cookie_user_id) {
        wp_set_current_user((int) $cookie_user_id);
    }
}
add_action('init', 'alpenia_restore_current_user_from_cookie', 1);

/**
 * Handle explicit logout via POST to avoid accidental GET prefetch logout.
 */
function alpenia_handle_logout_request() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!isset($_POST['alpenia_logout'])) {
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
 * Dashboard Schutz
 */
function alpenia_protect_dashboard() {
    if (is_page() && !is_user_logged_in()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'alpenia_dashboard')) {
            nocache_headers();
            return;
        }
    }
}
add_action('template_redirect', 'alpenia_protect_dashboard');

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

