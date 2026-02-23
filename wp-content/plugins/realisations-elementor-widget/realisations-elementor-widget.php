<?php
/**
 * Plugin Name: Realisations Elementor Widget
 * Description: Custom Elementor widget to display Realisations custom post type with category filtering and pagination
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: realisations-elementor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main Plugin Class
 */
final class Realisations_Elementor_Widget {
    
    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
    const MINIMUM_PHP_VERSION = '7.0';

    private static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return;
        }

        // Register widget
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Register widget styles
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'widget_styles']);
    }

    public function admin_notice_missing_elementor() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'realisations-elementor'),
            '' . esc_html__('Realisations Elementor Widget', 'realisations-elementor') . '',
            '' . esc_html__('Elementor', 'realisations-elementor') . ''
        );
        printf('%1$s', $message);
    }

    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'realisations-elementor'),
            '' . esc_html__('Realisations Elementor Widget', 'realisations-elementor') . '',
            '' . esc_html__('Elementor', 'realisations-elementor') . '',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        printf('%1$s', $message);
    }

    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'realisations-elementor'),
            '' . esc_html__('Realisations Elementor Widget', 'realisations-elementor') . '',
            '' . esc_html__('PHP', 'realisations-elementor') . '',
            self::MINIMUM_PHP_VERSION
        );
        printf('%1$s', $message);
    }

    public function register_widgets($widgets_manager) {
        require_once(__DIR__ . '/widgets/realisations-widget.php');
        $widgets_manager->register(new \Realisations_Widget());
    }

    public function widget_styles() {
        wp_register_style('realisations-widget-style', plugins_url('assets/css/realisations-style.css', __FILE__), [], self::VERSION);
        wp_enqueue_style('realisations-widget-style');
    }
}

Realisations_Elementor_Widget::instance();