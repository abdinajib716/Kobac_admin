# Flutter Developer Workflow Guide

**API Base URL:** `https://kobac.cajiibcreative.com/api/v1`  
**Last Updated:** January 18, 2026

This guide provides step-by-step workflows for Flutter developers implementing the Cajiib mobile app.

---

## Table of Contents

1. [Setup & Configuration](#setup--configuration)
2. [Individual User Workflow](#individual-user-workflow)
3. [Business User Workflow](#business-user-workflow)
4. [Payment Flow](#payment-flow)
5. [Error Handling](#error-handling)
6. [Models & DTOs](#models--dtos)

---

## Setup & Configuration

### HTTP Client Setup (Dio)

```dart
import 'package:dio/dio.dart';

class ApiClient {
  static const String baseUrl = 'https://kobac.cajiibcreative.com/api/v1';
  
  final Dio _dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
  ));
  
  void setAuthToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }
  
  void clearAuthToken() {
    _dio.options.headers.remove('Authorization');
  }
}
```

---

## Individual User Workflow

### Step 1: Fetch Locations (Before Registration Screen)

**Purpose:** Populate country/region/district dropdowns

```
GET /locations/countries
```

**Dart Request:**
```dart
Future<List<Country>> getCountries() async {
  final response = await dio.get('/locations/countries');
  final data = response.data['data']['countries'] as List;
  return data.map((e) => Country.fromJson(e)).toList();
}
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

### Step 2: Fetch Regions (After Country Selection)

```
GET /locations/countries/{countryId}/regions
```

**Dart Request:**
```dart
Future<List<Region>> getRegions(int countryId) async {
  final response = await dio.get('/locations/countries/$countryId/regions');
  final data = response.data['data']['regions'] as List;
  return data.map((e) => Region.fromJson(e)).toList();
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "country": { "id": 1, "name": "Somalia" },
    "regions": [
      { "id": 1, "name": "Awdal", "code": null },
      { "id": 2, "name": "Banaadir", "code": null }
    ]
  }
}
```

---

### Step 3: Fetch Districts (After Region Selection)

```
GET /locations/regions/{regionId}/districts
```

**Dart Request:**
```dart
Future<List<District>> getDistricts(int regionId) async {
  final response = await dio.get('/locations/regions/$regionId/districts');
  final data = response.data['data']['districts'] as List;
  return data.map((e) => District.fromJson(e)).toList();
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "country": { "id": 1, "name": "Somalia" },
    "region": { "id": 2, "name": "Banaadir" },
    "districts": [
      { "id": 11, "name": "Hodan", "code": null },
      { "id": 12, "name": "Wadajir", "code": null }
    ]
  }
}
```

---

### Step 4: Register Individual User

```
POST /auth/register
```

**Dart Request:**
```dart
Future<AuthResponse> registerIndividual({
  required String name,
  required String email,
  required String phone,
  required String password,
  int? countryId,
  int? regionId,
  int? districtId,
  String? address,
}) async {
  final response = await dio.post('/auth/register', data: {
    'user_type': 'individual',
    'name': name,
    'email': email,
    'phone': phone,
    'password': password,
    'password_confirmation': password,
    'country_id': countryId,
    'region_id': regionId,
    'district_id': districtId,
    'address': address,
  });
  return AuthResponse.fromJson(response.data);
}
```

**Request Payload:**
```json
{
  "user_type": "individual",
  "name": "Ahmed Ali",
  "email": "ahmed@example.com",
  "phone": "+252615000001",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "country_id": 1,
  "region_id": 2,
  "district_id": 11,
  "address": "Near Main Market"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "user": {
      "id": 11,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
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
        "address": "Near Main Market"
      }
    },
    "token": "11|abc123xyz..."
  }
}
```

**After Registration:**
1. Save `token` to secure storage
2. Save `user` to local state
3. Navigate to Home/Dashboard
4. Individual users have **FREE full access** - no payment needed

---

### Step 5: Login

```
POST /auth/login
```

**Dart Request:**
```dart
Future<AuthResponse> login({
  required String email,
  required String password,
  String? deviceName,
}) async {
  final response = await dio.post('/auth/login', data: {
    'email': email,
    'password': password,
    'device_name': deviceName ?? 'Flutter App',
  });
  return AuthResponse.fromJson(response.data);
}
```

**Request Payload:**
```json
{
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "device_name": "iPhone 15 Pro"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 11,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "user_type": "individual",
      "is_free": true,
      "location": {
        "country": { "id": 1, "name": "Somalia", "flag": "🇸🇴" },
        "region": { "id": 2, "name": "Banaadir" },
        "district": { "id": 11, "name": "Hodan" },
        "address": null
      }
    },
    "access": {
      "can_read": true,
      "can_write": true
    },
    "token": "14|xyz789abc..."
  }
}
```

---

## Business User Workflow

### Step 1: Fetch Available Plans (Before Registration)

```
GET /plans
```

**Dart Request:**
```dart
Future<List<Plan>> getPlans() async {
  final response = await dio.get('/plans');
  final data = response.data['data']['plans'] as List;
  return data.map((e) => Plan.fromJson(e)).toList();
}
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
        "trial_days": 7,
        "features": ["Feature 1", "Feature 2"]
      }
    ]
  }
}
```

---

### Step 2: Register Business User

```
POST /auth/register
```

**Dart Request:**
```dart
Future<AuthResponse> registerBusiness({
  required String name,
  required String email,
  required String phone,
  required String password,
  required int planId,
  int? countryId,
  int? regionId,
  int? districtId,
  String? address,
}) async {
  final response = await dio.post('/auth/register', data: {
    'user_type': 'business',
    'name': name,
    'email': email,
    'phone': phone,
    'password': password,
    'password_confirmation': password,
    'plan_id': planId,
    'country_id': countryId,
    'region_id': regionId,
    'district_id': districtId,
    'address': address,
  });
  return AuthResponse.fromJson(response.data);
}
```

**Request Payload:**
```json
{
  "user_type": "business",
  "name": "Abdinajib Mohamed Karshe",
  "email": "johaanpoi663@gmail.com",
  "phone": "252619821172",
  "password": "Test1234",
  "password_confirmation": "Test1234",
  "plan_id": 1,
  "country_id": 1,
  "region_id": 2,
  "district_id": 11,
  "address": null
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Account created successfully",
  "data": {
    "user": {
      "id": 12,
      "name": "Abdinajib Mohamed Karshe",
      "email": "johaanpoi663@gmail.com",
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
    "token": "12|def456ghi..."
  }
}
```

**After Business Registration:**
1. Save `token` to secure storage
2. Save `user` and `subscription` to local state
3. Check `subscription.status`:
   - `trial`: Show trial banner with days remaining
   - `active`: Full access
   - `expired`: Prompt to pay/renew

---

### Step 3: Check Subscription Status (On App Launch)

```
GET /subscription/status
```

**Dart Request:**
```dart
Future<SubscriptionStatus> getSubscriptionStatus() async {
  final response = await dio.get('/subscription/status');
  return SubscriptionStatus.fromJson(response.data['data']);
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_type": "business",
    "subscription": {
      "status": "active",
      "plan_name": "dahab plus",
      "can_read": true,
      "can_write": true,
      "days_remaining": 30,
      "is_blocked": false
    }
  }
}
```

---

## Payment Flow

### Step 1: Get Available Payment Methods

```
GET /subscription/payment-methods
```

**Dart Request:**
```dart
Future<List<PaymentMethod>> getPaymentMethods() async {
  final response = await dio.get('/subscription/payment-methods');
  final data = response.data['data']['payment_methods'] as List;
  return data.map((e) => PaymentMethod.fromJson(e)).toList();
}
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
      }
    ],
    "preferred_method": { ... }
  }
}
```

---

### Step 2: Subscribe with Online Payment (WaafiPay)

```
POST /subscription/subscribe
```

**Dart Request:**
```dart
Future<PaymentResult> subscribeOnline({
  required int planId,
  required String phoneNumber,
  String? walletType,
}) async {
  final response = await dio.post('/subscription/subscribe', data: {
    'plan_id': planId,
    'payment_type': 'online',
    'phone_number': phoneNumber,
    'wallet_type': walletType, // evc_plus, zaad, jeeb, sahal
  });
  return PaymentResult.fromJson(response.data['data']);
}
```

**Request Payload:**
```json
{
  "plan_id": 1,
  "payment_type": "online",
  "phone_number": "619821172",
  "wallet_type": "evc_plus"
}
```

**Success Response (Payment Completed):**
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

**Processing Response (Awaiting User Approval):**
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

**Payment Flow UI:**
```dart
void handlePaymentResult(PaymentResult result) {
  switch (result.status) {
    case 'success':
      // Payment completed - refresh subscription status
      showSuccessDialog('Payment successful!');
      refreshSubscription();
      break;
    case 'processing':
      // Show waiting dialog
      showWaitingDialog(
        'Please approve the payment on your phone',
        onCheckStatus: () => checkPaymentStatus(result.referenceId),
      );
      break;
    case 'failed':
      showErrorDialog(result.message);
      break;
  }
}
```

---

### Step 3: Subscribe with Offline Payment

```
POST /subscription/subscribe
```

**Request Payload:**
```json
{
  "plan_id": 1,
  "payment_type": "offline",
  "proof_of_payment": "Bank transfer receipt #12345"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "success": true,
    "status": "pending_approval",
    "message": "Payment submitted for admin approval",
    "transaction_id": 6,
    "reference_id": "OFF-20260118215000-ABC123"
  }
}
```

---

## Error Handling

### Validation Error (422)

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

**Dart Handler:**
```dart
try {
  final result = await authService.register(...);
} on DioException catch (e) {
  if (e.response?.statusCode == 422) {
    final errors = e.response?.data['data']['errors'] as Map<String, dynamic>;
    errors.forEach((field, messages) {
      showFieldError(field, (messages as List).first);
    });
  }
}
```

---

### Authentication Error (401)

```json
{
  "success": false,
  "message": "Invalid credentials",
  "error_code": "INVALID_CREDENTIALS"
}
```

---

### Write Blocked Error (Business Users)

```json
{
  "blocked": true,
  "reason": "subscription_expired",
  "action": "renew_required",
  "can_read": true,
  "can_write": false
}
```

**Dart Handler:**
```dart
void handleWriteBlocked(Map<String, dynamic> response) {
  if (response['blocked'] == true) {
    final action = response['action'];
    switch (action) {
      case 'upgrade_required':
        navigateToUpgrade();
        break;
      case 'renew_required':
        navigateToRenew();
        break;
      case 'subscribe_required':
        navigateToSubscribe();
        break;
    }
  }
}
```

---

## Models & DTOs

### User Model

```dart
class User {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final String userType;
  final String? avatar;
  final bool isActive;
  final bool? isFree;
  final DateTime createdAt;
  final Location? location;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    required this.userType,
    this.avatar,
    required this.isActive,
    this.isFree,
    required this.createdAt,
    this.location,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      userType: json['user_type'],
      avatar: json['avatar'],
      isActive: json['is_active'],
      isFree: json['is_free'],
      createdAt: DateTime.parse(json['created_at']),
      location: json['location'] != null 
          ? Location.fromJson(json['location']) 
          : null,
    );
  }
  
  bool get isIndividual => userType == 'individual';
  bool get isBusiness => userType == 'business';
}
```

### Location Model

```dart
class Location {
  final Country? country;
  final Region? region;
  final District? district;
  final String? address;

