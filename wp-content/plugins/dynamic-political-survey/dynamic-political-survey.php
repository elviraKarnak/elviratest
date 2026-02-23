<?php
/*
Plugin Name: Dynamic Political Survey System
Plugin URI: https://yoursite.com
Description: Complete dynamic survey system with conditional logic, multi-step forms, and advanced analytics
Version: 1.0.0
Author: Your Name
Author URI: https://yoursite.com
License: GPL v2 or later
Text Domain: dynamic-survey
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('DPS_VERSION', '1.0.0');
define('DPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DPS_PLUGIN_FILE', __FILE__);

// Activation Hook
register_activation_hook(__FILE__, 'dps_activate_plugin');
function dps_activate_plugin() {
    require_once DPS_PLUGIN_DIR . 'includes/class-activator.php';
    DPS_Activator::activate();
}

// Deactivation Hook
register_deactivation_hook(__FILE__, 'dps_deactivate_plugin');
function dps_deactivate_plugin() {
    require_once DPS_PLUGIN_DIR . 'includes/class-deactivator.php';
    DPS_Deactivator::deactivate();
}

// Initialize Plugin
add_action('plugins_loaded', 'dps_init_plugin');
function dps_init_plugin() {
    // Load text domain
    load_plugin_textdomain('dynamic-survey', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize admin
    if (is_admin()) {
        require_once DPS_PLUGIN_DIR . 'admin/class-admin.php';
        new DPS_Admin();
    }
    
    // Initialize public
    require_once DPS_PLUGIN_DIR . 'public/class-public.php';
    new DPS_Public();
    
    // Initialize shortcodes
    require_once DPS_PLUGIN_DIR . 'public/class-shortcode.php';
    new DPS_Shortcode();
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'dps_admin_enqueue_scripts');
function dps_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'dynamic-survey') === false) {
        return;
    }
    
    // CSS
    wp_enqueue_style('dps-admin-style', DPS_PLUGIN_URL . 'assets/css/admin-style.css', [], DPS_VERSION);
    wp_enqueue_style('dps-analytics-style', DPS_PLUGIN_URL . 'assets/css/analytics.css', [], DPS_VERSION);
    
    // JS
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', [], '3.9.1', true);
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('dps-admin-script', DPS_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], DPS_VERSION, true);
    wp_enqueue_script('dps-survey-builder', DPS_PLUGIN_URL . 'assets/js/survey-builder.js', ['jquery', 'jquery-ui-sortable'], DPS_VERSION, true);
    wp_enqueue_script('dps-analytics', DPS_PLUGIN_URL . 'assets/js/analytics.js', ['jquery', 'chart-js'], DPS_VERSION, true);
    
    // Localize script
    wp_localize_script('dps-admin-script', 'dpsAdmin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dps_admin_nonce'),
        'strings' => [
            'confirm_delete' => __('Are you sure you want to delete this?', 'dynamic-survey'),
            'error' => __('An error occurred. Please try again.', 'dynamic-survey'),
            'success' => __('Operation completed successfully!', 'dynamic-survey')
        ]
    ]);
}

// Enqueue public scripts and styles
add_action('wp_enqueue_scripts', 'dps_public_enqueue_scripts');
function dps_public_enqueue_scripts() {
    // CSS
    wp_enqueue_style('dps-survey-form', DPS_PLUGIN_URL . 'assets/css/survey-form.css', [], DPS_VERSION);
    
    // JS
    wp_enqueue_script('dps-survey-form', DPS_PLUGIN_URL . 'assets/js/survey-form.js', ['jquery'], DPS_VERSION, true);
    
    // Localize script
    wp_localize_script('dps-survey-form', 'dpsSurvey', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dps_public_nonce'),
        'strings' => [
            'required_field' => __('This field is required', 'dynamic-survey'),
            'error' => __('An error occurred. Please try again.', 'dynamic-survey'),
            'loading' => __('Loading...', 'dynamic-survey')
        ]
    ]);
}

// Add admin menu
add_action('admin_menu', 'dps_add_admin_menu');
function dps_add_admin_menu() {
    // Main menu
    add_menu_page(
        __('Dynamic Survey', 'dynamic-survey'),
        __('Survey System', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey',
        'dps_dashboard_page',
        'dashicons-chart-bar',
        30
    );
    
    // Dashboard
    add_submenu_page(
        'dynamic-survey',
        __('Dashboard', 'dynamic-survey'),
        __('Dashboard', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey',
        'dps_dashboard_page'
    );
    
    // Surveys
    add_submenu_page(
        'dynamic-survey',
        __('All Surveys', 'dynamic-survey'),
        __('All Surveys', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey-surveys',
        'dps_surveys_list_page'
    );
    
    // Add New Survey
    add_submenu_page(
        'dynamic-survey',
        __('Add New Survey', 'dynamic-survey'),
        __('Add New Survey', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey-add',
        'dps_survey_builder_page'
    );
    
    // Submissions
    add_submenu_page(
        'dynamic-survey',
        __('Submissions', 'dynamic-survey'),
        __('Submissions', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey-submissions',
        'dps_submissions_page'
    );
    
    // Analytics
    add_submenu_page(
        'dynamic-survey',
        __('Analytics', 'dynamic-survey'),
        __('Analytics', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey-analytics',
        'dps_analytics_page'
    );
    
    // Locations
    add_submenu_page(
        'dynamic-survey',
        __('Locations', 'dynamic-survey'),
        __('Locations', 'dynamic-survey'),
        'manage_options',
        'dynamic-survey-locations',
        'dps_locations_page'
    );
}

// Page callbacks
function dps_dashboard_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/dashboard.php';
}

function dps_surveys_list_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/surveys-list.php';
}

function dps_survey_builder_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/survey-builder.php';
}

function dps_submissions_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/submissions-list.php';
}

function dps_analytics_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/analytics.php';
}

function dps_locations_page() {
    require_once DPS_PLUGIN_DIR . 'admin/pages/locations.php';
}

// AJAX Handlers
require_once DPS_PLUGIN_DIR . 'admin/ajax/survey-ajax.php';
require_once DPS_PLUGIN_DIR . 'admin/ajax/location-ajax.php';
require_once DPS_PLUGIN_DIR . 'admin/ajax/analytics-ajax.php';

// Shortcode: [dynamic_survey id="1"]
add_shortcode('dynamic_survey', 'dps_survey_shortcode');
function dps_survey_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => 0
    ], $atts);
    
    if (!$atts['id']) {
        return '<p class="dps-error">' . __('Please specify a survey ID.', 'dynamic-survey') . '</p>';
    }
    
    ob_start();
    require DPS_PLUGIN_DIR . 'public/templates/survey-form.php';
    return ob_get_clean();
}