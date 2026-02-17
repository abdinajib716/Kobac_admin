# Product Map + QA Report (Single Source of Truth)

**Audience:** Non-technical business owner

**Important verification note (scope):**
- This report is based on what is present in the provided workspace.
- The workspace contains the server-side application logic and the admin back office.
- The mobile app source code was **not found** in this workspace, so:
  - Mobile **tab names and exact screen layouts** cannot be confirmed.
  - Mobile flows below describe **what the product supports** (what a user can do and what the system will store/calculate), but the exact UI steps may differ.

---

# A) Product Map (What Exists)

## A1) Mobile Screens (Grouped by Tabs)

> Because the mobile app code is not included here, the list below is a **functional map** (what the app must be able to show/do to match the system’s behavior). Your Flutter team should confirm the exact screen names and navigation.

### Tab: Apps
**What the user sees**
- A grid/list of modules available to the user.
- For business users, modules can appear:
  - Available
  - Hidden (not included in their plan)
  - Locked (visible but blocked when the subscription is not allowing changes)

**Actions the user can do**
- Open an available module.
- If locked, user is guided to take the next action (upgrade / renew / wait for approval).

**What changes after actions**
- Nothing financial changes directly on this screen.
- It controls what areas the user can access.

**Key product behavior**
- Individual accounts: core finance modules are available; business-only modules are hidden/locked.
- Business accounts: module visibility depends on the active plan’s feature toggles.
- Business accounts can become **read-only** (blocked from creating/updating/deleting) based on subscription state.

---

### Tab: Dashboard
#### Individual Dashboard
**What the user sees**
- Total balance across their accounts.
- Monthly income total.
- Monthly expense total.
- Count of accounts.
- Recent activity (mix of income and expense entries).

**Actions the user can do**
- Navigate to accounts.
- Navigate to add income.
- Navigate to add expense.

**What changes after actions**
- Adding income increases the selected account’s balance.
- Adding expense decreases the selected account’s balance.
- Dashboard totals update accordingly.

#### Business Dashboard
**What the user sees**
- Business summary (monthly totals): income, expense.
- Receivables summary (customers who owe the business): total owed + count.
- Payables summary (vendors the business owes): total owed + count.
- Net position (receivables minus payables).
- Stock overview: number of products + inventory value.
- Monthly profit/loss (income minus expense).
- Optional comparison across branches (when viewing the whole business).

**Actions the user can do**
- Switch context to a branch (see “Branch switching flow”).
- Navigate to each module from the dashboard.

**What changes after actions**
- Dashboard is a reflection screen; values change after activities in other modules.

---

### Tab: Activity
**What the user sees**
- A timeline of money-related entries:
  - “Recorded income …”
  - “Recorded expense …”
- Each entry shows: type, amount, account name, timestamp, and date.

**Actions the user can do**
- Scroll / paginate activity.

**What changes after actions**
- Nothing is changed by browsing activity.

**Key product behavior**
- Customer/vendor balance adjustments are **not** described here as activity events.
- Stock movements are **not** described here as activity events.

---

### Tab: Profile
**What the user sees**
- Personal details (name, phone, avatar).
- For business users: subscription/access status (trial/active/expired/pending approval), days remaining.

**Actions the user can do**
- Update profile details.
- Change password.
- Log out.

**What changes after actions**
- Profile fields update.
- Password change ends access to other sessions (depending on how the app handles tokens).

---

## A2) Admin Panel Screens (Back Office)

### Who uses it
- Internal admin staff (system operator).

### What can be created/edited/controlled
#### Subscription Management
- **Business Plans**
  - Create/edit business plans.
  - Pricing & billing cycles.
  - Trial settings (on/off + trial days).
  - Feature access toggles per plan (accounts, income, expense, customers, vendors, stock, branches, profit/loss, dashboard).
  - Choose a default plan.
  - Prevent deletion of a plan that already has subscribers.

- **Subscriptions**
  - View subscriptions.
  - Filter by status (trial, active, expired, cancelled, pending approval).
  - No direct editing/creating/deleting from admin panel.

#### Payments
- **Payment Transactions**
  - View payment attempts and payment history.
  - Filter by payment type (online vs offline) and status.
  - For offline payments:
    - Approve (activates subscription access)
    - Reject (requires a rejection reason)

#### Notifications
- **Push Notifications**
  - Compose and send notifications.
  - Choose target audience:
    - All mobile users
    - Individual users only
    - Business users only
    - A specific user
  - Optional custom payload data (for app navigation/deep links).
  - Track delivery counts and failures.

#### Location Management
- Countries
- Regions
- Districts

#### Business Data
- Business context viewer (used for support/inspection).
- Customer viewer (admin-side listing).
- Mobile users viewer.

#### Access Control
- Roles
- Permissions
- Admin users management

