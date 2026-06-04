# AI Context Brain

**Project**: WooCommerce Simple Customizations  
**Type**: Modular WooCommerce Plugin (PHP/React)

## Architecture
- **Modular**: specific features (Cart Limit, Product Inquiry) can be toggled.
- **Hybrid**: PHP backend (hooks/filters) + React Admin UI (SPA).

## Key Structures
- `includes/modules/`: Backend logic.
- `src/modules/`: Frontend settings (React).
- `includes/class-wsc-core.php`: Plugin loader.
- `src/components/SettingsApp.js`: Admin UI entry.

## Modules

### 1. Cart Limit
**Goal**: Enforce min/max cart quantities.
- **PHP**: [`includes/modules/cart-limit/class-wsc-cart-limit.php`](includes/modules/cart-limit/class-wsc-cart-limit.php)
- **JS**: [`src/modules/CartLimit/CartLimitSettings.js`](src/modules/CartLimit/CartLimitSettings.js)

### 2. Product Inquiry
**Goal**: Replace "Add to Cart" with inquiry flow (disable payment).
- **Features**: Toggle mode, custom button texts, "Inquiry Received" page, Priceless product inquiry.
- **PHP**: [`includes/modules/product-inquiry/class-wsc-product-inquiry.php`](includes/modules/product-inquiry/class-wsc-product-inquiry.php)
- **JS**: [`src/modules/ProductInquiry/ProductInquirySettings.js`](src/modules/ProductInquiry/ProductInquirySettings.js)
- **Note**: Uses CSS injection for Block Checkout text replacement.

### 3. Label Printing
**Goal**: Print order labels from admin.
- **PHP**: [`includes/modules/label-printing/class-wsc-label-printing.php`](includes/modules/label-printing/class-wsc-label-printing.php)
- **JS**: [`src/modules/LabelPrinting/LabelPrintingSettings.js`](src/modules/LabelPrinting/LabelPrintingSettings.js)

## workflows
- **Build**: `npm run build`
- **Lint**: `php -l <file>`
- **Test**: Manual verification (Classic & Block themes).

## Conventions
- **UI**: Add settings for all text strings. Use `@wordpress/components`.
- **Compat**: Ensure solutions work for **Block Checkout** (e.g., use `gettext` liberally or CSS overrides).
- **Code**: `snake_case` for PHP, `camelCase` for JS.
