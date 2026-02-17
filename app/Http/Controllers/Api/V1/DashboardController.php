<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    /**
     * Get individual dashboard summary
     * GET /api/v1/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        // For Individual users
        if ($user->isIndividual()) {
            return $this->individualDashboard($user);
        }

        // For Business users, redirect to business dashboard
        if ($user->isBusiness()) {
            return $this->error(
                'Please use /api/v1/business/dashboard for business accounts',
                'USE_BUSINESS_DASHBOARD',
                400
            );
        }

        return $this->error('Invalid user type', 'INVALID_USER_TYPE', 400);
    }

    private function individualDashboard($user): JsonResponse
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $accounts = Account::forUser($user)->active()->get();
        
        $totalBalance = $accounts->sum('balance');
        
        $totalIncome = IncomeTransaction::forUser($user)
            ->dateRange($from, $to)
            ->sum('amount');
            
        $totalExpense = ExpenseTransaction::forUser($user)
            ->dateRange($from, $to)
            ->sum('amount');

        // Get recent transactions (combined income + expense, sorted by date)
        $recentIncome = IncomeTransaction::forUser($user)
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => 'income',
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'description' => $t->description,
                'date' => $t->transaction_date->toDateString(),
                'account_name' => $t->account->name ?? null,
            ]);

        $recentExpense = ExpenseTransaction::forUser($user)
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => 'expense',
                'amount' => (float) $t->amount,
                'category' => $t->category,
                'description' => $t->description,
                'date' => $t->transaction_date->toDateString(),
                'account_name' => $t->account->name ?? null,
            ]);

        $recentTransactions = $recentIncome->concat($recentExpense)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return $this->success([
            'summary' => [
                'total_balance' => (float) $totalBalance,
                'total_income' => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'accounts_count' => $accounts->count(),
            ],
            'currency' => 'USD',
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => (float) $account->balance,
                ];
            }),
            'recent_transactions' => $recentTransactions,
        ]);
    }
}
