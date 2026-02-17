<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Services\SubscriptionPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends BaseController
{
    protected SubscriptionPaymentService $paymentService;

    public function __construct(SubscriptionPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get unified subscription/access status for ALL user types
     * GET /api/v1/subscription/status
     * 
     * Works for both Individual (FREE) and Business users
     * Drives: Profile screen, Upgrade banner, Lock overlays
     */
    public function status(Request $request): JsonResponse
    {
        $user = $this->user();

        // Individual users - FREE
        if ($user->isIndividual()) {
            return $this->success([
                'user_type' => 'individual',
                'is_free' => true,
                'plan' => 'Free',
                'status' => 'active',
                'status_label' => 'FREE - Full Access',
                'can_read' => true,
                'can_write' => true,
                'write_blocked' => false,
                'block_reason' => null,
                'block_action' => null,
                'trial_days_left' => null,
                'is_paid' => false,
                'upgrade_available' => false,
            ]);
        }

        // Business users - subscription based
        if ($user->isBusiness()) {
            $subscription = $user->subscription;
            $plan = $subscription?->plan;
            $canWrite = $user->canWrite();

            $blockReason = null;
            $blockAction = null;

            if (!$canWrite && $subscription) {
                if ($subscription->isTrialExpired()) {
                    $blockReason = 'trial_expired';
                    $blockAction = 'upgrade_required';
                } elseif ($subscription->isExpired()) {
                    $blockReason = 'subscription_expired';
                    $blockAction = 'renew_required';
                }
            } elseif (!$subscription) {
                $blockReason = 'no_subscription';
                $blockAction = 'subscribe_required';
            }

            return $this->success([
                'user_type' => 'business',
                'is_free' => false,
                'plan' => $plan?->name ?? 'No Plan',
                'plan_id' => $plan?->id,
                'status' => $subscription?->status ?? 'none',
                'status_label' => $subscription?->status_label ?? 'No Subscription',
                'can_read' => $subscription?->canRead() ?? false,
                'can_write' => $canWrite,
                'write_blocked' => !$canWrite,
                'block_reason' => $blockReason,
                'block_action' => $blockAction,
                'trial_days_left' => $subscription?->isOnTrial() ? $subscription->days_remaining : null,
                'days_remaining' => $subscription?->days_remaining ?? 0,
                'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
        
                'ends_at' => $subscription?->ends_at?->toIso8601String(),
                'is_paid' => $subscription?->status === 'active',
                'upgrade_available' => true,
            ]);
        }

        return $this->error('Invalid user type', 'INVALID_USER_TYPE', 400);
    }
  /**
     * Get current subscription (Business only - detailed)
     * GET /api/v1/subscription
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->user();

        if (!$user->isBusiness()) {
            return $this->error(
                'Individual users are FREE and do not have subscriptions',
                'NOT_APPLICABLE',
                400
            );
        }

        $subscription = $user->subscription;

        if (!$subscription) {
            return $this->error('No subscription found', 'NO_SUBSCRIPTION', 404);
        }

        return $this->success([
            'id' => $subscription->id,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'price' => (float) $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'billing_cycle' => $subscription->plan->billing_cycle,
            ],
            'status' => $subscription->status,
            'status_label' => $subscription->status_label,
            'can_read' => $subscription->canRead(),
            'can_write' => $subscription->canWrite(),
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'days_remaining' => $subscription->days_remaining,
        ]);
    }

    /**
     * Get upgrade options
     * GET /api/v1/subscription/upgrade-options
     */
    public function upgradeOptions(Request $request): JsonResponse
    {
        $user = $this->user();

        if (!$user->isBusiness()) {
            return $this->error(
                'Individual users are FREE and do not need upgrades',
                'NOT_APPLICABLE',
                400
            );
        }

        $subscription = $user->subscription;
        $currentPlan = $subscription?->plan;

        $upgradePlans = Plan::active()
            ->when($currentPlan, function ($query) use ($currentPlan) {
                return $query->where('price', '>', $currentPlan->price);
            })
            ->orderBy('price')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'billing_cycle' => $plan->billing_cycle,
                    'features' => $plan->features,
                ];
            });

        return $this->success([
            'current_plan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'status' => $subscription->status,
                'days_remaining' => $subscription->days_remaining,
            ] : null,
            'upgrade_options' => $upgradePlans,
        ]);
    }

    /**
     * Subscribe to a plan
     * POST /api/v1/subscription/subscribe
     * 
     * Supports both online (WaafiPay) and offline payment methods.
     * The system automatically detects available payment methods.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'payment_type' => 'required|in:online,offline',
            'phone_number' => 'required_if:payment_type,online|nullable|string',
            'wallet_type' => 'nullable|in:evc_plus,zaad,jeeb,sahal',
            'proof_of_payment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();

        // Check if payment methods are available
        if (!$this->paymentService->hasPaymentMethodAvailable()) {
            return $this->error(
                'No payment methods are currently available. Please contact support.',
                'NO_PAYMENT_METHODS',
                503
            );
        }

        $result = $this->paymentService->processPayment([
            'user' => $user,
            'plan_id' => $request->plan_id,
            'payment_type' => $request->payment_type,
            'phone_number' => $request->phone_number,
            'wallet_type' => $request->wallet_type,
            'proof_of_payment' => $request->proof_of_payment,
            'channel' => 'MOBILE',
        ]);

        if ($result['success']) {
            return $this->success($result);
        }

        return $this->error($result['message'], $result['error_code'] ?? 'PAYMENT_FAILED', 400);
    }

    /**
     * Renew current subscription
     * POST /api/v1/subscription/renew
     */
    public function renew(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => 'required|in:online,offline',
            'phone_number' => 'required_if:payment_type,online|nullable|string',
            'wallet_type' => 'nullable|in:evc_plus,zaad,jeeb,sahal',
            'proof_of_payment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $subscription = $user->subscription;

        if (!$subscription) {
            return $this->error('No subscription found to renew', 'NO_SUBSCRIPTION', 404);
        }

        $result = $this->paymentService->renewSubscription($user, [
            'payment_type' => $request->payment_type,
            'phone_number' => $request->phone_number,
            'wallet_type' => $request->wallet_type,
            'proof_of_payment' => $request->proof_of_payment,
            'channel' => 'MOBILE',
        ]);

        if ($result['success']) {
            return $this->success($result);
        }

        return $this->error($result['message'], $result['error_code'] ?? 'RENEWAL_FAILED', 400);
    }

    /**
     * Get available payment methods for subscription
     * GET /api/v1/subscription/payment-methods
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = $this->paymentService->getAvailablePaymentMethods();

        if (empty($methods)) {
            return $this->error(
                'No payment methods available',
                'NO_PAYMENT_METHODS',
                503
            );
        }

        return $this->success([
            'payment_methods' => $methods,
            'preferred_method' => $this->paymentService->getActivePaymentMethod(),
        ]);
    }
}
