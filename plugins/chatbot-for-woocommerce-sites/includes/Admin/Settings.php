<?php

namespace WcChatbot\Admin;

/**
 * Handles the admin settings page for the plugin.
 */
class Settings {

	/**
	 * Add the plugin settings menu.
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Chatbot Settings', 'wc-chatbot' ),
			__( 'Chatbot', 'wc-chatbot' ),
			'manage_woocommerce',
			'wc-chatbot',
			array( $this, 'display_plugin_setup_page' )
		);
	}

	/**
	 * Register settings and sections.
	 */
	public function register_settings() {
		register_setting( 'wc_chatbot_options', 'wc_chatbot_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'no',
        ) );
		register_setting( 'wc_chatbot_options', 'wc_chatbot_greeting', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Hi there! How can I help you today?',
        ) );
		register_setting( 'wc_chatbot_options', 'wc_chatbot_color', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default' => '#007cba',
        ) );

		add_settings_section(
			'wc_chatbot_main_section',
			__( 'General Settings', 'wc-chatbot' ),
			array( $this, 'main_section_cb' ),
			'wc-chatbot'
		);

		add_settings_field(
			'wc_chatbot_enabled',
			__( 'Enable Chatbot', 'wc-chatbot' ),
			array( $this, 'enabled_cb' ),
			'wc-chatbot',
			'wc_chatbot_main_section'
		);

		add_settings_field(
			'wc_chatbot_greeting',
			__( 'Greeting Message', 'wc-chatbot' ),
			array( $this, 'greeting_cb' ),
			'wc-chatbot',
			'wc_chatbot_main_section'
		);

		add_settings_field(
			'wc_chatbot_color',
			__( 'Primary Color', 'wc-chatbot' ),
			array( $this, 'color_cb' ),
			'wc-chatbot',
			'wc_chatbot_main_section'
		);
	}

	/**
	 * Main section callback.
	 */
	public function main_section_cb() {
		echo '<p>' . esc_html__( 'Configure the general settings for the WooCommerce Chatbot.', 'wc-chatbot' ) . '</p>';
	}

	/**
	 * Enabled field callback.
	 */
	public function enabled_cb() {
		$enabled = get_option( 'wc_chatbot_enabled', 'no' );
		?>
		<select id="wc_chatbot_enabled" name="wc_chatbot_enabled">
			<option value="yes" <?php selected( 'yes', $enabled ); ?>><?php esc_html_e( 'Yes', 'wc-chatbot' ); ?></option>
			<option value="no" <?php selected( 'no', $enabled ); ?>><?php esc_html_e( 'No', 'wc-chatbot' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Greeting field callback.
	 */
	public function greeting_cb() {
		$greeting = get_option( 'wc_chatbot_greeting', 'Hi there! How can I help you today?' );
		?>
		<input type="text" id="wc_chatbot_greeting" name="wc_chatbot_greeting" value="<?php echo esc_attr( $greeting ); ?>" class="regular-text" />
		<?php
	}

	/**
	 * Color field callback.
	 */
	public function color_cb() {
		$color = get_option( 'wc_chatbot_color', '#007cba' );
		?>
		<input type="color" id="wc_chatbot_color" name="wc_chatbot_color" value="<?php echo esc_attr( $color ); ?>" />
		<?php
	}

	/**
	 * Render the setup page.
	 */
	public function display_plugin_setup_page() {
		// Check capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wc_chatbot_options' );
				do_settings_sections( 'wc-chatbot' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
