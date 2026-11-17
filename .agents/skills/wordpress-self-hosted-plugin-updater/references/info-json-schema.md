# `info.json` Manifest Schema & Reference

The `info.json` file serves as the remote manifest that WordPress queries when checking for plugin updates and displaying details in the plugin information modal.

---

## 📄 Complete JSON Schema Example

```json
{
  "name": "Sahajanand Static Search",
  "slug": "static-search-index",
  "author": "<a href=\"https://sahajananddigital.in\">Sahajanand Digital</a>",
  "author_profile": "https://sahajananddigital.in",
  "homepage": "https://github.com/sahajananddigital/wordpress-plugins",
  "version": "1.0.3",
  "download_url": "https://github.com/sahajananddigital/wordpress-plugins/releases/download/static-search-index/v1.0.3/static-search-index-v1.0.3.zip",
  "requires": "5.8",
  "tested": "6.6",
  "requires_php": "7.4",
  "last_updated": "2026-11-17 18:55:00",
  "upgrade_notice": "Recommended update: improves permalink routing for static exports.",
  "sections": {
    "description": "<p>Generates a client-side JSON search index and powers fuzzy search with local Fuse.js for Simply Static exports.</p>",
    "installation": "<ol><li>Upload the plugin files to the <code>/wp-content/plugins/static-search-index</code> directory.</li><li>Activate the plugin through the 'Plugins' screen in WordPress.</li></ol>",
    "changelog": "<h4>1.0.3</h4><ul><li>Fixed issue where subdirectory paths from staging leaked into search results on static root domains.</li></ul>"
  },
  "banners": {
    "low": "https://sahajananddigital.github.io/wordpress-plugins/static-search-index/banner-772x250.jpg",
    "high": "https://sahajananddigital.github.io/wordpress-plugins/static-search-index/banner-1544x500.jpg"
  },
  "icons": {
    "1x": "https://sahajananddigital.github.io/wordpress-plugins/static-search-index/icon-128x128.png",
    "2x": "https://sahajananddigital.github.io/wordpress-plugins/static-search-index/icon-256x256.png"
  }
}
```

---

## 🏷️ Field Descriptions

Field | Type | Required | Description
:--- | :--- | :--- | :---
`name` | `string` | **Yes** | Display name of the plugin shown in update notices and the details popup.
`slug` | `string` | **Yes** | Directory slug of the plugin (e.g. `static-search-index`). Must match the folder name in `wp-content/plugins/`.
`version` | `string` | **Yes** | Semantic version string (e.g. `1.0.3`). WordPress uses `version_compare()` against the currently installed version.
`download_url` | `string` | **Yes** | Direct HTTPS download URL to the installable `.zip` archive.
`requires` | `string` | No | Minimum WordPress version required (e.g. `5.8`). WordPress will block updates if the site's WP version is lower.
`tested` | `string` | No | Highest WordPress version the plugin was tested against (e.g. `6.6`).
`requires_php` | `string` | No | Minimum PHP version required (e.g. `7.4`). WordPress will block updates if the server's PHP version is lower.
`last_updated` | `string` | No | UTC timestamp formatted as `YYYY-MM-DD HH:MM:SS`.
`author` | `string` | No | Author name or HTML anchor tag (e.g. `<a href="...">Author</a>`).
`author_profile` | `string` | No | URL to the author's website or GitHub profile.
`homepage` | `string` | No | URL to the plugin homepage or GitHub repository.
`upgrade_notice` | `string` | No | Short text displayed below the update notice in the WordPress plugins table (via `in_plugin_update_message`).
`sections` | `object` | **Yes** | Object whose keys become tabs in the WordPress "View version details" modal:
↳ `description` | `string` | No | HTML content for the **Description** tab.
↳ `installation`| `string` | No | HTML content for the **Installation** tab.
↳ `changelog`   | `string` | No | HTML content for the **Changelog** tab. Supports HTML (`<h4>`, `<ul>`, `<li>`).
↳ `faq`         | `string` | No | HTML content for the **FAQ** tab (optional).
`banners` | `object` | No | URLs for modal banner header image (`low`: 772×250, `high`: 1544×500).
`icons`   | `object` | No | URLs for plugin icon (`1x`: 128×128, `2x`: 256×256).
