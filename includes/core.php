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

/**
 * Backward-compatible alias used by older dashboard templates.
 */
function alpenia_user_is_admin() {
    return alpenia_is_admin_user();
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

function alpenia_user_can_edit_trip($trip_id) {
    if (!$trip_id) return false;

    $trip = get_post($trip_id);
    if (!$trip || $trip->post_type !== 'group_trip') return false;

    return alpenia_user_can_create_trip() && alpenia_user_can_access_trip($trip_id);
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
            'Dashboard' => 'Kontrol Paneli','Umre 2026' => 'Umre 2026','Teilnehmerliste dieser Reise' => 'Bu seyahatin katılımcı listesi','Teilnehmer dieser Reise' => 'Bu seyahatin katılımcıları','CSV Export' => 'CSV Dışa Aktar','PDF Export' => 'PDF Dışa Aktar','PDF erstellen' => 'PDF oluştur','Reise löschen' => 'Seyahati sil','Zurück zum Dashboard' => 'Kontrol paneline dön','Logout' => 'Çıkış','Bearbeiten' => 'Düzenle','Löschen' => 'Sil','Speichern' => 'Kaydet','Abbrechen' => 'İptal','Neu' => 'Yeni','Offen' => 'Açık','Status' => 'Durum','Reisetyp' => 'Seyahat türü','Zeitraum' => 'Tarih aralığı','Freie Plätze' => 'Boş kontenjan','Ziel' => 'Hedef','Land' => 'Ülke','Stadt' => 'Şehir','Reiseleiter' => 'Seyahat rehberi','WhatsApp' => 'WhatsApp','Zoom' => 'Zoom','Anrede' => 'Hitap','Herr' => 'Bay','Frau' => 'Bayan','Name' => 'Ad','Vorname' => 'Ad','Nachname' => 'Soyad','Geburtsdatum' => 'Doğum tarihi','Geschlecht' => 'Cinsiyet','Staatsbürgerschaft' => 'Vatandaşlık','Nationalität' => 'Uyruk','Reisepass Nr.' => 'Pasaport No.','Reisepass' => 'Pasaport','Reisepass gültig von' => 'Pasaport başlangıç tarihi','Reisepass gültig bis' => 'Pasaport geçerlilik tarihi','Einreiseland-Visumstatus' => 'Giriş ülkesi vize durumu','Visumstatus' => 'Vize durumu','Visum' => 'Vize','Visum gültig von' => 'Vize başlangıç tarihi','Visum gültig bis' => 'Vize geçerlilik tarihi','Dokumente' => 'Belgeler','Foto' => 'Fotoğraf','Meldezettel' => 'İkamet kayıt belgesi','Optional' => 'İsteğe bağlı','Vorhanden' => 'Mevcut','Nicht vorhanden' => 'Mevcut değil','Bezahlt' => 'Ödendi','Nicht bezahlt' => 'Ödenmedi','Zahlung' => 'Ödeme','Betrag' => 'Tutar','Offen:' => 'Açık:','Bezahlt:' => 'Ödendi:','Zimmer' => 'Oda','Gruppe' => 'Grup','Zimmer / Gruppe' => 'Oda / Grup','Aktionen' => 'İşlemler','Unterlagen unvollständig' => 'Belgeler eksik','neu' => 'yeni','offen' => 'açık','bearbeiten' => 'düzenle','löschen' => 'sil','Bist du sicher?' => 'Emin misiniz?','Reise wirklich löschen?' => 'Seyahat gerçekten silinsin mi?','Teilnehmer wirklich löschen?' => 'Katılımcı gerçekten silinsin mi?','Entwurf' => 'Taslak','Voll' => 'Dolu','Abgeschlossen' => 'Tamamlandı','Unterlagen komplett' => 'Belgeler tamam','Unterlagen fehlen' => 'Belgeler eksik','Unterlage fehlt' => 'Belge eksik','Teilnehmer' => 'Katılımcı','Reisen' => 'Seyahatler','Fehlende Unterlagen' => 'Eksik belgeler','Offene Zahlungen' => 'Açık ödemeler','Reisen mit Teilnehmerliste' => 'Katılımcı listeli seyahatler','Filtern' => 'Filtrele','Zurücksetzen' => 'Sıfırla','Teilnehmer ansehen' => 'Katılımcıları görüntüle','Keine Reisen für diese Suche / Filter gefunden.' => 'Bu arama / filtre için seyahat bulunamadı.','Reise' => 'Seyahat','Aktion' => 'İşlem','Reise öffnen' => 'Seyahati aç','Teilnehmer öffnen' => 'Katılımcıyı aç','Aktuell keine fehlenden Unterlagen.' => 'Şu anda eksik belge yok.','Offener Betrag' => 'Açık tutar','Zahlungsstatus' => 'Ödeme durumu','Aktuell keine offenen Zahlungen.' => 'Şu anda açık ödeme yok.','Letzte Teilnehmer' => 'Son katılımcılar','Keine Reise' => 'Seyahat yok','Noch keine Teilnehmer vorhanden.' => 'Henüz katılımcı yok.','Plugin language switch' => 'Eklenti dil seçimi','Deutsch' => 'Almanca','Türkçe' => 'Türkçe','Bitte zuerst einloggen.' => 'Lütfen önce giriş yapın.','Zum Login' => 'Girişe dön','Kein Zugriff.' => 'Erişim yok.','Sicherheitsfehler. Bitte erneut versuchen.' => 'Güvenlik hatası. Lütfen tekrar deneyin.','Bitte alle Pflichtfelder ausfüllen.' => 'Lütfen tüm zorunlu alanları doldurun.','Reise erfolgreich erstellt.' => 'Seyahat başarıyla oluşturuldu.','Fehler beim Erstellen der Reise.' => 'Seyahat oluşturulurken hata oluştu.','Kein Zugriff zum Löschen dieser Reise.' => 'Bu seyahati silme yetkiniz yok.','Reise wurde gelöscht.' => 'Seyahat silindi.','Löschen nicht erlaubt.' => 'Silme izni yok.','Sicherheitsfehler beim Teilnehmerformular.' => 'Katılımcı formunda güvenlik hatası.','Ungültige Reise oder kein Zugriff.' => 'Geçersiz seyahat veya erişim yok.','Eine oder mehrere Dateien sind zu groß.' => 'Bir veya daha fazla dosya çok büyük.','Teilnehmer erfolgreich gespeichert.' => 'Katılımcılar başarıyla kaydedildi.','Teilnehmer wurde gelöscht.' => 'Katılımcı silindi.','Sicherheitsfehler beim Bearbeiten.' => 'Düzenleme sırasında güvenlik hatası.','Teilnehmer erfolgreich aktualisiert.' => 'Katılımcı başarıyla güncellendi.','Benutzer erfolgreich erstellt.' => 'Kullanıcı başarıyla oluşturuldu.','Benutzer gelöscht.' => 'Kullanıcı silindi.','Fehler beim Speichern.' => 'Kaydetme sırasında hata oluştu.','Benutzer erfolgreich aktualisiert.' => 'Kullanıcı başarıyla güncellendi.','Neue Reise erstellen' => 'Yeni seyahat oluştur','Erstelle hier eine neue Kultur- oder Pilgerreise.' => 'Buradan yeni bir kültür veya hac seyahati oluşturun.','Reisetitel' => 'Seyahat başlığı','z. B. Frankfurt – Umrah' => 'örn. Frankfurt – Umre','Bitte wählen' => 'Lütfen seçin','Reisestatus' => 'Seyahat durumu','Reiseziel' => 'Seyahat hedefi','z. B. Mekka & Medina' => 'örn. Mekke ve Medine','z. B. Deutschland' => 'örn. Almanya','z. B. Frankfurt' => 'örn. Frankfurt','Startdatum' => 'Başlangıç tarihi','Enddatum' => 'Bitiş tarihi','tt.mm.jjjj' => 'gg.aa.yyyy','Max. Teilnehmer' => 'Maks. katılımcı','z. B. 40' => 'örn. 40','Standardpreis (€)' => 'Standart fiyat (€)','z. B. 1499' => 'örn. 1499','WhatsApp Gruppenlink' => 'WhatsApp grup bağlantısı','Zoom Meeting Link' => 'Zoom toplantı bağlantısı','Interne Notizen' => 'Dahili notlar','Interne Hinweise zur Reise' => 'Seyahat için dahili notlar','Reise speichern' => 'Seyahati kaydet','Benutzerverwaltung' => 'Kullanıcı yönetimi','Reiseleiter und Backoffice verwalten' => 'Rehber ve backoffice yönetimi','Neuen Benutzer anlegen' => 'Yeni kullanıcı oluştur','E-Mail' => 'E-posta','Passwort' => 'Şifre','Rolle' => 'Rol','Benutzer erstellen' => 'Kullanıcı oluştur','Benutzerliste' => 'Kullanıcı listesi','Aktiv' => 'Aktif','Eigenes Account' => 'Kendi hesabı','Deaktivieren' => 'Devre dışı bırak','Teilnehmerdaten erfassen' => 'Katılımcı verilerini girin','Bitte alle Pflichtfelder pro Person ausfüllen.' => 'Lütfen her kişi için zorunlu alanları doldurun.','Teilnehmer 1' => 'Katılımcı 1','2. Vorname' => '2. ad','Telefonnummer' => 'Telefon numarası','E-Mail-Adresse' => 'E-posta adresi','Notfallkontakt Name' => 'Acil durum kişi adı','Notfallkontakt Telefonnummer' => 'Acil durum kişi telefonu','Bearbeitungsstatus' => 'İşlem durumu','Aufenthaltstitelstatus (Einreiseland)' => 'Oturum izni durumu (giriş ülkesi)','z. B. Saudi-Arabien beantragt' => 'örn. Suudi Arabistan başvuruldu','Gesamtpreis (€)' => 'Toplam fiyat (€)','Anzahlung (€)' => 'Ön ödeme (€)','Bereits bezahlt (€)' => 'Ödenen tutar (€)','Reisepass hochladen' => 'Pasaport yükle','Meldezettel hochladen' => 'İkamet belgesi yükle','Porträt Foto hochladen' => 'Portre fotoğraf yükle','max. 5 MB' => 'maks. 5 MB','Checkliste' => 'Kontrol listesi','Reisepass geprüft' => 'Pasaport kontrol edildi','Aufenthaltstitel geprüft' => 'Oturum izni kontrol edildi','Foto geprüft' => 'Fotoğraf kontrol edildi','Zahlung geprüft' => 'Ödeme kontrol edildi','Alle Teilnehmer speichern' => 'Tüm katılımcıları kaydet','Zurück' => 'Geri','Teilnehmer hinzufügen' => 'Katılımcı ekle','Wähle die Reise und gib an, wie viele Teilnehmer du erfassen willst.' => 'Seyahati seçin ve kaç katılımcı gireceğinizi belirtin.','Reise auswählen' => 'Seyahat seçin','Bitte Reise wählen' => 'Lütfen seyahat seçin','Anzahl Teilnehmer' => 'Katılımcı sayısı','Weiter' => 'Devam','Alpenia Travel Dashboard' => 'Alpenia Travel Kontrol Paneli','willkommen im Dashboard' => 'kontrol paneline hoş geldiniz','Teilnehmer anzeigen' => 'Katılımcıları göster','teilweise bezahlt' => 'kısmen ödendi','Teilweise bezahlt' => 'Kısmen ödendi','offen' => 'açık','aktiv' => 'aktif','Pflicht sind Geschlecht, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von, Reisepass gültig bis, Reisepass, Porträtfoto und die komplette Checkliste. Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind zusätzlich Aufenthaltstitel Nummer, Aufenthaltstitel gültig von, Aufenthaltstitel gültig bis und Aufenthaltstitel Pflicht.' => 'Zorunlu alanlar: hitap, ad, soyad, vatandaşlık, pasaport geçerlilik başlangıç/bitiş tarihi, pasaport, portre fotoğraf ve kontrol listesinin tamamı. AB/Schengen dışı vatandaşlar için ayrıca oturum izni numarası, oturum izni geçerlilik başlangıç/bitiş tarihi ve oturum izni zorunludur.','Bitte Herr/Frau, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von und Reisepass gültig bis ausfüllen.' => 'Lütfen Bay/Bayan, ad, soyad, vatandaşlık, pasaport geçerlilik başlangıç ve bitiş tarihini doldurun.','Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht.' => 'AB/Schengen dışı vatandaşlar için oturum izni numarası ile oturum izni geçerlilik başlangıç ve bitiş tarihi zorunludur.','Noch keine Teilnehmer für diese Reise vorhanden.' => 'Bu seyahat için henüz katılımcı bulunmuyor.','Aktive' => 'Aktif','Aktivieren' => 'Aktifleştir','Benutzer bearbeiten' => 'Kullanıcı düzenle','Neues Passwort (leer lassen = unverändert)' => 'Yeni şifre (boş bırak = değişmez)','Neues Passwort' => 'Yeni şifre','Änderungen speichern' => 'Değişiklikleri kaydet','Benutzer wirklich löschen?' => 'Kullanıcı gerçekten silinsin mi?','Benutzer wirklich deaktivieren?' => 'Kullanıcı gerçekten devre dışı bırakılsın mı?','Benutzer konnte nicht erstellt werden.' => 'Kullanıcı oluşturulamadı.','Upload konnte nicht verarbeitet werden.' => 'Yükleme işlenemedi.','Ungültiger Dateityp. Nur erlaubte Formate sind zulässig.' => 'Geçersiz dosya türü. Yalnızca izin verilen formatlar kullanılabilir.','Die Datei ist zu groß. Maximal erlaubt: %s' => 'Dosya çok büyük. İzin verilen en fazla boyut: %s','Datei konnte nicht registriert werden.' => 'Dosya kaydedilemedi.','Reise nicht gefunden.' => 'Seyahat bulunamadı.','MFA ist für privilegierte Konten erforderlich.' => 'Yetkili hesaplar için MFA gereklidir.','Benutzer wurde erstellt, aber die Rolle konnte nicht korrekt gesetzt werden.' => 'Kullanıcı oluşturuldu, ancak rol doğru şekilde atanamadı.','Sicherheitsfehler beim Deaktivieren des Benutzers.' => 'Kullanıcı devre dışı bırakılırken güvenlik hatası oluştu.','Benutzer deaktiviert.' => 'Kullanıcı devre dışı bırakıldı.','Sicherheitsfehler beim Aktivieren des Benutzers.' => 'Kullanıcı etkinleştirilirken güvenlik hatası oluştu.','Benutzer aktiviert.' => 'Kullanıcı etkinleştirildi.','Sicherheitsfehler beim Löschen des Benutzers.' => 'Kullanıcı silinirken güvenlik hatası oluştu.','Sicherheitsfehler beim Bearbeiten des Benutzers.' => 'Kullanıcı düzenlenirken güvenlik hatası oluştu.','Reisepass Datei' => 'Pasaport dosyası','Foto Datei' => 'Fotoğraf dosyası','Reisepass nicht geprüft' => 'Pasaport kontrol edilmedi','Foto nicht geprüft' => 'Fotoğraf kontrol edilmedi','Zahlung nicht geprüft' => 'Ödeme kontrol edilmedi','Aufenthaltstitel Nummer' => 'Oturum izni numarası','Aufenthaltstitel gültig von' => 'Oturum izni başlangıç tarihi','Aufenthaltstitel gültig bis' => 'Oturum izni geçerlilik tarihi','Aufenthaltstitel nicht geprüft' => 'Oturum izni kontrol edilmedi'
        ],
    ];
}

