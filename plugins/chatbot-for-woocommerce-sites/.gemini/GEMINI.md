# WooCommerce Chatbot Plugin - Context and Agent Knowledge

## Architecture & Coding Standards
- **Standard:** Strict adherence to WordPress Coding Standards (WPCS).
- **Security:** All user input must be sanitized (`sanitize_text_field`, `sanitize_email`, etc.). All output must be escaped (`esc_html`, `esc_attr`, `wp_kses_post`, etc.).
- **Data Access:** Direct database queries are discouraged. Always use WooCommerce CRUD operations (`wc_get_order`, `wc_get_products`, etc.) and WordPress core functions.
- **API:** The plugin uses Custom WordPress REST API endpoints (`/wp-json/wc-chatbot/v1/...`) instead of `admin-ajax.php`.
- **Assets:** Frontend scripts must be localized using `wp_localize_script` to pass API URLs and security nonces.
- **PHP Compatibility:** Fully compatible with PHP 8.3. Explicit property declarations must be maintained in all classes to prevent deprecation warnings.

## Core Features Implemented/Planned
- **Admin Settings:** Options to enable/disable chatbot and configure primary color/greeting.
- **Product Suggestions:** Queries WooCommerce products based on keywords using a customized stop-word filter.
- **Order Status:** Looks up order status and tracking details using Order ID and Billing Email for security.
- **Local NLU (Intelligence):** Powered by `IntentClassifier.php`, using weighted keyword scoring for intent detection. Supports greetings, small talk, gratitude, and closings.
- **Sentiment Awareness:** Detects frustrated users and adapts response tone automatically.
- **Conversational Variety:** Uses randomized response pools to ensure the bot feels human and varied.

## Testing Strategy
- **Unit Testing:** Comprehensive PHPUnit suite in `/tests` for backend logic, NLU accuracy, API responses, and security implementations (nonce, email validation).
- **Sandbox Testing:** Integrated with **WordPress Playground CLI**. Use `npm run playground:test` to launch a pre-configured environment with WooCommerce and the chatbot ready for testing via `tests/playground-blueprint.json`.

## Master Technical Reference
- See **`agents.md`** for an exhaustive deep-dive into the system architecture, security mandates, and developer guidelines.
