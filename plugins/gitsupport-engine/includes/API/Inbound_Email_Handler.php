<?php
namespace GitSupport\Engine\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use GitSupport\Engine\Database;
use GitSupport\Engine\Integrations\AI_Triage;
use GitSupport\Engine\Integrations\GitHub_API;

/**
 * Inbound Email Handler class.
 */
class Inbound_Email_Handler {

	/**
	 * Handle inbound email.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle( WP_REST_Request $request ) {
		$params = $request->get_params();

		// Parse Postmark style.
		$from       = isset( $params['From'] ) ? $params['From'] : '';
		$subject    = isset( $params['Subject'] ) ? $params['Subject'] : '';
		$text_body  = isset( $params['TextBody'] ) ? $params['TextBody'] : '';
		$html_body  = isset( $params['HtmlBody'] ) ? $params['HtmlBody'] : '';
		$message_id = isset( $params['MessageID'] ) ? $params['MessageID'] : '';

		// Parse Mailgun style if Postmark was empty.
		if ( empty( $from ) ) {
			$from = isset( $params['from'] ) ? $params['from'] : ( isset( $params['sender'] ) ? $params['sender'] : '' );
		}
		if ( empty( $subject ) ) {
			$subject = isset( $params['subject'] ) ? $params['subject'] : '';
		}
		if ( empty( $text_body ) ) {
			$text_body = isset( $params['stripped-text'] ) ? $params['stripped-text'] : ( isset( $params['body-plain'] ) ? $params['body-plain'] : '' );
		}
		if ( empty( $message_id ) ) {
			$message_id = isset( $params['Message-Id'] ) ? $params['Message-Id'] : ( isset( $params['message-id'] ) ? $params['message-id'] : '' );
		}

		// Clean up fields.
		$email_body = ! empty( $text_body ) ? $text_body : wp_strip_all_tags( $html_body );

		// Extract email address from From string (e.g. "John Doe <john@example.com>")
		$customer_email = $this->extract_email( $from );

		if ( empty( $customer_email ) ) {
			return new WP_Error( 'missing_field', __( 'Sender email could not be parsed.', 'gitsupport-engine' ), array( 'status' => 400 ) );
		}

		if ( empty( $message_id ) ) {
			// fallback to a generated unique ID.
			$message_id = 'gen_' . uniqid() . '@gitsupport.local';
		}

		// 1. Run AI Triage.
		$triage_result = AI_Triage::triage( $subject, $email_body );

		$summary   = isset( $triage_result['summary'] ) ? $triage_result['summary'] : __( 'No summary available.', 'gitsupport-engine' );
		$sentiment = isset( $triage_result['sentiment'] ) ? $triage_result['sentiment'] : 'neutral';
		$priority  = isset( $triage_result['priority'] ) ? $triage_result['priority'] : 'medium';

		// 2. Open GitHub Issue.
		$issue_title = sprintf( '[Support] %s', $subject );

		$issue_body = sprintf(
			"### Customer Support Ticket\n\n" .
			"**From:** %s\n" .
			"**Email Message ID:** %s\n" .
			"**Priority:** %s\n" .
			"**Sentiment:** %s\n\n" .
			"#### AI Triage Summary\n%s\n\n" .
			"#### Original Email Message\n```\n%s\n```\n",
			$from,
			$message_id,
			strtoupper( $priority ),
			strtoupper( $sentiment ),
			$summary,
			$email_body
		);

		// Prepare labels.
		$labels = array(
			'support-ticket',
			'sentiment: ' . $sentiment,
			'priority: ' . $priority,
		);

		$github_issue = GitHub_API::create_issue( $issue_title, $issue_body, $labels );

		if ( is_wp_error( $github_issue ) ) {
			return $github_issue;
		}

		$github_issue_id = isset( $github_issue['number'] ) ? (string) $github_issue['number'] : '';

		if ( empty( $github_issue_id ) ) {
			return new WP_Error( 'github_error', __( 'Could not retrieve issue ID from GitHub.', 'gitsupport-engine' ), array( 'status' => 500 ) );
		}

		// 3. Save to Database.
		$ticket_id = Database::insert_ticket(
			array(
				'email_message_id' => $message_id,
				'customer_email'   => $customer_email,
				'github_issue_id'  => $github_issue_id,
				'status'           => 'open',
			)
		);

		if ( ! $ticket_id ) {
			return new WP_Error( 'db_error', __( 'Failed to save ticket to database.', 'gitsupport-engine' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'success'         => true,
				'ticket_id'       => $ticket_id,
				'github_issue_id' => $github_issue_id,
			),
			200
		);
	}

	/**
	 * Helper to extract email from From header.
	 *
	 * @param string $from From header value.
	 * @return string
	 */
	private function extract_email( $from ) {
		if ( filter_var( $from, FILTER_VALIDATE_EMAIL ) ) {
			return $from;
		}

		if ( preg_match( '/<([^>]+)>/', $from, $matches ) ) {
			if ( filter_var( $matches[1], FILTER_VALIDATE_EMAIL ) ) {
				return $matches[1];
			}
		}

		return '';
	}
}
