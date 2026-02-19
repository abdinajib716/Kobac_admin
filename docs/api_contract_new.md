# KOBAC Mobile App - New API Endpoints Contract

**Version:** 2.0  
**Last Updated:** February 19, 2026  
**Base URL:** `https://kobac.cajiibcreative.com/api/v1`

---

## Table of Contents

1. [WhatsApp Support](#1-whatsapp-support)
2. [Staff Management - Resend Invitation](#2-staff-management---resend-invitation)
3. [Staff Management - Reset Password](#3-staff-management---reset-password)
4. [Forgot Password (Fixed - Now Sends Email)](#4-forgot-password)

---

## 1. WhatsApp Support

Get WhatsApp support widget configuration for displaying in the mobile app.

### Endpoint

```
GET /api/v1/support/whatsapp
```

### Authentication

**Not Required** - Public endpoint

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| Accept | Yes | `application/json` |

### Response (WhatsApp Enabled)

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "enabled": true,
    "phone_number": "252613954330",
    "agent_name": "Ardaykaab",
    "agent_title": "Typically replies instantly",
    "greeting_message": "Assalamu Alaikum! 👋\nSo dhawoow macmiil sidee ku caawiya",
    "default_message": "Wcs ardaykaab",
    "whatsapp_url": "https://wa.me/252613954330?text=Wcs%20ardaykaab"
  }
}
```

### Response (WhatsApp Disabled)

```json
{
  "success": true,
  "message": "WhatsApp support is disabled",
  "data": {
    "enabled": false
  }
}
```

### Flutter Usage

```dart
// Check if enabled before showing WhatsApp button
if (response.data['enabled'] == true) {
  // Show WhatsApp floating button with:
  // - Agent name
  // - Agent title
  // - Greeting message in popup
  // - Open whatsapp_url on "Start Chat" tap
}
```

---

## 2. Staff Management - Resend Invitation

Resend invitation email to a staff user with a new temporary password.

### Endpoint

```
POST /api/v1/business/users/{businessUser}/resend-invitation
```

### Authentication

**Required** - Bearer Token  
**Role Required** - Owner or Admin

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| Authorization | Yes | `Bearer {token}` |
| Accept | Yes | `application/json` |
| X-Branch-ID | Optional | Branch context |

### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| businessUser | integer | Business user ID (not user_id) |

### Request Body

None required.

### Success Response (201)

```json
{
  "success": true,
  "message": "Invitation resent successfully",
  "data": {
    "email": "staff@example.com",
    "resent_at": "2026-02-19T09:30:00+00:00"
  }
}
```

### Error Responses

**400 - Cannot Resend to Owner**
```json
{
  "success": false,
  "message": "Cannot resend invitation to business owner",
  "error_code": "CANNOT_RESEND_TO_OWNER"
}
```

**403 - Unauthorized**
```json
{
  "success": false,
  "message": "Only owners and admins can manage users"
}
```

**404 - User Not Found**
```json
{
  "success": false,
  "message": "User not found",
  "error_code": "USER_NOT_FOUND"
}
```

### Email Sent

The staff user receives an email with:
- Business name
- Their role (admin/staff)
- Branch assignment (if any)
- **New temporary password**
- Login URL

---

## 3. Staff Management - Reset Password

Reset a staff user's password. Owner/Admin can reset passwords for their staff members.

### Endpoint

```
POST /api/v1/business/users/{businessUser}/reset-password
```

### Authentication

**Required** - Bearer Token  
**Role Required** - Owner or Admin

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| Authorization | Yes | `Bearer {token}` |
| Accept | Yes | `application/json` |
| Content-Type | Yes | `application/json` |
| X-Branch-ID | Optional | Branch context |

### Path Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| businessUser | integer | Business user ID (not user_id) |

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| new_password | string | No | Custom password (min 8 chars). If not provided, generates random 12-char password |
| send_email | boolean | No | Send email with new password (default: true) |

### Example Request

```json
{
  "new_password": "NewSecure123!",
  "send_email": true
}
```

### Example Request (Auto-generate password, no email)

```json
{
  "send_email": false
}
```

### Success Response (200)

**With email sent:**
```json
{
  "success": true,
  "message": "Password reset successfully",
  "data": {
    "user_id": 47,
    "email": "staff@example.com",
    "password_reset_at": "2026-02-19T09:35:00+00:00",
    "email_sent": true,
    "temporary_password": null
  }
}
```

**Without email (password returned):**
```json
{
  "success": true,
  "message": "Password reset successfully",
  "data": {
    "user_id": 47,
    "email": "staff@example.com",
    "password_reset_at": "2026-02-19T09:35:00+00:00",
    "email_sent": false,
    "temporary_password": "xK9mPq2rT5wZ"
  }
}
```

### Error Responses

**400 - Cannot Reset Owner Password**
```json
{
  "success": false,
  "message": "Cannot reset business owner password",
  "error_code": "CANNOT_RESET_OWNER"
}
```

**422 - Validation Error**
```json
{
  "success": false,
  "message": "Validation failed",
  "error_code": "VALIDATION_ERROR",
  "data": {
    "errors": {
      "new_password": ["The new password must be at least 8 characters."]
    }
  }
}
```

### Flutter UI Suggestion

```dart
// Show dialog with options:
// 1. "Generate Random Password" - send_email: true
// 2. "Set Custom Password" - show text field, send_email: false
//    Then display temporary_password to the admin to share manually
```

---

## 4. Forgot Password

Request a password reset code. **Now sends email with OTP code.**

### Endpoint

```
POST /api/v1/auth/forgot-password
```

### Authentication

**Not Required** - Public endpoint

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| Accept | Yes | `application/json` |
| Content-Type | Yes | `application/json` |

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | User's email address |

### Example Request

```json
{
  "email": "user@example.com"
}
```

### Success Response (200)

```json
{
  "success": true,
  "message": "Reset code sent to your email",
  "data": {
    "email": "user@example.com",
    "expires_in": 900
  }
}
```

### Email Sent

User receives an email with:
- 6-digit OTP code
- Code validity (15 minutes)
- Security notice

### Next Steps (Existing Endpoints)

1. **Verify Code:**
   ```
   POST /api/v1/auth/verify-reset-code
   {
     "email": "user@example.com",
     "code": "123456"
   }
   ```

2. **Reset Password:**
   ```
   POST /api/v1/auth/reset-password
   {
     "email": "user@example.com",
     "token": "{reset_token_from_verify}",
     "password": "NewPassword123!",
     "password_confirmation": "NewPassword123!"
   }
   ```

---

## Summary of New Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/support/whatsapp` | No | Get WhatsApp widget config |
| POST | `/business/users/{id}/resend-invitation` | Yes | Resend staff invitation email |
| POST | `/business/users/{id}/reset-password` | Yes | Reset staff password |
| POST | `/auth/forgot-password` | No | Request password reset (now sends email) |

---

## Admin Panel Settings (for WhatsApp)

The WhatsApp widget is configured via the admin panel under **Settings > WhatsApp Support** tab:

| Setting Key | Description |
|-------------|-------------|
| `whatsapp_enabled` | Toggle widget on/off |
| `whatsapp_phone_number` | WhatsApp number (with country code, no +) |
| `whatsapp_agent_name` | Support agent display name |
| `whatsapp_agent_title` | Subtitle (e.g., "Typically replies instantly") |
| `whatsapp_greeting_message` | Message shown in popup widget |
| `whatsapp_default_message` | Pre-filled message when user taps "Start Chat" |

---

## Testing with cURL

### Test WhatsApp Support
```bash
curl -X GET "https://kobac.cajiibcreative.com/api/v1/support/whatsapp" \
  -H "Accept: application/json"
```

### Test Resend Invitation
```bash
curl -X POST "https://kobac.cajiibcreative.com/api/v1/business/users/4/resend-invitation" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "X-Branch-ID: 26"
```

### Test Reset Password
```bash
curl -X POST "https://kobac.cajiibcreative.com/api/v1/business/users/4/reset-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Branch-ID: 26" \
  -d '{"send_email": false}'
```

### Test Forgot Password
```bash
curl -X POST "https://kobac.cajiibcreative.com/api/v1/auth/forgot-password" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'
```
