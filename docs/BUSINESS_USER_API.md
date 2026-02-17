# Business User - Complete API Workflow

> Business Management - Subscription-Based Account
> 
> **Base URL:** `https://api.kobac.app/api/v1`
> **Authentication:** Bearer Token (after login/register)
> **Branch Context:** `X-Branch-ID` header (optional, defaults to main branch)
> **Source:** Extracted from actual backend controllers

---

## User Flow Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           BUSINESS USER FLOW                                     │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐                     │
│  │ Splash Screen│────▶│  Onboarding  │────▶│Welcome Screen│                     │
│  └──────────────┘     └──────────────┘     └──────┬───────┘                     │
│                                                    │                             │
│                                          Choose "Business"                       │
│                                                    │                             │
│                                                    ▼                             │
│                                           ┌──────────────┐                       │
│                                           │ Plans Screen │                       │
│                                           │ GET /plans   │                       │
│                                           └──────┬───────┘                       │
│                                                  │                               │
│                                           Select Plan                            │
│                                                  │                               │
│                                                  ▼                               │
│                                           ┌──────────────┐                       │
│                                           │ Auth Screen  │                       │
│                                           │   Register   │                       │
│                                           └──────┬───────┘                       │
│                                                  │                               │
│                                       ┌──────────┴──────────┐                    │
│                                       │                     │                    │
│                                       ▼                     ▼                    │
│                                ┌─────────────┐       ┌─────────────┐             │
│                                │   Trial?    │       │    Paid?    │             │
│                                │ (14 days)   │       │  (Payment)  │             │
│                                └──────┬──────┘       └──────┬──────┘             │
│                                       │                     │                    │
│                                       │              ┌──────┴──────┐             │
│                                       │              │             │             │
│                                       │              ▼             ▼             │
│                                       │       ┌──────────┐  ┌──────────┐         │
│                                       │       │  Online  │  │ Offline  │         │
│                                       │       │ WaafiPay │  │ Payment  │         │
│                                       │       └────┬─────┘  └────┬─────┘         │
│                                       │            │             │               │
│                                       │            ▼             ▼               │
│                                       │       ┌────────┐   ┌──────────┐          │
│                                       │       │ Active │   │ Pending  │          │
│                                       │       └───┬────┘   │ Approval │          │
│                                       │           │        └────┬─────┘          │
│                                       │           │             │                │
│                                       └─────┬─────┴─────────────┘                │
│                                             │                                    │
│                                             ▼                                    │
│                                      ┌──────────────┐                            │
│                                      │Business Setup│                            │
│                                      │ POST /setup  │                            │
│                                      └──────┬───────┘                            │
│                                             │                                    │
│                                             ▼                                    │
│                                      ┌──────────────┐                            │
│                                      │  Dashboard   │                            │
│                                      │  (Business)  │                            │
│                                      └──────────────┘                            │
│                                                                                  │
│  ┌─────────────────────────────────────────────────────────────────────────┐    │
│  │                    AVAILABLE FEATURES (Based on Plan)                    │    │
│  ├─────────────────────────────────────────────────────────────────────────┤    │
│  │ ✅ Dashboard    ✅ Accounts    ✅ Income    ✅ Expenses                   │    │
│  │ ✅ Customers    ✅ Vendors     ✅ Stock     ✅ Profit & Loss              │    │
│  │ ✅ Branches     ✅ Activity    ✅ Profile   ✅ Search                     │    │
│  └─────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Plans & Registration

### 1.1 Get Available Plans

**Backend:** `PlanController.php:22-52`

```
GET /api/v1/plans
```

**Response:**
```json
{
  "success": true,
  "data": {
    "plans": [
      {
        "id": 1,
        "name": "Starter",
        "slug": "starter",
        "description": "Perfect for small businesses",
        "price": 9.99,
        "currency": "USD",
        "billing_cycle": "monthly",
        "trial_enabled": true,
        "trial_days": 14,
        "features": {
          "accounts": true,
          "income": true,
          "expense": true,
          "customers": true,
          "vendors": true,
          "stock": true,
          "profit_loss": true,
          "branches": false
        },
        "is_default": true,
        "is_recommended": true
      },
      {
        "id": 2,
        "name": "Professional",
        "slug": "professional",
        "description": "For growing businesses",
        "price": 19.99,
        "currency": "USD",
        "billing_cycle": "monthly",
        "trial_enabled": true,
        "trial_days": 14,
        "features": {
          "accounts": true,
          "income": true,
          "expense": true,
          "customers": true,
          "vendors": true,
          "stock": true,
          "profit_loss": true,
          "branches": true
        },
        "is_default": false,
        "is_recommended": false
      }
    ],
    "default_plan_id": 1,
    "note": "Individual users are FREE and do not need to select a plan"
  }
}
```

