import { expect, test } from '@playwright/test';
import { cmsBaseURL } from '../../playwright.config.js';

// VRT-1, VRT-2: the music section, on the server started with FEATURE_VERTICAL_MUSIC=true.
test.use({ baseURL: cmsBaseURL });

test('VRT-2 a learner finds music on the home page and books a free trial lesson', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/?utm_source=instagram&utm_campaign=e2e-music');
    await page.getByRole('link', { name: 'Explore music lessons' }).click();
    await expect(page).toHaveURL(/\/music$/);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(/music lessons/i);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);

    await page.getByRole('link', { name: /free trial lesson/i }).first().click();
    await expect(page).toHaveURL(/\/enquire\/music/);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Book a free trial lesson');

    await page.getByLabel('Your name').fill('Ravi Das');
    await page.getByLabel('Mobile (WhatsApp)').fill('98765 43210');
    await page.getByRole('button', { name: /send/i }).click();

    await expect(page).toHaveURL(/\/enquire\/thanks\?section=music/);
    await expect(page.getByRole('link', { name: 'Back to music lessons' })).toBeVisible();
    expect(errors).toEqual([]);
});
