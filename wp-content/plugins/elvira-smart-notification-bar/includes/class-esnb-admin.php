<?php
if (!defined('ABSPATH')) exit;

class ELVISMNO_Admin {
    private $option_key = 'elvismno_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'elvismno_menu']);
        add_action('admin_init', [$this, 'elvismno_register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'elvismno_enqueue_admin_assets']);
    }

    public function elvismno_menu() {
        add_options_page('Elvira Smart Notification Bar', 'Elvira Smart Notification Bar', 'manage_options', 'esnb-settings', [$this, 'elvismno_settings_page']);
    }

    public function elvismno_register_settings() {
        register_setting(
            'elvismno_settings_group',
            $this->option_key,
            array(
                'sanitize_callback' => array( $this, 'elvismno_sanitize_settings' ),
                'default' => array() // optional default
            )
        );
    }

    /**
     * Sanitization callback for elvismno_settings.
     *
     * @param array $input Raw input from the form.
     * @return array Sanitized values to be stored.
     */
    public function elvismno_sanitize_settings( $input ) {
        $sanitized = array();

        // If not an array, return empty array (defensive)
        if ( ! is_array( $input ) ) {
            return $sanitized;
        }

        // Enabled (checkbox)
        $sanitized['enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;

        // Display page - whitelist allowed values
        $allowed_pages = array( 'all', 'home', 'cart', 'checkout' );
        $display_page = isset( $input['display_page'] ) ? sanitize_text_field( $input['display_page'] ) : 'all';
        $sanitized['display_page'] = in_array( $display_page, $allowed_pages, true ) ? $display_page : 'all';

        // Message (WYSIWYG) - allow post HTML via wp_kses_post
        if ( isset( $input['message'] ) ) {
            $sanitized['message'] = wp_kses_post( $input['message'] );
        } else {
            $sanitized['message'] = '';
        }

        // Countdown enabled (pro flag)
        $sanitized['countdown_enabled'] = ! empty( $input['countdown_enabled'] ) ? 1 : 0;

        // Countdown end - accept datetime-local format from form and store as MySQL-like 'Y-m-d H:i:s'
        if ( ! empty( $input['countdown_end'] ) ) {
            // Typical datetime-local value: "2025-10-14T18:30" or it could be a saved MySQL-like "2025-10-14 18:30:00"
            $dt_raw = trim( $input['countdown_end'] );
            $dt_raw = str_replace( 'T', ' ', $dt_raw ); // convert HTML5 to space
            // try to parse
            $ts = strtotime( $dt_raw );
            if ( $ts !== false ) {
                // store in UTC-like MySQL format (no timezone conversion here)
                $sanitized['countdown_end'] = gmdate( 'Y-m-d H:i:s', $ts );
            } else {
                // invalid value - drop it
                $sanitized['countdown_end'] = '';
            }
        } else {
            $sanitized['countdown_end'] = '';
        }

        // Button text - plain text
        if ( isset( $input['btn_text'] ) ) {
            $sanitized['btn_text'] = sanitize_text_field( $input['btn_text'] );
        } else {
            $sanitized['btn_text'] = '';
        }

        // Button link - store safe URL
        if ( isset( $input['btn_link'] ) ) {
            $sanitized['btn_link'] = esc_url_raw( $input['btn_link'] );
        } else {
            $sanitized['btn_link'] = '';
        }

        // Background color & text color - sanitize_hex_color returns null if invalid
        if ( isset( $input['bg_color'] ) ) {
            $bg = sanitize_hex_color( $input['bg_color'] );
            $sanitized['bg_color'] = $bg ? $bg : '#000000';
        } else {
            $sanitized['bg_color'] = '#000000';
        }

        if ( isset( $input['text_color'] ) ) {
            $tc = sanitize_hex_color( $input['text_color'] );
            $sanitized['text_color'] = $tc ? $tc : '#ffffff';
        } else {
            $sanitized['text_color'] = '#ffffff';
        }

        // Position - whitelist
        $allowed_positions = array( 'top', 'bottom' );
        $position = isset( $input['position'] ) ? sanitize_text_field( $input['position'] ) : 'top';
        $sanitized['position'] = in_array( $position, $allowed_positions, true ) ? $position : 'top';

        // Dismiss - checkbox
        $sanitized['dismiss'] = ! empty( $input['dismiss'] ) ? 1 : 0;

        // Any other keys you plan to add should be sanitised here similarly.

        /**
         * Filter to allow other code to validate/modify sanitized settings before saving.
         * Example: add_filter('elvismno_sanitize_settings', function($sanitized, $input){ ... }, 10, 2);
         */
        $sanitized = apply_filters( 'elvismno_sanitize_settings', $sanitized, $input );

        return $sanitized;
    }

    public function elvismno_enqueue_admin_assets($hook) {
        // Only enqueue on our settings page
        if (strpos($hook, 'esnb-settings') === false && strpos($hook, 'settings_page_esnb-settings') === false) return;
        // Load local Flatpickr files instead of remote CDN
        wp_enqueue_style(
            'elvismno-flatpickr-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_enqueue_script(
            'elvismno-flatpickr-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
        wp_add_inline_script('flatpickr-js', "document.addEventListener('DOMContentLoaded', function(){ if(window.flatpickr){ flatpickr('.esnb-datetime', {enableTime:true, dateFormat:'Y-m-d H:i', altInput:true, altFormat:'F j, Y H:i'}); } });");
    }

    public function elvismno_settings_page() {
        if (!current_user_can('manage_options')) return;

        $opts = get_option($this->option_key, []);
        $is_pro = get_option('elvismno_license_status') === 'active';
        // Prepare a CSS class to blur/disable pro fields when inactive
        $pro_locked_class = $is_pro ? '' : 'esnb-pro-locked';

        $value = $opts['countdown_end'] ?? '';

        // Convert stored datetime (MySQL-like) to HTML5 datetime-local (Y-m-d\\TH:i)
        $dt_val = '';
        if (!empty($value)) {
            $ts = strtotime($value);
            if ($ts !== false) $dt_val = gmdate('Y-m-d\\TH:i', $ts);
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Elvira Smart Notification Bar', 'elvira-smart-notification-bar' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('elvismno_settings_group'); ?>

                <table class="form-table">
                    <tr><th><?php esc_html_e( 'Enable', 'elvira-smart-notification-bar' ); ?></th><td><input type="checkbox" name="elvismno_settings[enabled]" value="1" <?php checked(1, $opts['enabled'] ?? 0); ?>></td></tr>

                    <tr><th><?php esc_html_e( 'Show On', 'elvira-smart-notification-bar' ); ?></th>
                      <td>
                        <select name="elvismno_settings[display_page]">
                          <option value="all" <?php selected('all', $opts['display_page'] ?? 'all'); ?>>All Pages</option>
                          <option value="home" <?php selected('home', $opts['display_page'] ?? ''); ?>>Home Page</option>
                          <option value="cart" <?php selected('cart', $opts['display_page'] ?? ''); ?>>Cart Page</option>
                          <option value="checkout" <?php selected('checkout', $opts['display_page'] ?? ''); ?>>Checkout Page</option>
                        </select>
                      </td>
                    </tr>

                    <tr>
                      <th><?php esc_html_e( 'Message', 'elvira-smart-notification-bar' ); ?></th>
                      <td>
                        <?php
                        // Use WP editor (WYSIWYG)
                        $content = $opts['message'] ?? 'Free Shipping on orders over $50!';
                        wp_editor( $content, 'elvismno_settings_message', [
                          'textarea_name' => 'elvismno_settings[message]',
                          'media_buttons' => false,
                          'teeny' => true,
                          'textarea_rows' => 6,
                        ]);
                        ?>
                        <p class="description"><?php esc_html_e( 'Use the editor to format the announcement message.', 'elvira-smart-notification-bar' ); ?></p>
                      </td>
                    </tr>

                    <tr class="<?php echo esc_attr($pro_locked_class) ?>">
                      <th><?php esc_html_e( 'Countdown Timer (Pro)', 'elvira-smart-notification-bar' ); ?></th>
                      <td>
                        <input type="checkbox" name="elvismno_settings[countdown_enabled]" value="1" <?php checked(1, $opts['countdown_enabled'] ?? 0); ?> <?php disabled(!$is_pro); ?>>
                        <small><?php esc_html_e( 'Pro: Shows countdown', 'elvira-smart-notification-bar' ); ?></small><br>
                        <label><?php esc_html_e( 'End Time:', 'elvira-smart-notification-bar' ); ?></label>
                        <input type="datetime-local" name="elvismno_settings[countdown_end]" class="regular-text esnb-datetime" value="<?php echo esc_attr($dt_val); ?>" <?php disabled(!$is_pro); ?>>
                        <?php if (!$is_pro): ?>
                          <p class="description"><?php esc_html_e( 'Pro feature — activate your license to enable.', 'elvira-smart-notification-bar' ); ?></p>
                        <?php endif; ?>
                      </td>
                    </tr>

                    <tr><th><?php esc_html_e( 'Button Text', 'elvira-smart-notification-bar' ); ?></th><td><input type="text" name="elvismno_settings[btn_text]" value="<?php echo esc_attr($opts['btn_text'] ?? 'Shop Now'); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Button Link', 'elvira-smart-notification-bar' ); ?></th><td><input type="url" name="elvismno_settings[btn_link]" value="<?php echo esc_attr($opts['btn_link'] ?? '#'); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Background Color', 'elvira-smart-notification-bar' ); ?></th><td><input type="color" name="elvismno_settings[bg_color]" value="<?php echo esc_attr($opts['bg_color'] ?? '#000'); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Text Color', 'elvira-smart-notification-bar' ); ?></th><td><input type="color" name="elvismno_settings[text_color]" value="<?php echo esc_attr($opts['text_color'] ?? '#fff'); ?>"></td></tr>
                    <tr><th><?php esc_html_e( 'Position', 'elvira-smart-notification-bar' ); ?></th>
                      <td>
                        <select name="elvismno_settings[position]">
                          <option value="top" <?php selected('top', $opts['position'] ?? 'top'); ?>><?php esc_html_e( 'Top', 'elvira-smart-notification-bar' ); ?></option>
                          <option value="bottom" <?php selected('bottom', $opts['position'] ?? 'bottom'); ?>><?php esc_html_e( 'Bottom', 'elvira-smart-notification-bar' ); ?></option>
                        </select>
                      </td>
                    </tr>
                    <tr><th>Dismiss Option</th><td><input type="checkbox" name="elvismno_settings[dismiss]" value="1" <?php checked(1, $opts['dismiss'] ?? 0); ?>></td></tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2 style="margin-top:30px;">Pro Features</h2>

            <div class="esnb-pro-panel <?php echo esc_attr($pro_locked_class) ?>" style="position:relative;padding:15px;border:1px solid #ddd;border-radius:6px;">
              <?php if (!$is_pro): ?>
                <div class="esnb-pro-overlay">
                  <p style="margin:0;padding:10px 15px;background:rgba(255,255,255,0.85);position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                    <strong><?php esc_html_e( 'Elvira Smart Notification Bar Pro', 'elvira-smart-notification-bar' ); ?></strong>
                    <p style="margin:6px 0 0"><?php esc_html_e( 'Multiple scheduled bars, countdown per bar, and WooCommerce-aware bars.', 'elvira-smart-notification-bar' ); ?></p>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=esnb-license')); ?>" class="button button-primary" style="margin-top:8px;">Activate License</a>
                  </p>
                </div>
              <?php endif; ?>

              <p style="margin:0 0 8px;"><strong><?php esc_html_e( 'Multiple Scheduled Bars (Pro)', 'elvira-smart-notification-bar' ); ?></strong></p>
              <p class="description"><?php esc_html_e( 'Create multiple announcement bars, schedule start & end times, and show different bars on different pages.', 'elvira-smart-notification-bar' ); ?></p>
              <p style="margin-top:12px;">
                <a class="button" href="<?php echo $is_pro ? esc_url(admin_url('edit.php?post_type=elvismno_bar')) : '#'; ?>" <?php if (!$is_pro) echo 'onclick="return false;"'; ?>><?php esc_html_e( 'Open Smart Bars Manager', 'elvira-smart-notification-bar' ); ?></a>
                <?php if (!$is_pro): ?><span style="margin-left:10px;color:#666;"><?php esc_html_e( '(Locked)', 'elvira-smart-notification-bar' ); ?></span><?php endif; ?>
              </p>
            </div>

        </div>

        <style>
        /* Local styles to show disabled/pro locked area in admin */
        .esnb-pro-locked { opacity: 0.6; pointer-events: none; position: relative; }
        .esnb-pro-overlay { pointer-events: auto; }
        </style>

        <?php
    }
}
