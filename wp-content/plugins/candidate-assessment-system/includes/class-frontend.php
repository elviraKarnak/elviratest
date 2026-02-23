<?php
/**
 * Frontend Handler
 */

if (!defined('ABSPATH')) exit;

class CAS_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('candidate_assessment', array($this, 'render_assessment_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    public function enqueue_frontend_assets() {
        if (has_shortcode(get_post()->post_content ?? '', 'candidate_assessment')) {
            wp_enqueue_style('cas-frontend-css', CAS_PLUGIN_URL . 'assets/frontend.css', array(), CAS_VERSION);
            wp_enqueue_script('cas-frontend-js', CAS_PLUGIN_URL . 'assets/frontend.js', array('jquery'), CAS_VERSION, true);
            
            $settings = get_option('cas_settings');
            
            wp_localize_script('cas-frontend-js', 'casFrontend', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'primaryColor' => $settings['primary_color'],
                'secondaryColor' => $settings['secondary_color']
            ));
        }
    }
    
    public function render_assessment_form() {
        ob_start();
        include CAS_PLUGIN_DIR . 'templates/frontend/assessment-form.php';
        return ob_get_clean();
    }
}
