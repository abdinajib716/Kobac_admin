<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\IncomeTransaction;
use App\Models\ExpenseTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        $activities = collect();

        // Get income transactions
        if ($user->isIndividual()) {
            $incomeQuery = IncomeTransaction::forUser($user);
        } else {
            $business = $this->business();
            if ($business) {
                $incomeQuery = IncomeTransaction::forBusiness($business->id, $branchId);
            } else {
                $incomeQuery = null;
            }
        }

        if ($incomeQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $incomes = $incomeQuery->with('account')->latest()->take(50)->get();
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
            $expenses = $expenseQuery->with('account')->latest()->take(50)->get();
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
                    'timestamp' => $expense->created_at->toIso8601String(),
                    'date' => $expense->transaction_date->toDateString(),
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
