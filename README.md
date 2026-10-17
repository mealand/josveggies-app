# JosVeggies

Fresh vegetables from Jos, pre-ordered in limited slots and delivered to Abuja.

**Powered and developed by SPREZZ INTEGRATED LIMITED.**

One responsive web app containing two products that share data:

- **Customer Storefront.** See the next Run, reserve boxes and add-ons, pay with Paystack, track the order, and collect with a pickup code or QR.
- **Operations Console** (`/console`). Admin, Jos Ops and Abuja Ops use it for Runs, the catalogue, procurement and packing lists, substitutions, status updates, pickup verification, rider dispatch, forfeitures, costs and reports.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.3+), MariaDB 10.6+ / MySQL 8 |
| UI | Blade + Livewire 4 (CSP-safe Alpine), Tailwind CSS v4 |
| Motion | GSAP + ScrollTrigger (lazy-loaded), View Transitions, CSS; respects `prefers-reduced-motion` |
| Auth | Laravel Fortify: email + password, email verification, **mandatory TOTP 2FA for staff** |
| Payments | Paystack, behind a `PaymentGateway` interface (a fake gateway is used locally) |
| Notifications | Email + SMS (Termii; a log driver is used locally) |
| PDFs / QR | dompdf, chillerlan/php-qrcode, qr-scanner (camera scanning at the hub) |
| Audit / backups | spatie/laravel-activitylog, spatie/laravel-backup |
| Quality | Pest (business-rule tests named BR-01…BR-21), Larastan, Pint, GitHub Actions CI |

Shared cPanel hosting cannot run daemons, so the whole system runs on **PHP + MySQL + one per-minute cron line**.

## How the business rules map to code

- **Run state machine:** `app/Enums/RunState.php` and `app/Services/RunService.php`.
- **Order state machine:** `app/Enums/OrderState.php` and `app/Services/OrderStateMachine.php`.
- **Slot booking without overselling (BR-04/06/07):** `app/Services/CheckoutService.php` takes a row lock on the Run. A parallel-process test is in `tests/Concurrency`.
- **Payments confirmed only by the gateway (BR-08):** `app/Services/PaymentService.php` and `app/Http/Controllers/PaystackWebhookController.php`. The webhook signature is checked with HMAC-SHA512, the payment is re-verified through the API, processing is idempotent, and late payments are auto-refunded.
- **Substitutions and credit (BR-11–14, 20):** `app/Services/SubstitutionService.php` and `app/Services/CreditService.php` (an append-only ledger).
- **Time-based rules (window open/close, hold expiry, 9am/11am reminders, noon forfeiture):** `app/Services/RunClock.php`, run every minute by `josveggies:tick`.
- **Market-day secrecy (BR-19):** `market_day` is hidden from serialisation and shown only behind the `view-market-day` gate (Admin and Jos Ops). Customers see `delivery_starts_at`. The order-close gap is set by Admin (at least 2 days), so it is not a fixed offset.
- **Money:** stored as integer **kobo** and formatted by `App\Support\Money`. **Time:** West Africa Time (`Africa/Lagos`, no DST).

## Run it locally (Windows)

Requirements: PHP 8.3+ with Composer, MariaDB/MySQL, Node 20+. The original setup used portable copies in `C:\Users\<you>\tools` (PHP 8.4, MariaDB 11.4, Mailpit).

```bash
cp .env.example .env            # then set DB_* (database "josveggies")
composer install
php artisan key:generate
php artisan migrate --seed      # demo catalogue, an open Run, staff and a customer
npm install && npm run build    # or: npm run dev
php artisan serve               # http://127.0.0.1:8000
php artisan schedule:work       # in a second terminal: runs the minute tick and the queue
```

Demo accounts (local only; password `password1234`):

| Role | Email |
|---|---|
| Admin | admin@josveggies.test |
| Jos Ops | jos@josveggies.test |
| Abuja Ops | abuja@josveggies.test |
| Customer | customer@josveggies.test |

