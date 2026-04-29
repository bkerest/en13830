<?php
/**
 * Plugin Name: EN 13830 Curtain Wall Calculator
 * Plugin URI: https://yoursite.com
 * Description: Calculate required Ix values for curtain wall beams according to EN 13830 standard
 * Version: 1.0.0
 * Author: Elvial Digital Solutions
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EN13830_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EN13830_PLUGIN_PATH', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, 'en13830_create_profiles_table');

function en13830_create_profiles_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'en13830_profiles';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        code VARCHAR(50) NOT NULL,
        type VARCHAR(50) NOT NULL,
        system VARCHAR(50) NOT NULL,
        image_url TEXT,
        width FLOAT,
        depth FLOAT,
        weight FLOAT,
        ix FLOAT,
        iy FLOAT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}

class EN13830Calculator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('en13830_calculator', array($this, 'display_calculator'));
        add_action('wp_ajax_calculate_ix', array($this, 'ajax_calculate_ix'));
        add_action('wp_ajax_nopriv_calculate_ix', array($this, 'ajax_calculate_ix'));
    }
    
    public function init() {
        // Include required files
        require_once EN13830_PLUGIN_PATH . 'includes/class-calculator.php';
        require_once EN13830_PLUGIN_PATH . 'includes/class-admin.php';
        require_once EN13830_PLUGIN_PATH . 'includes/ajax-get-profile-suggestions.php'; 
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('en13830-calculator', EN13830_PLUGIN_URL . 'assets/css/calculator.css', array(), '1.0.0');
        wp_enqueue_script('en13830-calculator', EN13830_PLUGIN_URL . 'assets/js/calculator.js', array('jquery'), '1.0.0', true);
        
        // Localize script for AJAX - FIXED
        wp_localize_script('en13830-calculator', 'en13830_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('en13830_nonce'),
            'debug' => true // Add debug flag
        ));
    }

    
    public function display_calculator($atts) {
        ob_start();
        include EN13830_PLUGIN_PATH . 'templates/calculator-form.php';
        return ob_get_clean();
    }
    
    public function ajax_calculate_ix() {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'en13830_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        try {
            $calculator = new EN13830_Calculator_Engine();
            $result = $calculator->calculate($_POST);
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error('Calculation error: ' . $e->getMessage());
        }
    }

}

// Initialize the plugin
new EN13830Calculator();
?>
