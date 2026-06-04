<?php
/*
Plugin Name: Sensie Google Login with rtCamp
Plugin URI: https://github.com/sahajananddigital/wordpress-plugins/sensie-google-login-with-rtcamp-plugin
Requires at least: 5.2
Tested up to: 5.8
Requires PHP: 7.0
Requires WordPress: 5.2
Requires Plugins: sensei-lms, login-with-google
Description: A plugin to customize Google login with rtCamp for Sensie.
Version: 1.0.0
Author: Sahajanand Digital
Author URI: https://sahajananddigital.in
License: GPL2
*/
?>

<?php
// Add custom code here
add_action('sensei_login_form_inside_after', 'sensie_google_login_with_rtcamp');
function sensie_google_login_with_rtcamp() {
    echo do_shortcode('[google_login button_text="Google Login" force_display="yes" /]');
}
