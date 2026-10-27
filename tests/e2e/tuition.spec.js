import { expect, test } from '@playwright/test';

// M5 journeys on the demo data (global-setup.js seeds it; every password is "password").

async function logIn(page, login) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(login);
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
}

function noOverflow(page) {
    return page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
}

test('CLS-2 CLS-3 a student sees today\'s class and Join leads to the class link', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await logIn(page, 'riyademo');
    await expect(page).toHaveURL(/\/student$/);
    await expect(page.getByText('Class 10 Maths (CBSE)').first()).toBeVisible();

    const join = page.getByRole('link', { name: 'Join' }).first();
    await expect(join).toHaveAttribute('href', /\/classes\/\d+\/join$/);
    const response = await page.request.get(await join.getAttribute('href'), { maxRedirects: 0 });
    expect(response.status()).toBe(302);
    expect(response.headers().location).toContain('meet.google.com');

    expect(await noOverflow(page)).toBeLessThanOrEqual(0);
    expect(errors).toEqual([]);
});

test('CLS-1 a parent sees their child\'s batch, its fee status and other batches to join', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await logIn(page, 'parent@example.com');
    await page.goto('/parent/classes');
    await expect(page.getByRole('heading', { name: 'Classes', level: 1 })).toBeVisible();
    await expect(page.getByText('Class 10 Maths (CBSE)').first()).toBeVisible();
    await expect(page.getByText(/Paid until/)).toBeVisible();
    await expect(page.getByRole('button', { name: 'Ask for a place' })).toBeVisible();

    expect(await noOverflow(page)).toBeLessThanOrEqual(0);
    expect(errors).toEqual([]);
});
