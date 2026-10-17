# UniUyo Dues Portal

A portal where University of Uyo students pay Students' Union dues by card or by SMS, and union officers track who has paid.

- **Students** create an account, pay through Paystack's hosted checkout, and get a printable receipt. After the first payment the card is saved (as a Paystack token, never as a card number). Later payments are one click, or a text message: `PAY <PIN>`.
- **Admins** (union officers) see collection progress, search payments and students, export CSV, and open or close dues for each session.

Built with plain PHP 8.1+, MySQL/MariaDB and no framework or Composer packages, so it runs on a stock XAMPP install.

---

## What changed from the original mini-project

| Original | Now |
|---|---|
| Card numbers **and CVVs** stored in the database | No card data stored at all. Paystack holds the card; we keep only the reusable `authorization_code`, last 4 digits and expiry |
| Payment marked "Paid" after calling `transaction/initialize` (which charges nothing) | A payment is only marked paid after `transaction/verify` confirms status, **amount** and currency |
| `smstodb.php` open to anyone: one POST could mark any student paid | SMS endpoint needs a secret token, and students must text a 4-digit PIN (sender numbers can be spoofed) |
| Anyone could register as Administrator | Registration always creates students. Admins are created from the command line (`bin/create-admin.php`) |
| Admin pages only checked that *a* session existed | Every page checks the role on the server |
| MD5 passwords | `password_hash()` / `password_verify()`, rehashed automatically |
| Changing a password updated every user with the same **first name** | Updates by user ID, and requires the current password |
| Paystack secret key hard-coded in source | Read from `.env` (not committed) |
| No webhook | Signed Paystack webhook (HMAC-SHA512), idempotent, logged |
| Raw values echoed into HTML, string-built SQL | Everything escaped with `e()`, every query is a prepared statement |
| No CSRF protection, GET logout | CSRF tokens on every form, POST-only sign-out |
| No brute-force protection | 5 failed sign-ins per 15 min per account, plus a per-IP limit |
| Mixed-case filenames that break on Linux (`Process2.php`, `include/` vs `Include/`) | Consistent lowercase paths |
| No schema in the repo | `database/schema.sql` |
| Duplicate payments possible | DB constraint: at most one successful payment per student per dues; double clicks and repeated SMS are blocked |

---

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `curl`, `mbstring`, `openssl` (all enabled in XAMPP by default)
- MySQL 8.0+ or MariaDB 10.4+
- A Paystack account (free). Test keys work immediately; live keys need a verified business
- For webhooks and SMS in production: a public HTTPS URL on a host that accepts requests from non-browsers (not InfinityFree; see below)

---

## Setup on Windows with XAMPP

1. **Copy the project** to `C:\xampp\htdocs\uniuyo-dues` (so `C:\xampp\htdocs\uniuyo-dues\public\index.php` exists).

2. **Start Apache and MySQL** in the XAMPP Control Panel.

3. **Create the database.** Open <http://localhost/phpmyadmin>, click **New**, name it `uniuyo_dues` (collation `utf8mb4_unicode_ci`) and click **Create**. With that database selected, open the **Import** tab, pick `database/schema.sql` and click **Go**. This creates the tables and a starter dues of ₦1,700 for 2026/2027.

4. **Configure.** Copy `.env.example` to `.env` and edit it:
   ```ini
   APP_URL=http://localhost/uniuyo-dues/public
   DB_USER=root
   DB_PASS=
   PAYSTACK_SECRET_KEY=sk_test_xxxxxxxx
   PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxx
   SMS_WEBHOOK_TOKEN=<long random string>
   ```
   Your keys are in Paystack Dashboard → **Settings → API Keys & Webhooks**. To generate a token run `C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(24));"`.

5. **Create your admin account** from a terminal in the project folder:
   ```bat
   C:\xampp\php\php.exe bin\create-admin.php
   ```
   It asks for name, email, phone and password. Sign in with that email.

6. Visit <http://localhost/uniuyo-dues/public>. (Visiting `/uniuyo-dues/` also works: the root `.htaccess` forwards to `public/` and blocks the other folders.)

7. *(Optional)* Add demo students and payments so the admin pages have data:
   ```bat
   C:\xampp\php\php.exe bin\seed-demo.php
   ```
   Every demo student's password is `Student123`. The script refuses to run when `APP_ENV=production`.

## Setup on Linux or macOS

```bash
mysql -u root -p -e "CREATE DATABASE uniuyo_dues CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p uniuyo_dues < database/schema.sql
cp .env.example .env            # then edit it
php bin/create-admin.php
php -S localhost:8000 -t public # quick local server, or point an Apache/Nginx vhost at public/
```
For a quick local server, set `APP_URL=http://localhost:8000`.

**Production:** point the web server's document root at `public/` only, serve over HTTPS, set `APP_ENV=production` (hides errors, forces secure cookies and HSTS), and make `storage/logs` writable by the web server.


## Hosting on InfinityFree (or other free iFastNet hosts)

The app runs on InfinityFree, with two limits you should know about first.

