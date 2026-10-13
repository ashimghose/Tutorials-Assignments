import { expect, test } from '@playwright/test';

test('home page opens without errors', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') {
            errors.push(message.text());
        }
    });

    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).toBeVisible();
    expect(errors).toEqual([]);
});

test('home page fits the screen without sideways scrolling', async ({ page }) => {
    await page.goto('/');

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);

    expect(overflow).toBeLessThanOrEqual(0);
});
