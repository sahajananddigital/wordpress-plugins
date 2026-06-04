<?php
/*
 * @package Hari_Easy_Customise_Helper_Functions
 */

class Hari_Easy_Customise_Helper_Functions{

    /**
	 * A reference to an instance of this class.
	 */
	private static $instance;

    public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new Hari_Easy_Customise_Helper_Functions();
		}

		return self::$instance;

	}

    public function __construct(){
        
        add_action( 'after_theme_setup', array($this, 'custom_new_menu'));
        // Remove the REST API HTML entities.
        $post_type = "post";
        add_filter( 'rest_prepare_' . $post_type, array( $this, 'decode_rest_api_title' ), 20, 3 );

    }
      
    public function custom_new_menu() {
        $menus = array(
            'hari-footer-menu' => __( 'Footer Menu' ),
            'hari-main-menu' => __( 'Extra Menu' )
        );
        // $menus = apply_filters('hari_customiser_register_nav_menu', $menus);
        register_nav_menus($menus);
    }

  
    /**
     * Decode HTML entities from the website title.
     *
     * @param string $response Actual response.
     * @param Object    $request Actual request.
     * @param Object    $post actual Post object.
     *
     * @return $response return text with decoded entirties.
     */
    public function decode_rest_api_title( $response, $post, $request ) {
        if ( isset( $post ) ) {
            $decoded_title = html_entity_decode( $response->data['title']['rendered'] );
            $response->data['title']['rendered'] = $decoded_title;
        }
        return $response;
    }

}