**What works:** sign-up, sign-in, card payments through Paystack checkout, saved-card payments, receipts and all admin pages.

**What doesn't:** InfinityFree's "browser security system" only lets real web browsers reach your site. Paystack's **webhook** and **SMS Enabler** are programs rather than browsers, so they get blocked before PHP runs.
- *Webhook:* covered automatically. When a student opens their dashboard, the site asks Paystack about any unconfirmed payments (outgoing requests from PHP are allowed). Admins also get a **Check with Paystack now** button on the overview page. Payments still get confirmed even if a student closes the tab.
- *Pay by SMS:* not possible on InfinityFree. Use paid hosting for that feature.

### Steps

0. **Turn on free SSL first** (control panel → **SSL certificates**) and wait until `https://yoursite…` loads. With `APP_ENV=production`, sign-in cookies only work over HTTPS. If you're testing before SSL is ready, use `APP_ENV=local` and an `http://` `APP_URL` for now.
1. **Create the database.** Control panel → **MySQL Databases** → create one, e.g. `uniuyo_dues`. Note the full name (like `if0_12345678_uniuyo_dues`), the **MySQL host** (like `sql123.infinityfree.com`), the username (`if0_12345678`) and your hosting account password.
2. **Import the tables.** Open **phpMyAdmin** from the control panel, click your database on the left, open **Import**, choose `database/schema.sql` → **Go**.
3. **Fill in `.env`** on your computer before uploading:
   ```ini
   APP_URL=https://yoursite.infinityfreeapp.com
   APP_ENV=production
   DB_HOST=sql123.infinityfree.com
   DB_NAME=if0_12345678_uniuyo_dues
   DB_USER=if0_12345678
   DB_PASS=your-hosting-account-password
   PAYSTACK_SECRET_KEY=sk_test_xxxxxxxx
   PAYSTACK_PUBLIC_KEY=pk_test_xxxxxxxx
   ```
   `APP_URL` has **no** `/public` at the end: the `.htaccess` in the project root sends visitors into `public/` for you.
4. **Upload** the *contents* of the `uniuyo-dues` folder into `htdocs/`, using FileZilla (FTP details are in the control panel) or the online file manager. When you're done, `htdocs/` should contain `.env`, `.htaccess`, `public/`, `src/` and the other folders directly. Don't put them inside a `htdocs/uniuyo-dues/` subfolder.
   - Make sure the two dotfiles, `.env` and `.htaccess`, actually got uploaded; some FTP clients hide or skip them. In FileZilla turn on **Server → Force showing hidden files**.
   - If `.env` is missing, the site says *"Site not configured: the .env file was not found"*. If the database details are wrong, it says *"Can't connect to the database"*.
5. **Create your admin account.** There's no terminal on InfinityFree. Instead, sign up through the site with your email, then run this in phpMyAdmin:
   ```sql
   UPDATE users SET role = 'admin', reg_number = NULL WHERE email = 'you@example.com';
   ```
   Sign out and back in, and you'll land on the admin overview.

The code doesn't depend on `putenv()`/`getenv()`, which InfinityFree disables, so `.env` loads normally there.

---

## Paystack

### How a payment flows

```
First payment (new card)
  Student clicks "Pay"  ─►  pay.php creates a PENDING row  ─►  Paystack /transaction/initialize
       ─►  student enters card on checkout.paystack.com (never on our site)
       ─►  Paystack redirects to payment-callback.php?reference=…
       ─►  we call /transaction/verify  ─►  amount + currency + status match?  ─►  SUCCESS
       ─►  reusable authorization saved as the student's card
  In parallel, Paystack POSTs charge.success to webhook.php ─► signature checked ─► verify ─► same result (idempotent)

Later payments (saved card or SMS)
  pay.php / sms.php  ─►  PENDING row  ─►  /transaction/charge_authorization  ─►  /transaction/verify  ─►  SUCCESS
```

The webhook matters because students sometimes close the tab before the redirect. Without it the money arrives but the portal never hears about it.

### Configure the webhook

Paystack Dashboard → **Settings → API Keys & Webhooks** → set **Test Webhook URL** (and later the Live one) to:

```
https://your-domain/webhook.php
```

Paystack can't reach `localhost`. To test webhooks on your laptop, expose it with a tunnel such as `ngrok http 80` and use the `https://…ngrok…/uniuyo-dues/public/webhook.php` URL. Without a webhook, local testing still works because the callback page verifies payments itself.

### Test cards

In test mode, use Paystack's test card **4084 0840 8408 4081**, CVV **408**, any future expiry date. For the full list of test cards (declines, OTP and so on), see <https://paystack.com/docs/payments/test-payments/>.

### Going live

1. Complete business verification on Paystack.
2. Replace the `sk_test_` / `pk_test_` keys with live keys in `.env`.
3. Set the **Live Webhook URL**.
4. Make sure the site is on HTTPS and `APP_ENV=production`.

---

## Pay by SMS (SMS Enabler)

