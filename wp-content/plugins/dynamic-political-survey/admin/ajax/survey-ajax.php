<?php
/**
 * Survey AJAX Handlers
 * Handles all survey-related AJAX operations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Save/Update Survey
add_action('wp_ajax_dps_save_survey', 'dps_ajax_save_survey');
function dps_ajax_save_survey() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;
    $title = sanitize_text_field($_POST['survey_title']);
    $description = sanitize_textarea_field($_POST['survey_description']);
    $status = sanitize_text_field($_POST['survey_status']);
    
    if (empty($title)) {
        wp_send_json_error(['message' => 'Survey title is required']);
    }
    
    $table_surveys = $wpdb->prefix . 'dps_surveys';
    
    $survey_data = [
        'title' => $title,
        'description' => $description,
        'status' => $status
    ];
    
    // Update or Insert Survey
    if ($survey_id > 0) {
        $wpdb->update($table_surveys, $survey_data, ['id' => $survey_id]);
    } else {
        $wpdb->insert($table_surveys, $survey_data);
        $survey_id = $wpdb->insert_id;
    }
    
    // Delete existing steps for this survey
    $table_steps = $wpdb->prefix . 'dps_steps';
    $table_questions = $wpdb->prefix . 'dps_questions';
    $table_options = $wpdb->prefix . 'dps_question_options';
    
    $step_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $table_steps WHERE survey_id = %d",
        $survey_id
    ));
    
    if (!empty($step_ids)) {
        $step_ids_string = implode(',', array_map('intval', $step_ids));
        
        $question_ids = $wpdb->get_col(
            "SELECT id FROM $table_questions WHERE step_id IN ($step_ids_string)"
        );
        
        if (!empty($question_ids)) {
            $question_ids_string = implode(',', array_map('intval', $question_ids));
            $wpdb->query("DELETE FROM $table_options WHERE question_id IN ($question_ids_string)");
        }
        
        $wpdb->query("DELETE FROM $table_questions WHERE step_id IN ($step_ids_string)");
    }
    
    $wpdb->delete($table_steps, ['survey_id' => $survey_id]);
    
    // Save Steps
    if (isset($_POST['steps']) && is_array($_POST['steps'])) {
        foreach ($_POST['steps'] as $step_order => $step_data) {
            $step_title = sanitize_text_field($step_data['title']);
            $step_description = sanitize_textarea_field($step_data['description'] ?? '');
            
            $wpdb->insert($table_steps, [
                'survey_id' => $survey_id,
                'title' => $step_title,
                'description' => $step_description,
                'step_order' => $step_order
            ]);
            
            $step_id = $wpdb->insert_id;
            
            // Save Questions
            if (isset($step_data['questions']) && is_array($step_data['questions'])) {
                foreach ($step_data['questions'] as $q_order => $question_data) {
                    $question_code = sanitize_text_field($question_data['code'] ?? '');
                    $question_text = sanitize_textarea_field($question_data['text']);
                    $question_type = sanitize_text_field($question_data['type']);
                    $is_required = isset($question_data['required']) ? 1 : 0;
                    $conditional_question = isset($question_data['conditional_question']) ? intval($question_data['conditional_question']) : null;
                    $conditional_answer = sanitize_text_field($question_data['conditional_answer'] ?? '');
                    
                    $wpdb->insert($table_questions, [
                        'step_id' => $step_id,
                        'question_code' => $question_code,
                        'question_text' => $question_text,
                        'question_type' => $question_type,
                        'is_required' => $is_required,
                        'conditional_question_id' => $conditional_question,
                        'conditional_answer' => $conditional_answer,
                        'question_order' => $q_order
                    ]);
                    
                    $question_id = $wpdb->insert_id;
                    
                    // Save Options
                    if (isset($question_data['options']) && is_array($question_data['options'])) {
                        foreach ($question_data['options'] as $opt_order => $option_data) {
                            $option_text = sanitize_text_field($option_data['text']);
                            $option_value = sanitize_text_field($option_data['value']);
                            
                            if (!empty($option_text)) {
                                $wpdb->insert($table_options, [
                                    'question_id' => $question_id,
                                    'option_text' => $option_text,
                                    'option_value' => $option_value,
                                    'option_order' => $opt_order
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
    
    wp_send_json_success([
        'message' => 'Survey saved successfully',
        'survey_id' => $survey_id
    ]);
}

// Get Survey Questions
add_action('wp_ajax_dps_get_survey_questions', 'dps_ajax_get_survey_questions');
function dps_ajax_get_survey_questions() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = intval($_POST['survey_id']);
    
    $table_steps = $wpdb->prefix . 'dps_steps';
    $table_questions = $wpdb->prefix . 'dps_questions';
    
    $questions = $wpdb->get_results($wpdb->prepare("
        SELECT q.id, q.question_code as code, q.question_text as text
        FROM $table_questions q
        INNER JOIN $table_steps s ON q.step_id = s.id
        WHERE s.survey_id = %d
        ORDER BY s.step_order, q.question_order
    ", $survey_id));
    
    wp_send_json_success(['questions' => $questions]);
}

// Delete Survey
add_action('wp_ajax_dps_delete_survey', 'dps_ajax_delete_survey');
function dps_ajax_delete_survey() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = intval($_POST['survey_id']);
    
    $table_surveys = $wpdb->prefix . 'dps_surveys';
    $wpdb->delete($table_surveys, ['id' => $survey_id]);
    
    // Cascade delete handled by database foreign keys
    
    wp_send_json_success(['message' => 'Survey deleted successfully']);
}

// Duplicate Survey
add_action('wp_ajax_dps_duplicate_survey', 'dps_ajax_duplicate_survey');
function dps_ajax_duplicate_survey() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = intval($_POST['survey_id']);
    
    $table_surveys = $wpdb->prefix . 'dps_surveys';
    $table_steps = $wpdb->prefix . 'dps_steps';
    $table_questions = $wpdb->prefix . 'dps_questions';
    $table_options = $wpdb->prefix . 'dps_question_options';
    
    // Get original survey
    $survey = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_surveys WHERE id = %d",
        $survey_id
    ));
    
    if (!$survey) {
        wp_send_json_error(['message' => 'Survey not found']);
    }
    
    // Create duplicate survey
    $wpdb->insert($table_surveys, [
        'title' => $survey->title . ' (Copy)',
        'description' => $survey->description,
        'status' => 'draft'
    ]);
    
    $new_survey_id = $wpdb->insert_id;
    
    // Duplicate steps
    $steps = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_steps WHERE survey_id = %d ORDER BY step_order",
        $survey_id
    ));
    
    foreach ($steps as $step) {
        $wpdb->insert($table_steps, [
            'survey_id' => $new_survey_id,
            'title' => $step->title,
            'description' => $step->description,
            'step_order' => $step->step_order
        ]);
        
        $new_step_id = $wpdb->insert_id;
        $old_step_id = $step->id;
        
        // Duplicate questions
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_questions WHERE step_id = %d ORDER BY question_order",
            $old_step_id
        ));
        
        foreach ($questions as $question) {
            $wpdb->insert($table_questions, [
                'step_id' => $new_step_id,
                'question_code' => $question->question_code,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'is_required' => $question->is_required,
                'question_order' => $question->question_order
            ]);
            
            $new_question_id = $wpdb->insert_id;
            $old_question_id = $question->id;
            
            // Duplicate options
            $options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_options WHERE question_id = %d ORDER BY option_order",
                $old_question_id
            ));
            
            foreach ($options as $option) {
                $wpdb->insert($table_options, [
                    'question_id' => $new_question_id,
                    'option_text' => $option->option_text,
                    'option_value' => $option->option_value,
                    'option_order' => $option->option_order
                ]);
            }
        }
    }
    
    wp_send_json_success([
        'message' => 'Survey duplicated successfully',
        'survey_id' => $new_survey_id
    ]);
}