function alpenia_travel_t($text) {
    $lang = alpenia_travel_get_language();
    $translations = alpenia_travel_get_translations();
    $extra_tr = [
        'Startseite' => 'Ana sayfa',
        'Übersicht' => 'Genel bakış',
        'Reise bearbeiten' => 'Seyahati düzenle',
        'Öffentlicher Zugang' => 'Herkese açık erişim',
        'Öffentliche Anmeldung' => 'Herkese açık kayıt',
        'Nur mit Link' => 'Yalnızca bağlantıyla',
        '%1$d von %2$d Teilnehmern angezeigt' => '%2$d katılımcıdan %1$d tanesi gösteriliyor',
        'Anmeldeformular' => 'Kayıt formu',
        'Fehlende Unterlagen im Überblick' => 'Eksik belgeler genel bakışı',
        'Offene Dokumentaufgaben' => 'Eksik belgeler',
        'Nachfassen' => 'Takip',
        'Fehlende Unterlagen im Blick' => 'Eksik belgeler',
        'Offene Zahlungen im Blick' => 'Bekleyen ödemeler',
        'Unterlage' => 'belge',
        'Unterlagen' => 'belge',
        'Zum Teilnehmer' => 'Katılımcıya git',
        '%d Unterlage fehlt' => '%d belge eksik',
        '%d Unterlagen fehlen' => '%d belge eksik',
        '%1$s · %2$s fehlen' => '%1$s · %2$s eksik',
        '%1$s · %2$s offen' => '%1$s · %2$s bekliyor',
        '%1$s von %2$s sichtbar. Öffne den Teilnehmer direkt über die Karte.' => '%2$s içinden %1$s gösteriliyor. Katılımcıya doğrudan kart üzerinden gidin.',
        '%1$d Teilnehmer mit %2$d fehlenden Unterlagen' => '%1$d katılımcıda %2$d belge eksik',
        '+%d weitere' => '+%d diğer',
        'Es werden die wichtigsten %1$d von %2$d Teilnehmern angezeigt.' => 'En önemli %1$d / %2$d katılımcı gösteriliyor.',
        '%d Fehlende Unterlagen' => '%d eksik belge',
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
        'Bereits bezahlt' => 'Ödenen tutar',
        'Reisepassnummer' => 'Pasaport numarası',
        'Anmeldung für' => 'Kayıt',
        'Prüfe bitte vor dem Absenden die wichtigsten Reisedetails und halte die benötigten Dokumente bereit.' => 'Göndermeden önce seyahat detaylarını hızlıca doğrulayın; zorunlu belgelerin güncel ve okunaklı olduğundan emin olun.',

        'E-Mail Adresse' => 'E-posta adresi',
        'Aufenthaltstitel Nummer' => 'Oturum izni numarası',
        'Nummer des Visums' => 'Vize numarası',
        'Aufenthaltstitel gültig von' => 'Oturum izni başlangıç tarihi',
        'Aufenthaltstitel gültig bis' => 'Oturum izni geçerlilik tarihi',
        'z. B. Einreise-Visum für Saudi-Arabien' => 'örn. Suudi Arabistan giriş vizesi',
        'In Prüfung' => 'İncelemede',
        'Vollständig' => 'Tamamlandı',
        'z. B. Saudi-Arabien: beantragt' => 'örn. Suudi Arabistan: başvuruldu',
        'Aufenthaltstitel hochladen' => 'Oturum izni yükle',
        'Teilnehmer bearbeiten' => 'Katılımcıyı düzenle',
        'Zurück zur Reise' => 'Seyahate dön',
        'Visum Nummer' => 'Vize numarası',
        'Visumstatus (Einreiseland)' => 'Vize durumu (giriş ülkesi)',
        'Untergruppe / Busgruppe' => 'Alt grup / otobüs grubu',
        'Neuen Reisepass hochladen' => 'Yeni pasaport yükle',
        'Neues Porträt Foto hochladen' => 'Yeni portre fotoğrafı yükle',
        'Neuen Aufenthaltstitel hochladen' => 'Yeni oturum izni yükle',
        'Neuen Meldezettel hochladen' => 'Yeni ikamet kayıt belgesi yükle',
        'Backoffice' => 'Backoffice',
        'Reise oder Ziel suchen' => 'Seyahat veya hedef ara',
        'Teilnehmer, Passnummer oder E-Mail suchen' => 'Katılımcı, pasaport numarası veya e-posta ara',
        'Alle Reisearten' => 'Tüm seyahat türleri',
        'Alle Dokumente' => 'Tüm belgeler',
        'Alle Unterlagen' => 'Tüm belgeler',
        'Alle Zahlungen' => 'Tüm ödemeler',
        'Alle Bearbeitungsstatus' => 'Tüm işlem durumları',
        'Alle Status' => 'Tüm durumlar',
        'Alle Länder' => 'Tüm ülkeler',
        'Alle Städte' => 'Tüm şehirler',
        'Alle Reiseleiter' => 'Tüm seyahat rehberleri',
        'pro Seite' => 'sayfa başına',
        '%1$d Reisen gefunden – Seite %2$d von %3$d' => '%1$d seyahat bulundu – Sayfa %2$d / %3$d',
        'Start' => 'Başlangıç',
        'Ende' => 'Bitiş',
        'Offene Zahlungen im Überblick' => 'Açık ödemeler genel bakışı',
        'Offene Zahlungsaufgaben' => 'Bekleyen ödemeler',
        '%1$d Teilnehmer mit %2$s offen' => '%1$d katılımcıda %2$s bekliyor',
        'Info anzeigen' => 'Bilgileri göster','Info ausblenden' => 'Bilgileri gizle',
        'Dashboard Übersichten' => 'Kontrol paneli özetleri',
        'Übersicht' => 'Genel bakış',
        'Planung' => 'Planlama',
        'Erfassung' => 'Kayıt',
        'Team' => 'Ekip',
        'Zahlungen' => 'Ödemeler',
        'Aktivität' => 'Aktivite',
        'bezahlt' => 'ödendi',
        'nicht bezahlt' => 'ödenmedi',
        'Telefonnummer' => 'Telefon numarası',
        'Notfallkontakt' => 'Acil durum iletişim kişisi',
        'Notfallkontakt Telefon' => 'Acil durum iletişim telefonu',
        'Straße' => 'Sokak / Cadde',
        'PLZ / Ort' => 'Posta Kodu / Şehir',
        'Für die Anmeldung erforderlich' => 'Kayıt için gerekli',
        'Vollständige Kontaktdaten und Notfallkontakt' => 'Eksiksiz iletişim bilgileri ve acil durum iletişim bilgisi',
        'Gültige Reisepassdaten mit gut lesbarer Datei' => 'Geçerli pasaport bilgileri ve okunaklı dosya',
        'Porträtfoto und ggf. Aufenthaltstitel' => 'Portre fotoğrafı ve varsa oturum izni',
        'Reisepassdaten' => 'Pasaport bilgileri',
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
        'Deine Sitzung ist wegen Inaktivität abgelaufen. Bitte erneut einloggen.' => 'Oturumunuz işlem yapılmadığı için sona erdi. Lütfen tekrar giriş yapın.',
        'Dein Benutzerkonto wurde deaktiviert. Bitte den Support kontaktieren.' => 'Kullanıcı hesabınız devre dışı bırakıldı. Lütfen destek ile iletişime geçin.',
        'Dieser Benutzer wurde deaktiviert.' => 'Bu kullanıcı devre dışı bırakıldı.',
        'Anmeldung momentan nicht möglich. Bitte Support kontaktieren.' => 'Şu anda giriş yapılamıyor. Lütfen destek ile iletişime geçin.',
        'Wenn die E-Mail existiert, wurde ein Link zum Zurücksetzen gesendet.' => 'E-posta mevcutsa, şifre sıfırlama bağlantısı gönderildi.',
        'Reset-Link ungültig oder abgelaufen.' => 'Sıfırlama bağlantısı geçersiz veya süresi dolmuş.',
        'Passwort erfolgreich geändert. Bitte einloggen.' => 'Şifre başarıyla değiştirildi. Lütfen giriş yapın.',
        'Sicherheitsfehler beim Login. Bitte Seite neu laden und erneut versuchen.' => 'Giriş sırasında güvenlik hatası. Lütfen sayfayı yenileyip tekrar deneyin.',
        'Zu viele Fehlversuche. Bitte in %d Minute(n) erneut versuchen.' => 'Çok fazla hatalı deneme. Lütfen %d dakika sonra tekrar deneyin.',

        'Reisepass gültig bis' => 'Pasaport bitiş tarihi',
        'Aufenthaltstitel gültig bis' => 'Oturum izni bitiş tarihi',
        'Vize giriş ülkesi' => 'Vize giriş ülkesi',
        'Visum-Einreiseland' => 'Vize giriş ülkesi',
        'z. B. Saudi-Arabien' => 'Örn. Suudi Arabistan',
        'Vize numarası' => 'Vize numarası',
        'Visum Nummer' => 'Vize numarası',
        'Vize bitiş tarihi' => 'Vize bitiş tarihi',
        'Visum gültig bis' => 'Vize bitiş tarihi',
        'Vize Bilgileri' => 'Vize Bilgileri',
        'Lütfen bu alanı doldurun.' => 'Lütfen bu alanı doldurun.',
        'Lütfen geçerli bir tarih girin.' => 'Lütfen geçerli bir tarih girin.',
        'Lütfen geçerli bir e-posta adresi girin.' => 'Lütfen geçerli bir e-posta adresi girin.',
        'Lütfen geçerli bir telefon numarası girin.' => 'Lütfen geçerli bir telefon numarası girin.',
        'Lütfen geçerli bir değer girin.' => 'Lütfen geçerli bir değer girin.',
        'Lütfen geçerli bir dosya yükleyin.' => 'Lütfen geçerli bir dosya yükleyin.',
        'Bei Umrah-/Hajj-Reisen sind Vize-Einreiseland, Vize Nummer und Vize gültig bis Pflicht.' => 'Umre/Hac seyahatlerinde vize giriş ülkesi, vize numarası ve vize bitiş tarihi zorunludur.',
        'Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht.' => 'AB/Schengen dışı vatandaşlar için oturum izni numarası, oturum izni başlangıç tarihi ve oturum izni bitiş tarihi zorunludur.',
        'Bitte Herr/Frau, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von und Reisepass gültig bis ausfüllen.' => 'Lütfen hitap, ad, soyad, vatandaşlık, pasaport başlangıç tarihi ve pasaport bitiş tarihi alanlarını doldurun.',
        'Dieses Feld ist erforderlich.' => 'Lütfen bu alanı doldurun.',
        'Bitte eine gültige E-Mail-Adresse eingeben.' => 'Lütfen geçerli bir e-posta adresi girin.',
        'Bitte einen gültigen Wert eingeben.' => 'Lütfen geçerli bir değer girin.',
        'Bitte ein gültiges Datum eingeben.' => 'Lütfen geçerli bir tarih girin.',
        'Bitte eine gültige Telefonnummer eingeben.' => 'Lütfen geçerli bir telefon numarası girin.',
        'Bitte eine gültige Datei hochladen.' => 'Lütfen geçerli bir dosya yükleyin.',
        'PDF / Drucken' => 'PDF / Yazdır',
        'Unterlagen teilweise' => 'Belgeler kısmen tamam',
        'Pflicht sind Anrede, Vorname, Nachname, Staatsbürgerschaft, Reisepass gültig von, Reisepass gültig bis, Reisepass, Porträtfoto und die komplette Checkliste. Bei Nicht-EU-/Nicht-Schengen-Staatsbürgern sind zusätzlich Aufenthaltstitel Nummer, Aufenthaltstitel gültig von und Aufenthaltstitel gültig bis Pflicht. Bei Umrah-/Hajj-Reisen sind zusätzlich Vize-Einreiseland, Vize Nummer und Vize gültig bis Pflicht.' => 'Zorunlu alanlar: hitap, ad, soyad, vatandaşlık, pasaport başlangıç tarihi, pasaport bitiş tarihi, pasaport, portre fotoğrafı ve kontrol listesinin tamamı. AB/Schengen dışı vatandaşlar için ayrıca oturum izni numarası, oturum izni başlangıç tarihi ve oturum izni bitiş tarihi zorunludur. Umre/Hac seyahatlerinde ayrıca vize giriş ülkesi, vize numarası ve vize bitiş tarihi zorunludur.',
        'Bitte verwenden Sie den individuellen Anmeldelink Ihrer Reise.' => 'Lütfen seyahatinize ait bireysel kayıt bağlantısını kullanın.',
        'Dieser Anmeldelink ist ungültig oder die Reise ist nicht öffentlich anmeldbar.' => 'Bu kayıt bağlantısı geçersiz veya seyahat herkese açık kayda uygun değil.',
        'Teile ausschließlich diesen individuellen Anmeldelink mit Teilnehmern. Das öffentliche Formular ist ohne gültigen Link nicht zugänglich.' => 'Katılımcılarla yalnızca bu bireysel kayıt bağlantısını paylaşın. Herkese açık form geçerli bağlantı olmadan erişilebilir değildir.',
        'Individueller Anmeldelink' => 'Bireysel kayıt bağlantısı',
        'Abflug' => 'Kalkış',
        'Land / Stadt' => 'Ülke / Şehir',
        'Reiseart' => 'Seyahat türü',
        'Abflugstadt' => 'Kalkış şehri',
        'Flughafen' => 'Havalimanı',
        'Reisezeitraum' => 'Seyahat tarihleri',
        'z. B. Wien' => 'örn. Frankfurt',
        'z. B. Vienna International Airport' => 'örn. Frankfurt Havalimanı',
        'Formular öffnen' => 'Kayıt formunu aç',
        'Link kopieren' => 'Bağlantıyı kopyala',
        'Link kopiert' => 'Bağlantı kopyalandı',
        'Link konnte nicht automatisch kopiert werden. Bitte den Link markieren und manuell kopieren.' => 'Bağlantı otomatik kopyalanamadı. Lütfen bağlantıyı seçip elle kopyalayın.',
        'Schrittweise Erfassung' => 'Adım adım kayıt',
        'Teilnehmer einzeln bearbeiten' => 'Katılımcıları tek tek düzenle',
        'Damit nichts durcheinandergerät, wird immer nur eine Person geöffnet. Die Übersicht zeigt dir, bei welchem Teilnehmer du gerade bist.' => 'Karışıklık olmaması için aynı anda yalnızca bir kişi açık olur. Genel bakış hangi katılımcıda olduğunuzu gösterir.',
        'Teilnehmer auswählen' => 'Katılımcı seç',
        'Person %1$d von %2$d' => '%2$d kişiden %1$d. kişi',
        'Bitte zuerst diese Person fertig ausfüllen, dann zur nächsten Person wechseln.' => 'Lütfen önce bu kişiyi tamamen doldurun, sonra sonraki kişiye geçin.',
        'Vorheriger Teilnehmer' => 'Önceki katılımcı',
        'Nächster Teilnehmer' => 'Sonraki katılımcı',
        'Alle Teilnehmer prüfen und speichern' => 'Tüm katılımcıları kontrol et ve kaydet',
        'Du kannst jederzeit über die nummerierten Reiter zwischen den Teilnehmern wechseln.' => 'Numaralı sekmelerle katılımcılar arasında istediğiniz zaman geçiş yapabilirsiniz.',
        'Formularsprache wechseln' => 'Form dilini değiştir',
        'Sprache' => 'Dil',
        'Alpenia Group Trips' => 'Alpenia Grup Seyahatleri',
        'Bitte fülle deine persönlichen Daten vollständig aus. Deine Angaben werden sicher als Teilnehmerdatensatz gespeichert.' => 'Lütfen kişisel bilgilerinizi eksiksiz doldurun. Bilgileriniz güvenli şekilde katılımcı kaydı olarak saklanır.',
        'Mit * markierte Felder sind Pflichtfelder.' => '* ile işaretli alanlar zorunludur.',
        'Daten absenden' => 'Bilgileri gönder',
        'Adresse & Kontakt' => 'Adres ve iletişim',
        'Persönliche Daten' => 'Kişisel bilgiler',
        'Reisedokumente' => 'Seyahat belgeleri',
        'Bestätigungen' => 'Onaylar',
        'Zum Schutz deiner Daten benötigen wir vor dem Absenden zwei Zustimmungen.' => 'Verilerinizi korumak için göndermeden önce iki onaya ihtiyacımız var.',
        'Bitte bestätige vor dem Absenden die folgenden Pflichtzustimmungen:' => 'Lütfen göndermeden önce aşağıdaki zorunlu onayları verin:',
        'Datenschutzerklärung akzeptieren' => 'Gizlilik politikasını kabul et',
        'Ich habe die Datenschutzerklärung gelesen und bin mit der Verarbeitung meiner Daten zur Reiseanmeldung einverstanden.' => 'Gizlilik politikasını okudum ve seyahat kaydı için verilerimin işlenmesini kabul ediyorum.',
        'Echtheit der Daten bestätigen' => 'Bilgilerin doğruluğunu onayla',
        'Ich bestätige, dass alle Angaben wahrheitsgemäß, vollständig und anhand meiner gültigen Reisedokumente eingetragen wurden.' => 'Tüm bilgilerin doğru, eksiksiz ve geçerli seyahat belgelerime göre girildiğini onaylıyorum.',
        'Bitte lade gut lesbare Dateien in den angegebenen Formaten hoch.' => 'Lütfen belirtilen formatlarda okunaklı dosyalar yükleyin.',
        'Porträtfoto hochladen' => 'Portre fotoğrafı yükle',
        'Vielen Dank. Deine Teilnehmerdaten wurden erfolgreich übermittelt.' => 'Teşekkürler. Katılımcı bilgileriniz başarıyla gönderildi.',
        'Bitte bestätige die Datenschutzerklärung und die Echtheit deiner Angaben.' => 'Lütfen gizlilik politikasını ve bilgilerinizin doğruluğunu onaylayın.',
        'Das Formular konnte nicht gesendet werden.' => 'Form gönderilemedi.',
        'Firmensteuerung' => 'İş yönetimi',
        'Wachstum, Finanzen & Operations' => 'BÜYÜME, FİNANS VE OPERASYONLAR',
        'Kompakte Kennzahlen für Skalierung, Zahlungsfokus und Backoffice-Prioritäten.' => 'ÖLÇEKLEME, ÖDEME ODAĞI VE BACKOFFICE ÖNCELİKLERİ İÇİN KOMPAKT METRİKLER.',
        'Geplanter Umsatz' => 'Planlanan ciro',
        'Noch offen' => 'Hâlâ açık',
        'Auslastung' => 'Doluluk oranı',
        'Anmeldungen aktueller Monat' => 'Bu ayki kayıtlar',
        'Umsatz aktueller Monat' => 'Bu ayki ciro',
        '%+d%% zum Vormonat' => 'Önceki aya göre %+d%%',
        '%1$d von %2$d Plätzen' => '%2$d yerden %1$d dolu',
        'Top Reisearten' => 'En iyi seyahat türleri',
        '%1$d Teilnehmer · %2$d Reisen' => '%1$d katılımcı · %2$d seyahat',
        'Priorisierte Zahlungen' => 'Öncelikli ödemeler',
        'Backoffice-Fokus' => 'Backoffice odağı',
        '%1$d Unterlagen · %2$d Zahlungen · %3$d in Prüfung' => '%1$d belge · %2$d ödeme · %3$d incelemede',
        'Marketing-Fokus' => 'Pazarlama odağı',
        '%1$d%% Auslastung · %2$d/%3$d Plätze' => '%1$d%% doluluk · %2$d/%3$d yer',
        'Push' => 'Destekle',
        'Keine Teilnehmer für diese Suche / Filter gefunden.' => 'Bu arama / filtre için katılımcı bulunamadı.',
        'Noch keine Daten vorhanden.' => 'Henüz veri yok.',
        'Aktuell keine priorisierten Aufgaben.' => 'Şu anda öncelikli görev yok.',
        'Keine offenen Reisen mit niedriger Auslastung.' => 'Düşük doluluklu açık seyahat yok.',
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


function alpenia_display_value($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

function alpenia_format_date_display($date) {
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }

    $date_time = DateTime::createFromFormat('!Y-m-d', $date);
    if ($date_time instanceof DateTime && $date_time->format('Y-m-d') === $date) {
        return $date_time->format('d.m.Y');
    }

    return $date;
}

function alpenia_date_range_display($start, $end) {
    $start = trim((string) $start);
    $end = trim((string) $end);

    if ($start === '' && $end === '') {
        return '-';
    }

    return alpenia_format_date_display($start) . ' - ' . alpenia_format_date_display($end);
}

function alpenia_travel_pdf_label($key) {
    $labels = [
        'trip_participant_list' => 'Participant list for this trip',
        'create_pdf' => 'Create PDF',
        'trip_type' => 'Trip type',
        'status' => 'Status',
        'destination' => 'Destination',
        'country' => 'Country',
        'city' => 'City',
        'departure_city' => 'Departure city',
        'airport' => 'Airport',
        'travel_dates' => 'Travel dates',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'birth_date' => 'Date of birth',
        'gender' => 'Gender',
        'nationality' => 'Nationality',
        'passport_number' => 'Passport number',
        'passport_issue_date' => 'Passport valid from',
        'passport_expiry_date' => 'Passport valid until',
        'visa_information' => 'Visa information',
        'visa_entry_country' => 'Visa entry country',
        'visa_number' => 'Visa number',
        'visa_expiry_date' => 'Visa valid until',
        'no_participants' => 'No participants yet.',
    ];

    return $labels[$key] ?? alpenia_travel_t($key);
}

/**
 * Länder / EU / Gender
 */

function alpenia_is_pilgrimage_trip($trip_id) {
    $trip_type = strtolower((string) get_post_meta((int) $trip_id, 'trip_type', true));
    return in_array($trip_type, ['umrah', 'hajj'], true);
}

function alpenia_get_visa_entry_country($participant_id) {
    $visa_entry_country = trim((string) alpenia_get_secure_meta($participant_id, 'visa_entry_country', true));
    if ($visa_entry_country !== '') {
        return $visa_entry_country;
    }

    return trim((string) get_post_meta($participant_id, 'visa_status', true));
}

function alpenia_get_country_turkish_aliases() {
    return [
        'Deutschland' => 'Almanya','Österreich' => 'Avusturya','Schweiz' => 'İsviçre','Niederlande' => 'Hollanda',
        'Vereinigte Staaten' => 'Amerika Birleşik Devletleri','Vereinigtes Königreich' => 'Birleşik Krallık','Griechenland' => 'Yunanistan',
        'Spanien' => 'İspanya','Tschechien' => 'Çekya','Rumänien' => 'Romanya','Bulgarien' => 'Bulgaristan','Ungarn' => 'Macaristan',
        'Kroatien' => 'Hırvatistan','Südafrika' => 'Güney Afrika','Südkorea' => 'Güney Kore','Nordkorea' => 'Kuzey Kore',
        'Saudi-Arabien' => 'Suudi Arabistan','Vereinigte Arabische Emirate' => 'Birleşik Arap Emirlikleri','Elfenbeinküste' => 'Fildişi Sahili',
        'Weißrussland' => 'Belarus','Bosnien und Herzegowina' => 'Bosna Hersek','Türkei' => 'Türkiye','Ägypten' => 'Mısır',
        'Schweden' => 'İsveç','Norwegen' => 'Norveç','Dänemark' => 'Danimarka','Finnland' => 'Finlandiya','Frankreich' => 'Fransa',
        'Italien' => 'İtalya','Polen' => 'Polonya','Portugal' => 'Portekiz','Belgien' => 'Belçika','Irland' => 'İrlanda'
    ];
}


function alpenia_get_country_english_aliases() {
    return [
        'Afghanistan' => 'Afghanistan','Aegypt' => 'Egypt','Ägypten' => 'Egypt','Almanya' => 'Germany','Deutschland' => 'Germany',
        'Avusturya' => 'Austria','Österreich' => 'Austria','İsviçre' => 'Switzerland','Schweiz' => 'Switzerland','Hollanda' => 'Netherlands','Niederlande' => 'Netherlands',
        'Amerika Birleşik Devletleri' => 'United States','Vereinigte Staaten' => 'United States','Birleşik Krallık' => 'United Kingdom','İngiltere' => 'United Kingdom','Vereinigtes Königreich' => 'United Kingdom',
        'Yunanistan' => 'Greece','Griechenland' => 'Greece','İspanya' => 'Spain','Spanien' => 'Spain','Çekya' => 'Czechia','Tschechien' => 'Czechia',
        'Romanya' => 'Romania','Rumänien' => 'Romania','Bulgaristan' => 'Bulgaria','Bulgarien' => 'Bulgaria','Macaristan' => 'Hungary','Ungarn' => 'Hungary',
        'Hırvatistan' => 'Croatia','Kroatien' => 'Croatia','Güney Afrika' => 'South Africa','Südafrika' => 'South Africa','Güney Kore' => 'South Korea','Südkorea' => 'South Korea',
        'Kuzey Kore' => 'North Korea','Nordkorea' => 'North Korea','Suudi Arabistan' => 'Saudi Arabia','Saudi-Arabien' => 'Saudi Arabia',
        'Birleşik Arap Emirlikleri' => 'United Arab Emirates','Vereinigte Arabische Emirate' => 'United Arab Emirates','Fildişi Sahili' => "Côte d'Ivoire",'Elfenbeinküste' => "Côte d'Ivoire",
        'Belarus' => 'Belarus','Weißrussland' => 'Belarus','Bosna Hersek' => 'Bosnia and Herzegovina','Bosnien und Herzegowina' => 'Bosnia and Herzegovina',
        'Türkiye' => 'Turkey','Türkei' => 'Turkey','Mısır' => 'Egypt','İsveç' => 'Sweden','Schweden' => 'Sweden','Norveç' => 'Norway','Norwegen' => 'Norway',
        'Danimarka' => 'Denmark','Dänemark' => 'Denmark','Finlandiya' => 'Finland','Finnland' => 'Finland','Fransa' => 'France','Frankreich' => 'France',
        'İtalya' => 'Italy','Italien' => 'Italy','Polonya' => 'Poland','Polen' => 'Poland','Portekiz' => 'Portugal','Portugal' => 'Portugal',
        'Belçika' => 'Belgium','Belgien' => 'Belgium','İrlanda' => 'Ireland','Irland' => 'Ireland'
    ];
}

function alpenia_country_to_english($country) {
    $country = trim((string) $country);
    if ($country === '') {
        return '';
    }

    $aliases = alpenia_get_country_english_aliases();
    if (isset($aliases[$country])) {
        return $aliases[$country];
    }

    $country_folded = function_exists('mb_strtolower') ? mb_strtolower($country, 'UTF-8') : strtolower($country);
    foreach ($aliases as $alias => $english_name) {
        $alias_folded = function_exists('mb_strtolower') ? mb_strtolower((string) $alias, 'UTF-8') : strtolower((string) $alias);
        if ($alias_folded === $country_folded) {
            return $english_name;
        }
    }

    return $country;
}

function alpenia_get_countries_de() {
    return [
        'Afghanistan','Ägypten','Albanien','Algerien','Andorra','Angola','Antigua und Barbuda','Äquatorialguinea','Argentinien',
        'Armenien','Aserbaidschan','Äthiopien','Australien','Bahamas','Bahrain','Bangladesch','Barbados','Belarus','Belgien',
        'Belize','Benin','Bhutan','Bolivien','Bosnien und Herzegowina','Botswana','Brasilien','Brunei','Bulgarien','Burkina Faso',
        'Burundi','Chile','China','Costa Rica','Dänemark','Deutschland','Dominica','Dominikanische Republik','Dschibuti','Ecuador',
        'El Salvador','Elfenbeinküste','Eritrea','Estland','Eswatini','Fidschi','Finnland','Frankreich','Gabun','Gambia','Georgien',
        'Ghana','Grenada','Griechenland','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Indien','Indonesien',
        'Irak','Iran','Irland','Island','Italien','Jamaika','Japan','Jemen','Jordanien','Kambodscha','Kamerun','Kanada',
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

function alpenia_get_countries_tr() {
    return [
        'Afganistan','Almanya','Amerika Birleşik Devletleri','Andorra','Angola','Antigua ve Barbuda','Arjantin','Arnavutluk','Avustralya',
        'Avusturya','Azerbaycan','Bahamalar','Bahreyn','Bangladeş','Barbados','Belarus','Belçika','Belize','Benin','Bhutan',
        'Birleşik Arap Emirlikleri','Birleşik Krallık','Bolivya','Bosna Hersek','Botsvana','Brezilya','Brunei','Bulgaristan',
        'Burkina Faso','Burundi','Cezayir','Cibuti','Çad','Çekya','Çin','Danimarka','Dominika','Dominik Cumhuriyeti','Ekvador',
        'Ekvator Ginesi','El Salvador','Endonezya','Eritre','Ermenistan','Estonya','Esvatini','Etiyopya','Fas','Fiji','Fildişi Sahili',
        'Filipinler','Filistin','Finlandiya','Fransa','Gabon','Gambiya','Gana','Gine','Gine-Bissau','Grenada','Guatemala','Guyana',
        'Güney Afrika','Güney Kore','Güney Sudan','Gürcistan','Haiti','Hırvatistan','Hindistan','Hollanda','Honduras','Irak','İngiltere',
        'İran','İrlanda','İspanya','İsveç','İsviçre','İtalya','İzlanda','Jamaika','Japonya','Kamboçya','Kamerun','Kanada','Karadağ',
        'Katar','Kazakistan','Kenya','Kıbrıs','Kırgızistan','Kiribati','Kolombiya','Komorlar','Kongo','Kosova','Kosta Rika','Kuveyt',
        'Kuzey Kore','Kuzey Makedonya','Küba','Laos','Lesotho','Letonya','Liberya','Libya','Lihtenştayn','Litvanya','Lübnan','Lüksemburg',
        'Macaristan','Madagaskar','Malavi','Maldivler','Malezya','Mali','Malta','Marshall Adaları','Mauritius','Meksika','Mısır',
        'Mikronezya','Moğolistan','Moldova','Monako','Moritanya','Mozambik','Myanmar','Namibya','Nauru','Nepal','Nijer','Nijerya',
        'Nikaragua','Norveç','Orta Afrika Cumhuriyeti','Özbekistan','Pakistan','Palau','Panama','Papua Yeni Gine','Paraguay','Peru',
        'Polonya','Portekiz','Romanya','Ruanda','Rusya','Saint Kitts ve Nevis','Saint Lucia','Saint Vincent ve Grenadinler','Samoa',
        'San Marino','Sao Tome ve Principe','Senegal','Seyşeller','Sırbistan','Sierra Leone','Singapur','Slovakya','Slovenya','Solomon Adaları',
        'Somali','Sri Lanka','Sudan','Surinam','Suriye','Suudi Arabistan','Şili','Tacikistan','Tanzanya','Tayland','Tayvan','Togo','Tonga',
        'Trinidad ve Tobago','Tunus','Tuvalu','Türkiye','Türkmenistan','Uganda','Ukrayna','Umman','Uruguay','Ürdün','Vanuatu','Vatikan',
        'Venezuela','Vietnam','Yemen','Yeni Zelanda','Yunanistan','Zambiya','Zimbabve'
    ];
}

function alpenia_get_all_countries($lang = null) {
    $lang = $lang ? sanitize_key((string) $lang) : alpenia_travel_get_language();

    if ($lang === 'tr') {
        $countries = array_values(array_unique(array_merge(alpenia_get_countries_tr(), array_values(alpenia_get_country_turkish_aliases()))));
        sort($countries, SORT_NATURAL | SORT_FLAG_CASE);
        return $countries;
    }

    $countries = alpenia_get_countries_de();
    sort($countries, SORT_NATURAL | SORT_FLAG_CASE);
    return $countries;
}

function alpenia_get_eu_countries() {
    $countries = [
        'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich','Griechenland','Irland','Italien',
        'Kroatien','Lettland','Litauen','Luxemburg','Malta','Niederlande','Österreich','Polen','Portugal','Rumänien',
        'Schweden','Slowakei','Slowenien','Spanien','Tschechien','Ungarn','Zypern'
    ];
    return array_values(array_unique(array_merge($countries, array_values(alpenia_get_country_turkish_aliases()))));
}

function alpenia_get_schengen_countries() {
    $countries = [
        'Belgien','Bulgarien','Dänemark','Deutschland','Estland','Finnland','Frankreich','Griechenland','Island','Italien',
        'Kroatien','Lettland','Liechtenstein','Litauen','Luxemburg','Malta','Niederlande','Norwegen','Österreich','Polen',
        'Portugal','Rumänien','Schweden','Schweiz','Slowakei','Slowenien','Spanien','Tschechien','Ungarn'
    ];
    return array_values(array_unique(array_merge($countries, array_values(alpenia_get_country_turkish_aliases()))));
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
    if ($gender === '') return '';
    if ($gender === 'frau' || $gender === 'female' || $gender === 'f') return 'F';
    return 'M';
}
