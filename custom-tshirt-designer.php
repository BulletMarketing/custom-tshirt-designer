<?php
/**
 * Plugin Name: Custom T-Shirt Designer
 * Description: A comprehensive WooCommerce extension for custom t-shirt design with color options, sizes, decoration methods, and tier pricing.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: custom-tshirt-designer
 * Requires at least: 5.0
 * WC requires at least: 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('CTD_VERSION')) {
    define('CTD_VERSION', '1.0.0');
}
if (!defined('CTD_PLUGIN_DIR')) {
    define('CTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('CTD_PLUGIN_URL')) {
    define('CTD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Main plugin initialization
 */
function ctd_init_plugin() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'ctd_woocommerce_missing_notice');
        return;
    }
    
    // Load admin functionality
    if (is_admin()) {
        ctd_load_admin();
    }
    
    // Load frontend functionality
    ctd_load_frontend();
}

/**
 * Load admin functionality
 */
function ctd_load_admin() {
    // Load admin CSS and JS
    add_action('admin_enqueue_scripts', 'ctd_admin_scripts');
    
    // Try to load admin class if it exists
    $admin_file = CTD_PLUGIN_DIR . 'includes/class-ctd-admin.php';
    if (file_exists($admin_file)) {
        require_once $admin_file;
        if (class_exists('CTD_Admin')) {
            new CTD_Admin();
        }
    }
}

/**
 * Load frontend functionality
 */
function ctd_load_frontend() {
    // Load frontend CSS and JS
    add_action('wp_enqueue_scripts', 'ctd_frontend_scripts');
}

/**
 * Admin scripts and styles
 */
function ctd_admin_scripts($hook) {
    // Only load on product pages
    if (!in_array($hook, array('post.php', 'post-new.php'))) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') {
        return;
    }
    
    // Load admin CSS
    $admin_css = CTD_PLUGIN_URL . 'assets/css/admin.css';
    wp_enqueue_style('ctd-admin-style', $admin_css, array(), CTD_VERSION);
    
    // Load admin JS
    $admin_js = CTD_PLUGIN_URL . 'assets/js/admin.js';
    wp_enqueue_script('ctd-admin-script', $admin_js, array('jquery', 'wp-color-picker'), CTD_VERSION, true);
    
    // Load color picker
    wp_enqueue_style('wp-color-picker');
}

/**
 * Frontend scripts and styles
 */
function ctd_frontend_scripts() {
    // Only load on product pages
    if (!is_product()) {
        return;
    }
    
    // Load frontend CSS
    $frontend_css = CTD_PLUGIN_URL . 'assets/css/frontend.css';
    if (file_exists(str_replace(CTD_PLUGIN_URL, CTD_PLUGIN_DIR, $frontend_css))) {
        wp_enqueue_style('ctd-frontend-style', $frontend_css, array(), CTD_VERSION);
    }
    
    // Load frontend JS
    $frontend_js = CTD_PLUGIN_URL . 'assets/js/frontend.js';
    if (file_exists(str_replace(CTD_PLUGIN_URL, CTD_PLUGIN_DIR, $frontend_js))) {
        wp_enqueue_script('ctd-frontend-script', $frontend_js, array('jquery'), CTD_VERSION, true);
        
        // Localize script
        wp_localize_script('ctd-frontend-script', 'ctd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ctd_nonce')
        ));
    }
}

/**
 * WooCommerce missing notice
 */
function ctd_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Custom T-Shirt Designer</strong> requires WooCommerce to be installed and active.</p>
    </div>
    <?php
}

/**
 * Plugin activation
 */
function ctd_activate() {
    // Basic activation - just flush rewrite rules
    flush_rewrite_rules();
    
    // Try to run installer if it exists
    $installer_file = CTD_PLUGIN_DIR . 'includes/class-ctd-installer.php';
    if (file_exists($installer_file)) {
        require_once $installer_file;
        if (class_exists('CTD_Installer')) {
            CTD_Installer::install();
        }
    }
}

/**
 * Plugin deactivation
 */
function ctd_deactivate() {
    flush_rewrite_rules();
}

// Hook everything up
add_action('plugins_loaded', 'ctd_init_plugin');
register_activation_hook(__FILE__, 'ctd_activate');
register_deactivation_hook(__FILE__, 'ctd_deactivate');