<?php
if (!defined('ABSPATH')) exit;

/**
 * Rollen
 */
function alpenia_add_roles() {
    if (!get_role('reiseleiter')) {
        add_role('reiseleiter', 'Reiseleiter', [
            'read' => true,
            'upload_files' => true,
        ]);
    }

    if (!get_role('backoffice')) {
        add_role('backoffice', 'Backoffice', [
            'read' => true,
            'upload_files' => true,
        ]);
    }

    if (!get_role('customer')) {
        add_role('customer', 'Customer', ['read' => true]);
    }

    if (!get_role('support')) {
        add_role('support', 'Support', ['read' => true]);
    }

    if (!get_role('staff')) {
        add_role('staff', 'Staff', ['read' => true, 'upload_files' => true]);
    }

    if (!get_role('manager')) {
        add_role('manager', 'Manager', ['read' => true, 'upload_files' => true]);
    }

    if (!get_role('superadmin')) {
        add_role('superadmin', 'Superadmin', ['read' => true, 'upload_files' => true, 'list_users' => true]);
    }
}

register_activation_hook(ALPENIA_PLUGIN_FILE, 'alpenia_add_roles');
add_action('init', 'alpenia_add_roles');

/**
 * CPTs
 */
add_action('init', function () {
    register_post_type('group_trip', [
        'label' => 'Gruppenreisen',
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-location',
        'supports' => ['title', 'editor', 'author'],
        'show_in_rest' => true,
    ]);

    register_post_type('trip_participant', [
        'label' => 'Teilnehmer',
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'author'],
        'show_in_rest' => true,
    ]);
});

/**
 * Rollen / Zugriff
 */
function alpenia_get_current_user_roles() {
    if (!is_user_logged_in()) return [];
    $user = wp_get_current_user();
    return (array) $user->roles;
}

function alpenia_is_admin_user() {
    return in_array('administrator', alpenia_get_current_user_roles(), true);
}

function alpenia_is_reiseleiter_user() {
    return in_array('reiseleiter', alpenia_get_current_user_roles(), true);
}

function alpenia_is_backoffice_user() {
    return in_array('backoffice', alpenia_get_current_user_roles(), true);
}

function alpenia_has_role($role) {
    return in_array($role, alpenia_get_current_user_roles(), true);
}

function alpenia_user_can_access_dashboard() {
    if (!is_user_logged_in()) return false;

    return alpenia_is_admin_user()
        || alpenia_has_role('superadmin')
        || alpenia_has_role('manager')
        || alpenia_has_role('staff')
        || alpenia_has_role('support')
        || alpenia_is_reiseleiter_user()
        || alpenia_is_backoffice_user();
}

function alpenia_user_can_manage_users() {
    return alpenia_is_admin_user() || alpenia_has_role('superadmin') || alpenia_has_role('manager');
}

function alpenia_user_can_delete_trip($trip_id) {
    if (!$trip_id) return false;

    if (alpenia_is_admin_user() || alpenia_is_backoffice_user()) {
        return true;
    }

    $author_id = (int) get_post_field('post_author', $trip_id);
    $assigned_guide = (int) get_post_meta($trip_id, 'assigned_guide', true);
    $current_user_id = get_current_user_id();

    return $current_user_id === $author_id || $current_user_id === $assigned_guide;
}

/**
 * Frontend URLs for login/dashboard pages (resolved by shortcode page).
 */