#### System
- Settings screen (branding / configuration).
- System features screen.
- Activity log viewer.

---

## A3) End-to-End Flows (Step-by-Step)

### Flow 1: First Launch → Service Selection → Plan → Trial/Payment → Account Creation
#### Individual user
1) User selects “Individual”.
2) User registers with basic identity details.
3) User enters the app immediately with full access.
4) User creates accounts (cash, bank, etc.).
5) User starts recording income and expenses.

**Outcome:** Individual is always fully functional; no paid plan required.

#### Business user
1) User selects “Business”.
2) User chooses a business plan at registration.
3) User starts on a trial or paid cycle depending on plan configuration.
4) If trial expires or subscription expires:
   - User can still view, but cannot make changes.
5) If payment is offline and pending approval:
   - User can be blocked from changes until admin approval.

**Outcome:** Business access is feature-gated by plan and can become read-only.

---

### Flow 2: First Login → Business Setup Wizard → Main Branch → Initial Accounts → Dashboard
1) Business user logs in.
2) If business is not yet set up, the user is directed to a setup step.
3) The user provides business profile details.
4) The user creates the main branch.
5) The user creates initial accounts.
6) The dashboard becomes meaningful once accounts + entries exist.

**Outcome:** A business must be set up before business reporting screens work.

---

### Flow 3: Branch Switching Flow
1) Business user chooses a branch context (or defaults to main branch).
2) All “branch-scoped” data views and summaries reflect that branch.
3) If the user chooses an invalid/inactive branch:
   - The system rejects it with a clear error.

**Outcome:** Users can operate in a single-branch view or whole-business view.

---

### Flow 4: Customers (Receivables) Flow
1) User creates a customer profile.
2) When the customer takes goods/services on credit:
   - User records a “debit” on the customer.
   - Customer balance increases (customer owes more).
3) When the customer pays:
   - User records a “credit” on the customer.
   - Customer balance decreases.
   - The system **prevents overpayment**:
     - You cannot record a payment that is more than what the customer owes.
4) User can view customer transaction history.
5) User can deactivate/reactivate a customer.

**Outcome:** Customer balance is a receivable tracker and does not automatically move money into cash/bank accounts.

---

### Flow 5: Vendors (Payables) Flow
1) User creates a vendor profile.
2) When the business takes goods on credit from a vendor:
   - User records a “credit” on the vendor.
   - Vendor balance increases (business owes more).
3) When the business pays the vendor:
   - User records a “debit” on the vendor.
   - Vendor balance decreases.
   - The system **prevents overpayment**:
     - You cannot record a payment that is more than what the business owes.
4) User can view vendor transaction history.
5) User can deactivate/reactivate a vendor.

**Outcome:** Vendor balance is a payable tracker and does not automatically create an expense record.

---

### Flow 6: Income Flow
1) User selects an account.
2) User records income amount + category + description + date.
3) The selected account balance increases.
4) Dashboard and profit/loss reflect the change.

---

### Flow 7: Expense Flow
1) User selects an account.
2) User records expense amount + category + description + date.
3) The selected account balance decreases.
4) Dashboard and profit/loss reflect the change.

---

### Flow 8: Stock Flow
1) User creates products with:
   - Name, optional SKU, quantity, optional prices, optional image.
   - Optional low-stock alert threshold.
2) User can edit product details (including prices).
3) User can delete a product only if it has no movement history.
4) User can deactivate/reactivate a product.
5) User increases or decreases quantity with a reason and optional reference.
6) User views movement history, including before/after quantities.
7) Stock list shows a real-time summary:
   - Total number of products
   - Total quantity across products
   - Total value (cost-based and selling-based)
   - Count of products that are at/below their low-stock threshold

**Outcome:** Stock movement does not automatically create income/expense records.

---

### Flow 9: Accounts Flow
1) User creates accounts (cash/bank/mobile money).
2) User can activate/deactivate accounts.
3) Income/expense entries affect account balances.
4) User can view account ledger (running balance history).

---

### Flow 10: Profit & Loss Viewing Flow
1) Business user selects a period (from/to dates).
2) System calculates:
   - Total income
   - Total expense
   - Net profit/loss
3) System can break down totals by category.

**Outcome:** Profit & loss is driven only by income and expense records (not customer/vendor balances, not stock movements).

---

### Flow 11: Trial Expiry Behavior Flow
1) Business starts in trial (if enabled on chosen plan).
2) While trial is active, user can fully operate.
3) When trial expires:
   - The business becomes blocked from making changes.
   - The user is guided to upgrade.
4) When a paid subscription expires:
   - The business becomes blocked from making changes.
   - The user is guided to renew.
5) If an offline payment is submitted and awaiting approval:
   - The business may be blocked from changes until approval.

---

# B) Roles & Permissions (Truth From Code)

## Business Admin (Owner/Admin)
**What they can view**
- All business data.
- All branches.
- All customers/vendors/stock.

