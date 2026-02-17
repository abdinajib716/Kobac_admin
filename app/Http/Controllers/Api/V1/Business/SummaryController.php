<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SummaryController extends BaseController
{
    /**
     * Get receivables summary
     * GET /api/v1/business/receivables/summary
     */
    public function receivables(Request $request): JsonResponse
    {
        $business = $this->business();
        $branchId = $request->get('branch_id');

        $customers = Customer::forBusiness($business->id, $branchId)
            ->active()
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        $totalReceivable = $customers->sum('balance');
        $customersCount = $customers->count();

        // Top 10 customers by balance
        $topCustomers = $customers->take(10)->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'balance' => (float) $c->balance,
            'branch_name' => $c->branch?->name,
        ]);

        // Aging analysis (simplified)
        $aging = [
            'current' => 0,
            'overdue_30' => 0,
            'overdue_60' => 0,
            'overdue_90' => 0,
        ];

        return $this->success([
            'total_receivable' => (float) $totalReceivable,
            'customers_count' => $customersCount,
            'average_per_customer' => $customersCount > 0 ? round($totalReceivable / $customersCount, 2) : 0,
            'top_customers' => $topCustomers,
            'aging' => $aging,
            'currency' => $business->currency ?? 'USD',
        ]);
    }

    /**
     * Get payables summary
     * GET /api/v1/business/payables/summary
     */
    public function payables(Request $request): JsonResponse
    {
        $business = $this->business();
        $branchId = $request->get('branch_id');

        $vendors = Vendor::forBusiness($business->id, $branchId)
            ->active()
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();

        $totalPayable = $vendors->sum('balance');
        $vendorsCount = $vendors->count();

        // Top 10 vendors by balance
        $topVendors = $vendors->take(10)->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'phone' => $v->phone,
            'balance' => (float) $v->balance,
            'branch_name' => $v->branch?->name,
        ]);

        // Aging analysis (simplified)
        $aging = [
            'current' => 0,
            'overdue_30' => 0,
            'overdue_60' => 0,
            'overdue_90' => 0,
        ];

        return $this->success([
            'total_payable' => (float) $totalPayable,
            'vendors_count' => $vendorsCount,
            'average_per_vendor' => $vendorsCount > 0 ? round($totalPayable / $vendorsCount, 2) : 0,
            'top_vendors' => $topVendors,
            'aging' => $aging,
            'currency' => $business->currency ?? 'USD',
        ]);
    }
}
