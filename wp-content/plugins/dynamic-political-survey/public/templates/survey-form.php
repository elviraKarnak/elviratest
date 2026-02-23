<?php
/**
 * Frontend Survey Form Template
 * Multi-step survey with conditional logic
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$survey_id = intval($atts['id']);

// Get survey
$table_surveys = $wpdb->prefix . 'dps_surveys';
$survey = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_surveys WHERE id = %d AND status = 'active'",
    $survey_id
));

if (!$survey) {
    return '<p class="dps-error">Survey not found or inactive.</p>';
}

// Get steps
$table_steps = $wpdb->prefix . 'dps_steps';
$steps = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_steps WHERE survey_id = %d ORDER BY step_order",
    $survey_id
));

if (empty($steps)) {
    return '<p class="dps-error">No questions available.</p>';
}

// Get all questions with options
$table_questions = $wpdb->prefix . 'dps_questions';
$table_options = $wpdb->prefix . 'dps_question_options';

$step_ids = array_column($steps, 'id');
$step_ids_string = implode(',', array_map('intval', $step_ids));

$questions = $wpdb->get_results(
    "SELECT * FROM $table_questions WHERE step_id IN ($step_ids_string) ORDER BY question_order"
);

// Get options for all questions
$question_ids = array_column($questions, 'id');
if (!empty($question_ids)) {
    $question_ids_string = implode(',', array_map('intval', $question_ids));
    $all_options = $wpdb->get_results(
        "SELECT * FROM $table_options WHERE question_id IN ($question_ids_string) ORDER BY option_order"
    );
    
    // Group options by question_id
    $options_by_question = [];
    foreach ($all_options as $option) {
        $options_by_question[$option->question_id][] = $option;
    }
} else {
    $options_by_question = [];
}

// Get districts for location questions
$table_districts = $wpdb->prefix . 'dps_districts';
$districts = $wpdb->get_results("SELECT * FROM $table_districts ORDER BY name");
?>

<div class="dps-survey-wrapper" data-survey-id="<?php echo $survey_id; ?>">
    <div class="dps-survey-header">
        <h2 class="dps-survey-title"><?php echo esc_html($survey->title); ?></h2>
        <?php if ($survey->description): ?>
            <p class="dps-survey-description"><?php echo esc_html($survey->description); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Progress Bar -->
    <div class="dps-progress-container">
        <div class="dps-progress-bar">
            <div class="dps-progress-fill" style="width: <?php echo round(100 / count($steps)); ?>%"></div>
        </div>
        <div class="dps-progress-text">
            <span class="current-step">1</span> / <span class="total-steps"><?php echo count($steps); ?></span>
        </div>
    </div>
    
    <form id="dps-survey-form" class="dps-survey-form" data-survey-id="<?php echo $survey_id; ?>">
        <?php wp_nonce_field('dps_submit_survey', 'dps_survey_nonce'); ?>
        
        <?php foreach ($steps as $step_index => $step): ?>
            <div class="dps-step <?php echo $step_index === 0 ? 'active' : ''; ?>" 
                 data-step="<?php echo $step_index + 1; ?>">
                
                <div class="dps-step-header">
                    <h3 class="dps-step-title"><?php echo esc_html($step->title); ?></h3>
                    <?php if ($step->description): ?>
                        <p class="dps-step-description"><?php echo esc_html($step->description); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="dps-questions">
                    <?php 
                    // Get questions for this step
                    $step_questions = array_filter($questions, function($q) use ($step) {
                        return $q->step_id == $step->id;
                    });
                    
                    foreach ($step_questions as $question): 
                        $options = isset($options_by_question[$question->id]) ? $options_by_question[$question->id] : [];
                        $required_attr = $question->is_required ? 'required' : '';
                        $required_label = $question->is_required ? '<span class="required">*</span>' : '';
                        
                        // Conditional data attributes
                        $conditional_attrs = '';
                        if ($question->conditional_question_id) {
                            $conditional_attrs = sprintf(
                                'data-conditional="true" data-parent-question="%d" data-trigger-value="%s" style="display: none;"',
                                $question->conditional_question_id,
                                esc_attr($question->conditional_answer)
                            );
                        }
                    ?>
                        <div class="dps-question" 
                             data-question-id="<?php echo $question->id; ?>"
                             data-question-type="<?php echo $question->question_type; ?>"
                             <?php echo $conditional_attrs; ?>>
                            
                            <label class="dps-question-label">
                                <?php if ($question->question_code): ?>
                                    <span class="question-code"><?php echo esc_html($question->question_code); ?>.</span>
                                <?php endif; ?>
                                <?php echo esc_html($question->question_text); ?>
                                <?php echo $required_label; ?>
                            </label>
                            
                            <?php if ($question->question_type === 'radio'): ?>
                                <div class="dps-radio-group">
                                    <?php foreach ($options as $option): ?>
                                        <label class="dps-radio-option">
                                            <input type="radio" 
                                                   name="question_<?php echo $question->id; ?>" 
                                                   value="<?php echo esc_attr($option->option_value); ?>"
                                                   <?php echo $required_attr; ?>>
                                            <span><?php echo esc_html($option->option_text); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($question->question_type === 'select'): ?>
                                <select name="question_<?php echo $question->id; ?>" 
                                        class="dps-select"
                                        <?php echo $required_attr; ?>>
                                    <option value="">-- Select an option --</option>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?php echo esc_attr($option->option_value); ?>">
                                            <?php echo esc_html($option->option_text); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            
                            <?php elseif ($question->question_type === 'checkbox'): ?>
                                <div class="dps-checkbox-group">
                                    <?php foreach ($options as $option): ?>
                                        <label class="dps-checkbox-option">
                                            <input type="checkbox" 
                                                   name="question_<?php echo $question->id; ?>[]" 
                                                   value="<?php echo esc_attr($option->option_value); ?>">
                                            <span><?php echo esc_html($option->option_text); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($question->question_type === 'text'): ?>
                                <input type="text" 
                                       name="question_<?php echo $question->id; ?>" 
                                       class="dps-text-input"
                                       <?php echo $required_attr; ?>>
                            
                            <?php elseif ($question->question_type === 'textarea'): ?>
                                <textarea name="question_<?php echo $question->id; ?>" 
                                          class="dps-textarea"
                                          rows="4"
                                          <?php echo $required_attr; ?>></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="dps-step-navigation">
                    <?php if ($step_index > 0): ?>
                        <button type="button" class="dps-btn dps-btn-prev">
                            ← Previous
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($step_index < count($steps) - 1): ?>
                        <button type="button" class="dps-btn dps-btn-next">
                            Next →
                        </button>
                    <?php else: ?>
                        <button type="submit" class="dps-btn dps-btn-submit">
                            Submit Survey
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
    
    <div class="dps-loading" style="display: none;">
        <div class="dps-spinner"></div>
        <p>Submitting your response...</p>
    </div>
</div>

<style>
.dps-survey-wrapper {
    max-width: 800px;
    margin: 40px auto;
    padding: 40px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.dps-survey-header {
    text-align: center;
    margin-bottom: 40px;
}

.dps-survey-title {
    font-size: 32px;
    color: #2c3e50;
    margin-bottom: 15px;
}

.dps-survey-description {
    font-size: 16px;
    color: #7f8c8d;
}

.dps-progress-container {
    margin-bottom: 40px;
}

.dps-progress-bar {
    height: 8px;
    background: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.dps-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.dps-progress-text {
    text-align: center;
    font-size: 14px;
    color: #7f8c8d;
    font-weight: 600;
}

.dps-step {
    display: none;
}

.dps-step.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dps-step-header {
    margin-bottom: 30px;
}

.dps-step-title {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.dps-step-description {
    color: #7f8c8d;
    font-size: 14px;
}

.dps-question {
    margin-bottom: 30px;
}

.dps-question-label {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 12px;
}

.question-code {
    color: #667eea;
    font-weight: 700;
    margin-right: 5px;
}

.required {
    color: #e74c3c;
    margin-left: 3px;
}

.dps-radio-group,
.dps-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dps-radio-option,
.dps-checkbox-option {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.dps-radio-option:hover,
.dps-checkbox-option:hover {
    background: #e9ecef;
    border-color: #667eea;
}

.dps-radio-option input,
.dps-checkbox-option input {
    margin-right: 12px;
    cursor: pointer;
}

.dps-select,
.dps-text-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s;
}

.dps-select:focus,
.dps-text-input:focus,
.dps-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.dps-textarea {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-family: inherit;
    resize: vertical;
}

.dps-step-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #ecf0f1;
}

.dps-btn {
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.dps-btn-prev {
    background: #ecf0f1;
    color: #2c3e50;
}

.dps-btn-prev:hover {
    background: #d5dbdb;
}

.dps-btn-next,
.dps-btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    margin-left: auto;
}

.dps-btn-next:hover,
.dps-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.dps-loading {
    text-align: center;
    padding: 60px 20px;
}

.dps-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.dps-error {
    padding: 20px;
    background: #fee;
    color: #c33;
    border-radius: 8px;
    text-align: center;
}

@media (max-width: 768px) {
    .dps-survey-wrapper {
        padding: 20px;
        margin: 20px;
    }
    
    .dps-survey-title {
        font-size: 24px;
    }
    
    .dps-step-navigation {
        flex-direction: column;
        gap: 10px;
    }
    
    .dps-btn {
        width: 100%;
    }
}
</style>