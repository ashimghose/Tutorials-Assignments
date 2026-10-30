# CLAUDE.md — Online Tuition & Academic Services Platform

Read this file and `docs/spec.md` before starting any task. The spec is the source of
truth for WHAT to build; this file says HOW to build it. Requirement IDs (AUTH-1, ACA-6,
CHAT-4, MKT-3, PAY-2, …) come from the spec. Use them in plans, tests and commit messages.

## What this is

A website for an Indian education business with two offerings, each with its own
marketing section:

1. **Tuition** – live online school tuition, classes 1–12 (`/tuition`).
2. **Academic services** – guidance, editing, formatting, similarity checking, printing and
   delivery for Bachelors, Masters and PhD students' research papers, research books,
   synopses, theses and dissertations (`/academic`). Fixed price list + add-ons
   (printing, binding, courier, express) + custom quotes. Orders move through
   admin-configurable statuses.

Logged-in roles:
- **Parent** – owns a family account, adds school-age children, gives consent, pays tuition fees.
- **Student** – school student; login created by the parent; joins paid classes, watches recordings.
- **Academic student** – adult (18+) self sign-up; orders academic services, tracks status.
- **Teacher / expert** – invited by admin; runs tuition batches and/or works on assigned academic orders.
- **Admin** – manages people, batches, price lists, add-ons, order statuses, content, chat inbox, reports.

Scale for v1: ≤ 1,000 visitors/month, ≤ 50 school students, ≤ 20 academic orders/month,
≤ 3 concurrent classes of ≤ 15 students. The owner is a first-time entrepreneur on a tight
budget: prefer free, open-source and simple. Do not add a paid service or a new
Composer/npm dependency without asking first.

## Phased rollout — check the current phase before building

The business launches in phases and only pays for features once demand is proven
(spec: "Phased rollout"). The current phase is `APP_PHASE` in `.env`.

| Phase | What runs live | How it's built |
|---|---|---|
| 0 Listen | Static marketing site, Google Forms, WhatsApp buttons, Cloudflare Web Analytics | Blade pages exported to static HTML with `spatie/laravel-export`, deployed to Cloudflare Pages by GitHub Actions. SQLite locally, no Docker. |
| 1 Sell by hand | Same site + Razorpay Payment Links, Google Meet, Google Sheets | Mostly no code; small page changes only |
| 2 Platform core | Logins, checkout, academic orders, in-app chat, Filament admin | Same app deployed to a Lightsail server; Sail + PostgreSQL locally |
| 3 Own classroom | LiveKit classes, recordings | Classroom module + one React page |

Rules:
- Public marketing pages must stay exportable as static HTML: no session, CSRF-dependent
  forms or database reads that the export can't capture while `APP_PHASE` < 2.
- Phase 2+ features sit behind feature flags (`FEATURE_*` in `.env`, read via `config/features.php`)
  so they can be built and tested locally while an earlier phase is live.
- When content moves into the database (M4), seed it from the Phase 0 Blade/Markdown content rather than retyping.

## Stack

- **PHP 8.4+ / Laravel** (13.x at time of writing). Follow Laravel conventions; prefer first-party
  features over packages.
- **Blade + Tailwind CSS** for pages; **Livewire + Alpine.js** for interactive pages
  (order form, chat, dashboards). React only for the Phase 3 classroom page (LiveKit components), built with Vite.
- **Filament** for the admin panel and content management (`/admin`).
- **Eloquent + migrations**. Database: SQLite in Phases 0–1 on the laptop; **PostgreSQL** from Phase 2
  (Sail locally, same server in production, managed service later).
- **Cache, sessions, queues:** `database` driver now; Redis later by config only.
- **Storage:** Laravel Storage — `local` disk in development, `r2` disk (S3 driver, Cloudflare R2) in production.
- **Mail:** Laravel Mail — Mailpit locally, Brevo SMTP in production.
- **Real-time chat:** Laravel Reverb (self-hosted) with Laravel Echo; fall back to Livewire polling if Reverb is off.
- **Payments:** Razorpay Payment Links (Phase 1); Razorpay PHP SDK inside `app/Services/Payments` (Phase 2+).
- **Live classes:** Google Meet links on sessions (Phase 1–2); LiveKit (Phase 3) inside `app/Services/Video`,
  server tokens via the community `agence104/livekit-server-sdk` package.
