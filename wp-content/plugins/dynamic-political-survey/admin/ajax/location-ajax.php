<?php
/**
 * Location AJAX Handlers
 * Handles location hierarchy operations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get Loksabha by District
add_action('wp_ajax_dps_get_loksabha', 'dps_ajax_get_loksabha');
add_action('wp_ajax_nopriv_dps_get_loksabha', 'dps_ajax_get_loksabha');
function dps_ajax_get_loksabha() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    global $wpdb;
    
    $district_id = intval($_POST['district_id']);
    
    $table_loksabha = $wpdb->prefix . 'dps_loksabha';
    
    $loksabha = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name FROM $table_loksabha WHERE district_id = %d ORDER BY name",
        $district_id
    ));
    
    wp_send_json_success(['loksabha' => $loksabha]);
}

// Get Assembly by Loksabha
add_action('wp_ajax_dps_get_assembly', 'dps_ajax_get_assembly');
add_action('wp_ajax_nopriv_dps_get_assembly', 'dps_ajax_get_assembly');
function dps_ajax_get_assembly() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    global $wpdb;
    
    $loksabha_id = intval($_POST['loksabha_id']);
    
    $table_assembly = $wpdb->prefix . 'dps_assembly';
    
    $assembly = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name FROM $table_assembly WHERE loksabha_id = %d ORDER BY name",
        $loksabha_id
    ));
    
    wp_send_json_success(['assembly' => $assembly]);
}

// Add District
add_action('wp_ajax_dps_add_district', 'dps_ajax_add_district');
function dps_ajax_add_district() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $name = sanitize_text_field($_POST['name']);
    
    if (empty($name)) {
        wp_send_json_error(['message' => 'District name is required']);
    }
    
    $table_districts = $wpdb->prefix . 'dps_districts';
    
    $wpdb->insert($table_districts, ['name' => $name]);
    
    wp_send_json_success([
        'message' => 'District added successfully',
        'id' => $wpdb->insert_id
    ]);
}

// Add Loksabha
add_action('wp_ajax_dps_add_loksabha', 'dps_ajax_add_loksabha');
function dps_ajax_add_loksabha() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $district_id = intval($_POST['district_id']);
    $name = sanitize_text_field($_POST['name']);
    
    if (empty($name) || $district_id <= 0) {
        wp_send_json_error(['message' => 'District and name are required']);
    }
    
    $table_loksabha = $wpdb->prefix . 'dps_loksabha';
    
    $wpdb->insert($table_loksabha, [
        'district_id' => $district_id,
        'name' => $name
    ]);
    
    wp_send_json_success([
        'message' => 'Loksabha added successfully',
        'id' => $wpdb->insert_id
    ]);
}

// Add Assembly
add_action('wp_ajax_dps_add_assembly', 'dps_ajax_add_assembly');
function dps_ajax_add_assembly() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $loksabha_id = intval($_POST['loksabha_id']);
    $name = sanitize_text_field($_POST['name']);
    
    if (empty($name) || $loksabha_id <= 0) {
        wp_send_json_error(['message' => 'Loksabha and name are required']);
    }
    
    $table_assembly = $wpdb->prefix . 'dps_assembly';
    
    $wpdb->insert($table_assembly, [
        'loksabha_id' => $loksabha_id,
        'name' => $name
    ]);
    
    wp_send_json_success([
        'message' => 'Assembly added successfully',
        'id' => $wpdb->insert_id
    ]);
}

// Delete Location
add_action('wp_ajax_dps_delete_location', 'dps_ajax_delete_location');
function dps_ajax_delete_location() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $type = sanitize_text_field($_POST['type']); // district, loksabha, assembly
    $id = intval($_POST['id']);
    
    $table_map = [
        'district' => $wpdb->prefix . 'dps_districts',
        'loksabha' => $wpdb->prefix . 'dps_loksabha',
        'assembly' => $wpdb->prefix . 'dps_assembly'
    ];
    
    if (!isset($table_map[$type])) {
        wp_send_json_error(['message' => 'Invalid location type']);
    }
    
    $wpdb->delete($table_map[$type], ['id' => $id]);
    
    wp_send_json_success(['message' => ucfirst($type) . ' deleted successfully']);
}