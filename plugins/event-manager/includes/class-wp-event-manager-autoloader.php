<?php
/**
 * Autoloader class.
 */

defined('ABSPATH') || exit;

class WP_Event_Manager_Autoloader
{

    /**
     * Path to the includes directory.
     *
     * @var string
     */
    private $include_path = '';

    /**
     * constuctor.
     */
    public function __construct()
    {
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }

        spl_autoload_register([$this, 'autoload']);

        $this->include_path = untrailingslashit(plugin_dir_path(WP_EVENT_MANAGER_PLUGIN_FILE)) . '/includes/';
    }

    /**
     * Autoload a class.
     *
     * @param string $class Class name.
     */
    public function autoload($class)
    {
        $class = strtolower($class);

        if (0 !== strpos($class, 'wp_event_manager_')) {
            return;
        }

        $file = 'class-' . str_replace('_', '-', $class) . '.php';
        $path = $this->include_path;

        if (0 === strpos($class, 'wp_event_manager_rest_')) {
            $path .= 'api/';
        } elseif (0 === strpos($class, 'wp_event_manager_admin_')) {
            $path .= 'admin/';
        } elseif (0 === strpos($class, 'wp_event_manager_attendee_data_store')) { // Specific check for data store to ensure correct path
            $path .= 'data-stores/';
        } elseif (0 !== strpos($class, 'wp_event_manager_data_store') && false !== strpos($class, 'data_store')) {
            $path .= 'data-stores/';
        }

        if (empty($path) || !file_exists($path . $file)) {
            return;
        }

        include_once $path . $file;
    }
}

new WP_Event_Manager_Autoloader();
