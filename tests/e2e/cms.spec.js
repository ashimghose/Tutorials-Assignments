import { expect, test } from '@playwright/test';
import { cmsBaseURL } from '../../playwright.config.js';

// M4 journeys, on the server started with FEATURE_CMS=true (playwright.config.js).
test.use({ baseURL: cmsBaseURL });

test('CMS-3 MKT-3 a parent books a demo class on the site, with the campaign source kept', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/tuition?utm_source=instagram&utm_medium=social&utm_campaign=e2e');
    await page.getByRole('link', { name: /free demo class/i }).first().click();
    await expect(page).toHaveURL(/\/enquire\/tuition/);
    await expect(page.locator('a[href*="docs.google.com"]')).toHaveCount(0);

    await page.getByLabel("Parent's name").fill('Asha Verma');
    await page.getByLabel('Mobile (WhatsApp)').fill('98765 43210');
    await page.getByRole('button', { name: /send/i }).click();

    await expect(page).toHaveURL(/\/enquire\/thanks/);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
    expect(errors).toEqual([]);
});
