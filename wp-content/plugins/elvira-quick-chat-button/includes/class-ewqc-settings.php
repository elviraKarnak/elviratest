<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings page handler
 */
class EWQC_Settings {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'ewqc_add_menu_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'ewqc_register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ewqc_enqueue_admin_assets' ) );
    }

    /**
     * Add menu page
     */
    public static function ewqc_add_menu_page() {
        add_menu_page(
            __( 'WhatsApp Quick Chat', 'elvira-quick-chat-button' ),
            __( 'WhatsApp Chat', 'elvira-quick-chat-button' ),
            'manage_options',
            'ewqc-settings',
            array( __CLASS__, 'ewqc_render_settings_page' ),
            'dashicons-whatsapp',
            30
        );
        
        add_submenu_page(
            'ewqc-settings',
            __( 'Settings', 'elvira-quick-chat-button' ),
            __( 'Settings', 'elvira-quick-chat-button' ),
            'manage_options',
            'ewqc-settings'
        );
    }

    /**
     * Register settings
     */
    public static function ewqc_register_settings() {
        register_setting(
            'ewqc_settings_group',
            'ewqc_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array( __CLASS__, 'ewqc_sanitize_settings' ),
                'default' => array(),
            )
        );

        // Basic settings section
        add_settings_section(
            'ewqc_basic_section',
            __( 'Basic Settings', 'elvira-quick-chat-button' ),
            null,
            'ewqc-settings'
        );

        $fields = array(
            'phone_number' => array(
                'label' => __( 'WhatsApp Number', 'elvira-quick-chat-button' ),
                'type' => 'text',
                'desc' => __( 'Enter phone number with country code (e.g., 919999999999)', 'elvira-quick-chat-button' ),
            ),
            'default_message' => array(
                'label' => __( 'Default Message', 'elvira-quick-chat-button' ),
                'type' => 'textarea',
                'desc' => __( 'Pre-filled message when chat opens', 'elvira-quick-chat-button' ),
            ),
            'button_text' => array(
                'label' => __( 'Button Text', 'elvira-quick-chat-button' ),
                'type' => 'text',
                'desc' => __( 'Text to display on the button', 'elvira-quick-chat-button' ),
            ),
            'position' => array(
                'label' => __( 'Button Position', 'elvira-quick-chat-button' ),
                'type' => 'select',
                'options' => array(
                    'bottom-right' => __( 'Bottom Right', 'elvira-quick-chat-button' ),
                    'bottom-left' => __( 'Bottom Left', 'elvira-quick-chat-button' ),
                    'top-right' => __( 'Top Right', 'elvira-quick-chat-button' ),
                    'top-left' => __( 'Top Left', 'elvira-quick-chat-button' ),
                ),
            ),
            'mobile_only' => array(
                'label' => __( 'Show on Mobile Only', 'elvira-quick-chat-button' ),
                'type' => 'checkbox',
                'desc' => __( 'Display button only on mobile devices', 'elvira-quick-chat-button' ),
            ),
            'show_floating' => array(
                'label' => __( 'Show Floating Button', 'elvira-quick-chat-button' ),
                'type' => 'checkbox',
                'desc' => __( 'Display floating button on website', 'elvira-quick-chat-button' ),
            ),
        );

        foreach ( $fields as $key => $field ) {
            add_settings_field(
                'ewqc_' . $key,
                $field['label'],
                array( __CLASS__, 'ewqc_render_field' ),
                'ewqc-settings',
                'ewqc_basic_section',
                array(
                    'key' => $key,
                    'field' => $field,
                )
            );
        }
    }

    /**
     * Render field
     */
    public static function ewqc_render_field( $args ) {
        $settings = get_option( 'ewqc_settings', array() );
        $key = $args['key'];
        $field = $args['field'];
        $value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

        switch ( $field['type'] ) {
            case 'text':
                printf(
                    '<input type="text" name="ewqc_settings[%s]" id="ewqc_%s" value="%s" class="regular-text" />',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_attr( $value )
                );
                break;

            case 'textarea':
                printf(
                    '<textarea name="ewqc_settings[%s]" id="ewqc_%s" rows="3" class="large-text">%s</textarea>',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    esc_textarea( $value )
                );
                break;

            case 'checkbox':
                printf(
                    '<input type="checkbox" name="ewqc_settings[%s]" id="ewqc_%s" value="1" %s />',
                    esc_attr( $key ),
                    esc_attr( $key ),
                    checked( $value, '1', false )
                );
                break;

            case 'select':
                printf( '<select name="ewqc_settings[%s]" id="ewqc_%s">', esc_attr( $key ), esc_attr( $key ) );
                foreach ( $field['options'] as $opt_value => $opt_label ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $opt_value ),
                        selected( $value, $opt_value, false ),
                        esc_html( $opt_label )
                    );
                }
                echo '</select>';
                break;
        }

        if ( ! empty( $field['desc'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $field['desc'] ) );
        }
    }

    /**
     * Sanitize settings
     */
    public static function ewqc_sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['phone_number'] ) ) {
            $sanitized['phone_number'] = sanitize_text_field( $input['phone_number'] );
        }

        if ( isset( $input['default_message'] ) ) {
            $sanitized['default_message'] = sanitize_textarea_field( $input['default_message'] );
        }

        if ( isset( $input['button_text'] ) ) {
            $sanitized['button_text'] = sanitize_text_field( $input['button_text'] );
        }

        if ( isset( $input['position'] ) ) {
            $sanitized['position'] = sanitize_text_field( $input['position'] );
        }

        $sanitized['mobile_only'] = isset( $input['mobile_only'] ) ? '1' : '0';
        $sanitized['show_floating'] = isset( $input['show_floating'] ) ? '1' : '0';

        return $sanitized;
    }

    /**
     * Render settings page
     */
    public static function ewqc_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $is_pro = EWQC_License::ewqc_is_pro_active();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php if ( ! $is_pro ) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php esc_html_e( 'You are using the free version.', 'elvira-quick-chat-button' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'options-general.php?page=ewqc-license' ) ); ?>">
                            <?php esc_html_e( 'Activate Pro License', 'elvira-quick-chat-button' ); ?>
                        </a>
                        <?php esc_html_e( 'to unlock premium features.', 'elvira-quick-chat-button' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'ewqc_settings_group' );
                do_settings_sections( 'ewqc-settings' );
                submit_button();
                ?>
            </form>

            <div class="ewqc-pro-features <?php echo $is_pro ? '' : ''; ?>">
                <h2><?php esc_html_e( 'Pro Features', 'elvira-quick-chat-button' ); ?></h2>
                
                <?php if ( ! $is_pro ) : ?>
                    <div class="ewqc-pro-overlay">
                        <div class="ewqc-pro-message">
                            <h3><?php esc_html_e( 'Unlock Pro Features', 'elvira-quick-chat-button' ); ?></h3>
                            <p><?php esc_html_e( 'Activate your license to access:', 'elvira-quick-chat-button' ); ?></p>
                            <ul>
                                <li><?php esc_html_e( 'Multiple agents with round-robin', 'elvira-quick-chat-button' ); ?></li>
                                <li><?php esc_html_e( 'Custom icons & styles', 'elvira-quick-chat-button' ); ?></li>
                                <li><?php esc_html_e( 'Conditional display rules', 'elvira-quick-chat-button' ); ?></li>
                                <li><?php esc_html_e( 'Advanced analytics', 'elvira-quick-chat-button' ); ?></li>
                            </ul>
                            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=ewqc-license' ) ); ?>" class="button button-primary button-hero">
                                <?php esc_html_e( 'Activate License', 'elvira-quick-chat-button' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                if ( class_exists( 'EWQC_Pro' ) ) {
                    EWQC_Pro::ewqc_render_pro_settings();
                }
                ?>
            </div>

            <div class="ewqc-shortcode-info" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                <h3><?php esc_html_e( 'How to Use', 'elvira-quick-chat-button' ); ?></h3>
                <p><?php esc_html_e( 'Use the shortcode anywhere in your content:', 'elvira-quick-chat-button' ); ?></p>
                <code>[whatsapp_chat]</code>
                <p><?php esc_html_e( 'Or with custom parameters:', 'elvira-quick-chat-button' ); ?></p>
                <code>[whatsapp_chat phone="919999999999" message="Hello!" text="Chat Now"]</code>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     */
    public static function ewqc_enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_ewqc-settings' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'ewqc-admin',
            EWQC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EWQC_VERSION
        );

        wp_enqueue_script(
            'ewqc-admin',
            EWQC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            EWQC_VERSION,
            true
        );
    }
}