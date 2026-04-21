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

function alpenia_user_can_access_dashboard() {
    if (!is_user_logged_in()) return false;
    return alpenia_is_admin_user() || alpenia_is_reiseleiter_user() || alpenia_is_backoffice_user();
}

function alpenia_user_can_manage_users() {
    return alpenia_is_admin_user();
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

function alpenia_is_eu_nationality($nationality) {
    $nationality = trim((string) $nationality);
    if ($nationality === '') return false;
    return in_array($nationality, alpenia_get_eu_countries(), true);
}

function alpenia_gender_code($gender) {
    $gender = strtolower(trim((string) $gender));
    if ($gender === 'frau' || $gender === 'female' || $gender === 'f') return 'F';
    return 'M';
}
