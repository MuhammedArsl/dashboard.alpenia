<?php
if (!defined('ABSPATH')) exit;

/**
 * Login Shortcode (E-Mail + Passwort)
 */
function alpenia_login_shortcode() {
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();

    $debug_mode = isset($_GET['alpenia_debug_auth']) && $_GET['alpenia_debug_auth'] == '1';

    if (is_user_logged_in()) {
        return '<div style="max-width:420px;margin:80px auto;padding:30px;background:rgba(18,46,38,0.7);border-radius:16px;color:#fff;text-align:center;">
            Du bist bereits eingeloggt.<br><br>
            <a href="' . esc_url(alpenia_get_dashboard_url()) . '" style="color:#8ee0b8;font-weight:bold;">Zum Dashboard</a>
        </div>';
    }

    $error = '';

    if (isset($_POST['alpenia_login'])) {
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Bitte E-Mail und Passwort eingeben.';
        } else {
            $user = get_user_by('email', $email);

            if ($user) {
                $creds = [
                    'user_login'    => $user->user_login,
                    'user_password' => $password,
                    'remember'      => true,
                ];

                $signon = wp_signon($creds);

                if (!is_wp_error($signon)) {
                    wp_set_current_user($signon->ID);
                    wp_set_auth_cookie($signon->ID, true);
                    wp_safe_redirect(alpenia_get_dashboard_url());
                    exit;
                } else {
                    $error = 'Falsches Passwort.';
                }
            } else {
                $error = 'Benutzer nicht gefunden.';
            }
        }
    }

    ob_start();
    ?>
    <div style="max-width:420px;margin:80px auto;padding:30px;background:rgba(18,46,38,0.7);border-radius:16px;color:#fff;">
        <h2 style="margin-top:0;">Login</h2>

        <?php if ($debug_mode) : ?>
            <div style="margin:0 0 14px;padding:10px;border-radius:8px;background:rgba(255,255,255,0.08);font-size:12px;line-height:1.5;">
                <strong>Auth Debug</strong><br>
                Fail: <?php echo esc_html(sanitize_text_field($_GET['alpenia_auth_fail'] ?? 'none')); ?><br>
                Logged in: <?php echo is_user_logged_in() ? 'yes' : 'no'; ?><br>
                Current user id: <?php echo (int) get_current_user_id(); ?><br>
                Cookie user id: <?php echo (int) wp_validate_auth_cookie('', 'logged_in'); ?><br>
                Host: <?php echo esc_html(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? ''))); ?><br>
                URI: <?php echo esc_html(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? ''))); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <p style="color:#ffb3b3;"><?php echo esc_html($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <p>
                <label for="alpenia-login-email">E-Mail</label><br>
                <input id="alpenia-login-email" type="email" name="email" required style="width:100%;height:46px;padding:0 12px;border-radius:8px;border:1px solid #ccc;">
            </p>

            <p>
                <label for="alpenia-login-password">Passwort</label><br>
                <input id="alpenia-login-password" type="password" name="password" required style="width:100%;height:46px;padding:0 12px;border-radius:8px;border:1px solid #ccc;">
            </p>

            <p style="margin-bottom:0;">
                <button type="submit" name="alpenia_login" style="width:100%;height:48px;border:none;border-radius:10px;background:#1d4d3f;color:#fff;font-weight:bold;cursor:pointer;">
                    Login
                </button>
            </p>
        </form>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('alpenia_login', 'alpenia_login_shortcode');

