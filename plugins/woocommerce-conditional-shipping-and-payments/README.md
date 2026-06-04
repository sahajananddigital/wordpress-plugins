# WooCommerce Conditional Shipping and Payments

A robust WooCommerce plugin to conditionally restrict payment gateways based on customer billing details (specifically Country).

## Features

- **Conditional Logic**: Enable or Disable payment gateways based on customer billing country.
- **Dynamic UI**: React-based admin interface for managing conditions seamlessly within WooCommerce Settings.
- **Checkout Support**:
  - **Classic Checkout**: Fully supported via AJAX updates.
  - **Block Checkout**: Fully supported via Store API and Batch request handling.
- **WP-CLI Support**: Manage conditions via command line for automation and bulk operations.
- **REST API**: Custom endpoints for robust data persistence.

## Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd woocommerce-conditional-shipping-and-payments
   ```

2. **Install Dependencies**:
   ```bash
   composer install  # Install PHP dependencies
   npm install       # Install JavaScript dependencies
   ```

3. **Build Assets**:
   ```bash
   npm run build     # Compile the React admin interface
   ```

4. **Activate**:
   - Activate the plugin from the WordPress Plugins menu.
   - Ensure WooCommerce is also active.

## Configuration

Navigate to **WooCommerce > Settings > Conditions** to manage your shipping and payment rules.

- **Add Condition**: Click "Add Condition" to create a new rule.
- **Title**: descriptive object for the rule (e.g., "Block COD for US").
- **Action**: Choose "Enable" or "Disable".
- **Payment Methods**: Select payment gateways to apply the rule to.
- **Countries**: Select countries where this rule applies.

## CLI Commands

Manage conditions directly via WP-CLI.

| Command | Description | Example |
|---------|-------------|---------|
| `wp wc csp list` | List all existing conditions. | `wp wc csp list` |
| `wp wc csp create` | Create a new condition. | `wp wc csp create "Restrict iDEAL" --action=enable --payment_methods=ideal --countries=NL` |
| `wp wc csp delete` | Delete a specific condition by ID. | `wp wc csp delete 1735201234` |

### Create options:
- `<title>`: The title of the condition.
- `--action`: `enable` or `disable` (default: `enable`).
- `--payment_methods`: Comma-separated list of payment method IDs (e.g., `cod,bacs`).
- `--countries`: Comma-separated list of country codes (e.g., `US,IN,CA`).

## Development

- **Build**: `npm run build` - Compiles production-ready assets.
- **Watch**: `npm run start` - Starts webpack in watch mode for development.
- **Lint CSS**: `npm run lint:css`
- **Lint JS**: `npm run lint:js`

## Testing

### Unit Tests (PHPUnit)
Verifies the backend logic for country detection and gateway filtering.
```bash
vendor/bin/phpunit
```

### End-to-End Tests (Playwright)
Visually verifies the checkout flow in a browser.
```bash
# Install browsers first
npx playwright install

# Run tests headlessly
npx playwright test

# Run tests with UI (Recommended for debugging)
npx playwright test --ui
```
