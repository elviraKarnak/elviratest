<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Conditional display rules for the WhatsApp button (Pro)
 *
 * This version stores rules in a separate option 'ewqc_conditional_rules'.
 * It renders its own form and Save button and sanitizes input server-side.
 */
class EWQC_Conditional_Display_Rules {

    /**
     * Option name where rules are stored
     */
    const OPTION_NAME = 'ewqc_conditional_rules';

    public static function init() {
        // Admin scripts to control UI
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ewqc_admin_enqueue' ) );

        // Form handler for the independent Save button
        add_action( 'admin_post_ewqc_save_conditional_rules', array( __CLASS__, 'ewqc_handle_save_rules' ) );

        // Admin notice after save
        add_action( 'admin_notices', array( __CLASS__, 'ewqc_admin_notices' ) );
    }

    /**
     * Enqueue admin JS (for toggles). Limit to plugin settings page by checking current screen.
     */
    public static function ewqc_admin_enqueue( $hook = '' ) {
        if ( ! is_admin() ) {
            return;
        }

        // Only load on the plugin settings page if possible
        $is_our_page = false;

            // 1) Prefer the $hook param (admin_enqueue_scripts provides it and is available early)
            if ( ! empty( $hook ) && false !== strpos( (string) $hook, 'ewqc' ) ) {
                $is_our_page = true;
            }

            // 2) Fallback: use get_current_screen() if available (safer than checking $_GET).
            if ( ! $is_our_page && function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                if ( $screen && false !== strpos( $screen->id, 'ewqc' ) ) {
                    $is_our_page = true;
                }
            }

            // If we still can't determine the page, bail out to avoid using $_GET.
            if ( ! $is_our_page ) {
                return;
            }

        $ver = defined( 'EWQC_VERSION' ) ? EWQC_VERSION : false;

        // Enqueue a small admin JS file (optional). If you don't have the file, inline fallback below does the job.
        wp_enqueue_script( 'ewqc-conditional-admin', EWQC_PLUGIN_URL . 'assets/js/ewqc-conditional-admin.js', array( 'jquery' ), $ver, true );

        // Inline fallback so UX works even if external file missing
        $inline = "
            jQuery(function($){
                function toggleFields() {
                    var mode = $('input[name=\"ewqc_conditional_rules[mode]\"]:checked').val();
                    var checks = $('.ewqc-conditional-field');
                    if ( 'all' === mode ) {
                        checks.slideUp(120).find('input,select,textarea').prop('disabled', true);
                    } else {
                        checks.slideDown(120).find('input,select,textarea').prop('disabled', false);
                    }
                }
                $(document).on('change', 'input[name=\"ewqc_conditional_rules[mode]\"]', toggleFields);
                toggleFields();
            });
        ";

        wp_add_inline_script( 'ewqc-conditional-admin', $inline );
    }

