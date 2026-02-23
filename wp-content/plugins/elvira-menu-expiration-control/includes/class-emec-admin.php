<?php
if (!defined('ABSPATH')) exit;

/**
 * EMEC Admin class — renders fields in Appearance > Menus menu item rows
 */
class EMEC_Admin {

    const META_ENABLE     = '_emec_enable';
    const META_START      = '_emec_start';
    const META_END        = '_emec_end';
    const META_VISIBILITY = '_emec_visibility';
    const META_ROLES      = '_emec_roles'; // stores array of role slugs

    public static function init() {
        add_action('wp_nav_menu_item_custom_fields', [__CLASS__, 'emec_render_fields'], 10, 4);
        add_action('wp_update_nav_menu_item', [__CLASS__, 'emec_save_fields'], 10, 3);
        add_action('admin_enqueue_scripts', [__CLASS__, 'emec_admin_assets']);
    }

    public static function emec_admin_assets($hook) {
        if ($hook !== 'nav-menus.php') return;
        wp_enqueue_style('emec-admin', EMEC_PLUGIN_URL . 'assets/css/emec-style.css', [], EMEC_VERSION);

        // Load local Flatpickr files instead of remote CDN
        wp_enqueue_style(
            'flatpickr-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_enqueue_script(
            'flatpickr-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
        wp_add_inline_script('flatpickr-js', "document.addEventListener('DOMContentLoaded', function(){ if(window.flatpickr){ flatpickr('.snb-datetime', {enableTime:true, dateFormat:'Y-m-d H:i', altInput:true, altFormat:'F j, Y H:i'}); } });");
    }

    public static function emec_render_fields($item_id, $item, $depth, $args) {
        wp_nonce_field('emec_save_' . $item_id, 'emec_nonce_' . $item_id);

        // existing free meta
        $enabled = get_post_meta($item_id, self::META_ENABLE, true) === '1';
        $start = get_post_meta($item_id, self::META_START, true);
        $end = get_post_meta($item_id, self::META_END, true);
        $visibility = get_post_meta($item_id, self::META_VISIBILITY, true) ?: 'everyone';

        $roles = get_post_meta($item_id, self::META_ROLES, true);
        if (!is_array($roles)) $roles = [];

        // build editable roles list
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles_list_raw = get_editable_roles(); // slug => array(name=>..., capabilities=>...)
        $roles_list = [];
        foreach ($roles_list_raw as $slug => $data) {
            $roles_list[$slug] = translate_user_role($data['name']);
        }

        $value = $start ?? '';

        // Convert stored datetime (MySQL-like) to HTML5 datetime-local (Y-m-d\\TH:i)
        $dt_val = '';
        if (!empty($value)) {
            $ts = strtotime($value);
            if ($ts !== false) $dt_val = gmdate('Y-m-d\\TH:i', $ts);
        }

        $value2 = $end ?? '';

        // Convert stored datetime (MySQL-like) to HTML5 datetime-local (Y-m-d\\TH:i)
        $dt_val2 = '';
        if (!empty($value2)) {
            $ts2 = strtotime($value2);
            if ($ts2 !== false) $dt_val2 = gmdate('Y-m-d\\TH:i', $ts2);
        }
        ?>
        <div class="emec-fields description-wide">
            <strong><?php esc_html_e('Menu Expiration Control', 'elvira-menu-expiration-control'); ?></strong>
            <p class="description"><?php esc_html_e('Basic scheduling (Free): set start/end and simple visibility (everyone / logged-in / logged-out / roles).', 'elvira-menu-expiration-control'); ?></p>

            <p>
                <label>
                    <input type="checkbox" name="emec_enable[<?php echo esc_attr($item_id); ?>]" value="1" <?php checked($enabled); ?> />
                    <?php esc_html_e('Enable schedule', 'elvira-menu-expiration-control'); ?>
                </label>
            </p>

            <p>
                <label><?php esc_html_e('Start (datetime)', 'elvira-menu-expiration-control'); ?><br/>
                <input type="datetime-local" name="emec_start[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($dt_val); ?>" class="widefat snb-datetime" /></label>
            </p>

            <p>
                <label><?php esc_html_e('End (datetime)', 'elvira-menu-expiration-control'); ?><br/>
                <input type="datetime-local" name="emec_end[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($dt_val2); ?>" class="widefat snb-datetime" /></label>
            </p>

            <p>
                <label><?php esc_html_e('Visibility', 'elvira-menu-expiration-control'); ?><br/>
                <select name="emec_visibility[<?php echo esc_attr($item_id); ?>]" class="widefat emec-visibility-select" data-item="<?php echo esc_attr($item_id); ?>">>
                    <option value="everyone" <?php selected($visibility, 'everyone'); ?>><?php esc_html_e('Everyone', 'elvira-menu-expiration-control'); ?></option>
                    <option value="logged_in" <?php selected($visibility, 'logged_in'); ?>><?php esc_html_e('Logged-in users', 'elvira-menu-expiration-control'); ?></option>
                    <option value="logged_out" <?php selected($visibility, 'logged_out'); ?>><?php esc_html_e('Logged-out users', 'elvira-menu-expiration-control'); ?></option>
                    <option value="roles" <?php selected($visibility, 'roles'); ?>><?php esc_html_e('Specific roles', 'elvira-menu-expiration-control'); ?></option>
                </select>
                </label>
            </p>

            <p class="description"><?php esc_html_e('For role-specific rules and multiple windows, activate Pro with your license key.', 'elvira-menu-expiration-control'); ?></p>

            <!-- Roles multi-select (only relevant when "roles" visibility chosen) -->
            <div class="mecp-roles-wrap" id="mecp-roles-wrap-<?php echo esc_attr($item_id); ?>" style="<?php echo ($visibility === 'roles') ? '' : 'display:none;'; ?>">
                <label><?php esc_html_e('Allowed roles (any)', 'elvira-menu-expiration-control'); ?></label>
                <select name="mec_roles[<?php echo esc_attr($item_id); ?>][]" class="mec-roles widefat" multiple>
                    <?php foreach ($roles_list as $slug => $label): ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $roles, true)); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="mec-note"><?php esc_html_e('Hold Ctrl/Cmd to select multiple roles.', 'elvira-menu-expiration-control'); ?></div>
            </div>

