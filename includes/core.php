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

function alpenia_user_can_create_trip() {
    return alpenia_is_admin_user()
        || alpenia_is_backoffice_user()
        || alpenia_has_role('superadmin')
        || alpenia_has_role('manager');
}

function alpenia_user_can_delete_trip($trip_id) {
    if (!$trip_id) return false;

    return alpenia_user_can_create_trip();
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



function alpenia_travel_set_language($lang) {
    $allowed = ['de', 'tr'];
    $lang = sanitize_key((string) $lang);
    if (!in_array($lang, $allowed, true)) {
        $lang = 'de';
    }

    $cookie_options = [
        'expires'  => 0,
        'path'     => defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
        'domain'   => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
        'secure'   => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    ];

    if (!headers_sent()) {
        setcookie('alpenia_travel_lang', $lang, $cookie_options);
        $_COOKIE['alpenia_travel_lang'] = $lang;
    }

    return $lang;
}

function alpenia_travel_get_language() {
    $allowed = ['de', 'tr'];

    if (isset($_GET['ui_lang'])) {
        return alpenia_travel_set_language(wp_unslash($_GET['ui_lang']));
    }

    if (isset($_COOKIE['alpenia_travel_lang'])) {
        $cookie_lang = sanitize_key(wp_unslash($_COOKIE['alpenia_travel_lang']));
        if (in_array($cookie_lang, $allowed, true)) {
            return $cookie_lang;
        }
    }

    return 'de';
}

function alpenia_travel_get_translations() {
    return [
        'tr' => [
            'Dashboard' => 'Kontrol Paneli','Umre 2026' => 'Umre 2026','Teilnehmerliste dieser Reise' => 'Bu seyahatin katılımcı listesi','Teilnehmer dieser Reise' => 'Bu seyahatin katılımcıları','CSV Export' => 'CSV Dışa Aktar','PDF Export' => 'PDF Dışa Aktar','PDF erstellen' => 'PDF oluştur','Reise löschen' => 'Seyahati sil','Zurück zum Dashboard' => 'Kontrol paneline dön','Logout' => 'Çıkış','Bearbeiten' => 'Düzenle','Löschen' => 'Sil','Speichern' => 'Kaydet','Abbrechen' => 'İptal','Neu' => 'Yeni','Offen' => 'Açık','Status' => 'Durum','Reisetyp' => 'Seyahat türü','Zeitraum' => 'Tarih aralığı','Freie Plätze' => 'Boş kontenjan','Ziel' => 'Hedef','Land' => 'Ülke','Stadt' => 'Şehir','Reiseleiter' => 'Seyahat rehberi','WhatsApp' => 'WhatsApp','Zoom' => 'Zoom','Anrede' => 'Hitap','Herr' => 'Bay','Frau' => 'Bayan','Name' => 'Ad','Vorname' => 'Ad','Nachname' => 'Soyad','Geburtsdatum' => 'Doğum tarihi','Geschlecht' => 'Cinsiyet','Staatsbürgerschaft' => 'Vatandaşlık','Nationalität' => 'Uyruk','Reisepass Nr.' => 'Pasaport No.','Reisepass' => 'Pasaport','Reisepass gültig von' => 'Pasaport başlangıç tarihi','Reisepass gültig bis' => 'Pasaport geçerlilik tarihi','Einreiseland-Visumstatus' => 'Giriş ülkesi vize durumu','Visumstatus' => 'Vize durumu','Visum' => 'Vize','Visum gültig von' => 'Vize başlangıç tarihi','Visum gültig bis' => 'Vize geçerlilik tarihi','Dokumente' => 'Belgeler','Foto' => 'Fotoğraf','Meldezettel' => 'İkamet kayıt belgesi','Optional' => 'İsteğe bağlı','Vorhanden' => 'Mevcut','Nicht vorhanden' => 'Mevcut değil','Bezahlt' => 'Ödendi','Nicht bezahlt' => 'Ödenmedi','Zahlung' => 'Ödeme','Betrag' => 'Tutar','Offen:' => 'Açık:','Bezahlt:' => 'Ödendi:','Zimmer' => 'Oda','Gruppe' => 'Grup','Zimmer / Gruppe' => 'Oda / Grup','Aktionen' => 'İşlemler','Unterlagen unvollständig' => 'Belgeler eksik','neu' => 'yeni','offen' => 'açık','bearbeiten' => 'düzenle','löschen' => 'sil','Bist du sicher?' => 'Emin misiniz?','Reise wirklich löschen?' => 'Seyahat gerçekten silinsin mi?','Teilnehmer wirklich löschen?' => 'Katılımcı gerçekten silinsin mi?','Entwurf' => 'Taslak','Voll' => 'Dolu','Abgeschlossen' => 'Tamamlandı','Unterlagen komplett' => 'Belgeler tamam','Unterlagen fehlen' => 'Belgeler eksik','Teilnehmer' => 'Katılımcı','Reisen' => 'Seyahatler','Fehlende Unterlagen' => 'Eksik belgeler','Offene Zahlungen' => 'Açık ödemeler','Reisen mit Teilnehmerliste' => 'Katılımcı listeli seyahatler','Filtern' => 'Filtrele','Zurücksetzen' => 'Sıfırla','Teilnehmer ansehen' => 'Katılımcıları görüntüle','Keine Reisen für diese Suche / Filter gefunden.' => 'Bu arama / filtre için seyahat bulunamadı.','Reise' => 'Seyahat','Aktion' => 'İşlem','Reise öffnen' => 'Seyahati aç','Teilnehmer öffnen' => 'Katılımcıyı aç','Aktuell keine fehlenden Unterlagen.' => 'Şu anda eksik belge yok.','Offener Betrag' => 'Açık tutar','Zahlungsstatus' => 'Ödeme durumu','Aktuell keine offenen Zahlungen.' => 'Şu anda açık ödeme yok.','Letzte Teilnehmer' => 'Son katılımcılar','Keine Reise' => 'Seyahat yok','Noch keine Teilnehmer vorhanden.' => 'Henüz katılımcı yok.','Plugin language switch' => 'Eklenti dil seçimi','Deutsch' => 'Almanca','Türkçe' => 'Türkçe','Bitte zuerst einloggen.' => 'Lütfen önce giriş yapın.','Zum Login' => 'Girişe dön','Kein Zugriff.' => 'Erişim yok.','Sicherheitsfehler. Bitte erneut versuchen.' => 'Güvenlik hatası. Lütfen tekrar deneyin.','Bitte alle Pflichtfelder ausfüllen.' => 'Lütfen tüm zorunlu alanları doldurun.','Reise erfolgreich erstellt.' => 'Seyahat başarıyla oluşturuldu.','Fehler beim Erstellen der Reise.' => 'Seyahat oluşturulurken hata oluştu.','Kein Zugriff zum Löschen dieser Reise.' => 'Bu seyahati silme yetkiniz yok.','Reise wurde gelöscht.' => 'Seyahat silindi.','Löschen nicht erlaubt.' => 'Silme izni yok.','Sicherheitsfehler beim Teilnehmerformular.' => 'Katılımcı formunda güvenlik hatası.','Ungültige Reise oder kein Zugriff.' => 'Geçersiz seyahat veya erişim yok.','Eine oder mehrere Dateien sind zu groß.' => 'Bir veya daha fazla dosya çok büyük.','Teilnehmer erfolgreich gespeichert.' => 'Katılımcılar başarıyla kaydedildi.','Teilnehmer wurde gelöscht.' => 'Katılımcı silindi.','Sicherheitsfehler beim Bearbeiten.' => 'Düzenleme sırasında güvenlik hatası.','Teilnehmer erfolgreich aktualisiert.' => 'Katılımcı başarıyla güncellendi.','Benutzer erfolgreich erstellt.' => 'Kullanıcı başarıyla oluşturuldu.','Benutzer gelöscht.' => 'Kullanıcı silindi.','Fehler beim Speichern.' => 'Kaydetme sırasında hata oluştu.','Benutzer erfolgreich aktualisiert.' => 'Kullanıcı başarıyla güncellendi.','Neue Reise erstellen' => 'Yeni seyahat oluştur','Erstelle hier eine neue Kultur- oder Pilgerreise.' => 'Buradan yeni bir kültür veya hac seyahati oluşturun.','Reisetitel' => 'Seyahat başlığı','z. B. Frankfurt – Umrah' => 'örn. Frankfurt – Umre','Bitte wählen' => 'Lütfen seçin','Reisestatus' => 'Seyahat durumu','Reiseziel' => 'Seyahat hedefi','z. B. Mekka & Medina' => 'örn. Mekke ve Medine','z. B. Deutschland' => 'örn. Almanya','z. B. Frankfurt' => 'örn. Frankfurt','Startdatum' => 'Başlangıç tarihi','Enddatum' => 'Bitiş tarihi','tt.mm.jjjj' => 'gg.aa.yyyy','Max. Teilnehmer' => 'Maks. katılımcı','z. B. 40' => 'örn. 40','Standardpreis (€)' => 'Standart fiyat (€)','z. B. 1499' => 'örn. 1499','WhatsApp Gruppenlink' => 'WhatsApp grup bağlantısı','Zoom Meeting Link' => 'Zoom toplantı bağlantısı','Interne Notizen' => 'Dahili notlar','Interne Hinweise zur Reise' => 'Seyahat için dahili notlar','Reise speichern' => 'Seyahati kaydet','Benutzerverwaltung' => 'Kullanıcı yönetimi','Reiseleiter und Backoffice verwalten' => 'Rehber ve backoffice yönetimi','Neuen Benutzer anlegen' => 'Yeni kullanıcı oluştur','E-Mail' => 'E-posta','Passwort' => 'Şifre','Rolle' => 'Rol','Benutzer erstellen' => 'Kullanıcı oluştur','Benutzerliste' => 'Kullanıcı listesi','Aktiv' => 'Aktif','Eigenes Account' => 'Kendi hesabı','Deaktivieren' => 'Devre dışı bırak','Teilnehmerdaten erfassen' => 'Katılımcı verilerini girin','Bitte alle Pflichtfelder pro Person ausfüllen.' => 'Lütfen her kişi için zorunlu alanları doldurun.','Teilnehmer 1' => 'Katılımcı 1','2. Vorname' => '2. ad','Telefonnummer' => 'Telefon numarası','E-Mail-Adresse' => 'E-posta adresi','Notfallkontakt Name' => 'Acil durum kişi adı','Notfallkontakt Telefonnummer' => 'Acil durum kişi telefonu','Bearbeitungsstatus' => 'İşlem durumu','Aufenthaltstitelstatus (Einreiseland)' => 'Oturum izni durumu (giriş ülkesi)','z. B. Saudi-Arabien beantragt' => 'örn. Suudi Arabistan başvuruldu','Gesamtpreis (€)' => 'Toplam fiyat (€)','Anzahlung (€)' => 'Ön ödeme (€)','Bereits bezahlt (€)' => 'Ödenen tutar (€)','Reisepass hochladen' => 'Pasaport yükle','Meldezettel hochladen' => 'İkamet belgesi yükle','Porträt Foto hochladen' => 'Portre fotoğraf yükle','max. 5 MB' => 'maks. 5 MB','Checkliste' => 'Kontrol listesi','Reisepass geprüft' => 'Pasaport kontrol edildi','Aufenthaltstitel geprüft' => 'Oturum izni kontrol edildi','Foto geprüft' => 'Fotoğraf kontrol edildi','Zahlung geprüft' => 'Ödeme kontrol edildi','Alle Teilnehmer speichern' => 'Tüm katılımcıları kaydet','Zurück' => 'Geri','Teilnehmer hinzufügen' => 'Katılımcı ekle','Wähle die Reise und gib an, wie viele Teilnehmer du erfassen willst.' => 'Seyahati seçin ve kaç katılımcı gireceğinizi belirtin.','Reise auswählen' => 'Seyahat seçin','Bitte Reise wählen' => 'Lütfen seyahat seçin','Anzahl Teilnehmer' => 'Katılımcı sayısı','Weiter' => 'Devam','Alpenia Travel Dashboard' => 'Alpenia Travel Kontrol Paneli','willkommen im Dashboard' => 'kontrol paneline hoş geldiniz','Teilnehmer anzeigen' => 'Katılımcıları göster','teilweise bezahlt' => 'kısmen ödendi','Teilweise bezahlt' => 'Kısmen ödendi','offen' => 'açık','aktiv' => 'aktif','Pflicht sind Geschlecht, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von, Reisepass gültig bis, Reisepass, Porträtfoto und die komplette Checkliste. Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind zusätzlich Aufenthaltstitel Nummer, Aufenthaltstitel gültig von, Aufenthaltstitel gültig bis und Aufenthaltstitel Pflicht.' => 'Zorunlu alanlar: hitap, ad, soyad, vatandaşlık, pasaport geçerlilik başlangıç/bitiş tarihi, pasaport, portre fotoğraf ve kontrol listesinin tamamı. AB/Schengen dışı vatandaşlar için ayrıca oturum izni numarası, oturum izni geçerlilik başlangıç/bitiş tarihi ve oturum izni zorunludur.','Bitte Herr/Frau, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von und Reisepass gültig bis ausfüllen.' => 'Lütfen Bay/Bayan, ad, soyad, vatandaşlık, pasaport geçerlilik başlangıç ve bitiş tarihini doldurun.','Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht.' => 'AB/Schengen dışı vatandaşlar için oturum izni numarası ile oturum izni geçerlilik başlangıç ve bitiş tarihi zorunludur.','Noch keine Teilnehmer für diese Reise vorhanden.' => 'Bu seyahat için henüz katılımcı bulunmuyor.','Aktive' => 'Aktif','Aktivieren' => 'Aktifleştir','Benutzer bearbeiten' => 'Kullanıcı düzenle','Neues Passwort (leer lassen = unverändert)' => 'Yeni şifre (boş bırak = değişmez)','Neues Passwort' => 'Yeni şifre','Änderungen speichern' => 'Değişiklikleri kaydet','Benutzer wirklich löschen?' => 'Kullanıcı gerçekten silinsin mi?','Benutzer wirklich deaktivieren?' => 'Kullanıcı gerçekten devre dışı bırakılsın mı?'
        ],
    ];
}

function alpenia_travel_t($text) {
    $lang = alpenia_travel_get_language();
    $translations = alpenia_travel_get_translations();
    $extra_tr = [
        'Fehlende Unterlagen im Überblick' => 'Eksik belgeler genel bakış',
        'Zum Dashboard' => 'Kontrol paneline git',
        'Ungültige Anmeldedaten.' => 'Geçersiz giriş bilgileri.',
        'Bitte E-Mail und Passwort eingeben.' => 'Lütfen e-posta ve şifre girin.',
        'Passwort vergessen' => 'Şifremi unuttum',
        'Passwort ändern' => 'Şifreyi değiştir',
        'Login' => 'Giriş',
        'Kulturreisen' => 'Kültür gezileri',
        'Willkommen zurück' => 'Tekrar hoş geldiniz',
        'Melde dich an, um dein Dashboard zu öffnen.' => 'Kontrol panelinizi açmak için giriş yapın.',
        'Reset-Link senden' => 'Sıfırlama bağlantısı gönder',
        'Zurück zum Login' => 'Girişe dön',
        'Neues Passwort (mind. 12 Zeichen)' => 'Yeni şifre (en az 12 karakter)',
        'Passwort speichern' => 'Şifreyi kaydet',
        'Passwort vergessen?' => 'Şifrenizi mi unuttunuz?',
        'Reisepassnummer' => 'Pasaport numarası',

        'E-Mail Adresse' => 'E-posta adresi',
        'Aufenthaltstitel Nummer' => 'Oturum izni numarası',
        'Nummer des Visums' => 'Vize numarası',
        'Aufenthaltstitel gültig von' => 'Oturum izni başlangıç tarihi',
        'Aufenthaltstitel gültig bis' => 'Oturum izni geçerlilik tarihi',
        'Aufenthaltstitel Bemerkung' => 'Oturum izni notu',
        'z. B. Einreise-Visum für Saudi-Arabien' => 'örn. Suudi Arabistan giriş vizesi',
        'In Prüfung' => 'İncelemede',
        'Vollständig' => 'Tamamlandı',
        'z. B. Saudi-Arabien: beantragt' => 'örn. Suudi Arabistan: başvuruldu',
        'Aufenthaltstitel hochladen' => 'Oturum izni yükle',
        'Teilnehmer bearbeiten' => 'Katılımcıyı düzenle',
        'Zurück zur Reise' => 'Seyahate dön',
        'Visum Nummer' => 'Vize numarası',
        'Visum Bemerkung' => 'Vize notu',
        'Visumstatus (Einreiseland)' => 'Vize durumu (giriş ülkesi)',
        'Untergruppe / Busgruppe' => 'Alt grup / otobüs grubu',
        'Neuen Reisepass hochladen' => 'Yeni pasaport yükle',
        'Neues Porträt Foto hochladen' => 'Yeni portre fotoğrafı yükle',
        'Neuen Aufenthaltstitel hochladen' => 'Yeni oturum izni yükle',
        'Neuen Meldezettel hochladen' => 'Yeni ikamet kayıt belgesi yükle',
        'Backoffice' => 'Backoffice',
        'Reise oder Ziel suchen' => 'Seyahat veya hedef ara',
        'Alle Reisearten' => 'Tüm seyahat türleri',
        'Alle Status' => 'Tüm durumlar',
        'Alle Länder' => 'Tüm ülkeler',
        'Alle Städte' => 'Tüm şehirler',
        'Alle Reiseleiter' => 'Tüm seyahat rehberleri',
        'Start' => 'Başlangıç',
        'Ende' => 'Bitiş',
        'Offene Zahlungen im Überblick' => 'Açık ödemeler genel bakış',
        'bezahlt' => 'ödendi',
        'nicht bezahlt' => 'ödenmedi',
        'Telefonnummer' => 'Telefon numarası',
        'Notfallkontakt Telefonnummer' => 'Acil durum iletişim telefonu',
        'Bearbeitungsstatus' => 'İşlem durumu',
        'Reisepass hochladen' => 'Pasaport yükle',
        'Porträt Foto hochladen' => 'Portre fotoğrafı yükle',
        'Meldezettel hochladen' => 'İkamet kayıt belgesi yükle',
        'Checkliste' => 'Kontrol listesi',
        'Reisepass geprüft' => 'Pasaport kontrol edildi',
        'Foto geprüft' => 'Fotoğraf kontrol edildi',
        'Aufenthaltstitel geprüft' => 'Oturum izni kontrol edildi',
        'Zahlung geprüft' => 'Ödeme kontrol edildi',
        'Alle Teilnehmer speichern' => 'Tüm katılımcıları kaydet',
        'Kulturreise' => 'Kültür seyahati',
        'Öffnen' => 'Aç',
        'Kein Zugriff auf diese Reise.' => 'Bu seyahate erişim yok.',
        'Bitte Name, E-Mail und Passwort ausfüllen.' => 'Lütfen ad, e-posta ve şifre alanlarını doldurun.',
        'Diese E-Mail existiert bereits.' => 'Bu e-posta zaten mevcut.',
        'Diese E-Mail wird bereits verwendet.' => 'Bu e-posta zaten kullanılıyor.',
        'Sicherheitsfehler beim Anlegen des Benutzers.' => 'Kullanıcı oluşturulurken güvenlik hatası.',
        'ist zu groß. Erlaubt sind maximal' => 'çok büyük. İzin verilen maksimum boyut',
        'Reisepass-Datei' => 'Pasaport dosyası',
        'Aufenthaltstitel' => 'Oturum izni',
        'Reisepass Datei' => 'Pasaport dosyası',
        'Foto Datei' => 'Fotoğraf dosyası',
        'Reisepass nicht geprüft' => 'Pasaport kontrol edilmedi',
        'Foto nicht geprüft' => 'Fotoğraf kontrol edilmedi',
        'Zahlung nicht geprüft' => 'Ödeme kontrol edilmedi',
        'Aufenthaltstitel nicht geprüft' => 'Oturum izni kontrol edilmedi',
        'Reiseleiter dürfen keine neuen Reisen erstellen. Bitte füge Teilnehmer zu bestehenden Reisen hinzu.' => 'Seyahat rehberleri yeni seyahat oluşturamaz. Lütfen mevcut seyahatlere katılımcı ekleyin.',
    ];

    if (isset($translations['tr']) && is_array($translations['tr'])) {
        $translations['tr'] = array_merge($translations['tr'], $extra_tr);
    }

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
