/**
     * Add product data tab content
     */
    public function add_product_data_tab_content() {
        global $post;
        
        // Get existing values
        $enabled = get_post_meta($post->ID, '_ctd_enabled', true);
        $base_price = get_post_meta($post->ID, '_ctd_base_price', true);
        $size_type = get_post_meta($post->ID, '_ctd_size_type', true) ?: 'mens'; // Default to mens
        $color_keys = get_post_meta($post->ID, '_ctd_color_keys', true) ?: array();
        $color_names = get_post_meta($post->ID, '_ctd_color_names', true) ?: array();
        $color_sizes = get_post_meta($post->ID, '_ctd_color_sizes', true) ?: array();
        $inventory = get_post_meta($post->ID, '_ctd_inventory', true) ?: array();
        $decoration_method_keys = get_post_meta($post->ID, '_ctd_decoration_method_keys', true) ?: array();
        $decoration_method_names = get_post_meta($post->ID, '_ctd_decoration_method_names', true) ?: array();
        $decoration_method_fees = get_post_meta($post->ID, '_ctd_decoration_method_fees', true) ?: array();
        $tier_min = get_post_meta($post->ID, '_ctd_tier_min', true) ?: array();
        $tier_max = get_post_meta($post->ID, '_ctd_tier_max', true) ?: array();
        $tier_discount = get_post_meta($post->ID, '_ctd_tier_discount', true) ?: array();
        
        ?>
        <div id="ctd_product_data" class="panel woocommerce_options_panel">
            
            <!-- Enable CTD -->
            <div class="ctd-field-group">
                <?php
                woocommerce_wp_checkbox(array(
                    'id' => '_ctd_enabled',
                    'label' => __('Enable Custom T-Shirt Designer', 'custom-tshirt-designer'),
                    'description' => __('Enable custom design functionality for this product', 'custom-tshirt-designer'),
                    'value' => $enabled
                ));
                ?>
            </div>

            <!-- Base Price -->
            <div class="ctd-field-group">
                <?php
                woocommerce_wp_text_input(array(
                    'id' => '_ctd_base_price',
                    'label' => __('Base Price ($)', 'custom-tshirt-designer'),
                    'description' => __('Base price for this product before customizations', 'custom-tshirt-designer'),
                    'type' => 'number',
                    'custom_attributes' => array(
                        'step' => '0.01',
                        'min' => '0'
                    ),
                    'value' => $base_price
                ));
                ?>
            </div>

            <!-- Product-wide Size Type Selector -->
            <div class="ctd-field-group">
                <label for="ctd_product_size_type"><?php _e('Size Type', 'custom-tshirt-designer'); ?></label>
                <select id="ctd_product_size_type" name="_ctd_size_type" class="ctd-size-type-select">
                    <option value="mens" <?php selected($size_type, 'mens'); ?>><?php _e("Men's Sizes", 'custom-tshirt-designer'); ?></option>
                    <option value="womens" <?php selected($size_type, 'womens'); ?>><?php _e("Women's Sizes", 'custom-tshirt-designer'); ?></option>
                    <option value="kids" <?php selected($size_type, 'kids'); ?>><?php _e("Kids Sizes", 'custom-tshirt-designer'); ?></option>
                </select>
                <p class="description"><?php _e('Select the size type for this product. This will apply to all colors.', 'custom-tshirt-designer'); ?></p>
            </div>

            <!-- Colors Section -->
            <div class="ctd-section">
                <h3 class="ctd-section-title"><?php _e('Colors & Sizes', 'custom-tshirt-designer'); ?></h3>
                
                <div id="ctd_colors_container" class="ctd-colors-container">
                    <?php if (!empty($color_keys)): ?>
                        <?php for ($i = 0; $i < count($color_keys); $i++): ?>
                            <?php
                            $color_key = $color_keys[$i];
                            $color_name = isset($color_names[$i]) ? $color_names[$i] : '';
                            $sizes = isset($color_sizes[$color_key]) ? $color_sizes[$color_key] : array();
                            ?>
                            <div class="ctd-color-row">
                                <div class="ctd-color-header">
                                    <input type="text" name="_ctd_color_keys[]" value="<?php echo esc_attr($color_key); ?>" placeholder="Color code (e.g. #FF0000 or red)" class="ctd-color-key">
                                    <input type="text" name="_ctd_color_names[]" value="<?php echo esc_attr($color_name); ?>" placeholder="Color name" class="ctd-color-name">
                                    <span class="ctd-color-preview" style="background-color: <?php echo esc_attr($color_key); ?>;"></span>
                                    <a href="#" class="ctd-remove-color button"><?php _e('Remove', 'custom-tshirt-designer'); ?></a>
                                </div>
                                
                                <div class="ctd-color-sizes">
                                    <h4><?php _e('Sizes for this color', 'custom-tshirt-designer'); ?></h4>
                                    <div class="ctd-color-size-options" data-color="<?php echo esc_attr($color_key); ?>">
                                        <?php if (!empty($sizes)): ?>
                                            <?php foreach ($sizes as $size): ?>
                                                <?php $qty = isset($inventory[$color_key][$size]) ? $inventory[$color_key][$size] : 0; ?>
                                                <div class="ctd-color-size-item">
                                                    <label>
                                                        <input type="checkbox" name="_ctd_color_sizes[<?php echo esc_attr($color_key); ?>][]" value="<?php echo esc_attr($size); ?>" checked>
                                                        <span><?php echo esc_html($size); ?></span>
                                                    </label>
                                                    <div class="ctd-size-inventory">
                                                        <input type="text" name="_ctd_inventory[<?php echo esc_attr($color_key); ?>][<?php echo esc_attr($size); ?>]" placeholder="Qty" value="<?php echo esc_attr($qty); ?>" min="0" class="ctd-inventory-input">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ctd-add-custom-size-container">
                                        <input type="text" class="ctd-custom-size-input" placeholder="Custom size (e.g. 6XL)">
                                        <button type="button" class="button ctd-add-custom-size" data-color="<?php echo esc_attr($color_key); ?>"><?php _e('Add Size', 'custom-tshirt-designer'); ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="ctd_add_color" class="button"><?php _e('Add Color', 'custom-tshirt-designer'); ?></button>
            </div>

            <!-- Decoration Methods Section -->
            <div class="ctd-section">
                <h3 class="ctd-section-title"><?php _e('Decoration Methods', 'custom-tshirt-designer'); ?></h3>
                
                <div id="ctd_decoration_methods_container" class="ctd-decoration-methods-container">
                    <?php if (!empty($decoration_method_keys)): ?>
                        <?php for ($i = 0; $i < count($decoration_method_keys); $i++): ?>
                            <?php
                            $method_key = $decoration_method_keys[$i];
                            $method_name = isset($decoration_method_names[$i]) ? $decoration_method_names[$i] : '';
                            $method_fee = isset($decoration_method_fees[$i]) ? $decoration_method_fees[$i] : 0;
                            ?>
                            <div class="ctd-decoration-method-row">
                                <input type="text" name="_ctd_decoration_method_keys[]" value="<?php echo esc_attr($method_key); ?>" placeholder="Method key (e.g. screen_printing)" class="ctd-decoration-method-key">
                                <input type="text" name="_ctd_decoration_method_names[]" value="<?php echo esc_attr($method_name); ?>" placeholder="Method name (e.g. Screen Printing)" class="ctd-decoration-method-name">
                                <div class="ctd-decoration-method-fee">
                                    <label><?php _e('Setup Fee ($):', 'custom-tshirt-designer'); ?></label>
                                    <input type="number" name="_ctd_decoration_method_fees[]" value="<?php echo esc_attr($method_fee); ?>" step="0.01" min="0">
                                </div>
                                <div class="ctd-decoration-method-actions">
                                    <button type="button" class="button ctd-remove-decoration-method"><?php _e('Remove', 'custom-tshirt-designer'); ?></button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" id="ctd_add_decoration_method" class="button"><?php _e('Add Decoration Method', 'custom-tshirt-designer'); ?></button>
            </div>

            <!-- Tier Pricing Section -->
            <div class="ctd-section">
                <h3 class="ctd-section-title"><?php _e('Tier Pricing', 'custom-tshirt-designer'); ?></h3>
                
                <table id="ctd-tier-pricing-table" class="ctd-tier-pricing-table">
                    <thead>
                        <tr>
                            <th><?php _e('Min Quantity', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Max Quantity', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Discount (%)', 'custom-tshirt-designer'); ?></th>
                            <th><?php _e('Actions', 'custom-tshirt-designer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($tier_min)): ?>
                            <?php for ($i = 0; $i < count($tier_min); $i++): ?>
                                <?php
                                $min = $tier_min[$i];
                                $max = isset($tier_max[$i]) ? $tier_max[$i] : '';
                                $discount = isset($tier_discount[$i]) ? $tier_discount[$i] : '';
                                ?>
                                <tr class="ctd-tier-row">
                                    <td><input type="number" name="_ctd_tier_min[]" value="<?php echo esc_attr($min); ?>" min="0"></td>
                                    <td><input type="number" name="_ctd_tier_max[]" value="<?php echo esc_attr($max); ?>" min="0" placeholder="0 = no limit"></td>
                                    <td><input type="number" name="_ctd_tier_discount[]" value="<?php echo esc_attr($discount); ?>" min="0" max="100" step="0.1"></td>
                                    <td><button type="button" class="button ctd-remove-tier"><?php _e('Remove', 'custom-tshirt-designer'); ?></button></td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <button type="button" id="ctd_add_tier" class="button"><?php _e('Add Tier', 'custom-tshirt-designer'); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * Save product data
     */
    public function save_product_data($post_id) {
        // Save CTD enabled status
        $enabled = isset($_POST['_ctd_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ctd_enabled', $enabled);
        
        // Save base price
        if (isset($_POST['_ctd_base_price'])) {
            update_post_meta($post_id, '_ctd_base_price', sanitize_text_field($_POST['_ctd_base_price']));
        }
        
        // Save size type
        if (isset($_POST['_ctd_size_type'])) {
            update_post_meta($post_id, '_ctd_size_type', sanitize_text_field($_POST['_ctd_size_type']));
        }
        
        // Save colors
        if (isset($_POST['_ctd_color_keys'])) {
            $color_keys = array_map('sanitize_text_field', $_POST['_ctd_color_keys']);
            update_post_meta($post_id, '_ctd_color_keys', $color_keys);
        }
        
        if (isset($_POST['_ctd_color_names'])) {
            $color_names = array_map('sanitize_text_field', $_POST['_ctd_color_names']);
            update_post_meta($post_id, '_ctd_color_names', $color_names);
        }
        
        // Save sizes for each color
        if (isset($_POST['_ctd_color_sizes'])) {
            $color_sizes = array();
            foreach ($_POST['_ctd_color_sizes'] as $color_key => $sizes) {
                $color_sizes[sanitize_text_field($color_key)] = array_map('sanitize_text_field', $sizes);
            }
            update_post_meta($post_id, '_ctd_color_sizes', $color_sizes);
        }
        
        // Save inventory
        if (isset($_POST['_ctd_inventory'])) {
            $inventory = array();
            foreach ($_POST['_ctd_inventory'] as $color_key => $sizes) {
                foreach ($sizes as $size => $qty) {
                    $inventory[sanitize_text_field($color_key)][sanitize_text_field($size)] = intval($qty);
                }
            }
            update_post_meta($post_id, '_ctd_inventory', $inventory);
        }
        
        // Save decoration methods
        if (isset($_POST['_ctd_decoration_method_keys'])) {
            $method_keys = array_map('sanitize_text_field', $_POST['_ctd_decoration_method_keys']);
            update_post_meta($post_id, '_ctd_decoration_method_keys', $method_keys);
        }
        
        if (isset($_POST['_ctd_decoration_method_names'])) {
            $method_names = array_map('sanitize_text_field', $_POST['_ctd_decoration_method_names']);
            update_post_meta($post_id, '_ctd_decoration_method_names', $method_names);
        }
        
        if (isset($_POST['_ctd_decoration_method_fees'])) {
            $method_fees = array_map('floatval', $_POST['_ctd_decoration_method_fees']);
            update_post_meta($post_id, '_ctd_decoration_method_fees', $method_fees);
        }
        
        // Save tier pricing
        if (isset($_POST['_ctd_tier_min'])) {
            $tier_min = array_map('intval', $_POST['_ctd_tier_min']);
            update_post_meta($post_id, '_ctd_tier_min', $tier_min);
        }
        
        if (isset($_POST['_ctd_tier_max'])) {
            $tier_max = array_map('intval', $_POST['_ctd_tier_max']);
            update_post_meta($post_id, '_ctd_tier_max', $tier_max);
        }
        
        if (isset($_POST['_ctd_tier_discount'])) {
            $tier_discount = array_map('floatval', $_POST['_ctd_tier_discount']);
            update_post_meta($post_id, '_ctd_tier_discount', $tier_discount);
        }
    }