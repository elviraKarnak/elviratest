<?php
/**
 * Scoring Logic
 */

if (!defined('ABSPATH')) exit;

class CAS_Scoring {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function calculate_score($answers) {
        $total_score = 0;
        $max_possible = 0;
        
        // Answer values
        $answer_values = array(
            'strongly_agree' => 5,
            'agree' => 4,
            'neutral' => 3,
            'disagree' => 2,
            'strongly_disagree' => 1
        );
        
        foreach ($answers as $question_id => $answer) {
            $category = get_post_meta($question_id, 'cas_category', true);
            $answer_value = $answer_values[$answer];
            
            // Scoring logic based on category
            switch ($category) {
                case 'positive':
                    // Positive questions: higher agreement = higher score
                    $points = ($answer_value - 3) * 5; // -10 to +10
                    $total_score += $points;
                    $max_possible += 10;
                    break;
                    
                case 'negative':
                    // Negative questions: higher disagreement = higher score
                    $points = (3 - $answer_value) * 5; // +10 to -10
                    $total_score += $points;
                    $max_possible += 10;
                    break;
                    
                case 'moderate':
                    // Moderate questions: balanced approach
                    $deviation = abs($answer_value - 3);
                    $points = ($deviation <= 1) ? 5 : 0;
                    $total_score += $points;
                    $max_possible += 5;
                    break;
            }
        }
        
        // Normalize to 0-100 scale
        $normalized_score = $max_possible > 0 ? (($total_score + $max_possible) / ($max_possible * 2)) * 100 : 0;
        $normalized_score = max(0, min(100, round($normalized_score)));
        
        // Get rating
        $settings = get_option('cas_settings');
        $rating = $this->get_rating($normalized_score, $settings);
        
        return array(
            'score' => $normalized_score,
            'rating' => $rating
        );
    }
    
    private function get_rating($score, $settings) {
        if ($score >= $settings['safe_threshold']) {
            return 'safe';
        } elseif ($score >= $settings['acceptable_threshold']) {
            return 'acceptable';
        } elseif ($score >= $settings['risk_threshold']) {
            return 'risk';
        } else {
            return 'harmful';
        }
    }
}
