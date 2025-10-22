<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\WelcomeEmailNotification;
use App\Notifications\PasswordResetEmailNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a welcome notification to a user (database only)
     */
    public static function sendWelcome(User $user): void
    {
        $user->notify(new WelcomeNotification());
    }

    /**
     * Send a welcome email notification to a user (email + database)
     */
    public static function sendWelcomeEmail(User $user): void
    {
        $user->notify(new WelcomeEmailNotification());
    }

    /**
     * Send a password reset email
     */
    public static function sendPasswordResetEmail(User $user, string $token): void
    {
        $user->notify(new PasswordResetEmailNotification($token));
    }

    /**
     * Send a system alert to specific users
     */
    public static function sendAlert(
        User|Collection $users,
        string $title,
        string $message,
        string $type = 'warning'
    ): void {
        if ($users instanceof User) {
            $users = collect([$users]);
        }

        $users->each(function (User $user) use ($title, $message, $type) {
            $user->notify(new SystemAlertNotification($title, $message, $type));
        });
    }

    /**
     * Send a system alert to all users
     */
    public static function sendAlertToAll(
        string $title,
        string $message,
        string $type = 'warning'
    ): void {
        User::all()->each(function (User $user) use ($title, $message, $type) {
            $user->notify(new SystemAlertNotification($title, $message, $type));
        });
    }

    /**
     * Send a flash notification (visible immediately without database storage)
     */
    public static function sendFlash(
        string $title,
        string $body,
        string $type = 'info'
    ): Notification {
        $notification = Notification::make()
            ->title($title)
            ->body($body);

        return match ($type) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            'info' => $notification->info(),
            default => $notification->info(),
        };
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * Delete all notifications for a user
     */
    public static function deleteAll(User $user): void
    {
        $user->notifications()->delete();
    }
}
