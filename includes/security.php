<?php
if (!defined('ABSPATH')) exit;

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
        nocache_headers();
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
