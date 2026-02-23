<?php
/**
 * Plugin Name: WP Retirement Calculator (No-Build UMD v2)
 * Description: Retirement calculator with chart toggle (Income vs Super Balance). No Node required. Shortcode: [retirement_calculator]
 * Version: 1.2.0
 * Author: Raihan Reza
 * License: GPL-3.0+
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WPRC_UMD_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPRC_UMD_URL', plugin_dir_url( __FILE__ ) );

require_once WPRC_UMD_DIR . 'includes/Calculator.php';

class WPRC_UMD_Plugin_V2 {
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
        $ver = file_exists(WPRC_UMD_DIR . 'dist/index.js') ? filemtime(WPRC_UMD_DIR . 'dist/index.js') : '1.2.0';

        // Chart.js UMD
        wp_enqueue_script('chart-js-umd', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js', [], '4.4.4', false); // header

        // Styles + App
        wp_enqueue_style('wprc-umd-style', WPRC_UMD_URL . 'dist/style.css', [], $ver);

        wp_register_script('wprc-umd-app', WPRC_UMD_URL . 'dist/index.js', ['chart-js-umd'], $ver, false); // header
        wp_enqueue_script('wprc-umd-app');

        wp_localize_script('wprc-umd-app', 'WPRC_CFG', [
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

        $calc = new WPRC_UMD_V2\Calculator();
        try {
            $result = $calc->run($data);
            return new WP_REST_Response($result, 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response([ 'error' => $e->getMessage() ], 400);
        }
    }
}
new WPRC_UMD_Plugin_V2();
