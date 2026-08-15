import { defineConfig, devices } from '@playwright/test';

/**
 * 购买流冒烟：需本机/staging WordPress。
 * TS_E2E_BASE_URL=https://cosfusion.local
 * TS_E2E_PRODUCT_PATH=/product/sample-product/
 */
export default defineConfig({
	testDir: './e2e',
	timeout: 60_000,
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	retries: 0,
	reporter: 'list',
	use: {
		baseURL: process.env.TS_E2E_BASE_URL || 'http://localhost',
		trace: 'on-first-retry',
		...devices['Desktop Chrome'],
	},
});
