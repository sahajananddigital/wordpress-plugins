# WooCommerce Chatbot Plugin

## 🚀 Instant Live Demo
Test the chatbot features directly in your browser with pre-configured settings:

[![Try with WordPress Playground](https://img.shields.io/badge/Try_with-WordPress_Playground-21759b?logo=wordpress&logoColor=white)](https://playground.wordpress.net/#{"landingPage":"/wp-admin/plugins.php","steps":[{"step":"login","username":"admin","password":"password"},{"step":"installPlugin","pluginZipFile":{"resource":"wordpress.org/plugins","slug":"woocommerce"}},{"step":"runPHP","code":"<?php%20include%20'wordpress/wp-load.php';%20delete_transient('%20_wc_activation_redirect'%20);"},{"step":"installPlugin","pluginZipFile":{"resource":"url","url":"https://github.com/sahajananddigital/chatbot-for-woocommerce-sites/archive/refs/heads/main.zip"}},{"step":"setSiteOptions","options":{"wc_chatbot_enabled":"yes","wc_chatbot_greeting":"Welcome%20to%20the%20Playground!%20How%20can%20I%20help%20you%20today?","wc_chatbot_color":"#2271b1"}}]})

A lightweight, secure, and privacy-focused chatbot for WooCommerce stores. This plugin provides a human-like assistant that helps customers track orders and discover products without relying on expensive or privacy-invasive external AI APIs.

## 🚀 Features

- **Secure Order Tracking:** Customers can check their order status by providing their Order ID and Billing Email (strictly verified).
- **Product Suggestions:** An intelligent search engine that understands natural language queries to recommend products.
- **Local NLU (Natural Language Understanding):** A custom-built, keyword-weighted intent classifier that runs entirely on your server.
- **Sentiment Awareness:** The bot detects frustrated customers and automatically adjusts its tone to be more empathetic.
- **Humanized Responses:** Randomized conversational variations to prevent the "robotic" feel.
- **Admin Customization:** Easy-to-use settings for primary colors, greeting messages, and enabling/disabling the bot.
- **REST API Powered:** Uses the modern WordPress REST API for fast, asynchronous communication.

## 🛠 Requirements

- **WordPress:** 5.8 or higher
- **WooCommerce:** 6.0 or higher
- **PHP:** 7.4 to 8.3+ (Fully compatible with PHP 8.3)

## 📥 Installation

1.  Upload the `chatbot-for-woocommerce-sites` folder to your `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **WooCommerce > Chatbot** to configure your settings.
4.  Ensure the chatbot is set to "Enabled".

## 🧪 Developer & Testing Tools

This project is built with a "test-first" mentality and includes modern development tools:

### WordPress Playground (Sandbox Testing)
Test the plugin in a zero-config, temporary WordPress environment:
```bash
# Basic playground
npm run playground

# Playground with WooCommerce pre-installed and configured
npm run playground:test
```

### Unit Testing (PHPUnit)
We maintain high code quality with a comprehensive test suite:
```bash
# Run backend and NLU tests
./vendor/bin/phpunit
```

## 📖 Technical Documentation
For a deep dive into the architecture, security protocols, and NLU logic, please refer to the [agents.md](./agents.md) file.

## 📜 License
GPL-2.0+
