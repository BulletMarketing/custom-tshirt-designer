<?php
/**
 * Plugin Name: Custom T-Shirt Designer
 * Plugin URI: https://yourwebsite.com/custom-tshirt-designer
 * Description: A comprehensive WooCommerce extension for custom t-shirt design with color options, sizes, decoration methods, and tier pricing.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-tshirt-designer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CTD_VERSION', '1.0.0');
define('CTD_PLUGIN_FILE', __FILE__);
define('CTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Custom T-Shirt Designer Class
 */
class Custom_TShirt_Designer {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Check if WooCommerce is active
        add_action('admin_init', array($this, 'check_woocommerce'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin classes
        $this->load_classes();
        
        // Initialize components
        $this->init_components();
        
        // Load assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Load plugin classes
     */
    private function load_classes() {
        // Core classes
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-installer.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-admin.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-ajax.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-product.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-cart.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-email.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-debug.php';
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-importer.php';
        
        // Migration and database update classes
        require_once CTD_PLUGIN_DIR . 'includes/class-ctd-migration-notice.php';
        require_once CTD_PLUGIN_DIR . 'db-update.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new CTD_Admin();
        }
        
        // Initialize AJAX handlers
        new CTD_Ajax();
        
        // Initialize product functionality
        new CTD_Product();
        
        // Initialize cart functionality
        new CTD_Cart();
        
        // Initialize email functionality
        new CTD_Email();
        
        // Initialize debug functionality (if enabled)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            new CTD_Debug();
        }
        
        // Initialize importer
        new CTD_Importer();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'custom-tshirt-designer',
            false,
            dirname(CTD_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load on product pages and cart
        if (is_product() || is_cart() || is_checkout()) {
            wp_enqueue_style(
                'ctd-frontend-style',
                CTD_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                CTD_VERSION
            );
            
            wp_enqueue_script(
                'ctd-frontend-script',
                CTD_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                CTD_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ctd-frontend-script', 'ctd_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ctd_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'custom-tshirt-designer'),
                    'error' => __('An error occurred. Please try again.', 'custom-tshirt-designer'),
                    'success' => __('Success!', 'custom-tshirt-designer')
                )
            ));
            
            // Load horizontal gallery scripts if needed
            if (get_theme_mod('blocksy_horizontal_gallery', false)) {
                wp_enqueue_style(
                    'ctd-blocksy-horizontal-gallery',
                    CTD_PLUGIN_URL . 'assets/css/blocksy-horizontal-gallery.css',
                    array(),
                    CTD_VERSION
                );
                
                wp_enqueue_script(
                    'ctd-blocksy-horizontal-gallery',
                    CTD_PLUGIN_URL . 'assets/js/blocksy-horizontal-gallery.js',
                    array('jquery'),
                    CTD_VERSION,
                    true
                );
            } else {
                wp_enqueue_style(
                    'ctd-horizontal-gallery',
                    CTD_PLUGIN_URL . 'assets/css/horizontal-gallery.css',
                    array(),
                    CTD_VERSION
                );
                
                wp_enqueue_script(
                    'ctd-horizontal-gallery',
                    CTD_PLUGIN_URL . 'assets/js/horizontal-gallery.js',
                    array('jquery'),
                    CTD_VERSION,
                    true
                );
            }
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on product edit pages and CTD settings pages
        if (in_array($hook, array('post.php', 'post-new.php')) || strpos($hook, 'ctd') !== false) {
            wp_enqueue_style(
                'ctd-admin-style',
                CTD_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CTD_VERSION
            );
            
            wp_enqueue_script(
                'ctd-admin-script',
                CTD_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-color-picker'),
                CTD_VERSION,
                true
            );
            
            // Enqueue WordPress color picker
            wp_enqueue_style('wp-color-picker');
            
            // Database management script
            if (strpos($hook, 'ctd') !== false) {
                wp_enqueue_script(
                    'ctd-admin-database',
                    CTD_PLUGIN_URL . 'assets/js/admin-database.js',
                    array('jquery'),
                    CTD_VERSION,
                    true
                );
                
                // Localize script for database operations
                wp_localize_script('ctd-admin-database', 'ctd_admin_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('ctd_admin_nonce'),
                    'strings' => array(
                        'confirm_repair' => __('Are you sure you want to repair the database? This action cannot be undone.', 'custom-tshirt-designer'),
                        'confirm_cleanup' => __('Are you sure you want to clean up orphaned data? This action cannot be undone.', 'custom-tshirt-designer'),
                        'processing' => __('Processing...', 'custom-tshirt-designer'),
                        'success' => __('Operation completed successfully.', 'custom-tshirt-designer'),
                        'error' => __('An error occurred. Please check the logs.', 'custom-tshirt-designer')
                    )
                ));
            }
        }
    }
    
    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(CTD_PLUGIN_BASENAME);
        }
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Custom T-Shirt Designer', 'custom-tshirt-designer'); ?></strong>
                <?php _e('requires WooCommerce to be installed and active.', 'custom-tshirt-designer'); ?>
                <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>">
                    <?php _e('Install WooCommerce', 'custom-tshirt-designer'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(CTD_PLUGIN_BASENAME);
            wp_die(
                __('Custom T-Shirt Designer requires WooCommerce to be installed and active.', 'custom-tshirt-designer'),
                __('Plugin Activation Error', 'custom-tshirt-designer'),
                array('back_link' => true)
            );
        }
        
        // Create/update database tables
        CTD_Installer::install();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Schedule migration check
        wp_schedule_single_event(time() + 30, 'ctd_check_migration_needed');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('ctd_check_migration_needed');
        wp_clear_scheduled_hook('ctd_run_size_type_migration');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'ctd_enable_debug' => false,
            'ctd_default_decoration_method' => 'screen_printing',
            'ctd_max_upload_size' => 5, // MB
            'ctd_allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'svg'),
            'ctd_enable_guest_designs' => true,
            'ctd_auto_save_designs' => true,
            'ctd_design_expiry_days' => 30
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Get plugin version
     */
    public static function get_version() {
        return CTD_VERSION;
    }
    
    /**
     * Get plugin path
     */
    public static function get_plugin_path() {
        return CTD_PLUGIN_DIR;
    }
    
    /**
     * Get plugin URL
     */
    public static function get_plugin_url() {
        return CTD_PLUGIN_URL;
    }
}

/**
 * Initialize the plugin
 */
function ctd_init() {
    return Custom_TShirt_Designer::get_instance();
}

// Start the plugin
ctd_init();

/**
 * Helper function to get main plugin instance
 */
function CTD() {
    return Custom_TShirt_Designer::get_instance();
}

// Declare WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});