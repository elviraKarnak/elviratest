<?php
/**
 * Plugin Name: Menu Expiration Control
 * Description: Hide or show individual menu items based on date/time windows and user visibility rules (logged-in / logged-out / by role).
 * Version: 1.0.0
 * Author: Raihan Reza
 * Text Domain: menu-expiration-control
 */

if (!defined('ABSPATH')) exit;

final class MEC_Menu_Expiration_Control {
    const META_ENABLE     = '_mec_enable';
    const META_START      = '_mec_start';  // 'YYYY-MM-DDTHH:MM' (local site TZ)
    const META_END        = '_mec_end';
    const META_VISIBILITY = '_mec_visibility'; // everyone|logged_in|logged_out|roles
    const META_ROLES      = '_mec_roles';      // array of role slugs

    public function __construct() {
        // Add custom fields to each menu item row in Appearance ▸ Menus
        add_action('wp_nav_menu_item_custom_fields', [$this, 'render_fields'], 10, 4);
        add_action('admin_head-nav-menus.php', [$this, 'admin_head_assets']);

        // Save fields
        add_action('wp_update_nav_menu_item', [$this, 'save_fields'], 10, 3);

        // Filter visibility before menus render (classic, Elementor, Navigation Block)
        add_filter('wp_get_nav_menu_items', [$this, 'filter_menu_items'], 10, 3);
    }

