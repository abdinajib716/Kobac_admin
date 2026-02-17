<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ProfitLossController extends BaseController
{
    /**
     * Get P&L summary (Read-Only)
     * GET /api/v1/business/profit-loss
     * Formula: P&L = Total Cash Income – Total Cash Expense
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        $branchId = $request->get('branch_id');

        // Default to current month
        $from = $request->has('from') 
            ? Carbon::parse($request->get('from')) 
            : Carbon::now()->startOfMonth();
            
        $to = $request->has('to') 
            ? Carbon::parse($request->get('to')) 
            : Carbon::now()->endOfMonth();

        // Get income by category
        $incomeQuery = IncomeTransaction::forBusiness($business->id, $branchId)
            ->dateRange($from, $to);
            
        $totalIncome = $incomeQuery->sum('amount');
        
        $incomeByCategory = IncomeTransaction::forBusiness($business->id, $branchId)
            ->dateRange($from, $to)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Get expense by category
        $expenseQuery = ExpenseTransaction::forBusiness($business->id, $branchId)
            ->dateRange($from, $to);
            
        $totalExpense = $expenseQuery->sum('amount');
        
        $expenseByCategory = ExpenseTransaction::forBusiness($business->id, $branchId)
            ->dateRange($from, $to)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Calculate P&L
        $profitLoss = $totalIncome - $totalExpense;

        return $this->success([
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'income' => [
                'total' => (float) $totalIncome,
                'by_category' => array_map('floatval', $incomeByCategory),
            ],
            'expense' => [
                'total' => (float) $totalExpense,
                'by_category' => array_map('floatval', $expenseByCategory),
            ],
            'profit_loss' => (float) $profitLoss,
            'currency' => $business->currency,
        ]);
    }
}
