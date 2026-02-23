<?php
/**
 * Admin Menu and Pages
 */

if (!defined('ABSPATH')) exit;

class CAS_Admin_Menu {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Assessment System',
            'Assessments',
            'manage_options',
            'cas-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'cas-dashboard',
            'Questions',
            'Questions',
            'manage_options',
            'cas-questions',
            array($this, 'render_questions')
        );
        
        add_submenu_page(
            'cas-dashboard',
            'Submissions',
            'Submissions',
            'manage_options',
            'cas-submissions',
            array($this, 'render_submissions')
        );
        
        add_submenu_page(
            'cas-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cas-settings',
            array($this, 'render_settings')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cas-') === false) return;
        
        wp_enqueue_style('cas-admin-css', CAS_PLUGIN_URL . 'assets/admin.css', array(), CAS_VERSION);
        wp_enqueue_script('cas-admin-js', CAS_PLUGIN_URL . 'assets/admin.js', array('jquery'), CAS_VERSION, true);
        
        wp_localize_script('cas-admin-js', 'casAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cas_admin_nonce')
        ));
    }
    
    public function render_dashboard() {
        include CAS_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    public function render_questions() {
        include CAS_PLUGIN_DIR . 'templates/admin/questions.php';
    }
    
    public function render_submissions() {
        include CAS_PLUGIN_DIR . 'templates/admin/submissions.php';
    }
    
    public function render_settings() {
        if (isset($_POST['cas_save_settings'])) {
            check_admin_referer('cas_settings_nonce');
            
            $settings = array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'questions_per_page' => intval($_POST['questions_per_page']),
                'safe_threshold' => intval($_POST['safe_threshold']),
                'acceptable_threshold' => intval($_POST['acceptable_threshold']),
                'risk_threshold' => intval($_POST['risk_threshold'])
            );
            
            update_option('cas_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        include CAS_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}
