<?php
/**
 * Uninstall script
 *
 * This file is executed when the plugin is deleted from the WP admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// --- Delete options (single-site)
delete_option( 'ewqc_settings' );
delete_option( 'ewqc_license_key' );
delete_option( 'ewqc_license_status' );
delete_option( 'ewqc_agents' );

// --- Delete network/site options (multisite)
if ( is_multisite() ) {
    delete_site_option( 'ewqc_settings' );
    delete_site_option( 'ewqc_license_key' );
    delete_site_option( 'ewqc_license_status' );
    delete_site_option( 'ewqc_agents' );
}

// --- Delete transients
delete_transient( 'ewqc_last_agent_index' );
if ( is_multisite() ) {
    // If you used site transients, remove them here:
    // delete_site_transient( 'ewqc_last_agent_index' );
}

// --- Drop analytics table
global $wpdb;
$table_name = $wpdb->prefix . 'ewqc_analytics';

/*
 * The following direct query is intentional and safe because:
 *  - $table_name is derived from $wpdb->prefix (not user input),
 *  - DROP TABLE cannot be executed via a higher-level WP API, and
 *  - $wpdb->prepare() cannot be used for SQL identifiers (table names).
 *
 * Tell PHPCS to ignore both the direct DB query and the interpolated-identifier rule
 * for the next line only.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- safe use: dropping plugin-created table by prefixed name
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// --- Clear scheduled cron(s)
// Remove all scheduled events for our hook
wp_clear_scheduled_hook( 'ewqc_hourly_license_check' );

// If you specifically scheduled events with arguments, you can unschedule them individually:
// $timestamp = wp_next_scheduled( 'ewqc_hourly_license_check' );
// if ( $timestamp ) {
//     wp_unschedule_event( $timestamp, 'ewqc_hourly_license_check' );
// }
