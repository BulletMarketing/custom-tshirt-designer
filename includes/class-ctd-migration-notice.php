<?php
/**
 * Migration Notice Handler for Custom T-Shirt Designer
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTD_Migration_Notice {
    
    public function __construct() {
        add_action('admin_notices', array($this, 'show_migration_notice'));
        add_action('wp_ajax_ctd_run_migration', array($this, 'ajax_run_migration'));
        add_action('wp_ajax_ctd_dismiss_migration_notice', array($this, 'ajax_dismiss_notice'));
    }
    
    /**
     * Show migration notice if needed
     */
    public function show_migration_notice() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if notice was dismissed
        if (get_option('ctd_migration_notice_dismissed', false)) {
            return;
        }
        
        // Include the db-update functions
        require_once plugin_dir_path(__FILE__) . '../db-update.php';
        
        // Check if migration is needed
        if (!ctd_needs_size_type_migration()) {
            return;
        }
        
        $status = ctd_get_migration_status();
        
        ?>
        <div class="notice notice-info ctd-migration-notice" id="ctd-migration-notice">
            <h3><?php _e('Custom T-Shirt Designer - Database Update Required', 'custom-tshirt-designer'); ?></h3>
            <p>
                <?php _e('We\'ve added a new size type feature that requires updating your existing products. This update will:', 'custom-tshirt-designer'); ?>
            </p>
            <ul style="margin-left: 20px;">
                <li><?php _e('Add size type selection (Men\'s, Women\'s, Kids) to your products', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Set existing products to use Men\'s sizes by default', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Preserve all your existing inventory and size data', 'custom-tshirt-designer'); ?></li>
            </ul>
            <p>
                <strong><?php printf(__('Products to update: %d', 'custom-tshirt-designer'), $status['total_ctd_products'] - $status['migrated_products']); ?></strong>
            </p>
            <p>
                <button type="button" class="button button-primary" id="ctd-run-migration">
                    <?php _e('Update Now', 'custom-tshirt-designer'); ?>
                </button>
                <button type="button" class="button" id="ctd-dismiss-migration">
                    <?php _e('Dismiss (Update Later)', 'custom-tshirt-designer'); ?>
                </button>
                <span class="ctd-migration-spinner" style="display: none;">
                    <span class="spinner is-active" style="float: none; margin: 0 10px;"></span>
                    <?php _e('Updating products...', 'custom-tshirt-designer'); ?>
                </span>
            </p>
            <div id="ctd-migration-result" style="display: none; margin-top: 15px;"></div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Run migration
            $('#ctd-run-migration').on('click', function() {
                var $button = $(this);
                var $spinner = $('.ctd-migration-spinner');
                var $result = $('#ctd-migration-result');
                
                $button.prop('disabled', true);
                $spinner.show();
                $result.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ctd_run_migration',
                        nonce: '<?php echo wp_create_nonce('ctd_migration_nonce'); ?>'
                    },
                    success: function(response) {
                        $spinner.hide();
                        
                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p><strong>Migration completed successfully!</strong><br>' + response.data.message + '</p></div>').show();
                            
                            // Hide the notice after 3 seconds
                            setTimeout(function() {
                                $('#ctd-migration-notice').fadeOut();
                            }, 3000);
                        } else {
                            $result.html('<div class="notice notice-error inline"><p><strong>Migration failed:</strong><br>' + response.data.message + '</p></div>').show();
                            $button.prop('disabled', false);
                        }
                    },
                    error: function() {
                        $spinner.hide();
                        $result.html('<div class="notice notice-error inline"><p><strong>Error:</strong> Failed to communicate with server. Please try again.</p></div>').show();
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Dismiss notice
            $('#ctd-dismiss-migration').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ctd_dismiss_migration_notice',
                        nonce: '<?php echo wp_create_nonce('ctd_migration_nonce'); ?>'
                    },
                    success: function() {
                        $('#ctd-migration-notice').fadeOut();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler to run migration
     */
    public function ajax_run_migration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ctd_migration_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        // Include the db-update functions
        require_once plugin_dir_path(__FILE__) . '../db-update.php';
        
        // Run the migration
        $result = ctd_update_database_add_size_type();
        
        if ($result['success']) {
            // Mark notice as dismissed since migration is complete
            update_option('ctd_migration_notice_dismissed', true);
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully updated %d products. %s', 'custom-tshirt-designer'),
                    $result['updated'],
                    $result['errors'] > 0 ? sprintf(__('%d errors occurred.', 'custom-tshirt-designer'), $result['errors']) : ''
                ),
                'log' => $result['log']
            ));
        } else {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Migration completed with %d errors. Updated %d products.', 'custom-tshirt-designer'),
                    $result['errors'],
                    $result['updated']
                ),
                'log' => $result['log']
            ));
        }
    }
    
    /**
     * AJAX handler to dismiss notice
     */
    public function ajax_dismiss_notice() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ctd_migration_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        update_option('ctd_migration_notice_dismissed', true);
        wp_send_json_success();
    }
}

// Initialize the migration notice
new CTD_Migration_Notice();