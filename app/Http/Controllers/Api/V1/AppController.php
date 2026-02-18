<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppController extends BaseController
{
    /**
     * App metadata configuration
     */
    private const APP_CONFIG = [
        'dashboard' => [
            'name' => 'Dashboard',
            'icon' => 'home',
            'route' => '/dashboard',
            'business_only' => false,
        ],
        'accounts' => [
            'name' => 'Accounts',
            'icon' => 'wallet',
            'route' => '/accounts',
            'business_only' => false,
        ],
        'income' => [
            'name' => 'Income',
            'icon' => 'arrow-down-circle',
            'route' => '/income',
            'business_only' => false,
        ],
        'expense' => [
            'name' => 'Expenses',
            'icon' => 'arrow-up-circle',
            'route' => '/expenses',
            'business_only' => false,
        ],
        'activity' => [
            'name' => 'Activity',
            'icon' => 'clock',
            'route' => '/activity',
            'business_only' => false,
        ],
        'profile' => [
            'name' => 'Profile',
            'icon' => 'user',
            'route' => '/profile',
            'business_only' => false,
        ],
        'customers' => [
            'name' => 'Customers',
            'icon' => 'users',
            'route' => '/business/customers',
            'business_only' => true,
        ],
        'vendors' => [
            'name' => 'Vendors',
            'icon' => 'truck',
            'route' => '/business/vendors',
            'business_only' => true,
        ],
        'stock' => [
            'name' => 'Stock',
            'icon' => 'package',
            'route' => '/business/stock',
            'business_only' => true,
        ],
        'profit_loss' => [
            'name' => 'Profit & Loss',
            'icon' => 'trending-up',
            'route' => '/business/profit-loss',
            'business_only' => true,
        ],
        'branches' => [
            'name' => 'Branches',
            'icon' => 'git-branch',
            'route' => '/business/branches',
            'business_only' => true,
        ],
        'users' => [
            'name' => 'Users',
            'icon' => 'user-plus',
            'route' => '/business/users',
            'business_only' => true,
        ],
    ];

    /**
     * Get enabled apps/features for current user
     * GET /api/v1/apps
     * 
     * Returns which apps to show, which are locked, which are hidden by plan
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        // Individual users - FREE, get core apps only
        if ($user->isIndividual()) {
            $apps = [];
            foreach (self::APP_CONFIG as $key => $config) {
                $isBusinessOnly = $config['business_only'];
                $apps[] = [
                    'id' => $key,
                    'name' => $config['name'],
                    'icon' => $config['icon'],
                    'route' => $config['route'],
                    'enabled' => !$isBusinessOnly,
                    'locked' => $isBusinessOnly,
                    'hidden' => $isBusinessOnly,
                ];
            }

            return $this->success([
                'user_type' => 'individual',
                'is_free' => true,
                'apps' => $apps,
                'write_blocked' => false,
                'block_reason' => null,
                'block_action' => null,
            ]);
        }

        // Business users - check plan features and subscription status
        if ($user->isBusiness()) {
            $subscription = $user->subscription;
            $plan = $subscription?->plan;
            $features = $plan?->features ?? [];
            
            $canWrite = $user->canWrite();
            $blockReason = null;
            $blockAction = null;

            if (!$canWrite) {
                if ($subscription?->isTrialExpired()) {
                    $blockReason = 'trial_expired';
                    $blockAction = 'upgrade_required';
                } elseif ($subscription?->isExpired()) {
                    $blockReason = 'subscription_expired';
                    $blockAction = 'renew_required';
                } elseif ($subscription?->isPendingPayment()) {
                    $blockReason = 'pending_payment';
                    $blockAction = 'wait_approval';
                } else {
                    $blockReason = 'no_subscription';
                    $blockAction = 'subscribe_required';
                }
            }

            // Build apps list based on plan features
            $apps = [];
            foreach (self::APP_CONFIG as $key => $config) {
                $featureEnabled = $features[$key] ?? true;
                if (is_string($featureEnabled)) {
                    $featureEnabled = $featureEnabled === 'true';
                }
                
                $apps[] = [
                    'id' => $key,
                    'name' => $config['name'],
                    'icon' => $config['icon'],
                    'route' => $config['route'],
                    'enabled' => $featureEnabled,
                    'locked' => !$canWrite,
                    'hidden' => !$featureEnabled,
                ];
            }

            return $this->success([
                'user_type' => 'business',
                'is_free' => false,
                'plan_name' => $plan?->name ?? 'No Plan',
                'apps' => $apps,
                'write_blocked' => !$canWrite,
                'block_reason' => $blockReason,
                'block_action' => $blockAction,
            ]);
        }

        return $this->error('Invalid user type', 'INVALID_USER_TYPE', 400);
    }
}