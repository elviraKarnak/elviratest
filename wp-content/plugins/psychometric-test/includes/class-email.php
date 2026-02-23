<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Psychometric_Email {
    
    public static function send_notification($submission_data, $submission_id) {
        $settings = get_option('psychometric_settings', array(
            'admin_emails' => array(get_option('admin_email'))
        ));
        
        $admin_emails = $settings['admin_emails'];
        
        if (empty($admin_emails)) {
            return false;
        }
        
        $subject = 'New Psychometric Test Submission - ' . $submission_data['name'];
        
        $message = self::get_email_template($submission_data, $submission_id);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        foreach ($admin_emails as $email) {
            wp_mail(trim($email), $subject, $message, $headers);
        }
        
        return true;
    }
    
    private static function get_email_template($data, $submission_id) {
        $view_url = admin_url('admin.php?page=psychometric-submissions&action=view&submission_id=' . $submission_id);
        
        $risk_color = self::get_risk_color($data['risk_level']);
        $risk_icon = self::get_risk_icon($data['risk_level']);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #21BECA 0%, #1a959e 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }
                .email-body {
                    padding: 30px 20px;
                }
                .info-section {
                    background: #f9f9f9;
                    border-radius: 6px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    padding: 10px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .info-label {
                    font-weight: 600;
                    width: 40%;
                    color: #555;
                }
                .info-value {
                    width: 60%;
                    color: #333;
                }
                .score-section {
                    text-align: center;
                    padding: 30px 20px;
                    background: #f9f9f9;
                    border-radius: 6px;
                    margin: 20px 0;
                }
                .score-circle {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    background: <?php echo $risk_color; ?>;
                    color: white;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 20px;
                    font-size: 36px;
                    font-weight: bold;
                }
                .score-label {
                    font-size: 14px;
                    font-weight: normal;
                    margin-top: 5px;
                }
                .risk-badge {
                    display: inline-block;
                    padding: 10px 20px;
                    border-radius: 20px;
                    background: <?php echo $risk_color; ?>;
                    color: white;
                    font-weight: 600;
                    font-size: 16px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background: #21BECA;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    margin-top: 20px;
                }
                .email-footer {
                    background: #f5f5f5;
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>New Test Submission Received</h1>
                </div>
                
                <div class="email-body">
                    <p>A new psychometric test has been completed. Here are the details:</p>
                    
                    <div class="info-section">
                        <h3 style="margin-top: 0;">Candidate Information</h3>
                        <div class="info-row">
                            <div class="info-label">Name:</div>
                            <div class="info-value"><?php echo esc_html($data['name']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo esc_html($data['email']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo esc_html($data['phone']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Interview Date:</div>
                            <div class="info-value"><?php echo date('F d, Y', strtotime($data['interview_date'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="score-section">
                        <div class="score-circle">
                            <?php echo number_format($data['total_score'], 1); ?>
                            <div class="score-label">Score</div>
                        </div>
                        <div class="risk-badge">
                            <?php echo $risk_icon . ' ' . $data['risk_level']; ?>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="<?php echo $view_url; ?>" class="button">View Full Details</a>
                    </div>
                </div>
                
                <div class="email-footer">
                    <p>This is an automated notification from <?php echo get_bloginfo('name'); ?></p>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private static function get_risk_color($risk_level) {
        $colors = array(
            'Safe' => '#22c55e',
            'Acceptable' => '#3b82f6',
            'Risk' => '#f59e0b',
            'Harmful' => '#ef4444'
        );
        
        return isset($colors[$risk_level]) ? $colors[$risk_level] : '#666';
    }
    
    private static function get_risk_icon($risk_level) {
        $icons = array(
            'Safe' => '✅',
            'Acceptable' => '⚖️',
            'Risk' => '⚠️',
            'Harmful' => '❌'
        );
        
        return isset($icons[$risk_level]) ? $icons[$risk_level] : '';
    }
}
