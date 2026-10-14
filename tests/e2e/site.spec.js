import { expect, test } from '@playwright/test';

const pages = [
    '/',
    '/tuition',
    '/academic',
    '/resources',
    '/resources/how-to-structure-a-synopsis',
    '/resources/citation-styles-at-a-glance',
    '/resources/board-exam-90-day-plan',
    '/about',
    '/contact',
    '/privacy',
    '/terms',
    '/refund-policy',
];

for (const path of pages) {
    test(`${path} opens without errors and fits the screen`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
            if (message.type() === 'error') {
                errors.push(message.text());
            }
        });

        const response = await page.goto(path);

        expect(response?.status()).toBe(200);
        await expect(page.locator('h1')).toBeVisible();
        await expect(page.locator('[data-whatsapp]')).toBeVisible();

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow).toBeLessThanOrEqual(0);
        expect(errors).toEqual([]);
    });
}

test('CHAT-6 WhatsApp buttons carry a different message per section', async ({ page }) => {
    await page.goto('/tuition');
    const tuition = await page.locator('[data-whatsapp]').getAttribute('href');

    await page.goto('/academic');
    const academic = await page.locator('[data-whatsapp]').getAttribute('href');

    expect(tuition).toContain('wa.me/');
    expect(academic).toContain('wa.me/');
    expect(tuition).not.toEqual(academic);
});

test('the menu opens on a phone', async ({ page, isMobile }) => {
    test.skip(!isMobile, 'The menu button only shows on small screens.');

    await page.goto('/');
    await page.getByLabel('Menu').click();
    await page.getByRole('link', { name: 'Academic services' }).first().click();

    await expect(page).toHaveURL(/\/academic$/);
});

test('MKT-3 a tagged link adds its campaign to the enquiry form, even after moving to another page', async ({ page }) => {
    await page.goto('/academic?utm_source=whatsapp&utm_medium=status&utm_campaign=phd-synopsis-oct');

    const formLink = page.locator('a[data-form="academic"][data-utm-entries]').first();
    test.skip((await formLink.count()) === 0, 'Needs the fake academic form from playwright.config.js (a fresh test server).');

    let href = new URL(await formLink.getAttribute('href'));
    expect(href.searchParams.get('entry.101')).toBe('whatsapp');
    expect(href.searchParams.get('entry.102')).toBe('status');
    expect(href.searchParams.get('entry.103')).toBe('phd-synopsis-oct');
    expect(href.searchParams.get('entry.104')).toBe('academic');

    // The campaign is remembered for the rest of the visit.
    await page.goto('/contact');
    href = new URL(await page.locator('a[data-form="academic"][data-utm-entries]').first().getAttribute('href'));
    expect(href.searchParams.get('entry.101')).toBe('whatsapp');
    expect(href.searchParams.get('entry.104')).toBe('contact');
});
