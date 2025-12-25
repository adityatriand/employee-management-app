# CSS Module Structure

This directory contains the modular CSS structure for WorkforceHub. The CSS has been split into maintainable modules instead of one large file.

## Structure

```
resources/css/
├── _variables.css      # CSS custom properties (colors, spacing, etc.)
├── _base.css           # Base styles (reset, typography, body)
├── _sidebar.css        # Sidebar navigation styles
├── _layout.css         # Main content area and page header
├── _components.css     # Reusable components (cards, buttons, tables)
├── _forms.css          # Form elements and searchable selects
├── _utilities.css      # Utility classes (action buttons, badges, pagination, search/filter)
├── _pages.css          # Page-specific styles (auth, landing, employee profile)
├── _modules.css        # Feature modules (activity log, file management, asset management)
├── _responsive.css     # Responsive styles and print media queries
├── main.css            # Main entry point (imports all modules)
└── README.md           # This file
```

## Building CSS

The CSS modules are compiled into a single `public/css/custom.css` file using Laravel Mix. The compilation order is defined in `webpack.mix.js`.

### Development

```bash
npm run dev
# or
npm run watch
```

### Production

```bash
npm run production
```

## Module Descriptions

### `_variables.css`
CSS custom properties (variables) used throughout the application. This must be imported first.

### `_base.css`
Base styles including typography and body styles.

### `_sidebar.css`
Sidebar navigation component styles.

### `_layout.css`
Main content area and page header styles.

### `_components.css`
Reusable UI components:
- Cards
- Buttons
- Tables

### `_forms.css`
Form-related styles:
- Form controls
- Select dropdowns
- Searchable select component

### `_utilities.css`
Utility classes and helper styles:
- Action buttons
- Badges
- Pagination
- Search and filter components
- File upload boxes
- Employee photos

### `_pages.css`
Page-specific styles:
- Authentication pages
- Landing page
- Employee profile page
- Dashboard styles

### `_modules.css`
Feature-specific module styles:
- Activity log
- File management
- Asset management
- Timeline components

### `_responsive.css`
Responsive breakpoints and print styles. Must be imported last.

## Adding New Styles

1. **New component?** Add to `_components.css`
2. **New utility?** Add to `_utilities.css`
3. **New page?** Add to `_pages.css`
4. **New feature module?** Add to `_modules.css` or create a new `_feature-name.css` file
5. **New variable?** Add to `_variables.css`

## Best Practices

1. **Use CSS variables** from `_variables.css` for colors, spacing, etc.
2. **Keep modules focused** - each module should have a single responsibility
3. **Follow naming conventions** - use BEM-like naming for components
4. **Mobile-first** - write base styles for mobile, then use media queries for larger screens
5. **Document complex styles** - add comments for non-obvious CSS

## Migration Notes

The original `public/css/custom.css` file (1666 lines) has been split into these modules. The compiled output maintains the same functionality while being much more maintainable.

