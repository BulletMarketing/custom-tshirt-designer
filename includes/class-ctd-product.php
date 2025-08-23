<?php
/**
 * Product class for Custom T-Shirt Designer
 */
class CTD_Product {
    
    /**
     * Initialize product hooks
     */
    public function init() {
        // Add designer to product page
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_designer'));
        
        // Validate add to cart
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 5);
    }
    
    /**
     * Check if designer is enabled for product
     */
    public function is_designer_enabled($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('CTD Error: Table does not exist when checking if designer is enabled: ' . $table_name);
            // Default to disabled if table doesn't exist
            return false;
        }
        
        // Use direct query to get enabled status with explicit casting
        $query = $wpdb->prepare("SELECT enabled FROM $table_name WHERE product_id = %d", $product_id);
        error_log('CTD is_designer_enabled query: ' . $query);
        
        $enabled = $wpdb->get_var($query);
        
        // If no record found for this product, default to disabled
        if ($enabled === null) {
            error_log('CTD is_designer_enabled: No record found for product ID ' . $product_id);
            return false;
        }
        
        // Debug output
        error_log('CTD is_designer_enabled for product ID ' . $product_id . ': ' . ($enabled ? 'Yes' : 'No') . ' (raw value: ' . $enabled . ')');
        
        if ($wpdb->last_error) {
            error_log('CTD Database Error: ' . $wpdb->last_error);
        }
        
        // Ensure we're returning a proper boolean
        return (bool) (int) $enabled;
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
            
            // Return a default config object as fallback
            $default_config = new stdClass();
            $default_config->enabled = 1;
            $default_config->product_type = 'shirt';
            $default_config->sizes = serialize(array('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'));
            $default_config->colors = serialize(array(
                'white' => 'White',
                'black' => 'Black',
                'red' => 'Red',
                'blue' => 'Blue',
                'green' => 'Green',
                'yellow' => 'Yellow',
                'purple' => 'Purple',
                'gray' => 'Gray'
            ));
            $default_config->decoration_methods = serialize(array(
                'screen_printing' => 'Screen Printing',
                'dtg' => 'Direct to Garment (DTG)',
                'embroidery' => 'Embroidery',
                'heat_transfer' => 'Heat Transfer'
            ));
            $default_config->setup_fees = serialize(array(
                'screen_printing' => 10.95,
                'dtg' => 8.95,
                'embroidery' => 15.95,
                'heat_transfer' => 7.95
            ));
            $default_config->tier_pricing = serialize(array(
                array('min' => 50, 'max' => 99, 'discount' => 5),
                array('min' => 100, 'max' => 249, 'discount' => 10),
                array('min' => 250, 'max' => 499, 'discount' => 15),
                array('min' => 500, 'max' => 999, 'discount' => 20),
                array('min' => 1000, 'max' => 0, 'discount' => 25)
            ));
            
            return $default_config;
        }
        
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d", $product_id));
        
        // If no config found for this product, return a default config
        if (!$config) {
            $config = new stdClass();
            $config->enabled = 0; // Default to disabled
            $config->product_type = 'shirt';
            $config->sizes = serialize(array('XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'));
            $config->colors = serialize(array(
                'white' => 'White',
                'black' => 'Black',
                'red' => 'Red',
                'blue' => 'Blue',
                'green' => 'Green',
                'yellow' => 'Yellow',
                'purple' => 'Purple',
                'gray' => 'Gray'
            ));
            $config->decoration_methods = serialize(array(
                'screen_printing' => 'Screen Printing',
                'dtg' => 'Direct to Garment (DTG)',
                'embroidery' => 'Embroidery',
                'heat_transfer' => 'Heat Transfer'
            ));
            $config->setup_fees = serialize(array(
                'screen_printing' => 10.95,
                'dtg' => 8.95,
                'embroidery' => 15.95,
                'heat_transfer' => 7.95
            ));
            $config->tier_pricing = serialize(array(
                array('min' => 50, 'max' => 99, 'discount' => 5),
                array('min' => 100, 'max' => 249, 'discount' => 10),
                array('min' => 250, 'max' => 499, 'discount' => 15),
                array('min' => 500, 'max' => 999, 'discount' => 20),
                array('min' => 1000, 'max' => 0, 'discount' => 25)
            ));
        }
        
        if ($wpdb->last_error) {
            error_log('CTD Database Error: ' . $wpdb->last_error);
        }
        
        // Debug output
        error_log('CTD get_product_config for product ID ' . $product_id . ': ' . print_r($config, true));
        
        return $config;
    }
    
    /**
     * Display designer on product page
     */
    public function display_designer() {
        global $product;
        
        if (!$product) {
            error_log('CTD Error: No product found when displaying designer');
            return;
        }
        
        $product_id = $product->get_id();
        error_log('CTD Attempting to display designer for product ID: ' . $product_id);
        
        // Check if designer is enabled for this product
        if (!$this->is_designer_enabled($product_id)) {
            error_log('CTD Designer is not enabled for product ID: ' . $product_id);
            return;
        }
        
        // Get product configuration
        $config = $this->get_product_config($product_id);
        if (!$config) {
            error_log('CTD Error: No configuration found for product ID: ' . $product_id);
            return;
        }
        
        error_log('CTD Displaying designer for product ID: ' . $product_id);
        
        $product_type = $config->product_type;
        
        // Get sizes from product configuration (globally and per color)
        $sizes = maybe_unserialize($config->sizes);
        $color_sizes = isset($config->color_sizes) ? maybe_unserialize($config->color_sizes) : array();
        $inventory_enabled = isset($config->inventory_enabled) ? $config->inventory_enabled : 0;
        $inventory = isset($config->inventory) ? maybe_unserialize($config->inventory) : array();
        
        // Get product image for overlay
        $image_id = $product->get_image_id();
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('full');
        }
        
        // Get product gallery images for different views
        $gallery_image_ids = $product->get_gallery_image_ids();
        $front_image = $image_url; // Default front image is the main product image
        $back_image = $image_url;  // Default to main image
        $side_image = $image_url;  // Default to main image
        
        // If gallery images exist, use them for back and side views
        if (!empty($gallery_image_ids)) {
            if (isset($gallery_image_ids[0])) {
                $back_image = wp_get_attachment_image_url($gallery_image_ids[0], 'full') ?: $image_url;
            }
            if (isset($gallery_image_ids[1])) {
                $side_image = wp_get_attachment_image_url($gallery_image_ids[1], 'full') ?: $image_url;
            }
        }
        
        // Apply filters for product images
        $front_image = apply_filters('ctd_product_front_image', $front_image, $product_id, $product_type);
        $back_image = apply_filters('ctd_product_back_image', $back_image, $product_id, $product_type);
        $side_image = apply_filters('ctd_product_side_image', $side_image, $product_id, $product_type);
        
        // Get colors from product configuration
        $colors = maybe_unserialize($config->colors);
        if (empty($colors)) {
            // Default colors if none are set
            $colors = array(
                '#FFFFFF' => __('White', 'custom-tshirt-designer'),
                '#000000' => __('Black', 'custom-tshirt-designer'),
                '#FF0000' => __('Red', 'custom-tshirt-designer'),
                '#0000FF' => __('Blue', 'custom-tshirt-designer'),
                '#008000' => __('Green', 'custom-tshirt-designer'),
                '#FFFF00' => __('Yellow', 'custom-tshirt-designer'),
                '#800080' => __('Purple', 'custom-tshirt-designer'),
                '#808080' => __('Gray', 'custom-tshirt-designer'),
            );
        }

        // Debug output for colors
        error_log('CTD Frontend - Colors for product ID ' . $product_id . ': ' . print_r($colors, true));
        
        // Apply filter for colors (for backward compatibility)
        $colors = apply_filters('ctd_product_colors', $colors, $product_id, $product_type);
        
        // Get decoration methods
        $decoration_methods = isset($config->decoration_methods) ? maybe_unserialize($config->decoration_methods) : array(
            'screen_printing' => __('Screen Printing', 'custom-tshirt-designer'),
            'dtg' => __('Direct to Garment (DTG)', 'custom-tshirt-designer'),
            'embroidery' => __('Embroidery', 'custom-tshirt-designer'),
            'heat_transfer' => __('Heat Transfer', 'custom-tshirt-designer'),
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
            array('min' => 1000, 'max' => 0, 'discount' => 25)
        );
        
        // Sort tier pricing by min quantity
        usort($tier_pricing, function($a, $b) {
            return $a['min'] - $b['min'];
        });
        
        // Get related products
        $related_product_ids = wc_get_related_products($product_id, 4);
        $upsell_product_ids = $product->get_upsell_ids();
        $cross_sell_product_ids = $product->get_cross_sell_ids();
        
        // Combine all related products
        $related_products = array_unique(array_merge($related_product_ids, $upsell_product_ids, $cross_sell_product_ids));
        $related_products = array_slice($related_products, 0, 3); // Limit to 3 products
        
        // Get color-specific sizes
        $color_sizes = array();
        if (isset($config->color_sizes) && !empty($config->color_sizes)) {
            $color_sizes = maybe_unserialize($config->color_sizes);
            error_log('CTD Frontend - Color sizes for product ID ' . $product_id . ': ' . print_r($color_sizes, true));
        }

        // Ensure all colors have size data
        foreach ($colors as $color_key => $color_name) {
            if (!isset($color_sizes[$color_key])) {
                // If this color doesn't have sizes yet, add default sizes
                $color_sizes[$color_key] = $sizes;
                error_log("CTD Frontend - Adding default sizes for color: {$color_key}");
            }
        }
        
        // Output designer HTML
        ?>
        <div id="ctd-designer" class="ctd-designer" data-product-id="<?php echo esc_attr($product_id); ?>" data-product-type="<?php echo esc_attr($product_type); ?>">
            <h3><?php _e('Custom Design Options', 'custom-tshirt-designer'); ?></h3>
            
            <div class="ctd-steps">
                <!-- Step 1: Color Selection -->
                <div class="ctd-step ctd-step-colors">
                    <h4><?php _e('Step 1: Select Color(s)', 'custom-tshirt-designer'); ?></h4>
                    
                    <div class="ctd-color-toggle">
                        <label>
                            <input type="checkbox" id="ctd-multi-color" name="ctd_multi_color" value="1">
                            <?php _e('Select multiple colors', 'custom-tshirt-designer'); ?>
                        </label>
                    </div>
                    
                    <div class="ctd-color-swatches">
                        <?php foreach ($colors as $color_key => $color_name) : ?>
                            <div class="ctd-color-swatch">
                                <label>
                                    <input type="checkbox" name="ctd_colors[]" value="<?php echo esc_attr($color_key); ?>" class="ctd-color-input">
                                    <span class="ctd-swatch" style="background-color: <?php echo esc_attr($color_key); ?>;" title="<?php echo esc_attr($color_name); ?>"></span>
                                    <span class="ctd-color-name"><?php echo esc_html($color_name); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Step 2: Size Selection -->
                <div class="ctd-step ctd-step-sizes">
                    <h4><?php _e('Step 2: Select Sizes & Quantities', 'custom-tshirt-designer'); ?></h4>
                    
                    <div class="ctd-size-tables-container">
                        <!-- Size tables will be dynamically generated here based on selected colors -->
                        <div class="ctd-size-table-wrapper" data-color="default">
                            <table class="ctd-size-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Size', 'custom-tshirt-designer'); ?></th>
                                        <?php foreach ($sizes as $size) : ?>
                                            <th><?php echo esc_html($size); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php _e('Quantity', 'custom-tshirt-designer'); ?></td>
                                        <?php foreach ($sizes as $size) : ?>
                                            <td>
                                                <input type="number" name="ctd_quantity[default][<?php echo esc_attr($size); ?>]" min="0" value="0" class="ctd-quantity-input">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="ctd-total-quantity">
                        <p><?php _e('Total Quantity:', 'custom-tshirt-designer'); ?> <span id="ctd-total-quantity">0</span></p>
                        <p class="ctd-min-quantity-notice"><?php printf(__('Minimum order quantity: %d items', 'custom-tshirt-designer'), CTD_MINIMUM_ORDER_QUANTITY); ?></p>
                    </div>
                    
                    <?php if (!empty($tier_pricing)) : ?>
                    <div class="ctd-tier-pricing-info">
                        <h5><?php _e('Bulk Discounts: 5–25%. Add More to Cart to Save.', 'custom-tshirt-designer'); ?></h5>
                        <p id="ctd-next-tier-message" class="ctd-next-tier-message"></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Step 3: Upload Your Designs -->
                <div class="ctd-step ctd-step-designs">
                    <h4><?php _e('Step 3: Upload Your Designs', 'custom-tshirt-designer'); ?></h4>
                    
                    <!-- Decoration Method Selection - Single selection for all positions -->
                    <div class="ctd-decoration-method-container">
                        <label for="ctd-decoration-method"><?php _e('Decoration Method:', 'custom-tshirt-designer'); ?></label>
                        <select name="ctd_decoration_method" id="ctd-decoration-method" class="ctd-decoration-select">
                            <option value=""><?php _e('Select Method', 'custom-tshirt-designer'); ?></option>
                            <?php foreach ($decoration_methods as $method_key => $method_name) : ?>
                                <option value="<?php echo esc_attr($method_key); ?>" data-setup-fee="<?php echo isset($setup_fees[$method_key]) ? esc_attr($setup_fees[$method_key]) : '0'; ?>"><?php echo esc_html($method_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="ctd-decoration-method-info" id="ctd-setup-fee-info"></div>
                        
                        <div class="ctd-decoration-help">
                            <a href="#" id="ctd-decoration-help-link"><?php _e('Need help choosing? See Decoration Methods Explained', 'custom-tshirt-designer'); ?></a>
                        </div>
                    </div>
                    
                    <!-- Upload Squares -->
                    <div class="ctd-upload-squares">
                        <!-- Front Upload Square -->
                        <div class="ctd-upload-square" data-position="front">
                            <div class="ctd-upload-square-label"><?php _e('Front Design', 'custom-tshirt-designer'); ?></div>
                            <div class="ctd-upload-square-icon">+</div>
                            <div class="ctd-upload-square-text"><?php _e('Click to upload', 'custom-tshirt-designer'); ?></div>
                            <input type="file" name="ctd_design_front" id="ctd-design-front" class="ctd-design-upload" accept="image/*" data-position="front">
                            <div class="ctd-upload-square-preview" style="display: none;"></div>
                        </div>
                        
                        <!-- Back Upload Square -->
                        <div class="ctd-upload-square" data-position="back">
                            <div class="ctd-upload-square-label"><?php _e('Back Design', 'custom-tshirt-designer'); ?></div>
                            <div class="ctd-upload-square-icon">+</div>
                            <div class="ctd-upload-square-text"><?php _e('Click to upload', 'custom-tshirt-designer'); ?></div>
                            <input type="file" name="ctd_design_back" id="ctd-design-back" class="ctd-design-upload" accept="image/*" data-position="back">
                            <div class="ctd-upload-square-preview" style="display: none;"></div>
                        </div>
                        
                        <!-- Side Upload Square -->
                        <div class="ctd-upload-square" data-position="side">
                            <div class="ctd-upload-square-label"><?php _e('Side/Other Design', 'custom-tshirt-designer'); ?></div>
                            <div class="ctd-upload-square-icon">+</div>
                            <div class="ctd-upload-square-text"><?php _e('Click to upload', 'custom-tshirt-designer'); ?></div>
                            <input type="file" name="ctd_design_side" id="ctd-design-side" class="ctd-design-upload" accept="image/*" data-position="side">
                            <div class="ctd-upload-square-preview" style="display: none;"></div>
                        </div>
                    </div>
                    
                    <!-- Floating Design Controls (initially hidden) -->
                    <div id="ctd-floating-controls" class="ctd-floating-controls">
                        <div class="ctd-control-group">
                            <button type="button" class="ctd-control-btn ctd-zoom-in" title="<?php _e('Zoom In', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">+</span>
                            </button>
                            <button type="button" class="ctd-control-btn ctd-zoom-out" title="<?php _e('Zoom Out', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">-</span>
                            </button>
                        </div>
                        <div class="ctd-control-group">
                            <button type="button" class="ctd-control-btn ctd-rotate-left" title="<?php _e('Rotate Left', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">↺</span>
                            </button>
                            <button type="button" class="ctd-control-btn ctd-rotate-right" title="<?php _e('Rotate Right', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">↻</span>
                            </button>
                        </div>
                        <div class="ctd-control-group">
                            <button type="button" class="ctd-control-btn ctd-move-up" title="<?php _e('Move Up', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">↑</span>
                            </button>
                            <button type="button" class="ctd-control-btn ctd-move-down" title="<?php _e('Move Down', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">↓</span>
                            </button>
                            <button type="button" class="ctd-control-btn ctd-move-left" title="<?php _e('Move Left', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">←</span>
                            </button>
                            <button type="button" class="ctd-control-btn ctd-move-right" title="<?php _e('Move Right', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">→</span>
                            </button>
                        </div>
                        <div class="ctd-control-group">
                            <button type="button" class="ctd-control-btn ctd-reset" title="<?php _e('Reset Position', 'custom-tshirt-designer'); ?>">
                                <span class="ctd-icon">↺</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Decoration Methods Modal -->
                    <div id="ctd-decoration-help-modal" class="ctd-modal">
                        <div class="ctd-modal-content">
                            <span class="ctd-modal-close">&times;</span>
                            <h3><?php _e('Decoration Methods Explained', 'custom-tshirt-designer'); ?></h3>
                            <div class="ctd-decoration-methods-info">
                                <div class="ctd-decoration-method-info">
                                    <h4><?php _e('Screen Printing', 'custom-tshirt-designer'); ?></h4>
                                    <p><?php _e('Best for large quantities and solid colors. Durable and cost-effective for bulk orders.', 'custom-tshirt-designer'); ?></p>
                                </div>
                                <div class="ctd-decoration-method-info">
                                    <h4><?php _e('Direct to Garment (DTG)', 'custom-tshirt-designer'); ?></h4>
                                    <p><?php _e('Ideal for detailed, multi-colored designs. Great for small quantities and photographic images.', 'custom-tshirt-designer'); ?></p>
                                </div>
                                <div class="ctd-decoration-method-info">
                                    <h4><?php _e('Embroidery', 'custom-tshirt-designer'); ?></h4>
                                    <p><?php _e('Premium look with raised stitching. Best for logos and simple designs. Adds texture and dimension.', 'custom-tshirt-designer'); ?></p>
                                </div>
                                <div class="ctd-decoration-method-info">
                                    <h4><?php _e('Heat Transfer', 'custom-tshirt-designer'); ?></h4>
                                    <p><?php _e('Versatile method for various fabrics. Good for small runs and full-color designs.', 'custom-tshirt-designer'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Related Products -->
                <div class="ctd-step ctd-step-related">
                    <h4><?php _e('Step 4: Related Products', 'custom-tshirt-designer'); ?></h4>
                    
                    <?php if (!empty($related_products)) : ?>
                        <div class="ctd-related-products">
                            <ul class="products">
                                <?php foreach ($related_products as $related_product_id) : 
                                    $related_product = wc_get_product($related_product_id);
                                    if (!$related_product) continue;
                                ?>
                                    <li class="product">
                                        <a href="<?php echo esc_url(get_permalink($related_product_id)); ?>" target="_blank">
                                            <?php echo $related_product->get_image(); ?>
                                            <h2 class="woocommerce-loop-product__title"><?php echo esc_html($related_product->get_name()); ?></h2>
                                            <span class="price"><?php echo $related_product->get_price_html(); ?></span>
                                        </a>
                                        <a href="<?php echo esc_url($related_product->add_to_cart_url()); ?>" class="button add_to_cart_button" target="_blank"><?php _e('Add to cart', 'custom-tshirt-designer'); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else : ?>
                        <p><?php _e('No related products found.', 'custom-tshirt-designer'); ?></p>
                    <?php endif; ?>
                    
                    <div class="ctd-total-cost">
                        <h3><?php _e('Total Estimated Cost:', 'custom-tshirt-designer'); ?> <span id="ctd-total-cost">$0.00</span></h3>
                        <p class="ctd-cost-breakdown">
                            <span id="ctd-product-cost-breakdown">Product cost: $0.00</span><br>
                            <span id="ctd-setup-fee-breakdown">Setup fees: $0.00</span><br>
                            <span id="ctd-discount-breakdown" style="display: none;">Discount: -$0.00</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="ctd-validation-messages"></div>
            
            <!-- Hidden fields to store design data -->
            <input type="hidden" name="ctd_design_data" id="ctd-design-data" value="">
            <input type="hidden" name="ctd_setup_fee" id="ctd-setup-fee" value="0">
            <input type="hidden" name="ctd_positions" id="ctd-positions" value="">
            <input type="hidden" name="ctd_composite_images" id="ctd-composite-images" value="">
            <input type="hidden" name="ctd_product_price" id="ctd-product-price" value="<?php echo esc_attr($product->get_price()); ?>">
            
            <!-- Hidden fields for product images -->
            <input type="hidden" id="ctd-front-image" value="<?php echo esc_url($front_image); ?>">
            <input type="hidden" id="ctd-back-image" value="<?php echo esc_url($back_image); ?>">
            <input type="hidden" id="ctd-side-image" value="<?php echo esc_url($side_image); ?>">
            
            <!-- Hidden fields for setup fees and tier pricing -->
            <input type="hidden" name="ctd_setup_fees" id="ctd-setup-fees" value="<?php echo esc_attr(json_encode($setup_fees)); ?>">
            <input type="hidden" name="ctd_tier_pricing" id="ctd-tier-pricing" value="<?php echo esc_attr(json_encode($tier_pricing)); ?>">

            <!-- Hidden fields for design overlay -->
            <input type="hidden" id="ctd-design-overlay-active" value="0">
            <input type="hidden" id="ctd-front-image" value="<?php echo esc_url($front_image); ?>">
            <input type="hidden" id="ctd-back-image" value="<?php echo esc_url($back_image); ?>">
            <input type="hidden" id="ctd-side-image" value="<?php echo esc_url($side_image); ?>">

            <!-- Hidden fields for color-specific sizes and inventory -->
            <input type="hidden" id="ctd-color-sizes" value="<?php echo esc_attr(json_encode($color_sizes)); ?>">
            <input type="hidden" id="ctd-inventory-enabled" value="<?php echo esc_attr($inventory_enabled); ?>">
            <input type="hidden" id="ctd-inventory" value="<?php echo esc_attr(json_encode($inventory)); ?>">
        </div>
        <?php
        
        error_log('CTD Designer HTML output complete');
    }
    
    /**
     * Validate add to cart
     */
    public function validate_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
        // Check if designer is enabled for this product
        if (!$this->is_designer_enabled($product_id)) {
            return $passed;
        }
        
        // Check if design data is provided
        if (!isset($_POST['ctd_design_data']) || empty($_POST['ctd_design_data'])) {
            wc_add_notice(__('Please complete the custom design options.', 'custom-tshirt-designer'), 'error');
            return false;
        }
        
        // Decode design data
        $design_data = json_decode(stripslashes($_POST['ctd_design_data']), true);
        if (!$design_data) {
            wc_add_notice(__('Invalid design data.', 'custom-tshirt-designer'), 'error');
            return false;
        }
        
        // Check total quantity
        $total_quantity = 0;
        if (isset($design_data['quantities'])) {
            foreach ($design_data['quantities'] as $color => $sizes) {
                foreach ($sizes as $size => $qty) {
                    $total_quantity += intval($qty);
                }
            }
        }
        
        if ($total_quantity < CTD_MINIMUM_ORDER_QUANTITY) {
            wc_add_notice(sprintf(__('Minimum order quantity is %d items.', 'custom-tshirt-designer'), CTD_MINIMUM_ORDER_QUANTITY), 'error');
            return false;
        }
        
        // Check inventory if enabled
        if ($total_quantity >= CTD_MINIMUM_ORDER_QUANTITY) {
            // Get product configuration
            $config = $this->get_product_config($product_id);
            
            // Check if inventory tracking is enabled
            if (isset($config->inventory_enabled) && $config->inventory_enabled) {
                $inventory = isset($config->inventory) ? maybe_unserialize($config->inventory) : array();
                
                // Check if we have enough inventory for each color and size
                $inventory_error = false;
                $out_of_stock_items = array();
                
                foreach ($design_data['quantities'] as $color => $sizes) {
                    foreach ($sizes as $size => $qty) {
                        if ($qty > 0) {
                            $available = isset($inventory[$color][$size]) ? intval($inventory[$color][$size]) : 0;
                            
                            if ($available < $qty) {
                                $inventory_error = true;
                                $color_name = isset($design_data['color_names'][$color]) ? $design_data['color_names'][$color] : $color;
                                $out_of_stock_items[] = sprintf('%s (%s) - %d requested, %d available', 
                                                      $color_name, $size, $qty, $available);
                    }
                }
            }
        }
        
        if ($inventory_error) {
            $message = __('Some items are out of stock or have insufficient quantity:', 'custom-tshirt-designer');
            $message .= '<ul>';
            foreach ($out_of_stock_items as $item) {
                $message .= '<li>' . $item . '</li>';
            }
            $message .= '</ul>';
            
            wc_add_notice($message, 'error');
            return false;
        }
    }
}
        
        // Check if at least one color is selected
        if (!isset($design_data['colors']) || empty($design_data['colors'])) {
            wc_add_notice(__('Please select at least one color.', 'custom-tshirt-designer'), 'error');
            return false;
        }
        
        // Check if at least one position has a design
        if (!isset($design_data['positions']) || empty($design_data['positions'])) {
            wc_add_notice(__('Please upload at least one design.', 'custom-tshirt-designer'), 'error');
            return false;
        }
        
        // We no longer require decoration method to be selected
        
        // Calculate and store the total price
        if (isset($design_data['quantities'])) {
            $total_quantity = 0;
            foreach ($design_data['quantities'] as $color => $sizes) {
                foreach ($sizes as $size => $qty) {
                    $total_quantity += intval($qty);
                }
            }
            
            // Get product price
            $product = wc_get_product($product_id);
            $unit_price = $product->get_price();
            
            // Calculate total product price
            $product_total = $unit_price * $total_quantity;
            
            // Get setup fees
            $config = $this->get_product_config($product_id);
            $setup_fees = isset($config->setup_fees) ? maybe_unserialize($config->setup_fees) : array();
            
            // Calculate setup fee based on decoration method
            $setup_fee_total = 0;
            if (isset($design_data['decoration_method'])) {
                $method = $design_data['decoration_method'];
                if (isset($setup_fees[$method])) {
                    // Multiply by the number of positions with designs
                    $setup_fee_total = floatval($setup_fees[$method]) * count($design_data['positions']);
                }
            }
            
            // Apply quantity discount if applicable
            $discount_amount = 0;
            $tier_pricing = isset($config->tier_pricing) ? maybe_unserialize($config->tier_pricing) : array();

            if (!empty($tier_pricing)) {
                // Sort tier pricing by min quantity in descending order
                usort($tier_pricing, function($a, $b) {
                    return $b['min'] - $a['min'];
                });
                
                // Find applicable discount
                foreach ($tier_pricing as $tier) {
                    if ($total_quantity >= $tier['min'] && ($tier['max'] == 0 || $total_quantity <= $tier['max'])) {
                        $discount_percent = floatval($tier['discount']);
                        $discount_amount = ($product_total * $discount_percent) / 100;
                        break;
                    }
                }
            }
            
            // Calculate final total
            $final_total = $product_total + $setup_fee_total - $discount_amount;
            
            // Store the calculated values
            $_POST['ctd_calculated_product_total'] = $product_total;
            $_POST['ctd_calculated_setup_fee'] = $setup_fee_total;
            $_POST['ctd_calculated_discount'] = $discount_amount;
            $_POST['ctd_calculated_total'] = $final_total;
        }
        
        return $passed;
    }
}
