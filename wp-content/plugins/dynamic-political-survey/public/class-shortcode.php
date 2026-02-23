<?php
/**
 * Shortcode Class
 * Handles survey shortcode functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPS_Shortcode {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Shortcode is already registered in main plugin file
        // This class provides helper methods
    }
    
    /**
     * Validate survey exists and is active
     */
    public static function validate_survey($survey_id) {
        global $wpdb;
        
        $table_surveys = $wpdb->prefix . 'dps_surveys';
        $survey = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_surveys WHERE id = %d",
            $survey_id
        ));
        
        if (!$survey) {
            return ['valid' => false, 'message' => __('Survey not found.', 'dynamic-survey')];
        }
        
        if ($survey->status !== 'active') {
            return ['valid' => false, 'message' => __('This survey is not currently active.', 'dynamic-survey')];
        }
        
        return ['valid' => true, 'survey' => $survey];
    }
    
    /**
     * Get survey preview link
     */
    public static function get_preview_url($survey_id) {
        $args = [
            'dps_preview' => 1,
            'survey_id' => $survey_id,
            'preview_nonce' => wp_create_nonce('dps_preview_' . $survey_id)
        ];
        
        return add_query_arg($args, home_url('/'));
    }
    
    /**
     * Check if current request is a preview
     */
    public static function is_preview() {
        if (!isset($_GET['dps_preview']) || !isset($_GET['survey_id']) || !isset($_GET['preview_nonce'])) {
            return false;
        }
        
        $survey_id = intval($_GET['survey_id']);
        $nonce = sanitize_text_field($_GET['preview_nonce']);
        
        return wp_verify_nonce($nonce, 'dps_preview_' . $survey_id);
    }
    
    /**
     * Render error message
     */
    public static function render_error($message) {
        return sprintf(
            '<div class="dps-error-message" style="padding: 20px; background: #fee; color: #c33; border-radius: 8px; text-align: center; margin: 20px 0;">
                <p style="margin: 0; font-size: 16px;"><strong>%s</strong></p>
            </div>',
            esc_html($message)
        );
    }
    
    /**
     * Render already submitted message
     */
    public static function render_already_submitted() {
        return '<div class="dps-already-submitted" style="padding: 40px 20px; background: #fff; border: 2px solid #f0f0f0; border-radius: 12px; text-align: center; margin: 20px 0;">
            <div style="font-size: 60px; margin-bottom: 20px;">✓</div>
            <h3 style="color: #27ae60; margin-bottom: 10px;">' . __('Thank You!', 'dynamic-survey') . '</h3>
            <p style="color: #7f8c8d; font-size: 16px;">' . __('You have already submitted this survey recently.', 'dynamic-survey') . '</p>
        </div>';
    }
}