---

### 1.2 Register Business User

**Backend:** `AuthController.php:20-73`

```
POST /api/v1/auth/register
```

**Request:**
```json
{
  "user_type": "business",
  "name": "Ali Mohamed",
  "email": "ali@business.com",
  "phone": "+252615987654",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "plan_id": 1
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
| `plan_id` | `required_if:user_type,business\|exists:plans,id` |

**Response (201):**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Ali Mohamed",
      "email": "ali@business.com",
      "phone": "+252615987654",
      "user_type": "business",
      "avatar": null,
      "is_active": true,
      "is_free": false,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "subscription": {
      "id": 1,
      "plan_name": "Starter",
      "status": "trial",
      "trial_ends_at": "2026-01-24T06:30:00.000000Z",
      "days_remaining": 14
    },
    "token": "1|abc123xyz..."
  }
}
```

---

### 1.3 Login

**Backend:** `AuthController.php:79-136`

```
POST /api/v1/auth/login
```

**Request:**
```json
{
  "email": "ali@business.com",
  "password": "SecurePass123",
  "device_name": "iPhone 15 Pro"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Ali Mohamed",
      "email": "ali@business.com",
      "phone": "+252615987654",
      "user_type": "business",
      "avatar": null,
      "is_active": true,
      "is_free": false,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "subscription": {
      "id": 1,
      "plan_name": "Starter",
      "status": "trial",
      "trial_ends_at": "2026-01-24T06:30:00.000000Z",
      "days_remaining": 14
    },
    "access": {
      "can_read": true,
      "can_write": true
    },
    "token": "2|xyz789abc..."
  }
}
```

---

## Phase 2: Payment (For Non-Trial Users)

### 2.1 Get Payment Methods

**Backend:** `PaymentController.php:26-41`

```
GET /api/v1/payment/methods
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "type": "online",
      "name": "Mobile Wallet (WaafiPay)",
      "description": "Pay instantly using EVC Plus, Zaad, Jeeb, or Sahal",
      "providers": [
        {"id": "evc_plus", "name": "EVC Plus", "prefix": "61"},
        {"id": "zaad", "name": "Zaad", "prefix": "63"},
        {"id": "jeeb", "name": "Jeeb", "prefix": "65"}
      ],
      "is_instant": true
    },
    {
      "type": "offline",
      "name": "Offline Payment",
      "description": "Bank transfer, cash, or other manual payment methods",
      "instructions": "Transfer to Premier Bank...",
      "is_instant": false,
      "requires_approval": true
    }
  ]
}
```

---

### 2.2 Initiate Online Payment (WaafiPay)

**Backend:** `PaymentController.php:138-175`

```
POST /api/v1/payment/initiate
Authorization: Bearer {token}
```

