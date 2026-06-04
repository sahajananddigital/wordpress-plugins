<?php

namespace WcChatbot\Frontend;

/**
 * Handles enqueueing frontend scripts and styles.
 */
class Scripts {

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'wc-chatbot-style',
			WC_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
			array(),
			WC_CHATBOT_VERSION
		);

		wp_enqueue_script(
			'wc-chatbot-script',
			WC_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
			array(),
			WC_CHATBOT_VERSION,
			true
		);

		// Localize script for API URL and initial data.
		wp_localize_script(
			'wc-chatbot-script',
			'wcChatbotData',
			array(
				'apiUrl'   => esc_url_raw( rest_url( 'wc-chatbot/v1/message' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'greeting' => get_option( 'wc_chatbot_greeting', 'Hi there! How can I help you today?' ),
				'color'    => get_option( 'wc_chatbot_color', '#007cba' ),
			)
		);
        
        // Output inline style for dynamic color
        $color = get_option( 'wc_chatbot_color', '#007cba' );
        $custom_css = "
            #wc-chatbot-container .chatbot-header { background-color: {$color}; }
            #wc-chatbot-container .chatbot-toggle { background-color: {$color}; }
            #wc-chatbot-container .chatbot-message.bot { border-left: 3px solid {$color}; }
        ";
        wp_add_inline_style( 'wc-chatbot-style', $custom_css );
	}

	/**
	 * Render the chatbot HTML shell in the footer.
	 */
	public function render_chatbot_html() {
		?>
		<div id="wc-chatbot-container" class="wc-chatbot-hidden">
			<div class="chatbot-window">
				<div class="chatbot-header">
					<span><?php esc_html_e( 'Chat Support', 'wc-chatbot' ); ?></span>
					<button id="wc-chatbot-close">&times;</button>
				</div>
				<div id="wc-chatbot-messages" class="chatbot-messages">
					<!-- Messages will be injected here -->
				</div>
				<div class="chatbot-input-area">
					<input type="text" id="wc-chatbot-input" placeholder="<?php esc_attr_e( 'Type your message...', 'wc-chatbot' ); ?>" autocomplete="off" />
					<button id="wc-chatbot-send"><?php esc_html_e( 'Send', 'wc-chatbot' ); ?></button>
				</div>
			</div>
			<button id="wc-chatbot-toggle" class="chatbot-toggle">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="currentColor"/>
				</svg>
			</button>
		</div>
		<?php
	}
}
