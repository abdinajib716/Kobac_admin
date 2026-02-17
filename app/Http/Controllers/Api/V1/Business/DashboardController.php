<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Branch;
use App\Models\Account;
use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    /**
     * Get business dashboard
     * GET /api/v1/business/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $branchId = $request->get('branch_id');
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Current branch info
        $currentBranch = $branchId 
            ? Branch::find($branchId) 
            : $business->mainBranch();

        // Income stats
        $todayIncome = IncomeTransaction::forBusiness($business->id, $branchId)
            ->whereDate('transaction_date', $today)
            ->sum('amount');
            
        $monthIncome = IncomeTransaction::forBusiness($business->id, $branchId)
            ->dateRange($startOfMonth, $endOfMonth)
            ->sum('amount');

        // Expense stats
        $todayExpense = ExpenseTransaction::forBusiness($business->id, $branchId)
            ->whereDate('transaction_date', $today)
            ->sum('amount');
            
        $monthExpense = ExpenseTransaction::forBusiness($business->id, $branchId)
            ->dateRange($startOfMonth, $endOfMonth)
            ->sum('amount');

        // Receivables (customers who owe us)
        $receivablesQuery = Customer::forBusiness($business->id, $branchId)
            ->where('balance', '>', 0);
        $totalReceivables = $receivablesQuery->sum('balance');
        $receivablesCount = $receivablesQuery->count();

        // Payables (we owe vendors)
        $payablesQuery = Vendor::forBusiness($business->id, $branchId)
            ->where('balance', '>', 0);
        $totalPayables = $payablesQuery->sum('balance');
        $payablesCount = $payablesQuery->count();

        // Stock summary
        $stockQuery = StockItem::forBusiness($business->id, $branchId)->active();
        $stockItems = $stockQuery->count();
        $stockValue = $stockQuery->get()->sum(fn ($item) => $item->quantity * ($item->selling_price ?? 0));

        // P&L this month
        $profitLoss = $monthIncome - $monthExpense;

        // Branch comparison (only if no specific branch selected)
        $branchComparison = [];
        if (!$branchId) {
            $branches = $business->branches()->active()->get();
            foreach ($branches as $branch) {
                $branchIncome = IncomeTransaction::forBusiness($business->id, $branch->id)
                    ->dateRange($startOfMonth, $endOfMonth)
                    ->sum('amount');
                $branchExpense = ExpenseTransaction::forBusiness($business->id, $branch->id)
                    ->dateRange($startOfMonth, $endOfMonth)
                    ->sum('amount');

                $branchComparison[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'income' => (float) $branchIncome,
                    'expense' => (float) $branchExpense,
                ];
            }
        }

        return $this->success([
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'currency' => $business->currency ?? 'USD',
                'is_active' => true,
            ],
            'current_branch' => $currentBranch ? [
                'id' => $currentBranch->id,
                'name' => $currentBranch->name,
                'is_main' => $currentBranch->is_main ?? false,
            ] : null,
            'summary' => [
                'total_income' => (float) $monthIncome,
                'total_expense' => (float) $monthExpense,
                'total_receivables' => (float) $totalReceivables,
                'total_payables' => (float) $totalPayables,
                'net_position' => (float) ($totalReceivables - $totalPayables),
            ],
            'income' => [
                'today' => (float) $todayIncome,
                'this_month' => (float) $monthIncome,
            ],
            'expense' => [
                'today' => (float) $todayExpense,
                'this_month' => (float) $monthExpense,
            ],
            'customers' => [
                'total' => Customer::forBusiness($business->id, $branchId)->count(),
                'with_balance' => $receivablesCount,
                'total_owed' => (float) $totalReceivables,
            ],
            'vendors' => [
                'total' => Vendor::forBusiness($business->id, $branchId)->count(),
                'with_balance' => $payablesCount,
                'total_owed' => (float) $totalPayables,
            ],
            'stock' => [
                'total_items' => $stockItems,
                'total_value' => (float) $stockValue,
            ],
            'profit_loss' => [
                'this_month' => (float) $profitLoss,
            ],
            'branch_comparison' => $branchComparison,
        ]);
    }
}
