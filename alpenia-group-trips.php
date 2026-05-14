<?php
/**
 * Plugin Name: Alpenia Travel Operations Dashboard
 * Description: Dashboard für Reisen, Teilnehmer, Dokumente, Zahlungen, Gruppenplanung und Benutzerverwaltung.
 * Version: 4.4
 * Text Domain: alpenia-travel
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('ALPENIA_PLUGIN_FILE', __FILE__);
define('ALPENIA_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once ALPENIA_PLUGIN_DIR . 'includes/core.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/security-hardening.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/uploads.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/trips.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/admin-participants.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/public-participant-form.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/export-print.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/shortcode-login.php';
require_once ALPENIA_PLUGIN_DIR . 'includes/security.php';

require_once ALPENIA_PLUGIN_DIR . 'includes/dashboard-shortcode.php';




add_action('plugins_loaded', 'alpenia_travel_load_textdomain');
function alpenia_travel_load_textdomain() {
    load_plugin_textdomain(
        'alpenia-travel',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