  Location({this.country, this.region, this.district, this.address});

  factory Location.fromJson(Map<String, dynamic> json) {
    return Location(
      country: json['country'] != null 
          ? Country.fromJson(json['country']) 
          : null,
      region: json['region'] != null 
          ? Region.fromJson(json['region']) 
          : null,
      district: json['district'] != null 
          ? District.fromJson(json['district']) 
          : null,
      address: json['address'],
    );
  }
  
  String get fullAddress {
    final parts = [
      district?.name,
      region?.name,
      country?.name,
    ].whereType<String>().toList();
    return parts.join(', ');
  }
}

class Country {
  final int id;
  final String name;
  final String? code;
  final String? phoneCode;
  final String? flag;

  Country({
    required this.id,
    required this.name,
    this.code,
    this.phoneCode,
    this.flag,
  });

  factory Country.fromJson(Map<String, dynamic> json) {
    return Country(
      id: json['id'],
      name: json['name'],
      code: json['code'],
      phoneCode: json['phone_code'],
      flag: json['flag'],
    );
  }
}

class Region {
  final int id;
  final String name;
  final String? code;

  Region({required this.id, required this.name, this.code});

  factory Region.fromJson(Map<String, dynamic> json) {
    return Region(
      id: json['id'],
      name: json['name'],
      code: json['code'],
    );
  }
}

