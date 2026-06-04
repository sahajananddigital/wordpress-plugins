<?php
namespace MultisiteSubsiteAdminUserEditor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Security Gatekeeper Class.
 *
 * Enforces strict security boundaries before the profile/user-edit screen
 * processes requests or displays content.
 */
class SecurityGatekeeper {

    /**
     * Registers hooks with WordPress.
     *
     * @return void
     */
    public function register_hooks() {
        // Hook to load-user-edit.php which fires before the page logic processes POST or outputs HTML.
        add_action( 'load-user-edit.php', [ $this, 'check_edit_permission' ] );
    }

    /**
     * Verifies that the editing user has permission to edit the target user.
     *
     * Aborts request execution with wp_die if security boundaries are crossed.
     *
     * @return void
     */
    public function check_edit_permission() {
        $current_user = wp_get_current_user();
        
        // Super Admins are exempt from checks.
        if ( is_super_admin( $current_user->ID ) ) {
            return;
        }

        // Get the target user ID from the query string or POST data.
        $target_user_id = 0;
        if ( isset( $_REQUEST['user_id'] ) ) {
            $target_user_id = (int) $_REQUEST['user_id'];
        }

        // If no target user is specified, WordPress defaults to editing self (profile.php behavior),
        // or user-edit.php without user_id which is invalid (WordPress handles that natively).
        if ( ! $target_user_id || $target_user_id === $current_user->ID ) {
            return;
        }

        $blog_id = get_current_blog_id();

        // Trigger action before doing security checks for custom logging/integrations.
        do_action( 'ms_user_editor_before_edit_permission_check', $current_user->ID, $target_user_id, $blog_id );

        // 1. Protect Super Admin accounts from being edited by subsite admins.
        if ( is_super_admin( $target_user_id ) ) {
            wp_die(
                esc_html__( 'Security check: You do not have permission to edit a Super Admin user.', 'multisite-subsite-admin-user-editor' ),
                esc_html__( 'Permission Denied', 'multisite-subsite-admin-user-editor' ),
                [ 'response' => 403 ]
            );
        }

        // 2. Ensure both users belong to the current subsite.
        $editor_is_member = is_user_member_of_blog( $current_user->ID, $blog_id );
        $target_is_member = is_user_member_of_blog( $target_user_id, $blog_id );

        if ( ! $editor_is_member || ! $target_is_member ) {
            wp_die(
                esc_html__( 'Security check: You do not have permission to edit this user as they belong to a different subsite.', 'multisite-subsite-admin-user-editor' ),
                esc_html__( 'Permission Denied', 'multisite-subsite-admin-user-editor' ),
                [ 'response' => 403 ]
            );
        }

        // 3. Double check the core capability logic.
        if ( ! current_user_can( 'edit_user', $target_user_id ) ) {
            wp_die(
                esc_html__( 'Security check: You do not have sufficient permissions to edit this user.', 'multisite-subsite-admin-user-editor' ),
                esc_html__( 'Permission Denied', 'multisite-subsite-admin-user-editor' ),
                [ 'response' => 403 ]
            );
        }
    }
}
