<?php
/**
 * Plugin Name: Elvira Quick Chat Button
 * Plugin URI: https://elvirainfotech.com/elvira-quick-chat-button
 * Description: Lightweight WhatsApp quick chat button with floating corner button and a shortcode [whatsapp_chat].
 * Version: 1.0.0
 * Author: Raihan Reza
 * Author URI: https://elvirainfotech.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: elvira-quick-chat-button
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'EWQC_VERSION', '1.0.0' );
define( 'EWQC_PLUGIN_FILE', __FILE__ );
define( 'EWQC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EWQC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EWQC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class Elvira_WhatsApp_Quick_Chat {

    /**
     * The single instance of the class
     *
     * @var Elvira_WhatsApp_Quick_Chat
     */
    private static $instance = null;

    /**
     * Main instance
     *
     * @return Elvira_WhatsApp_Quick_Chat
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once EWQC_PLUGIN_DIR . 'includes/class-ewqc-settings.php';
        require_once EWQC_PLUGIN_DIR . 'includes/class-ewqc-frontend.php';
        require_once EWQC_PLUGIN_DIR . 'includes/class-ewqc-shortcode.php';
        require_once EWQC_PLUGIN_DIR . 'includes/class-ewqc-license.php';
        
        // Pro features (license-gated)
        if ( file_exists( EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-pro.php' ) ) {
            require_once EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-pro.php';
        }
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'ewqc_init' ) );
        add_action( 'plugins_loaded', array( $this, 'ewqc_load' ), 20 );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'ewqc_add_settings_link') );

        
        // Activation/Deactivation hooks
        register_activation_hook( EWQC_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( EWQC_PLUGIN_FILE, array( $this, 'deactivate' ) );
    }

    

    public function ewqc_add_settings_link( $links ) {
        $settings_url  = admin_url( 'options-general.php?page=ewqc-settings' );
        $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'elvira-quick-chat-button' ) . '</a>';

        // Put the Settings link first (so it appears next to "Deactivate")
        array_unshift( $links, $settings_link );

        return $links;
    }



    public function ewqc_load() {
        if (class_exists('EWQC_License')) {
            EWQC_License::init();
        }
    }


    /**
     * Initialize plugin
     */
    public function ewqc_init() {
        // Initialize core classes
        EWQC_Settings::init();
        EWQC_Frontend::init();
        EWQC_Shortcode::init();
        EWQC_License::init();
        
        // Initialize pro features if license is active
        if ( EWQC_License::ewqc_is_pro_active() && class_exists( 'EWQC_Pro' ) ) {
            EWQC_Pro::init();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'phone_number' => '',
            'default_message' => __( 'Hello! I need some help.', 'elvira-quick-chat-button' ),
            'button_text' => __( 'Chat with us', 'elvira-quick-chat-button' ),
            'position' => 'bottom-right',
            'mobile_only' => '0',
            'show_floating' => '1',
        );
        
        if ( false === get_option( 'ewqc_settings' ) ) {
            add_option( 'ewqc_settings', $defaults );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron
        $timestamp = wp_next_scheduled( EWQC_License::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, EWQC_License::CRON_HOOK );
        }
        
        flush_rewrite_rules();
    }
}

/**
 * Returns the main instance of Elvira_WhatsApp_Quick_Chat
 */
function EWQC() {
    return Elvira_WhatsApp_Quick_Chat::instance();
}

// Initialize the plugin
EWQC();