# Individual User - Complete API Workflow

> Personal Finance Management - FREE Account
> 
> **Base URL:** `https://api.kobac.app/api/v1`
> **Authentication:** Bearer Token (after login/register)
> **Source:** Extracted from actual backend controllers

---

## User Flow Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         INDIVIDUAL USER FLOW                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐                 │
│  │ Splash Screen│────▶│  Onboarding  │────▶│Welcome Screen│                 │
│  └──────────────┘     └──────────────┘     └──────┬───────┘                 │
│                                                    │                         │
│                                          Choose "Individual"                 │
│                                                    │                         │
│                                                    ▼                         │
│                                           ┌──────────────┐                   │
│                                           │  Info Modal  │                   │
│                                           │ (X to close) │                   │
│                                           └──────┬───────┘                   │
│                                                  │                           │
│                                                  ▼                           │
│                                           ┌──────────────┐                   │
│                                           │ Auth Screen  │                   │
│                                           │Login/Register│                   │
│                                           └──────┬───────┘                   │
│                                                  │                           │
│                               ┌──────────────────┴──────────────────┐        │
│                               │                                     │        │
│                               ▼                                     ▼        │
│                        ┌─────────────┐                      ┌─────────────┐  │
│                        │   Login     │                      │  Register   │  │
│                        │ POST /login │                      │POST /register│ │
│                        └──────┬──────┘                      └──────┬──────┘  │
│                               │                                    │         │
│                               └────────────────┬───────────────────┘         │
│                                                │                             │
│                                         Save Token                           │
│                                                │                             │
│                                                ▼                             │
│                                        ┌──────────────┐                      │
│                                        │  Dashboard   │                      │
│                                        │ (Individual) │                      │
│                                        └──────────────┘                      │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                    AVAILABLE FEATURES (FREE)                         │    │
│  ├─────────────────────────────────────────────────────────────────────┤    │
│  │ ✅ Dashboard    ✅ Accounts    ✅ Income    ✅ Expenses               │    │
│  │ ✅ Activity     ✅ Profile     ✅ Search                              │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Authentication

### 1.1 Register Individual User

**Backend:** `AuthController.php:20-73`

```
POST /api/v1/auth/register
```

**Request:**
```json
{
  "user_type": "individual",
  "name": "Ahmed Mohamed",
  "email": "ahmed@example.com",
  "phone": "+252615123456",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `user_type` | `required\|in:individual,business` |
| `name` | `required\|string\|max:255` |
| `email` | `required\|email\|unique:users,email` |
| `phone` | `nullable\|string\|max:20` |
| `password` | `required\|confirmed\|min:8` |

**Response (201):**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmed Mohamed",
      "email": "ahmed@example.com",
      "phone": "+252615123456",
      "user_type": "individual",
      "avatar": null,
      "is_active": true,
      "is_free": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "token": "1|abc123xyz..."
  }
}
```

---

### 1.2 Login

**Backend:** `AuthController.php:79-136`

```
POST /api/v1/auth/login
```

**Request:**
```json
{
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "device_name": "iPhone 15 Pro"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `email` | `required\|email` |
| `password` | `required\|string` |
| `device_name` | `nullable\|string\|max:255` |

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmed Mohamed",
      "email": "ahmed@example.com",
      "phone": "+252615123456",
      "user_type": "individual",
      "avatar": null,
      "is_active": true,
      "is_free": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "access": {
      "can_read": true,
      "can_write": true
    },
    "token": "2|xyz789abc..."
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "error_code": "INVALID_CREDENTIALS"
}
```

---

### 1.3 Get Current User (Auto-Login Check)

**Backend:** `AuthController.php:153-189`

```
GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Ahmed Mohamed",
      "email": "ahmed@example.com",
      "phone": "+252615123456",
      "user_type": "individual",
      "avatar": null,
      "is_active": true,
      "is_free": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "access": {
      "can_read": true,
      "can_write": true,
      "is_blocked": false
    }
  }
}
```

---

### 1.4 Logout

**Backend:** `AuthController.php:142-147`

