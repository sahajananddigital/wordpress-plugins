// @ts-check
const { test, expect } = require('@playwright/test');

test('Checkout: Payment methods update when country changes', async ({ page }) => {
  // 1. Add a product to cart
  await page.goto('/product/shirt-green/');
  await page.getByRole('button', { name: 'Add to cart', exact: true }).click();
  await page.getByRole('link', { name: 'View cart' }).first().click();
  await page.getByRole('link', { name: 'Proceed to checkout' }).click();

  // 2. Wait for checkout to load
  await expect(page.getByRole('heading', { name: 'Billing address' })).toBeVisible();

  // 3. Reset to India
  const countrySelect = page.getByRole('combobox', { name: 'Country/Region' });
  await countrySelect.click();
  // Filter list
  await page.keyboard.type('India');
  // Select option
  await page.getByRole('option', { name: 'India', exact: true }).click();
  
  // Wait for update
  await page.waitForTimeout(2000); 

  // 4. Change to "Iraq"
  await countrySelect.click();
  await page.keyboard.type('Iraq');
  await page.getByRole('option', { name: 'Iraq', exact: true }).click();

  // 5. Verify Payment Methods update
  await page.waitForTimeout(2000);
  
  const paymentMethods = await page.locator('.wc-block-components-radio-control__label').allInnerTexts();
  console.log('Payment Methods for Iraq:', paymentMethods);

  // 6. Change back to India to compare
  await countrySelect.click();
  await page.keyboard.type('India');
  await page.getByRole('option', { name: 'India', exact: true }).click();
  
  await page.waitForTimeout(2000);
  const paymentMethodsIndia = await page.locator('.wc-block-components-radio-control__label').allInnerTexts();
  console.log('Payment Methods for India:', paymentMethodsIndia);

  expect(paymentMethods).not.toEqual(paymentMethodsIndia);
});
