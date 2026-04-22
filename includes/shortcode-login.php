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
        .alpenia-login-page-shell{
            --alpenia-surface:#0b1526;
            --alpenia-surface-soft:#132239;
            --alpenia-accent:#1fbf9d;
            --alpenia-accent-hover:#17ab8d;
            --alpenia-text:#f4f8ff;
            --alpenia-text-muted:rgba(232,241,255,0.74);
            --alpenia-border:rgba(255,255,255,0.16);
            min-height:calc(100vh - 64px);
            width:100%;
            max-width:1140px;
            margin:0 auto;
            padding:clamp(24px,4vw,44px) 20px 28px;
            display:flex;
            justify-content:center;
            align-items:flex-start;
            box-sizing:border-box;
        }
        .alpenia-login-card{
            width:min(100%,460px);
            padding:clamp(26px,4vw,34px);
            background:linear-gradient(155deg,var(--alpenia-surface) 0%,var(--alpenia-surface-soft) 100%);
            border-radius:20px;
            border:1px solid var(--alpenia-border);
            box-shadow:0 20px 44px rgba(3,9,18,0.24);
            color:var(--alpenia-text);
            position:relative;
            overflow:hidden;
        }
        .alpenia-login-card::before{
            content:"";
            position:absolute;
            inset:-48% auto auto -20%;
            width:230px;
            height:230px;
            background:radial-gradient(circle,rgba(31,191,157,0.28) 0%,rgba(31,191,157,0) 70%);
            pointer-events:none;
        }
        .alpenia-login-card > *{position:relative;z-index:1;}
        .alpenia-login-title{margin:0 0 8px;font-size:clamp(30px,4vw,36px);line-height:1.1;letter-spacing:0.2px;color:var(--alpenia-text);}
        .alpenia-login-subtitle{margin:0 0 24px;color:var(--alpenia-text-muted);font-size:15px;line-height:1.45;}
        .alpenia-login-form{display:grid;gap:14px;}
        .alpenia-form-group{margin:0;display:grid;gap:8px;}
        .alpenia-form-label{font-size:13px;font-weight:600;letter-spacing:0.2px;color:rgba(244,248,255,0.9);}
        .alpenia-login-page-shell input{
            width:100%;
            height:48px;
            padding:0 14px;
            border-radius:11px;
            border:1px solid rgba(255,255,255,0.2);
            background:rgba(255,255,255,0.06);
            color:var(--alpenia-text);
            outline:none;
            box-sizing:border-box;
            transition:border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }
        .alpenia-login-page-shell input:hover{background:rgba(255,255,255,0.08);}
        .alpenia-login-page-shell input:focus{border-color:rgba(31,191,157,0.9);box-shadow:0 0 0 3px rgba(31,191,157,0.2);}
        .alpenia-login-page-shell input::placeholder{color:rgba(244,248,255,0.58);}
        .alpenia-login-error{
            margin:0 0 18px;
            color:#ffdede;
            background:rgba(150, 30, 30, 0.32);
            border:1px solid rgba(255,160,160,0.44);
            padding:10px 12px;
            border-radius:10px;
            font-size:14px;
        }
        .alpenia-login-submit{margin:4px 0 0;}
        .alpenia-login-submit button{
            width:100%;
            min-height:48px;
            border:none;
            border-radius:11px;
            background:linear-gradient(135deg,var(--alpenia-accent),#20c7a4);
            color:#ffffff;
            font-weight:700;
            letter-spacing:0.2px;
            cursor:pointer;
            transition:transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            box-shadow:0 12px 28px rgba(17,129,106,0.34);
        }
        .alpenia-login-submit button:hover{background:linear-gradient(135deg,var(--alpenia-accent-hover),#19b996);}
        .alpenia-login-submit button:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(31,191,157,0.25),0 12px 28px rgba(17,129,106,0.34);}
        .alpenia-login-submit button:active{transform:translateY(1px);}
        .site-header,
        .ast-primary-header-bar,
        .main-header-bar,
        .ast-below-header-wrap{
            background:linear-gradient(120deg,#061121 0%,#0b1526 62%,#10213a 100%) !important;
            border-bottom:1px solid rgba(255,255,255,0.08);
        }
        .main-navigation a,
        .ast-header-navigation a{
            color:#e6edf9 !important;
            font-weight:600;
        }
        .main-navigation a:hover,
        .ast-header-navigation a:hover{
            color:#ffffff !important;
        }
        @media (max-width: 900px){
            .alpenia-login-page-shell{padding:28px 16px 20px;}
        }
        @media (max-width: 640px){
            .alpenia-login-page-shell{min-height:calc(100vh - 56px);padding:22px 14px 18px;}
            .alpenia-login-card{border-radius:16px;padding:24px 18px;}
            .alpenia-login-title{font-size:30px;}
            .alpenia-login-subtitle{font-size:14px;margin-bottom:20px;}
            .alpenia-login-form{gap:12px;}
            .alpenia-login-page-shell input{height:46px;}
        }
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
            <p class="alpenia-login-error"><?php echo esc_html($error); ?></p>
        <?php endif; ?>

        <form class="alpenia-login-form" method="post">
            <?php wp_nonce_field('alpenia_login_action', 'alpenia_login_nonce'); ?>
            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-login-email">E-Mail</label>
                <input id="alpenia-login-email" type="email" name="email" required>
            </p>

            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-login-password">Passwort</label>
                <input id="alpenia-login-password" type="password" name="password" required>
            </p>

            <p class="alpenia-login-submit">
                <button type="submit" name="alpenia_login">
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
