<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflinePaymentService
{
    protected bool $isEnabled;
    protected ?string $instructions;

    public function __construct()
    {
        $this->isEnabled = (bool) Setting::get('offline_payment_enabled', false);
        $this->instructions = Setting::get('offline_payment_instructions');
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    /**
     * Get available payment methods based on configuration
     */
    public function getAvailablePaymentMethods(): array
    {
        $methods = [];

        // Check if WaafiPay is configured
        $waafiPayService = app(WaafiPayService::class);
        if ($waafiPayService->isConfigured()) {
            $methods[] = [
                'type' => 'online',
                'name' => 'Mobile Wallet (WaafiPay)',
                'description' => 'Pay instantly using EVC Plus, Zaad, Jeeb, or Sahal',
                'providers' => $waafiPayService->getPaymentMethods(),
                'is_instant' => true,
            ];
        }

        // Check if offline payment is enabled
        if ($this->isEnabled) {
            $methods[] = [
                'type' => 'offline',
                'name' => 'Offline Payment',
                'description' => 'Bank transfer, cash, or other manual payment methods',
                'instructions' => $this->instructions,
                'is_instant' => false,
                'requires_approval' => true,
            ];
        }

        return $methods;
    }

    /**
     * Initiate an offline payment request
     */
    public function initiatePayment(array $params): array
    {
        if (!$this->isEnabled) {
            return [
                'success' => false,
                'message' => 'Offline payment is not enabled. Please contact support.',
            ];
        }

        $user = $params['user'] ?? auth()->user();
        $plan = Plan::find($params['plan_id']);

        if (!$plan) {
            return [
                'success' => false,
                'message' => 'Invalid plan selected.',
            ];
        }

        if (!$user->isBusiness()) {
            return [
                'success' => false,
                'message' => 'Only business users can subscribe to paid plans.',
            ];
        }

        $referenceId = 'OFF-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));

        try {
            DB::beginTransaction();

            // Create or update subscription with pending_payment status
            $subscription = $user->subscription;
            
            if ($subscription) {
                // Update existing subscription
                $subscription->update([
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_PENDING_PAYMENT,
                    'payment_method' => 'offline',
                    'payment_reference' => $referenceId,
                ]);
            } else {
                // Create new subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_PENDING_PAYMENT,
                    'starts_at' => now(),
                    'payment_method' => 'offline',
                    'payment_reference' => $referenceId,
                ]);
            }

            // Create payment transaction
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'reference_id' => $referenceId,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'payment_method' => 'OFFLINE',
                'payment_type' => 'offline',
                'phone_number' => $user->phone ?? 'N/A',
                'customer_name' => $user->name,
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'USD',
                'description' => "Subscription payment for {$plan->name} plan",
                'status' => 'pending_approval',
                'channel' => $params['channel'] ?? 'MOBILE',
                'environment' => 'LIVE',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'initiated_at' => now(),
                'proof_of_payment' => $params['proof_of_payment'] ?? null,
            ]);

            DB::commit();

            Log::info('Offline Payment Initiated', [
                'reference_id' => $referenceId,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
            ]);

            // Send email notification to user
            NotificationService::sendOfflinePaymentSubmitted($user, [
                'plan_name' => $plan->name,
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'USD',
                'reference_id' => $referenceId,
                'submitted_at' => now()->format('M d, Y h:i A'),
                'instructions' => $this->instructions,
            ]);

            return [
                'success' => true,
                'status' => 'pending_approval',
                'message' => 'Payment request submitted successfully. Waiting for admin approval.',
                'transaction_id' => $transaction->id,
                'reference_id' => $referenceId,
                'subscription_id' => $subscription->id,
                'instructions' => $this->instructions,
                'data' => [
                    'plan' => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'price' => (float) $plan->price,
                        'currency' => $plan->currency,
                    ],
                    'amount' => (float) $plan->price,
                    'currency' => $plan->currency ?? 'USD',
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Offline Payment Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
                'plan_id' => $params['plan_id'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payment request. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Approve an offline payment (Admin action)
     */
    public function approvePayment(PaymentTransaction $transaction, ?User $approvedBy = null, ?string $notes = null): array
    {
        if ($transaction->payment_type !== 'offline') {
            return [
                'success' => false,
                'message' => 'This transaction is not an offline payment.',
            ];
        }

        if ($transaction->status !== 'pending_approval') {
            return [
                'success' => false,
                'message' => 'This payment has already been processed.',
            ];
        }

        try {
            DB::beginTransaction();

            // Update transaction
            $transaction->update([
                'status' => 'approved',
                'approved_by' => $approvedBy?->id,
                'approved_at' => now(),
                'admin_notes' => $notes,
                'completed_at' => now(),
            ]);

            // Activate subscription
            $subscription = $transaction->subscription;
            $plan = $transaction->plan;

            if ($subscription && $plan) {
                $endDate = $this->calculateEndDate($plan->billing_cycle ?? 'monthly', $plan->billing_days);

                $subscription->update([
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => $endDate,
                    'trial_ends_at' => null,
                ]);
            }

            DB::commit();

            Log::info('Offline Payment Approved', [
                'reference_id' => $transaction->reference_id,
                'approved_by' => $approvedBy?->id,
                'subscription_id' => $subscription?->id,
            ]);

            // Send approval + subscription activated emails
            $user = $transaction->user;
            if ($user) {
                NotificationService::sendOfflinePaymentApproved($user, [
                    'plan_name' => $plan?->name ?? 'Business',
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency ?? 'USD',
                    'reference_id' => $transaction->reference_id,
                    'approved_at' => now()->format('M d, Y h:i A'),
                ]);
                NotificationService::sendSubscriptionActivated($user, $subscription);
            }

            return [
                'success' => true,
                'message' => 'Payment approved successfully. Subscription activated.',
                'subscription' => $subscription,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Offline Payment Approval Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to approve payment.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reject an offline payment (Admin action)
     */
    public function rejectPayment(PaymentTransaction $transaction, string $reason, ?User $rejectedBy = null): array
    {
        if ($transaction->payment_type !== 'offline') {
            return [
                'success' => false,
                'message' => 'This transaction is not an offline payment.',
            ];
        }

        if ($transaction->status !== 'pending_approval') {
            return [
                'success' => false,
                'message' => 'This payment has already been processed.',
            ];
        }

        try {
            DB::beginTransaction();

            // Update transaction
            $transaction->update([
                'status' => 'rejected',
                'approved_by' => $rejectedBy?->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
                'failed_at' => now(),
            ]);

            // Revert subscription to trial or expired
            $subscription = $transaction->subscription;
            if ($subscription && $subscription->status === Subscription::STATUS_PENDING_PAYMENT) {
                // If user had a trial before, check if it's still valid
                if ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture()) {
                    $subscription->update(['status' => Subscription::STATUS_TRIAL]);
                } else {
                    $subscription->update(['status' => Subscription::STATUS_EXPIRED]);
                }
            }

            DB::commit();

            Log::info('Offline Payment Rejected', [
                'reference_id' => $transaction->reference_id,
                'rejected_by' => $rejectedBy?->id,
                'reason' => $reason,
            ]);

            // Send rejection email
            $user = $transaction->user;
            $plan = $transaction->plan;
            if ($user) {
                NotificationService::sendOfflinePaymentRejected($user, [
                    'plan_name' => $plan?->name ?? 'Business',
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency ?? 'USD',
                    'reference_id' => $transaction->reference_id,
                    'reason' => $reason,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Payment rejected.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Offline Payment Rejection Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reject payment.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get pending offline payments count
     */
    public function getPendingCount(): int
    {
        return PaymentTransaction::where('payment_type', 'offline')
            ->where('status', 'pending_approval')
            ->count();
    }

    /**
     * Check payment status
     */
    public function checkStatus(string $referenceId): array
    {
        $transaction = PaymentTransaction::where('reference_id', $referenceId)->first();

        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found.',
            ];
        }

        $statusMessages = [
            'pending_approval' => 'Your payment is pending admin approval.',
            'approved' => 'Your payment has been approved. Subscription is active.',
            'rejected' => 'Your payment was rejected. Reason: ' . ($transaction->rejection_reason ?? 'Not specified'),
            'pending' => 'Payment is being processed.',
            'failed' => 'Payment failed.',
        ];

        return [
            'success' => true,
            'status' => $transaction->status,
            'message' => $statusMessages[$transaction->status] ?? 'Unknown status',
            'transaction' => [
                'id' => $transaction->id,
                'reference_id' => $transaction->reference_id,
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'payment_type' => $transaction->payment_type,
                'created_at' => $transaction->created_at->toIso8601String(),
                'approved_at' => $transaction->approved_at?->toIso8601String(),
                'rejection_reason' => $transaction->rejection_reason,
            ],
            'subscription' => $transaction->subscription ? [
                'id' => $transaction->subscription->id,
                'status' => $transaction->subscription->status,
                'plan_name' => $transaction->subscription->plan->name ?? 'Unknown',
            ] : null,
        ];
    }

    /**
     * Calculate subscription end date based on billing cycle or custom billing days.
     */
    protected function calculateEndDate(string $billingCycle, ?int $billingDays = null): Carbon
    {
        // If custom billing days is set, use it
        if ($billingDays && $billingDays > 0) {
            return now()->addDays($billingDays);
        }

        return match ($billingCycle) {
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly', 'annual' => now()->addYear(),
            'lifetime' => now()->addYears(100),
            default => now()->addMonth(),
        };
    }

    /**
     * Calculate subscription end date from a Plan model.
     */
    public function calculateEndDateFromPlan(Plan $plan): Carbon
    {
        return $this->calculateEndDate($plan->billing_cycle, $plan->billing_days);
    }
}
