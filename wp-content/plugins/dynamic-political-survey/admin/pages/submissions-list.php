<?php
/**
 * Submissions List Page
 * Display and manage survey submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Check if viewing single submission
if (isset($_GET['view'])) {
    include DPS_PLUGIN_DIR . 'admin/pages/submission-view.php';
    return;
}

// Get filter parameters
$survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;
$page_num = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

// Get all surveys for filter
$table_surveys = $wpdb->prefix . 'dps_surveys';
$all_surveys = $wpdb->get_results("SELECT id, title FROM $table_surveys ORDER BY title");

// Build query
$table_submissions = $wpdb->prefix . 'dps_submissions';
$table_districts = $wpdb->prefix . 'dps_districts';

$sql = "SELECT s.*, sv.title as survey_title, d.name as district_name
        FROM $table_submissions s
        LEFT JOIN $table_surveys sv ON s.survey_id = sv.id
        LEFT JOIN $table_districts d ON s.district_id = d.id";

$count_sql = "SELECT COUNT(*) FROM $table_submissions s";

$where = [];
$params = [];

if ($survey_id > 0) {
    $where[] = "s.survey_id = %d";
    $params[] = $survey_id;
}

if (!empty($where)) {
    $where_clause = " WHERE " . implode(' AND ', $where);
    $sql .= $where_clause;
    $count_sql .= $where_clause;
}

$sql .= " ORDER BY s.submitted_at DESC LIMIT %d OFFSET %d";
$params[] = $per_page;
$params[] = $offset;

// Get submissions
if (!empty($params)) {
    $submissions = $wpdb->get_results($wpdb->prepare($sql, $params));
    $total = $wpdb->get_var($wpdb->prepare($count_sql, array_slice($params, 0, -2)));
} else {
    $submissions = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
    $total = $wpdb->get_var($count_sql);
}

$total_pages = ceil($total / $per_page);
?>

<div class="wrap dps-submissions-page">
    <h1><?php _e('Survey Submissions', 'dynamic-survey'); ?></h1>
    
    <!-- Filters -->
    <div class="dps-filters-bar">
        <form method="get" action="">
            <input type="hidden" name="page" value="dynamic-survey-submissions">
            
            <select name="survey_id" id="filter-survey">
                <option value=""><?php _e('All Surveys', 'dynamic-survey'); ?></option>
                <?php foreach ($all_surveys as $survey): ?>
                    <option value="<?php echo $survey->id; ?>" <?php selected($survey_id, $survey->id); ?>>
                        <?php echo esc_html($survey->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="button"><?php _e('Filter', 'dynamic-survey'); ?></button>
            
            <?php if ($survey_id > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=dynamic-survey-submissions'); ?>" class="button">
                    <?php _e('Clear', 'dynamic-survey'); ?>
                </a>
            <?php endif; ?>
            
            <div class="filter-stats">
                <strong><?php echo number_format($total); ?></strong> 
                <?php _e('submissions found', 'dynamic-survey'); ?>
            </div>
        </form>
        
        <div class="bulk-actions">
            <button type="button" class="button" id="export-submissions">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export CSV', 'dynamic-survey'); ?>
            </button>
            <button type="button" class="button" id="delete-selected" disabled>
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Delete Selected', 'dynamic-survey'); ?>
            </button>
        </div>
    </div>
    
    <!-- Submissions Table -->
    <?php if (empty($submissions)): ?>
        <div class="dps-empty-state">
            <div class="empty-icon">📋</div>
            <h2><?php _e('No Submissions Yet', 'dynamic-survey'); ?></h2>
            <p><?php _e('Submissions will appear here once users start completing your surveys.', 'dynamic-survey'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="select-all">
                    </td>
                    <th><?php _e('ID', 'dynamic-survey'); ?></th>
                    <th><?php _e('Survey', 'dynamic-survey'); ?></th>
                    <th><?php _e('District', 'dynamic-survey'); ?></th>
                    <th><?php _e('Submitted', 'dynamic-survey'); ?></th>
                    <th><?php _e('IP Address', 'dynamic-survey'); ?></th>
                    <th><?php _e('Actions', 'dynamic-survey'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" class="submission-checkbox" value="<?php echo $submission->id; ?>">
                        </th>
                        <td><strong>#<?php echo $submission->id; ?></strong></td>
                        <td><?php echo esc_html($submission->survey_title); ?></td>
                        <td><?php echo $submission->district_name ? esc_html($submission->district_name) : '-'; ?></td>
                        <td>
                            <?php echo date('M d, Y', strtotime($submission->submitted_at)); ?>
                            <br>
                            <small style="color: #999;">
                                <?php echo date('H:i:s', strtotime($submission->submitted_at)); ?>
                            </small>
                        </td>
                        <td><code><?php echo esc_html($submission->ip_address); ?></code></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=dynamic-survey-submissions&view=' . $submission->id); ?>" 
                               class="button button-small">
                                <?php _e('View Details', 'dynamic-survey'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(__('%s items', 'dynamic-survey'), number_format($total)); ?>
                    </span>
                    
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $page_num,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.dps-submissions-page {
    max-width: 1400px;
}

.dps-filters-bar {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.dps-filters-bar form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.filter-stats {
    padding: 8px 15px;
    background: #f0f0f1;
    border-radius: 4px;
    font-size: 14px;
}

.bulk-actions {
    display: flex;
    gap: 10px;
}

.dps-empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    margin-top: 20px;
}

.empty-icon {
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
}

.wp-list-table {
    margin-top: 0;
}

.wp-list-table code {
    background: #f0f0f1;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}

@media (max-width: 768px) {
    .dps-filters-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .dps-filters-bar form {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#select-all').on('change', function() {
        $('.submission-checkbox').prop('checked', $(this).is(':checked'));
        toggleBulkActions();
    });
    
    // Individual checkbox
    $('.submission-checkbox').on('change', toggleBulkActions);
    
    function toggleBulkActions() {
        const checked = $('.submission-checkbox:checked').length;
        $('#delete-selected').prop('disabled', checked === 0);
    }
    
    // Export submissions
    $('#export-submissions').on('click', function() {
        const surveyId = $('#filter-survey').val();
        const url = ajaxurl + '?action=dps_export_submissions&survey_id=' + surveyId + '&nonce=<?php echo wp_create_nonce("dps_admin_nonce"); ?>';
        window.location.href = url;
    });
    
    // Delete selected
    $('#delete-selected').on('click', function() {
        const ids = $('.submission-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!confirm('Are you sure you want to delete ' + ids.length + ' submission(s)? This cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dps_delete_submissions',
                ids: ids,
                nonce: '<?php echo wp_create_nonce("dps_admin_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error deleting submissions');
                }
            }
        });
    });
});
</script>