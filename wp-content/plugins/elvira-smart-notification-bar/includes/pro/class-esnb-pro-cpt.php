<?php
if (!defined('ABSPATH')) exit;

class ELVISMNO_Pro_CPT {
    public function __construct() {
        add_action( 'init', [ $this, 'elvismno_register_cpt' ] );

        // Use post-type-specific hooks to limit scope
        add_action( 'add_meta_boxes_elvismno_bar', [ $this, 'elvismno_add_meta_boxes' ] );
        add_action( 'save_post_elvismno_bar',   [ $this, 'elvismno_save_meta' ] );

        // ensure wp_editor assets are available
        add_action( 'admin_enqueue_scripts', [ $this, 'elvismno_enqueue_admin_assets' ] );
    }

    public function elvismno_enqueue_admin_assets() {
        wp_enqueue_editor();
        wp_enqueue_style(
            'elvismno-flatpickr-css',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_enqueue_script(
            'elvismno-flatpickr-js',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
        wp_add_inline_script('elvismno-flatpickr-js', "document.addEventListener('DOMContentLoaded', function(){ if(window.flatpickr){ flatpickr('.esnb-datetime', {enableTime:true, dateFormat:'Y-m-d H:i', altInput:true, altFormat:'F j, Y H:i'}); } });");
    }

    public function elvismno_register_cpt() {
        register_post_type('elvismno_bar', [
            'labels' => ['name' => __('Smart Bars','elvira-smart-notification-bar'),'singular_name' => __('Smart Bar','elvira-smart-notification-bar')],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title'],
        ]);
    }

    public function elvismno_add_meta_boxes() {
        add_meta_box('elvismno_bar_meta', 'Bar Settings', [$this, 'elvismno_render_meta_box'], 'elvismno_bar', 'normal', 'high');
    }

    public function elvismno_render_meta_box($post) {
        $fields = get_post_meta($post->ID, '_elvismno_fields', true) ?: [];
        // Use wp_editor for message so admin has full WYSIWYG

        $value = $fields['start_date'] ?? '';
        $dt_val = '';
        if (!empty($value)) {
            $ts = strtotime($value);
            if ($ts !== false) $dt_val = gmdate('Y-m-d\\TH:i', $ts);
        }

        $value2 = $fields['end_date'] ?? '';
        $dt_val2 = '';
        if (!empty($value2)) {
            $ts = strtotime($value2);
            if ($ts !== false) $dt_val2 = gmdate('Y-m-d\\TH:i', $ts);
        }
        wp_nonce_field( 'elvismno_bar_meta_save', 'elvismno_bar_meta_nonce' );
        ?>
        <table class="form-table">
          <tr><th><?php esc_html_e( 'Message', 'elvira-smart-notification-bar' ); ?></th>
            <td>
              <?php
                $content = $fields['message'] ?? '';
                wp_editor($content, 'elvismno_bar_message_' . (int)$post->ID, [
                  'textarea_name' => 'elvismno_fields[message]',
                  'media_buttons' => false,
                  'textarea_rows' => 6,
                  'teeny' => false,
                ]);
              ?>
            </td>
          </tr>

          <tr><th><?php esc_html_e( 'Button Text', 'elvira-smart-notification-bar' ); ?></th><td><input type="text" name="elvismno_fields[btn_text]" value="<?php echo esc_attr($fields['btn_text'] ?? ''); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'Button URL', 'elvira-smart-notification-bar' ); ?></th><td><input type="url" name="elvismno_fields[btn_link]" value="<?php echo esc_attr($fields['btn_link'] ?? ''); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'Background Color', 'elvira-smart-notification-bar' ); ?></th><td><input type="color" name="elvismno_fields[bg_color]" value="<?php echo esc_attr($fields['bg_color'] ?? '#000'); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'Text Color', 'elvira-smart-notification-bar' ); ?></th><td><input type="color" name="elvismno_fields[text_color]" value="<?php echo esc_attr($fields['text_color'] ?? '#fff'); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'Position', 'elvira-smart-notification-bar' ); ?></th><td>
            <select name="elvismno_fields[position]">
              <option value="top" <?php selected('top', $fields['position'] ?? 'top'); ?>><?php esc_html_e( 'Top', 'elvira-smart-notification-bar' ); ?></option>
              <option value="bottom" <?php selected('bottom', $fields['position'] ?? 'bottom'); ?>><?php esc_html_e( 'Bottom', 'elvira-smart-notification-bar' ); ?></option>
            </select>
          </td></tr>
          <tr><th><?php esc_html_e( 'Show On', 'elvira-smart-notification-bar' ); ?></th><td>
            <select name="elvismno_fields[display_page]">
              <option value="all" <?php selected('all', $fields['display_page'] ?? 'all'); ?>><?php esc_html_e( 'All Pages', 'elvira-smart-notification-bar' ); ?></option>
              <option value="home" <?php selected('home', $fields['display_page'] ?? ''); ?>><?php esc_html_e( 'Home', 'elvira-smart-notification-bar' ); ?></option>
              <option value="cart" <?php selected('cart', $fields['display_page'] ?? ''); ?>><?php esc_html_e( 'Cart', 'elvira-smart-notification-bar' ); ?></option>
              <option value="checkout" <?php selected('checkout', $fields['display_page'] ?? ''); ?>><?php esc_html_e( 'Checkout', 'elvira-smart-notification-bar' ); ?></option>
            </select>
          </td></tr>
          <tr><th><?php esc_html_e( 'Start Date', 'elvira-smart-notification-bar' ); ?></th><td><input type="datetime-local" name="elvismno_fields[start_date]" class="regular-text esnb-datetime" value="<?php echo esc_attr($dt_val); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'End Date', 'elvira-smart-notification-bar' ); ?></th><td><input type="datetime-local" name="elvismno_fields[end_date]" class="regular-text esnb-datetime" value="<?php echo esc_attr($dt_val2); ?>"></td></tr>
          <tr><th><?php esc_html_e( 'Enable Countdown', 'elvira-smart-notification-bar' ); ?></th><td><input type="checkbox" name="elvismno_fields[countdown]" value="1" <?php checked(1, $fields['countdown'] ?? 0); ?>></td></tr>
        </table>
        <?php
    }

