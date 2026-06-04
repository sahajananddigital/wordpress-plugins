<?php
/**
 * Main Plugin Class.
 */

defined('ABSPATH') || exit;

final class WP_Event_Manager
{

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.1.0';

    /**
     * The single instance of the class.
     *
     * @var WP_Event_Manager
     */
    protected static $_instance = null;

    /**
     * Main WP_Event_Manager Instance.
     *
     * Ensures only one instance of WP_Event_Manager is loaded or can be loaded.
     *
     * @return WP_Event_Manager - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define Constants.
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir(null, false);

        $this->define('WP_EVENT_MANAGER_ABSPATH', dirname(WP_EVENT_MANAGER_PLUGIN_FILE) . '/');
        $this->define('WP_EVENT_MANAGER_URL', plugin_dir_url(WP_EVENT_MANAGER_PLUGIN_FILE));
        $this->define('WP_EVENT_MANAGER_VERSION', $this->version);
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        // Abstracts
        include_once WP_EVENT_MANAGER_ABSPATH . 'includes/abstracts/abstract-wp-event-manager-data.php';

        include_once WP_EVENT_MANAGER_ABSPATH . 'includes/models/class-wp-event-manager-expense.php';
        include_once WP_EVENT_MANAGER_ABSPATH . 'includes/data-stores/class-wp-event-manager-expense-data-store.php';
        include_once WP_EVENT_MANAGER_ABSPATH . 'includes/class-wp-event-manager-autoloader.php';
        include_once WP_EVENT_MANAGER_ABSPATH . 'includes/class-wp-event-manager-install.php';


        if (is_admin()) {
            include_once WP_EVENT_MANAGER_ABSPATH . 'includes/admin/class-wp-event-manager-admin.php';
        }
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks()
    {
        register_activation_hook(WP_EVENT_MANAGER_PLUGIN_FILE, ['WP_Event_Manager_Install', 'install']);
        add_action('rest_api_init', [$this, 'init_rest_api']);
    }

    /**
     * Init REST API.
     */
    public function init_rest_api()
    {
        // Controller classes are autoloaded
        $controller = new WP_Event_Manager_REST_Controller();
        $controller->register_routes();
    }
}
