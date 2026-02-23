<?php
if (!defined('ABSPATH')) exit;

class ELVISMNO_Frontend {
    public function __construct() {
        add_action('wp_footer', [$this, 'elvismno_render_bar']);
        add_action('wp_enqueue_scripts', [$this, 'elvismno_enqueue_assets']);
    }

    public function elvismno_enqueue_assets() {
        wp_enqueue_style('esnb-style', ELVISMNO_PLUGIN_URL . 'assets/css/esnb-style.css', [], ELVISMNO_VERSION);
        wp_enqueue_script('esnb-script', ELVISMNO_PLUGIN_URL . 'assets/js/esnb-script.js', [], ELVISMNO_VERSION, true);
    }

    private function elvismno_should_show($opts) {
        $display = $opts['display_page'] ?? 'all';
        switch ($display) {
            case 'home': return is_front_page() || is_home();
            case 'cart': return function_exists('is_cart') && is_cart();
            case 'checkout': return function_exists('is_checkout') && is_checkout();
            default: return true;
        }
    }

    public function elvismno_render_bar() {
        $opts = get_option('elvismno_settings', []);
        if (empty($opts['enabled'])) return;
        if (!$this->elvismno_should_show($opts)) return;

        $countdown_enabled = !empty($opts['countdown_enabled']);
        $countdown_end = '';
        if ($countdown_enabled && !empty($opts['countdown_end'])) {
            $countdown_end = ELVISMNO_Utils::elvismno_datetime_to_timestamp($opts['countdown_end']);
        }

        echo '<div id="esnb-bar" class="esnb-' . esc_attr($opts['position'] ?? 'top') . '" data-countdown="' . esc_attr($countdown_end) . '" style="background:' . esc_attr($opts['bg_color'] ?? '#000') . ';color:' . esc_attr($opts['text_color'] ?? '#fff') . ';">';

        // Message: allow safe HTML from WYSIWYG
        echo '<span class="esnb-text">' . wp_kses_post($opts['message'] ?? '') . '</span>';

        if ($countdown_enabled && $countdown_end) {
            echo ' <span class="esnb-timer" id="esnb-timer"></span>';
        }

        if (!empty($opts['btn_text'])) {
            echo ' <a href="' . esc_url($opts['btn_link'] ?? '#') . '" class="esnb-btn">' . esc_html($opts['btn_text']) . '</a>';
        }
        if (!empty($opts['dismiss'])) {
            echo ' <span id="esnb-close" class="esnb-close">&times;</span>';
        }
        echo '</div>';
    }
}
