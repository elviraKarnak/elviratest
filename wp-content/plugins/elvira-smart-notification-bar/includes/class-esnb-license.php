<?php
if (! defined('ABSPATH')) exit;

class ELVISMNO_License {
    private $option_key    = 'elvismno_license_key';
    private $status_key    = 'elvismno_license_status';
    // Point to your validate & deactivate endpoints (use .php direct files if your server doesn't rewrite)
    private $api_verify    = 'https://elvirainfotech.live/license-api/validate.php';
    private $api_deactivate= 'https://elvirainfotech.live/license-api/deactivate.php'; // optional
    private $cron_hook = 'elvismno_hourly_license_check';
    private $timeout       = 20;

    public function __construct() {
        add_action('admin_menu', [$this, 'elvismno_license_menu']);
        add_action('admin_post_elvismno_activate_license', [$this, 'elvismno_handle_activate']);
        add_action('admin_post_elvismno_deactivate_license', [$this, 'elvismno_handle_deactivate']);

        // Show notices saved via query arg
        add_action('admin_notices', [$this, 'elvismno_maybe_show_admin_notice']);

        add_action('init', [$this, 'elvismno_maybe_schedule_cron']);
        add_action($this->cron_hook, [$this, 'elvismno_cron_check_license']);
    }

    public function elvismno_maybe_schedule_cron() {
        if (! wp_next_scheduled($this->cron_hook)) {
            wp_schedule_event(time() + 60, 'hourly', $this->cron_hook);
        }
    }

    public function elvismno_license_menu() {
        add_submenu_page(
            'options-general.php',
            'ESNB License',
            'ESNB License',
            'manage_options',
            'esnb-license',
            [$this, 'elvismno_license_page']
        );
    }

