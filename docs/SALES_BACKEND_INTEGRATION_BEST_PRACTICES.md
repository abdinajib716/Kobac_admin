# Sales Module Backend Integration Best Practices

**Project:** Kobac Business API  
**Scope:** Backend-only design for Flutter mobile app integration  
**Date:** April 30, 2026

---

## 1. Purpose

This document defines the best-practice backend approach for introducing a new `Sales` module into the existing Laravel API without breaking the current `Stock`, `Customer`, `Reports`, `Accounts`, and mobile app flows.

This is **not** a frontend UI plan. It is a backend integration guide for building APIs that power a Flutter POS-like sales experience.

---

## 2. Current Backend Reality

The current codebase already has strong building blocks for this module:

- Business feature discovery via [`app/Http/Controllers/Api/V1/AppController.php`](/var/www/kobac.cajiibcreative.com/app/Http/Controllers/Api/V1/AppController.php:1)
- Business feature gating via [`app/Http/Middleware/CheckFeatureEnabled.php`](/var/www/kobac.cajiibcreative.com/app/Http/Middleware/CheckFeatureEnabled.php:1)
- Branch-aware business routes in [`routes/api.php`](/var/www/kobac.cajiibcreative.com/routes/api.php:1)
- Stock items and stock movements via [`app/Models/StockItem.php`](/var/www/kobac.cajiibcreative.com/app/Models/StockItem.php:1) and [`app/Models/StockMovement.php`](/var/www/kobac.cajiibcreative.com/app/Models/StockMovement.php:1)
- Customers and receivable transactions via [`app/Models/Customer.php`](/var/www/kobac.cajiibcreative.com/app/Models/Customer.php:1) and [`app/Models/CustomerTransaction.php`](/var/www/kobac.cajiibcreative.com/app/Models/CustomerTransaction.php:1)
- Business dashboard metrics in [`app/Http/Controllers/Api/V1/Business/DashboardController.php`](/var/www/kobac.cajiibcreative.com/app/Http/Controllers/Api/V1/Business/DashboardController.php:1)
- Manual income recording in [`app/Http/Controllers/Api/V1/IncomeController.php`](/var/www/kobac.cajiibcreative.com/app/Http/Controllers/Api/V1/IncomeController.php:1)

### Important architectural observation

The current system already separates:

- `Stock` adjustments
- `Customer` debit/credit balance changes
- `Income` posting to accounts

That is good, but it means the new `Sales` module must become the **orchestrator**.  
Flutter should not finalize a sale by calling multiple existing endpoints one by one.

Instead:

- Flutter sends **one checkout request**
- Backend processes the sale in **one database transaction**
- Backend updates stock, customer balance, reporting data, and receipt metadata together

This is the cleanest and safest pattern for POS behavior.

---

## 3. Core Design Principles

### 3.1 Sales must be its own domain

Create `Sales` as a first-class business module, not as a thin wrapper around:

- `income`
- `customer/debit`
- `stock/decrease`

Those existing modules should remain valid for manual bookkeeping, but a completed sale should come from the new `Sales` service layer.

### 3.2 One checkout request, one transaction

Sale completion must run inside a single `DB::transaction()` so these changes either all succeed or all fail:

- create sale header
- create sale items
- validate and decrease stock
- create payment rows
- update customer balance for credit sales
- optionally post accounting/income entries
- persist receipt identifiers
- log activity

### 3.3 Sales owns the business workflow

The `Sales` module should call stock/customer/accounting internals as part of the workflow, but external clients should only call Sales APIs for checkout-related work.

### 3.4 Keep manual customer receiving separate

This matches your requirement exactly.

Best practice:

- `POST /business/customers/{id}/credit` remains the **manual customer payment** flow
- `POST /business/sales/checkout` becomes the **sales-created receivable** flow

They can both write to `customer_transactions`, but the source of the transaction must be traceable.

### 3.5 Design for auditability from day one

Every stock deduction, customer balance change, payment, and receipt must be traceable back to:

- `sale_id`
- `business_id`
- `branch_id`
- `created_by`
- exact timestamp

---

## 4. Recommended Domain Model

## 4.1 New tables

### `sales`

Main sale header.

Recommended fields:

