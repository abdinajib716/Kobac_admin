<?php

namespace App\Services;

use App\Mail\AccountDeactivatedMail;
use App\Mail\OfflinePaymentApprovedMail;
use App\Mail\OfflinePaymentRejectedMail;
use App\Mail\OfflinePaymentSubmittedMail;
use App\Mail\PasswordResetMail;
use App\Mail\PaymentFailedMail;
use App\Mail\StaffInvitationMail;
use App\Mail\SubscriptionActivatedMail;
use App\Mail\SubscriptionExpiredMail;
use App\Mail\TrialExpiredMail;
use App\Mail\TrialExpiringMail;
use App\Models\Business;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\WelcomeEmailNotification;
use App\Notifications\PasswordResetEmailNotification;
use App\Services\FirebaseNotificationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    // ─── Email Methods (Centralized Template) ──────────────

    /**
     * Send password reset email using branded template
     */
    public static function sendPasswordReset(User $user, string $token): void
    {
        try {
            $resetUrl = url(route('filament.admin.auth.password-reset.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false));

            Mail::to($user->email)->send(new PasswordResetMail(
                user: $user,
                resetUrl: $resetUrl,
                expireMinutes: config('auth.passwords.users.expire', 60),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send account deactivation email
     */
    public static function sendAccountDeactivated(User $user, ?string $reason = null): void
    {
        try {
            Mail::to($user->email)->send(new AccountDeactivatedMail(
                user: $user,
                reason: $reason,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send deactivation email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send trial expiring warning email
     */
    public static function sendTrialExpiring(User $user): void
    {
        try {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isOnTrial()) return;

            $plan = $subscription->plan;
            Mail::to($user->email)->send(new TrialExpiringMail(
                user: $user,
                planName: $plan?->name ?? 'Business',
                trialEndsAt: $subscription->trial_ends_at->format('M d, Y'),
                daysRemaining: $subscription->days_remaining,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send trial expiring email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send trial expired email
     */
    public static function sendTrialExpired(User $user): void
    {
        try {
            $subscription = $user->subscription;
            $plan = $subscription?->plan;

            Mail::to($user->email)->send(new TrialExpiredMail(
                user: $user,
                planName: $plan?->name ?? 'Business',
                trialEndedAt: $subscription?->trial_ends_at?->format('M d, Y') ?? now()->format('M d, Y'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send trial expired email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send subscription activated email
     */
    public static function sendSubscriptionActivated(User $user, $subscription = null): void
    {
        try {
            $subscription = $subscription ?? $user->subscription;
            if (!$subscription) return;

            $plan = $subscription->plan;
            Mail::to($user->email)->send(new SubscriptionActivatedMail(
                user: $user,
                planName: $plan?->name ?? 'Business',
                startsAt: $subscription->starts_at?->format('M d, Y') ?? now()->format('M d, Y'),
                endsAt: $subscription->ends_at?->format('M d, Y') ?? 'N/A',
                paymentMethod: ucfirst($subscription->payment_method ?? 'N/A'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send subscription activated email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send subscription expired email
     */
    public static function sendSubscriptionExpired(User $user): void
    {
        try {
            $subscription = $user->subscription;
            $plan = $subscription?->plan;

            Mail::to($user->email)->send(new SubscriptionExpiredMail(
                user: $user,
                planName: $plan?->name ?? 'Business',
                expiredAt: $subscription?->ends_at?->format('M d, Y') ?? now()->format('M d, Y'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send subscription expired email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send payment failed email
     */
    public static function sendPaymentFailed(User $user, array $paymentData): void
    {
        try {
            Mail::to($user->email)->send(new PaymentFailedMail(
                user: $user,
                planName: $paymentData['plan_name'] ?? 'Unknown',
                amount: (float) ($paymentData['amount'] ?? 0),
                currency: $paymentData['currency'] ?? 'USD',
                paymentMethod: $paymentData['payment_method'] ?? 'Unknown',
                referenceId: $paymentData['reference_id'] ?? 'N/A',
                errorMessage: $paymentData['error_message'] ?? null,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send payment failed email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send offline payment submitted email
     */
    public static function sendOfflinePaymentSubmitted(User $user, array $paymentData): void
    {
        try {
            Mail::to($user->email)->send(new OfflinePaymentSubmittedMail(
                user: $user,
                planName: $paymentData['plan_name'] ?? 'Unknown',
                amount: (float) ($paymentData['amount'] ?? 0),
                currency: $paymentData['currency'] ?? 'USD',
                referenceId: $paymentData['reference_id'] ?? 'N/A',
                submittedAt: $paymentData['submitted_at'] ?? now()->format('M d, Y h:i A'),
                instructions: $paymentData['instructions'] ?? null,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send offline payment submitted email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send offline payment approved email
     */
    public static function sendOfflinePaymentApproved(User $user, array $paymentData): void
    {
        try {
            Mail::to($user->email)->send(new OfflinePaymentApprovedMail(
                user: $user,
                planName: $paymentData['plan_name'] ?? 'Unknown',
                amount: (float) ($paymentData['amount'] ?? 0),
                currency: $paymentData['currency'] ?? 'USD',
                referenceId: $paymentData['reference_id'] ?? 'N/A',
                approvedAt: $paymentData['approved_at'] ?? now()->format('M d, Y h:i A'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send offline payment approved email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send offline payment rejected email
     */
    public static function sendOfflinePaymentRejected(User $user, array $paymentData): void
    {
        try {
            Mail::to($user->email)->send(new OfflinePaymentRejectedMail(
                user: $user,
                planName: $paymentData['plan_name'] ?? 'Unknown',
                amount: (float) ($paymentData['amount'] ?? 0),
                currency: $paymentData['currency'] ?? 'USD',
                referenceId: $paymentData['reference_id'] ?? 'N/A',
                reason: $paymentData['reason'] ?? null,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send offline payment rejected email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send staff invitation email
     */
    public static function sendStaffInvitation(
        User $user,
        Business $business,
        string $role,
        ?string $branchName = null,
        ?string $temporaryPassword = null
    ): void {
        try {
            Mail::to($user->email)->send(new StaffInvitationMail(
                user: $user,
                business: $business,
                role: $role,
                branchName: $branchName,
                temporaryPassword: $temporaryPassword,
                loginUrl: config('app.url'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send staff invitation email', [
                'user_id' => $user->id,
                'business_id' => $business->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send password reset notification email
     */
    public static function sendPasswordResetNotification(
        User $user,
        string $newPassword,
        string $businessName
    ): void {
        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordResetNotificationMail(
                user: $user,
                newPassword: $newPassword,
                businessName: $businessName,
                loginUrl: config('app.url'),
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send password reset notification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send forgot password OTP email
     */
    public static function sendForgotPasswordOtp(User $user, string $code): void
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\ForgotPasswordOtpMail(
                user: $user,
                code: $code,
                expiresInMinutes: 15,
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send forgot password OTP email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ─── Push Notification Methods ───────────────────────────

    /**
     * Send push notification to a single user
     */
    public static function sendPushToUser(User $user, string $title, string $body, array $data = []): array
    {
        $firebase = app(FirebaseNotificationService::class);

        if (!$firebase->isEnabled()) {
            return ['success' => false, 'error' => 'Firebase is disabled'];
        }

        return $firebase->sendToUser($user, $title, $body, $data);
    }

    /**
     * Send push notification to a collection of users
     */
    public static function sendPushToUsers(Collection $users, string $title, string $body, array $data = []): array
    {
        $firebase = app(FirebaseNotificationService::class);

        if (!$firebase->isEnabled()) {
            return ['success' => false, 'error' => 'Firebase is disabled'];
        }

        return $firebase->sendToUsers($users, $title, $body, $data);
    }

    /**
     * Send push notification to a topic
     */
    public static function sendPushToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        $firebase = app(FirebaseNotificationService::class);

        if (!$firebase->isEnabled()) {
            return ['success' => false, 'error' => 'Firebase is disabled'];
        }

        return $firebase->sendToTopic($topic, $title, $body, $data);
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
