<?php
namespace GitSupport\Engine\Integrations;

use WP_Error;

/**
 * GitHub API integration class.
 */
class GitHub_API {

	/**
	 * Create an issue on GitHub.
	 *
	 * @param string $title  Issue title.
	 * @param string $body   Issue body.
	 * @param array  $labels Labels to apply.
	 * @return array|WP_Error
	 */
	public static function create_issue( $title, $body, $labels = array() ) {
		$token = get_option( 'gitsupport_github_token', '' );
		$owner = get_option( 'gitsupport_github_owner', '' );
		$repo  = get_option( 'gitsupport_github_repo', '' );

		if ( empty( $token ) || empty( $owner ) || empty( $repo ) ) {
			return new WP_Error( 'unconfigured', __( 'GitHub settings are not fully configured.', 'gitsupport-engine' ) );
		}

		$url = sprintf( 'https://api.github.com/repos/%s/%s/issues', $owner, $repo );

		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/vnd.github+json',
				'User-Agent'    => 'GitSupport-Engine-WordPress-Plugin',
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'title'  => $title,
					'body'   => $body,
					'labels' => $labels,
				)
			),
			'timeout' => 15,
		);

		$response = wp_safe_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 201 !== $status_code && 200 !== $status_code ) {
			return new WP_Error(
				'github_api_error',
				sprintf( __( 'GitHub API responded with code %d: %s', 'gitsupport-engine' ), $status_code, $response_body )
			);
		}

		$data = json_decode( $response_body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'github_api_invalid_json', __( 'GitHub API returned invalid JSON.', 'gitsupport-engine' ) );
		}

		return $data;
	}
}
