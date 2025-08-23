<?php
/**
 * Enhanced Importer class for Custom T-Shirt Designer
 */
class CTD_Importer {
    
    private $errors = array();
    private $warnings = array();
    private $processed_count = 0;
    private $success_count = 0;
    private $error_count = 0;
    
    /**
     * Initialize importer hooks
     */
    public function init() {
        // Add submenu page for imports
        add_action('admin_menu', array($this, 'add_import_menu'), 20);
        
        // Register AJAX handlers
        add_action('wp_ajax_ctd_process_import', array($this, 'process_import'));
        add_action('wp_ajax_ctd_get_import_template', array($this, 'get_import_template'));
        add_action('wp_ajax_ctd_validate_import', array($this, 'validate_import'));
        add_action('wp_ajax_ctd_process_import_batch', array($this, 'process_import_batch'));
    }
    
    /**
     * Add import menu
     */
    public function add_import_menu() {
        add_submenu_page(
            'woocommerce',
            __('Import/Export T-Shirt Products', 'custom-tshirt-designer'),
            __('Import/Export Products', 'custom-tshirt-designer'),
            'manage_woocommerce',
            'ctd-import-export',
            array($this, 'render_import_export_page')
        );
    }
    
    /**
     * Render import/export page
     */
    public function render_import_export_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import';
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export T-Shirt Products', 'custom-tshirt-designer'); ?></h1>
            
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=ctd-import-export&tab=import'); ?>" class="nav-tab <?php echo $tab === 'import' ? 'nav-tab-active' : ''; ?>"><?php _e('Import', 'custom-tshirt-designer'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=ctd-import-export&tab=export'); ?>" class="nav-tab <?php echo $tab === 'export' ? 'nav-tab-active' : ''; ?>"><?php _e('Export', 'custom-tshirt-designer'); ?></a>
            </nav>
            
            <div class="tab-content">
                <?php
                if ($tab === 'import') {
                    $this->render_import_tab();
                } elseif ($tab === 'export') {
                    $this->render_export_tab();
                }
                ?>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Import functionality
                $('#ctd-download-template').on('click', function(e) {
                    e.preventDefault();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ctd_get_import_template',
                            nonce: '<?php echo wp_create_nonce('ctd-import-nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                var a = document.createElement('a');
                                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data.content);
                                a.download = 'ctd-product-import-template.csv';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        }
                    });
                });
                
                // Validate import file
                $('#ctd_import_file').on('change', function() {
                    var file = this.files[0];
                    if (file) {
                        var formData = new FormData();
                        formData.append('ctd_import_file', file);
                        formData.append('action', 'ctd_validate_import');
                        formData.append('nonce', '<?php echo wp_create_nonce('ctd-import-nonce'); ?>');
                        
                        $('#ctd-validation-results').html('<p>Validating file...</p>').show();
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    var html = '<div class="notice notice-success"><p>File validation passed! Found ' + response.data.valid_rows + ' valid products.</p></div>';
                                    if (response.data.warnings.length > 0) {
                                        html += '<div class="notice notice-warning"><p>Warnings:</p><ul>';
                                        response.data.warnings.forEach(function(warning) {
                                            html += '<li>' + warning + '</li>';
                                        });
                                        html += '</ul></div>';
                                    }
                                    $('#ctd-validation-results').html(html);
                                    $('#ctd-import-submit').prop('disabled', false);
                                } else {
                                    var html = '<div class="notice notice-error"><p>Validation failed: ' + response.data.message + '</p></div>';
                                    if (response.data.errors && response.data.errors.length > 0) {
                                        html += '<ul>';
                                        response.data.errors.forEach(function(error) {
                                            html += '<li>' + error + '</li>';
                                        });
                                        html += '</ul>';
                                    }
                                    $('#ctd-validation-results').html(html);
                                    $('#ctd-import-submit').prop('disabled', true);
                                }
                            }
                        });
                    }
                });
                
                // Handle import form submission
                $('#ctd-import-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var formData = new FormData(this);
                    formData.append('action', 'ctd_process_import');
                    formData.append('nonce', '<?php echo wp_create_nonce('ctd-import-nonce'); ?>');
                    
                    $('#ctd-import-form').hide();
                    $('#ctd-import-progress').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            $('#ctd-import-progress').hide();
                            $('#ctd-import-results').show();
                            
                            if (response.success) {
                                var summary = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                                $('#ctd-import-summary').html(summary);
                                
                                var details = '<table class="widefat">';
                                details += '<thead><tr><th>Product</th><th>Status</th><th>Message</th></tr></thead><tbody>';
                                
                                response.data.products.forEach(function(product) {
                                    var statusClass = product.status === 'success' ? 'ctd-success' : 'ctd-error';
                                    details += '<tr class="' + statusClass + '">';
                                    details += '<td>' + product.name + ' (' + product.sku + ')</td>';
                                    details += '<td>' + product.status + '</td>';
                                    details += '<td>' + product.message + '</td>';
                                    details += '</tr>';
                                });
                                
                                details += '</tbody></table>';
                                $('#ctd-import-details').html(details);
                            } else {
                                var summary = '<div class="notice notice-error"><p>' + response.data.message + '</p></div>';
                                $('#ctd-import-summary').html(summary);
                            }
                            
                            $('#ctd-import-form').show();
                        }
                    });
                });
                
                // Export functionality
                $('#ctd-export-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var formData = $(this).serialize();
                    formData += '&action=ctd_process_export&nonce=<?php echo wp_create_nonce('ctd-export-nonce'); ?>';
                    
                    $('#ctd-export-progress').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            $('#ctd-export-progress').hide();
                            
                            if (response.success) {
                                var a = document.createElement('a');
                                a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data.content);
                                a.download = response.data.filename;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                
                                $('#ctd-export-results').html('<div class="notice notice-success"><p>Export completed successfully! ' + response.data.count + ' products exported.</p></div>').show();
                            } else {
                                $('#ctd-export-results').html('<div class="notice notice-error"><p>Export failed: ' + response.data.message + '</p></div>').show();
                            }
                        }
                    });
                });
            });
        </script>
        
        <style>
            .ctd-success { background-color: #f0fff0; }
            .ctd-error { background-color: #fff0f0; }
            #ctd-import-details, #ctd-export-results { margin-top: 20px; max-height: 400px; overflow-y: auto; }
            .ctd-progress-bar-container { height: 20px; width: 100%; background-color: #f0f0f0; border-radius: 3px; margin-bottom: 10px; }
            #ctd-progress-bar { height: 100%; width: 0%; background-color: #2271b1; border-radius: 3px; }
            .nav-tab-wrapper { margin-bottom: 20px; }
            .tab-content { background: #fff; padding: 20px; border: 1px solid #ccd0d4; }
        </style>
        <?php
    }
    
    /**
     * Render import tab
     */
    private function render_import_tab() {
        ?>
        <div class="card">
            <h2><?php _e('Import Products with Designer Features', 'custom-tshirt-designer'); ?></h2>
            <p><?php _e('Use this tool to import products with Custom T-Shirt Designer features pre-configured.', 'custom-tshirt-designer'); ?></p>
            
            <h3><?php _e('Instructions', 'custom-tshirt-designer'); ?></h3>
            <ol>
                <li><?php _e('Download the CSV template below', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Fill in your product details following the template format', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Upload the completed CSV file (file will be automatically validated)', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Review the import results', 'custom-tshirt-designer'); ?></li>
            </ol>
            
            <p>
                <a href="#" id="ctd-download-template" class="button button-secondary"><?php _e('Download CSV Template', 'custom-tshirt-designer'); ?></a>
            </p>
            
            <form id="ctd-import-form" method="post" enctype="multipart/form-data">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctd_import_file"><?php _e('CSV File', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="ctd_import_file" id="ctd_import_file" accept=".csv" required>
                            <p class="description"><?php _e('Select a CSV file to import. File will be validated automatically.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ctd_update_existing"><?php _e('Update Existing Products', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ctd_update_existing" id="ctd_update_existing" value="1">
                            <p class="description"><?php _e('If checked, existing products with the same SKU will be updated.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ctd_create_categories"><?php _e('Create Missing Categories', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ctd_create_categories" id="ctd_create_categories" value="1" checked>
                            <p class="description"><?php _e('Automatically create product categories that don\'t exist.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ctd_download_images"><?php _e('Download Remote Images', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ctd_download_images" id="ctd_download_images" value="1" checked>
                            <p class="description"><?php _e('Download and store remote images locally.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div id="ctd-validation-results" style="display: none;"></div>
                
                <p class="submit">
                    <input type="submit" name="submit" id="ctd-import-submit" class="button button-primary" value="<?php _e('Import Products', 'custom-tshirt-designer'); ?>" disabled>
                </p>
            </form>
            
            <div id="ctd-import-progress" style="display: none;">
                <h3><?php _e('Import Progress', 'custom-tshirt-designer'); ?></h3>
                <div class="ctd-progress-bar-container">
                    <div id="ctd-progress-bar"></div>
                </div>
                <p id="ctd-progress-status"><?php _e('Processing...', 'custom-tshirt-designer'); ?></p>
            </div>
            
            <div id="ctd-import-results" style="display: none;">
                <h3><?php _e('Import Results', 'custom-tshirt-designer'); ?></h3>
                <div id="ctd-import-summary"></div>
                <div id="ctd-import-details"></div>
            </div>
        </div>
        
        <?php $this->render_csv_format_guide(); ?>
        <?php
    }
    
    /**
     * Render export tab
     */
    private function render_export_tab() {
        ?>
        <div class="card">
            <h2><?php _e('Export Products with Designer Features', 'custom-tshirt-designer'); ?></h2>
            <p><?php _e('Export your existing products with Custom T-Shirt Designer configurations to a CSV file.', 'custom-tshirt-designer'); ?></p>
            
            <form id="ctd-export-form" method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ctd_export_type"><?php _e('Export Type', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <select name="ctd_export_type" id="ctd_export_type">
                                <option value="designer_only"><?php _e('Only products with designer enabled', 'custom-tshirt-designer'); ?></option>
                                <option value="all_products"><?php _e('All WooCommerce products', 'custom-tshirt-designer'); ?></option>
                                <option value="specific_categories"><?php _e('Specific categories', 'custom-tshirt-designer'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose which products to export.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr id="ctd-categories-row" style="display: none;">
                        <th scope="row">
                            <label for="ctd_export_categories"><?php _e('Categories', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'product_cat',
                                'hide_empty' => false,
                            ));
                            
                            if ($categories && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    echo '<label><input type="checkbox" name="ctd_export_categories[]" value="' . esc_attr($category->term_id) . '"> ' . esc_html($category->name) . '</label><br>';
                                }
                            }
                            ?>
                            <p class="description"><?php _e('Select categories to export.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ctd_include_images"><?php _e('Include Image URLs', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ctd_include_images" id="ctd_include_images" value="1" checked>
                            <p class="description"><?php _e('Include product image URLs in the export.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ctd_include_inventory"><?php _e('Include Inventory Data', 'custom-tshirt-designer'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="ctd_include_inventory" id="ctd_include_inventory" value="1" checked>
                            <p class="description"><?php _e('Include inventory quantities in the export.', 'custom-tshirt-designer'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="ctd-export-submit" class="button button-primary" value="<?php _e('Export Products', 'custom-tshirt-designer'); ?>">
                </p>
            </form>
            
            <div id="ctd-export-progress" style="display: none;">
                <h3><?php _e('Export Progress', 'custom-tshirt-designer'); ?></h3>
                <p><?php _e('Generating export file...', 'custom-tshirt-designer'); ?></p>
            </div>
            
            <div id="ctd-export-results" style="display: none;"></div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('#ctd_export_type').on('change', function() {
                    if ($(this).val() === 'specific_categories') {
                        $('#ctd-categories-row').show();
                    } else {
                        $('#ctd-categories-row').hide();
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Render CSV format guide
     */
    private function render_csv_format_guide() {
        ?>
        <div class="card" style="margin-top: 20px;">
            <h2><?php _e('CSV Format Guide', 'custom-tshirt-designer'); ?></h2>
            <p><?php _e('Your CSV file should include the following columns:', 'custom-tshirt-designer'); ?></p>
            
            <table class="widefat" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th><?php _e('Column', 'custom-tshirt-designer'); ?></th>
                        <th><?php _e('Description', 'custom-tshirt-designer'); ?></th>
                        <th><?php _e('Example', 'custom-tshirt-designer'); ?></th>
                        <th><?php _e('Required', 'custom-tshirt-designer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>name</strong></td>
                        <td><?php _e('Product name', 'custom-tshirt-designer'); ?></td>
                        <td>Custom T-Shirt</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>sku</strong></td>
                        <td><?php _e('Product SKU (must be unique)', 'custom-tshirt-designer'); ?></td>
                        <td>TSHIRT-001</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>description</strong></td>
                        <td><?php _e('Product description (HTML allowed)', 'custom-tshirt-designer'); ?></td>
                        <td>High-quality custom t-shirt...</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>short_description</strong></td>
                        <td><?php _e('Product short description', 'custom-tshirt-designer'); ?></td>
                        <td>Custom t-shirt with your design</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>regular_price</strong></td>
                        <td><?php _e('Regular price (numbers only)', 'custom-tshirt-designer'); ?></td>
                        <td>19.99</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>sale_price</strong></td>
                        <td><?php _e('Sale price (numbers only)', 'custom-tshirt-designer'); ?></td>
                        <td>15.99</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>categories</strong></td>
                        <td><?php _e('Product categories (comma-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>T-Shirts, Custom Apparel</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>tags</strong></td>
                        <td><?php _e('Product tags (comma-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>custom, t-shirt, apparel</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>image</strong></td>
                        <td><?php _e('Main product image URL', 'custom-tshirt-designer'); ?></td>
                        <td>https://example.com/images/tshirt.jpg</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>gallery_images</strong></td>
                        <td><?php _e('Additional images URLs (pipe-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>https://example.com/img1.jpg|https://example.com/img2.jpg</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>product_type</strong></td>
                        <td><?php _e('Designer product type', 'custom-tshirt-designer'); ?></td>
                        <td>shirt, hoodie, hat, bottle</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>colors</strong></td>
                        <td><?php _e('Available colors (format: code:name, comma-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>#FFFFFF:White, #000000:Black, #FF0000:Red</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>color_sizes</strong></td>
                        <td><?php _e('Color-specific sizes (format: color_code:size1,size2, pipe-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>#FFFFFF:XS,S,M,L,XL|#000000:S,M,L,XL,2XL</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>inventory</strong></td>
                        <td><?php _e('Inventory quantities (format: color_code:size:quantity, pipe-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>#FFFFFF:XS:10,S:20,M:30|#000000:S:15,M:25</td>
                        <td>No</td>
                    </tr>
                    <tr>
                        <td><strong>inventory_enabled</strong></td>
                        <td><?php _e('Enable inventory tracking', 'custom-tshirt-designer'); ?></td>
                        <td>yes, no</td>
                        <td>No (defaults to no)</td>
                    </tr>
                    <tr>
                        <td><strong>decoration_methods</strong></td>
                        <td><?php _e('Decoration methods (format: key:name:fee, comma-separated)', 'custom-tshirt-designer'); ?></td>
                        <td>screen_printing:Screen Printing:10.95, dtg:DTG:8.95</td>
                        <td><span class="required">Yes</span></td>
                    </tr>
                    <tr>
                        <td><strong>designer_enabled</strong></td>
                        <td><?php _e('Enable designer for this product', 'custom-tshirt-designer'); ?></td>
                        <td>yes, no</td>
                        <td>No (defaults to yes)</td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php _e('Important Notes', 'custom-tshirt-designer'); ?></h3>
            <ul>
                <li><?php _e('All color codes should be in hex format (e.g., #FFFFFF) or valid CSS color names', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Prices should be numbers only (no currency symbols)', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Image URLs must be publicly accessible', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('SKUs must be unique across all products', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('If color_sizes is not specified, default sizes will be used for all colors', 'custom-tshirt-designer'); ?></li>
                <li><?php _e('Inventory quantities are only used if inventory_enabled is set to "yes"', 'custom-tshirt-designer'); ?></li>
            </ul>
        </div>
        
        <style>
            .required { color: #d63638; font-weight: bold; }
            .widefat th, .widefat td { padding: 8px 10px; }
            .card h3 { margin-top: 20px; }
        </style>
        <?php
    }
    
    /**
     * Validate import file
     */
    public function validate_import() {
        check_ajax_referer('ctd-import-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to import products.', 'custom-tshirt-designer')));
        }
        
        if (!isset($_FILES['ctd_import_file']) || empty($_FILES['ctd_import_file']['name'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'custom-tshirt-designer')));
        }
        
        $file = $_FILES['ctd_import_file'];
        
        // Check file type
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($file_ext !== 'csv') {
            wp_send_json_error(array('message' => __('Invalid file type. Only CSV files are allowed.', 'custom-tshirt-designer')));
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File is too large. Maximum size is 10MB.', 'custom-tshirt-designer')));
        }
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('Failed to open file.', 'custom-tshirt-designer')));
        }
        
        // Get header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            wp_send_json_error(array('message' => __('Failed to read header row.', 'custom-tshirt-designer')));
        }
        
        // Validate header
        $required_columns = array('name', 'sku', 'regular_price', 'product_type', 'colors', 'decoration_methods');
        $missing_columns = array_diff($required_columns, $header);
        
        if (!empty($missing_columns)) {
            fclose($handle);
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Missing required columns: %s', 'custom-tshirt-designer'),
                    implode(', ', $missing_columns)
                )
            ));
        }
        
        $this->errors = array();
        $this->warnings = array();
        $valid_rows = 0;
        $row_number = 1;
        
        // Validate each row
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            if (empty($row) || count(array_filter($row)) === 0) {
                continue;
            }
            
            $data = array_combine($header, $row);
            $this->validate_row($data, $row_number);
            
            if (empty($this->errors)) {
                $valid_rows++;
            }
        }
        
        fclose($handle);
        
        if (!empty($this->errors)) {
            wp_send_json_error(array(
                'message' => __('Validation failed. Please fix the errors below.', 'custom-tshirt-designer'),
                'errors' => $this->errors
            ));
        }
        
        wp_send_json_success(array(
            'valid_rows' => $valid_rows,
            'warnings' => $this->warnings
        ));
    }
    
    /**
     * Validate a single row
     */
    private function validate_row($data, $row_number) {
        // Check required fields
        $required_fields = array('name', 'sku', 'regular_price', 'product_type', 'colors', 'decoration_methods');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->errors[] = sprintf(__('Row %d: Missing required field "%s"', 'custom-tshirt-designer'), $row_number, $field);
            }
        }
        
        // Validate SKU uniqueness
        if (!empty($data['sku'])) {
            $existing_product = wc_get_product_id_by_sku($data['sku']);
            if ($existing_product) {
                $this->warnings[] = sprintf(__('Row %d: Product with SKU "%s" already exists', 'custom-tshirt-designer'), $row_number, $data['sku']);
            }
        }
        
        // Validate price format
        if (!empty($data['regular_price']) && !is_numeric($data['regular_price'])) {
            $this->errors[] = sprintf(__('Row %d: Regular price must be a number', 'custom-tshirt-designer'), $row_number);
        }
        
        if (!empty($data['sale_price']) && !is_numeric($data['sale_price'])) {
            $this->errors[] = sprintf(__('Row %d: Sale price must be a number', 'custom-tshirt-designer'), $row_number);
        }
        
        // Validate product type
        if (!empty($data['product_type'])) {
            $valid_types = array('shirt', 'hoodie', 'hat', 'bottle');
            if (!in_array($data['product_type'], $valid_types)) {
                $this->errors[] = sprintf(__('Row %d: Invalid product type. Must be one of: %s', 'custom-tshirt-designer'), $row_number, implode(', ', $valid_types));
            }
        }
        
        // Validate colors format
        if (!empty($data['colors'])) {
            $colors = array_map('trim', explode(',', $data['colors']));
            foreach ($colors as $color) {
                if (strpos($color, ':') === false) {
                    $this->errors[] = sprintf(__('Row %d: Invalid color format. Use "code:name" format', 'custom-tshirt-designer'), $row_number);
                    break;
                }
            }
        }
        
        // Validate decoration methods format
        if (!empty($data['decoration_methods'])) {
            $methods = array_map('trim', explode(',', $data['decoration_methods']));
            foreach ($methods as $method) {
                $parts = explode(':', $method);
                if (count($parts) < 2) {
                    $this->errors[] = sprintf(__('Row %d: Invalid decoration method format. Use "key:name:fee" format', 'custom-tshirt-designer'), $row_number);
                    break;
                }
            }
        }
        
        // Validate image URLs
        if (!empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
            $this->warnings[] = sprintf(__('Row %d: Invalid image URL format', 'custom-tshirt-designer'), $row_number);
        }
        
        if (!empty($data['gallery_images'])) {
            $gallery_images = array_map('trim', explode('|', $data['gallery_images']));
            foreach ($gallery_images as $image_url) {
                if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $this->warnings[] = sprintf(__('Row %d: Invalid gallery image URL format', 'custom-tshirt-designer'), $row_number);
                    break;
                }
            }
        }
    }
    
    /**
     * Process import (enhanced version)
     */
    public function process_import() {
        check_ajax_referer('ctd-import-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to import products.', 'custom-tshirt-designer')));
        }
        
        if (!isset($_FILES['ctd_import_file']) || empty($_FILES['ctd_import_file']['name'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'custom-tshirt-designer')));
        }
        
        $file = $_FILES['ctd_import_file'];
        $update_existing = isset($_POST['ctd_update_existing']) && $_POST['ctd_update_existing'] === '1';
        $create_categories = isset($_POST['ctd_create_categories']) && $_POST['ctd_create_categories'] === '1';
        $download_images = isset($_POST['ctd_download_images']) && $_POST['ctd_download_images'] === '1';
        
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('Failed to open file.', 'custom-tshirt-designer')));
        }
        
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            wp_send_json_error(array('message' => __('Failed to read header row.', 'custom-tshirt-designer')));
        }
        
        $products = array();
        $this->processed_count = 0;
        $this->success_count = 0;
        $this->error_count = 0;
        
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || count(array_filter($row)) === 0) {
                continue;
            }
            
            $data = array_combine($header, $row);
            $result = $this->process_product($data, $update_existing, $create_categories, $download_images);
            
            $products[] = array(
                'name' => isset($data['name']) ? $data['name'] : __('Unknown', 'custom-tshirt-designer'),
                'sku' => isset($data['sku']) ? $data['sku'] : __('Unknown', 'custom-tshirt-designer'),
                'status' => $result['status'],
                'message' => $result['message'],
                'product_id' => isset($result['product_id']) ? $result['product_id'] : 0
            );
            
            $this->processed_count++;
            if ($result['status'] === 'success') {
                $this->success_count++;
            } else {
                $this->error_count++;
            }
        }
        
        fclose($handle);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Import completed. %d products processed: %d successful, %d failed.', 'custom-tshirt-designer'),
                $this->processed_count,
                $this->success_count,
                $this->error_count
            ),
            'products' => $products,
            'stats' => array(
                'processed' => $this->processed_count,
                'success' => $this->success_count,
                'errors' => $this->error_count
            )
        ));
    }
    
    /**
     * Enhanced process product method
     */
    private function process_product($data, $update_existing = false, $create_categories = true, $download_images = true) {
        // Check required fields
        $required_fields = array('name', 'sku', 'regular_price', 'product_type', 'colors', 'decoration_methods');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return array(
                    'status' => 'error',
                    'message' => sprintf(__('Missing required field: %s', 'custom-tshirt-designer'), $field)
                );
            }
        }
        
        // Check if product exists
        $existing_product_id = wc_get_product_id_by_sku($data['sku']);
        
        if ($existing_product_id && !$update_existing) {
            return array(
                'status' => 'error',
                'message' => __('Product with this SKU already exists and update option is not enabled.', 'custom-tshirt-designer')
            );
        }
        
        try {
            // Create or update product
            if ($existing_product_id && $update_existing) {
                $product = wc_get_product($existing_product_id);
                if (!$product) {
                    return array(
                        'status' => 'error',
                        'message' => __('Failed to get existing product.', 'custom-tshirt-designer')
                    );
                }
                $product_id = $product->get_id();
            } else {
                $product = new WC_Product_Simple();
                $product->set_sku($data['sku']);
                $product_id = 0;
            }
            
            // Set basic product data
            $product->set_name($data['name']);
            $product->set_regular_price($data['regular_price']);
            $product->set_status('publish');
            
            if (!empty($data['description'])) {
                $product->set_description($data['description']);
            }
            
            if (!empty($data['short_description'])) {
                $product->set_short_description($data['short_description']);
            }
            
            if (!empty($data['sale_price'])) {
                $product->set_sale_price($data['sale_price']);
            }
            
            // Save product to get ID
            $product_id = $product->save();
            
            if (!$product_id) {
                return array(
                    'status' => 'error',
                    'message' => __('Failed to save product.', 'custom-tshirt-designer')
                );
            }
            
            // Set categories
            if (!empty($data['categories'])) {
                $this->set_product_categories($product_id, $data['categories'], $create_categories);
            }
            
            // Set tags
            if (!empty($data['tags'])) {
                $tags = array_map('trim', explode(',', $data['tags']));
                wp_set_object_terms($product_id, $tags, 'product_tag');
            }
            
            // Set images
            if ($download_images) {
                if (!empty($data['image'])) {
                    $this->set_product_image($product_id, $data['image'], true);
                }
                
                if (!empty($data['gallery_images'])) {
                    $gallery_images = array_map('trim', explode('|', $data['gallery_images']));
                    foreach ($gallery_images as $image_url) {
                        $this->set_product_image($product_id, $image_url, false);
                    }
                }
            }
            
            // Process designer configuration
            $this->save_enhanced_designer_config($product_id, $data);
            
            return array(
                'status' => 'success',
                'message' => $existing_product_id ? __('Product updated successfully.', 'custom-tshirt-designer') : __('Product created successfully.', 'custom-tshirt-designer'),
                'product_id' => $product_id
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => sprintf(__('Error processing product: %s', 'custom-tshirt-designer'), $e->getMessage())
            );
        }
    }
    
    /**
     * Set product categories with option to create missing ones
     */
    private function set_product_categories($product_id, $categories_string, $create_categories = true) {
        $categories = array_map('trim', explode(',', $categories_string));
        $category_ids = array();
        
        foreach ($categories as $category_name) {
            $term = get_term_by('name', $category_name, 'product_cat');
            
            if ($term) {
                $category_ids[] = $term->term_id;
            } elseif ($create_categories) {
                $new_term = wp_insert_term($category_name, 'product_cat');
                if (!is_wp_error($new_term)) {
                    $category_ids[] = $new_term['term_id'];
                }
            }
        }
        
        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }
    }
    
    /**
     * Enhanced image handling with better error checking
     */
    private function set_product_image($product_id, $image_url, $is_main_image = false) {
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Get image data with timeout and user agent
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; WordPress; CTD Plugin)'
            )
        ));
        
        $image_data = file_get_contents($image_url, false, $context);
        
        if ($image_data === false) {
            return false;
        }
        
        // Get upload directory
        $upload_dir = wp_upload_dir();
        
        // Generate unique filename
        $image_name = basename(parse_url($image_url, PHP_URL_PATH));
        if (empty($image_name) || strpos($image_name, '.') === false) {
            $image_name = 'imported-image-' . time() . '.jpg';
        }
        
        $file_name = wp_unique_filename($upload_dir['path'], $image_name);
        $file_path = $upload_dir['path'] . '/' . $file_name;
        
        // Save image
        if (file_put_contents($file_path, $image_data) === false) {
            return false;
        }
        
        // Check file type
        $file_type = wp_check_filetype($file_name, null);
        
        if (!$file_type['type']) {
            unlink($file_path);
            return false;
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name($file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $file_path, $product_id);
        
        if (is_wp_error($attachment_id)) {
            unlink($file_path);
            return false;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Set as product image
        if ($is_main_image) {
            set_post_thumbnail($product_id, $attachment_id);
        } else {
            $product = wc_get_product($product_id);
            $gallery_ids = $product->get_gallery_image_ids();
            $gallery_ids[] = $attachment_id;
            $product->set_gallery_image_ids($gallery_ids);
            $product->save();
        }
        
        return $attachment_id;
    }
    
    /**
     * Enhanced designer configuration saving
     */
    private function save_enhanced_designer_config($product_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctd_product_configs';
        
        // Ensure table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            CTD_Installer::create_tables();
        }
        
        // Parse data
        $designer_enabled = isset($data['designer_enabled']) ? strtolower($data['designer_enabled']) === 'yes' : true;
        $inventory_enabled = isset($data['inventory_enabled']) ? strtolower($data['inventory_enabled']) === 'yes' : false;
        
        // Parse colors
        $colors = array();
        $color_items = array_map('trim', explode(',', $data['colors']));
        foreach ($color_items as $color_item) {
            $color_parts = explode(':', $color_item, 2);
            if (count($color_parts) === 2) {
                $colors[trim($color_parts[0])] = trim($color_parts[1]);
            }
        }
        
        // Parse color sizes
        $color_sizes = array();
        if (!empty($data['color_sizes'])) {
            $color_size_items = array_map('trim', explode('|', $data['color_sizes']));
            foreach ($color_size_items as $color_size_item) {
                $parts = explode(':', $color_size_item, 2);
                if (count($parts) === 2) {
                    $color_code = trim($parts[0]);
                    $sizes = array_map('trim', explode(',', $parts[1]));
                    $color_sizes[$color_code] = $sizes;
                }
            }
        } else {
            // Default sizes
            $default_sizes = array('2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL');
            foreach ($colors as $color_code => $color_name) {
                $color_sizes[$color_code] = $default_sizes;
            }
        }
        
        // Parse inventory
        $inventory = array();
        if (!empty($data['inventory'])) {
            $inventory_items = array_map('trim', explode('|', $data['inventory']));
            foreach ($inventory_items as $inventory_item) {
                $color_parts = explode(':', $inventory_item, 2);
                if (count($color_parts) === 2) {
                    $color_code = trim($color_parts[0]);
                    $size_quantities = trim($color_parts[1]);
                    
                    $size_qty_pairs = array_map('trim', explode(',', $size_quantities));
                    foreach ($size_qty_pairs as $size_qty_pair) {
                        $size_qty = explode(':', $size_qty_pair);
                        if (count($size_qty) === 2) {
                            $size = trim($size_qty[0]);
                            $qty = intval(trim($size_qty[1]));
                            $inventory[$color_code][$size] = $qty;
                        }
                    }
                }
            }
        }
        
        // Parse decoration methods
        $decoration_methods = array();
        $setup_fees = array();
        $method_items = array_map('trim', explode(',', $data['decoration_methods']));
        foreach ($method_items as $method_item) {
            $method_parts = explode(':', $method_item);
            if (count($method_parts) >= 2) {
                $key = trim($method_parts[0]);
                $name = trim($method_parts[1]);
                $fee = isset($method_parts[2]) ? floatval(trim($method_parts[2])) : 0;
                
                $decoration_methods[$key] = $name;
                $setup_fees[$key] = $fee;
            }
        }
        
        // Default sizes
        $sizes = array('2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL');
        
        // Prepare data for database
        $config_data = array(
            'enabled' => $designer_enabled ? 1 : 0,
            'product_type' => $data['product_type'],
            'sizes' => maybe_serialize($sizes),
            'colors' => maybe_serialize($colors),
            'decoration_methods' => maybe_serialize($decoration_methods),
            'setup_fees' => maybe_serialize($setup_fees),
            'color_sizes' => maybe_serialize($color_sizes),
            'inventory_enabled' => $inventory_enabled ? 1 : 0,
            'inventory' => maybe_serialize($inventory),
            'updated_at' => current_time('mysql')
        );
        
        // Check if config exists
        $existing_config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE product_id = %d", $product_id));
        
        if ($existing_config) {
            $wpdb->update($table_name, $config_data, array('product_id' => $product_id));
        } else {
            $config_data['product_id'] = $product_id;
            $config_data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $config_data);
        }
        
        return true;
    }
    
    /**
     * Get import template with enhanced format
     */
    public function get_import_template() {
        check_ajax_referer('ctd-import-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to download the template.', 'custom-tshirt-designer')));
        }
        
        $template = array(
            // Header row
            array(
                'name',
                'sku',
                'description',
                'short_description',
                'regular_price',
                'sale_price',
                'categories',
                'tags',
                'image',
                'gallery_images',
                'product_type',
                'colors',
                'color_sizes',
                'inventory',
                'inventory_enabled',
                'decoration_methods',
                'designer_enabled'
            ),
            // Example row 1
            array(
                'Custom T-Shirt Premium',
                'TSHIRT-PREMIUM-001',
                'High-quality custom t-shirt made from 100% cotton. Perfect for custom designs and comfortable wear.',
                'Premium custom t-shirt with your design',
                '24.99',
                '19.99',
                'T-Shirts, Premium Apparel, Custom Products',
                'custom, t-shirt, premium, cotton',
                'https://example.com/images/tshirt-premium.jpg',
                'https://example.com/images/tshirt-premium-back.jpg|https://example.com/images/tshirt-premium-side.jpg',
                'shirt',
                '#FFFFFF:White, #000000:Black, #FF0000:Red, #0000FF:Blue, #008000:Green',
                '#FFFFFF:XS,S,M,L,XL,2XL|#000000:S,M,L,XL,2XL,3XL|#FF0000:XS,S,M,L,XL|#0000FF:S,M,L,XL,2XL|#008000:M,L,XL,2XL',
                '#FFFFFF:XS:10,S:25,M:35,L:30,XL:20,2XL:10|#000000:S:20,M:30,L:40,XL:25,2XL:15,3XL:5|#FF0000:XS:8,S:15,M:20,L:18,XL:12|#0000FF:S:12,M:18,L:25,XL:15,2XL:8|#008000:M:15,L:20,XL:18,2XL:10',
                'yes',
                'screen_printing:Screen Printing:12.95, dtg:Direct to Garment:9.95, embroidery:Embroidery:16.95, heat_transfer:Heat Transfer:8.95',
                'yes'
            ),
            // Example row 2
            array(
                'Custom Hoodie Deluxe',
                'HOODIE-DELUXE-001',
                'Comfortable custom hoodie with front pocket. Made from cotton-polyester blend for durability and comfort.',
                'Deluxe custom hoodie with your design',
                '39.99',
                '34.99',
                'Hoodies, Custom Apparel, Winter Wear',
                'custom, hoodie, warm, comfortable',
                'https://example.com/images/hoodie-deluxe.jpg',
                'https://example.com/images/hoodie-deluxe-back.jpg|https://example.com/images/hoodie-deluxe-hood.jpg',
                'hoodie',
                '#808080:Gray, #000000:Black, #800080:Purple, #000080:Navy',
                '#808080:S,M,L,XL,2XL|#000000:XS,S,M,L,XL,2XL,3XL|#800080:S,M,L,XL|#000080:M,L,XL,2XL',
                '#808080:S:15,M:25,L:30,XL:20,2XL:10|#000000:XS:5,S:18,M:28,L:35,XL:22,2XL:12,3XL:6|#800080:S:10,M:15,L:18,XL:12|#000080:M:12,L:18,XL:15,2XL:8',
                'yes',
                'screen_printing:Screen Printing:15.95, dtg:Direct to Garment:12.95, embroidery:Embroidery:19.95',
                'yes'
            )
        );
        
        // Convert to CSV
        $csv = '';
        foreach ($template as $row) {
            $csv .= '"' . implode('","', $row) . '"' . "\n";
        }
        
        wp_send_json_success(array('content' => $csv));
    }
}

