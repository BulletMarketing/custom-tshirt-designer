<?php
/**
 * Debug class for Custom T-Shirt Designer
 */
class CTD_Debug {
    
    /**
     * Debug log enabled
     */
    private static $enabled = false;
    
    /**
     * Debug log file
     */
    private static $log_file = '';
    
    /**
     * Initialize debugging
     */
    public static function init() {
        // Check if debugging is enabled
        self::$enabled = get_option('ctd_debug_enabled', false);
        
        if (self::$enabled) {
            // Set log file
            $upload_dir = wp_upload_dir();
            self::$log_file = $upload_dir['basedir'] . '/ctd-debug.log';
            
            // Create log file if it doesn't exist
            if (!file_exists(self::$log_file)) {
                file_put_contents(self::$log_file, "=== Custom T-Shirt Designer Debug Log ===\n\n");
            }
        }
    }
    
    /**
     * Log message
     */
    public static function log($message, $type = 'info') {
        if (!self::$enabled) {
            return;
        }
        
        // Format message
        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] [{$type}] {$message}\n";
        
        // Write to log file
        file_put_contents(self::$log_file, $formatted_message, FILE_APPEND);
        
        // Also log to WordPress debug log if available
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("CTD: {$type} - {$message}");
        }
    }
    
    /**
     * Log variable
     */
    public static function log_var($var, $label = '', $type = 'info') {
        if (!self::$enabled) {
            return;
        }
        
        // Format variable
        $output = print_r($var, true);
        $message = $label ? "{$label}: {$output}" : $output;
        
        // Log message
        self::log($message, $type);
    }
    
    /**
     * Enable debugging
     */
    public static function enable() {
        update_option('ctd_debug_enabled', true);
        self::$enabled = true;
        
        // Set log file
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/ctd-debug.log';
        
        // Create log file
        file_put_contents(self::$log_file, "=== Custom T-Shirt Designer Debug Log ===\n\n");
        
        return true;
    }
    
    /**
     * Disable debugging
     */
    public static function disable() {
        update_option('ctd_debug_enabled', false);
        self::$enabled = false;
        
        return true;
    }
    
    /**
     * Get log file content
     */
    public static function get_log() {
        if (!self::$enabled || !file_exists(self::$log_file)) {
            return '';
        }
        
        return file_get_contents(self::$log_file);
    }
    
    /**
     * Clear log file
     */
    public static function clear_log() {
        if (!self::$enabled || !file_exists(self::$log_file)) {
            return false;
        }
        
        file_put_contents(self::$log_file, "=== Custom T-Shirt Designer Debug Log ===\n\n");
        
        return true;
    }
    
    /**
     * Check if debugging is enabled
     */
    public static function is_enabled() {
        return self::$enabled;
    }
    
    /**
     * Check database tables
     */
    public static function check_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'ctd_product_configs',
            $wpdb->prefix . 'ctd_designs',
            $wpdb->prefix . 'ctd_order_meta'
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            $results[$table] = array(
                'exists' => $table_exists,
                'records' => 0,
                'columns' => array()
            );
            
            if ($table_exists) {
                // Count records
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $results[$table]['records'] = intval($count);
                
                // Get columns
                $columns = $wpdb->get_results("DESCRIBE $table", ARRAY_A);
                foreach ($columns as $column) {
                    $results[$table]['columns'][] = $column['Field'];
                }
            }
        }
        
        return $results;
    }
}

// Initialize debug class
add_action('init', array('CTD_Debug', 'init'));
