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
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");
        .entry-title,
        .page-title,
        .wp-block-post-title{display:none !important;}
        .alpenia-login-page-shell{
            --alpenia-primary:#0f3d2e;
            --alpenia-primary-soft:#1f7a63;
            --alpenia-accent:#2bd4a3;
            --alpenia-accent-soft:rgba(43,212,163,0.32);
            --alpenia-text:#ffffff;
            --alpenia-text-muted:rgba(244,250,248,0.78);
            --alpenia-text-dark:#1a1a1a;
            --alpenia-card-border:rgba(255,255,255,0.34);
            min-height:calc(100vh - 56px);
            width:100%;
            max-width:1160px;
            margin:0 auto;
            padding:clamp(28px,4vw,52px) 20px 34px;
            display:flex;
            flex-direction:column;
            gap:clamp(20px,3vw,30px);
            justify-content:center;
            align-items:center;
            box-sizing:border-box;
            font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            position:relative;
            isolation:isolate;
        }
        .alpenia-login-page-shell::before{
            content:"";
            position:absolute;
            inset:0;
            z-index:-2;
            background:linear-gradient(150deg,#f5f5f3 0%,#efefea 45%,#eaeae6 100%);
            border-radius:30px;
        }
        .alpenia-login-page-shell::after{
            content:"";
            position:absolute;
            inset:0;
            z-index:-1;
            border-radius:30px;
            background:
                radial-gradient(circle at 8% 15%,rgba(31,122,99,0.14),rgba(31,122,99,0) 42%),
                radial-gradient(circle at 90% 8%,rgba(43,212,163,0.14),rgba(43,212,163,0) 38%);
            pointer-events:none;
        }
        .alpenia-login-nav{
            width:min(100%,780px);
            padding:10px;
            border-radius:999px;
            background:linear-gradient(120deg,#0f3d2e 0%,#1f7a63 100%);
            box-shadow:0 16px 35px rgba(15,61,46,0.22);
            border:1px solid rgba(255,255,255,0.22);
            backdrop-filter:blur(8px);
            -webkit-backdrop-filter:blur(8px);
        }
        .alpenia-login-nav-list{
            margin:0;
            padding:0;
            list-style:none;
            display:flex;
            gap:10px;
            justify-content:center;
            flex-wrap:wrap;
        }
        .alpenia-login-nav-pill{
            border:none;
            text-decoration:none;
            color:#ffffff;

            border-radius:999px;
            background:rgba(9,39,30,0.92);
            color:#ffffff;
            padding:10px 20px;
            font-size:13px;
            font-weight:600;
            letter-spacing:0.2px;
            box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 8px 16px rgba(5,23,18,0.25);
            transition:all 0.25s ease;
        }
        .alpenia-login-nav-pill:hover,
        .alpenia-login-nav-pill:focus{
            color:#ffffff;
            background:rgba(12,63,48,0.96);
            transform:translateY(-1px);
            box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 0 0 1px rgba(43,212,163,0.45),0 0 16px rgba(43,212,163,0.32);
        }
        .alpenia-login-nav-pill.is-active{
            background:linear-gradient(135deg,rgba(43,212,163,0.35),rgba(43,212,163,0.2));
            box-shadow:inset 0 0 0 1px rgba(43,212,163,0.62),0 0 18px rgba(43,212,163,0.42);
        }
        .alpenia-login-card{
            width:min(100%,460px);
            padding:clamp(30px,4vw,38px);
            background:linear-gradient(155deg,var(--alpenia-primary) 0%,var(--alpenia-primary-soft) 100%);
            border-radius:28px;
            border:1px solid var(--alpenia-card-border);
            box-shadow:0 30px 56px rgba(9,43,33,0.26), inset 0 1px 0 rgba(255,255,255,0.2);
            color:var(--alpenia-text);
            position:relative;
            overflow:hidden;
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
        }
        .alpenia-login-card::before{
            content:"";
            position:absolute;
            inset:-56% auto auto -24%;
            width:280px;
            height:280px;
            background:radial-gradient(circle,rgba(43,212,163,0.38) 0%,rgba(43,212,163,0) 70%);
            pointer-events:none;
        }
        .alpenia-login-card::after{
            content:"";
            position:absolute;
            inset:auto -20% -58% auto;
            width:260px;
            height:260px;
            background:radial-gradient(circle,rgba(255,255,255,0.24) 0%,rgba(255,255,255,0) 72%);
            pointer-events:none;
        }
        .alpenia-login-card > *{position:relative;z-index:1;}
        .alpenia-login-title{margin:0 0 10px;font-size:clamp(31px,4vw,40px);line-height:1.08;letter-spacing:0.2px;color:var(--alpenia-text);font-weight:800;}
        .alpenia-login-subtitle{margin:0 0 24px;color:var(--alpenia-text-muted);font-size:15px;line-height:1.45;}
        .alpenia-login-form{display:grid;gap:15px;}
        .alpenia-form-group{margin:0;display:grid;gap:8px;}
        .alpenia-form-label{font-size:13px;font-weight:600;letter-spacing:0.2px;color:rgba(255,255,255,0.92);}
        .alpenia-login-page-shell input{
            width:100%;
            height:52px;
            padding:0 16px;
            border-radius:16px;
            border:1px solid rgba(255,255,255,0.72);
            background:#ffffff;
            color:var(--alpenia-text-dark);
            outline:none;
            box-sizing:border-box;
            transition:border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
            box-shadow:inset 0 2px 6px rgba(15,61,46,0.12);
        }
        .alpenia-login-page-shell input:hover{transform:translateY(-1px);}
        .alpenia-login-page-shell input:focus{border-color:rgba(43,212,163,0.95);box-shadow:0 0 0 3px rgba(43,212,163,0.3),inset 0 2px 8px rgba(15,61,46,0.12);}
        .alpenia-login-page-shell input::placeholder{color:#555;opacity:1;}
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
            min-height:54px;
            border:none;
            border-radius:18px;
            background:linear-gradient(135deg,#0f3d2e 0%,#1f7a63 100%);
            color:#ffffff;
            font-weight:700;
            font-size:16px;
            letter-spacing:0.25px;
            cursor:pointer;
            transition:transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
            box-shadow:0 16px 34px rgba(8,36,28,0.35), inset 0 1px 0 rgba(255,255,255,0.24);
        }
        .alpenia-login-submit button:hover{filter:brightness(1.08);box-shadow:0 0 0 1px rgba(43,212,163,0.42),0 0 22px rgba(43,212,163,0.36),0 16px 34px rgba(8,36,28,0.35);}
        .alpenia-login-submit button:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(43,212,163,0.32),0 0 24px rgba(43,212,163,0.34),0 16px 34px rgba(8,36,28,0.35);}
        .alpenia-login-submit button:active{transform:translateY(1px);}
        .site-header,
        .ast-primary-header-bar,
        .main-header-bar,
        .ast-below-header-wrap{
            background:linear-gradient(120deg,#0c3327 0%,#0f3d2e 50%,#1f7a63 100%) !important;
            border-bottom:1px solid rgba(255,255,255,0.12);
        }
        .main-navigation a,
        .ast-header-navigation a{
            color:#ffffff !important;
            font-weight:600;
            border-radius:999px;
            padding:8px 14px !important;
            transition:all 0.25s ease;
        }
        .main-navigation a:hover,
        .ast-header-navigation a:hover{
            color:#ffffff !important;
            box-shadow:0 0 0 1px rgba(43,212,163,0.5),0 0 12px rgba(43,212,163,0.33);
        }
        .main-navigation .current-menu-item > a,
        .ast-header-navigation .current-menu-item > a{
            background:rgba(43,212,163,0.25);
            box-shadow:inset 0 0 0 1px rgba(43,212,163,0.5);
        }
        @media (max-width: 900px){
            .alpenia-login-page-shell{padding:24px 16px 20px;}
            .alpenia-login-nav{padding:8px;}
        }
        @media (max-width: 640px){
            .alpenia-login-page-shell{min-height:calc(100vh - 52px);padding:20px 12px 16px;border-radius:22px;}
            .alpenia-login-nav-list{gap:8px;}
            .alpenia-login-nav-pill{padding:8px 14px;font-size:12px;}
            .alpenia-login-card{border-radius:24px;padding:24px 18px;}
            .alpenia-login-title{font-size:30px;}
            .alpenia-login-subtitle{font-size:14px;margin-bottom:20px;}
            .alpenia-login-form{gap:12px;}
            .alpenia-login-page-shell input{height:48px;}
        }
    </style>';

    if (is_user_logged_in()) {
        return $layout_styles . '<div class="alpenia-login-page-shell">
            <div class="alpenia-login-card" style="padding:36px;text-align:center;">
                Du bist bereits eingeloggt.<br><br>
                <a href="' . esc_url(alpenia_get_dashboard_url()) . '" style="display:inline-block;color:#d5ffe9;background:linear-gradient(135deg,#1e664f,#2f9574);padding:10px 16px;border-radius:10px;font-weight:700;text-decoration:none;">' . esc_html(alpenia_travel_t('Zum Dashboard')) . '</a>
            </div>
        </div>';
    }

    $error = '';
    $success = '';
    $mode = isset($_GET['mode']) ? sanitize_key($_GET['mode']) : 'login';

    if (isset($_GET['session_expired']) && $_GET['session_expired'] === '1' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $error = alpenia_travel_t('Deine Sitzung ist wegen Inaktivität abgelaufen. Bitte erneut einloggen.');
        wp_clear_auth_cookie();
    }

    if (!function_exists('alpenia_login_attempt_key')) {
        function alpenia_login_attempt_key($email, $ip_address) {
            $identifier = strtolower(trim((string) $email)) . '|' . trim((string) $ip_address);
            return 'alpenia_login_attempts_' . md5($identifier);
        }
    }

    if (!function_exists('alpenia_auth_error_message')) {
        function alpenia_auth_error_message($signon_error) {
            if (!is_wp_error($signon_error)) {
                return alpenia_travel_t('Ungültige Anmeldedaten.');
            }

            $codes = (array) $signon_error->get_error_codes();

            if (in_array('alpenia_disabled_user', $codes, true)) {
                return alpenia_travel_t('Dein Benutzerkonto wurde deaktiviert. Bitte den Support kontaktieren.');
            }

            if (in_array('incorrect_password', $codes, true) || in_array('invalid_username', $codes, true)) {
                return alpenia_travel_t('Ungültige Anmeldedaten.');
            }

            $first_error = $signon_error->get_error_message();
            if (is_string($first_error) && trim($first_error) !== '') {
                return alpenia_travel_t(wp_strip_all_tags($first_error));
            }

            return alpenia_travel_t('Anmeldung momentan nicht möglich. Bitte Support kontaktieren.');
        }
    }

    if (!function_exists('alpenia_attempt_signon')) {
        function alpenia_attempt_signon($identifiers, $password) {
            $identifiers = is_array($identifiers) ? $identifiers : [$identifiers];
            $last_error = null;

            foreach ($identifiers as $identifier) {
                $identifier = trim((string) $identifier);
                if ($identifier === '') {
                    continue;
                }

                wp_clear_auth_cookie();
                $signon = wp_signon([
                    'user_login'    => $identifier,
                    'user_password' => (string) $password,
                    'remember'      => false,
                ]);

                if (!is_wp_error($signon)) {
                    return $signon;
                }

                $last_error = $signon;
            }

            if ($last_error instanceof WP_Error) {
                return $last_error;
            }

            return new WP_Error('invalid_username', alpenia_travel_t('Ungültige Anmeldedaten.'));
        }
    }

    if (isset($_POST['alpenia_request_reset'])) {
        if (!isset($_POST['alpenia_reset_nonce']) || !wp_verify_nonce($_POST['alpenia_reset_nonce'], 'alpenia_reset_action')) {
            $error = alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.');
        } else {
            $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            if (!empty($email)) {
                $user = get_user_by('email', $email);
                if ($user && !empty($user->ID)) {
                    $token = alpenia_generate_password_reset_token($user->ID);
                    $reset_url = add_query_arg([
                        'mode' => 'reset',
                        'uid' => (int) $user->ID,
                        'token' => rawurlencode($token),
                    ], alpenia_get_login_url());
                    wp_mail($email, 'Passwort zurücksetzen', 'Setze dein Passwort hier zurück: ' . esc_url_raw($reset_url));
                    alpenia_security_log('password_reset_requested', ['target_user_id' => (int) $user->ID]);
                }
            }
            $success = alpenia_travel_t('Wenn die E-Mail existiert, wurde ein Link zum Zurücksetzen gesendet.');
            $mode = 'login';
        }
    }

    if (isset($_POST['alpenia_set_new_password'])) {
        if (!isset($_POST['alpenia_set_password_nonce']) || !wp_verify_nonce($_POST['alpenia_set_password_nonce'], 'alpenia_set_password_action')) {
            $error = alpenia_travel_t('Sicherheitsfehler. Bitte erneut versuchen.');
            $mode = 'reset';
        } else {
            $user_id = (int) ($_POST['uid'] ?? 0);
            $token = sanitize_text_field(wp_unslash($_POST['token'] ?? ''));
            $password = trim((string) ($_POST['new_password'] ?? ''));

            if ($user_id <= 0 || strlen($password) < 12 || !alpenia_validate_password_reset_token($user_id, $token)) {
                $error = alpenia_travel_t('Reset-Link ungültig oder abgelaufen.');
                alpenia_security_log('password_reset_failed', ['target_user_id' => $user_id]);
            } else {
                wp_set_password($password, $user_id);
                alpenia_consume_password_reset_token($user_id);
                $user = get_user_by('id', $user_id);

                if ($user && !empty($user->user_login)) {
                    $signon = alpenia_attempt_signon([$user->user_email ?? '', $user->user_login], $password);

                    if (!is_wp_error($signon)) {
                        wp_set_current_user($signon->ID);
                        alpenia_security_log('password_reset_success', ['target_user_id' => $user_id, 'auto_login' => true]);
                        wp_safe_redirect(alpenia_get_dashboard_url());
                        exit;
                    }

                    $error = alpenia_auth_error_message($signon);
                    alpenia_security_log('password_reset_success', ['target_user_id' => $user_id, 'auto_login' => false]);
                } else {
                    alpenia_security_log('password_reset_success', ['target_user_id' => $user_id, 'auto_login' => false]);
                }

                if (empty($error)) {
                    $success = alpenia_travel_t('Passwort erfolgreich geändert. Bitte einloggen.');
                }

                $mode = 'login';
            }
        }
    }

    if (isset($_POST['alpenia_login'])) {
        if (!isset($_POST['alpenia_login_nonce']) || !wp_verify_nonce($_POST['alpenia_login_nonce'], 'alpenia_login_action')) {
            $error = alpenia_travel_t('Sicherheitsfehler beim Login. Bitte Seite neu laden und erneut versuchen.');
        } else {
            $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $password = trim((string) ($_POST['password'] ?? ''));
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
                $error = sprintf(alpenia_travel_t('Zu viele Fehlversuche. Bitte in %d Minute(n) erneut versuchen.'), $minutes_left);
            }
        }

        if (empty($error)) {
            if (empty($email) || empty($password)) {
                $error = alpenia_travel_t('Bitte E-Mail und Passwort eingeben.');
            } else {
                if (!empty($email)) {
                    $user = get_user_by('email', $email);
                    $identifiers = [$email];
                    if ($user && !empty($user->user_login)) {
                        $identifiers[] = $user->user_login;
                    }

                    $signon = alpenia_attempt_signon($identifiers, $password);

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
                        $error = alpenia_auth_error_message($signon);
                        alpenia_security_log('login_failed', ['email_hash' => hash('sha256', strtolower($email))]);
                    }
                } else {
                    $error = alpenia_travel_t('Ungültige Anmeldedaten.');
                    alpenia_security_log('login_failed', ['email_hash' => hash('sha256', strtolower($email))]);
                }
            }
        }
    }

    ob_start();
    ?>
    <?php echo $layout_styles; ?>
    <div class="alpenia-login-page-shell">
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;">
            <a href="<?php echo esc_url(add_query_arg('ui_lang', 'de')); ?>" class="alpenia-login-nav-pill" style="text-decoration:none;">Deutsch</a>
            <a href="<?php echo esc_url(add_query_arg('ui_lang', 'tr')); ?>" class="alpenia-login-nav-pill" style="text-decoration:none;"><?php echo esc_html(alpenia_travel_t('Türkçe')); ?></a>
        </div>
        <nav class="alpenia-login-nav" aria-label="Travel sections">
            <ul class="alpenia-login-nav-list">
                <li><span class="alpenia-login-nav-pill is-active"><?php echo esc_html($mode === 'reset_request' ? alpenia_travel_t('Passwort vergessen') : ($mode === 'reset' ? alpenia_travel_t('Passwort ändern') : alpenia_travel_t('Login'))); ?></span></li>
                <li><span class="alpenia-login-nav-pill"><?php echo esc_html(alpenia_travel_t('Kulturreisen')); ?></span></li>
                <li><span class="alpenia-login-nav-pill"><?php echo esc_html(alpenia_travel_t('Umrah')); ?></span></li>
                <li><span class="alpenia-login-nav-pill"><?php echo esc_html(alpenia_travel_t('Hadsch')); ?></span></li>
            </ul>
        </nav>
        <div class="alpenia-login-card">
        <h2 class="alpenia-login-title"><?php echo esc_html(alpenia_travel_t('Willkommen zurück')); ?></h2>
        <p class="alpenia-login-subtitle"><?php echo esc_html(alpenia_travel_t('Melde dich an, um dein Dashboard zu öffnen.')); ?></p>

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
        <?php if (!empty($success)) : ?>
            <p class="alpenia-login-error" style="background:rgba(8,120,62,.32);border-color:rgba(130,255,178,.44);color:#d8ffe8;"><?php echo esc_html($success); ?></p>
        <?php endif; ?>

        <?php if ($mode === 'reset_request') : ?>
        <form class="alpenia-login-form" method="post">
            <?php wp_nonce_field('alpenia_reset_action', 'alpenia_reset_nonce'); ?>
            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-reset-email"><?php echo esc_html(alpenia_travel_t('E-Mail')); ?></label>
                <input id="alpenia-reset-email" type="email" name="email" required>
            </p>
            <p class="alpenia-login-submit"><button type="submit" name="alpenia_request_reset"><?php echo esc_html(alpenia_travel_t('Reset-Link senden')); ?></button></p>
            <p><a style="color:#fff;" href="<?php echo esc_url(alpenia_get_login_url()); ?>"><?php echo esc_html(alpenia_travel_t('Zurück zum Login')); ?></a></p>
        </form>
        <?php elseif ($mode === 'reset') : ?>
        <form class="alpenia-login-form" method="post">
            <?php wp_nonce_field('alpenia_set_password_action', 'alpenia_set_password_nonce'); ?>
            <input type="hidden" name="uid" value="<?php echo (int) ($_GET['uid'] ?? 0); ?>">
            <input type="hidden" name="token" value="<?php echo esc_attr(sanitize_text_field(wp_unslash($_GET['token'] ?? ''))); ?>">
            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-new-password"><?php echo esc_html(alpenia_travel_t('Neues Passwort (mind. 12 Zeichen)')); ?></label>
                <input id="alpenia-new-password" type="password" name="new_password" minlength="12" required>
            </p>
            <p class="alpenia-login-submit"><button type="submit" name="alpenia_set_new_password"><?php echo esc_html(alpenia_travel_t('Passwort speichern')); ?></button></p>
        </form>
        <?php else : ?>
        <form class="alpenia-login-form" method="post">
            <?php wp_nonce_field('alpenia_login_action', 'alpenia_login_nonce'); ?>
            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-login-email"><?php echo esc_html(alpenia_travel_t('E-Mail')); ?></label>
                <input id="alpenia-login-email" type="email" name="email" required>
            </p>

            <p class="alpenia-form-group">
                <label class="alpenia-form-label" for="alpenia-login-password"><?php echo esc_html(alpenia_travel_t('Passwort')); ?></label>
                <input id="alpenia-login-password" type="password" name="password" required>
            </p>

            <p class="alpenia-login-submit">
                <button type="submit" name="alpenia_login">
                    <?php echo esc_html(alpenia_travel_t('Login')); ?>
                </button>
            </p>
            <p><a style="color:#fff;" href="<?php echo esc_url(add_query_arg('mode', 'reset_request', alpenia_get_login_url())); ?>"><?php echo esc_html(alpenia_travel_t('Passwort vergessen?')); ?></a></p>
        </form>
        <?php endif; ?>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('alpenia_login', 'alpenia_login_shortcode');
