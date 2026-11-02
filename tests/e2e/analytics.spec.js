import { expect, test } from '@playwright/test';
import { gaBaseURL } from '../../playwright.config.js';

// Google Analytics loads only after "Allow", on the server started with GA_MEASUREMENT_ID (playwright.config.js).
test.use({ baseURL: gaBaseURL });

test('Google Analytics loads only after the visitor allows cookies', async ({ page }) => {
    const gaRequests = [];
    await page.route('https://www.googletagmanager.com/**', (route) => {
        gaRequests.push(route.request().url());
        route.fulfill({ status: 200, contentType: 'text/javascript', body: '' });
    });

    await page.goto('/');
    const banner = page.getByRole('region', { name: 'Cookie choice' });
    await expect(banner).toBeVisible();
    expect(gaRequests).toHaveLength(0);

    const width = await page.evaluate(() => document.documentElement.scrollWidth);
    expect(width).toBeLessThanOrEqual(page.viewportSize().width);

    await banner.getByRole('button', { name: 'Allow' }).click();
    await expect(banner).toBeHidden();
    await expect.poll(() => gaRequests.length).toBe(1);
    expect(gaRequests[0]).toContain('id=G-E2ETEST01');

    // Remembered on the next page, without asking again.
    await page.goto('/tuition');
    await expect(banner).toBeHidden();
    await expect.poll(() => gaRequests.length).toBe(2);
});

test('Google Analytics never loads when the visitor says no, and the choice can be changed', async ({ page }) => {
    const gaRequests = [];
    await page.route('https://www.googletagmanager.com/**', (route) => {
        gaRequests.push(route.request().url());
        route.fulfill({ status: 200, contentType: 'text/javascript', body: '' });
    });

    await page.goto('/academic');
    const banner = page.getByRole('region', { name: 'Cookie choice' });
    await banner.getByRole('button', { name: 'No, thanks' }).click();
    await expect(banner).toBeHidden();

    await page.goto('/');
    await expect(banner).toBeHidden();
    expect(gaRequests).toHaveLength(0);

    await page.getByRole('button', { name: 'Cookie settings' }).click();
    await expect(banner).toBeVisible();
    await banner.getByRole('button', { name: 'Allow' }).click();
    await expect.poll(() => gaRequests.length).toBe(1);
});
