<?php
/**
 * Submissions Management Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="cas-admin-wrap">
    <div class="cas-header">
        <h1>📋 Candidate Submissions</h1>
        <div class="cas-header-actions">
            <input type="text" id="cas-search" placeholder="Search by name or email..." class="cas-search-input">
            <select id="cas-filter" class="cas-select">
                <option value="all">All Ratings</option>
                <option value="safe">✅ Safe</option>
                <option value="acceptable">⚖️ Acceptable</option>
                <option value="risk">⚠️ Risk</option>
                <option value="harmful">❌ Harmful</option>
            </select>
        </div>
    </div>
    
    <div class="cas-submissions-grid" id="cas-submissions-grid">
        <!-- Submissions will be loaded via AJAX -->
    </div>
</div>

<!-- Submission Detail Modal -->
<div id="cas-submission-modal" class="cas-modal">
    <div class="cas-modal-content cas-modal-large">
        <div class="cas-modal-header">
            <h2>Candidate Assessment Details</h2>
            <span class="cas-modal-close">&times;</span>
        </div>
        <div class="cas-modal-body" id="cas-submission-detail">
            <!-- Detail will be loaded via AJAX -->
        </div>
        <div class="cas-modal-footer">
            <button type="button" class="cas-btn cas-btn-secondary cas-modal-close">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load submissions
    function loadSubmissions() {
        let filter = $('#cas-filter').val();
        let search = $('#cas-search').val();
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_get_submissions',
                nonce: casAdmin.nonce,
                filter: filter,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderSubmissions(response.data);
                }
            }
        });
    }
    
    // Render submissions
    function renderSubmissions(submissions) {
        let html = '';
        
        if (submissions.length === 0) {
            html = '<div class="cas-empty-state"><p>No submissions found.</p></div>';
        } else {
            submissions.forEach(function(sub) {
                let ratingClass = 'rating-' + sub.rating;
                let ratingIcon = sub.rating === 'safe' ? '✅' : 
                                (sub.rating === 'acceptable' ? '⚖️' : 
                                (sub.rating === 'risk' ? '⚠️' : '❌'));
                let ratingLabel = sub.rating.charAt(0).toUpperCase() + sub.rating.slice(1);
                
                html += `
                    <div class="cas-submission-card ${ratingClass}">
                        <div class="submission-header">
                            <div class="candidate-info">
                                <h3>${sub.name}</h3>
                                <div class="contact-info">
                                    <span>📧 ${sub.email}</span>
                                    <span>📱 ${sub.phone}</span>
                                </div>
                            </div>
                            <div class="rating-badge ${ratingClass}">
                                ${ratingIcon} ${ratingLabel}
                            </div>
                        </div>
                        <div class="submission-meta">
                            <div class="meta-item">
                                <span class="meta-label">Score:</span>
                                <span class="meta-value">${sub.score}/100</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Interview:</span>
                                <span class="meta-value">${sub.interview_date}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Submitted:</span>
                                <span class="meta-value">${sub.date}</span>
                            </div>
                        </div>
                        <div class="submission-actions">
                            <button class="cas-btn cas-btn-small cas-view-submission" data-id="${sub.id}">
                                View Details
                            </button>
                            <button class="cas-btn cas-btn-small cas-btn-danger cas-delete-submission" data-id="${sub.id}">
                                Delete
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#cas-submissions-grid').html(html);
    }
    
    // View submission detail
    $(document).on('click', '.cas-view-submission', function() {
        let submissionId = $(this).data('id');
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_get_submission_detail',
                nonce: casAdmin.nonce,
                id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    renderSubmissionDetail(response.data);
                    $('#cas-submission-modal').fadeIn();
                }
            }
        });
    });
    
    // Render submission detail
    function renderSubmissionDetail(data) {
        let ratingClass = 'rating-' + data.rating;
        let ratingIcon = data.rating === 'safe' ? '✅' : 
                        (data.rating === 'acceptable' ? '⚖️' : 
                        (data.rating === 'risk' ? '⚠️' : '❌'));
        let ratingLabel = data.rating.charAt(0).toUpperCase() + data.rating.slice(1);
        
        let ratingDescription = '';
        switch(data.rating) {
            case 'safe':
                ratingDescription = 'Positive, stable personality traits. Low risk of counterproductive behavior. Suitable for workplace integration.';
                break;
            case 'acceptable':
                ratingDescription = 'Some areas of concern (e.g., stress handling, adaptability). Manageable with guidance or training.';
                break;
            case 'risk':
                ratingDescription = 'Traits showing potential absenteeism, lack of responsibility, or conflict-prone behavior. Requires corrective action.';
                break;
            case 'harmful':
                ratingDescription = 'Strong negative indicators (dishonesty, aggression, lack of accountability). Not recommended for sensitive roles.';
                break;
        }
        
        let html = `
            <div class="submission-detail-header ${ratingClass}">
                <div class="detail-candidate">
                    <h3>${data.name}</h3>
                    <div class="detail-contact">
                        <span>📧 ${data.email}</span>
                        <span>📱 ${data.phone}</span>
                    </div>
                    <div class="detail-dates">
                        <span>Interview: ${data.interview_date}</span>
                        <span>Submitted: ${data.date}</span>
                    </div>
                </div>
                <div class="detail-rating">
                    <div class="rating-badge-large ${ratingClass}">
                        ${ratingIcon} ${ratingLabel}
                    </div>
                    <div class="score-display">
                        <span class="score-number">${data.score}</span>
                        <span class="score-label">/100</span>
                    </div>
                    <p class="rating-description">${ratingDescription}</p>
                </div>
            </div>
            
            <div class="submission-answers">
                <h3>Question Responses</h3>
        `;
        
        let answerLabels = {
            'strongly_agree': 'Strongly Agree',
            'agree': 'Agree',
            'neutral': 'Neutral',
            'disagree': 'Disagree',
            'strongly_disagree': 'Strongly Disagree'
        };
        
        let index = 1;
        for (let questionId in data.answers) {
            let answer = data.answers[questionId];
            let answerClass = 'answer-' + answer.replace('_', '-');
            
            html += `
                <div class="answer-item">
                    <div class="answer-number">${index}</div>
                    <div class="answer-content">
                        <div class="answer-question">${answer}</div>
                        <div class="answer-response ${answerClass}">
                            ${answerLabels[answer] || answer}
                        </div>
                    </div>
                </div>
            `;
            index++;
        }
        
        html += '</div>';
        
        $('#cas-submission-detail').html(html);
    }
    
    // Delete submission
    $(document).on('click', '.cas-delete-submission', function() {
        if (!confirm('Are you sure you want to delete this submission?')) return;
        
        let submissionId = $(this).data('id');
        
        $.ajax({
            url: casAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cas_delete_submission',
                nonce: casAdmin.nonce,
                id: submissionId
            },
            success: function(response) {
                if (response.success) {
                    loadSubmissions();
                }
            }
        });
    });
    
    // Filter and search
    $('#cas-filter, #cas-search').on('change keyup', function() {
        loadSubmissions();
    });
    
    // Close modal
    $('.cas-modal-close').on('click', function() {
        $('.cas-modal').fadeOut();
    });
    
    // Initial load
    loadSubmissions();
});
</script>
