<?php
/**
 * Plugin Name: Menu Expiration Control
 * Description: Adds start and expiration dates to WordPress menu items, showing them only within the set date range.
 * Version: 1.2
 * Author: Raihan Reza
 * Author URI: https://elvirainfotech.com
 * Tested up to: 6.9
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Hook to add custom fields for start and expiration dates to menu items.
function menuec_add_menu_dates_fields($item_id, $item, $depth, $args) {
    // Add a nonce field for verification
    wp_nonce_field('menuec_save_menu_dates_' . $item_id, 'menuec_menu_dates_nonce[' . $item_id . ']');
    $start_date = get_post_meta($item_id, '_menu_start_date', true);
    $expiry_date = get_post_meta($item_id, '_menu_expiry_date', true);
    $start_time = get_post_meta($item_id, '_menu_start_time', true);
    $expiry_time = get_post_meta($item_id, '_menu_expiry_time', true);
    
    // Get WordPress timezone setting
    $timezone_string = get_option('timezone_string');
    $gmt_offset = get_option('gmt_offset');
    
    // Format timezone display
    if ($timezone_string) {
        $timezone_display = $timezone_string;
    } elseif ($gmt_offset) {
        $timezone_display = 'UTC' . ($gmt_offset >= 0 ? '+' : '') . $gmt_offset;
    } else {
        $timezone_display = 'UTC';
    }
    
    // Parse time values
    $start_hour = '';
    $start_minute = '';
    $start_period = 'AM';
    if (!empty($start_time)) {
        $time_parts = explode(':', $start_time);
        if (count($time_parts) === 3) {
            $start_hour = intval($time_parts[0]);
            $start_minute = intval($time_parts[1]);
            $start_period = $time_parts[2];
        }
    }
    
    $expiry_hour = '';
    $expiry_minute = '';
    $expiry_period = 'AM';
    if (!empty($expiry_time)) {
        $time_parts = explode(':', $expiry_time);
        if (count($time_parts) === 3) {
            $expiry_hour = intval($time_parts[0]);
            $expiry_minute = intval($time_parts[1]);
            $expiry_period = $time_parts[2];
        }
    }
    ?>
    <p class="description description-wide" style="background: #f0f0f1; padding: 10px; border-left: 4px solid #2271b1; margin: 10px 0;">
        <strong><?php esc_html_e('Time Zone Information:', 'menu-expiration-control'); ?></strong><br>
        <?php
        printf(
            /* translators: %s: Current timezone setting */
            esc_html__('All times use your site\'s timezone: %s', 'menu-expiration-control'),
            '<strong>' . esc_html($timezone_display) . '</strong>'
        );
        ?><br>
        <small><?php esc_html_e('You can change the timezone in Settings > General', 'menu-expiration-control'); ?></small>
    </p>
    <p class="field-start_date description description-wide">
        <label for="edit-menu-item-start-date-<?php echo esc_attr($item_id); ?>">
            <?php esc_html_e('Menu Start Date (YYYY-MM-DD)', 'menu-expiration-control'); ?><br>
            <input type="text" id="edit-menu-item-start-date-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-start-date" name="menu-item-start-date[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($start_date); ?>" placeholder="YYYY-MM-DD" />
        </label>
    </p>
    <p class="field-start_time description description-wide">
        <label>
            <?php esc_html_e('Menu Start Time', 'menu-expiration-control'); ?><br>
            <select name="menu-item-start-hour[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="">HH</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($start_hour, $i); ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
                <?php endfor; ?>
            </select>
            :
            <select name="menu-item-start-minute[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="">MM</option>
                <?php for ($i = 0; $i <= 59; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($start_minute, (string)$i); ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
                <?php endfor; ?>
            </select>
            <select name="menu-item-start-period[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="AM" <?php selected($start_period, 'AM'); ?>>AM</option>
                <option value="PM" <?php selected($start_period, 'PM'); ?>>PM</option>
            </select>
        </label>
    </p>
    <p class="field-expiry_date description description-wide">
        <label for="edit-menu-item-expiry-date-<?php echo esc_attr($item_id); ?>">
            <?php esc_html_e('Menu Stop Date (YYYY-MM-DD)', 'menu-expiration-control'); ?><br>
            <input type="text" id="edit-menu-item-expiry-date-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-expiry-date" name="menu-item-expiry-date[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($expiry_date); ?>" placeholder="YYYY-MM-DD" />
        </label>
    </p>
    <p class="field-expiry_time description description-wide">
        <label>
            <?php esc_html_e('Menu Stop Time', 'menu-expiration-control'); ?><br>
            <select name="menu-item-expiry-hour[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="">HH</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($expiry_hour, $i); ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
                <?php endfor; ?>
            </select>
            :
            <select name="menu-item-expiry-minute[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="">MM</option>
                <?php for ($i = 0; $i <= 59; $i++): ?>
                    <option value="<?php echo esc_attr($i); ?>" <?php selected($expiry_minute, (string)$i); ?>><?php echo esc_html(sprintf('%02d', $i)); ?></option>
                <?php endfor; ?>
            </select>
            <select name="menu-item-expiry-period[<?php echo esc_attr($item_id); ?>]" style="width: 70px;">
                <option value="AM" <?php selected($expiry_period, 'AM'); ?>>AM</option>
                <option value="PM" <?php selected($expiry_period, 'PM'); ?>>PM</option>
            </select>
        </label>
    </p>
    <?php
}
add_action('wp_nav_menu_item_custom_fields', 'menuec_add_menu_dates_fields', 10, 4);

