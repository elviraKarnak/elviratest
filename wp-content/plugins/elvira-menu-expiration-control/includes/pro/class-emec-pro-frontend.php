<?php
if (!defined('ABSPATH')) exit;

/**
 * Pro frontend: supports multiple rules per menu item and day-of-week/time windows.
 *
 * Hooks run at priority 30 so they execute after the free plugin's filters (priority 10).
 */
class EMEC_Pro_Frontend {
    const META_KEY = '_emecp_rules';

    public static function init() {
        // Run after free plugin (free plugin uses priority 10)
        add_filter('wp_get_nav_menu_items', [__CLASS__, 'emec_apply_pro_rules'], 30, 3);
        add_action('wp_nav_menu_item_custom_fields', [__CLASS__, 'emec_render_pro_fields'], 30, 4);
        add_action('wp_update_nav_menu_item', [__CLASS__, 'emec_save_pro_fields'], 30, 3);

        // Enqueue admin assets (flatpickr etc)
        add_action('admin_enqueue_scripts', [__CLASS__, 'emec_admin_assets']);
    }

    /**
     * Enqueue admin assets and provide JS initializer for flatpickr.
     * Call window.EMECP_init_flatpickr(root) to initialize flatpickr on any new DOM subtree.
     */
    public static function emec_admin_assets($hook) {
        if ($hook !== 'nav-menus.php') return;

        // Replace EMEC_PLUGIN_URL and EMEC_VERSION with your plugin constants / paths
        if ( defined('EMEC_PLUGIN_URL') ) {
            $base = EMEC_PLUGIN_URL;
        } else {
            $base = plugin_dir_url( dirname(__FILE__) );
        }
        $version = defined('EMEC_VERSION') ? EMEC_VERSION : '1';

        wp_enqueue_style('emec-admin', $base . 'assets/css/emec-style.css', [], $version);

        // Load local Flatpickr files instead of remote CDN
        wp_enqueue_style(
            'flatpickr-css',
            $base . 'assets/css/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_enqueue_script(
            'flatpickr-js',
            $base . 'assets/js/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );

        // Inline script: create a reusable initializer and run it on DOMContentLoaded
        // NOTE: Avoid heredoc/nowdoc — use a normal quoted string so Squiz rules are happy.
        $inline = '
            // Expose a reusable initializer that accepts a root DOM node (or undefined -> document)
            window.EMECP_init_flatpickr = function(root) {
                try {
                    if (!window.flatpickr) return;
                    root = root || document;
                    // Use querySelectorAll for compatibility
                    var nodes = root.querySelectorAll ? root.querySelectorAll(".snb-datetime") : [];
                    // NodeList.forEach should exist in modern WP admin; fallback to for-loop
                    if (nodes.forEach) {
                        nodes.forEach(function(el){
                            // Avoid double initialization
                            if (el._emec_flatpickr) return;
                            el._emec_flatpickr = window.flatpickr(el, {
                                enableTime: true,
                                dateFormat: "Y-m-d H:i",
                                allowInput: true
                            });
                        });
                    } else {
                        for (var i = 0; i < nodes.length; i++) {
                            var el = nodes[i];
                            if (el._emec_flatpickr) continue;
                            el._emec_flatpickr = window.flatpickr(el, {
                                enableTime: true,
                                dateFormat: "Y-m-d H:i",
                                allowInput: true
                            });
                        }
                    }
                } catch (e) {
                    // fail silently in admin
                }
            };

            document.addEventListener("DOMContentLoaded", function(){
                if (typeof window.EMECP_init_flatpickr === "function") {
                    window.EMECP_init_flatpickr();
                }
            });
            ';

        wp_add_inline_script('flatpickr-js', $inline);
    }

    /**
     * Renders the pro meta UI (multi-row editor).
     * Only visible when license is active.
     */
    public static function emec_render_pro_fields($item_id, $item, $depth, $args) {
        // Only show pro UI when license active
        if (!class_exists('EMEC_License') || !EMEC_License::emec_is_pro_active()) {
            return;
        }

        wp_nonce_field('mecp_save_' . $item_id, 'mecp_nonce_' . $item_id);

        $rules = get_post_meta($item_id, self::META_KEY, true);
        if (!is_array($rules)) $rules = [];

        $days = ['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'];

        /**
         * Allowed tags/attributes for the template and printed rows.
         * Keep this list in sync with the markup produced by emec_render_rule_row().
         */
        $allowed = [
            'div'      => [ 'class' => true, 'style' => true, 'data-item' => true ],
            'label'    => [ 'class' => true, 'style' => true ],
            'span'     => [ 'class' => true ],
            'input'    => [
                'type' => true, 'class' => true, 'name' => true, 'value' => true,
                'placeholder' => true, 'checked' => true, 'id' => true, 'step' => true
            ],
            'select'   => [ 'class' => true, 'name' => true, 'multiple' => true ],
            'option'   => [ 'value' => true, 'selected' => true ],
            'a'        => [ 'href' => true, 'class' => true ],
            'template' => [],
        ];

        // Build the template HTML once and sanitize it for safe embedding.
        $tmpl_html = self::emec_render_rule_row('__ITEM__', '__KEY__', [], $days, false);
        $tmpl_html_safe = wp_kses( $tmpl_html, $allowed );

        ?>
        <div class="mecp-fields description-wide">
            <strong><?php esc_html_e('Pro: Multiple Schedules & Audience', 'elvira-menu-expiration-control'); ?></strong>
            <span class="mecp-badge"><?php esc_html_e('PRO', 'elvira-menu-expiration-control'); ?></span>
            <p class="description"><?php esc_html_e('Add multiple date/time windows, restrict by day of week, and target by roles or user IDs.', 'elvira-menu-expiration-control'); ?></p>

            <div class="mecp-rules-wrap">
                <div class="mecp-rules" data-item="<?php echo esc_attr($item_id); ?>">
                    <!-- Template element left empty intentionally; JS will populate from window.EMECP_rule_template -->
                    <template class="mecp-rule-tmpl"></template>

                    <?php
                    if (empty($rules)) {
                        // render a single blank row (key time() to avoid collision)
                        echo wp_kses( self::emec_render_rule_row( $item_id, time(), [], $days ), $allowed );
                    } else {
                        foreach ($rules as $key => $rule) {
                            echo wp_kses( self::emec_render_rule_row( $item_id, $key, $rule, $days ), $allowed );
                        }
                    }
                    ?>
                </div>

                <p><a href="#" class="button button-secondary mecp-add-rule"><?php esc_html_e('+ Add another window', 'elvira-menu-expiration-control'); ?></a></p>
                <p class="description"><?php esc_html_e('Tip: If the free start/end is enabled, both free and pro rules must pass (AND logic).', 'elvira-menu-expiration-control'); ?></p>
            </div>
        </div>

        <script type="text/javascript">
        // Pass the template HTML to JS in a safe, JSON-encoded form.
        // Use wp_json_encode() inline to avoid PHPCS EscapeOutput errors.
        window.EMECP_rule_template = <?php echo wp_json_encode( $tmpl_html_safe ); ?>;
        (function($){
            // Register handlers only once across the whole admin page.
            if ( window.EMECP_PRO_INIT ) {
                return;
            }
            window.EMECP_PRO_INIT = true;

            // delegated add
            $(document).on('click', '.mecp-add-rule', function(e){
                e.preventDefault();
                var $btn  = $(this);
                var $wrap = $btn.closest('.mecp-fields').find('.mecp-rules').first();
                if (!$wrap.length) return;

                var $tmpl = $wrap.find('template.mecp-rule-tmpl').first();
                if (!$tmpl.length) return;

                // Grab HTML of the template content; if empty, fall back to global var
                var html = $tmpl.html();
                if (!html && window.EMECP_rule_template) {
                    html = window.EMECP_rule_template;
                    // also populate template for later inspection
                    $tmpl.html(html);
                }
                if (!html) return;

                // menu item id is held in data-item
                var itemId = $wrap.data('item') || '';
                if (!itemId) return;

                // create unique key (prefix letter to keep name valid)
                var id = 'k' + (new Date().getTime()) + Math.floor(Math.random() * 1000);

                // replace placeholders __ITEM__ and __KEY__
                var newHtml = html.replace(/__ITEM__/g, String(itemId)).replace(/__KEY__/g, id);

                // append to wrap
                $wrap.append(newHtml);

                // move focus to first input in the newly added block for UX
                var $last = $wrap.children('.mecp-rule').last();
                $last.find('input, select, textarea').first().focus();

                // Initialize flatpickr on the newly appended block (so datepickers work)
                if (typeof window.EMECP_init_flatpickr === 'function') {
                    // pass the DOM node (not jQuery)
                    window.EMECP_init_flatpickr( $last.get(0) );
                }
            });

            // delegated remove
            $(document).on('click', '.mecp-remove-rule', function(e){
                e.preventDefault();
                var $link = $(this);
                var $rule = $link.closest('.mecp-rule');
                if (!$rule.length) return;
                // destroy flatpickr instance if present to avoid leaks
                var el = $rule.find('.snb-datetime').get(0);
                if (el && el._emec_flatpickr) {
                    try { el._emec_flatpickr.destroy(); } catch (err) {}
                    el._emec_flatpickr = null;
                }
                $rule.remove();
            });

            // Avoid WP menu editor collapsing panels accidentally
            $(document).on('click', '.mecp-rules a, .mecp-rules input, .mecp-rules select, .mecp-rules textarea', function(e){
                e.stopPropagation();
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Render a single rule row.
     *
     * $parent_id = menu_item id (or '__ITEM__' placeholder)
     * $key = rule key (numeric id or placeholder '__KEY__')
     * $rule = array of fields
     * $days = days map
     * If $return_raw is true we return one-line string (not used here).
     */
    private static function emec_render_rule_row($parent_id, $key, $rule, $days, $return_raw=false) {
        $start = isset($rule['start']) ? $rule['start'] : '';
        $end   = isset($rule['end'])   ? $rule['end']   : '';
        $from  = isset($rule['from'])  ? $rule['from']  : '';
        $to    = isset($rule['to'])    ? $rule['to']    : '';
        $dsel  = isset($rule['days'])  ? (array)$rule['days'] : [];
        $roles = isset($rule['roles']) ? (array)$rule['roles'] : [];
        $users = isset($rule['users']) ? (string)$rule['users'] : '';

        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $all_roles = get_editable_roles();

        // Build input name prefix: mecp_rules[<parent_id>][<key>]
        // We keep raw prefix for internal composition but always esc_attr() when printing attribute values.
        $name_prefix = 'mecp_rules[' . esc_attr($parent_id) . '][' . esc_attr($key) . ']';

        $value = $start ?? '';

        // Convert stored datetime (MySQL-like) to our flatpickr-friendly format (Y-m-d H:i)
        $dt_val = '';
        if (!empty($value)) {
            $ts = strtotime($value);
            if ($ts !== false) $dt_val = gmdate('Y-m-d H:i', $ts);
        }

        $value2 = $end ?? '';
        $dt_val2 = '';
        if (!empty($value2)) {
            $ts2 = strtotime($value2);
            if ($ts2 !== false) $dt_val2 = gmdate('Y-m-d H:i', $ts2);
        }

        ob_start();
        ?>
        <div class="mecp-rule" style="border:1px solid #e2e4e7; padding:10px; border-radius:4px; margin-bottom:10px;">
            <div class="mecp-row">
                <label>
                    <span class="description"><?php esc_html_e('Start (date)', 'elvira-menu-expiration-control'); ?></span>
                    <!-- use type="text" so flatpickr can attach reliably -->
                    <input type="text" class="widefat snb-datetime" name="<?php echo esc_attr( $name_prefix . '[start]' ); ?>" value="<?php echo esc_attr($dt_val); ?>">
                </label>
                <label>
                    <span class="description"><?php esc_html_e('End (date)', 'elvira-menu-expiration-control'); ?></span>
                    <input type="text" class="widefat snb-datetime" name="<?php echo esc_attr( $name_prefix . '[end]' ); ?>" value="<?php echo esc_attr($dt_val2); ?>">
                </label>
                <div class="mecp-remove">
                    <a href="#" class="button button-link-delete mecp-remove-rule"><?php esc_html_e('Remove', 'elvira-menu-expiration-control'); ?></a>
                </div>
            </div>

            <div class="mecp-row">
                <label>
                    <span class="description"><?php esc_html_e('From (time of day)', 'elvira-menu-expiration-control'); ?></span>
                    <input type="time" class="widefat" name="<?php echo esc_attr( $name_prefix . '[from]' ); ?>" value="<?php echo esc_attr($from); ?>">
                </label>
                <label>
                    <span class="description"><?php esc_html_e('To (time of day)', 'elvira-menu-expiration-control'); ?></span>
                    <input type="time" class="widefat" name="<?php echo esc_attr( $name_prefix . '[to]' ); ?>" value="<?php echo esc_attr($to); ?>">
                </label>
                <div></div>
            </div>

            <div class="mecp-row wide">
                <div>
                    <span class="description"><?php esc_html_e('Days of week', 'elvira-menu-expiration-control'); ?></span><br>
                    <?php foreach ($days as $slug => $label): ?>
                        <label class="mecp-pill" style="margin-right:6px;">
                            <input type="checkbox" name="<?php echo esc_attr( $name_prefix . '[days][]' ); ?>" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $dsel, true)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mecp-row wide">
                <label>
                    <span class="description"><?php esc_html_e('Allowed roles (any)', 'elvira-menu-expiration-control'); ?></span>
                    <select class="widefat" name="<?php echo esc_attr( $name_prefix . '[roles][]' ); ?>" multiple>
                        <?php foreach ($all_roles as $slug => $data): ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected(in_array($slug, $roles, true)); ?>>
                                <?php echo esc_html(translate_user_role($data['name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span class="description"><?php esc_html_e('Allowed user IDs', 'elvira-menu-expiration-control'); ?></span>
                    <input type="text" class="widefat" name="<?php echo esc_attr( $name_prefix . '[users]' ); ?>" value="<?php echo esc_attr($users); ?>" placeholder="<?php esc_attr_e('e.g., 2,15,27', 'elvira-menu-expiration-control'); ?>">
                </label>
            </div>
        </div>
        <?php
        $html = trim(ob_get_clean());
        if ($return_raw) return preg_replace('/\s+/', ' ', $html);
        return $html;
    }

    /* Save, apply, emec_rule_passes, emec_relink_menu_tree (unchanged) */

    public static function emec_save_pro_fields($menu_id, $menu_item_db_id, $args) {
        // Only save when license active
        if (!class_exists('EMEC_License') || !EMEC_License::emec_is_pro_active()) {
            return;
        }

        // Verify nonce printed per-item in render_pro_fields()
        $nonce_key = 'mecp_nonce_' . $menu_item_db_id;

        $raw = filter_input( INPUT_POST, $nonce_key, FILTER_UNSAFE_RAW );

        if ( null === $raw ) {
            return;
        }

        $nonce = wp_unslash( $raw );
        if ( ! wp_verify_nonce( $nonce, 'mecp_save_' . $menu_item_db_id ) ) {
            return;
        }

        // Grab only the rules for this specific menu item
        $post = wp_unslash( $_POST );

        $all_posted = isset( $post['mecp_rules'] ) && is_array( $post['mecp_rules'] )
            ? (array) $post['mecp_rules']
            : [];
        
        if (!isset($all_posted[$menu_item_db_id]) || !is_array($all_posted[$menu_item_db_id])) {
            // nothing posted for this item => ensure meta removed
            delete_post_meta($menu_item_db_id, self::META_KEY);
            return;
        }

        $posted = (array) $all_posted[$menu_item_db_id];

        // Sanitize each posted rule entry (keys may be numeric or 'k123')
        $clean_posted = [];
        foreach ($posted as $raw_k => $raw_r) {
            $k = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $raw_k);
            if ($k === '') continue;
            if (!is_array($raw_r)) continue;

            $rule = [
                'start' => isset($raw_r['start']) ? sanitize_text_field($raw_r['start']) : '',
                'end'   => isset($raw_r['end'])   ? sanitize_text_field($raw_r['end'])   : '',
                'from'  => isset($raw_r['from'])  ? sanitize_text_field($raw_r['from'])  : '',
                'to'    => isset($raw_r['to'])    ? sanitize_text_field($raw_r['to'])    : '',
                'days'  => isset($raw_r['days'])  ? array_values(array_map('sanitize_key', (array)$raw_r['days'])) : [],
                'roles' => isset($raw_r['roles']) ? array_values(array_map('sanitize_key', (array)$raw_r['roles'])) : [],
                'users' => isset($raw_r['users']) ? preg_replace('/[^0-9,]/', '', $raw_r['users']) : '',
            ];

            if (implode('', $rule) === '') continue;
            $clean_posted[$k] = $rule;
        }

        // Re-index to numeric array to avoid lingering keys from past saves
        $final = array_values($clean_posted);

        // Delete existing meta first to guarantee a clean replace and prevent leftover data
        delete_post_meta($menu_item_db_id, self::META_KEY);

        if (empty($final)) {
            // nothing to save — meta already deleted
            return;
        }

        update_post_meta($menu_item_db_id, self::META_KEY, $final);
    }

    public static function emec_apply_pro_rules($items, $menu, $args) {
        // Do not run in admin/editor/REST/AJAX requests
        if ( is_admin() || ( defined('REST_REQUEST') && REST_REQUEST ) || ( defined('DOING_AJAX') && DOING_AJAX ) ) {
            return $items;
        }

        if (empty($items)) return $items;

        // Normalize menu -> get WP_Term to know the menu term id
        $menu_obj = wp_get_nav_menu_object($menu);
        $menu_term_id = $menu_obj && isset($menu_obj->term_id) ? (int) $menu_obj->term_id : null;

        $tz  = wp_timezone();
        $now = new DateTimeImmutable('now', $tz);
        $dow = strtolower($now->format('D')); // e.g., Mon -> mon
        $dow = substr($dow, 0, 3);

        $map = ['mon','tue','wed','thu','fri','sat','sun'];

        $filtered = [];

        foreach ($items as $item) {
            if ($menu_term_id !== null) {
                $item_menu_terms = wp_get_post_terms($item->ID, 'nav_menu', ['fields' => 'ids']);
                if (is_wp_error($item_menu_terms) || empty($item_menu_terms) || !in_array($menu_term_id, (array)$item_menu_terms, true)) {
                    $filtered[] = $item;
                    continue;
                }
            }

            $rules = get_post_meta($item->ID, self::META_KEY, true);
            if (!is_array($rules) || empty($rules)) {
                $filtered[] = $item;
                continue;
            }

            $passes_any = false;
            foreach ($rules as $rule) {
                if (self::emec_rule_passes($rule, $now, $tz, $dow, $map)) {
                    $passes_any = true;
                    break;
                }
            }

            if ($passes_any) {
                $filtered[] = $item;
            }
        }

        return self::emec_relink_menu_tree($filtered);
    }

    private static function emec_rule_passes($rule, DateTimeImmutable $now, DateTimeZone $tz, $dow, $map) {
        // 1) Date window
        $start_ok = true; $end_ok = true;

        if (!empty($rule['start'])) {
            try {
                $s = new DateTimeImmutable($rule['start'], $tz);
                if ($now < $s) $start_ok = false;
            } catch (Exception $e) { return false; }
        }
        if (!empty($rule['end'])) {
            try {
                $e = new DateTimeImmutable($rule['end'], $tz);
                if ($now > $e) $end_ok = false;
            } catch (Exception $e) { return false; }
        }
        if (!($start_ok && $end_ok)) return false;

        // 2) Day-of-week
        if (!empty($rule['days'])) {
            $days = array_intersect(array_map('strtolower', (array)$rule['days']), $map);
            if (empty($days) || !in_array($dow, $days, true)) return false;
        }

        // 3) Time of day window
        if (!empty($rule['from']) && !empty($rule['to'])) {
            $t_now = $now->format('H:i');
            if ($rule['from'] <= $rule['to']) {
                if (!($t_now >= $rule['from'] && $t_now <= $rule['to'])) return false;
            } else {
                if (!($t_now >= $rule['from'] || $t_now <= $rule['to'])) return false;
            }
        }

        // 4) Roles
        if (!empty($rule['roles'])) {
            if (!is_user_logged_in()) return false;
            $u = wp_get_current_user();
            if (!$u || empty($u->roles)) return false;
            if (!array_intersect((array)$rule['roles'], (array)$u->roles)) return false;
        }

        // 5) Users CSV
        if (!empty($rule['users'])) {
            $ids = array_filter(array_map('absint', explode(',', $rule['users'])));
            if (empty($ids)) return false;
            if (!is_user_logged_in()) return false;
            $u = wp_get_current_user();
            if (!$u || !in_array((int)$u->ID, $ids, true)) return false;
        }

        return true;
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
