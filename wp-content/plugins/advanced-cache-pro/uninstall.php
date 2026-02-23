<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'acp_options' );

$cache_dir = WP_CONTENT_DIR . '/advanced-cache-pro-cache';
if ( is_dir( $cache_dir ) ) {
    $files = glob( trailingslashit( $cache_dir ) . '*', GLOB_NOSORT );
    if ( $files ) {
        foreach ( $files as $f ) {
            if ( is_file( $f ) ) @unlink( $f );
        }
    }
    @rmdir( $cache_dir );
}
