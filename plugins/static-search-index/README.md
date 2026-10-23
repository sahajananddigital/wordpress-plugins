# Sahajanand Static Search Index

Self-contained WordPress plugin that builds a local `search-index.json` and powers instant fuzzy search with **local Fuse.js** — designed for sites exported with the free [Simply Static](https://wordpress.org/plugins/simply-static/) plugin (no MySQL, PHP, or CDN at runtime).

## Folder structure

```
static-search-index/
├── sahajanand-static-search-index.php
├── README.md
└── assets/
    ├── js/
    │   ├── fuse.min.js      ← must be present (see below)
    │   └── static-search.js
    └── css/
        └── static-search.css
```

## 1. Install Fuse.js locally (required)

Fuse.js must live in the plugin — **do not** load it from a CDN in production.

### Option A — curl (recommended)

From the plugin root:

```bash
curl -fsSL "https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js" \
  -o assets/js/fuse.min.js
```

### Option B — npm

```bash
npm pack fuse.js@6.6.2
tar -xzf fuse.js-6.6.2.tgz
cp package/dist/fuse.min.js assets/js/fuse.min.js
rm -rf package fuse.js-6.6.2.tgz
```

### Option C — manual download

1. Open https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js
2. Save the file as `assets/js/fuse.min.js`

Confirm the file header mentions **Fuse.js v6.6.x** (or newer 6.x).

> This repository already includes `fuse.min.js` v6.6.2. Re-run the curl command only if you need to refresh it.

## 2. Install the plugin in WordPress

1. Copy the `static-search-index` folder into `wp-content/plugins/`.
2. Activate **Sahajanand Static Search Index** in **Plugins**.
3. On activation, the plugin writes `wp-content/uploads/search-index.json`.
4. The index regenerates automatically when published posts, pages, or public custom post types are saved, trashed, or deleted.

## 3. Verify live search (before export)

1. Visit the front end and use the theme’s search form (`form[role="search"]`, `.search-form`, or the Search block).
2. Type at least 2 characters — results should appear in `#static-search-results`.
3. Confirm these assets load from your domain (Network tab):
   - `/wp-content/plugins/static-search-index/assets/js/fuse.min.js`
   - `/wp-content/plugins/static-search-index/assets/js/static-search.js`
   - `/wp-content/plugins/static-search-index/assets/css/static-search.css`
   - `/wp-content/uploads/search-index.json`

## 4. Export with Simply Static

1. Install and activate Simply Static (free).
2. Run a static export as usual.
3. The plugin registers `search-index.json` via Simply Static filters (`simply_static_additional_urls` / `ss_additional_urls` and file variants) so the JSON is included even if the crawler misses it.
4. Plugin JS/CSS are linked from pages, so Simply Static should crawl and copy them automatically.

After export, open the static HTML and confirm search still works offline / without PHP.

## Index entry shape

```json
{
  "id": 12,
  "title": "About Us",
  "permalink": "/about-us/",
  "excerpt": "Short preview…",
  "content": "Full plain-text content…",
  "categories": ["News"],
  "type": "page"
}
```

Fuse.js weights: **title 0.5**, **excerpt 0.3**, **content 0.2**.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Writable `wp-content/uploads/`
- Fuse.js ≥ 6.6 in `assets/js/fuse.min.js`
