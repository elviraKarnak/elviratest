<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pro features handler
 */
class EWQC_Pro {

    /**
     * Initialize
     */
    public static function init() {
        if ( ! EWQC_License::ewqc_is_pro_active() ) {
            return;
        }

        // Load pro feature classes
        require_once EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-agents.php';
        require_once EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-custom-style.php';
        require_once EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-conditional-display-rules.php';
        require_once EWQC_PLUGIN_DIR . 'includes/pro/class-ewqc-analytics.php';

        // Init pro classes if available
        if ( class_exists( 'EWQC_Agents' ) ) {
            EWQC_Agents::init();
        }
        if ( class_exists( 'EWQC_Custom_Style' ) ) {
            EWQC_Custom_Style::init();
        }
        if ( class_exists( 'EWQC_Conditional_Display_Rules' ) ) {
            EWQC_Conditional_Display_Rules::init();
        }
        if ( class_exists( 'EWQC_Analytics' ) ) {
            EWQC_Analytics::init();
        }
    }

    /**
     * Render pro settings
     */
    public static function ewqc_render_pro_settings() {
        if ( ! EWQC_License::ewqc_is_pro_active() ) {
            ?>
            <div style="padding: 40px; text-align: center; opacity: 0.6;">
                <h3><?php esc_html_e( 'Multiple Agents', 'elvira-quick-chat-button' ); ?></h3>
                <p><?php esc_html_e( 'Add multiple support agents with round-robin distribution', 'elvira-quick-chat-button' ); ?></p>

                <h3><?php esc_html_e( 'Custom Styling', 'elvira-quick-chat-button' ); ?></h3>
                <p><?php esc_html_e( 'Customize button colors, icons, and animations', 'elvira-quick-chat-button' ); ?></p>

                <h3><?php esc_html_e( 'Analytics Dashboard', 'elvira-quick-chat-button' ); ?></h3>
                <p><?php esc_html_e( 'Track clicks, conversions, and user behavior', 'elvira-quick-chat-button' ); ?></p>
            </div>
            <?php
            return;
        }

        // Render actual pro settings when active
        if ( class_exists( 'EWQC_Agents' ) ) {
            EWQC_Agents::ewqc_render_settings();
            echo '<hr />';
        }

        if ( class_exists( 'EWQC_Custom_Style' ) ) {
            EWQC_Custom_Style::ewqc_render_settings();
            echo '<hr />';
        }

        if ( class_exists( 'EWQC_Conditional_Display_Rules' ) ) {
            EWQC_Conditional_Display_Rules::ewqc_render_settings();
            echo '<hr />';
        }

        if ( class_exists( 'EWQC_Analytics' ) ) {
            EWQC_Analytics::ewqc_render_dashboard();
        }
    }
}
