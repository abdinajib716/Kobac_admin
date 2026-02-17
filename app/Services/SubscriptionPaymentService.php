<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Subscription Payment Service
 * 
 * Handles payment workflow for both online (WaafiPay) and offline payments.
 * This service provides a unified interface for:
 * - Detecting available payment methods
 * - Processing subscription payments
 * - Activating subscriptions after successful payment
 * - Managing payment transactions
 */
class SubscriptionPaymentService
{
    protected WaafiPayService $waafiPay;
    protected OfflinePaymentService $offlinePayment;

    public function __construct(WaafiPayService $waafiPay, OfflinePaymentService $offlinePayment)
    {
        $this->waafiPay = $waafiPay;
        $this->offlinePayment = $offlinePayment;
    }

    /**
     * Get all available payment methods for subscriptions.
     */
    public function getAvailablePaymentMethods(): array
    {
        return $this->offlinePayment->getAvailablePaymentMethods();
    }

    /**
     * Get the active/preferred payment method.
     * Returns the first available method, prioritizing online payments.
     */
    public function getActivePaymentMethod(): ?array
    {
        $methods = $this->getAvailablePaymentMethods();
        
        if (empty($methods)) {
            return null;
        }

        // Prioritize online payment if available
        foreach ($methods as $method) {
            if ($method['type'] === 'online') {
                return $method;
            }
        }

        return $methods[0] ?? null;
    }

    /**
     * Check if any payment method is available.
     */
    public function hasPaymentMethodAvailable(): bool
    {
        return !empty($this->getAvailablePaymentMethods());
    }

    /**
     * Check if online payment is available.
     */
    public function isOnlinePaymentAvailable(): bool
    {
        return $this->waafiPay->isConfigured();
    }

    /**
     * Check if offline payment is available.
     */
    public function isOfflinePaymentAvailable(): bool
    {
        return $this->offlinePayment->isEnabled();
    }

    /**
     * Process a subscription payment.
     * 
     * This method automatically routes to the appropriate payment handler
     * based on the payment_type parameter.
     */
    public function processPayment(array $params): array
    {
        $paymentType = $params['payment_type'] ?? 'online';
        $user = $params['user'] ?? auth()->user();
        $planId = $params['plan_id'];

        if (!$user->isBusiness()) {
            return [
                'success' => false,
                'message' => 'Only business users can subscribe to paid plans.',
            ];
        }

        $plan = Plan::find($planId);
        if (!$plan) {
            return [
                'success' => false,
                'message' => 'Invalid plan selected.',
            ];
        }

        // Validate minimum price
        if ($plan->price < 0.01) {
            return [
                'success' => false,
                'message' => 'Plan price is invalid.',
            ];
        }

        if ($paymentType === 'offline') {
            return $this->processOfflinePayment($user, $plan, $params);
        }

        return $this->processOnlinePayment($user, $plan, $params);
    }

    /**
     * Process online payment via WaafiPay.
     */
    protected function processOnlinePayment(User $user, Plan $plan, array $params): array
    {
        if (!$this->waafiPay->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Online payment is not available.',
            ];
        }

        $phoneNumber = $params['phone_number'] ?? $user->phone;
        if (!$phoneNumber) {
            return [
                'success' => false,
                'message' => 'Phone number is required for mobile wallet payment.',
            ];
        }

        // Create or update subscription with pending status
        $subscription = $this->getOrCreateSubscription($user, $plan, 'online');

        $result = $this->waafiPay->purchase([
            'customer_id' => $user->id,
            'phone_number' => $phoneNumber,
            'amount' => $plan->price,
            'currency' => $plan->currency ?? 'USD',
            'description' => "Subscription payment for {$plan->name} plan",
            'wallet_type' => $params['wallet_type'] ?? null,
            'channel' => $params['channel'] ?? 'MOBILE',
        ]);