- `id`
- `business_id`
- `branch_id`
- `customer_id` nullable
- `sale_number` unique human-readable code
- `receipt_number` unique human-readable code
- `status` enum: `draft`, `completed`, `cancelled`, `void`
- `sale_type` enum: `cash`, `credit`
- `payment_status` enum: `paid`, `unpaid`, `partial`
- `subtotal`
- `discount_total`
- `tax_total`
- `total`
- `amount_paid`
- `amount_due`
- `cost_total`
- `profit_total`
- `notes` nullable
- `sold_at`
- `created_by`
- `completed_at` nullable
- `voided_at` nullable
- `voided_by` nullable
- `void_reason` nullable
- `receipt_pdf_path` nullable
- `meta` json nullable

### `sale_items`

Line items captured at sale time.

Recommended fields:

- `id`
- `sale_id`
- `stock_item_id`
- `product_name_snapshot`
- `sku_snapshot` nullable
- `unit_snapshot`
- `quantity`
- `cost_price_snapshot`
- `unit_price`
- `line_discount`
- `line_tax`
- `line_total`
- `meta` json nullable

Why snapshots matter:

- product names and prices may change later
- receipts and reports must still reflect the original sale

### `sale_payments`

Separate payment records for future flexibility.

Recommended fields:

- `id`
- `sale_id`
- `payment_method` enum or string
- `payment_type` enum: `cash`, `credit`
- `amount`
- `paid_at` nullable
- `reference` nullable
- `status` enum: `pending`, `completed`, `failed`, `cancelled`
- `created_by`
- `meta` json nullable

Even if today you only support full cash or full credit, this table prepares you for:

- partial payments
- split payments
- later credit collection
- printer payload history

### Optional: `sale_order_holds`

Only add this if you want server-side saved carts or held POS orders across devices.  
If not needed yet, let Flutter hold the cart locally and only persist when the user taps checkout.

---

## 4.2 Existing tables that should be extended carefully

### `customer_transactions`

Recommended additions:

- `source_type` nullable, example: `sale`, `manual_payment`
- `source_id` nullable
- `reference` nullable
- `meta` json nullable

Reason:

The current table records debit/credit, but it does not clearly identify whether a row came from:

- a sale
- a manual adjustment
- a later customer payment

That distinction becomes very important once Sales goes live.

### `stock_movements`

Recommended additions:

- `source_type` nullable, example: `sale`, `manual_stock_change`
- `source_id` nullable
- `unit_price_snapshot` nullable
- `meta` json nullable

At minimum, set `reference` to the sale number.  
Best practice is to also store direct source linkage.

---

## 5. Recommended Backend Modules

Create new backend components instead of overloading existing controllers.

### Suggested classes

- `App\Models\Sale`
- `App\Models\SaleItem`
- `App\Models\SalePayment`
- `App\Http\Controllers\Api\V1\Business\SalesController`
- `App\Http\Controllers\Api\V1\Business\SalesReportController`
- `App\Http\Controllers\Api\V1\Business\SalesReceiptController`
- `App\Services\Sales\CheckoutSaleService`
- `App\Services\Sales\VoidSaleService`
- `App\Services\Sales\GenerateReceiptPdfService`
- `App\Services\Sales\BuildPrintPayloadService`

### Why use services

Controllers should stay thin.  
The transactional business rules belong in a service, especially for:

- stock locking
- payment branching
- customer receivable posting
- future printer integration
- future refunds/voids

---

## 6. API Structure for Flutter

Use the same existing business API style:

- prefix: `/api/v1/business`
- auth: `auth:sanctum`
- business user middleware
- `branch.context`
- `subscription.write`
- new feature flag: `sales`

### 6.1 App registration changes

Add `sales` to:

- `AppController::APP_CONFIG`
- plan `features`
- `CheckFeatureEnabled` business-only features list
- mobile app discovery response

### 6.2 Recommended endpoints

#### Dashboard

`GET /api/v1/business/sales/dashboard`

Return:

- `total_sales`
- `total_products`
- `total_profit`
- `customer_owes`
- daily or monthly sales trend

#### Product catalog for POS

`GET /api/v1/business/sales/products`

Supported query params:

- `search`
- `category_id` or `category`
- `active_only`
- `in_stock_only`
- `per_page`

This endpoint should return only data the POS screen needs.

Do not force Flutter to use the generic global search endpoint for checkout.

#### Customer lookup for credit sale

`GET /api/v1/business/sales/customers?search=...`

Keep this optimized for POS lookup:

- id
- name
- phone
- current balance
- status

#### Save draft or held order

`POST /api/v1/business/sales/orders`

Optional for MVP.  
Use only if you want backend-persisted draft carts.

#### Complete checkout

`POST /api/v1/business/sales/checkout`

This is the main API for the POS flow.

Example payload:

