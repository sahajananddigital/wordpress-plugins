<?php
namespace GitSupport\Engine\Integrations;

use WP_Error;

/**
 * AI Triage integration class.
 */
class AI_Triage {

	/**
	 * Perform triage analysis on email subject and body.
	 *
	 * @param string $subject Support email subject.
	 * @param string $body    Support email body.
	 * @return array Array containing summary, sentiment, and priority.
	 */
	public static function triage( $subject, $body ) {
		$api_key  = get_option( 'gitsupport_ai_api_key', '' );
		$provider = get_option( 'gitsupport_ai_provider', 'gemini' );

		// Fallback values if API key is not set.
		$fallback = array(
			'summary'   => wp_trim_words( $body, 15, '...' ),
			'sentiment' => 'neutral',
			'priority'  => 'medium',
		);

		if ( empty( $api_key ) ) {
			return $fallback;
		}

		if ( 'openai' === $provider ) {
			return self::triage_openai( $subject, $body, $api_key, $fallback );
		}

		return self::triage_gemini( $subject, $body, $api_key, $fallback );
	}

	/**
	 * Triage using Gemini API.
	 *
	 * @param string $subject  Subject.
	 * @param string $body     Body.
	 * @param string $api_key  API Key.
	 * @param array  $fallback Fallback response.
	 * @return array
	 */
	private static function triage_gemini( $subject, $body, $api_key, $fallback ) {
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode( $api_key );

		$prompt = "Analyze the following customer support request.\n\n" .
			"Subject: " . $subject . "\n" .
			"Message Body:\n" . $body . "\n\n" .
			"You must analyze the text and output a JSON object with EXACTLY the following keys:\n" .
			"{\n" .
			"  \"summary\": \"A concise 1-2 sentence summary of the customer's problem or request.\",\n" .
			"  \"sentiment\": \"positive\", \"neutral\", or \"negative\",\n" .
			"  \"priority\": \"low\", \"medium\", or \"high\"\n" .
			"}\n" .
			"Ensure the response is valid JSON and contains only the JSON object.";

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'contents'         => array(
						array(
							'parts' => array(
								array( 'text' => $prompt ),
							),
						),
					),
					'generationConfig' => array(
						'responseMimeType' => 'application/json',
					),
				)
			),
			'timeout' => 15,
		);

		$response = wp_safe_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $fallback;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return $fallback;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! empty( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$json_text = $data['candidates'][0]['content']['parts'][0]['text'];
			$parsed = json_decode( trim( $json_text ), true );
			if ( is_array( $parsed ) ) {
				return array(
					'summary'   => isset( $parsed['summary'] ) ? sanitize_text_field( $parsed['summary'] ) : $fallback['summary'],
					'sentiment' => isset( $parsed['sentiment'] ) ? sanitize_key( strtolower( $parsed['sentiment'] ) ) : $fallback['sentiment'],
					'priority'  => isset( $parsed['priority'] ) ? sanitize_key( strtolower( $parsed['priority'] ) ) : $fallback['priority'],
				);
			}
		}

		return $fallback;
	}

	/**
	 * Triage using OpenAI API.
	 *
	 * @param string $subject  Subject.
	 * @param string $body     Body.
	 * @param string $api_key  API Key.
	 * @param array  $fallback Fallback response.
	 * @return array
	 */
	private static function triage_openai( $subject, $body, $api_key, $fallback ) {
		$url = 'https://api.openai.com/v1/chat/completions';

		$system_prompt = "You are a customer support triage assistant.\n" .
			"You must analyze the incoming email and return a JSON response with these keys:\n" .
			"1. summary: A concise 1-2 sentence summary of the customer's problem or request.\n" .
			"2. sentiment: positive, neutral, or negative\n" .
			"3. priority: low, medium, or high";

		$user_prompt = "Subject: " . $subject . "\nBody: " . $body;

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'model'           => 'gpt-4o-mini',
					'messages'        => array(
						array(
							'role'    => 'system',
							'content' => $system_prompt,
						),
						array(
							'role'    => 'user',
							'content' => $user_prompt,
						),
					),
					'response_format' => array(
						'type' => 'json_object',
					),
				)
			),
			'timeout' => 15,
		);

		$response = wp_safe_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $fallback;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return $fallback;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
			$json_text = $data['choices'][0]['message']['content'];
			$parsed = json_decode( trim( $json_text ), true );
			if ( is_array( $parsed ) ) {
				return array(
					'summary'   => isset( $parsed['summary'] ) ? sanitize_text_field( $parsed['summary'] ) : $fallback['summary'],
					'sentiment' => isset( $parsed['sentiment'] ) ? sanitize_key( strtolower( $parsed['sentiment'] ) ) : $fallback['sentiment'],
					'priority'  => isset( $parsed['priority'] ) ? sanitize_key( strtolower( $parsed['priority'] ) ) : $fallback['priority'],
				);
			}
		}

		return $fallback;
	}
}