function alpenia_get_request_scheme() {
    if (is_ssl()) {
        return 'https';
    }

    $forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower((string) wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';
    if ($forwarded_proto !== '') {
        $parts = array_map('trim', explode(',', $forwarded_proto));
        if (in_array('https', $parts, true)) {
            return 'https';
        }
    }

    $request_scheme = isset($_SERVER['REQUEST_SCHEME']) ? strtolower((string) wp_unslash($_SERVER['REQUEST_SCHEME'])) : '';
    if ($request_scheme === 'https') {
        return 'https';
    }

    $home_scheme = wp_parse_url(home_url(), PHP_URL_SCHEME);
    return $home_scheme === 'https' ? 'https' : 'http';
}

function alpenia_normalize_url_to_current_host($url) {
    $url = (string) $url;
    if ($url === '') return $url;

    $parts = wp_parse_url($url);
    if (!is_array($parts)) return $url;

    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $scheme = alpenia_get_request_scheme();
    if ($host === '') {
        return set_url_scheme($url, $scheme);
    }

    $path = isset($parts['path']) ? $parts['path'] : '/';
    $query = isset($parts['query']) ? $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

    $normalized = $scheme . '://' . $host . $path;
    if ($query !== '') $normalized .= '?' . $query;
    if ($fragment !== '') $normalized .= '#' . $fragment;

    return $normalized;
}

function alpenia_get_dashboard_url() {
    static $dashboard_url = null;
    if ($dashboard_url !== null) return $dashboard_url;

    global $post;
    if ($post && !empty($post->post_content) && has_shortcode($post->post_content, 'alpenia_dashboard')) {
        $current_url = get_permalink($post->ID);
        if ($current_url) {
            $dashboard_url = alpenia_normalize_url_to_current_host($current_url);
            return $dashboard_url;
        }
    }

    $pages = get_posts([
        'post_type'           => 'page',
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'suppress_filters'    => false,
        'ignore_sticky_posts' => true,
        'orderby'             => 'ID',
        'order'               => 'ASC',
    ]);

    foreach ((array) $pages as $page) {
        if (!empty($page->post_content) && has_shortcode($page->post_content, 'alpenia_dashboard')) {
            $url = get_permalink($page->ID);
            if ($url) {
                $dashboard_url = alpenia_normalize_url_to_current_host($url);
                return $dashboard_url;
            }
        }
    }

    $dashboard_url = alpenia_normalize_url_to_current_host(home_url('/dashboard/'));
    return $dashboard_url;
}

function alpenia_get_login_url() {
    static $login_url = null;
    if ($login_url !== null) return $login_url;

    global $post;
    if ($post && !empty($post->post_content) && has_shortcode($post->post_content, 'alpenia_login')) {
        $current_url = get_permalink($post->ID);
        if ($current_url) {
            $login_url = alpenia_normalize_url_to_current_host($current_url);
            return $login_url;
        }
    }

    $pages = get_posts([
        'post_type'           => 'page',
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'suppress_filters'    => false,
        'ignore_sticky_posts' => true,
        'orderby'             => 'ID',
        'order'               => 'ASC',
    ]);

    foreach ((array) $pages as $page) {
        if (!empty($page->post_content) && has_shortcode($page->post_content, 'alpenia_login')) {
            $url = get_permalink($page->ID);
            if ($url) {
                $login_url = alpenia_normalize_url_to_current_host($url);
                return $login_url;
            }
        }
    }

    $login_url = alpenia_normalize_url_to_current_host(home_url('/login/'));
    return $login_url;
}



function alpenia_travel_get_language() {
    $allowed = ['de', 'tr'];

    if (isset($_GET['ui_lang'])) {
        $lang = sanitize_key(wp_unslash($_GET['ui_lang']));
        if (in_array($lang, $allowed, true)) {
            if (is_user_logged_in()) {
                update_user_meta(get_current_user_id(), 'alpenia_travel_ui_lang', $lang);
            }

            if (!headers_sent()) {
                setcookie('alpenia_ui_lang', $lang, time() + MONTH_IN_SECONDS * 6, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
                $_COOKIE['alpenia_ui_lang'] = $lang;
            }

            return $lang;
        }
    }

    if (is_user_logged_in()) {
        $user_lang = sanitize_key((string) get_user_meta(get_current_user_id(), 'alpenia_travel_ui_lang', true));
        if (in_array($user_lang, $allowed, true)) {
            return $user_lang;
        }
    }

    if (isset($_COOKIE['alpenia_ui_lang'])) {
        $cookie_lang = sanitize_key(wp_unslash($_COOKIE['alpenia_ui_lang']));
        if (in_array($cookie_lang, $allowed, true)) {
            return $cookie_lang;
        }
    }

    return 'de';
}

function alpenia_travel_get_translations() {
    return [
        'tr' => [
            'Dashboard' => 'Kontrol Paneli','Umre 2026' => 'Umre 2026','Teilnehmerliste dieser Reise' => 'Bu seyahatin katılımcı listesi','Teilnehmer dieser Reise' => 'Bu seyahatin katılımcıları','CSV Export' => 'CSV Dışa Aktar','PDF Export' => 'PDF Dışa Aktar','PDF erstellen' => 'PDF oluştur','Reise löschen' => 'Seyahati sil','Zurück zum Dashboard' => 'Kontrol paneline dön','Logout' => 'Çıkış','Bearbeiten' => 'Düzenle','Löschen' => 'Sil','Speichern' => 'Kaydet','Abbrechen' => 'İptal','Neu' => 'Yeni','Offen' => 'Açık','Status' => 'Durum','Reisetyp' => 'Seyahat türü','Zeitraum' => 'Tarih aralığı','Freie Plätze' => 'Boş kontenjan','Ziel' => 'Hedef','Land' => 'Ülke','Stadt' => 'Şehir','Reiseleiter' => 'Seyahat rehberi','WhatsApp' => 'WhatsApp','Zoom' => 'Zoom','Anrede' => 'Hitap','Herr' => 'Bay','Frau' => 'Bayan','Name' => 'Ad','Vorname' => 'Ad','Nachname' => 'Soyad','Geburtsdatum' => 'Doğum tarihi','Geschlecht' => 'Cinsiyet','Staatsbürgerschaft' => 'Vatandaşlık','Nationalität' => 'Uyruk','Reisepass Nr.' => 'Pasaport No.','Reisepass' => 'Pasaport','Reisepass gültig von' => 'Pasaport başlangıç tarihi','Reisepass gültig bis' => 'Pasaport geçerlilik tarihi','Einreiseland-Visumstatus' => 'Giriş ülkesi vize durumu','Visumstatus' => 'Vize durumu','Visum' => 'Vize','Visum gültig von' => 'Vize başlangıç tarihi','Visum gültig bis' => 'Vize geçerlilik tarihi','Dokumente' => 'Belgeler','Foto' => 'Fotoğraf','Meldezettel' => 'İkamet kayıt belgesi','Optional' => 'İsteğe bağlı','Vorhanden' => 'Mevcut','Nicht vorhanden' => 'Mevcut değil','Bezahlt' => 'Ödendi','Nicht bezahlt' => 'Ödenmedi','Zahlung' => 'Ödeme','Betrag' => 'Tutar','Offen:' => 'Açık:','Bezahlt:' => 'Ödendi:','Zimmer' => 'Oda','Gruppe' => 'Grup','Zimmer / Gruppe' => 'Oda / Grup','Aktionen' => 'İşlemler','Unterlagen unvollständig' => 'Belgeler eksik','neu' => 'yeni','offen' => 'açık','bearbeiten' => 'düzenle','löschen' => 'sil','Bist du sicher?' => 'Emin misiniz?','Reise wirklich löschen?' => 'Seyahat gerçekten silinsin mi?','Teilnehmer wirklich löschen?' => 'Katılımcı gerçekten silinsin mi?','Entwurf' => 'Taslak','Voll' => 'Dolu','Abgeschlossen' => 'Tamamlandı','Unterlagen komplett' => 'Belgeler tamam','Unterlagen fehlen' => 'Belgeler eksik','Teilnehmer' => 'Katılımcı','Reisen' => 'Seyahatler','Fehlende Unterlagen' => 'Eksik belgeler','Offene Zahlungen' => 'Açık ödemeler','Reisen mit Teilnehmerliste' => 'Katılımcı listeli seyahatler','Filtern' => 'Filtrele','Zurücksetzen' => 'Sıfırla','Teilnehmer ansehen' => 'Katılımcıları görüntüle','Keine Reisen für diese Suche / Filter gefunden.' => 'Bu arama / filtre için seyahat bulunamadı.','Reise' => 'Seyahat','Aktion' => 'İşlem','Reise öffnen' => 'Seyahati aç','Teilnehmer öffnen' => 'Katılımcıyı aç','Aktuell keine fehlenden Unterlagen.' => 'Şu anda eksik belge yok.','Offener Betrag' => 'Açık tutar','Zahlungsstatus' => 'Ödeme durumu','Aktuell keine offenen Zahlungen.' => 'Şu anda açık ödeme yok.','Letzte Teilnehmer' => 'Son katılımcılar','Keine Reise' => 'Seyahat yok','Noch keine Teilnehmer vorhanden.' => 'Henüz katılımcı yok.'
        ],
    ];
}

function alpenia_travel_t($text) {
    $lang = alpenia_travel_get_language();
    $translations = alpenia_travel_get_translations();

    if ($lang === 'tr' && isset($translations['tr'][$text])) {
        return $translations['tr'][$text];
    }

    return $text;
}

function alpenia_get_ui_lang() { return alpenia_travel_get_language(); }

function alpenia_t($de, $tr) { return alpenia_travel_get_language() === 'tr' ? $tr : $de; }

function alpenia_dashboard_link($args = []) {
    global $post;
    if ($post && !empty($post->post_content) && has_shortcode($post->post_content, 'alpenia_dashboard')) {
        $base_url = get_permalink($post->ID);
    } else {
        $base_url = alpenia_get_dashboard_url();
    }
    $base_url = alpenia_normalize_url_to_current_host($base_url);

    if (empty($args) || !is_array($args)) {
        return $base_url;
    }

    return add_query_arg($args, $base_url);
}

/**
 * Länder / EU / Gender
 */
function alpenia_get_all_countries() {
    return [
        'Afghanistan','Ägypten','Albanien','Algerien','Andorra','Angola','Antigua und Barbuda','Äquatorialguinea','Argentinien',
        'Armenien','Aserbaidschan','Äthiopien','Australien','Bahamas','Bahrain','Bangladesch','Barbados','Belarus','Belgien',
        'Belize','Benin','Bhutan','Bolivien','Bosnien und Herzegowina','Botswana','Brasilien','Brunei','Bulgarien','Burkina Faso',
        'Burundi','Chile','China','Costa Rica','Dänemark','Deutschland','Dominica','Dominikanische Republik','Dschibuti','Ecuador',
        'El Salvador','Elfenbeinküste','Eritrea','Estland','Eswatini','Fidschi','Finnland','Frankreich','Gabun','Gambia','Georgien',
        'Ghana','Grenada','Griechenland','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Indien','Indonesien',
        'Irak','Iran','Irland','Island','Israel','Italien','Jamaika','Japan','Jemen','Jordanien','Kambodscha','Kamerun','Kanada',
        'Kap Verde','Kasachstan','Katar','Kenia','Kirgisistan','Kiribati','Kolumbien','Komoren','Kongo','Kosovo','Kroatien','Kuba',
        'Kuwait','Laos','Lesotho','Lettland','Libanon','Liberia','Libyen','Liechtenstein','Litauen','Luxemburg','Madagaskar','Malawi',
        'Malaysia','Malediven','Mali','Malta','Marokko','Marshallinseln','Mauretanien','Mauritius','Mexiko','Mikronesien','Moldau',
        'Monaco','Mongolei','Montenegro','Mosambik','Myanmar','Namibia','Nauru','Nepal','Neuseeland','Nicaragua','Niederlande',
        'Niger','Nigeria','Nordkorea','Nordmazedonien','Norwegen','Oman','Österreich','Pakistan','Palau','Panama',
        'Papua-Neuguinea','Paraguay','Peru','Philippinen','Polen','Portugal','Ruanda','Rumänien','Russland','Salomonen','Sambia',
        'Samoa','San Marino','São Tomé und Príncipe','Saudi-Arabien','Schweden','Schweiz','Senegal','Serbien','Seychellen',
        'Sierra Leone','Simbabwe','Singapur','Slowakei','Slowenien','Somalia','Spanien','Sri Lanka','St. Kitts und Nevis',
        'St. Lucia','St. Vincent und die Grenadinen','Südafrika','Sudan','Südkorea','Südsudan','Suriname','Syrien','Tadschikistan',
        'Taiwan','Tansania','Thailand','Timor-Leste','Togo','Tonga','Trinidad und Tobago','Tschad','Tschechien','Tunesien',
        'Türkei','Turkmenistan','Tuvalu','Uganda','Ukraine','Ungarn','Uruguay','Usbekistan','Vanuatu','Vatikanstadt','Venezuela',
        'Vereinigte Arabische Emirate','Vereinigte Staaten','Vereinigtes Königreich','Vietnam','Zentralafrikanische Republik','Zypern'
    ];
}

function alpenia_get_eu_countries() {
    return [
        'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich','Griechenland','Irland','Italien',
        'Kroatien','Lettland','Litauen','Luxemburg','Malta','Niederlande','Österreich','Polen','Portugal','Rumänien',
        'Schweden','Slowakei','Slowenien','Spanien','Tschechien','Ungarn','Zypern'
    ];
}

function alpenia_get_schengen_countries() {
    return [
        'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich','Griechenland','Island','Italien',
        'Kroatien','Lettland','Liechtenstein','Litauen','Luxemburg','Malta','Niederlande','Norwegen','Österreich','Polen',
        'Portugal','Rumänien','Schweden','Schweiz','Slowakei','Slowenien','Spanien','Tschechien','Ungarn'
    ];
}

function alpenia_is_eu_nationality($nationality) {
    $nationality = trim((string) $nationality);
    if ($nationality === '') return false;
    return in_array($nationality, alpenia_get_eu_countries(), true);
}

function alpenia_is_eu_or_schengen_nationality($nationality) {
    $nationality = trim((string) $nationality);
    if ($nationality === '') return false;

    return in_array($nationality, alpenia_get_eu_countries(), true)
        || in_array($nationality, alpenia_get_schengen_countries(), true);
}

function alpenia_gender_code($gender) {
    $gender = strtolower(trim((string) $gender));
    if ($gender === 'frau' || $gender === 'female' || $gender === 'f') return 'F';
    return 'M';
}
