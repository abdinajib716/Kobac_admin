# Kobac API Postman Collections

## Files

| File | Description |
|------|-------------|
| `Kobac_API_Environment.json` | Environment variables with auto-token support |
| `Kobac_API_Collection.json` | Core API (Auth, Accounts, Income, Expenses, etc.) |
| `Kobac_Business_Collection.json` | Business-only API (Customers, Vendors, Stock, etc.) |

---

## Quick Start

### 1. Import Environment
1. Open Postman
2. Click **Import** → Select `Kobac_API_Environment.json`
3. Set `base_url` to your API URL (default: `https://kobac.cajiibcreative.com/api/v1`)

### 2. Import Collections
1. Click **Import** → Select both collection JSON files
2. Collections will appear in sidebar

### 3. Select Environment
1. Click environment dropdown (top-right)
2. Select **"Kobac API Environment"**

---

## Auto-Token Feature ✨

**No manual token copying needed!**

The collections include automatic scripts that:
- Save `token` after **Login** or **Register**
- Save `user_id`, `user_type`, `business_id`
- Save resource IDs (`account_id`, `customer_id`, etc.) on List/Create
- Clear `token` on **Logout**

### How it works:
```javascript
// Auto-runs after Login/Register
var r = pm.response.json();
if (r.success && r.data.token) {
    pm.environment.set('token', r.data.token);
    pm.environment.set('user_id', r.data.user.id);
}
```

---

## Environment Variables

| Variable | Auto-Set By | Description |
|----------|-------------|-------------|
| `base_url` | Manual | API base URL |
| `token` | Login/Register | Bearer token (auto-saved) |
| `user_id` | Login/Register | Current user ID |
| `user_type` | Login/Register | `individual` or `business` |
| `business_id` | Login | Business ID (if business user) |
| `branch_id` | List Branches | Branch ID for context |
| `account_id` | List/Create Account | Account ID |
| `income_id` | List/Create Income | Income ID |
| `expense_id` | List/Create Expense | Expense ID |
| `customer_id` | List/Create Customer | Customer ID |
| `vendor_id` | List/Create Vendor | Vendor ID |
| `stock_id` | List/Create Stock | Stock item ID |

---

## Collection Structure

### Core API (Kobac_API_Collection.json)
1. **Authentication** - Register, Login, Logout, Me
2. **Profile** - Update profile
3. **Dashboard** - Individual & Business dashboards
4. **Accounts** - CRUD + Deactivate/Activate + Ledger
5. **Income** - CRUD with date filters
6. **Expenses** - CRUD with date filters
7. **Subscription & Plans** - Status, Details, Upgrade options
8. **Activity & Search** - Timeline, Global search, App features

### Business API (Kobac_Business_Collection.json)
9. **Business Setup & Profile** - Initial setup, profile management
10. **Branches** - Multi-location support
11. **Customers (Receivables)** - CRUD + Debit/Credit + Transactions + Summary
12. **Vendors (Payables)** - CRUD + Credit/Debit + Transactions + Summary
13. **Stock (Inventory)** - CRUD + Increase/Decrease + Movements
14. **Profit & Loss** - P&L report

---

## Pagination Limits

| Resource | Default | Max |
|----------|---------|-----|
| Income/Expense | 20 | 50 |
| Customers/Vendors | 20 | 50 |
| Stock Items | 20 | 50 |
| Account Ledger | 50 | 100 |
| Search Results | 5/type | 10/type |

---

## Testing Workflow

### Individual User Flow:
1. Register Individual → Token auto-saved
2. Create Account → account_id auto-saved
3. Create Income/Expense
4. View Dashboard
5. View Account Ledger

### Business User Flow:
1. Register Business → Token auto-saved
2. Setup Business → business_id auto-saved
3. Create Branch → branch_id auto-saved
4. Create Customer → customer_id auto-saved
5. Debit/Credit Customer
6. View Receivables Summary
7. Create Stock Item → stock_id auto-saved
8. Increase/Decrease Stock
9. View Stock Movements
10. View P&L Report

---

## Verified Endpoints: 73 ✅

All endpoints validated against Laravel route:list on Jan 3, 2026.
