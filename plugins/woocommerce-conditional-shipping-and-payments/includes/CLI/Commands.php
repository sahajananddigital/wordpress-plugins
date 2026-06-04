<?php
/**
 * WP-CLI Commands.
 *
 * @package WooCommerceConditionalShipping
 */

namespace SahajanandDigital\WooCommerceConditionalShipping\CLI;

use WP_CLI;
use WP_CLI_Command;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSP Commands class.
 */
class Commands extends WP_CLI_Command {

	/**
	 * Init.
	 */
	public static function init() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'wc csp', __CLASS__ );
		}
	}

	/**
	 * List all conditions.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc csp list
	 *
	 * @subcommand list
	 */
	public function list_conditions( $args, $assoc_args ) {
		$conditions = get_option( 'wc_csp_conditions', array() );

		if ( empty( $conditions ) ) {
			WP_CLI::error( 'No conditions found.' );
		}

		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'id', 'title', 'enabled', 'action' ) );
		$formatter->display_items( $conditions );
	}

	/**
	 * Create a condition.
	 *
	 * ## OPTIONS
	 *
	 * <title>
	 * : The title of the condition.
	 *
	 * [--action=<action>]
	 * : The action to apply (enable/disable). Default: enable.
	 * 
	 * [--payment_methods=<methods>]
	 * : Comma-separated list of payment methods.
	 * 
	 * [--countries=<countries>]
	 * : Comma-separated list of billing countries.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc csp create "Allow iDEAL for NL" --action=enable --payment_methods=ideal --countries=NL
	 *
	 * @subcommand create
	 */
	public function create_condition( $args, $assoc_args ) {
		$title = $args[0];
		$action = $assoc_args['action'] ?? 'enable';
		$payment_methods = explode( ',', $assoc_args['payment_methods'] ?? '' );
		$countries = explode( ',', $assoc_args['countries'] ?? '' );

		$conditions = get_option( 'wc_csp_conditions', array() );
		
		$new_condition = array(
			'id'              => time(), // Simple ID generation
			'title'           => $title,
			'enabled'         => true,
			'action'          => $action,
			'payment_methods' => $payment_methods,
			'countries'       => $countries,
		);

		$conditions[] = $new_condition;
		update_option( 'wc_csp_conditions', $conditions );

		WP_CLI::success( "Condition created with ID: " . $new_condition['id'] );
	}

	/**
	 * Delete a condition.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the condition to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc csp delete 12345
	 *
	 * @subcommand delete
	 */
	public function delete_condition( $args, $assoc_args ) {
		$id = $args[0];
		$conditions = get_option( 'wc_csp_conditions', array() );

		$found = false;
		foreach ( $conditions as $key => $condition ) {
			if ( $condition['id'] == $id ) {
				unset( $conditions[ $key ] );
				$found = true;
				break;
			}
		}

		if ( $found ) {
			update_option( 'wc_csp_conditions', array_values( $conditions ) );
			WP_CLI::success( "Condition deleted." );
		} else {
			WP_CLI::error( "Condition not found." );
		}
	}
}