**Request:**
```json
{
  "phone_number": "615123456",
  "amount": 9.99,
  "wallet_type": "evc_plus",
  "description": "Starter Plan - Monthly"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `phone_number` | `required\|string\|regex:/^(61\|62\|63\|65\|68\|71\|90)\d{7}$/` |
| `amount` | `required\|numeric\|min:0.01\|max:10000` |
| `wallet_type` | `nullable\|in:evc_plus,zaad,jeeb,sahal` |
| `description` | `nullable\|string\|max:500` |

**Response (Success):**
```json
{
  "success": true,
  "message": "Payment initiated. Please approve on your phone.",
  "data": {
    "transaction_id": "TXN-20260110-ABC123",
    "status": "pending",
    "amount": 9.99,
    "currency": "USD"
  }
}
```

---

### 2.3 Check Online Payment Status

**Backend:** `PaymentController.php:177-198`

```
POST /api/v1/payment/status
Authorization: Bearer {token}
```

**Request:**
```json
{
  "reference_id": "TXN-20260110-ABC123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "status": "success",
  "message": "Payment completed successfully",
  "data": {
    "transaction_id": "TXN-20260110-ABC123",
    "amount": 9.99,
    "currency": "USD",
    "subscription": {
      "status": "active",
      "ends_at": "2026-02-10T06:30:00.000000Z"
    }
  }
}
```

---

### 2.4 Initiate Offline Payment

**Backend:** `PaymentController.php:47-90` + `OfflinePaymentService.php:72-192`

```
POST /api/v1/payment/offline
Authorization: Bearer {token}
```

**Request:**
```json
{
  "plan_id": 1,
  "proof_of_payment": "base64_encoded_image_or_url"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `plan_id` | `required\|exists:plans,id` |
| `proof_of_payment` | `nullable\|string` |

**Response (201):**
```json
{
  "success": true,
  "status": "pending_approval",
  "message": "Payment request submitted successfully. Waiting for admin approval.",
  "data": {
    "transaction_id": 1,
    "reference_id": "OFF-20260110063000-ABC123",
    "subscription_id": 1,
    "instructions": "Please transfer the payment to:\nBank: Premier Bank\nAccount: 1234567890\nName: Kobac Ltd.",
    "plan": {
      "id": 1,
      "name": "Starter",
      "price": 9.99,
      "currency": "USD"
    },
    "amount": 9.99,
    "currency": "USD"
  }
}
```

---

### 2.5 Check Offline Payment Status

**Backend:** `PaymentController.php:96-117` + `OfflinePaymentService.php:355-394`

```
POST /api/v1/payment/offline/status
Authorization: Bearer {token}
```

**Request:**
```json
{
  "reference_id": "OFF-20260110063000-ABC123"
}
```

**Response (Pending):**
```json
{
  "success": true,
  "status": "pending_approval",
  "message": "Your payment is pending admin approval.",
  "transaction": {
    "id": 1,
    "reference_id": "OFF-20260110063000-ABC123",
    "amount": 9.99,
    "currency": "USD",
    "status": "pending_approval",
    "payment_type": "offline",
    "created_at": "2026-01-10T06:30:00.000000Z",
    "approved_at": null,
    "rejection_reason": null
  },
  "subscription": {
    "id": 1,
    "status": "pending_payment",
    "plan_name": "Starter"
  }
}
```

**Response (Approved):**
```json
{
  "success": true,
  "status": "approved",
  "message": "Your payment has been approved. Subscription is active.",
  "transaction": {
    "id": 1,
    "reference_id": "OFF-20260110063000-ABC123",
    "amount": 9.99,
    "currency": "USD",
    "status": "approved",
    "payment_type": "offline",
    "created_at": "2026-01-10T06:30:00.000000Z",
    "approved_at": "2026-01-10T08:00:00.000000Z"
  },
  "subscription": {
    "id": 1,
    "status": "active",
    "plan_name": "Starter"
  }
}
```

---

### 2.6 Get Offline Payment Instructions

**Backend:** `PaymentController.php:123-136`

```
GET /api/v1/payment/offline/instructions
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "instructions": "Please transfer the payment to:\nBank: Premier Bank\nAccount: 1234567890\nName: Kobac Ltd.\n\nAfter payment, your subscription will be activated within 24 hours."
}
```

---

## Phase 3: Business Setup

### 3.1 Complete Business Setup

**Backend:** `SetupController.php:20-118`

```
POST /api/v1/business/setup
Authorization: Bearer {token}
```

**Request:**
```json
{
  "business": {
    "name": "Ali Trading Co.",
    "legal_name": "Ali Trading Company Ltd.",
    "phone": "+252615987654",
    "email": "info@alitrading.com",
    "address": "Mogadishu, Somalia",
    "currency": "USD"
  },
  "main_branch": {
    "name": "Main Store",
    "code": "HQ",
    "address": "Mogadishu, Somalia"
  },
  "initial_accounts": [
    {
      "name": "Cash",
      "type": "cash",
      "provider": null,
      "initial_balance": 0
    },
    {
      "name": "EVC Plus",
      "type": "mobile_money",
      "provider": "Hormuud",
      "initial_balance": 0
    }
  ]
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `business.name` | `required\|string\|max:255` |
| `business.legal_name` | `nullable\|string\|max:255` |
| `business.phone` | `nullable\|string\|max:20` |
| `business.email` | `nullable\|email\|max:255` |
| `business.address` | `nullable\|string\|max:1000` |
| `business.currency` | `nullable\|string\|max:3` |
| `main_branch.name` | `required\|string\|max:255` |
| `main_branch.code` | `nullable\|string\|max:20` |
| `main_branch.address` | `nullable\|string\|max:1000` |
| `initial_accounts` | `nullable\|array` |
| `initial_accounts[].name` | `required\|string\|max:255` |
| `initial_accounts[].type` | `required\|in:cash,mobile_money,bank` |
| `initial_accounts[].provider` | `nullable\|string\|max:100` |
| `initial_accounts[].initial_balance` | `nullable\|numeric\|min:0` |

**Response (201):**
```json
{
  "success": true,
  "message": "Business setup completed",
  "data": {
    "business": {
      "id": 1,
      "name": "Ali Trading Co.",
      "currency": "USD"
    },
    "branch": {
      "id": 1,
      "name": "Main Store",
      "is_main": true
    },
    "accounts": [
      {
        "id": 1,
        "name": "Cash",
        "balance": 0.00
      },
      {
        "id": 2,
        "name": "EVC Plus",
        "balance": 0.00
      }
    ]
  }
}
```

---

### 3.2 Get Business Profile

**Backend:** `SetupController.php:124-143`

```
GET /api/v1/business/profile
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Ali Trading Co.",
    "legal_name": "Ali Trading Company Ltd.",
    "phone": "+252615987654",
    "email": "info@alitrading.com",
    "address": "Mogadishu, Somalia",
    "logo": null,
    "currency": "USD",
    "created_at": "2026-01-10T06:35:00.000000Z"
  }
}
```

---

### 3.3 Update Business Profile

**Backend:** `SetupController.php:149-191`

```
PUT /api/v1/business/profile
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request:**
```json
{
  "name": "Ali Trading Co. Updated",
  "phone": "+252615111222",
  "logo": "(file upload)"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `sometimes\|string\|max:255` |
| `legal_name` | `sometimes\|nullable\|string\|max:255` |
| `phone` | `sometimes\|nullable\|string\|max:20` |
| `email` | `sometimes\|nullable\|email\|max:255` |
| `address` | `sometimes\|nullable\|string\|max:1000` |
| `logo` | `sometimes\|nullable\|image\|max:2048` |

---

## Phase 4: Subscription Management

### 4.1 Get Subscription Status

**Backend:** `SubscriptionController.php:18-86`

```
GET /api/v1/subscription/status
Authorization: Bearer {token}
```

**Response (Trial):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan": "Starter",
    "plan_id": 1,
    "status": "trial",
    "status_label": "Trial (14 days left)",
    "can_read": true,
    "can_write": true,
    "write_blocked": false,
    "block_reason": null,
    "block_action": null,
    "trial_days_left": 14,
    "days_remaining": 14,
    "trial_ends_at": "2026-01-24T06:30:00.000000Z",
    "ends_at": null,
    "is_paid": false,
    "upgrade_available": true
  }
}
```

