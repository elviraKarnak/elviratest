/**
 * Frontend Survey Form JavaScript
 * Handles multi-step navigation, conditional logic, and submission
 */

(function($) {
    'use strict';
    
    const SurveyForm = {
        currentStep: 1,
        totalSteps: 0,
        
        init: function() {
            if ($('.dps-survey-wrapper').length === 0) return;
            
            this.totalSteps = $('.dps-step').length;
            this.bindEvents();
            this.initConditionalLogic();
        },
        
        bindEvents: function() {
            // Next button
            $(document).on('click', '.dps-btn-next', this.nextStep.bind(this));
            
            // Previous button
            $(document).on('click', '.dps-btn-prev', this.prevStep.bind(this));
            
            // Form submission
            $('#dps-survey-form').on('submit', this.handleSubmit.bind(this));
            
            // Trigger conditional logic on change
            $(document).on('change', 'input[type="radio"], select', this.handleConditionalLogic.bind(this));
        },
        
        initConditionalLogic: function() {
            // Hide all conditional questions initially
            $('.dps-question[data-conditional="true"]').hide();
        },
        
        nextStep: function(e) {
            e.preventDefault();
            
            // Validate current step
            if (!this.validateStep(this.currentStep)) {
                return;
            }
            
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateProgress();
            }
        },
        
        prevStep: function(e) {
            e.preventDefault();
            
            if (this.currentStep > 1) {
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateProgress();
            }
        },
        
        showStep: function(step) {
            $('.dps-step').removeClass('active');
            $('.dps-step[data-step="' + step + '"]').addClass('active');
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.dps-survey-wrapper').offset().top - 50
            }, 300);
        },
        
        updateProgress: function() {
            const percentage = (this.currentStep / this.totalSteps) * 100;
            $('.dps-progress-fill').css('width', percentage + '%');
            $('.current-step').text(this.currentStep);
        },
        
        validateStep: function(step) {
            const $step = $('.dps-step[data-step="' + step + '"]');
            let isValid = true;
            
            // Check required fields in visible questions only
            $step.find('.dps-question:visible').each(function() {
                const $question = $(this);
                const questionType = $question.data('question-type');
                const $inputs = $question.find('input, select, textarea');
                
                // Check if any required field is empty
                $inputs.filter('[required]').each(function() {
                    const $input = $(this);
                    
                    if (questionType === 'radio' || questionType === 'checkbox') {
                        const name = $input.attr('name');
                        if (!$question.find('input[name="' + name + '"]:checked').length) {
                            isValid = false;
                            $question.addClass('error');
                            
                            if (!$question.find('.error-message').length) {
                                $question.append('<span class="error-message">This field is required</span>');
                            }
                        } else {
                            $question.removeClass('error');
                            $question.find('.error-message').remove();
                        }
                    } else {
                        if (!$input.val() || $input.val().trim() === '') {
                            isValid = false;
                            $input.addClass('error');
                            
                            if (!$question.find('.error-message').length) {
                                $question.append('<span class="error-message">This field is required</span>');
                            }
                        } else {
                            $input.removeClass('error');
                            $question.find('.error-message').remove();
                        }
                    }
                });
            });
            
            if (!isValid) {
                // Show error message
                if (!$step.find('.step-error-message').length) {
                    $step.prepend('<div class="step-error-message">Please fill in all required fields</div>');
                }
                
                // Scroll to first error
                $('html, body').animate({
                    scrollTop: $step.find('.error').first().offset().top - 100
                }, 300);
            } else {
                $step.find('.step-error-message').remove();
            }
            
            return isValid;
        },
        
        handleConditionalLogic: function(e) {
            const $input = $(e.target);
            const questionId = $input.closest('.dps-question').data('question-id');
            const selectedValue = $input.val();
            
            // Find all conditional questions that depend on this question
            $('.dps-question[data-parent-question="' + questionId + '"]').each(function() {
                const $conditionalQuestion = $(this);
                const triggerValue = $conditionalQuestion.data('trigger-value');
                
                if (selectedValue === triggerValue) {
                    $conditionalQuestion.slideDown(300);
                } else {
                    $conditionalQuestion.slideUp(300);
                    // Clear values in hidden questions
                    $conditionalQuestion.find('input, select, textarea').val('');
                    $conditionalQuestion.find('input[type="radio"], input[type="checkbox"]').prop('checked', false);
                }
            });
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            
            // Validate last step
            if (!this.validateStep(this.currentStep)) {
                return;
            }
            
            const $form = $(e.target);
            const formData = new FormData($form[0]);
            
            // Add action and nonce
            formData.append('action', 'dps_submit_survey');
            
            // Show loading
            $('.dps-survey-form').hide();
            $('.dps-loading').show();
            
            // Submit via AJAX
            $.ajax({
                url: dpsSurvey.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.dps-loading').html(
                            '<div class="dps-success">' +
                            '<div class="success-icon">✓</div>' +
                            '<h3>Thank You!</h3>' +
                            '<p>' + (response.data.message || 'Your response has been recorded successfully.') + '</p>' +
                            '</div>'
                        );
                        
                        // Scroll to top
                        $('html, body').animate({
                            scrollTop: $('.dps-survey-wrapper').offset().top - 50
                        }, 300);
                    } else {
                        // Show error
                        $('.dps-loading').html(
                            '<div class="dps-error">' +
                            '<h3>Error</h3>' +
                            '<p>' + (response.data.message || 'An error occurred. Please try again.') + '</p>' +
                            '<button class="dps-btn" onclick="location.reload()">Try Again</button>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $('.dps-loading').html(
                        '<div class="dps-error">' +
                        '<h3>Connection Error</h3>' +
                        '<p>Unable to submit your response. Please check your internet connection and try again.</p>' +
                        '<button class="dps-btn" onclick="location.reload()">Try Again</button>' +
                        '</div>'
                    );
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        SurveyForm.init();
    });
    
})(jQuery);

// Add styles for error messages
jQuery(document).ready(function($) {
    $('<style>')
        .text(`
            .error-message {
                display: block;
                color: #e74c3c;
                font-size: 14px;
                margin-top: 5px;
            }
            
            .step-error-message {
                padding: 12px 16px;
                background: #fee;
                color: #c33;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 600;
            }
            
            input.error,
            select.error,
            textarea.error {
                border-color: #e74c3c !important;
            }
            
            .dps-question.error {
                animation: shake 0.5s;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            
            .dps-success {
                text-align: center;
                padding: 40px 20px;
            }
            
            .success-icon {
                width: 80px;
                height: 80px;
                background: #27ae60;
                color: #fff;
                font-size: 50px;
                line-height: 80px;
                border-radius: 50%;
                margin: 0 auto 20px;
                animation: scaleIn 0.5s ease;
            }
            
            @keyframes scaleIn {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            .dps-success h3 {
                font-size: 32px;
                color: #27ae60;
                margin-bottom: 15px;
            }
            
            .dps-success p {
                font-size: 18px;
                color: #7f8c8d;
            }
        `)
        .appendTo('head');
});