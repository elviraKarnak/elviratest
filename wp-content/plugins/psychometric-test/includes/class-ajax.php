<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Psychometric_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_psychometric_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_psychometric_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_psychometric_get_submission', array($this, 'get_submission_details'));
        add_action('wp_ajax_nopriv_psychometric_get_submission', array($this, 'get_submission_details'));
    }
    
    public function handle_submission() {
        check_ajax_referer('psychometric_frontend_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $interview_date = sanitize_text_field($_POST['interview_date']);
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($phone) || empty($interview_date) || empty($answers)) {
            wp_send_json_error(array('message' => 'All fields are required'));
            return;
        }
        
        // Calculate scores
        $calculated_answers = array();
        $total_normalized_score = 0;
        $question_count = 0;
        
        foreach ($answers as $question_id => $answer_value) {
            $question_id = intval($question_id);
            $answer_value = intval($answer_value);
            
            // Get question polarity
            global $wpdb;
            $table_questions = $wpdb->prefix . 'psychometric_questions';
            $question = $wpdb->get_row($wpdb->prepare(
                "SELECT polarity FROM $table_questions WHERE id = %d",
                $question_id
            ));
            
            if (!$question) {
                continue;
            }
            
            // Calculate raw score based on polarity
            if ($question->polarity === 'positive') {
                $raw_score = $answer_value; // 1-7
            } else {
                $raw_score = 8 - $answer_value; // Reverse for negative polarity
            }
            
            // Normalize to 0-100 scale
            $normalized_score = (($raw_score - 1) / 6) * 100;
            
            $calculated_answers[] = array(
                'question_id' => $question_id,
                'answer_value' => $answer_value,
                'normalized_score' => $normalized_score
            );
            
            $total_normalized_score += $normalized_score;
            $question_count++;
        }
        
        // Calculate average score
        $total_score = $question_count > 0 ? $total_normalized_score / $question_count : 0;
        
        // Determine risk level
        if ($total_score >= 0 && $total_score <= 24) {
            $risk_level = 'Safe';
        } elseif ($total_score >= 25 && $total_score <= 49) {
            $risk_level = 'Acceptable';
        } elseif ($total_score >= 50 && $total_score <= 74) {
            $risk_level = 'Risk';
        } else {
            $risk_level = 'Harmful';
        }
        
        // Save submission
        $submission_data = array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'interview_date' => $interview_date,
            'total_score' => $total_score,
            'risk_level' => $risk_level
        );
        
        $submission_id = Psychometric_Database::save_submission($submission_data);
        
        if ($submission_id) {
            // Save answers
            Psychometric_Database::save_answers($submission_id, $calculated_answers);
            
            // Send email notification
            Psychometric_Email::send_notification($submission_data, $submission_id);
            
            wp_send_json_success(array(
                'message' => 'Assessment submitted successfully',
                'submission_id' => $submission_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save submission'));
        }
    }
    
    public function get_submission_details() {
        check_ajax_referer('psychometric_frontend_nonce', 'nonce');
        
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        
        if (!$submission_id) {
            wp_send_json_error(array('message' => 'Invalid submission ID'));
            return;
        }
        
        $submission = Psychometric_Database::get_submission_details($submission_id);
        
        if (!$submission) {
            wp_send_json_error(array('message' => 'Submission not found'));
            return;
        }
        
        // Format answers for display
        $formatted_answers = array();
        if (isset($submission->answers) && is_array($submission->answers)) {
            foreach ($submission->answers as $answer) {
                $formatted_answers[] = array(
                    'question_text' => $answer->question_text,
                    'polarity' => $answer->polarity,
                    'answer_value' => intval($answer->answer_value),
                    'answer_label' => $this->get_answer_label($answer->answer_value),
                    'normalized_score' => floatval($answer->normalized_score)
                );
            }
        }
        
        // Get risk description
        $risk_descriptions = array(
            'Safe' => array(
                'icon' => '✅',
                'title' => 'Safe - Recommended',
                'points' => array(
                    'Positive, stable personality traits',
                    'Low risk of counterproductive behavior',
                    'Suitable for workplace integration'
                )
            ),
            'Acceptable' => array(
                'icon' => '⚖️',
                'title' => 'Acceptable / Moderate Risk',
                'points' => array(
                    'Some areas of concern (e.g., stress handling, adaptability)',
                    'Manageable with guidance or training',
                    'Not harmful but needs monitoring'
                )
            ),
            'Risk' => array(
                'icon' => '⚠️',
                'title' => 'Risk / Concerning',
                'points' => array(
                    'Traits showing potential absenteeism, lack of responsibility, or conflict-prone behavior',
                    'Requires corrective action or counseling',
                    'Could affect performance if unchecked'
                )
            ),
            'Harmful' => array(
                'icon' => '❌',
                'title' => 'Harmful / High Risk',
                'points' => array(
                    'Strong negative indicators (dishonesty, aggression, lack of accountability)',
                    'Unsafe for team harmony or organizational culture',
                    'Not recommended for sensitive roles'
                )
            )
        );
        
        $risk_desc = isset($risk_descriptions[$submission->risk_level]) ? $risk_descriptions[$submission->risk_level] : null;
        
        // Format submission data
        $submission_data = array(
            'id' => $submission->id,
            'candidate_name' => $submission->candidate_name,
            'candidate_email' => $submission->candidate_email,
            'candidate_phone' => $submission->candidate_phone,
            'interview_date' => $submission->interview_date,
            'total_score' => floatval($submission->total_score),
            'risk_level' => $submission->risk_level,
            'submitted_at' => $submission->submitted_at,
            'answers' => $formatted_answers
        );
        
        wp_send_json_success(array(
            'submission' => $submission_data,
            'risk_description' => $risk_desc
        ));
    }
    
    private function get_answer_label($value) {
        $labels = array(
            1 => 'Strongly Agree',
            2 => 'Agree',
            3 => 'Slightly Agree',
            4 => 'Neutral',
            5 => 'Slightly Disagree',
            6 => 'Disagree',
            7 => 'Strongly Disagree'
        );
        
        return isset($labels[$value]) ? $labels[$value] : $value;
    }
}