**Response (Expired Trial):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan": "Starter",
    "plan_id": 1,
    "status": "expired",
    "status_label": "Trial Expired",
    "can_read": true,
    "can_write": false,
    "write_blocked": true,
    "block_reason": "trial_expired",
    "block_action": "upgrade_required",
    "trial_days_left": 0,
    "days_remaining": 0,
    "is_paid": false,
    "upgrade_available": true
  }
}
```

**Response (Active Paid):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan": "Starter",
    "plan_id": 1,
    "status": "active",
    "status_label": "Active",
    "can_read": true,
    "can_write": true,
    "write_blocked": false,
    "block_reason": null,
    "block_action": null,
    "trial_days_left": null,
    "days_remaining": 25,
    "ends_at": "2026-02-10T06:30:00.000000Z",
    "is_paid": true,
    "upgrade_available": true
  }
}
```

---

### 4.2 Get Subscription Details

**Backend:** `SubscriptionController.php:91-127`

```
GET /api/v1/subscription
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "plan": {
      "id": 1,
      "name": "Starter",
      "price": 9.99,
      "currency": "USD",
      "billing_cycle": "monthly"
    },
    "status": "active",
    "status_label": "Active",
    "can_read": true,
    "can_write": true,
    "trial_ends_at": null,
    "starts_at": "2026-01-10T06:30:00.000000Z",
    "ends_at": "2026-02-10T06:30:00.000000Z",
    "days_remaining": 25
  }
}
```

---

### 4.3 Get Upgrade Options

**Backend:** `SubscriptionController.php:133-174`

```
GET /api/v1/subscription/upgrade-options
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_plan": {
      "id": 1,
      "name": "Starter",
      "status": "active",
      "days_remaining": 25
    },
    "upgrade_options": [
      {
        "id": 2,
        "name": "Professional",
        "price": 19.99,
        "currency": "USD",
        "billing_cycle": "monthly",
        "features": {
          "branches": true
        }
      }
    ]
  }
}
```

---

## Phase 5: App Discovery

