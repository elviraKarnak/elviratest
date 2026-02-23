<?php
/**
 * Plugin Name: WP Retirement Calculator (No-Build UMD)
 * Description: Retirement calculator using classic scripts (no ES modules, no Node). Shortcode: [retirement_calculator]
 * Version: 1.0.1
 * Author: Raihan Reza
 * License: GPL-3.0+
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'WPRC_UMD_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPRC_UMD_URL', plugin_dir_url( __FILE__ ) );

require_once WPRC_UMD_DIR . 'includes/Calculator.php';

class WPRC_UMD_Plugin {
    public function __construct() {
        add_shortcode('retirement_calculator', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function shortcode($atts = []) {
        $atts = shortcode_atts([ 'id' => 'retcalc-root' ], $atts, 'retirement_calculator');
        ob_start(); ?>
        <div id="<?php echo esc_attr($atts['id']); ?>" class="wprc-container">
            <div class="wprc-app"><header class="hero"><h2>Retirement Planner</h2>
            <p>Estimate your retirement income and projected super balance. Results are indicative only and shown in today's dollars.</p></header>
            <div class="layout">
              <div class="left">
                <section class="card"><h3>Basics</h3><div class="grid">
                  <label class="field"><span>Current age</span><input type="number" id="wprc-age" value="35" min="18" max="74"></label>
                  <label class="field"><span>Retirement age</span><input type="number" id="wprc-retireAge" value="67" min="19" max="75"></label>
                  <label class="field"><span>Current salary (annual)</span><input type="number" id="wprc-salary" value="90000" step="1000"></label>
                  <label class="field"><span>Current super balance</span><input type="number" id="wprc-balance" value="80000" step="1000"></label>
                </div></section>
                <section class="card"><h3>Contributions</h3><div class="grid">
                  <label class="field"><span>Employer SG rate</span><input type="number" id="wprc-sgRate" value="12" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Voluntary pre-tax (salary sacrifice)</span><input type="number" id="wprc-volPre" value="0" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Voluntary after-tax (annual)</span><input type="number" id="wprc-volAfter" value="0" step="500"></label>
                </div></section>
                <section class="card"><h3>Assumptions</h3><div class="grid">
                  <label class="field"><span>Investment return (nominal p.a.)</span><input type="number" id="wprc-return" value="6.5" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Earnings tax (effective)</span><input type="number" id="wprc-earnTax" value="7.0" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Contribution tax (employer + pre-tax)</span><input type="number" id="wprc-contribTax" value="15" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Fees (% of balance)</span><input type="number" id="wprc-feePct" value="0.7" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Fees (fixed $ p.a.)</span><input type="number" id="wprc-feeFixed" value="100" step="10"></label>
                  <label class="field"><span>Salary growth</span><input type="number" id="wprc-salGrowth" value="3.5" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Inflation (for today's dollars)</span><input type="number" id="wprc-infl" value="2.5" step="0.1"><span class="suffix">%</span></label>
                  <label class="field"><span>Plan to age</span><input type="number" id="wprc-longevity" value="92" min="70" max="110"></label>
                  <label class="field"><span>Expected Age Pension (annual)</span><input type="number" id="wprc-pension" value="0" step="500"></label>
                </div></section>
                <div class="actions"><button id="wprc-run">Update results</button></div>
                <div id="wprc-error" class="error" style="display:none;"></div>
              </div>
              <div class="right">
                <section class="card">
                  <h3>Results</h3>
                  <div class="results">
                    <div class="kpis">
                      <div class="kpi"><span class="label" id="wprc-balance-label">Projected balance</span><strong id="wprc-balance-out">$0</strong></div>
                      <div class="kpi"><span class="label">Annual income from super (today's $)</span><strong id="wprc-super-income">$0</strong></div>
                      <div class="kpi"><span class="label">Expected Age Pension (today's $)</span><strong id="wprc-pension-out">$0</strong></div>
                      <div class="kpi"><span class="label">Estimated total annual income</span><strong id="wprc-total">$0</strong></div>
                    </div>
                    <div class="chart"><canvas id="wprc-chart" height="320"></canvas></div>
                    <p class="disclaimer">This tool is for general information only and does not consider your personal objectives, financial situation or needs. Consider seeking independent advice.</p>
                  </div>
                </section>
              </div>
            </div></div>
        </div>
        <?php return ob_get_clean();
    }

    public function enqueue_assets() {
        $css_path = WPRC_UMD_URL . 'dist/style.css';
        $js_path  = WPRC_UMD_URL . 'dist/index.js';
        $ver = file_exists(WPRC_UMD_DIR . 'dist/index.js') ? filemtime(WPRC_UMD_DIR . 'dist/index.js') : '1.0.1';
        wp_enqueue_style('wprc-umd-style', $css_path, [], $ver);
        // load in HEAD to avoid footer issues
        wp_enqueue_script('wprc-umd-app', $js_path, [], $ver, false);
        wp_localize_script('wprc-umd-app', 'WPRC_CFG', [
            'restUrl' => esc_url_raw( rest_url('retcalc/v1/calc') ),
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

        $calc = new WPRC_UMD\Calculator();
        try {
            $result = $calc->run($data);
            return new WP_REST_Response($result, 200);
        } catch (\Throwable $e) {
            return new WP_REST_Response(['error'=>$e->getMessage()], 400);
        }
    }
}
new WPRC_UMD_Plugin();
