<?php
/**
 * Plugin Name:     Hari Easy Customise Helper
 * Plugin URI:      https://github.com/vanpariyar/hari-easy-customise-helper/
 * Description:     This is the simple customizer Helper for the Custom theme development
 * Author:          Ronak Vanpariya
 * Author URI:      https://vanpariyar.in
 * Text Domain:     hari-easy-customise-helper
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Hari_Easy_Customise_Helper
 */

// Your code starts here.



if ( !function_exists( 'add_action' ) ) {
	echo __('Hi there!  I\'m just a plugin, not much I can do when called directly.', 'useful-custom-rule-for-automatewoo');
	exit;
}

/* Plugin Constants */
if (!defined('Hari_Easy_Customise_Helper_URL')) {
    define('Hari_Easy_Customise_Helper_URL', plugin_dir_url(__FILE__));
}

if (!defined('Hari_Easy_Customise_Helper_PLUGIN_PATH')) {
    define('Hari_Easy_Customise_Helper_PLUGIN_PATH', plugin_dir_path(__FILE__));
}


require_once Hari_Easy_Customise_Helper_PLUGIN_PATH.'/includes/shortcodes/basic-shortcodes.php';
require_once Hari_Easy_Customise_Helper_PLUGIN_PATH.'/includes/functions/customisers.php';

class Hari_Easy_Customise_Helper {

    /**
	 * A reference to an instance of this class.
	 */
	private static $instance;

    public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new Hari_Easy_Customise_Helper();
		}

		return self::$instance;

	}

    public function __construct(){
        
        Hari_Easy_Customise_Helper_Shortcodes::get_instance();
        Hari_Easy_Customise_Helper_Functions::get_instance();
    }
         
}

Hari_Easy_Customise_Helper::get_instance();