**What they can edit/create/delete**
- Full access within the business modules.

**What is blocked**
- If the subscription is not allowing changes, even admins become blocked from create/update/delete.

---

## Branch User (Staff)
**What they can view**
- Intended: business data within their assigned branch context.

**What they can edit/create/delete**
- Intended: only actions explicitly granted via a permission map.

**Important QA risk (permission clarity)**
- The staff permission system exists, but the enforcement points need to be verified across all modules.
- There is a strong risk of “partial enforcement” (some screens may enforce it, others may not) unless consistently applied everywhere.

---

## Cross-Branch Access Risk
**Branch selection behavior is validated** (invalid branch is rejected).

**QA risk:**
- Some screens accept a “branch filter” and may not always validate branch ownership consistently (needs systematic test across every business module).

---

# C) Rules & Calculations (Truth From Code)

## C1) Account balances
- Income increases the selected account balance.
- Expense decreases the selected account balance.
- Ledger is derived from those entries.

## C2) Customer balances (Receivables)
- “Debit customer” means the customer owes more → balance increases.
- “Credit customer” means customer paid → balance decreases.
- Overpayment is not allowed:
  - If customer owes nothing, payment is blocked.
  - If payment exceeds amount owed, payment is blocked.
- Customer payments do **not** automatically increase any cash/bank account.

## C3) Vendor balances (Payables)
- “Credit vendor” means business owes more → balance increases.
- “Debit vendor” means business paid → balance decreases.
- Overpayment is not allowed:
  - If business owes nothing, payment is blocked.
  - If payment exceeds amount owed, payment is blocked.
- Vendor payments do **not** automatically create an expense.

## C4) Profit & Loss
- Calculated from income and expense only.
- Customer/vendor balances do not affect profit/loss until income/expense is recorded.
- Stock changes do not affect profit/loss.

## C5) Stock & inventory totals
- Product list provides:
  - Total products count
  - Total quantity
  - Total inventory value (selling-price based for dashboard summary)
- Low-stock rules:
  - A product can have a low-stock threshold.
  - If quantity is at/below threshold, it is flagged as low stock.

## C6) Subscription and write-blocking
- Individual users: not blocked by subscription.
- Business users:
  - Can read data in most states.
  - Can only create/update/delete when trial is active or subscription is active.
  - Expired or no subscription blocks changes.
  - Pending approval scenarios exist.

---

# D) Redundancy & Conflicts Report

> This section highlights areas likely to become confusing or inconsistent in real usage.

## D1) Two parallel permission systems
**What is conflicting**
- There is an admin “roles & permissions” system.
- There is also an in-business “owner/admin/staff + permissions map” system.

**Why it is dangerous**
- Staff might be blocked in some places and allowed in others.
- A business owner may believe staff restrictions exist, but they may not consistently apply.

**Which version to keep (recommendation)**
- Keep the simpler business-facing roles: Owner/Admin/Staff with explicit module permissions.
- Use the admin “roles & permissions” only for internal admin panel access.

## D2) Branch context vs branch filtering
**What is conflicting**
- Some views default to “main branch,” while others allow a branch filter.

**Why it is confusing**
- Users may believe they are viewing one branch while actually viewing all-business totals.

**Which version to keep (recommendation)**
- Standardize: the app must always show the currently selected branch clearly and consistently.

---

# E) PRD Fit Check (Gap Analysis)

**PRD not provided yet.**
Once you paste the PRD, I will produce:
- ✅ Matches PRD
- ⚠️ Partially matches
- ❌ Missing

---

# F) Final Clean Product Description (One Truth)

KOBAC is a finance tracking system with two service modes:

## Individual service
- For personal money management.
- Users create accounts and record income and expenses.
- The app shows totals and a recent activity timeline.

## Business service
- For a business that may operate across branches.
- Access is subscription-based and can become read-only if the subscription is not active.
- The business can:
  - Track money in/out (income/expense) per branch or whole business.
  - Track customer receivables (who owes the business).
  - Track vendor payables (who the business owes).
  - Track inventory quantities and value, including low-stock warnings.
  - View profit/loss reports.

## What the system intentionally does NOT do (based on current behavior)
- Customer payments do not automatically increase cash/bank balances.
- Vendor payments do not automatically create expense records.
- Inventory increases/decreases do not automatically create income/expense records.

---

# G) Questions You Must Answer (Only If Needed)

1) Where is the Flutter mobile codebase located (path or repo URL)?
2) Please paste the PRD (or attach the PRD file text).
3) For business staff users: do you want permissions to be:
   - Simple (module-level access only), or
   - Detailed (separate permissions for create/edit/delete per module)?
4) For customer/vendor payments: should the product eventually link these to cash/bank accounts (to keep ledgers consistent), or should they remain “balance trackers only”?
