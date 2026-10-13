# Tutorials and Assignments

Website for live online tuition (classes 1 to 12) and academic services for Bachelors, Masters and PhD students.

- What to build: [docs/spec.md](docs/spec.md)
- How to build it: [CLAUDE.md](CLAUDE.md)
- Decisions so far: [docs/decisions.md](docs/decisions.md)

The current rollout phase is `APP_PHASE` in `.env` (0 = static marketing site).

## Run it locally (Phases 0 and 1)

Needs PHP 8.4+, Composer and Node.js 24.

```bash
composer setup                 # install, create .env, migrate SQLite, build assets
composer run dev               # http://localhost:8000
```

## Checks

```bash
./vendor/bin/pint --test       # code style
php artisan test               # Pest
npx playwright install chromium   # once
npx playwright test            # browser tests (desktop and 360 px phone)
```

`composer test` runs Pint, Larastan and Pest together. GitHub Actions runs all of the above on every push.
