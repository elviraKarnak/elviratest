<?php
/**
 * Analytics Dashboard - Redesigned
 * Filters at top, Results at bottom
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all surveys
$table_surveys = $wpdb->prefix . 'dps_surveys';
$surveys = $wpdb->get_results("SELECT * FROM $table_surveys WHERE status = 'active' ORDER BY created_at DESC");

// Get all districts
$table_districts = $wpdb->prefix . 'dps_districts';
$districts = $wpdb->get_results("SELECT * FROM $table_districts ORDER BY name ASC");
?>

<div class="wrap dps-analytics-page">
    <h1><?php _e('📊 Survey Analytics', 'dynamic-survey'); ?></h1>
    
    <div class="dps-analytics-container">
        
        <!-- FILTERS SECTION - AT TOP -->
        <div class="dps-filters-section">
            <div class="dps-filters-header">
                <h2><?php _e('Filter & Analyze', 'dynamic-survey'); ?></h2>
            </div>
            
            <!-- Survey & Question Selection -->
            <div class="dps-selection-row">
                <div class="question-select-box">
                    <h3><?php _e('❓ Select Question to Analyze', 'dynamic-survey'); ?></h3>
                    <div class="filter-group">
                        <label><?php _e('Survey', 'dynamic-survey'); ?></label>
                        <select id="filter-survey">
                            <option value=""><?php _e('Select a survey...', 'dynamic-survey'); ?></option>
                            <?php foreach ($surveys as $survey): ?>
                                <option value="<?php echo $survey->id; ?>">
                                    <?php echo esc_html($survey->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><?php _e('Question', 'dynamic-survey'); ?></label>
                        <select id="filter-question">
                            <option value=""><?php _e('Select survey first...', 'dynamic-survey'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="chart-type-box">
                    <h3><?php _e('📊 Chart Type', 'dynamic-survey'); ?></h3>
                    <div class="chart-type-options">
                        <label class="chart-option active">
                            <input type="radio" name="chart_type" value="pie" checked>
                            <span class="dashicons dashicons-chart-pie"></span>
                            <span><?php _e('Pie Chart', 'dynamic-survey'); ?></span>
                        </label>
                        <label class="chart-option">
                            <input type="radio" name="chart_type" value="bar">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <span><?php _e('Bar Chart', 'dynamic-survey'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Main Filters Grid -->
            <div class="dps-filters-grid">
                <!-- Location Filters -->
                <div class="dps-filter-box">
                    <h3><?php _e('📍 Location', 'dynamic-survey'); ?></h3>
                    <div class="filter-group">
                        <label><?php _e('District', 'dynamic-survey'); ?></label>
                        <select id="filter-district">
                            <option value=""><?php _e('All Districts', 'dynamic-survey'); ?></option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo $district->id; ?>">
                                    <?php echo esc_html($district->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><?php _e('Loksabha', 'dynamic-survey'); ?></label>
                        <select id="filter-loksabha" disabled>
                            <option value=""><?php _e('Select district first', 'dynamic-survey'); ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><?php _e('Assembly', 'dynamic-survey'); ?></label>
                        <select id="filter-assembly" disabled>
                            <option value=""><?php _e('Select loksabha first', 'dynamic-survey'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Date Filters -->
                <div class="dps-filter-box">
                    <h3><?php _e('📅 Date Range', 'dynamic-survey'); ?></h3>
                    <div class="filter-group">
                        <label><?php _e('Year', 'dynamic-survey'); ?></label>
                        <select id="filter-year">
                            <option value=""><?php _e('All Years', 'dynamic-survey'); ?></option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 5; $year--):
                            ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><?php _e('Month', 'dynamic-survey'); ?></label>
                        <select id="filter-month">
                            <option value=""><?php _e('All Months', 'dynamic-survey'); ?></option>
                            <option value="01"><?php _e('January', 'dynamic-survey'); ?></option>
                            <option value="02"><?php _e('February', 'dynamic-survey'); ?></option>
                            <option value="03"><?php _e('March', 'dynamic-survey'); ?></option>
                            <option value="04"><?php _e('April', 'dynamic-survey'); ?></option>
                            <option value="05"><?php _e('May', 'dynamic-survey'); ?></option>
                            <option value="06"><?php _e('June', 'dynamic-survey'); ?></option>
                            <option value="07"><?php _e('July', 'dynamic-survey'); ?></option>
                            <option value="08"><?php _e('August', 'dynamic-survey'); ?></option>
                            <option value="09"><?php _e('September', 'dynamic-survey'); ?></option>
                            <option value="10"><?php _e('October', 'dynamic-survey'); ?></option>
                            <option value="11"><?php _e('November', 'dynamic-survey'); ?></option>
                            <option value="12"><?php _e('December', 'dynamic-survey'); ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><?php _e('Age Group', 'dynamic-survey'); ?></label>
                        <select id="filter-age">
                            <option value=""><?php _e('All Ages', 'dynamic-survey'); ?></option>
                            <option value="18-20">18-20</option>
                            <option value="21-25">21-25</option>
                            <option value="26-30">26-30</option>
                            <option value="31-35">31-35</option>
                            <option value="36-40">36-40</option>
                            <option value="41-45">41-45</option>
                            <option value="46-50">46-50</option>
                            <option value="51-60">51-60</option>
                            <option value="60-70">60-70</option>
                            <option value="70-80">70-80</option>
                            <option value="80+">80+</option>
                        </select>
                    </div>
                </div>
                
                <!-- Demographics Checkboxes -->
                <div class="dps-filter-box">
                    <h3><?php _e('👥 Demographics', 'dynamic-survey'); ?></h3>
                    <div class="dps-checkbox-filters">
                        <div class="checkbox-filter-group">
                            <h4><?php _e('Gender', 'dynamic-survey'); ?></h4>
                            <div class="checkbox-list">
                                <label>
                                    <input type="checkbox" name="gender[]" value="Male">
                                    <span>Male</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="gender[]" value="Female">
                                    <span>Female</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="gender[]" value="Others">
                                    <span>Others</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="checkbox-filter-group">
                            <h4><?php _e('Religion', 'dynamic-survey'); ?></h4>
                            <div class="checkbox-list">
                                <label>
                                    <input type="checkbox" name="religion[]" value="Hindu">
                                    <span>Hindu</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="religion[]" value="Muslim">
                                    <span>Muslim</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="religion[]" value="Christ">
                                    <span>Christian</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="religion[]" value="Sikh">
                                    <span>Sikh</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="religion[]" value="Other">
                                    <span>Other</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="checkbox-filter-group">
                            <h4><?php _e('Caste', 'dynamic-survey'); ?></h4>
                            <div class="checkbox-list">
                                <label>
                                    <input type="checkbox" name="caste[]" value="General">
                                    <span>General</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="caste[]" value="SC">
                                    <span>SC</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="caste[]" value="ST">
                                    <span>ST</span>
                                </label>
                                <label>
                                    <input type="checkbox" name="caste[]" value="OBC">
                                    <span>OBC</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="dps-filter-actions">
                <button type="button" id="generate-analytics-btn">
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php _e('Generate Analytics', 'dynamic-survey'); ?>
                </button>
                <button type="button" id="reset-filters-btn">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Reset Filters', 'dynamic-survey'); ?>
                </button>
            </div>
        </div>
        
        <!-- RESULTS SECTION - AT BOTTOM -->
        <div class="dps-results-section">
            <div class="dps-results-header">
                <h2><?php _e('📈 Analysis Results', 'dynamic-survey'); ?></h2>
                <div class="dps-export-buttons" style="display: none;">
                    <button id="export-csv-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'dynamic-survey'); ?>
                    </button>
                    <button id="export-image-btn">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php _e('Export Image', 'dynamic-survey'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Active Filters Display -->
            <div id="selected-filters-display" class="selected-filters-display" style="display: none;">
                <h4><?php _e('Active Filters:', 'dynamic-survey'); ?></h4>
                <div id="active-filters-list" class="active-filters-tags"></div>
            </div>
            
            <!-- No Data Message -->
            <div id="no-data-message" class="dps-no-data">
                <div class="dps-no-data-icon">📊</div>
                <h3><?php _e('No Data to Display', 'dynamic-survey'); ?></h3>
                <p><?php _e('Select a survey and question from the filters above, then click "Generate Analytics" to view results', 'dynamic-survey'); ?></p>
            </div>
            
            <!-- Analytics Results -->
            <div id="analytics-results" style="display: none;">
                <!-- Stats Cards -->
                <div class="dps-stats-grid">
                    <div class="dps-stat-card">
                        <div class="stat-value" id="total-responses">0</div>
                        <div class="stat-label"><?php _e('Total Responses', 'dynamic-survey'); ?></div>
                    </div>
                    
                    <div class="dps-stat-card">
                        <div class="stat-value" id="unique-answers">0</div>
                        <div class="stat-label"><?php _e('Unique Answers', 'dynamic-survey'); ?></div>
                    </div>
                    
                    <div class="dps-stat-card">
                        <div class="stat-value" id="most-popular">-</div>
                        <div class="stat-label"><?php _e('Most Popular', 'dynamic-survey'); ?></div>
                    </div>
                    
                    <div class="dps-stat-card">
                        <div class="stat-value" id="date-range">-</div>
                        <div class="stat-label"><?php _e('Date Range', 'dynamic-survey'); ?></div>
                    </div>
                </div>
                
                <!-- Chart Container -->
                <div class="dps-chart-container">
                    <canvas id="analytics-chart"></canvas>
                </div>
                
                <!-- Data Table -->
                <div class="dps-data-table-container">
                    <h3><?php _e('Detailed Breakdown', 'dynamic-survey'); ?></h3>
                    <table id="analytics-table">
                        <thead>
                            <tr>
                                <th><?php _e('Answer', 'dynamic-survey'); ?></th>
                                <th><?php _e('Count', 'dynamic-survey'); ?></th>
                                <th><?php _e('Percentage', 'dynamic-survey'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="analytics-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>