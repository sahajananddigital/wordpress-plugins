<?php
namespace MultisiteSubsiteAdminUserEditor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Capability Manager Class.
 *
 * Intercepts WordPress capability mapping to grant edit rights
 * to subsite administrators under strict security conditions.
 */
class CapabilityManager {

    /**
     * Registers hooks with WordPress.
     *
     * @return void
     */
    public function register_hooks() {
        add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
        add_filter( 'enable_edit_any_user_configuration', [ $this, 'enable_edit_any_user_configuration' ], 10, 2 );
    }

    /**
     * Maps meta capabilities to primitive capabilities.
     *
     * Enables subsite administrators to edit users in their subsite,
     * while protecting Super Admins and blocking user creation/deletion.
     *
     * @param string[] $caps    Primitive capabilities required for the capability.
     * @param string   $cap     Capability being checked.
     * @param int      $user_id The user ID performing the check (the editor).
     * @param array    $args    Contextual arguments (typically [target_user_id]).
     * @return string[] Updated capabilities.
     */
    public function map_meta_cap( $caps, $cap, $user_id, $args ) {
        // Super Admins bypass all restrictions in Multisite.
        if ( is_super_admin( $user_id ) ) {
            return $caps;
        }

        $allowed_caps = apply_filters( 'ms_user_editor_allowed_caps', [ 'edit_user', 'edit_users' ] );
        $blocked_caps = apply_filters( 'ms_user_editor_blocked_caps', [ 'delete_user', 'delete_users', 'create_users', 'add_users' ] );

        // 1. Explicitly block creation and deletion capabilities for non-Super Admins.
        if ( in_array( $cap, $blocked_caps, true ) ) {
            return [ 'do_not_allow' ];
        }

        // 2. Process editing capabilities.
        if ( in_array( $cap, $allowed_caps, true ) ) {
            $blog_id = get_current_blog_id();

            // Check if we are targeting a specific user.
            if ( isset( $args[0] ) ) {
                $target_user_id = (int) $args[0];

                // Prevent editing self via this flow?
                // WordPress naturally handles editing self. We only restrict editing other users.
                if ( $user_id === $target_user_id ) {
                    return $caps;
                }

                // Strictly prevent non-Super Admins from editing Super Admins.
                if ( is_super_admin( $target_user_id ) ) {
                    return [ 'do_not_allow' ];
                }

                // Check that both users belong to the current subsite.
                $editor_is_member = is_user_member_of_blog( $user_id, $blog_id );
                $target_is_member = is_user_member_of_blog( $target_user_id, $blog_id );

                if ( ! $editor_is_member || ! $target_is_member ) {
                    return [ 'do_not_allow' ];
                }

                // Extensibility: allow other plugins to add additional rules.
                $is_editable = apply_filters(
                    'ms_user_editor_is_editable',
                    true,
                    $target_user_id,
                    $user_id,
                    $blog_id
                );

                if ( ! $is_editable ) {
                    return [ 'do_not_allow' ];
                }
            }

            // Map the "do_not_allow" caps to "edit_users".
            // Since subsite administrators have the "edit_users" capability locally, this allows them.
            foreach ( $caps as $key => $capability ) {
                if ( 'do_not_allow' === $capability ) {
                    $caps[ $key ] = 'edit_users';
                }
            }
        }

        return $caps;
    }

    /**
     * Enables user configuration screens to be editable.
     *
     * @param bool $allow   Whether editing is allowed.
     * @param int  $user_id The ID of the user being edited.
     * @return bool Updated value.
     */
    public function enable_edit_any_user_configuration( $allow, $user_id ) {
        // If the current user has capability to edit the target user, allow configuration edit.
        if ( current_user_can( 'edit_user', $user_id ) ) {
            return apply_filters( 'ms_user_editor_enable_edit_any_user_config', true, $user_id );
        }
        return $allow;
    }
}
