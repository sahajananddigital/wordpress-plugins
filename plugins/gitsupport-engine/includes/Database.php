<?php
namespace GitSupport\Engine;

/**
 * Database manager class.
 */
class Database {

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'gitsupport_tickets';
	}

	/**
	 * Activate and create custom database table.
	 */
	public static function activate() {
		global $wpdb;
		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email_message_id varchar(255) NOT NULL,
			customer_email varchar(255) NOT NULL,
			github_issue_id varchar(255) NOT NULL,
			status varchar(50) DEFAULT 'open' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			resolved_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY email_message_id (email_message_id),
			KEY github_issue_id (github_issue_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a ticket.
	 *
	 * @param array $data Ticket data.
	 * @return int|bool
	 */
	public static function insert_ticket( $data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'email_message_id' => sanitize_text_field( $data['email_message_id'] ),
				'customer_email'   => sanitize_email( $data['customer_email'] ),
				'github_issue_id'  => sanitize_text_field( $data['github_issue_id'] ),
				'status'           => sanitize_text_field( $data['status'] ),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $inserted ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update a ticket.
	 *
	 * @param string $github_issue_id GitHub issue ID.
	 * @param array  $data            Ticket data to update.
	 * @return bool
	 */
	public static function update_ticket_by_github_issue( $github_issue_id, $data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$update_data = array();
		$formats     = array();

		if ( isset( $data['status'] ) ) {
			$update_data['status'] = sanitize_text_field( $data['status'] );
			$formats[]             = '%s';
			if ( 'resolved' === $data['status'] || 'closed' === $data['status'] ) {
				$update_data['resolved_at'] = current_time( 'mysql' );
				$formats[]                  = '%s';
			}
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		$updated = $wpdb->update(
			$table_name,
			$update_data,
			array( 'github_issue_id' => sanitize_text_field( $github_issue_id ) ),
			$formats,
			array( '%s' )
		);

		return $updated !== false;
	}

	/**
	 * Retrieve a ticket by email message ID.
	 *
	 * @param string $email_message_id Message ID.
	 * @return object|null
	 */
	public static function get_ticket_by_email_message_id( $email_message_id ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE email_message_id = %s LIMIT 1",
				sanitize_text_field( $email_message_id )
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Retrieve a ticket by GitHub issue ID.
	 *
	 * @param string $github_issue_id GitHub issue ID.
	 * @return object|null
	 */
	public static function get_ticket_by_github_issue_id( $github_issue_id ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE github_issue_id = %s LIMIT 1",
				sanitize_text_field( $github_issue_id )
			)
		);

		return $row ? $row : null;
	}

	/**
	 * Compute Metrics.
	 *
	 * @return array
	 */
	public static function get_metrics() {
		global $wpdb;
		$table_name = self::get_table_name();

		// Check if table exists first to avoid crash if called before activation
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return array(
				'total_tickets'          => 0,
				'open_tickets'           => 0,
				'resolved_tickets'       => 0,
				'avg_resolution_seconds' => 0,
			);
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
		$open  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE status = %s", 'open' ) );
		$resolved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_name WHERE status IN (%s, %s)", 'resolved', 'closed' ) );

		// Compute average resolution time in seconds.
		// Handled via TIMESTAMPDIFF to work in MySQL.
		$avg_res_time = (float) $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, resolved_at)) FROM $table_name WHERE resolved_at IS NOT NULL"
		);

		return array(
			'total_tickets'          => $total,
			'open_tickets'           => $open,
			'resolved_tickets'       => $resolved,
			'avg_resolution_seconds' => round( $avg_res_time, 2 ),
		);
	}
}