```json
{
  "customer_id": 45,
  "sale_type": "credit",
  "payment_method": "cash",
  "notes": "Counter sale",
  "items": [
    {
      "stock_item_id": 10,
      "quantity": 2,
      "unit_price": 5.00
    },
    {
      "stock_item_id": 11,
      "quantity": 1,
      "unit_price": 12.50
    }
  ],
  "idempotency_key": "5b46c7d2-a814-4fb2-a40a-fc43c3f5277e"
}
```

#### Receipt PDF

`GET /api/v1/business/sales/{sale}/receipt-pdf`

#### Print payload

`GET /api/v1/business/sales/{sale}/print-payload`

This endpoint is important for future Bluetooth printer support.

Return a device-agnostic payload, not printer SDK logic.

#### Sales list

`GET /api/v1/business/sales`

Filters:

- `status`
- `sale_type`
- `payment_status`
- `customer_id`
- `from`
- `to`

#### Sales reports

`GET /api/v1/business/sales/reports/summary`
`GET /api/v1/business/sales/reports/trends`
`GET /api/v1/business/sales/reports/top-products`
`GET /api/v1/business/sales/reports/items`

---

## 7. Checkout Rules

## 7.1 Cash sale

Rules:

- no customer required
- `sale_type = cash`
- `payment_status = paid`
- `amount_due = 0`
- stock decreases immediately
- receipt generated immediately

Optional accounting integration:

- create an `IncomeTransaction`
- credit the selected cash/mobile/bank account

### Best practice

If you need cash sales to appear in existing financial dashboards, post an internal accounting entry from Sales.  
Do not make Flutter call `/income` separately.

## 7.2 Credit sale

Rules:

- `customer_id` required
- `sale_type = credit`
- `payment_status = unpaid` or `partial`
- customer balance increases
- stock decreases immediately
- receipt generated immediately

### Best practice

When a credit sale is completed:

- create the sale
- create sale items
- decrease stock
- create a `customer_transactions` debit row
- increase `customers.balance`

But mark that customer transaction as originating from Sales using `source_type/source_id`.

## 7.3 No mixed client-side orchestration

Avoid this mobile flow:

1. create sale
2. call stock decrease
3. call customer debit
4. call receipt endpoint

That pattern causes partial failures and duplicate sales during retries.

Use one backend checkout endpoint instead.

---

## 8. Stock Integration Best Practices

### 8.1 Lock stock rows during checkout

Use `lockForUpdate()` on affected `stock_items` rows inside the DB transaction.

This prevents overselling when:

- two users sell the same product at the same time
- mobile retries submit the same request twice

### 8.2 Validate stock before commit

For each line item:

- product must belong to current business
- product must belong to current branch or allowed branch scope
- product must be active
- quantity must be positive
- available quantity must be enough

### 8.3 Record stock movements from Sales

Do not directly subtract raw quantity and stop there.

Every stock decrease should also create a `stock_movements` row with:

- type: `decrease`
- reason: `Sale`
- reference: sale number
- source link to sale if available

### 8.4 Snapshot pricing in sale items

Never rely on the current `stock_items.selling_price` later for old sales history.  
Store the sold price per item in `sale_items`.

---

## 9. Customer Integration Best Practices

### 9.1 Reuse customer ledger, not customer checkout endpoint

The current `CustomerController` debit/credit endpoints are useful for manual operations, but checkout should not call those endpoints over HTTP.

Instead, the Sales service should:

- use the `Customer` model
- create the balance movement internally
- persist ledger evidence with source linkage

### 9.2 Credit sale must require customer ownership validation

Validate that the selected customer:

- belongs to the same business
- belongs to the same branch if your branch rules require that
- is active

### 9.3 Keep customer payment collection separate

Later, when the customer pays:

- use a dedicated sales receivable payment flow, or
- extend the existing customer payment flow carefully

Recommended future endpoint:

`POST /api/v1/business/sales/{sale}/collect-payment`

That is cleaner than losing the connection between payment and original sale.

---

## 10. Reporting Best Practices

This is the most important integration point after stock.

### 10.1 Do not depend only on `income_transactions`

Current dashboards and profit/loss use `IncomeTransaction` and `ExpenseTransaction`.  
A pure Sales implementation that never posts accounting entries will create this problem:

- Sales module shows revenue
- Existing dashboard/profit-loss may not show the same revenue

### 10.2 Decide reporting mode explicitly

You should choose one of these patterns:

### Option A: Sales is the source of truth for sales reports

Use `sales` and `sale_items` for:

- total sales
- sales charts
- top products
- gross profit
- cash vs credit split

