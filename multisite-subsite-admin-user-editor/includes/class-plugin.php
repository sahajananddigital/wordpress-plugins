<?php
namespace MultisiteSubsiteAdminUserEditor;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 */
class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Capability Manager instance.
     *
     * @var CapabilityManager|null
     */
    private $capability_manager = null;

    /**
     * Security Gatekeeper instance.
     *
     * @var SecurityGatekeeper|null
     */
    private $security_gatekeeper = null;

    /**
     * Gets the singleton instance.
     *
     * @return Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {}

    /**
     * Boots the plugin services.
     *
     * @return void
     */
    public function boot() {
        if ( ! is_multisite() ) {
            add_action( 'admin_notices', [ $this, 'display_multisite_only_notice' ] );
            return;
        }

        $this->capability_manager = new CapabilityManager();
        $this->capability_manager->register_hooks();

        $this->security_gatekeeper = new SecurityGatekeeper();
        $this->security_gatekeeper->register_hooks();
    }

    /**
     * Display a warning notice if WordPress is not configured as Multisite.
     *
     * @return void
     */
    public function display_multisite_only_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'Multisite Subsite Admin User Editor requires a WordPress Multisite configuration to function.', 'multisite-subsite-admin-user-editor' ); ?></p>
        </div>
        <?php
    }

    /**
     * Get Capability Manager.
     *
     * @return CapabilityManager|null
     */
    public function get_capability_manager() {
        return $this->capability_manager;
    }

    /**
     * Get Security Gatekeeper.
     *
     * @return SecurityGatekeeper|null
     */
    public function get_security_gatekeeper() {
        return $this->security_gatekeeper;
    }
}
