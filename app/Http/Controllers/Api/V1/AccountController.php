<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AccountController extends BaseController
{
    /**
     * List accounts
     * GET /api/v1/accounts
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        $query = $user->isIndividual()
            ? Account::forUser($user)
            : Account::forBusiness($user->business?->id, $request->get('branch_id'));

        $accounts = $query->active()->get();

        $totalBalance = $accounts->sum('balance');

        return $this->success([
            'accounts' => $accounts->map(fn ($account) => $this->formatAccount($account)),
            'summary' => [
                'total_balance' => (float) $totalBalance,
                'currency' => 'USD',
            ],
        ]);
    }

    /**
     * Create account
     * POST /api/v1/accounts
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,mobile_money,bank',
            'provider' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'initial_balance' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $user = $this->user();
        $data = $validator->validated();

        $account = Account::create([
            'user_id' => $user->isIndividual() ? $user->id : null,
            'business_id' => $user->isBusiness() ? $user->business?->id : null,
            'branch_id' => $data['branch_id'] ?? null,
            'name' => $data['name'],
            'type' => $data['type'],
            'balance' => $data['initial_balance'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'provider' => $data['provider'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'is_active' => true,
        ]);

        return $this->success($this->formatAccount($account), 'Account created successfully', 201);
    }

    /**
     * Show account
     * GET /api/v1/accounts/{account}
     */
    public function show(Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        return $this->success($this->formatAccount($account));
    }

    /**
     * Update account
     * PUT /api/v1/accounts/{account}
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'provider' => 'sometimes|nullable|string|max:100',
            'account_number' => 'sometimes|nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $account->update($validator->validated());

        return $this->success($this->formatAccount($account), 'Account updated successfully');
    }

    /**
     * Delete account
     * DELETE /api/v1/accounts/{account}
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        if ($account->incomeTransactions()->exists() || $account->expenseTransactions()->exists()) {
            return $this->error(
                'Cannot delete account with transactions. Deactivate it instead.',
                'HAS_TRANSACTIONS',
                400
            );
        }

        $account->delete();

        return $this->success(null, 'Account deleted successfully');
    }

    /**
     * Deactivate account (soft delete)
     * POST /api/v1/accounts/{account}/deactivate
     */
    public function deactivate(Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $account->update(['is_active' => false]);

        return $this->success($this->formatAccount($account), 'Account deactivated successfully');
    }

    /**
     * Activate account
     * POST /api/v1/accounts/{account}/activate
     */
    public function activate(Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $account->update(['is_active' => true]);

        return $this->success($this->formatAccount($account), 'Account activated successfully');
    }

    /**
     * Get account ledger (transaction history with running balance)
     * GET /api/v1/accounts/{account}/ledger
     */
    public function ledger(Request $request, Account $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $perPage = min((int) $request->get('per_page', 50), 100);

        // Get income transactions
        $incomes = $account->incomeTransactions()
            ->whereBetween('transaction_date', [$from, $to])
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => 'income',
                'amount' => (float) $t->amount,
                'description' => $t->description,
                'category' => $t->category,
                'date' => $t->transaction_date->toDateString(),
                'created_at' => $t->created_at->toIso8601String(),
            ]);

        // Get expense transactions
        $expenses = $account->expenseTransactions()
            ->whereBetween('transaction_date', [$from, $to])
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => 'expense',
                'amount' => (float) -$t->amount,
                'description' => $t->description,
                'category' => $t->category,
                'date' => $t->transaction_date->toDateString(),
                'created_at' => $t->created_at->toIso8601String(),
            ]);

        // Merge and sort by date
        $transactions = $incomes->concat($expenses)
            ->sortBy('date')
            ->values();

        // Calculate running balance
        $openingBalance = $this->calculateOpeningBalance($account, $from);
        $runningBalance = $openingBalance;
        $ledger = [];

        foreach ($transactions as $t) {
            $runningBalance += $t['amount'];
            $ledger[] = array_merge($t, ['running_balance' => round($runningBalance, 2)]);
        }

        return $this->success([
            'account' => $this->formatAccount($account),
            'period' => ['from' => $from, 'to' => $to],
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'total_income' => round($incomes->sum('amount'), 2),
            'total_expense' => round(abs($expenses->sum('amount')), 2),
            'ledger' => $ledger,
            'pagination' => [
                'total' => count($ledger),
                'per_page' => $perPage,
                'default_per_page' => 50,
                'max_per_page' => 100,
            ],
        ]);
    }

    private function calculateOpeningBalance(Account $account, string $from): float
    {
        $incomesBefore = $account->incomeTransactions()
            ->where('transaction_date', '<', $from)
            ->sum('amount');

        $expensesBefore = $account->expenseTransactions()
            ->where('transaction_date', '<', $from)
            ->sum('amount');

        // Assuming initial balance was set when account was created
        return (float) ($incomesBefore - $expensesBefore);
    }

    private function authorizeAccount(Account $account): void
    {
        $user = $this->user();

        if ($user->isIndividual() && $account->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this account');
        }

        if ($user->isBusiness() && $account->business_id !== $user->business?->id) {
            abort(403, 'Unauthorized access to this account');
        }
    }

    private function formatAccount(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type,
            'type_label' => $account->getTypeLabel(),
            'balance' => (float) $account->balance,
            'currency' => $account->currency,
            'provider' => $account->provider,
            'account_number' => $account->account_number,
            'is_active' => $account->is_active,
            'created_at' => $account->created_at->toIso8601String(),
        ];
    }
}
