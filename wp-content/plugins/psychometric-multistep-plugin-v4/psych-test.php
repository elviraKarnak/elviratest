<?php
/*
Plugin Name: Psychometric Multistep Test (v4)
Description: Multistep psychometric test with GUI repeater, refined visuals, CSV export, Slack webhook, improved UI and admin fixes.
Version: 4.0
Author: Generated
*/

if (!defined('ABSPATH')) exit;

class Psychometric_Multistep_Plugin_v4 {
    private $option_key = 'psych_test_settings_v4';

    public function __construct(){
        add_action('init', [$this,'register_post_type']);
        add_action('admin_menu', [$this,'admin_menus']);
        add_action('admin_init', [$this,'register_settings']);
        add_shortcode('psych_test', [$this,'render_shortcode']);
        add_shortcode('psych_submissions', [$this,'render_submissions_shortcode']);
        add_action('wp_enqueue_scripts', [$this,'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this,'admin_assets']);
        add_action('wp_ajax_psych_submit', [$this,'handle_submit']);
        add_action('wp_ajax_nopriv_psych_submit', [$this,'handle_submit']);
        add_action('wp_ajax_psych_view_result', [$this,'ajax_view_result']);
        add_action('admin_post_psych_export_csv', [$this,'export_csv']);
    }

    public function register_post_type(){
        register_post_type('candidate_test', [
            'label' => 'Candidate Tests',
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public function admin_menus(){
        add_menu_page('Psych Test', 'Psych Test', 'manage_options', 'psych_test_main', [$this,'admin_page_main'], 'dashicons-editor-ol', 56);
        add_submenu_page('psych_test_main', 'Questions', 'Questions', 'manage_options', 'psych_test_questions', [$this,'admin_page_questions']);
        add_submenu_page('psych_test_main', 'Submissions', 'Submissions', 'manage_options', 'psych_test_submissions', [$this,'admin_page_submissions']);
        add_submenu_page('psych_test_main', 'Settings', 'Settings', 'manage_options', 'psych_test_settings', [$this,'admin_page_settings']);
    }

    public function register_settings(){
        register_setting($this->option_key, $this->option_key);
    }

    public function admin_page_main(){
        echo '<div class="wrap"><h1>Psychometric Multistep Test</h1><p>Shortcodes: <code>[psych_test]</code> and <code>[psych_submissions]</code> (admin-only).</p></div>';
    }

    public function admin_page_questions(){
        $opt = get_option($this->option_key, []);
        $questions = $opt['questions'] ?? $this->default_questions();
        if (is_string($questions)) $questions = json_decode($questions,true) ?: $this->default_questions();
        ?>
        <div class="wrap">
            <h1>Questions — Repeater GUI</h1>
            <p><strong>Note:</strong> <em>Polarity</em> determines scoring direction: <strong>positive</strong> means agreement is positive; <strong>reverse</strong> means agreement indicates risk (reverse-scored).</p>
            <div id="psych-questions-gui"></div>
            <p><button id="psych-save-questions" class="button button-primary">Save Questions</button> <button id="psych-reset-questions" class="button">Reset to defaults</button></p>
            <form id="psych-questions-form" method="post" style="display:none;">
                <?php settings_fields($this->option_key); ?>
                <textarea name="<?php echo esc_attr($this->option_key); ?>[questions]" id="psych-hidden-questions" rows="10" style="width:100%;font-family:monospace;display:none;"><?php echo esc_textarea(json_encode($questions, JSON_PRETTY_PRINT)); ?></textarea>
                <?php submit_button('Hidden Save', 'button', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public function admin_page_submissions(){
        if (!current_user_can('manage_options')) { echo '<p>Not allowed.</p>'; return; }
        $export_nonce = wp_create_nonce('psych_export_nonce');
        $export_url = admin_url('admin-post.php?action=psych_export_csv&nonce='.$export_nonce);
        echo '<div class="wrap"><h1>Submissions</h1>';
        echo '<p><a class="button button-primary" href="'.esc_url($export_url).'">Export CSV</a></p>';
        echo '<p>Place <code>[psych_submissions]</code> on a page to view front-end admin table (paginated).</p>';
        echo '</div>';
    }

    public function admin_page_settings(){
        $opt = get_option($this->option_key, []);
        $emails = $opt['emails'] ?? '';
        $slack = $opt['slack_webhook'] ?? '';
        ?>
        <div class="wrap">
            <h1>Psych Test Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_key); ?>
                <table class="form-table">
                    <tr><th scope="row">Receiver emails (comma separated)</th>
                        <td><input type="text" name="<?php echo esc_attr($this->option_key); ?>[emails]" value="<?php echo esc_attr($emails); ?>" style="width:80%"></td></tr>
                    <tr><th scope="row">Slack webhook URL (optional)</th>
                        <td><input type="text" name="<?php echo esc_attr($this->option_key); ?>[slack_webhook]" value="<?php echo esc_attr($slack); ?>" style="width:80%"><p class="description">If provided, a notification will be sent to this webhook on each submission.</p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_assets(){
        wp_enqueue_style('psych-test-css', plugin_dir_url(__FILE__).'assets/style.css', [], '4.0');
        wp_enqueue_script('psych-test-js', plugin_dir_url(__FILE__).'assets/app.js', ['jquery'], '4.0', true);
        wp_localize_script('psych-test-js', 'psychL10n', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psych_submit_nonce'),
            'primary' => '#21BECA'
        ]);
    }

    public function admin_assets($hook){
        wp_enqueue_style('psych-test-admin', plugin_dir_url(__FILE__).'assets/admin.css', [], '4.0');
        wp_enqueue_script('psych-test-admin-js', plugin_dir_url(__FILE__).'assets/admin.js', ['jquery'], '4.0', true);
        wp_localize_script('psych-test-admin-js', 'psychAdmin', ['ajax_url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('psych_admin_nonce')]);
        $opt = get_option($this->option_key, []);
        $questions = $opt['questions'] ?? $this->default_questions();
        wp_localize_script('psych-test-admin-js', 'psychAdminData', ['questions' => $questions, 'option_key' => $this->option_key]);
    }

    public function render_shortcode($atts){
        if (is_admin()) return '';
        $opt = get_option($this->option_key, []);
        $questions = $opt['questions'] ?? $this->default_questions();
        if (is_string($questions)) $questions = json_decode($questions,true) ?: $this->default_questions();
        ob_start();
        ?>
        <div id="psych-test-app"></div>
        <script type="application/json" id="psych-questions-config"><?php echo wp_json_encode($questions);?></script>
        <?php
        return ob_get_clean();
    }

    public function render_submissions_shortcode($atts){
        if (!current_user_can('manage_options')) return '<p>Not allowed.</p>';
        $paged = max(1, intval($_GET['psych_page'] ?? 1));
        $per = 10;
        $args = ['post_type'=>'candidate_test','posts_per_page'=>$per,'paged'=>$paged,'orderby'=>'date','order'=>'DESC'];
        $q = new WP_Query($args);
        ob_start();
        echo '<div class="psych-submissions-wrap"><table class="psych-table"><thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Score</th><th>Risk</th><th>Action</th></tr></thead><tbody>';
        $i = ($paged-1)*$per + 1;
        foreach ($q->posts as $post){
            $meta = get_post_meta($post->ID);
            $name = esc_html($meta['candidate_name'][0] ?? '');
            $email = esc_html($meta['candidate_email'][0] ?? '');
            $phone = esc_html($meta['candidate_phone'][0] ?? '');
            $score = esc_html($meta['score'][0] ?? '');
            $risk = esc_html($meta['risk'][0] ?? '');
            echo "<tr><td>{$i}</td><td>{$name}</td><td>{$email}</td><td>{$phone}</td><td>{$score}</td><td class='risk-td'>{$risk}</td><td><button class='view-result' data-id='{$post->ID}'>View</button></td></tr>";
            $i++;
        }
        echo '</tbody></table>';
        $total = $q->found_posts;
        $pages = ceil($total / $per);
        echo '<div class="psych-pagination">';
        for ($p=1;$p<=$pages;$p++){
            $link = add_query_arg('psych_page',$p);
            echo '<a class="page-link" href="'.esc_url($link).'">'.intval($p).'</a> ';
        }
        echo '</div></div>';
        return ob_get_clean();
    }

    public function handle_submit(){
        check_ajax_referer('psych_submit_nonce', 'nonce');
        $payload = $_POST['payload'] ?? '';
        if (!$payload) wp_send_json_error('No data');
        $data = json_decode(stripslashes($payload), true);
        if (!is_array($data) || empty($data['answers'])) wp_send_json_error('Invalid data');

        $name = sanitize_text_field($data['candidate']['name'] ?? '');
        $email = sanitize_email($data['candidate']['email'] ?? '');
        $phone = sanitize_text_field($data['candidate']['phone'] ?? '');
        $interview = sanitize_text_field($data['candidate']['interview_date'] ?? '');

        // compute score
        $questions = $this->get_questions_flat();
        $norms = [];
        foreach ($data['answers'] as $qid => $selected){
            $sel = intval($selected);
            if ($sel < 1 || $sel > 7) { $sel = 4; }
            $pol = $questions[$qid] ?? 'positive';
            $raw = ($pol === 'reverse') ? (8 - $sel) : $sel;
            $q_norm = (($raw - 1) / 6) * 100;
            $norms[$qid] = round($q_norm,2);
        }
        $avg = array_sum($norms) / max(1, count($norms));
        $score = round($avg,2);
        if ($score <= 24) $risk = 'Safe';
        elseif ($score <= 49) $risk = 'Acceptable / Moderate Risk';
        elseif ($score <= 74) $risk = 'Risk / Concerning';
        else $risk = 'Harmful / High Risk';

        $post_id = wp_insert_post([
            'post_type' => 'candidate_test',
            'post_title' => $name ? $name . ' - ' . current_time('mysql') : 'Candidate Test - '.current_time('mysql'),
            'post_status' => 'publish',
        ]);

        if ($post_id && !is_wp_error($post_id)){
            update_post_meta($post_id, 'candidate_name', $name);
            update_post_meta($post_id, 'candidate_email', $email);
            update_post_meta($post_id, 'candidate_phone', $phone);
            update_post_meta($post_id, 'interview_date', $interview);
            update_post_meta($post_id, 'answers', $data['answers']);
            update_post_meta($post_id, 'norms', $norms);
            update_post_meta($post_id, 'score', $score);
            update_post_meta($post_id, 'risk', $risk);
        }

        // email to configured receivers
        $opt = get_option($this->option_key, []);
        $emails = $opt['emails'] ?? '';
        $tos = array_filter(array_map('trim', explode(',', $emails)));
        if (!empty($tos)){
            $subject = "New Psych Test Submission: {$name}";
            $message = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nScore: {$score}\nRisk: {$risk}\n\nView in WP admin.";
            foreach ($tos as $to){
                wp_mail($to, $subject, $message);
            }
        }

        // Slack webhook support
        if (!empty($opt['slack_webhook'])){
            $webhook = esc_url_raw($opt['slack_webhook']);
            $payload = json_encode([
                'text' => "New Psych Test Submission\n*Name:* {$name}\n*Email:* {$email}\n*Phone:* {$phone}\n*Score:* {$score}\n*Risk:* {$risk}"
            ]);
            wp_remote_post($webhook, ['body'=>$payload,'headers'=>['Content-Type'=>'application/json'],'timeout'=>5]);
        }

        wp_send_json_success(['score'=>$score, 'risk'=>$risk, 'post_id'=>$post_id]);
    }

    public function ajax_view_result(){
        check_ajax_referer('psych_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Not allowed');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error('Invalid id');
        $meta = get_post_meta($id);
        $resp = [
            'id' => $id,
            'name'=>$meta['candidate_name'][0] ?? '',
            'email'=>$meta['candidate_email'][0] ?? '',
            'phone'=>$meta['candidate_phone'][0] ?? '',
            'score'=>$meta['score'][0] ?? '',
            'risk'=>$meta['risk'][0] ?? '',
            'norms'=> $meta['norms'][0] ?? '',
            'answers' => maybe_unserialize($meta['answers'][0] ?? ''),
        ];
        wp_send_json_success($resp);
    }

    public function export_csv(){
        if (!current_user_can('manage_options')) wp_die('Not allowed');
        check_admin_referer('psych_export_nonce', 'nonce');
        $args = ['post_type'=>'candidate_test','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC'];
        $q = new WP_Query($args);
        $filename = 'psychometric_submissions_'.date('Ymd_His').'.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename='.$filename);
        $out = fopen('php://output','w');
        fputcsv($out, ['ID','Name','Email','Phone','Interview Date','Score','Risk','Answers','Date']);
        foreach ($q->posts as $post){
            $m = get_post_meta($post->ID);
            $row = [
                $post->ID,
                $m['candidate_name'][0] ?? '',
                $m['candidate_email'][0] ?? '',
                $m['candidate_phone'][0] ?? '',
                $m['interview_date'][0] ?? '',
                $m['score'][0] ?? '',
                $m['risk'][0] ?? '',
                maybe_serialize($m['answers'][0] ?? ''),
                $post->post_date
            ];
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    private function get_questions_flat(){
        $opt = get_option($this->option_key, []);
        $questions = $opt['questions'] ?? $this->default_questions();
        if (is_string($questions)) $questions = json_decode($questions,true) ?: $this->default_questions();
        $flat = [];
        foreach ($questions as $step){
            foreach ($step as $q){
                $flat[$q['id']] = $q['polarity'] ?? 'positive';
            }
        }
        return $flat;
    }

    private function default_questions(){
        $out = [];
        for ($s=1;$s<=10;$s++){
            $step = [];
            for ($q=1;$q<=6;$q++){
                $id = 'q'.(($s-1)*6 + $q);
                $step[] = ['id'=>$id,'text'=>"Placeholder question {$id} - replace this text",'polarity'=>($q%3===0?'reverse':'positive')];
            }
            $out[] = $step;
        }
        return $out;
    }
}

new Psychometric_Multistep_Plugin_v4();
