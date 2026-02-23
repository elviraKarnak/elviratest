<?php
/**
 * Plugin Activator
 * Creates database tables on plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class DPS_Activator {
    
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Surveys table
        $table_surveys = $wpdb->prefix . 'dps_surveys';
        $sql_surveys = "CREATE TABLE $table_surveys (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Steps table (Multi-step form)
        $table_steps = $wpdb->prefix . 'dps_steps';
        $sql_steps = "CREATE TABLE $table_steps (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            survey_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            step_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY survey_id (survey_id)
        ) $charset_collate;";
        
        // Questions table
        $table_questions = $wpdb->prefix . 'dps_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            step_id bigint(20) NOT NULL,
            question_code varchar(50),
            question_text text NOT NULL,
            question_type varchar(20) NOT NULL,
            is_required tinyint(1) DEFAULT 1,
            conditional_question_id bigint(20) DEFAULT NULL,
            conditional_answer text,
            question_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY step_id (step_id),
            KEY conditional_question_id (conditional_question_id)
        ) $charset_collate;";
        
        // Question Options table
        $table_options = $wpdb->prefix . 'dps_question_options';
        $sql_options = "CREATE TABLE $table_options (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            question_id bigint(20) NOT NULL,
            option_text varchar(255) NOT NULL,
            option_value varchar(255) NOT NULL,
            option_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        // Districts table
        $table_districts = $wpdb->prefix . 'dps_districts';
        $sql_districts = "CREATE TABLE $table_districts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Loksabha table
        $table_loksabha = $wpdb->prefix . 'dps_loksabha';
        $sql_loksabha = "CREATE TABLE $table_loksabha (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            district_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            PRIMARY KEY (id),
            KEY district_id (district_id)
        ) $charset_collate;";
        
        // Assembly table
        $table_assembly = $wpdb->prefix . 'dps_assembly';
        $sql_assembly = "CREATE TABLE $table_assembly (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            loksabha_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            PRIMARY KEY (id),
            KEY loksabha_id (loksabha_id)
        ) $charset_collate;";
        
        // Submissions table
        $table_submissions = $wpdb->prefix . 'dps_submissions';
        $sql_submissions = "CREATE TABLE $table_submissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            survey_id bigint(20) NOT NULL,
            respondent_name varchar(255),
            district_id bigint(20),
            loksabha_id bigint(20),
            assembly_id bigint(20),
            ip_address varchar(45),
            user_agent text,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY survey_id (survey_id),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";
        
        // Submission Answers table
        $table_answers = $wpdb->prefix . 'dps_submission_answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            submission_id bigint(20) NOT NULL,
            question_id bigint(20) NOT NULL,
            answer_text text,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        // Execute all table creations
        dbDelta($sql_surveys);
        dbDelta($sql_steps);
        dbDelta($sql_questions);
        dbDelta($sql_options);
        dbDelta($sql_districts);
        dbDelta($sql_loksabha);
        dbDelta($sql_assembly);
        dbDelta($sql_submissions);
        dbDelta($sql_answers);
        
        // Insert sample districts (West Bengal)
        self::insert_sample_locations($wpdb);
        
        // Set plugin version
        update_option('dps_version', DPS_VERSION);
        update_option('dps_installed', time());
    }
    
    private static function insert_sample_locations($wpdb) {
        $table_districts = $wpdb->prefix . 'dps_districts';
        $table_loksabha = $wpdb->prefix . 'dps_loksabha';
        $table_assembly = $wpdb->prefix . 'dps_assembly';
        
        // Check if already populated
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_districts");
        if ($count > 0) {
            return;
        }
        
        // Sample West Bengal locations
        $locations = [
            'Coochbehar' => [
                'Coochbehar' => ['Mathabhanga', 'Sitai', 'Cooch Behar Uttar', 'Cooch Behar Dakshin'],
                'Alipurduar' => ['Kumargram', 'Kalchini', 'Alipurduar', 'Falakata']
            ],
            'Jalpaiguri' => [
                'Jalpaiguri' => ['Jalpaiguri', 'Rajganj', 'Dabgram-Phulbari', 'Mal'],
                'Darjeeling' => ['Darjeeling', 'Kurseong', 'Matigara-Naxalbari', 'Siliguri']
            ],
            'Kolkata' => [
                'Kolkata Uttar' => ['Burtolla', 'Shyampukur', 'Kolkata Port', 'Kashipur-Belgachia'],
                'Kolkata Dakshin' => ['Behala Purba', 'Behala Paschim', 'Tollygunge', 'Kasba']
            ],
            'Howrah' => [
                'Howrah' => ['Howrah Uttar', 'Howrah Madhya', 'Shibpur', 'Howrah Dakshin'],
                'Uluberia' => ['Panchla', 'Uluberia Uttar', 'Uluberia Dakshin', 'Shyampur']
            ]
        ];
        
        foreach ($locations as $district_name => $loksabhas) {
            // Insert district
            $wpdb->insert($table_districts, ['name' => $district_name]);
            $district_id = $wpdb->insert_id;
            
            foreach ($loksabhas as $loksabha_name => $assemblies) {
                // Insert loksabha
                $wpdb->insert($table_loksabha, [
                    'district_id' => $district_id,
                    'name' => $loksabha_name
                ]);
                $loksabha_id = $wpdb->insert_id;
                
                foreach ($assemblies as $assembly_name) {
                    // Insert assembly
                    $wpdb->insert($table_assembly, [
                        'loksabha_id' => $loksabha_id,
                        'name' => $assembly_name
                    ]);
                }
            }
        }
    }
}
