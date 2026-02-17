<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Models\IncomeTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class IncomeController extends BaseController
{
    /**
     * List income transactions
     * GET /api/v1/income
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();
        $perPage = min($request->get('per_page', 20), 50);

        $query = $user->isIndividual()
            ? IncomeTransaction::forUser($user)
            : IncomeTransaction::forBusiness($user->business?->id, $request->get('branch_id'));

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

        $data = $paginator->getCollection()->map(fn ($income) => $this->formatIncome($income));

        return $this->paginated($paginator, $data);
    }

    /**
     * Create income transaction
     * POST /api/v1/income
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

        DB::transaction(function () use ($user, $data, $account, &$income) {
            $income = IncomeTransaction::create([
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
            $account->credit($data['amount']);
            
            // Log activity
            ActivityLogger::income('created', $income, [
                'amount' => $data['amount'],
                'account_name' => $account->name,
            ]);
        });

        return $this->success([
            'transaction' => [
                'id' => $income->id,
                'amount' => (float) $income->amount,
                'description' => $income->description,
                'transaction_date' => $income->transaction_date->toDateString(),
            ],
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $account->fresh()->balance,
            ],
        ], 'Income recorded successfully', 201);
    }

    /**
     * Show income transaction
     * GET /api/v1/income/{income}
     */
    public function show(IncomeTransaction $income): JsonResponse
    {
        $this->authorizeTransaction($income);

        return $this->success($this->formatIncome($income));
    }

    /**
     * Update income transaction
     * PUT /api/v1/income/{income}
     */
    public function update(Request $request, IncomeTransaction $income): JsonResponse
    {
        $this->authorizeTransaction($income);

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

        $income->update($validator->validated());

        return $this->success($this->formatIncome($income), 'Income updated successfully');
    }

    /**
     * Delete income transaction
     * DELETE /api/v1/income/{income}
     */
    public function destroy(IncomeTransaction $income): JsonResponse
    {
        $this->authorizeTransaction($income);

        $account = $income->account;

        $amount = $income->amount;
        
        DB::transaction(function () use ($income, $account) {
            // Reverse the balance change
            $account->debit($income->amount);
            $income->delete();
        });
        
        // Log activity
        ActivityLogger::income('deleted', null, [
            'amount' => $amount,
            'account_name' => $account->name,
        ]);

        return $this->success(null, 'Income deleted successfully');
    }

    private function authorizeTransaction(IncomeTransaction $income): void
    {
        $user = $this->user();

        if ($user->isIndividual() && $income->user_id !== $user->id) {
            abort(403, 'Unauthorized access');
        }

        if ($user->isBusiness() && $income->business_id !== $user->business?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatIncome(IncomeTransaction $income): array
    {
        return [
            'id' => $income->id,
            'account_id' => $income->account_id,
            'account_name' => $income->account->name ?? 'Unknown',
            'amount' => (float) $income->amount,
            'description' => $income->description,
            'category' => $income->category,
            'reference' => $income->reference,
            'transaction_date' => $income->transaction_date->toDateString(),
            'created_at' => $income->created_at->toIso8601String(),
        ];
    }
}
