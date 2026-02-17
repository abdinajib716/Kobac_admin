<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';

    protected $description = 'Check for expiring trials and subscriptions and send email notifications';

    public function handle(): int
    {
        $this->info('Checking subscription expiry...');

        $this->checkExpiringTrials();
        $this->checkExpiredTrials();
        $this->checkExpiredSubscriptions();

        $this->info('Done.');
        return Command::SUCCESS;
    }

    /**
     * Send warning emails for trials expiring within 3 days
     */
    protected function checkExpiringTrials(): void
    {
        $expiring = Subscription::where('status', Subscription::STATUS_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(3)])
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expiring as $subscription) {
            if ($subscription->user && $subscription->user->is_active && $subscription->user->email) {
                NotificationService::sendTrialExpiring($subscription->user);
                $count++;
            }
        }

        $this->line("  Trial expiring warnings sent: {$count}");
    }

    /**
     * Mark expired trials and send notification
     */
    protected function checkExpiredTrials(): void
    {
        $expired = Subscription::where('status', Subscription::STATUS_TRIAL)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

            if ($subscription->user && $subscription->user->is_active && $subscription->user->email) {
                NotificationService::sendTrialExpired($subscription->user);
                $count++;
            }
        }

        $this->line("  Expired trials processed: {$count}");
    }

    /**
     * Mark expired subscriptions and send notification
     */
    protected function checkExpiredSubscriptions(): void
    {
        $expired = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

            if ($subscription->user && $subscription->user->is_active && $subscription->user->email) {
                NotificationService::sendSubscriptionExpired($subscription->user);
                $count++;
            }
        }

        $this->line("  Expired subscriptions processed: {$count}");
    }
}
