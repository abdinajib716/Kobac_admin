<?php

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\IncomeTransaction;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockItem;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutSaleService
{
    public function execute(array $payload, int $businessId, ?int $branchId, int $userId): Sale
    {
        if (!empty($payload['idempotency_key'])) {
            $existingSale = Sale::query()
                ->where('business_id', $businessId)
                ->where('idempotency_key', $payload['idempotency_key'])
                ->first();

            if ($existingSale) {
                return $existingSale->load(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);
            }
        }

        return DB::transaction(function () use ($payload, $businessId, $branchId, $userId) {
            $stockIds = collect($payload['items'])->pluck('stock_item_id')->unique()->values();

            $stockItems = StockItem::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereIn('id', $stockIds)
                ->active()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($stockItems->count() !== $stockIds->count()) {
                throw ValidationException::withMessages([
                    'items' => ['One or more stock items are invalid or inactive for this branch.'],
                ]);
            }

            $customer = null;
            if (($payload['sale_type'] ?? 'cash') === 'credit') {
                $customer = Customer::query()
                    ->where('business_id', $businessId)
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('id', $payload['customer_id'] ?? null)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => ['A valid active customer is required for credit sales.'],
                    ]);
                }
            }

            $account = null;
            if (!empty($payload['account_id'])) {
                $account = Account::query()
                    ->where('business_id', $businessId)
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->where('id', $payload['account_id'])
                    ->active()
                    ->lockForUpdate()
                    ->first();

                if (!$account) {
                    throw ValidationException::withMessages([
                        'account_id' => ['The selected account is invalid for this branch.'],
                    ]);
                }
            }

            $lineItems = [];
            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $costTotal = 0.0;

            foreach ($payload['items'] as $itemInput) {
                $stock = $stockItems->get($itemInput['stock_item_id']);
                $quantity = round((float) $itemInput['quantity'], 2);
                $unitPrice = round((float) $itemInput['unit_price'], 2);
                $lineDiscount = round((float) ($itemInput['line_discount'] ?? 0), 2);
                $lineTax = round((float) ($itemInput['line_tax'] ?? 0), 2);

                if ($stock->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for {$stock->name}. Available: {$stock->quantity}"],
                    ]);
                }

                $lineSubtotal = round($quantity * $unitPrice, 2);
                $lineTotal = round($lineSubtotal - $lineDiscount + $lineTax, 2);
                $lineCost = round($quantity * (float) ($stock->cost_price ?? 0), 2);

                $subtotal += $lineSubtotal;
                $discountTotal += $lineDiscount;
                $taxTotal += $lineTax;
                $costTotal += $lineCost;

                $lineItems[] = [
                    'stock' => $stock,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_discount' => $lineDiscount,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                    'line_cost' => $lineCost,
                ];
            }

            $total = round($subtotal - $discountTotal + $taxTotal, 2);
            $amountPaid = ($payload['sale_type'] ?? 'cash') === 'cash' ? $total : round((float) ($payload['amount_paid'] ?? 0), 2);
            $amountDue = max(0, round($total - $amountPaid, 2));
            $paymentStatus = $amountDue <= 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');
            $soldAt = $payload['sold_at'] ?? now()->toDateTimeString();

            $sale = Sale::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'customer_id' => $customer?->id,
                'idempotency_key' => $payload['idempotency_key'] ?? null,
                'status' => Sale::STATUS_COMPLETED,
                'sale_type' => $payload['sale_type'],
                'payment_status' => $paymentStatus,
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => $total,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
                'cost_total' => round($costTotal, 2),
                'profit_total' => round($total - $costTotal, 2),
                'notes' => $payload['notes'] ?? null,
                'sold_at' => $soldAt,
                'created_by' => $userId,
                'completed_at' => now(),
                'meta' => [
                    'payment_method' => $payload['payment_method'] ?? null,
                ],
            ]);

            $sale->update([
                'sale_number' => 'SAL-' . now()->format('Y') . '-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'receipt_number' => 'RCP-' . now()->format('Y') . '-' . str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($lineItems as $lineItem) {
                $stock = $lineItem['stock'];

                $sale->items()->create([
                    'stock_item_id' => $stock->id,
                    'product_name_snapshot' => $stock->name,
                    'sku_snapshot' => $stock->sku,
                    'unit_snapshot' => $stock->unit ?? 'pcs',
                    'quantity' => $lineItem['quantity'],
                    'cost_price_snapshot' => (float) ($stock->cost_price ?? 0),
                    'unit_price' => $lineItem['unit_price'],
                    'line_discount' => $lineItem['line_discount'],
                    'line_tax' => $lineItem['line_tax'],
                    'line_total' => $lineItem['line_total'],
                ]);

                $quantityBefore = (float) $stock->quantity;
                $stock->decrement('quantity', $lineItem['quantity']);
                $stock->refresh();

                $stock->movements()->create([
                    'branch_id' => $stock->branch_id,
                    'type' => 'decrease',
                    'quantity' => $lineItem['quantity'],
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => (float) $stock->quantity,
                    'reason' => 'Sale',
                    'reference' => $sale->sale_number,
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'created_by' => $userId,
                    'meta' => [
                        'sale_number' => $sale->sale_number,
                        'unit_price' => $lineItem['unit_price'],
                    ],
                ]);
            }

            if ($payload['sale_type'] === 'credit' && $customer) {
                $customer->increment('balance', $total);
                $customer->refresh();

                CustomerTransaction::create([
                    'customer_id' => $customer->id,
                    'branch_id' => $customer->branch_id,
                    'type' => 'debit',
                    'amount' => $total,
                    'description' => 'Credit sale ' . $sale->sale_number,
                    'reference' => $sale->sale_number,
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'balance_after' => (float) $customer->balance,
                    'transaction_date' => $sale->sold_at?->toDateString() ?? now()->toDateString(),
                    'created_by' => $userId,
                    'meta' => [
                        'sale_number' => $sale->sale_number,
                        'payment_status' => $paymentStatus,
                    ],
                ]);
            }

            if ($account && $amountPaid > 0) {
                IncomeTransaction::create([
                    'user_id' => null,
                    'business_id' => $businessId,
                    'branch_id' => $account->branch_id,
                    'account_id' => $account->id,
                    'amount' => $amountPaid,
                    'description' => 'Sales receipt ' . $sale->sale_number,
                    'category' => 'sales',
                    'reference' => $sale->sale_number,
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'transaction_date' => $sale->sold_at?->toDateString() ?? now()->toDateString(),
                    'created_by' => $userId,
                    'meta' => [
                        'sale_number' => $sale->sale_number,
                        'payment_method' => $payload['payment_method'] ?? null,
                    ],
                ]);

                $account->credit($amountPaid);
            }

            SalePayment::create([
                'sale_id' => $sale->id,
                'account_id' => $account?->id,
                'payment_method' => $payload['payment_method'] ?? null,
                'payment_type' => $payload['sale_type'],
                'amount' => $amountPaid,
                'paid_at' => $amountPaid > 0 ? now() : null,
                'reference' => $sale->sale_number,
                'status' => $amountPaid > 0 ? 'completed' : 'pending',
                'created_by' => $userId,
                'meta' => [
                    'sale_number' => $sale->sale_number,
                ],
            ]);

            ActivityLogger::sale('created', $sale, [
                'name' => $sale->sale_number,
                'amount' => $sale->total,
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'sale_type' => $sale->sale_type,
            ]);

            return $sale->fresh(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);
        });
    }
}
