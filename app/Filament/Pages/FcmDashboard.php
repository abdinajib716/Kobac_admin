<?php

namespace App\Filament\Pages;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\Setting;
use App\Models\User;
use Filament\Pages\Page;

class FcmDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Notifications';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'FCM Dashboard';

    protected static ?string $title = 'Push Notification Dashboard';

    protected static string $view = 'filament.pages.fcm-dashboard';

    public function getStats(): array
    {
        $totalNotifications = PushNotification::count();
        $sentNotifications = PushNotification::whereIn('status', ['sent', 'partial'])->count();
        $failedNotifications = PushNotification::where('status', 'failed')->count();
        $totalDelivered = PushNotification::sum('success_count');
        $totalFailed = PushNotification::sum('failure_count');
        $totalRecipients = PushNotification::sum('total_recipients');

        $deliveryRate = $totalRecipients > 0
            ? round(($totalDelivered / $totalRecipients) * 100, 1)
            : 0;

        return [
            'total_notifications' => $totalNotifications,
            'sent_notifications' => $sentNotifications,
            'failed_notifications' => $failedNotifications,
            'total_delivered' => $totalDelivered,
            'total_failed_deliveries' => $totalFailed,
            'delivery_rate' => $deliveryRate,
        ];
    }

    public function getDeviceStats(): array
    {
        $totalTokens = DeviceToken::where('is_active', true)->count();
        $androidTokens = DeviceToken::where('is_active', true)->where('platform', 'android')->count();
        $iosTokens = DeviceToken::where('is_active', true)->where('platform', 'ios')->count();
        $webTokens = DeviceToken::where('is_active', true)->where('platform', 'web')->count();
        $uniqueUsers = DeviceToken::where('is_active', true)->distinct('user_id')->count('user_id');
        $inactiveTokens = DeviceToken::where('is_active', false)->count();

        return [
            'total_tokens' => $totalTokens,
            'android_tokens' => $androidTokens,
            'ios_tokens' => $iosTokens,
            'web_tokens' => $webTokens,
            'unique_users' => $uniqueUsers,
            'inactive_tokens' => $inactiveTokens,
        ];
    }

    public function getUserCoverage(): array
    {
        $totalMobileUsers = User::active()->mobileUsers()->count();
        $usersWithTokens = DeviceToken::where('is_active', true)
            ->distinct('user_id')
            ->count('user_id');

        $coverage = $totalMobileUsers > 0
            ? round(($usersWithTokens / $totalMobileUsers) * 100, 1)
            : 0;

        $individualWithTokens = DeviceToken::where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('user_type', 'individual')->where('is_active', true))
            ->distinct('user_id')
            ->count('user_id');

        $businessWithTokens = DeviceToken::where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('user_type', 'business')->where('is_active', true))
            ->distinct('user_id')
            ->count('user_id');

        return [
            'total_mobile_users' => $totalMobileUsers,
            'users_with_tokens' => $usersWithTokens,
            'coverage_percent' => $coverage,
            'individual_with_tokens' => $individualWithTokens,
            'business_with_tokens' => $businessWithTokens,
        ];
    }

    public function getRecentNotifications(): \Illuminate\Database\Eloquent\Collection
    {
        return PushNotification::with('sender')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function getWeeklyChart(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');
            $data[] = PushNotification::whereDate('created_at', $date)
                ->whereIn('status', ['sent', 'partial'])
                ->sum('success_count');
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getFirebaseStatus(): array
    {
        return [
            'enabled' => (bool) Setting::get('firebase_enabled', false),
            'project_id' => Setting::get('firebase_project_id'),
            'has_credentials' => !empty(Setting::get('firebase_client_email')) && !empty(Setting::get('firebase_private_key')),
            'default_topic' => Setting::get('firebase_default_topic', 'kobac_all'),
        ];
    }
}
