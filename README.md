# Custom T-Shirt Designer Plugin

**Version:** 1.3.8  
**Author:** Bullet Marketing  
**Requires:** WordPress 5.0+, WooCommerce 3.0+  
**Tested up to:** WordPress 6.4, WooCommerce 8.0  
**License:** GPL v2 or later  

## Description

The Custom T-Shirt Designer plugin is a comprehensive WooCommerce extension that allows customers to design custom t-shirts with an intuitive drag-and-drop interface. Perfect for print-on-demand businesses, custom apparel stores, and promotional product companies.

## Features

### 🎨 Design Tools
- **Fabric.js Canvas**: Professional-grade design canvas
- **Text Editor**: Add custom text with font selection, sizing, and styling
- **Image Upload**: Upload and manipulate custom graphics
- **Color Picker**: Choose from predefined colors or custom color options
- **Layer Management**: Organize design elements with layers

### 👕 Product Management
- **Size & Color Matrix**: Manage inventory for multiple size/color combinations
- **Custom Sizes**: Add non-standard sizes (2XS, 3XL, 4XL, 5XL, etc.)
- **Minimum Order Quantities**: Set minimum order requirements (default: 20 pieces)
- **Stock Management**: Track inventory for each size/color combination

### 🛒 E-commerce Integration
- **WooCommerce Native**: Seamless integration with WooCommerce
- **Cart Integration**: Custom designs saved to cart with preview
- **Order Management**: Design data included in order details
- **Email Notifications**: Custom design previews in order emails

### 🔧 Admin Features
- **Database Management**: Built-in database repair and maintenance tools
- **Product Configuration**: Easy setup for t-shirt designer products
- **Debug Tools**: Comprehensive logging and debugging system
- **Import/Export**: Bulk product configuration management

## Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/custom-tshirt-designer/`
3. **Activate** the plugin through the WordPress admin
4. **Configure** WooCommerce products to enable the designer

### Requirements
- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Configuration

### Enabling Designer for Products

1. Edit a WooCommerce product
2. Go to the **T-Shirt Designer** tab
3. Check **Enable T-Shirt Designer**
4. Configure available colors and sizes
5. Set inventory quantities for each combination
6. Save the product

### Color Management

Add colors with:
- Color name (e.g., "Navy Blue")
- Hex color code (e.g., "#000080")
- Size availability checkboxes
- Individual inventory quantities

### Size Configuration

Standard sizes supported:
- XS, S, M, L, XL, 2XL, 3XL, 4XL, 5XL

Custom sizes can be added using the "Add Custom Size" feature.

## Usage

### Customer Experience

1. **Select Product**: Choose a t-shirt with designer enabled
2. **Choose Options**: Select color and size
3. **Design**: Use the canvas to add text, images, and graphics
4. **Preview**: See real-time preview of the design
5. **Add to Cart**: Design is saved with the product

### Design Tools

- **Text Tool**: Click to add text, customize font, size, color
- **Image Upload**: Upload PNG, JPG, or SVG files
- **Move & Resize**: Drag elements, use handles to resize
- **Layers**: Manage element stacking order
- **Undo/Redo**: Full history management

## Database Structure

The plugin creates several database tables:

- `wp_ctd_product_configs`: Product configuration settings
- `wp_ctd_designs`: Saved customer designs
- `wp_ctd_colors`: Available color options
- `wp_ctd_inventory`: Size/color inventory tracking

## Customization

### Hooks & Filters

\`\`\`php
// Modify minimum order quantity
add_filter('ctd_minimum_order_quantity', function($quantity) {
    return 25; // Change from default 20 to 25
});

// Customize design canvas size
add_filter('ctd_canvas_dimensions', function($dimensions) {
    return array('width' => 400, 'height' => 500);
});
\`\`\`

### CSS Customization

Override styles by adding CSS to your theme:

\`\`\`css
.ctd-designer-canvas {
    border: 2px solid #your-color;
}

.ctd-color-picker {
    /* Your custom styles */
}
\`\`\`

## Troubleshooting

### Common Issues

**Plugin Activation Error**
- Ensure WooCommerce is installed and activated
- Check PHP version compatibility
- Verify file permissions

**Design Not Saving**
- Check AJAX functionality
- Verify nonce security
- Review error logs

**Database Issues**
- Use built-in repair tools in admin
- Check database permissions
- Verify table structure

### Debug Mode

Enable debug mode by adding to `wp-config.php`:

\`\`\`php
define('CTD_DEBUG', true);
\`\`\`

## Changelog

### Version 1.3.8
- **Fixed**: Admin size alignment issues
- **Improved**: CSS grid layout for size options
- **Enhanced**: Responsive design for mobile admin
- **Updated**: Author information to Bullet Marketing

### Version 1.3.7
- **Added**: Blocksy theme compatibility
- **Fixed**: Horizontal gallery thumbnail sizing
- **Improved**: Gallery thumbnail layout (100px x 100px)
- **Enhanced**: Theme-specific CSS loading

### Version 1.3.6
- **Added**: Horizontal gallery layout option
- **Fixed**: Product image gallery vertical scrolling
- **Improved**: Category view "Select Options" button
- **Enhanced**: Admin interface styling

## Support

For technical support and feature requests:

- **Email**: support@bulletmarketing.com
- **Documentation**: [Plugin Documentation](https://bulletmarketing.com/docs/custom-tshirt-designer)
- **Updates**: Check WordPress admin for automatic updates

## License

This plugin is licensed under the GPL v2 or later.

\`\`\`
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
\`\`\`

---

**Developed by Bullet Marketing** - Empowering e-commerce with custom solutions.
