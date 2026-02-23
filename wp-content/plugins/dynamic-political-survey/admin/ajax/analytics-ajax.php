<?php
/**
 * Analytics AJAX Handlers
 * Handles analytics generation and export
 */

if (!defined('ABSPATH')) {
    exit;
}

// Generate Analytics
add_action('wp_ajax_dps_generate_analytics', 'dps_ajax_generate_analytics');
function dps_ajax_generate_analytics() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = intval($_POST['survey_id']);
    $question_id = intval($_POST['question_id']);
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    
    // Get question details
    $table_questions = $wpdb->prefix . 'dps_questions';
    $question = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_questions WHERE id = %d",
        $question_id
    ));
    
    if (!$question) {
        wp_send_json_error(['message' => 'Question not found']);
    }
    
    // Build query to get answers
    $table_submissions = $wpdb->prefix . 'dps_submissions';
    $table_answers = $wpdb->prefix . 'dps_submission_answers';
    
    $sql = "SELECT sa.answer_text, COUNT(*) as count
            FROM $table_answers sa
            INNER JOIN $table_submissions s ON sa.submission_id = s.id
            WHERE s.survey_id = %d AND sa.question_id = %d";
    
    $query_params = [$survey_id, $question_id];
    
    // Apply filters
    if (!empty($filters['district'])) {
        $sql .= " AND s.district_id = %d";
        $query_params[] = intval($filters['district']);
    }
    
    if (!empty($filters['loksabha'])) {
        $sql .= " AND s.loksabha_id = %d";
        $query_params[] = intval($filters['loksabha']);
    }
    
    if (!empty($filters['assembly'])) {
        $sql .= " AND s.assembly_id = %d";
        $query_params[] = intval($filters['assembly']);
    }
    
    // Date filters
    if (!empty($filters['year'])) {
        $sql .= " AND YEAR(s.submitted_at) = %d";
        $query_params[] = intval($filters['year']);
    }
    
    if (!empty($filters['month'])) {
        $sql .= " AND MONTH(s.submitted_at) = %d";
        $query_params[] = intval($filters['month']);
    }
    
    // Demographic filters (need to get from other question answers)
    // This requires joining with other answers - simplified for now
    
    $sql .= " GROUP BY sa.answer_text ORDER BY count DESC";
    
    $results = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    
    // Process results
    $labels = [];
    $values = [];
    $total = 0;
    
    foreach ($results as $result) {
        $labels[] = $result->answer_text;
        $values[] = (int)$result->count;
        $total += (int)$result->count;
    }
    
    // Calculate percentages
    $percentages = [];
    foreach ($values as $value) {
        $percentages[] = $total > 0 ? round(($value / $total) * 100, 1) : 0;
    }
    
    // Get most popular answer
    $most_popular = !empty($labels) ? $labels[0] : '-';
    
    // Get date range
    $date_range_sql = "SELECT MIN(submitted_at) as min_date, MAX(submitted_at) as max_date
                       FROM $table_submissions
                       WHERE survey_id = %d";
    $date_range = $wpdb->get_row($wpdb->prepare($date_range_sql, $survey_id));
    
    $date_range_text = '-';
    if ($date_range && $date_range->min_date && $date_range->max_date) {
        $min = date('M d, Y', strtotime($date_range->min_date));
        $max = date('M d, Y', strtotime($date_range->max_date));
        $date_range_text = $min . ' - ' . $max;
    }
    
    wp_send_json_success([
        'labels' => $labels,
        'values' => $values,
        'percentages' => $percentages,
        'total_responses' => $total,
        'unique_answers' => count($labels),
        'most_popular' => $most_popular,
        'date_range' => $date_range_text,
        'question_text' => $question->question_text
    ]);
}

