<?php
/**
 * Generic Self-Hosted Plugin Updater for WordPress.
 *
 * Implements native WordPress plugin updates using a custom JSON endpoint (info.json)
 * and remote release ZIPs based on Misha Rudrastyh's architecture.
 *
 * @see https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles checking for updates, displaying plugin info modal, and caching.
 */
class Sample_Plugin_Updater {

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Plugin basename (e.g. sample-plugin/sample-plugin.php).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin directory slug (e.g. sample-plugin).
	 *
	 * @var string
	 */
	private $directory_slug;

	/**
	 * Current installed plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Remote info.json URL.
	 *
	 * @var string
	 */
	private $update_url;

	/**
	 * Transient cache key.
	 *
	 * @var string
	 */
	private $cache_key;

	/**
	 * Cache lifespan in seconds (default 12 hours).
	 *
	 * @var int
	 */
	private $cache_ttl;

	/**
	 * Constructor.
	 *
	 * @param string $file       Main plugin file (__FILE__).
	 * @param string $version    Current plugin version.
	 * @param string $update_url Remote info.json endpoint URL.
	 */
	public function __construct( $file, $version, $update_url = '' ) {
		$this->file           = $file;
		$this->plugin_slug    = plugin_basename( $file );
		$this->directory_slug = dirname( $this->plugin_slug );
		$this->version        = $version;
		$this->update_url     = $update_url;
		$this->cache_key      = 'sample_plugin_upd_' . md5( $this->plugin_slug );
		$this->cache_ttl      = 12 * HOUR_IN_SECONDS;

		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_directory_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'purge_cache' ), 10, 2 );
		add_action( 'in_plugin_update_message-' . $this->plugin_slug, array( $this, 'update_message' ), 10, 2 );

		// Support WordPress 5.8+ Update URI hostname hook.
		$host = wp_parse_url( $this->update_url, PHP_URL_HOST );
		if ( $host ) {
			add_filter( 'update_plugins_' . $host, array( $this, 'filter_update_plugins_host' ), 10, 4 );
		}
	}

	/**
	 * Fetch remote update information.
	 *
	 * @param bool $force_check Whether to bypass the transient cache.
	 * @return object|false Remote data object on success, false on failure.
	 */
	public function get_remote_info( $force_check = false ) {
		/**
		 * Filter the remote update manifest URL.
		 *
		 * @param string $url The endpoint URL.
		 */
		$url = apply_filters( 'sample_plugin_update_url', $this->update_url );

		if ( empty( $url ) ) {
			return false;
		}

		// Allow manual force-check via "Check Again" button in wp-admin/update-core.php.
		if ( ! $force_check && ! empty( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$force_check = true;
		}

		if ( ! $force_check ) {
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
				'headers'    => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! is_object( $data ) || empty( $data->version ) ) {
			return false;
		}

		set_transient( $this->cache_key, $data, $this->cache_ttl );

		return $data;
	}

	/**
	 * Hook into site_transient_update_plugins to inject update details if a new version is available.
	 *
	 * @param object $transient Update transient.
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = $this->get_remote_info();

		if (
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& ( empty( $remote->requires ) || version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) )
			&& ( empty( $remote->requires_php ) || version_compare( $remote->requires_php, PHP_VERSION, '<=' ) )
		) {
			$item              = new stdClass();
			$item->id          = $this->plugin_slug;
			$item->slug        = ! empty( $remote->slug ) ? $remote->slug : $this->directory_slug;
			$item->plugin      = $this->plugin_slug;
			$item->new_version = $remote->version;
			$item->url         = ! empty( $remote->homepage ) ? $remote->homepage : ( ! empty( $remote->download_url ) ? $remote->download_url : '' );
			$item->package     = ! empty( $remote->download_url ) ? $remote->download_url : '';
			$item->tested      = ! empty( $remote->tested ) ? $remote->tested : '';
			$item->requires    = ! empty( $remote->requires ) ? $remote->requires : '';
			$item->requires_php= ! empty( $remote->requires_php ) ? $remote->requires_php : '';

			if ( ! empty( $remote->icons ) ) {
				$item->icons = (array) $remote->icons;
			}
			if ( ! empty( $remote->banners ) ) {
				$item->banners = (array) $remote->banners;
			}

			$transient->response[ $this->plugin_slug ] = $item;
			unset( $transient->no_update[ $this->plugin_slug ] );
		} elseif ( $remote ) {
			// Populate no_update to clear badges in WP 5.5+
			$item              = new stdClass();
			$item->id          = $this->plugin_slug;
			$item->slug        = ! empty( $remote->slug ) ? $remote->slug : $this->directory_slug;
			$item->plugin      = $this->plugin_slug;
			$item->new_version = $this->version;
			$item->url         = ! empty( $remote->homepage ) ? $remote->homepage : '';
			$item->package     = '';
			$item->tested      = ! empty( $remote->tested ) ? $remote->tested : '';
			$item->requires    = ! empty( $remote->requires ) ? $remote->requires : '';
			$item->requires_php= ! empty( $remote->requires_php ) ? $remote->requires_php : '';

			$transient->no_update[ $this->plugin_slug ] = $item;
			unset( $transient->response[ $this->plugin_slug ] );
		}

		return $transient;
	}

	/**
	 * Hook into plugins_api to show plugin modal details.
	 *
	 * @param false|object|array $res    Response object or false.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		if (
			! isset( $args->slug )
			|| ( $args->slug !== $this->directory_slug && $args->slug !== $this->plugin_slug )
		) {
			return $res;
		}

		$remote = $this->get_remote_info();
		if ( ! $remote ) {
			return $res;
		}

		$info                 = new stdClass();
		$info->name           = ! empty( $remote->name ) ? $remote->name : 'Sample Plugin';
		$info->slug           = $this->directory_slug;
		$info->version        = $remote->version;
		$info->author         = ! empty( $remote->author ) ? $remote->author : '';
		$info->author_profile = ! empty( $remote->author_profile ) ? $remote->author_profile : '';
		$info->requires       = ! empty( $remote->requires ) ? $remote->requires : '';
		$info->tested         = ! empty( $remote->tested ) ? $remote->tested : '';
		$info->requires_php   = ! empty( $remote->requires_php ) ? $remote->requires_php : '';
		$info->homepage       = ! empty( $remote->homepage ) ? $remote->homepage : '';
		$info->download_link  = ! empty( $remote->download_url ) ? $remote->download_url : '';
		$info->trunk          = ! empty( $remote->download_url ) ? $remote->download_url : '';
		$info->last_updated   = ! empty( $remote->last_updated ) ? $remote->last_updated : '';
		$info->sections       = ! empty( $remote->sections ) ? (array) $remote->sections : array();

		if ( ! empty( $remote->banners ) ) {
			$info->banners = (array) $remote->banners;
		}
		if ( ! empty( $remote->icons ) ) {
			$info->icons = (array) $remote->icons;
		}

		return $info;
	}

	/**
	 * Rename the unzipped directory to ensure it matches the plugin directory slug.
	 *
	 * Prevents issues where GitHub release zips unpack to a different folder name.
	 *
	 * @param string      $source        File source location.
	 * @param string      $remote_source Remote source location.
	 * @param WP_Upgrader $upgrader      Upgrader instance.
	 * @param array       $hook_extra    Extra arguments.
	 * @return string
	 */
	public function fix_directory_name( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $source;
		}

		$correct_source = trailingslashit( $remote_source ) . $this->directory_slug . '/';

		if ( $source !== $correct_source && ! empty( $wp_filesystem ) ) {
			$wp_filesystem->move( $source, $correct_source );
			return $correct_source;
		}

		return $source;
	}

	/**
	 * Purge transient cache after plugin is updated.
	 *
	 * @param WP_Upgrader $upgrader Upgrader instance.
	 * @param array       $options  Options array.
	 */
	public function purge_cache( $upgrader, $options ) {
		if (
			isset( $options['action'], $options['type'] )
			&& 'update' === $options['action']
			&& 'plugin' === $options['type']
		) {
			$plugins = array();
			if ( ! empty( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
				$plugins = $options['plugins'];
			} elseif ( ! empty( $options['plugin'] ) ) {
				$plugins = array( $options['plugin'] );
			}

			if ( empty( $plugins ) || in_array( $this->plugin_slug, $plugins, true ) ) {
				delete_transient( $this->cache_key );
			}
		}
	}

	/**
	 * Display update notice or custom message under plugin row in wp-admin/plugins.php.
	 *
	 * @param array  $plugin_data Plugin metadata array.
	 * @param object $response    Update response object.
	 */
	public function update_message( $plugin_data, $response ) {
		$remote = $this->get_remote_info();
		if ( $remote && ! empty( $remote->upgrade_notice ) ) {
			echo '</p><p class="sample-plugin-update-notice" style="margin: 5px 0 0; color: #d63638;"><strong>' . esc_html__( 'Notice:', 'sample-plugin' ) . '</strong> ' . wp_kses_post( $remote->upgrade_notice );
		}
	}

	/**
	 * WordPress 5.8+ Update URI hostname filter.
	 *
	 * Hooked dynamically to `update_plugins_{$hostname}` where $hostname matches
	 * the domain in the plugin's `Update URI` header.
	 *
	 * @param array|false $update      Current update data or false.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin file path relative to plugins dir.
	 * @param string[]    $locales     Installed locales.
	 * @return array|false
	 */
	public function filter_update_plugins_host( $update, $plugin_data, $plugin_file, $locales = array() ) {
		if ( $plugin_file !== $this->plugin_slug ) {
			return $update;
		}

		$remote = $this->get_remote_info();
		if (
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
			&& ( empty( $remote->requires ) || version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) )
			&& ( empty( $remote->requires_php ) || version_compare( $remote->requires_php, PHP_VERSION, '<=' ) )
		) {
			return array(
				'id'          => ! empty( $plugin_data['UpdateURI'] ) ? $plugin_data['UpdateURI'] : $this->update_url,
				'slug'        => $this->directory_slug,
				'version'     => $remote->version,
				'url'         => ! empty( $remote->homepage ) ? $remote->homepage : ( ! empty( $remote->download_url ) ? $remote->download_url : '' ),
				'package'     => ! empty( $remote->download_url ) ? $remote->download_url : '',
				'tested'      => ! empty( $remote->tested ) ? $remote->tested : '',
				'requires_php'=> ! empty( $remote->requires_php ) ? $remote->requires_php : '',
			);
		}

		return $update;
	}
}
