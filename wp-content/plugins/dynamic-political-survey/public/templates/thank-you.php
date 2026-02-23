<?php
/**
 * Thank You Template
 * Displayed after successful survey submission
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="dps-thank-you-wrapper">
    <div class="dps-thank-you-content">
        <div class="success-icon">
            <svg width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="#27ae60" opacity="0.1"/>
                <circle cx="50" cy="50" r="40" fill="none" stroke="#27ae60" stroke-width="3"/>
                <path d="M30 50 L45 65 L70 35" fill="none" stroke="#27ae60" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <h2><?php _e('Thank You for Your Participation!', 'dynamic-survey'); ?></h2>
        
        <p class="success-message">
            <?php _e('Your response has been recorded successfully. We appreciate you taking the time to complete this survey.', 'dynamic-survey'); ?>
        </p>
        
        <div class="thank-you-actions">
            <a href="<?php echo home_url('/'); ?>" class="button button-primary">
                <?php _e('Return to Homepage', 'dynamic-survey'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.dps-thank-you-wrapper {
    max-width: 600px;
    margin: 60px auto;
    padding: 20px;
}

.dps-thank-you-content {
    background: #fff;
    padding: 60px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-align: center;
}

.success-icon {
    margin-bottom: 30px;
    animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.dps-thank-you-content h2 {
    font-size: 32px;
    color: #27ae60;
    margin-bottom: 20px;
}

.success-message {
    font-size: 18px;
    color: #7f8c8d;
    line-height: 1.6;
    margin-bottom: 40px;
}

.thank-you-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.button {
    padding: 14px 32px;
    font-size: 16px;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.button-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
}

.button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .dps-thank-you-content {
        padding: 40px 20px;
    }
    
    .dps-thank-you-content h2 {
        font-size: 24px;
    }
    
    .success-message {
        font-size: 16px;
    }
}
</style>