        if ($result['success'] && $result['status'] === 'success') {
            // Payment completed immediately - activate subscription
            $this->activateSubscription($subscription, $plan);
            $result['subscription_activated'] = true;
            $result['subscription_id'] = $subscription->id;
        } elseif ($result['success'] && $result['status'] === 'processing') {
            // Payment pending customer approval
            $result['subscription_id'] = $subscription->id;
            $result['subscription_activated'] = false;
        } elseif (!$result['success']) {
            // Payment failed - send notification
            NotificationService::sendPaymentFailed($user, [
                'plan_name' => $plan->name,
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'USD',
                'payment_method' => 'WaafiPay (Mobile Wallet)',
                'reference_id' => $result['reference_id'] ?? 'N/A',
                'error_message' => $result['message'] ?? null,
            ]);
        }

        // Link transaction to subscription
        if (isset($result['transaction_id'])) {
            PaymentTransaction::where('id', $result['transaction_id'])->update([
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
            ]);
        }

        return $result;
    }

    /**
     * Process offline payment request.
     */
    protected function processOfflinePayment(User $user, Plan $plan, array $params): array
    {
        return $this->offlinePayment->initiatePayment([
            'user' => $user,
            'plan_id' => $plan->id,
            'proof_of_payment' => $params['proof_of_payment'] ?? null,
            'channel' => $params['channel'] ?? 'MOBILE',
        ]);
    }

    /**
     * Get or create a subscription for the user.
     */
    protected function getOrCreateSubscription(User $user, Plan $plan, string $paymentMethod): Subscription
    {
        $subscription = $user->subscription;

        if ($subscription) {
            $subscription->update([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING_PAYMENT,
                'payment_method' => $paymentMethod,
            ]);
        } else {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING_PAYMENT,
                'starts_at' => now(),
                'payment_method' => $paymentMethod,
            ]);
        }

        return $subscription;
    }

    /**
     * Activate a subscription after successful payment.
     */
    public function activateSubscription(Subscription $subscription, ?Plan $plan = null): Subscription
    {
        $plan = $plan ?? $subscription->plan;
        $endDate = $this->calculateEndDate($plan);

        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $endDate,
            'trial_ends_at' => null,
        ]);

        Log::info('Subscription Activated', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $plan->id,
            'ends_at' => $endDate->toIso8601String(),
        ]);

        // Send subscription activated email
        $user = $subscription->user;
        if ($user) {
            NotificationService::sendSubscriptionActivated($user, $subscription);
        }

        return $subscription;
    }

    /**
     * Calculate subscription end date from plan.
     */
    protected function calculateEndDate(Plan $plan): Carbon
    {
        // If custom billing days is set, use it
        if ($plan->billing_days && $plan->billing_days > 0) {
            return now()->addDays($plan->billing_days);
        }

        return match ($plan->billing_cycle) {
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            'lifetime' => now()->addYears(100),
            default => now()->addMonth(),
        };
    }

    /**
     * Handle successful payment callback/webhook.
     * Called when a payment is confirmed (e.g., WaafiPay webhook).
     */
    public function handlePaymentSuccess(PaymentTransaction $transaction): array
    {
        if ($transaction->status !== 'success') {
            return [
                'success' => false,
                'message' => 'Transaction is not successful.',
            ];
        }

        $subscription = $transaction->subscription;
        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'No subscription linked to this transaction.',
            ];
        }

        $plan = $transaction->plan ?? $subscription->plan;
        $this->activateSubscription($subscription, $plan);

        return [
            'success' => true,
            'message' => 'Subscription activated successfully.',
            'subscription' => $subscription,
        ];
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(User $user, array $params): array
    {
        $subscription = $user->subscription;
        
        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'No subscription found to renew.',
            ];
        }

        $params['plan_id'] = $params['plan_id'] ?? $subscription->plan_id;
        $params['user'] = $user;

        return $this->processPayment($params);
    }

    /**
     * Get payment status summary for a user.
     */
    public function getPaymentStatus(User $user): array
    {
        $pendingTransactions = PaymentTransaction::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'pending_approval'])
            ->orderBy('created_at', 'desc')
            ->get();

        $lastSuccessfulPayment = PaymentTransaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->orderBy('completed_at', 'desc')
            ->first();

        return [
            'has_pending_payment' => $pendingTransactions->isNotEmpty(),
            'pending_transactions' => $pendingTransactions->count(),
            'last_payment_date' => $lastSuccessfulPayment?->completed_at?->toIso8601String(),
            'last_payment_amount' => $lastSuccessfulPayment?->amount,
        ];
    }
}
