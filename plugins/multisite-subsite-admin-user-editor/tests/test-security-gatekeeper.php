<?php
namespace MultisiteSubsiteAdminUserEditor\Tests;

use WP_Mock\Tools\TestCase;
use WP_Mock;
use MultisiteSubsiteAdminUserEditor\SecurityGatekeeper;

/**
 * Class TestSecurityGatekeeper
 *
 * Tests the security gating logic of SecurityGatekeeper.
 */
class TestSecurityGatekeeper extends TestCase {

    /**
     * Set up tests.
     */
    public function setUp(): void {
        WP_Mock::setUp();
        // Load class under test
        require_once dirname( __DIR__ ) . '/includes/class-security-gatekeeper.php';
    }

    /**
     * Tear down tests.
     */
    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    /**
     * Helper to mock wp_die to throw an exception so we can capture it in PHPUnit.
     */
    private function mock_wp_die() {
        WP_Mock::userFunction( 'wp_die' )
            ->andReturnUsing( function( $message ) {
                throw new \Exception( $message );
            } );
    }

    /**
     * Test that Super Admins are bypass-allowed and no exceptions are thrown.
     */
    public function test_check_edit_permission_allows_super_admins() {
        $gatekeeper = new SecurityGatekeeper();
        $this->mock_wp_die();

        // Mock current user as Super Admin (ID 1)
        $user = new \stdClass();
        $user->ID = 1;
        WP_Mock::userFunction( 'wp_get_current_user' )
            ->andReturn( $user );

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 1 )
            ->andReturn( true );

        // No exception should be thrown
        $gatekeeper->check_edit_permission();
        $this->assertTrue( true );
    }

    /**
     * Test that if editing self, it is allowed without checks.
     */
    public function test_check_edit_permission_allows_editing_self() {
        $gatekeeper = new SecurityGatekeeper();
        $this->mock_wp_die();

        $user = new \stdClass();
        $user->ID = 2; // Subsite Admin
        WP_Mock::userFunction( 'wp_get_current_user' )
            ->andReturn( $user );

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 2 )
            ->andReturn( false );

        // Target user is the same as the editor
        $_REQUEST['user_id'] = 2;

        $gatekeeper->check_edit_permission();
        $this->assertTrue( true );
        
        unset( $_REQUEST['user_id'] );
    }

    /**
     * Test that subsite admins editing a Super Admin triggers wp_die.
     */
    public function test_check_edit_permission_dies_when_editing_super_admin() {
        $gatekeeper = new SecurityGatekeeper();
        $this->mock_wp_die();

        $user = new \stdClass();
        $user->ID = 2; // Subsite Admin
        WP_Mock::userFunction( 'wp_get_current_user' )
            ->andReturn( $user );

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 2 )
            ->andReturn( false );

        // Target user is Super Admin (ID 1)
        $_REQUEST['user_id'] = 1;

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 1 )
            ->andReturn( true );

        WP_Mock::userFunction( 'get_current_blog_id' )
            ->andReturn( 1 );

        // Mock translation helper functions used in wp_die
        WP_Mock::userFunction( 'esc_html__' )
            ->andReturnFirstArg();

        $this->expectException( \Exception::class );
        $this->expectExceptionMessage( 'Security check: You do not have permission to edit a Super Admin user.' );

        try {
            $gatekeeper->check_edit_permission();
        } finally {
            unset( $_REQUEST['user_id'] );
        }
    }

    /**
     * Test that subsite admins editing a user from another subsite triggers wp_die.
     */
    public function test_check_edit_permission_dies_when_user_from_different_subsite() {
        $gatekeeper = new SecurityGatekeeper();
        $this->mock_wp_die();

        $user = new \stdClass();
        $user->ID = 2; // Subsite Admin
        WP_Mock::userFunction( 'wp_get_current_user' )
            ->andReturn( $user );

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 2 )
            ->andReturn( false );

        $_REQUEST['user_id'] = 3; // Target User

        WP_Mock::userFunction( 'is_super_admin' )
            ->with( 3 )
            ->andReturn( false );

        $blog_id = 1;
        WP_Mock::userFunction( 'get_current_blog_id' )
            ->andReturn( $blog_id );

        // Editor is member, target is NOT member
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( 2, $blog_id )
            ->andReturn( true );
        WP_Mock::userFunction( 'is_user_member_of_blog' )
            ->with( 3, $blog_id )
            ->andReturn( false );

        WP_Mock::userFunction( 'esc_html__' )
            ->andReturnFirstArg();

        $this->expectException( \Exception::class );
        $this->expectExceptionMessage( 'Security check: You do not have permission to edit this user as they belong to a different subsite.' );

        try {
            $gatekeeper->check_edit_permission();
        } finally {
            unset( $_REQUEST['user_id'] );
        }
    }
}
