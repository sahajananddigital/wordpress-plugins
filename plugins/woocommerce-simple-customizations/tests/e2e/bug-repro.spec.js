// @ts-check
const { test, expect, request } = require('@playwright/test');

test.describe('Bug Repro: Double Quantity on Add', () => {
    
  test.beforeAll(async ({ baseURL }) => {
    // 1. Enable Auto-Adjust via API
    const apiContext = await request.newContext({
      baseURL: baseURL,
      httpCredentials: { username: 'admin', password: 'password' },
    });
    
    // Set Min Qty 3, Auto Adjust ON
    await apiContext.post('/index.php?rest_route=/wsc/v1/settings', {
      data: {
        cart_limit_enabled: '1',
        cart_limit_rules: [{
            target_type: 'global',
            min_qty: 3,
            auto_adjust_qty: '1',
            conditions: []
        }]
      }
    });

    // Create unique product for this test to avoid cart conflicts
    await apiContext.post('/index.php?rest_route=/wc/v3/products', {
      data: {
        name: 'Auto Adjust Product',
        type: 'simple',
        regular_price: '10.00',
        status: 'publish'
      }
    });
  });

  test('should add delta quantity (1) not full min quantity (3) on second add', async ({ page }) => {
    // 1. Visit Shop
    await page.goto('/?post_type=product');
    
    const addToCart = page.locator('.add_to_cart_button', { hasText: 'Add to cart' }).filter({ hasText: 'Auto Adjust Product' }).first();
    // Fallback if filter fails (e.g. only one product)
    const btn = await addToCart.count() > 0 ? addToCart : page.locator('.add_to_cart_button').first();

    // 2. First Add: Should be 3
    await btn.click();
    await expect(page.locator('a.added_to_cart')).toBeVisible(); // Wait for ajax
    
    // Check Cart Bubble or View Cart - easiest is to go to cart
    // But we want to stay on shop to click again. 
    // Let's click "View Cart", verify 3, then go back?
    // Or just click again.
    
    // 3. Second Add: Should be 4
    // We need to trigger the add again.
    // Usually the button changes to "View Cart". 
    // We can force a visit to the product page to add again, or click the button if it stays.
    // Standard WC Loop: "View cart" replaces "Add to cart". 
    
    // Let's go to Single Product Page for specific control
    await page.getByText('Auto Adjust Product').first().click();
    
    // Ensure input is 1
    const qtyInput = page.locator('input.qty');
    await expect(qtyInput).toHaveValue('1');
    
    const singleAddBtn = page.locator('button[name="add-to-cart"]');
    
    // We already have 3 in cart (from shop page).
    // Adding 1 more.
    await singleAddBtn.click();
    
    // 4. Verify Total is 4
    await page.locator('.woocommerce-message').waitFor(); // "x has been added to your cart"
    await page.goto('/cart/');
    
    const cartQty = page.locator('input.qty').first();
    await expect(cartQty).toHaveValue('4'); 
  });
});
