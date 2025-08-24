<?php
/**
 * Plugin Name: Custom T-Shirt Designer
 * Description: A comprehensive WooCommerce extension for custom t-shirt design
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Just define constants - nothing else
define('CTD_VERSION', '1.0.0');
define('CTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Simple test function
function ctd_test_function() {
    // Do nothing - just test if plugin loads
}

// Hook the test function
add_action('init', 'ctd_test_function');