Staff sign in at `/console/login` and must enrol an authenticator app on first sign-in.

Local helpers:

- **Payments:** `PAYMENT_GATEWAY=fake` shows a simulated checkout page with success and failure buttons. To use real Paystack test mode, set `PAYMENT_GATEWAY=paystack` with test keys and expose `/webhooks/paystack` through a tunnel (cloudflared or ngrok).
- **Email:** Mailpit at http://localhost:8025 (SMTP 127.0.0.1:1025).
- **SMS:** written to `storage/logs/sms.log` while `SMS_DRIVER=log`.

### Tests and quality

```bash
vendor/bin/pest                 # needs a "josveggies_test" database (see phpunit.xml)
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint
```

## Deploy to shared cPanel hosting

1. **Pre-flight.** Upload `deploy/host-check.php` to `public_html`, open it in a browser, fix anything marked FAIL, then delete the file.
2. **Build a release** locally with `bash deploy/build-release.sh`. It produces `release/josveggies-<date>.zip`, containing compiled assets and production dependencies but no tests or dev files.
3. **Upload and extract** the zip to `~/josveggies` (outside `public_html`). Point the domain's document root at `~/josveggies/public`. If the host doesn't allow that, replace `public_html` with a symlink to it.
4. **Database.** In cPanel → MySQL Databases, create a database and user and grant all privileges.
5. **Environment.** Copy `.env.example` to `~/josveggies/.env` and set:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://josveggies.ng`, `SESSION_SECURE_COOKIE=true`
   - `DB_*`, plus SMTP credentials from a transactional email provider (for example ZeptoMail or Brevo)
   - `PAYMENT_GATEWAY=paystack`, `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`
   - `SMS_DRIVER=termii`, `TERMII_API_KEY`, `TERMII_SENDER_ID`
   - `BACKUP_DISK`, pointing at off-site S3-compatible storage
6. **From cPanel Terminal:**
   ```bash
   cd ~/josveggies
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --class=PolicySeeder --force
   php artisan josveggies:create-admin
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
7. **Cron (cPanel → Cron Jobs, every minute):**
   ```
   * * * * * /usr/local/bin/php /home/USER/josveggies/artisan schedule:run >> /dev/null 2>&1
   ```
8. **Domain and integrations.**
   - Enable AutoSSL. Add josveggies.com and josveggies.com.ng as aliases; `.htaccess` redirects them to `https://josveggies.ng`.
   - Set the Paystack webhook URL to `https://josveggies.ng/webhooks/paystack`.
   - Register the Termii sender ID early, because approval can take time.
9. **Rehearse first.** Do a full dry Run on a staging subdomain with Paystack test keys before going live.

## Security summary

- **CSP:** strict, nonce-based scripts with no `unsafe-eval`. Also HSTS, `X-Frame-Options`, `Referrer-Policy` and `Permissions-Policy`; the camera is allowed only on the pickup desk.
- **Accounts:** role-based access with policies and gates on every console route and Livewire action. Staff have mandatory TOTP 2FA, a 30-minute idle timeout, and must re-confirm their password for staff, settings and policy changes.
- **Sign-in:** rate-limited login, 2FA, checkout, pickup-code lookups and webhooks. Passwords need at least 10 characters with letters and numbers, plus a breached-password check in production.
- **Data at rest:** encrypted sessions. Phone numbers, addresses and pickup codes are encrypted; pickup codes are looked up through an HMAC hash.
- **Payments and uploads:** prices are always recalculated on the server. Webhooks are HMAC-verified and re-checked with the gateway API. Uploaded images are re-encoded to WebP with metadata stripped.
- **Audit:** an audit log covers state changes, substitutions, credit, refunds, handovers, settings and staff changes.

---

© JosVeggies. Powered and developed by SPREZZ INTEGRATED LIMITED.
