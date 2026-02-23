<?php
/**
 * Surveys List Page
 * Display all surveys with actions
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all surveys
$table_surveys = $wpdb->prefix . 'dps_surveys';
$surveys = $wpdb->get_results("SELECT * FROM $table_surveys ORDER BY created_at DESC");

// Get submission counts
$table_submissions = $wpdb->prefix . 'dps_submissions';
$submission_counts = $wpdb->get_results(
    "SELECT survey_id, COUNT(*) as count FROM $table_submissions GROUP BY survey_id",
    OBJECT_K
);
?>

<div class="wrap dps-surveys-list">
    <h1 class="wp-heading-inline"><?php _e('All Surveys', 'dynamic-survey'); ?></h1>
    
    <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add'); ?>" class="page-title-action">
        <?php _e('Add New Survey', 'dynamic-survey'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (empty($surveys)): ?>
        <div class="dps-empty-state">
            <div class="dps-empty-icon">📋</div>
            <h2><?php _e('No Surveys Yet', 'dynamic-survey'); ?></h2>
            <p><?php _e('Create your first survey to start collecting responses.', 'dynamic-survey'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add'); ?>" class="button button-primary button-large">
                <?php _e('Create Your First Survey', 'dynamic-survey'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="dps-surveys-grid">
            <?php foreach ($surveys as $survey): 
                $submission_count = isset($submission_counts[$survey->id]) ? $submission_counts[$survey->id]->count : 0;
                $status_class = 'status-' . $survey->status;
                $status_label = ucfirst($survey->status);
            ?>
                <div class="dps-survey-card <?php echo $status_class; ?>">
                    <div class="survey-header">
                        <h3 class="survey-title"><?php echo esc_html($survey->title); ?></h3>
                        <span class="survey-status"><?php echo $status_label; ?></span>
                    </div>
                    
                    <?php if ($survey->description): ?>
                        <p class="survey-description"><?php echo esc_html($survey->description); ?></p>
                    <?php endif; ?>
                    
                    <div class="survey-stats">
                        <div class="stat">
                            <span class="stat-value"><?php echo $submission_count; ?></span>
                            <span class="stat-label"><?php _e('Responses', 'dynamic-survey'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?php echo date('M d, Y', strtotime($survey->created_at)); ?></span>
                            <span class="stat-label"><?php _e('Created', 'dynamic-survey'); ?></span>
                        </div>
                    </div>
                    
                    <div class="survey-actions">
                        <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add&survey_id=' . $survey->id); ?>" 
                           class="button button-primary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Edit', 'dynamic-survey'); ?>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=dynamic-survey-submissions&survey_id=' . $survey->id); ?>" 
                           class="button">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('View Responses', 'dynamic-survey'); ?>
                        </a>
                        
                        <button class="button dps-copy-shortcode" 
                                data-shortcode='[dynamic_survey id="<?php echo $survey->id; ?>"]'>
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Copy Shortcode', 'dynamic-survey'); ?>
                        </button>
                        
                        <div class="survey-more-actions">
                            <button class="button" onclick="this.nextElementSibling.classList.toggle('show')">
                                <span class="dashicons dashicons-ellipsis"></span>
                            </button>
                            <div class="more-actions-menu">
                                <button class="dps-duplicate-survey" data-survey-id="<?php echo $survey->id; ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                    <?php _e('Duplicate', 'dynamic-survey'); ?>
                                </button>
                                <button class="dps-delete-survey" data-survey-id="<?php echo $survey->id; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Delete', 'dynamic-survey'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.dps-surveys-list {
    max-width: 1400px;
}

.dps-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-top: 20px;
}

.dps-empty-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.dps-empty-state h2 {
    font-size: 24px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.dps-empty-state p {
    font-size: 16px;
    color: #7f8c8d;
    margin-bottom: 30px;
}

.dps-surveys-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.dps-survey-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 25px;
    transition: all 0.3s;
    position: relative;
}

.dps-survey-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.dps-survey-card.status-active {
    border-left: 4px solid #27ae60;
}

.dps-survey-card.status-inactive {
    border-left: 4px solid #e74c3c;
}

.dps-survey-card.status-draft {
    border-left: 4px solid #f39c12;
}

.survey-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
}

.survey-title {
    font-size: 20px;
    color: #2c3e50;
    margin: 0;
    flex: 1;
}

.survey-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active .survey-status {
    background: #d4edda;
    color: #155724;
}

.status-inactive .survey-status {
    background: #f8d7da;
    color: #721c24;
}

.status-draft .survey-status {
    background: #fff3cd;
    color: #856404;
}

.survey-description {
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.survey-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 15px 0;
    border-top: 1px solid #ecf0f1;
    border-bottom: 1px solid #ecf0f1;
    margin-bottom: 20px;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
}

.survey-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.survey-actions .button {
    flex: 1;
    min-width: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.survey-more-actions {
    position: relative;
}

.more-actions-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 10;
    min-width: 150px;
}

.more-actions-menu.show {
    display: block;
}

.more-actions-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 15px;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
    transition: background 0.2s;
}

.more-actions-menu button:hover {
    background: #f5f5f5;
}

@media (max-width: 768px) {
    .dps-surveys-grid {
        grid-template-columns: 1fr;
    }
    
    .survey-actions .button {
        min-width: auto;
        flex: 0 0 calc(50% - 4px);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode
    $('.dps-copy-shortcode').on('click', function() {
        const shortcode = $(this).data('shortcode');
        const $button = $(this);
        
        navigator.clipboard.writeText(shortcode).then(function() {
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
            
            setTimeout(function() {
                $button.html(originalText);
            }, 2000);
        });
    });
    
    // Delete survey
    $('.dps-delete-survey').on('click', function() {
        if (!confirm('Are you sure you want to delete this survey? This action cannot be undone.')) {
            return;
        }
        
        const surveyId = $(this).data('survey-id');
        const $card = $(this).closest('.dps-survey-card');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dps_delete_survey',
                survey_id: surveyId,
                nonce: '<?php echo wp_create_nonce("dps_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(300, function() {
                        $(this).remove();
                        
                        if ($('.dps-survey-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Error deleting survey');
                }
            }
        });
    });
    
    // Duplicate survey
    $('.dps-duplicate-survey').on('click', function() {
        const surveyId = $(this).data('survey-id');
        const $button = $(this);
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Duplicating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dps_duplicate_survey',
                survey_id: surveyId,
                nonce: '<?php echo wp_create_nonce("dps_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error duplicating survey');
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-page"></span> Duplicate');
                }
            }
        });
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.survey-more-actions').length) {
            $('.more-actions-menu').removeClass('show');
        }
    });
});
</script>