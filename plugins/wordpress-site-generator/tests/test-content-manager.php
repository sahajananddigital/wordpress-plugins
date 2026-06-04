<?php
/**
 * Test for Ai_Site_Gen_Content_Manager with Content.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/class-content-manager.php';

class Content_Manager_Test {

	public function run() {
		echo "Running Content_Manager_Test...\n";
		
		$this->test_create_pages_with_content();
		$this->test_invalid_data();
		
		echo "All tests passed for Content_Manager_Test!\n";
	}

	private function test_create_pages_with_content() {
		$manager = new Ai_Site_Gen_Content_Manager();
		$site_data = array(
			'siteTitle' => 'My New Site',
			'pages' => array(
				array(
					'title' => 'Home',
					'slug' => 'home',
					'sections' => array(
						array(
							'type' => 'hero',
							'headline' => 'Welcome to My Site',
							'subheadline' => 'This is generated copy.',
							'buttonText' => 'Click Me'
						)
					)
				)
			)
		);

		$created = $manager->create_pages( $site_data );

		if ( count( $created ) !== 1 ) {
			throw new Exception( "Expected 1 page created" );
		}

		$home_id = $created[0]['id'];
		$content = $GLOBALS['wp_posts'][$home_id]['post_content'];

		if ( strpos( $content, 'Welcome to My Site' ) === false ) {
			throw new Exception( "Home page missing generated headline" );
		}

		if ( strpos( $content, 'This is generated copy.' ) === false ) {
			throw new Exception( "Home page missing generated subheadline" );
		}

		if ( strpos( $content, 'Click Me' ) === false ) {
			throw new Exception( "Home page missing generated button text" );
		}
	}

	private function test_invalid_data() {
		$manager = new Ai_Site_Gen_Content_Manager();
		$created = $manager->create_pages( array() );
		if ( ! is_array( $created ) || count( $created ) !== 0 ) {
			throw new Exception( "Expected empty array for invalid data" );
		}
	}
}

$test = new Content_Manager_Test();
$test->run();
