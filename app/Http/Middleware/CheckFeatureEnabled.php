<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureEnabled
{
    /**
     * Handle an incoming request.
     * Checks if feature is enabled for the user's plan.
     * Individual users: blocked from business-only features
     * Business users: checked against plan features
     * 
     * Usage: middleware('feature.enabled:customers')
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Define business-only features
        $businessOnlyFeatures = [
            'customers',
            'vendors',
            'stock',
            'profit_loss',
            'branches',
        ];

        // Individual users - block business-only features
        if ($user->isIndividual()) {
            if (in_array($feature, $businessOnlyFeatures)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature is not available for Individual accounts.',
                    'error_code' => 'FEATURE_NOT_AVAILABLE',
                    'feature' => $feature,
                    'user_type' => 'individual',
                    'upgrade_available' => false,
                ], 403);
            }
            
            // Individual users have all core features
            return $next($request);
        }

        // Business users - check plan features
        if ($user->isBusiness()) {
            $subscription = $user->subscription;
            $plan = $subscription?->plan;
            $features = $plan?->features ?? [];

            // Check if feature is enabled in plan
            $featureEnabled = $features[$feature] ?? true;
            
            // Convert string 'true'/'false' to boolean
            if (is_string($featureEnabled)) {
                $featureEnabled = $featureEnabled === 'true';
            }

            if (!$featureEnabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature is not included in your current plan.',
                    'error_code' => 'FEATURE_NOT_IN_PLAN',
                    'feature' => $feature,
                    'plan' => $plan?->name ?? 'Unknown',
                    'upgrade_available' => true,
                ], 403);
            }

            return $next($request);
        }

        return $next($request);
    }
}
