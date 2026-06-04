<?php
namespace MultisiteSubsiteAdminUserEditor\Tests;

use WP_Mock\Tools\TestCase;
use WP_Mock;
use MultisiteSubsiteAdminUserEditor\CapabilityManager;

/**
 * Class TestCapabilityManager
 *
 * Tests the capability mapping logic of the CapabilityManager.
 */
class TestCapabilityManager extends TestCase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        WP_Mock::setUp();
        // Load the class under test
        require_once dirname( __DIR__ ) . '/includes/class-capability-manager.php';
    }

    /**
     * Tear down tests.
     */
    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    /**
     * Test that if the editor is a Super Admin, the original capabilities are returned unchanged.
     */
    public function test_map_meta_cap_returns_original_if_editor_is_super_admin() {
        $manager = new CapabilityManager();
        $caps    = [ 'edit_users' ];
        $cap     = 'edit_user';
        $user_id = 1; // Super Admin ID
        $args    = [ 2 ]; // Target User ID

        // Mock is_super_admin to return true for editor
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $user_id )
            ->andReturn( true );

        $result = $manager->map_meta_cap( $caps, $cap, $user_id, $args );

        $this->assertEquals( $caps, $result );
    }

    /**
     * Test that blocked capabilities (like delete_user) return 'do_not_allow' for non-Super Admins.
     */
    public function test_map_meta_cap_blocks_forbidden_caps_for_subsite_admins() {
        $manager = new CapabilityManager();
        $caps    = [ 'delete_users' ];
        $cap     = 'delete_user';
        $user_id = 2; // Subsite Admin ID
        $args    = [ 3 ]; // Target User ID

        // Mock is_super_admin to return false for the editor
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $user_id )
            ->andReturn( false );

        $result = $manager->map_meta_cap( $caps, $cap, $user_id, $args );

        $this->assertEquals( [ 'do_not_allow' ], $result );
    }

    /**
     * Test that editing a Super Admin is strictly blocked.
     */
    public function test_map_meta_cap_blocks_editing_super_admin() {
        $manager = new CapabilityManager();
        $caps    = [ 'do_not_allow' ];
        $cap     = 'edit_user';
        $user_id = 2; // Subsite Admin
        $args    = [ 1 ]; // Target User (Super Admin ID)

        // Mock is_super_admin to return false for editor (2) and true for target (1)
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $user_id )
            ->andReturn( false );
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $args[0] )
            ->andReturn( true );

        // Mock current blog ID
        WP_Mock::userFunction( 'get_current_blog_id' )
            ->andReturn( 1 );

        $result = $manager->map_meta_cap( $caps, $cap, $user_id, $args );

        $this->assertEquals( [ 'do_not_allow' ], $result );
    }

    /**
     * Test that editing is blocked if either the editor or the target is not a member of the current blog.
     */
    public function test_map_meta_cap_blocks_editing_if_not_same_blog() {
        $manager = new CapabilityManager();
        $caps    = [ 'do_not_allow' ];
        $cap     = 'edit_user';
        $user_id = 2; // Subsite Admin
        $args    = [ 3 ]; // Target User
        $blog_id = 1;

        // Mock is_super_admin to return false for both
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $user_id )
            ->andReturn( false );
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $args[0] )
            ->andReturn( false );

        WP_Mock::userFunction( 'get_current_blog_id' )
            ->andReturn( $blog_id );

        // Mock one of them as not a member of the blog
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( $user_id, $blog_id )
            ->andReturn( true );
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( $args[0], $blog_id )
            ->andReturn( false );

        $result = $manager->map_meta_cap( $caps, $cap, $user_id, $args );

        $this->assertEquals( [ 'do_not_allow' ], $result );
    }

    /**
     * Test that editing is allowed and mapped to 'edit_users' if both belong to the same blog
     * and the target is not a Super Admin.
     */
    public function test_map_meta_cap_allows_editing_when_conditions_are_met() {
        $manager = new CapabilityManager();
        $caps    = [ 'do_not_allow' ];
        $cap     = 'edit_user';
        $user_id = 2; // Subsite Admin
        $args    = [ 3 ]; // Target User
        $blog_id = 1;

        // Mock is_super_admin to return false for both
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $user_id )
            ->andReturn( false );
        WP_Mock::userFunction( 'is_super_admin' )
            ->with( $args[0] )
            ->andReturn( false );

        WP_Mock::userFunction( 'get_current_blog_id' )
            ->andReturn( $blog_id );

        // Mock both as members of the blog
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( $user_id, $blog_id )
            ->andReturn( true );
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( $args[0], $blog_id )
            ->andReturn( true );

        $result = $manager->map_meta_cap( $caps, $cap, $user_id, $args );

        $this->assertEquals( [ 'edit_users' ], $result );
    }

    /**
     * Test configuration edit capabilities based on user_can_edit.
     */
    public function test_enable_edit_any_user_configuration() {
        $manager = new CapabilityManager();
        $target_id = 3;

        // Mock current_user_can to return true
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'edit_user', $target_id )
            ->andReturn( true );

        $result = $manager->enable_edit_any_user_configuration( false, $target_id );
        $this->assertTrue( $result );
    }
}
