# Agents Technical Reference: WooCommerce Chatbot Plugin

## 1. Introduction & Mission Statement
This document serves as the primary knowledge base for the **WooCommerce Chatbot** plugin. It is designed to provide a deep technical overview for AI agents and senior developers, detailing the architectural decisions, security mandates, and functional logic that govern the system.

The mission of this plugin is to provide a secure, lightweight, and human-like assistant for WooCommerce stores. Unlike many "black box" chatbot solutions that rely on heavy external APIs and monthly subscriptions, this plugin emphasizes **privacy, performance, and local processing**. It leverages the WordPress REST API and a custom-built, local Natural Language Understanding (NLU) engine to handle order tracking, product discovery, and customer support.

---

## 2. Project Architecture & Design Patterns

### 2.1. The Plugin Entry Point (`wc-chatbot.php`)
The plugin follows the standard WordPress singleton-like bootstrap pattern. It defines critical constants (`WC_CHATBOT_VERSION`, `WC_CHATBOT_PLUGIN_DIR`) and implements a robust autoloader. 

**Key Architectural Decision:** Dependency Check. 
Before the plugin initializes, it executes `wc_chatbot_check_dependencies()` to verify that WooCommerce is active. This prevents fatal errors and provides a graceful admin notice if the core dependency is missing.

### 2.2. Core Coordination (`includes/Plugin.php`)
The `Plugin` class acts as the central orchestrator. It is responsible for:
- **Module Loading:** Initializing `Settings`, `Rest_Endpoints`, and `Frontend\Scripts`.
- **Hook Registration:** Separating admin hooks (menu, settings) from public hooks (scripts, footer rendering, API routes).
- **PHP 8.3 Compatibility:** All properties are explicitly declared to avoid dynamic property deprecation warnings.

### 2.3. REST API Architecture (`includes/Api/Rest_Endpoints.php`)
A critical design choice was to avoid the legacy `admin-ajax.php` in favor of the **Custom WordPress REST API**.
- **Namespace:** `wc-chatbot/v1`
- **Security:** Every request is protected by a strict **Nonce Verification** in the `permissions_check` method. This prevents CSRF attacks on the chatbot endpoint.
- **Statelessness:** The API remains stateless, but the conversation history and multi-step state are managed via a `context` object passed between the client and server.

---

## 3. Directory Structure Breakdown

```text
/chatbot-for-woocommerce-sites/
├── assets/
│   ├── css/
│   │   └── chatbot.css (Clean, modern UI with CSS variables for dynamic coloring)
│   └── js/
│       └── chatbot.js (Asynchronous fetch-based chat handler, markdown parser)
├── includes/
│   ├── Admin/
│   │   └── Settings.php (WordPress Settings API implementation for plugin config)
│   ├── Api/
│   │   └── Rest_Endpoints.php (Custom REST route registration and handler)
│   ├── Bot/
│   │   ├── Logic.php (Main intent-to-action routing engine)
│   │   └── IntentClassifier.php (Local NLU and sentiment analysis engine)
│   ├── Frontend/
│   │   └── Scripts.php (Asset management and localized data injection)
│   └── Plugin.php (Main orchestration class)
├── tests/
│   ├── bootstrap.php (PHPUnit environment setup)
│   ├── test-logic.php (Functional tests for the bot logic)
│   ├── test-rest-endpoints.php (Security and API response tests)
│   ├── test-intent-classifier.php (NLU accuracy tests)
│   └── playground-blueprint.json (WP Playground CLI configuration)
├── wc-chatbot.php (Plugin entry point)
├── phpunit.xml (Test suite configuration)
└── package.json (NPM scripts for Playground CLI)
```

---

## 4. The Intelligence Layer (NLU & Logic)

