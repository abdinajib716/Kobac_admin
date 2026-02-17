<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VendorController extends BaseController
{
    /**
     * List vendors
     * GET /api/v1/business/vendors
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        $perPage = min($request->get('per_page', 20), 50);

        $query = Vendor::forBusiness($business->id, $request->get('branch_id'))
            ->when($request->boolean('active_only', true), fn ($q) => $q->active())
            ->when($request->has('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $paginator = $query->orderBy('name')->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($vendor) => $this->formatVendor($vendor));

        $totalPayable = Vendor::forBusiness($business->id, $request->get('branch_id'))
            ->where('balance', '>', 0)
            ->sum('balance');

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total_payable' => (float) $totalPayable,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create vendor
     * POST /api/v1/business/vendors
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

        $vendor = Vendor::create([
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

        return $this->success($this->formatVendor($vendor), 'Vendor created successfully', 201);
    }

    /**
     * Show vendor
     * GET /api/v1/business/vendors/{vendor}
     */
    public function show(Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

        return $this->success($this->formatVendor($vendor));
    }

    /**
     * Update vendor
     * PUT /api/v1/business/vendors/{vendor}
     */
    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

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

        $vendor->update($validator->validated());

        return $this->success($this->formatVendor($vendor), 'Vendor updated successfully');
    }

    /**
     * Delete vendor
     * DELETE /api/v1/business/vendors/{vendor}
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

        if ($vendor->transactions()->exists()) {
            return $this->error('Cannot delete vendor with transactions. Deactivate instead.', 'HAS_TRANSACTIONS', 400);
        }

        $vendor->delete();

        return $this->success(null, 'Vendor deleted successfully');
    }

    /**
     * Credit vendor (we owe more)
     * POST /api/v1/business/vendors/{vendor}/credit
     * NOTE: Does NOT create expense
     */
    public function credit(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

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
        $previousBalance = $vendor->balance;

        $transaction = $vendor->credit(
            $data['amount'],
            $data['description'] ?? 'Credit',
            $this->user()->id
        );

        return $this->success([
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $vendor->fresh()->balance,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => 'credit',
                'amount' => (float) $data['amount'],
                'description' => $data['description'] ?? 'Credit',
            ],
        ], 'Vendor credited successfully', 201);
    }

    /**
     * Debit vendor (we paid)
     * POST /api/v1/business/vendors/{vendor}/debit
     * NOTE: Does NOT create expense
     */
    public function debit(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

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
        $previousBalance = $vendor->balance;

        // Prevent overpayment: cannot pay more than we owe vendor
        if ($vendor->balance <= 0) {
            return $this->error('No outstanding balance owed to this vendor', 'NO_BALANCE_OWED', 400);
        }

        if ($data['amount'] > $vendor->balance) {
            return $this->error(
                'Payment amount exceeds outstanding balance. Maximum allowed: $' . number_format($vendor->balance, 2),
                'OVERPAYMENT_NOT_ALLOWED',
                400,
                ['max_amount' => (float) $vendor->balance]
            );
        }

        $transaction = $vendor->debit(
            $data['amount'],
            $data['description'] ?? 'Debit',
            $this->user()->id
        );

        return $this->success([
            'vendor' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'previous_balance' => (float) $previousBalance,
                'new_balance' => (float) $vendor->fresh()->balance,
            ],
            'transaction' => [
                'id' => $transaction->id,
                'type' => 'debit',
                'amount' => (float) $data['amount'],
                'description' => $data['description'] ?? 'Debit',
            ],
        ], 'Vendor debited successfully', 201);
    }

    /**
     * Deactivate vendor (soft delete)
     * POST /api/v1/business/vendors/{vendor}/deactivate
     */
    public function deactivate(Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

        $vendor->update(['is_active' => false]);

        return $this->success($this->formatVendor($vendor), 'Vendor deactivated successfully');
    }

    /**
     * Activate vendor
     * POST /api/v1/business/vendors/{vendor}/activate
     */
    public function activate(Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

        $vendor->update(['is_active' => true]);

        return $this->success($this->formatVendor($vendor), 'Vendor activated successfully');
    }

    /**
     * Get vendor transaction history
     * GET /api/v1/business/vendors/{vendor}/transactions
     */
    public function transactions(Request $request, Vendor $vendor): JsonResponse
    {
        $this->authorizeVendor($vendor);

        $perPage = min((int) $request->get('per_page', 20), 50);
        $from = $request->get('from');
        $to = $request->get('to');

        $query = $vendor->transactions()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc');

        $paginator = $query->paginate($perPage);

        $runningBalance = $vendor->balance;
        $transactions = $paginator->getCollection()->map(function ($t) use (&$runningBalance) {
            $balanceAfter = $runningBalance;
            $runningBalance -= ($t->type === 'credit' ? $t->amount : -$t->amount);
            
            return [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'description' => $t->description,
                'balance_after' => (float) $balanceAfter,
                'created_by' => $t->user?->name,
                'created_at' => $t->created_at->toIso8601String(),
            ];
        });

        return $this->success([
            'vendor' => $this->formatVendor($vendor),
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

    private function authorizeVendor(Vendor $vendor): void
    {
        if ($vendor->business_id !== $this->business()?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatVendor(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
            'address' => $vendor->address,
            'balance' => (float) $vendor->balance,
            'status' => $vendor->status,
            'branch_id' => $vendor->branch_id,
            'branch_name' => $vendor->branch?->name,
            'notes' => $vendor->notes,
            'is_active' => $vendor->is_active,
            'created_at' => $vendor->created_at->toIso8601String(),
        ];
    }
}