            <p class="description"><?php esc_html_e('For role-specific rules and multiple windows, activate Pro with your license key.', 'elvira-menu-expiration-control'); ?></p>
        </div>

        <script type="text/javascript">
        (function($){
            // Toggle roles select visibility when the dropdown changes (only inside this menu item)
            $(document).on('change', '.emec-visibility-select[data-item="<?php echo esc_js($item_id); ?>"]', function(){
                var val = $(this).val();
                var wrap = $('#mecp-roles-wrap-<?php echo esc_js($item_id); ?>');
                if (val === 'roles') {
                    wrap.show();
                } else {
                    // clear selection? keep values but hide visually (we still save only when visibility==roles)
                    wrap.hide();
                }
            });

            // stop propagation for clicks inside our fields to avoid WP menu editor collapsing behavior
            $('#mecp-roles-wrap-<?php echo esc_js($item_id); ?>').on('click', 'select, input, textarea', function(e){ e.stopPropagation(); });
        })(jQuery);
        </script>
        <?php
    }

    public static function emec_save_fields($menu_id, $menu_item_db_id, $args) {
		
		$nonce_key = 'emec_nonce_' . $menu_item_db_id;

		$raw = filter_input( INPUT_POST, $nonce_key, FILTER_UNSAFE_RAW );
		if ( null === $raw || '' === $raw ) {
			return;
		}
		$nonce = wp_unslash( $raw );
		if ( ! wp_verify_nonce( $nonce, 'emec_save_' . $menu_item_db_id ) ) {
			return;
		}

        // enable
        $enable = isset($_POST['emec_enable'][$menu_item_db_id]) ? '1' : '0';
        update_post_meta($menu_item_db_id, self::META_ENABLE, $enable);

        // start / end
        //$start = isset($_POST['emec_start'][$menu_item_db_id]) ? sanitize_text_field($_POST['emec_start'][$menu_item_db_id]) : '';
		$post = wp_unslash( $_POST );

		// Now read & sanitize from the local array
		$start = isset( $post['emec_start'][ $menu_item_db_id ] ) ? sanitize_text_field( $post['emec_start'][ $menu_item_db_id ] )  : '';
		$end = isset( $post['emec_end'][ $menu_item_db_id ] ) ? sanitize_text_field( $post['emec_end'][ $menu_item_db_id ] ) : '';
        //$end   = isset($_POST['emec_end'][$menu_item_db_id])   ? sanitize_text_field($_POST['emec_end'][$menu_item_db_id])   : '';
        update_post_meta($menu_item_db_id, self::META_START, $start);
        update_post_meta($menu_item_db_id, self::META_END, $end);

        // visibility
        $visibility = isset($_POST['emec_visibility'][$menu_item_db_id]) ? sanitize_key($_POST['emec_visibility'][$menu_item_db_id]) : 'everyone';
        update_post_meta($menu_item_db_id, self::META_VISIBILITY, $visibility);

        // roles (only save if provided). Accepts multiple values from select.
        $roles_post = isset( $post['mec_roles'][ $menu_item_db_id ] ) && is_array( $post['mec_roles'][ $menu_item_db_id ] ) ? array_map( 'sanitize_key', $post['mec_roles'][ $menu_item_db_id ] ) : [];
        //$roles_post = isset($_POST['mec_roles'][$menu_item_db_id]) && is_array($_POST['mec_roles'][$menu_item_db_id]) ? (array) $_POST['mec_roles'][$menu_item_db_id] : [];

        // sanitize role keys and keep only non-empty
        $roles_clean = array_values(array_filter(array_map('sanitize_key', $roles_post), function($v){ return $v !== ''; }));

        if ($visibility === 'roles' && !empty($roles_clean)) {
            update_post_meta($menu_item_db_id, self::META_ROLES, $roles_clean);
        } else {
            // if visibility not 'roles' or no roles selected, remove roles meta to avoid stale data
            delete_post_meta($menu_item_db_id, self::META_ROLES);
        }
    }
}
