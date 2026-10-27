import { expect, test } from '@playwright/test';

// M7 journey on the demo data (global-setup.js seeds it; every password is "password").

async function logIn(page, login) {
    await page.goto('/login');
    await page.getByLabel('Email or username').fill(login);
    await page.getByLabel('Password', { exact: true }).fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForURL((url) => !url.pathname.startsWith('/login'));
}

test('CHAT-4 a school student\'s message reaches their parent, who replies, and the student sees it was read', async ({ browser }, testInfo) => {
    const subject = `Doubt in algebra (${testInfo.project.name} ${Date.now()})`;
    const errors = [];

    const studentContext = await browser.newContext({ viewport: testInfo.project.use.viewport });
    const student = await studentContext.newPage();
    student.on('pageerror', (error) => errors.push(error.message));
    await logIn(student, 'riyademo');
    await student.goto('/messages');
    await student.locator('[data-test="new-message"]').click();
    await student.getByLabel('Subject').fill(subject);
    await student.getByLabel('Message').fill('Can we go over quadratic equations again?');
    await student.locator('[data-test="send-new-message"]').click();
    await expect(student).toHaveURL(/\/messages\/\d+$/);
    await expect(student.getByText('Can we go over quadratic equations again?')).toBeVisible();
    const overflow = await student.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);

    const parentContext = await browser.newContext({ viewport: testInfo.project.use.viewport });
    const parent = await parentContext.newPage();
    parent.on('pageerror', (error) => errors.push(error.message));
    await logIn(parent, 'parent@example.com');
    await parent.goto('/messages');
    await parent.getByRole('link', { name: new RegExp(subject.replace(/[()]/g, '\\$&')) }).click();
    await expect(parent.getByText('Can we go over quadratic equations again?')).toBeVisible();
    await parent.getByLabel('Message').fill('I will ask the teacher to cover it on Monday.');
    await parent.locator('[data-test="send-message"]').click();
    await expect(parent.getByText('I will ask the teacher to cover it on Monday.')).toBeVisible();

    // The student's page polls: the reply and the read receipt arrive without a reload.
    await expect(student.getByText('I will ask the teacher to cover it on Monday.')).toBeVisible({ timeout: 15_000 });
    await expect(student.locator('[data-test="seen"]')).toBeVisible();

    expect(errors).toEqual([]);
    await studentContext.close();
    await parentContext.close();
});
