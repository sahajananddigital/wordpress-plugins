<?php
/**
 * Unit Tests for Sahajanand_Static_Search_Updater.
 *
 * @package Sahajanand_Static_Search
 */

class Test_Sahajanand_Static_Search_Updater {

	/**
	 * @var Sahajanand_Static_Search_Updater
	 */
	private $updater;
	private $plugin_file;

	public function setUp() {
		wp_mock_reset();
		$this->plugin_file = dirname( __DIR__ ) . '/sahajanand-static-search-index.php';
		$this->updater     = new Sahajanand_Static_Search_Updater(
			$this->plugin_file,
			'1.0.3',
			'https://opensource.sahajananddigital.in/wordpress-plugins/static-search-index/info.json'
		);
	}

	public function test_check_update_when_newer_version_available() {
		global $wp_mock_remote_response;

		$remote_data = (object) array(
			'name'         => 'Sahajanand Static Search',
			'slug'         => 'static-search-index',
			'version'      => '1.1.0',
			'download_url' => 'https://example.com/static-search-index-1.1.0.zip',
			'homepage'     => 'https://example.com/plugin',
			'requires'     => '5.8',
			'tested'       => '7.1',
		);

		$wp_mock_remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $remote_data ),
		);

		$transient          = new stdClass();
		$transient->checked = array(
			'static-search-index/sahajanand-static-search-index.php' => '1.0.3',
		);
		$transient->response = array();
		$transient->no_update = array();

		$result = $this->updater->check_update( $transient );

		$slug = 'static-search-index/sahajanand-static-search-index.php';
		assert( isset( $result->response[ $slug ] ), 'Update must be present in response' );
		assert( '1.1.0' === $result->response[ $slug ]->new_version, 'New version must be 1.1.0' );
		assert( ! empty( $result->response[ $slug ]->package ), 'Package URL must not be empty' );
	}

	public function test_check_update_when_up_to_date() {
		global $wp_mock_remote_response;

		$remote_data = (object) array(
			'name'         => 'Sahajanand Static Search',
			'slug'         => 'static-search-index',
			'version'      => '1.0.3',
			'download_url' => 'https://example.com/static-search-index-1.0.3.zip',
		);

		$wp_mock_remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $remote_data ),
		);

		$transient          = new stdClass();
		$transient->checked = array(
			'static-search-index/sahajanand-static-search-index.php' => '1.0.3',
		);
		$transient->response = array();
		$transient->no_update = array();

		$result = $this->updater->check_update( $transient );

		$slug = 'static-search-index/sahajanand-static-search-index.php';
		assert( empty( $result->response[ $slug ] ), 'No update should be in response' );
		assert( isset( $result->no_update[ $slug ] ), 'Item must be in no_update list' );
		assert( '1.0.3' === $result->no_update[ $slug ]->new_version, 'Version must remain 1.0.3' );
	}

	public function test_transient_caching() {
		global $wp_mock_remote_response, $wp_mock_transients;

		$remote_data = (object) array(
			'name'    => 'Sahajanand Static Search',
			'version' => '1.0.4',
		);

		$wp_mock_remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $remote_data ),
		);

		// First call should write to transient.
		$data1 = $this->updater->get_remote_info( true );
		assert( ! empty( $data1 ), 'Remote info must not be empty' );
		assert( ! empty( $wp_mock_transients ), 'Transient cache must be populated' );

		// Simulate server returning 500 error on next call.
		$wp_mock_remote_response = array(
			'response' => array( 'code' => 500 ),
			'body'     => 'Server error',
		);

		// Second call with cache should still return cached data1.
		$data2 = $this->updater->get_remote_info( false );
		assert( is_object( $data2 ), 'Cached data must be returned from transient' );
		assert( '1.0.4' === $data2->version, 'Cached version must match' );
	}

	public function test_plugin_info_modal() {
		global $wp_mock_remote_response;

		$remote_data = (object) array(
			'name'         => 'Sahajanand Static Search',
			'version'      => '1.1.0',
			'author'       => 'Sahajanand Digital',
			'download_url' => 'https://example.com/download.zip',
			'sections'     => (object) array(
				'description' => 'Great static search plugin',
				'changelog'   => 'Bug fixes and performance boosts',
			),
		);

		$wp_mock_remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $remote_data ),
		);

		$args = (object) array( 'slug' => 'static-search-index' );
		$info = $this->updater->plugin_info( false, 'plugin_information', $args );

		assert( is_object( $info ), 'Info must be an object' );
		assert( 'Sahajanand Static Search' === $info->name, 'Info name must match' );
		assert( '1.1.0' === $info->version, 'Info version must match' );
		assert( 'https://example.com/download.zip' === $info->download_link, 'Download link must match' );
	}

	public function test_purge_cache_on_plugin_update() {
		global $wp_mock_transients;

		$cache_key = 'ssi_update_' . md5( 'static-search-index/sahajanand-static-search-index.php' );
		$wp_mock_transients[ $cache_key ] = (object) array( 'version' => '1.0.3' );

		$upgrader = new stdClass();
		$options  = array(
			'action'  => 'update',
			'type'    => 'plugin',
			'plugins' => array( 'static-search-index/sahajanand-static-search-index.php' ),
		);

		$this->updater->purge_cache( $upgrader, $options );

		assert( ! isset( $wp_mock_transients[ $cache_key ] ), 'Transient must be purged after update' );
	}

	public function test_update_plugins_hostname_filter() {
		global $wp_mock_remote_response;

		$remote_data = (object) array(
			'name'         => 'Sahajanand Static Search',
			'version'      => '1.2.0',
			'download_url' => 'https://example.com/static-search-index-1.2.0.zip',
			'homepage'     => 'https://example.com/plugin',
		);

		$wp_mock_remote_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $remote_data ),
		);

		$plugin_data = array(
			'Name'      => 'Sahajanand Static Search',
			'Version'   => '1.0.3',
			'UpdateURI' => 'https://opensource.sahajananddigital.in/wordpress-plugins/static-search-index/',
		);

		$result = $this->updater->filter_update_plugins_host(
			false,
			$plugin_data,
			'static-search-index/sahajanand-static-search-index.php'
		);

		assert( is_array( $result ), 'Result must be an array' );
		assert( '1.2.0' === $result['version'], 'New version must be 1.2.0' );
		assert( 'https://example.com/static-search-index-1.2.0.zip' === $result['package'], 'Package URL must match' );
		assert( 'static-search-index' === $result['slug'], 'Slug must match directory slug' );
	}
}
