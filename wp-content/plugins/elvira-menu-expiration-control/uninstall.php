<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean uninstall for Menu Expiration Control
 * - remove plugin options
 * - remove menu-item post meta via WP API (in batches)
 */

// delete plugin options
delete_option( 'emec_installed' );
delete_option( 'emec_license_key' );
delete_option( 'emec_license_status' );

// meta keys to remove from nav menu items
$meta_keys = array(
    '_emec_enable',
    '_emec_start',
    '_emec_end',
    '_emec_visibility',
    '_emec_roles',    // if you used this in admin
    '_emecp_rules',   // pro rules
);

// Batch size to avoid memory issues on large sites
$posts_per_page = 200;
$paged = 1;

do {
    $q = new WP_Query( array(
        'post_type'      => 'nav_menu_item',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'fields'         => 'ids',
        'post_status'    => 'any',
    ) );

    if ( empty( $q->posts ) ) {
        break;
    }

    foreach ( $q->posts as $menu_item_id ) {
        foreach ( $meta_keys as $meta_key ) {
            // delete_post_meta() is safe and triggers cache invalidation internally
            delete_post_meta( (int) $menu_item_id, $meta_key );
        }
    }

    $paged++;
    wp_reset_postdata();
} while ( count( $q->posts ) === $posts_per_page );
