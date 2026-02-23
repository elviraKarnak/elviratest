<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) exit;

$settings = get_option('cas_settings');
?>

<div class="cas-admin-wrap">
    <div class="cas-header">
        <h1>⚙️ Assessment Settings</h1>
        <p>Configure your assessment system appearance and scoring thresholds</p>
    </div>
    
    <form method="post" action="" class="cas-settings-form">
        <?php wp_nonce_field('cas_settings_nonce'); ?>
        
        <div class="cas-settings-grid">
            <!-- Color Settings -->
            <div class="cas-settings-card">
                <h2>🎨 Color Scheme</h2>
                <p class="description">Customize the look and feel of your assessment forms</p>
                
                <div class="cas-form-group">
                    <label for="primary_color">Primary Color</label>
                    <div class="cas-color-picker">
                        <input type="color" id="primary_color" name="primary_color" 
                               value="<?php echo esc_attr($settings['primary_color']); ?>" 
                               class="cas-color-input">
                        <input type="text" value="<?php echo esc_attr($settings['primary_color']); ?>" 
                               class="cas-color-text" readonly>
                    </div>
                    <small>Used for buttons, headers, and primary elements</small>
                </div>
                
                <div class="cas-form-group">
                    <label for="secondary_color">Secondary Color</label>
                    <div class="cas-color-picker">
                        <input type="color" id="secondary_color" name="secondary_color" 
                               value="<?php echo esc_attr($settings['secondary_color']); ?>" 
                               class="cas-color-input">
                        <input type="text" value="<?php echo esc_attr($settings['secondary_color']); ?>" 
                               class="cas-color-text" readonly>
                    </div>
                    <small>Used for accents and success messages</small>
                </div>
                
                <div class="cas-color-preview">
                    <div class="preview-box" style="background: <?php echo esc_attr($settings['primary_color']); ?>">
                        Primary
                    </div>
                    <div class="preview-box" style="background: <?php echo esc_attr($settings['secondary_color']); ?>">
                        Secondary
                    </div>
                </div>
            </div>
            
            <!-- Form Settings -->
            <div class="cas-settings-card">
                <h2>📝 Form Configuration</h2>
                <p class="description">Control how questions are displayed to candidates</p>
                
                <div class="cas-form-group">
                    <label for="questions_per_page">Questions Per Page</label>
                    <div class="cas-range-slider">
                        <input type="range" id="questions_per_page" name="questions_per_page" 
                               min="5" max="20" value="<?php echo esc_attr($settings['questions_per_page']); ?>" 
                               class="cas-slider">
                        <span class="cas-range-value"><?php echo esc_html($settings['questions_per_page']); ?></span>
                    </div>
                    <small>Number of questions shown per step</small>
                </div>
            </div>
            
            <!-- Scoring Thresholds -->
            <div class="cas-settings-card cas-full-width">
                <h2>📊 Scoring Thresholds</h2>
                <p class="description">Define score ranges for each rating level (0-100 scale)</p>
                
                <div class="cas-threshold-grid">
                    <div class="cas-threshold-item cas-safe">
                        <div class="threshold-icon">✅</div>
                        <h3>Safe</h3>
                        <div class="cas-form-group">
                            <label>Minimum Score</label>
                            <input type="number" name="safe_threshold" 
                                   value="<?php echo esc_attr($settings['safe_threshold']); ?>" 
                                   min="0" max="100" class="cas-input">
                        </div>
                        <small>Positive, stable personality traits</small>
                    </div>
                    
                    <div class="cas-threshold-item cas-acceptable">
                        <div class="threshold-icon">⚖️</div>
                        <h3>Acceptable</h3>
                        <div class="cas-form-group">
                            <label>Minimum Score</label>
                            <input type="number" name="acceptable_threshold" 
                                   value="<?php echo esc_attr($settings['acceptable_threshold']); ?>" 
                                   min="0" max="100" class="cas-input">
                        </div>
                        <small>Manageable with guidance</small>
                    </div>
                    
                    <div class="cas-threshold-item cas-risk">
                        <div class="threshold-icon">⚠️</div>
                        <h3>Risk</h3>
                        <div class="cas-form-group">
                            <label>Minimum Score</label>
                            <input type="number" name="risk_threshold" 
                                   value="<?php echo esc_attr($settings['risk_threshold']); ?>" 
                                   min="0" max="100" class="cas-input">
                        </div>
                        <small>Requires corrective action</small>
                    </div>
                    
                    <div class="cas-threshold-item cas-harmful">
                        <div class="threshold-icon">❌</div>
                        <h3>Harmful</h3>
                        <div class="cas-form-group">
                            <label>Score Range</label>
                            <input type="text" value="Below <?php echo esc_attr($settings['risk_threshold']); ?>" 
                                   class="cas-input" disabled>
                        </div>
                        <small>Strong negative indicators</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cas-settings-footer">
            <button type="submit" name="cas_save_settings" class="cas-btn cas-btn-primary">
                💾 Save Settings
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Update color text inputs
    $('.cas-color-input').on('input', function() {
        $(this).siblings('.cas-color-text').val($(this).val());
    });
    
    // Update range slider value display
    $('.cas-slider').on('input', function() {
        $(this).siblings('.cas-range-value').text($(this).val());
    });
});
</script>
