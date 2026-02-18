# Cajiib Mobile App API Contract

**Base URL:** `https://kobac.cajiibcreative.com/api/v1`  
**Version:** 1.0  
**Last Updated:** January 18, 2026

---

## Table of Contents

1. [Authentication](#authentication)
2. [Response Format](#response-format)
3. [Error Handling](#error-handling)
4. [Public Endpoints](#public-endpoints)
5. [Protected Endpoints](#protected-endpoints)
6. [Subscription & Payments](#subscription--payments)
7. [Business Features](#business-features)

---

## Authentication

### Headers Required

| Header | Value | Required |
|--------|-------|----------|
| `Content-Type` | `application/json` | Yes |
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {token}` | Protected routes only |
| `X-Branch-ID` | `{branch_id}` | Business routes (optional) |

### User Types

| Type | Description | Subscription |
|------|-------------|--------------|
| `individual` | Personal users | FREE - Full access |
| `business` | Business users | Requires paid subscription |

---

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "error_code": "ERROR_CODE",
    "data": {
        "errors": { ... }
    }
}
```

---

## Error Handling

### Common Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Invalid request data |
| `INVALID_CREDENTIALS` | 401 | Wrong email/password |
| `ACCOUNT_DEACTIVATED` | 403 | Account is deactivated |
| `UNAUTHENTICATED` | 401 | Missing/invalid token |
| `NO_SUBSCRIPTION` | 404 | No subscription found |
| `PAYMENT_FAILED` | 400 | Payment processing failed |

### Write-Blocked Response (Business Users)
```json
{
    "blocked": true,
    "reason": "trial_expired|subscription_expired|no_subscription",
    "action": "upgrade_required|renew_required|subscribe_required",
    "can_read": true,
    "can_write": false
}
```

---

## Public Endpoints

### Get Available Plans
```
GET /plans
```

**Response:**
```json
{
    "success": true,
    "data": {
        "plans": [
            {
                "id": 1,
                "name": "dahab plus",
                "price": 0.01,
                "currency": "USD",
                "billing_cycle": "monthly",
                "trial_enabled": true,
                "trial_days": 7
            }
        ]
    }
}
```

---

### Get Countries
```
GET /locations/countries
```

**Response:**
```json
{
    "success": true,
    "data": {
        "countries": [
            {
                "id": 1,
                "name": "Somalia",
                "code": "SOM",
                "code_alpha2": "SO",
                "phone_code": "+252",
                "currency": "SOS",
                "flag": "🇸🇴"
            }
        ]
    }
}
```

---

### Get Regions by Country
```
GET /locations/countries/{countryId}/regions
```

**Response:**
```json
{
    "success": true,
    "data": {
        "country": { "id": 1, "name": "Somalia" },
        "regions": [
            { "id": 2, "name": "Banaadir", "code": null }
        ]
    }
}
```

---

### Get Districts by Region
```
GET /locations/regions/{regionId}/districts
```

**Response:**
```json
{
    "success": true,
    "data": {
        "country": { "id": 1, "name": "Somalia" },
        "region": { "id": 2, "name": "Banaadir" },
        "districts": [
            { "id": 11, "name": "Hodan", "code": null }
        ]
    }
}
```

---

### Get Location Hierarchy (for caching)
```
GET /locations/hierarchy
```

Returns complete country → region → district hierarchy for offline caching.

---

### Search Locations
```
GET /locations/search?q={query}
```

**Query Parameters:**
- `q` (required): Search term (min 2 characters)

---

### Register User
```
POST /auth/register
```

**Request Body (Individual):**
```json
{
    "user_type": "individual",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+252615000001",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123",
    "country_id": 1,
    "region_id": 2,
    "district_id": 11,
    "address": "123 Main Street"
}
```

**Request Body (Business):**
```json
{
    "user_type": "business",
    "name": "Abdinajib Mohamed",
    "email": "business@example.com",
    "phone": "252619821172",
    "password": "SecurePass123",
    "password_confirmation": "SecurePass123",
    "plan_id": 1,
    "country_id": 1,
    "region_id": 2,
    "district_id": 11,
    "address": "Business Address"
}
```

**Response (Individual):**
```json
{
    "success": true,
    "message": "Account created successfully",
    "data": {
        "user": {
            "id": 11,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+252615000001",
            "user_type": "individual",
            "avatar": null,
            "is_active": true,
            "is_free": true,
            "created_at": "2026-01-18T21:40:00+00:00",
            "location": {
                "country": { "id": 1, "name": "Somalia", "flag": "🇸🇴" },
                "region": { "id": 2, "name": "Banaadir" },
                "district": { "id": 11, "name": "Hodan" },
                "address": null
            }
        },
        "token": "11|xxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**Response (Business):**
```json
{
    "success": true,
    "message": "Account created successfully",
    "data": {
        "user": {
            "id": 12,
            "name": "Abdinajib Mohamed",
            "email": "business@example.com",
            "phone": "252619821172",
            "user_type": "business",
            "avatar": null,
            "is_active": true,
            "created_at": "2026-01-18T21:40:11+00:00",
            "location": {
                "country": { "id": 1, "name": "Somalia", "flag": "🇸🇴" },
                "region": { "id": 2, "name": "Banaadir" },
                "district": { "id": 11, "name": "Hodan" },
                "address": null
            }
        },
        "subscription": {
            "id": 7,
            "plan_name": "dahab plus",
            "status": "trial",
            "trial_ends_at": "2026-01-25T21:40:11+00:00",
            "days_remaining": 7
        },
        "token": "12|xxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

### Login
```
POST /auth/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "SecurePass123",
    "device_name": "iPhone 15 Pro"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 12,
            "name": "Abdinajib Mohamed",
            "email": "business@example.com",
            "user_type": "business",
            "is_active": true
        },
        "subscription": {
            "status": "active",
            "plan_name": "dahab plus",
            "can_write": true,
            "days_remaining": 30
        },
        "token": "14|xxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

## Protected Endpoints

> **Note:** All endpoints below require `Authorization: Bearer {token}` header

### Logout
```
POST /auth/logout
```

### Get Current User
```
GET /auth/me
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": { ... },
        "subscription": {
            "id": 7,
            "plan_id": 1,
            "plan_name": "dahab plus",
            "status": "active",
            "can_read": true,
            "can_write": true,
            "trial_ends_at": null,
            "days_remaining": 28,
            "is_blocked": false
        }
    }
}
```

### Get Available Apps/Features
```
GET /apps
```

Returns list of enabled features for the user.

### Get Dashboard
```
GET /dashboard
```

### Get Activity Feed
```
GET /activity?per_page=50
```

**Headers (Business Users):**
```
X-Branch-ID: {branch_id}   # Optional, defaults to main branch
```

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| per_page | int | 20 | Items per page (max 50) |
| page | int | 1 | Page number |

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "income_123",
            "type": "income",
            "description": "Sales revenue",
            "amount": 500.00,
            "category": "sales",
            "account_name": "Cash",
            "account_id": 45,
            "reference": "INV-001",
            "timestamp": "2026-02-18T09:30:00+00:00",
            "date": "2026-02-18"
        },
        {
            "id": "expense_456",
            "type": "expense",
            "description": "Office supplies",
            "amount": 50.00,
            "category": "supplies",
            "account_name": "Cash",
            "account_id": 45,
            "reference": null,
            "timestamp": "2026-02-18T08:15:00+00:00",
            "date": "2026-02-18"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 50,
        "total": 2
    }
}
```

**Notes:**
- Returns combined income and expense transactions sorted by timestamp (newest first)
- Business users: filtered by branch context from `X-Branch-ID` header
- Individual users: returns all user's transactions
- Each item has `type` field to distinguish income vs expense

### Update Profile
```
PUT /profile
```

---

## Subscription & Payments

### Get Subscription Status
```
GET /subscription/status
```

Works for both Individual (FREE) and Business users.

### Get Current Subscription (Business Only)
```
GET /subscription
```

### Get Upgrade Options
```
GET /subscription/upgrade-options
```

**Response:**
```json
{
    "success": true,
    "data": {
        "current_plan": {
            "id": 1,
            "name": "dahab plus",
            "status": "active",
            "days_remaining": 28
        },
        "upgrade_options": [
            {
                "id": 2,
                "name": "pro",
                "price": 99.00,
                "currency": "USD",
                "billing_cycle": "monthly",
                "features": ["Feature 1", "Feature 2"]
            }
        ]
    }
}
```

### Get Payment Methods
```
GET /subscription/payment-methods
```

**Response:**
```json
{
    "success": true,
    "data": {
        "payment_methods": [
            {
                "id": "waafipay",
                "name": "WaafiPay Mobile Money",
                "type": "online",
                "description": "Pay with EVC Plus, Zaad, Jeeb, or Sahal"
            },
            {
                "id": "offline",
                "name": "Bank Transfer / Cash",
                "type": "offline",
                "description": "Manual payment with admin approval"
            }
        ],
        "preferred_method": { ... }
    }
}
```

### Subscribe to Plan (Online Payment)
```
POST /subscription/subscribe
```

**Request Body:**
```json
{
    "plan_id": 1,
    "payment_type": "online",
    "phone_number": "619821172",
    "wallet_type": "evc_plus"
}
```

**Wallet Types:** `evc_plus`, `zaad`, `jeeb`, `sahal`

**Response (Success):**
```json
{
    "success": true,
    "data": {
        "success": true,
        "status": "success",
        "message": "✅ Payment completed successfully!",
        "transaction_id": 5,
        "reference_id": "TXN-20260118214048-0D9CE7",
        "waafi_transaction_id": "65573457",
        "subscription_activated": true,
        "subscription_id": 7
    }
}
```

**Response (Processing - Awaiting User Approval):**
```json
{
    "success": true,
    "data": {
        "success": true,
        "status": "processing",
        "message": "📱 Payment request sent. Please approve on your phone.",
        "transaction_id": 5,
        "reference_id": "TXN-20260118214048-0D9CE7"
    }
}
```

### Subscribe with Offline Payment
```
POST /subscription/subscribe
```

**Request Body:**
```json
{
    "plan_id": 1,
    "payment_type": "offline",
    "proof_of_payment": "Bank transfer receipt #BT-2026-0118-FAARAX from Salaam Bank"
}
```

**Response (Pending Approval):**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "success": true,
        "status": "pending_approval",
        "message": "Payment request submitted successfully. Waiting for admin approval.",
        "transaction_id": 6,
        "reference_id": "OFF-20260118215829-52B053",
        "subscription_id": 8,
        "instructions": null,
        "data": {
            "plan": {
                "id": 1,
                "name": "dahab plus",
                "price": 0.01,
                "currency": "USD"
            },
            "amount": 0.01,
            "currency": "USD"
        }
    }
}
```

**Offline Payment Status Values:**
| Status | Description | Action |
|--------|-------------|--------|
| `pending_approval` | Waiting for admin to verify payment | Show "Pending" badge |
| `approved` | Admin approved, subscription activated | Refresh subscription |
| `rejected` | Admin rejected the payment | Show error, allow retry |

**Offline Payment Flow:**
1. User submits payment with proof (receipt number, transfer ID, etc.)
2. Transaction created with `pending_approval` status
3. Admin reviews in **Admin Panel → Payments → Transactions**
4. Admin approves → Subscription auto-activates
5. Admin rejects → User notified, can retry

### Renew Subscription
```
POST /subscription/renew
```

**Request Body:**
```json
{
    "payment_type": "online",
    "phone_number": "619821172"
}
```

---

## General Payment Endpoints

### Get Payment Methods
```
GET /payment/methods
```

### Initiate Payment
```
POST /payment/initiate
```

**Request Body:**
```json
{
    "phone_number": "619821172",
    "amount": 10.00,
    "wallet_type": "evc_plus",
    "description": "Service payment"
}
```

### Check Payment Status
```
POST /payment/status
```

**Request Body:**
```json
{
    "reference_id": "TXN-20260118214048-0D9CE7"
}
```

### Payment History
```
GET /payment/history
```

### Offline Payment Instructions
```
GET /payment/offline/instructions
```

---

## Business Features

> **Note:** Business routes require `user_type: business` and optional `X-Branch-ID` header

### Business Setup
```
POST /business/setup
GET /business/profile
PUT /business/profile
```

### Business Dashboard
```
GET /business/dashboard
```

### Customers (Receivables)
```
GET    /business/customers
POST   /business/customers
GET    /business/customers/{id}
PUT    /business/customers/{id}
DELETE /business/customers/{id}
POST   /business/customers/{id}/debit
POST   /business/customers/{id}/credit
POST   /business/customers/{id}/deactivate
POST   /business/customers/{id}/activate
GET    /business/customers/{id}/transactions
```

### Vendors (Payables)
```
GET    /business/vendors
POST   /business/vendors
GET    /business/vendors/{id}
PUT    /business/vendors/{id}
DELETE /business/vendors/{id}
POST   /business/vendors/{id}/credit
POST   /business/vendors/{id}/debit
POST   /business/vendors/{id}/deactivate
POST   /business/vendors/{id}/activate
GET    /business/vendors/{id}/transactions
```

### Stock Management
```
GET    /business/stock
POST   /business/stock
GET    /business/stock/{id}
PUT    /business/stock/{id}
DELETE /business/stock/{id}
POST   /business/stock/{id}/increase
POST   /business/stock/{id}/decrease
POST   /business/stock/{id}/deactivate
POST   /business/stock/{id}/activate
GET    /business/stock/{id}/movements
```

### Branches
```
GET    /business/branches
POST   /business/branches
GET    /business/branches/{id}
PUT    /business/branches/{id}
DELETE /business/branches/{id}
```

### Users (Staff Management)

**Feature Guard:** `users`

```
GET    /business/users                      # List all team members
GET    /business/users/permissions          # Get available permissions & roles
POST   /business/users                      # Invite new user
GET    /business/users/{id}                 # Get user details
PUT    /business/users/{id}                 # Update user role/permissions
DELETE /business/users/{id}                 # Remove user from business
POST   /business/users/{id}/deactivate      # Deactivate user
POST   /business/users/{id}/activate        # Activate user
```

#### List Users
```
GET /business/users?active_only=true&role=staff&branch_id=1
```

**Response:**
```json
{
    "success": true,
    "data": {
        "users": [
            {
                "id": 1,
                "user_id": 5,
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "252612345678",
                "avatar": null,
                "role": "admin",
                "role_label": "Admin",
                "branch_id": null,
                "branch_name": null,
                "permissions": {
                    "customers": true,
                    "vendors": true,
                    "income": true,
                    "expense": true,
                    "stock": true,
                    "accounts": true,
                    "reports": true
                },
                "is_active": true,
                "is_owner": false,
                "is_admin": true,
                "created_at": "2026-02-18T10:00:00+00:00"
            }
        ],
        "summary": {
            "total": 3,
            "owners": 1,
            "admins": 1,
            "staff": 1
        }
    }
}
```

#### Invite User
```
POST /business/users
```

**Request:**
```json
{
    "email": "newuser@example.com",
    "name": "New User",
    "phone": "252612345678",
    "role": "staff",
    "branch_id": 1,
    "permissions": {
        "customers": true,
        "vendors": true,
        "income": true,
        "expense": true,
        "stock": false,
        "accounts": false,
        "reports": false
    }
}
```

**Notes:**
- If email exists: Links existing user to business
- If email doesn't exist: Creates new user account with temporary password
- `role` must be `admin` or `staff` (cannot invite as owner)
- `branch_id` is optional (null = all branches)

**Error Codes:**
| Code | Description |
|------|-------------|
| `ALREADY_MEMBER` | User is already a member of this business |
| `OWNS_BUSINESS` | User already owns another business |
| `INVALID_BRANCH` | Branch doesn't belong to this business |

#### Update User
```
PUT /business/users/{id}
```

**Request:**
```json
{
    "role": "admin",
    "branch_id": null,
    "permissions": {
        "customers": true,
        "vendors": true,
        "income": true,
        "expense": true,
        "stock": true,
        "accounts": true,
        "reports": true
    },
    "is_active": true
}
```

**Error Codes:**
| Code | Description |
|------|-------------|
| `CANNOT_MODIFY_OWNER` | Cannot modify business owner |

#### Remove User
```
DELETE /business/users/{id}
```

**Error Codes:**
| Code | Description |
|------|-------------|
| `CANNOT_REMOVE_OWNER` | Cannot remove business owner |
| `CANNOT_REMOVE_SELF` | Cannot remove yourself from the business |

#### Get Available Permissions
```
GET /business/users/permissions
```

**Response:**
```json
{
    "success": true,
    "data": {
        "permissions": {
            "customers": {"name": "Customers", "description": "View and manage customers (receivables)"},
            "vendors": {"name": "Vendors", "description": "View and manage vendors (payables)"},
            "income": {"name": "Income", "description": "Record income transactions"},
            "expense": {"name": "Expenses", "description": "Record expense transactions"},
            "stock": {"name": "Stock", "description": "Manage stock/inventory"},
            "accounts": {"name": "Accounts", "description": "View and manage accounts"},
            "reports": {"name": "Reports", "description": "View profit & loss and other reports"}
        },
        "roles": {
            "owner": {"name": "Owner", "description": "Full access to everything. Cannot be modified."},
            "admin": {"name": "Admin", "description": "Full access to all features. Can manage staff."},
            "staff": {"name": "Staff", "description": "Limited access based on assigned permissions."}
        }
    }
}
```

### Summaries
```
GET /business/receivables/summary
GET /business/payables/summary
GET /business/profit-loss
```

---

## Accounts (All Users)

```
GET    /accounts
POST   /accounts
GET    /accounts/{id}
PUT    /accounts/{id}
DELETE /accounts/{id}
POST   /accounts/{id}/deactivate
POST   /accounts/{id}/activate
GET    /accounts/{id}/ledger
```

---

## Income & Expenses (All Users)

### Income
```
GET    /income
POST   /income
GET    /income/{id}
PUT    /income/{id}
DELETE /income/{id}
```

### Expenses
```
GET    /expenses
POST   /expenses
GET    /expenses/{id}
PUT    /expenses/{id}
DELETE /expenses/{id}
```

---

## Global Search

```
GET /search?q={query}
```

---

## Testing Credentials

### Individual User (Created for Testing)
- **Email:** `individual.test2026@gmail.com`
- **Password:** `Test1234`
- **Type:** Individual (FREE)
- **Location:** Somalia → Banaadir → Hodan

### Business User - Online Payment (Created for Testing)
- **Email:** `johaanpoi663@gmail.com`
- **Password:** `Test1234`
- **Type:** Business
- **Plan:** dahab plus
- **Phone:** 252619821172
- **Location:** Somalia → Banaadir → Hodan
- **Payment:** ✅ Online (WaafiPay) - Completed

### Business User - Offline Payment (Created for Testing)
- **Email:** `faarax.cabdi2026@gmail.com`
- **Password:** `Test1234`
- **Type:** Business
- **Name:** Faarax Cabdi Nuur
- **Plan:** dahab plus
- **Phone:** 252617345678
- **Location:** Somalia → Banaadir → Hodan
- **Payment:** ⏳ Offline - Pending Admin Approval
- **Reference:** `OFF-20260118215829-52B053`
- **Proof:** Bank transfer receipt #BT-2026-0118-FAARAX from Salaam Bank

---

## Webhook (Server-to-Server)

### WaafiPay Webhook
```
POST /waafipay/webhook
```

This endpoint is called by WaafiPay to confirm payment status. No authentication required.

---

## Rate Limiting

- **Default:** 60 requests per minute
- **Authentication:** 5 requests per minute (login/register)

---

## Changelog

### v1.0 (January 2026)
- Initial API release
- Registration with location selection (country/region/district)
- Online payment via WaafiPay
- Offline payment with admin approval
- Dynamic billing cycles with custom days
- Minimum plan price: $0.01
