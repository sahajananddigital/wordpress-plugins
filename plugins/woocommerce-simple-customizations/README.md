# WooCommerce Simple Customizations

A modular WooCommerce plugin to add simple customizations, featuring a React-based settings UI.


## Requirements
- WordPress 6.0+
- WooCommerce installed and active
- PHP 7.4+

## Features

### Cart Limits
Limit the purchase capability based on product categories, tags, or the entire cart.
- **Rule Type**: Select between "Global" (All Products), "Category", or "Tag".
- **Minimum Quantity**: Enforce a minimum quantity of items from the selected term in the cart.
- **Validation**: Displays an error message on the Cart page if limits are not met.

## Installation

1. Download the latest `.zip` release from the [GitHub Releases](https://github.com/multidots/woocommerce-simple-customizations/releases) page.
2. Go to your WordPress Admin.
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Upload the zip file and click **Install Now**.
5. Activate the plugin.

## Configuration

1. Go to **WooCommerce > Settings > Simple Customizations**.
2. Toggle **Enable Cart Limit**.
3. Select your criteria (Category/Tag) and the specific term.
4. Set the **Minimum Quantity**.
5. Click **Save Changes**.

## Development

### Prerequisites
- Node.js (v18+)
- Composer
- WordPress Environment

### Setup
1. Clone the repository to your `wp-content/plugins/` directory.
2. Install dependencies:
   ```bash
   npm install
   composer install
   ```
3. Build the assets:
   ```bash
   npm run build
   ```
4. Start development mode (watch for changes):
   ```bash
   npm start
   ```

### Release
This repository uses GitHub Actions to automatically build and release the plugin.
1. Draft a new Release on GitHub.
2. Create a new tag (e.g., `v1.0.1`).
3. Publish. The action will build the plugin and attach a `.zip` file to the release.