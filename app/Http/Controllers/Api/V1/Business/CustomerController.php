<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerController extends BaseController
{
    /**
     * List customers
     * GET /api/v1/business/customers
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        $perPage = min($request->get('per_page', 20), 50);

        $query = Customer::forBusiness($business->id, $request->get('branch_id'))
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->when($request->has('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $paginator = $query->orderBy('name')->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($customer) => $this->formatCustomer($customer));

        $totalReceivable = Customer::forBusiness($business->id, $request->get('branch_id'))
            ->where('balance', '>', 0)
            ->sum('balance');

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total_receivable' => (float) $totalReceivable,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create customer
     * POST /api/v1/business/customers
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();
        $data = $validator->validated();

        $customer = Customer::create([
            'business_id' => $business->id,
            'branch_id' => $data['branch_id'] ?? null,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'balance' => 0,
            'is_active' => true,
        ]);

        return $this->success($this->formatCustomer($customer), 'Customer created successfully', 201);
    }

    /**
     * Show customer
     * GET /api/v1/business/customers/{customer}
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        return $this->success($this->formatCustomer($customer));
    }

    /**
     * Update customer
     * PUT /api/v1/business/customers/{customer}
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'address' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $customer->update($validator->validated());

        return $this->success($this->formatCustomer($customer), 'Customer updated successfully');
    }

    /**
     * Delete customer
     * DELETE /api/v1/business/customers/{customer}
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        if ($customer->transactions()->exists()) {
            return $this->error('Cannot delete customer with transactions. Deactivate instead.', 'HAS_TRANSACTIONS', 400);
        }

        $customer->delete();

        return $this->success(null, 'Customer deleted successfully');
    }

    /**
     * Debit customer (customer owes more)
     * POST /api/v1/business/customers/{customer}/debit
     * NOTE: Does NOT affect cash accounts
     */
    public function debit(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        $previousBalance = $customer->balance;

        $transaction = $customer->debit(
            $data['amount'],
            $data['description'] ?? 'Debit',
            $this->user()->id
        );

        return $this->success([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $customer->fresh()->balance,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => 'debit',
                'amount' => (float) $data['amount'],
                'description' => $data['description'] ?? 'Debit',
            ],
        ], 'Customer debited successfully', 201);
    }

    /**
     * Credit customer (customer paid)
     * POST /api/v1/business/customers/{customer}/credit
     * NOTE: Does NOT affect cash accounts
     */
    public function credit(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        $previousBalance = $customer->balance;

        // Prevent overpayment: cannot credit more than customer owes
        if ($customer->balance <= 0) {
            return $this->error('Customer has no outstanding balance to pay', 'NO_BALANCE_OWED', 400);
        }

        if ($data['amount'] > $customer->balance) {
            return $this->error(
                'Payment amount exceeds outstanding balance. Maximum allowed: $' . number_format($customer->balance, 2),
                'OVERPAYMENT_NOT_ALLOWED',
                400,
                ['max_amount' => (float) $customer->balance]
            );
        }

        $transaction = $customer->credit(
            $data['amount'],
            $data['description'] ?? 'Credit',
            $this->user()->id
        );

        return $this->success([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $customer->fresh()->balance,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => 'credit',
                'amount' => (float) $data['amount'],
                'description' => $data['description'] ?? 'Credit',
            ],
        ], 'Customer credited successfully', 201);
    }

    /**
     * Deactivate customer (soft delete)
     * POST /api/v1/business/customers/{customer}/deactivate
     */
    public function deactivate(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $customer->update(['is_active' => false]);

        return $this->success($this->formatCustomer($customer), 'Customer deactivated successfully');
    }

    /**
     * Activate customer
     * POST /api/v1/business/customers/{customer}/activate
     */
    public function activate(Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $customer->update(['is_active' => true]);

        return $this->success($this->formatCustomer($customer), 'Customer activated successfully');
    }

    /**
     * Get customer transaction history
     * GET /api/v1/business/customers/{customer}/transactions
     */
    public function transactions(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $perPage = min((int) $request->get('per_page', 20), 50);
        $from = $request->get('from');
        $to = $request->get('to');

        $query = $customer->transactions()
            ->with('createdBy:id,name')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        $runningBalance = $customer->balance;
        $transactions = $paginator->getCollection()->map(function ($t) use (&$runningBalance) {
            $balanceAfter = $runningBalance;
            $runningBalance -= ($t->type === 'debit' ? $t->amount : -$t->amount);
            
            return [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'description' => $t->description,
                'balance_after' => (float) $balanceAfter,
                'created_by' => $t->createdBy?->name,
                'created_at' => $t->created_at->toIso8601String(),
            ];
        });

        return $this->success([
            'customer' => $this->formatCustomer($customer),
            'transactions' => $transactions,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'default_per_page' => 20,
                'max_per_page' => 50,
            ],
        ]);
    }

    /**
     * Export customer statement PDF
     * GET /api/v1/business/customers/{customer}/statement-pdf
     */
    public function statementPdf(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeCustomer($customer);

        $validator = Validator::make($request->all(), [
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();
        $currency = $business?->currency ?? 'USD';
        $validated = $validator->validated();

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        $query = $customer->transactions()
            ->with('createdBy:id,name')
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($from) {
            $query->whereDate('transaction_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('transaction_date', '<=', $to->toDateString());
        }

        $transactions = $query->get();

        $openingBalance = 0.0;
        if ($from) {
            $openingBalance = (float) $customer->transactions()
                ->whereDate('transaction_date', '<', $from->toDateString())
                ->selectRaw('COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE -amount END), 0) as opening_balance')
                ->value('opening_balance');
        }

        $runningBalance = $openingBalance;
        $rows = $transactions->map(function ($transaction) use (&$runningBalance) {
            $amount = (float) $transaction->amount;
            $isDebit = $transaction->type === 'debit';
            $runningBalance += $isDebit ? $amount : -$amount;

            return [
                'date' => $transaction->transaction_date?->toDateString() ?? '-',
                'description' => $transaction->description ?? ($isDebit ? 'Debit' : 'Credit'),
                'debit' => $isDebit ? round($amount, 2) : 0.0,
                'credit' => $isDebit ? 0.0 : round($amount, 2),
                'balance' => round($runningBalance, 2),
                'created_by' => $transaction->createdBy?->name ?? '-',
            ];
        })->values()->all();

        $totalDebit = round((float) $transactions->where('type', 'debit')->sum('amount'), 2);
        $totalCredit = round((float) $transactions->where('type', 'credit')->sum('amount'), 2);
        $closingBalance = round((float) $runningBalance, 2);

        $timestamp = now()->format('Ymd_His');
        $filename = "customer_statement_{$customer->id}_{$timestamp}.pdf";
        $path = "exports/{$business->id}/customer_statements/{$filename}";

        $pdf = Pdf::loadView('exports.customer-statement', [
            'business_name' => $business->name,
            'currency' => $currency,
            'customer' => $this->formatCustomer($customer),
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => $closingBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'rows' => $rows,
            'generated_at' => now()->toIso8601String(),
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $this->success([
            'customer' => $this->formatCustomer($customer),
            'file_name' => $filename,
            'file_path' => $path,
            'download_url' => asset('storage/' . $path),
            'period' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'summary' => [
                'opening_balance' => round($openingBalance, 2),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'closing_balance' => $closingBalance,
                'transactions_count' => $transactions->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ], 'Customer statement PDF generated successfully');
    }

    private function authorizeCustomer(Customer $customer): void
    {
        if ($customer->business_id !== $this->business()?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'balance' => (float) $customer->balance,
            'status' => $customer->status,
            'branch_id' => $customer->branch_id,
            'branch_name' => $customer->branch?->name,
            'notes' => $customer->notes,
            'is_active' => $customer->is_active,
            'created_at' => $customer->created_at->toIso8601String(),
        ];
    }
}
