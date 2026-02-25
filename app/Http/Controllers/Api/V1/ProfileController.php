<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProfileController extends BaseController
{
    /**
     * Update user profile
     * PUT /api/v1/profile
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $data = $validator->validated();

        if (isset($data['name'])) {
            $nameParts = explode(' ', $data['name']);
            $data['first_name'] = $nameParts[0] ?? $data['name'];
            $data['last_name'] = $nameParts[1] ?? '';
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'preferred_locale' => $user->preferred_locale ?? config('app.locale', 'en'),
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ], 'Profile updated successfully');
    }

    /**
     * Get mobile app preferences.
     * GET /api/v1/profile/preferences
     */
    public function preferences(): JsonResponse
    {
        $user = $this->user();
        $supported = config('localization.supported_locales', []);
        $locale = $user->preferred_locale ?? config('app.locale', 'en');

        return $this->success([
            'locale' => $locale,
            'fallback_locale' => config('app.fallback_locale', 'en'),
            'available_locales' => collect($supported)->map(function (array $meta, string $code) {
                return [
                    'code' => $code,
                    'name' => $meta['name'] ?? strtoupper($code),
                    'native_name' => $meta['native_name'] ?? strtoupper($code),
                    'rtl' => (bool) ($meta['rtl'] ?? false),
                ];
            })->values(),
        ]);
    }

    /**
     * Update mobile app preferences.
     * PUT /api/v1/profile/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $locales = array_keys(config('localization.supported_locales', ['en' => []]));

        $validator = Validator::make($request->all(), [
            'locale' => ['required', 'string', Rule::in($locales)],
        ]);

        if ($validator->fails()) {
            return $this->error(__('mobile.errors.validation_failed'), 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $locale = $validator->validated()['locale'];

        $user->update([
            'preferred_locale' => $locale,
        ]);

        app()->setLocale($locale);

        return $this->success([
            'locale' => $locale,
            'message_key' => 'mobile.messages.language_changed',
        ], __('mobile.messages.preferences_updated'));
    }
}
