<?php
/**
 * Plugin Name:       Sahajanand Static Search
 * Plugin URI:        https://github.com/sahajananddigital/wordpress-plugins
 * Update URI:        https://opensource.sahajananddigital.in/wordpress-plugins/static-search-index/
 * GitHub Plugin URI: https://github.com/sahajananddigital/wordpress-plugins
 * Primary Branch:    main
 * Release Asset:     true
 * Description:       Generates a client-side JSON search index and powers fuzzy search with local Fuse.js for Simply Static exports.
 * Version:           1.0.3
 * Requires at least: 5.8
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Sahajanand Digital
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sahajanand-static-search
 *
 * @package Sahajanand_Static_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSI_VERSION', '1.0.3' );
define( 'SSI_PLUGIN_FILE', __FILE__ );
define( 'SSI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSI_INDEX_FILENAME', 'search-index.json' );
define( 'SSI_UPDATE_INFO_URL', 'https://opensource.sahajananddigital.in/wordpress-plugins/static-search-index/info.json' );

/**
 * Main plugin bootstrap.
 */
final class Sahajanand_Static_Search {

	/**
	 * Singleton instance.
	 *
	 * @var Sahajanand_Static_Search|null
	 */
	private static $instance = null;

	/**
	 * Tracks whether the static version was already bumped in the current request.
	 *
	 * @var bool
	 */
	private static $version_bumped = false;

	/**
	 * Tracks whether static export generation was already processed in the current request.
	 *
	 * @var bool
	 */
	private static $export_processed = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Sahajanand_Static_Search
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		register_activation_hook( SSI_PLUGIN_FILE, array( $this, 'activate' ) );

		add_action( 'save_post', array( $this, 'maybe_regenerate_index' ), 20, 2 );
		add_action( 'deleted_post', array( $this, 'regenerate_index' ) );
		add_action( 'trashed_post', array( $this, 'regenerate_index' ) );
		add_action( 'untrashed_post', array( $this, 'regenerate_index' ) );
		add_action( 'wp_loaded', array( $this, 'maybe_ensure_index_exists' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_results_container' ), 5 );

		// Simply Static export triggers: bump version on export start.
		add_action( 'ss_before_static_export', array( $this, 'on_static_export' ) );
		add_action( 'simply_static_before_export', array( $this, 'on_static_export' ) );
		add_action( 'simply_static_export_start', array( $this, 'on_static_export' ) );

		// Simply Static: ensure the JSON index is included in the export.
		add_filter( 'simply_static_additional_files', array( $this, 'simply_static_additional_files' ) );
		add_filter( 'simply_static_additional_urls', array( $this, 'simply_static_additional_urls' ) );
		add_filter( 'ss_additional_urls', array( $this, 'simply_static_additional_urls' ) );
		add_filter( 'ss_additional_files', array( $this, 'simply_static_additional_files' ) );

		// Optional filter for asset cache-busting across static site.
		add_filter( 'style_loader_src', array( $this, 'cache_bust_asset_src' ), 999 );
		add_filter( 'script_loader_src', array( $this, 'cache_bust_asset_src' ), 999 );

		// Self-hosted update checker.
		$this->init_updater();
	}

	/**
	 * Initialize self-hosted update checker.
	 */
	private function init_updater() {
		/**
		 * Filter whether to enable self-hosted updates.
		 *
		 * @param bool $enable Default true.
		 */
		if ( ! apply_filters( 'sahajanand_static_search_enable_self_hosted_updates', true ) ) {
			return;
		}

		$updater_file = SSI_PLUGIN_DIR . 'includes/class-updater.php';
		if ( ! file_exists( $updater_file ) ) {
			return;
		}

		require_once $updater_file;

		new Sahajanand_Static_Search_Updater(
			SSI_PLUGIN_FILE,
			SSI_VERSION,
			SSI_UPDATE_INFO_URL
		);
	}

	/**
	 * Generate index on plugin activation.
	 */
	public function activate() {
		$this->regenerate_index();
	}

	/**
	 * Absolute filesystem path to the search index JSON file.
	 *
	 * @return string
	 */
	public function get_index_path() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . SSI_INDEX_FILENAME;
	}

	/**
	 * Public URL to the search index JSON file.
	 *
	 * @param bool $with_version Whether to append ?version= query parameter for cache busting.
	 * @return string
	 */
	public function get_index_url( $with_version = true ) {
		$upload_dir = wp_upload_dir();
		$raw_url    = trailingslashit( $upload_dir['baseurl'] ) . SSI_INDEX_FILENAME;

		if ( ! $with_version ) {
			return $raw_url;
		}

		return add_query_arg( 'version', $this->get_static_version(), $raw_url );
	}