1. Install [SMS Enabler](https://www.smsenabler.com/) on the PC with the GSM modem or phone, and set **SMS to Webserver** to POST to:
   ```
   https://your-domain/sms.php?token=YOUR_SMS_WEBHOOK_TOKEN
   ```
   Turn on "send the response as a reply SMS".
2. Put the receiving number in `.env` as `SMS_NUMBER` so students see it on their dashboard.
3. A student who has paid once by card sets a PIN under **Account → SMS PIN**, then texts `PAY 4821` (their PIN) from their registered number.

Safeguards: the secret token in the URL; the PIN (hashed); SMS paused for an hour after 3 wrong PINs; at most 5 texts per number per 10 minutes; no second charge while one is in flight or after the dues are paid. PINs are masked before messages are logged. Replies avoid the ₦ sign so they fit in a single 160-character SMS.

---

## Project structure

```
uniuyo-dues/
├── public/                  ← the only web-visible folder (document root)
│   ├── index.php            landing page
│   ├── register.php, login.php, logout.php
│   ├── dashboard.php        student: dues ticket, pay buttons, saved card, history
│   ├── pay.php              starts checkout or charges the saved card (POST)
│   ├── payment-callback.php Paystack redirect target, verifies the payment
│   ├── webhook.php          Paystack webhook (signature-checked)
│   ├── sms.php              SMS Enabler handler
│   ├── receipt.php          printable receipt
│   ├── account.php          phone, SMS PIN, password
│   ├── cards.php            remove saved card (POST)
│   ├── admin/               index (overview), payments (+CSV), students, dues
│   └── assets/              css, js, images, self-hosted fonts
├── src/                     application code (denied to the web)
│   ├── bootstrap.php        config, session, security headers
│   ├── paystack.php         Paystack API client + webhook signature check
│   ├── payments.php         payment rules (pending → verified → success)
│   ├── auth.php, csrf.php, db.php, helpers.php, env.php
│   └── views/               templates
├── database/schema.sql
├── bin/create-admin.php, bin/seed-demo.php
├── storage/logs/            app.log and php-error.log
└── .env.example
```

### Database tables

| Table | Purpose |
|---|---|
| `users` | Students and admins: bcrypt password, hashed SMS PIN, unique email/phone/reg. number |
| `dues` | One row per levy and session; exactly one is open (`is_active`) |
| `payment_methods` | Paystack authorizations: token, last 4, expiry, bank. **No PAN or CVV** |
| `payments` | Every attempt with status `pending/success/failed/abandoned`; a generated column makes a second successful payment for the same student and dues impossible |
| `webhook_events` | Raw signed webhook payloads and what we did with them |
| `sms_messages` | Inbound texts (PIN masked) and our replies |
| `login_attempts` | Failed sign-ins for throttling |

---

## Security notes for your write-up

- **PCI scope:** card data is entered only on Paystack's page, so the portal never handles cardholder data. The stored `authorization_code` can only be used with the union's secret key.
- **Trust boundaries:** the browser redirect and the webhook body are both treated as hints. Only a server-to-server `transaction/verify` call changes a payment to success, and the amount is compared with the amount we asked for.
- **Content Security Policy** allows only same-origin scripts, styles and fonts, and form posts only to this site and `checkout.paystack.com`. That's why the fonts are self-hosted.
- **Sessions:** HttpOnly, SameSite=Lax, Secure in production, ID regenerated on sign-in, 30-minute idle timeout.
- **Things to watch:** payments flagged `DUPLICATE` (a student paid twice in two tabs) need a refund in the Paystack dashboard. The admin overview shows a warning when any exist.

---

## Credits

- University of Uyo seal: from the original project's assets.
- Photos, from Wikimedia Commons:
  - [University of Uyo Town Campus Entrance Gate](https://commons.wikimedia.org/wiki/File:University_of_Uyo_Town_Campus_Entrance_Gate.jpg) by Lisaesu, CC BY-SA 4.0
  - [Entrance Gate of University of Uyo Campus](https://commons.wikimedia.org/wiki/File:Entrance_Gate_Of_University_Of_Uyo_Campus.jpg) by Nsikaki254, CC BY-SA 4.0
  - [Sculptures in University of Uyo](https://commons.wikimedia.org/wiki/File:Sculptures_in_University_of_Uyo,_Uyo.jpg) by Rexxie1, CC BY-SA 4.0
  - [1Lib1Ref University of Ibadan 2024](https://commons.wikimedia.org/wiki/File:1Lib1Ref_University_of_Ibadan_2024.jpg) by Aminat23, CC0
  - [Directorate of General Studies GST Unit Building, annex](https://commons.wikimedia.org/wiki/File:Directorate_of_General_Studies_GST_Unit_Building,_annex.jpg) by Lisaesu, CC BY-SA 4.0 (spare image in `assets/img/gst.webp`)

  The CC BY-SA images were resized and re-encoded as WebP; the credits in the site footer satisfy the attribution requirement. Keep them if you reuse the images.
- Fonts: Bricolage Grotesque and Instrument Sans, SIL Open Font License (see `public/assets/fonts`).
