<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /**
     * Register a device token for push notifications
     * POST /api/v1/notifications/register-token
     */
    public function registerToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string|max:500',
            'platform' => 'required|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);

        $user = $this->user();

        // Upsert: update existing or create new
        $token = DeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_token' => $request->device_token,
            ],
            [
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'device_id' => $request->device_id,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        // Deactivate this token for other users (token can only belong to one user)
        DeviceToken::where('device_token', $request->device_token)
            ->where('user_id', '!=', $user->id)
            ->update(['is_active' => false]);

        return $this->success([
            'token_id' => $token->id,
            'registered' => true,
        ], 'Device token registered successfully');
    }

    /**
     * Unregister a device token
     * POST /api/v1/notifications/unregister-token
     */
    public function unregisterToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $deleted = DeviceToken::where('user_id', $this->user()->id)
            ->where('device_token', $request->device_token)
            ->update(['is_active' => false]);

        return $this->success([
            'unregistered' => $deleted > 0,
        ], $deleted > 0 ? 'Device token unregistered' : 'Token not found');
    }

    /**
     * Get push notification history for the authenticated user
     * GET /api/v1/notifications/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $user = $this->user();

        $notifications = PushNotification::where(function ($query) use ($user) {
                // Notifications sent to this specific user
                $query->where('target_user_id', $user->id);
            })
            ->orWhere(function ($query) use ($user) {
                // Notifications sent to all users
                $query->where('audience', 'all')
                    ->whereIn('status', ['sent', 'partial']);
            })
            ->orWhere(function ($query) use ($user) {
                // Notifications sent to user's type
                $query->where('audience', $user->user_type)
                    ->whereIn('status', ['sent', 'partial']);
            })
            ->orderByDesc('sent_at')
            ->paginate($perPage);

        $data = $notifications->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'image_url' => $n->image_url,
            'sent_at' => $n->sent_at?->toIso8601String(),
        ]);

        return $this->paginated($notifications, $data, 'Notification history');
    }
}
