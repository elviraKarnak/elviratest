<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Multiple agents handler (Pro feature)
 */
class EWQC_Agents {

    /**
     * Initialize
     */
    public static function init() {
        // Keep previous registration for consistency
        add_action( 'admin_init', array( __CLASS__, 'ewqc_register_settings' ) );

        // Handle form POST from our custom Save button
        add_action( 'admin_post_ewqc_save_agents', array( __CLASS__, 'ewqc_handle_save_agents' ) );
    }

    /**
     * Register settings
     *
     * (Optional — keeps WP aware of the option)
     */
    public static function ewqc_register_settings() {
        register_setting(
            'ewqc_agents_group',
            'ewqc_agents',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'ewqc_sanitize_agents' ),
                'default'           => array(),
            )
        );
    }

    /**
     * Sanitize agents
     */
    public static function ewqc_sanitize_agents( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $input as $agent ) {
            // allow rows where name and phone are provided
            $name  = isset( $agent['name'] ) ? sanitize_text_field( wp_unslash( $agent['name'] ) ) : '';
            $phone = isset( $agent['phone'] ) ? sanitize_text_field( wp_unslash( $agent['phone'] ) ) : '';
            $title = isset( $agent['title'] ) ? sanitize_text_field( wp_unslash( $agent['title'] ) ) : '';
            $active = isset( $agent['active'] ) && ( '1' === $agent['active'] || 1 === $agent['active'] ) ? '1' : '0';

            if ( '' !== $name && '' !== $phone ) {
                $sanitized[] = array(
                    'name'      => $name,
                    'phone'     => $phone,
                    'title'     => $title,
                    'active'    => $active,
                );
            }
        }

        return $sanitized;
    }

    /**
     * Render settings (outputs the agents table and a Save button)
     *
     * NOTE: this renders its own form to save agents. If you already
     * have a global settings form and prefer that, remove the form tags
     * and the submit button here and ensure the input names match your
     * registered option.
     */
    public static function ewqc_render_settings() {
        $agents = get_option( 'ewqc_agents', array() );
        ?>
        <h2><?php esc_html_e( 'Multiple Agents (Pro)', 'elvira-quick-chat-button' ); ?></h2>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php
            // action value handled by admin_post_ewqc_save_agents
            ?>
            <input type="hidden" name="action" value="ewqc_save_agents" />
            <?php wp_nonce_field( 'ewqc_save_agents_nonce', 'ewqc_save_agents_nonce_field' ); ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'elvira-quick-chat-button' ); ?></th>
                        <th><?php esc_html_e( 'Title', 'elvira-quick-chat-button' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'elvira-quick-chat-button' ); ?></th>
                        <th><?php esc_html_e( 'Active', 'elvira-quick-chat-button' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'elvira-quick-chat-button' ); ?></th>
                    </tr>
                </thead>
                <tbody id="ewqc-agents-list">
                    <?php
                    if ( ! empty( $agents ) ) {
                        foreach ( $agents as $index => $agent ) {
                            self::ewqc_render_agent_row( $index, $agent );
                        }
                    } else {
                        echo '<tr class="ewqc-no-agents"><td colspan="5">' . esc_html__( 'No agents added yet. Click "Add Agent" to get started.', 'elvira-quick-chat-button' ) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button" id="ewqc-add-agent">
                    <?php esc_html_e( 'Add Agent', 'elvira-quick-chat-button' ); ?>
                </button>
                &nbsp;
                <?php submit_button( esc_html__( 'Save Agents', 'elvira-quick-chat-button' ), 'primary', 'ewqc_save_agents_btn', false ); ?>
            </p>

            <p class="description">
                <?php esc_html_e( 'Agents will be selected in round-robin order when customers click the WhatsApp button.', 'elvira-quick-chat-button' ); ?>
            </p>
        </form>

        <style>
        /* small inline styling so rows align like your screenshot */
        #ewqc-agents-list input.regular-text { width: 100%; box-sizing: border-box; }
        </style>

        <?php
    }

    /**
     * Render agent row
     */
    private static function ewqc_render_agent_row( $index, $agent ) {
        $name  = isset( $agent['name'] ) ? $agent['name'] : '';
        $title = isset( $agent['title'] ) ? $agent['title'] : '';
        $phone = isset( $agent['phone'] ) ? $agent['phone'] : '';
        $active = isset( $agent['active'] ) && '1' === $agent['active'] ? '1' : '0';
        ?>
        <tr>
            <td>
                <input type="text" name="ewqc_agents[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" class="regular-text" />
            </td>
            <td>
                <input type="text" name="ewqc_agents[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" class="regular-text" />
            </td>
            <td>
                <input type="text" name="ewqc_agents[<?php echo esc_attr( $index ); ?>][phone]" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" />
            </td>
            <td>
                <input type="checkbox" name="ewqc_agents[<?php echo esc_attr( $index ); ?>][active]" value="1" <?php checked( $active, '1' ); ?> />
            </td>
            <td>
                <button type="button" class="button ewqc-remove-agent"><?php esc_html_e( 'Remove', 'elvira-quick-chat-button' ); ?></button>
            </td>
        </tr>
        <?php
    }

    /**
     * Handle the form submission from our Save Agents button
     */
    public static function ewqc_handle_save_agents() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'elvira-quick-chat-button' ) );

        }

        // Sanitize and verify nonce
        $nonce = isset( $_POST['ewqc_save_agents_nonce_field'] )
            ? sanitize_text_field( wp_unslash( $_POST['ewqc_save_agents_nonce_field'] ) )
            : '';

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ewqc_save_agents_nonce' ) ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'elvira-quick-chat-button' ) );

        }

        // Safely read posted agents array (unslash then sanitize each value)
        $posted = array();

        // Safely fetch and sanitize input before use
        $raw_agents = filter_input( INPUT_POST, 'ewqc_agents', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

        if ( is_array( $raw_agents ) ) {
            $posted = wp_unslash( $raw_agents );

            // Recursively sanitize all values
            array_walk_recursive(
                $posted,
                static function ( &$value ) {
                    // Use wp_kses_post() if HTML is expected; sanitize_text_field() for plain text
                    $value = sanitize_text_field( $value );
                }
            );
        }

        // Apply your existing sanitization routine (redundant but safe)
        $sanitized = self::ewqc_sanitize_agents( $posted );


        // Persist sanitized data
        update_option( 'ewqc_agents', $sanitized );

        // Redirect back to referrer with a query arg to indicate success
        $redirect = wp_get_referer();
        if ( $redirect ) {
            // add_query_arg sanitizes the URL for us here; ensure URL is safe for redirect
            $redirect = add_query_arg( 'ewqc_agents_saved', '1', $redirect );
            wp_safe_redirect( $redirect );
        } else {
            wp_safe_redirect( admin_url() );
        }

        exit;
    }


    /**
     * Get next agent in round-robin
     */
    public static function ewqc_get_next_agent() {
        $agents = get_option( 'ewqc_agents', array() );

        // Filter active agents
        $active_agents = array_filter( $agents, function( $agent ) {
            return isset( $agent['active'] ) && '1' === $agent['active'];
        } );

        if ( empty( $active_agents ) ) {
            return null;
        }

        // Get last index
        $last_index = get_transient( 'ewqc_last_agent_index' );
        if ( false === $last_index ) {
            $last_index = -1;
        }

        // Get next index
        $active_keys = array_keys( $active_agents );
        $next_position = ( $last_index + 1 ) % count( $active_keys );
        $next_key = $active_keys[ $next_position ];

        // Save for next time
        set_transient( 'ewqc_last_agent_index', $next_position, HOUR_IN_SECONDS );

        return $active_agents[ $next_key ];
    }
}