### 5.1 Get Available Apps

**Backend:** `AppController.php:118-171`

```
GET /api/v1/apps
Authorization: Bearer {token}
```

**Response (Active Subscription):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan_name": "Starter",
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
        "id": "customers",
        "name": "Customers",
        "icon": "users",
        "route": "/business/customers",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "vendors",
        "name": "Vendors",
        "icon": "truck",
        "route": "/business/vendors",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "stock",
        "name": "Stock",
        "icon": "package",
        "route": "/business/stock",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "profit_loss",
        "name": "Profit & Loss",
        "icon": "trending-up",
        "route": "/business/profit-loss",
        "enabled": true,
        "locked": false,
        "hidden": false
      },
      {
        "id": "branches",
        "name": "Branches",
        "icon": "git-branch",
        "route": "/business/branches",
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

**Response (Expired - Write Blocked):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan_name": "Starter",
    "apps": [
      {
        "id": "customers",
        "name": "Customers",
        "icon": "users",
        "route": "/business/customers",
        "enabled": true,
        "locked": true,
        "hidden": false
      }
    ],
    "write_blocked": true,
    "block_reason": "trial_expired",
    "block_action": "upgrade_required"
  }
}
```

---

## Phase 6: Business Dashboard

### 6.1 Get Business Dashboard

**Backend:** `Business/DashboardController.php:23-147`

```
GET /api/v1/business/dashboard
Authorization: Bearer {token}
X-Branch-ID: {branch_id} (optional)
```

**Response:**
```json
{
  "success": true,
  "data": {
    "business": {
      "id": 1,
      "name": "Ali Trading Co.",
      "currency": "USD",
      "is_active": true
    },
    "current_branch": {
      "id": 1,
      "name": "Main Store",
      "is_main": true
    },
    "summary": {
      "total_income": 25000.00,
      "total_expense": 10000.00,
      "total_receivables": 5000.00,
      "total_payables": 2000.00,
      "net_position": 3000.00
    },
    "income": {
      "today": 500.00,
      "this_month": 25000.00
    },
    "expense": {
      "today": 200.00,
      "this_month": 10000.00
    },
    "customers": {
      "total": 25,
      "with_balance": 10,
      "total_owed": 5000.00
    },
    "vendors": {
      "total": 15,
      "with_balance": 5,
      "total_owed": 2000.00
    },
    "stock": {
      "total_items": 50,
      "total_value": 8000.00
    },
    "profit_loss": {
      "this_month": 15000.00
    },
    "branch_comparison": [
      {
        "branch_id": 1,
        "branch_name": "Main Store",
        "income": 20000.00,
        "expense": 8000.00
      },
      {
        "branch_id": 2,
        "branch_name": "Branch 2",
        "income": 5000.00,
        "expense": 2000.00
      }
    ]
  }
}
```

---

## Phase 7: Customers (Receivables)

### 7.1 List Customers

**Backend:** `Business/CustomerController.php:18-53`

```
GET /api/v1/business/customers?search=ali&per_page=20
Authorization: Bearer {token}
X-Branch-ID: {branch_id} (optional)
```

**Query Parameters:**
| Param | Default | Description |
|-------|---------|-------------|
| `search` | None | Search by name or phone |
| `active_only` | true | Filter active only |
| `per_page` | 20 | Max 50 |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Ahmed Customer",
      "phone": "+252615111222",
      "email": "ahmed@example.com",
      "address": "Mogadishu",
      "balance": 500.00,
      "status": "owes",
      "branch_id": 1,
      "branch_name": "Main Store",
      "notes": "Regular customer",
      "is_active": true,
      "created_at": "2026-01-05T10:00:00.000000Z"
    }
  ],
  "summary": {
    "total_receivable": 5000.00
  },
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 25
  }
}
```

---

### 7.2 Create Customer

**Backend:** `Business/CustomerController.php:59-94`

```
POST /api/v1/business/customers
Authorization: Bearer {token}
X-Branch-ID: {branch_id} (optional)
```

