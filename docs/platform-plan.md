# Proposal: three verticals and partner businesses on one platform

Status: **proposal, awaiting the owner's decision** (2 Nov 2026). Nothing here changes
the app yet. Once the owner picks an option, the chosen parts move into `docs/spec.md`,
`CLAUDE.md` and `docs/decisions.md`, and the build starts milestone by milestone.

## The short answer

- **Yes, it is possible**, and the current code is a good base: tuition and academic are
  already separate sections with their own pages, WhatsApp numbers, leads and money
  reports. Adding music as a third section is a small, safe change. Letting *other*
  businesses run on the platform is a much bigger change that touches every table,
  every permission check, payments, email, legal terms and GST.
- **Recommended:** add the three sections (verticals) and domain-readiness now, keep
  running your own business on it, and switch on partner businesses later, when at least
  two or three real businesses say they would pay for it. This keeps your monthly cost
  the same today.
- **Running cost:** three sections on one domain cost nothing extra. Each extra domain is
  about ₹1,000 a year. Partner businesses cost little in hosting at first (the same
  ₹1,050 server holds several small ones), but they bring real one-off costs: a lawyer
  for platform terms, a CA for GST on platform fees, and build time.

## 1. What to call these business owners

Recommended name: **Partner** (their business is a *partner institute*).

| Name | Why or why not |
|---|---|
| **Partner** (recommended) | Short, respectful, and normal in Indian edtech ("partner institutes"). Works for a solo music teacher, a coaching centre or an editing agency. Says they run their own business with you, not for you. |
| Entrepreneur | Good in marketing copy ("Start your own tuition business"), but many will be existing centres, not new founders, and it is awkward as a login role. |
| Institute / Academy | Fits tuition and music, not an academic-editing service. Use it for the *business*, not the person. |
| Operator / Tenant | Technical words. We use `Tenant` inside the code only. |

Other names that follow from this:
- **You** become the **Platform owner** (one super-admin above all partners), and your own
  business becomes **partner no. 1** on the platform.
- A partner's own staff keep today's roles: Admin (now "Partner admin"), Teacher/expert,
  Parent, Student, Academic student, plus a new **Music learner** (adult) case.

## 2. Is the change possible now?

| Part | Possible now? | Size | Risk |
|---|---|---|---|
| Music as a third section | Yes | Small to medium (reuses tuition batches) | Low. Needs a few rules for 1-to-1 lessons and adult learners. |
| Each section on its own domain later | Yes | Small (the app chooses the section from the web address) | Low. Main cost is SEO moves if a section changes domain after it ranks. |
| Other businesses (partners) on the platform | Yes, technically | Large (every table and permission, Filament panels, files, email, payments) | Medium to high: one missed filter could show one partner's students to another. Needs a lawyer and a CA. |
| Partners collecting their own payments with their own GST | Yes, after your own Razorpay checkout is live | Medium | Money and GST rules must be confirmed with Razorpay and a CA first. |

Why wait for partners: your own payments are not live yet (GST pending), you have no
paying partner yet, and building it now would delay the four open threads (SEO, Google
Analytics, the social campaign and the explainer videos) by roughly two to three months.

## 3. Updated requirements (new IDs)

These would be added to `docs/spec.md` when approved. Existing IDs stay as they are.

### Verticals (VRT): tuition, academic, music

- **VRT-1** The platform has three verticals: *School tuition* (classes 1 to 12),
  *Academic services* and *Music lessons*. Each vertical can be switched on or off
  (`FEATURE_VERTICAL_*`).
- **VRT-2** Each vertical has its own landing page, CTAs, WhatsApp number and message,
  enquiry form, campaign pages, FAQs and price list, all editable in `/admin`.
- **VRT-3** A vertical can be served on its own domain (for example
  `tuition.example.in` or a separate brand) or as a path (`/music`) on the main domain.
  The domain-to-vertical map is configuration, not code. Moving a vertical to a new
  domain sends permanent (301) redirects from the old addresses.
