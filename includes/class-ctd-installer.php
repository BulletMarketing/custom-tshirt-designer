<?php
/**
 * Custom T-Shirt Designer Installer
 * Handles database table creation and updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTD_Installer {
    
    /**
     * Install/Update database tables
     */
    public static function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create designs table
        $designs_table = $wpdb->prefix . 'ctd_designs';
        $designs_sql = "CREATE TABLE $designs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            design_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // Create orders table
        $orders_table = $wpdb->prefix . 'ctd_orders';
        $orders_sql = "CREATE TABLE $orders_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            design_id mediumint(9) DEFAULT NULL,
            customization_data longtext DEFAULT NULL,
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY design_id (design_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Create inventory table
        $inventory_table = $wpdb->prefix . 'ctd_inventory';
        $inventory_sql = "CREATE TABLE $inventory_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            color_key varchar(100) NOT NULL,
            size varchar(50) NOT NULL,
            quantity int(11) DEFAULT 0,
            reserved_quantity int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_color_size (product_id, color_key, size),
            KEY product_id (product_id),
            KEY color_key (color_key),
            KEY size (size)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($designs_sql);
        dbDelta($orders_sql);
        dbDelta($inventory_sql);
        
        // Run migration for existing products
        self::migrate_existing_products();
        
        // Update version
        update_option('ctd_db_version', CTD_VERSION);
    }
    
    /**
     * Migrate existing products to add size_type field
     */
    private static function migrate_existing_products() {
        // Get all products that have CTD data but no size_type set
        $products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_ctd_enabled',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'key' => '_ctd_size_type',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        foreach ($products as $product) {
            // Default to men's sizes for existing products
            update_post_meta($product->ID, '_ctd_size_type', 'mens');
            
            // Migrate existing size data if needed
            self::migrate_product_sizes($product->ID);
        }
    }
    
    /**
     * Migrate individual product sizes to new format
     */
    private static function migrate_product_sizes($product_id) {
        $existing_colors = get_post_meta($product_id, '_ctd_color_keys', true);
        $existing_sizes = get_post_meta($product_id, '_ctd_color_sizes', true);
        $existing_inventory = get_post_meta($product_id, '_ctd_inventory', true);
        
        if (!empty($existing_colors) && !empty($existing_sizes)) {
            // Check if we need to migrate size format
            $needs_migration = false;
            
            foreach ($existing_sizes as $color_key => $sizes) {
                if (is_array($sizes)) {
                    foreach ($sizes as $size) {
                        // Check if size looks like old format that needs conversion
                        if (self::needs_size_conversion($size)) {
                            $needs_migration = true;
                            break 2;
                        }
                    }
                }
            }
            
            if ($needs_migration) {
                // Perform size migration while preserving inventory data
                $migrated_sizes = array();
                $migrated_inventory = array();
                
                foreach ($existing_sizes as $color_key => $sizes) {
                    if (is_array($sizes)) {
                        $migrated_sizes[$color_key] = array();
                        
                        foreach ($sizes as $size) {
                            $new_size = self::convert_size_format($size);
                            $migrated_sizes[$color_key][] = $new_size;
                            
                            // Migrate inventory data
                            if (isset($existing_inventory[$color_key][$size])) {
                                $migrated_inventory[$color_key][$new_size] = $existing_inventory[$color_key][$size];
                            }
                        }
                    }
                }
                
                // Update with migrated data
                update_post_meta($product_id, '_ctd_color_sizes', $migrated_sizes);
                update_post_meta($product_id, '_ctd_inventory', $migrated_inventory);
                
                // Log migration
                error_log("CTD: Migrated sizes for product ID: $product_id");
            }
        }
    }
    
    /**
     * Check if a size needs conversion
     */
    private static function needs_size_conversion($size) {
        // Add logic here if you have specific old formats that need conversion
        // For now, we'll assume all existing sizes are compatible
        return false;
    }
    
    /**
     * Convert size from old format to new format
     */
    private static function convert_size_format($size) {
        // Add conversion logic here if needed
        // For now, return as-is since we're not changing the size format
        return $size;
    }
    
    /**
     * Check database status
     */
    public static function check_database_status() {
        global $wpdb;
        
        $status = array();
        
        // Check tables
        $tables = array(
            'designs' => $wpdb->prefix . 'ctd_designs',
            'orders' => $wpdb->prefix . 'ctd_orders',
            'inventory' => $wpdb->prefix . 'ctd_inventory'
        );
        
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $status['tables'][$name] = array(
                'exists' => $exists,
                'name' => $table
            );
            
            if ($exists) {
                // Check table structure
                $columns = $wpdb->get_results("DESCRIBE $table");
                $status['tables'][$name]['columns'] = $columns;
            }
        }
        
        // Check version
        $db_version = get_option('ctd_db_version', '0.0.0');
        $status['version'] = array(
            'current' => CTD_VERSION,
            'database' => $db_version,
            'needs_update' => version_compare($db_version, CTD_VERSION, '<')
        );
        
        return $status;
    }
    
    /**
     * Repair/Update database
     */
    public static function repair_database() {
        try {
            self::install();
            return array(
                'success' => true,
                'message' => 'Database successfully updated/repaired.'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Database repair failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get database statistics
     */
    public static function get_database_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Count designs
        $designs_table = $wpdb->prefix . 'ctd_designs';
        $stats['designs'] = $wpdb->get_var("SELECT COUNT(*) FROM $designs_table");
        
        // Count orders
        $orders_table = $wpdb->prefix . 'ctd_orders';
        $stats['orders'] = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table");
        
        // Count inventory items
        $inventory_table = $wpdb->prefix . 'ctd_inventory';
        $stats['inventory_items'] = $wpdb->get_var("SELECT COUNT(*) FROM $inventory_table");
        
        // Count CTD-enabled products
        $stats['ctd_products'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_ctd_enabled' 
            AND meta_value = 'yes'
        ");
        
        return $stats;
    }
    
    /**
     * Clean up orphaned data
     */
    public static function cleanup_orphaned_data() {
        global $wpdb;
        
        $cleaned = array();
        
        // Clean up designs for deleted products
        $designs_table = $wpdb->prefix . 'ctd_designs';
        $deleted_designs = $wpdb->query("
            DELETE d FROM $designs_table d
            LEFT JOIN {$wpdb->posts} p ON d.product_id = p.ID
            WHERE p.ID IS NULL
        ");
        $cleaned['designs'] = $deleted_designs;
        
        // Clean up orders for deleted orders
        $orders_table = $wpdb->prefix . 'ctd_orders';
        $deleted_orders = $wpdb->query("
            DELETE o FROM $orders_table o
            LEFT JOIN {$wpdb->posts} p ON o.order_id = p.ID
            WHERE p.ID IS NULL
        ");
        $cleaned['orders'] = $deleted_orders;
        
        // Clean up inventory for deleted products
        $inventory_table = $wpdb->prefix . 'ctd_inventory';
        $deleted_inventory = $wpdb->query("
            DELETE i FROM $inventory_table i
            LEFT JOIN {$wpdb->posts} p ON i.product_id = p.ID
            WHERE p.ID IS NULL
        ");
        $cleaned['inventory'] = $deleted_inventory;
        
        return $cleaned;
    }
}