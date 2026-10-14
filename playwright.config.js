import { defineConfig, devices } from '@playwright/test';

const port = process.env.E2E_PORT ?? '8000';
const baseURL = `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        launchOptions: {
            // Lets a machine with a preinstalled Chromium skip `npx playwright install`.
            executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || undefined,
        },
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
        {
            name: 'phone-360',
            use: { ...devices['Desktop Chrome'], viewport: { width: 360, height: 800 }, isMobile: true, hasTouch: true },
        },
    ],
    webServer: {
        command: `php artisan serve --no-reload --host=127.0.0.1 --port=${port}`,
        // A fake academic form, so the UTM test can check the pre-filled link.
        env: {
            FORM_URL_ACADEMIC: 'https://docs.google.com/forms/d/e/e2e-test/viewform',
            FORM_ACADEMIC_ENTRY_SOURCE: 'entry.101',
            FORM_ACADEMIC_ENTRY_MEDIUM: 'entry.102',
            FORM_ACADEMIC_ENTRY_CAMPAIGN: 'entry.103',
            FORM_ACADEMIC_ENTRY_PAGE: 'entry.104',
        },
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