- **VRT-4** Logins, dashboards, chat, notices, leads, payments and the money report all
  carry the vertical, and reports can be filtered by it.
- **VRT-5** Brand per vertical: name, logo, colours and footer text can differ per
  vertical, so "Indian Assignments" need not appear on the tuition or music site.

### Music lessons (MUS)

- **MUS-1** Instruments and singing styles are admin-managed data (for example Hindustani
  vocal, Western vocal, guitar, keyboard, tabla, violin), each with levels (Beginner,
  Intermediate, Advanced) and optional exam tracks (for example Trinity, ABRSM,
  Gandharva Mahavidyalaya). Never hard-coded.
- **MUS-2** Lessons are either **group batches** (as in tuition) or **1-to-1 slots**, priced
  monthly per student, with a trial lesson option.
- **MUS-3** Learners can be **minors** (signed up by a parent, exactly like school
  students, with consent) or **adults** (18+ self sign-up, like academic students, with
  their own consent).
- **MUS-4** Safeguarding for 1-to-1 lessons with a minor: the parent is always in the
  chat (CHAT-4 applies), lessons are scheduled on the platform, and the parent can see
  every lesson and join link. Recording 1-to-1 lessons with minors needs the parent's
  explicit consent. *Owner decision needed:* whether a parent or guardian must be present
  for under-13 1-to-1 lessons.
- **MUS-5** Practice log and teacher feedback per lesson (short notes, optional audio
  clip uploaded to private storage). Phase 3 or later.
- **MUS-6** Live audio for music needs "original sound" (noise suppression and echo
  cancellation off, higher audio quality). Google Meet handles speech well but can
  damage music; in Phase 3 the LiveKit room offers a music mode. Until then, teachers
  are advised which Meet audio settings to turn off.

### Partners (TEN), switched on later

- **TEN-1** A partner is a separate business on the platform with its own name, brand,
  contact details, verticals, price lists, teachers, students, orders, chat and reports.
  Nothing is shared between partners.
- **TEN-2** Each partner is reached by its own subdomain (`name.platformdomain.in`) or
  its own domain (with HTTPS set up automatically).
- **TEN-3** The Platform owner can create, suspend and close partners, see platform-wide
  totals (counts and fees, not personal data), and sign in as a partner admin only with
  the partner's permission and an audit log entry.
- **TEN-4** Partner sign-up: business details, GSTIN (optional at first, required before
  online payments), PAN, bank or Razorpay details, acceptance of the platform terms and
  the data processing agreement.
- **TEN-5** Data protection (DPDP Act): each partner is the *data fiduciary* for its own
  students; the platform is its *data processor*. Partner data is exportable and deleted
  on request when a partner leaves.
- **TEN-6** Per-partner limits: storage, emails a day and number of active students,
  according to the partner's plan.
- **TEN-7** Platform fee to partners: monthly plan and/or a percentage of collections,
  with GST invoices from the platform owner to the partner.

### Payments for partners (PAY-7 onwards), switched on later

- **PAY-7** Each partner collects fees **into its own account under its own GST
  registration**. The platform never holds a partner's customer money for longer than the
  payment provider requires.
- **PAY-8** Two supported set-ups, to be confirmed with Razorpay and a CA before building:
  (a) **partner's own Razorpay account** connected to the platform (preferred: money
  goes straight to the partner, the partner issues its own GST invoices, and the platform
  bills its fee separately); or (b) **Razorpay Route**, where payments come to the
  platform's account and are split to the partner's linked account, which can make the
  platform responsible for more GST and compliance work.
- **PAY-9** Each partner's keys and webhook secrets are stored encrypted, per partner,
  and every webhook is matched to exactly one partner before it is trusted. All existing
  PAY rules (paise, server-side totals, signature checks, idempotent webhooks) apply per
  partner.
- **PAY-10** Receipts and invoices show the partner's legal name, address and GSTIN, not
  the platform's.

## 4. Architecture changes

### Now (verticals and domains)

