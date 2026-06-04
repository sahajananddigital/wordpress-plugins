<?php

namespace WcChatbot\Bot;

use WP_Query;

/**
 * Handles the logic for processing messages and returning responses.
 */
class Logic {

    /**
     * @var IntentClassifier
     */
    private $classifier;

    public function __construct() {
        $this->classifier = new IntentClassifier();
    }

	/**
	 * Process the incoming message and determine intent.
	 *
	 * @param string $message The user's message.
	 * @param array  $context The conversation context.
	 * @return array The response data containing text and updated context.
	 */
	public function process_message( $message, $context = array() ) {
		$message_lower = strtolower( trim( $message ) );
        
        // Handle contextual state (e.g., waiting for order ID, then waiting for email).
        if ( isset( $context['state'] ) ) {
            return $this->handle_state( $message_lower, $context );
        }

		// Detect intent using the classifier.
		$intent = $this->classifier->classify( $message );
        $sentiment = $this->classifier->detect_sentiment( $message );

        switch ( $intent ) {
            case 'order_lookup':
                return $this->start_order_lookup( $sentiment );
            case 'product_search':
                return $this->start_product_search( $message_lower, $sentiment );
            case 'greeting':
                return $this->get_greeting();
            case 'small_talk_wellbeing':
            case 'small_talk_identity':
            case 'gratitude':
            case 'closing':
                return $this->handle_small_talk( $intent, $sentiment );
            default:
                return $this->get_fallback( $sentiment );
        }
	}

    /**
     * Handle multi-step states like order lookup.
     */
    private function handle_state( $message, $context ) {
        if ( 'awaiting_order_id' === $context['state'] ) {
            $order_id = absint( preg_replace( '/[^0-9]/', '', $message ) );
            if ( ! $order_id ) {
                return array(
                    'text' => $this->randomize( array(
                        __( 'I need a numerical Order ID to proceed. Could you check that again?', 'wc-chatbot' ),
                        __( "That doesn't look like a valid Order ID. Please enter numbers only.", 'wc-chatbot' ),
                    ) ),
                    'context' => $context
                );
            }
            $context['state'] = 'awaiting_order_email';
            $context['order_id'] = $order_id;
            return array(
                'text' => $this->randomize( array(
                    __( "Got it! Now, for security, what's the billing email address for this order?", 'wc-chatbot' ),
                    __( "Thanks! Please enter the billing email associated with this order.", 'wc-chatbot' ),
                ) ),
                'context' => $context
            );
        } elseif ( 'awaiting_order_email' === $context['state'] ) {
            $email = sanitize_email( $message );
            if ( ! is_email( $email ) ) {
                return array(
                    'text' => __( "That doesn't look like a valid email. Could you double-check it?", 'wc-chatbot' ),
                    'context' => $context
                );
            }
            return $this->perform_order_lookup( $context['order_id'], $email );
        }

        return $this->get_fallback();
    }

	/**
	 * Get the greeting message from settings with variations.
	 */
	private function get_greeting() {
		$greeting = get_option( 'wc_chatbot_greeting', 'Hi there! How can I help you today?' );
        $variations = array(
            $greeting,
            __( "Hello! I'm here to help with your orders and products. What's on your mind?", 'wc-chatbot' ),
            __( "Hey there! Need help finding something or checking an order?", 'wc-chatbot' ),
        );

		return array(
			'text'    => $this->randomize( $variations ),
			'context' => array(),
		);
	}

    /**
     * Handle small talk intents.
     */
    private function handle_small_talk( $intent, $sentiment ) {
        $responses = array();
        
        switch ( $intent ) {
            case 'small_talk_wellbeing':
                $responses = array(
                    __( "I'm doing great, thank you for asking! How can I help you today?", 'wc-chatbot' ),
                    __( "Systems are green and I'm ready to help! How are you doing?", 'wc-chatbot' ),
                    __( "I'm having a wonderful day helping customers like you!", 'wc-chatbot' ),
                );
                break;
            case 'small_talk_identity':
                $responses = array(
                    __( "I'm your friendly store assistant. I can help you track orders and find products!", 'wc-chatbot' ),
                    __( "Think of me as your digital guide to this shop. I'm a specialized chatbot here for you.", 'wc-chatbot' ),
                );
                break;
            case 'gratitude':
                $responses = array(
                    __( "You're very welcome! Let me know if you need anything else.", 'wc-chatbot' ),
                    __( "Happy to help! Is there anything else I can do for you?", 'wc-chatbot' ),
                    __( "Anytime! Have a great day.", 'wc-chatbot' ),
                );
                break;
            case 'closing':
                $responses = array(
                    __( "Goodbye! Hope to see you again soon.", 'wc-chatbot' ),
                    __( "Bye! Have a wonderful day!", 'wc-chatbot' ),
                    __( "See you later! Feel free to come back if you have more questions.", 'wc-chatbot' ),
                );
                break;
        }

        return array(
            'text'    => $this->randomize( $responses ),
            'context' => array(),
        );
    }

