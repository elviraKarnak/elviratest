<?php
/**
 * Plugin Name: Elvira Smart Notification Bar
 * Plugin URI: https://elvirainfotech.com/
 * Description: Lightweight sticky bar for announcements, sales, or free shipping offers — built by Elvira Infotech.
 * Version:     1.0.0
 * Author:      Raihan Reza
 * Author URI:  https://elvirainfotech.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: elvira-smart-notification-bar
 * Domain Path: /languages
 *
 * @package ElviraSmartNotificationBar
 */
if (!defined('ABSPATH')) exit;

define( 'ELVISMNO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define( 'ELVISMNO_PLUGIN_URL', plugin_dir_url(__FILE__));
define( 'ELVISMNO_VERSION', '1.0.0');

require_once ELVISMNO_PLUGIN_DIR . 'includes/class-esnb-admin.php';
require_once ELVISMNO_PLUGIN_DIR . 'includes/class-esnb-frontend.php';
require_once ELVISMNO_PLUGIN_DIR . 'includes/class-esnb-license.php';
require_once ELVISMNO_PLUGIN_DIR . 'includes/helpers/class-esnb-utils.php';

// Init free features
function elvismno_plugins_loaded_init() {
    // Ensure admin/frontend classes are available (we usually require files earlier, but double-check)
    if ( class_exists( 'ELVISMNO_Admin' ) ) {
        new ELVISMNO_Admin();
    }

    if ( class_exists( 'ELVISMNO_Frontend' ) ) {
        new ELVISMNO_Frontend();
    }

    // License/pro logic
    if ( class_exists( 'ELVISMNO_License' ) ) {
        $license = new ELVISMNO_License();

        if ( method_exists( $license, 'elvismno_is_pro_active' ) && $license->elvismno_is_pro_active() ) {

            // Load pro classes (use plugin dir constant)
            $pro_cpt = ELVISMNO_PLUGIN_DIR . 'includes/pro/class-esnb-pro-cpt.php';
            $pro_frontend = ELVISMNO_PLUGIN_DIR . 'includes/pro/class-esnb-pro-frontend.php';

            if ( file_exists( $pro_cpt ) ) {
                require_once $pro_cpt;
            }
            if ( file_exists( $pro_frontend ) ) {
                require_once $pro_frontend;
            }

            if ( class_exists( 'ELVISMNO_Pro_CPT' ) ) {
                new ELVISMNO_Pro_CPT();
            }
            if ( class_exists( 'ELVISMNO_Pro_Frontend' ) ) {
                new ELVISMNO_Pro_Frontend();
            }
        }
    }
}

add_action( 'plugins_loaded', 'elvismno_plugins_loaded_init' );

/**
 * Add "Settings" link on the Plugins page for this plugin
 * Points to: /wp-admin/options-general.php?page=esnb-settings
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'elvismno_add_settings_link' );
function elvismno_add_settings_link( $links ) {
    $settings_url  = admin_url( 'options-general.php?page=esnb-settings' ); // prefixed slug
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'elvira-smart-notification-bar' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

