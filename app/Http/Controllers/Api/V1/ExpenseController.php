<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Models\ExpenseTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class ExpenseController extends BaseController
{
    /**
     * List expense transactions
     * GET /api/v1/expenses
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();
        $perPage = min($request->get('per_page', 20), 50);

        $query = $user->isIndividual()
            ? ExpenseTransaction::forUser($user)
            : ExpenseTransaction::forBusiness($user->business?->id, $request->get('branch_id'));

        if ($request->has('from')) {
            $query->whereDate('transaction_date', '>=', $request->get('from'));
        }

        if ($request->has('to')) {
            $query->whereDate('transaction_date', '<=', $request->get('to'));
        }

        if ($request->has('account_id')) {
            $query->where('account_id', $request->get('account_id'));
        }

        $paginator = $query->with('account')->latest('transaction_date')->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($expense) => $this->formatExpense($expense));

        return $this->paginated($paginator, $data);
    }

    /**
     * Create expense transaction
     * POST /api/v1/expenses
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $data = $validator->validated();
        $account = Account::findOrFail($data['account_id']);

        // Verify account ownership
        if ($user->isIndividual() && $account->user_id !== $user->id) {
            return $this->error('Unauthorized account', 'UNAUTHORIZED', 403);
        }

        if ($user->isBusiness() && $account->business_id !== $user->business?->id) {
            return $this->error('Unauthorized account', 'UNAUTHORIZED', 403);
        }

        $previousBalance = $account->balance;

        DB::transaction(function () use ($user, $data, $account, &$expense) {
            $expense = ExpenseTransaction::create([
                'user_id' => $user->isIndividual() ? $user->id : null,
                'business_id' => $user->isBusiness() ? $user->business?->id : null,
                'branch_id' => $account->branch_id,
                'account_id' => $account->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'reference' => $data['reference'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_by' => $user->id,
            ]);

            // Update account balance
            $account->debit($data['amount']);
            
            // Log activity
            ActivityLogger::expense('created', $expense, [
                'amount' => $data['amount'],
                'account_name' => $account->name,
            ]);
        });

        return $this->success([
            'transaction' => [
                'id' => $expense->id,
                'amount' => (float) $expense->amount,
                'description' => $expense->description,
                'transaction_date' => $expense->transaction_date->toDateString(),
            ],
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $account->fresh()->balance,
            ],
        ], 'Expense recorded successfully', 201);
    }

    /**
     * Show expense transaction
     * GET /api/v1/expenses/{expense}
     */
    public function show(ExpenseTransaction $expense): JsonResponse
    {
        $this->authorizeTransaction($expense);

        return $this->success($this->formatExpense($expense));
    }

    /**
     * Update expense transaction
     * PUT /api/v1/expenses/{expense}
     */
    public function update(Request $request, ExpenseTransaction $expense): JsonResponse
    {
        $this->authorizeTransaction($expense);

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|nullable|string|max:500',
            'category' => 'sometimes|nullable|string|max:100',
            'reference' => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $expense->update($validator->validated());

        return $this->success($this->formatExpense($expense), 'Expense updated successfully');
    }

    /**
     * Delete expense transaction
     * DELETE /api/v1/expenses/{expense}
     */
    public function destroy(ExpenseTransaction $expense): JsonResponse
    {
        $this->authorizeTransaction($expense);

        $account = $expense->account;

        DB::transaction(function () use ($expense, $account) {
            // Reverse the balance change
            $account->credit($expense->amount);
            $expense->delete();
        });

        return $this->success(null, 'Expense deleted successfully');
    }

    private function authorizeTransaction(ExpenseTransaction $expense): void
    {
        $user = $this->user();

        if ($user->isIndividual() && $expense->user_id !== $user->id) {
            abort(403, 'Unauthorized access');
        }

        if ($user->isBusiness() && $expense->business_id !== $user->business?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatExpense(ExpenseTransaction $expense): array
    {
        return [
            'id' => $expense->id,
            'account_id' => $expense->account_id,
            'account_name' => $expense->account->name ?? 'Unknown',
            'amount' => (float) $expense->amount,
            'description' => $expense->description,
            'category' => $expense->category,
            'reference' => $expense->reference,
            'transaction_date' => $expense->transaction_date->toDateString(),
            'created_at' => $expense->created_at->toIso8601String(),
        ];
    }
}
