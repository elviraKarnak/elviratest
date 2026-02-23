<?php
/**
 * Plugin Name: Elvira Menu Expiration Control
 * Plugin URI: https://elvirainfotech.com/elvira-menu-expiration-control
 * Description: Let site owners schedule menu items or links — show/hide by date/time and audience. Free core; Pro features unlock via license.
 * Version: 1.0.0
 * Author: Raihan Reza
 * Author URI: https://elvirainfotech.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: elvira-menu-expiration-control
 * Domain Path: /languages
 *
 * @package ElviraMenuExpirationControl
 */

if (!defined('ABSPATH')) exit;

define('EMEC_VERSION', '1.0.0');
define('EMEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EMEC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EMEC_FILE', __FILE__);

require_once EMEC_PLUGIN_DIR . 'includes/class-emec-admin.php';
require_once EMEC_PLUGIN_DIR . 'includes/class-emec-frontend.php';
require_once EMEC_PLUGIN_DIR . 'includes/class-emec-license.php';
require_once EMEC_PLUGIN_DIR . 'includes/helpers/class-emec-utils.php';

// Pro classes are included but gated by license.
require_once EMEC_PLUGIN_DIR . 'includes/pro/class-emec-pro-helper.php';
require_once EMEC_PLUGIN_DIR . 'includes/pro/class-emec-pro-frontend.php';

function emec_load() {
    // Instantiate core classes
    if (class_exists('EMEC_Admin')) {
        EMEC_Admin::init();
    }
    if (class_exists('EMEC_Frontend')) {
        EMEC_Frontend::init();
    }
    if (class_exists('EMEC_License')) {
        EMEC_License::init();
    }

    // Pro: initialize only when license is active
    if (EMEC_License::emec_is_pro_active()) {
        if (class_exists('EMEC_Pro_Frontend')) EMEC_Pro_Frontend::init();
        if (class_exists('EMEC_Pro_CPT')) EMEC_Pro_CPT::init();
    }
}
add_action('plugins_loaded', 'emec_load', 20);

// Activation/Deactivation
function emec_activate() {
    // placeholder: flush rewrite if CPT added later
    if (!get_option('emec_installed')) {
        update_option('emec_installed', time());
    }
}
register_activation_hook(__FILE__, 'emec_activate');

function emec_deactivate() {
    // leave options; uninstall.php will remove if requested
}
register_deactivation_hook(__FILE__, 'emec_deactivate');