    /**
     * Render the settings and its own Save button/form.
     * Fields names: ewqc_conditional_rules[...]
     */
    public static function ewqc_render_settings() {
        $rules = get_option( self::OPTION_NAME, array() );

        // defaults
        $defaults = array(
            'mode'                => 'all', // all | include | exclude
            'device'              => 'all', // all | mobile | desktop
            'homepage'            => '',    // 1 => match homepage
            'singular'            => '',    // 1 => match any singular
            'post_types'          => '',    // comma separated post types
            'page_ids'            => '',    // comma separated page/post IDs
            'product_ids'         => '',    // comma separated product IDs (if WC active)
            'product_cats'        => '',    // comma separated product_cat slugs or IDs
            'url_contains'        => '',    // substring to match in REQUEST_URI
            'user_logged_in'      => '',    // '' | only | only_not
            'user_roles'          => '',    // comma separated roles
        );

        $rules = wp_parse_args( $rules, $defaults );
        ?>
        <h2><?php esc_html_e( 'Display Rules (Pro)', 'elvira-quick-chat-button' ); ?></h2>

        <p class="description"><?php esc_html_e( 'Control where the floating WhatsApp button is shown. Choose "Include" to show only when any condition matches, or "Exclude" to hide when any condition matches. "All" always shows (subject to the main "Show Floating Button" toggle).', 'elvira-quick-chat-button' ); ?></p>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ewqc_save_conditional_rules" />
            <?php wp_nonce_field( 'ewqc_save_conditional_rules_nonce', 'ewqc_save_conditional_rules_nonce_field' ); ?>

            <table class="form-table ewqc-conditional-display-rules-table">
                <tbody>
                    <tr>
                        <th><?php esc_html_e( 'Mode', 'elvira-quick-chat-button' ); ?></th>
                        <td>
                            <label><input type="radio" name="ewqc_conditional_rules[mode]" value="all" <?php checked( $rules['mode'], 'all' ); ?> /> <?php esc_html_e( 'All pages (no conditional rules)', 'elvira-quick-chat-button' ); ?></label><br>
                            <label><input type="radio" name="ewqc_conditional_rules[mode]" value="include" <?php checked( $rules['mode'], 'include' ); ?> /> <?php esc_html_e( 'Show only when any condition matches (OR)', 'elvira-quick-chat-button' ); ?></label><br>
                            <label><input type="radio" name="ewqc_conditional_rules[mode]" value="exclude" <?php checked( $rules['mode'], 'exclude' ); ?> /> <?php esc_html_e( 'Hide when any condition matches (OR)', 'elvira-quick-chat-button' ); ?></label>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'Device', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <select name="ewqc_conditional_rules[device]">
                                <option value="all" <?php selected( $rules['device'], 'all' ); ?>><?php esc_html_e( 'All devices', 'elvira-quick-chat-button' ); ?></option>
                                <option value="mobile" <?php selected( $rules['device'], 'mobile' ); ?>><?php esc_html_e( 'Mobile only', 'elvira-quick-chat-button' ); ?></option>
                                <option value="desktop" <?php selected( $rules['device'], 'desktop' ); ?>><?php esc_html_e( 'Desktop only', 'elvira-quick-chat-button' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Target device for the rule.', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'Specific page types', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <label><input type="checkbox" name="ewqc_conditional_rules[homepage]" value="1" <?php checked( $rules['homepage'], '1' ); ?> /> <?php esc_html_e( 'Front page / homepage', 'elvira-quick-chat-button' ); ?></label><br>
                            <label><input type="checkbox" name="ewqc_conditional_rules[singular]" value="1" <?php checked( $rules['singular'], '1' ); ?> /> <?php esc_html_e( 'Any single post/page/custom-post-type (is_singular)', 'elvira-quick-chat-button' ); ?></label><br>
                            <p class="description"><?php esc_html_e( 'Check these to match the common page types.', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'Post types', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <input type="text" name="ewqc_conditional_rules[post_types]" value="<?php echo esc_attr( $rules['post_types'] ); ?>" class="regular-text" placeholder="post,page,product" />
                            <p class="description"><?php esc_html_e( 'Comma-separated post types to match (e.g. post,page,product). Leave blank for any.', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'Page / post IDs', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <input type="text" name="ewqc_conditional_rules[page_ids]" value="<?php echo esc_attr( $rules['page_ids'] ); ?>" class="regular-text" placeholder="12,45,78" />
                            <p class="description"><?php esc_html_e( 'Comma-separated page/post IDs to match. Example: 12,45', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'WooCommerce product IDs', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <input type="text" name="ewqc_conditional_rules[product_ids]" value="<?php echo esc_attr( $rules['product_ids'] ); ?>" class="regular-text" placeholder="123,456" />
                            <p class="description"><?php esc_html_e( 'Comma-separated product IDs. Leave blank to not use.', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'Product categories', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <input type="text" name="ewqc_conditional_rules[product_cats]" value="<?php echo esc_attr( $rules['product_cats'] ); ?>" class="regular-text" placeholder="shirts,10,20" />
                            <p class="description"><?php esc_html_e( 'Comma-separated product category slugs or IDs (WooCommerce).', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e( 'URL contains', 'elvira-quick-chat-button' ); ?></th>
                        <td class="ewqc-conditional-field">
                            <input type="text" name="ewqc_conditional_rules[url_contains]" value="<?php echo esc_attr( $rules['url_contains'] ); ?>" class="regular-text" placeholder="/special-offer" />
                            <p class="description"><?php esc_html_e( 'Show/hide when request URL contains this substring. Partial match.', 'elvira-quick-chat-button' ); ?></p>
                        </td>
                    </tr>

                </tbody>
            </table>

            <p class="submit">
                <?php submit_button( __( 'Save Display Rules', 'elvira-quick-chat-button' ), 'primary', 'ewqc_save_conditional_rules_button', false ); ?>
            </p>
        </form>
        <?php
    }

    /**
     * Form handler (admin_post) - saves posted rules to a separate option.
     */
    public static function ewqc_handle_save_rules() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to perform this action.', 'elvira-quick-chat-button' ) );
        }

        check_admin_referer( 'ewqc_save_conditional_rules_nonce', 'ewqc_save_conditional_rules_nonce_field' );

        // Posted rules
        $posted = array();

        // Preferred: read POST via filter_input so PHPCS won't flag the superglobal.
        $raw = filter_input(
            INPUT_POST,
            'ewqc_conditional_rules',
            FILTER_DEFAULT,
            array( 'flags' => FILTER_REQUIRE_ARRAY )
        );

        // Fallback for environments where filter_input() doesn't return the array (rare).
        if ( ! is_array( $raw ) && isset( $_POST['ewqc_conditional_rules'] ) && is_array( $_POST['ewqc_conditional_rules'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fallback when filter_input() is not available.
            $raw = wp_unslash($_POST['ewqc_conditional_rules']);
        }

        if ( is_array( $raw ) ) {
            // Uns lash before sanitizing.
            $unslashed = wp_unslash( $raw );

            // Recursively sanitize each value (assume plain text here).
            array_walk_recursive(
                $unslashed,
                static function ( &$value ) {
                    $value = sanitize_text_field( $value );
                }
            );

            $posted = $unslashed;
        }

        // Build allowed keys and normalize checkboxes
        $allowed = array(
            'mode',
            'device',
            'homepage',
            'singular',
            'post_types',
            'page_ids',
            'product_ids',
            'product_cats',
            'url_contains',
        );

        $rules = array();
        foreach ( $allowed as $k ) {
            if ( array_key_exists( $k, $posted ) ) {
                $rules[ $k ] = $posted[ $k ];
            } else {
                // absent checkbox-like => clear
                if ( in_array( $k, array( 'homepage', 'singular' ), true ) ) {
                    $rules[ $k ] = '';
                } else {
                    $rules[ $k ] = '';
                }
            }
        }

        // Sanitize
        $rules = self::ewqc_sanitize_rules_array( $rules );

        // Save to separate option
        $saved = update_option( self::OPTION_NAME, $rules );

        // Redirect back with flag
        // After processing the save:
        $redirect = wp_get_referer() ? wp_get_referer() : admin_url();

        // Create a nonce for the redirect notice (single-purpose string)
        $nonce = wp_create_nonce( 'ewqc_conditional_saved_notice' );

        // Add both the saved flag and the nonce to the redirect URL
        $redirect = add_query_arg(
            array(
                'ewqc_conditional_saved' => ( $saved ? '1' : '0' ),
                '_wpnonce'                => $nonce,
            ),
            $redirect
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Admin notice after saving
     */
    public static function ewqc_admin_notices() {
        if ( isset( $_GET['_wpnonce'], $_GET['ewqc_conditional_saved'] ) ) {
            // Verify nonce first (unslash + sanitize key for safety)
            $nonce = sanitize_key( wp_unslash( $_GET['_wpnonce'] ) );

            if ( wp_verify_nonce( $nonce, 'ewqc_conditional_saved_notice' ) ) {
                // Now safely read and sanitize the saved flag
                $saved_flag = sanitize_text_field( wp_unslash( $_GET['ewqc_conditional_saved'] ) );

                if ( '1' === $saved_flag ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Display rules saved.', 'elvira-quick-chat-button' ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to save display rules.', 'elvira-quick-chat-button' ) . '</p></div>';
                }
            }
            // If nonce verification fails, do nothing (ignore invalid/malicious requests).
        }
    }


    /**
     * Sanitize a rules array (not merging into other settings).
     *
     * @param array $rules
     * @return array
     */
    public static function ewqc_sanitize_rules_array( $rules ) {
        if ( ! is_array( $rules ) ) {
            $rules = array();
        }

        $allowed_modes = array( 'all', 'include', 'exclude' );
        $rules['mode'] = isset( $rules['mode'] ) && in_array( $rules['mode'], $allowed_modes, true ) ? $rules['mode'] : 'all';

        $rules['device'] = isset( $rules['device'] ) && in_array( $rules['device'], array( 'all', 'mobile', 'desktop' ), true ) ? $rules['device'] : 'all';

        $rules['homepage'] = ( ! empty( $rules['homepage'] ) && ( $rules['homepage'] === '1' || $rules['homepage'] === 1 ) ) ? '1' : '';
        $rules['singular'] = ( ! empty( $rules['singular'] ) && ( $rules['singular'] === '1' || $rules['singular'] === 1 ) ) ? '1' : '';

        $fields = array( 'post_types', 'page_ids', 'product_ids', 'product_cats', 'url_contains' );
        foreach ( $fields as $f ) {
            if ( isset( $rules[ $f ] ) ) {
                $rules[ $f ] = sanitize_text_field( wp_unslash( $rules[ $f ] ) );
            } else {
                $rules[ $f ] = '';
            }
        }

        return $rules;
    }

    /**
     * Public runtime check — returns true if the button should be shown for current request.
     * This method reads rules from the separate option 'ewqc_conditional_rules'.
     */
    public static function ewqc_should_show_button() {
        $rules = get_option( self::OPTION_NAME, array() );
        $rules = wp_parse_args( $rules, array(
            'mode' => 'all',
            'device' => 'all',
        ) );

        $mode = isset( $rules['mode'] ) ? $rules['mode'] : 'all';
        if ( 'all' === $mode ) {
            return true;
        }

        // Device check
        if ( isset( $rules['device'] ) && $rules['device'] !== 'all' ) {
            $is_mobile = wp_is_mobile();
            if ( $rules['device'] === 'mobile' && ! $is_mobile ) {
                if ( $mode === 'include' ) { return false; }
            } elseif ( $rules['device'] === 'desktop' && $is_mobile ) {
                if ( $mode === 'include' ) { return false; }
            }
        }

        $condition_matched = false;

        if ( ! empty( $rules['homepage'] ) && is_front_page() ) {
            $condition_matched = true;
        }

        if ( ! $condition_matched && ! empty( $rules['singular'] ) && is_singular() ) {
            $condition_matched = true;
        }

        if ( ! $condition_matched && ! empty( $rules['post_types'] ) ) {
            $types = array_map( 'trim', explode( ',', $rules['post_types'] ) );
            $pt = get_post_type();
            if ( $pt && in_array( $pt, $types, true ) ) {
                $condition_matched = true;
            }
        }

        if ( ! $condition_matched && ! empty( $rules['page_ids'] ) ) {
            $ids = array_filter( array_map( 'intval', explode( ',', $rules['page_ids'] ) ) );
            $queried_id = get_queried_object_id();
            if ( $queried_id && in_array( (int) $queried_id, $ids, true ) ) {
                $condition_matched = true;
            }
        }

        if ( ! $condition_matched && ! empty( $rules['product_ids'] ) ) {
            $pids = array_filter( array_map( 'intval', explode( ',', $rules['product_ids'] ) ) );
            if ( function_exists( 'is_product' ) && is_product() ) {
                global $post;
                if ( $post && in_array( (int) $post->ID, $pids, true ) ) {
                    $condition_matched = true;
                }
            }
        }

        if ( ! $condition_matched && ! empty( $rules['product_cats'] ) ) {
            $cats = array_map( 'trim', explode( ',', $rules['product_cats'] ) );
            if ( function_exists( 'is_product_category' ) && is_product_category() ) {
                $queried_obj = get_queried_object();
                if ( $queried_obj && ( in_array( $queried_obj->slug, $cats, true ) || in_array( (int) $queried_obj->term_id, array_map( 'intval', $cats ), true ) ) ) {
                    $condition_matched = true;
                }
            }
            if ( ! $condition_matched && function_exists( 'is_product' ) && is_product() ) {
                global $post;
                if ( $post ) {
                    foreach ( $cats as $c ) {
                        if ( is_numeric( $c ) ) {
                            if ( has_term( intval( $c ), 'product_cat', $post->ID ) ) {
                                $condition_matched = true;
                                break;
                            }
                        } else {
                            if ( has_term( sanitize_text_field( $c ), 'product_cat', $post->ID ) ) {
                                $condition_matched = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ( ! $condition_matched && ! empty( $rules['url_contains'] ) ) {
            $needle = trim( $rules['url_contains'] );

            // Read REQUEST_URI via filter_input() where possible. This avoids using the superglobal directly.
            $request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_DEFAULT );

            // Fallback for environments where filter_input() returns null (rare).
            if ( null === $request_uri && isset( $_SERVER['REQUEST_URI'] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- fallback when filter_input() is unavailable.
                $request_uri = wp_unslash($_SERVER['REQUEST_URI']);
            }

            if ( is_string( $request_uri ) ) {
                // Uns lash and sanitize before using.
                $request_uri = wp_unslash( $request_uri );
                $request_uri = sanitize_text_field( $request_uri );

                // Normalize needle too (defensive).
                $needle = sanitize_text_field( $needle );

                if ( $needle !== '' && false !== strpos( $request_uri, $needle ) ) {
                    $condition_matched = true;
                }
            }
        }



        if ( 'include' === $mode ) {
            return (bool) $condition_matched;
        } elseif ( 'exclude' === $mode ) {
            return ! (bool) $condition_matched;
        }

        return true;
    }
}
