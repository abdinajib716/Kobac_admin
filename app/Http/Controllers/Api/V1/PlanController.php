<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends BaseController
{
    /**
     * List available plans for Business signup
     * GET /api/v1/plans
     * 
     * NOTE: Individual users are FREE - no plans needed
     * This endpoint is for Business signup ONLY
     * 
     * Filters:
     * - Active plans only
     * - Ordered by sort_order
     * - Default plan highlighted
     */
    public function index(): JsonResponse
    {
        $plans = Plan::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'billing_cycle' => $plan->billing_cycle,
                    'trial_enabled' => $plan->trial_enabled,
                    'trial_days' => $plan->trial_days,
                    'features' => $plan->normalizedFeatures(),
                    'is_default' => $plan->is_default,
                    'is_recommended' => $plan->is_default,
                ];
            });

        $defaultPlan = $plans->firstWhere('is_default', true);

        return $this->success([
            'plans' => $plans,
            'default_plan_id' => $defaultPlan['id'] ?? null,
            'note' => 'Individual accounts are FREE and do not require a plan.',
        ]);
    }
}
