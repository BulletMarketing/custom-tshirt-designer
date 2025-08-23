<?php
/**
 * Email class for Custom T-Shirt Designer
 */
class CTD_Email {
    
    /**
     * Initialize email hooks
     */
    public function init() {
        // Send design notification email when order is placed
        add_action('woocommerce_checkout_order_processed', array($this, 'send_design_notification'), 20, 3);
        
        // Add designs to order emails
        add_action('woocommerce_email_order_details', array($this, 'add_designs_to_email'), 20, 4);
        
        // Filter email recipients
        add_filter('woocommerce_email_recipient_new_order', array($this, 'filter_new_order_email_recipients'), 10, 2);
    }
    
    /**
     * Send design notification email
     */
    public function send_design_notification($order_id, $posted_data, $order) {
        global $wpdb;
        
        // Check if any designs were uploaded
        $designs_table = $wpdb->prefix . 'ctd_designs';
        $designs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $designs_table WHERE order_id = %d",
            $order_id
        ));
        
        if (!$designs || count($designs) === 0) {
            return;
        }
        
        // Get additional recipients
        $additional_recipients = get_option('ctd_additional_recipients', '');
        $recipients = array();
        
        if (!empty($additional_recipients)) {
            $recipients = array_map('trim', explode(',', $additional_recipients));
        } else {
            // Use default admin email
            $recipients[] = get_option('admin_email');
        }
        
        // Get email subject prefix
        $subject_prefix = get_option('ctd_email_subject_prefix', '[Custom Design] ');
        
        // Build email subject
        $subject = $subject_prefix . sprintf(__('New Custom Design Order #%s', 'custom-tshirt-designer'), $order_id);
        
        // Start building email content
        $content = '<h2>' . __('New Custom Design Order', 'custom-tshirt-designer') . '</h2>';
        $content .= '<p>' . sprintf(__('Order #%s has been placed with custom designs.', 'custom-tshirt-designer'), $order_id) . '</p>';
        
        // Add order details
        $content .= '<h3>' . __('Order Details', 'custom-tshirt-designer') . '</h3>';
        $content .= '<p><strong>' . __('Order ID:', 'custom-tshirt-designer') . '</strong> ' . $order_id . '</p>';
        $content .= '<p><strong>' . __('Customer:', 'custom-tshirt-designer') . '</strong> ' . $order->get_formatted_billing_full_name() . '</p>';
        $content .= '<p><strong>' . __('Email:', 'custom-tshirt-designer') . '</strong> ' . $order->get_billing_email() . '</p>';
        $content .= '<p><strong>' . __('Total:', 'custom-tshirt-designer') . '</strong> ' . $order->get_formatted_order_total() . '</p>';
        
        // Add designs
        $content .= '<h3>' . __('Custom Designs', 'custom-tshirt-designer') . '</h3>';
        $content .= '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
        
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
                $content .= '<div style="text-align: center; margin-bottom: 20px;">';
                $content .= '<img src="' . esc_url($image_url) . '" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;" />';
                $content .= '<div style="margin-top: 5px;">' . esc_html($position) . '</div>';
                $content .= '</div>';
            }
        }
        
        $content .= '</div>';
        
        // Add link to order
        $order_url = admin_url('post.php?post=' . $order_id . '&action=edit');
        $content .= '<p><a href="' . esc_url($order_url) . '" style="display: inline-block; padding: 10px 15px; background-color: #2271b1; color: #fff; text-decoration: none; border-radius: 3px;">' . __('View Order', 'custom-tshirt-designer') . '</a></p>';
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );
        
        // Send email to each recipient
        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $content, $headers);
        }
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
            <div style="margin-top: 20px; margin-bottom: 20px;">
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
                        <div style="display: inline-block; margin: 0 10px 10px 0; text-align: center;">
                            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; background: #fff;" />
                            <div style="margin-top: 5px;"><?php echo esc_html($position); ?></div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
        }
    }
    
    /**
     * Filter new order email recipients
     */
    public function filter_new_order_email_recipients($recipient, $order) {
        // Check if this order has custom designs
        if (!$order) {
            return $recipient;
        }
        
        global $wpdb;
        $designs_table = $wpdb->prefix . 'ctd_designs';
        
        // Get all designs for this order
        $designs_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $designs_table WHERE order_id = %d",
            $order->get_id()
        ));
        
        if ($designs_count > 0) {
            // Get additional recipients
            $additional_recipients = get_option('ctd_additional_recipients', '');
            
            if (!empty($additional_recipients)) {
                // Add additional recipients to the existing ones
                if (!empty($recipient)) {
                    $recipient .= ',' . $additional_recipients;
                } else {
                    $recipient = $additional_recipients;
                }
            }
        }
        
        return $recipient;
    }
}
