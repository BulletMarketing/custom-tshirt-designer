# AI Rules and Guidelines

## Tech Stack Overview

- **Framework**: Next.js 15 with TypeScript and App Router
- **Styling**: Tailwind CSS with shadcn/ui component library
- **UI Components**: Radix UI primitives with custom shadcn/ui implementations
- **Icons**: Lucide React for consistent iconography
- **Forms**: React Hook Form with Zod validation and @hookform/resolvers
- **State Management**: React hooks and context (no external state management)
- **Theming**: next-themes for dark/light mode support
- **Toast Notifications**: Built-in toast system using Radix UI Toast primitives

## Library Usage Rules

### UI Components
- **ALWAYS** use shadcn/ui components from `/components/ui/` directory
- **DO NOT** install additional UI libraries (Material-UI, Ant Design, etc.)
- For icons, **ONLY** use `lucide-react` - it's already installed
- Use Radix UI primitives only through the shadcn/ui wrappers

### Styling
- **ALWAYS** use Tailwind CSS classes for styling
- **DO NOT** write custom CSS files or use CSS-in-JS libraries
- Use the existing CSS custom properties defined in `globals.css`
- Follow the established design system with consistent spacing, colors, and typography

### Forms and Validation
- **ALWAYS** use React Hook Form for form management
- **ALWAYS** use Zod for schema validation
- Use `@hookform/resolvers/zod` for integration
- Follow the established form patterns in `/components/ui/form.tsx`

### Data Fetching
- Use native `fetch` API or browser APIs
- **DO NOT** install axios, SWR, or React Query unless specifically requested
- Handle loading and error states manually with React state

### Routing
- **ALWAYS** use Next.js App Router (not Pages Router)
- Place pages in the `/app` directory following App Router conventions
- Use Next.js built-in navigation components and hooks

### Utilities
- Use the existing `cn()` utility function from `/lib/utils.ts` for className merging
- Use `clsx` and `tailwind-merge` (already installed) for conditional classes
- **DO NOT** install lodash or similar utility libraries

## Code Organization Rules

### File Structure
- Components go in `/components/` directory
- UI components stay in `/components/ui/`
- Pages go in `/app/` directory following App Router structure
- Utilities go in `/lib/` directory
- Hooks go in `/hooks/` directory
- Types can be defined inline or in component files

### Component Patterns
- Use `React.forwardRef` for components that need ref forwarding
- Use TypeScript interfaces for props
- Follow the established shadcn/ui component patterns
- Export components as named exports when possible

### Naming Conventions
- Use PascalCase for component names and files
- Use camelCase for functions, variables, and props
- Use kebab-case for CSS classes and file names when appropriate
- Prefix custom hooks with `use`

## WordPress Plugin Context

This codebase appears to be a React frontend for a WordPress plugin called "Custom T-Shirt Designer". Key considerations:

### Plugin Integration
- The app integrates with WooCommerce for e-commerce functionality
- Uses WordPress admin-ajax.php for backend communication
- Handles product customization with image uploads and design positioning
- Manages inventory, pricing tiers, and decoration methods

### Asset Management
- Images and uploads are handled through WordPress media system
- CSS and JS assets are enqueued through WordPress hooks
- Database operations use WordPress custom tables

### Compatibility
- Must work with various WordPress themes (especially Blocksy theme)
- Responsive design is crucial for mobile WooCommerce stores
- Gallery integration with WooCommerce product images

## Development Guidelines

### Performance
- Use React.memo() sparingly and only when needed
- Prefer native browser APIs over heavy libraries
- Optimize images and assets for web delivery
- Use proper loading states for better UX

### Accessibility
- Follow WCAG guidelines
- Use semantic HTML elements
- Ensure proper keyboard navigation
- Include appropriate ARIA labels

### Error Handling
- Handle errors gracefully with user-friendly messages
- Use the toast system for notifications
- Provide fallback UI for failed states
- Log errors appropriately for debugging

### Testing Considerations
- Write components that are easily testable
- Use proper TypeScript types for better development experience
- Follow established patterns for consistency

## Restrictions

### What NOT to Install
- **DO NOT** install additional CSS frameworks (Bootstrap, Bulma, etc.)
- **DO NOT** install additional icon libraries (FontAwesome, Heroicons, etc.)
- **DO NOT** install additional form libraries (Formik, Final Form, etc.)
- **DO NOT** install additional state management (Redux, Zustand, etc.)
- **DO NOT** install additional HTTP clients (axios, ky, etc.)

### What NOT to Do
- **DO NOT** modify existing shadcn/ui components in `/components/ui/`
- **DO NOT** create custom CSS files unless absolutely necessary
- **DO NOT** use inline styles - use Tailwind classes
- **DO NOT** break the established file structure
- **DO NOT** ignore TypeScript errors - fix them properly

## When in Doubt
- Follow existing patterns in the codebase
- Use shadcn/ui components and Tailwind CSS
- Keep it simple and maintainable
- Ask for clarification if requirements are unclear