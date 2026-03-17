<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $perPage = max(1, min((int) $request->get('per_page', 20), 50));
        $user = $this->user();

        $notifications = $this->visibleNotificationsQuery($user)
            ->leftJoin('push_notification_user_states as notification_state', function ($join) use ($user) {
                $join->on('push_notifications.id', '=', 'notification_state.push_notification_id')
                    ->where('notification_state.user_id', '=', $user->id);
            })
            ->whereNull('notification_state.deleted_at')
            ->select('push_notifications.*', 'notification_state.read_at as user_read_at')
            ->orderByDesc('push_notifications.sent_at')
            ->orderByDesc('push_notifications.id')
            ->paginate($perPage);

        $data = $notifications->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'image_url' => $n->image_url,
            'sent_at' => $n->sent_at?->toIso8601String(),
            'is_read' => !empty($n->user_read_at),
            'read_at' => $n->user_read_at ? Carbon::parse($n->user_read_at)->toIso8601String() : null,
        ])->values();

        return $this->paginated($notifications, $data, 'Notification history');
    }

    /**
     * Mark all visible notifications as read for the authenticated user
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->user();

        $notificationIds = $this->visibleNotificationsQuery($user)
            ->leftJoin('push_notification_user_states as notification_state', function ($join) use ($user) {
                $join->on('push_notifications.id', '=', 'notification_state.push_notification_id')
                    ->where('notification_state.user_id', '=', $user->id);
            })
            ->whereNull('notification_state.deleted_at')
            ->whereNull('notification_state.read_at')
            ->pluck('push_notifications.id')
            ->all();

        if (empty($notificationIds)) {
            return $this->success([
                'read_count' => 0,
            ], 'All notifications are already read');
        }

        $now = now();
        $rows = array_map(function ($notificationId) use ($user, $now) {
            return [
                'push_notification_id' => $notificationId,
                'user_id' => $user->id,
                'read_at' => $now,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $notificationIds);

        DB::table('push_notification_user_states')->upsert(
            $rows,
            ['push_notification_id', 'user_id'],
            ['read_at', 'deleted_at', 'updated_at']
        );

        return $this->success([
            'read_count' => count($notificationIds),
        ], 'All notifications marked as read');
    }

    /**
     * Delete a single visible notification for the authenticated user
     * DELETE /api/v1/notifications/{notification}
     */
    public function destroy(int $notification): JsonResponse
    {
        $user = $this->user();

        $canAccess = $this->visibleNotificationsQuery($user)
            ->where('push_notifications.id', $notification)
            ->exists();

        if (!$canAccess) {
            return $this->error('Notification not found', 'NOTIFICATION_NOT_FOUND', 404);
        }

        $now = now();
        DB::table('push_notification_user_states')->upsert(
            [[
                'push_notification_id' => $notification,
                'user_id' => $user->id,
                'deleted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['push_notification_id', 'user_id'],
            ['deleted_at', 'updated_at']
        );

        return $this->success([
            'deleted' => true,
            'notification_id' => $notification,
        ], 'Notification deleted');
    }

    /**
     * Delete all visible notifications for the authenticated user
     * DELETE /api/v1/notifications/delete-all
     */
    public function deleteAll(): JsonResponse
    {
        $user = $this->user();

        $notificationIds = $this->visibleNotificationsQuery($user)
            ->leftJoin('push_notification_user_states as notification_state', function ($join) use ($user) {
                $join->on('push_notifications.id', '=', 'notification_state.push_notification_id')
                    ->where('notification_state.user_id', '=', $user->id);
            })
            ->whereNull('notification_state.deleted_at')
            ->pluck('push_notifications.id')
            ->all();

        if (empty($notificationIds)) {
            return $this->success([
                'deleted_count' => 0,
            ], 'No notifications to delete');
        }

        $now = now();
        $rows = array_map(function ($notificationId) use ($user, $now) {
            return [
                'push_notification_id' => $notificationId,
                'user_id' => $user->id,
                'deleted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $notificationIds);

        DB::table('push_notification_user_states')->upsert(
            $rows,
            ['push_notification_id', 'user_id'],
            ['deleted_at', 'updated_at']
        );

        return $this->success([
            'deleted_count' => count($notificationIds),
        ], 'All notifications deleted');
    }

    /**
     * Base query for notifications visible to the authenticated user.
     */
    private function visibleNotificationsQuery(User $user): Builder
    {
        return PushNotification::query()
            ->where(function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
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
                });
            });
    }
}
