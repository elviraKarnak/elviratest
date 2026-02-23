<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EQC License manager
 *
 * Uses remote endpoints to validate/deactivate license and schedules hourly checks.
 */
class EWQC_License {
    const OPTION_KEY    = 'ewqc_license_key';
    const OPTION_STATUS = 'ewqc_license_status'; // 'valid'|'invalid'|'unknown'
    const CRON_HOOK     = 'ewqc_hourly_license_check';
    const NONCE_ACTION  = 'ewqc_license_action';

    // Remote endpoints (change to your actual endpoints if needed)
    private static $api_verify     = 'https://elvirainfotech.live/license-api/validate.php';
    private static $api_deactivate = 'https://elvirainfotech.live/license-api/deactivate.php';

    // timeout for remote requests (seconds)
    private static $timeout = 20;

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'ewqc_add_license_page' ] );
        add_action( 'admin_post_ewqc_activate_license', [ __CLASS__, 'ewqc_handle_activate' ] );
        add_action( 'admin_post_ewqc_deactivate_license', [ __CLASS__, 'ewqc_handle_deactivate' ] );

        add_action( 'admin_notices', [ __CLASS__, 'ewqc_maybe_show_admin_notice' ] );

        add_action( 'init', [ __CLASS__, 'ewqc_maybe_schedule_cron' ] );
        add_action( self::CRON_HOOK, [ __CLASS__, 'ewqc_cron_check_license' ] );
    }

    /**
     * Add settings page under Settings -> WhatsApp Quick Chat (License)
     */
    public static function ewqc_add_license_page() {
        add_options_page(
            __( 'EWQC License', 'elvira-quick-chat-button' ),
            __( 'EWQC License', 'elvira-quick-chat-button' ),
            'manage_options',
            'ewqc-license',
            [ __CLASS__, 'ewqc_license_page' ]
        );
    }

    /**
     * Render license settings page with activate / deactivate forms
     */
    public static function ewqc_license_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'elvira-quick-chat-button' ) );
        }

        $key    = get_option( self::OPTION_KEY, '' );
        $status = get_option( self::OPTION_STATUS, 'unknown' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Elvira WhatsApp Quick Chat License', 'elvira-quick-chat-button' ); ?></h1>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( self::NONCE_ACTION ); ?>
                <input type="hidden" name="action" value="ewqc_activate_license" />
                <table class="form-table">
                    <tr>
                        <th><label for="ewqc_license_key"><?php esc_html_e( 'License key', 'elvira-quick-chat-button' ); ?></label></th>
                        <td><input name="ewqc_license_key" id="ewqc_license_key" class="regular-text" value="<?php echo esc_attr( $key ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Status', 'elvira-quick-chat-button' ); ?></th>
                        <td><?php echo esc_html( ucfirst( $status ) ); ?></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Activate License', 'elvira-quick-chat-button' ) ); ?>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:15px;">
                <?php wp_nonce_field( self::NONCE_ACTION ); ?>
                <input type="hidden" name="action" value="ewqc_deactivate_license" />
                <?php submit_button( __( 'Deactivate License', 'elvira-quick-chat-button' ), 'secondary' ); ?>
            </form>

            <p class="description">
                <?php esc_html_e( 'This page uses a remote license server to validate your Pro key. The plugin also checks license status hourly via WP-Cron.', 'elvira-quick-chat-button' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Handle activation (POST)
     */
    public static function ewqc_handle_activate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( self::NONCE_ACTION );

        // PHPCS-friendly POST read
        $raw_key = filter_input( INPUT_POST, 'ewqc_license_key', FILTER_UNSAFE_RAW );
        $key     = $raw_key !== null ? sanitize_text_field( wp_unslash( $raw_key ) ) : '';

        update_option( self::OPTION_KEY, $key );

        if ( empty( $key ) ) {
            update_option( self::OPTION_STATUS, 'invalid' );
            self::ewqc_redirect_with_msg( 'missing_key', 'error', 'Please enter a license key.' );
        }

        $payload = [
            'license_key' => $key,
            'domain'      => home_url(),
        ];

        $resp = self::ewqc_remote_post_json( self::$api_verify, $payload );

        if ( is_wp_error( $resp ) ) {
            update_option( self::OPTION_STATUS, 'invalid' );
            self::ewqc_redirect_with_msg( 'network_error', 'error', $resp->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $body = wp_remote_retrieve_body( $resp );
        $json = json_decode( $body, true );

        if ( $code >= 200 && $code < 300 && ! empty( $json ) && ! empty( $json['valid'] ) ) {
            update_option( self::OPTION_STATUS, 'valid' );
            self::ewqc_redirect_with_msg( 'activated', 'success' );
        } else {
            update_option( self::OPTION_STATUS, 'invalid' );
            $msg = 'Invalid license';
            if ( is_array( $json ) && ! empty( $json['message'] ) ) {
                $msg = sanitize_text_field( $json['message'] );
            } elseif ( $body ) {
                $msg = wp_strip_all_tags( wp_trim_words( $body, 20 ) );
            }
            self::ewqc_redirect_with_msg( 'invalid', 'error', $msg );
        }
    }

    /**
     * Handle deactivation (POST)  attempts remote deactivation if endpoint exists
     */
    public static function ewqc_handle_deactivate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( self::NONCE_ACTION );

        $key = get_option( self::OPTION_KEY, '' );

        if ( ! empty( $key ) && ! empty( self::$api_deactivate ) ) {
            $payload = [
                'license_key' => $key,
                'domain'      => home_url(),
            ];
            // attempt remote deactivation; ignore result but report error if necessary
            $resp = self::ewqc_remote_post_json( self::$api_deactivate, $payload );
            if ( is_wp_error( $resp ) ) {
                // proceed with local deactivation but notify user
                update_option( self::OPTION_KEY, '' );
                update_option( self::OPTION_STATUS, 'invalid' );
                self::ewqc_redirect_with_msg( 'deactivated_remote_error', 'error', $resp->get_error_message() );
            }
        }

        // Clear local
        update_option( self::OPTION_KEY, '' );
        update_option( self::OPTION_STATUS, 'invalid' );

        self::ewqc_redirect_with_msg( 'deactivated', 'success' );
    }

    /**
     * Cron scheduler  ensures the hourly event is scheduled.
     */
    public static function ewqc_maybe_schedule_cron() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            // schedule first event in ~1 minute so first check runs soon
            wp_schedule_event( time() + 60, 'hourly', self::CRON_HOOK );
        }
    }

    /**
     * Cron job: check license with remote validate endpoint and update status accordingly.
     */
    public static function ewqc_cron_check_license() {
        $key = get_option( self::OPTION_KEY, '' );
        if ( empty( $key ) ) {
            // nothing to check
            return;
        }

        $payload = [
            'license_key' => $key,
            'domain'      => home_url(),
        ];

        $resp = wp_remote_post( self::$api_verify, [
            'headers'   => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'      => wp_json_encode( $payload ),
            'timeout'   => self::$timeout,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $resp ) ) {
            // network problem  retain current status
            return;
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $json = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( $code >= 200 && $code < 300 && ! empty( $json ) && ! empty( $json['valid'] ) ) {
            update_option( self::OPTION_STATUS, 'valid' );
        } else {
            update_option( self::OPTION_STATUS, 'invalid' );
        }
    }

    /**
     * Helper to perform JSON POST and return response or WP_Error
     */
    private static function ewqc_remote_post_json( $url, $data ) {
        $args = [
            'headers'   => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'      => wp_json_encode( $data ),
            'timeout'   => self::$timeout,
            'sslverify' => true,
        ];

        return wp_remote_post( $url, $args );
    }

    /**
     * Redirect helper with short code and optional extra message
     */
    private static function ewqc_redirect_with_msg( $code = 'ok', $type = 'success', $extra = '' ) {
        $url = add_query_arg( 'ewqc_msg', $code, admin_url( 'options-general.php?page=ewqc-license' ) );
        if ( ! empty( $extra ) ) {
            $url = add_query_arg( 'ewqc_note', rawurlencode( $extra ), $url );
        }
        wp_safe_redirect( $url );
        exit;
    }

    /**
     * Show admin notices on the license settings screen
     */
    public static function ewqc_maybe_show_admin_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // limit notices to settings page for this plugin
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'settings_page_ewqc-license' ) {
            return;
        }

        $raw_msg  = filter_input( INPUT_GET, 'ewqc_msg', FILTER_UNSAFE_RAW );
        $msg      = $raw_msg !== null ? sanitize_text_field( wp_unslash( $raw_msg ) ) : '';

        $raw_note = filter_input( INPUT_GET, 'ewqc_note', FILTER_UNSAFE_RAW );
        $note     = $raw_note !== null ? sanitize_text_field( wp_unslash( $raw_note ) ) : '';

        switch ( $msg ) {
            case 'activated':
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__( 'License activated Pro features enabled.', 'elvira-quick-chat-button' )
                );
                break;

            case 'deactivated':
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__( 'License deactivated.', 'elvira-quick-chat-button' )
                );
                break;

            case 'missing_key':
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__( 'Please enter a license key.', 'elvira-quick-chat-button' )
                );
                break;

            case 'invalid':
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
                    esc_html__( 'License validation failed:', 'elvira-quick-chat-button' ),
                    '<strong>' . esc_html( $note ?: 'Invalid license' ) . '</strong>'
                );
                break;

            case 'network_error':
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
                    esc_html__( 'Network error when contacting license server:', 'elvira-quick-chat-button' ),
                    '<strong>' . esc_html( $note ) . '</strong>'
                );
                break;

            case 'deactivated_remote_error':
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s %s</p></div>',
                    esc_html__( 'Deactivated locally but remote deactivation failed:', 'elvira-quick-chat-button' ),
                    '<strong>' . esc_html( $note ) . '</strong>'
                );
                break;

            default:
                // no notice
                break;
        }
    }

    /**
     * Public: is pro active?
     */
    public static function ewqc_is_pro_active() {
        $status = get_option( self::OPTION_STATUS, 'unknown' );
        return in_array( $status, [ 'valid', 'active' ], true );
    }
}