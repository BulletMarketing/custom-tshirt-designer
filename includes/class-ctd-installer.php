<?php
/**
 * Installer class for Custom T-Shirt Designer
 */
class CTD_Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Force table creation and log any errors
        $result = self::create_tables();
        if ($result === false) {
            error_log('CTD Error: Failed to create tables during activation');
        } else {
            error_log('CTD Success: Tables created during activation');
        }
        
        self::create_upload_directory();
        
        // Set version
        update_option('ctd_version', CTD_VERSION);
        
        // Set a flag to show a welcome message
        update_option('ctd_show_welcome', 1);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for product configurations
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            enabled tinyint(1) DEFAULT 0,
            product_type varchar(50) DEFAULT 'shirt',
            sizes longtext,
            colors longtext,
            decoration_methods longtext,
            setup_fees longtext,
            tier_pricing longtext,
            color_sizes longtext,
            inventory_enabled tinyint(1) DEFAULT 0,
            inventory longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Table for design uploads
        $table_name_designs = $wpdb->prefix . 'ctd_designs';
        
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name_designs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            order_item_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            position varchar(20) NOT NULL,
            file_path varchar(255) NOT NULL,
            decoration_method varchar(50),
            composite_image varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Table for order metadata
        $table_name_orders = $wpdb->prefix . 'ctd_order_meta';
        
        $sql .= "CREATE TABLE IF NOT EXISTS $table_name_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            order_item_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            colors longtext,
            sizes longtext,
            setup_fees decimal(10,2) DEFAULT 0,
            total_quantity int DEFAULT 0,
            discount_applied decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Use try-catch to prevent fatal errors
        try {
            // Execute each query separately to better identify issues
            $tables = array(
                $wpdb->prefix . 'ctd_product_configs',
                $wpdb->prefix . 'ctd_designs',
                $wpdb->prefix . 'ctd_order_meta'
            );
            
            // Split SQL into individual table creation statements
            $sql_statements = explode(';', $sql);
            $sql_statements = array_filter($sql_statements);
            
            foreach ($sql_statements as $statement) {
                if (!empty(trim($statement))) {
                    $result = $wpdb->query($statement);
                    if ($result === false) {
                        error_log('CTD Table Creation Error: ' . $wpdb->last_error . ' for query: ' . $statement);
                    }
                }
            }
            
            // Verify tables exist
            $all_exist = true;
            foreach ($tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                    $all_exist = false;
                    error_log("CTD Table does not exist after creation: $table");
                }
            }
            
            if (!$all_exist) {
                // Try one more time with dbDelta
                $result = dbDelta($sql);
                error_log('CTD Table Creation Results (dbDelta): ' . print_r($result, true));
                
                // Check again
                $all_exist = true;
                foreach ($tables as $table) {
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                        $all_exist = false;
                        error_log("CTD Table still does not exist after dbDelta: $table");
                    }
                }
                
                return $all_exist;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('CTD Table Creation Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create upload directory for designs
     */
    public static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        
        // Create directory for designs
        $ctd_dir = $upload_dir['basedir'] . '/ctd-designs';
        if (!file_exists($ctd_dir)) {
            wp_mkdir_p($ctd_dir);
            
            // Create .htaccess file to protect direct access
            $htaccess_file = $ctd_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Deny direct access to files\n";
                $htaccess_content .= "<FilesMatch \"\\.(php|js|css|jpg|jpeg|png|gif)$\">\n";
                $htaccess_content .= "Order Allow,Deny\n";
                $htaccess_content .= "Deny from all\n";
                $htaccess_content .= "</FilesMatch>\n";
                
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Create index.php file
            $index_file = $ctd_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.");
            }
        }
        
        // Create directory for composite images
        $composite_dir = $upload_dir['basedir'] . '/ctd-composites';
        if (!file_exists($composite_dir)) {
            wp_mkdir_p($composite_dir);
            
            // Create .htaccess file to protect direct access
            $htaccess_file = $composite_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Deny direct access to files\n";
                $htaccess_content .= "<FilesMatch \"\\.(php|js|css)$\">\n";
                $htaccess_content .= "Order Allow,Deny\n";
                $htaccess_content .= "Deny from all\n";
                $htaccess_content .= "</FilesMatch>\n";
                
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Create index.php file
            $index_file = $composite_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.");
            }
        }
    }
    
    /**
     * Check and update table structure if needed
     */
    public static function check_and_update_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            return self::create_tables();
        }
        
        // Check if color_sizes column exists
        $color_sizes_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'color_sizes'");
        if (!$color_sizes_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN color_sizes longtext AFTER tier_pricing");
            error_log('CTD Added color_sizes column to ' . $table_name);
        }
        
        // Check if inventory_enabled column exists
        $inventory_enabled_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory_enabled'");
        if (!$inventory_enabled_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN inventory_enabled tinyint(1) DEFAULT 0 AFTER color_sizes");
            error_log('CTD Added inventory_enabled column to ' . $table_name);
        }
        
        // Check if inventory column exists
        $inventory_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory'");
        if (!$inventory_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN inventory longtext AFTER inventory_enabled");
            error_log('CTD Added inventory column to ' . $table_name);
        }
        
        // Check if updated_at column has ON UPDATE CURRENT_TIMESTAMP
        $updated_at_check = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'updated_at'", ARRAY_A);
        if (!empty($updated_at_check)) {
            $column_type = $updated_at_check[0]['Type'];
            $column_default = $updated_at_check[0]['Default'];
            $column_extra = isset($updated_at_check[0]['Extra']) ? $updated_at_check[0]['Extra'] : '';
            
            // If it has ON UPDATE CURRENT_TIMESTAMP, modify it
            if (strpos($column_extra, 'on update CURRENT_TIMESTAMP') !== false) {
                $wpdb->query("ALTER TABLE $table_name MODIFY updated_at datetime DEFAULT CURRENT_TIMESTAMP");
                error_log('CTD Fixed updated_at column in ' . $table_name);
            }
        }

        return true;
    }
    
    /**
     * Regenerate tables - can be called from admin
     */
    public static function regenerate_tables() {
        global $wpdb;
        
        try {
            // Instead of dropping tables first, just create them if they don't exist
            // This is safer than dropping tables which could cause data loss
            self::create_tables();
        
            // Check and update table structure if needed
            self::check_and_update_tables();
        
            // Verify tables exist
            $tables = array(
                $wpdb->prefix . 'ctd_product_configs',
                $wpdb->prefix . 'ctd_designs',
                $wpdb->prefix . 'ctd_order_meta'
            );
            
            $all_exist = true;
            foreach ($tables as $table) {
                if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                    $all_exist = false;
                    error_log("CTD Table does not exist after regeneration: $table");
                }
            }
            
            return $all_exist;
        } catch (Exception $e) {
            error_log('CTD Table Regeneration Error: ' . $e->getMessage());
            return false;
        }
    }
}
