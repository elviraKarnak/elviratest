<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Psychometric_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_psychometric_save_step', array($this, 'save_step'));
        add_action('admin_post_psychometric_delete_step', array($this, 'delete_step'));
        add_action('admin_post_psychometric_toggle_question', array($this, 'toggle_question'));
        add_action('admin_post_psychometric_delete_question', array($this, 'delete_question_handler'));
        add_action('admin_post_psychometric_save_settings', array($this, 'save_settings'));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Psychometric Test',
            'Psychometric Test',
            'manage_options',
            'psychometric-test',
            array($this, 'questions_page'),
            'dashicons-clipboard',
            30
        );
        
        // Questions submenu
        add_submenu_page(
            'psychometric-test',
            'Questions',
            'Questions',
            'manage_options',
            'psychometric-test',
            array($this, 'questions_page')
        );
        
        // Submissions submenu
        add_submenu_page(
            'psychometric-test',
            'Submissions',
            'Submissions',
            'manage_options',
            'psychometric-submissions',
            array($this, 'submissions_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'psychometric-test',
            'Settings',
            'Settings',
            'manage_options',
            'psychometric-settings',
            array($this, 'settings_page')
        );
    }
    
    public function questions_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $step_id = isset($_GET['step_id']) ? intval($_GET['step_id']) : 0;
        
        if ($action === 'edit' && $step_id > 0) {
            $this->edit_step_page($step_id);
        } elseif ($action === 'add') {
            $this->add_step_page();
        } else {
            $this->list_steps_page();
        }
    }
    
    public function list_steps_page() {
        $steps = Psychometric_Database::get_all_steps();
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">
                Manage Questions
                <a href="<?php echo admin_url('admin.php?page=psychometric-test&action=add'); ?>" class="page-title-action">Add New Step</a>
            </h1>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($this->get_message($_GET['message'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="psychometric-card">
                <?php if (empty($steps)): ?>
                    <div class="psychometric-empty-state">
                        <p>No steps created yet. Click "Add New Step" to get started.</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped psychometric-table">
                        <thead>
                            <tr>
                                <th width="10%">Step #</th>
                                <th width="40%">Title</th>
                                <th width="20%">Questions</th>
                                <th width="15%">Status</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($steps as $step): 
                                $questions = Psychometric_Database::get_questions_by_step($step->id);
                                $question_count = count($questions);
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($step->step_number); ?></strong></td>
                                    <td><?php echo esc_html($step->step_title); ?></td>
                                    <td><?php echo $question_count; ?> question<?php echo $question_count !== 1 ? 's' : ''; ?></td>
                                    <td><span class="psychometric-badge badge-success">Active</span></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=psychometric-test&action=edit&step_id=' . $step->id); ?>" class="button button-small">Edit</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=psychometric_delete_step&step_id=' . $step->id), 'delete_step_' . $step->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Are you sure you want to delete this step and all its questions?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function add_step_page() {
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">Add New Step</h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="psychometric-form">
                <?php wp_nonce_field('psychometric_save_step'); ?>
                <input type="hidden" name="action" value="psychometric_save_step">
                
                <div class="psychometric-card">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="step_number">Step Number</label></th>
                            <td>
                                <input type="number" name="step_number" id="step_number" class="regular-text" required min="1">
                                <p class="description">Enter a unique step number (e.g., 1, 2, 3...)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="step_title">Step Title</label></th>
                            <td>
                                <input type="text" name="step_title" id="step_title" class="regular-text" required>
                                <p class="description">Give this step a descriptive title</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Questions (6 per step)</h3>
                    <div id="questions-repeater">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="question-item">
                                <div class="question-header">
                                    <strong>Question <?php echo $i; ?></strong>
                                </div>
                                <div class="question-fields">
                                    <div class="field-group">
                                        <label>Question Text</label>
                                        <textarea name="questions[<?php echo $i; ?>][text]" rows="3" class="large-text" required></textarea>
                                    </div>
                                    <div class="field-group">
                                        <label>Polarity</label>
                                        <select name="questions[<?php echo $i; ?>][polarity]" required>
                                            <option value="positive">Positive</option>
                                            <option value="negative">Negative</option>
                                        </select>
                                        <p class="description">Positive = lower score is better | Negative = reversed scoring</p>
                                    </div>
                                    <input type="hidden" name="questions[<?php echo $i; ?>][order]" value="<?php echo $i; ?>">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary button-hero" value="Save Step">
                        <a href="<?php echo admin_url('admin.php?page=psychometric-test'); ?>" class="button button-hero">Cancel</a>
                    </p>
                </div>
            </form>
        </div>
        <?php
    }
    
    public function edit_step_page($step_id) {
        $step = Psychometric_Database::get_step($step_id);
        if (!$step) {
            wp_die('Step not found');
        }
        
        $questions = Psychometric_Database::get_questions_by_step($step_id);
        $questions_indexed = array();
        foreach ($questions as $q) {
            $questions_indexed[$q->order_number] = $q;
        }
        
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">Edit Step <?php echo esc_html($step->step_number); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="psychometric-form">
                <?php wp_nonce_field('psychometric_save_step'); ?>
                <input type="hidden" name="action" value="psychometric_save_step">
                <input type="hidden" name="step_id" value="<?php echo $step->id; ?>">
                
                <div class="psychometric-card">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="step_number">Step Number</label></th>
                            <td>
                                <input type="number" name="step_number" id="step_number" class="regular-text" value="<?php echo esc_attr($step->step_number); ?>" required min="1">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="step_title">Step Title</label></th>
                            <td>
                                <input type="text" name="step_title" id="step_title" class="regular-text" value="<?php echo esc_attr($step->step_title); ?>" required>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Questions (6 per step)</h3>
                    <div id="questions-repeater">
                        <?php for ($i = 1; $i <= 6; $i++): 
                            $question = isset($questions_indexed[$i]) ? $questions_indexed[$i] : null;
                        ?>
                            <div class="question-item">
                                <div class="question-header">
                                    <strong>Question <?php echo $i; ?></strong>
                                </div>
                                <div class="question-fields">
                                    <?php if ($question): ?>
                                        <input type="hidden" name="questions[<?php echo $i; ?>][id]" value="<?php echo $question->id; ?>">
                                    <?php endif; ?>
                                    <div class="field-group">
                                        <label>Question Text</label>
                                        <textarea name="questions[<?php echo $i; ?>][text]" rows="3" class="large-text" required><?php echo $question ? esc_textarea($question->question_text) : ''; ?></textarea>
                                    </div>
                                    <div class="field-group">
                                        <label>Polarity</label>
                                        <select name="questions[<?php echo $i; ?>][polarity]" required>
                                            <option value="positive" <?php echo ($question && $question->polarity === 'positive') ? 'selected' : ''; ?>>Positive</option>
                                            <option value="negative" <?php echo ($question && $question->polarity === 'negative') ? 'selected' : ''; ?>>Negative</option>
                                        </select>
                                        <p class="description">Positive = lower score is better | Negative = reversed scoring</p>
                                    </div>
                                    <input type="hidden" name="questions[<?php echo $i; ?>][order]" value="<?php echo $i; ?>">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary button-hero" value="Update Step">
                        <a href="<?php echo admin_url('admin.php?page=psychometric-test'); ?>" class="button button-hero">Cancel</a>
                    </p>
                </div>
            </form>
        </div>
        <?php
    }
    
    public function save_step() {
        check_admin_referer('psychometric_save_step');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $step_id = isset($_POST['step_id']) ? intval($_POST['step_id']) : 0;
        $step_number = intval($_POST['step_number']);
        $step_title = sanitize_text_field($_POST['step_title']);
        $questions = isset($_POST['questions']) ? $_POST['questions'] : array();
        
        if ($step_id > 0) {
            // Update existing step
            Psychometric_Database::update_step($step_id, $step_number, $step_title);
            
            // Update questions
            foreach ($questions as $order => $question) {
                if (isset($question['id']) && !empty($question['id'])) {
                    // Update existing question
                    Psychometric_Database::update_question(
                        intval($question['id']),
                        $question['text'],
                        $question['polarity'],
                        intval($order)
                    );
                } else {
                    // Create new question
                    Psychometric_Database::create_question(
                        $step_id,
                        $question['text'],
                        $question['polarity'],
                        intval($order)
                    );
                }
            }
            
            $message = 'step_updated';
        } else {
            // Create new step
            $step_id = Psychometric_Database::create_step($step_number, $step_title);
            
            // Create questions
            foreach ($questions as $order => $question) {
                Psychometric_Database::create_question(
                    $step_id,
                    $question['text'],
                    $question['polarity'],
                    intval($order)
                );
            }
            
            $message = 'step_created';
        }
        
        wp_redirect(admin_url('admin.php?page=psychometric-test&message=' . $message));
        exit;
    }
    
    public function delete_step() {
        $step_id = isset($_GET['step_id']) ? intval($_GET['step_id']) : 0;
        check_admin_referer('delete_step_' . $step_id);
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        Psychometric_Database::delete_step($step_id);
        
        wp_redirect(admin_url('admin.php?page=psychometric-test&message=step_deleted'));
        exit;
    }
    
    public function submissions_page() {
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;
        $offset = ($paged - 1) * $per_page;
        
        $submissions = Psychometric_Database::get_submissions($per_page, $offset);
        $total = Psychometric_Database::get_total_submissions();
        $total_pages = ceil($total / $per_page);
        
        // Handle view action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['submission_id'])) {
            $this->view_submission_page(intval($_GET['submission_id']));
            return;
        }
        
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">Submissions</h1>
            
            <div class="psychometric-card">
                <?php if (empty($submissions)): ?>
                    <div class="psychometric-empty-state">
                        <p>No submissions yet.</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped psychometric-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Name</th>
                                <th width="20%">Email</th>
                                <th width="15%">Phone</th>
                                <th width="12%">Interview Date</th>
                                <th width="10%">Score</th>
                                <th width="13%">Risk Level</th>
                                <th width="5%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo $submission->id; ?></td>
                                    <td><strong><?php echo esc_html($submission->candidate_name); ?></strong></td>
                                    <td><?php echo esc_html($submission->candidate_email); ?></td>
                                    <td><?php echo esc_html($submission->candidate_phone); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($submission->interview_date)); ?></td>
                                    <td><strong><?php echo number_format($submission->total_score, 2); ?></strong></td>
                                    <td><?php echo $this->get_risk_badge($submission->risk_level); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=psychometric-submissions&action=view&submission_id=' . $submission->id); ?>" class="button button-small">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="psychometric-pagination">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $paged,
                                'total' => $total_pages,
                                'prev_text' => '&laquo; Previous',
                                'next_text' => 'Next &raquo;'
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function view_submission_page($submission_id) {
        $submission = Psychometric_Database::get_submission_details($submission_id);
        if (!$submission) {
            wp_die('Submission not found');
        }
        
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">
                Submission Details
                <a href="<?php echo admin_url('admin.php?page=psychometric-submissions'); ?>" class="page-title-action">Back to List</a>
            </h1>
            
            <div class="psychometric-card">
                <div class="psychometric-result-header">
                    <div class="result-info">
                        <h2><?php echo esc_html($submission->candidate_name); ?></h2>
                        <p><strong>Email:</strong> <?php echo esc_html($submission->candidate_email); ?></p>
                        <p><strong>Phone:</strong> <?php echo esc_html($submission->candidate_phone); ?></p>
                        <p><strong>Interview Date:</strong> <?php echo date('F d, Y', strtotime($submission->interview_date)); ?></p>
                        <p><strong>Submitted:</strong> <?php echo date('F d, Y g:i A', strtotime($submission->submitted_at)); ?></p>
                    </div>
                    <div class="result-score">
                        <div class="score-circle <?php echo strtolower(str_replace(' ', '-', $submission->risk_level)); ?>">
                            <div class="score-value"><?php echo number_format($submission->total_score, 1); ?></div>
                            <div class="score-label">Score</div>
                        </div>
                        <div class="risk-badge-large">
                            <?php echo $this->get_risk_badge($submission->risk_level, true); ?>
                        </div>
                    </div>
                </div>
                
                <div class="risk-description-box">
                    <h3>Assessment Summary</h3>
                    <?php echo $this->get_risk_description($submission->risk_level); ?>
                </div>
                
                <h3>Answer Details</h3>
                <table class="wp-list-table widefat striped psychometric-table">
                    <thead>
                        <tr>
                            <th width="55%">Question</th>
                            <th width="12%">Polarity</th>
                            <th width="20%">Answer</th>
                            <th width="13%">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submission->answers as $answer): ?>
                            <tr>
                                <td><?php echo esc_html($answer->question_text); ?></td>
                                <td><span class="psychometric-badge badge-<?php echo $answer->polarity; ?>"><?php echo ucfirst($answer->polarity); ?></span></td>
                                <td><?php echo $this->get_answer_pill($answer->answer_value); ?></td>
                                <td><strong><?php echo number_format($answer->normalized_score, 2); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        $settings = get_option('psychometric_settings', array(
            'admin_emails' => array(get_option('admin_email')),
            'primary_color' => '#21BECA'
        ));
        
        ?>
        <div class="wrap psychometric-admin-wrap">
            <h1 class="psychometric-page-title">Settings</h1>
            
            <?php if (isset($_GET['message']) && $_GET['message'] === 'settings_saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully!</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('psychometric_save_settings'); ?>
                <input type="hidden" name="action" value="psychometric_save_settings">
                
                <div class="psychometric-card">
                    <h2>Email Notifications</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="admin_emails">Admin Email Addresses</label></th>
                            <td>
                                <textarea name="admin_emails" id="admin_emails" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $settings['admin_emails'])); ?></textarea>
                                <p class="description">Enter one email address per line. These emails will receive notifications when someone submits the test.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h2>Appearance</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="primary_color">Primary Color</label></th>
                            <td>
                                <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>">
                                <p class="description">Choose the primary color for the test interface (default: #21BECA)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary button-hero" value="Save Settings">
                    </p>
                </div>
            </form>
            
            <div class="psychometric-card">
                <h2>Shortcodes</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Display Test Form</th>
                        <td>
                            <code>[psychometric_test]</code>
                            <p class="description">Use this shortcode to display the test on any page or post</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Display Submissions (Password Protected)</th>
                        <td>
                            <code>[psychometric_submissions password="your_password_here"]</code>
                            <p class="description">Display submissions list with password protection. Replace "your_password_here" with your desired password.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    public function save_settings() {
        check_admin_referer('psychometric_save_settings');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $admin_emails_raw = sanitize_textarea_field($_POST['admin_emails']);
        $admin_emails = array_filter(array_map('trim', explode("\n", $admin_emails_raw)));
        $primary_color = sanitize_hex_color($_POST['primary_color']);
        
        update_option('psychometric_settings', array(
            'admin_emails' => $admin_emails,
            'primary_color' => $primary_color
        ));
        
        wp_redirect(admin_url('admin.php?page=psychometric-settings&message=settings_saved'));
        exit;
    }
    
    private function get_message($code) {
        $messages = array(
            'step_created' => 'Step created successfully!',
            'step_updated' => 'Step updated successfully!',
            'step_deleted' => 'Step deleted successfully!'
        );
        
        return isset($messages[$code]) ? $messages[$code] : '';
    }
    
    private function get_risk_badge($risk_level, $large = false) {
        $badges = array(
            'Safe' => '<span class="psychometric-badge badge-safe' . ($large ? ' badge-large' : '') . '">✅ Safe</span>',
            'Acceptable' => '<span class="psychometric-badge badge-acceptable' . ($large ? ' badge-large' : '') . '">⚖️ Acceptable</span>',
            'Risk' => '<span class="psychometric-badge badge-risk' . ($large ? ' badge-large' : '') . '">⚠️ Risk</span>',
            'Harmful' => '<span class="psychometric-badge badge-harmful' . ($large ? ' badge-large' : '') . '">❌ Harmful</span>'
        );
        
        return isset($badges[$risk_level]) ? $badges[$risk_level] : $risk_level;
    }
    
    private function get_answer_label($value) {
        $labels = array(
            1 => 'Strongly Agree',
            2 => 'Agree',
            3 => 'Slightly Agree',
            4 => 'Neutral',
            5 => 'Slightly Disagree',
            6 => 'Disagree',
            7 => 'Strongly Disagree'
        );
        
        return isset($labels[$value]) ? $labels[$value] : $value;
    }
    
    private function get_answer_pill($value) {
        $answers = array(
            1 => array('label' => 'Strongly Agree', 'class' => 'answer-pill-1'),
            2 => array('label' => 'Agree', 'class' => 'answer-pill-2'),
            3 => array('label' => 'Slightly Agree', 'class' => 'answer-pill-3'),
            4 => array('label' => 'Neutral', 'class' => 'answer-pill-4'),
            5 => array('label' => 'Slightly Disagree', 'class' => 'answer-pill-5'),
            6 => array('label' => 'Disagree', 'class' => 'answer-pill-6'),
            7 => array('label' => 'Strongly Disagree', 'class' => 'answer-pill-7')
        );
        
        if (isset($answers[$value])) {
            return '<span class="answer-pill ' . $answers[$value]['class'] . '">' . esc_html($answers[$value]['label']) . '</span>';
        }
        
        return esc_html($value);
    }
    
    private function get_risk_description($risk_level) {
        $descriptions = array(
            'Safe' => '
                <div class="risk-box risk-safe">
                    <h4><span class="risk-icon">✅</span> Safe - Recommended</h4>
                    <ul>
                        <li>Positive, stable personality traits</li>
                        <li>Low risk of counterproductive behavior</li>
                        <li>Suitable for workplace integration</li>
                    </ul>
                </div>
            ',
            'Acceptable' => '
                <div class="risk-box risk-acceptable">
                    <h4><span class="risk-icon">⚖️</span> Acceptable / Moderate Risk</h4>
                    <ul>
                        <li>Some areas of concern (e.g., stress handling, adaptability)</li>
                        <li>Manageable with guidance or training</li>
                        <li>Not harmful but needs monitoring</li>
                    </ul>
                </div>
            ',
            'Risk' => '
                <div class="risk-box risk-warning">
                    <h4><span class="risk-icon">⚠️</span> Risk / Concerning</h4>
                    <ul>
                        <li>Traits showing potential absenteeism, lack of responsibility, or conflict-prone behavior</li>
                        <li>Requires corrective action or counseling</li>
                        <li>Could affect performance if unchecked</li>
                    </ul>
                </div>
            ',
            'Harmful' => '
                <div class="risk-box risk-harmful">
                    <h4><span class="risk-icon">❌</span> Harmful / High Risk</h4>
                    <ul>
                        <li>Strong negative indicators (dishonesty, aggression, lack of accountability)</li>
                        <li>Unsafe for team harmony or organizational culture</li>
                        <li>Not recommended for sensitive roles</li>
                    </ul>
                </div>
            '
        );
        
        return isset($descriptions[$risk_level]) ? $descriptions[$risk_level] : '';
    }
}