<?php

class Ai_Site_Gen_MCP_Client {
	private $endpoint;

	public function __construct() {
		$this->endpoint = get_option( 'ai_site_gen_mcp_endpoint', 'http://127.0.0.1:11434/v1/chat/completions' );
	}

	public function chat( $messages, $options = array() ) {
		if ( empty( $this->endpoint ) ) {
			return new WP_Error( 'mcp_error', 'MCP Endpoint is not configured.' );
		}

		$body = array(
			// Default model for Ollama if not specified, but usually ignored or specific model needed.
			// Let's pass a generic model or allow the user to set it later. 
			// Many local setups just use whatever model is loaded if not strict.
			'model' => 'llama3:8b', 
			'messages' => $messages,
			'stream' => false,
			'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
			'max_tokens' => isset($options['max_tokens']) ? $options['max_tokens'] : 2000,
		);

		// If the endpoint contains 'v1/chat/completions', we format it as OpenAI API style.
		// If it's a raw MCP JSON-RPC endpoint, we'd need to format it as JSON-RPC, but 
		// "OpenAI compatible" is the most robust way to connect to local LLMs like Ollama/LM Studio.
		
		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => json_encode( $body ),
			'timeout' => 120, // Local LLMs might take a bit longer
		);

		$response = wp_remote_post( $this->endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return new WP_Error( 'mcp_api_error', 'Failed to connect to local LLM: HTTP ' . $response_code . ' - ' . substr($response_body, 0, 200) );
		}

		$data = json_decode( $response_body, true );

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return array(
				'content' => $data['choices'][0]['message']['content']
			);
		} elseif ( isset( $data['message']['content'] ) ) {
			// Some Ollama setups return this if not fully OpenAI compatible
			return array(
				'content' => $data['message']['content']
			);
		}

		return new WP_Error( 'invalid_mcp_response', 'Unexpected response schema from local LLM.' );
	}
}
