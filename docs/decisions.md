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

## 2026-10-27 · Logo file and WhatsApp numbers per section

- **Logo.** The owner chose a line-drawn diya with no background and asked for an image-only logo. `public/logo.svg` (same drawing as `favicon.svg`) is the header logo as an `<img>`; the site name is no longer shown beside it but stays in the link's label, the page title and the footer. `apple-touch-icon.png` puts the diya on white, because iOS fills transparent areas with black.
- **WhatsApp numbers.** `WHATSAPP_NUMBER_TUITION` and `WHATSAPP_NUMBER_ACADEMIC` send each section's chats to its own number; general pages and any section left blank use `WHATSAPP_NUMBER`. The numbers are public on the site, so `.env.example` carries the owner's numbers as defaults, and GitHub Actions variables of the same names override them.

## 2026-10-27 · Owner's "Indian Assignments" logo

- The owner supplied their own logo. `public/images/brand/indian-assignments-logo.svg` (book, pencil and graduation cap with the words "Indian Assignments") replaces the diya in the header. `indian-assignments-icon.svg` (no text) is kept beside it for small places such as the footer. The PNG versions they sent are stored in `resources/brand/` and are not published.
- The colours (#075B45 green, #C9570B orange) are close to the Calm Scholar tokens, so the theme is unchanged. The favicon is now the icon too: `favicon.svg` is `indian-assignments-icon.svg` with a square viewBox, and `favicon.ico` (16/32/48) and `apple-touch-icon.png` (180, on white) are rendered from it.

## 2026-10-27 · M3 accounts, roles and the admin panel

- **Testing machine.** The cloud sandbox has PHP 8.3 only; M3 onwards is tested there in a PHP 8.4 container (Ubuntu 25.10 packages), against SQLite and PostgreSQL. CI gains a second job that runs Pest on PostgreSQL 18.
- **Roles.** One `users` table with a `role` enum (`App\Enums\Role`) and a `status` (`active`/`deactivated`). Role, status and lockout columns are never mass-assignable; they are set with `forceFill()` where an account is made. Profiles live in `parents` (model `ParentProfile`, because `parent` is reserved in PHP), `students`, `academic_students` and `teachers`.
- **School student logins.** A child logs in with a username their parent chooses (many children have no email). `users.email` is nullable; a student counts as verified because the parent's email is. Students have no profile or delete-account page; their parent manages them.
- **Login.** Fortify's `authenticateUsing` accepts an email or a username, locks an account for 15 minutes after 5 wrong passwords in a row, and refuses deactivated accounts. A deactivated user who is already logged in is logged out on the next request. Fortify's own per-minute login throttle stays. Sign-up, forgot-password, reset and invitation forms are limited to 5 posts a minute per IP.
- **Consent.** `consents` stores the purpose, policy version (`CONSENT_POLICY_VERSION`, `draft-2026-10` until the lawyer approves the wording) and time. Withdrawing a child's consent switches the child's login off; giving it again switches it on.
- **Teacher invitations.** The admin invites from People; the teacher gets a signed link valid 7 days and sets a password. The link works once because it only applies to an account with no password.
- **Admin panel.** Filament 5 at `/admin`, admins only (`canAccessPanel`). There is no Filament login page: admins use `/login`, so the lockout applies. Accounts are viewed, deactivated, reactivated and unlocked, never edited or deleted, and each action is written to `audit_logs`. Avatars are drawn locally instead of Filament's default ui-avatars.com. The first admin is made with `php artisan app:make-admin`.
- **Look.** The portal and admin use the Calm Scholar greens, Source Sans 3 / Source Serif 4 (now in `resources/css/fonts.css`, shared with the public site) and the Indian Assignments logo. The starter kit's forced dark mode and Bunny Fonts request are gone.
- **Portal pages** follow the starter kit's Livewire single-file components in `resources/views/pages/` rather than classes in `app/Livewire/`.
- **Sail.** `compose.yaml` runs PHP 8.4, PostgreSQL 18 and Mailpit. `.env.example` keeps SQLite for Phases 0–1 and lists the Sail values; steps for the owner are in `docs/portal.md`.

## 2026-10-27 · M4 website editing and lead tracking

- **Two content sources.** `App\Support\SiteContent` reads page text from `resources/content/*.php` while `FEATURE_CMS` is off (the static Phase 0 site keeps working) and from the `site_sections` table when it is on, falling back to the file for a page not yet in the database. `php artisan db:seed` (`ContentSeeder`) copies the files into the database once and never overwrites edits.
- **Page content as JSON.** Each page (home, tuition, academic) is one `site_sections` row with a JSON `content` column, edited by a tabbed Filament form. This keeps the page layouts unchanged; the academic price list moves into proper tables in M6, when orders need it.
- **Money in the admin.** Prices are typed in whole rupees and stored in paise (`App\Filament\Support\MoneyInput`).
- **Leads.** With `FEATURE_CMS` on, the "Enquire", "Book a demo" and waitlist buttons go to the site's own form (`/enquire/{form}`) instead of Google Forms. `CaptureUtm` keeps the first UTM tags of the visit in the session (or the referring site, or "direct") and each lead stores them. Forms are limited to 5 posts a minute per IP and have a hidden honeypot field. A new lead emails the contact address.
- **Campaign pages** at `/c/{slug}` carry their own form; a lead from one records the campaign even without UTM tags.
- **Marketing list.** The CSV export holds only leads who ticked the news-and-offers box, and each download is audited.
- **Audit.** Admin edits to pages, guides, testimonials, campaigns and teachers are logged by the `AuditAdminChanges` observer with the names of the changed fields, not their values.

## 2026-10-27 · M5 batches, timetables and enrolment (payments held back)

- **No payments yet.** The owner's GST registration is in progress, so no Razorpay code. An enrolment waits as "pending" until the admin presses "Fee received" (after a Payment Link, UPI or cash). That button calls `App\Actions\Enrolments\ActivateEnrolment`, which the payments module will call after verifying the signature or webhook (PAY-2). `FEATURE_BATCHES` switches M5 on.
- **Classes table.** Scheduled classes live in `class_sessions` (model `ClassSession`) because Laravel's login sessions use `sessions`.
- **Timetable.** A batch's weekly slots are stored in India time as JSON (`day`, `start`, `minutes`). `app:schedule-classes` runs daily and keeps four weeks of classes; each class remembers its slot in `scheduled_for`, so a moved or cancelled class isn't added again. Changing a batch's timetable replaces only future classes that came from the old timetable and weren't changed by hand.
- **Access.** A child may join when the enrolment is active and `paid_through` plus 3 grace days is today or later in India (PAY-4). `app:check-enrolments` runs daily: reminders 5 and 1 days before, then "expired" after the grace period. Seats are held by pending and active enrolments; the batch row is locked while a seat is taken.
- **Join.** The Meet link never appears on the page. Join goes to `/classes/{id}/join`, which checks the role, batch, access and the time window (10 minutes before to the end) and then redirects. The teacher's first click records `started_at`, for the payout report later.
- **Demo data.** `DemoSeeder` makes a parent, child, teacher and two batches for trying the portal and for the Playwright tests. It refuses to run in production.

## 2026-10-27 · M6 academic orders (payments held back)

- **No payments yet** (GST registration in progress). An order waits in the first status ("Awaiting payment") until the admin presses "Payment received", which calls `App\Actions\Orders\MarkOrderPaid`; the payments module will call the same action after verifying the gateway (PAY-2). `ChangeOrderStatus` refuses to move an unpaid order anywhere except the first status or a final one (Cancelled). `FEATURE_ACADEMIC_ORDERS` switches M6 on.
- **Price list.** `services` holds one row per service with a price column per level (Bachelors, Masters, PhD; empty = quoted), which matches the public price table and keeps the admin form to one screen. `service_addons` (printing, courier, express, other; per copy or per order) are linked to the services that offer them. `AcademicCatalogueSeeder` copies both from the M4 content once. With orders on, the public page reads them from these tables and the CMS page form hides its old price list.
- **Pricing.** `App\Services\Pricing`: base for the level + add-ons × quantity (per-order add-ons count once, copies capped at 20) + GST at `ACADEMIC_GST_PERCENT`, rounded to the nearest paisa. Quotes are the admin's price plus the order's add-ons plus GST. The order keeps a copy of each add-on's name and price.
- **GST default.** 18% added on top of the listed prices, as the spec's "base + add-ons + GST". To be confirmed by the owner's accountant, including whether listed prices should include GST.
- **Statuses.** Admin-managed (`order_statuses`) with flags instead of names in code: `notify_student`, `is_final`, `expert_can_set`, `is_revision`, `needs_tracking`. The first by sort order is where new orders start; "after payment" is the next non-final one.
- **Files.** Private disk from `ORDER_FILES_DISK` (local now; `r2` in production, see M9). PDF and DOCX only, checked from the file's content by Livewire, up to 25 MB (the server's PHP upload limit must allow it), 5 per upload. Downloads go through a 10-minute signed link and a policy check. When an order reaches a final status its files get a delete-after date (`ORDER_FILE_RETENTION_DAYS`, default 180) and `app:prune-order-files` deletes them daily.
- **Experts** see only orders assigned to them, upload drafts and finals, and set only statuses marked "experts may set" once the order is paid. Students get one revision request per free revision, after a draft or final is uploaded.

