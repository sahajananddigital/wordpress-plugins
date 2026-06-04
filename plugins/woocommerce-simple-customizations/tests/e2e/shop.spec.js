// @ts-check
const { test, expect, request } = require('@playwright/test');

test.describe('WooCommerce Simple Customizations E2E', () => {
  let productId;

  // Setup: Create a product (Optional fallback if blueprint fails to seed, but mostly verifying API works)
  test.beforeAll(async ({ baseURL }) => {
    // We assume the Blueprint has already seeded the "Demo Product".
    // This block is just to fetch its ID if we needed it dynamically, 
    // but for the tests below we can rely on standard selectors or the seeded name.
    console.log('Test Suite Started');
  });

  test('Scenario 1: Price Suffix should be visible on Product Page', async ({ page }) => {
    await page.goto('/?post_type=product');
    
    // Expect to see the product
    const productTitle = page.getByText('Demo Product').first();
    await expect(productTitle).toBeVisible();

    // Click to go to single product page
    await productTitle.click();

    // Verify Suffix
    // The blueprint sets suffix to "per box"
    // Price HTML usually looks like: <span class="amount">$25.00</span> <span class="wsc-price-suffix">per box</span>
    const priceSuffix = page.locator('.wsc-price-suffix');
    await expect(priceSuffix).toContainText('per box');
    
    console.log('Verified: Price Suffix "per box" is visible.');
  });

  test('Scenario 2: Cart Limit Validation (Fail & Success)', async ({ page }) => {
    // 1. Visit Shop and Add 1 Item
    await page.goto('/?post_type=product');
    const addToCart = page.locator('.add_to_cart_button').first();
    await addToCart.click();

    // Wait for "View cart"
    const viewCart = page.locator('a.added_to_cart').first();
    await expect(viewCart).toBeVisible();
    await viewCart.click();

    // 2. Verify Failure (Quantity 1 < 5)
    await expect(page).toHaveURL(/cart/);
    
    // Check for Error Message
    const errorMsg = page.getByText(/You must purchase at least 5 items/);
    await expect(errorMsg).toBeVisible();

    // Check Checkout Button is GONE
    await expect(page.locator('.checkout-button')).not.toBeVisible();
    console.log('Verified: Checkout blocked for 1 item.');

    // 3. Verify Success (Quantity 5 >= 5)
    // Update Quantity Input
    const qtyInput = page.locator('input.qty').first();
    await qtyInput.fill('5');
    
    // Click Update Cart
    const updateCartBtn = page.locator('[name="update_cart"]');
    await updateCartBtn.click();
    
    // Wait for reload/update
    // The error message should disappear
    await expect(errorMsg).not.toBeVisible();

    // The checkout button should reappear
    // Note: Some themes hide it via CSS, others remove the hook. 
    // Our code removes the hook. So we check if it currently exists/visible.
    // Sometimes WC requires a refresh or AJAX complete.
    // Let's assert it eventually becomes visible.
    const checkoutBtn = page.locator('.checkout-button');
    await expect(checkoutBtn).toBeVisible();
    
    console.log('Verified: Checkout allowed for 5 items.');
  });
});
