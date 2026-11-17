<?php
/**
 * Unit Tests for Sahajanand_Static_Search.
 *
 * @package Sahajanand_Static_Search
 */

class Test_Sahajanand_Static_Search {

	/**
	 * @var Sahajanand_Static_Search
	 */
	private $plugin;

	public function setUp() {
		wp_mock_reset();
		$this->plugin = Sahajanand_Static_Search::instance();
	}

	public function test_singleton_instance() {
		$inst1 = Sahajanand_Static_Search::instance();
		$inst2 = sahajanand_static_search();
		assert( $inst1 instanceof Sahajanand_Static_Search, 'Instance must be Sahajanand_Static_Search' );
		assert( $inst1 === $inst2, 'Singleton instances must be identical' );
	}

	public function test_relative_path_root_installation() {
		global $wp_mock_home_url;
		$wp_mock_home_url = 'https://example.com';

		$url      = 'https://example.com/about-us/';
		$relative = $this->plugin->to_relative_path( $url );
		assert( '/about-us/' === $relative, "Expected '/about-us/', got '{$relative}'" );
	}

	public function test_relative_path_subdirectory_installation() {
		global $wp_mock_home_url;
		$wp_mock_home_url = 'https://projects.sahajananddigital.in/shajananddigital';

		$url      = 'https://projects.sahajananddigital.in/shajananddigital/my-article/';
		$relative = $this->plugin->to_relative_path( $url );
		assert( '/my-article/' === $relative, "Subdirectory must be stripped. Expected '/my-article/', got '{$relative}'" );
	}

	public function test_relative_path_preserves_query_and_fragment() {
		global $wp_mock_home_url;
		$wp_mock_home_url = 'https://example.com';

		$url      = 'https://example.com/contact-us/?ref=newsletter#form';
		$relative = $this->plugin->to_relative_path( $url );
		assert( '/contact-us/?ref=newsletter#form' === $relative, "Expected query and hash to be preserved, got '{$relative}'" );
	}

	public function test_relative_path_destination_url_with_subpath() {
		global $wp_mock_home_url, $wp_mock_options;
		$wp_mock_home_url = 'https://projects.sahajananddigital.in/staging';
		$wp_mock_options['simply-static'] = array(
			'destination_url' => 'https://username.github.io/my-portfolio/',
		);

		$url      = 'https://projects.sahajananddigital.in/staging/project-1/';
		$relative = $this->plugin->to_relative_path( $url );
		assert( '/my-portfolio/project-1/' === $relative, "Expected destination subpath prefix '/my-portfolio/project-1/', got '{$relative}'" );
	}

	public function test_relative_path_defensive_empty_input() {
		assert( '/' === $this->plugin->to_relative_path( '' ), 'Empty string must return /' );
		assert( '/' === $this->plugin->to_relative_path( null ), 'Null must return /' );
		assert( '/' === $this->plugin->to_relative_path( 12345 ), 'Non-string must return /' );
	}

	public function test_relative_path_filter_hook() {
		add_filter(
			'sahajanand_static_search_relative_path',
			function ( $path, $url ) {
				return '/custom' . $path;
			},
			10,
			2
		);

		$relative = $this->plugin->to_relative_path( 'https://example.com/item/' );
		assert( '/custom/item/' === $relative, "Filter should modify path to '/custom/item/', got '{$relative}'" );
	}

	public function test_static_version_bumping() {
		update_option( 'ssi_static_version', 5 );
		$version = $this->plugin->get_static_version();
		assert( '5' === $version, "Expected version '5', got '{$version}'" );
	}

	public function test_supported_post_types() {
		global $wp_mock_cpts;
		$wp_mock_cpts = array( 'portfolio' => 'portfolio', 'team' => 'team' );

		$types = $this->plugin->get_supported_post_types();
		assert( in_array( 'post', $types, true ), 'Must include post' );
		assert( in_array( 'page', $types, true ), 'Must include page' );
		assert( in_array( 'portfolio', $types, true ), 'Must include custom types' );
		assert( in_array( 'team', $types, true ), 'Must include custom types' );
	}

	public function test_regenerate_index_and_json_structure() {
		global $wp_mock_posts;

		$post1               = new WP_Post();
		$post1->ID          = 101;
		$post1->post_title   = 'Welcome &amp; Hello World';
		$post1->post_content = 'This is [caption]sample[/caption] <strong>content</strong> for testing static search.';
		$post1->post_excerpt = '';
		$post1->post_type    = 'post';
		$post1->permalink    = 'https://example.com/welcome-hello/';

		$wp_mock_posts = array( $post1 );

		$success = $this->plugin->regenerate_index();
		assert( true === $success, 'regenerate_index must succeed' );

		$path = $this->plugin->get_index_path();
		assert( file_exists( $path ), "Search index file must exist at {$path}" );

		$raw = file_get_contents( $path );
		$data = json_decode( $raw, true );

		assert( is_array( $data ), 'Index payload must be valid JSON' );
		assert( isset( $data['generated'] ), "Must have 'generated' timestamp" );
		assert( isset( $data['version'] ), "Must have 'version'" );
		assert( 1 === $data['count'], 'Count must match number of posts' );
		assert( 1 === count( $data['items'] ), 'Items count must match' );

		$item = $data['items'][0];
		assert( 101 === $item['id'], 'Post ID must match' );
		assert( 'Welcome & Hello World' === $item['title'], 'HTML entities in title must be decoded' );
		assert( '/welcome-hello/' === $item['permalink'], 'Permalink must be relative' );
		assert( false === strpos( $item['content'], '<strong>' ), 'HTML tags must be stripped from content' );
		assert( false === strpos( $item['content'], '[caption]' ), 'Shortcodes must be stripped from content' );
		assert( ! empty( $item['excerpt'] ), 'Excerpt fallback must be generated' );
	}

	public function test_simply_static_additional_urls() {
		$urls = $this->plugin->simply_static_additional_urls( array( 'https://example.com/' ) );
		assert( is_array( $urls ), 'URLs must be array' );
		assert( count( $urls ) >= 3, 'Must append unversioned and versioned index URLs' );

		$unversioned = $this->plugin->get_index_url( false );
		$versioned   = $this->plugin->get_index_url( true );
		assert( in_array( $unversioned, $urls, true ), 'Must contain unversioned URL' );
		assert( in_array( $versioned, $urls, true ), 'Must contain versioned URL' );
	}

	public function test_simply_static_additional_files() {
		$path = $this->plugin->get_index_path();
		$files = $this->plugin->simply_static_additional_files( array() );
		if ( file_exists( $path ) ) {
			assert( in_array( $path, $files, true ), 'Must include index file path' );
		}
	}
}
