<?php
/**
 * Plugin Name: WP Retirement Calculator (No-Build, Header Script)
 * Description: No-Node retirement calculator. Loads module script in <head> to avoid themes missing wp_footer(). Shortcode: [retirement_calculator]
 * Version: 1.0.1
 * Author: Raihan Reza
 * License: GPL-3.0+
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'WPRC3_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPRC3_URL', plugin_dir_url( __FILE__ ) );
require_once WPRC3_DIR . 'includes/Calculator.php';
class WPRC3_Plugin {
    public function __construct() {
        add_shortcode('retirement_calculator', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    public function shortcode($atts = []) {
        $atts = shortcode_atts([ 'id' => 'retcalc-root' ], $atts, 'retirement_calculator');
        ob_start(); ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="wprc-container">
            <noscript>Please enable JavaScript to use the retirement calculator.</noscript>
        </div>
        <?php return ob_get_clean();
    }
    public function enqueue_assets() {
        $js_path = WPRC3_URL . 'dist/index.js';
        $css_path = WPRC3_URL . 'dist/style.css';
        $ver = file_exists(WPRC3_DIR . 'dist/index.js') ? filemtime(WPRC3_DIR . 'dist/index.js') : '1.0.1';
        wp_register_style('wprc3-style', $css_path, [], $ver);
        wp_enqueue_style('wprc3-style');

        // Enqueue in header (in_footer = false) so it doesn't rely on wp_footer()
        wp_register_script('wprc3-app', $js_path, [], $ver, false);
        if ( function_exists('wp_script_add_data') ) { wp_script_add_data('wprc3-app', 'type', 'module'); }
        wp_enqueue_script('wprc3-app');

        wp_localize_script('wprc3-app', 'WPRC_CFG', [
            'restUrl' => esc_url_raw( rest_url('retcalc/v1/calc') ),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }
    public function register_routes() {
        register_rest_route('retcalc/v1', '/calc', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_calc'],
            'permission_callback' => '__return_true',
        ]);
    }
    public function handle_calc(WP_REST_Request $req) {
        $data = $req->get_json_params();
        if (!$data) { $data = json_decode($req->get_body(), true); }
        if (!is_array($data)) $data = [];
        $calc = new WPRC3\Calculator();
        try {
            $result = $calc->run($data);
            return new WP_REST_Response($result, 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response([ 'error' => $e->getMessage() ], 400);
        }
    }
}
new WPRC3_Plugin();
