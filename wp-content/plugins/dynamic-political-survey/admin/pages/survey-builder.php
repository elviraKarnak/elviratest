<?php
/**
 * Survey Builder Page
 * Visual drag-and-drop survey creation interface
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
$is_edit = $survey_id > 0;

// Load survey data if editing
$survey = null;
$steps = [];
if ($is_edit) {
    $table_surveys = $wpdb->prefix . 'dps_surveys';
    $survey = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_surveys WHERE id = %d", $survey_id));
    
    if (!$survey) {
        wp_die(__('Survey not found', 'dynamic-survey'));
    }
    
    // Load steps
    $table_steps = $wpdb->prefix . 'dps_steps';
    $steps = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_steps WHERE survey_id = %d ORDER BY step_order ASC",
        $survey_id
    ));
}
?>

<div class="wrap dps-survey-builder">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Survey', 'dynamic-survey') : __('Create New Survey', 'dynamic-survey'); ?>
    </h1>
    
    <?php if ($is_edit): ?>
        <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add'); ?>" class="page-title-action">
            <?php _e('Add New', 'dynamic-survey'); ?>
        </a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <div class="dps-builder-container">
        <form id="dps-survey-form" method="post">
            <input type="hidden" name="survey_id" id="survey_id" value="<?php echo $survey_id; ?>">
            <?php wp_nonce_field('dps_save_survey', 'dps_survey_nonce'); ?>
            
            <!-- Survey Basic Info -->
            <div class="dps-builder-section dps-survey-info">
                <h2><?php _e('Survey Information', 'dynamic-survey'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="survey_title"><?php _e('Survey Title', 'dynamic-survey'); ?> *</label></th>
                        <td>
                            <input type="text" 
                                   id="survey_title" 
                                   name="survey_title" 
                                   class="regular-text" 
                                   value="<?php echo $survey ? esc_attr($survey->title) : ''; ?>" 
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="survey_description"><?php _e('Description', 'dynamic-survey'); ?></label></th>
                        <td>
                            <textarea id="survey_description" 
                                      name="survey_description" 
                                      class="large-text" 
                                      rows="3"><?php echo $survey ? esc_textarea($survey->description) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="survey_status"><?php _e('Status', 'dynamic-survey'); ?></label></th>
                        <td>
                            <select id="survey_status" name="survey_status">
                                <option value="draft" <?php echo ($survey && $survey->status === 'draft') ? 'selected' : ''; ?>>
                                    <?php _e('Draft', 'dynamic-survey'); ?>
                                </option>
                                <option value="active" <?php echo ($survey && $survey->status === 'active') ? 'selected' : ''; ?>>
                                    <?php _e('Active', 'dynamic-survey'); ?>
                                </option>
                                <option value="inactive" <?php echo ($survey && $survey->status === 'inactive') ? 'selected' : ''; ?>>
                                    <?php _e('Inactive', 'dynamic-survey'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Only active surveys will be displayed on the frontend', 'dynamic-survey'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Multi-Step Builder -->
            <div class="dps-builder-section dps-steps-builder">
                <div class="dps-section-header">
                    <h2><?php _e('Survey Steps', 'dynamic-survey'); ?></h2>
                    <button type="button" class="button button-primary" id="add-step-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Step', 'dynamic-survey'); ?>
                    </button>
                </div>
                
                <div id="steps-container" class="dps-steps-container">
                    <?php if (!empty($steps)): ?>
                        <?php foreach ($steps as $index => $step): ?>
                            <?php include DPS_PLUGIN_DIR . 'admin/partials/step-item.php'; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dps-no-steps">
                            <p><?php _e('No steps created yet. Click "Add Step" to create your first step.', 'dynamic-survey'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Save Buttons -->
            <div class="dps-builder-footer">
                <button type="submit" class="button button-primary button-large" id="save-survey-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo $is_edit ? __('Update Survey', 'dynamic-survey') : __('Create Survey', 'dynamic-survey'); ?>
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=dynamic-survey-surveys'); ?>" class="button button-large">
                    <?php _e('Cancel', 'dynamic-survey'); ?>
                </a>
                
                <?php if ($is_edit): ?>
                    <button type="button" class="button button-large dps-preview-btn" data-survey-id="<?php echo $survey_id; ?>">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Preview', 'dynamic-survey'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Step Template (Hidden) -->
<script type="text/template" id="step-template">
    <div class="dps-step-item" data-step-index="{{step_index}}">
        <div class="dps-step-header">
            <span class="dps-step-handle dashicons dashicons-menu"></span>
            <span class="dps-step-number">Step {{step_number}}</span>
            <input type="text" 
                   name="steps[{{step_index}}][title]" 
                   class="dps-step-title-input" 
                   placeholder="<?php _e('Enter step title...', 'dynamic-survey'); ?>" 
                   value="{{step_title}}">
            <button type="button" class="button dps-toggle-step">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <button type="button" class="button dps-delete-step">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        
        <div class="dps-step-content">
            <div class="dps-step-description">
                <textarea name="steps[{{step_index}}][description]" 
                          class="widefat" 
                          rows="2" 
                          placeholder="<?php _e('Step description (optional)', 'dynamic-survey'); ?>">{{step_description}}</textarea>
            </div>
            
            <div class="dps-questions-container" data-step-index="{{step_index}}">
                <div class="dps-questions-header">
                    <h4><?php _e('Questions', 'dynamic-survey'); ?></h4>
                    <button type="button" class="button button-secondary add-question-btn" data-step-index="{{step_index}}">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add Question', 'dynamic-survey'); ?>
                    </button>
                </div>
                
                <div class="dps-questions-list sortable-questions">
                    <!-- Questions will be added here -->
                </div>
            </div>
        </div>
    </div>
</script>

<!-- Question Template (Hidden) -->
<script type="text/template" id="question-template">
    <div class="dps-question-item" data-question-index="{{question_index}}">
        <div class="dps-question-header">
            <span class="dps-question-handle dashicons dashicons-menu"></span>
            <span class="dps-question-code">{{question_code}}</span>
            <input type="text" 
                   name="steps[{{step_index}}][questions][{{question_index}}][text]" 
                   class="dps-question-text-input" 
                   placeholder="<?php _e('Enter your question...', 'dynamic-survey'); ?>" 
                   value="{{question_text}}">
            <button type="button" class="button dps-toggle-question">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
            <button type="button" class="button dps-delete-question">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        
        <div class="dps-question-content">
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Question Code', 'dynamic-survey'); ?></label></th>
                    <td>
                        <input type="text" 
                               name="steps[{{step_index}}][questions][{{question_index}}][code]" 
                               class="regular-text question-code-input" 
                               placeholder="e.g., A1, B2, C3" 
                               value="{{question_code}}">
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Question Type', 'dynamic-survey'); ?></label></th>
                    <td>
                        <select name="steps[{{step_index}}][questions][{{question_index}}][type]" class="question-type-select">
                            <option value="radio"><?php _e('Radio Buttons', 'dynamic-survey'); ?></option>
                            <option value="select"><?php _e('Dropdown Select', 'dynamic-survey'); ?></option>
                            <option value="checkbox"><?php _e('Checkboxes', 'dynamic-survey'); ?></option>
                            <option value="text"><?php _e('Text Input', 'dynamic-survey'); ?></option>
                            <option value="textarea"><?php _e('Text Area', 'dynamic-survey'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php _e('Required', 'dynamic-survey'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="steps[{{step_index}}][questions][{{question_index}}][required]" 
                                   value="1" checked>
                            <?php _e('This question is required', 'dynamic-survey'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr class="conditional-logic-row">
                    <th><label><?php _e('Conditional Logic', 'dynamic-survey'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="enable-conditional" data-question-index="{{question_index}}">
                            <?php _e('Show this question based on another answer', 'dynamic-survey'); ?>
                        </label>
                        
                        <div class="conditional-settings" style="display: none; margin-top: 10px;">
                            <p>
                                <label><?php _e('Show when:', 'dynamic-survey'); ?></label>
                                <select name="steps[{{step_index}}][questions][{{question_index}}][conditional_question]" class="conditional-question-select">
                                    <option value=""><?php _e('Select a question...', 'dynamic-survey'); ?></option>
                                </select>
                            </p>
                            <p>
                                <label><?php _e('Has answer:', 'dynamic-survey'); ?></label>
                                <input type="text" 
                                       name="steps[{{step_index}}][questions][{{question_index}}][conditional_answer]" 
                                       class="regular-text" 
                                       placeholder="<?php _e('e.g., AITC, BJP, Yes', 'dynamic-survey'); ?>">
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
            
            <div class="dps-options-container question-options-area" style="display: none;">
                <div class="dps-options-header">
                    <h5><?php _e('Answer Options', 'dynamic-survey'); ?></h5>
                    <button type="button" class="button button-small add-option-btn" data-question-index="{{question_index}}">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add Option', 'dynamic-survey'); ?>
                    </button>
                </div>
                
                <div class="dps-options-list sortable-options">
                    <!-- Options will be added here -->
                </div>
            </div>
        </div>
    </div>
</script>

<!-- Option Template (Hidden) -->
<script type="text/template" id="option-template">
    <div class="dps-option-item" data-option-index="{{option_index}}">
        <span class="dps-option-handle dashicons dashicons-menu"></span>
        <input type="text" 
               name="steps[{{step_index}}][questions][{{question_index}}][options][{{option_index}}][text]" 
               class="dps-option-text" 
               placeholder="<?php _e('Option text', 'dynamic-survey'); ?>" 
               value="{{option_text}}">
        <input type="text" 
               name="steps[{{step_index}}][questions][{{question_index}}][options][{{option_index}}][value]" 
               class="dps-option-value" 
               placeholder="<?php _e('Value', 'dynamic-survey'); ?>" 
               value="{{option_value}}">
        <button type="button" class="button dps-delete-option">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</script>

<style>
.dps-survey-builder {
    max-width: 1400px;
}

.dps-builder-container {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.dps-builder-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 4px;
}

.dps-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dps-section-header h2 {
    margin: 0;
}

.dps-steps-container {
    margin-top: 20px;
}

.dps-step-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.dps-step-item:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.dps-step-header {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f0f0f1;
    border-bottom: 1px solid #ddd;
    cursor: move;
}

.dps-step-handle {
    color: #999;
    margin-right: 10px;
    cursor: grab;
}

.dps-step-number {
    font-weight: 600;
    margin-right: 15px;
    color: #2271b1;
}

.dps-step-title-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.dps-toggle-step,
.dps-delete-step {
    margin-left: 10px;
}

.dps-step-content {
    padding: 20px;
}

.dps-questions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0 15px;
}

.dps-question-item {
    background: #fafafa;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 10px;
    padding: 15px;
}

.dps-question-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.dps-question-code {
    font-weight: 600;
    color: #646970;
    margin-right: 10px;
    min-width: 50px;
}

.dps-question-text-input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.dps-option-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    padding: 8px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.dps-option-text,
.dps-option-value {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.dps-builder-footer {
    padding: 20px;
    background: #f9f9f9;
    border-top: 1px solid #ddd;
    text-align: right;
}

.dps-builder-footer .button {
    margin-left: 10px;
}

.dps-no-steps {
    text-align: center;
    padding: 40px;
    color: #999;
}

.conditional-settings {
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.sortable-placeholder {
    background: #f0f0f1;
    border: 2px dashed #2271b1;
    visibility: visible !important;
    height: 50px;
    margin: 10px 0;
}
</style>