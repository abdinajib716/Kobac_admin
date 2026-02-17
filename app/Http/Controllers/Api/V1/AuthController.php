<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    /**
     * Register a new mobile user
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:individual,business',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan_id' => 'required_if:user_type,business|exists:plans,id',
            // Location fields
            'country_id' => 'nullable|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        
        // Validate location hierarchy
        if (isset($data['region_id']) && isset($data['country_id'])) {
            $region = \App\Models\Region::find($data['region_id']);
            if ($region && $region->country_id != $data['country_id']) {
                return $this->error('Region does not belong to selected country', 'VALIDATION_ERROR', 422);
            }
        }
        
        if (isset($data['district_id']) && isset($data['region_id'])) {
            $district = \App\Models\District::find($data['district_id']);
            if ($district && $district->region_id != $data['region_id']) {
                return $this->error('District does not belong to selected region', 'VALIDATION_ERROR', 422);
            }
        }
        
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'first_name' => explode(' ', $data['name'])[0] ?? $data['name'],
            'last_name' => explode(' ', $data['name'])[1] ?? '',
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'region_id' => $data['region_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
            'user_type' => $data['user_type'],
            'is_active' => true,
        ]);

        $response = [
            'user' => $this->formatUser($user),
        ];

        // Business users need subscription
        if ($user->isBusiness()) {
            $plan = Plan::findOrFail($data['plan_id']);
            $subscription = Subscription::createTrialForUser($user, $plan);
            
            $response['subscription'] = [
                'id' => $subscription->id,
                'plan_name' => $plan->name,
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'days_remaining' => $subscription->days_remaining,
            ];
        }

        // Create token
        $response['token'] = $user->createToken('mobile-app')->plainTextToken;

        return $this->success($response, 'Account created successfully', 201);
    }

    /**
     * Login user
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 'INVALID_CREDENTIALS', 401);
        }

        if (!$user->is_active) {
            return $this->error('Your account has been deactivated', 'ACCOUNT_DEACTIVATED', 403);
        }

        if (!$user->isMobileUser()) {
            return $this->error('This account cannot access the mobile app', 'INVALID_ACCOUNT_TYPE', 403);
        }

        $response = [
            'user' => $this->formatUser($user),
        ];

        // Individual users - FREE
        if ($user->isIndividual()) {
            $response['access'] = [
                'can_read' => true,
                'can_write' => true,
            ];
        }

        // Business users - subscription based
        if ($user->isBusiness()) {
            $subscription = $user->subscription;
            if ($subscription) {
                $response['subscription'] = [
                    'status' => $subscription->status,
                    'plan_name' => $subscription->plan->name ?? 'Unknown',
                    'can_write' => $subscription->canWrite(),
                    'days_remaining' => $subscription->days_remaining,
                ];
            }
        }

        $deviceName = $request->device_name ?? 'mobile-app';
        $response['token'] = $user->createToken($deviceName)->plainTextToken;

        return $this->success($response, 'Login successful');
    }

    /**
     * Logout user
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get current user profile
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $response = [
            'user' => $this->formatUser($user),
        ];

        // Individual users - FREE
        if ($user->isIndividual()) {
            $response['access'] = [
                'can_read' => true,
                'can_write' => true,
                'is_blocked' => false,
            ];
        }

        // Business users - subscription based
        if ($user->isBusiness()) {
            $subscription = $user->subscription;
            if ($subscription) {
                $response['subscription'] = [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'plan_name' => $subscription->plan->name ?? 'Unknown',
                    'status' => $subscription->status,
                    'can_read' => $subscription->canRead(),
                    'can_write' => $subscription->canWrite(),
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                    'days_remaining' => $subscription->days_remaining,
                    'is_blocked' => !$subscription->canWrite(),
                ];
            }
        }

        return $this->success($response);
    }

    /**
     * Request password reset (Forgot Password)
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Return success even if user not found (security best practice)
            return $this->success(null, 'If this email exists, a reset code has been sent');
        }

        if (!$user->isMobileUser()) {
            return $this->success(null, 'If this email exists, a reset code has been sent');
        }

        // Generate 6-digit OTP code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in cache (expires in 15 minutes)
        \Cache::put('password_reset_' . $user->email, [
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ], now()->addMinutes(15));

        // TODO: Send SMS/Email with code
        // For now, log it (remove in production)
        \Log::info("Password reset code for {$user->email}: {$code}");

        return $this->success([
            'email' => $user->email,
            'expires_in' => 900, // 15 minutes in seconds
        ], 'Reset code sent to your email/phone');
    }

    /**
     * Verify reset code
     * POST /api/v1/auth/verify-reset-code
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $cached = \Cache::get('password_reset_' . $request->email);

        if (!$cached || $cached['code'] !== $request->code) {
            return $this->error('Invalid or expired reset code', 'INVALID_CODE', 400);
        }

        if (now()->isAfter($cached['expires_at'])) {
            \Cache::forget('password_reset_' . $request->email);
            return $this->error('Reset code has expired', 'CODE_EXPIRED', 400);
        }

        // Generate a temporary token for password reset
        $resetToken = Str::random(64);
        \Cache::put('password_reset_token_' . $request->email, $resetToken, now()->addMinutes(10));

        return $this->success([
            'reset_token' => $resetToken,
            'expires_in' => 600, // 10 minutes
        ], 'Code verified successfully');
    }

    /**
     * Reset password with token
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $storedToken = \Cache::get('password_reset_token_' . $request->email);

        if (!$storedToken || $storedToken !== $request->reset_token) {
            return $this->error('Invalid or expired reset token', 'INVALID_TOKEN', 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('User not found', 'USER_NOT_FOUND', 404);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Clear reset tokens
        \Cache::forget('password_reset_' . $request->email);
        \Cache::forget('password_reset_token_' . $request->email);

        // Revoke all existing tokens (force re-login)
        $user->tokens()->delete();

        return $this->success(null, 'Password reset successfully. Please login with your new password.');
    }

    /**
     * Change password (authenticated user)
     * POST /api/v1/auth/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 'WRONG_PASSWORD', 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Optionally revoke other tokens (keep current session)
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Format user data for response
     */
    private function formatUser(User $user): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'user_type' => $user->user_type,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at->toIso8601String(),
        ];

        // Add location data
        if ($user->country_id || $user->region_id || $user->district_id) {
            $data['location'] = [
                'country' => $user->country ? [
                    'id' => $user->country->id,
                    'name' => $user->country->name,
                    'flag' => $user->country->flag,
                ] : null,
                'region' => $user->region ? [
                    'id' => $user->region->id,
                    'name' => $user->region->name,
                ] : null,
                'district' => $user->district ? [
                    'id' => $user->district->id,
                    'name' => $user->district->name,
                ] : null,
                'address' => $user->address,
            ];
        }

        if ($user->isIndividual()) {
            $data['is_free'] = true;
        }

        return $data;
    }
}