- **Analytics:** Cloudflare Web Analytics (cookie-less), public pages only; optional Google Analytics 4
  (`GA_MEASUREMENT_ID`) on public pages, logged-out visitors only, loaded only after cookie consent.
- **Tests:** Pest (unit + feature), Playwright (browser end-to-end). **Code style:** Laravel Pint.
- **Local dev:** `composer run dev` (Phases 0–1); Laravel Sail (Phase 2+). Node.js 24 LTS for Vite and Playwright.

## Commands

```bash
# Phases 0–1 (no Docker)
composer run dev               # web server :8000 + queue worker + Vite
php artisan test               # Pest
npx playwright test            # browser tests
./vendor/bin/pint              # code style
php artisan migrate            # apply migrations
php artisan export             # Phase 0: static HTML for Cloudflare Pages (spatie/laravel-export)

# Phase 2+ (Sail: Postgres, Mailpit :8025, later Reverb and LiveKit)
sail up -d && sail npm run dev # app at http://localhost
sail artisan test
sail artisan migrate
sail artisan make:filament-resource Model
```

If a command above isn't set up yet, set it up when that milestone needs it.

## Folder layout (Laravel defaults plus)

```
app/
  Models/                 Eloquent models (one per spec collection)
  Policies/               authorization per model (who can view/update)
  Filament/Resources/     admin screens
  Livewire/               interactive pages: Orders/, Chat/, Parent/, Student/, Teacher/
  Services/Payments/      PaymentGateway interface + RazorpayGateway
  Services/Video/         VideoProvider interface + LiveKitProvider (Phase 3)
  Services/Pricing.php    order total = base + add-ons × qty + GST, or admin quote
  Support/Money.php       paise <-> display (₹1,00,000)
  Http/Middleware/        CaptureUtm, EnsureFeatureEnabled
config/features.php       APP_PHASE + FEATURE_* flags
database/migrations/  database/seeders/
resources/views/site/     Phase 0 marketing pages (home, tuition/, academic/, resources/)
resources/js/classroom/   React classroom (Phase 3)
routes/web.php            public + portal routes; routes/channels.php for chat
tests/Feature/  tests/Unit/  tests/e2e/ (Playwright)
docs/spec.md  docs/decisions.md
```

## Non-negotiable rules

### Access control
- Every read and write is authorized **on the server** with Policies, Gates or Filament
  resource authorization. Never rely on hiding buttons. Scope queries to the user
  (`->whereBelongsTo($user)`), never filter in Blade.
- School students see only their own data, their batches' sessions and recordings.
- Parents see only their own children, payments, notices and their children's chats.
- Academic students see only their own orders, files, payments and conversations.
- Teachers/experts see only batches and orders assigned to them, and their assigned conversations.
- Only admins reach `/admin` (Filament `canAccessPanel`). Admin actions are written to the audit log (ADM-6).

### Payments (PAY)
- Amounts are integers in **paise** everywhere (`Money` helper for display). Never floats.
- Totals are computed on the server by `Services/Pricing`. Never trust a price from the browser.
- An enrolment becomes Active, or an academic order leaves "Awaiting payment", ONLY after the
  server verifies the Razorpay signature or a verified webhook. Never trust the browser redirect.
- Webhook handling is idempotent (store the event id; same event twice = no double credit).
  Exclude the webhook route from CSRF, but verify its signature.
- Card/UPI details never touch our server.

### Academic services (ACA)
- Sell guidance, editing, formatting, similarity checking, printing and delivery. Do NOT write
  copy, features or templates offering finished theses or papers to submit as the student's
  own work (UGC 2018 anti-plagiarism rules; universities treat it as misconduct).
- Service types, levels, add-ons and order statuses are admin-managed data. Never hard-code
  the status list; use the `is_final` and `notify_student` flags.
- Every status change writes `order_status_histories` and queues an email when `notify_student` is on.
- Academic students must declare 18+; under-18s are sent to the Tuition section.
- Order files are private (`r2`/`local` private disk), downloaded via temporary signed URLs after a
  Policy check; validate MIME type server-side; size limits; scheduled deletion after retention.
