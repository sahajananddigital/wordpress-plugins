<?php
/**
 * Mock MCP Client for testing.
 */
class Ai_Site_Gen_MCP_Client {
	public static $mock_response = array();
	public function chat( $messages, $options = array() ) {
		return self::$mock_response;
	}
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../includes/class-ai-generator.php';

class AI_Generator_Test {

	public function run() {
		echo "Running AI_Generator_Test...\n";
		
		$this->test_generate_site();
		$this->test_parse_content_valid();
		$this->test_parse_content_with_markdown();
		$this->test_parse_content_invalid();
		
		echo "All tests passed for AI_Generator_Test!\n";
	}

	private function test_generate_site() {
		$generator = new Ai_Site_Gen_Generator();
		
		// Setup mock response
		Ai_Site_Gen_MCP_Client::$mock_response = array(
			'content' => '{"siteTitle": "Mock Site", "pages": [{"title": "Home", "slug": "home", "sections": ["hero"]}]}'
		);

		$result = $generator->generate_site( "A test site" );

		if ( is_wp_error( $result ) ) {
			throw new Exception( "Expected successful result, got " . $result->get_error_message() );
		}

		if ( $result['siteTitle'] !== 'Mock Site' ) {
			throw new Exception( "Expected siteTitle to be 'Mock Site', got " . $result['siteTitle'] );
		}
	}

	private function test_parse_content_valid() {
		$generator = new Ai_Site_Gen_Generator();
		
		// Use reflection to call private method
		$method = new ReflectionMethod( 'Ai_Site_Gen_Generator', 'parse_content' );
		$method->setAccessible( true );

		$content = '{"key": "value"}';
		$result = $method->invoke( $generator, $content );
		if ( $result['key'] !== 'value' ) {
			throw new Exception( "Failed to parse valid JSON" );
		}
	}

	private function test_parse_content_with_markdown() {
		$generator = new Ai_Site_Gen_Generator();
		$method = new ReflectionMethod( 'Ai_Site_Gen_Generator', 'parse_content' );
		$method->setAccessible( true );

		$content = "Here is the JSON:\n```json\n{\"key\": \"value\"}\n```\nEnjoy!";
		$result = $method->invoke( $generator, $content );
		if ( $result['key'] !== 'value' ) {
			throw new Exception( "Failed to parse JSON with markdown" );
		}
	}

	private function test_parse_content_invalid() {
		$generator = new Ai_Site_Gen_Generator();
		$method = new ReflectionMethod( 'Ai_Site_Gen_Generator', 'parse_content' );
		$method->setAccessible( true );

		$content = 'Not JSON';
		$result = $method->invoke( $generator, $content );
		if ( ! is_wp_error( $result ) ) {
			throw new Exception( "Expected WP_Error for invalid JSON" );
		}
	}
}

$test = new AI_Generator_Test();
$test->run();
