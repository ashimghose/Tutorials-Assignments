import { expect, test } from '@playwright/test';

// M6 journey on the demo data (global-setup.js seeds it; every password is "password").

test('ACA-4 an academic student orders editing with printing and sees what to pay', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/login');
    await page.getByLabel('Email or username').fill('student@example.com');
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).toHaveURL(/\/my-orders$/);

    await page.goto('/my-orders/new');
    await page.getByLabel('Service').selectOption({ label: 'Thesis or dissertation editing and proofreading' });
    await expect(page.getByText('Free revisions: 2')).toBeVisible();
    await page.getByLabel('Topic or title').fill('Partition in Bengali fiction');
    await page.getByLabel('Deadline').fill('2030-01-15');
    await page.getByLabel('Copies of Spiral bound copy').fill('2');
    await expect(page.getByText('Delivery address')).toBeVisible();
    await page.getByRole('textbox', { name: 'Address' }).fill('12 College Street');
    await page.getByRole('textbox', { name: 'City' }).fill('Kolkata');
    await page.getByRole('textbox', { name: 'State' }).fill('West Bengal');
    await page.getByRole('textbox', { name: 'PIN code' }).fill('700073');
    await expect(page.locator('[data-test="order-total"]')).toHaveText('₹9,794');

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);

    await page.locator('[data-test="place-order"]').click();
    await expect(page).toHaveURL(/\/my-orders\/\d+$/);
    await expect(page.getByText('Payment due: ₹9,794')).toBeVisible();
    await expect(page.getByText('Awaiting payment').first()).toBeVisible();
    expect(errors).toEqual([]);
});