	/**
	 * Get current static site version number (e.g. "1", "2").
	 *
	 * @return string
	 */
	public function get_static_version() {
		$version = (int) get_option( 'ssi_static_version', 1 );
		if ( $version < 1 ) {
			$version = 1;
		}

		/**
		 * Filter the unique static site version.
		 *
		 * @param string $version Unique version string.
		 */
		return (string) apply_filters( 'sahajanand_static_search_version', (string) $version );
	}

	/**
	 * Increment static site version to invalidate browser and CDN caches.
	 *
	 * @return int New version number.
	 */
	public function bump_static_version() {
		if ( self::$version_bumped ) {
			return (int) get_option( 'ssi_static_version', 1 );
		}
		self::$version_bumped = true;

		$current     = (int) get_option( 'ssi_static_version', 0 );
		$new_version = $current + 1;
		update_option( 'ssi_static_version', $new_version, false );

		return $new_version;
	}

	/**
	 * Triggered when Simply Static initiates a static export.
	 *
	 * Deduplicates multiple Simply Static hooks in the same export request
	 * to prevent redundant version bumping and index rebuilding.
	 */
	public function on_static_export() {
		if ( self::$export_processed ) {
			return;
		}
		self::$export_processed = true;

		$this->bump_static_version();
		$this->regenerate_index();
	}

	/**
	 * Optionally cache-bust enqueued theme and plugin assets for static export.
	 *
	 * @param string $src Asset URL.
	 * @return string
	 */
	public function cache_bust_asset_src( $src ) {
		if ( empty( $src ) || is_admin() ) {
			return $src;
		}

		/**
		 * Filter whether to append static version query string to all enqueued assets.
		 *
		 * @param bool $enable Default false.
		 */
		if ( ! apply_filters( 'sahajanand_static_search_cache_bust_all_assets', false ) ) {
			return $src;
		}

		return add_query_arg( 'version', $this->get_static_version(), $src );
	}

	/**
	 * Regenerate when a published post/page is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function maybe_regenerate_index( $post_id, $post = null ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( null === $post ) {
			$post = get_post( $post_id );
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$supported = $this->get_supported_post_types();
		if ( ! in_array( $post->post_type, $supported, true ) ) {
			return;
		}

		// Rebuild for publish, update, or status transitions away from publish.
		$this->regenerate_index();
	}

	/**
	 * Get list of post types supported for indexing.
	 *
	 * Only includes public post types that are not explicitly excluded from search.
	 *
	 * @return string[]
	 */
	public function get_supported_post_types() {
		$custom_types = get_post_types(
			array(
				'public'              => true,
				'exclude_from_search' => false,
				'_builtin'            => false,
			),
			'names'
		);

		$post_types = array_merge( array( 'post', 'page' ), array_values( $custom_types ) );

		/**
		 * Filter supported post types for search index.
		 *
		 * @param string[] $post_types Array of post type names.
		 */
		return apply_filters( 'sahajanand_static_search_post_types', array_values( array_unique( $post_types ) ) );
	}

