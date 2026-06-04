<?php
/**
 * Class Test_IntentClassifier
 *
 * @package Wc_Chatbot
 */

namespace WcChatbot\Tests;

use WP_UnitTestCase;
use WcChatbot\Bot\IntentClassifier;

/**
 * Intent Classifier test case.
 */
class Test_IntentClassifier extends WP_UnitTestCase {

	/**
	 * Test classification of common phrases.
	 */
	public function test_classification() {
		$classifier = new IntentClassifier();

		// Basic intents
		$this->assertEquals( 'greeting', $classifier->classify( 'Hello there!' ) );
		$this->assertEquals( 'order_lookup', $classifier->classify( 'Where is my order?' ) );
		$this->assertEquals( 'product_search', $classifier->classify( 'Can you suggest some shoes?' ) );

		// Small talk
		$this->assertEquals( 'small_talk_wellbeing', $classifier->classify( 'How are you doing today?' ) );
		$this->assertEquals( 'small_talk_identity', $classifier->classify( 'Who are you?' ) );
		
		// Gratitude
		$this->assertEquals( 'gratitude', $classifier->classify( 'Thank you so much!' ) );

		// Negative or unknown
		$this->assertNull( $classifier->classify( 'xyzabc' ) );
	}

	/**
	 * Test sentiment detection.
	 */
	public function test_sentiment() {
		$classifier = new IntentClassifier();

		$this->assertEquals( 'positive', $classifier->detect_sentiment( 'This is awesome thanks!' ) );
		$this->assertEquals( 'negative', $classifier->detect_sentiment( 'I have a big problem and I am angry' ) );
		$this->assertEquals( 'neutral', $classifier->detect_sentiment( 'I want to see my order' ) );
	}
}
