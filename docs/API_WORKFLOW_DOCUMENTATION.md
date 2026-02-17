# Kobac Mobile App - API Workflow Documentation

> Complete API endpoint documentation for Individual and Business user flows

---

## Table of Contents

1. [App Flow Overview](#app-flow-overview)
2. [Public Endpoints (No Auth Required)](#public-endpoints-no-auth-required)
3. [Individual User Flow](#individual-user-flow)
4. [Business User Flow](#business-user-flow)
5. [Payment Flow](#payment-flow)
6. [Dashboard & Features](#dashboard--features)
7. [Complete JSON Endpoint Reference](#complete-json-endpoint-reference)

---

## App Flow Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              APP LAUNCH                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│  Splash Screen → Onboarding (first time) → Welcome Screen                   │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                     WELCOME SCREEN                                   │    │
│  │  ┌──────────────────┐          ┌──────────────────┐                 │    │
│  │  │   INDIVIDUAL     │          │    BUSINESS      │                 │    │
│  │  │   (Free User)    │          │  (Subscription)  │                 │    │
│  │  │                  │          │                  │                 │    │
│  │  │  [ℹ️ Info Modal] │          │  [ℹ️ Info Modal] │                 │    │
│  │  └────────┬─────────┘          └────────┬─────────┘                 │    │
│  └───────────┼─────────────────────────────┼───────────────────────────┘    │
│              │                             │                                 │
│              ▼                             ▼                                 │
│     ┌────────────────┐            ┌────────────────┐                        │
│     │ Auth Screen    │            │ Auth Screen    │                        │
│     │ Login/Register │            │ Login/Register │                        │
│     └───────┬────────┘            └───────┬────────┘                        │
│             │                             │                                  │
│             ▼                             ▼                                  │
│     ┌────────────────┐            ┌────────────────┐                        │
│     │   Dashboard    │            │  Plan Selection │                       │
│     │  (Individual)  │            │  Free/Paid      │                       │
│     └────────────────┘            └───────┬────────┘                        │
│                                           │                                  │
│                              ┌────────────┴────────────┐                    │
│                              │                         │                    │
│                              ▼                         ▼                    │
│                     ┌──────────────┐          ┌──────────────┐              │
│                     │  FREE TRIAL  │          │    PAID      │              │
│                     │  (14 days)   │          │  (Checkout)  │              │
│                     └──────┬───────┘          └──────┬───────┘              │
│                            │                         │                      │
│                            │                         ▼                      │
│                            │                 ┌──────────────┐               │
│                            │                 │   Payment    │               │
│                            │                 │   Methods    │               │
│                            │                 └──────┬───────┘               │
│                            │                        │                       │
│                            ▼                        ▼                       │
│                     ┌────────────────────────────────────┐                  │
│                     │       Business Setup Screen        │                  │
│                     └─────────────────┬──────────────────┘                  │
│                                       │                                     │
│                                       ▼                                     │
│                              ┌────────────────┐                             │
│                              │   Dashboard    │                             │
│                              │   (Business)   │                             │
│                              └────────────────┘                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Public Endpoints (No Auth Required)

### 1. Get Available Plans

**Human Readable:**
> Fetch all available subscription plans for business users. Individual users are FREE.

**Endpoint:**
```
GET /api/v1/plans
```

**Headers:**
```
Accept: application/json
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
          "customers": true,
          "vendors": true,
          "stock": true,
          "branches": false,
          "profit_loss": true
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
          "customers": true,
          "vendors": true,
          "stock": true,
          "branches": true,
          "profit_loss": true
        },
        "is_default": false,
        "is_recommended": false
      }
    ],
    "default_plan_id": 1,
    "note": "Individual accounts are FREE and do not require a plan."
  }
}
```

---

## Individual User Flow

### Flow Steps:
1. **Launch App** → Splash Screen
2. **First Time** → Onboarding Screens (swipeable)
3. **Welcome Screen** → Choose "Individual"
4. **Info Modal** → Shows Individual benefits (X to close)
5. **Auth Screen** → Login or Register
6. **Register** → Submit individual payload
7. **Success** → Auto-redirect to Individual Dashboard
8. **App Restart** → Auto-login to Individual Dashboard

---

### 2. Register Individual User

**Human Readable:**
> Register a new individual user account. Individual users get FREE full access to personal finance features.

**Endpoint:**
```
POST /api/v1/auth/register
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Request Payload:**
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

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_type` | string | ✅ Yes | Must be `"individual"` |
| `name` | string | ✅ Yes | Full name (max 255 chars) |
| `email` | string | ✅ Yes | Unique email address |
| `phone` | string | ❌ Optional | Phone number with country code |
| `password` | string | ✅ Yes | Min 8 characters |
| `password_confirmation` | string | ✅ Yes | Must match password |

**Success Response (201):**
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

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "error_code": "VALIDATION_ERROR",
  "data": {
    "errors": {
      "email": ["The email has already been taken."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

---

### 3. Login (Individual or Business)

**Human Readable:**
> Login with email and password. Returns user data and access token. Works for both Individual and Business users.

**Endpoint:**
```
POST /api/v1/auth/login
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Request Payload:**
```json
{
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "device_name": "iPhone 15 Pro"
}
```

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | ✅ Yes | Registered email |
| `password` | string | ✅ Yes | Account password |
| `device_name` | string | ❌ Optional | Device identifier for token |

**Success Response - Individual (200):**
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

**Success Response - Business (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 2,
      "name": "Mohamed Ali",
      "email": "business@example.com",
      "phone": "+252615987654",
      "user_type": "business",
      "avatar": null,
      "is_active": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "subscription": {
      "status": "trial",
      "plan_name": "Starter",
      "can_write": true,
      "days_remaining": 14
    },
    "token": "3|def456ghi..."
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

## Business User Flow

### Flow Steps:
1. **Launch App** → Splash Screen
2. **First Time** → Onboarding Screens
3. **Welcome Screen** → Choose "Business"
4. **Info Modal** → Shows Business benefits (X to close)
5. **Auth Screen** → Login or Register
6. **Register** → Submit business payload with plan selection
7. **Plan Selection**:
   - **Free Trial** → 14 days trial → Business Setup
   - **Paid Plan** → Checkout → Payment Methods → Business Setup
8. **Business Setup** → Complete business profile
9. **Success** → Redirect to Business Dashboard
10. **App Restart** → Auto-login to Business Dashboard

---

### 4. Register Business User (Free Trial)

**Human Readable:**
> Register a new business user with a subscription plan. If the plan has trial enabled, user gets free trial period.

**Endpoint:**
```
POST /api/v1/auth/register
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
```

**Request Payload:**
```json
{
  "user_type": "business",
  "name": "Mohamed Ali",
  "email": "business@example.com",
  "phone": "+252615987654",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "plan_id": 1
}
```

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_type` | string | ✅ Yes | Must be `"business"` |
| `name` | string | ✅ Yes | Full name (max 255 chars) |
| `email` | string | ✅ Yes | Unique email address |
| `phone` | string | ❌ Optional | Phone number with country code |
| `password` | string | ✅ Yes | Min 8 characters |
| `password_confirmation` | string | ✅ Yes | Must match password |
| `plan_id` | integer | ✅ Yes | Selected plan ID from `/plans` |

**Success Response (201):**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "user": {
      "id": 2,
      "name": "Mohamed Ali",
      "email": "business@example.com",
      "phone": "+252615987654",
      "user_type": "business",
      "avatar": null,
      "is_active": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "subscription": {
      "id": 1,
      "plan_name": "Starter",
      "status": "trial",
      "trial_ends_at": "2026-01-24T06:30:00.000000Z",
      "days_remaining": 14
    },
    "token": "4|mno012pqr..."
  }
}
```

---

## Payment Flow

### When User Chooses Paid Plan (Skip Trial)

After registration, if user wants to pay immediately or upgrade from trial:

---

### 5. Get Available Payment Methods

**Human Readable:**
> Get available payment methods. Returns Online (WaafiPay) and/or Offline based on admin configuration.

**Endpoint:**
```
GET /api/v1/payment/methods
```

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Both methods available):**
```json
{
  "success": true,
  "data": [
    {
      "type": "online",
      "name": "Mobile Wallet (WaafiPay)",
      "description": "Pay instantly using EVC Plus, Zaad, Jeeb, or Sahal",
      "providers": [
        {
          "id": "evc_plus",
          "name": "EVC Plus",
          "logo": "https://example.com/images/evc-plus.png"
        },
        {
          "id": "zaad",
          "name": "Zaad Service",
          "logo": "https://example.com/images/zaad.png"
        },
        {
          "id": "jeeb",
          "name": "Jeeb",
          "logo": "https://example.com/images/jeeb.png"
        },
        {
          "id": "sahal",
          "name": "Sahal",
          "logo": "https://example.com/images/sahal.png"
        }
      ],
      "is_instant": true
    },
    {
      "type": "offline",
      "name": "Offline Payment",
      "description": "Bank transfer, cash, or other manual payment methods",
      "instructions": "Please transfer the payment to:\nBank: Premier Bank\nAccount: 1234567890\nName: Kobac Ltd.\n\nAfter payment, your subscription will be activated within 24 hours.",
      "is_instant": false,
      "requires_approval": true
    }
  ]
}
```

**Response (Only WaafiPay available):**
```json
{
  "success": true,
  "data": [
    {
      "type": "online",
      "name": "Mobile Wallet (WaafiPay)",
      "description": "Pay instantly using EVC Plus, Zaad, Jeeb, or Sahal",
      "providers": [...],
      "is_instant": true
    }
  ]
}
```

---

### 6. Initiate Online Payment (WaafiPay)

**Human Readable:**
> Initiate mobile wallet payment via WaafiPay. Supports EVC Plus, Zaad, Jeeb, and Sahal.

**Endpoint:**
```
POST /api/v1/payment/initiate
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Payload:**
```json
{
  "phone_number": "615123456",
  "amount": 9.99,
  "wallet_type": "evc_plus",
  "description": "Starter plan subscription"
}
```

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `phone_number` | string | ✅ Yes | 9-digit phone (61/62/63/65/68/71/90 prefix) |
| `amount` | number | ✅ Yes | Payment amount (min 0.01, max 10000) |
| `wallet_type` | string | ❌ Optional | `evc_plus`, `zaad`, `jeeb`, `sahal` |
| `description` | string | ❌ Optional | Payment description |

**Success Response - Instant (200):**
```json
{
  "success": true,
  "status": "success",
  "message": "✅ Payment completed successfully!",
  "transaction_id": 1,
  "reference_id": "TXN-20260110063000-ABC123",
  "waafi_transaction_id": "WP123456789",
  "data": {
    "responseCode": "2001",
    "responseMsg": "Success"
  }
}
```

**Success Response - Pending Approval (200):**
```json
{
  "success": true,
  "status": "processing",
  "message": "📱 Payment request sent. Waiting for customer approval...",
  "transaction_id": 1,
  "reference_id": "TXN-20260110063000-ABC123",
  "data": {
    "responseCode": "2002",
    "responseMsg": "Pending"
  }
}
```

---

### 7. Check Payment Status

**Human Readable:**
> Check the status of a payment transaction by reference ID.

**Endpoint:**
```
POST /api/v1/payment/status
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Payload:**
```json
{
  "reference_id": "TXN-20260110063000-ABC123"
}
```

**Response:**
```json
{
  "success": true,
  "status": "success",
  "amount": 9.99,
  "phone_number": "252615123456",
  "transaction": {
    "id": 1,
    "reference_id": "TXN-20260110063000-ABC123",
    "waafi_transaction_id": "WP123456789",
    "status": "success",
    "amount": 9.99,
    "currency": "USD",
    "wallet_type": "EVC Plus",
    "phone_number": "252615123456",
    "customer_name": "Mohamed Ali",
    "description": "Starter plan subscription",
    "created_at": "2026-01-10T06:30:00.000000Z",
    "completed_at": "2026-01-10T06:30:15.000000Z"
  }
}
```

---

### 8. Initiate Offline Payment

**Human Readable:**
> Request offline/manual payment for subscription. Payment will be pending until admin approves.

**Endpoint:**
```
POST /api/v1/payment/offline
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Payload:**
```json
{
  "plan_id": 1,
  "proof_of_payment": "Bank transfer receipt #12345 dated 2026-01-10"
}
```

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `plan_id` | integer | ✅ Yes | Plan ID to subscribe to |
| `proof_of_payment` | string | ❌ Optional | Payment proof description |

**Success Response (200):**
```json
{
  "success": true,
  "status": "pending_approval",
  "message": "Payment request submitted successfully. Waiting for admin approval.",
  "transaction_id": 2,
  "reference_id": "OFF-20260110063000-XYZ789",
  "subscription_id": 1,
  "instructions": "Please transfer the payment to:\nBank: Premier Bank\nAccount: 1234567890\nName: Kobac Ltd.",
  "data": {
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

### 9. Check Offline Payment Status

**Human Readable:**
> Check status of offline payment request. Returns pending/approved/rejected status.

**Endpoint:**
```
POST /api/v1/payment/offline/status
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Payload:**
```json
{
  "reference_id": "OFF-20260110063000-XYZ789"
}
```

**Response - Pending:**
```json
{
  "success": true,
  "status": "pending_approval",
  "message": "Your payment is pending admin approval.",
  "transaction": {
    "id": 2,
    "reference_id": "OFF-20260110063000-XYZ789",
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

**Response - Approved:**
```json
{
  "success": true,
  "status": "approved",
  "message": "Your payment has been approved. Subscription is active.",
  "transaction": {
    "id": 2,
    "reference_id": "OFF-20260110063000-XYZ789",
    "amount": 9.99,
    "currency": "USD",
    "status": "approved",
    "payment_type": "offline",
    "created_at": "2026-01-10T06:30:00.000000Z",
    "approved_at": "2026-01-10T07:00:00.000000Z",
    "rejection_reason": null
  },
  "subscription": {
    "id": 1,
    "status": "active",
    "plan_name": "Starter"
  }
}
```

**Response - Rejected:**
```json
{
  "success": true,
  "status": "rejected",
  "message": "Your payment was rejected. Reason: Payment proof not valid",
  "transaction": {
    "id": 2,
    "reference_id": "OFF-20260110063000-XYZ789",
    "amount": 9.99,
    "currency": "USD",
    "status": "rejected",
    "payment_type": "offline",
    "created_at": "2026-01-10T06:30:00.000000Z",
    "approved_at": "2026-01-10T07:00:00.000000Z",
    "rejection_reason": "Payment proof not valid"
  },
  "subscription": {
    "id": 1,
    "status": "expired",
    "plan_name": "Starter"
  }
}
```

---

### 10. Get Offline Payment Instructions

**Human Readable:**
> Get payment instructions for offline payment (bank details, etc.).

**Endpoint:**
```
GET /api/v1/payment/offline/instructions
```

**Headers:**
```
Accept: application/json
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

## Dashboard & Features

### 11. Business Setup (After Registration/Payment)

**Human Readable:**
> Complete business profile setup after registration. Required for business users. Creates business, main branch, and initial accounts.

**Endpoint:**
```
POST /api/v1/business/setup
```

**Headers:**
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Payload:**
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

**Payload Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `business.name` | string | ✅ Yes | Business name (max 255 chars) |
| `business.legal_name` | string | ❌ Optional | Legal registered name |
| `business.phone` | string | ❌ Optional | Business phone (max 20 chars) |
| `business.email` | string | ❌ Optional | Business email |
| `business.address` | string | ❌ Optional | Business address (max 1000 chars) |
| `business.currency` | string | ❌ Optional | Default currency (USD, SOS) |
| `main_branch.name` | string | ✅ Yes | Branch name (max 255 chars) |
| `main_branch.code` | string | ❌ Optional | Branch code (default: HQ) |
| `main_branch.address` | string | ❌ Optional | Branch address |
| `initial_accounts` | array | ❌ Optional | Array of initial accounts |
| `initial_accounts[].name` | string | ✅ Yes* | Account name |
| `initial_accounts[].type` | string | ✅ Yes* | `cash`, `mobile_money`, `bank` |
| `initial_accounts[].provider` | string | ❌ Optional | Provider name (e.g., Hormuud) |
| `initial_accounts[].initial_balance` | number | ❌ Optional | Starting balance (default: 0) |

**Success Response (201):**
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

### 12. Get Current User (Auto-login Check)

**Human Readable:**
> Get current authenticated user data. Use on app launch to check if user is logged in.

**Endpoint:**
```
GET /api/v1/auth/me
```

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

**Response - Individual:**
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

**Response - Business:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Mohamed Ali",
      "email": "business@example.com",
      "phone": "+252615987654",
      "user_type": "business",
      "avatar": null,
      "is_active": true,
      "created_at": "2026-01-10T06:30:00.000000Z"
    },
    "subscription": {
      "id": 1,
      "plan_id": 1,
      "plan_name": "Starter",
      "status": "active",
      "can_read": true,
      "can_write": true,
      "trial_ends_at": null,
      "days_remaining": 30,
      "is_blocked": false
    }
  }
}
```

---

### 13. Get Subscription Status

**Human Readable:**
> Get unified subscription status. Works for both Individual (always FREE) and Business users.

**Endpoint:**
```
GET /api/v1/subscription/status
```

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

**Response - Individual (FREE):**
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

**Response - Business (Trial):**
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

**Response - Business (Pending Payment):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan": "Starter",
    "plan_id": 1,
    "status": "pending_payment",
    "status_label": "Pending Payment Approval",
    "can_read": true,
    "can_write": false,
    "write_blocked": true,
    "block_reason": "pending_payment",
    "block_action": "wait_approval",
    "trial_days_left": null,
    "days_remaining": 0,
    "is_paid": false,
    "upgrade_available": true
  }
}
```

---

### 14. Individual Dashboard

**Human Readable:**
> Get dashboard data for individual users. Shows accounts summary, balance overview, and recent transactions for current month.

**Endpoint:**
```
GET /api/v1/dashboard
```

**Headers:**
```
Accept: application/json
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

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `summary.total_balance` | number | Sum of all account balances |
| `summary.total_income` | number | Total income this month |
| `summary.total_expense` | number | Total expenses this month |
| `summary.accounts_count` | number | Number of active accounts |
| `currency` | string | User's default currency |
| `period.from` | string | Period start date (YYYY-MM-DD) |
| `period.to` | string | Period end date (YYYY-MM-DD) |
| `accounts` | array | List of user's active accounts |
| `recent_transactions` | array | Last 10 transactions (income + expense combined) |

---

### 15. Business Dashboard

**Human Readable:**
> Get dashboard data for business users. Shows business info, income/expense metrics, customers, vendors, stock summary, and profit/loss.

**Endpoint:**
```
GET /api/v1/business/dashboard
```

**Headers:**
```
Accept: application/json
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

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `business` | object | Business info (id, name, currency) |
| `current_branch` | object | Currently selected branch info |
| `summary` | object | Quick summary of key metrics |
| `income.today` | number | Today's total income |
| `income.this_month` | number | This month's total income |
| `expense.today` | number | Today's total expenses |
| `expense.this_month` | number | This month's total expenses |
| `customers.total` | number | Total customers count |
| `customers.with_balance` | number | Customers who owe money |
| `customers.total_owed` | number | Total receivables amount |
| `vendors.total` | number | Total vendors count |
| `vendors.with_balance` | number | Vendors we owe money to |
| `vendors.total_owed` | number | Total payables amount |
| `stock.total_items` | number | Total stock items count |
| `stock.total_value` | number | Total stock value |
| `profit_loss.this_month` | number | Net profit/loss this month |
| `branch_comparison` | array | Performance comparison (only if no branch selected) |

---

### 16. Get App Features

**Human Readable:**
> Get available features/apps based on user type and subscription status. Returns array of apps with id, name, icon, route, and status flags.

**Endpoint:**
```
GET /api/v1/apps
```

**Headers:**
```
Accept: application/json
Authorization: Bearer {token}
```

**Response - Individual:**
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

**Response - Business (Active):**
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
      }
    ],
    "write_blocked": false,
    "block_reason": null,
    "block_action": null
  }
}
```

**Response - Business (Expired/Blocked):**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "is_free": false,
    "plan_name": "Starter",
    "apps": [
      {
        "id": "accounts",
        "name": "Accounts",
        "icon": "wallet",
        "route": "/accounts",
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

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `user_type` | string | `individual` or `business` |
| `is_free` | boolean | `true` for individual users |
| `plan_name` | string | Plan name (business only) |
| `apps` | array | Array of app objects |
| `apps[].id` | string | App identifier (e.g., `accounts`, `customers`) |
| `apps[].name` | string | Display name (e.g., "Accounts") |
| `apps[].icon` | string | Icon name (Lucide icons) |
| `apps[].route` | string | Navigation route |
| `apps[].enabled` | boolean | Feature enabled in plan |
| `apps[].locked` | boolean | Write operations locked |
| `apps[].hidden` | boolean | Hide from UI |
| `write_blocked` | boolean | Global write block status |
| `block_reason` | string\|null | `trial_expired`, `subscription_expired`, `no_subscription`, `pending_payment` |
| `block_action` | string\|null | `upgrade_required`, `renew_required`, `subscribe_required`, `wait_approval` |

---

### 17. Logout

**Human Readable:**
> Logout current user and invalidate the access token.

**Endpoint:**
```
POST /api/v1/auth/logout
```

**Headers:**
```
Accept: application/json
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

## Complete JSON Endpoint Reference

```json
{
  "base_url": "https://api.kobac.app/api/v1",
  "endpoints": {
    "public": {
      "get_plans": {
        "method": "GET",
        "path": "/plans",
        "auth": false,
        "description": "Get available subscription plans"
      }
    },
    "auth": {
      "register": {
        "method": "POST",
        "path": "/auth/register",
        "auth": false,
        "payload": {
          "individual": {
            "user_type": "individual",
            "name": "string|required",
            "email": "string|required|unique",
            "phone": "string|optional",
            "password": "string|required|min:8",
            "password_confirmation": "string|required"
          },
          "business": {
            "user_type": "business",
            "name": "string|required",
            "email": "string|required|unique",
            "phone": "string|optional",
            "password": "string|required|min:8",
            "password_confirmation": "string|required",
            "plan_id": "integer|required"
          }
        }
      },
      "login": {
        "method": "POST",
        "path": "/auth/login",
        "auth": false,
        "payload": {
          "email": "string|required",
          "password": "string|required",
          "device_name": "string|optional"
        }
      },
      "me": {
        "method": "GET",
        "path": "/auth/me",
        "auth": true,
        "description": "Get current user data"
      },
      "logout": {
        "method": "POST",
        "path": "/auth/logout",
        "auth": true
      }
    },
    "subscription": {
      "status": {
        "method": "GET",
        "path": "/subscription/status",
        "auth": true,
        "description": "Get subscription status (works for both user types)"
      },
      "details": {
        "method": "GET",
        "path": "/subscription",
        "auth": true,
        "user_type": "business",
        "description": "Get detailed subscription info"
      },
      "upgrade_options": {
        "method": "GET",
        "path": "/subscription/upgrade-options",
        "auth": true,
        "user_type": "business"
      }
    },
    "payment": {
      "methods": {
        "method": "GET",
        "path": "/payment/methods",
        "auth": true,
        "description": "Get available payment methods"
      },
      "initiate_online": {
        "method": "POST",
        "path": "/payment/initiate",
        "auth": true,
        "payload": {
          "phone_number": "string|required|9digits",
          "amount": "number|required|min:0.01",
          "wallet_type": "string|optional|evc_plus,zaad,jeeb,sahal",
          "description": "string|optional"
        }
      },
      "check_status": {
        "method": "POST",
        "path": "/payment/status",
        "auth": true,
        "payload": {
          "reference_id": "string|required"
        }
      },
      "history": {
        "method": "GET",
        "path": "/payment/history",
        "auth": true
      },
      "initiate_offline": {
        "method": "POST",
        "path": "/payment/offline",
        "auth": true,
        "user_type": "business",
        "payload": {
          "plan_id": "integer|required",
          "proof_of_payment": "string|optional"
        }
      },
      "check_offline_status": {
        "method": "POST",
        "path": "/payment/offline/status",
        "auth": true,
        "payload": {
          "reference_id": "string|required"
        }
      },
      "offline_instructions": {
        "method": "GET",
        "path": "/payment/offline/instructions",
        "auth": true
      }
    },
    "business": {
      "setup": {
        "method": "POST",
        "path": "/business/setup",
        "auth": true,
        "user_type": "business",
        "payload": {
          "business_name": "string|required",
          "business_type": "string|required|retail,wholesale,service,manufacturing",
          "industry": "string|optional",
          "address": "string|optional",
          "phone": "string|optional",
          "currency": "string|optional|USD,SOS"
        }
      },
      "dashboard": {
        "method": "GET",
        "path": "/business/dashboard",
        "auth": true,
        "user_type": "business",
        "headers": {
          "X-Branch-ID": "optional"
        }
      }
    },
    "dashboard": {
      "individual": {
        "method": "GET",
        "path": "/dashboard",
        "auth": true,
        "description": "Dashboard for individual users"
      }
    },
    "apps": {
      "list": {
        "method": "GET",
        "path": "/apps",
        "auth": true,
        "description": "Get available features based on user type"
      }
    }
  }
}
```

---

## Status Codes Reference

| Code | Meaning |
|------|---------|
| `200` | Success |
| `201` | Created |
| `400` | Bad Request |
| `401` | Unauthorized (invalid token) |
| `403` | Forbidden (no permission) |
| `404` | Not Found |
| `422` | Validation Error |
| `503` | Service Unavailable |

---

## Subscription Status Reference

| Status | Description | Can Write |
|--------|-------------|-----------|
| `trial` | Free trial period active | ✅ Yes |
| `active` | Paid subscription active | ✅ Yes |
| `pending_payment` | Waiting for payment approval | ❌ No |
| `expired` | Trial/subscription expired | ❌ No |
| `cancelled` | Subscription cancelled | ❌ No |

---

## Notes

1. **Token Storage**: Store the `token` securely on device after login/register
2. **Auto-Login**: On app launch, check stored token with `/auth/me`
3. **Expiry Handling**: If 401 error, clear token and redirect to login
4. **Branch Context**: Business users can optionally send `X-Branch-ID` header
5. **Write Blocking**: Check `can_write` before allowing create/update/delete

---

*Last Updated: January 10, 2026*
