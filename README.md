# WordPress Plugins Monorepo

This repository is a monorepo containing multiple WordPress plugins under a unified structure. Each plugin is located in its own subdirectory inside the `plugins/` folder, operates independently, and is versioned/released separately.

---

## 📁 Repository Structure

```
wordpress-plugins/
├── .github/
│   └── workflows/
│       └── release-plugins.yml                # CI/CD path & version-based release workflow
├── plugins/                                   # All WordPress & WooCommerce Plugins
│   ├── HestiaCP-WordPress-Plugin/
│   ├── chatbot-for-woocommerce-sites/
│   ├── event-manager/
│   ├── multisite-subsite-admin-user-editor/   # Subsite Admin user editor plugin
│   ├── sd-onetime-login-link/                 # One-time login link plugin
│   ├── sensie-google-login-with-rtcamp-plugin/# Google login integration
│   ├── woocommerce-conditional-shipping-and-payments/
│   ├── woocommerce-simple-customizations/
│   ├── wordpress-simple-customise-helper/
│   ├── wordpress-site-generator/
│   └── wp-plugin-sahajanand-customise-helper/
├── LICENSE
└── README.md                                  # This documentation
```

---

## 🚀 How Automated Releases Work

Releases are fully automated via GitHub Actions using **version-detection** on plugins inside the `plugins/` folder. You do not need to manually create git tags or releases.

### Triggering a Release:
1. Make your changes inside a specific plugin directory (e.g. `plugins/multisite-subsite-admin-user-editor/`).
2. Bump the **`Version:`** value in the plugin's main PHP file header:
   ```php
   /**
    * Plugin Name: Multisite Subsite Admin User Editor (Edit Only)
    * Version: 1.2.0    <-- Bump this version number
    */
   ```
3. Commit and push your changes to the `main` branch.

### 📦 Compiling Assets (Gutenberg Blocks / SCSS / JS)
If a plugin directory contains a **`package.json`** file, the workflow will automatically:
1. Initialize Node.js dependencies (`npm ci` or `npm install`).
2. Execute the build command (`npm run build`).
This compiles Gutenberg blocks and packages production assets automatically before creating the release archive.

### 🔍 Custom File Exclusions (`.distignore`)
To control which files end up in the final release `.zip` folder, add a **`.distignore`** file to the root of your plugin directory.

**Example `.distignore`:**
```text
# Exclude testing environments
tests/
phpunit.xml.dist

# Exclude package managers and source codes
composer.json
composer.lock
package.json
package-lock.json
node_modules/
src/
webpack.config.js

# Exclude git system files
.gitignore
.distignore
```

*Note: If no `.distignore` file is present inside the plugin's folder, the workflow will fall back to a default set of exclusions, deleting standard development configuration files (such as `tests/`, `composer.json`, and `vendor/`).*
