<?php
/**
 * Plugin Name:       Sahajanand Static Search
 * Plugin URI:        https://github.com/sahajananddigital/wordpress-plugins
 * GitHub Plugin URI: https://github.com/sahajananddigital/wordpress-plugins
 * Primary Branch:    main
 * Release Asset:     true
 * Description:       Generates a client-side JSON search index and powers fuzzy search with local Fuse.js for Simply Static exports.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Sahajanand Digital
 * License:           GPL-2.0-or-later
 * Text Domain:       sahajanand-static-search
 *
 * @package Sahajanand_Static_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSI_VERSION', '1.0.2' );
define( 'SSI_PLUGIN_FILE', __FILE__ );
define( 'SSI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSI_INDEX_FILENAME', 'search-index.json' );
define( 'SSI_UPDATE_INFO_URL', 'https://sahajananddigital.github.io/wordpress-plugins/static-search-index/info.json' );

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
		require_once SSI_PLUGIN_DIR . 'includes/class-updater.php';

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
	 */
	public function on_static_export() {
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
	 * @return string[]
	 */
	public function get_supported_post_types() {
		$custom_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
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
		$query = new WP_Query(
			array(
				'post_type'              => $this->get_supported_post_types(),
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => true,
			)
		);

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
		$relative  = $this->to_relative_path( $permalink );

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

		return array(
			'id'         => (int) $post->ID,
			'title'      => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'permalink'  => $relative,
			'excerpt'    => $excerpt,
			'content'    => $raw_content,
			'categories' => $categories,
			'type'       => $post->post_type,
		);
	}

	/**
	 * Convert an absolute permalink to a site-relative path.
	 *
	 * @param string $url Absolute URL.
	 * @return string Relative path beginning with /.
	 */
	private function to_relative_path( $url ) {
		$home = home_url( '/' );
		$path = wp_make_link_relative( $url );

		if ( empty( $path ) || 0 !== strpos( $path, '/' ) ) {
			$path = str_replace( $home, '/', $url );
			$path = '/' . ltrim( (string) $path, '/' );
		}

		return $path;
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
		<div id="static-search-results" class="static-search-results" hidden role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'static-search-index' ); ?>"></div>
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

/**
 * Backward compatibility alias.
 *
 * @return Sahajanand_Static_Search
 */
function static_search_index() {
	return sahajanand_static_search();
}

sahajanand_static_search();
