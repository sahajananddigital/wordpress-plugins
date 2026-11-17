# Troubleshooting Self-Hosted WordPress Plugin Updates

Common issues, gotchas, and solutions when working with self-hosted WordPress plugin updates.

---

## 1. WordPress Doesn't Show the Update Notification

### Potential Causes:
- **Transient Cache**: WordPress caches update responses in a transient for up to 12 hours (`12 * HOUR_IN_SECONDS`).
- **PHP or WordPress Version Requirements**: If the remote `info.json` specifies `"requires": "6.5"` and the site runs WP 6.2, or `"requires_php": "8.1"` and the site runs PHP 7.4, WordPress silently hides the update.
- **Checked Transient Empty**: When visiting arbitrary admin pages, WordPress does not always populate `$transient->checked`.

### Solutions:
1. Go to **Dashboard > Updates** and click **"Check Again"**. Ensure your updater checks `! empty( $_GET['force-check'] )` to bypass the cache.
2. Clear the transient via WP-CLI:
   ```bash
   wp transient delete ssi_update_<md5-hash>
   wp transient delete update_plugins
   ```
3. Verify that `requires` and `requires_php` in `info.json` do not exceed the testing environment's specifications.

---

## 2. Updated Plugin Installs to a Duplicate or Wrong Folder

### The Problem:
After clicking "Update now", WordPress installs the plugin to a folder like `static-search-index-1.0.3/` or `static-search-index-main/` instead of `static-search-index/`. As a result, the plugin is deactivated or duplicated.

### Why This Happens:
When building a ZIP package (especially from GitHub release downloads), the root folder inside the ZIP defines the destination directory in `wp-content/plugins/`.

### Solutions:
1. **Ensure Proper Packaging**:
   When packaging the zip, ensure the top-level folder inside matches your plugin slug:
   ```bash
   mkdir -p build/my-plugin
   cp -r plugin-files/* build/my-plugin/
   cd build && zip -r ../my-plugin-v1.0.3.zip my-plugin
   ```
2. **Use `upgrader_source_selection`**:
   The updater class includes an `upgrader_source_selection` filter that automatically inspects and renames the extracted directory back to `$this->directory_slug`:
   ```php
   add_filter( 'upgrader_source_selection', array( $this, 'fix_directory_name' ), 10, 4 );
   ```

---

## 3. GitHub Releases Download Returns HTTP 403 or Fails

### The Problem:
WordPress fails to download the package with an error like:
> *"Download failed. Forbidden (403)"* or *"cURL error 28: Operation timed out"*.

### Why This Happens:
1. **User-Agent Header**: GitHub's API and release asset downloads require a non-empty `User-Agent` header. Default PHP requests without a user agent are rejected with HTTP 403.
2. **HTTP 302 Redirects**: GitHub release assets redirect to Amazon S3 (`objects.githubusercontent.com`). The download function in WordPress must follow redirects.

### Solutions:
- In `class-updater.php`, always send a standard WordPress User-Agent header:
  ```php
  'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' )
  ```
- WordPress core's `download_url()` natively follows 302 redirects, provided PHP's `curl` extension is enabled and not restricted by `open_basedir`.

---

## 4. "Ghost Update" Badges (Update Notice Won't Disappear)

### The Problem:
Even after successfully updating the plugin to `1.0.3`, the admin menu still shows an update notification badge for version `1.0.3`.

### Why This Happens:
In WordPress 5.5+, WordPress maintains two arrays inside the `update_plugins` transient:
- `$transient->response`: Plugins that have updates available.
- `$transient->no_update`: Plugins that are up to date.

If your updater class does not unset `$transient->response[ $plugin_slug ]` or fails to populate `$transient->no_update[ $plugin_slug ]` when versions match, WordPress keeps the badge.

### Solutions:
In `check_update()`:
```php
if ( version_compare( $this->version, $remote->version, '<' ) ) {
    $transient->response[ $this->plugin_slug ] = $item;
    unset( $transient->no_update[ $this->plugin_slug ] );
} else {
    $transient->no_update[ $this->plugin_slug ] = $item;
    unset( $transient->response[ $this->plugin_slug ] );
}
```
And ensure `upgrader_process_complete` calls `delete_transient( $this->cache_key )`.
