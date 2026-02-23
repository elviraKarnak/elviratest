<?php
/**
 * Questions Management Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="cas-admin-wrap">
    <div class="cas-header">
        <h1>❓ Questions Management</h1>
        <button class="cas-btn cas-btn-primary" id="cas-add-question-btn">+ Add New Question</button>
    </div>
    
    <div class="cas-questions-container">
        <div class="cas-questions-list" id="cas-questions-list">
            <!-- Questions will be loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Add/Edit Question Modal -->
<div id="cas-question-modal" class="cas-modal">
    <div class="cas-modal-content">
        <div class="cas-modal-header">
            <h2 id="cas-modal-title">Add New Question</h2>
            <span class="cas-modal-close">&times;</span>
        </div>
        <div class="cas-modal-body">
            <form id="cas-question-form">
                <input type="hidden" id="question-id" value="">
                
                <div class="cas-form-group">
                    <label for="question-text">Question Text *</label>
                    <textarea id="question-text" rows="3" class="cas-textarea" required 
                              placeholder="Enter your question here..."></textarea>
                </div>
                
                <div class="cas-form-group">
                    <label>Question Category *</label>
                    <div class="cas-category-selector">
                        <label class="cas-category-option">
                            <input type="radio" name="question-category" value="positive" required>
                            <span class="category-badge positive">
                                <span class="badge-icon">✓</span>
                                <span class="badge-text">Positive</span>
                            </span>
                            <small>Agreement indicates good traits</small>
                        </label>
                        
                        <label class="cas-category-option">
                            <input type="radio" name="question-category" value="negative" required>
                            <span class="category-badge negative">
                                <span class="badge-icon">✗</span>
                                <span class="badge-text">Negative</span>
                            </span>
                            <small>Agreement indicates concerning traits</small>
                        </label>
                        
                        <label class="cas-category-option">
                            <input type="radio" name="question-category" value="moderate" required>
                            <span class="category-badge moderate">
                                <span class="badge-icon">◐</span>
                                <span class="badge-text">Moderate</span>
                            </span>
                            <small>Balanced/neutral question</small>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="cas-modal-footer">
            <button type="button" class="cas-btn cas-btn-secondary cas-modal-close">Cancel</button>
            <button type="button" class="cas-btn cas-btn-primary" id="cas-save-question-btn">Save Question</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let editingQuestionId = null;
    
    // Load questions
    function loadQuestions() {
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_get_questions',
                nonce: casAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderQuestions(response.data);
                }
            }
        });
    }
    
    // Render questions
    function renderQuestions(questions) {
        let html = '';
        
        if (questions.length === 0) {
            html = '<div class="cas-empty-state"><p>No questions yet. Click "Add New Question" to get started!</p></div>';
        } else {
            questions.forEach(function(q, index) {
                let categoryClass = 'category-' + q.category;
                let categoryIcon = q.category === 'positive' ? '✓' : (q.category === 'negative' ? '✗' : '◐');
                
                html += `
                    <div class="cas-question-card" data-id="${q.id}">
                        <div class="question-number">${index + 1}</div>
                        <div class="question-content">
                            <div class="question-text">${q.question}</div>
                            <span class="question-category ${categoryClass}">
                                ${categoryIcon} ${q.category.charAt(0).toUpperCase() + q.category.slice(1)}
                            </span>
                        </div>
                        <div class="question-actions">
                            <button class="cas-icon-btn cas-edit-question" data-id="${q.id}">
                                <span>✏️</span>
                            </button>
                            <button class="cas-icon-btn cas-delete-question" data-id="${q.id}">
                                <span>🗑️</span>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#cas-questions-list').html(html);
    }
    
    // Open modal for new question
    $('#cas-add-question-btn').on('click', function() {
        editingQuestionId = null;
        $('#question-id').val('');
        $('#question-text').val('');
        $('input[name="question-category"]').prop('checked', false);
        $('#cas-modal-title').text('Add New Question');
        $('#cas-question-modal').fadeIn();
    });
    
    // Edit question
    $(document).on('click', '.cas-edit-question', function() {
        let questionId = $(this).data('id');
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_get_questions',
                nonce: casAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    let question = response.data.find(q => q.id == questionId);
                    if (question) {
                        editingQuestionId = questionId;
                        $('#question-id').val(questionId);
                        $('#question-text').val(question.question);
                        $('input[name="question-category"][value="' + question.category + '"]').prop('checked', true);
                        $('#cas-modal-title').text('Edit Question');
                        $('#cas-question-modal').fadeIn();
                    }
                }
            }
        });
    });
    
    // Save question
    $('#cas-save-question-btn').on('click', function() {
        let questionText = $('#question-text').val().trim();
        let category = $('input[name="question-category"]:checked').val();
        
        if (!questionText || !category) {
            alert('Please fill in all fields');
            return;
        }
        
        let data = {
            nonce: casAdmin.nonce,
            question: questionText,
            category: category
        };
        
        if (editingQuestionId) {
            data.action = 'cas_update_question';
            data.id = editingQuestionId;
        } else {
            data.action = 'cas_add_question';
        }
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#cas-question-modal').fadeOut();
                    loadQuestions();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Delete question
    $(document).on('click', '.cas-delete-question', function() {
        if (!confirm('Are you sure you want to delete this question?')) return;
        
        let questionId = $(this).data('id');
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_delete_question',
                nonce: casAdmin.nonce,
                id: questionId
            },
            success: function(response) {
                if (response.success) {
                    loadQuestions();
                }
            }
        });
    });
    
    // Close modal
    $('.cas-modal-close').on('click', function() {
        $('#cas-question-modal').fadeOut();
    });
    
    // Initial load
    loadQuestions();
});
</script>
