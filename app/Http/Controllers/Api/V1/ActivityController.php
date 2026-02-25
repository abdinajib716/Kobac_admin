<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use App\Models\StockMovement;
use App\Models\VendorTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class ActivityController extends BaseController
{
    /**
     * Get activity timeline
     * GET /api/v1/activity
     * 
     * For business users: uses X-Branch-ID header for branch filtering
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();
        $perPage = min($request->get('per_page', 20), 50);
        $branchId = $this->branchId();
        $business = $user->isBusiness() ? $this->business() : null;

        $activities = collect();

        // Get income transactions
        if ($user->isIndividual()) {
            $incomeQuery = IncomeTransaction::forUser($user);
        } else {
            if ($business) {
                $incomeQuery = IncomeTransaction::forBusiness($business->id, $branchId);
            } else {
                $incomeQuery = null;
            }
        }

        if ($incomeQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $incomes = $incomeQuery->with(['account', 'createdBy'])->latest()->take(50)->get();
            foreach ($incomes as $income) {
                $activities->push([
                    'id' => 'income_' . $income->id,
                    'type' => 'income',
                    'description' => $income->description ?? 'Income recorded',
                    'amount' => (float) $income->amount,
                    'category' => $income->category,
                    'account_name' => $income->account->name ?? 'Unknown',
                    'account_id' => $income->account_id,
                    'reference' => $income->reference,
                    'created_by' => $income->createdBy?->name,
                    'timestamp' => $income->created_at->toIso8601String(),
                    'date' => $income->transaction_date->toDateString(),
                ]);
            }
        }

        // Get expense transactions
        if ($user->isIndividual()) {
            $expenseQuery = ExpenseTransaction::forUser($user);
        } else {
            $business = $this->business();
            if ($business) {
                $expenseQuery = ExpenseTransaction::forBusiness($business->id, $branchId);
            } else {
                $expenseQuery = null;
            }
        }

        if ($expenseQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $expenses = $expenseQuery->with(['account', 'createdBy'])->latest()->take(50)->get();
            foreach ($expenses as $expense) {
                $activities->push([
                    'id' => 'expense_' . $expense->id,
                    'type' => 'expense',
                    'description' => $expense->description ?? 'Expense recorded',
                    'amount' => (float) $expense->amount,
                    'category' => $expense->category,
                    'account_name' => $expense->account->name ?? 'Unknown',
                    'account_id' => $expense->account_id,
                    'reference' => $expense->reference,
                    'created_by' => $expense->createdBy?->name,
                    'timestamp' => $expense->created_at->toIso8601String(),
                    'date' => $expense->transaction_date->toDateString(),
                ]);
            }
        }

        // Business-only activity stream: vendor transactions
        if ($business) {
            $vendorTransactions = VendorTransaction::query()
                ->whereHas('vendor', function ($query) use ($business) {
                    $query->where('business_id', $business->id);
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->with(['vendor:id,name', 'createdBy:id,name'])
                ->latest()
                ->take(50)
                ->get();

            foreach ($vendorTransactions as $transaction) {
                $isCredit = $transaction->type === 'credit';

                $activities->push([
                    'id' => 'vendor_' . $transaction->id,
                    'type' => $isCredit ? 'vendor_credit' : 'vendor_debit',
                    'description' => $transaction->description ?: ($isCredit ? 'Vendor credited' : 'Vendor debited'),
                    'amount' => (float) $transaction->amount,
                    'category' => 'vendor',
                    'account_name' => null,
                    'account_id' => null,
                    'reference' => null,
                    'created_by' => $transaction->createdBy?->name,
                    'vendor_name' => $transaction->vendor?->name,
                    'timestamp' => $transaction->created_at->toIso8601String(),
                    'date' => $transaction->transaction_date?->toDateString(),
                ]);
            }

            $stockMovements = StockMovement::query()
                ->whereHas('stockItem', function ($query) use ($business) {
                    $query->where('business_id', $business->id);
                })
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->with(['stockItem:id,name,sku', 'createdBy:id,name'])
                ->latest()
                ->take(50)
                ->get();

            foreach ($stockMovements as $movement) {
                $isIncrease = $movement->type === 'increase';
                $itemName = $movement->stockItem?->name ?? 'Stock item';

                $activities->push([
                    'id' => 'stock_' . $movement->id,
                    'type' => $isIncrease ? 'stock_increase' : 'stock_decrease',
                    'description' => $movement->reason ?: ($isIncrease ? 'Stock increased' : 'Stock decreased'),
                    'amount' => null,
                    'quantity' => (float) $movement->quantity,
                    'category' => 'stock',
                    'account_name' => null,
                    'account_id' => null,
                    'reference' => $movement->reference,
                    'created_by' => $movement->createdBy?->name,
                    'stock_item_name' => $itemName,
                    'stock_sku' => $movement->stockItem?->sku,
                    'timestamp' => $movement->created_at->toIso8601String(),
                    'date' => $movement->created_at->toDateString(),
                ]);
            }

            // Stock CRUD activity events (create/update/delete)
            $stockCrudActivities = ActivityLog::query()
                ->where('log_name', 'stock')
                ->where('properties->business_id', $business->id)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($subQuery) use ($branchId) {
                        $subQuery->where('properties->branch_id', (int) $branchId)
                            ->orWhereNull('properties->branch_id');
                    });
                })
                ->with('causer:id,name')
                ->latest()
                ->take(50)
                ->get();

            foreach ($stockCrudActivities as $stockActivity) {
                $action = data_get($stockActivity->properties, 'action', $stockActivity->event);
                $name = data_get($stockActivity->properties, 'name');
                $sku = data_get($stockActivity->properties, 'sku');

                $type = match ($action) {
                    'created' => 'stock_created',
                    'updated' => 'stock_updated',
                    'deleted' => 'stock_deleted',
                    default => 'stock_' . $action,
                };

                $activities->push([
                    'id' => 'stock_activity_' . $stockActivity->id,
                    'type' => $type,
                    'description' => $stockActivity->description ?: ('Stock item ' . $action),
                    'amount' => null,
                    'quantity' => data_get($stockActivity->properties, 'quantity'),
                    'category' => 'stock',
                    'account_name' => null,
                    'account_id' => null,
                    'reference' => $sku,
                    'created_by' => $stockActivity->causer?->name,
                    'stock_item_id' => data_get($stockActivity->properties, 'stock_item_id', $stockActivity->subject_id),
                    'stock_item_name' => $name,
                    'stock_sku' => $sku,
                    'timestamp' => $stockActivity->created_at->toIso8601String(),
                    'date' => $stockActivity->created_at->toDateString(),
                ]);
            }
        }

        // Sort by timestamp descending and paginate
        $sorted = $activities->sortByDesc('timestamp')->values();
        $page = $request->get('page', 1);
        $paginated = $sorted->forPage($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->values(),
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'total' => $sorted->count(),
            ],
        ]);
    }
}
