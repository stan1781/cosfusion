import { expect, test } from '@playwright/test';

const productPath = process.env.TS_E2E_PRODUCT_PATH || '/product/sample-product/';

test.describe('purchase smoke', () => {
	test('add to cart opens drawer with line item', async ({ page }) => {
		test.skip(
			!process.env.TS_E2E_BASE_URL,
			'Set TS_E2E_BASE_URL and TS_E2E_PRODUCT_PATH to a simple (non-variable) product URL'
		);

		await page.goto(productPath);

		const addButton = page.locator(
			'form.cart button.single_add_to_cart_button, form.cart button[name="add-to-cart"]'
		).first();
		await expect(addButton).toBeVisible();
		await addButton.click();

		const drawer = page.locator('.site-cart-drawer[data-open="true"], .site-cart-drawer:not([data-open="false"])').first();
		await expect(page.locator('[data-ts-cart-drawer-inner]')).toBeVisible({ timeout: 15_000 });

		const line = page.locator('[data-ts-cart-items] li').first();
		await expect(line).toBeVisible({ timeout: 15_000 });
		await expect(drawer).toBeVisible();
	});
});