    public function elvismno_license_page() {
        if (! current_user_can('manage_options')) return;
        $key = get_option($this->option_key, '');
        $status = get_option($this->status_key, 'inactive');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Elvira Smart Notification Bar — License', 'elvira-smart-notification-bar' ); ?></h1>
            <p><?php esc_html_e( 'Enter your Pro license key to enable', 'elvira-smart-notification-bar' ); ?> <strong><?php esc_html_e( 'Pro', 'elvira-smart-notification-bar' ); ?></strong> <?php esc_html_e( 'features.', 'elvira-smart-notification-bar' ); ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('elvismno_license_action'); ?>
                <input type="hidden" name="action" value="elvismno_activate_license">
                <table class="form-table">
                    <tr>
                        <th style="width:140px"><?php esc_html_e( 'License Key', 'elvira-smart-notification-bar' ); ?></th>
                        <td>
                          <input type="text" name="elvismno_license_key" value="<?php echo esc_attr($key); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <p><?php esc_html_e( 'Status:', 'elvira-smart-notification-bar' ); ?> <strong><?php echo esc_html(ucfirst($status)); ?></strong></p>
                <?php submit_button('Activate License'); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:15px;">
                <?php wp_nonce_field('elvismno_license_action'); ?>
                <input type="hidden" name="action" value="elvismno_deactivate_license">
                <?php submit_button('Deactivate License', 'secondary'); ?>
            </form>

            <p><strong><?php esc_html_e( 'Note:', 'elvira-smart-notification-bar' ); ?></strong> <?php esc_html_e( 'License status is checked hourly by WP-Cron. You can run the check immediately by clicking Activate again after server-side changes.', 'elvira-smart-notification-bar' ); ?></p>

            <h2 style="margin-top:24px"><?php esc_html_e( 'Pro features', 'elvira-smart-notification-bar' ); ?></h2>
            <ul>
              <li><?php esc_html_e( 'Multiple scheduled bars (Pro)', 'elvira-smart-notification-bar' ); ?></li>
              <li><?php esc_html_e( 'Custom Bar CPT with start/end scheduling (Pro)', 'elvira-smart-notification-bar' ); ?></li>
              <li><?php esc_html_e( 'WooCommerce sale-aware bars (Pro)', 'elvira-smart-notification-bar' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Handle activation form POST
     */
    public function elvismno_handle_activate() {
        if (! current_user_can('manage_options')) wp_die('Unauthorized');

        // Verify nonce (throws if invalid)
        check_admin_referer('elvismno_license_action');

        // Read POST value in PHPCS-friendly manner, then unslash + sanitize
        $raw_key = filter_input( INPUT_POST, 'elvismno_license_key', FILTER_UNSAFE_RAW );
        $key = $raw_key !== null ? sanitize_text_field( wp_unslash( $raw_key ) ) : '';

        update_option($this->option_key, $key);

        if (empty($key)) {
            update_option($this->status_key, 'inactive');
            $this->elvismno_redirect_with_msg('missing_key', 'error');
        }

        // Prepare payload for remote validate
        $payload = [
            'license_key' => $key,
            'domain'      => home_url()
        ];

        $resp = $this->elvismno_remote_post_json($this->api_verify, $payload);

        if (is_wp_error($resp)) {
            // network / WP error
            update_option($this->status_key, 'inactive');
            $this->elvismno_redirect_with_msg('network_error', 'error', $resp->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);

        if ($code >= 200 && $code < 300 && !empty($json) && !empty($json['valid'])) {
            update_option($this->status_key, 'active');
            $this->elvismno_redirect_with_msg('activated', 'success');
        } else {
            update_option($this->status_key, 'inactive');
            // try to extract message
            $msg = 'Invalid license';
            if (is_array($json) && !empty($json['message'])) $msg = sanitize_text_field($json['message']);
            elseif ($body) $msg = wp_strip_all_tags(wp_trim_words($body, 20));
            $this->elvismno_redirect_with_msg('invalid', 'error', $msg);
        }
    }

    /**
     * Handle deactivation form POST (tries remote deactivation if endpoint exists)
     */
    public function elvismno_handle_deactivate() {
        if (! current_user_can('manage_options')) wp_die('Unauthorized');

        // Verify nonce (throws if invalid)
        check_admin_referer('elvismno_license_action');

        // We don't expect a key here (it lives in options), but still defensively read from POST if provided.
        $raw_key = filter_input( INPUT_POST, 'elvismno_license_key', FILTER_UNSAFE_RAW );
        $posted_key = $raw_key !== null ? sanitize_text_field( wp_unslash( $raw_key ) ) : '';

        $key = get_option($this->option_key, '');
        // If a posted key exists and doesn't match stored key, ignore it — we rely on stored option
        // Attempt remote deactivation if API available
        if (!empty($key) && !empty($this->api_deactivate)) {
            $payload = [
                'license_key' => $key,
                'domain'      => home_url()
            ];
            $resp = $this->elvismno_remote_post_json($this->api_deactivate, $payload);
            // ignore remote result for now, but you could check for errors
        }

        // Clear local
        update_option($this->option_key, '');
        update_option($this->status_key, 'inactive');
        $this->elvismno_redirect_with_msg('deactivated', 'success');
    }

    /**
     * Utility: perform JSON POST and return wp_remote_post response or WP_Error
     */
    private function elvismno_remote_post_json($url, $data) {
        $args = [
            'headers'     => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ],
            'body'        => wp_json_encode($data),
            'timeout'     => $this->timeout,
            'sslverify'   => true
        ];

        return wp_remote_post($url, $args);
    }

    /**
     * Redirect helper with short code and optional extra message
     */
    private function elvismno_redirect_with_msg($code = 'ok', $type = 'success', $extra = '') {
        $url = add_query_arg('elvismno_msg', $code, admin_url('options-general.php?page=esnb-license'));
        if (!empty($extra)) $url = add_query_arg('elvismno_note', rawurlencode($extra), $url);
        wp_safe_redirect($url);
        exit;
    }

    /**
     * Show admin notices based on elvismno_msg query var
     */
    public function elvismno_maybe_show_admin_notice() {
        if (! current_user_can('manage_options')) return;
        //if (empty($_GET['page']) || $_GET['page'] !== 'esnb-license') return;

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->id !== 'settings_page_esnb-license' ) {
            return;
        }

        // PHPCS-friendly GET reading + sanitization
        $raw_msg  = filter_input( INPUT_GET, 'elvismno_msg', FILTER_UNSAFE_RAW );
        $msg      = $raw_msg !== null ? sanitize_text_field( wp_unslash( $raw_msg ) ) : '';

        $raw_note = filter_input( INPUT_GET, 'elvismno_note', FILTER_UNSAFE_RAW );
        $note     = $raw_note !== null ? sanitize_text_field( wp_unslash( $raw_note ) ) : '';

        switch ($msg) {
            case 'activated':
                printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('License activated — Pro features enabled.', 'elvira-smart-notification-bar'));
                break;
            case 'deactivated':
                printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html__('License deactivated.', 'elvira-smart-notification-bar'));
                break;
            case 'missing_key':
                printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('Please enter a license key.', 'elvira-smart-notification-bar'));
                break;
            case 'invalid':
                printf('<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
                    esc_html__('License validation failed:', 'elvira-smart-notification-bar'),
                    '<strong>' . esc_html($note ?: 'Invalid license') . '</strong>'
                );
                break;
            case 'network_error':
                printf('<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
                    esc_html__('Network error when contacting license server:', 'elvira-smart-notification-bar'),
                    '<strong>' . esc_html($note) . '</strong>'
                );
                break;
            case 'error':
                printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__('An error occurred.', 'elvira-smart-notification-bar'));
                break;
            default:
                // no notice
                break;
        }
    }

    // Cron job to validate license status against remote server
    public function elvismno_cron_check_license() {
        $key = get_option($this->option_key, '');
        if (empty($key)) return;
        $payload = ['license_key' => $key, 'domain' => home_url()];
        $resp = wp_remote_post($this->api_verify, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($payload),
            'timeout' => $this->timeout,
            'sslverify' => true
        ]);
        if (is_wp_error($resp)) {
            // network problem — do nothing (retain current status)
            return;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if ($code >=200 && $code < 300 && !empty($json['valid'])) {
            update_option($this->status_key, 'active');
        } else {
            update_option($this->status_key, 'inactive');
        }
    }

    /**
     * Check if pro is active
     */
    public function elvismno_is_pro_active() {
        return get_option($this->status_key) === 'active';
    }
}
