/**
 * Admin JavaScript for Psychometric Test Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functions
        initQuestionValidation();
        initDeleteConfirmation();
        initEmailValidation();
        initQuestionToggle();
        
    });
    
    /**
     * Initialize question toggle via AJAX
     */
    function initQuestionToggle() {
        // This is now handled inline in the edit page
        // But we can add additional functionality here if needed
    }
    
    /**
     * Validate question form before submission
     */
    function initQuestionValidation() {
        $('.psychometric-form').on('submit', function(e) {
            let isValid = true;
            let emptyQuestions = 0;
            
            // Check each question
            $('#questions-repeater .question-item').each(function() {
                const questionText = $(this).find('textarea[name*="[text]"]').val().trim();
                
                if (questionText === '') {
                    emptyQuestions++;
                }
            });
            
            // Ensure all 6 questions are filled
            if (emptyQuestions > 0) {
                e.preventDefault();
                alert('Please fill in all 6 questions before saving.');
                
                // Highlight empty questions
                $('#questions-repeater .question-item').each(function() {
                    const textarea = $(this).find('textarea[name*="[text]"]');
                    if (textarea.val().trim() === '') {
                        textarea.css('border-color', '#ef4444');
                        textarea.on('input', function() {
                            if ($(this).val().trim() !== '') {
                                $(this).css('border-color', '#e2e8f0');
                            }
                        });
                    }
                });
                
                isValid = false;
            }
            
            // Validate step number is unique (for new steps)
            const stepId = $('input[name="step_id"]').val();
            if (!stepId) {
                const stepNumber = $('input[name="step_number"]').val();
                
                // This would need an AJAX call to check uniqueness
                // For now, just ensure it's filled
                if (!stepNumber || stepNumber <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid step number.');
                    $('input[name="step_number"]').focus();
                    isValid = false;
                }
            }
            
            return isValid;
        });
    }
    
    /**
     * Confirm before deleting steps
     */
    function initDeleteConfirmation() {
        $('.button-link-delete').on('click', function(e) {
            const confirmed = confirm('Are you sure you want to delete this step? This action cannot be undone and will delete all questions in this step.');
            
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Validate email addresses in settings
     */
    function initEmailValidation() {
        $('form').on('submit', function(e) {
            const emailField = $('#admin_emails');
            
            if (emailField.length) {
                const emails = emailField.val().split('\n');
                let invalidEmails = [];
                
                emails.forEach(function(email) {
                    email = email.trim();
                    if (email !== '' && !isValidEmail(email)) {
                        invalidEmails.push(email);
                    }
                });
                
                if (invalidEmails.length > 0) {
                    e.preventDefault();
                    alert('The following email addresses are invalid:\n' + invalidEmails.join('\n'));
                    emailField.focus();
                    return false;
                }
            }
        });
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Auto-save indication
     */
    function showSaveIndicator() {
        const indicator = $('<div class="save-indicator">Saving...</div>');
        indicator.css({
            'position': 'fixed',
            'top': '32px',
            'right': '20px',
            'background': '#21BECA',
            'color': 'white',
            'padding': '10px 20px',
            'border-radius': '6px',
            'z-index': '9999',
            'box-shadow': '0 2px 8px rgba(0,0,0,0.2)',
            'animation': 'fadeIn 0.3s'
        });
        
        $('body').append(indicator);
        
        setTimeout(function() {
            indicator.fadeOut(300, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    /**
     * Character counter for textareas
     */
    function initCharacterCounter() {
        $('textarea[name*="[text]"]').each(function() {
            const maxLength = 500;
            const $textarea = $(this);
            const $counter = $('<div class="char-counter"></div>');
            
            $counter.css({
                'text-align': 'right',
                'font-size': '12px',
                'color': '#64748b',
                'margin-top': '5px'
            });
            
            $textarea.after($counter);
            
            function updateCounter() {
                const length = $textarea.val().length;
                $counter.text(length + ' / ' + maxLength + ' characters');
                
                if (length > maxLength) {
                    $counter.css('color', '#ef4444');
                } else {
                    $counter.css('color', '#64748b');
                }
            }
            
            updateCounter();
            $textarea.on('input', updateCounter);
        });
    }
    
    // Initialize character counter if textareas exist
    if ($('textarea[name*="[text]"]').length > 0) {
        initCharacterCounter();
    }
    
    /**
     * Smooth scroll to validation errors
     */
    function scrollToError() {
        const firstError = $('.question-item textarea[style*="border-color: rgb(239, 68, 68)"]').first();
        
        if (firstError.length) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
        }
    }
    
    /**
     * Copy shortcode to clipboard
     */
    $('body').on('click', 'code', function() {
        const $code = $(this);
        const text = $code.text();
        
        // Create temporary input
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            document.execCommand('copy');
            
            // Show feedback
            const originalText = $code.text();
            $code.text('Copied!');
            $code.css('background', '#22c55e');
            
            setTimeout(function() {
                $code.text(originalText);
                $code.css('background', '');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        
        $temp.remove();
    });
    
    /**
     * Enhanced table row highlighting
     */
    $('.psychometric-table tbody tr').hover(
        function() {
            $(this).css('background-color', '#f1f5f9');
        },
        function() {
            if ($(this).index() % 2 === 0) {
                $(this).css('background-color', '');
            } else {
                $(this).css('background-color', '#f9fafb');
            }
        }
    );
    
    /**
     * Auto-fill interview date with today's date
     */
    if ($('#interview_date').length && !$('#interview_date').val()) {
        const today = new Date().toISOString().split('T')[0];
        $('#interview_date').attr('min', today);
    }
    
    /**
     * Prevent accidental page leave with unsaved changes
     */
    let formChanged = false;
    
    $('.psychometric-form input, .psychometric-form textarea, .psychometric-form select').on('change', function() {
        formChanged = true;
    });
    
    $('.psychometric-form').on('submit', function() {
        formChanged = false;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

})(jQuery);