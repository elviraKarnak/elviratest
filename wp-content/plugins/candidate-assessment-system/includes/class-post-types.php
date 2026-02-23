<?php
/**
 * Custom Post Types
 */

if (!defined('ABSPATH')) exit;

class CAS_Post_Types {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    public function register_post_types() {
        // Questions Post Type
        register_post_type('cas_question', array(
            'labels' => array(
                'name' => 'Questions',
                'singular_name' => 'Question',
                'add_new' => 'Add New Question',
                'add_new_item' => 'Add New Question',
                'edit_item' => 'Edit Question',
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-list-view',
            'rewrite' => false,
        ));
        
        // Submissions Post Type
        register_post_type('cas_submission', array(
            'labels' => array(
                'name' => 'Submissions',
                'singular_name' => 'Submission',
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title'),
            'rewrite' => false,
        ));
    }
    
    public function register_taxonomies() {
        register_taxonomy('cas_category', 'cas_question', array(
            'labels' => array(
                'name' => 'Question Categories',
                'singular_name' => 'Category',
            ),
            'hierarchical' => true,
            'show_ui' => false,
            'public' => false,
        ));
    }
}
