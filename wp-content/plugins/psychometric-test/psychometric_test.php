<?php
/**
 * Plugin Name: Psychometric Test
 * Plugin URI: https://yourwebsite.com
 * Description: A comprehensive psychometric test plugin with multi-step questions and risk assessment
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * Text Domain: psychometric-test
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PSYCHOMETRIC_VERSION', '1.0.0');
define('PSYCHOMETRIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PSYCHOMETRIC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once PSYCHOMETRIC_PLUGIN_DIR . 'includes/class-database.php';
require_once PSYCHOMETRIC_PLUGIN_DIR . 'includes/class-admin.php';
require_once PSYCHOMETRIC_PLUGIN_DIR . 'includes/class-frontend.php';
require_once PSYCHOMETRIC_PLUGIN_DIR . 'includes/class-ajax.php';
require_once PSYCHOMETRIC_PLUGIN_DIR . 'includes/class-email.php';

// Activation hook
register_activation_hook(__FILE__, 'psychometric_activate');
function psychometric_activate() {
    Psychometric_Database::create_tables();
    
    // Set default options
    if (!get_option('psychometric_settings')) {
        update_option('psychometric_settings', array(
            'admin_emails' => array(get_option('admin_email')),
            'primary_color' => '#21BECA'
        ));
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'psychometric_deactivate');
function psychometric_deactivate() {
    // Clean up if needed
}

// Initialize plugin
add_action('plugins_loaded', 'psychometric_init');
function psychometric_init() {
    // Initialize classes
    new Psychometric_Admin();
    new Psychometric_Frontend();
    new Psychometric_Ajax();
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'psychometric_admin_scripts');
function psychometric_admin_scripts($hook) {
    if (strpos($hook, 'psychometric') !== false) {
        wp_enqueue_style('psychometric-admin-css', PSYCHOMETRIC_PLUGIN_URL . 'assets/css/admin.css', array(), PSYCHOMETRIC_VERSION);
        wp_enqueue_script('psychometric-admin-js', PSYCHOMETRIC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PSYCHOMETRIC_VERSION, true);
        
        wp_localize_script('psychometric-admin-js', 'psychometricAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psychometric_admin_nonce')
        ));
    }
}

// Enqueue frontend styles and scripts
add_action('wp_enqueue_scripts', 'psychometric_frontend_scripts');
function psychometric_frontend_scripts() {
    wp_enqueue_style('psychometric-frontend-css', PSYCHOMETRIC_PLUGIN_URL . 'assets/css/frontend.css', array(), PSYCHOMETRIC_VERSION);
    wp_enqueue_script('psychometric-frontend-js', PSYCHOMETRIC_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PSYCHOMETRIC_VERSION, true);
    
    $settings = get_option('psychometric_settings', array('primary_color' => '#21BECA'));
    
    wp_localize_script('psychometric-frontend-js', 'psychometricFrontend', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('psychometric_frontend_nonce'),
        'primaryColor' => $settings['primary_color']
    ));
}
