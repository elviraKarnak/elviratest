/**
 * Survey Builder JavaScript
 * Handles drag-and-drop, conditional logic, and dynamic form building
 */

(function($) {
    'use strict';
    
    let stepCounter = 0;
    let questionCounter = 0;
    let optionCounter = 0;
    
    const SurveyBuilder = {
        
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.loadExistingData();
        },
        
        bindEvents: function() {
            // Add Step
            $(document).on('click', '#add-step-btn', this.addStep);
            
            // Delete Step
            $(document).on('click', '.dps-delete-step', this.deleteStep);
            
            // Toggle Step
            $(document).on('click', '.dps-toggle-step', this.toggleStep);
            
            // Add Question
            $(document).on('click', '.add-question-btn', this.addQuestion);
            
            // Delete Question
            $(document).on('click', '.dps-delete-question', this.deleteQuestion);
            
            // Toggle Question
            $(document).on('click', '.dps-toggle-question', this.toggleQuestion);
            
            // Question Type Change
            $(document).on('change', '.question-type-select', this.handleQuestionTypeChange);
            
            // Add Option
            $(document).on('click', '.add-option-btn', this.addOption);
            
            // Delete Option
            $(document).on('click', '.dps-delete-option', this.deleteOption);
            
            // Conditional Logic Toggle
            $(document).on('change', '.enable-conditional', this.toggleConditionalLogic);
            
            // Update Question Code on input
            $(document).on('input', '.question-code-input', this.updateQuestionCodeDisplay);
            
            // Form Submit
            $('#dps-survey-form').on('submit', this.saveSurvey);
        },
        
        initSortable: function() {
            // Make steps sortable
            $('#steps-container').sortable({
                handle: '.dps-step-handle',
                placeholder: 'sortable-placeholder',
                update: function() {
                    SurveyBuilder.updateStepNumbers();
                }
            });
            
            // Make questions sortable
            $(document).on('mouseenter', '.sortable-questions', function() {
                if (!$(this).hasClass('ui-sortable')) {
                    $(this).sortable({
                        handle: '.dps-question-handle',
                        placeholder: 'sortable-placeholder',
                        connectWith: '.sortable-questions'
                    });
                }
            });
            
            // Make options sortable
            $(document).on('mouseenter', '.sortable-options', function() {
                if (!$(this).hasClass('ui-sortable')) {
                    $(this).sortable({
                        handle: '.dps-option-handle',
                        placeholder: 'sortable-placeholder'
                    });
                }
            });
        },
        
        addStep: function(e) {
            e.preventDefault();
            
            $('.dps-no-steps').remove();
            
            const stepIndex = stepCounter++;
            const stepNumber = $('#steps-container .dps-step-item').length + 1;
            
            const template = $('#step-template').html();
            const stepHtml = template
                .replace(/{{step_index}}/g, stepIndex)
                .replace(/{{step_number}}/g, stepNumber)
                .replace(/{{step_title}}/g, '')
                .replace(/{{step_description}}/g, '');
            
            $('#steps-container').append(stepHtml);
            
            // Initialize sortable for new step's questions
            $(`[data-step-index="${stepIndex}"] .sortable-questions`).sortable({
                handle: '.dps-question-handle',
                placeholder: 'sortable-placeholder'
            });
        },
        
        deleteStep: function(e) {
            e.preventDefault();
            
            if (!confirm(dpsAdmin.strings.confirm_delete)) {
                return;
            }
            
            $(this).closest('.dps-step-item').fadeOut(300, function() {
                $(this).remove();
                SurveyBuilder.updateStepNumbers();
                
                if ($('#steps-container .dps-step-item').length === 0) {
                    $('#steps-container').html('<div class="dps-no-steps"><p>' + 
                        'No steps created yet. Click "Add Step" to create your first step.' + 
                        '</p></div>');
                }
            });
        },
        
        toggleStep: function(e) {
            e.preventDefault();
            
            const $step = $(this).closest('.dps-step-item');
            const $content = $step.find('.dps-step-content');
            const $icon = $(this).find('.dashicons');
            
            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        },
        
        addQuestion: function(e) {
            e.preventDefault();
            
            const stepIndex = $(this).data('step-index');
            const questionIndex = questionCounter++;
            const questionNumber = $(this).closest('.dps-questions-container')
                .find('.dps-question-item').length + 1;
            
            const template = $('#question-template').html();
            const questionHtml = template
                .replace(/{{step_index}}/g, stepIndex)
                .replace(/{{question_index}}/g, questionIndex)
                .replace(/{{question_code}}/g, 'Q' + questionNumber)
                .replace(/{{question_text}}/g, '');
            
            $(`[data-step-index="${stepIndex}"] .sortable-questions`).append(questionHtml);
            
            // Update conditional question options
            SurveyBuilder.updateConditionalOptions();
        },
        
        deleteQuestion: function(e) {
            e.preventDefault();
            
            if (!confirm(dpsAdmin.strings.confirm_delete)) {
                return;
            }
            
            $(this).closest('.dps-question-item').fadeOut(300, function() {
                $(this).remove();
                SurveyBuilder.updateConditionalOptions();
            });
        },
        
        toggleQuestion: function(e) {
            e.preventDefault();
            
            const $question = $(this).closest('.dps-question-item');
            const $content = $question.find('.dps-question-content');
            const $icon = $(this).find('.dashicons');
            
            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        },
        
        handleQuestionTypeChange: function() {
            const type = $(this).val();
            const $question = $(this).closest('.dps-question-item');
            const $optionsArea = $question.find('.question-options-area');
            
            // Show options area for radio, select, checkbox
            if (['radio', 'select', 'checkbox'].includes(type)) {
                $optionsArea.slideDown();
                
                // Add default options if none exist
                if ($optionsArea.find('.dps-option-item').length === 0) {
                    const questionIndex = $question.data('question-index');
                    const stepIndex = $question.closest('.dps-step-item').data('step-index');
                    
                    for (let i = 1; i <= 3; i++) {
                        SurveyBuilder.addOptionProgrammatically(stepIndex, questionIndex, `Option ${i}`, `option_${i}`);
                    }
                }
            } else {
                $optionsArea.slideUp();
            }
        },
        
        addOption: function(e) {
            e.preventDefault();
            
            const questionIndex = $(this).data('question-index');
            const $question = $(this).closest('.dps-question-item');
            const stepIndex = $question.closest('.dps-step-item').data('step-index');
            
            SurveyBuilder.addOptionProgrammatically(stepIndex, questionIndex, '', '');
        },
        
        addOptionProgrammatically: function(stepIndex, questionIndex, text, value) {
            const optionIndex = optionCounter++;
            
            const template = $('#option-template').html();
            const optionHtml = template
                .replace(/{{step_index}}/g, stepIndex)
                .replace(/{{question_index}}/g, questionIndex)
                .replace(/{{option_index}}/g, optionIndex)
                .replace(/{{option_text}}/g, text)
                .replace(/{{option_value}}/g, value);
            
            $(`[data-question-index="${questionIndex}"] .sortable-options`).append(optionHtml);
        },
        
        deleteOption: function(e) {
            e.preventDefault();
            
            $(this).closest('.dps-option-item').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        toggleConditionalLogic: function() {
            const $settings = $(this).closest('td').find('.conditional-settings');
            
            if ($(this).is(':checked')) {
                $settings.slideDown();
            } else {
                $settings.slideUp();
            }
        },
        
        updateConditionalOptions: function() {
            const $selects = $('.conditional-question-select');
            
            $selects.each(function() {
                const currentValue = $(this).val();
                $(this).empty().append('<option value="">Select a question...</option>');
                
                // Get all questions before this one
                $('.dps-question-item').each(function() {
                    const code = $(this).find('.question-code-input').val();
                    const text = $(this).find('.dps-question-text-input').val();
                    const questionIndex = $(this).data('question-index');
                    
                    if (code && text) {
                        const optionText = code + ': ' + (text.length > 50 ? text.substring(0, 50) + '...' : text);
                        $(this).append(`<option value="${questionIndex}">${optionText}</option>`);
                    }
                });
                
                if (currentValue) {
                    $(this).val(currentValue);
                }
            });
        },
        
        updateQuestionCodeDisplay: function() {
            const code = $(this).val();
            $(this).closest('.dps-question-item').find('.dps-question-code').text(code);
            SurveyBuilder.updateConditionalOptions();
        },
        
        updateStepNumbers: function() {
            $('#steps-container .dps-step-item').each(function(index) {
                $(this).find('.dps-step-number').text('Step ' + (index + 1));
            });
        },
        
        saveSurvey: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $('#save-survey-btn');
            const buttonText = $button.html();
            
            // Validate
            if (!$('#survey_title').val().trim()) {
                alert('Please enter a survey title');
                return false;
            }
            
            if ($('#steps-container .dps-step-item').length === 0) {
                alert('Please add at least one step to the survey');
                return false;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
            
            // Collect all form data
            const formData = new FormData($form[0]);
            formData.append('action', 'dps_save_survey');
            formData.append('nonce', dpsAdmin.nonce);
            
            // Send AJAX request
            $.ajax({
                url: dpsAdmin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(dpsAdmin.strings.success);
                        
                        if (response.data.survey_id) {
                            // Redirect to edit page
                            window.location.href = 'admin.php?page=dynamic-survey-add&survey_id=' + response.data.survey_id;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert(response.data.message || dpsAdmin.strings.error);
                        $button.prop('disabled', false).html(buttonText);
                    }
                },
                error: function() {
                    alert(dpsAdmin.strings.error);
                    $button.prop('disabled', false).html(buttonText);
                }
            });
            
            return false;
        },
        
        loadExistingData: function() {
            // This would load existing survey data when editing
            // Implementation depends on how data is passed from PHP
            
            // Update conditional options after page load
            setTimeout(function() {
                SurveyBuilder.updateConditionalOptions();
            }, 500);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.dps-survey-builder').length) {
            SurveyBuilder.init();
        }
    });
    
    // Add spinning animation for loading state
    $('<style>')
        .text('.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }')
        .appendTo('head');
    
})(jQuery);