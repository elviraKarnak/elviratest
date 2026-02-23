<?php
/**
 * Frontend Assessment Form Template
 */

if (!defined('ABSPATH')) exit;

$settings = get_option('cas_settings');

// Check if questions exist
$question_count = wp_count_posts('cas_question')->publish;
?>

<div class="cas-assessment-wrapper">
    <?php if ($question_count == 0): ?>
        <div class="cas-assessment-container">
            <div class="cas-empty-state" style="padding: 60px 20px; text-align: center;">
                <h2>⚠️ No Questions Available</h2>
                <p>The assessment is not yet configured. Please contact the administrator to add questions.</p>
            </div>
        </div>
    <?php else: ?>
    <div class="cas-assessment-container" id="cas-assessment-form">
        <!-- Progress Bar -->
        <div class="cas-progress-container">
            <div class="cas-progress-bar">
                <div class="cas-progress-fill" id="cas-progress-fill"></div>
            </div>
            <div class="cas-progress-text">
                <span id="cas-current-step">1</span> / <span id="cas-total-steps">1</span>
            </div>
        </div>
        
        <!-- Questions Container -->
        <div class="cas-questions-wrapper" id="cas-questions-wrapper">
            <!-- Questions will be loaded via AJAX -->
        </div>
        
        <!-- Personal Info Form -->
        <div class="cas-personal-info" id="cas-personal-info" style="display: none;">
            <h2>✍️ Personal Information</h2>
            <p class="cas-info-subtitle">Please provide your details to complete the assessment</p>
            
            <div class="cas-form-row">
                <div class="cas-form-field">
                    <label for="candidate-name">Full Name *</label>
                    <input type="text" id="candidate-name" class="cas-input" required placeholder="John Doe">
                </div>
            </div>
            
            <div class="cas-form-row cas-two-cols">
                <div class="cas-form-field">
                    <label for="candidate-email">Email Address *</label>
                    <input type="email" id="candidate-email" class="cas-input" required placeholder="john@example.com">
                </div>
                
                <div class="cas-form-field">
                    <label for="candidate-phone">Phone Number *</label>
                    <input type="tel" id="candidate-phone" class="cas-input" required placeholder="+1 234 567 8900">
                </div>
            </div>
            
            <div class="cas-form-row">
                <div class="cas-form-field">
                    <label for="interview-datetime">Interview Date & Time *</label>
                    <input type="datetime-local" id="interview-datetime" class="cas-input" required>
                </div>
            </div>
        </div>
        
        <!-- Navigation Buttons -->
        <div class="cas-navigation">
            <button type="button" class="cas-btn cas-btn-secondary" id="cas-prev-btn" style="display: none;">
                ← Previous
            </button>
            <button type="button" class="cas-btn cas-btn-primary" id="cas-next-btn">
                Next →
            </button>
            <button type="button" class="cas-btn cas-btn-primary" id="cas-submit-btn" style="display: none;">
                Submit Assessment
            </button>
        </div>
    </div>
    
    <!-- Success Message -->
    <div class="cas-success-message" id="cas-success-message" style="display: none;">
        <div class="success-icon">✅</div>
        <h2>Assessment Submitted Successfully!</h2>
        <p>Thank you for completing the assessment. Your responses have been recorded.</p>
    </div>
    <?php endif; ?>
</div>

<style>
:root {
    --cas-primary: <?php echo esc_attr($settings['primary_color']); ?>;
    --cas-secondary: <?php echo esc_attr($settings['secondary_color']); ?>;
}
</style>