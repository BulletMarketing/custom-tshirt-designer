<?php
/**
 * Cart class for Custom T-Shirt Designer
 */
class CTD_Cart {
    
    /**
     * Initialize cart hooks
     */
    public function init() {
        // Add custom data to cart item
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        
        // Display custom data in cart
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
        
        // Add custom price to cart item
        add_action('woocommerce_before_calculate_totals', array($this, 'calculate_totals'), 10, 1);
        
        // Save custom data to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 10, 4);
        
        // Save design data to order
        add_action('woocommerce_checkout_order_processed', array($this, 'save_order_design_data'), 10, 3);
    }
    
    // Update the add_cart_item_data function to use the product's setup fee
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Check if designer data is provided
        if (!isset($_POST['ctd_design_data']) || empty($_POST['ctd_design_data'])) {
            return $cart_item_data;
        }
        
        // Get design data
        $design_data = json_decode(stripslashes($_POST['ctd_design_data']), true);
        if (!$design_data) {
            return $cart_item_data;
        }
        
        // Add design data to cart item
        $cart_item_data['ctd_design_data'] = $design_data;
        
        // Add setup fee - use the calculated setup fee from validation
        $setup_fee = isset($_POST['ctd_calculated_setup_fee']) ? floatval($_POST['ctd_calculated_setup_fee']) : 0;
        if (!$setup_fee && isset($_POST['ctd_setup_fee'])) {
            $setup_fee = floatval($_POST['ctd_setup_fee']);
        }
        $cart_item_data['ctd_setup_fee'] = $setup_fee;
        
        // Add positions
        $positions = isset($_POST['ctd_positions']) ? explode(',', $_POST['ctd_positions']) : array();
        $cart_item_data['ctd_positions'] = $positions;
        
        // Add composite images if available
        if (isset($_POST['ctd_composite_images']) && !empty($_POST['ctd_composite_images'])) {
            $cart_item_data['ctd_composite_images'] = json_decode(stripslashes($_POST['ctd_composite_images']), true);
        }
        
        // Add unique key to prevent merging
        $cart_item_data['unique_key'] = md5(microtime() . rand());
        
