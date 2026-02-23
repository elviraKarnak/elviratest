<?php
/**
 * Plugin Deactivator
 * Handles plugin deactivation tasks
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPS_Deactivator {
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled cron jobs if we add them later
        // wp_clear_scheduled_hook('dps_daily_cleanup');
        
        // Optional: Clear transients
        delete_transient('dps_survey_cache');
        
        // Log deactivation
        error_log('Dynamic Political Survey System deactivated');
    }
}
