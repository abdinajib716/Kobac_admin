# Individual User API Contract (Source-Truth)

Generated from current backend controller code on 2026-03-05.

## Scope

This contract covers `user_type = individual` payloads from:

- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/V1/AppController.php`
- `app/Http/Controllers/Api/V1/SubscriptionController.php`
- `app/Http/Controllers/Api/V1/DashboardController.php`
- `app/Http/Controllers/Api/V1/ProfileController.php`
- `app/Http/Controllers/Api/V1/AccountController.php`
- `app/Http/Controllers/Api/V1/IncomeController.php`
- `app/Http/Controllers/Api/V1/ExpenseController.php`
- `app/Http/Controllers/Api/V1/SearchController.php`
- `app/Http/Controllers/Api/V1/ActivityController.php`
- `app/Http/Controllers/Api/V1/NotificationController.php`
- `app/Http/Controllers/Api/PaymentController.php`

## Response Envelope

Most v1 endpoints use one of:

1. Success envelope
```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```
2. Error envelope
```json
{
  "success": false,
  "message": "Validation failed",
  "error_code": "VALIDATION_ERROR",
  "errors": {}
}
```
3. Paginated envelope
```json
{
  "success": true,
  "message": "Success",
  "data": [],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 0,
    "last_page": 1
  }
}
```

## Individual Access Rules

- Individual users are free-tier:
  - `can_read = true`
  - `can_write = true`
- Business-only routes under `/api/v1/business/*` are not part of this contract.
- `POST /api/v1/payment/offline` returns `403` for individual users.

## Endpoint Contract

## Public/Auth

### `POST /api/v1/auth/register`

Request fields:

- `user_type` (required): `individual|business`
- `name` (required, string)
- `email` (required, unique)
- `phone` (nullable)
- `preferred_locale` (nullable, supported locale key)
- `password` + `password_confirmation` (required)
- `country_id|region_id|district_id|address` (nullable)
- `plan_id` is required only for `business`

Individual success data:

- `user`: `{id, name, email, phone, user_type, preferred_locale, avatar, is_active, created_at, is_free}`
- `token`: Sanctum token string

### `POST /api/v1/auth/login`

Request fields:

- `email`, `password` (required)
- `device_name` (nullable)

Individual success data:

- `user`: same shape as register
- `access`: `{can_read: true, can_write: true}`
- `token`

### `GET /api/v1/auth/me`

Success data:

- `user`: same shape as register/login
- `access`: `{can_read, can_write, is_blocked}`

### `POST /api/v1/auth/logout`

Success: no `data` payload.

### Password reset flow

- `POST /api/v1/auth/forgot-password`
  - Request: `email`
  - Success data: `{email, expires_in}` when user exists
- `POST /api/v1/auth/verify-reset-code`
  - Request: `email`, `code`
  - Success data: `{reset_token, expires_in}`
- `POST /api/v1/auth/reset-password`
  - Request: `email`, `reset_token`, `password`, `password_confirmation`
  - Success: no `data`
- `POST /api/v1/auth/change-password`
  - Request: `current_password`, `password`, `password_confirmation`
  - Success: no `data`

## App/Access

### `GET /api/v1/apps`

Individual success data:

- `user_type`: `individual`
- `is_free`: `true`
- `locale`: selected locale
- `apps`: array of
  - `{id, name, icon, route, enabled, locked, hidden}`
  - Business apps are returned with `enabled=false`, `locked=true`, `hidden=true`
- `write_blocked`: `false`
- `block_reason`: `null`
- `block_action`: `null`

### `GET /api/v1/subscription/status`

Individual success data:

- `user_type: individual`
- `is_free: true`
- `plan: "Free"`
- `status: "active"`
- `status_label: "FREE - Full Access"`
- `can_read`, `can_write`, `write_blocked`
- `block_reason`, `block_action` as `null`
- `trial_days_left: null`
- `is_paid: false`
- `upgrade_available: false`

## Dashboard/Profile

### `GET /api/v1/dashboard`

Individual success data:

- `summary`:
  - `total_balance`, `total_income`, `total_expense`, `accounts_count`
- `currency` (currently `USD`)
- `period`: `{from, to}`
- `accounts`: `[{id, name, type, balance}]`
- `recent_transactions`: up to 10 mixed income/expense rows:
  - `{id, type, amount, category, description, date, account_name}`

### `PUT /api/v1/profile`

Request fields:

- `name`, `phone`, `avatar` (all optional)

Success data:

- `{id, name, email, phone, preferred_locale, avatar}`

### `GET /api/v1/profile/preferences`

Success data:

- `locale`
- `fallback_locale`
- `available_locales`: `[{code, name, native_name, rtl}]`

### `PUT /api/v1/profile/preferences`

Request fields:

- `locale` (required, supported locale)

Success data:

- `{locale, message_key}`

## Accounts

### `GET /api/v1/accounts`

Success data:

- `accounts`: `[{id, name, type, type_label, balance, currency, provider, account_number, is_active, created_at}]`
- `summary`: `{total_balance, currency}`

### `POST /api/v1/accounts`

Request fields:

- `name`, `type` required
- optional: `provider`, `account_number`, `initial_balance`, `currency`

Success data:

- Account object (same shape as list item)

### `GET /api/v1/accounts/{account}`
- Success data: account object

### `PUT /api/v1/accounts/{account}`
- Request: partial `name|provider|account_number|is_active`
- Success data: account object

### `DELETE /api/v1/accounts/{account}`
- Success: no `data`

### `POST /api/v1/accounts/{account}/activate`
### `POST /api/v1/accounts/{account}/deactivate`
- Success data: account object

### `GET /api/v1/accounts/{account}/ledger`

Query:

- `from`, `to` (optional; defaults applied in controller)
- `per_page` (max 100)

Success data:

- `account`: account object
- `period`: `{from, to}`
- `opening_balance`, `closing_balance`
- `total_income`, `total_expense`
- `ledger`: `[{id, type, amount, description, category, date, created_at, running_balance}]`
- `pagination`: `{total, per_page, default_per_page, max_per_page}`

## Income

### `GET /api/v1/income`

Query:

- `from`, `to`, `account_id`, `per_page` (max 50)

Success:

- Paginated `data` array of:
  - `{id, account_id, account_name, amount, description, category, reference, transaction_date, created_at}`

### `POST /api/v1/income`

Request:

- `account_id`, `amount`, `transaction_date` required
- optional: `description`, `category`, `reference`

Success data:

- `transaction`: `{id, amount, description, transaction_date}`
- `account`: `{id, name, previous_balance, new_balance}`

### `GET /api/v1/income/{income}`
### `PUT /api/v1/income/{income}`
### `DELETE /api/v1/income/{income}`

- `GET`: returns income object
- `PUT`: accepts `description|category|reference`; returns income object
- `DELETE`: success with no `data`

## Expenses

### `GET /api/v1/expenses`

Query:

- `from`, `to`, `account_id`, `per_page` (max 50)

Success:

- Paginated `data` array of:
  - `{id, account_id, account_name, amount, description, category, reference, transaction_date, created_at}`

### `POST /api/v1/expenses`

Request:

- `account_id`, `amount`, `transaction_date` required
- optional: `description`, `category`, `reference`

Success data:

- `transaction`: `{id, amount, description, transaction_date}`
- `account`: `{id, name, previous_balance, new_balance}`

### `GET /api/v1/expenses/{expense}`
### `PUT /api/v1/expenses/{expense}`
### `DELETE /api/v1/expenses/{expense}`

- `GET`: returns expense object
- `PUT`: accepts `description|category|reference`; returns expense object
- `DELETE`: success with no `data`

## Search/Activity

### `GET /api/v1/search?q={text}&limit={n}`

Rules:

- `q` min length = 2
- `limit` max = 10

Individual success data:

- `query`
- `results.accounts`: `[{id, name, type, balance}]` (only accounts for individual)
- `total_results`
- `limit_per_type`

### `GET /api/v1/activity`

Query:

- `per_page` (max 50), `page`

Individual success data:

- mixed `income` + `expense` activity rows:
  - `{id, type, description, amount, category, account_name, account_id, reference, created_by, timestamp, date}`
- `pagination`: `{current_page, per_page, total}`

## Notifications

### `POST /api/v1/notifications/register-token`

Request:

- `device_token`, `platform` required
- optional: `device_name`, `device_id`

Success data:

- `{token_id, registered}`

### `POST /api/v1/notifications/unregister-token`

Request:

- `device_token` required

Success data:

- `{unregistered}`

### `GET /api/v1/notifications/history`

Success:

- Paginated `data` rows:
  - `{id, title, body, data, image_url, sent_at}`

## Payments

### `GET /api/v1/payment/methods`

Success data:

- Array of available methods from backend config:
  - online method fields: `{type, name, description, providers, is_instant}`
  - offline method fields: `{type, name, description, instructions, instructions_note, instructions_channels, is_instant, requires_approval}`

### `POST /api/v1/payment/initiate`

Request:

- `phone_number`, `amount` required
- optional: `wallet_type`, `customer_name`, `description`, `invoice_id`

Success/failure payload is pass-through from `WaafiPayService::purchase`.

### `POST /api/v1/payment/status`
- Request: `reference_id`
- Response: pass-through from `WaafiPayService::checkStatus`

### `GET /api/v1/payment/history`
- Success data: paginated payment transactions for authenticated user.

### `GET /api/v1/payment/offline/instructions`

Success fields:

- `instructions` (legacy string)
- `instructions_note` (note string)
- `instructions_channels` (array of `{name, ussd_code, number}`)
- `instructions_payload`: `{note, channels, legacy_text}`

### `POST /api/v1/payment/offline`

For individual users:

- returns `403` with message:
  - `"Only business users can subscribe to paid plans."`

### `POST /api/v1/payment/offline/status`

Request:

- `reference_id`

Success data:

- `status`, `message`
- `transaction`: `{id, reference_id, amount, currency, status, payment_type, created_at, approved_at, rejection_reason}`
- `subscription`: `{id, status, plan_name}` or `null`
