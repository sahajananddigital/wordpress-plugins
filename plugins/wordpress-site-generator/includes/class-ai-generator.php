<?php

class Ai_Site_Gen_Generator {

	public function generate_site( $prompt, $extra_context = array() ) {
		$client = new Ai_Site_Gen_MCP_Client();

		// Append Context to Prompt
		$context_str = "";
		if ( ! empty( $extra_context['email'] ) ) $context_str .= "\n- Contact Email: " . $extra_context['email'];
		if ( ! empty( $extra_context['phone'] ) ) $context_str .= "\n- Phone Number: " . $extra_context['phone'];
		if ( ! empty( $extra_context['address'] ) ) $context_str .= "\n- Physical Address: " . $extra_context['address'];
		if ( ! empty( $extra_context['services'] ) ) $context_str .= "\n- Services Offered (MANDATORY): " . $extra_context['services'];
		if ( ! empty( $extra_context['reference_url'] ) ) $context_str .= "\n- Reference URL / Profile: " . $extra_context['reference_url'];

		$final_prompt = $prompt;
		if ( ! empty( $context_str ) ) {
			$final_prompt .= "\n\n### ADDITIONAL BUSINESS CONTEXT (USE THIS DATA IN GENERATED CONTENT):\n" . $context_str;
		}

		$system_instruction = <<<'EOD'
You are an expert FSE (Full Site Editing) WordPress Architect and Content Writer.

# Role
Your task is to design a site structure AND write the actual copy (headlines, descriptions, button text) for a complete WordPress website based on the user's business description.

# Response Schema
Return ONLY a valid JSON object with this exact structure:
{
	"siteTitle": "String",
	"pages": [
		{
			"title": "String",
			"slug": "string",
			"sections": [
				{
					"type": "hero",
					"headline": "String",
					"subheadline": "String",
					"buttonText": "String"
				},
				{
					"type": "features",
					"headline": "String",
					"items": [
						{"title": "Service 1", "description": "Desc 1"},
						{"title": "Service 2", "description": "Desc 2"},
						{"title": "Service 3", "description": "Desc 3"}
					]
				},
				{
					"type": "about",
					"headline": "String",
					"content": "A long paragraph about the business."
				},
				{
					"type": "testimonials",
					"headline": "String",
					"quotes": [
						{"text": "Quote 1", "author": "Person 1"},
						{"text": "Quote 2", "author": "Person 2"}
					]
				},
				{
					"type": "cta",
					"headline": "String",
					"buttonText": "String"
				},
				{
					"type": "contact-form",
					"headline": "String",
					"email": "String",
					"phone": "String"
				}
			]
		}
	]
}

# Available Section Types
Use these types only: "hero", "features", "about", "testimonials", "pricing", "team", "gallery", "cta", "contact-form", "faq", "text".

# Rules
1. CRITICAL: Write unique, high-quality, professional copy for every section based on the user's business.
2. SERVICES LIST: If the user provides "Services Offered" in the context, you MUST use exactly those services. DO NOT invent new ones. If the user lists 3 services, create a "features" section with those 3 services.
3. If business context (email, phone, address) is provided, use it in the generated content.
4. Generate at least 3 pages (Home, About, Services/Contact).
5. Return strictly valid JSON. Do not include markdown code blocks.
EOD;

		$messages = array(
			array(
				'role' => 'system',
				'content' => $system_instruction
			),
			array(
				'role' => 'user',
				'content' => $final_prompt
			)
		);

		$response = $client->chat( $messages, array(
			'temperature' => 0.4, // Slightly higher for better writing
			'max_tokens'  => 3000
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->parse_content( $response['content'] );
	}

	private function parse_content( $content ) {
		error_log( 'AI Raw Message Content: ' . $content );
		
		// 1. First, try to find a JSON object using regex
		if ( preg_match( '/\{(?:[^{}]|(?R))*\}/s', $content, $matches ) ) {
			$json_str = $matches[0];
			$json = json_decode( $json_str, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array($json) ) {
				return $json;
			}
		}

		// 2. Fallback: Naive cleanup
		$cleaned = trim($content);
		$cleaned = preg_replace( '/^```json/', '', $cleaned );
		$cleaned = preg_replace( '/^```/', '', $cleaned );
		$cleaned = preg_replace( '/```$/', '', $cleaned );
		$cleaned = trim($cleaned);
		
		$json = json_decode( $cleaned, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array($json) ) {
			return $json;
		}

		return new WP_Error( 'invalid_ai_response', 'Failed to parse AI response. Raw Output: [' . substr( $content, 0, 1000 ) . '...]' );
	}
}
