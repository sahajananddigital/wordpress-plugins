---
name: wordpress-self-hosted-plugin-updater
description: >-
  Guide and runbook for setting up, debugging, and maintaining self-hosted WordPress plugin updates
  using Misha Rudrastyh's architecture (info.json manifest, native WordPress hooks,
  transient caching, and automated GitHub Releases/Pages workflows).
  Use when the user asks to set up, configure, fix, or release self-hosted updates for WordPress plugins.
---

# Self-Hosted WordPress Plugin Updates (Misha Rudrastyh Architecture)

This skill provides the complete runbook for implementing, maintaining, and automating self-hosted plugin updates in WordPress without bloated external libraries (such as EDD or Plugin Update Checker).

Based on the developer guide by **Misha Rudrastyh**: [Self-Hosted Plugin Update for WordPress](https://rudrastyh.com/wordpress/self-hosted-plugin-update.html).

---

## 🏗️ Architecture Overview

The update system consists of two parts:
1. **Update Server (Remote)**:
   - Hosts a static or dynamic `info.json` metadata manifest.
   - Hosts or links to the downloadable `.zip` release archive.
   - Hosted publicly on GitHub Pages (`https://<org>.github.io/<repo>/<plugin>/info.json`) with ZIPs attached to GitHub Releases.
2. **Client Plugin (Local WordPress)**:
   - Declares the **`Update URI`** header (WordPress 5.8+) to prevent updates from being hijacked by same-name plugins on WordPress.org.
   - Uses native WordPress filters (`site_transient_update_plugins`, `update_plugins_{$hostname}`, and `plugins_api`).
   - Uses `upgrader_process_complete` to purge transient cache upon update.
   - Uses `upgrader_source_selection` to guarantee correct folder naming on unzip.
   - Employs WordPress transients with automatic cache-busting when admins click **"Check Again"** (`?force-check=1`).

---

## 📋 Implementation Checklist

- [ ] **1. Plugin Header (`Update URI`)**: Declared in main plugin header (`Update URI: https://...`) to prevent WordPress.org spoofing.
- [ ] **2. Metadata Manifest (`info.json`)**: Hosted and accessible via HTTPS.
- [ ] **3. Updater Class (`class-updater.php`)**: Included in the plugin's `includes/` directory.
- [ ] **4. Plugin Bootstrap**: Instantiated in the main plugin file with file path, version, and info URL.
- [ ] **5. Packaging & Folder Structure**: Release ZIP contains `<plugin-slug>/` as its single root folder.
- [ ] **6. Automation Workflow**: GitHub Actions workflow automatically updates `info.json` and attaches the ZIP to a release when versions bump.

---

## 🚀 Step 1: The Remote Manifest (`info.json`)

The update server serves an `info.json` manifest. Below is the standard structure:

```json
{
  "name": "My Plugin Name",
  "slug": "my-plugin-slug",
  "author": "<a href=\"https://example.com\">My Company</a>",
  "author_profile": "https://example.com",
  "homepage": "https://github.com/my-org/my-repo",
  "version": "1.0.1",
  "download_url": "https://github.com/my-org/my-repo/releases/download/my-plugin-slug/v1.0.1/my-plugin-slug-v1.0.1.zip",
  "requires": "5.8",
  "tested": "6.6",
  "requires_php": "7.4",
  "last_updated": "2026-11-17 12:00:00",
  "sections": {
    "description": "Full description of the plugin features and usage.",
    "installation": "<ol><li>Upload plugin to <code>/wp-content/plugins/</code>.</li><li>Activate via Plugins screen.</li></ol>",
    "changelog": "<h4>1.0.1</h4><ul><li>Bug fixes and performance improvements.</li></ul>"
  },
  "banners": {
    "low": "https://example.com/assets/banner-772x250.jpg",
    "high": "https://example.com/assets/banner-1544x500.jpg"
  },
  "icons": {
    "1x": "https://example.com/assets/icon-128x128.png",
    "2x": "https://example.com/assets/icon-256x256.png"
  }
}
```

> [!NOTE]
> Detailed field specifications can be found in [references/info-json-schema.md](./references/info-json-schema.md).

---

## 🧩 Step 2: The Client Updater Class

Drop the updater class into your plugin (e.g. `includes/class-updater.php`).

See the reference implementation in:
👉 [examples/class-updater.php](./examples/class-updater.php)

Key responsibilities of the class:
1. **`check_update( $transient )`** on `site_transient_update_plugins`:
   - Checks `$transient->checked` to avoid unnecessary work.
   - Compares current plugin version with `$remote->version` using `version_compare()`.
   - Validates minimum WordPress version (`requires`) and PHP version (`requires_php`).
   - If update available: injects `$item` into `$transient->response[ $plugin_slug ]`.
   - If up to date: unsets from `response` and sets `$transient->no_update[ $plugin_slug ]`.
2. **`plugin_info( $res, $action, $args )`** on `plugins_api`:
   - Checks if `$action === 'plugin_information'` and `$args->slug === $this->directory_slug`.
   - Returns a `stdClass` containing tabs for the modal popup: `name`, `version`, `author`, `download_link`, `sections` (description, installation, changelog), `banners`, and `icons`.
3. **`fix_directory_name( $source, $remote_source, $upgrader, $hook_extra )`** on `upgrader_source_selection`:
   - Ensures the unzipped folder is renamed to match the plugin slug directory, preventing folder duplication or renaming issues if release zips have branch/tag suffixes.
4. **`purge_cache( $upgrader, $options )`** on `upgrader_process_complete`:
   - Automatically deletes the transient cache (`delete_transient( $this->cache_key )`) as soon as the plugin upgrade completes.
5. **Force-Check Support**:
   - Bypasses transient cache when `$_GET['force-check']` is present (triggered by clicking **"Check Again"** on `wp-admin/update-core.php`).

---

## 🔌 Step 3: Plugin Integration

In your main plugin file (e.g. `my-plugin/my-plugin.php`):

```php
/**
 * Plugin Name: My Plugin
 * Version:     1.0.1
 * Update URI:  https://sahajananddigital.github.io/wordpress-plugins/my-plugin/
 */

define( 'MY_PLUGIN_VERSION', '1.0.1' );
define( 'MY_PLUGIN_FILE', __FILE__ );
define( 'MY_PLUGIN_UPDATE_URL', 'https://sahajananddigital.github.io/wordpress-plugins/my-plugin/info.json' );

// Bootstrap self-hosted updater
add_action( 'plugins_loaded', function() {
    if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        require_once plugin_dir_path( MY_PLUGIN_FILE ) . 'includes/class-updater.php';

        new My_Plugin_Updater(
            MY_PLUGIN_FILE,
            MY_PLUGIN_VERSION,
            MY_PLUGIN_UPDATE_URL
        );
    }
} );
```

---

## 🤖 Step 4: Automated CI/CD Release with GitHub Actions

Automate the release process by having GitHub Actions build the `.zip` archive and update `docs/<plugin-slug>/info.json` for GitHub Pages.

See the complete GitHub Actions workflow template:
👉 [examples/release-workflow.yml](./examples/release-workflow.yml)

### Workflow Pipeline Steps:
1. **Detect Version Bump**: Reads `Version:` header from the plugin's main PHP file.
2. **Compile Assets**: Runs `npm install && npm run build` if `package.json` exists.
3. **Generate `info.json`**: Executes [scripts/generate-info-json.js](../../scripts/generate-info-json.js) to write `docs/<plugin-slug>/info.json`.
4. **Package ZIP**:
   - Applies `.distignore` exclusions (removes tests, `.git`, config files).
   - Packs the plugin into `<plugin-slug>-v<version>.zip` ensuring `<plugin-slug>/` is the top-level directory.
5. **Publish**:
   - Commits `info.json` to GitHub Pages.
   - Creates a Git tag (e.g. `<plugin-slug>/v1.0.1`).
   - Publishes a GitHub Release and attaches the ZIP package.

---

## 🧪 Testing & Verifying Updates

### Step-by-Step Test Procedure:
1. Install an older version (e.g. `1.0.0`) on a test WordPress site.
2. In the remote `info.json`, publish a higher version (e.g. `1.0.1`).
3. In WordPress admin, go to **Dashboard > Updates** and click **"Check Again"**.
4. Verify:
   - An update notification badge appears in the sidebar and on the **Plugins** screen.
   - The notification reads: *"There is a new version of [Plugin] available. View version 1.0.1 details or update now."*
   - Clicking **"View version details"** opens the native modal displaying your description and changelog.
   - Clicking **"Update now"** downloads the release zip, installs the update, and activates it seamlessly.
   - After update completes, the notice disappears and the plugin shows version `1.0.1`.

> [!TIP]
> For debugging and troubleshooting common update issues, refer to [references/troubleshooting.md](./references/troubleshooting.md).
