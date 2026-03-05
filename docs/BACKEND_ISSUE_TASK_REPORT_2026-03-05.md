# Backend Issue Task Report (2026-03-05)

## Task List

- [x] Critical cleanup: remove malicious injected files/backdoors
- [x] Restore clean `public/.htaccess`
- [x] Verify application runtime recovers from HTTP 500
- [x] Add backend support for offline payment structured instructions
- [x] Add customer statement PDF endpoint (backend)
- [x] Fix quarterly plan creation persistence issue (DB enum mismatch)
- [x] Generate individual-user API contract from source payloads

## Incident / Root Cause

Production instability was linked to malicious file injection in project directories.

Removed:

- injected `wp-cron.php` and `wp-blog-header.php` files across multiple folders
- injected `public/txets.php` backdoor
- injected `.htaccess` files in non-public folders
- restored `public/.htaccess` to clean Laravel front-controller rules

Post-cleanup checks:

- `php artisan list` works normally
- `https://kobac.cajiibcreative.com` returns `302` to `/admin` (healthy)
- `https://kobac.cajiibcreative.com/api/v1/plans` returns `200`

## Issue 1: Vendor edit not working (Flutter UI/UX)

Status: Backend endpoint is present and functional.

- Endpoint: `PUT /api/v1/business/vendors/{vendor}`
- Backend validation and update logic exist in `VendorController::update`.

Conclusion:

- This issue is likely Flutter form/request wiring or field-state handling, not backend core logic.

## Issue 2: Dashboard not updating until refresh (Flutter realtime UX)

Status: Backend can provide fresh values each request; current UX gap is client refresh strategy.

Flutter best-practice recommendation:

1. Use short polling on dashboard page (e.g. every 10-20s, pause in background).
2. Trigger immediate refresh after money operations (`income/expense/customer/vendor` success callbacks).
3. Use optimistic UI update for totals, then reconcile with server.
4. Keep pull-to-refresh as manual fallback.

Optional backend improvement later:

- Add websocket/SSE push channel if true realtime push is required.

## Issue 3: Missing customer statement PDF (Backend)

Status: Fixed.

Added endpoint:

- `GET /api/v1/business/customers/{customer}/statement-pdf`
- Supports optional query: `from`, `to`
- Generates/stores PDF and returns:
  - `file_name`, `file_path`, `download_url`
  - period and summary (`opening_balance`, `total_debit`, `total_credit`, `closing_balance`)

Files:

- `app/Http/Controllers/Api/V1/Business/CustomerController.php`
- `resources/views/exports/customer-statement.blade.php`
- `routes/api.php`

## Issue 4: Quarterly plan not working when creating plan

Status: Fixed.

Root cause:

- `plans.billing_cycle` DB enum did not include `quarterly` (or `weekly/custom`), while UI offered those values.

Fix:

- Added migration to expand enum:
  - `weekly`, `monthly`, `quarterly`, `yearly`, `lifetime`, `custom`
- Migration executed successfully.

File:

- `database/migrations/2026_03_05_090000_expand_plans_billing_cycle_enum.php`

## Offline Payment Instruction Upgrade (Backend API + Admin Settings)

Status: Fixed.

Implemented structured instruction model:

- Note text (`offline_payment_instructions`)
- Unlimited channel list (`offline_payment_channels[]`):
  - `name`
  - `ussd_code`
  - `number`

Admin panel updates:

- Added repeater for unlimited channels in Settings page.

API updates:

- `GET /api/v1/payment/offline/instructions` now returns:
  - `instructions` (legacy string)
  - `instructions_note`
  - `instructions_channels` (array)
  - `instructions_payload` (full object)
- `GET /api/v1/payment/methods` offline method now includes structured instruction fields.

Files:

- `app/Filament/Pages/Settings.php`
- `app/Services/OfflinePaymentService.php`
- `app/Http/Controllers/Api/PaymentController.php`

## Somali UX text (for Flutter form validation behavior)

Suggested corrected Somali wording:

`Marka aad foomka buuxinayso oo aad ka tagto meel waajib ah, app-ku waa inuu si cad kuu tusaa meesha qaladku ka jiro si aad u buuxiso.`

English meaning:

`When filling a form, if a required field is missing, the app must clearly indicate the exact field with the error so the user can complete it.`