- **Vertical as data, not just an enum.** Today `App\Enums\Offering` has Tuition,
  Academic and Both, stored as a short string on about 8 tables. Add `Music` and replace
  "Both" with a teacher-to-verticals link, so a teacher can teach any mix. Existing rows
  keep their values, so this is a simple migration.
- **Domain routing.** A small middleware reads the web address and picks the vertical and
  brand from `config/verticals.php` (domains from `.env`). On one domain, paths decide;
  on separate domains, the host does. Logins stay on one main domain for now
  (`app.` subdomain or the current domain) so people don't need two accounts.
- **Music reuses tuition.** Batches, class sessions, enrolments, Meet links, fees and
  payouts work as today, with a `kind` (group or one-to-one) and an optional instrument
  and level instead of class and board.
- **No new paid service and no new package.** Cloudflare handles extra domains for free;
  the same server, database, R2 buckets and Brevo account are used.

### Later (partners)

- **One database, one app, a `tenant_id` on every business table** (recommended for our
  size). A global scope adds "where tenant is the current partner" to every query, and a
  test checks that every model has it. Your existing data becomes tenant no. 1 with a
  migration, so nothing is retyped. Separate databases per partner cost more to run and
  back up, and are not worth it below roughly 50 partners.
- **Finding the partner:** from the domain or subdomain, before anything else runs.
  Sessions and logins are per partner: the same email can be a parent at two partners
  with two separate accounts.
- **Filament** has multi-tenancy built in (a tenant model on the admin panel), so each
  partner admin sees only their partner's screens. A second, separate panel
  (`/platform`) is for the Platform owner only.
- **Files** go under a per-partner folder in the same private R2 bucket; signed download
  links still check the policy first.
- **Email** is sent from the platform's verified domain with the partner's name as the
  sender name and the partner's email as reply-to. A partner with its own domain can
  verify it with Brevo later.
- **Payments:** `PaymentGateway` gets a per-partner configuration (keys or connected
  account id) instead of one set of `.env` keys. The webhook URL includes the partner, and
  the signature is checked with that partner's secret.
- **Custom domains:** a few can be added by hand (Cloudflare DNS plus the server's HTTPS
  certificate). At more than about 20 partners, Cloudflare's custom-hostname feature for
  SaaS can automate HTTPS (check its current free allowance at that time).
- **Backups** stay one encrypted database backup a night; a per-partner export command is
  added for TEN-5.

### Rules that change in `CLAUDE.md` (when approved)

- Add the Music vertical and the Partner role to "What this is".
- Access control: "every query is scoped to the current partner **and** the user".
- Payments: "keys come from the current partner's settings, never from another partner".
- Tests: every policy test runs a "user from another partner is denied" case.

## 5. Project plan

### Option B (recommended): verticals now, partners later