	/**
	 * Create the index if it is missing (e.g. after uploads wipe).
	 */
	public function maybe_ensure_index_exists() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$path = $this->get_index_path();
		if ( ! file_exists( $path ) ) {
			$this->regenerate_index();
		}
	}

	/**
	 * Build and write search-index.json to the uploads directory.
	 *
	 * @return bool True on success.
	 */
	public function regenerate_index() {
		$default_args = array(
			'post_type'              => $this->get_supported_post_types(),
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
		);

		/**
		 * Filter the WP_Query arguments used to generate the static search index.
		 *
		 * @param array<string, mixed> $default_args WP_Query arguments.
		 */
		$query_args = apply_filters( 'sahajanand_static_search_query_args', $default_args );

		$query = new WP_Query( $query_args );

		$entries = array();

		foreach ( $query->posts as $post ) {
			$entries[] = $this->build_entry( $post );
		}

		wp_reset_postdata();

		// Bump static site version for cache busting upon rebuild.
		$this->bump_static_version();

		$payload = array(
			'generated' => gmdate( 'c' ),
			'version'   => $this->get_static_version(),
			'count'     => count( $entries ),
			'items'     => $entries,
		);

		$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return false;
		}

		$path    = $this->get_index_path();
		$dirname = dirname( $path );

		if ( ! wp_mkdir_p( $dirname ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$result = file_put_contents( $path, $json, LOCK_EX );

		return false !== $result;
	}

	/**
	 * Map a WP_Post to a search index entry.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	private function build_entry( WP_Post $post ) {
		$permalink = get_permalink( $post );
		$relative  = $this->to_relative_path( $permalink, $post );

		$raw_content = $post->post_content;
		$raw_content = strip_shortcodes( $raw_content );
		$raw_content = wp_strip_all_tags( $raw_content );
		$raw_content = preg_replace( '/\s+/u', ' ', $raw_content );
		$raw_content = trim( (string) $raw_content );

		$excerpt = $post->post_excerpt;
		if ( '' === $excerpt ) {
			$excerpt = wp_trim_words( $raw_content, 40, '…' );
		} else {
			$excerpt = wp_strip_all_tags( $excerpt );
		}

		$categories = array();
		if ( 'post' === $post->post_type ) {
			$terms = get_the_terms( $post->ID, 'category' );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = $term->name;
				}
			}
		}

		$entry = array(
			'id'         => (int) $post->ID,
			'title'      => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'permalink'  => $relative,
			'excerpt'    => $excerpt,
			'content'    => $raw_content,
			'categories' => $categories,
			'type'       => $post->post_type,
		);

		/**
		 * Filter the search index entry for a post.
		 *
		 * @param array<string, mixed> $entry Search index entry.
		 * @param WP_Post              $post  Post object.
		 */
		return apply_filters( 'sahajanand_static_search_entry', $entry, $post );
	}

	/**
	 * Get Simply Static destination URL if configured.
	 *
	 * @return string Destination URL or empty string.
	 */
	public function get_destination_url() {
		$destination_url = '';

		// 1. Check Simply_Static\Options class if available.
		if ( class_exists( '\Simply_Static\Options' ) ) {
			try {
				$options = \Simply_Static\Options::instance();
				if ( method_exists( $options, 'get_destination_url' ) ) {
					$destination_url = (string) $options->get_destination_url();
				} elseif ( method_exists( $options, 'get' ) ) {
					$destination_url = (string) $options->get( 'destination_url' );
				}
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fall back to options table.
			}
		}

		// 2. Check WordPress options table for Simply Static settings.
		if ( empty( $destination_url ) ) {
			$ss_options = get_option( 'simply-static' );
			if ( is_array( $ss_options ) && ! empty( $ss_options['destination_url'] ) ) {
				$destination_url = (string) $ss_options['destination_url'];
			}
		}

		if ( empty( $destination_url ) ) {
			$ss_options_alt = get_option( 'simply_static_options' );
			if ( is_array( $ss_options_alt ) && ! empty( $ss_options_alt['destination_url'] ) ) {
				$destination_url = (string) $ss_options_alt['destination_url'];
			}
		}

		if ( empty( $destination_url ) ) {
			$single_dest = get_option( 'simply_static_destination_url' );
			if ( ! empty( $single_dest ) && is_string( $single_dest ) ) {
				$destination_url = $single_dest;
			}
		}

		/**
		 * Filter the destination URL for static search index permalinks.
		 *
		 * @param string $destination_url Destination URL or empty string.
		 */
		return (string) apply_filters( 'sahajanand_static_search_destination_url', $destination_url );
	}

	/**
	 * Convert an absolute permalink to a clean site-relative path for static search.
	 *
	 * Strips WordPress installation subdirectories (e.g. /shajananddigital) so
	 * that exported static sites on root domains (e.g. shajananddigital.in)
	 * don't retain staging subdirectories in search results.
	 *
	 * @param string       $url  Absolute URL.
	 * @param WP_Post|null $post Post object if available.
	 * @return string Relative path beginning with /.
	 */
	public function to_relative_path( $url, $post = null ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return '/';
		}

		$home = home_url();

		// Parse WordPress home path (e.g. "/shajananddigital" when installed in a subfolder).
		$home_path = (string) parse_url( $home, PHP_URL_PATH );
		$home_path = '/' . trim( $home_path, '/' );
		if ( '/' === $home_path ) {
			$home_path = '';
		}

		// Parse permalink components.
		$url_path = (string) parse_url( $url, PHP_URL_PATH );
		$query    = parse_url( $url, PHP_URL_QUERY );
		$fragment = parse_url( $url, PHP_URL_FRAGMENT );

		// Strip WordPress installation subfolder if present at the start of permalink path.
		if ( '' !== $home_path && 0 === strpos( $url_path, $home_path ) ) {
			$url_path = substr( $url_path, strlen( $home_path ) );
		}

		$url_path = '/' . ltrim( (string) $url_path, '/' );

		// If Simply Static has a destination URL configured with a subfolder (e.g. GitHub Pages /repo/).
		$dest_url = $this->get_destination_url();
		if ( ! empty( $dest_url ) ) {
			$dest_path = (string) parse_url( $dest_url, PHP_URL_PATH );
			$dest_path = '/' . trim( $dest_path, '/' );
			if ( '/' !== $dest_path && '' !== $dest_path ) {
				$url_path = $dest_path . '/' . ltrim( $url_path, '/' );
			}
		}

		if ( ! empty( $query ) ) {
			$url_path .= '?' . $query;
		}
		if ( ! empty( $fragment ) ) {
			$url_path .= '#' . $fragment;
		}

		/**
		 * Filter the calculated relative path.
		 *
		 * @param string       $url_path Calculated relative path.
		 * @param string       $url      Original URL.
		 * @param WP_Post|null $post     Post object if available.
		 */
		return (string) apply_filters( 'sahajanand_static_search_relative_path', $url_path, $url, $post );
	}

	/**
	 * Enqueue local Fuse.js, search script, and styles (no CDN).
	 */
	public function enqueue_assets() {
		$fuse_path   = SSI_PLUGIN_DIR . 'assets/js/fuse.min.js';
		$script_path = SSI_PLUGIN_DIR . 'assets/js/static-search.js';
		$style_path  = SSI_PLUGIN_DIR . 'assets/css/static-search.css';

		if ( ! file_exists( $fuse_path ) ) {
			return;
		}

		$static_version = $this->get_static_version();

		wp_enqueue_style(
			'static-search',
			SSI_PLUGIN_URL . 'assets/css/static-search.css',
			array(),
			$static_version
		);

		wp_enqueue_script(
			'fuse-js',
			SSI_PLUGIN_URL . 'assets/js/fuse.min.js',
			array(),
			'6.6.2',
			true
		);

		wp_enqueue_script(
			'static-search',
			SSI_PLUGIN_URL . 'assets/js/static-search.js',
			array( 'fuse-js' ),
			$static_version,
			true
		);

		wp_localize_script(
			'static-search',
			'StaticSearchSettings',
			array(
				'indexUrl'   => esc_url_raw( $this->get_index_url( true ) ),
				'homeUrl'    => esc_url_raw( home_url( '/' ) ),
				'version'    => (string) $static_version,
				'minChars'   => 2,
				'maxResults' => 12,
				'i18n'       => array(
					'placeholder' => __( 'Search…', 'sahajanand-static-search' ),
					'noResults'   => __( 'No results found.', 'sahajanand-static-search' ),
					'searching'   => __( 'Searching…', 'sahajanand-static-search' ),
					'error'       => __( 'Search is temporarily unavailable.', 'sahajanand-static-search' ),
					'resultsFor'  => __( 'Results for', 'sahajanand-static-search' ),
				),
			)
		);
	}

	/**
	 * Markup for the results dropdown (one container shared by all forms).
	 */
	public function render_results_container() {
		?>
		<div id="static-search-results" class="static-search-results" hidden role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'sahajanand-static-search' ); ?>"></div>
		<?php
	}

	/**
	 * Register search-index.json URL so Simply Static crawls/exports it.
	 *
	 * @param array|string $urls Existing additional URLs.
	 * @return array
	 */
	public function simply_static_additional_urls( $urls ) {
		if ( ! is_array( $urls ) ) {
			$urls = array_filter( array_map( 'trim', explode( "\n", (string) $urls ) ) );
		}

		$raw_url = $this->get_index_url( false );
		if ( $raw_url && ! in_array( $raw_url, $urls, true ) ) {
			$urls[] = $raw_url;
		}

		$versioned_url = $this->get_index_url( true );
		if ( $versioned_url && ! in_array( $versioned_url, $urls, true ) ) {
			$urls[] = $versioned_url;
		}

		return $urls;
	}

	/**
	 * Register search-index.json as an additional file for Simply Static.
	 *
	 * @param array|string $files Existing additional files.
	 * @return array
	 */
	public function simply_static_additional_files( $files ) {
		if ( ! is_array( $files ) ) {
			$files = array_filter( array_map( 'trim', explode( "\n", (string) $files ) ) );
		}

		$path = $this->get_index_path();
		if ( file_exists( $path ) && ! in_array( $path, $files, true ) ) {
			$files[] = $path;
		}

		return $files;
	}
}

/**
 * Initialize the plugin.
 *
 * @return Sahajanand_Static_Search
 */
function sahajanand_static_search() {
	return Sahajanand_Static_Search::instance();
}

sahajanand_static_search();
