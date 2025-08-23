<?php
/**
 * Database Update Script for Custom T-Shirt Designer
 * Adds size_type field to existing products and migrates data
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update database to add size_type field to existing CTD products
 */
function ctd_update_database_add_size_type() {
    global $wpdb;
    
    $updated_count = 0;
    $error_count = 0;
    $log = array();
    
    try {
        // Get all products that have CTD enabled but no size_type set
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
        
        $log[] = "Found " . count($products) . " CTD products without size_type field";
        
        foreach ($products as $product) {
            try {
                // Set default size type to 'mens' for existing products
                $result = update_post_meta($product->ID, '_ctd_size_type', 'mens');
                
                if ($result !== false) {
                    $updated_count++;
                    $log[] = "Updated product ID {$product->ID} ({$product->post_title}) with mens size type";
                    
                    // Validate existing size data
                    $existing_sizes = get_post_meta($product->ID, '_ctd_color_sizes', true);
                    if (!empty($existing_sizes)) {
                        $log[] = "Product ID {$product->ID} has existing size data - preserved";
                    }
                } else {
                    $error_count++;
                    $log[] = "Failed to update product ID {$product->ID}";
                }
                
            } catch (Exception $e) {
                $error_count++;
                $log[] = "Error updating product ID {$product->ID}: " . $e->getMessage();
            }
        }
        
        // Update plugin version to indicate migration completed
        update_option('ctd_size_type_migration_completed', current_time('mysql'));
        
        $log[] = "Migration completed successfully";
        $log[] = "Updated: {$updated_count} products";
        $log[] = "Errors: {$error_count} products";
        
    } catch (Exception $e) {
        $log[] = "Critical error during migration: " . $e->getMessage();
        $error_count++;
    }
    
    return array(
        'success' => $error_count === 0,
        'updated' => $updated_count,
        'errors' => $error_count,
        'log' => $log
    );
}

/**
 * Check if size type migration is needed
 */
function ctd_needs_size_type_migration() {
    // Check if migration was already completed
    $migration_completed = get_option('ctd_size_type_migration_completed', false);
    if ($migration_completed) {
        return false;
    }
    
    // Check if there are any CTD products without size_type
    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => 1,
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
    
    return !empty($products);
}

/**
 * Get migration status
 */
function ctd_get_migration_status() {
    $migration_completed = get_option('ctd_size_type_migration_completed', false);
    
    // Count total CTD products
    $total_ctd_products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_ctd_enabled',
                'value' => 'yes',
                'compare' => '='
            )
        ),
        'fields' => 'ids'
    ));
    
    // Count products with size_type
    $migrated_products = get_posts(array(
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
                'compare' => 'EXISTS'
            )
        ),
        'fields' => 'ids'
    ));
    
    return array(
        'migration_completed' => $migration_completed,
        'total_ctd_products' => count($total_ctd_products),
        'migrated_products' => count($migrated_products),
        'needs_migration' => ctd_needs_size_type_migration()
    );
}

/**
 * Run the migration if needed (called during plugin activation/update)
 */
function ctd_maybe_run_size_type_migration() {
    if (ctd_needs_size_type_migration()) {
        $result = ctd_update_database_add_size_type();
        
        // Log the result
        if (function_exists('error_log')) {
            error_log('CTD Size Type Migration Result: ' . print_r($result, true));
        }
        
        return $result;
    }
    
    return array(
        'success' => true,
        'message' => 'Migration not needed - already completed or no CTD products found'
    );
}

// Hook to run migration on plugin activation/update
add_action('init', function() {
    // Only run once per request and only for admin users
    if (is_admin() && current_user_can('manage_options')) {
        static $migration_checked = false;
        
        if (!$migration_checked) {
            $migration_checked = true;
            
            // Check if we need to run migration
            if (ctd_needs_size_type_migration()) {
                // Run migration in background
                wp_schedule_single_event(time() + 10, 'ctd_run_size_type_migration');
            }
        }
    }
});

// Hook for scheduled migration
add_action('ctd_run_size_type_migration', 'ctd_maybe_run_size_type_migration');