class District {
  final int id;
  final String name;
  final String? code;

  District({required this.id, required this.name, this.code});

  factory District.fromJson(Map<String, dynamic> json) {
    return District(
      id: json['id'],
      name: json['name'],
      code: json['code'],
    );
  }
}
```

### Subscription Model

```dart
class Subscription {
  final int? id;
  final int? planId;
  final String planName;
  final String status;
  final bool canRead;
  final bool canWrite;
  final DateTime? trialEndsAt;
  final int daysRemaining;
  final bool isBlocked;

  Subscription({
    this.id,
    this.planId,
    required this.planName,
    required this.status,
    required this.canRead,
    required this.canWrite,
    this.trialEndsAt,
    required this.daysRemaining,
    required this.isBlocked,
  });

  factory Subscription.fromJson(Map<String, dynamic> json) {
    return Subscription(
      id: json['id'],
      planId: json['plan_id'],
      planName: json['plan_name'] ?? 'Unknown',
      status: json['status'] ?? 'unknown',
      canRead: json['can_read'] ?? true,
      canWrite: json['can_write'] ?? false,
      trialEndsAt: json['trial_ends_at'] != null 
          ? DateTime.parse(json['trial_ends_at']) 
          : null,
      daysRemaining: json['days_remaining'] ?? 0,
      isBlocked: json['is_blocked'] ?? false,
    );
  }

