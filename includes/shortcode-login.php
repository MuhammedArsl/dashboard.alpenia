<?php
if (!defined('ABSPATH')) exit;

/**
 * Login Shortcode (E-Mail + Passwort)
 */
function alpenia_login_shortcode() {
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (function_exists('alpenia_send_strict_no_cache_headers')) {
        alpenia_send_strict_no_cache_headers();
    } else {
        nocache_headers();
    }

    $debug_mode = isset($_GET['alpenia_debug_auth']) && $_GET['alpenia_debug_auth'] == '1';
    $layout_styles = '<style>
        .entry-title,
        .page-title,
        .wp-block-post-title{display:none !important;}
        .entry-content,
        .site-main,
        .content-area,
        article{margin:0 !important;padding:0 !important;background:transparent !important;}
        .alpenia-login-page-shell{min-height:100vh;background:#2f9574;display:flex;align-items:center;justify-content:center;padding:20px;}
        .alpenia-login-card{width:100%;max-width:420px;padding:34px;background:#050505;border-radius:18px;border:1px solid rgba(255,255,255,0.08);box-shadow:0 20px 50px rgba(0,0,0,0.38);color:#fff;}
        .alpenia-login-title{margin:0 0 8px;font-size:30px;letter-spacing:0.4px;color:#fff;}
        .alpenia-login-subtitle{margin:0 0 22px;color:rgba(255,255,255,0.84);font-size:14px;}
        .alpenia-login-page-shell input{width:100%;height:48px;padding:0 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.16);background:rgba(255,255,255,0.05);color:#fff;outline:none;box-sizing:border-box;}
        .alpenia-login-page-shell input::placeholder{color:rgba(255,255,255,0.64);}
    </style>';

    if (is_user_logged_in()) {
        return $layout_styles . '<div class="alpenia-login-page-shell">
            <div class="alpenia-login-card" style="padding:36px;text-align:center;">
                Du bist bereits eingeloggt.<br><br>
                <a href="' . esc_url(alpenia_get_dashboard_url()) . '" style="display:inline-block;color:#d5ffe9;background:linear-gradient(135deg,#1e664f,#2f9574);padding:10px 16px;border-radius:10px;font-weight:700;text-decoration:none;">Zum Dashboard</a>
            </div>
        </div>';
    }

    $error = '';

    if (isset($_GET['session_expired']) && $_GET['session_expired'] === '1') {
        $error = 'Deine Sitzung ist wegen Inaktivität abgelaufen. Bitte erneut einloggen.';
        wp_clear_auth_cookie();
    }

    if (!function_exists('alpenia_login_attempt_key')) {
        function alpenia_login_attempt_key($email, $ip_address) {
            $identifier = strtolower(trim((string) $email)) . '|' . trim((string) $ip_address);
            return 'alpenia_login_attempts_' . md5($identifier);
        }
    }

    if (isset($_POST['alpenia_login'])) {
        if (!isset($_POST['alpenia_login_nonce']) || !wp_verify_nonce($_POST['alpenia_login_nonce'], 'alpenia_login_action')) {
            $error = 'Sicherheitsfehler beim Login. Bitte Seite neu laden und erneut versuchen.';
        } else {
            $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $ip_address = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $attempt_key = alpenia_login_attempt_key($email, $ip_address);
            $attempt_data = get_transient($attempt_key);

            if (!is_array($attempt_data)) {
                $attempt_data = [
                    'count' => 0,
                    'locked_until' => 0,
                ];
            }

            if (!empty($attempt_data['locked_until']) && (int) $attempt_data['locked_until'] > time()) {
                $minutes_left = max(1, (int) ceil(((int) $attempt_data['locked_until'] - time()) / MINUTE_IN_SECONDS));
                $error = sprintf('Zu viele Fehlversuche. Bitte in %d Minute(n) erneut versuchen.', $minutes_left);
            }
        }

        if (empty($error)) {
            if (empty($email) || empty($password)) {
                $error = 'Bitte E-Mail und Passwort eingeben.';
            } else {
                $user = get_user_by('email', $email);

                if ($user) {
                    $creds = [
                        'user_login'    => $user->user_login,
                        'user_password' => $password,
                        'remember'      => false,
                    ];

                    wp_clear_auth_cookie();
                    $signon = wp_signon($creds);

                    if (!is_wp_error($signon)) {
                        wp_set_current_user($signon->ID);
                        delete_transient($attempt_key);
                        wp_safe_redirect(alpenia_get_dashboard_url());
                        exit;
                    } else {
                        $attempt_data['count'] = ((int) ($attempt_data['count'] ?? 0)) + 1;
                        $attempt_data['locked_until'] = 0;

                        if ($attempt_data['count'] >= 5) {
                            $attempt_data['locked_until'] = time() + (10 * MINUTE_IN_SECONDS);
                            $attempt_data['count'] = 0;
                        }

                        set_transient($attempt_key, $attempt_data, 10 * MINUTE_IN_SECONDS);
                        $error = 'Falsches Passwort.';
                    }
                } else {
                    $error = 'Benutzer nicht gefunden.';
                }
            }
        }
    }

    ob_start();
    ?>
    <?php echo $layout_styles; ?>
    <div class="alpenia-login-page-shell">
        <div class="alpenia-login-card">
        <h2 class="alpenia-login-title">Willkommen zurück</h2>
        <p class="alpenia-login-subtitle">Melde dich an, um dein Dashboard zu öffnen.</p>

        <?php if ($debug_mode) : ?>
            <div style="margin:0 0 14px;padding:10px;border-radius:10px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);font-size:12px;line-height:1.5;">
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
            <p style="color:#ffd2d2;background:rgba(154,0,0,0.25);border:1px solid rgba(255,130,130,0.45);padding:10px 12px;border-radius:10px;"><?php echo esc_html($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('alpenia_login_action', 'alpenia_login_nonce'); ?>
            <p>
                <label for="alpenia-login-email" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">E-Mail</label><br>
                <input id="alpenia-login-email" type="email" name="email" required>
            </p>

            <p>
                <label for="alpenia-login-password" style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.85);">Passwort</label><br>
                <input id="alpenia-login-password" type="password" name="password" required>
            </p>

            <p style="margin:14px 0 0;">
                <button type="submit" name="alpenia_login" style="width:100%;height:50px;border:none;border-radius:12px;background:linear-gradient(135deg,#1e664f,#2f9574);color:#fff;font-weight:700;letter-spacing:0.2px;cursor:pointer;box-shadow:0 12px 26px rgba(35,119,92,0.36);">
                    Login
                </button>
            </p>
        </form>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('alpenia_login', 'alpenia_login_shortcode');
