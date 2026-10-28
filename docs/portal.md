# Trying the Phase 2 portal on your laptop

The logins, portal and admin panel are built behind `FEATURE_ACCOUNTS`, website
editing and on-site enquiry forms behind `FEATURE_CMS`, tuition batches and
enrolments behind `FEATURE_BATCHES`, academic orders behind `FEATURE_ACADEMIC_ORDERS`, and messages, notices and marketing emails behind `FEATURE_CHAT`. They stay
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
FEATURE_ACADEMIC_ORDERS=true
FEATURE_CHAT=true
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
./vendor/bin/sail artisan db:seed        # copies today's website text and price list into the database
./vendor/bin/sail artisan app:make-admin
./vendor/bin/sail artisan db:seed --class=DemoSeeder   # optional: made-up parent, child, teacher and batches
```

The demo data's logins all use the password `password`: `parent@example.com`,
`teacher@example.com`, `student@example.com` (academic student), `expert@example.com`
(academic expert), and the child logs in with the username `riyademo`. It has a
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

## What to click through (M6): academic orders

Online payment is not built yet. You send the payment link yourself and press
"Payment received" in the admin. Prices show GST at `ACADEMIC_GST_PERCENT` (18 until
your accountant says otherwise).

| Where | What to try |
| --- | --- |
| Admin → Academic → Services, Add-ons | The price list from the website, now one screen per service: prices per level in rupees (empty = quote), free revisions, and which add-ons each service offers. |
| Admin → Academic → Order statuses | The status list. Drag to reorder; tick which ones email the student, which are final, which experts may set, and which asks for a tracking number. |
| http://localhost/my-orders/new | As `student@example.com`: choose a service and level, add copies or courier (the address form appears), upload a PDF, and see the itemised total with GST. Tick "send me a quote instead" for a large job. |
| Admin → Academic → Orders | Open the order: "Payment received" moves it on; "Send quote" prices a quote request; "More → Assign expert" emails the expert; "Change status" (Shipped asks for the tracking number). |
| http://localhost/teacher/orders | As `expert@example.com` after you assign them: read the brief, upload a draft, set "Draft shared". |
| The student's order page | Download the draft, ask for a revision, and see the timeline. |

Order files are private. Each download link works for 10 minutes and checks who is
asking. Files are deleted 180 days after the order is finished or cancelled.

## What to click through (M7): messages, notices and marketing emails

Messages refresh every few seconds on their own. There is no paid real-time
service; this is enough for a few dozen conversations.

| Where | What to try |
| --- | --- |
| http://localhost/messages | As `riyademo` (the child): "New message". The parent is added automatically, so there is no private chat between a teacher and a child. |
| The same page as `parent@example.com` | The child's message is there. Reply, attach a PDF or photo, or report a message. Your own last message shows "Seen" once someone has read it. |
| Admin → Messages → Inbox | Every conversation with unread and reported counts. Click one to reply, assign it to a teacher or expert, or close it. |
| `teacher@example.com` → Messages | Only conversations assigned to them or about their own batches. Teachers can reply but can't start a conversation. |
| `student@example.com` → Messages | Start one about an order. It goes straight to the order's expert as well as you. |
| Admin → Messages → Notices | Write a notice for all parents, one batch's parents, or all academic students, then "Publish". It is emailed and shows on their home page. |
| Admin → Marketing → Marketing emails | Write a newsletter and "Send". Only people who ticked "news and offers" (at sign-up or on an enquiry form) get it, each with a one-click unsubscribe link. |

Outside office hours (`CHAT_OFFICE_*` in `.env`, Monday to Saturday 10:00 to 19:00
India time by default) the chat shows when you'll reply and posts one automatic
note. Anyone who hasn't read a new message after 15 minutes gets an email. The email
doesn't include the message itself. Emails go to Mailpit at http://localhost:8025.

## What to click through (M8): reports and teacher payouts

| Where | What to try |
| --- | --- |
| Admin → Teachers and experts → edit | Under "Pay": a rate per class or per hour, and per academic order (a fixed amount or a % of the service price). |
| Admin → Batches → a batch → Classes → "Class taken" | Enter the start time when a teacher forgot to press Join, or the end time if a class ended early. A class counts for pay when it started and ran at least 30 minutes. |
| Admin → Tuition → Enrolments → "Fee received", and an order's "Payment received" | Now also ask how much arrived, how, the reference and any Razorpay fee. That is what the money report adds up. |
| An order → More → "Add printing or courier cost", or Admin → Reports → Costs | Printing, courier and other costs. |
| Admin → Reports → Teacher payouts | Pick a month: each teacher's classes and completed orders at their rates. "Mark paid" records the date and reference and keeps the amounts as they were. |
| Admin → Reports → Money report | Choose dates: received, gateway fees, paid to teachers and experts, printing and courier, other costs and the net, for Tuition and Academic separately. |
| Admin → Reports → Orders report, Subscriptions | Orders by status, service and expert; students per batch by status with the next paid-through date. |
| Admin → People | Filter "Active" or "Past" and "Download CSV" for the list as filtered. |

## Stopping

```bash
./vendor/bin/sail down          # stop (data is kept)
```

To go back to Phase 0 work without Docker, restore `.env.backup`.
