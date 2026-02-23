<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Custom styling & icon options for WhatsApp button (Pro)
 *
 * - Renders admin UI for icon + style
 * - Provides a Save button (posts to admin-post.php) and server-side handler
 * - Prints inline front-end CSS based on saved settings
 *
 * IMPORTANT:
 * 1) require_once this file in EWQC_Pro::init().
 * 2) after the require_once call, add: EWQC_Custom_Style::init();
 * 3) render the settings where you want: EWQC_Custom_Style::ewqc_render_settings();
 */
class EWQC_Custom_Style {

    /** Option name where custom-style is stored (separate from ewqc_settings) */
    const OPTION_NAME = 'ewqc_custom_style';

    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin assets (loads WP media + color picker)
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ewqc_admin_enqueue' ) );

        // Handle form POST for saving custom style (logged-in users)
        add_action( 'admin_post_ewqc_save_custom_style', array( __CLASS__, 'ewqc_handle_save_custom_style' ) );

        // Frontend: print inline styles to apply customizations
        add_action( 'wp_head', array( __CLASS__, 'ewqc_print_custom_styles' ), 20 );

        // Show admin notice after save
        add_action( 'admin_notices', array( __CLASS__, 'ewqc_admin_notices' ) );
    }

    /**
     * Enqueue admin scripts/styles (media + color picker)
     */
    public static function ewqc_admin_enqueue( $hook = '' ) {
        // OPTIONAL: restrict to plugin settings page by checking $hook if desired
        // if ( 'toplevel_page_ewqc-settings' !== $hook ) return;

        // WP media for uploader
        wp_enqueue_media();

        // WP color picker
        wp_enqueue_style( 'wp-color-picker' );

        // Enqueue your admin JS which depends on wp-color-picker and jquery
        wp_enqueue_script(
            'ewqc-custom-style-admin',
            EWQC_PLUGIN_URL . 'assets/js/ewqc-custom-style-admin.js', // create this file (see below)
            array( 'wp-color-picker', 'jquery' ),
            defined( 'EWQC_VERSION' ) ? EWQC_VERSION : false,
            true
        );

        // If you need any localized strings/values
        wp_localize_script( 'ewqc-custom-style-admin', 'ewqcCustom', array(
            'uploadTitle' => __( 'Select or upload an icon', 'elvira-quick-chat-button' ),
            'uploadButton' => __( 'Use this icon', 'elvira-quick-chat-button' ),
        ) );
    }

    /**
     * Default values for the custom-style subset
     *
     * @return array
     */
    public static function ewqc_defaults() {
        return array(
            'button_icon_type'       => 'default', // default|chat-bubble|paper-plane|custom
            'button_icon_custom_url' => '',
            'button_bg_color'        => '#25D366',
            'button_text_color'      => '#ffffff',
            'button_icon_color'      => '',
            'button_size'            => '56',
            'button_border_radius'   => '50',
            'button_animation'       => 'none', // none|pulse|bounce (optional)
            'button_position_override' => '',   // '', 'bottom-right', 'bottom-left', 'top-right', 'top-left'
        );
    }

    /**
     * Sanitize ONLY the custom-style subset.
     * Returns only the subset (not full global settings).
     *
     * @param array $custom Partial array (subset) to sanitize.
     * @return array Sanitized subset
     */
    public static function ewqc_sanitize_custom_settings( $custom ) {
        if ( ! is_array( $custom ) ) {
            $custom = array();
        }

        $defaults = self::ewqc_defaults();
        $custom = array_merge( $defaults, $custom );

        $custom['button_icon_type'] = in_array( $custom['button_icon_type'], array( 'default', 'chat-bubble', 'paper-plane', 'custom' ), true ) ? $custom['button_icon_type'] : $defaults['button_icon_type'];
        $custom['button_icon_custom_url'] = isset( $custom['button_icon_custom_url'] ) ? esc_url_raw( $custom['button_icon_custom_url'] ) : '';
        $custom['button_bg_color'] = isset( $custom['button_bg_color'] ) ? sanitize_hex_color( $custom['button_bg_color'] ) : $defaults['button_bg_color'];
        $custom['button_text_color'] = isset( $custom['button_text_color'] ) ? sanitize_hex_color( $custom['button_text_color'] ) : $defaults['button_text_color'];
        $custom['button_icon_color'] = isset( $custom['button_icon_color'] ) ? sanitize_hex_color( $custom['button_icon_color'] ) : '';
        $custom['button_size'] = isset( $custom['button_size'] ) ? intval( $custom['button_size'] ) : intval( $defaults['button_size'] );
        $custom['button_border_radius'] = isset( $custom['button_border_radius'] ) ? intval( $custom['button_border_radius'] ) : intval( $defaults['button_border_radius'] );
        $custom['button_animation'] = in_array( $custom['button_animation'], array( 'none', 'pulse', 'bounce' ), true ) ? $custom['button_animation'] : $defaults['button_animation'];
        $custom['button_position_override'] = in_array( $custom['button_position_override'], array( '', 'bottom-right', 'bottom-left', 'top-right', 'top-left' ), true ) ? $custom['button_position_override'] : $defaults['button_position_override'];

        return $custom;
    }

    /**
     * Render admin settings UI (includes own form + Save button).
     * Call this from EWQC_Pro::ewqc_render_pro_settings() before analytics.
     */
    public static function ewqc_render_settings() {
        // Ensure media & color picker scripts/styles loaded
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Merge global + custom for display (custom overrides global)
        $global = get_option( 'ewqc_settings', array() );
        $custom = get_option( self::OPTION_NAME, array() );
        $merged = array_merge( (array) $global, (array) $custom );
        $display = self::ewqc_sanitize_custom_settings( $merged );

        $icon_type = $display['button_icon_type'];
        $custom_icon = $display['button_icon_custom_url'];
        ?>
        <h2><?php esc_html_e( 'Custom Styling (Pro)', 'elvira-quick-chat-button' ); ?></h2>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ewqc_save_custom_style" />
            <?php wp_nonce_field( 'ewqc_save_custom_style_nonce', 'ewqc_save_custom_style_nonce_field' ); ?>

            <table class="form-table ewqc-custom-style-table">
                <tr>
                    <th><?php esc_html_e( 'Button Icon', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <label><input type="radio" name="ewqc_settings[button_icon_type]" value="default" <?php checked( $icon_type, 'default' ); ?> /> <?php esc_html_e( 'Default', 'elvira-quick-chat-button' ); ?></label><br>
                        <label><input type="radio" name="ewqc_settings[button_icon_type]" value="chat-bubble" <?php checked( $icon_type, 'chat-bubble' ); ?> /> <?php esc_html_e( 'Chat bubble', 'elvira-quick-chat-button' ); ?></label><br>
                        <label><input type="radio" name="ewqc_settings[button_icon_type]" value="paper-plane" <?php checked( $icon_type, 'paper-plane' ); ?> /> <?php esc_html_e( 'Paper plane', 'elvira-quick-chat-button' ); ?></label><br>
                        <label><input type="radio" name="ewqc_settings[button_icon_type]" value="custom" <?php checked( $icon_type, 'custom' ); ?> /> <?php esc_html_e( 'Custom image', 'elvira-quick-chat-button' ); ?></label>
                    </td>
                </tr>

                <tr class="ewqc-custom-icon-row" <?php if ( 'custom' !== $icon_type ) echo 'style="display:none"'; ?>>
                    <th><?php esc_html_e( 'Custom Icon', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <input type="text" name="ewqc_settings[button_icon_custom_url]" id="ewqc_custom_icon_url" value="<?php echo esc_attr( $custom_icon ); ?>" class="regular-text" />
                        <input type="button" id="ewqc_upload_icon_button" class="button-secondary" value="<?php esc_attr_e( 'Upload / Select Image', 'elvira-quick-chat-button' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Upload a PNG, SVG, or WebP icon (recommended 64×64).', 'elvira-quick-chat-button' ); ?></p>
                        <?php if ( ! empty( $custom_icon ) ) : ?>
                            <p><img src="<?php echo esc_url( $custom_icon ); ?>" style="max-width:64px;max-height:64px;border:1px solid #ddd;" alt="<?php esc_attr_e( 'Custom icon', 'elvira-quick-chat-button' ); ?>" /></p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Button Colors', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <label><?php esc_html_e( 'Background', 'elvira-quick-chat-button' ); ?>: <input type="text" name="ewqc_settings[button_bg_color]" value="<?php echo esc_attr( $display['button_bg_color'] ); ?>" class="ewqc-color-field" /></label><br>
                        <label><?php esc_html_e( 'Text', 'elvira-quick-chat-button' ); ?>: <input type="text" name="ewqc_settings[button_text_color]" value="<?php echo esc_attr( $display['button_text_color'] ); ?>" class="ewqc-color-field" /></label><br>
                        <label><?php esc_html_e( 'Icon Color (optional)', 'elvira-quick-chat-button' ); ?>: <input type="text" name="ewqc_settings[button_icon_color]" value="<?php echo esc_attr( $display['button_icon_color'] ); ?>" class="ewqc-color-field" /></label>
                        <p class="description"><?php esc_html_e( 'If left empty, icon will inherit the text color.', 'elvira-quick-chat-button' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Size & Shape', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <label><?php esc_html_e( 'Button size (px)', 'elvira-quick-chat-button' ); ?>: <input type="number" min="24" max="200" name="ewqc_settings[button_size]" value="<?php echo esc_attr( $display['button_size'] ); ?>" style="width:80px" /></label><br>
                        <label><?php esc_html_e( 'Border radius (px)', 'elvira-quick-chat-button' ); ?>: <input type="number" min="0" max="200" name="ewqc_settings[button_border_radius]" value="<?php echo esc_attr( $display['button_border_radius'] ); ?>" style="width:80px" /></label>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Animation', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <select name="ewqc_settings[button_animation]">
                            <option value="none" <?php selected( $display['button_animation'], 'none' ); ?>><?php esc_html_e( 'None', 'elvira-quick-chat-button' ); ?></option>
                            <option value="pulse" <?php selected( $display['button_animation'], 'pulse' ); ?>><?php esc_html_e( 'Pulse', 'elvira-quick-chat-button' ); ?></option>
                            <option value="bounce" <?php selected( $display['button_animation'], 'bounce' ); ?>><?php esc_html_e( 'Bounce', 'elvira-quick-chat-button' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e( 'Position Override', 'elvira-quick-chat-button' ); ?></th>
                    <td>
                        <select name="ewqc_settings[button_position_override]">
                            <option value="" <?php selected( $display['button_position_override'], '' ); ?>><?php esc_html_e( 'Use plugin default', 'elvira-quick-chat-button' ); ?></option>
                            <option value="bottom-right" <?php selected( $display['button_position_override'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom right', 'elvira-quick-chat-button' ); ?></option>
                            <option value="bottom-left" <?php selected( $display['button_position_override'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom left', 'elvira-quick-chat-button' ); ?></option>
                            <option value="top-right" <?php selected( $display['button_position_override'], 'top-right' ); ?>><?php esc_html_e( 'Top right', 'elvira-quick-chat-button' ); ?></option>
                            <option value="top-left" <?php selected( $display['button_position_override'], 'top-left' ); ?>><?php esc_html_e( 'Top left', 'elvira-quick-chat-button' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( __( 'Save Custom Style', 'elvira-quick-chat-button' ), 'primary', 'ewqc_save_custom_style_button', false ); ?>
            </p>
        </form>

        <script>
        (function($){
            // toggle custom icon row
            $('input[name="ewqc_settings[button_icon_type]"]').on('change', function(){
                if ($(this).val() === 'custom') {
                    $('.ewqc-custom-icon-row').slideDown();
                } else {
                    $('.ewqc-custom-icon-row').slideUp();
                }
            });

            // media uploader
            var frame;
            $('#ewqc_upload_icon_button').on('click', function(e){
                e.preventDefault();
                if ( frame ) {
                    frame.open();
                    return;
                }
                frame = wp.media({
                    title: '<?php echo esc_js( __( 'Select or upload an icon', 'elvira-quick-chat-button' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this icon', 'elvira-quick-chat-button' ) ); ?>' },
                    multiple: false
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#ewqc_custom_icon_url').val(attachment.url);
                });
                frame.open();
            });

            // colorpicker
            if ( typeof wp !== 'undefined' && typeof jQuery.fn.wpColorPicker === 'function' ) {
                jQuery('.ewqc-color-field').wpColorPicker();
            }
        })(jQuery);
        </script>

        <?php
    }

    /**
     * Handle saving the custom style POST (admin_post handler)
     * Saves only the custom subset into its own option to avoid overwriting global settings.
     */
    public static function ewqc_handle_save_custom_style() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'elvira-quick-chat-button' ) );
        }

        // Nonce verification
        check_admin_referer( 'ewqc_save_custom_style_nonce', 'ewqc_save_custom_style_nonce_field' );

        // Grab posted settings subset (only keys we want)
        $posted = array();

        // Use filter_input with the FILTER_REQUIRE_ARRAY flag so we don't touch $_POST directly.
        // filter_input returns NULL if the field is missing.
        $raw_settings = filter_input(
            INPUT_POST,
            'ewqc_settings',
            FILTER_DEFAULT,
            array( 'flags' => FILTER_REQUIRE_ARRAY )
        );

        if ( is_array( $raw_settings ) ) {
            // Now explicitly unslash the data.
            $unslashed_settings = wp_unslash( $raw_settings );

            // Recursively sanitize values (assume plain text — swap to wp_kses_post() if HTML allowed).
            array_walk_recursive(
                $unslashed_settings,
                static function ( &$value ) {
                    $value = sanitize_text_field( $value );
                }
            );

            $posted = $unslashed_settings;
        }



        // Pick only the allowed keys into a custom array
        $allowed = array_keys( self::ewqc_defaults() );
        $custom = array();
        foreach ( $allowed as $k ) {
            if ( array_key_exists( $k, $posted ) ) {
                $custom[ $k ] = $posted[ $k ];
            }
        }

        // Sanitize subset and save to its own option
        $custom = self::ewqc_sanitize_custom_settings( $custom );
        update_option( self::OPTION_NAME, $custom );

        // Redirect back with success flag
        //$redirect = wp_get_referer() ? wp_get_referer() : admin_url();
        $redirect = add_query_arg(
            array(
                'ewqc_custom_style_saved' => '1',
                '_wpnonce' => wp_create_nonce( 'ewqc_custom_style_action' ),
            ),
            admin_url( 'admin.php?page=ewqc-settings' )
        );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Admin notice after saving
     */
    public static function ewqc_admin_notices() {
        // Verify nonce before processing the request.
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'ewqc_custom_style_action' ) ) {
            if ( isset( $_GET['ewqc_custom_style_saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['ewqc_custom_style_saved'] ) ) ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Custom styling saved.', 'elvira-quick-chat-button' ) . '</p></div>';
            }
        }
    }


    /**
     * Print inline CSS on front-end applying the selected styles.
     * Merges global settings with custom-style option (custom overrides).
     */
    public static function ewqc_print_custom_styles() {
        // load global + custom
        $global = get_option( 'ewqc_settings', array() );
        $custom = get_option( self::OPTION_NAME, array() );
        $settings = array_merge( (array) $global, (array) $custom );
        $settings = self::ewqc_sanitize_custom_settings( $settings );

        $bg = esc_attr( $settings['button_bg_color'] );
        $text = esc_attr( $settings['button_text_color'] );
        $icon_color = ! empty( $settings['button_icon_color'] ) ? esc_attr( $settings['button_icon_color'] ) : $text;
        $size = max( 24, intval( $settings['button_size'] ) );
        $radius = intval( $settings['button_border_radius'] );
        $animation = $settings['button_animation'];
        $position_override = $settings['button_position_override'];

        $animation_css = '';
        if ( 'pulse' === $animation ) {
            $animation_css = "animation: ewqc-pulse 2s infinite;";
        } elseif ( 'bounce' === $animation ) {
            $animation_css = "animation: ewqc-bounce 2s infinite;";
        }

        $css = "
        /* EWQC custom button styles (generated) */
        .ewqc-floating-button .ewqc-button {
            background: {$bg} !important;
            color: {$text} !important;
            border-radius: {$radius}px !important;
            height: {$size}px;
            min-height: {$size}px;
            line-height: {$size}px;
            display: inline-flex;
            align-items: center;
            padding: 0 12px;
            gap: 8px;
            box-sizing: border-box;
            {$animation_css}
        }
        .ewqc-floating-button .ewqc-button .ewqc-icon-wrap svg,
        .ewqc-floating-button .ewqc-button .ewqc-custom-icon {
            width: " . max( 12, floor( $size * 0.5 ) ) . "px;
            height: " . max( 12, floor( $size * 0.5 ) ) . "px;
            vertical-align: middle;
            fill: {$icon_color};
            color: {$icon_color};
        }
        .ewqc-floating-button .ewqc-button .ewqc-text {
            color: {$text} !important;
            font-size: " . max( 12, floor( $size * 0.36 ) ) . "px;
        }
        @keyframes ewqc-pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 rgba(0,0,0,0.0); }
            50% { transform: scale(1.06); box-shadow: 0 6px 12px rgba(0,0,0,0.06); }
            100% { transform: scale(1); box-shadow: 0 0 0 rgba(0,0,0,0); }
        }
        @keyframes ewqc-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        ";

        if ( ! empty( $position_override ) ) {
            switch ( $position_override ) {
                case 'bottom-right':
                    $css .= ".ewqc-floating-button{ right: 24px !important; left: auto !important; bottom: 24px !important; top: auto !important; }";
                    break;
                case 'bottom-left':
                    $css .= ".ewqc-floating-button{ left: 24px !important; right: auto !important; bottom: 24px !important; top: auto !important; }";
                    break;
                case 'top-right':
                    $css .= ".ewqc-floating-button{ right: 24px !important; left: auto !important; top: 24px !important; bottom: auto !important; }";
                    break;
                case 'top-left':
                    $css .= ".ewqc-floating-button{ left: 24px !important; right: auto !important; top: 24px !important; bottom: auto !important; }";
                    break;
            }
        }

        if ( $css !== '' ) {
         echo '<style id="ewqc-custom-styles">' . esc_html( $css ) . '</style>';
        }
    }

    /**
     * Helper: return merged settings (global + custom) sanitized for style keys.
     * Useful for other code needing full view.
     *
     * @return array
     */
    public static function ewqc_get_settings() {
        $global = get_option( 'ewqc_settings', array() );
        $custom = get_option( self::OPTION_NAME, array() );
        $merged = array_merge( (array) $global, (array) $custom );
        return self::ewqc_sanitize_custom_settings( $merged );
    }
}