This is required no matter what.

### Option B: Sales also posts accounting entries

For cash sales, create matching `IncomeTransaction` rows so current business financial summaries stay aligned.

This is recommended if:

- your existing dashboard must reflect sales cash inflow
- you want existing account balances to move automatically

### Recommended approach

Use both:

- `sales` tables for operational sales reporting
- internal accounting posting for cash/account movement where needed

But keep posting logic inside the Sales service, not in Flutter.

## 10.3 Sales report table requirement

For the Sales report screen, the backend should return row data with these columns:

- `product`
- `qty`
- `price`
- `cost`
- `profit`

### Recommended definitions

- `product`: product name snapshot from `sale_items.product_name_snapshot`
- `qty`: total sold quantity in the selected date range
- `price`: total sales amount for that product in the selected date range
- `cost`: total cost amount for that product in the selected date range
- `profit`: `price - cost`

### Best practice

Do not calculate this from current stock prices.  
Use sale-time snapshots stored in `sale_items`:

- `quantity`
- `unit_price`
- `cost_price_snapshot`
- `line_total`

Recommended aggregation:

- `qty = SUM(quantity)`
- `price = SUM(line_total)`
- `cost = SUM(quantity * cost_price_snapshot)`
- `profit = SUM(line_total) - SUM(quantity * cost_price_snapshot)`

## 10.4 Date range filter and View action

The report API must support date range filtering.

Recommended request params:

- `from`
- `to`
- optional `branch_id`
- optional `customer_id`
- optional `stock_item_id`

Recommended endpoint:

`GET /api/v1/business/sales/reports/items?from=2026-04-01&to=2026-04-30`

### Flutter behavior

For the `View` button:

1. User selects `from` date
2. User selects `to` date
3. User taps `View`
4. Flutter calls the report endpoint with the selected range
5. Backend returns only rows within that date range

### Response shape example

```json
{
  "success": true,
  "data": {
    "filters": {
      "from": "2026-04-01",
      "to": "2026-04-30"
    },
    "summary": {
      "total_qty": 120,
      "total_price": 2450.00,
      "total_cost": 1700.00,
      "total_profit": 750.00
    },
    "rows": [
      {
        "stock_item_id": 10,
        "product": "Product A",
        "qty": 20,
        "price": 300.00,
        "cost": 180.00,
        "profit": 120.00
      },
      {
        "stock_item_id": 11,
        "product": "Product B",
        "qty": 8,
        "price": 200.00,
        "cost": 140.00,
        "profit": 60.00
      }
    ]
  }
}
```

### Optional drill-down endpoint

If the `View` action later needs line-by-line history instead of grouped product totals, add:

`GET /api/v1/business/sales/reports/items/details?from=2026-04-01&to=2026-04-30&stock_item_id=10`

That endpoint can return:

- sale number
- receipt number
- sale date
- product
- qty
- unit price
- line cost
- line profit

---

## 11. Receipt and PDF Best Practices

### 11.1 Generate receipt from sale snapshots

PDF receipts should render from:

- `sales`
- `sale_items`
- `customer`
- payment summary

Not from current stock product values.

### 11.2 Receipt content

Include:

- business name
- branch name
- receipt number
- sale date/time
- cashier/user
- customer if present
- payment type
- item lines
- subtotal
- discount
- tax
- total
- amount paid
- amount due

### 11.3 PDF generation strategy

Use a dedicated service such as `GenerateReceiptPdfService`.

Recommended options:

- generate on demand
- optionally cache the PDF file path on the sale row

### 11.4 Print-first architecture

For future Bluetooth support, create a print payload builder now.

Return a normalized payload like:

```json
{
  "receipt_number": "RCP-2026-000123",
  "sale_number": "SAL-2026-000123",
  "business_name": "Kobac Store",
  "branch_name": "Main Branch",
  "payment_type": "cash",
  "customer_name": null,
  "lines": [
    {
      "name": "Product A",
      "qty": 2,
      "price": 5.00,
      "total": 10.00
    }
  ],
  "total": 10.00
}
```

This keeps printer integration flexible across:

- PDF download
- browser print
- Bluetooth ESC/POS printer
- native mobile printer SDK later

---

## 12. Mobile API Best Practices for Flutter

### 12.1 Use idempotency for checkout

Mobile networks are unstable.  
Flutter may retry a checkout request.

Best practice:

- require `idempotency_key` on checkout
- store it per business/branch/user
- return the existing sale if the same key is retried

This prevents duplicate completed sales.

### 12.2 Keep cart state client-side until save or checkout

