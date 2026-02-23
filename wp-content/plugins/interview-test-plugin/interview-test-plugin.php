<?php
/**
 * Plugin Name: Interview Test (Vanilla JS)
 * Description: Multi-step interview test with scoring, admin management, settings for colors & questions-per-step. Vanilla HTML/JS (no build step).
 * Version: 1.0
 * Author: ChatGPT (generated)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Interview_Test_Plugin {
    private $option_name = 'it_settings';

    public function __construct() {
        add_action('init', array($this,'register_post_types'));
        add_action('add_meta_boxes', array($this,'question_meta_box'));
        add_action('save_post', array($this,'save_question_meta'));
        add_action('admin_menu', array($this,'add_settings_page'));
        add_action('admin_init', array($this,'register_settings'));
        add_shortcode('interview_test', array($this,'render_frontend'));
        add_action('wp_enqueue_scripts', array($this,'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this,'admin_assets'));
        add_action('wp_ajax_it_submit', array($this,'handle_submission'));
        add_action('wp_ajax_nopriv_it_submit', array($this,'handle_submission'));
        add_filter('manage_it_submission_posts_columns', array($this,'submission_columns'));
        add_action('manage_it_submission_posts_custom_column', array($this,'submission_column_values'), 10, 2);
    }

    public function register_post_types(){
        register_post_type('it_question', array(
            'labels'=>array('name'=>'Questions','singular_name'=>'Question'),
            'public'=>false,
            'show_ui'=>true,
            'supports'=>array('title','editor'),
            'menu_icon'=>'dashicons-welcome-learn-more',
            'menu_position' => 25,
        ));

        register_post_type('it_submission', array(
            'labels'=>array('name'=>'Submissions','singular_name'=>'Submission'),
            'public'=>false,
            'show_ui'=>true,
            'supports'=>array('title'),
            'menu_icon'=>'dashicons-list-view',
            'menu_position' => 26,
        ));
    }

    public function question_meta_box(){
        add_meta_box('it_q_meta','Question Meta', array($this,'render_question_meta'),'it_question','side');
    }

    public function render_question_meta($post){
        $types = get_post_meta($post->ID,'q_type',true) ?: 'positive';
        $required = get_post_meta($post->ID,'q_required',true) ? 'checked' : '';
        ?>
        <p>
            <label>Question Type</label>
            <select name="q_type" style="width:100%;">
                <option value="positive" <?php selected($types,'positive');?>>Positive</option>
                <option value="moderate" <?php selected($types,'moderate');?>>Moderate</option>
                <option value="negative" <?php selected($types,'negative');?>>Negative</option>
            </select>
        </p>
        <p>
            <label><input type="checkbox" name="q_required" <?php echo $required;?> /> Required</label>
        </p>
        <?php
        wp_nonce_field('it_q_meta_nonce','it_q_meta_nonce');
    }

    public function save_question_meta($post_id){
        if(get_post_type($post_id) !== 'it_question') return;
        if(!isset($_POST['it_q_meta_nonce']) || !wp_verify_nonce($_POST['it_q_meta_nonce'],'it_q_meta_nonce')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(isset($_POST['q_type'])) update_post_meta($post_id,'q_type',sanitize_text_field($_POST['q_type']));
        update_post_meta($post_id,'q_required', isset($_POST['q_required']) ? 1 : 0);
    }

    public function add_settings_page(){
        add_options_page('Interview Test Settings','Interview Test', 'manage_options', 'it-settings', array($this,'settings_page'));
    }

    public function register_settings(){
        register_setting($this->option_name, $this->option_name, array($this,'validate_settings'));
        add_settings_section('it_general','General Settings', '__return_false', $this->option_name);
        add_settings_field('questions_per_step','Questions per step', array($this,'field_questions_per_step'), $this->option_name, 'it_general');
        add_settings_field('primary_color','Primary color', array($this,'field_primary_color'), $this->option_name, 'it_general');
        add_settings_field('secondary_color','Secondary color', array($this,'field_secondary_color'), $this->option_name, 'it_general');
        add_settings_field('thresholds','Thresholds (Safe,Acceptable,Risk,Harmful %)', array($this,'field_thresholds'), $this->option_name, 'it_general');
    }

    public function validate_settings($input){
        $out = array();
        $out['questions_per_step'] = max(1,intval($input['questions_per_step'] ?? 10));
        $out['primary_color'] = sanitize_text_field($input['primary_color'] ?? '#0077AC');
        $out['secondary_color'] = sanitize_text_field($input['secondary_color'] ?? '#FF9900');
        $t = isset($input['thresholds']) ? explode(',',$input['thresholds']) : array(75,50,30);
        $out['thresholds'] = array_map('intval',$t);
        return $out;
    }

    public function field_questions_per_step(){
        $opts = get_option($this->option_name);
        $v = $opts['questions_per_step'] ?? 10;
        echo '<input type="number" name="'.$this->option_name.'[questions_per_step]" value="'.esc_attr($v).'" min="1" />';
    }
    public function field_primary_color(){
        $opts = get_option($this->option_name);
        $v = $opts['primary_color'] ?? '#0077AC';
        echo '<input type="text" name="'.$this->option_name.'[primary_color]" value="'.esc_attr($v).'" class="it-color" />';
    }
    public function field_secondary_color(){
        $opts = get_option($this->option_name);
        $v = $opts['secondary_color'] ?? '#FF9900';
        echo '<input type="text" name="'.$this->option_name.'[secondary_color]" value="'.esc_attr($v).'" class="it-color" />';
    }
    public function field_thresholds(){
        $opts = get_option($this->option_name);
        $t = $opts['thresholds'] ?? array(75,50,30);
        $val = implode(',',$t);
        echo '<input type="text" name="'.$this->option_name.'[thresholds]" value="'.esc_attr($val).'" /> <p class="description">Comma-separated: Safe,Acceptable,Risk (Harmful is below risk)</p>';
    }

    public function settings_page(){
        ?>
        <div class="wrap">
            <h1>Interview Test Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); do_settings_sections($this->option_name); submit_button(); ?>
            </form>
            <h2>Quick links</h2>
            <p><a href="<?php echo admin_url('edit.php?post_type=it_question');?>">Manage Questions</a> | <a href="<?php echo admin_url('edit.php?post_type=it_submission');?>">View Submissions</a></p>
        </div>
        <?php
    }

    public function enqueue_assets(){
        wp_enqueue_style('it-frontend', plugins_url('assets/css/frontend.css', __FILE__));
        wp_enqueue_script('it-frontend', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), false, true);
        // localize questions and settings
        $questions = array();
        $qposts = get_posts(array('post_type'=>'it_question','numberposts'=>-1,'orderby'=>'menu_order ID'));
        foreach($qposts as $q){
            $questions[] = array(
                'id' => $q->ID,
                'text' => get_the_title($q->ID),
                'type' => get_post_meta($q->ID,'q_type',true) ?: 'positive',
                'required' => get_post_meta($q->ID,'q_required',true) ? true : false,
            );
        }
        $opts = get_option($this->option_name);
        $settings = array(
            'questions_per_step' => $opts['questions_per_step'] ?? 10,
            'primary_color' => $opts['primary_color'] ?? '#0077AC',
            'secondary_color' => $opts['secondary_color'] ?? '#FF9900',
            'thresholds' => $opts['thresholds'] ?? array(75,50,30),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('it_nonce'),
        );
        wp_localize_script('it-frontend','ITData', array('questions'=>$questions,'settings'=>$settings));
    }

    public function admin_assets($hook){
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('it-admin', plugins_url('assets/css/admin.css', __FILE__));
        wp_add_inline_script('wp-color-picker', 'jQuery(function($){ $(".it-color").wpColorPicker(); });');
    }

    public function render_frontend($atts){
        // simple container - JS will render the multi-step UI
        return '<div id="it-app" class="it-app"></div>';
    }

    private function choice_to_base($choice){
        switch($choice){
            case 'sa': return 2;
            case 'a': return 1;
            case 'n': return 0;
            case 'd': return -1;
            case 'sd': return -2;
        }
        return 0;
    }

    private function compute_score_percent($answers){
        $raw = 0;
        $N = count($answers);
        foreach($answers as $qid => $choice){
            $q_type = get_post_meta(intval($qid),'q_type',true) ?: 'positive';
            $base = $this->choice_to_base($choice);
            if($q_type === 'negative') $base = -$base;
            if($q_type === 'moderate') $base = $base * 0.5;
            $raw += $base;
        }
        if($N <= 0) return 0;
        $min = -2 * $N;
        $max = 2 * $N;
        $pct = (($raw - $min) / ($max - $min)) * 100;
        return round($pct,2);
    }

    public function handle_submission(){
        check_ajax_referer('it_nonce','nonce');
        $data = $_POST['data'] ?? null;
        if(!$data) wp_send_json_error('No data');
        $data = json_decode(stripslashes($data), true);
        if(!$data) wp_send_json_error('Invalid data');
        // validate minimal fields
        $name = sanitize_text_field($data['name'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');
        $datetime = sanitize_text_field($data['datetime'] ?? '');
        $answers = $data['answers'] ?? array();
        // server-side validation: required questions must be answered
        $qposts = get_posts(array('post_type'=>'it_question','numberposts'=>-1));
        foreach($qposts as $qp){
            $qid = $qp->ID;
            $req = get_post_meta($qid,'q_required',true) ? 1 : 0;
            if($req){
                if(!isset($answers[$qid]) || $answers[$qid] === ''){
                    wp_send_json_error('Required question not answered (ID: '.$qid.')');
                }
            }
        }
        // validate email
        if($email && !is_email($email)) wp_send_json_error('Invalid email');
        // compute score
        $pct = $this->compute_score_percent($answers);
        $label = $this->label_from_pct($pct);
        // save submission
        $post_id = wp_insert_post(array(
            'post_type' => 'it_submission',
            'post_title' => $name ? $name : 'Submission - '.date('c'),
            'post_status' => 'publish',
        ));
        if(!$post_id) wp_send_json_error('Could not save');
        update_post_meta($post_id,'candidate_name',$name);
        update_post_meta($post_id,'candidate_email',$email);
        update_post_meta($post_id,'candidate_phone',$phone);
        update_post_meta($post_id,'interview_datetime',$datetime);
        update_post_meta($post_id,'answers',$answers);
        update_post_meta($post_id,'score_pct',$pct);
        update_post_meta($post_id,'computed_label',$label);
        wp_send_json_success(array('pct'=>$pct,'label'=>$label));
    }

    private function label_from_pct($pct){
        $opts = get_option($this->option_name);
        $t = $opts['thresholds'] ?? array(75,50,30);
        $safe = $t[0];
        $accept = $t[1];
        $risk = $t[2];
        if($pct >= $safe) return 'Safe';
        if($pct >= $accept) return 'Acceptable / Moderate Risk';
        if($pct >= $risk) return 'Risk / Concerning';
        return 'Harmful / High Risk';
    }

    public function submission_columns($cols){
        $cols = array(
            'cb' => '<input type="checkbox" />',
            'title' => 'Candidate',
            'email' => 'Email',
            'score' => 'Score %',
            'label' => 'Label',
            'date' => 'Date',
        );
        return $cols;
    }

    public function submission_column_values($column, $post_id){
        if($column === 'email'){
            echo esc_html(get_post_meta($post_id,'candidate_email',true));
        } elseif($column === 'score'){
            echo esc_html(get_post_meta($post_id,'score_pct',true));
        } elseif($column === 'label'){
            echo esc_html(get_post_meta($post_id,'computed_label',true));
        }
    }

}

new WP_Interview_Test_Plugin();

?>