**Request:**
```json
{
  "name": "New Customer",
  "phone": "+252615333444",
  "email": "new@customer.com",
  "address": "Mogadishu, Somalia",
  "notes": "VIP customer"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `required\|string\|max:255` |
| `phone` | `nullable\|string\|max:20` |
| `email` | `nullable\|email\|max:255` |
| `address` | `nullable\|string\|max:1000` |
| `notes` | `nullable\|string\|max:1000` |
| `branch_id` | `nullable\|exists:branches,id` |

**Response (201):**
```json
{
  "success": true,
  "message": "Customer created successfully",
  "data": {
    "id": 2,
    "name": "New Customer",
    "phone": "+252615333444",
    "email": "new@customer.com",
    "address": "Mogadishu, Somalia",
    "balance": 0.00,
    "status": "settled",
    "is_active": true
  }
}
```

---

### 7.3 Debit Customer (Customer Owes More)

**Backend:** `Business/CustomerController.php:157-201`

```
POST /api/v1/business/customers/{id}/debit
Authorization: Bearer {token}
```

**Request:**
```json
{
  "amount": 100.00,
  "description": "Sold goods on credit"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Customer debited successfully",
  "data": {
    "customer": {
      "id": 1,
      "name": "Ahmed Customer",
      "previous_balance": 500.00,
      "new_balance": 600.00
    },
    "transaction": {
      "id": 1,
      "type": "debit",
      "amount": 100.00,
      "description": "Sold goods on credit"
    }
  }
}
```

---

### 7.4 Credit Customer (Customer Paid)

**Backend:** `Business/CustomerController.php:208-252`

```
POST /api/v1/business/customers/{id}/credit
Authorization: Bearer {token}
```

**Request:**
```json
{
  "amount": 200.00,
  "description": "Received payment"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Customer credited successfully",
  "data": {
    "customer": {
      "id": 1,
      "name": "Ahmed Customer",
      "previous_balance": 600.00,
      "new_balance": 400.00
    },
    "transaction": {
      "id": 2,
      "type": "credit",
      "amount": 200.00,
      "description": "Received payment"
    }
  }
}
```

---

### 7.5 Customer Transactions

**Backend:** `Business/CustomerController.php:284-326`

```
GET /api/v1/business/customers/{id}/transactions?per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 1,
      "name": "Ahmed Customer",
      "balance": 400.00
    },
    "transactions": [
      {
        "id": 2,
        "type": "credit",
        "amount": 200.00,
        "description": "Received payment",
        "balance_after": 400.00,
        "created_by": "Ali Mohamed",
        "created_at": "2026-01-10T11:00:00.000000Z"
      },
      {
        "id": 1,
        "type": "debit",
        "amount": 100.00,
        "description": "Sold goods on credit",
        "balance_after": 600.00,
        "created_by": "Ali Mohamed",
        "created_at": "2026-01-10T10:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 2
    }
  }
}
```

---

## Phase 8: Vendors (Payables)

### 8.1 List Vendors

**Backend:** `Business/VendorController.php:18-53`

```
GET /api/v1/business/vendors?search=supplier&per_page=20
Authorization: Bearer {token}
X-Branch-ID: {branch_id} (optional)
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "ABC Supplier",
      "phone": "+252615555666",
      "email": "abc@supplier.com",
      "address": "Mogadishu",
      "balance": 300.00,
      "status": "owed",
      "branch_id": 1,
      "branch_name": "Main Store",
      "notes": "Main supplier",
      "is_active": true,
      "created_at": "2026-01-03T09:00:00.000000Z"
    }
  ],
  "summary": {
    "total_payable": 2000.00
  },
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 15
  }
}
```

---

### 8.2 Credit Vendor (We Owe More)

**Backend:** `Business/VendorController.php:157-196`

```
POST /api/v1/business/vendors/{id}/credit
Authorization: Bearer {token}
```

**Request:**
```json
{
  "amount": 500.00,
  "description": "Purchased goods on credit"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Vendor credited successfully",
  "data": {
    "vendor": {
      "id": 1,
      "name": "ABC Supplier",
      "previous_balance": 300.00,
      "new_balance": 800.00
    },
    "transaction": {
      "id": 1,
      "type": "credit",
      "amount": 500.00,
      "description": "Purchased goods on credit"
    }
  }
}
```

---

### 8.3 Debit Vendor (We Paid)

**Backend:** `Business/VendorController.php:203-242`

```
POST /api/v1/business/vendors/{id}/debit
Authorization: Bearer {token}
```

**Request:**
```json
{
  "amount": 200.00,
  "description": "Paid supplier"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Vendor debited successfully",
  "data": {
    "vendor": {
      "id": 1,
      "name": "ABC Supplier",
      "previous_balance": 800.00,
      "new_balance": 600.00
    },
    "transaction": {
      "id": 2,
      "type": "debit",
      "amount": 200.00,
      "description": "Paid supplier"
    }
  }
}
```

---

## Phase 9: Stock Management

### 9.1 List Stock Items

**Backend:** `Business/StockController.php:18-57`

```
GET /api/v1/business/stock?search=phone&per_page=20
Authorization: Bearer {token}
X-Branch-ID: {branch_id} (optional)
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "iPhone 15 Pro",
      "sku": "IPH15P-001",
      "description": "Apple iPhone 15 Pro 256GB",
      "quantity": 10.00,
      "unit": "pcs",
      "cost_price": 900.00,
      "selling_price": 1100.00,
      "image": "https://api.kobac.app/storage/stock-images/iphone.jpg",
      "branch_id": 1,
      "branch_name": "Main Store",
      "is_active": true,
      "created_at": "2026-01-02T08:00:00.000000Z"
    }
  ],
  "summary": {
    "total_items": 50,
    "total_quantity": 500.00,
    "total_cost_value": 45000.00,
    "total_selling_value": 55000.00
  },
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 50
  }
}
```

---

### 9.2 Create Stock Item

**Backend:** `Business/StockController.php:63-107`

```
POST /api/v1/business/stock
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request:**
```json
{
  "name": "Samsung Galaxy S24",
  "sku": "SAM-S24-001",
  "description": "Samsung Galaxy S24 Ultra",
  "quantity": 5,
  "unit": "pcs",
  "cost_price": 800.00,
  "selling_price": 999.00,
  "image": "(file upload)"
}
```

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | `required\|string\|max:255` |
| `sku` | `nullable\|string\|max:50` |
| `description` | `nullable\|string\|max:1000` |
| `quantity` | `nullable\|numeric\|min:0` |
| `unit` | `nullable\|string\|max:20` |
| `cost_price` | `nullable\|numeric\|min:0` |
| `selling_price` | `nullable\|numeric\|min:0` |
| `image` | `nullable\|image\|max:2048` |
| `branch_id` | `nullable\|exists:branches,id` |