```
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Phase 2: App Discovery

### 2.1 Get Available Apps/Features

**Backend:** `AppController.php:88-116`

```
GET /api/v1/apps
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_type": "individual",
    "is_free": true,
    "apps": [
      {
        "id": "dashboard",
        "name": "Dashboard",
        "icon": "home",
        "route": "/dashboard",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "accounts",
        "name": "Accounts",
        "icon": "wallet",
        "route": "/accounts",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "income",
        "name": "Income",
        "icon": "arrow-down-circle",
        "route": "/income",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "expense",
        "name": "Expenses",
        "icon": "arrow-up-circle",
        "route": "/expenses",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "activity",
        "name": "Activity",
        "icon": "clock",
        "route": "/activity",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "profile",
        "name": "Profile",
        "icon": "user",
        "route": "/profile",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "customers",
        "name": "Customers",
        "icon": "users",
        "route": "/business/customers",
        "enabled": false,
        "locked": true,
        "hidden": true
      }
    ],
    "write_blocked": false,
    "block_reason": null,
    "block_action": null
  }
}
```

**Note:** Apps with `hidden: true` should not be displayed in the UI for Individual users.

---

### 2.2 Subscription Status

**Backend:** `SubscriptionController.php:18-39`

```
GET /api/v1/subscription/status
Authorization: Bearer {token}
```

**Response (Individual - Always FREE):**
```json
{
  "success": true,
  "data": {
    "user_type": "individual",
    "is_free": true,
    "plan": "Free",
    "status": "active",
    "status_label": "FREE - Full Access",
    "can_read": true,
    "can_write": true,
    "write_blocked": false,
    "block_reason": null,
    "block_action": null,
    "trial_days_left": null,
    "is_paid": false,
    "upgrade_available": false
  }
}
```

---

## Phase 3: Dashboard

### 3.1 Get Dashboard

**Backend:** `DashboardController.php:39-113`

```
GET /api/v1/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_balance": 5000.00,
      "total_income": 8500.00,
      "total_expense": 3500.00,
      "accounts_count": 3
    },
    "currency": "USD",
    "period": {
      "from": "2026-01-01",
      "to": "2026-01-31"
    },
    "accounts": [
      {
        "id": 1,
        "name": "Cash Wallet",
        "type": "cash",
        "balance": 2500.00
      },
      {
        "id": 2,
        "name": "EVC Plus",
        "type": "mobile_money",
        "balance": 2500.00
      }
    ],
    "recent_transactions": [
      {
        "id": 1,
        "type": "income",
        "amount": 500.00,
        "category": "salary",
        "description": "Monthly salary",
        "date": "2026-01-10",
        "account_name": "Cash Wallet"
      },
      {
        "id": 2,
        "type": "expense",
        "amount": 50.00,
        "category": "food",
        "description": "Groceries",
        "date": "2026-01-09",
        "account_name": "Cash Wallet"
      }
    ]
  }
}
```

---

## Phase 4: Accounts Management

### 4.1 List Accounts

**Backend:** `AccountController.php:16-35`

```
GET /api/v1/accounts
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "accounts": [
      {
        "id": 1,
        "name": "Cash Wallet",
        "type": "cash",
        "type_label": "Cash",
        "balance": 2500.00,
        "currency": "USD",
        "provider": null,
        "account_number": null,
        "is_active": true,
        "created_at": "2026-01-01T00:00:00.000000Z"
      },
      {
        "id": 2,
        "name": "EVC Plus",
        "type": "mobile_money",
        "type_label": "Mobile Money",
        "balance": 1500.00,
        "currency": "USD",
        "provider": "Hormuud",
        "account_number": "615123456",
        "is_active": true,
        "created_at": "2026-01-01T00:00:00.000000Z"
      }
    ],
    "summary": {
      "total_balance": 4000.00,
      "currency": "USD"
    }
  }
}
```

---

### 4.2 Create Account

**Backend:** `AccountController.php:41-76`

```
POST /api/v1/accounts
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "Bank Account",
  "type": "bank",
  "provider": "Premier Bank",
  "account_number": "1234567890",
  "initial_balance": 1000.00,
  "currency": "USD"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `required\|string\|max:255` |
| `type` | `required\|in:cash,mobile_money,bank` |
| `provider` | `nullable\|string\|max:100` |
| `account_number` | `nullable\|string\|max:50` |
| `initial_balance` | `nullable\|numeric\|min:0` |
| `currency` | `nullable\|string\|max:3` |

