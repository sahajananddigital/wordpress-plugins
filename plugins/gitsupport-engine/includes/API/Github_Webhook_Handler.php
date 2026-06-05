<?php
namespace GitSupport\Engine\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use GitSupport\Engine\Database;
use GitSupport\Engine\Outbound\Mailer;

/**
 * GitHub Webhook Handler class.
 */
class Github_Webhook_Handler {

	/**
	 * Handle GitHub Webhook request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle( WP_REST_Request $request ) {
		// 1. Verify Webhook Signature.
		$signature_header = $request->get_header( 'X-Hub-Signature-256' );
		$webhook_secret   = get_option( 'gitsupport_github_webhook_secret', '' );

		if ( empty( $webhook_secret ) ) {
			return new WP_Error( 'unconfigured', __( 'GitHub webhook secret is not configured.', 'gitsupport-engine' ), array( 'status' => 403 ) );
		}

		if ( empty( $signature_header ) ) {
			return new WP_Error( 'missing_signature', __( 'Missing X-Hub-Signature-256 header.', 'gitsupport-engine' ), array( 'status' => 401 ) );
		}

		$raw_body = $request->get_body();
		$computed_signature = 'sha256=' . hash_hmac( 'sha256', $raw_body, $webhook_secret );

		if ( ! hash_equals( $computed_signature, $signature_header ) ) {
			return new WP_Error( 'invalid_signature', __( 'Invalid webhook signature.', 'gitsupport-engine' ), array( 'status' => 403 ) );
		}

		// 2. Parse Payload.
		$payload = json_decode( $raw_body, true );
		if ( ! $payload ) {
			return new WP_Error( 'invalid_payload', __( 'Invalid JSON payload.', 'gitsupport-engine' ), array( 'status' => 400 ) );
		}

		$event = $request->get_header( 'X-GitHub-Event' );

		// Handle issue comments.
		if ( 'issue_comment' === $event ) {
			return $this->handle_issue_comment( $payload );
		}

		// Handle issue state changes directly (e.g. closing an issue closes the ticket).
		if ( 'issues' === $event ) {
			return $this->handle_issue_event( $payload );
		}

		return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Event ignored.', 'gitsupport-engine' ) ), 200 );
	}

	/**
	 * Handle issue comment event.
	 *
	 * @param array $payload Webhook payload.
	 * @return WP_REST_Response|WP_Error
	 */
	private function handle_issue_comment( $payload ) {
		$action  = isset( $payload['action'] ) ? $payload['action'] : '';
		$comment = isset( $payload['comment'] ) ? $payload['comment'] : array();
		$issue   = isset( $payload['issue'] ) ? $payload['issue'] : array();

		if ( 'created' !== $action || empty( $comment ) || empty( $issue ) ) {
			return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Comment action ignored.', 'gitsupport-engine' ) ), 200 );
		}

		$comment_body = isset( $comment['body'] ) ? $comment['body'] : '';
		$issue_number = isset( $issue['number'] ) ? (string) $issue['number'] : '';

		// Check if comment contains "/send".
		if ( strpos( $comment_body, '/send' ) === false ) {
			return new WP_REST_Response( array( 'success' => true, 'message' => __( 'No command in comment.', 'gitsupport-engine' ) ), 200 );
		}

		// Fetch ticket from database.
		$ticket = Database::get_ticket_by_github_issue_id( $issue_number );
		if ( ! $ticket ) {
			return new WP_Error( 'not_found', __( 'Ticket not found for this issue.', 'gitsupport-engine' ), array( 'status' => 404 ) );
		}

		// Extract email message.
		$email_text = '';
		$pos = strpos( $comment_body, '/send' );
		if ( $pos !== false ) {
			$email_text = trim( substr( $comment_body, $pos + 5 ) );
		}

		if ( empty( $email_text ) ) {
			return new WP_Error( 'empty_content', __( 'No email content provided after /send.', 'gitsupport-engine' ), array( 'status' => 400 ) );
		}

		// Compose Email.
		$to = $ticket->customer_email;
		$original_title = isset( $issue['title'] ) ? $issue['title'] : 'Support Request';
		
		$subject = ( stripos( $original_title, 'Re:' ) === 0 ) ? $original_title : 'Re: ' . $original_title;

		$headers = array();
		if ( ! empty( $ticket->email_message_id ) ) {
			$headers[] = 'In-Reply-To: ' . $ticket->email_message_id;
			$headers[] = 'References: ' . $ticket->email_message_id;
		}

		// Send Email.
		$sent = Mailer::send_email( $to, $subject, $email_text, $headers );

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', __( 'Failed to send outbound email.', 'gitsupport-engine' ), array( 'status' => 500 ) );
		}

		// Determine if comment indicates resolution, or if issue is already closed.
		$status = 'open';
		$is_resolved = ( 'closed' === $issue['state'] ) || ( strpos( $comment_body, '/resolve' ) !== false ) || ( strpos( $comment_body, '/close' ) !== false );

		if ( $is_resolved ) {
			$status = 'resolved';
			Database::update_ticket_by_github_issue( $issue_number, array( 'status' => 'resolved' ) );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Email sent successfully.', 'gitsupport-engine' ),
				'status'  => $status,
			),
			200
		);
	}

	/**
	 * Handle general issue event (e.g. closed).
	 *
	 * @param array $payload Webhook payload.
	 * @return WP_REST_Response
	 */
	private function handle_issue_event( $payload ) {
		$action = isset( $payload['action'] ) ? $payload['action'] : '';
		$issue  = isset( $payload['issue'] ) ? $payload['issue'] : array();

		if ( empty( $issue ) ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		$issue_number = isset( $issue['number'] ) ? (string) $issue['number'] : '';

		if ( 'closed' === $action ) {
			Database::update_ticket_by_github_issue( $issue_number, array( 'status' => 'resolved' ) );
		} elseif ( 'reopened' === $action ) {
			Database::update_ticket_by_github_issue( $issue_number, array( 'status' => 'open' ) );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