## 2026-10-27 · M7 chat, notices and marketing emails

- **No Reverb yet.** The chat page polls every 5 seconds with Livewire (`wire:poll`) and the list every 30. That is "near real time without a paid service" (CHAT-3) at our size. Reverb can replace polling later without changing the data. `FEATURE_CHAT` switches M7 on.
- **Who sees what.** `conversation_user` holds members (the people a conversation belongs to) and read positions. Admins see everything. Teachers and experts see conversations assigned to them or linked to their batches (`Conversation::visibleTo`). Only parents, school students and academic students can start one. A school student's parent is always a member (CHAT-4), so teachers and children never have a private channel.
- **Read receipts and unread counts** come from each person's `last_read_at`; opening a conversation marks it read. "Seen" means someone other than the sender has read up to that message.
- **Unread emails.** Each message queues `EmailUnreadMessage` for 15 minutes later. It emails each member, and the assignee, who still hasn't read it, once per unread stretch (`notified_at`). The email doesn't quote the message.
- **Attachments** use the same private disk as order files. Images, PDF and DOCX up to 10 MB, checked from the content, downloaded through a 10-minute signed link after a policy check.
- **Limits.** 10 messages a minute per person. Reported messages are flagged in the admin inbox, which shows a red count.
- **Notices** (NOT-2) are emailed on publish and shown on the parent's and academic student's home page for 60 days. A published notice can't be edited.
- **Marketing emails** (NOT-3) go only to users who ticked the opt-in at sign-up (stored in `users.marketing_opt_in_at`, unticked by default) or leads who ticked it on a form. Each email has a signed one-click unsubscribe link plus `List-Unsubscribe` and `List-Unsubscribe-Post` headers. The unsubscribe route accepts the mail app's POST without a CSRF token, and the signature protects it.