	/**
	 * Get fallback message with empathy.
	 */
	private function get_fallback( $sentiment = 'neutral' ) {
        if ( 'negative' === $sentiment ) {
            return array(
                'text' => __( "I'm sorry I'm not understanding correctly. I'm still learning! You might want to try asking about 'order status' or 'product suggestions', or contact our human support team directly.", 'wc-chatbot' ),
                'context' => array(),
            );
        }

		return array(
			'text'    => __( "I'm not quite sure I follow. I can help you track orders or suggest products—just ask!", 'wc-chatbot' ),
			'context' => array(),
		);
	}

	/**
	 * Initiate the order lookup process.
	 */
	private function start_order_lookup( $sentiment = 'neutral' ) {
        $prefix = ( 'negative' === $sentiment ) ? __( "I understand you're looking for an update. ", 'wc-chatbot' ) : "";
		return array(
			'text'    => $prefix . __( 'I can help with that. Please enter your Order ID.', 'wc-chatbot' ),
			'context' => array( 'state' => 'awaiting_order_id' ),
		);
	}

    /**
     * Perform the actual WooCommerce order lookup.
     */
    private function perform_order_lookup( $order_id, $email ) {
        $order = wc_get_order( $order_id );

        if ( ! $order || $order->get_billing_email() !== $email ) {
            return array(
                'text'    => __( "I'm sorry, but I couldn't find an order with that ID and email. Could you please check your details and try again?", 'wc-chatbot' ),
                'context' => array(), // Reset state
            );
        }

        $status = wc_get_order_status_name( $order->get_status() );
        $text = sprintf( __( 'I found it! Your order #%1$d is currently: **%2$s**.', 'wc-chatbot' ), $order_id, $status );

        // Basic support for tracking info
        $tracking_number = $order->get_meta( '_tracking_number' );
        if ( $tracking_number ) {
            $text .= "\n" . sprintf( __( 'Good news! Your tracking number is: %s', 'wc-chatbot' ), $tracking_number );
        }

        return array(
            'text'    => $text,
            'context' => array(), // Reset state
        );
    }

	/**
	 * Simple product search based on keywords in message.
	 */
	private function start_product_search( $message, $sentiment = 'neutral' ) {
        $stop_words = array('suggest', 'product', 'products', 'recommend', 'me', 'a', 'some', 'for', 'like', 'find', 'looking', 'buy', 'search');
        $words = explode(' ', $message);
        $search_terms = array_diff($words, $stop_words);
        $search_query = implode(' ', $search_terms);

        if ( empty( trim( $search_query ) ) ) {
            return array(
                'text'    => __( "I'd love to suggest some products! What kind of items are you looking for today?", 'wc-chatbot' ),
                'context' => array(),
            );
        }

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 3,
            'post_status'    => 'publish',
            's'              => $search_query,
        );

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return array(
                'text'    => sprintf( __( "I searched high and low, but couldn't find products matching '%s'. Try another keyword?", 'wc-chatbot' ), esc_html( $search_query ) ),
                'context' => array(),
            );
        }

        $products_text = sprintf( __( "I found some great options for '%s':\n", 'wc-chatbot' ), esc_html( $search_query ) );
        
        while ( $query->have_posts() ) {
            $query->the_post();
            $product = wc_get_product( get_the_ID() );
            $products_text .= sprintf( "- [%s](%s) - %s\n", $product->get_name(), $product->get_permalink(), wp_strip_all_tags( wc_price( $product->get_price() ) ) );
        }
        
        wp_reset_postdata();

		return array(
			'text'    => $products_text,
			'context' => array(),
		);
	}

    /**
     * Helper to pick a random item from an array.
     */
    private function randomize( $array ) {
        return $array[ array_rand( $array ) ];
    }
}