- Collect a delivery address only when printing or courier is in the order.

### Chat (CHAT)
- Conversations are tagged Tuition or Academic and may link to a batch or an order.
- Safeguarding: any conversation involving a school student always includes that student's parent.
  No private teacher-to-minor channels. Authorize every private broadcast channel in `routes/channels.php`.
- Rate-limit messages; keep full history; allow reporting a message.

### Marketing and interest tracking (MKT)
- `/tuition` and `/academic` are separate sections with their own landing pages and CTAs.
- `CaptureUtm` middleware stores `utm_source`, `utm_medium`, `utm_campaign` on landing and attaches
  them to every lead, waitlist sign-up and resulting order/enrolment.
- WhatsApp buttons use a different pre-filled message per section.
- Analytics and campaign tracking only on public pages. Tuition marketing targets parents.

### Live classes (CLS) and recordings (REC) — Phase 3
- Room tokens are issued only after checking role, batch membership, enrolment Active
  (paid-through ≥ today, Asia/Kolkata) and not removed; short-lived (≤ 2 hours), minimum grants.
- "Questions allowed" off = students cannot publish audio or chat; toggled via the server.
- Teacher's notebook camera is a second video track (second camera or phone joined via QR).
- Remove-from-class uses the LiveKit server API and blocks token re-issue for that session.
- Recordings: teacher's browser (MediaRecorder) → chunked upload via signed URLs → private storage;
  playback via temporary signed URLs after an enrolment check; title, tags, delete-after date.

### Privacy (India DPDP Act)
- School students are minors: only a verified parent can create a child profile; store consent
  (policy version + timestamp). Minimum child data: first name, class, board, optional school.
- Academic students consent for themselves; store the 18+ declaration and consent.
- No ad trackers, behavioural analytics or profiling on logged-in pages.
- Marketing email only with explicit opt-in and one-click unsubscribe.

### Built to scale (keep these true)
- The app server is stateless: sessions/cache/queues in the database (later Redis), files on a
  storage disk, never written to the server's local filesystem in production.
- Every outside service is behind a Laravel driver/config value or one interface in `app/Services`.
- All schema changes are migrations; use database-agnostic Eloquent/query builder (no raw
  Postgres-only SQL unless recorded in `docs/decisions.md`).

### Security
- Secrets only in `.env` (never commit it; keep `.env.example` updated with names, not values).
- Rate-limit login, sign-up, forgot-password, public forms and chat (`RateLimiter`).
- Validate all input with Form Requests or Livewire validation rules.
- Mass-assignment: use `$fillable`; never `$guarded = []` on models with role or money fields.
- Don't log personal data, tokens, file contents or payment payloads.

### Dates and locale
- `APP_TIMEZONE=UTC` for storage; display and schedule in **Asia/Kolkata**. Indian number format (₹1,00,000).

## How to work

1. Check `APP_PHASE` and the milestone in the spec; don't build ahead of the milestone asked.
2. For any new milestone or multi-file change, **propose a plan first** and wait for approval.
3. Work in small steps; keep the app runnable (and the marketing pages exportable in Phases 0–1).
4. Write or update tests with every change. Payments, pricing, policies, order status and
   enrolment status need tests for both the allowed and the denied path.
5. Before saying a task is done run `./vendor/bin/pint --test && php artisan test`
   (Sail: `sail artisan test`); run Playwright for user-journey changes.
6. Never edit the database by hand; every change is a migration (plus a seeder if data is needed).
7. If the spec is unclear or seems wrong, ask. Don't guess on money, access, privacy or academic-integrity wording.
8. Record notable technical decisions in `docs/decisions.md` (one short entry each).
9. Commit messages: `M6 ACA-6: admin-configurable order statuses`.
10. When unsure how a Laravel, Filament, Livewire or LiveKit API works today, check the official docs rather than memory.

## Definition of done

- Requirement IDs in scope are implemented and covered by tests.
- Pint, Pest and Playwright pass.
- Works on a 360 px-wide phone screen and on desktop.
- No secrets, console errors or TODOs left in the changed code.
- `.env.example`, `docs/spec.md` (if behaviour changed) and `docs/decisions.md` are updated.
