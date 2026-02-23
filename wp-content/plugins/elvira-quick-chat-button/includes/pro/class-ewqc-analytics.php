<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Analytics handler (Pro feature)
 */
class EWQC_Analytics {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'wp_ajax_ewqc_track_click', array( __CLASS__, 'ewqc_track_click' ) );
        add_action( 'wp_ajax_nopriv_ewqc_track_click', array( __CLASS__, 'ewqc_track_click' ) );
    }

    /**
     * Track click
     */
    public static function ewqc_track_click() {
        // Match this string to the wp_create_nonce() you used when localizing the script.
        check_ajax_referer( 'ewqc_track_click', 'nonce' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'ewqc_analytics';

        // Make sure ewqc_get_user_ip() exists, or fall back to REMOTE_ADDR
        $ip = '';
        if ( method_exists( __CLASS__, 'get_user_ip' ) ) {
            $ip = sanitize_text_field( wp_unslash( self::ewqc_get_user_ip() ) );
        } else {
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        }

        $page_url   = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $timestamp  = current_time( 'mysql' );

        /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- safe use of $wpdb->insert with sanitized input */
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'timestamp'  => $timestamp,
                'user_agent' => $user_agent,
                'ip_address' => $ip,
                'page_url'   => $page_url,
            ),
            array( '%s', '%s', '%s', '%s' )
        );



        if ( $inserted === false ) {
            // Insert failed
            wp_send_json_error( array( 'message' => 'DB insert failed', 'db_error' => $wpdb->last_error ) );
        }

        wp_send_json_success( array( 'insert_id' => $wpdb->insert_id ) );
    }

    /**
     * Get user IP
     */
    private static function ewqc_get_user_ip() {
        $ip = '';
        
        if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        
        return $ip;
    }

    /**
     * Render dashboard
     */
    public static function ewqc_render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ewqc_analytics';
        $esc_table = esc_sql( $table_name );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Intentional safe query to check if custom analytics table exists
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table_name
            )
        );


        // $wpdb->get_var() returns the table name if present, or null if not.
        if ( null === $exists ) {
            self::ewqc_create_analytics_table();
        }

        /**
         * Caching for counts to address WPCS NoCaching warnings and reduce DB load.
         * Cache keys include date for today's count so it's unique per day.
         */
        $cache_group     = 'ewqc_analytics';
        $cache_ttl       = 5 * MINUTE_IN_SECONDS; // adjust as desired
        $cache_key_total = 'ewqc_total_clicks';
        $cache_key_today = 'ewqc_today_clicks_' . current_time( 'Y-m-d' );

        // Total clicks (cached)
        $total_clicks = wp_cache_get( $cache_key_total, $cache_group );
        if ( false === $total_clicks ) {
            // Escape identifier (table name) correctly
            $esc_table = esc_sql( $table_name );

            /*
             * Note: Added 'WordPress.DB.PreparedSQL.InterpolatedNotPrepared'
             * to ignore the necessary interpolation of the safe table name.
             */
            /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- intentional safe DB query; result will be cached */
            $total_clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$esc_table}" );
            wp_cache_set( $cache_key_total, $total_clicks, $cache_group, $cache_ttl );
        } else {
            $total_clicks = (int) $total_clicks;
        }

        // Today's clicks (cached)
        $today_clicks = wp_cache_get( $cache_key_today, $cache_group );
        if ( false === $today_clicks ) {
            // Compute start/end of day in site time (WP-aware)
            $start_of_day = wp_date( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );
            $end_of_day   = wp_date( 'Y-m-d 23:59:59', current_time( 'timestamp' ) );

            // Defensive validate table name
            if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
                $today_clicks = 0;
            } else {
                // Escape identifier (table name) correctly
                $esc_table = esc_sql( $table_name );

                /* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery */
                $today_clicks = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        // The ignore is placed immediately after the problematic string
                        "SELECT COUNT(*) FROM {$esc_table} WHERE `timestamp` >= %s AND `timestamp` <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $start_of_day,
                        $end_of_day
                    )
                );
            }


            wp_cache_set( $cache_key_today, $today_clicks, $cache_group, $cache_ttl );
        } else {
            $today_clicks = (int) $today_clicks;
        }

        // Ensure numeric fallback (in case DB returns null)
        $total_clicks = (int) ( $total_clicks ?? 0 );
        $today_clicks = (int) ( $today_clicks ?? 0 );
        ?>
        <h2><?php esc_html_e( 'Analytics Dashboard (Pro)', 'elvira-quick-chat-button' ); ?></h2>
        <div class="ewqc-analytics-cards">
            <div class="ewqc-card">
                <h3><?php esc_html_e( 'Total Clicks', 'elvira-quick-chat-button' ); ?></h3>
                <p class="ewqc-stat"><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></p>
            </div>
            <div class="ewqc-card">
                <h3><?php esc_html_e( 'Today\'s Clicks', 'elvira-quick-chat-button' ); ?></h3>
                <p class="ewqc-stat"><?php echo esc_html( number_format_i18n( $today_clicks ) ); ?></p>
            </div>
        </div>
        <?php
    }


    /**
     * Create analytics table
     */
    private static function ewqc_create_analytics_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ewqc_analytics';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL,
            user_agent text NOT NULL,
            ip_address varchar(100) NOT NULL,
            page_url text NOT NULL,
            PRIMARY KEY  (id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}