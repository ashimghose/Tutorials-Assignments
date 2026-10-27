# Trying the Phase 2 portal on your laptop

The logins, portal and admin panel are built behind `FEATURE_ACCOUNTS`, website
editing and on-site enquiry forms behind `FEATURE_CMS`, and tuition batches and
enrolments behind `FEATURE_BATCHES`. They stay
switched off on the live site (indianassignments.com) until M9. To try them on your
laptop you need **Docker Desktop** (free for a business your size) once.

## First time

```bash
git pull
composer install
npm install && npm run build
cp .env .env.backup            # keep your Phase 0 settings
```

In `.env`, change these lines:

```
FEATURE_ACCOUNTS=true
FEATURE_CMS=true
FEATURE_BATCHES=true
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
APP_URL=http://localhost
```

Then start everything and create the database tables and your admin login:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed        # copies today's website text into the database
./vendor/bin/sail artisan app:make-admin
./vendor/bin/sail artisan db:seed --class=DemoSeeder   # optional: made-up parent, child, teacher and batches
```

The demo data's logins all use the password `password`: `parent@example.com`,
`teacher@example.com`, and the child logs in with the username `riyademo`. It has a
Maths class running "now" each time you run the seeder, so you can try Join. Never run
it on the live server (it refuses to).

## What to click through (M3)

| Where | What to try |
| --- | --- |
| http://localhost/register | Sign up as a parent. The email lands in Mailpit at http://localhost:8025; click the link. |
| http://localhost/parent/children | Add a child with a username and password, then log out and log in as the child with that username. |
| http://localhost/register?as=academic | Sign up as an academic student; leave "I am 18 or older" unticked to see the message. |
| http://localhost/admin | Log in as the admin. Under People, invite a teacher; the invitation email is in Mailpit. |
| http://localhost/login | Type a wrong password 5 times: the account locks for 15 minutes. Unlock it from People. |

## What to click through (M4): changing the website yourself

| Where | What to try |
| --- | --- |
| Admin → Website → Pages | Edit the Home, Tuition or Academic page: headlines, fee plans, the academic price list (in rupees), FAQs. Save, then click "View on site". |
| Admin → Website → Pages → Home | Type a notice (for example "Admissions open for April"); it shows across the top of every page. Empty it to hide it. |
| Admin → Website → Guides | Write a guide in Markdown. It goes live at the publish date you set; leave the date empty to keep it as a draft. |
| Admin → Website → Testimonials, Teachers and experts | Add a parent's or student's quote; switch on "Show on the website" for a teacher to list them on the section page. |
| Admin → Marketing → Campaign pages | Make a page for an offer, for example `/c/diwali-offer`, and use that link in an advert. |
| http://localhost/tuition?utm_source=instagram | Click "Book a free demo class" and send the form. The lead appears under Admin → Marketing → Leads with "instagram" as its source. |
| Admin → Marketing → Leads | Mark a lead as contacted or converted; the dashboard counts leads and customers by source. "Download marketing list" gives only people who agreed to news. |

Every change made in the admin is recorded under Audit log.

## What to click through (M5): batches, timetables and enrolment

Online fee payment is not built yet (it waits for GST registration). Until then you
send the fee link yourself, as now, and press "Fee received" in the admin.

| Where | What to try |
| --- | --- |
| Admin → Tuition → Batches → New | Make a batch: subject, class, board, teacher, weekly times (India time), seats, monthly fee and the Google Meet link. Four weeks of classes are added at once, and kept topped up every morning. |
| A batch → Classes | Move or cancel one class; the teacher and parents get an email. "Add an extra class" for a one-off. |
| http://localhost/parent/classes | As a parent, "Ask for a place" for your child. The admins get an email. |
| Admin → Tuition → Enrolments | The request shows as "Waiting for fee". After the money arrives, choose "Fee received" and the paid-until date. The parent gets an email and the child can join. |
| http://localhost/student | As the child: the timetable for two weeks. Join opens 10 minutes before each class and opens the Meet link. |
| http://localhost/teacher | As the teacher: their classes and the students in each batch. |

Every morning the site emails fee reminders 5 days and 1 day before the paid-until
date, and pauses a child's access 3 days after it. On the live server this needs the
scheduler (`php artisan schedule:run` every minute), set up in M9.

## Stopping

```bash
./vendor/bin/sail down          # stop (data is kept)
```

To go back to Phase 0 work without Docker, restore `.env.backup`.
