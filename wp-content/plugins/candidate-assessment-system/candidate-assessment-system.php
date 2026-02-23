<?php
/**
 * Plugin Name: Candidate Assessment System
 * Description: Modern personality assessment test system for candidate interviews
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class CandidateAssessmentSystem {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    private function define_constants() {
        define('CAS_VERSION', '1.0.0');
        define('CAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('CAS_PLUGIN_URL', plugin_dir_url(__FILE__));
    }
    
    private function includes() {
        require_once CAS_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once CAS_PLUGIN_DIR . 'includes/class-admin-menu.php';
        require_once CAS_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once CAS_PLUGIN_DIR . 'includes/class-scoring.php';
        require_once CAS_PLUGIN_DIR . 'includes/class-frontend.php';
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init_plugin'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init_plugin() {
        CAS_Post_Types::get_instance();
        CAS_Admin_Menu::get_instance();
        CAS_Ajax_Handler::get_instance();
        CAS_Frontend::get_instance();
    }
    
    public function activate() {
        // Create default settings
        $default_settings = array(
            'primary_color' => '#4F46E5',
            'secondary_color' => '#10B981',
            'questions_per_page' => 10,
            'safe_threshold' => 80,
            'acceptable_threshold' => 50,
            'risk_threshold' => 20
        );
        
        if (!get_option('cas_settings')) {
            add_option('cas_settings', $default_settings);
        }
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize plugin
function cas_init() {
    return CandidateAssessmentSystem::get_instance();
}
cas_init();