// Export to CSV
add_action('wp_ajax_dps_export_csv', 'dps_ajax_export_csv');
function dps_ajax_export_csv() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    
    $survey_id = intval($_POST['survey_id']);
    $question_id = intval($_POST['question_id']);
    $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : [];
    
    // Get question details
    $table_questions = $wpdb->prefix . 'dps_questions';
    $question = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_questions WHERE id = %d",
        $question_id
    ));
    
    // Build query
    $table_submissions = $wpdb->prefix . 'dps_submissions';
    $table_answers = $wpdb->prefix . 'dps_submission_answers';
    
    $sql = "SELECT sa.answer_text, COUNT(*) as count
            FROM $table_answers sa
            INNER JOIN $table_submissions s ON sa.submission_id = s.id
            WHERE s.survey_id = %d AND sa.question_id = %d";
    
    $query_params = [$survey_id, $question_id];
    
    // Apply same filters as analytics
    if (!empty($filters['district'])) {
        $sql .= " AND s.district_id = %d";
        $query_params[] = intval($filters['district']);
    }
    
    if (!empty($filters['loksabha'])) {
        $sql .= " AND s.loksabha_id = %d";
        $query_params[] = intval($filters['loksabha']);
    }
    
    if (!empty($filters['assembly'])) {
        $sql .= " AND s.assembly_id = %d";
        $query_params[] = intval($filters['assembly']);
    }
    
    if (!empty($filters['year'])) {
        $sql .= " AND YEAR(s.submitted_at) = %d";
        $query_params[] = intval($filters['year']);
    }
    
    if (!empty($filters['month'])) {
        $sql .= " AND MONTH(s.submitted_at) = %d";
        $query_params[] = intval($filters['month']);
    }
    
    $sql .= " GROUP BY sa.answer_text ORDER BY count DESC";
    
    $results = $wpdb->get_results($wpdb->prepare($sql, $query_params));
    
    // Calculate total and percentages
    $total = 0;
    foreach ($results as $result) {
        $total += (int)$result->count;
    }
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="survey-analytics-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['Question: ' . $question->question_text]);
    fputcsv($output, ['Total Responses: ' . $total]);
    fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Column headers
    fputcsv($output, ['Answer', 'Count', 'Percentage']);
    
    // Data rows
    foreach ($results as $result) {
        $percentage = $total > 0 ? round(((int)$result->count / $total) * 100, 1) : 0;
        fputcsv($output, [
            $result->answer_text,
            $result->count,
            $percentage . '%'
        ]);
    }
    
    fclose($output);
    exit;
}

// Get All Submissions (for submissions list page)
add_action('wp_ajax_dps_get_submissions', 'dps_ajax_get_submissions');
function dps_ajax_get_submissions() {
    check_ajax_referer('dps_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    global $wpdb;
    
    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $table_submissions = $wpdb->prefix . 'dps_submissions';
    $table_surveys = $wpdb->prefix . 'dps_surveys';
    $table_districts = $wpdb->prefix . 'dps_districts';
    
    $sql = "SELECT s.*, sv.title as survey_title, d.name as district_name
            FROM $table_submissions s
            LEFT JOIN $table_surveys sv ON s.survey_id = sv.id
            LEFT JOIN $table_districts d ON s.district_id = d.id";
    
    $count_sql = "SELECT COUNT(*) FROM $table_submissions s";
    
    if ($survey_id > 0) {
        $sql .= " WHERE s.survey_id = %d";
        $count_sql .= " WHERE s.survey_id = %d";
    }
    
    $sql .= " ORDER BY s.submitted_at DESC LIMIT %d OFFSET %d";
    
    if ($survey_id > 0) {
        $submissions = $wpdb->get_results($wpdb->prepare($sql, $survey_id, $per_page, $offset));
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $survey_id));
    } else {
        $submissions = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        $total = $wpdb->get_var($count_sql);
    }
    
    wp_send_json_success([
        'submissions' => $submissions,
        'total' => $total,
        'pages' => ceil($total / $per_page),
        'current_page' => $page
    ]);
}