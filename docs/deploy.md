# Putting the site live (Phase 0)

The marketing pages are exported to plain HTML with `php artisan export`
(spatie/laravel-export) and published on **Cloudflare Pages** (free) at
**https://indianassignments.com**. After the one-time setup below, every
push to `main` that passes the tests is published automatically by
`.github/workflows/deploy.yml`.

You need: your GoDaddy login, your GitHub login, and about 30 minutes.
Do the parts in order. Part 2 can take a few hours to take effect; you can
do Parts 3 to 5 while you wait.

## Part 1 · Create a free Cloudflare account and add your domain

1. Open <https://dash.cloudflare.com/sign-up> in Edge or Chrome.
2. Enter your email and a new password, click **Sign up**, then open the
   email from Cloudflare and click the link to verify it.
3. In the Cloudflare dashboard, click **Add a domain** (it may say
   **Onboard a domain**).
4. Type `indianassignments.com`, leave **Quick scan for DNS records**
   selected, and click **Continue**.
5. Scroll down, pick the **Free** plan, and click **Continue**.
6. Cloudflare shows the DNS records it copied from GoDaddy.
   - If you use GoDaddy email, **keep** every row of type **MX** and
     **TXT**.
   - **Delete** any row of type **A** or **CNAME** whose name is
     `indianassignments.com` (or `@`) or `www`. These point at GoDaddy's
     "parked" page and would block the website later. Click the row, then
     **Delete**.
   - Click **Continue to activation**.
7. Cloudflare now shows **two nameservers**, something like
   `ada.ns.cloudflare.com` and `bob.ns.cloudflare.com`. Leave this tab open;
   you need them in Part 2.

## Part 2 · Point the domain at Cloudflare (on GoDaddy)

1. In a new tab, sign in at <https://www.godaddy.com>.
2. Click your name at the top right, then **My Products**.
3. Next to `indianassignments.com`, click **DNS** (or **Manage DNS**).
4. Open the **Nameservers** tab and click **Change Nameservers**.
5. Choose **I'll use my own nameservers**.
6. Delete what is in the boxes and paste the two Cloudflare nameservers from
   Part 1, one per box. Click **Save**, then **Continue** on the warning.
   - If GoDaddy says DNSSEC is on, turn DNSSEC off first (same page), then
     change the nameservers.
7. Go back to the Cloudflare tab and click **Check nameservers now**.

Cloudflare emails you when the domain is **Active**. This usually takes
under an hour but can take up to 24 hours.

## Part 3 · Create a Cloudflare API token and find your Account ID

The token lets GitHub upload the site to your Cloudflare account and nothing
else.

1. In Cloudflare, click the person icon at the top right, then
   **My Profile**.
2. Click **API Tokens** on the left, then **Create Token**.
3. Next to **Create Custom Token**, click **Get started**.
4. Fill in:
   - **Token name:** `GitHub deploy`
   - **Permissions:** pick **Account**, then **Cloudflare Pages**, then
     **Edit**.
   - **Account Resources:** **Include**, then your account.
5. Click **Continue to summary**, then **Create Token**.
6. Click **Copy** and paste the token into Notepad for a moment. Cloudflare
   shows it only once.
7. Now the Account ID: click the Cloudflare logo (top left) to go home,
   click `indianassignments.com`, and on the **Overview** page scroll down
   the right-hand column to **API**. Click the **Account ID** to copy it, and
   paste it into Notepad too.

## Part 4 · Save the token and Account ID in GitHub

1. Open <https://github.com/ashimghose/Tutorials-Assignments> and click
   **Settings** (top row of tabs).
2. On the left, click **Secrets and variables**, then **Actions**.
3. Click **New repository secret**.
   - **Name:** `CLOUDFLARE_API_TOKEN`
   - **Secret:** paste the token
   - Click **Add secret**.
4. Click **New repository secret** again.
   - **Name:** `CLOUDFLARE_ACCOUNT_ID`
   - **Secret:** paste the Account ID
   - Click **Add secret**.
5. Close Notepad without saving.

## Part 5 · Publish the site for the first time

1. In the GitHub repository, click the **Actions** tab.
2. Click **deploy** on the left, then **Run workflow** on the right, keep
   **Branch: main**, and click the green **Run workflow** button.
3. Wait for the green tick (a few minutes). The first run also creates the
   Cloudflare Pages project called `indianassignments` for you, so you don't
   have to create it yourself.
4. To preview, open the run, click **deploy**, expand **Publish to
   Cloudflare Pages** and open the `https://….pages.dev` link at the end.
   The pages will look right, but their links go to indianassignments.com,
   which works after Part 6.

## Part 6 · Connect your domain to the site

Do this once Cloudflare says the domain is **Active** (Part 2).

1. In Cloudflare, click **Workers & Pages** on the left (under **Compute**
   on newer menus), then the `indianassignments` project.
2. Open the **Custom domains** tab and click **Set up a custom domain**.
3. Type `indianassignments.com`, click **Continue**, then
   **Activate domain**.
4. Repeat steps 2 and 3 for `www.indianassignments.com`.
5. Wait for both to show **Active** (usually a few minutes; the HTTPS
   certificate is free and automatic). Then open
   <https://indianassignments.com>.

## Part 7 · Your contact details, WhatsApp number and forms

The live site reads these from GitHub, not from your laptop's `.env`. Until
you set them, the site shows its placeholders and the form buttons open
WhatsApp.

1. In the repository, go to **Settings > Secrets and variables > Actions**
   and open the **Variables** tab.
2. Click **New repository variable** for each value you want to set. Use the
   same names as in `.env.example`:

   | Name | Example |
   |---|---|
   | `WHATSAPP_NUMBER` | `919876543210` (country code, no + or spaces) |
   | `CONTACT_EMAIL` | `hello@indianassignments.com` |
   | `BUSINESS_CITY` | `Kolkata` |
   | `FORM_URL_TUITION`, `FORM_URL_ACADEMIC`, `FORM_URL_WAITLIST` and the `FORM_*_ENTRY_*` ids | see `docs/google-forms.md` |
   | `CF_ANALYTICS_TOKEN` | see below |
   | `APP_NAME` | your brand name, when you pick one |

3. Publish again: **Actions > deploy > Run workflow**.

**Cloudflare Web Analytics (optional, free, no cookies):** in Cloudflare open
**Analytics & Logs > Web Analytics**, click **Add a site**, enter
`indianassignments.com`, and copy the long value after `"token":` in the
code it shows. Save it as the `CF_ANALYTICS_TOKEN` variable and run the
deploy again.

## Day to day

- Every push to `main` is published automatically once the **tests**
  workflow passes. A red cross on **tests** means nothing was published.
- To publish without a code change (for example after changing a variable),
  use **Actions > deploy > Run workflow**.
- To check the export on your laptop: `php artisan export`, then
  `php -S localhost:8080 -t dist` and open <http://localhost:8080>. Set
  `APP_URL=http://localhost:8080` in `.env` first so the links stay local.

## If something goes wrong

- **deploy shows a yellow warning "not published":** the two secrets from
  Part 4 are missing or misspelt.
- **"Authentication error" in Publish to Cloudflare Pages:** the token is
  wrong or lacks **Cloudflare Pages: Edit**. Create a new one (Part 3) and
  update the `CLOUDFLARE_API_TOKEN` secret.
- **Custom domain says a DNS record already exists:** delete the old A or
  CNAME record for that name under your domain's **DNS > Records** in
  Cloudflare, then try Part 6 again.
- **Email stopped working after Part 2:** add your email provider's MX and
  TXT records under **DNS > Records** in Cloudflare.
