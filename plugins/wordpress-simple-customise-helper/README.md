# WordPress Simple Customise Helper

A modular WordPress helper plugin designed to easily add and manage custom features on your site. Built with a modern React-based Admin Dashboard.

## Features

- **Modular Architecture**: Features are built as independent modules, keeping your site lightweight.
- **Modern Admin UI**: React-powered "Marketplace" style dashboard for managing customizations.
- **Zero Configuration**: Simply enable the modules you need.
- **Auto-Updates**: Supports automatic updates via GitHub (Git Updater compatible).

## Modules

### Contact Icons
Add floating WhatsApp and Phone contact buttons to your website's frontend.
- **Enable/Disable**: Toggle on demand.
- **Customizable**: Set your specific WhatsApp and Phone numbers.
- **Sticky UI**: Always visible contact options for your visitors.

## Installation

### Manual
1. Download the `wordpress-simple-customise-helper.zip` from the [Releases](https://github.com/sahajananddigital/wordpress-simple-customise-helper/releases) page.
2. Go to **Plugins > Add New** in your WordPress admin.
3. Click **Upload Plugin** and select the zip file.
4. Activate the plugin.

### Via Git Updater
1. Ensure you have the [Git Updater](https://git-updater.com/) plugin installed.
2. Navigate to **Git Updater > Install Plugin**.
3. Enter the plugin's GitHub URL: `https://github.com/sahajananddigital/wordpress-simple-customise-helper`.
4. Install and Activate.

## Development

This plugin uses a hybrid structure with PHP for the backend and React for the admin interface.

### Prerequisites
- Node.js (v14+)
- Composer (Optional, for future PHP dependencies)

### Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/sahajananddigital/wordpress-simple-customise-helper.git
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start development server (watches for changes):
   ```bash
   npm start
   ```
4. Build for production:
   ```bash
   npm run build
   ```

## Releases

This repository uses **GitHub Actions** to automate releases.
- Pushing a tag starting with `v` (e.g., `v1.0.0`) triggers a workflow.
- The workflow builds the assets, creates a production-ready zip (excluding dev files), and attaches it to the GitHub Release.

## License

GPL-2.0-or-later