**Response (201):**
```json
{
  "success": true,
  "message": "Stock item created successfully",
  "data": {
    "id": 2,
    "name": "Samsung Galaxy S24",
    "sku": "SAM-S24-001",
    "quantity": 5.00,
    "unit": "pcs",
    "cost_price": 800.00,
    "selling_price": 999.00
  }
}
```

---

### 9.3 Increase Stock

**Backend:** `Business/StockController.php:178-223`

```
POST /api/v1/business/stock/{id}/increase
Authorization: Bearer {token}
```

**Request:**
```json
{
  "quantity": 10,
  "reason": "New shipment arrived",
  "reference": "PO-2026-001"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Stock increased successfully",
  "data": {
    "item": {
      "id": 1,
      "name": "iPhone 15 Pro",
      "previous_quantity": 10.00,
      "new_quantity": 20.00
    },
    "movement": {
      "id": 1,
      "type": "increase",
      "quantity": 10.00,
      "reason": "New shipment arrived"
    }
  }
}
```

---

### 9.4 Decrease Stock

**Backend:** `Business/StockController.php:230-280`

```
POST /api/v1/business/stock/{id}/decrease
Authorization: Bearer {token}
```

**Request:**
```json
{
  "quantity": 2,
  "reason": "Sold to customer",
  "reference": "INV-2026-001"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Stock decreased successfully",
  "data": {
    "item": {
      "id": 1,
      "name": "iPhone 15 Pro",
      "previous_quantity": 20.00,
      "new_quantity": 18.00
    },
    "movement": {
      "id": 2,
      "type": "decrease",
      "quantity": 2.00,
      "reason": "Sold to customer"
    }
  }
}
```

**Error (Insufficient Stock):**
```json
{
  "success": false,
  "message": "Insufficient stock quantity",
  "error_code": "INSUFFICIENT_STOCK"
}
```

---

### 9.5 Stock Movements History

**Backend:** `Business/StockController.php:312-350`

```
GET /api/v1/business/stock/{id}/movements?per_page=20
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "item": {
      "id": 1,
      "name": "iPhone 15 Pro",
      "quantity": 18.00
    },
    "movements": [
      {
        "id": 2,
        "type": "decrease",
        "quantity": 2.00,
        "quantity_before": 20.00,
        "quantity_after": 18.00,
        "reason": "Sold to customer",
        "reference": "INV-2026-001",
        "created_by": "Ali Mohamed",
        "created_at": "2026-01-10T12:00:00.000000Z"
      },
      {
        "id": 1,
        "type": "increase",
        "quantity": 10.00,
        "quantity_before": 10.00,
        "quantity_after": 20.00,
        "reason": "New shipment arrived",
        "reference": "PO-2026-001",
        "created_by": "Ali Mohamed",
        "created_at": "2026-01-10T11:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 2
    }
  }
}
```

