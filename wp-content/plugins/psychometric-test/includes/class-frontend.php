<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Psychometric_Frontend {
    
    public function __construct() {
        add_shortcode('psychometric_test', array($this, 'render_test_form'));
        add_shortcode('psychometric_submissions', array($this, 'render_submissions_list'));
    }
    
    public function render_test_form($atts) {
        $steps = Psychometric_Database::get_all_steps();
        
        if (empty($steps)) {
            return '<p>No test available at the moment. Please check back later.</p>';
        }
        
        $settings = get_option('psychometric_settings', array('primary_color' => '#21BECA'));
        $primary_color = $settings['primary_color'];
        
        ob_start();
        ?>
        <div class="psychometric-test-container" data-primary-color="<?php echo esc_attr($primary_color); ?>">
            <div class="psychometric-test-wrapper">
                <!-- Progress Bar -->
                <div class="psychometric-progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="psychometric-progress-text">Step <span class="current-step">1</span> of <?php echo count($steps); ?></div>
                
                <!-- Steps Container -->
                <div class="psychometric-steps">
                    <?php foreach ($steps as $index => $step): 
                        $questions = Psychometric_Database::get_questions_by_step($step->id);
                        if (count($questions) < 1) continue; // Skip steps with no active questions
                    ?>
                        <div class="psychometric-step" data-step="<?php echo ($index + 1); ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                            <div class="step-header">
                                <h2><?php echo esc_html($step->step_title); ?></h2>
                                <p class="step-instructions">Please respond to each statement below. Select your level of agreement.</p>
                            </div>
                            
                            <div class="questions-list">
                                <?php foreach ($questions as $question): ?>
                                    <div class="question-block" data-question-id="<?php echo $question->id; ?>" data-polarity="<?php echo $question->polarity; ?>">
                                        <div class="question-text"><?php echo esc_html($question->question_text); ?></div>
                                        
                                        <div class="answer-options">
                                            <div class="option-labels">
                                                <span class="label-agree">Agree</span>
                                                <span class="label-disagree">Disagree</span>
                                            </div>
                                            <div class="circles-container">
                                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                                    <div class="circle-wrapper">
                                                        <input type="radio" 
                                                               name="question_<?php echo $question->id; ?>" 
                                                               id="q<?php echo $question->id; ?>_<?php echo $i; ?>" 
                                                               value="<?php echo $i; ?>" 
                                                               class="circle-input">
                                                        <label for="q<?php echo $question->id; ?>_<?php echo $i; ?>" 
                                                               class="circle-label" 
                                                               data-value="<?php echo $i; ?>">
                                                            <span class="checkmark">✓</span>
                                                        </label>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="step-navigation">
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn-prev">Previous</button>
                                <?php endif; ?>
                                
                                <?php if ($index < count($steps) - 1): ?>
                                    <button type="button" class="btn-next" disabled>Next</button>
                                <?php else: ?>
                                    <button type="button" class="btn-show-form" disabled>Continue to Submit</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Final Form Step -->
                    <div class="psychometric-step psychometric-final-step" data-step="final" style="display: none;">
                        <div class="step-header">
                            <h2>Your Information</h2>
                            <p class="step-instructions">Please provide your details to complete the assessment.</p>
                        </div>
                        
                        <form id="psychometric-final-form" class="candidate-info-form">
                            <div class="form-group">
                                <label for="candidate_name">Full Name <span class="required">*</span></label>
                                <input type="text" id="candidate_name" name="candidate_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="candidate_email">Email Address <span class="required">*</span></label>
                                <input type="email" id="candidate_email" name="candidate_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="candidate_phone">Phone Number <span class="required">*</span></label>
                                <input type="tel" id="candidate_phone" name="candidate_phone" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="interview_date">Interview Date <span class="required">*</span></label>
                                <input type="date" id="interview_date" name="interview_date" required>
                            </div>
                            
                            <div class="step-navigation">
                                <button type="button" class="btn-prev">Previous</button>
                                <button type="submit" class="btn-submit">Submit Assessment</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Success Message -->
                    <div class="psychometric-step psychometric-success" style="display: none;">
                        <div class="success-icon">✓</div>
                        <h2>Thank You!</h2>
                        <p>Your assessment has been submitted successfully.</p>
                        <p class="success-subtext">We will review your responses and get back to you soon.</p>
                    </div>
                    
                    <!-- Loading Overlay -->
                    <div class="psychometric-loading" style="display: none;">
                        <div class="loader"></div>
                        <p>Submitting your assessment...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_submissions_list($atts) {
        // Check if password is required
        $atts = shortcode_atts(array(
            'password' => ''
        ), $atts);
        
        // Password protection
        if (!empty($atts['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Check if password submitted
            if (isset($_POST['submissions_password'])) {
                if ($_POST['submissions_password'] === $atts['password']) {
                    $_SESSION['psychometric_auth'] = true;
                } else {
                    $error = 'Incorrect password. Please try again.';
                }
            }
            
            // Show password form if not authenticated
            if (!isset($_SESSION['psychometric_auth']) || !$_SESSION['psychometric_auth']) {
                ob_start();
                ?>
                <div class="psychometric-password-form">
                    <h3>Protected Content</h3>
                    <p>Please enter the password to view submissions.</p>
                    <?php if (isset($error)): ?>
                        <div class="error-message"><?php echo esc_html($error); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="password" name="submissions_password" placeholder="Enter Password" required>
                        <button type="submit">Access</button>
                    </form>
                </div>
                <?php
                return ob_get_clean();
            }
        }
        
        // Get submissions
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;
        $offset = ($paged - 1) * $per_page;
        
        $submissions = Psychometric_Database::get_submissions($per_page, $offset);
        $total = Psychometric_Database::get_total_submissions();
        $total_pages = ceil($total / $per_page);
        
        ob_start();
        ?>
        <div class="psychometric-submissions-frontend">
            <div class="submissions-header">
                <h2>Assessment Submissions</h2>
                <p>Total Submissions: <strong><?php echo $total; ?></strong></p>
            </div>
            
            <?php if (empty($submissions)): ?>
                <div class="no-submissions">
                    <p>No submissions found.</p>
                </div>
            <?php else: ?>
                <div class="submissions-table-wrapper">
                    <table class="submissions-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Interview Date</th>
                                <th>Score</th>
                                <th>Risk Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td data-label="Name"><strong><?php echo esc_html($submission->candidate_name); ?></strong></td>
                                    <td data-label="Email"><?php echo esc_html($submission->candidate_email); ?></td>
                                    <td data-label="Phone"><?php echo esc_html($submission->candidate_phone); ?></td>
                                    <td data-label="Interview Date"><?php echo date('M d, Y', strtotime($submission->interview_date)); ?></td>
                                    <td data-label="Score"><strong><?php echo number_format($submission->total_score, 1); ?></strong></td>
                                    <td data-label="Risk Level"><?php echo $this->get_risk_badge($submission->risk_level); ?></td>
                                    <td data-label="Action">
                                        <button class="btn-view-details" data-submission-id="<?php echo $submission->id; ?>" onclick="console.log('Button clicked directly!'); return false;">View Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="submissions-pagination">
                        <?php
                        $base_url = get_permalink();
                        for ($i = 1; $i <= $total_pages; $i++) {
                            $class = ($i === $paged) ? 'active' : '';
                            $url = add_query_arg('paged', $i, $base_url);
                            echo '<a href="' . esc_url($url) . '" class="page-number ' . $class . '">' . $i . '</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Logout button -->
            <?php if (!empty($atts['password'])): ?>
                <div class="submissions-logout">
                    <form method="post" action="<?php echo add_query_arg('logout', '1'); ?>">
                        <button type="submit" name="logout_submissions">Logout</button>
                    </form>
                </div>
                <?php
                if (isset($_GET['logout']) || isset($_POST['logout_submissions'])) {
                    unset($_SESSION['psychometric_auth']);
                    wp_redirect(get_permalink());
                    exit;
                }
                ?>
            <?php endif; ?>
        </div>
        
        <!-- Modal for viewing details -->
        <div id="submission-modal" class="psychometric-modal" style="display: none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <div class="modal-body">
                    <div class="modal-loading">
                        <div class="loader"></div>
                        <p>Loading details...</p>
                    </div>
                    <div class="modal-details" style="display: none;"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('Submissions shortcode loaded');
            console.log('Modal exists:', $('#submission-modal').length);
            console.log('View buttons count:', $('.btn-view-details').length);
            
            // Direct event binding as fallback
            $('.btn-view-details').each(function(index) {
                console.log('Button ' + index + ' found, ID:', $(this).data('submission-id'));
            });
            
            // Attach click handler directly
            $('.btn-view-details').on('click', function(e) {
                e.preventDefault();
                console.log('Direct click handler fired!');
                var submissionId = $(this).data('submission-id');
                console.log('Opening modal for submission:', submissionId);
                
                // Try to call the function if it exists
                if (typeof window.psychometricLoadSubmission === 'function') {
                    window.psychometricLoadSubmission(submissionId);
                } else {
                    console.error('psychometricLoadSubmission function not found!');
                    // Fallback: just open the modal
                    $('#submission-modal').fadeIn(300);
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function get_risk_badge($risk_level) {
        $badges = array(
            'Safe' => '<span class="risk-badge badge-safe">✅ Safe</span>',
            'Acceptable' => '<span class="risk-badge badge-acceptable">⚖️ Acceptable</span>',
            'Risk' => '<span class="risk-badge badge-risk">⚠️ Risk</span>',
            'Harmful' => '<span class="risk-badge badge-harmful">❌ Harmful</span>'
        );
        
        return isset($badges[$risk_level]) ? $badges[$risk_level] : $risk_level;
    }
}