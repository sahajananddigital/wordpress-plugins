const { defineConfig } = require( '@playwright/test' );
let wordpressConfig;
try {
	wordpressConfig = require( '@wordpress/scripts/config/playwright.config.js' );
} catch ( err ) {
	wordpressConfig = {};
}

module.exports = defineConfig( {
	...wordpressConfig,
	testDir: './tests/e2e',
	timeout: 30000,
	workers: 1, // Avoid concurrent test runs conflicting on database
	use: {
		...wordpressConfig.use,
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8889',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
} );
