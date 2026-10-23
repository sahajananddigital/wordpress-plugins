<?php
/**
 * Plugin Name:       Sahajanand Static Search
 * Plugin URI:        https://github.com/sahajananddigital/wordpress-plugins
 * GitHub Plugin URI: https://github.com/sahajananddigital/wordpress-plugins
 * Primary Branch:    main
 * Release Asset:     true
 * Description:       Generates a client-side JSON search index and powers fuzzy search with local Fuse.js for Simply Static exports.
 * Version:           1.0.1
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

define( 'SSI_VERSION', '1.0.1' );
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

		// Simply Static: ensure the JSON index is included in the export.
		add_filter( 'simply_static_additional_files', array( $this, 'simply_static_additional_files' ) );
		add_filter( 'simply_static_additional_urls', array( $this, 'simply_static_additional_urls' ) );
		add_filter( 'ss_additional_urls', array( $this, 'simply_static_additional_urls' ) );
		add_filter( 'ss_additional_files', array( $this, 'simply_static_additional_files' ) );

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
	 * @return string
	 */
	public function get_index_url() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . SSI_INDEX_FILENAME;
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

		$payload = array(
			'generated' => gmdate( 'c' ),
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

		wp_enqueue_style(
			'static-search',
			SSI_PLUGIN_URL . 'assets/css/static-search.css',
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : SSI_VERSION
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
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : SSI_VERSION,
			true
		);

		wp_localize_script(
			'static-search',
			'StaticSearchSettings',
			array(
				'indexUrl'   => esc_url_raw( $this->get_index_url() ),
				'homeUrl'    => esc_url_raw( home_url( '/' ) ),
				'minChars'   => 2,
				'maxResults' => 12,
				'i18n'       => array(
					'placeholder' => __( 'Search…', 'static-search-index' ),
					'noResults'   => __( 'No results found.', 'static-search-index' ),
					'searching'   => __( 'Searching…', 'static-search-index' ),
					'error'       => __( 'Search is temporarily unavailable.', 'static-search-index' ),
					'resultsFor'  => __( 'Results for', 'static-search-index' ),
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

		$index_url = $this->get_index_url();
		if ( $index_url && ! in_array( $index_url, $urls, true ) ) {
			$urls[] = $index_url;
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
