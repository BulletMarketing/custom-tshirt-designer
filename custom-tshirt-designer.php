<?php
/**
* Plugin Name: Custom T-Shirt Designer
* Plugin URI: https://bulletmarketing.com.au/custom-tshirt-designer
* Description: A custom t-shirt designer plugin for WooCommerce
* Version: 1.3.10
* Author: Bullet Marketing
* Author URI: https://bulletmarketing.com.au
* Text Domain: custom-tshirt-designer
* Domain Path: /languages
* WC requires at least: 3.0.0
* WC tested up to: 7.0.0
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

// Define plugin constants
define('CTD_VERSION', '1.3.8');
define('CTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTD_MINIMUM_ORDER_QUANTITY', 20); // Define the minimum order quantity

// Include required files
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-installer.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-debug.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-admin.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-ajax.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-product.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-cart.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-email.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-importer.php';

// Activation hook
register_activation_hook(__FILE__, array('CTD_Installer', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('CTD_Installer', 'deactivate'));

// Initialize the plugin
function ctd_init() {
  // Check if WooCommerce is active
  if (!class_exists('WooCommerce')) {
     add_action('admin_notices', 'ctd_woocommerce_missing_notice');
     return;
  }
  
  // Initialize debug first so it's available for other components
  $debug = new CTD_Debug();
  $debug->init();
  
  // Initialize admin
  $admin = new CTD_Admin();
  $admin->init();
  
  // Initialize AJAX
  $ajax = new CTD_Ajax();
  $ajax->init();
  
  // Initialize product
  $product = new CTD_Product();
  $product->init();
  
  // Initialize cart
  $cart = new CTD_Cart();
  $cart->init();
  
  // Initialize email
  $email = new CTD_Email();
  $email->init();
  
  // Initialize importer
  $importer = new CTD_Importer();
  $importer->init();
  
  // Load text domain
  load_plugin_textdomain('custom-tshirt-designer', false, dirname(plugin_basename(__FILE__)) . '/languages');
  
  // Register frontend scripts and styles
  add_action('wp_enqueue_scripts', 'ctd_enqueue_scripts');
  
  // Add filter to change "Add to Cart" text
  add_filter('woocommerce_product_add_to_cart_text', 'ctd_change_add_to_cart_text', 10, 2);
  
  // Add filter to change "Add to Cart" URL
  add_filter('woocommerce_product_add_to_cart_url', 'ctd_change_add_to_cart_url', 10, 2);
}
add_action('plugins_loaded', 'ctd_init');

/**
* Show WooCommerce missing notice
*/
function ctd_woocommerce_missing_notice() {
  ?>
  <div class="error">
     <p><?php _e('Custom T-Shirt Designer requires WooCommerce to be installed and activated.', 'custom-tshirt-designer'); ?></p>
  </div>
  <?php
}

/**
* Enqueue frontend scripts and styles
*/
function ctd_enqueue_scripts() {
  // Only load on product pages
  if (!is_product()) {
     return;
  }
  
  // Enqueue styles
  wp_enqueue_style('ctd-frontend', CTD_PLUGIN_URL . 'assets/css/frontend.css', array(), CTD_VERSION);
  
  // Check if Blocksy theme is active
  $current_theme = wp_get_theme();
  $is_blocksy = ($current_theme->get('Name') === 'Blocksy' || $current_theme->get('Template') === 'blocksy');
  
  if ($is_blocksy) {
    // Enqueue Blocksy-specific horizontal gallery styles
    wp_enqueue_style('ctd-blocksy-horizontal-gallery', CTD_PLUGIN_URL . 'assets/css/blocksy-horizontal-gallery.css', array(), CTD_VERSION);
  } else {
    // Enqueue standard horizontal gallery styles
    wp_enqueue_style('ctd-horizontal-gallery', CTD_PLUGIN_URL . 'assets/css/horizontal-gallery.css', array(), CTD_VERSION);
  }
  
  // Enqueue scripts
  wp_enqueue_script('fabric', CTD_PLUGIN_URL . 'assets/js/fabric.min.js', array('jquery'), '5.2.1', true);
  wp_enqueue_script('ctd-frontend', CTD_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'fabric'), CTD_VERSION, true);
  
  // Enqueue the appropriate horizontal gallery script
  if ($is_blocksy) {
    wp_enqueue_script('ctd-blocksy-horizontal-gallery', CTD_PLUGIN_URL . 'assets/js/blocksy-horizontal-gallery.js', array('jquery'), CTD_VERSION, true);
  } else {
    wp_enqueue_script('ctd-horizontal-gallery', CTD_PLUGIN_URL . 'assets/js/horizontal-gallery.js', array('jquery'), CTD_VERSION, true);
  }
  
  // Localize script
  wp_localize_script('ctd-frontend', 'ctd_params', array(
     'ajax_url' => admin_url('admin-ajax.php'),
     'nonce' => wp_create_nonce('ctd-frontend-nonce'),
     'plugin_url' => CTD_PLUGIN_URL,
     'min_quantity' => CTD_MINIMUM_ORDER_QUANTITY,
     'i18n' => array(
        'upload_error' => __('Error uploading file. Please try again.', 'custom-tshirt-designer'),
        'invalid_file' => __('Invalid file type. Please upload an image file.', 'custom-tshirt-designer'),
        'confirm_remove' => __('Are you sure you want to remove this design?', 'custom-tshirt-designer'),
        'no_designs' => __('Please add at least one design before adding to cart.', 'custom-tshirt-designer'),
        'select_color' => __('Please select a color.', 'custom-tshirt-designer'),
        'select_size' => __('Please select a size.', 'custom-tshirt-designer'),
        'select_method' => __('Please select a decoration method.', 'custom-tshirt-designer'),
        'out_of_stock' => __('This color/size combination is out of stock.', 'custom-tshirt-designer')
     )
  ));
}

/**
 * Change "Add to Cart" text for products with t-shirt designer enabled
 */
function ctd_change_add_to_cart_text($text, $product) {
  // Check if product has t-shirt designer enabled
  $product_id = $product->get_id();
  
  // Get product configuration
  global $wpdb;
  $table_name = $wpdb->prefix . 'ctd_product_configs';
  $enabled = $wpdb->get_var($wpdb->prepare("SELECT enabled FROM $table_name WHERE product_id = %d", $product_id));
  
  // If enabled and product is in archive/category view, change text
  if ($enabled && !is_product()) {
    return __('Select Options', 'custom-tshirt-designer');
  }
  
  return $text;
}

/**
 * Change "Add to Cart" URL for products with t-shirt designer enabled
 */
function ctd_change_add_to_cart_url($url, $product) {
  // Check if product has t-shirt designer enabled
  $product_id = $product->get_id();
  
  // Get product configuration
  global $wpdb;
  $table_name = $wpdb->prefix . 'ctd_product_configs';
  $enabled = $wpdb->get_var($wpdb->prepare("SELECT enabled FROM $table_name WHERE product_id = %d", $product_id));
  
  // If enabled and product is in archive/category view, change URL to product page
  if ($enabled && !is_product()) {
    return get_permalink($product_id);
  }
  
  return $url;
}
