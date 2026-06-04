<?php

namespace WcChatbot\Bot;

/**
 * Lightweight Intent Classifier for the chatbot.
 * 
 * Uses keyword scoring and weighted matching to detect user intent without external APIs.
 */
class IntentClassifier {

    /**
     * Intent mapping with keywords and their weights.
     * Higher weight means stronger signal.
     */
    private $intent_map = array(
        'order_lookup' => array(
            'keywords' => array(
                'order' => 5,
                'track' => 5,
                'status' => 5,
                'where' => 2,
                'package' => 3,
                'delivered' => 2,
                'shipping' => 2,
                'received' => 2,
                'id' => 1,
            ),
            'threshold' => 5,
        ),
        'product_search' => array(
            'keywords' => array(
                'suggest' => 5,
                'product' => 5,
                'recommend' => 5,
                'find' => 3,
                'looking' => 2,
                'buy' => 2,
                'items' => 1,
                'shop' => 2,
                'search' => 3,
            ),
            'threshold' => 5,
        ),
        'greeting' => array(
            'keywords' => array(
                'hi' => 5,
                'hello' => 5,
                'hey' => 5,
                'morning' => 2,
                'evening' => 2,
                'greetings' => 5,
                'yo' => 3,
            ),
            'threshold' => 3,
        ),
        'small_talk_wellbeing' => array(
            'keywords' => array(
                'how' => 1,
                'are' => 1,
                'you' => 1,
                'doing' => 2,
                'going' => 1,
                'today' => 1,
                'well' => 2,
                'good' => 1,
            ),
            'threshold' => 3, // Requires "how are you" or similar
            'required' => array('how', 'you'),
        ),
        'small_talk_identity' => array(
            'keywords' => array(
                'who' => 2,
                'what' => 1,
                'are' => 1,
                'you' => 1,
                'name' => 3,
                'human' => 5,
                'robot' => 5,
                'bot' => 5,
            ),
            'threshold' => 4,
            'required' => array('you'),
        ),
        'gratitude' => array(
            'keywords' => array(
                'thanks' => 5,
                'thank' => 5,
                'you' => 1,
                'awesome' => 3,
                'great' => 2,
                'helped' => 3,
                'helpful' => 3,
            ),
            'threshold' => 5,
        ),
        'closing' => array(
            'keywords' => array(
                'bye' => 5,
                'goodbye' => 5,
                'see' => 1,
                'later' => 2,
                'stop' => 2,
                'quit' => 3,
            ),
            'threshold' => 5,
        ),
    );

    /**
     * Classify the user message into one of the known intents.
     *
     * @param string $message The user's input.
     * @return string|null The detected intent name or null if no intent matches.
     */
    public function classify( $message ) {
        $message = strtolower( trim( $message ) );
        $words = preg_split( '/\s+/', $message );
        $scores = array();

        foreach ( $this->intent_map as $intent => $config ) {
            $score = 0;
            $has_required = empty( $config['required'] ) ? true : false;
            $matched_required = array();

            foreach ( $config['keywords'] as $keyword => $weight ) {
                if ( strpos( $message, $keyword ) !== false ) {
                    $score += $weight;
                    if ( ! empty( $config['required'] ) && in_array( $keyword, $config['required'] ) ) {
                        $matched_required[] = $keyword;
                    }
                }
            }

            if ( ! empty( $config['required'] ) ) {
                $has_required = count( array_intersect( $config['required'], $matched_required ) ) === count( $config['required'] );
            }

            if ( $has_required && $score >= $config['threshold'] ) {
                $scores[ $intent ] = $score;
            }
        }

        if ( empty( $scores ) ) {
            return null;
        }

        arsort( $scores );
        return array_key_first( $scores );
    }

    /**
     * Detect sentiment of the message (simple implementation).
     *
     * @param string $message The user's input.
     * @return string 'positive', 'negative', or 'neutral'.
     */
    public function detect_sentiment( $message ) {
        $message = strtolower( trim( $message ) );
        $positive_words = array( 'good', 'great', 'awesome', 'happy', 'love', 'perfect', 'thanks', 'wonderful' );
        $negative_words = array( 'bad', 'wrong', 'broken', 'angry', 'terrible', 'failed', 'problem', 'unhappy', 'not' );

        $score = 0;
        foreach ( $positive_words as $word ) {
            if ( strpos( $message, $word ) !== false ) $score++;
        }
        foreach ( $negative_words as $word ) {
            if ( strpos( $message, $word ) !== false ) $score--;
        }

        if ( $score > 0 ) return 'positive';
        if ( $score < 0 ) return 'negative';
        return 'neutral';
    }
}
