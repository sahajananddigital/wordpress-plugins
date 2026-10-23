<?php
/**
 * Self-hosted updater for Sahajanand Static Search.
 *
 * Implements native WordPress plugin updates using custom JSON endpoint
 * based on Misha Rudrastyh's self-hosted update mechanism.
 *
 * @see https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
 * @package Sahajanand_Static_Search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles checking for updates and displaying plugin info modal.
 */
class Sahajanand_Static_Search_Updater {

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Plugin basename (e.g. static-search-index/sahajanand-static-search-index.php).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin directory slug (e.g. static-search-index).
	 *
	 * @var string
	 */
	private $directory_slug;

	/**
	 * Current plugin version.
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
	 * @param string $file       Main plugin file.
	 * @param string $version    Current plugin version.
	 * @param string $update_url Remote info.json endpoint URL.
	 */
	public function __construct( $file, $version, $update_url = '' ) {
		$this->file           = $file;
		$this->plugin_slug    = plugin_basename( $file );
		$this->directory_slug = dirname( $this->plugin_slug );
		$this->version        = $version;
		$this->update_url     = $update_url;
		$this->cache_key      = 'ssi_update_' . md5( $this->plugin_slug );
		$this->cache_ttl      = 12 * HOUR_IN_SECONDS;

		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'purge_cache' ), 10, 2 );
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
		$url = apply_filters( 'sahajanand_static_search_update_url', $this->update_url );

		if ( empty( $url ) ) {
			return false;
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
		if ( empty( $transient->checked ) ) {
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
			|| ( $args->slug !== $this->directory_slug && $args->slug !== $this->plugin_slug && $args->slug !== 'static-search-index' )
		) {
			return $res;
		}

		$remote = $this->get_remote_info();
		if ( ! $remote ) {
			return $res;
		}

		$info                 = new stdClass();
		$info->name           = ! empty( $remote->name ) ? $remote->name : 'Sahajanand Static Search';
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
			&& ! empty( $options['plugins'] )
		) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin === $this->plugin_slug ) {
					delete_transient( $this->cache_key );
					break;
				}
			}
		}
	}
}
