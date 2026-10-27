# Trying the Phase 2 portal on your laptop

The logins, portal and admin panel are built behind `FEATURE_ACCOUNTS`. They stay
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
./vendor/bin/sail artisan app:make-admin
```

## What to click through (M3)

| Where | What to try |
| --- | --- |
| http://localhost/register | Sign up as a parent. The email lands in Mailpit at http://localhost:8025; click the link. |
| http://localhost/parent/children | Add a child with a username and password, then log out and log in as the child with that username. |
| http://localhost/register?as=academic | Sign up as an academic student; leave "I am 18 or older" unticked to see the message. |
| http://localhost/admin | Log in as the admin. Under People, invite a teacher; the invitation email is in Mailpit. |
| http://localhost/login | Type a wrong password 5 times: the account locks for 15 minutes. Unlock it from People. |

## Stopping

```bash
./vendor/bin/sail down          # stop (data is kept)
```

To go back to Phase 0 work without Docker, restore `.env.backup`.