### 4.1. Local Intent Classification (`IntentClassifier.php`)
To eliminate the need for paid AI APIs, we implemented a **Weighted Keyword Scoring** system.
- **Intent Map:** A dictionary of intents (e.g., `order_lookup`, `product_search`, `small_talk`) with associated keywords and weights.
- **Scoring Algorithm:** The `classify()` method tokenizes the input, calculates a cumulative score for each intent, and returns the highest-scoring intent that exceeds a predefined threshold.
- **Sentiment Analysis:** A secondary heuristic scans for "negative signal" words (e.g., "broken", "angry", "problem"). This allows the bot to pivot its tone from "helpful assistant" to "empathetic support" when the customer is frustrated.

### 4.2. Functional Routing (`Bot/Logic.php`)
The `Logic` class interprets the detected intent and executes the corresponding WooCommerce action.
- **Product Suggestions:** Uses `WP_Query` with a customized stop-word filter to extract search terms from natural sentences.
- **Order Tracking State Machine:**
    1.  **Intent Detected:** Start the "awaiting_order_id" state.
    2.  **ID Provided:** Validate integer, store in context, move to "awaiting_order_email".
    3.  **Security Check:** Verify the provided email matches the WooCommerce order's billing email before disclosing status.
    4.  **Tracking Data:** Integration with WooCommerce metadata to provide real-time status and tracking numbers.

### 4.3. Humanizing the Experience
To avoid the "robotic" feel, the `Logic` class uses a `randomize()` helper. This ensures that even for common greetings or fallbacks, the bot rotates through multiple variations (e.g., "Hi there!", "Hello! How can I help?", "Hey! Ready to find some products?").

---

## 5. Security & Engineering Mandates

### 5.1. Data Integrity & Sanitization
The plugin adheres strictly to **WordPress Coding Standards (WPCS)**:
- **Input:** All API inputs are sanitized via `sanitize_text_field()` and `sanitize_email()`.
- **Output:** Every piece of dynamic data is escaped at the point of output (`esc_html`, `esc_attr`, `wp_kses_post`).
- **Database:** No direct SQL queries. All data access is performed through WooCommerce CRUD methods (`wc_get_order()`, `wc_get_product()`) to ensure compatibility with various database optimizers and caching layers.

### 5.2. Nonce Security
The frontend script (`chatbot.js`) receives a security nonce via `wp_localize_script`. This nonce is included in the `X-WP-Nonce` header of every POST request to the custom REST API. The server verifies this nonce before processing any intent, ensuring that chat messages can only be sent from the legitimate store frontend.

---

## 6. Testing & Validation Strategy

### 6.1. Backend Unit Testing
The plugin features a comprehensive PHPUnit suite located in `/tests`.
- **Logic Tests:** Verifies the state machine transitions and fallback behaviors.
- **API Tests:** Ensures unauthorized requests are blocked (403) and malformed messages are handled gracefully (400).
- **Classifier Tests:** Ensures the NLU correctly identifies intents across varied phrasing.

### 6.2. Sandbox Testing (WordPress Playground)
We use the **WordPress Playground CLI (`@wp-playground/cli`)** for rapid environment spinning.
- **Blueprint Logic:** The `playground-blueprint.json` file automates the installation of WooCommerce, handles onboarding skipping via a `runPHP` step, and dynamically activates the chatbot plugin regardless of the mount path.
- **CI/CD Ready:** The `package.json` scripts (`npm run playground:test`) allow for automated visual and functional regression testing in a clean, throwaway WordPress instance.

---

## 7. Operational Guidelines for Future Agents
When modifying this codebase, agents MUST:
1.  **Maintain Type Safety:** Explicitly declare properties and return types.
2.  **Validate on PHP 8.3:** Ensure no deprecation notices are triggered by dynamic properties or legacy syntax.
3.  **Respect the State Machine:** Do not break the context object; any new conversational flows must be integrated into the `handle_state` logic in `Logic.php`.
4.  **Test the NLU:** If adding keywords to `IntentClassifier`, run the existing unit tests to ensure no regressions in intent detection accuracy.
5.  **Sanitize Early, Escape Late:** Follow the WordPress security mantra for every new line of code.

---
*End of Technical Reference*
