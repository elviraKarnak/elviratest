<?php
/**
 * Dashboard Template
 */

if (!defined('ABSPATH')) exit;

// Get statistics
$total_questions = wp_count_posts('cas_question')->publish;
$total_submissions = wp_count_posts('cas_submission')->publish;

// Get rating counts
$args = array(
    'post_type' => 'cas_submission',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);

$submissions = get_posts($args);
$rating_counts = array(
    'safe' => 0,
    'acceptable' => 0,
    'risk' => 0,
    'harmful' => 0
);

foreach ($submissions as $sub) {
    $rating = get_post_meta($sub->ID, 'cas_rating', true);
    if (isset($rating_counts[$rating])) {
        $rating_counts[$rating]++;
    }
}
?>

<div class="cas-admin-wrap">
    <div class="cas-header">
        <h1>📊 Assessment Dashboard</h1>
        <p>Overview of your candidate assessment system</p>
    </div>
    
    <div class="cas-dashboard-grid">
        <!-- Stats Cards -->
        <div class="cas-stat-card">
            <div class="stat-icon">❓</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_questions; ?></div>
                <div class="stat-label">Total Questions</div>
            </div>
        </div>
        
        <div class="cas-stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_submissions; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
        </div>
        
        <div class="cas-stat-card rating-safe">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $rating_counts['safe']; ?></div>
                <div class="stat-label">Safe Candidates</div>
            </div>
        </div>
        
        <div class="cas-stat-card rating-acceptable">
            <div class="stat-icon">⚖️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $rating_counts['acceptable']; ?></div>
                <div class="stat-label">Acceptable</div>
            </div>
        </div>
        
        <div class="cas-stat-card rating-risk">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $rating_counts['risk']; ?></div>
                <div class="stat-label">Risk</div>
            </div>
        </div>
        
        <div class="cas-stat-card rating-harmful">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $rating_counts['harmful']; ?></div>
                <div class="stat-label">Harmful</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="cas-quick-actions">
        <h2>⚡ Quick Actions</h2>
        <div class="cas-action-grid">
            <a href="<?php echo admin_url('admin.php?page=cas-questions'); ?>" class="cas-action-card">
                <div class="action-icon">➕</div>
                <div class="action-content">
                    <h3>Add Questions</h3>
                    <p>Create new assessment questions</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=cas-submissions'); ?>" class="cas-action-card">
                <div class="action-icon">👥</div>
                <div class="action-content">
                    <h3>View Submissions</h3>
                    <p>Review candidate responses</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=cas-settings'); ?>" class="cas-action-card">
                <div class="action-icon">⚙️</div>
                <div class="action-content">
                    <h3>Configure Settings</h3>
                    <p>Adjust colors and thresholds</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- How to Use -->
    <div class="cas-info-box">
        <h2>🚀 Getting Started</h2>
        <ol class="cas-steps-list">
            <li>
                <strong>Add Questions:</strong> Go to Questions page and create your assessment questions. 
                Mark each as Positive, Negative, or Moderate.
            </li>
            <li>
                <strong>Configure Settings:</strong> Set your brand colors and scoring thresholds in Settings.
            </li>
            <li>
                <strong>Add to Page:</strong> Use the shortcode <code>[candidate_assessment]</code> on any page 
                to display the assessment form to candidates.
            </li>
            <li>
                <strong>Review Results:</strong> Check Submissions page to see candidate responses and ratings.
            </li>
        </ol>
    </div>
</div>