    public function elvismno_save_meta($post_id) {
        // Basic autosave/revision checks
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Verify nonce is present and valid (unslash + sanitize before verifying)
        $nonce = isset( $_POST['elvismno_bar_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['elvismno_bar_meta_nonce'] ) ) : '';

        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'elvismno_bar_meta_save' ) ) {
            return;
        }

        // Check post type is our CPT
        $post_type = get_post_type( $post_id );
        if ( 'elvismno_bar' !== $post_type ) {
            return;
        }

        // Capability check
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        /**
         * Read $_POST['elvismno_fields'] safely:
         * - We call wp_unslash() once into $raw (so PHPCS won't flag direct $_POST usage),
         * - then ensure it's an array before processing.
         */
        $raw = isset( $_POST['elvismno_fields'] )
    ? wp_unslash( filter_input( INPUT_POST, 'elvismno_fields', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) )
    : null;


        if ( is_array( $raw ) ) {
            $sanitized = [];

            // Message - allow safe post HTML
            if ( isset( $raw['message'] ) ) {
                $sanitized['message'] = wp_kses_post( $raw['message'] );
            } else {
                $sanitized['message'] = '';
            }

            // Button text
            if ( isset( $raw['btn_text'] ) ) {
                $sanitized['btn_text'] = sanitize_text_field( $raw['btn_text'] );
            } else {
                $sanitized['btn_text'] = '';
            }

            // Button link (URL)
            if ( isset( $raw['btn_link'] ) ) {
                $sanitized['btn_link'] = esc_url_raw( $raw['btn_link'] );
            } else {
                $sanitized['btn_link'] = '';
            }

            // Colors
            if ( isset( $raw['bg_color'] ) ) {
                $bg = sanitize_hex_color( $raw['bg_color'] );
                $sanitized['bg_color'] = $bg ? $bg : '#000000';
            } else {
                $sanitized['bg_color'] = '#000000';
            }

            if ( isset( $raw['text_color'] ) ) {
                $tc = sanitize_hex_color( $raw['text_color'] );
                $sanitized['text_color'] = $tc ? $tc : '#ffffff';
            } else {
                $sanitized['text_color'] = '#ffffff';
            }

            // Position - whitelist
            $allowed_positions = [ 'top', 'bottom' ];
            $pos = isset( $raw['position'] ) ? sanitize_text_field( $raw['position'] ) : 'top';
            $sanitized['position'] = in_array( $pos, $allowed_positions, true ) ? $pos : 'top';

            // Display page - whitelist
            $allowed_pages = [ 'all', 'home', 'cart', 'checkout' ];
            $dp = isset( $raw['display_page'] ) ? sanitize_text_field( $raw['display_page'] ) : 'all';
            $sanitized['display_page'] = in_array( $dp, $allowed_pages, true ) ? $dp : 'all';

            // Datetimes - accept HTML5 datetime-local (e.g. "2025-10-14T18:30") or MySQL-like string
            $sanitized['start_date'] = '';
            if ( ! empty( $raw['start_date'] ) ) {
                $d = str_replace( 'T', ' ', sanitize_text_field( $raw['start_date'] ) );
                $ts = strtotime( $d );
                if ( $ts !== false ) {
                    $sanitized['start_date'] = gmdate( 'Y-m-d H:i:s', $ts );
                }
            }

            $sanitized['end_date'] = '';
            if ( ! empty( $raw['end_date'] ) ) {
                $d2 = str_replace( 'T', ' ', sanitize_text_field( $raw['end_date'] ) );
                $ts2 = strtotime( $d2 );
                if ( $ts2 !== false ) {
                    $sanitized['end_date'] = gmdate( 'Y-m-d H:i:s', $ts2 );
                }
            }

            // Countdown checkbox
            $sanitized['countdown'] = ! empty( $raw['countdown'] ) ? 1 : 0;

            // Allow other plugins/themes to filter/validate final sanitized array
            $sanitized = apply_filters( 'elvismno_pro_bar_sanitize_meta', $sanitized, $raw, $post_id );

            // Save sanitized meta
            update_post_meta( $post_id, '_elvismno_fields', $sanitized );
        }
    }
}