For MVP:

- Flutter manages the live cart locally
- backend persists only draft orders if user explicitly saves them

This reduces server complexity.

### 12.3 Keep POS endpoints optimized

Do not make the checkout screen compose itself from too many generic APIs.

Recommended minimum backend calls:

1. `GET /business/sales/dashboard`
2. `GET /business/sales/products`
3. `GET /business/sales/customers?search=...`
4. `POST /business/sales/checkout`
5. `GET /business/sales/{id}/receipt-pdf`

---

## 13. Security and Validation Rules

Enforce all of the following:

- business ownership validation on every sale object
- branch context validation
- active product validation
- active customer validation
- positive quantity validation
- price cannot be negative
- line totals recalculated on server
- subtotal and total recalculated on server
- do not trust mobile-calculated amounts
- prevent editing completed sales without a formal void/refund process
- all writes behind `subscription.write`
- all sales routes behind `feature.enabled:sales`

---

## 14. Suggested Route Layout

```php
Route::middleware([
    'user.type:business',
    'branch.context',
    'subscription.write',
    'feature.enabled:sales',
])->prefix('business')->group(function () {
    Route::get('sales/dashboard', [SalesController::class, 'dashboard']);
    Route::get('sales/products', [SalesController::class, 'products']);
    Route::get('sales/customers', [SalesController::class, 'customers']);
    Route::get('sales', [SalesController::class, 'index']);
    Route::post('sales/orders', [SalesController::class, 'storeDraft']);
    Route::post('sales/checkout', [SalesController::class, 'checkout']);
    Route::get('sales/{sale}', [SalesController::class, 'show']);
    Route::post('sales/{sale}/void', [SalesController::class, 'void']);
    Route::get('sales/{sale}/receipt-pdf', [SalesReceiptController::class, 'pdf']);
    Route::get('sales/{sale}/print-payload', [SalesReceiptController::class, 'printPayload']);
    Route::get('sales/reports/summary', [SalesReportController::class, 'summary']);
    Route::get('sales/reports/trends', [SalesReportController::class, 'trends']);
    Route::get('sales/reports/top-products', [SalesReportController::class, 'topProducts']);
});
```

---

## 15. Recommended Checkout Transaction Flow

```text
1. Validate request
2. Resolve business + branch + user
3. Check idempotency key
4. Begin DB transaction
5. Lock all stock rows used in the cart
6. Recalculate item prices, subtotal, total, cost_total, profit_total
7. Validate stock availability
8. Create sale header
9. Create sale_items rows
10. Create sale_payments rows
11. Decrease stock and create stock_movements rows
12. If credit sale:
    - increase customer balance
    - create customer_transactions debit row with source link
13. If cash sale and accounting integration enabled:
    - create IncomeTransaction
    - credit selected account
14. Generate receipt number and optional PDF path
15. Commit transaction
16. Return sale summary + receipt links
```

---

## 16. What To Avoid

- Do not let Flutter call `/stock/decrease` during checkout
- Do not let Flutter call `/customers/{id}/debit` to complete a credit sale
- Do not trust frontend totals without server recalculation
- Do not update stock without recording stock movement
- Do not use current product price when regenerating an old receipt
- Do not mix manual customer collection entries with sale-originated receivables without source tracking
- Do not allow deletion of completed sales; use `void` or `refund`
- Do not make Sales depend on PDF generation succeeding before sale commit

---

## 17. Recommended MVP Scope

For phase 1, implement only:

- sales dashboard KPI endpoint
- product search/filter endpoint for POS
- checkout endpoint
- sale list endpoint
- receipt PDF endpoint
- print payload endpoint
- sales summary/trend reports
- stock update integration
- customer balance integration for credit sales

Delay until phase 2:

- partial payments
- refunds
- returns
- Bluetooth device SDK integration
- server-side draft order sync across devices

---

## 18. Final Recommendation

The best approach for this codebase is:

1. Add `Sales` as a new business feature beside `Customers`, `Stock`, and `Profit & Loss`
2. Build dedicated `sales`, `sale_items`, and `sale_payments` tables
3. Finalize checkout through one transactional backend endpoint
4. Update stock and customer balance internally from the Sales service
5. Keep manual customer payment logic separate, but traceable through shared ledger records
6. Add receipt PDF and printer payload endpoints from the beginning
7. Decide explicitly whether Sales should also post accounting entries so existing dashboard totals remain aligned

If we follow this structure, the new Sales module will fit the current backend cleanly, support Flutter well, and stay flexible for reporting, printing, and future POS expansion.
