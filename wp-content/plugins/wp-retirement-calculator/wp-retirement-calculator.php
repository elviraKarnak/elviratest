<?php
/**
 * Plugin Name: WP Retirement Calculator (Moneysmart-style)
 * Description: Custom retirement calculator inspired by Moneysmart Planner — shortcode: [retirement_calculator]. Build the React app with Vite to populate /dist.
 * Version: 1.0.0
 * Author: Raihan Reza
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-3.0+
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WPRC_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPRC_URL', plugin_dir_url( __FILE__ ) );

require_once WPRC_DIR . 'includes/Calculator.php';

class WPRC_Plugin {
    public function __construct() {
        add_action('init', [$this, 'register_block']);
        add_shortcode('retirement_calculator', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_notices', [$this, 'build_notice']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_block() {
        // Optional: could register a block.json later; keeping shortcode-first.
    }

    public function shortcode($atts = []) {
        $atts = shortcode_atts([
            'id' => 'retcalc-root'
        ], $atts, 'retirement_calculator');

        ob_start();
        ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="wprc-container">
            <noscript>Please enable JavaScript to use the retirement calculator.</noscript>
        </div>
        <?php
        return ob_get_clean();
    }

    public function build_notice() {
        // Notify admin if /dist assets are missing
        $js = WPRC_DIR . 'dist/index.js';
        if ( is_admin() && current_user_can('manage_options') && ! file_exists($js) ) {
            echo '<div class="notice notice-warning"><p><strong>WP Retirement Calculator:</strong> Build assets not found. Run <code>npm install</code> and <code>npm run build</code> in the plugin directory to generate <code>/dist</code> assets.</p></div>';
        }
    }

    public function enqueue_assets() {
        // Only enqueue on pages that have the shortcode
        if ( ! is_singular() && ! is_front_page() && ! is_page() ) { return; }

        // Heuristic: enqueue always; for fine-grain, detect via has_shortcode in the_content (not reliable for blocks)
        $js_path = WPRC_URL . 'dist/index.js';
        $css_path = WPRC_URL . 'dist/style.css';

        // Version with filemtime if present
        $ver = '1.0.0';
        if ( file_exists(WPRC_DIR . 'dist/index.js') ) {
            $ver = filemtime(WPRC_DIR . 'dist/index.js');
        }

        wp_register_style('wprc-style', $css_path, [], $ver);
        wp_enqueue_style('wprc-style');

        wp_register_script('wprc-app', $js_path, [], $ver, true);
        wp_enqueue_script('wprc-app');

        wp_localize_script('wprc-app', 'WPRC_CFG', [
            'restUrl' => esc_url_raw( rest_url('retcalc/v1/calc') ),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }

    public function register_routes() {
        register_rest_route('retcalc/v1', '/calc', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_calc'],
            'permission_callback' => '__return_true',
            'args' => []
        ]);
    }

    public function handle_calc(WP_REST_Request $req) {
        $data = $req->get_json_params();
        if (!$data) {
            $data = json_decode($req->get_body(), true);
        }
        if (!is_array($data)) $data = [];

        $calc = new WPRC\Calculator();

        try {
            $result = $calc->run($data);
            return new WP_REST_Response($result, 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response([ 'error' => $e->getMessage() ], 400);
        }
    }
}

new WPRC_Plugin();
