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
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();
        $perPage = min($request->get('per_page', 20), 50);

        $activities = collect();

        // Get income transactions
        $incomeQuery = $user->isIndividual()
            ? IncomeTransaction::forUser($user)
            : ($user->business ? IncomeTransaction::forBusiness($user->business->id) : collect());

        if ($incomeQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $incomes = $incomeQuery->with('account')->latest()->take(50)->get();
            foreach ($incomes as $income) {
                $activities->push([
                    'id' => 'income_' . $income->id,
                    'type' => 'income',
                    'description' => 'Recorded income: ' . ($income->description ?? 'No description'),
                    'amount' => (float) $income->amount,
                    'account_name' => $income->account->name ?? 'Unknown',
                    'timestamp' => $income->created_at->toIso8601String(),
                    'date' => $income->transaction_date->toDateString(),
                ]);
            }
        }

        // Get expense transactions
        $expenseQuery = $user->isIndividual()
            ? ExpenseTransaction::forUser($user)
            : ($user->business ? ExpenseTransaction::forBusiness($user->business->id) : collect());

        if ($expenseQuery instanceof \Illuminate\Database\Eloquent\Builder) {
            $expenses = $expenseQuery->with('account')->latest()->take(50)->get();
            foreach ($expenses as $expense) {
                $activities->push([
                    'id' => 'expense_' . $expense->id,
                    'type' => 'expense',
                    'description' => 'Recorded expense: ' . ($expense->description ?? 'No description'),
                    'amount' => (float) $expense->amount,
                    'account_name' => $expense->account->name ?? 'Unknown',
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
