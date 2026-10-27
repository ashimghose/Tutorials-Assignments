import { expect, test } from '@playwright/test';

// M3 user journeys. The web server runs with FEATURE_ACCOUNTS=true (playwright.config.js).

test('AUTH-1 a parent signs up and is asked to verify their email', async ({ page }, testInfo) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/register');
    await expect(page.getByRole('heading', { name: 'Create a parent account' })).toBeVisible();

    await page.getByLabel("Parent's full name").fill('Asha Verma');
    await page.getByLabel('Email address').fill(`asha-${testInfo.project.name}-${Date.now()}@example.com`);
    await page.getByLabel('Mobile number').fill('98765 43210');
    await page.getByLabel('Password', { exact: true }).fill('a-long-password');
    await page.getByLabel('Confirm password').fill('a-long-password');
    await page.locator('ui-checkbox[name="consent"]').click();
    await page.getByRole('button', { name: 'Create account' }).click();

    await expect(page).toHaveURL(/\/email\/verify$/);
    await expect(page.getByText('verify your email', { exact: false }).first()).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
    expect(errors).toEqual([]);
});

test('ACA-3 the academic sign-up asks for the 18+ declaration and links under-18s to Tuition', async ({ page }) => {
    await page.goto('/register?as=academic');

    await expect(page.getByRole('heading', { name: 'Create your academic account' })).toBeVisible();
    await expect(page.getByText('I am 18 or older')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Our Tuition section is for school students' })).toHaveAttribute('href', /\/tuition$/);
});

test('the login page accepts an email or a username and fits the screen', async ({ page }) => {
    await page.goto('/login');

    await expect(page.getByLabel('Email or username')).toBeVisible();
    await expect(page.locator('img[alt]').first()).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
});
