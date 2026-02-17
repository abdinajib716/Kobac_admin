<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends BaseController
{
    /**
     * Global search across all resources
     * GET /api/v1/search?q=...
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 5), 10);

        if (strlen($query) < 2) {
            return $this->error('Search query must be at least 2 characters', 'QUERY_TOO_SHORT', 400);
        }

        $user = $this->user();
        $results = [];

        // Search accounts
        $accountsQuery = $user->isIndividual()
            ? Account::forUser($user)
            : Account::forBusiness($user->business?->id);

        $accounts = $accountsQuery
            ->where('name', 'like', "%{$query}%")
            ->active()
            ->limit($limit)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'balance' => (float) $a->balance,
            ]);

        if ($accounts->isNotEmpty()) {
            $results['accounts'] = $accounts;
        }

        // Business-only searches
        if ($user->isBusiness() && $user->business) {
            $businessId = $user->business->id;

            // Search customers
            $customers = Customer::forBusiness($businessId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->active()
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'balance' => (float) $c->balance,
                ]);

            if ($customers->isNotEmpty()) {
                $results['customers'] = $customers;
            }

            // Search vendors
            $vendors = Vendor::forBusiness($businessId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->active()
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'phone' => $v->phone,
                    'balance' => (float) $v->balance,
                ]);

            if ($vendors->isNotEmpty()) {
                $results['vendors'] = $vendors;
            }

            // Search stock items
            $stockItems = StockItem::forBusiness($businessId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                })
                ->active()
                ->limit($limit)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'sku' => $s->sku,
                    'quantity' => (float) $s->quantity,
                    'unit' => $s->unit,
                ]);

            if ($stockItems->isNotEmpty()) {
                $results['stock_items'] = $stockItems;
            }
        }

        $totalResults = collect($results)->flatten(1)->count();

        return $this->success([
            'query' => $query,
            'results' => $results,
            'total_results' => $totalResults,
            'limit_per_type' => $limit,
        ]);
    }
}
