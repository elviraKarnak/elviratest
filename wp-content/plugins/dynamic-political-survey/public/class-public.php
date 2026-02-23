<?php
/**
 * Public Class
 * Handles all frontend functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPS_Public {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add custom body class for survey pages
        add_filter('body_class', [$this, 'body_class']);
        
        // Add meta tags for survey pages
        add_action('wp_head', [$this, 'add_meta_tags']);
        
        // Prevent caching of survey forms
        add_action('template_redirect', [$this, 'prevent_caching']);
    }
    
    /**
     * Add custom body class
     */
    public function body_class($classes) {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dynamic_survey')) {
            $classes[] = 'has-dps-survey';
        }
        
        return $classes;
    }
    
    /**
     * Add meta tags for survey pages
     */
    public function add_meta_tags() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dynamic_survey')) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }
    
    /**
     * Prevent caching of survey form pages
     */
    public function prevent_caching() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dynamic_survey')) {
            // Prevent page caching
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            
            // Set no-cache headers
            nocache_headers();
        }
    }
    
    /**
     * Get user IP address
     */
    public static function get_user_ip() {
        $ip_address = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP
        $ip_address = filter_var($ip_address, FILTER_VALIDATE_IP);
        
        return $ip_address ? $ip_address : '0.0.0.0';
    }
    
    /**
     * Check if user has already submitted survey (basic check)
     */
    public static function has_submitted_survey($survey_id) {
        global $wpdb;
        
        $ip_address = self::get_user_ip();
        $table_submissions = $wpdb->prefix . 'dps_submissions';
        
        // Check if IP submitted in last 24 hours
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_submissions 
             WHERE survey_id = %d 
             AND ip_address = %s 
             AND submitted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $survey_id,
            $ip_address
        ));
        
        return $count > 0;
    }
}
