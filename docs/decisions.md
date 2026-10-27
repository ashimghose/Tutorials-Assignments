# Decisions

One short entry per notable technical decision, newest at the bottom.

## 2026-10-13 · M0 foundation

- **Starter kit.** Laravel 13 from the official Livewire starter kit (Fortify auth, Flux UI free components, Livewire 4). Its login and settings screens stay unused until M3 but save rebuilding them later. All of the kit's optional auth features (registration, email verification, 2FA, passkeys, password confirmation) are kept; revisit in M3.
- **Tests.** Pest 4 replaces PHPUnit (tests converted with Pest Drift). `RefreshDatabase` is applied to every Feature test from `tests/Pest.php`. Browser tests use `@playwright/test` in `tests/e2e/`, run on a desktop viewport and a 360 px phone viewport.
- **CI.** One GitHub Actions workflow (`.github/workflows/tests.yml`) runs on every push to any branch: Pint, Larastan (kept from the starter kit), Pest, then Playwright. CI uses PHP 8.4 and Node 24 as in CLAUDE.md; `composer.json` still allows PHP 8.3.
- **Rollout flags.** `config/features.php` reads `APP_PHASE` and the `FEATURE_*` flags from `.env`; everything defaults to phase 0 with every module off.
- **Spec in the repo.** `docs/spec.md` is a Markdown export of the v1 spec document; its two diagrams are in `docs/images/`. Re-export it when the source document changes.

## 2026-10-14 · M1 marketing site

- **Content as files.** Section content (prices, FAQs, steps, waitlists) lives in `resources/content/*.php` and guides in `resources/content/resources/*.md`, read by `App\Support\SiteContent`. M4 seeds the CMS tables from these files. Prices are whole paise, shown with `App\Support\Money`.
- **Public layout.** The marketing pages use their own layout (`x-site.layout`), stylesheet (`resources/css/site.css`) and script (`resources/js/site.js`), with system fonts and no Flux or Livewire, so they stay small and exportable. The phone menu is a `<details>` element (no JavaScript).
- **UTM capture in Phase 0 is client-side.** A static page can't run the `CaptureUtm` middleware, so `site.js` keeps `utm_source/medium/campaign` in `sessionStorage` for the visit and adds them to Google Form pre-fill links; the page and interest are pre-filled on the server. `CaptureUtm` arrives with the leads table in M4. Form entry ids are `.env` values (`FORM_*_ENTRY_*`); see `docs/google-forms.md`.
- **Fallbacks.** A form button opens the section's WhatsApp chat until its form URL is set. Teacher, expert and testimonial sections stay hidden until real entries are added; nothing is invented.
- **Export and deploy are separate.** `spatie/laravel-export` and the Cloudflare Pages workflow are left for the deploy thread; routes here are GET-only and read no session or database.
- **Playwright server.** `php artisan serve --no-reload` so the fake form settings in `playwright.config.js` reach the app for the UTM test.

## 2026-10-14 · Phase 0 deploy

- **Export.** `spatie/laravel-export` (dev dependency) crawls the public pages from `APP_URL` into `dist/`, plus `sitemap.xml` and a `404.html` that Cloudflare Pages serves for unknown addresses. Links are absolute, so the export must run with the live `APP_URL`.
- **PHP 8.4.** laravel-export 1.5 needs PHP 8.4 and pulled in some Symfony 8 components and Guzzle 7, so `composer.json` now requires PHP `^8.4` (CI already used 8.4).
- **Deploy.** `.github/workflows/deploy.yml` runs after the tests workflow passes on `main` (or by hand), exports and uploads with `npx wrangler@4 pages deploy`; it creates the Pages project on first run. No new npm dependency. Without the `CLOUDFLARE_*` secrets it exports and warns instead of failing.
- **Site settings in CI.** Public site values (contact, WhatsApp, form URLs and entry ids, analytics token, `APP_NAME`) come from GitHub Actions repository variables, allow-listed by name; unset ones keep the `.env.example` defaults. `APP_URL` and the project name are fixed in the workflow. Steps for the owner: `docs/deploy.md`.

## 2026-10-27 · Site icon

- **Favicon.** The Laravel logo is replaced by the header's monogram ("TA", white on brand-800 `#1f3c8f`): `public/favicon.svg` draws the letters as paths so no font is needed, `favicon.ico` (16/32/48 px) and `apple-touch-icon.png` (180 px, square, iOS rounds it) are rendered from it. Redraw all three when the brand name is chosen.

## 2026-10-27 · Site look: "Calm Scholar" and the diya icon

- **Colours.** Chosen by the owner from three researched options. Brand is forest green (`#0b5d45`), tuition is saffron (`#b4561a` for text and buttons), academic uses the brand greens. Only the `@theme` tokens in `resources/css/site.css` changed, so the pages' classes stay the same.
- **Fonts.** Source Serif 4 for `h1`–`h3` and Source Sans 3 for text (both SIL OFL). They are self-hosted in `resources/fonts` and bundled by Vite, so the static site makes no request to Google and adds about 80 KB on English pages. The Latin Extended files load only when a page uses those characters.
- **Icon.** A diya (lamp of learning) replaces the "TA" monogram, so the icon no longer depends on the brand name. `public/favicon.svg` is the source; the header draws the same SVG inline; `favicon.ico` (16/32/48 px) and `apple-touch-icon.png` (180 px, square) are rendered from it with Playwright.