        return $cart_item_data;
    }
    
    /**
     * Display custom data in cart
     */
    public function get_item_data($item_data, $cart_item) {
        if (isset($cart_item['ctd_design_data'])) {
            $design_data = $cart_item['ctd_design_data'];
            
            // Add colors
            if (isset($design_data['colors']) && !empty($design_data['colors'])) {
                $colors = implode(', ', $design_data['colors']);
                $item_data[] = array(
                    'key'   => __('Colors', 'custom-tshirt-designer'),
                    'value' => $colors,
                );
            }
            
            // Add positions
            if (isset($design_data['positions']) && !empty($design_data['positions'])) {
                $positions = implode(', ', $design_data['positions']);
                $item_data[] = array(
                    'key'   => __('Design Positions', 'custom-tshirt-designer'),
                    'value' => $positions,
                );
            }
            
            // Add total quantity
            if (isset($design_data['quantities'])) {
                $total_quantity = 0;
                foreach ($design_data['quantities'] as $color => $sizes) {
                    foreach ($sizes as $size => $qty) {
                        $total_quantity += intval($qty);
                    }
                }
                
                $item_data[] = array(
                    'key'   => __('Total Quantity', 'custom-tshirt-designer'),
                    'value' => $total_quantity,
                );
            }
            
            // Add setup fee
            if (isset($cart_item['ctd_setup_fee']) && $cart_item['ctd_setup_fee'] > 0) {
                $item_data[] = array(
                    'key'   => __('Setup Fee', 'custom-tshirt-designer'),
                    'value' => wc_price($cart_item['ctd_setup_fee']),
                );
            }
        }
        
        return $item_data;
    }
    
    // Update the calculate_totals function to properly calculate the price
    public function calculate_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['ctd_design_data']) && isset($cart_item['ctd_setup_fee'])) {
                $product = $cart_item['data'];
                $base_price = $product->get_price();
                
                // Get quantities from design data
                $design_data = $cart_item['ctd_design_data'];
                $total_quantity = 0;
                
                if (isset($design_data['quantities'])) {
                    foreach ($design_data['quantities'] as $color => $sizes) {
                        foreach ($sizes as $size => $qty) {
                            $total_quantity += intval($qty);
                        }
                    }
                }
                
                // Calculate total price: (base price * quantity) + setup fee
                $total_price = ($base_price * $total_quantity) + $cart_item['ctd_setup_fee'];
                
                // Set the new price
                $product->set_price($total_price);
                
                // Store the quantity for display
                $cart_item['ctd_total_quantity'] = $total_quantity;
            }
        }
    }
    
    /**
     * Save custom data to order line item
     */
    public function checkout_create_order_line_item($item, $cart_item_key, $values, $order) {
        if (isset($values['ctd_design_data'])) {
            $item->add_meta_data('_ctd_design_data', $values['ctd_design_data']);
        }
        
        if (isset($values['ctd_setup_fee'])) {
            $item->add_meta_data('_ctd_setup_fee', $values['ctd_setup_fee']);
        }
        
        if (isset($values['ctd_positions'])) {
            $item->add_meta_data('_ctd_positions', $values['ctd_positions']);
        }
        
        if (isset($values['ctd_composite_images'])) {
            $item->add_meta_data('_ctd_composite_images', $values['ctd_composite_images']);
        }
    }
    
    /**
     * Save design data to order
     */
    public function save_order_design_data($order_id, $posted_data, $order) {
        global $wpdb;
        
        // Get order items
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        
        foreach ($items as $item_id => $item) {
            // Get design data
            $design_data = $item->get_meta('_ctd_design_data');
            if (!$design_data) {
                continue;
            }
            
            $product_id = $item->get_product_id();
            $setup_fee = $item->get_meta('_ctd_setup_fee');
            $positions = $item->get_meta('_ctd_positions');
            $composite_images = $item->get_meta('_ctd_composite_images');
            
            // Calculate total quantity
            $total_quantity = 0;
            if (isset($design_data['quantities'])) {
                foreach ($design_data['quantities'] as $color => $sizes) {
                    foreach ($sizes as $size => $qty) {
                        $total_quantity += intval($qty);
                    }
                }
            }
            
            // Save order metadata
            $table_name = $wpdb->prefix . 'ctd_order_meta';
            $wpdb->insert(
                $table_name,
                array(
                    'order_id'       => $order_id,
                    'order_item_id'  => $item_id,
                    'product_id'     => $product_id,
                    'colors'         => isset($design_data['colors']) ? maybe_serialize($design_data['colors']) : '',
                    'sizes'          => isset($design_data['quantities']) ? maybe_serialize($design_data['quantities']) : '',
                    'setup_fees'     => $setup_fee,
                    'total_quantity' => $total_quantity,
                )
            );
            
            // Save design uploads
            if (isset($design_data['designs']) && !empty($design_data['designs'])) {
                $table_name_designs = $wpdb->prefix . 'ctd_designs';
                
                foreach ($design_data['designs'] as $position => $design) {
                    // Get decoration method
                    $decoration_method = isset($design_data['decoration_methods'][$position]) ? $design_data['decoration_methods'][$position] : '';
                    
                    // Get composite image if available
                    $composite_image = '';
                    if (isset($composite_images[$position])) {
                        $composite_image = $composite_images[$position];
                    }
                    
                    // Save design data
                    $wpdb->insert(
                        $table_name_designs,
                        array(
                            'order_id'          => $order_id,
                            'order_item_id'     => $item_id,
                            'product_id'        => $product_id,
                            'position'          => $position,
                            'file_path'         => $design,
                            'decoration_method' => $decoration_method,
                            'composite_image'   => $composite_image,
                        )
                    );
                }
            }
        }
    }
}
