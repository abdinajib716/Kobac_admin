<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionWrite
{
    /**
     * Handle an incoming request.
     * Checks if user's subscription allows write operations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Individual users always have write access (no subscription required)
        if ($user->user_type === 'individual') {
            return $next($request);
        }

        // Business users need active subscription for write operations
        if ($user->user_type === 'business') {
            $subscription = $user->subscription;

            // No subscription at all
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription. Please subscribe to a plan.',
                    'error_code' => 'NO_SUBSCRIPTION',
                ], 403);
            }

            // Check if subscription is active or in trial
            if ($subscription->status === 'active' || $subscription->status === 'trial') {
                return $next($request);
            }

            // Subscription expired or cancelled
            return response()->json([
                'success' => false,
                'message' => 'Your subscription has expired. Please renew to continue.',
                'error_code' => 'SUBSCRIPTION_EXPIRED',
                'subscription_status' => $subscription->status,
            ], 403);
        }

        return $next($request);
    }
}