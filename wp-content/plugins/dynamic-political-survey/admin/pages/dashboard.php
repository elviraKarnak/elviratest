<?php
/**
 * Dashboard Page
 * Overview of all survey statistics
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get statistics
$table_surveys = $wpdb->prefix . 'dps_surveys';
$table_submissions = $wpdb->prefix . 'dps_submissions';

$total_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $table_surveys");
$active_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $table_surveys WHERE status = 'active'");
$draft_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $table_surveys WHERE status = 'draft'");
$inactive_surveys = $wpdb->get_var("SELECT COUNT(*) FROM $table_surveys WHERE status = 'inactive'");
$total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $table_submissions");

// Get recent submissions
$recent_submissions = $wpdb->get_results("
    SELECT s.*, sv.title as survey_title 
    FROM $table_submissions s
    LEFT JOIN $table_surveys sv ON s.survey_id = sv.id
    ORDER BY s.submitted_at DESC
    LIMIT 10
");

// Get submissions this month
$submissions_this_month = $wpdb->get_var("
    SELECT COUNT(*) FROM $table_submissions 
    WHERE MONTH(submitted_at) = MONTH(CURRENT_DATE())
    AND YEAR(submitted_at) = YEAR(CURRENT_DATE())
");

// Get top performing surveys
$top_surveys = $wpdb->get_results("
    SELECT sv.id, sv.title, COUNT(s.id) as response_count
    FROM $table_surveys sv
    LEFT JOIN $table_submissions s ON sv.id = s.survey_id
    WHERE sv.status = 'active'
    GROUP BY sv.id
    ORDER BY response_count DESC
    LIMIT 5
");
?>

<div class="wrap dps-dashboard">
    <h1><?php _e('Survey Dashboard', 'dynamic-survey'); ?></h1>
    
    <!-- Stats Grid -->
    <div class="dps-stats-grid">
        <div class="dps-stat-card primary">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo number_format($total_surveys); ?></h3>
                <p><?php _e('Total Surveys', 'dynamic-survey'); ?></p>
            </div>
        </div>
        
        <div class="dps-stat-card success">
            <div class="stat-icon">✓</div>
            <div class="stat-content">
                <h3><?php echo number_format($active_surveys); ?></h3>
                <p><?php _e('Active Surveys', 'dynamic-survey'); ?></p>
            </div>
        </div>
        
        <div class="dps-stat-card info">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <h3><?php echo number_format($total_submissions); ?></h3>
                <p><?php _e('Total Responses', 'dynamic-survey'); ?></p>
            </div>
        </div>
        
        <div class="dps-stat-card warning">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <h3><?php echo number_format($submissions_this_month); ?></h3>
                <p><?php _e('This Month', 'dynamic-survey'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dps-quick-actions">
        <h2><?php _e('Quick Actions', 'dynamic-survey'); ?></h2>
        <div class="actions-grid">
            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-add'); ?>" class="action-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <strong><?php _e('Create Survey', 'dynamic-survey'); ?></strong>
                <p><?php _e('Start building a new survey', 'dynamic-survey'); ?></p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-surveys'); ?>" class="action-card">
                <span class="dashicons dashicons-list-view"></span>
                <strong><?php _e('View Surveys', 'dynamic-survey'); ?></strong>
                <p><?php _e('Manage all your surveys', 'dynamic-survey'); ?></p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-analytics'); ?>" class="action-card">
                <span class="dashicons dashicons-chart-bar"></span>
                <strong><?php _e('Analytics', 'dynamic-survey'); ?></strong>
                <p><?php _e('View survey insights', 'dynamic-survey'); ?></p>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-submissions'); ?>" class="action-card">
                <span class="dashicons dashicons-archive"></span>
                <strong><?php _e('Submissions', 'dynamic-survey'); ?></strong>
                <p><?php _e('View all responses', 'dynamic-survey'); ?></p>
            </a>
        </div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="dps-dashboard-columns">
        <!-- Top Performing Surveys -->
        <div class="dps-dashboard-widget">
            <h2><?php _e('Top Performing Surveys', 'dynamic-survey'); ?></h2>
            <?php if (!empty($top_surveys)): ?>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Survey', 'dynamic-survey'); ?></th>
                            <th><?php _e('Responses', 'dynamic-survey'); ?></th>
                            <th><?php _e('Actions', 'dynamic-survey'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_surveys as $survey): ?>
                            <tr>
                                <td><strong><?php echo esc_html($survey->title); ?></strong></td>
                                <td><?php echo number_format($survey->response_count); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=dynamic-survey-analytics&survey_id=' . $survey->id); ?>">
                                        <?php _e('View Analytics', 'dynamic-survey'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data"><?php _e('No survey data yet.', 'dynamic-survey'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Submissions -->
        <div class="dps-dashboard-widget">
            <h2><?php _e('Recent Submissions', 'dynamic-survey'); ?></h2>
            <?php if (!empty($recent_submissions)): ?>
                <div class="recent-submissions-list">
                    <?php foreach ($recent_submissions as $submission): ?>
                        <div class="submission-item">
                            <div class="submission-info">
                                <strong><?php echo esc_html($submission->survey_title); ?></strong>
                                <span class="submission-date">
                                    <?php echo human_time_diff(strtotime($submission->submitted_at), current_time('timestamp')) . ' ago'; ?>
                                </span>
                            </div>
                            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-submissions&view=' . $submission->id); ?>" class="button button-small">
                                <?php _e('View', 'dynamic-survey'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data"><?php _e('No submissions yet.', 'dynamic-survey'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Survey Status Overview -->
    <?php if ($total_surveys > 0): ?>
        <div class="dps-dashboard-widget full-width">
            <h2><?php _e('Survey Status Overview', 'dynamic-survey'); ?></h2>
            <div class="status-bars">
                <div class="status-bar">
                    <div class="status-label">
                        <strong><?php _e('Active', 'dynamic-survey'); ?></strong>
                        <span><?php echo $active_surveys; ?> / <?php echo $total_surveys; ?></span>
                    </div>
                    <div class="status-progress">
                        <div class="progress-fill success" style="width: <?php echo ($active_surveys / $total_surveys) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="status-bar">
                    <div class="status-label">
                        <strong><?php _e('Draft', 'dynamic-survey'); ?></strong>
                        <span><?php echo $draft_surveys; ?> / <?php echo $total_surveys; ?></span>
                    </div>
                    <div class="status-progress">
                        <div class="progress-fill warning" style="width: <?php echo ($draft_surveys / $total_surveys) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="status-bar">
                    <div class="status-label">
                        <strong><?php _e('Inactive', 'dynamic-survey'); ?></strong>
                        <span><?php echo $inactive_surveys; ?> / <?php echo $total_surveys; ?></span>
                    </div>
                    <div class="status-progress">
                        <div class="progress-fill danger" style="width: <?php echo ($inactive_surveys / $total_surveys) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.dps-dashboard {
    max-width: 1400px;
}

.dps-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 20px 0 40px;
}

.dps-stat-card {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    border-left: 4px solid #ddd;
}

.dps-stat-card.primary { border-left-color: #667eea; }
.dps-stat-card.success { border-left-color: #27ae60; }
.dps-stat-card.info { border-left-color: #3498db; }
.dps-stat-card.warning { border-left-color: #f39c12; }

.stat-icon {
    font-size: 40px;
}

.stat-content h3 {
    font-size: 36px;
    margin: 0 0 5px;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

.dps-quick-actions {
    margin: 40px 0;
}

.dps-quick-actions h2 {
    margin-bottom: 20px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.action-card {
    background: #fff;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    border: 2px solid #e9ecef;
    transition: all 0.3s;
}

.action-card:hover {
    border-color: #667eea;
    transform: translateY(-4px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.action-card .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #667eea;
    margin-bottom: 15px;
}

.action-card strong {
    display: block;
    font-size: 18px;
    color: #2c3e50;
    margin-bottom: 8px;
}

.action-card p {
    color: #7f8c8d;
    font-size: 14px;
    margin: 0;
}

.dps-dashboard-columns {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 40px 0;
}

.dps-dashboard-widget {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.dps-dashboard-widget.full-width {
    grid-column: 1 / -1;
}

.dps-dashboard-widget h2 {
    margin: 0 0 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #ecf0f1;
    font-size: 20px;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.recent-submissions-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.submission-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.submission-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.submission-date {
    font-size: 13px;
    color: #7f8c8d;
}

.status-bars {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.status-bar {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.status-label {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.status-progress {
    height: 12px;
    background: #ecf0f1;
    border-radius: 6px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s;
}

.progress-fill.success { background: #27ae60; }
.progress-fill.warning { background: #f39c12; }
.progress-fill.danger { background: #e74c3c; }

@media (max-width: 1200px) {
    .dps-stats-grid,
    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dps-stats-grid,
    .actions-grid,
    .dps-dashboard-columns {
        grid-template-columns: 1fr;
    }
}
</style>