**Response (201):**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "id": 3,
    "name": "Bank Account",
    "type": "bank",
    "type_label": "Bank",
    "balance": 1000.00,
    "currency": "USD",
    "provider": "Premier Bank",
    "account_number": "1234567890",
    "is_active": true,
    "created_at": "2026-01-10T06:30:00.000000Z"
  }
}
```

---

### 4.3 Get Account

**Backend:** `AccountController.php:82-87`

```
GET /api/v1/accounts/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Cash Wallet",
    "type": "cash",
    "type_label": "Cash",
    "balance": 2500.00,
    "currency": "USD",
    "provider": null,
    "account_number": null,
    "is_active": true,
    "created_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### 4.4 Update Account

**Backend:** `AccountController.php:93-113`

```
PUT /api/v1/accounts/{id}
Authorization: Bearer {token}
```

**Request:**
```json
{
  "name": "Updated Wallet Name",
  "provider": "Updated Provider"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `sometimes\|string\|max:255` |
| `provider` | `sometimes\|nullable\|string\|max:100` |
| `account_number` | `sometimes\|nullable\|string\|max:50` |
| `is_active` | `sometimes\|boolean` |

**Response:**
```json
{
  "success": true,
  "message": "Account updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Wallet Name",
    "type": "cash",
    "type_label": "Cash",
    "balance": 2500.00,
    "currency": "USD",
    "provider": "Updated Provider",
    "account_number": null,
    "is_active": true,
    "created_at": "2026-01-01T00:00:00.000000Z"
  }
}
```

---

### 4.5 Delete Account

**Backend:** `AccountController.php:119-134`

```
DELETE /api/v1/accounts/{id}
Authorization: Bearer {token}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

**Response (Error - Has Transactions):**
```json
{
  "success": false,
  "message": "Cannot delete account with transactions. Deactivate it instead.",
  "error_code": "HAS_TRANSACTIONS"
}
```

---

### 4.6 Deactivate Account

**Backend:** `AccountController.php:140-147`

```
POST /api/v1/accounts/{id}/deactivate
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Account deactivated successfully",
  "data": {
    "id": 1,
    "name": "Cash Wallet",
    "is_active": false
  }
}
```

---

### 4.7 Activate Account

**Backend:** `AccountController.php:153-160`

```
POST /api/v1/accounts/{id}/activate
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Account activated successfully",
  "data": {
    "id": 1,
    "name": "Cash Wallet",
    "is_active": true
  }
}
```

---

### 4.8 Account Ledger

**Backend:** `AccountController.php:166-232`

```
GET /api/v1/accounts/{id}/ledger?from=2026-01-01&to=2026-01-31
Authorization: Bearer {token}
```

**Query Parameters:**
| Param | Default | Description |
|-------|---------|-------------|
| `from` | Start of month | Date range start (YYYY-MM-DD) |
| `to` | Today | Date range end (YYYY-MM-DD) |
| `per_page` | 50 | Max 100 |

**Response:**
```json
{
  "success": true,
  "data": {
    "account": {
      "id": 1,
      "name": "Cash Wallet",
      "type": "cash",
      "balance": 2500.00
    },
    "period": {
      "from": "2026-01-01",
      "to": "2026-01-31"
    },
    "opening_balance": 2000.00,
    "closing_balance": 2500.00,
    "total_income": 1000.00,
    "total_expense": 500.00,
    "ledger": [
      {
        "id": 1,
        "type": "income",
        "amount": 500.00,
        "description": "Salary",
        "category": "salary",
        "date": "2026-01-05",
        "created_at": "2026-01-05T10:00:00.000000Z",
        "running_balance": 2500.00
      },
      {
        "id": 2,
        "type": "expense",
        "amount": -100.00,
        "description": "Groceries",
        "category": "food",
        "date": "2026-01-06",
        "created_at": "2026-01-06T14:00:00.000000Z",
        "running_balance": 2400.00
      }
    ],
    "pagination": {
      "total": 2,
      "per_page": 50,
      "default_per_page": 50,
      "max_per_page": 100
    }
  }
}
```

---

## Phase 5: Income Management

### 5.1 List Income

**Backend:** `IncomeController.php:19-45`

```
GET /api/v1/income?from=2026-01-01&to=2026-01-31&per_page=20
Authorization: Bearer {token}
```

