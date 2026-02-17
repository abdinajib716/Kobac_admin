# Subscription & Registration System Documentation

## Overview

This document describes the enhanced subscription and registration system implemented for the Cajiib Dashboard application. The system supports:

- **Dynamic billing cycles** with custom billing days
- **Unified payment workflow** for online and offline payments
- **Location-based registration** with country, region, and district selection
- **JSON-based configuration** for easy location data management

---

## Table of Contents

1. [Subscription System](#subscription-system)
2. [Payment Workflow](#payment-workflow)
3. [User Registration](#user-registration)
4. [Location Configuration](#location-configuration)
5. [API Endpoints](#api-endpoints)
6. [Testing Guide](#testing-guide)

---

## Subscription System

### Plan Configuration

Plans support flexible billing cycles with the following options:

| Billing Cycle | Days | Description |
|---------------|------|-------------|
| `weekly` | 7 | Weekly subscription |
| `monthly` | 30 | Monthly subscription |
| `quarterly` | 90 | Quarterly subscription |
| `yearly` | 365 | Annual subscription |
| `lifetime` | ~100 years | Permanent access |
| `custom` | User-defined | Custom number of days |

### Dynamic Billing Days

When `billing_cycle` is set to `custom`, the `billing_days` field allows you to set any number of days (1-3650). This enables flexible billing periods like:

- 15-day plans
- 45-day plans
- 60-day plans
- Any custom duration

### Minimum Price

The minimum allowed price for any plan is **$0.01**.

### Plan Model

```php
// app/Models/Plan.php

// Get effective billing days (respects custom billing_days)
$plan->effective_billing_days; // Returns integer

// Get human-readable billing cycle label
$plan->billing_cycle_label; // Returns "15 days" or "Monthly"
```

### Admin Panel (Filament)

The PlanResource in Filament allows admins to:

1. Set plan name, description, and slug
2. Configure pricing with minimum $0.01
3. Select billing cycle (including custom)
4. Set custom billing days when custom cycle is selected
5. Enable/disable trial periods
6. Configure plan features

---

## Payment Workflow

### Supported Payment Methods

The system supports two payment methods:

#### 1. Online Payment (WaafiPay)

- **Status**: Fully functional
- **Providers**: EVC Plus, Zaad, Jeeb, Sahal
- **Process**:
  1. User initiates payment with phone number
  2. WaafiPay sends payment request to user's mobile wallet
  3. User approves on their phone
  4. Webhook confirms payment
  5. Subscription activated automatically

#### 2. Offline Payment

- **Status**: Integrated
- **Process**:
  1. User initiates offline payment request
  2. System creates transaction with `pending_approval` status
  3. Admin reviews and approves/rejects in dashboard
  4. Upon approval, subscription is activated

### Unified Transaction System

All payments (online and offline) are tracked in the `payment_transactions` table:

```php
// app/Models/PaymentTransaction.php

$transaction->payment_type; // 'online' or 'offline'
$transaction->status;       // 'pending', 'processing', 'success', 'failed', 'pending_approval', 'approved', 'rejected'
$transaction->subscription; // Related subscription
$transaction->plan;         // Related plan
```

### SubscriptionPaymentService

The unified payment service handles all payment logic:

```php
use App\Services\SubscriptionPaymentService;

$service = app(SubscriptionPaymentService::class);

// Get available payment methods
$methods = $service->getAvailablePaymentMethods();

// Process a payment
$result = $service->processPayment([
    'user' => $user,
    'plan_id' => 1,
    'payment_type' => 'online', // or 'offline'
    'phone_number' => '615123456',
]);

// Renew subscription
$result = $service->renewSubscription($user, [
    'payment_type' => 'online',
    'phone_number' => '615123456',
]);
```

### Payment Flow Diagram

```
┌─────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Mobile    │────>│  API Gateway    │────>│ Payment Service │
│    App      │     │  (Laravel)      │     │                 │
└─────────────┘     └─────────────────┘     └─────────────────┘
                                                    │
                    ┌───────────────────────────────┴───────────────────────────────┐
                    │                                                               │
                    ▼                                                               ▼
          ┌─────────────────┐                                             ┌─────────────────┐
          │  Online Payment │                                             │ Offline Payment │
          │   (WaafiPay)    │                                             │   (Manual)      │
          └─────────────────┘                                             └─────────────────┘
                    │                                                               │
                    ▼                                                               ▼
          ┌─────────────────┐                                             ┌─────────────────┐
          │  Webhook/API    │                                             │ Admin Approval  │
          │   Callback      │                                             │   (Filament)    │
          └─────────────────┘                                             └─────────────────┘
                    │                                                               │
                    └───────────────────────────────┬───────────────────────────────┘
                                                    │
                                                    ▼
                                          ┌─────────────────┐
                                          │   Subscription  │
                                          │   Activated     │
                                          └─────────────────┘
```

---

## User Registration

### Supported User Types

| Type | Description | Subscription |
|------|-------------|--------------|
| `individual` | Personal users | FREE - No subscription needed |
| `business` | Business users | Requires paid subscription |
| `client` | Admin panel users | N/A |

### Registration Fields

```json
{
    "user_type": "business",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+252615123456",
    "password": "password123",
    "password_confirmation": "password123",
    "plan_id": 1,
    "country_id": 1,
    "region_id": 5,
    "district_id": 10,
    "address": "123 Main Street"
}
```

### Location Hierarchy Validation

The system validates that:
- Region belongs to selected country
- District belongs to selected region

---

## Location Configuration

### JSON Structure

Location data is stored in JSON files at `database/data/locations/`:

```json
{
    "country": {
        "name": "Somalia",
        "code": "SOM",
        "code_alpha2": "SO",
        "phone_code": "+252",
        "currency": "SOS",
        "flag": "🇸🇴"
    },
    "regions": {
        "Banaadir": [
            "Hodan",
            "Kaaraan",
            "Wadajir"
        ],
        "Bari": [
            "Boosaaso",
            "Qardho"
        ]
    }
}
```

### Adding New Countries

1. Create a new JSON file in `database/data/locations/` (e.g., `kenya.json`)
2. Follow the JSON structure above
3. Run the seeder:

```bash
php artisan db:seed --class=LocationSeeder
```

Or programmatically:

```php
use Database\Seeders\LocationSeeder;

$result = LocationSeeder::seedCountry(database_path('data/locations/kenya.json'));
```

### Database Tables

| Table | Description |
|-------|-------------|
| `countries` | Country information (code, phone_code, currency, flag) |
| `regions` | States/provinces linked to countries |
| `districts` | Districts/cities linked to regions |

---

## API Endpoints

### Public Endpoints (No Authentication)

#### Plans
```
GET /api/v1/plans
```

#### Locations
```
GET /api/v1/locations/countries
GET /api/v1/locations/countries/{countryId}/regions
GET /api/v1/locations/regions/{regionId}/districts
GET /api/v1/locations/hierarchy
GET /api/v1/locations/search?q=mogadishu
```

#### Authentication
```
POST /api/v1/auth/register
POST /api/v1/auth/login
```

### Protected Endpoints (Authentication Required)

#### Subscription
```
GET  /api/v1/subscription/status
GET  /api/v1/subscription
GET  /api/v1/subscription/upgrade-options
GET  /api/v1/subscription/payment-methods
POST /api/v1/subscription/subscribe
POST /api/v1/subscription/renew
```

#### Payment
```
GET  /api/v1/payment/methods
POST /api/v1/payment/initiate
POST /api/v1/payment/status
GET  /api/v1/payment/history
POST /api/v1/payment/offline
POST /api/v1/payment/offline/status
GET  /api/v1/payment/offline/instructions
```

---

## Testing Guide

### 1. Test Location API

```bash
# Get all countries
curl http://localhost/api/v1/locations/countries

# Get regions for Somalia (country_id = 1)
curl http://localhost/api/v1/locations/countries/1/regions

# Get districts for Banaadir (region_id = 2)
curl http://localhost/api/v1/locations/regions/2/districts

# Get complete hierarchy (for caching)
curl http://localhost/api/v1/locations/hierarchy

# Search locations
curl "http://localhost/api/v1/locations/search?q=hodan"
```

### 2. Test Registration with Location

```bash
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "user_type": "business",
    "name": "Test Business",
    "email": "test@example.com",
    "phone": "+252615123456",
    "password": "password123",
    "password_confirmation": "password123",
    "plan_id": 1,
    "country_id": 1,
    "region_id": 2,
    "district_id": 7
  }'
```

### 3. Test Subscription Payment

```bash
# Get available payment methods
curl http://localhost/api/v1/subscription/payment-methods \
  -H "Authorization: Bearer {token}"

# Subscribe with online payment
curl -X POST http://localhost/api/v1/subscription/subscribe \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 1,
    "payment_type": "online",
    "phone_number": "615123456"
  }'

# Subscribe with offline payment
curl -X POST http://localhost/api/v1/subscription/subscribe \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 1,
    "payment_type": "offline",
    "proof_of_payment": "Bank transfer receipt #12345"
  }'
```

### 4. Test Plan with Custom Billing Days

In Filament admin panel:
1. Go to Subscription Management > Business Plans
2. Create new plan
3. Set price (min $0.01)
4. Select "Custom (Set days)" for billing cycle
5. Enter custom days (e.g., 45)
6. Save and verify

### 5. Verify Database

```bash
php artisan tinker

# Check countries
App\Models\Country::count(); // Should return 1 (Somalia)

# Check regions
App\Models\Region::count(); // Should return 18

# Check districts
App\Models\District::count(); // Should return 106

# Check plan billing days
$plan = App\Models\Plan::first();
$plan->effective_billing_days; // Returns calculated days
```

---

## Configuration Files

| File | Purpose |
|------|---------|
| `database/data/locations/somalia.json` | Somalia location data |
| `app/Models/Country.php` | Country model |
| `app/Models/Region.php` | Region model |
| `app/Models/District.php` | District model |
| `app/Services/SubscriptionPaymentService.php` | Unified payment service |
| `app/Services/OfflinePaymentService.php` | Offline payment handling |
| `app/Services/WaafiPayService.php` | WaafiPay integration |
| `database/seeders/LocationSeeder.php` | Location data seeder |

---

## Migration Files

| Migration | Description |
|-----------|-------------|
| `2026_01_18_210900_add_billing_days_to_plans_table.php` | Adds billing_days field |
| `2026_01_18_210901_create_locations_tables.php` | Creates countries, regions, districts tables |
| `2026_01_18_210902_add_location_fields_to_users_table.php` | Adds location fields to users |

---

## Summary

### What Was Implemented

1. **Dynamic Billing Cycle**
   - Custom billing days support (1-3650 days)
   - Minimum price $0.01
   - Updated Plan model and PlanResource

2. **Unified Payment System**
   - SubscriptionPaymentService for all payment handling
   - Online payment via WaafiPay
   - Offline payment with admin approval
   - Automatic subscription activation

3. **Location-Based Registration**
   - Country, Region, District models
   - JSON configuration for easy data management
   - Somalia data seeded (18 regions, 106 districts)
   - Cascading location selection API
   - Location hierarchy validation

4. **API Endpoints**
   - Location discovery endpoints
   - Subscription management endpoints
   - Payment processing endpoints

### Files Created/Modified

- **New Files**: 12
- **Modified Files**: 7
- **Migrations**: 3
- **Models**: 3
- **Controllers**: 1
- **Services**: 1
- **Seeders**: 1