// Save the menu item's start and expiry dates.
function menuec_save_menu_dates_fields($menu_id, $menu_item_db_id) {
    // Check if the nonce is set and valid.
    if (!isset($_POST['menuec_menu_dates_nonce'][$menu_item_db_id])) {
        return;
    }

    // Retrieve and sanitize the nonce
    $nonce = isset($_POST['menuec_menu_dates_nonce'][$menu_item_db_id]) ? sanitize_text_field(wp_unslash($_POST['menuec_menu_dates_nonce'][$menu_item_db_id])) : '';

    // Verify the nonce
    if (!wp_verify_nonce($nonce, 'menuec_save_menu_dates_' . $menu_item_db_id)) {
        return;
    }

    // Check user capabilities
    if (!current_user_can('edit_theme_options')) {
        return;
    }

    // Sanitize and save the start date.
    if (isset($_POST['menu-item-start-date'][$menu_item_db_id])) {
        $start_date = sanitize_text_field(wp_unslash($_POST['menu-item-start-date'][$menu_item_db_id]));
        update_post_meta($menu_item_db_id, '_menu_start_date', $start_date);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_start_date');
    }

    // Save start time
    if (isset($_POST['menu-item-start-hour'][$menu_item_db_id]) && 
        isset($_POST['menu-item-start-minute'][$menu_item_db_id]) && 
        isset($_POST['menu-item-start-period'][$menu_item_db_id])) {
        $start_hour = sanitize_text_field(wp_unslash($_POST['menu-item-start-hour'][$menu_item_db_id]));
        $start_minute = sanitize_text_field(wp_unslash($_POST['menu-item-start-minute'][$menu_item_db_id]));
        $start_period = sanitize_text_field(wp_unslash($_POST['menu-item-start-period'][$menu_item_db_id]));
        if (!empty($start_hour) && $start_minute !== '') {
            $start_time = sprintf('%02d', $start_hour) . ':' . sprintf('%02d', $start_minute) . ':' . $start_period;
            update_post_meta($menu_item_db_id, '_menu_start_time', $start_time);
        } else {
            delete_post_meta($menu_item_db_id, '_menu_start_time');
        }
    } else {
        delete_post_meta($menu_item_db_id, '_menu_start_time');
    }

    // Unsplash and sanitize the expiry date.
    if (isset($_POST['menu-item-expiry-date'][$menu_item_db_id])) {
        $expiry_date = sanitize_text_field(wp_unslash($_POST['menu-item-expiry-date'][$menu_item_db_id]));
        update_post_meta($menu_item_db_id, '_menu_expiry_date', $expiry_date);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_expiry_date');
    }

    // Save expiry time
    if (isset($_POST['menu-item-expiry-hour'][$menu_item_db_id]) && 
        isset($_POST['menu-item-expiry-minute'][$menu_item_db_id]) && 
        isset($_POST['menu-item-expiry-period'][$menu_item_db_id])) {
        $expiry_hour = sanitize_text_field(wp_unslash($_POST['menu-item-expiry-hour'][$menu_item_db_id]));
        $expiry_minute = sanitize_text_field(wp_unslash($_POST['menu-item-expiry-minute'][$menu_item_db_id]));
        $expiry_period = sanitize_text_field(wp_unslash($_POST['menu-item-expiry-period'][$menu_item_db_id]));
        if (!empty($expiry_hour) && $expiry_minute !== '') {
            $expiry_time = sprintf('%02d', $expiry_hour) . ':' . sprintf('%02d', $expiry_minute) . ':' . $expiry_period;
            update_post_meta($menu_item_db_id, '_menu_expiry_time', $expiry_time);
        } else {
            delete_post_meta($menu_item_db_id, '_menu_expiry_time');
        }
    } else {
        delete_post_meta($menu_item_db_id, '_menu_expiry_time');
    }
}
add_action('wp_update_nav_menu_item', 'menuec_save_menu_dates_fields', 10, 2);