| Step | What | When | Rough size* |
|---|---|---|---|
| Keep going | The four open threads (SEO, Google Analytics, social campaign, explainer videos) continue, with the small adjustments in section 7 | Now | as planned |
| **V1** Verticals and domains | Music in the enum and admin, teacher-to-vertical link, per-vertical brand, domain map, 301 redirect support, tests (VRT-1 to 5) | Next | 1 to 2 weeks |
| **V2** Music lessons | Instruments and levels, group and 1-to-1 batches, trial lesson, adult learner sign-up, music landing page and FAQs, safeguarding rules (MUS-1 to 4, MUS-6 advice) | After V1 | 2 to 3 weeks |
| GST and Razorpay | Your own online checkout (M9's remaining part) | When GST is done | as planned |
| **Gate P** | Start partner work only when 2 to 3 businesses agree to try it and a CA and lawyer have answered the questions in section 8 | Later | — |
| **T1** Partner foundation | `tenant_id` everywhere, current-partner scope, Filament tenancy, isolation tests on every policy (TEN-1, TEN-2) | After Gate P | 3 to 4 weeks |
| **T2** Partner onboarding | Sign-up, settings, branding, subdomains, platform-owner panel, limits, data export (TEN-3 to 6) | After T1 | 2 weeks |
| **T3** Partner payments | Per-partner Razorpay, webhooks per partner, partner receipts and invoices (PAY-7 to 10) | After your own checkout is proven | 2 weeks |
| **T4** Platform billing | Plans, platform fee invoices with GST, suspend for non-payment (TEN-7) | After T3 | 1 to 2 weeks |
| Pilot | One friendly partner for a month before opening to others | — | — |

\*Planning guesses for part-time work with Claude, in the same style as the spec's
milestone estimates, not commitments.

### Other options

- **Option A, minimum:** add music as a third section only, no domain work and no partner
  plans. About 2 weeks. Fine if you're unsure about separate domains or partners.
- **Option C, everything now:** build V1, V2 and T1 to T4 before the open threads.
  About 3 months before your own marketing resumes, with no partner yet to test it on.
  Not recommended.

## 6. Running cost

Rupee figures use about ₹88 per US dollar, as in the spec.

| Item | Today | Option B (verticals) | Partners later, first 5 to 10 small partners | Partners at 30+ |
|---|---|---|---|---|
| Server (Lightsail Mumbai) | $12 ≈ ₹1,050 | Same | Same, or $24 ≈ ₹2,100 if busy | $24 to $44 ≈ ₹2,100 to ₹3,900 |
| Domains | ≈ ₹85/month | +₹85/month for each extra domain (only if you split) | Partners pay for their own domains | Same |
| Email (Brevo) | Free (300 a day) | Free | Free limit is shared by everyone, so a paid plan is likely (check Brevo's current price) | Paid plan |
| File storage (R2) | Free within 10 GB | Free | Free at first | A few hundred rupees a month |
| HTTPS for partner domains | — | — | Free (Let's Encrypt or Cloudflare) | Free up to Cloudflare's allowance, then a small fee per domain |
| Payment fees | 2.36% of your collections | Same | Partners pay their own gateway fees; Route, if used, may add a fee | Same |
| **Monthly total** | **≈ ₹1,150** | **≈ ₹1,150** (+₹85 per extra domain) | **≈ ₹1,150 to ₹3,000** | **≈ ₹3,000 to ₹6,000** |

One-off and people costs that matter more than hosting:
- Lawyer: platform terms, partner agreement, data processing agreement, privacy policy
  per partner (needed before the first partner).
- CA: GST on your platform fee, and whether the payment set-up makes you an "e-commerce
  operator" with extra GST duties.
- Support time: partners will ask questions; plan for it in your platform fee.
- Build time: section 5.

## 7. What this means for the four open threads

None of them needs to stop. Only the brand and domain question touches them.

- **SEO ("Improve Google search ranking"):** keep going. If tuition or music might move to
  its own domain within a year, decide the domain *before* investing in tuition-specific
  SEO, because moving a page that already ranks needs redirects and costs some ranking
  for a few weeks. Academic SEO on indianassignments.com is safe.
- **Google Analytics:** keep going. Add a separate data stream later for any new domain.
- **Social media campaign:** keep going. Use the existing campaign links (`/c/{slug}`),
  which can be pointed at a new domain later without changing the posts.
- **Explainer videos:** keep going. Avoid saying "Indian Assignments" in the tuition
  videos if tuition might get its own brand; a music video can be added after V2.

## 8. Decisions needed from the owner

1. **Which option:** B (recommended), A or C.
2. **Brands and domains:** keep everything on indianassignments.com under `/tuition`,
   `/academic` and `/music` for now, or choose separate brand names now? (Recommended:
   one domain now, split later only when a brand needs it.)
3. **Music details:** which instruments and styles to start with, group or 1-to-1 or both,
   and the under-13 parent-present rule (MUS-4).
4. **Partner name:** "Partner" (recommended) or another word.
5. **Before Gate P, ask a CA and a lawyer:** GST on platform fees, own-account vs Route,
   the partner agreement and the data processing agreement.
