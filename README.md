# WordPress Plugins Monorepo

This repository is a monorepo containing multiple WordPress plugins. Each plugin is located in its own subdirectory, operates independently, and is versioned/released separately.

---

## 📁 Repository Structure

```
wordpress-plugins/
├── .github/
│   └── workflows/
│       └── release-plugins.yml                # CI/CD path & version-based release workflow
├── multisite-subsite-admin-user-editor/       # Subsite Admin user editor plugin
├── sd-onetime-login-link/                     # One-time login link plugin
├── sensie-google-login-with-rtcamp-plugin/    # Google login integration
├── LICENSE
└── README.md                                  # This documentation
```

---

## 🚀 How Automated Releases Work

Releases are fully automated via GitHub Actions using **version-detection**. You do not need to manually create git tags or releases.

### Triggering a Release:
1. Make your changes to a plugin folder (e.g. `multisite-subsite-admin-user-editor`).
2. Bump the **`Version:`** value in the plugin's main PHP file header:
   ```php
   /**
    * Plugin Name: Multisite Subsite Admin User Editor (Edit Only)
    * Version: 1.2.0    <-- Bump this version number
    */
   ```
3. Commit and push your changes to the `main` branch.

### Automated Workflow Actions:
When you push to the `main` branch, the [release-plugins.yml](file:///C:/Users/vanpa/OneDrive/Documents/github/wordpress-plugins/.github/workflows/release-plugins.yml) workflow:
1. Scans all plugin subdirectories.
2. Reads the current version inside each plugin's main PHP file.
3. Compares the version against the existing Git tags.
4. If a tag for `{plugin-folder-name}/v{version}` **does not** exist, the runner:
   - Copies the plugin files to a clean build space.
   - Cleans out all development-only files (`tests/`, `phpunit.xml.dist`, `composer.json`, etc.).
   - Compresses the production files into a `.zip` archive.
   - Generates the git tag and pushes it back to GitHub.
   - Creates a new GitHub Release with the Title Case name (e.g., `Multisite Subsite Admin User Editor v1.2.0`) and attaches the `.zip` archive.
5. If the version tag **already exists**, it safely skips that plugin and does nothing.
