# AI Rules and Guidelines

## Tech Stack Overview

- **Plugin Type**: WordPress plugin with React frontend components
- **Frontend Framework**: React 19 with TypeScript (bundled for WordPress)
- **Styling**: Tailwind CSS with shadcn/ui component library
- **UI Components**: Radix UI primitives with custom shadcn/ui implementations
- **Icons**: Lucide React for consistent iconography
- **Forms**: React Hook Form with Zod validation and @hookform/resolvers
- **Backend Integration**: WordPress admin-ajax.php for AJAX requests
- **Database**: WordPress custom tables and WooCommerce integration
- **Asset Management**: WordPress enqueue system for CSS/JS files

## WordPress Plugin Architecture

### Backend (PHP)
- **Main Plugin File**: `custom-tshirt-designer.php` - handles plugin initialization
- **Admin Interface**: `class-ctd-admin.php` - WordPress admin panel integration
- **AJAX Handler**: `class-ctd-ajax.php` - handles frontend AJAX requests
- **Product Integration**: `class-ctd-product.php` - WooCommerce product customization
- **Database**: `class-ctd-installer.php` - custom table management

### Frontend Integration
- React components are built and enqueued through WordPress hooks
- Assets are served from `/assets/` directory (CSS/JS)
- Integration with WooCommerce product pages and cart functionality
- Must work with various WordPress themes (especially Blocksy theme)

## Library Usage Rules

### UI Components
- **ALWAYS** use shadcn/ui components from `/components/ui/` directory
- **DO NOT** install additional UI libraries (Material-UI, Ant Design, etc.)
- For icons, **ONLY** use `lucide-react` - it's already installed
- Use Radix UI primitives only through the shadcn/ui wrappers

### Styling
- **ALWAYS** use Tailwind CSS classes for styling
- **DO NOT** write custom CSS files unless absolutely necessary
- Use existing CSS files in `/assets/css/` for WordPress-specific styling
- Follow WordPress admin styling conventions when building admin interfaces

### Forms and Validation
- **ALWAYS** use React Hook Form for form management
- **ALWAYS** use Zod for schema validation
- Use `@hookform/resolvers/zod` for integration
- Follow the established form patterns in `/components/ui/form.tsx`

### Data Communication
- Use WordPress admin-ajax.php for backend communication
- **DO NOT** install axios, SWR, or React Query - use native fetch with WordPress nonces
- Handle WordPress-specific security (nonces, capabilities, sanitization)
- Integrate with WooCommerce hooks and filters

### WordPress Integration
- Follow WordPress coding standards and security practices
- Use WordPress hooks and filters for extensibility
- Ensure compatibility with WordPress multisite
- Handle WordPress user capabilities and permissions

## Code Organization Rules

### File Structure
- **React Components**: `/components/` directory
- **UI Components**: `/components/ui/` (shadcn/ui components)
- **WordPress Assets**: `/assets/css/` and `/assets/js/`
- **PHP Classes**: `/includes/` directory
- **Utilities**: `/lib/` directory for TypeScript utilities
- **Hooks**: `/hooks/` directory for React hooks

### WordPress-Specific Patterns
- Use WordPress nonces for AJAX security
- Follow WordPress sanitization and validation practices
- Use WordPress transients for caching when appropriate
- Integrate with WordPress media library for file uploads

### Component Patterns
- Build React components that can be embedded in WordPress pages
- Use `React.forwardRef` for components that need ref forwarding
- Follow established shadcn/ui component patterns
- Export components as named exports when possible

## WordPress Plugin Context

### WooCommerce Integration
- Extends WooCommerce product functionality
- Handles custom product options (colors, sizes, decorations)
- Integrates with WooCommerce cart and checkout
- Manages inventory and pricing tiers

### Admin Interface
- WordPress admin panel integration for product configuration
- Color picker integration using WordPress color picker
- Inventory management interface
- Pricing tier configuration

### Frontend Features
- Product customization interface on WooCommerce product pages
- Image upload and positioning tools
- Real-time preview of customizations
- Integration with product gallery and variations

## Development Guidelines

### WordPress Compatibility
- Test with common WordPress themes (especially Blocksy)
- Ensure responsive design for mobile WooCommerce stores
- Follow WordPress accessibility guidelines
- Handle WordPress updates and plugin conflicts gracefully

### Performance
- Optimize for WordPress hosting environments
- Use WordPress caching mechanisms
- Minimize JavaScript bundle size for faster page loads
- Optimize database queries and use WordPress query optimization

### Security
- Follow WordPress security best practices
- Sanitize all user inputs
- Use WordPress nonces for AJAX requests
- Validate user capabilities before sensitive operations

### Error Handling
- Use WordPress error handling mechanisms
- Log errors using WordPress debug logging
- Provide user-friendly error messages
- Handle WordPress-specific error scenarios

## Restrictions

### What NOT to Install
- **DO NOT** install additional CSS frameworks (Bootstrap, Bulma, etc.)
- **DO NOT** install additional icon libraries (FontAwesome, Heroicons, etc.)
- **DO NOT** install additional form libraries (Formik, Final Form, etc.)
- **DO NOT** install additional state management (Redux, Zustand, etc.)
- **DO NOT** install additional HTTP clients (axios, ky, etc.)

### WordPress-Specific Restrictions
- **DO NOT** modify WordPress core files
- **DO NOT** use deprecated WordPress functions
- **DO NOT** bypass WordPress security mechanisms
- **DO NOT** create direct database queries without WordPress WPDB
- **DO NOT** ignore WordPress coding standards

## When in Doubt
- Follow WordPress plugin development best practices
- Use shadcn/ui components and Tailwind CSS
- Integrate properly with WooCommerce hooks and filters
- Test with common WordPress themes and plugins
- Ask for clarification if WordPress integration requirements are unclear