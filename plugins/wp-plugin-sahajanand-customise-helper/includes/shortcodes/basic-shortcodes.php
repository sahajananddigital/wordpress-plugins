<?php
/*
 * @package         Hari_Easy_Customise_Helper
 */


class Hari_Easy_Customise_Helper_Shortcodes {

    /**
	 * A reference to an instance of this class.
	 */
	private static $instance;

    public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new Hari_Easy_Customise_Helper_Shortcodes();
		}

		return self::$instance;

	}

    public function __construct(){
        add_filter ('widget_text', 'do_shortcode');
        add_shortcode ('year', array( $this, 'year_shortcode'));
    }

    /**
     * @use [year] 
     * @displays :- 2021
     */
    public function year_shortcode () {
        $year = date_i18n ('Y');
        return $year;
    }
}