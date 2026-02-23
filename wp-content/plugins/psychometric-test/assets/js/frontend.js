/**
 * Frontend JavaScript for Psychometric Test Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        if ($('.psychometric-test-container').length === 0) {
            return;
        }
        
        // Initialize test
        const primaryColor = psychometricFrontend.primaryColor || '#21BECA';
        
        // Check if it's the test page
        if ($('.psychometric-test-container').length > 0) {
            initializeTest(primaryColor);
        }
        
        // Check if it's the submissions page
        if ($('.psychometric-submissions-frontend').length > 0) {
            initializeSubmissionsPage();
        }
        
    });
    
    function initializeTest(primaryColor) {
        // Set CSS custom property for primary color
        document.documentElement.style.setProperty('--primary-color', primaryColor);
        
        let currentStep = 1;
        const $steps = $('.psychometric-step').not('.psychometric-final-step, .psychometric-success');
        const totalSteps = $steps.length;
        let answers = {};
        
        // Update progress bar
        function updateProgress() {
            const progress = ((currentStep - 1) / totalSteps) * 100;
            $('.progress-fill').css('width', progress + '%');
            $('.current-step').text(currentStep);
        }
        
        // Check if all questions in current step are answered
        function checkStepComplete() {
            const $currentStepEl = $steps.eq(currentStep - 1);
            const $questions = $currentStepEl.find('.question-block');
            let allAnswered = true;
            
            $questions.each(function() {
                const questionId = $(this).data('question-id');
                if (!answers[questionId]) {
                    allAnswered = false;
                    return false;
                }
            });
            
            return allAnswered;
        }
        
        // Enable/disable next button
        function updateNextButton() {
            const $currentStepEl = $steps.eq(currentStep - 1);
            const $nextBtn = $currentStepEl.find('.btn-next, .btn-show-form');
            
            if (checkStepComplete()) {
                $nextBtn.prop('disabled', false);
            } else {
                $nextBtn.prop('disabled', true);
            }
        }
        
        // Handle answer selection
        $('.circle-input').on('change', function() {
            const $questionBlock = $(this).closest('.question-block');
            const questionId = $questionBlock.data('question-id');
            const answerValue = parseInt($(this).val());
            
            // Save answer
            answers[questionId] = answerValue;
            
            // Mark question as answered
            $questionBlock.addClass('answered');
            
            // Update next button
            updateNextButton();
        });
        
        // Next button click
        $('.btn-next').on('click', function() {
            if (currentStep < totalSteps) {
                // Hide current step
                $steps.eq(currentStep - 1).fadeOut(300, function() {
                    // Show next step
                    currentStep++;
                    $steps.eq(currentStep - 1).fadeIn(300);
                    updateProgress();
                    updateNextButton();
                    
                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: $('.psychometric-test-container').offset().top - 50
                    }, 300);
                });
            }
        });
        
        // Previous button click
        $('.btn-prev').on('click', function() {
            const $currentStepEl = $(this).closest('.psychometric-step');
            
            if ($currentStepEl.hasClass('psychometric-final-step')) {
                // Going back from final form to last question step
                $currentStepEl.fadeOut(300, function() {
                    $steps.eq(totalSteps - 1).fadeIn(300);
                    updateProgress();
                });
            } else if (currentStep > 1) {
                // Going back between question steps
                $steps.eq(currentStep - 1).fadeOut(300, function() {
                    currentStep--;
                    $steps.eq(currentStep - 1).fadeIn(300);
                    updateProgress();
                    updateNextButton();
                    
                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: $('.psychometric-test-container').offset().top - 50
                    }, 300);
                });
            }
        });
        
        // Show final form button
        $('.btn-show-form').on('click', function() {
            if (checkStepComplete()) {
                $steps.eq(currentStep - 1).fadeOut(300, function() {
                    $('.psychometric-final-step').fadeIn(300);
                    
                    // Update progress to 100%
                    $('.progress-fill').css('width', '100%');
                    $('.psychometric-progress-text').html('Final Step - <strong>Your Information</strong>');
                    
                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: $('.psychometric-test-container').offset().top - 50
                    }, 300);
                });
            }
        });
        
        // Form submission
        $('#psychometric-final-form').on('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = {
                name: $('#candidate_name').val().trim(),
                email: $('#candidate_email').val().trim(),
                phone: $('#candidate_phone').val().trim(),
                interview_date: $('#interview_date').val(),
                answers: answers,
                action: 'psychometric_submit',
                nonce: psychometricFrontend.nonce
            };
            
            // Validate
            if (!formData.name || !formData.email || !formData.phone || !formData.interview_date) {
                alert('Please fill in all required fields.');
                return;
            }
            
            if (!isValidEmail(formData.email)) {
                alert('Please enter a valid email address.');
                $('#candidate_email').focus();
                return;
            }
            
            if (Object.keys(answers).length === 0) {
                alert('Please answer all questions before submitting.');
                return;
            }
            
            // Show loading
            $('.psychometric-loading').fadeIn(200);
            
            // Submit via AJAX
            $.ajax({
                url: psychometricFrontend.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('.psychometric-loading').fadeOut(200, function() {
                        if (response.success) {
                            // Show success message
                            $('.psychometric-final-step').fadeOut(300, function() {
                                $('.psychometric-success').fadeIn(300);
                                
                                // Scroll to top
                                $('html, body').animate({
                                    scrollTop: $('.psychometric-test-container').offset().top - 50
                                }, 300);
                            });
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to submit assessment. Please try again.'));
                        }
                    });
                },
                error: function(xhr, status, error) {
                    $('.psychometric-loading').fadeOut(200);
                    console.error('Submission error:', error);
                    alert('An error occurred while submitting your assessment. Please try again.');
                }
            });
        });
        
        // Email validation
        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
        
        // Phone number formatting (optional)
        $('#candidate_phone').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            $(this).val(value);
        });
        
        // Date validation - prevent past dates
        const today = new Date().toISOString().split('T')[0];
        $('#interview_date').attr('min', today);
        
        // Initialize progress
        updateProgress();
        updateNextButton();
        
        // Keyboard navigation
        $(document).on('keydown', function(e) {
            // Enter key on radio buttons
            if (e.key === 'Enter' && $(e.target).hasClass('circle-input')) {
                e.preventDefault();
                $(e.target).trigger('change');
            }
            
            // Arrow keys for navigation between circles
            if ($(e.target).hasClass('circle-input')) {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const $prev = $(e.target).closest('.circle-wrapper').prev().find('.circle-input');
                    if ($prev.length) {
                        $prev.focus().click();
                    }
                } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const $next = $(e.target).closest('.circle-wrapper').next().find('.circle-input');
                    if ($next.length) {
                        $next.focus().click();
                    }
                }
            }
        });
        
        // Prevent multiple submissions
        let isSubmitting = false;
        $('#psychometric-final-form').on('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
        
        // Auto-save to localStorage (optional - for draft recovery)
        function saveProgress() {
            const progressData = {
                currentStep: currentStep,
                answers: answers,
                timestamp: Date.now()
            };
            
            try {
                localStorage.setItem('psychometric_progress', JSON.stringify(progressData));
            } catch (e) {
                console.warn('Could not save progress:', e);
            }
        }
        
        function loadProgress() {
            try {
                const saved = localStorage.getItem('psychometric_progress');
                if (saved) {
                    const data = JSON.parse(saved);
                    
                    // Check if saved data is less than 24 hours old
                    if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
                        const restore = confirm('We found a previous session. Would you like to continue where you left off?');
                        
                        if (restore) {
                            answers = data.answers || {};
                            
                            // Restore checked answers
                            Object.keys(answers).forEach(function(questionId) {
                                const value = answers[questionId];
                                $('input[name="question_' + questionId + '"][value="' + value + '"]')
                                    .prop('checked', true)
                                    .closest('.question-block')
                                    .addClass('answered');
                            });
                            
                            updateNextButton();
                        } else {
                            localStorage.removeItem('psychometric_progress');
                        }
                    } else {
                        localStorage.removeItem('psychometric_progress');
                    }
                }
            } catch (e) {
                console.warn('Could not load progress:', e);
            }
        }
        
        // Load progress on init
        loadProgress();
        
        // Save progress periodically
        setInterval(saveProgress, 30000); // Every 30 seconds
        
        // Clear progress on successful submission
        $(document).on('psychometric_submitted', function() {
            try {
                localStorage.removeItem('psychometric_progress');
            } catch (e) {
                console.warn('Could not clear progress:', e);
            }
        });
        
        // Accessibility improvements
        $('.circle-label').attr('tabindex', '0');
        
        $('.circle-label').on('keypress', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).prev('.circle-input').click();
            }
        });
        
        // Focus management
        $('.btn-next, .btn-show-form').on('click', function() {
            setTimeout(function() {
                $('.psychometric-step:visible').find('.question-block:first .circle-input:first').focus();
            }, 400);
        });
    }

    function initializeSubmissionsPage() {
        console.log('Initializing submissions page...');
        
        // View Details Button Click
        $(document).on('click', '.btn-view-details', function(e) {
            e.preventDefault();
            console.log('View details button clicked');
            const submissionId = $(this).data('submission-id');
            console.log('Submission ID:', submissionId);
            loadSubmissionDetails(submissionId);
        });
        
        // Close Modal
        $(document).on('click', '.modal-close, .modal-overlay', function() {
            $('#submission-modal').fadeOut(300);
        });
        
        // Prevent modal content click from closing
        $(document).on('click', '.modal-content', function(e) {
            e.stopPropagation();
        });
        
        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#submission-modal').fadeOut(300);
            }
        });
        
        console.log('Submissions page initialized');
    }
    
    function loadSubmissionDetails(submissionId) {
        console.log('Loading submission details for ID:', submissionId);
        
        const $modal = $('#submission-modal');
        
        if ($modal.length === 0) {
            console.error('Modal not found in DOM!');
            alert('Modal element not found. Please refresh the page.');
            return;
        }
        
        const $loading = $modal.find('.modal-loading');
        const $details = $modal.find('.modal-details');
        
        console.log('Modal elements found:', {
            modal: $modal.length,
            loading: $loading.length,
            details: $details.length
        });
        
        // Show modal with loading
        $modal.fadeIn(300);
        $loading.show();
        $details.hide().html('');
        
        console.log('Sending AJAX request...');
        
        // Fetch details via AJAX
        $.ajax({
            url: psychometricFrontend.ajaxurl,
            type: 'POST',
            data: {
                action: 'psychometric_get_submission',
                submission_id: submissionId,
                nonce: psychometricFrontend.nonce
            },
            beforeSend: function() {
                console.log('AJAX request starting...');
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                
                if (response.success) {
                    const data = response.data;
                    const submission = data.submission;
                    const riskDesc = data.risk_description;
                    
                    console.log('Submission data:', submission);
                    console.log('Answers count:', submission.answers ? submission.answers.length : 0);
                    
                    // Build HTML
                    let html = '<h3>' + escapeHtml(submission.candidate_name) + '</h3>';
                    
                    html += '<div class="detail-section">';
                    html += '<div class="detail-row">';
                    html += '<span class="detail-label">Email:</span>';
                    html += '<span class="detail-value">' + escapeHtml(submission.candidate_email) + '</span>';
                    html += '</div>';
                    html += '<div class="detail-row">';
                    html += '<span class="detail-label">Phone:</span>';
                    html += '<span class="detail-value">' + escapeHtml(submission.candidate_phone) + '</span>';
                    html += '</div>';
                    html += '<div class="detail-row">';
                    html += '<span class="detail-label">Interview Date:</span>';
                    html += '<span class="detail-value">' + formatDate(submission.interview_date) + '</span>';
                    html += '</div>';
                    html += '<div class="detail-row">';
                    html += '<span class="detail-label">Submitted:</span>';
                    html += '<span class="detail-value">' + formatDateTime(submission.submitted_at) + '</span>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Score Display
                    const riskClass = submission.risk_level.toLowerCase().replace(/ /g, '-');
                    html += '<div class="score-display">';
                    html += '<div class="score-circle-modal ' + riskClass + '">';
                    html += parseFloat(submission.total_score).toFixed(1) + '%';
                    html += '<div class="score-label">Risk Score</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Risk Description
                    if (riskDesc) {
                        html += '<div class="risk-description-modal ' + riskClass + '">';
                        html += '<h4><span class="risk-icon">' + riskDesc.icon + '</span>' + riskDesc.title + '</h4>';
                        html += '<ul>';
                        riskDesc.points.forEach(function(point) {
                            html += '<li>' + escapeHtml(point) + '</li>';
                        });
                        html += '</ul>';
                        html += '</div>';
                    }
                    
                    // Answers Section
                    if (submission.answers && submission.answers.length > 0) {
                        html += '<h4 style="margin-top: 30px; margin-bottom: 15px; color: #1e293b; font-size: 18px;">Detailed Responses</h4>';
                        html += '<div class="answers-list">';
                        
                        submission.answers.forEach(function(answer, index) {
                            const answerPill = getAnswerPill(answer.answer_value, answer.answer_label);
                            const polarityBadge = '<span class="polarity-badge polarity-' + answer.polarity + '">' + capitalizeFirst(answer.polarity) + '</span>';
                            
                            html += '<div class="answer-item">';
                            html += '<div class="answer-question">';
                            html += '<span class="question-number">' + (index + 1) + '.</span> ';
                            html += escapeHtml(answer.question_text);
                            html += '</div>';
                            html += '<div class="answer-details">';
                            html += '<div class="answer-response">';
                            html += '<span style="font-size: 13px; color: #64748b; margin-right: 8px;">Response:</span>';
                            html += answerPill;
                            html += '</div>';
                            html += '<div class="answer-meta">';
                            html += polarityBadge;
                            html += '<span class="answer-score">Score: <strong>' + parseFloat(answer.normalized_score).toFixed(2) + '</strong></span>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                        
                        html += '</div>';
                    }
                    
                    console.log('HTML built, displaying...');
                    
                    // Show details
                    $loading.fadeOut(200, function() {
                        $details.html(html).fadeIn(300);
                    });
                } else {
                    console.error('Response not successful:', response);
                    $loading.fadeOut(200, function() {
                        $details.html('<p style="text-align:center;color:#ef4444;">Failed to load details: ' + (response.data && response.data.message ? response.data.message : 'Unknown error') + '</p>').fadeIn(300);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response Text:', xhr.responseText);
                $loading.fadeOut(200, function() {
                    $details.html('<p style="text-align:center;color:#ef4444;">An error occurred while loading details. Check console for details.</p>').fadeIn(300);
                });
            }
        });
    }
    
    function getAnswerPill(value, label) {
        const pillClasses = {
            1: 'answer-pill-1',
            2: 'answer-pill-2',
            3: 'answer-pill-3',
            4: 'answer-pill-4',
            5: 'answer-pill-5',
            6: 'answer-pill-6',
            7: 'answer-pill-7'
        };
        
        const pillClass = pillClasses[value] || 'answer-pill-4';
        return '<span class="answer-pill ' + pillClass + '">' + escapeHtml(label) + '</span>';
    }
    
    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    function formatDateTime(dateString) {
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        };
        return date.toLocaleDateString('en-US', options);
    }

})(jQuery);