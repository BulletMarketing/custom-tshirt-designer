<?php
/**
 * AJAX class for Custom T-Shirt Designer
 */
class CTD_Ajax {
    
    /**
     * Initialize AJAX hooks
     */
    public function init() {
        // Upload design
        add_action('wp_ajax_ctd_upload_design', array($this, 'upload_design'));
        add_action('wp_ajax_nopriv_ctd_upload_design', array($this, 'upload_design'));
        
        // Remove design
        add_action('wp_ajax_ctd_remove_design', array($this, 'remove_design'));
        add_action('wp_ajax_nopriv_ctd_remove_design', array($this, 'remove_design'));
        
        // Save composite image
        add_action('wp_ajax_ctd_save_composite', array($this, 'save_composite'));
        add_action('wp_ajax_nopriv_ctd_save_composite', array($this, 'save_composite'));
    }
    
    /**
     * Upload design
     */
    public function upload_design() {
        // Check nonce
        check_ajax_referer('ctd-nonce', 'nonce');
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('Upload design AJAX request received', 'info');
            CTD_Debug::log_var($_FILES, 'Files', 'info');
            CTD_Debug::log_var($_POST, 'POST data', 'info');
        }
        
        // Check if file is uploaded
        if (!isset($_FILES['design']) || empty($_FILES['design']['name'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'custom-tshirt-designer')));
        }
        
        // Get position
        $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 'front';
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $ctd_dir = $upload_dir['basedir'] . '/ctd-designs';
        
        // Create directory if it doesn't exist
        if (!file_exists($ctd_dir)) {
            wp_mkdir_p($ctd_dir);
            
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Created upload directory: ' . $ctd_dir, 'info');
            }
        }
        
        // Get file info
        $file = $_FILES['design'];
        $file_name = sanitize_file_name($file['name']);
        $file_tmp = $file['tmp_name'];
        $file_type = $file['type'];
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type. Only JPG, PNG, and GIF files are allowed.', 'custom-tshirt-designer')));
        }
        
        // Generate unique file name
        $file_name = uniqid() . '-' . $file_name;
        $file_path = $ctd_dir . '/' . $file_name;
        
        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $file_path)) {
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Failed to move uploaded file to: ' . $file_path, 'error');
            }
            
            wp_send_json_error(array('message' => __('Failed to upload file.', 'custom-tshirt-designer')));
        }
        
        // Get file URL
        $file_url = $upload_dir['baseurl'] . '/ctd-designs/' . $file_name;
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('File uploaded successfully: ' . $file_url, 'info');
        }
        
        // Check if we need to store in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctd_designs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            // Store temporary design record
            // Note: We'll update this with order info when the order is placed
            $wpdb->insert(
                $table_name,
                array(
                    'order_id' => 0, // Temporary value
                    'order_item_id' => 0, // Temporary value
                    'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
                    'position' => $position,
                    'file_path' => $file_path,
                    'decoration_method' => isset($_POST['decoration_method']) ? sanitize_text_field($_POST['decoration_method']) : '',
                    'created_at' => current_time('mysql')
                )
            );
            
            if ($wpdb->last_error) {
                // Debug log
                if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                    CTD_Debug::log('Database error when inserting design: ' . $wpdb->last_error, 'error');
                }
            } else {
                // Debug log
                if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                    CTD_Debug::log('Design record inserted into database with ID: ' . $wpdb->insert_id, 'info');
                }
            }
        } else {
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Designs table does not exist: ' . $table_name, 'warning');
            }
        }
        
        // Check if file is accessible via URL
        $response = wp_remote_head($file_url);
        $is_accessible = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        
        if (!$is_accessible) {
            // If not accessible via URL, use data URI as fallback
            $file_data = file_get_contents($file_path);
            if ($file_data !== false) {
                $base64 = base64_encode($file_data);
                $file_url = 'data:' . $file_type . ';base64,' . $base64;
                
                // Debug log
                if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                    CTD_Debug::log('Using data URI as fallback for file: ' . $file_path, 'info');
                }
            }
        }
        
        // Return success
        wp_send_json_success(array(
            'file_path' => $file_path,
            'file_url' => $file_url,
            'position' => $position,
        ));
    }
    
    /**
     * Remove design
     */
    public function remove_design() {
        // Check nonce
        check_ajax_referer('ctd-nonce', 'nonce');
        
        // Get file path
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('Remove design AJAX request received', 'info');
            CTD_Debug::log('File path: ' . $file_path, 'info');
        }
        
        // Check if file exists
        if (!file_exists($file_path)) {
            wp_send_json_error(array('message' => __('File not found.', 'custom-tshirt-designer')));
        }
        
        // Delete file
        if (!unlink($file_path)) {
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Failed to delete file: ' . $file_path, 'error');
            }
            
            wp_send_json_error(array('message' => __('Failed to delete file.', 'custom-tshirt-designer')));
        }
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('File deleted successfully: ' . $file_path, 'info');
        }
        
        // Return success
        wp_send_json_success(array('message' => __('File deleted successfully.', 'custom-tshirt-designer')));
    }
    
    /**
     * Save composite image
     */
    public function save_composite() {
        // Check nonce
        check_ajax_referer('ctd-nonce', 'nonce');
        
        // Get image data
        $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
        $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 'front';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('Save composite AJAX request received', 'info');
            CTD_Debug::log('Position: ' . $position . ', Product ID: ' . $product_id, 'info');
        }
        
        if (empty($image_data) || empty($product_id)) {
            wp_send_json_error(array('message' => __('Invalid data.', 'custom-tshirt-designer')));
        }
        
        // Decode base64 image data
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $image_data = base64_decode($image_data);
        
        if (!$image_data) {
            wp_send_json_error(array('message' => __('Invalid image data.', 'custom-tshirt-designer')));
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        $ctd_dir = $upload_dir['basedir'] . '/ctd-composites';
        
        // Create directory if it doesn't exist
        if (!file_exists($ctd_dir)) {
            wp_mkdir_p($ctd_dir);
            
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Created composites directory: ' . $ctd_dir, 'info');
            }
        }
        
        // Generate unique file name
        $file_name = 'composite-' . $product_id . '-' . $position . '-' . uniqid() . '.png';
        $file_path = $ctd_dir . '/' . $file_name;
        
        // Save image
        if (!file_put_contents($file_path, $image_data)) {
            // Debug log
            if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
                CTD_Debug::log('Failed to save composite image: ' . $file_path, 'error');
            }
            
            wp_send_json_error(array('message' => __('Failed to save composite image.', 'custom-tshirt-designer')));
        }
        
        // Get file URL
        $file_url = $upload_dir['baseurl'] . '/ctd-composites/' . $file_name;
        
        // Debug log
        if (class_exists('CTD_Debug') && CTD_Debug::is_enabled()) {
            CTD_Debug::log('Composite image saved successfully: ' . $file_url, 'info');
        }
        
        // Return success
        wp_send_json_success(array(
            'file_path' => $file_path,
            'file_url' => $file_url,
            'position' => $position,
        ));
    }
}
