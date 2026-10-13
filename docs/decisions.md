# Decisions

One short entry per notable technical decision, newest at the bottom.

## 2026-10-13 · M0 foundation

- **Starter kit.** Laravel 13 from the official Livewire starter kit (Fortify auth, Flux UI free components, Livewire 4). Its login and settings screens stay unused until M3 but save rebuilding them later. All of the kit's optional auth features (registration, email verification, 2FA, passkeys, password confirmation) are kept; revisit in M3.
- **Tests.** Pest 4 replaces PHPUnit (tests converted with Pest Drift). `RefreshDatabase` is applied to every Feature test from `tests/Pest.php`. Browser tests use `@playwright/test` in `tests/e2e/`, run on a desktop viewport and a 360 px phone viewport.
- **CI.** One GitHub Actions workflow (`.github/workflows/tests.yml`) runs on every push to any branch: Pint, Larastan (kept from the starter kit), Pest, then Playwright. CI uses PHP 8.4 and Node 24 as in CLAUDE.md; `composer.json` still allows PHP 8.3.
- **Rollout flags.** `config/features.php` reads `APP_PHASE` and the `FEATURE_*` flags from `.env`; everything defaults to phase 0 with every module off.
- **Spec in the repo.** `docs/spec.md` is a Markdown export of the v1 spec document; its two diagrams are in `docs/images/`. Re-export it when the source document changes.