**Query Parameters:**
| Param | Default | Description |
|-------|---------|-------------|
| `from` | None | Filter by start date |
| `to` | None | Filter by end date |
| `account_id` | None | Filter by account |
| `per_page` | 20 | Max 50 |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_id": 1,
      "account_name": "Cash Wallet",
      "amount": 500.00,
      "description": "Monthly salary",
      "category": "salary",
      "reference": "SAL-001",
      "transaction_date": "2026-01-05",
      "created_at": "2026-01-05T10:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

### 5.2 Create Income

**Backend:** `IncomeController.php:51-121`

```
POST /api/v1/income
Authorization: Bearer {token}
```

**Request:**
```json
{
  "account_id": 1,
  "amount": 500.00,
  "description": "Freelance work",
  "category": "freelance",
  "reference": "FRL-001",
  "transaction_date": "2026-01-10"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `account_id` | `required\|exists:accounts,id` |
| `amount` | `required\|numeric\|min:0.01` |
| `description` | `nullable\|string\|max:500` |
| `category` | `nullable\|string\|max:100` |
| `reference` | `nullable\|string\|max:100` |
| `transaction_date` | `required\|date` |

**Response (201):**
```json
{
  "success": true,
  "message": "Income recorded successfully",
  "data": {
    "transaction": {
      "id": 2,
      "amount": 500.00,
      "description": "Freelance work",
      "transaction_date": "2026-01-10"
    },
    "account": {
      "id": 1,
      "name": "Cash Wallet",
      "previous_balance": 2500.00,
      "new_balance": 3000.00
    }
  }
}
```

---

### 5.3 Get Income

**Backend:** `IncomeController.php:127-132`

```
GET /api/v1/income/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "account_id": 1,
    "account_name": "Cash Wallet",
    "amount": 500.00,
    "description": "Monthly salary",
    "category": "salary",
    "reference": "SAL-001",
    "transaction_date": "2026-01-05",
    "created_at": "2026-01-05T10:00:00.000000Z"
  }
}
```

---

### 5.4 Update Income

**Backend:** `IncomeController.php:138-157`

```
PUT /api/v1/income/{id}
Authorization: Bearer {token}
```

**Request:**
```json
{
  "description": "Updated description",
  "category": "updated_category"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `description` | `sometimes\|nullable\|string\|max:500` |
| `category` | `sometimes\|nullable\|string\|max:100` |
| `reference` | `sometimes\|nullable\|string\|max:100` |

**Response:**
```json
{
  "success": true,
  "message": "Income updated successfully",
  "data": {
    "id": 1,
    "amount": 500.00,
    "description": "Updated description",
    "category": "updated_category"
  }
}
```

---

### 5.5 Delete Income

**Backend:** `IncomeController.php:163-184`

```
DELETE /api/v1/income/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Income deleted successfully"
}
```

**Note:** Deleting income reverses the account balance automatically.

---

## Phase 6: Expense Management

### 6.1 List Expenses

**Backend:** `ExpenseController.php:19-45`

```
GET /api/v1/expenses?from=2026-01-01&to=2026-01-31&per_page=20
Authorization: Bearer {token}
```

**Query Parameters:**
| Param | Default | Description |
|-------|---------|-------------|
| `from` | None | Filter by start date |
| `to` | None | Filter by end date |
| `account_id` | None | Filter by account |
| `per_page` | 20 | Max 50 |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "account_id": 1,
      "account_name": "Cash Wallet",
      "amount": 50.00,
      "description": "Groceries",
      "category": "food",
      "reference": "GRO-001",
      "transaction_date": "2026-01-06",
      "created_at": "2026-01-06T14:00:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

---

### 6.2 Create Expense

**Backend:** `ExpenseController.php:51-121`

```
POST /api/v1/expenses
Authorization: Bearer {token}
```

**Request:**
```json
{
  "account_id": 1,
  "amount": 50.00,
  "description": "Lunch",
  "category": "food",
  "reference": "LUN-001",
  "transaction_date": "2026-01-10"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `account_id` | `required\|exists:accounts,id` |
| `amount` | `required\|numeric\|min:0.01` |
| `description` | `nullable\|string\|max:500` |
| `category` | `nullable\|string\|max:100` |
| `reference` | `nullable\|string\|max:100` |
| `transaction_date` | `required\|date` |

**Response (201):**
```json
{
  "success": true,
  "message": "Expense recorded successfully",
  "data": {
    "transaction": {
      "id": 2,
      "amount": 50.00,
      "description": "Lunch",
      "transaction_date": "2026-01-10"
    },
    "account": {
      "id": 1,
      "name": "Cash Wallet",
      "previous_balance": 3000.00,
      "new_balance": 2950.00
    }
  }
}
```

---

### 6.3 Get Expense

**Backend:** `ExpenseController.php:127-132`

```
GET /api/v1/expenses/{id}
Authorization: Bearer {token}
```

---

### 6.4 Update Expense

**Backend:** `ExpenseController.php:138-157`

```
PUT /api/v1/expenses/{id}
Authorization: Bearer {token}
```

---

### 6.5 Delete Expense

**Backend:** `ExpenseController.php:163-176`

```
DELETE /api/v1/expenses/{id}
Authorization: Bearer {token}
```

**Note:** Deleting expense reverses the account balance automatically.

---

## Phase 7: Activity & Profile

### 7.1 Activity Timeline

**Backend:** `ActivityController.php:16-77`

```
GET /api/v1/activity?per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "income_1",
      "type": "income",
      "description": "Recorded income: Monthly salary",
      "amount": 500.00,
      "account_name": "Cash Wallet",
      "timestamp": "2026-01-05T10:00:00.000000Z",
      "date": "2026-01-05"
    },
    {
      "id": "expense_1",
      "type": "expense",
      "description": "Recorded expense: Groceries",
      "amount": 50.00,
      "account_name": "Cash Wallet",
      "timestamp": "2026-01-06T14:00:00.000000Z",
      "date": "2026-01-06"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 2
  }
}
```

---

### 7.2 Update Profile

**Backend:** `ProfileController.php:15-52`

```
PUT /api/v1/profile
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request:**
```json
{
  "name": "Ahmed Mohamed Updated",
  "phone": "+252615111222",
  "avatar": "(file upload)"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `sometimes\|string\|max:255` |
| `phone` | `sometimes\|nullable\|string\|max:20` |
| `avatar` | `sometimes\|nullable\|image\|max:2048` |

**Response:**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "Ahmed Mohamed Updated",
    "email": "ahmed@example.com",
    "phone": "+252615111222",
    "avatar": "https://api.kobac.app/storage/avatars/abc123.jpg"
  }
}
```