  bool get isActive => status == 'active';
  bool get isTrial => status == 'trial';
  bool get isExpired => status == 'expired';
}
```

### Plan Model

```dart
class Plan {
  final int id;
  final String name;
  final double price;
  final String currency;
  final String billingCycle;
  final bool trialEnabled;
  final int trialDays;
  final List<String>? features;

  Plan({
    required this.id,
    required this.name,
    required this.price,
    required this.currency,
    required this.billingCycle,
    required this.trialEnabled,
    required this.trialDays,
    this.features,
  });

  factory Plan.fromJson(Map<String, dynamic> json) {
    return Plan(
      id: json['id'],
      name: json['name'],
      price: (json['price'] as num).toDouble(),
      currency: json['currency'] ?? 'USD',
      billingCycle: json['billing_cycle'] ?? 'monthly',
      trialEnabled: json['trial_enabled'] ?? false,
      trialDays: json['trial_days'] ?? 0,
      features: json['features'] != null 
          ? List<String>.from(json['features']) 
          : null,
    );
  }
  
  String get formattedPrice => '\$$price/$billingCycle';
}
```

---

## Quick Reference

### Endpoint Summary

| Action | Method | Endpoint | Auth |
|--------|--------|----------|------|
| Get Plans | GET | `/plans` | ❌ |
| Get Countries | GET | `/locations/countries` | ❌ |
| Get Regions | GET | `/locations/countries/{id}/regions` | ❌ |
| Get Districts | GET | `/locations/regions/{id}/districts` | ❌ |
| Register | POST | `/auth/register` | ❌ |
| Login | POST | `/auth/login` | ❌ |
| Logout | POST | `/auth/logout` | ✅ |
| Get Profile | GET | `/auth/me` | ✅ |
| Subscription Status | GET | `/subscription/status` | ✅ |
| Payment Methods | GET | `/subscription/payment-methods` | ✅ |
| Subscribe | POST | `/subscription/subscribe` | ✅ |
| Renew | POST | `/subscription/renew` | ✅ |

### Validation Rules

| Field | Rules |
|-------|-------|
| `name` | Required, max 255 chars |
| `email` | Required, valid email, unique |
| `phone` | Optional, max 20 chars |
| `password` | Required, min 8 chars, must match confirmation |
| `plan_id` | Required for business, must exist |
| `country_id` | Optional, must exist |
| `region_id` | Optional, must exist, must belong to country |
| `district_id` | Optional, must exist, must belong to region |

---

## Testing Credentials

| Type | Email | Password |
|------|-------|----------|
| Individual | `individual.test2026@gmail.com` | `Test1234` |
| Business | `johaanpoi663@gmail.com` | `Test1234` |