    /** ---------------------------
     * Admin UI
     * --------------------------- */
    public function admin_head_assets() {
        ?>
        <style>
            .mec-fields {margin-top:8px;border-top:1px dashed #ddd;padding-top:8px;}
            .mec-fields .description {margin:4px 0 8px;color:#555;}
            .mec-grid {display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:flex-start;}
            .mec-grid .wide {grid-column:1 / -1;}
            .mec-inline {display:flex;gap:12px;align-items:center;}
            .mec-roles {min-height:80px;width:100%;}
            .mec-note {font-size:12px;color:#666;margin-top:4px;}
            .mec-badge {display:inline-block;background:#2271b1;color:#fff;border-radius:3px;padding:2px 6px;font-size:11px;margin-left:6px;}
        </style>
        <?php
    }

    public function render_fields($item_id, $item, $depth, $args) {
        wp_nonce_field('mec_save_' . $item_id, 'mec_nonce_' . $item_id);

        $enable     = get_post_meta($item_id, self::META_ENABLE, true) === '1';
        $start      = (string) get_post_meta($item_id, self::META_START, true);
        $end        = (string) get_post_meta($item_id, self::META_END, true);
        $visibility = get_post_meta($item_id, self::META_VISIBILITY, true);
        if (!$visibility) $visibility = 'everyone';
        $roles      = (array) get_post_meta($item_id, self::META_ROLES, true);

        $roles_list = $this->get_all_roles();
        ?>
        <div class="mec-fields description-wide">
            <strong><?php esc_html_e('Menu Expiration & Visibility', 'menu-expiration-control'); ?></strong>
            <span class="mec-badge"><?php esc_html_e('MEC', 'menu-expiration-control'); ?></span>
            <p class="description"><?php esc_html_e('Control when and to whom this menu item is visible.', 'menu-expiration-control'); ?></p>

            <div class="mec-grid">
                <label class="mec-inline wide">
                    <input type="checkbox" name="mec_enable[<?php echo esc_attr($item_id); ?>]" value="1" <?php checked($enable, true); ?> />
                    <span><?php esc_html_e('Enable scheduling window', 'menu-expiration-control'); ?></span>
                </label>

                <label>
                    <span class="description"><?php esc_html_e('Visible from (start)', 'menu-expiration-control'); ?></span>
                    <input type="datetime-local"
                           name="mec_start[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr($start); ?>"
                           class="widefat" />
                    <div class="mec-note"><?php printf(esc_html__('Site timezone: %s', 'menu-expiration-control'), esc_html($this->get_site_tz_name())); ?></div>
                </label>

                <label>
                    <span class="description"><?php esc_html_e('Visible until (end)', 'menu-expiration-control'); ?></span>
                    <input type="datetime-local"
                           name="mec_end[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr($end); ?>"
                           class="widefat" />
                    <div class="mec-note"><?php esc_html_e('Leave blank for no end date.', 'menu-expiration-control'); ?></div>
                </label>

                <div class="wide">
                    <span class="description"><?php esc_html_e('Visibility rule', 'menu-expiration-control'); ?></span>
                    <select name="mec_visibility[<?php echo esc_attr($item_id); ?>]" class="widefat">
                        <option value="everyone"   <?php selected($visibility, 'everyone'); ?>><?php esc_html_e('Everyone', 'menu-expiration-control'); ?></option>
                        <option value="logged_in"  <?php selected($visibility, 'logged_in'); ?>><?php esc_html_e('Logged-in users only', 'menu-expiration-control'); ?></option>
                        <option value="logged_out" <?php selected($visibility, 'logged_out'); ?>><?php esc_html_e('Logged-out users only', 'menu-expiration-control'); ?></option>
                        <option value="roles"      <?php selected($visibility, 'roles'); ?>><?php esc_html_e('Specific roles...', 'menu-expiration-control'); ?></option>
                    </select>
                    <div class="mec-note"><?php esc_html_e('If "Specific roles" is chosen, select roles below.', 'menu-expiration-control'); ?></div>
                </div>

                <div class="wide">
                    <select name="mec_roles[<?php echo esc_attr($item_id); ?>][]" class="mec-roles" multiple>
                        <?php foreach ($roles_list as $slug => $label): ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $roles, true)); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mec-note"><?php esc_html_e('Hold Ctrl/Cmd to select multiple roles.', 'menu-expiration-control'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_fields($menu_id, $menu_item_db_id, $args) {
        // Nonce
        if (!isset($_POST['mec_nonce_' . $menu_item_db_id]) || !wp_verify_nonce($_POST['mec_nonce_' . $menu_item_db_id], 'mec_save_' . $menu_item_db_id)) {
            return;
        }

        // Enable
        $enable = isset($_POST['mec_enable'][$menu_item_db_id]) ? '1' : '0';
        update_post_meta($menu_item_db_id, self::META_ENABLE, $enable);

        // Start / End (allow empty)
        $start = isset($_POST['mec_start'][$menu_item_db_id]) ? sanitize_text_field($_POST['mec_start'][$menu_item_db_id]) : '';
        $end   = isset($_POST['mec_end'][$menu_item_db_id])   ? sanitize_text_field($_POST['mec_end'][$menu_item_db_id])   : '';
        update_post_meta($menu_item_db_id, self::META_START, $start);
        update_post_meta($menu_item_db_id, self::META_END,   $end);

        // Visibility
        $visibility = isset($_POST['mec_visibility'][$menu_item_db_id]) ? sanitize_key($_POST['mec_visibility'][$menu_item_db_id]) : 'everyone';
        if (!in_array($visibility, ['everyone','logged_in','logged_out','roles'], true)) {
            $visibility = 'everyone';
        }
        update_post_meta($menu_item_db_id, self::META_VISIBILITY, $visibility);

        // Roles
        $roles = isset($_POST['mec_roles'][$menu_item_db_id]) ? (array) $_POST['mec_roles'][$menu_item_db_id] : [];
        $roles = array_values(array_filter(array_map('sanitize_key', $roles)));
        update_post_meta($menu_item_db_id, self::META_ROLES, $roles);
    }

    /** ---------------------------
     * Front-end filter
     * --------------------------- */
    public function filter_menu_items($items, $menu, $args) {
        if (empty($items)) return $items;

        $tz  = wp_timezone(); // respects site timezone
        $now = new DateTimeImmutable('now', $tz);

        $filtered = [];
        foreach ($items as $item) {
            $visible = true;

            $enable = get_post_meta($item->ID, self::META_ENABLE, true) === '1';
            $start  = (string) get_post_meta($item->ID, self::META_START, true);
            $end    = (string) get_post_meta($item->ID, self::META_END, true);

            if ($enable) {
                // If start set and now < start => hide
                if ($start) {
                    $start_dt = $this->parse_local_dt($start, $tz);
                    if ($start_dt && $now < $start_dt) {
                        $visible = false;
                    }
                }
                // If end set and now > end => hide
                if ($visible && $end) {
                    $end_dt = $this->parse_local_dt($end, $tz);
                    if ($end_dt && $now > $end_dt) {
                        $visible = false;
                    }
                }
            }

            if ($visible) {
                $visibility = get_post_meta($item->ID, self::META_VISIBILITY, true) ?: 'everyone';
                switch ($visibility) {
                    case 'logged_in':
                        if (!is_user_logged_in()) $visible = false;
                        break;
                    case 'logged_out':
                        if (is_user_logged_in()) $visible = false;
                        break;
                    case 'roles':
                        // Only show if user has ANY of selected roles
                        $allowed_roles = (array) get_post_meta($item->ID, self::META_ROLES, true);
                        if (empty($allowed_roles)) {
                            // No roles selected -> hide for safety
                            $visible = false;
                        } else {
                            if (!is_user_logged_in()) {
                                $visible = false;
                            } else {
                                $user    = wp_get_current_user();
                                $user_has_role = (bool) array_intersect($allowed_roles, (array) $user->roles);
                                if (!$user_has_role) $visible = false;
                            }
                        }
                        break;
                    case 'everyone':
                    default:
                        // do nothing
                        break;
                }
            }

            if ($visible) {
                $filtered[] = $item;
            }
        }

        // Rebuild parent/child relationships because removing items can orphan children
        return $this->relink_menu_tree($filtered);
    }

    /** Utilities */
    private function parse_local_dt($value, DateTimeZone $tz) {
        // Expecting 'YYYY-MM-DDTHH:MM' (HTML datetime-local); allow seconds optionally.
        // Return DateTimeImmutable|false
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(\:\d{2})?$/', $value) !== 1) {
                // Attempt fallback
                return new DateTimeImmutable($value, $tz);
            }
            return new DateTimeImmutable($value, $tz);
        } catch (Exception $e) {
            return false;
        }
    }

    private function relink_menu_tree($items) {
        // If a parent is hidden, WordPress normally promotes children; we’ll remove children whose parent is gone.
        $ids = wp_list_pluck($items, 'ID');
        foreach ($items as $k => $it) {
            if ($it->menu_item_parent && !in_array((int) $it->menu_item_parent, $ids, true)) {
                // Parent removed; drop this child to avoid floating orphans
                unset($items[$k]);
            }
        }
        return array_values($items);
    }

    private function get_site_tz_name() {
        $tz = get_option('timezone_string');
        if (!$tz || $tz === '') {
            $offset = (float) get_option('gmt_offset');
            $sign = $offset >= 0 ? '+' : '-';
            $hours = floor(abs($offset));
            $mins  = (abs($offset) - $hours) * 60;
            return sprintf('UTC%s%02d:%02d', $sign, $hours, $mins);
        }
        return $tz;
    }

    private function get_all_roles() {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $roles = get_editable_roles();
        $out = [];
        foreach ($roles as $slug => $data) {
            $out[$slug] = translate_user_role($data['name']);
        }
        return $out;
    }
}

new MEC_Menu_Expiration_Control();