// Add export functionality
add_action('wp_ajax_ctd_process_export', array('CTD_Exporter', 'process_export'));

/**
 * Export functionality class
 */
class CTD_Exporter {
    
    /**
     * Process export
     */
    public static function process_export() {
        check_ajax_referer('ctd-export-nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to export products.', 'custom-tshirt-designer')));
        }
        
        $export_type = isset($_POST['ctd_export_type']) ? sanitize_text_field($_POST['ctd_export_type']) : 'designer_only';
        $include_images = isset($_POST['ctd_include_images']) && $_POST['ctd_include_images'] === '1';
        $include_inventory = isset($_POST['ctd_include_inventory']) && $_POST['ctd_include_inventory'] === '1';
        $export_categories = isset($_POST['ctd_export_categories']) ? array_map('intval', $_POST['ctd_export_categories']) : array();
        
        // Get products based on export type
        $products = self::get_products_for_export($export_type, $export_categories);
        
        if (empty($products)) {
            wp_send_json_error(array('message' => __('No products found to export.', 'custom-tshirt-designer')));
        }
        
        // Generate CSV
        $csv_data = self::generate_csv($products, $include_images, $include_inventory);
        
        $filename = 'ctd-products-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        wp_send_json_success(array(
            'content' => $csv_data,
            'filename' => $filename,
            'count' => count($products)
        ));
    }
    
    /**
     * Get products for export
     */
    private static function get_products_for_export($export_type, $export_categories = array()) {
        global $wpdb;
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        if ($export_type === 'designer_only') {
            // Only products with designer enabled
            $config_table = $wpdb->prefix . 'ctd_product_configs';
            $product_ids = $wpdb->get_col("SELECT product_id FROM $config_table WHERE enabled = 1");
            
            if (empty($product_ids)) {
                return array();
            }
            
            $args['post__in'] = $product_ids;
        } elseif ($export_type === 'specific_categories' && !empty($export_categories)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $export_categories
                )
            );
        }
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Generate CSV data
     */
    private static function generate_csv($products, $include_images = true, $include_inventory = true) {
        global $wpdb;
        
        $config_table = $wpdb->prefix . 'ctd_product_configs';
        
        // CSV header
        $header = array(
            'name',
            'sku',
            'description',
            'short_description',
            'regular_price',
            'sale_price',
            'categories',
            'tags',
            'product_type',
            'colors',
            'color_sizes',
            'decoration_methods',
            'designer_enabled'
        );
        
        if ($include_images) {
            array_splice($header, 8, 0, array('image', 'gallery_images'));
        }
        
        if ($include_inventory) {
            array_splice($header, -1, 0, array('inventory', 'inventory_enabled'));
        }
        
        $csv_rows = array($header);
        
        foreach ($products as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) continue;
            
            // Get designer config
            $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $config_table WHERE product_id = %d", $post->ID));
            
            // Basic product data
            $row = array(
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'description' => $product->get_description(),
                'short_description' => $product->get_short_description(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'categories' => self::get_product_categories($post->ID),
                'tags' => self::get_product_tags($post->ID)
            );
            
            // Add images if requested
            if ($include_images) {
                $row['image'] = self::get_product_main_image($post->ID);
                $row['gallery_images'] = self::get_product_gallery_images($post->ID);
            }
            
            // Designer configuration
            if ($config) {
                $row['product_type'] = $config->product_type;
                $row['colors'] = self::format_colors_for_export($config->colors);
                $row['color_sizes'] = self::format_color_sizes_for_export($config->color_sizes);
                $row['decoration_methods'] = self::format_decoration_methods_for_export($config->decoration_methods, $config->setup_fees);
                $row['designer_enabled'] = $config->enabled ? 'yes' : 'no';
                
                if ($include_inventory) {
                    $row['inventory'] = self::format_inventory_for_export($config->inventory);
                    $row['inventory_enabled'] = $config->inventory_enabled ? 'yes' : 'no';
                }
            } else {
                // Default values for products without designer config
                $row['product_type'] = 'shirt';
                $row['colors'] = '#FFFFFF:White, #000000:Black';
                $row['color_sizes'] = '';
                $row['decoration_methods'] = 'screen_printing:Screen Printing:10.95';
                $row['designer_enabled'] = 'no';
                
                if ($include_inventory) {
                    $row['inventory'] = '';
                    $row['inventory_enabled'] = 'no';
                }
            }
            
            $csv_rows[] = $row;
        }
        
        // Convert to CSV string
        $csv = '';
        foreach ($csv_rows as $row) {
            $csv .= '"' . implode('","', array_map('str_replace', array('"'), array('""'), $row)) . '"' . "\n";
        }
        
        return $csv;
    }
    
    /**
     * Get product categories as comma-separated string
     */
    private static function get_product_categories($product_id) {
        $terms = get_the_terms($product_id, 'product_cat');
        if (!$terms || is_wp_error($terms)) {
            return '';
        }
        
        $category_names = array();
        foreach ($terms as $term) {
            $category_names[] = $term->name;
        }
        
        return implode(', ', $category_names);
    }
    
    /**
     * Get product tags as comma-separated string
     */
    private static function get_product_tags($product_id) {
        $terms = get_the_terms($product_id, 'product_tag');
        if (!$terms || is_wp_error($terms)) {
            return '';
        }
        
        $tag_names = array();
        foreach ($terms as $term) {
            $tag_names[] = $term->name;
        }
        
        return implode(', ', $tag_names);
    }
    
    /**
     * Get product main image URL
     */
    private static function get_product_main_image($product_id) {
        $image_id = get_post_thumbnail_id($product_id);
        if (!$image_id) {
            return '';
        }
        
        $image_url = wp_get_attachment_url($image_id);
        return $image_url ? $image_url : '';
    }
    
    /**
     * Get product gallery images as pipe-separated string
     */
    private static function get_product_gallery_images($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }
        
        $gallery_ids = $product->get_gallery_image_ids();
        if (empty($gallery_ids)) {
            return '';
        }
        
        $gallery_urls = array();
        foreach ($gallery_ids as $image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $gallery_urls[] = $image_url;
            }
        }
        
        return implode('|', $gallery_urls);
    }
    
    /**
     * Format colors for export
     */
    private static function format_colors_for_export($colors_data) {
        if (empty($colors_data)) {
            return '';
        }
        
        $colors = maybe_unserialize($colors_data);
        if (!is_array($colors)) {
            return '';
        }
        
        $formatted_colors = array();
        foreach ($colors as $code => $name) {
            $formatted_colors[] = $code . ':' . $name;
        }
        
        return implode(', ', $formatted_colors);
    }
    
    /**
     * Format color sizes for export
     */
    private static function format_color_sizes_for_export($color_sizes_data) {
        if (empty($color_sizes_data)) {
            return '';
        }
        
        $color_sizes = maybe_unserialize($color_sizes_data);
        if (!is_array($color_sizes)) {
            return '';
        }
        
        $formatted_color_sizes = array();
        foreach ($color_sizes as $color_code => $sizes) {
            if (is_array($sizes)) {
                $formatted_color_sizes[] = $color_code . ':' . implode(',', $sizes);
            }
        }
        
        return implode('|', $formatted_color_sizes);
    }
    
    /**
     * Format decoration methods for export
     */
    private static function format_decoration_methods_for_export($methods_data, $fees_data) {
        if (empty($methods_data)) {
            return '';
        }
        
        $methods = maybe_unserialize($methods_data);
        $fees = maybe_unserialize($fees_data);
        
        if (!is_array($methods)) {
            return '';
        }
        
        if (!is_array($fees)) {
            $fees = array();
        }
        
        $formatted_methods = array();
        foreach ($methods as $key => $name) {
            $fee = isset($fees[$key]) ? $fees[$key] : 0;
            $formatted_methods[] = $key . ':' . $name . ':' . $fee;
        }
        
        return implode(', ', $formatted_methods);
    }
    
    /**
     * Format inventory for export
     */
    private static function format_inventory_for_export($inventory_data) {
        if (empty($inventory_data)) {
            return '';
        }
        
        $inventory = maybe_unserialize($inventory_data);
        if (!is_array($inventory)) {
            return '';
        }
        
        $formatted_inventory = array();
        foreach ($inventory as $color_code => $sizes) {
            if (is_array($sizes)) {
                $size_quantities = array();
                foreach ($sizes as $size => $quantity) {
                    $size_quantities[] = $size . ':' . $quantity;
                }
                if (!empty($size_quantities)) {
                    $formatted_inventory[] = $color_code . ':' . implode(',', $size_quantities);
                }
            }
        }
        
        return implode('|', $formatted_inventory);
    }
}