---

## Error Handling

### Write-Blocked Error (Expired Subscription)

When a business user's trial expires or subscription ends, write operations return:

```json
{
  "blocked": true,
  "reason": "trial_expired",
  "action": "upgrade_required",
  "can_read": true,
  "can_write": false,
  "message": "Your trial has expired. Please upgrade to continue."
}
```

**Block Reasons:**
| Reason | Action | Description |
|--------|--------|-------------|
| `trial_expired` | `upgrade_required` | Trial period ended |
| `subscription_expired` | `renew_required` | Paid subscription ended |
| `no_subscription` | `subscribe_required` | No subscription found |
| `pending_payment` | `wait_approval` | Offline payment pending |

---

## API Endpoints Summary

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/plans` | Get available plans |
| `POST` | `/auth/register` | Register (with plan_id) |
| `POST` | `/auth/login` | Login |
| `POST` | `/auth/logout` | Logout |
| `GET` | `/auth/me` | Get current user |

### Payment
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/payment/methods` | Get payment methods |
| `POST` | `/payment/initiate` | Initiate online payment |
| `POST` | `/payment/status` | Check payment status |
| `POST` | `/payment/offline` | Initiate offline payment |
| `POST` | `/payment/offline/status` | Check offline status |
| `GET` | `/payment/offline/instructions` | Get payment instructions |

### Subscription
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/subscription/status` | Get subscription status |
| `GET` | `/subscription` | Get subscription details |
| `GET` | `/subscription/upgrade-options` | Get upgrade options |

### Business Setup
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/business/setup` | Complete business setup |
| `GET` | `/business/profile` | Get business profile |
| `PUT` | `/business/profile` | Update business profile |
| `GET` | `/business/dashboard` | Get business dashboard |

### Customers
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/business/customers` | List customers |
| `POST` | `/business/customers` | Create customer |
| `GET` | `/business/customers/{id}` | Get customer |
| `PUT` | `/business/customers/{id}` | Update customer |
| `DELETE` | `/business/customers/{id}` | Delete customer |
| `POST` | `/business/customers/{id}/debit` | Debit customer |
| `POST` | `/business/customers/{id}/credit` | Credit customer |
| `POST` | `/business/customers/{id}/deactivate` | Deactivate customer |
| `POST` | `/business/customers/{id}/activate` | Activate customer |
| `GET` | `/business/customers/{id}/transactions` | Customer transactions |

### Vendors
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/business/vendors` | List vendors |
| `POST` | `/business/vendors` | Create vendor |
| `GET` | `/business/vendors/{id}` | Get vendor |
| `PUT` | `/business/vendors/{id}` | Update vendor |
| `DELETE` | `/business/vendors/{id}` | Delete vendor |
| `POST` | `/business/vendors/{id}/credit` | Credit vendor |
| `POST` | `/business/vendors/{id}/debit` | Debit vendor |
| `POST` | `/business/vendors/{id}/deactivate` | Deactivate vendor |
| `POST` | `/business/vendors/{id}/activate` | Activate vendor |
| `GET` | `/business/vendors/{id}/transactions` | Vendor transactions |

### Stock
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/business/stock` | List stock items |
| `POST` | `/business/stock` | Create stock item |
| `GET` | `/business/stock/{id}` | Get stock item |
| `PUT` | `/business/stock/{id}` | Update stock item |
| `DELETE` | `/business/stock/{id}` | Delete stock item |
| `POST` | `/business/stock/{id}/increase` | Increase stock |
| `POST` | `/business/stock/{id}/decrease` | Decrease stock |
| `POST` | `/business/stock/{id}/deactivate` | Deactivate stock |
| `POST` | `/business/stock/{id}/activate` | Activate stock |
| `GET` | `/business/stock/{id}/movements` | Stock movements |

### Shared (Also available for Business)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/apps` | Get available features |
| `GET` | `/accounts` | List accounts |
| `POST` | `/accounts` | Create account |
| `GET` | `/income` | List income |
| `POST` | `/income` | Create income |
| `GET` | `/expenses` | List expenses |
| `POST` | `/expenses` | Create expense |
| `GET` | `/activity` | Get activity timeline |
| `PUT` | `/profile` | Update profile |

---

*Source: Backend Controllers - January 10, 2026*
