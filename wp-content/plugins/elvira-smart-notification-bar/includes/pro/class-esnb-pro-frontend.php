<?php
if (!defined('ABSPATH')) exit;

class ELVISMNO_Pro_Frontend {
    public function __construct() {
        add_action('wp_footer', [$this, 'elvismno_render_active_bars']);
        add_action('wp_enqueue_scripts', [$this, 'elvismno_enqueue_assets']);
    }

    public function elvismno_enqueue_assets() {
        if ( defined('ELVISMNO_PLUGIN_URL') ) {
            wp_enqueue_style('esnb-style-css', ELVISMNO_PLUGIN_URL . 'assets/css/esnb-style.css', [], ELVISMNO_VERSION);
            wp_enqueue_script('esnb-script-js', ELVISMNO_PLUGIN_URL . 'assets/js/esnb-script.js', [], ELVISMNO_VERSION, true);
        }
    }

    public function elvismno_render_active_bars() {
        $args = [
            'post_type' => 'elvismno_bar',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => -1,
        ];
        $bars = get_posts($args);
        $now = current_time('timestamp');
        $index = 0;
        foreach ($bars as $bar) {
            $f = get_post_meta($bar->ID, '_elvismno_fields', true);
            if (!$f) continue;

            $start = !empty($f['start_date']) ? strtotime($f['start_date']) : 0;
            $end   = !empty($f['end_date']) ? strtotime($f['end_date']) : 9999999999;
            if ($now < $start || $now > $end) continue;

            // Page check
            $display = $f['display_page'] ?? 'all';
            if ($display == 'home' && !(is_home() || is_front_page())) continue;
            if ($display == 'cart' && !(function_exists('is_cart') && is_cart())) continue;
            if ($display == 'checkout' && !(function_exists('is_checkout') && is_checkout())) continue;

            $position = esc_attr($f['position'] ?? 'top');
            $countdown = ! empty( $f['countdown'] ) ? strtotime( $f['end_date'] ) : '';
            echo esc_html( $countdown );
			// ensure later bars appear on top: larger z-index
			$z = 9999 + $index;

            echo '<div id="esnb-bar-' . esc_attr($bar->ID) . '" class="esnb-bar esnb-' . esc_attr($position) . '" 
                style="background:' . esc_attr($f['bg_color']) . ';color:' . esc_attr($f['text_color']) . ';z-index:' . intval($z) . ';" 
                data-countdown="' . esc_attr($countdown) . '">';
            echo '<span class="esnb-text">' . esc_html($f['message']) . '</span>';
            if ($countdown) echo ' <span class="esnb-timer" id="esnb-timer-' . esc_attr($bar->ID) . '"></span>';
            if (!empty($f['btn_text'])) echo ' <a href="' . esc_url($f['btn_link']) . '" class="esnb-btn">' . esc_html($f['btn_text']) . '</a>';
            echo ' <span class="esnb-close">&times;</span>';
            echo '</div>';

			$index++;
        }
    }
}