## 2026-10-27 · M8 reports and payouts (payments still by hand)

- **Money received is recorded.** `payments` holds every fee and order payment with its method, reference and gateway fee. "Fee received" and "Payment received" now ask for these, and the payments module will write the same rows with Razorpay's fee after verifying a payment (PAY-2). Printing, courier and other costs go in `expenses`.
- **Pay rates** sit on `teachers`: `class_rate` per class or per hour, and `order_rate` as a fixed amount (paise) or a whole percentage of the order's service price (subtotal without GST and add-ons).
- **Which classes count** (ADM-2): not cancelled, started (the teacher's first Join click, or a time the admin enters), and at least 30 minutes from the start to the recorded end or, without one, the scheduled end. Google Meet doesn't tell us when a class really ended. Hourly pay uses the scheduled length. Months are India months.
- **Which orders count** (ACA-10): paid, completed in the month, and not in a status marked "means cancelled" (a new `is_cancelled` flag on order statuses).
- **Payouts** are snapshots: "Mark paid" stores the classes, orders and amounts as calculated then, so later corrections don't change a paid month. In the money report, class pay counts to Tuition and order pay to Academic, on the date paid.
- **Active and past people** (ADM-5): an active account and still with us: teachers without a past end date, school students and their parents with an active or pending place, academic students with an order still open.
- **Times in tests** must be saved in UTC: Eloquent writes a Carbon's wall-clock time without converting its timezone.

## 2026-10-27 · Hero illustrations
Home, Tuition and Academic heroes show original flat SVG illustrations (public/images/illustrations, about 5 KB each) drawn for this site in the Calm Scholar colours. They belong to the business outright, so there is no licence to track, and they add almost nothing to page weight on 4G. Real photos can be added later by the owner (Unsplash/Pexels licence, WebP, lazy-loaded below the fold); stock photos are never captioned as our students or teachers.

## 2026-10-28 · M9 file storage, backups and the Razorpay library

- **Three free libraries**, agreed with the owner: `league/flysystem-aws-s3-v3` (Laravel's S3 driver, which Cloudflare R2 speaks), `spatie/laravel-backup` and `razorpay/razorpay` (the official PHP SDK).
- **R2 disks.** `r2` holds order files and chat attachments (`ORDER_FILES_DISK=r2` in production); `r2-backups` holds backups in a separate bucket, so a mistake on one can't touch the other. Both are private and throw on errors. Keys are `R2_*` in `.env`.
- **Backups** are the database only: files already live on R2 and the code is in GitHub. `backup:run --only-db` runs at 02:00 India time, `backup:clean` at 01:30 and `backup:monitor` at 08:00. Each zip is AES-256 encrypted with `BACKUP_ARCHIVE_PASSWORD`, kept 30 days, and stored on `BACKUP_DISK` (`r2-backups` in production). Failures and stale backups email `BACKUP_NOTIFY_EMAIL`, falling back to `CONTACT_EMAIL`. The server needs `pg_dump` (the `postgresql-client` package) and a running scheduler (`schedule:run` every minute). Keep the archive password outside the server too, or the backups can't be opened.
- **Razorpay** is installed and its keys (`RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`) are read through `config/services.php`. Checkout, signature checks and the webhook wait until GST registration is done.

## 2026-10-29 · M9 server set-up

- **Scripts, not hand steps.** `deploy/provision.sh` sets up the Lightsail server once and `deploy/deploy.sh` deploys `main`; both are in the repo, so the server can be rebuilt from scratch. See `docs/server.md`.
- **One user.** PHP-FPM, the queue worker, the scheduler and deploys all run as `www-data`, so log and cache files never end up owned by the wrong user.
- **Build on the server.** Composer and Vite run on the server during a deploy (a 2 GB swap file covers the memory). There's no separate build server or artefact store to pay for.
- **Behind Cloudflare.** Only Cloudflare's address ranges (`App\Support\Cloudflare`) are trusted proxies, so the visitor's IP (used for rate limits) and HTTPS come through, and someone reaching the server directly can't fake them.
