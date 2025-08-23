<?php
/**
 * Database Update Script for Custom T-Shirt Designer
 * 
 * This script adds missing columns to the database tables.
 * 
 * IMPORTANT: Place this file in your WordPress root directory and run it once.
 * Delete it immediately after running for security reasons.
 */

// Load WordPress
require_once('wp-load.php');

// Security check - only allow admin users to run this script
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator to run this script.');
}

// Get the database prefix
global $wpdb;
$table_name = $wpdb->prefix . 'ctd_product_configs';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
    echo "<p>Error: Table $table_name does not exist. Please activate the plugin first.</p>";
    exit;
}

// Check and add color_sizes column
$color_sizes_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'color_sizes'");
if (!$color_sizes_exists) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN color_sizes longtext AFTER tier_pricing");
    echo "<p>Added color_sizes column to $table_name</p>";
} else {
    echo "<p>color_sizes column already exists in $table_name</p>";
}

// Check and add inventory_enabled column
$inventory_enabled_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory_enabled'");
if (!$inventory_enabled_exists) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN inventory_enabled tinyint(1) DEFAULT 0 AFTER color_sizes");
    echo "<p>Added inventory_enabled column to $table_name</p>";
} else {
    echo "<p>inventory_enabled column already exists in $table_name</p>";
}

// Check and add inventory column
$inventory_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory'");
if (!$inventory_exists) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN inventory longtext AFTER inventory_enabled");
    echo "<p>Added inventory column to $table_name</p>";
} else {
    echo "<p>inventory column already exists in $table_name</p>";
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
        echo "<p>Fixed updated_at column in $table_name</p>";
    } else {
        echo "<p>updated_at column is correctly configured in $table_name</p>";
    }
}

echo "<h2>Database update completed successfully!</h2>";
echo "<p>You can now delete this file for security reasons.</p>";
?>
