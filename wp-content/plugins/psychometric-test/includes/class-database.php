<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Psychometric_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Steps table
        $table_steps = $wpdb->prefix . 'psychometric_steps';
        $sql_steps = "CREATE TABLE $table_steps (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            step_number int(11) NOT NULL,
            step_title varchar(255) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY step_number (step_number)
        ) $charset_collate;";
        
        // Questions table
        $table_questions = $wpdb->prefix . 'psychometric_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            step_id bigint(20) UNSIGNED NOT NULL,
            question_text text NOT NULL,
            polarity varchar(10) NOT NULL DEFAULT 'positive',
            order_number int(11) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY step_id (step_id)
        ) $charset_collate;";
        
        // Submissions table
        $table_submissions = $wpdb->prefix . 'psychometric_submissions';
        $sql_submissions = "CREATE TABLE $table_submissions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            candidate_name varchar(255) NOT NULL,
            candidate_email varchar(255) NOT NULL,
            candidate_phone varchar(50) NOT NULL,
            interview_date date NOT NULL,
            total_score decimal(5,2) NOT NULL,
            risk_level varchar(50) NOT NULL,
            submitted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Answers table
        $table_answers = $wpdb->prefix . 'psychometric_answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) UNSIGNED NOT NULL,
            question_id bigint(20) UNSIGNED NOT NULL,
            answer_value int(11) NOT NULL,
            normalized_score decimal(5,2) NOT NULL,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_steps);
        dbDelta($sql_questions);
        dbDelta($sql_submissions);
        dbDelta($sql_answers);
    }
    
    // Steps CRUD
    public static function get_all_steps() {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_steps';
        return $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1 ORDER BY step_number");
    }
    
    public static function get_step($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_steps';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }
    
    public static function create_step($step_number, $step_title) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_steps';
        
        $wpdb->insert(
            $table,
            array(
                'step_number' => intval($step_number),
                'step_title' => sanitize_text_field($step_title),
                'is_active' => 1
            ),
            array('%d', '%s', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    public static function update_step($id, $step_number, $step_title) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_steps';
        
        return $wpdb->update(
            $table,
            array(
                'step_number' => intval($step_number),
                'step_title' => sanitize_text_field($step_title)
            ),
            array('id' => intval($id)),
            array('%d', '%s'),
            array('%d')
        );
    }
    
    public static function delete_step($id) {
        global $wpdb;
        $table_steps = $wpdb->prefix . 'psychometric_steps';
        $table_questions = $wpdb->prefix . 'psychometric_questions';
        
        // Delete associated questions first
        $wpdb->delete($table_questions, array('step_id' => intval($id)), array('%d'));
        
        // Delete step
        return $wpdb->delete($table_steps, array('id' => intval($id)), array('%d'));
    }
    
    // Questions CRUD
    public static function get_questions_by_step($step_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_questions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE step_id = %d AND is_active = 1 ORDER BY order_number",
            $step_id
        ));
    }
    
    public static function get_all_questions() {
        global $wpdb;
        $table_questions = $wpdb->prefix . 'psychometric_questions';
        $table_steps = $wpdb->prefix . 'psychometric_steps';
        
        return $wpdb->get_results(
            "SELECT q.*, s.step_number, s.step_title 
            FROM $table_questions q 
            INNER JOIN $table_steps s ON q.step_id = s.id 
            WHERE q.is_active = 1 AND s.is_active = 1 
            ORDER BY s.step_number, q.order_number"
        );
    }
    
    public static function create_question($step_id, $question_text, $polarity, $order_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_questions';
        
        $wpdb->insert(
            $table,
            array(
                'step_id' => intval($step_id),
                'question_text' => sanitize_textarea_field($question_text),
                'polarity' => sanitize_text_field($polarity),
                'order_number' => intval($order_number),
                'is_active' => 1
            ),
            array('%d', '%s', '%s', '%d', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    public static function update_question($id, $question_text, $polarity, $order_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_questions';
        
        return $wpdb->update(
            $table,
            array(
                'question_text' => sanitize_textarea_field($question_text),
                'polarity' => sanitize_text_field($polarity),
                'order_number' => intval($order_number)
            ),
            array('id' => intval($id)),
            array('%s', '%s', '%d'),
            array('%d')
        );
    }
    
    public static function toggle_question_status($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_questions';
        
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $table WHERE id = %d",
            $id
        ));
        
        $new_status = $current ? 0 : 1;
        
        return $wpdb->update(
            $table,
            array('is_active' => $new_status),
            array('id' => intval($id)),
            array('%d'),
            array('%d')
        );
    }
    
    public static function delete_question($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_questions';
        return $wpdb->delete($table, array('id' => intval($id)), array('%d'));
    }
    
    // Submissions
    public static function save_submission($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_submissions';
        
        $wpdb->insert(
            $table,
            array(
                'candidate_name' => sanitize_text_field($data['name']),
                'candidate_email' => sanitize_email($data['email']),
                'candidate_phone' => sanitize_text_field($data['phone']),
                'interview_date' => sanitize_text_field($data['interview_date']),
                'total_score' => floatval($data['total_score']),
                'risk_level' => sanitize_text_field($data['risk_level'])
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public static function save_answers($submission_id, $answers) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_answers';
        
        foreach ($answers as $answer) {
            $wpdb->insert(
                $table,
                array(
                    'submission_id' => intval($submission_id),
                    'question_id' => intval($answer['question_id']),
                    'answer_value' => intval($answer['answer_value']),
                    'normalized_score' => floatval($answer['normalized_score'])
                ),
                array('%d', '%d', '%d', '%f')
            );
        }
    }
    
    public static function get_submissions($limit = 10, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_submissions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    public static function get_total_submissions() {
        global $wpdb;
        $table = $wpdb->prefix . 'psychometric_submissions';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    public static function get_submission_details($id) {
        global $wpdb;
        $table_submissions = $wpdb->prefix . 'psychometric_submissions';
        $table_answers = $wpdb->prefix . 'psychometric_answers';
        $table_questions = $wpdb->prefix . 'psychometric_questions';
        
        $submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_submissions WHERE id = %d",
            $id
        ));
        
        if ($submission) {
            $submission->answers = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, q.question_text, q.polarity 
                FROM $table_answers a 
                INNER JOIN $table_questions q ON a.question_id = q.id 
                WHERE a.submission_id = %d",
                $id
            ));
        }
        
        return $submission;
    }
}