// Frontend Assessment Form JavaScript

jQuery(document).ready(function($) {
    let currentPage = 1;
    let totalPages = 1;
    let allAnswers = {};
    let questionsData = [];
    
    // Load first page of questions
    loadQuestions(1);
    
    function loadQuestions(page) {
        // Show loading
        $('#cas-questions-wrapper').html('<div class="cas-loading"><div class="cas-loading-spinner"></div><p>Loading questions...</p></div>');
        
        $.ajax({
            url: casFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_get_test_questions',
                page: page
            },
            success: function(response) {
                console.log('Questions loaded:', response);
                
                if (response.success) {
                    if (response.data.questions.length === 0) {
                        $('#cas-questions-wrapper').html('<div class="cas-empty-state"><p>No questions available. Please contact the administrator.</p></div>');
                        $('#cas-next-btn').hide();
                        return;
                    }
                    
                    questionsData = response.data.questions;
                    totalPages = response.data.total_pages;
                    currentPage = page;
                    
                    renderQuestions(questionsData);
                    updateProgress();
                    updateNavigation();
                } else {
                    $('#cas-questions-wrapper').html('<div class="cas-empty-state"><p>Error loading questions. Please refresh the page.</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#cas-questions-wrapper').html('<div class="cas-empty-state"><p>Error loading questions. Please refresh the page.</p></div>');
            }
        });
    }
    
    function renderQuestions(questions) {
        let html = '';
        
        questions.forEach(function(q, index) {
            let globalIndex = ((currentPage - 1) * questions.length) + index + 1;
            let savedAnswer = allAnswers[q.id] || '';
            
            html += `
                <div class="cas-question-item" style="animation-delay: ${index * 0.1}s">
                    <div class="cas-question-header">
                        <div class="cas-question-number">${globalIndex}</div>
                        <div class="cas-question-text">${q.question}</div>
                    </div>
                    <div class="cas-answer-options">
                        <div class="cas-answer-option">
                            <input type="radio" name="question_${q.id}" value="strongly_agree" 
                                   id="q${q.id}_sa" ${savedAnswer === 'strongly_agree' ? 'checked' : ''}>
                            <label for="q${q.id}_sa" class="cas-answer-label">
                                <span class="cas-answer-icon">😄</span>
                                <span class="cas-answer-text">Strongly Agree</span>
                            </label>
                        </div>
                        <div class="cas-answer-option">
                            <input type="radio" name="question_${q.id}" value="agree" 
                                   id="q${q.id}_a" ${savedAnswer === 'agree' ? 'checked' : ''}>
                            <label for="q${q.id}_a" class="cas-answer-label">
                                <span class="cas-answer-icon">🙂</span>
                                <span class="cas-answer-text">Agree</span>
                            </label>
                        </div>
                        <div class="cas-answer-option">
                            <input type="radio" name="question_${q.id}" value="neutral" 
                                   id="q${q.id}_n" ${savedAnswer === 'neutral' ? 'checked' : ''}>
                            <label for="q${q.id}_n" class="cas-answer-label">
                                <span class="cas-answer-icon">😐</span>
                                <span class="cas-answer-text">Neutral</span>
                            </label>
                        </div>
                        <div class="cas-answer-option">
                            <input type="radio" name="question_${q.id}" value="disagree" 
                                   id="q${q.id}_d" ${savedAnswer === 'disagree' ? 'checked' : ''}>
                            <label for="q${q.id}_d" class="cas-answer-label">
                                <span class="cas-answer-icon">🙁</span>
                                <span class="cas-answer-text">Disagree</span>
                            </label>
                        </div>
                        <div class="cas-answer-option">
                            <input type="radio" name="question_${q.id}" value="strongly_disagree" 
                                   id="q${q.id}_sd" ${savedAnswer === 'strongly_disagree' ? 'checked' : ''}>
                            <label for="q${q.id}_sd" class="cas-answer-label">
                                <span class="cas-answer-icon">😞</span>
                                <span class="cas-answer-text">Strongly Disagree</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#cas-questions-wrapper').html(html);
    }
    
    function updateProgress() {
        let progress = (currentPage / (totalPages + 1)) * 100;
        $('#cas-progress-fill').css('width', progress + '%');
        $('#cas-current-step').text(currentPage);
        $('#cas-total-steps').text(totalPages + 1);
    }
    
    function updateNavigation() {
        if (currentPage === 1) {
            $('#cas-prev-btn').hide();
        } else {
            $('#cas-prev-btn').show();
        }
        
        if (currentPage === totalPages) {
            $('#cas-next-btn').hide();
            $('#cas-submit-btn').hide();
        } else {
            $('#cas-next-btn').show();
            $('#cas-submit-btn').hide();
        }
    }
    
    function saveCurrentAnswers() {
        questionsData.forEach(function(q) {
            let answer = $('input[name="question_' + q.id + '"]:checked').val();
            if (answer) {
                allAnswers[q.id] = answer;
            }
        });
    }
    
    function validateCurrentPage() {
        let allAnswered = true;
        
        questionsData.forEach(function(q) {
            if (!$('input[name="question_' + q.id + '"]:checked').length) {
                allAnswered = false;
            }
        });
        
        if (!allAnswered) {
            alert('Please answer all questions before proceeding.');
            return false;
        }
        
        return true;
    }
    
    // Next button
    $('#cas-next-btn').on('click', function() {
        if (!validateCurrentPage()) return;
        
        saveCurrentAnswers();
        
        if (currentPage < totalPages) {
            loadQuestions(currentPage + 1);
        } else {
            // Show personal info form
            $('#cas-questions-wrapper').hide();
            $('#cas-personal-info').fadeIn();
            $('#cas-next-btn').hide();
            $('#cas-submit-btn').show();
            currentPage++;
            updateProgress();
        }
    });
    
    // Previous button
    $('#cas-prev-btn').on('click', function() {
        saveCurrentAnswers();
        
        if (currentPage === totalPages + 1) {
            // Going back from personal info to questions
            $('#cas-personal-info').hide();
            $('#cas-questions-wrapper').fadeIn();
            $('#cas-submit-btn').hide();
            $('#cas-next-btn').show();
            currentPage--;
            updateProgress();
        } else if (currentPage > 1) {
            loadQuestions(currentPage - 1);
        }
    });
    
    // Submit button
    $('#cas-submit-btn').on('click', function() {
        let name = $('#candidate-name').val().trim();
        let email = $('#candidate-email').val().trim();
        let phone = $('#candidate-phone').val().trim();
        let interviewDate = $('#interview-datetime').val();
        
        if (!name || !email || !phone || !interviewDate) {
            alert('Please fill in all personal information fields.');
            return;
        }
        
        // Disable button
        $(this).prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: casFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_submit_test',
                name: name,
                email: email,
                phone: phone,
                interview_date: interviewDate,
                answers: allAnswers
            },
            success: function(response) {
                if (response.success) {
                    $('#cas-assessment-form').fadeOut(function() {
                        $('#cas-success-message').fadeIn();
                    });
                } else {
                    alert('Error: ' + response.data);
                    $('#cas-submit-btn').prop('disabled', false).text('Submit Assessment');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#cas-submit-btn').prop('disabled', false).text('Submit Assessment');
            }
        });
    });
});