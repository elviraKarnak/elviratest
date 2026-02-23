<?php
/**
 * Admin Class
 * Handles all admin-related functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPS_Admin {
    
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
        // Add admin notices
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // Add custom admin body class
        add_filter('admin_body_class', [$this, 'admin_body_class']);
        
        // Handle quick actions
        add_action('admin_init', [$this, 'handle_quick_actions']);
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if plugin just activated
        if (get_transient('dps_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('Dynamic Political Survey System activated!', 'dynamic-survey'); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add'); ?>">
                        <?php _e('Create your first survey', 'dynamic-survey'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('dps_activation_notice');
        }
        
        // Check if surveys need attention
        global $wpdb;
        $table_surveys = $wpdb->prefix . 'dps_surveys';
        $draft_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_surveys WHERE status = 'draft'");
        
        if ($draft_count > 0 && isset($_GET['page']) && $_GET['page'] === 'dynamic-survey') {
            ?>
            <div class="notice notice-info">
                <p>
                    <?php 
                    printf(
                        __('You have %d draft survey(s). Don\'t forget to activate them!', 'dynamic-survey'),
                        $draft_count
                    ); 
                    ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Add custom admin body class
     */
    public function admin_body_class($classes) {
        if (isset($_GET['page']) && strpos($_GET['page'], 'dynamic-survey') !== false) {
            $classes .= ' dps-admin-page';
        }
        return $classes;
    }
    
    /**
     * Handle quick actions from URL parameters
     */
    public function handle_quick_actions() {
        // Check for action parameter
        if (!isset($_GET['dps_action']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'dps_quick_action')) {
            wp_die(__('Security check failed', 'dynamic-survey'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action', 'dynamic-survey'));
        }
        
        $action = sanitize_text_field($_GET['dps_action']);
        $survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
        
        global $wpdb;
        $table_surveys = $wpdb->prefix . 'dps_surveys';
        
        switch ($action) {
            case 'activate':
                $wpdb->update($table_surveys, ['status' => 'active'], ['id' => $survey_id]);
                wp_redirect(add_query_arg(['page' => 'dynamic-survey-surveys', 'message' => 'activated'], admin_url('admin.php')));
                exit;
                
            case 'deactivate':
                $wpdb->update($table_surveys, ['status' => 'inactive'], ['id' => $survey_id]);
                wp_redirect(add_query_arg(['page' => 'dynamic-survey-surveys', 'message' => 'deactivated'], admin_url('admin.php')));
                exit;
                
            case 'draft':
                $wpdb->update($table_surveys, ['status' => 'draft'], ['id' => $survey_id]);
                wp_redirect(add_query_arg(['page' => 'dynamic-survey-surveys', 'message' => 'drafted'], admin_url('admin.php')));
                exit;
        }
    }
}
