const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'GitSupport Engine Dashboard E2E', () => {
	let consoleErrors = [];

	test.beforeEach( async ( { admin, page } ) => {
		consoleErrors = [];
		page.on( 'console', ( msg ) => {
			if ( msg.type() === 'error' ) {
				consoleErrors.push( msg.text() );
			}
		} );

		// Navigate directly to the GitSupport Dashboard (tools page)
		await admin.visitAdminPage( 'tools.php?page=gitsupport-engine' );
	} );

	test( 'loads the dashboard with metrics and settings successfully without console errors', async ( { page } ) => {
		// 1. Verify dashboard heading loads
		await expect( page.getByRole( 'heading', { name: 'GitSupport Engine Dashboard' } ) ).toBeVisible();

		// 2. Verify metrics dashboard components are present
		await expect( page.getByText( 'Total Tickets' ) ).toBeVisible();
		await expect( page.getByText( 'Open Tickets' ) ).toBeVisible();
		await expect( page.getByText( 'Resolved Tickets' ) ).toBeVisible();
		await expect( page.getByText( 'Avg Resolution Time' ) ).toBeVisible();

		// 3. Switch to Settings tab
		await page.getByRole( 'tab', { name: 'Settings' } ).click();

		// 4. Modify settings fields
		await page.getByLabel( 'GitHub API Personal Access Token' ).fill( 'ghp_mock_token_value_for_e2e_testing' );
		await page.getByLabel( 'GitHub Repository Owner' ).fill( 'test-owner-org' );
		await page.getByLabel( 'GitHub Repository Name' ).fill( 'test-repository-name' );
		await page.getByLabel( 'GitHub Webhook Secret' ).fill( 'webhook_secret_xyz' );
		await page.getByLabel( 'Inbound Email Hook Secret' ).fill( 'inbound_secret_abc' );
		await page.getByLabel( 'AI API Key' ).fill( 'ai_api_key_123' );
		
		// Update select drop-down value
		await page.getByLabel( 'AI Provider' ).selectOption( 'openai' );

		// 5. Submit and Save settings
		await page.getByRole( 'button', { name: 'Save Settings' } ).click();

		// 6. Verify settings saved successfully notice appears
		await expect( page.getByText( 'Settings saved successfully.' ) ).toBeVisible();

		// 7. Verify no console errors occurred during the test flow
		expect( consoleErrors ).toEqual( [] );
	} );
} );
