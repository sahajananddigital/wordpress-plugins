<?php
/**
 * Class PluginInitTest
 *
 * @package Gitsupport_Engine
 */

class PluginInitTest extends WP_UnitTestCase {

	/**
	 * Test that the plugin singleton instance is loaded.
	 */
	public function test_plugin_instance() {
		$instance = \GitSupport\Engine\Plugin::get_instance();
		$this->assertInstanceOf( \GitSupport\Engine\Plugin::class, $instance );
	}

	/**
	 * Test that the plugin hooks are registered.
	 */
	public function test_hooks_registered() {
		$this->assertNotFalse( has_action( 'admin_menu', array( \GitSupport\Engine\Plugin::get_instance(), 'register_admin_menu' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( \GitSupport\Engine\Plugin::get_instance(), 'enqueue_admin_assets' ) ) );
		$this->assertNotFalse( has_action( 'rest_api_init', array( \GitSupport\Engine\Plugin::get_instance(), 'register_rest_routes' ) ) );
	}
}
