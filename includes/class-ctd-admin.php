<?php
/**
 * Admin class for Custom T-Shirt Designer
 */
class CTD_Admin {
    
    /**
     * Initialize admin hooks
     */
    public function init() {
        // Add product tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        
        // Add product data panel
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        
        // Save product data
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register AJAX for table regeneration
        add_action('wp_ajax_ctd_regenerate_tables', array($this, 'ajax_regenerate_tables'));

        // Register AJAX for database checks
        add_action('wp_ajax_ctd_check_database_status', array($this, 'ajax_check_database_status'));
        add_action('wp_ajax_ctd_repair_tables', array($this, 'ajax_repair_tables'));
        
        // Add admin styles
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Add designs to order admin display
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_order_item_header'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_order_item_values'), 10, 3);
        
        // Add designs to order emails
        add_action('woocommerce_email_order_details', array($this, 'add_designs_to_email'), 20, 4);
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin notice if database needs updating
        add_action('admin_notices', array($this, 'database_update_notice'));
        
        // Add welcome message
        add_action('admin_notices', array($this, 'welcome_notice'));
    }
    
    /**
     * Show welcome message
     */
    public function welcome_notice() {
        if (get_option('ctd_show_welcome', 0)) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Custom T-Shirt Designer:</strong> Thank you for installing the plugin! Please go to <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=database'); ?>">T-Shirt Designer Settings &gt; Database</a> to verify your database tables are set up correctly.</p>
            </div>
            <?php
            // Remove the flag
            update_option('ctd_show_welcome', 0);
        }
    }
    
    /**
     * Show admin notice if database needs updating
     */
    public function database_update_notice() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            ?>
            <div class="notice notice-error">
                <p><strong>Custom T-Shirt Designer:</strong> Database tables are missing. Please go to <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=database'); ?>">T-Shirt Designer Settings &gt; Database</a> and click "Repair Tables" to fix this issue.</p>
            </div>
            <?php
            return;
        }
        
        // Check if color_sizes column exists
        $color_sizes_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'color_sizes'");
        $inventory_enabled_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory_enabled'");
        $inventory_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory'");
        
        if (!$color_sizes_exists || !$inventory_enabled_exists || !$inventory_exists) {
            ?>
            <div class="notice notice-error">
                <p><strong>Custom T-Shirt Designer:</strong> Your database needs to be updated. Please go to <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=database'); ?>">T-Shirt Designer Settings &gt; Database</a> and click "Repair Tables" to fix this issue.</p>
            </div>
            <?php
        }
    }
    
    /**
     * Add admin styles
     */
    public function admin_styles() {
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        
        // Enqueue our admin styles
        wp_enqueue_style('ctd-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css', array('wp-color-picker'), CTD_VERSION);
    }
    
    /**
     * Add admin scripts
     */
    public function admin_scripts($hook) {
        // Load scripts on product edit page and our settings page
        if ($hook == 'post.php' || $hook == 'post-new.php' || $hook == 'woocommerce_page_ctd-settings') {
            // Enqueue WordPress color picker
            wp_enqueue_script('wp-color-picker');
            
            // Enqueue our admin scripts
            wp_enqueue_script('ctd-admin-scripts', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), CTD_VERSION, true);
            
            // Add localized script data
            wp_localize_script('ctd-admin-scripts', 'ctd_admin_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ctd-admin-nonce'),
                'plugin_url' => plugin_dir_url(dirname(__FILE__)),
                'confirm_regenerate' => __('WARNING: This will attempt to recreate the database tables. Existing data will be preserved, but this operation could potentially cause issues if there are database conflicts. Do you want to continue?', 'custom-tshirt-designer'),
                'confirm_repair' => __('This will repair the database tables structure. Your data will be preserved. Do you want to continue?', 'custom-tshirt-designer'),
                'success_message' => __('Operation completed successfully!', 'custom-tshirt-designer'),
                'error_message' => __('An error occurred. Please check the server logs for more information.', 'custom-tshirt-designer')
            ));
        }
        
        // Only load database-specific scripts on our settings page
        if ($hook == 'woocommerce_page_ctd-settings') {
            wp_enqueue_script('ctd-admin-database', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-database.js', array('jquery'), CTD_VERSION, true);
            
            // Add localized script data
            wp_localize_script('ctd-admin-database', 'ctd_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ctd-admin-nonce'),
                'confirm_regenerate' => __('WARNING: This will attempt to recreate the database tables. Existing data will be preserved, but this operation could potentially cause issues if there are database conflicts. Do you want to continue?', 'custom-tshirt-designer'),
                'confirm_repair' => __('This will repair the database tables structure. Your data will be preserved. Do you want to continue?', 'custom-tshirt-designer'),
                'success_message' => __('Operation completed successfully!', 'custom-tshirt-designer'),
                'error_message' => __('An error occurred. Please check the server logs for more information.', 'custom-tshirt-designer')
            ));
        }
    }
    
    /**
     * Add product data tab
     */
    public function add_product_data_tab($tabs) {
        $tabs['ctd_designer'] = array(
            'label'    => __('T-Shirt Designer', 'custom-tshirt-designer'),
            'target'   => 'ctd_designer_product_data',
            'class'    => array('show_if_simple'),
            'priority' => 80,
        );
        
        return $tabs;
    }
    
    
    /**
     * Get product configuration
     */
    public function get_product_config($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('CTD Error: Table does not exist when getting product config: ' . $table_name);
            return null;
        }
        
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d", $product_id));
        
        if ($wpdb->last_error) {
            error_log('CTD Database Error: ' . $wpdb->last_error);
        }
        
        return $config;
    }
    
    /**
     * Add product data panel
     */
    public function add_product_data_panel() {
        global $post;
        
        
        // Get saved data
        $product_id = $post->ID;
        
        // Add nonce field for security
        wp_nonce_field('ctd_save_product_data', 'ctd_product_nonce');
        
        $config = $this->get_product_config($product_id);
        
        // Debug output to check what's being retrieved
        error_log('CTD Product Config: ' . print_r($config, true));
        
        // Get color-specific sizes
        $color_sizes = array();
        if (isset($config->color_sizes) && !empty($config->color_sizes)) {
            $color_sizes = maybe_unserialize($config->color_sizes);
        }
        
        // Get inventory tracking setting
        $inventory_enabled = 0;
        if (isset($config->inventory_enabled)) {
            $inventory_enabled = $config->inventory_enabled;
        }
        
        // Get inventory data
        $inventory = array();
        if (isset($config->inventory) && !empty($config->inventory)) {
            $inventory = maybe_unserialize($config->inventory);
        }
        
        $enabled = isset($config->enabled) ? $config->enabled : 0;
        $product_type = isset($config->product_type) ? $config->product_type : 'shirt';
        
        // Updated default sizes to include 2XS and 4XL
        $default_sizes = array('2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL');
        $sizes = isset($config->sizes) ? maybe_unserialize($config->sizes) : $default_sizes;
        
        $colors = isset($config->colors) ? maybe_unserialize($config->colors) : array(
            'white' => 'White',
            'black' => 'Black',
            'red' => 'Red',
            'blue' => 'Blue',
            'green' => 'Green',
            'yellow' => 'Yellow',
            'purple' => 'Purple',
            'gray' => 'Gray'
        );
        
        // Get decoration methods
        $decoration_methods = isset($config->decoration_methods) ? maybe_unserialize($config->decoration_methods) : array(
            'screen_printing' => 'Screen Printing',
            'dtg' => 'Direct to Garment (DTG)',
            'embroidery' => 'Embroidery',
            'heat_transfer' => 'Heat Transfer'
        );
        
        // Get setup fees
        $setup_fees = isset($config->setup_fees) ? maybe_unserialize($config->setup_fees) : array(
            'screen_printing' => 10.95,
            'dtg' => 8.95,
            'embroidery' => 15.95,
            'heat_transfer' => 7.95
        );
        
        // Get tier pricing
        $tier_pricing = isset($config->tier_pricing) ? maybe_unserialize($config->tier_pricing) : array(
            array('min' => 50, 'max' => 99, 'discount' => 5),
            array('min' => 100, 'max' => 249, 'discount' => 10),
            array('min' => 250, 'max' => 499, 'discount' => 15),
            array('min' => 500, 'max' => 999, 'discount' => 20),
            array('min' => 1000, 'max' => 0, 'discount' => 25) // 0 max means no upper limit
        );
        
        // Debug output for the enabled value
        error_log('CTD Enabled Value: ' . $enabled);
        
        ?>
        <div id="ctd_designer_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // FIXED: Use direct HTML for the checkbox to avoid WooCommerce function issues
                ?>
                <p class="form-field">
                    <label for="_ctd_enabled"><?php _e('Enable Custom T-Shirt Designer', 'custom-tshirt-designer'); ?></label>
                    <input type="checkbox" id="_ctd_enabled" name="_ctd_enabled" value="1" <?php checked($enabled, 1); ?> class="checkbox">
                    <span class="description"><?php _e('Enable the custom designer for this product.', 'custom-tshirt-designer'); ?></span>
                </p>
                <?php
                
                // Product type select
                woocommerce_wp_select(array(
                    'id'          => '_ctd_product_type',
                    'label'       => __('Product Type', 'custom-tshirt-designer'),
                    'description' => __('Select the type of apparel product.', 'custom-tshirt-designer'),
                    'options'     => array(
                        'shirt'  => __('Shirt', 'custom-tshirt-designer'),
                        'hoodie' => __('Hoodie', 'custom-tshirt-designer'),
                        'hat'    => __('Hat', 'custom-tshirt-designer'),
                        'bottle' => __('Bottle', 'custom-tshirt-designer'),
                    ),
                    'value'       => $product_type,
                ));
                ?>
                
                <!-- Color options with per-color sizes and inventory -->
                <p class="form-field">
                    <label><?php _e('Color Options with Sizes', 'custom-tshirt-designer'); ?></label>
                    <span class="description"><?php _e('Manage available colors, sizes, and inventory for each color.', 'custom-tshirt-designer'); ?></span>
                </p>

                <!-- Inventory Tracking Toggle -->
                <p class="form-field">
                    <label for="_ctd_inventory_enabled"><?php _e('Enable Inventory Tracking', 'custom-tshirt-designer'); ?></label>
                    <input type="checkbox" id="_ctd_inventory_enabled" name="_ctd_inventory_enabled" value="1" <?php checked($inventory_enabled, 1); ?> class="checkbox">
                    <span class="description"><?php _e('Track inventory for each color and size combination.', 'custom-tshirt-designer'); ?></span>
                </p>

                <div id="ctd_colors_container" class="ctd-colors-container">
                    <?php
                    // Display existing colors
                    foreach ($colors as $color_key => $color_name) {
                        // Get sizes for this color, or use default sizes
                        $color_specific_sizes = isset($color_sizes[$color_key]) ? $color_sizes[$color_key] : $default_sizes;
                        
                        // Ensure all default sizes are included
                        foreach ($default_sizes as $default_size) {
                            if (!in_array($default_size, $color_specific_sizes)) {
                                $color_specific_sizes[] = $default_size;
                            }
                        }
                        
                        // Sort sizes by their natural order
                        usort($color_specific_sizes, function($a, $b) use ($default_sizes) {
                            $a_index = array_search($a, $default_sizes);
                            $b_index = array_search($b, $default_sizes);
                            
                            if ($a_index !== false && $b_index !== false) {
                                return $a_index - $b_index;
                            } elseif ($a_index !== false) {
                                return -1;
                            } elseif ($b_index !== false) {
                                return 1;
                            } else {
                                return strcmp($a, $b);
                            }
                        });
                        
                        ?>
                        <div class="ctd-color-row">
                            <div class="ctd-color-header">
                                <input type="text" name="_ctd_color_keys[]" value="<?php echo esc_attr($color_key); ?>" placeholder="<?php _e('Color code (e.g. #FF0000 or red)', 'custom-tshirt-designer'); ?>" class="ctd-color-key">
                                <input type="text" name="_ctd_color_names[]" value="<?php echo esc_attr($color_name); ?>" placeholder="<?php _e('Color name', 'custom-tshirt-designer'); ?>" class="ctd-color-name">
                                <span class="ctd-color-preview" style="background-color: <?php echo esc_attr($color_key); ?>;"></span>
                                <a href="#" class="ctd-remove-color button"><?php _e('Remove', 'custom-tshirt-designer'); ?></a>
                            </div>
                            
                            <!-- Size options for this color -->
                            <div class="ctd-color-sizes">
                                <h4><?php printf(__('Sizes for %s', 'custom-tshirt-designer'), esc_html($color_name)); ?></h4>
                                
                                <!-- FIXED: Simplified size display without categories -->
                                <div class="ctd-color-size-options">
                                    <?php
                                    // Display all sizes
                                    foreach ($color_specific_sizes as $size) {
                                        $checked = in_array($size, $color_specific_sizes) ? 'checked="checked"' : '';
                                        $inventory_qty = isset($inventory[$color_key][$size]) ? $inventory[$color_key][$size] : '0';
                                        $is_custom = !in_array($size, $default_sizes) ? 'ctd-custom-size-item' : '';
                                        ?>
                                        <div class="ctd-color-size-item <?php echo $is_custom; ?>">
                                            <label>
                                                <input type="checkbox" name="_ctd_color_sizes[<?php echo esc_attr($color_key); ?>][]" value="<?php echo esc_attr($size); ?>" <?php echo $checked; ?>> 
                                                <?php echo esc_html($size); ?>
                                            </label>
                                            <div class="ctd-size-inventory">
                                                <input type="text" name="_ctd_inventory[<?php echo esc_attr($color_key); ?>][<?php echo esc_attr($size); ?>]" placeholder="<?php _e('Qty', 'custom-tshirt-designer'); ?>" value="<?php echo esc_attr($inventory_qty); ?>" min="0" class="ctd-inventory-input">
                                            </div>
                                            <?php if (!in_array($size, $default_sizes)) : ?>
                                                <a href="#" class="ctd-remove-custom-size">×</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                
                                <div class="ctd-add-custom-size-container">
                                    <input type="text" class="ctd-custom-size-input" placeholder="<?php _e('Custom size (e.g. 6XL)', 'custom-tshirt-designer'); ?>">
                                    <button type="button" class="button ctd-add-custom-size" data-color="<?php echo esc_attr($color_key); ?>"><?php _e('Add Custom Size', 'custom-tshirt-designer'); ?></button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <p class="form-field">
                    <button type="button" class="button" id="ctd_add_color"><?php _e('Add Color', 'custom-tshirt-designer'); ?></button>
                </p>

                <!-- Decoration Methods -->
                <p class="form-field">
                    <label><?php _e('Decoration Methods', 'custom-tshirt-designer'); ?></label>
                    <span class="description"><?php _e('Manage decoration methods and their setup fees.', 'custom-tshirt-designer'); ?></span>
                </p>
                
                <div id="ctd_decoration_methods_container" class="ctd-decoration-methods-container">
                    <?php
                    // Display existing decoration methods
                    foreach ($decoration_methods as $method_key => $method_name) {
                        $setup_fee = isset($setup_fees[$method_key]) ? $setup_fees[$method_key] : 0;
                        ?>
                        <div class="ctd-decoration-method-row">
                            <input type="text" name="_ctd_decoration_method_keys[]" value="<?php echo esc_attr($method_key); ?>" placeholder="<?php _e('Method key (e.g. screen_printing)', 'custom-tshirt-designer'); ?>" class="ctd-decoration-method-key">
                            <input type="text" name="_ctd_decoration_method_names[]" value="<?php echo esc_attr($method_name); ?>" placeholder="<?php _e('Method name (e.g. Screen Printing)', 'custom-tshirt-designer'); ?>" class="ctd-decoration-method-name">
                            <div class="ctd-decoration-method-fee">
                                <label><?php _e('Setup Fee ($):', 'custom-tshirt-designer'); ?></label>
                                <input type="number" name="_ctd_decoration_method_fees[]" value="<?php echo esc_attr($setup_fee); ?>" step="0.01" min="0">
                            </div>
                            <div class="ctd-decoration-method-actions">
                                <button type="button" class="button ctd-remove-decoration-method"><?php _e('Remove', 'custom-tshirt-designer'); ?></button>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                
                <p class="form-field ctd-add-decoration-method">
                    <button type="button" class="button" id="ctd_add_decoration_method"><?php _e('Add Decoration Method', 'custom-tshirt-designer'); ?></button>
                </p>
                
                <!-- Tier Pricing -->
                <p class="form-field">
                    <label><?php _e('Quantity-Based Discounts', 'custom-tshirt-designer'); ?></label>
                    <span class="description"><?php _e('Set up quantity tiers and discounts.', 'custom-tshirt-designer'); ?></span>
                </p>
                
                <table class="ctd-tier-pricing-table" id="ctd-tier-pricing-table">
                    <thead>
                        <tr>
                            <th><?php _e('Min Quantity', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Max Quantity', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Discount (%)', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Actions', 'custom-tshirt-designer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($tier_pricing as $index => $tier) {
                            $min = isset($tier['min']) ? $tier['min'] : 0;
                            $max = isset($tier['max']) ? $tier['max'] : 0;
                            $discount = isset($tier['discount']) ? $tier['discount'] : 0;
                            ?>
                            <tr class="ctd-tier-row">
                                <td><input type="number" name="_ctd_tier_min[]" value="<?php echo esc_attr($min); ?>" min="0"></td>
                                <td><input type="number" name="_ctd_tier_max[]" value="<?php echo esc_attr($max); ?>" min="0" placeholder="<?php _e('0 = no limit', 'custom-tshirt-designer'); ?>"></td>
                                <td><input type="number" name="_ctd_tier_discount[]" value="<?php echo esc_attr($discount); ?>" min="0" max="100" step="0.1"></td>
                                <td><button type="button" class="button ctd-remove-tier"><?php _e('Remove', 'custom-tshirt-designer'); ?></button></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                
                <p class="form-field ctd-add-tier">
                    <button type="button" class="button" id="ctd_add_tier"><?php _e('Add Pricing Tier', 'custom-tshirt-designer'); ?></button>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save product data
     */
    public function save_product_data($product_id) {
        global $wpdb;
        
        // Check if nonce is set and valid
        if (!isset($_POST['ctd_product_nonce']) || !wp_verify_nonce($_POST['ctd_product_nonce'], 'ctd_save_product_data')) {
            CTD_Debug::log('Nonce verification failed when saving product data', 'error');
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $product_id)) {
            CTD_Debug::log('User does not have permission to edit product ID: ' . $product_id, 'error');
            return;
        }
        
        // Debug: Log all POST data to help diagnose issues
        CTD_Debug::log('Saving product data for product ID: ' . $product_id, 'info');
        CTD_Debug::log_var($_POST, 'POST data', 'debug');

        // Get form data - Simple checkbox handling
        $enabled = isset($_POST['_ctd_enabled']) ? 1 : 0;
        $inventory_enabled = isset($_POST['_ctd_inventory_enabled']) ? 1 : 0;
        
        CTD_Debug::log('Enabled value: ' . $enabled, 'debug');
        CTD_Debug::log('Inventory enabled value: ' . $inventory_enabled, 'debug');

        $product_type = isset($_POST['_ctd_product_type']) ? sanitize_text_field($_POST['_ctd_product_type']) : 'shirt';

        // Updated default sizes
        $sizes = array('2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL');
        
        // Get color data
        $color_keys = isset($_POST['_ctd_color_keys']) ? $_POST['_ctd_color_keys'] : array();
        $color_names = isset($_POST['_ctd_color_names']) ? $_POST['_ctd_color_names'] : array();

        // Debug output for color data
        CTD_Debug::log_var($color_keys, 'Color keys', 'debug');
        CTD_Debug::log_var($color_names, 'Color names', 'debug');

        // Combine color keys and names
        $colors = array();
        if (!empty($color_keys) && !empty($color_names) && count($color_keys) === count($color_names)) {
            for ($i = 0; $i < count($color_keys); $i++) {
                if (!empty($color_keys[$i]) && !empty($color_names[$i])) {
                    $color_key = sanitize_text_field($color_keys[$i]);
                    $colors[$color_key] = sanitize_text_field($color_names[$i]);
                    CTD_Debug::log("Added color: {$color_key} => {$color_names[$i]}", 'debug');
                }
            }
        }

        // If no colors were added, use defaults
        if (empty($colors)) {
            CTD_Debug::log('No colors found, using defaults', 'debug');
            $colors = array(
                '#FFFFFF' => 'White',
                '#000000' => 'Black',
                '#FF0000' => 'Red',
                '#0000FF' => 'Blue',
                '#008000' => 'Green',
                '#FFFF00' => 'Yellow',
                '#800080' => 'Purple',
                '#808080' => 'Gray'
            );
        }
        
        // Get color-specific sizes
        $color_sizes = array();
        if (isset($_POST['_ctd_color_sizes']) && is_array($_POST['_ctd_color_sizes'])) {
            CTD_Debug::log_var($_POST['_ctd_color_sizes'], 'Color sizes data', 'debug');
            
            foreach ($_POST['_ctd_color_sizes'] as $color_key => $color_sizes_array) {
                // Process sizes for all colors, even if they might be renamed
                if (!empty($color_sizes_array) && is_array($color_sizes_array)) {
                    $color_sizes[$color_key] = array_map('sanitize_text_field', $color_sizes_array);
                    CTD_Debug::log("Saving sizes for color: {$color_key}, Sizes: " . implode(', ', $color_sizes[$color_key]), 'debug');
                }
            }
            
            // Now ensure all colors in the final $colors array have size data
            foreach ($colors as $color_key => $color_name) {
                if (!isset($color_sizes[$color_key])) {
                    // If this color doesn't have sizes yet, add default sizes
                    $color_sizes[$color_key] = $sizes;
                    CTD_Debug::log("Adding default sizes for new color: {$color_key}", 'debug');
                }
            }
        }

        // Ensure we have color sizes for all colors
        if (empty($color_sizes)) {
            CTD_Debug::log('No color sizes found, using defaults for all colors', 'debug');
            foreach ($colors as $color_key => $color_name) {
                $color_sizes[$color_key] = $sizes;
            }
        }
        
        // Process inventory data
        $inventory = array();
        if (isset($_POST['_ctd_inventory']) && is_array($_POST['_ctd_inventory'])) {
            CTD_Debug::log_var($_POST['_ctd_inventory'], 'Raw inventory data', 'debug');
            
            foreach ($_POST['_ctd_inventory'] as $color_key => $size_inventory) {
                $inventory[$color_key] = array();
                
                if (is_array($size_inventory)) {
                    foreach ($size_inventory as $size => $qty) {
                        // Ensure quantity is properly sanitized as integer
                        $qty_value = intval($qty);
                        
                        // Only save non-zero quantities to reduce database size
                        if ($qty_value > 0 || $inventory_enabled) {
                            $inventory[$color_key][$size] = $qty_value;
                            CTD_Debug::log("Saving inventory: Color: {$color_key}, Size: {$size}, Qty: {$qty_value}", 'debug');
                        }
                    }
                }
            }
        }

        CTD_Debug::log_var($inventory, 'Processed inventory data', 'debug');
        
        // Get decoration method data
        $decoration_method_keys = isset($_POST['_ctd_decoration_method_keys']) ? array_map('sanitize_text_field', $_POST['_ctd_decoration_method_keys']) : array();
        $decoration_method_names = isset($_POST['_ctd_decoration_method_names']) ? array_map('sanitize_text_field', $_POST['_ctd_decoration_method_names']) : array();
        $decoration_method_fees = isset($_POST['_ctd_decoration_method_fees']) ? array_map('floatval', $_POST['_ctd_decoration_method_fees']) : array();
        
        // Combine decoration method data
        $decoration_methods = array();
        $setup_fees = array();
        
        if (!empty($decoration_method_keys) && !empty($decoration_method_names) && count($decoration_method_keys) === count($decoration_method_names)) {
            for ($i = 0; $i < count($decoration_method_keys); $i++) {
                if (!empty($decoration_method_keys[$i]) && !empty($decoration_method_names[$i])) {
                    $key = sanitize_key($decoration_method_keys[$i]);
                    $decoration_methods[$key] = $decoration_method_names[$i];
                    $setup_fees[$key] = isset($decoration_method_fees[$i]) ? $decoration_method_fees[$i] : 0;
                }
            }
        }
        
        // If no decoration methods were added, use defaults
        if (empty($decoration_methods)) {
            $decoration_methods = array(
                'screen_printing' => 'Screen Printing',
                'dtg' => 'Direct to Garment (DTG)',
                'embroidery' => 'Embroidery',
                'heat_transfer' => 'Heat Transfer'
            );
            
            $setup_fees = array(
                'screen_printing' => 10.95,
                'dtg' => 8.95,
                'embroidery' => 15.95,
                'heat_transfer' => 7.95
            );
        }
        
        // Get tier pricing
        $tier_mins = isset($_POST['_ctd_tier_min']) ? array_map('intval', $_POST['_ctd_tier_min']) : array();
        $tier_maxs = isset($_POST['_ctd_tier_max']) ? array_map('intval', $_POST['_ctd_tier_max']) : array();
        $tier_discounts = isset($_POST['_ctd_tier_discount']) ? array_map('floatval', $_POST['_ctd_tier_discount']) : array();
        
        $tier_pricing = array();
        if (!empty($tier_mins) && !empty($tier_discounts) && count($tier_mins) === count($tier_discounts)) {
            for ($i = 0; $i < count($tier_mins); $i++) {
                if (!empty($tier_mins[$i]) && isset($tier_discounts[$i])) {
                    $tier_pricing[] = array(
                        'min' => $tier_mins[$i],
                        'max' => isset($tier_maxs[$i]) ? $tier_maxs[$i] : 0,
                        'discount' => $tier_discounts[$i]
                    );
                }
            }
        }
        
        // If no tiers were added, use defaults
        if (empty($tier_pricing)) {
            $tier_pricing = array(
                array('min' => 50, 'max' => 99, 'discount' => 5),
                array('min' => 100, 'max' => 249, 'discount' => 10),
                array('min' => 250, 'max' => 499, 'discount' => 15),
                array('min' => 500, 'max' => 999, 'discount' => 20),
                array('min' => 1000, 'max' => 0, 'discount' => 25)
            );
        }
        
        // Debug output for form data
        CTD_Debug::log('Form Data - Enabled: ' . $enabled, 'debug');
        CTD_Debug::log('Form Data - Product Type: ' . $product_type, 'debug');
        CTD_Debug::log_var($sizes, 'Form Data - Sizes', 'debug');
        CTD_Debug::log_var($colors, 'Form Data - Colors', 'debug');
        CTD_Debug::log_var($decoration_methods, 'Form Data - Decoration Methods', 'debug');
        CTD_Debug::log_var($setup_fees, 'Form Data - Setup Fees', 'debug');
        CTD_Debug::log_var($tier_pricing, 'Form Data - Tier Pricing', 'debug');
        
        // Get existing config
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            CTD_Debug::log('Table does not exist, creating tables', 'info');
            CTD_Installer::create_tables();
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$table_exists) {
                CTD_Debug::log('Failed to create table, aborting save', 'error');
                return;
            }
        }
        
        // Check if required columns exist
        $color_sizes_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'color_sizes'");
        $inventory_enabled_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory_enabled'");
        $inventory_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory'");
        
        if (!$color_sizes_exists || !$inventory_enabled_exists || !$inventory_exists) {
            CTD_Debug::log('Missing required columns, updating tables', 'info');
            // Run the check_and_update_tables function to add missing columns
            CTD_Installer::check_and_update_tables();
            
            // Check again
            $color_sizes_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'color_sizes'");
            $inventory_enabled_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory_enabled'");
            $inventory_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'inventory'");
            
            if (!$color_sizes_exists || !$inventory_enabled_exists || !$inventory_exists) {
                CTD_Debug::log('Failed to add required columns, aborting save', 'error');
                return;
            }
        }
        
        // Get existing config
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d", $product_id));
        
        // Update existing config
        if ($config) {
            CTD_Debug::log('Updating config for product ID ' . $product_id, 'info');
            CTD_Debug::log_var($colors, 'Colors to save', 'debug');
            CTD_Debug::log_var($color_sizes, 'Color Sizes to save', 'debug');
            CTD_Debug::log_var($inventory, 'Inventory to save', 'debug');
            
            // Update existing config
            $result = $wpdb->update(
                $table_name,
                array(
                    'enabled'           => $enabled,
                    'product_type'      => $product_type,
                    'sizes'             => maybe_serialize($sizes),
                    'colors'            => maybe_serialize($colors),
                    'decoration_methods' => maybe_serialize($decoration_methods),
                    'setup_fees'        => maybe_serialize($setup_fees),
                    'tier_pricing'      => maybe_serialize($tier_pricing),
                    'color_sizes'       => maybe_serialize($color_sizes),
                    'inventory_enabled' => $inventory_enabled,
                    'inventory'         => maybe_serialize($inventory),
                    'updated_at'        => current_time('mysql')
                ),
                array('product_id' => $product_id)
            );
            
            CTD_Debug::log('Update Result: ' . ($result !== false ? 'Success' : 'Failed'), 'info');
            if ($result === false) {
                CTD_Debug::log('Update Error: ' . $wpdb->last_error, 'error');
            }
            if ($result !== false) {
                // Force a direct check to verify the data was saved
                $verify_enabled = $wpdb->get_var($wpdb->prepare("SELECT enabled FROM $table_name WHERE product_id = %d", $product_id));
                CTD_Debug::log('Verification - Enabled value after save: ' . $verify_enabled, 'debug');
                
                // Verify colors were saved correctly
                $verify_colors = $wpdb->get_var($wpdb->prepare("SELECT colors FROM $table_name WHERE product_id = %d", $product_id));
                CTD_Debug::log_var(maybe_unserialize($verify_colors), 'Verification - Colors after save', 'debug');
            }
        } else {
            CTD_Debug::log('Inserting new config for product ID ' . $product_id, 'info');
            CTD_Debug::log_var($colors, 'Colors to save', 'debug');
            CTD_Debug::log_var($color_sizes, 'Color Sizes to save', 'debug');
            CTD_Debug::log_var($inventory, 'Inventory to save', 'debug');
            
            // Insert new config
            $result = $wpdb->insert(
                $table_name,
                array(
                    'product_id'        => $product_id,
                    'enabled'           => $enabled,
                    'product_type'      => $product_type,
                    'sizes'             => maybe_serialize($sizes),
                    'colors'            => maybe_serialize($colors),
                    'decoration_methods' => maybe_serialize($decoration_methods),
                    'setup_fees'        => maybe_serialize($setup_fees),
                    'tier_pricing'      => maybe_serialize($tier_pricing),
                    'color_sizes'       => maybe_serialize($color_sizes),
                    'inventory_enabled' => $inventory_enabled,
                    'inventory'         => maybe_serialize($inventory),
                    'created_at'        => current_time('mysql'),
                    'updated_at'        => current_time('mysql')
                )
            );
            
            CTD_Debug::log('Insert Result: ' . ($result !== false ? 'Success' : 'Failed'), 'info');
            if ($result === false) {
                CTD_Debug::log('Insert Error: ' . $wpdb->last_error, 'error');
            }
            if ($result !== false) {
                // Force a direct check to verify the data was saved
                $verify_enabled = $wpdb->get_var($wpdb->prepare("SELECT enabled FROM $table_name WHERE product_id = %d", $product_id));
                CTD_Debug::log('Verification - Enabled value after save: ' . $verify_enabled, 'debug');
                
                // Verify colors were saved correctly
                $verify_colors = $wpdb->get_var($wpdb->prepare("SELECT colors FROM $table_name WHERE product_id = %d", $product_id));
                CTD_Debug::log_var(maybe_unserialize($verify_colors), 'Verification - Colors after save', 'debug');
            }
        }
        
        // Verify the save
        $saved_config = $this->get_product_config($product_id);
        
        if ($saved_config) {
            CTD_Debug::log('Saved Config - Enabled: ' . $saved_config->enabled, 'debug');
            CTD_Debug::log('Saved Config - Inventory Enabled: ' . (isset($saved_config->inventory_enabled) ? $saved_config->inventory_enabled : 'Not set'), 'debug');
            
            // Verify inventory data was saved correctly
            if (isset($saved_config->inventory)) {
                $saved_inventory = maybe_unserialize($saved_config->inventory);
                CTD_Debug::log_var($saved_inventory, 'Saved Inventory', 'debug');
            } else {
                CTD_Debug::log('Saved Inventory: Not set', 'debug');
            }
            
            // Verify colors were saved correctly
            if (isset($saved_config->colors)) {
                $saved_colors = maybe_unserialize($saved_config->colors);
                CTD_Debug::log_var($saved_colors, 'Saved Colors', 'debug');
            } else {
                CTD_Debug::log('Saved Colors: Not set', 'debug');
            }
        } else {
            CTD_Debug::log('Failed to retrieve saved config', 'error');
        }
    }
    
    // The rest of the methods remain unchanged...
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('T-Shirt Designer', 'custom-tshirt-designer'),
            __('T-Shirt Designer', 'custom-tshirt-designer'),
            'manage_woocommerce',
            'ctd-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current tab
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Custom T-Shirt Designer Settings', 'custom-tshirt-designer'); ?></h1>
            
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=general'); ?>" class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'custom-tshirt-designer'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=database'); ?>" class="nav-tab <?php echo $tab === 'database' ? 'nav-tab-active' : ''; ?>"><?php _e('Database', 'custom-tshirt-designer'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=ctd-settings&tab=email'); ?>" class="nav-tab <?php echo $tab === 'email' ? 'nav-tab-active' : ''; ?>"><?php _e('Email Settings', 'custom-tshirt-designer'); ?></a>
            </nav>
            
            <div class="tab-content">
                <?php
                if ($tab === 'general') {
                    $this->render_general_tab();
                } elseif ($tab === 'database') {
                    $this->render_database_tab();
                } elseif ($tab === 'email') {
                    $this->render_email_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general tab
     */
    private function render_general_tab() {
        ?>
        <div class="card">
            <h2><?php _e('Plugin Information', 'custom-tshirt-designer'); ?></h2>
            <p><strong><?php _e('Version:', 'custom-tshirt-designer'); ?></strong> <?php echo CTD_VERSION; ?></p>
            <p><strong><?php _e('Database Tables:', 'custom-tshirt-designer'); ?></strong></p>
            <ul>
                <li><?php echo $GLOBALS['wpdb']->prefix; ?>ctd_product_configs</li>
                <li><?php echo $GLOBALS['wpdb']->prefix; ?>ctd_designs</li>
                <li><?php echo $GLOBALS['wpdb']->prefix; ?>ctd_order_meta</li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Render database tab
     */
    private function render_database_tab() {
        ?>
        <div class="card">
            <h2><?php _e('Database Management', 'custom-tshirt-designer'); ?></h2>
            <p><?php _e('Check the status of plugin database tables and fix any issues.', 'custom-tshirt-designer'); ?></p>
            
            <div class="ctd-database-status">
                <h3><?php _e('Database Status', 'custom-tshirt-designer'); ?></h3>
                <table class="widefat ctd-database-status-table" id="ctd-database-status-table">
                    <thead>
                        <tr>
                            <th><?php _e('Table Name', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Status', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Records', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Structure', 'custom-tshirt-designer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4"><?php _e('Click "Check Database Status" to verify tables', 'custom-tshirt-designer'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="ctd-database-actions">
                    <button id="ctd-check-database" class="button button-secondary"><?php _e('Check Database Status', 'custom-tshirt-designer'); ?></button>
                    <button id="ctd-repair-tables" class="button button-primary"><?php _e('Repair Tables', 'custom-tshirt-designer'); ?></button>
                    <button id="ctd-regenerate-tables" class="button"><?php _e('Regenerate Database Tables', 'custom-tshirt-designer'); ?></button>
                </div>
                
                <div id="ctd-database-result"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render email tab
     */
    private function render_email_tab() {
        ?>
        <div class="card">
            <h2><?php _e('Email Settings', 'custom-tshirt-designer'); ?></h2>
            <p><?php _e('Configure how designs are handled in order emails.', 'custom-tshirt-designer'); ?></p>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ctd_email_settings');
                do_settings_sections('ctd_email_settings');
                ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Include Designs in Admin Emails', 'custom-tshirt-designer'); ?></th>
                        <td>
                            <input type="checkbox" name="ctd_include_designs_admin" value="1" <?php checked(get_option('ctd_include_designs_admin', '1'), '1'); ?> />
                            <p class="description"><?php _e('Include design images in order notification emails sent to administrators.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Include Designs in Customer Emails', 'custom-tshirt-designer'); ?></th>
                        <td>
                            <input type="checkbox" name="ctd_include_designs_customer" value="1" <?php checked(get_option('ctd_include_designs_customer', '1'), '1'); ?> />
                            <p class="description"><?php _e('Include design images in order emails sent to customers.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Additional Email Recipients', 'custom-tshirt-designer'); ?></th>
                        <td>
                            <input type="text" name="ctd_additional_recipients" value="<?php echo esc_attr(get_option('ctd_additional_recipients', '')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Comma-separated list of email addresses to receive design notifications. Leave empty to use default admin email.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><?php _e('Email Subject Prefix', 'custom-tshirt-designer'); ?></th>
                        <td>
                            <input type="text" name="ctd_email_subject_prefix" value="<?php echo esc_attr(get_option('ctd_email_subject_prefix', '[Custom Design] ')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Prefix added to the subject line of design notification emails.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ctd_email_settings', 'ctd_include_designs_admin');
        register_setting('ctd_email_settings', 'ctd_include_designs_customer');
        register_setting('ctd_email_settings', 'ctd_additional_recipients');
        register_setting('ctd_email_settings', 'ctd_email_subject_prefix'); // Fixed: Added missing group parameter
    }
    
    /**
     * AJAX handler for table regeneration
     */
    public function ajax_regenerate_tables() {
        // Check nonce
        check_ajax_referer('ctd-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'custom-tshirt-designer')));
        }
        
        // Regenerate tables
        $result = CTD_Installer::regenerate_tables();
        
        if ($result) {
            wp_send_json_success(array('message' => __('Database tables have been successfully regenerated.', 'custom-tshirt-designer')));
        } else {
            wp_send_json_error(array('message' => __('An error occurred while regenerating database tables. Please check server error logs.', 'custom-tshirt-designer')));
        }
    }

    /**
     * Check database tables status
     */
    public function ajax_check_database_status() {
        // Check nonce
        check_ajax_referer('ctd-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'custom-tshirt-designer')));
        }
        
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'ctd_product_configs',
            $wpdb->prefix . 'ctd_designs',
            $wpdb->prefix . 'ctd_order_meta'
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $table_status = array(
                'name' => $table,
                'exists' => false,
                'records' => 0,
                'structure' => false
            );
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $table_status['exists'] = $table_exists;
            
            if ($table_exists) {
                // Count records
                $record_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $table_status['records'] = intval($record_count);
                
                // Check structure
                $table_structure = $this->check_table_structure($table);
                $table_status['structure'] = $table_structure;
            }
            
            $results[] = $table_status;
        }
        
        wp_send_json_success(array('tables' => $results));
    }
    
    /**
     * Check table structure
     */
    private function check_table_structure($table) {
        global $wpdb;
        
        // Get table columns
        $columns = $wpdb->get_results("DESCRIBE $table", ARRAY_A);
        
        if (!$columns) {
            return false;
        }
        
        // Define expected columns for each table
        $expected_columns = array();
        
        if ($table === $wpdb->prefix . 'ctd_product_configs') {
            $expected_columns = array('id', 'product_id', 'enabled', 'product_type', 'sizes', 'colors', 'decoration_methods', 'setup_fees', 'tier_pricing', 'color_sizes', 'inventory_enabled', 'inventory', 'created_at', 'updated_at');
        } elseif ($table === $wpdb->prefix . 'ctd_designs') {
            $expected_columns = array('id', 'order_id', 'order_item_id', 'product_id', 'position', 'file_path', 'decoration_method', 'composite_image', 'created_at');
        } elseif ($table === $wpdb->prefix . 'ctd_order_meta') {
            $expected_columns = array('id', 'order_id', 'order_item_id', 'product_id', 'colors', 'sizes', 'setup_fees', 'total_quantity', 'discount_applied', 'created_at');
        }
        
        // Check if all expected columns exist
        $existing_columns = array_column($columns, 'Field');
        $missing_columns = array_diff($expected_columns, $existing_columns);
        
        return empty($missing_columns);
    }
    
    /**
     * Repair database tables
     */
    public function ajax_repair_tables() {
        // Check nonce
        check_ajax_referer('ctd-admin-nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'custom-tshirt-designer')));
        }
        
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'ctd_product_configs',
            $wpdb->prefix . 'ctd_designs',
            $wpdb->prefix . 'ctd_order_meta'
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $result = array(
                'name' => $table,
                'status' => 'unknown'
            );
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if (!$table_exists) {
                // Table doesn't exist, create it
                $result['status'] = 'created';
            } else {
                // Table exists, check structure
                $table_structure = $this->check_table_structure($table);
                
                if (!$table_structure) {
                    // Structure is incorrect, repair it
                    $result['status'] = 'repaired';
                } else {
                    // Structure is correct
                    $result['status'] = 'ok';
                }
            }
            
            $results[] = $result;
        }
        
        // Regenerate tables to fix any issues
        CTD_Installer::check_and_update_tables();
        
        wp_send_json_success(array('tables' => $results));
    }
    
    /**
     * Add order item header for designs
     */
    public function add_order_item_header() {
        echo '<th class="item-designs">' . __('Designs', 'custom-tshirt-designer') . '</th>';
    }
    
    /**
     * Add order item values for designs
     */
    public function add_order_item_values($product, $item, $item_id) {
        global $wpdb;
        
        // Get designs for this order item
        $designs_table = $wpdb->prefix . 'ctd_designs';
        $designs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $designs_table WHERE order_id = %d AND order_item_id = %d",
            $item->get_order_id(),
            $item_id
        ));
        
        echo '<td class="item-designs">';
        
        if ($designs && count($designs) > 0) {
            echo '<div class="ctd-order-designs-container">';
            
            foreach ($designs as $design) {
                $position = ucfirst($design->position);
                $image_url = '';
                
                // Use composite image if available, otherwise use original design
                if (!empty($design->composite_image)) {
                    $image_url = $design->composite_image;
                } else if (!empty($design->file_path)) {
                    // Convert file path to URL
                    $upload_dir = wp_upload_dir();
                    $file_path = $design->file_path;
                    
                    // Check if the file path is already a URL
                    if (filter_var($file_path, FILTER_VALIDATE_URL)) {
                        $image_url = $file_path;
                    } else {
                        // Convert file path to URL
                        $image_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
                    }
                }
                
                if (!empty($image_url)) {
                    echo '<div class="ctd-design-item">';
                    echo '<img src="' . esc_url($image_url) . '" class="ctd-order-design-preview" />';
                    echo '<div class="ctd-design-position">' . esc_html($position) . '</div>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
        } else {
            echo '<span class="na">' . __('No designs', 'custom-tshirt-designer') . '</span>';
        }
        
        echo '</td>';
    }
    
    /**
     * Add designs to order emails
     */
    public function add_designs_to_email($order, $sent_to_admin, $plain_text, $email) {
        // Check if we should include designs
        $include_designs = false;
        
        if ($sent_to_admin && get_option('ctd_include_designs_admin', '1') === '1') {
            $include_designs = true;
        } else if (!$sent_to_admin && get_option('ctd_include_designs_customer', '1') === '1') {
            $include_designs = true;
        }
        
        if (!$include_designs) {
            return;
        }
        
        global $wpdb;
        $designs_table = $wpdb->prefix . 'ctd_designs';
        
        // Get all designs for this order
        $designs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $designs_table WHERE order_id = %d",
            $order->get_id()
        ));
        
        if (!$designs || count($designs) === 0) {
            return;
        }
        
        if ($plain_text) {
            echo "\n\n" . __('Custom Designs:', 'custom-tshirt-designer') . "\n";
            echo __('Design images are not visible in plain text emails. Please check your order in the admin panel.', 'custom-tshirt-designer') . "\n\n";
        } else {
            ?>
            <h2><?php _e('Custom Designs', 'custom-tshirt-designer'); ?></h2>
            <div class="ctd-email-designs-container">
                <?php
                foreach ($designs as $design) {
                    $position = ucfirst($design->position);
                    $image_url = '';
                    
                    // Use composite image if available, otherwise use original design
                    if (!empty($design->composite_image)) {
                        $image_url = $design->composite_image;
                    } else if (!empty($design->file_path)) {
                        // Convert file path to URL
                        $upload_dir = wp_upload_dir();
                        $file_path = $design->file_path;
                        
                        // Check if the file path is already a URL
                        if (filter_var($file_path, FILTER_VALIDATE_URL)) {
                            $image_url = $file_path;
                        } else {
                            // Convert file path to URL
                            $image_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
                        }
                    }
                    
                    if (!empty($image_url)) {
                        ?>
                        <div class="ctd-email-design-item">
                            <img src="<?php echo esc_url($image_url); ?>" class="ctd-email-design-image" />
                            <div><?php echo esc_html($position); ?></div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
        }
    }
}
