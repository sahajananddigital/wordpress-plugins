<?php
namespace GitSupport\Engine\Outbound;

/**
 * Outbound Mailer class.
 */
class Mailer {

	/**
	 * Send an email to the customer.
	 *
	 * @param string       $to      Recipient email address.
	 * @param string       $subject Email subject.
	 * @param string       $message Email message body (plain text or HTML).
	 * @param array|string $headers Optional headers (like In-Reply-To).
	 * @return bool
	 */
	public static function send_email( $to, $subject, $message, $headers = array() ) {
		// Prepare HTML content.
		// Wrap comment text in simple template.
		$html_message = sprintf(
			'<html><body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">' .
			'<div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">' .
			'%s' .
			'</div>' .
			'</body></html>',
			nl2br( esc_html( $message ) )
		);

		// Combine headers.
		$final_headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( is_array( $headers ) ) {
			$final_headers = array_merge( $final_headers, $headers );
		} elseif ( is_string( $headers ) && ! empty( $headers ) ) {
			$final_headers[] = $headers;
		}

		return wp_mail( $to, $subject, $html_message, $final_headers );
	}
}
