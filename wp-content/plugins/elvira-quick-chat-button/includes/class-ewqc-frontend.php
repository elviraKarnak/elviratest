<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend functionality
 */
class EWQC_Frontend {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'ewqc_enqueue_assets' ) );
        add_action( 'wp_footer', array( __CLASS__, 'ewqc_render_floating_button' ) );

        // AJAX endpoint to get next agent (round-robin)
        add_action( 'wp_ajax_ewqc_get_agent', array( __CLASS__, 'ewqc_ajax_get_agent' ) );
        add_action( 'wp_ajax_nopriv_ewqc_get_agent', array( __CLASS__, 'ewqc_ajax_get_agent' ) );
    }

    /**
     * Enqueue frontend assets
     */
    public static function ewqc_enqueue_assets() {
        wp_enqueue_style(
            'ewqc-frontend',
            EWQC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            defined( 'EWQC_VERSION' ) ? EWQC_VERSION : false
        );

        wp_enqueue_script(
            'ewqc-frontend',
            EWQC_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            defined( 'EWQC_VERSION' ) ? EWQC_VERSION : false,
            true
        );

        // base settings (global)
        $settings = get_option( 'ewqc_settings', array() );

        // merge style settings from EWQC_Custom_Style if available (its ewqc_get_settings returns sanitized subset)
        $style = array();
        if ( class_exists( 'EWQC_Custom_Style' ) && method_exists( 'EWQC_Custom_Style', 'ewqc_get_settings' ) ) {
            $style = EWQC_Custom_Style::ewqc_get_settings();
        } else {
            // fallback: use global keys if present
            $style = array(
                'button_icon_type'        => isset( $settings['button_icon_type'] ) ? $settings['button_icon_type'] : 'default',
                'button_icon_custom_url'  => isset( $settings['button_icon_custom_url'] ) ? $settings['button_icon_custom_url'] : '',
                'button_bg_color'         => isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#25D366',
                'button_text_color'       => isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff',
                'button_icon_color'       => isset( $settings['button_icon_color'] ) ? $settings['button_icon_color'] : '',
                'button_size'             => isset( $settings['button_size'] ) ? $settings['button_size'] : '56',
                'button_border_radius'    => isset( $settings['button_border_radius'] ) ? $settings['button_border_radius'] : '50',
                'button_animation'        => isset( $settings['button_animation'] ) ? $settings['button_animation'] : 'none',
                'button_position_override'=> isset( $settings['button_position_override'] ) ? $settings['button_position_override'] : '',
            );
        }

        // Optional: pass a flag to the JS whether conditionals exist (for client-side logic if needed)
        $conditional_rules_exist = false;
        if ( class_exists( 'EWQC_Conditional_Display_Rules' ) ) {
            // We don't necessarily need to pass all rules; a simple existence flag is enough
            $cond = get_option( 'ewqc_conditional_rules', array() );
            $conditional_rules_exist = ( is_array( $cond ) && ! empty( $cond ) );
        }

        // Localize script with everything frontend needs
        wp_localize_script(
            'ewqc-frontend',
            'ewqcData',
            array(
                'isMobile'        => wp_is_mobile(),
                'mobileOnly'      => isset( $settings['mobile_only'] ) ? $settings['mobile_only'] : '0',
                'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
                'nonce_get_agent' => wp_create_nonce( 'ewqc_get_agent' ),
                'nonce_track'     => wp_create_nonce( 'ewqc_track_click' ),
                'default_phone'   => isset( $settings['phone_number'] ) ? preg_replace( '/[^0-9]/', '', $settings['phone_number'] ) : '',
                'default_message' => isset( $settings['default_message'] ) ? $settings['default_message'] : '',
                'style'           => array(
                    'icon_type'    => isset( $style['button_icon_type'] ) ? $style['button_icon_type'] : 'default',
                    'icon_url'     => isset( $style['button_icon_custom_url'] ) ? $style['button_icon_custom_url'] : '',
                    'bg_color'     => isset( $style['button_bg_color'] ) ? $style['button_bg_color'] : '#25D366',
                    'text_color'   => isset( $style['button_text_color'] ) ? $style['button_text_color'] : '#ffffff',
                    'icon_color'   => isset( $style['button_icon_color'] ) ? $style['button_icon_color'] : '',
                    'size'         => isset( $style['button_size'] ) ? intval( $style['button_size'] ) : 56,
                    'radius'       => isset( $style['button_border_radius'] ) ? intval( $style['button_border_radius'] ) : 50,
                    'animation'    => isset( $style['button_animation'] ) ? $style['button_animation'] : 'none',
                    'position_override' => isset( $style['button_position_override'] ) ? $style['button_position_override'] : '',
                ),
                'conditionals_present' => $conditional_rules_exist,
            )
        );
    }

    /**
     * Render floating button
     */
    public static function ewqc_render_floating_button() {
        // If conditional rules class exists, respect it
        if ( class_exists( 'EWQC_Conditional_Display_Rules' ) ) {
            // If rules say "do not show", return early
            if ( ! EWQC_Conditional_Display_Rules::ewqc_should_show_button() ) {
                return;
            }
        }

        $settings = get_option( 'ewqc_settings', array() );

        $show_floating = isset( $settings['show_floating'] ) ? $settings['show_floating'] : '1';

        if ( '1' !== $show_floating ) {
            return;
        }

        $text = isset( $settings['button_text'] ) ? $settings['button_text'] : __( 'Chat with us', 'elvira-quick-chat-button' );
        $position = isset( $settings['position'] ) ? $settings['position'] : 'bottom-right';
        $mobile_only = isset( $settings['mobile_only'] ) ? $settings['mobile_only'] : '0';

        // pick styling from custom-style option (via class if available)
        $style = array();
        if ( class_exists( 'EWQC_Custom_Style' ) && method_exists( 'EWQC_Custom_Style', 'ewqc_get_settings' ) ) {
            $style = EWQC_Custom_Style::ewqc_get_settings();
        } else {
            $style = array(
                'button_icon_type'        => isset( $settings['button_icon_type'] ) ? $settings['button_icon_type'] : 'default',
                'button_icon_custom_url'  => isset( $settings['button_icon_custom_url'] ) ? $settings['button_icon_custom_url'] : '',
                'button_bg_color'         => isset( $settings['button_bg_color'] ) ? $settings['button_bg_color'] : '#25D366',
                'button_text_color'       => isset( $settings['button_text_color'] ) ? $settings['button_text_color'] : '#ffffff',
                'button_icon_color'       => isset( $settings['button_icon_color'] ) ? $settings['button_icon_color'] : '',
                'button_size'             => isset( $settings['button_size'] ) ? intval( $settings['button_size'] ) : 56,
                'button_border_radius'    => isset( $settings['button_border_radius'] ) ? intval( $settings['button_border_radius'] ) : 50,
                'button_animation'        => isset( $settings['button_animation'] ) ? $settings['button_animation'] : 'none',
                'button_position_override'=> isset( $settings['button_position_override'] ) ? $settings['button_position_override'] : '',
            );
        }

        // compute class + inline styles fallback (print_custom_styles will usually override)
        $position_class = 'ewqc-position-' . esc_attr( $position );
        if ( ! empty( $style['button_position_override'] ) ) {
            $position_class = 'ewqc-position-' . esc_attr( $style['button_position_override'] );
        }

        $class = 'ewqc-floating-button ' . $position_class;
        if ( '1' === $mobile_only ) {
            $class .= ' ewqc-mobile-only';
        }

        $inline_style = sprintf(
            'background:%s;color:%s;border-radius:%spx;height:%spx;line-height:%spx;',
            esc_attr( $style['button_bg_color'] ),
            esc_attr( $style['button_text_color'] ),
            esc_attr( $style['button_border_radius'] ),
            esc_attr( $style['button_size'] ),
            esc_attr( $style['button_size'] )
        );

        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <a href="#" class="ewqc-button" aria-label="<?php echo esc_attr( $text ); ?>" style="<?php echo esc_attr( $inline_style ); ?>">
                <span class="ewqc-icon-wrap" aria-hidden="true">
                    <?php
                    // render icon according to style
                    $icon_type = isset( $style['button_icon_type'] ) ? $style['button_icon_type'] : 'default';
                    $icon_color = ! empty( $style['button_icon_color'] ) ? $style['button_icon_color'] : $style['button_text_color'];

                    if ( 'custom' === $icon_type && ! empty( $style['button_icon_custom_url'] ) ) :
                        // custom image
                        ?>
                        <img src="<?php echo esc_url( $style['button_icon_custom_url'] ); ?>" class="ewqc-custom-icon" alt="" style="width:<?php echo esc_attr( max( 12, floor( (int) $style['button_size'] * 0.5 ) ) ); ?>px; height:<?php echo esc_attr( max( 12, floor( (int) $style['button_size'] * 0.5 ) ) ); ?>px; vertical-align:middle;"/>
                        <?php
                    elseif ( 'paper-plane' === $icon_type ) :
                        // paper plane svg
                        $icon_size_raw = absint( max( 12, floor( (int) $style['button_size'] * 0.5 ) ) );
                        ?>
                        <svg class="ewqc-icon-svg" viewBox="0 0 24 24" width="<?php echo esc_attr( $icon_size_raw ); ?>" height="<?php echo esc_attr( $icon_size_raw ); ?>" fill="<?php echo esc_attr( $icon_color ); ?>" xmlns="http://www.w3.org/2000/svg"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
                        <?php
                    elseif ( 'chat-bubble' === $icon_type ) :
                        // chat bubble svg
                        $icon_size_raw = max( 12, floor( (int) $style['button_size'] * 0.5 ) );
                        ?>
                        <svg class="ewqc-icon-svg" viewBox="0 0 24 24" width="<?php echo esc_attr( $icon_size_raw ); ?>" height="<?php echo esc_attr( $icon_size_raw ); ?>" fill="<?php echo esc_attr( $icon_color ); ?>" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4C2.9 2 2 2.9 2 4v14l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                        <?php
                    else :
                        // default whatsapp-like icon (simple chat + phone)
                        $icon_size_raw = max( 12, floor( (int) $style['button_size'] * 0.5 ) );
                        $icon_size_int = absint( $icon_size_raw );
                        ?>
                        <svg class="ewqc-icon-svg" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" width="<?php echo esc_attr( $icon_size_int ); ?>" height="<?php echo esc_attr( $icon_size_int ); ?>"> <path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825.737 5.607 2.137 8.048L0 32l7.933-2.127C10.353 31.269 13.117 32 16 32c8.837 0 16-7.163 16-16S24.837 0 16 0zm5.305 18.421c-.365-.183-2.159-1.066-2.495-1.187-.336-.121-.581-.183-.825.183s-.947 1.187-1.162 1.432c-.214.245-.429.275-.794.092s-1.549-.571-2.95-1.82c-1.092-.973-1.828-2.176-2.042-2.541s-.023-.563.161-.745c.165-.164.365-.429.548-.643s.244-.365.365-.61.061-.458-.03-.643-.825-1.985-1.13-2.717c-.297-.714-.599-.617-.825-.628-.214-.01-.458-.012-.702-.012s-.641.091-.977.458S6.35 7.62 6.35 9.426c0 1.806 1.314 3.548 1.497 3.793s2.589 3.951 6.272 5.542c.876.378 1.56.604 2.093.773.88.279 1.681.24 2.314.146.706-.106 2.159-.883 2.465-1.735s.305-1.584.214-1.735-.336-.245-.702-.428z" fill="<?php echo esc_attr( $icon_color ); ?>"/></svg>
                        <?php
                    endif;
                    ?>
                </span>

                <span class="ewqc-text"><?php echo esc_html( $text ); ?></span>
            </a>
        </div>
        <?php
    }

    /**
     * AJAX: return next active agent (phone + url) in JSON
     */
    public static function ewqc_ajax_get_agent() {
        check_ajax_referer( 'ewqc_get_agent', 'nonce' );

        // Try to use EWQC_Agents class if available
        if ( class_exists( 'EWQC_Agents' ) && method_exists( 'EWQC_Agents', 'get_next_agent' ) ) {
            $agent = EWQC_Agents::get_next_agent();
        } else {
            $agent = null;
        }

        // Fallback to default phone stored in settings
        $settings = get_option( 'ewqc_settings', array() );
        $message = isset( $settings['default_message'] ) ? $settings['default_message'] : '';

        if ( ! empty( $agent ) && isset( $agent['phone'] ) ) {
            $phone = preg_replace( '/[^0-9]/', '', $agent['phone'] );
        } else {
            $phone = isset( $settings['phone_number'] ) ? preg_replace( '/[^0-9]/', '', $settings['phone_number'] ) : '';
        }

        if ( empty( $phone ) ) {
            wp_send_json_error( array( 'message' => 'no_phone' ) );
        }

        // build whatsapp url depending on device
        $message_encoded = rawurlencode( $message );
        $url = wp_is_mobile()
            ? "https://api.whatsapp.com/send?phone={$phone}&text={$message_encoded}"
            : "https://web.whatsapp.com/send?phone={$phone}&text={$message_encoded}";

        wp_send_json_success( array(
            'phone' => $phone,
            'url'   => $url,
        ) );
    }

    /**
     * Generate WhatsApp URL (unused by click flow — kept for compatibility)
     */
    public static function ewqc_generate_whatsapp_url( $phone, $message = '' ) {
        $phone = preg_replace( '/[^0-9]/', '', $phone );
        $message = rawurlencode( $message );

        if ( wp_is_mobile() ) {
            return "https://api.whatsapp.com/send?phone={$phone}&text={$message}";
        }

        return "https://web.whatsapp.com/send?phone={$phone}&text={$message}";
    }
}