---

## Error Handling

### Standard Error Response

```json
{
  "success": false,
  "message": "Error description",
  "error_code": "ERROR_CODE",
  "data": {
    "errors": {
      "field_name": ["Validation error message"]
    }
  }
}
```

### Common Error Codes

| Code | HTTP | Description |
|------|------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `INVALID_CREDENTIALS` | 401 | Wrong email/password |
| `ACCOUNT_DEACTIVATED` | 403 | User account is deactivated |
| `UNAUTHORIZED` | 403 | No permission for resource |
| `HAS_TRANSACTIONS` | 400 | Cannot delete account with transactions |

---

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/auth/register` | Register new user |
| `POST` | `/auth/login` | Login |
| `POST` | `/auth/logout` | Logout |
| `GET` | `/auth/me` | Get current user |
| `GET` | `/apps` | Get available features |
| `GET` | `/subscription/status` | Get subscription status |
| `GET` | `/dashboard` | Get dashboard data |
| `GET` | `/accounts` | List accounts |
| `POST` | `/accounts` | Create account |
| `GET` | `/accounts/{id}` | Get account |
| `PUT` | `/accounts/{id}` | Update account |
| `DELETE` | `/accounts/{id}` | Delete account |
| `POST` | `/accounts/{id}/deactivate` | Deactivate account |
| `POST` | `/accounts/{id}/activate` | Activate account |
| `GET` | `/accounts/{id}/ledger` | Get account ledger |
| `GET` | `/income` | List income |
| `POST` | `/income` | Create income |
| `GET` | `/income/{id}` | Get income |
| `PUT` | `/income/{id}` | Update income |
| `DELETE` | `/income/{id}` | Delete income |
| `GET` | `/expenses` | List expenses |
| `POST` | `/expenses` | Create expense |
| `GET` | `/expenses/{id}` | Get expense |
| `PUT` | `/expenses/{id}` | Update expense |
| `DELETE` | `/expenses/{id}` | Delete expense |
| `GET` | `/activity` | Get activity timeline |
| `PUT` | `/profile` | Update profile |

---

*Source: Backend Controllers - January 10, 2026*
