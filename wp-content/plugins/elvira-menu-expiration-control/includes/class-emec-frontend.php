<?php
if (!defined('ABSPATH')) exit;

/**
 * EMEC Frontend - free runtime checks
 */
class EMEC_Frontend {
    public static function init() {
        add_filter('wp_get_nav_menu_items', [__CLASS__, 'emec_filter_menu_items'], 10, 3);
    }

    public static function emec_filter_menu_items($items, $menu, $args) {
        // IMPORTANT: Do not alter menus in admin/editor/REST/AJAX requests.
        if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST ) || ( defined('DOING_AJAX') && DOING_AJAX ) ) {
            return $items;
        }

        if (empty($items)) return $items;

        $tz = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $out = [];

        foreach ($items as $item) {
            $visible = true;

            // Canonical menu item post ID: prefer db_id (wp_nav_menu_items sometimes includes both ID/db_id)
            $menu_item_id = isset($item->db_id) && $item->db_id ? (int) $item->db_id : (int) $item->ID;

            // Basic free meta
            $enabled = get_post_meta($menu_item_id, '_emec_enable', true) === '1';
            $start   = (string) get_post_meta($menu_item_id, '_emec_start', true);
            $end     = (string) get_post_meta($menu_item_id, '_emec_end', true);

            if ($enabled) {
                if ($start) {
                    try {
                        $s = new DateTimeImmutable($start, $tz);
                        if ($now < $s) $visible = false;
                    } catch (Exception $e) {
                        // ignore parse errors
                    }
                }
                if ($end && $visible) {
                    try {
                        $e = new DateTimeImmutable($end, $tz);
                        if ($now > $e) $visible = false;
                    } catch (Exception $e) {
                        // ignore parse errors
                    }
                }
            }

            if ($visible) {
                $visibility = get_post_meta($menu_item_id, '_emec_visibility', true) ?: 'everyone';

                switch ($visibility) {
                    case 'logged_in':
                        if (!is_user_logged_in()) $visible = false;
                        break;

                    case 'logged_out':
                        if (is_user_logged_in()) $visible = false;
                        break;

                    case 'roles':
                        // Check saved roles meta (array of role slugs). If empty => hide.
                        $allowed_roles = get_post_meta($menu_item_id, '_emec_roles', true);
                        if (!is_array($allowed_roles)) $allowed_roles = [];

                        if (empty($allowed_roles)) {
                            // No roles defined -> hide (safer default)
                            $visible = false;
                        } else {
                            if (!is_user_logged_in()) {
                                $visible = false;
                            } else {
                                $user = wp_get_current_user();
                                $user_roles = (array) $user->roles;
                                if (empty($user_roles) || !array_intersect($allowed_roles, $user_roles)) {
                                    $visible = false;
                                }
                            }
                        }
                        break;

                    default:
                        // 'everyone' or unknown -> visible
                        break;
                }
            }

            if ($visible) $out[] = $item;
        }

        // Remove orphaned children
        return self::emec_relink_menu_tree($out);
    }

    private static function emec_relink_menu_tree($items) {
        $ids = wp_list_pluck($items, 'ID');
        foreach ($items as $k => $it) {
            if ($it->menu_item_parent && !in_array((int) $it->menu_item_parent, $ids, true)) {
                unset($items[$k]);
            }
        }
        return array_values($items);
    }
}