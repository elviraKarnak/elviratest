<?php
/**
 * AJAX Handler
 */

if (!defined('ABSPATH')) exit;

class CAS_Ajax_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Admin AJAX
        add_action('wp_ajax_cas_add_question', array($this, 'add_question'));
        add_action('wp_ajax_cas_update_question', array($this, 'update_question'));
        add_action('wp_ajax_cas_delete_question', array($this, 'delete_question'));
        add_action('wp_ajax_cas_get_questions', array($this, 'get_questions'));
        add_action('wp_ajax_cas_update_question_order', array($this, 'update_question_order'));
        add_action('wp_ajax_cas_get_submissions', array($this, 'get_submissions'));
        add_action('wp_ajax_cas_get_submission_detail', array($this, 'get_submission_detail'));
        add_action('wp_ajax_cas_delete_submission', array($this, 'delete_submission'));
        
        // Frontend AJAX
        add_action('wp_ajax_nopriv_cas_get_test_questions', array($this, 'get_test_questions'));
        add_action('wp_ajax_nopriv_cas_submit_test', array($this, 'submit_test'));
    }
    
    public function add_question() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $question_text = sanitize_textarea_field($_POST['question']);
        $category = sanitize_text_field($_POST['category']);
        
        $post_id = wp_insert_post(array(
            'post_title' => substr($question_text, 0, 100),
            'post_content' => $question_text,
            'post_type' => 'cas_question',
            'post_status' => 'publish'
        ));
        
        if ($post_id) {
            update_post_meta($post_id, 'cas_category', $category);
            update_post_meta($post_id, 'cas_order', 9999);
            
            wp_send_json_success(array(
                'message' => 'Question added successfully',
                'id' => $post_id
            ));
        } else {
            wp_send_json_error('Failed to add question');
        }
    }
    
    public function update_question() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['id']);
        $question_text = sanitize_textarea_field($_POST['question']);
        $category = sanitize_text_field($_POST['category']);
        
        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => substr($question_text, 0, 100),
            'post_content' => $question_text
        ));
        
        update_post_meta($post_id, 'cas_category', $category);
        
        wp_send_json_success('Question updated successfully');
    }
    
    public function delete_question() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['id']);
        wp_delete_post($post_id, true);
        
        wp_send_json_success('Question deleted successfully');
    }
    
    public function get_questions() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $args = array(
            'post_type' => 'cas_question',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => 'cas_order',
            'order' => 'ASC'
        );
        
        $questions = get_posts($args);
        $data = array();
        
        foreach ($questions as $q) {
            $data[] = array(
                'id' => $q->ID,
                'question' => $q->post_content,
                'category' => get_post_meta($q->ID, 'cas_category', true),
                'order' => get_post_meta($q->ID, 'cas_order', true)
            );
        }
        
        wp_send_json_success($data);
    }
    
    public function update_question_order() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $order_data = $_POST['order'];
        
        foreach ($order_data as $index => $id) {
            update_post_meta(intval($id), 'cas_order', $index);
        }
        
        wp_send_json_success('Order updated successfully');
    }
    
    public function get_submissions() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $args = array(
            'post_type' => 'cas_submission',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if ($filter !== 'all') {
            $args['meta_query'] = array(
                array(
                    'key' => 'cas_rating',
                    'value' => $filter
                )
            );
        }
        
        if ($search) {
            $args['s'] = $search;
        }
        
        $submissions = get_posts($args);
        $data = array();
        
        foreach ($submissions as $sub) {
            $data[] = array(
                'id' => $sub->ID,
                'name' => get_post_meta($sub->ID, 'cas_name', true),
                'email' => get_post_meta($sub->ID, 'cas_email', true),
                'phone' => get_post_meta($sub->ID, 'cas_phone', true),
                'date' => get_the_date('Y-m-d H:i', $sub->ID),
                'interview_date' => get_post_meta($sub->ID, 'cas_interview_date', true),
                'rating' => get_post_meta($sub->ID, 'cas_rating', true),
                'score' => get_post_meta($sub->ID, 'cas_score', true)
            );
        }
        
        wp_send_json_success($data);
    }
    
    public function get_submission_detail() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['id']);
        
        $data = array(
            'name' => get_post_meta($post_id, 'cas_name', true),
            'email' => get_post_meta($post_id, 'cas_email', true),
            'phone' => get_post_meta($post_id, 'cas_phone', true),
            'interview_date' => get_post_meta($post_id, 'cas_interview_date', true),
            'rating' => get_post_meta($post_id, 'cas_rating', true),
            'score' => get_post_meta($post_id, 'cas_score', true),
            'answers' => json_decode(get_post_meta($post_id, 'cas_answers', true), true),
            'date' => get_the_date('F j, Y g:i A', $post_id)
        );
        
        wp_send_json_success($data);
    }
    
    public function delete_submission() {
        check_ajax_referer('cas_admin_nonce', 'nonce');
        
        $post_id = intval($_POST['id']);
        wp_delete_post($post_id, true);
        
        wp_send_json_success('Submission deleted successfully');
    }
    
    public function get_test_questions() {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $settings = get_option('cas_settings');
        $per_page = isset($settings['questions_per_page']) ? $settings['questions_per_page'] : 10;
        
        $args = array(
            'post_type' => 'cas_question',
            'posts_per_page' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'meta_key' => 'cas_order',
            'order' => 'ASC'
        );
        
        $questions = get_posts($args);
        $total = wp_count_posts('cas_question')->publish;
        
        $data = array();
        foreach ($questions as $q) {
            $data[] = array(
                'id' => $q->ID,
                'question' => $q->post_content ? $q->post_content : $q->post_title,
                'category' => get_post_meta($q->ID, 'cas_category', true)
            );
        }
        
        wp_send_json_success(array(
            'questions' => $data,
            'total_pages' => $total > 0 ? ceil($total / $per_page) : 1,
            'current_page' => $page,
            'total_questions' => $total
        ));
    }
    
    public function submit_test() {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $interview_date = sanitize_text_field($_POST['interview_date']);
        $answers = $_POST['answers'];
        
        // Calculate score
        $scoring = CAS_Scoring::get_instance();
        $result = $scoring->calculate_score($answers);
        
        // Create submission
        $post_id = wp_insert_post(array(
            'post_title' => $name . ' - ' . date('Y-m-d H:i:s'),
            'post_type' => 'cas_submission',
            'post_status' => 'publish'
        ));
        
        if ($post_id) {
            update_post_meta($post_id, 'cas_name', $name);
            update_post_meta($post_id, 'cas_email', $email);
            update_post_meta($post_id, 'cas_phone', $phone);
            update_post_meta($post_id, 'cas_interview_date', $interview_date);
            update_post_meta($post_id, 'cas_answers', json_encode($answers));
            update_post_meta($post_id, 'cas_score', $result['score']);
            update_post_meta($post_id, 'cas_rating', $result['rating']);
            
            wp_send_json_success(array(
                'message' => 'Assessment submitted successfully!',
                'rating' => $result['rating']
            ));
        } else {
            wp_send_json_error('Failed to submit assessment');
        }
    }
}