// Filter menu items based on the start and expiration dates.
function menuec_filter_menu_items_by_dates($items) {
    $current_time = current_time('timestamp');
    foreach ($items as $key => $item) {
        $start_date = get_post_meta($item->ID, '_menu_start_date', true);
        $expiry_date = get_post_meta($item->ID, '_menu_expiry_date', true);
        $start_time = get_post_meta($item->ID, '_menu_start_time', true);
        $expiry_time = get_post_meta($item->ID, '_menu_expiry_time', true);

        $should_hide = false;

        // Check start date/time
        if (!empty($start_date)) {
            $start_datetime = $start_date;
            if (!empty($start_time)) {
                $time_parts = explode(':', $start_time);
                if (count($time_parts) === 3) {
                    $hour = intval($time_parts[0]);
                    $minute = intval($time_parts[1]);
                    $period = $time_parts[2];
                    
                    // Convert to 24-hour format
                    if ($period == 'PM' && $hour != 12) {
                        $hour += 12;
                    } elseif ($period == 'AM' && $hour == 12) {
                        $hour = 0;
                    }
                    $start_datetime .= ' ' . sprintf('%02d:%02d:00', $hour, $minute);
                } else {
                    $start_datetime .= ' 00:00:00';
                }
            } else {
                $start_datetime .= ' 00:00:00';
            }
            
            if (strtotime($start_datetime) > $current_time) {
                $should_hide = true;
            }
        }

        // Check expiry date/time
        if (!empty($expiry_date)) {
            $expiry_datetime = $expiry_date;
            if (!empty($expiry_time)) {
                $time_parts = explode(':', $expiry_time);
                if (count($time_parts) === 3) {
                    $hour = intval($time_parts[0]);
                    $minute = intval($time_parts[1]);
                    $period = $time_parts[2];
                    
                    // Convert to 24-hour format
                    if ($period == 'PM' && $hour != 12) {
                        $hour += 12;
                    } elseif ($period == 'AM' && $hour == 12) {
                        $hour = 0;
                    }
                    $expiry_datetime .= ' ' . sprintf('%02d:%02d:00', $hour, $minute);
                } else {
                    $expiry_datetime .= ' 23:59:59';
                }
            } else {
                $expiry_datetime .= ' 23:59:59';
            }
            
            if (strtotime($expiry_datetime) < $current_time) {
                $should_hide = true;
            }
        }

        if ($should_hide) {
            unset($items[$key]);
        }
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'menuec_filter_menu_items_by_dates');


// Enqueue necessary scripts and styles for the datepicker
function menuec_enqueue_admin_scripts($hook) {
    if ($hook !== 'nav-menus.php') {
        return;
    }

    // Enqueue jQuery and jQuery UI Datepicker
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-datepicker');
    
    // Enqueue local jQuery UI CSS
    $local_css_path = plugin_dir_path(__FILE__) . 'assets/jquery-ui.css';
    if (file_exists($local_css_path)) {
        wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'assets/jquery-ui.css', array(), '1.12.1');
    }

    // Add inline script for datepicker initialization
    wp_add_inline_script('jquery-ui-datepicker', "
        jQuery(document).ready(function($) {
            // Initialize datepicker on existing fields
            $('.edit-menu-item-start-date, .edit-menu-item-expiry-date').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-10:+10'
            });
            
            // Reinitialize datepicker when new menu items are added
            $(document).on('focus', '.edit-menu-item-start-date, .edit-menu-item-expiry-date', function() {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        dateFormat: 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true,
                        yearRange: '-10:+10'
                    });
                }
            });
        });
    ");
}
add_action('admin_enqueue_scripts', 'menuec_enqueue_admin_scripts');