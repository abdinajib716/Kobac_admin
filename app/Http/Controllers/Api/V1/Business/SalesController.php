<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\IncomeTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockItem;
use App\Services\ActivityLogger;
use App\Services\Sales\CheckoutSaleService;
use App\Services\Sales\GenerateSaleReceiptPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SalesController extends BaseController
{
    public function __construct(
        private readonly CheckoutSaleService $checkoutSaleService,
        private readonly GenerateSaleReceiptPdfService $receiptPdfService,
    ) {
    }

    /**
     * Sales dashboard summary.
     * GET /api/v1/business/sales
     */
    public function index(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $branchId = $this->branchId();
        $from = $request->get('from')
            ? Carbon::parse($request->get('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))->endOfDay()
            : now()->endOfMonth();

        $salesQuery = Sale::forBusiness($business->id, $branchId)
            ->completed()
            ->whereBetween('sold_at', [$from, $to]);

        $totalSales = (float) (clone $salesQuery)->sum('total');
        $totalProfit = (float) (clone $salesQuery)->sum('profit_total');
        $customerOwes = (float) Customer::forBusiness($business->id, $branchId)
            ->where('balance', '>', 0)
            ->sum('balance');
        $totalProducts = StockItem::forBusiness($business->id, $branchId)
            ->active()
            ->count();

        $trend = Sale::query()
            ->selectRaw('DATE(sold_at) as sale_date, SUM(total) as total_sales, SUM(profit_total) as total_profit')
            ->forBusiness($business->id, $branchId)
            ->completed()
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('sale_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->sale_date,
                'total_sales' => round((float) $row->total_sales, 2),
                'total_profit' => round((float) $row->total_profit, 2),
            ])
            ->values();

        return $this->success([
            'summary' => [
                'total_sales' => round($totalSales, 2),
                'total_products' => $totalProducts,
                'total_profit' => round($totalProfit, 2),
                'customer_owes' => round($customerOwes, 2),
            ],
            'chart' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'points' => $trend,
            ],
        ]);
    }

    /**
     * Product list for POS.
     * GET /api/v1/business/sales/products
     */
    public function products(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 50);
        $query = StockItem::forBusiness($business->id, $this->branchId())
            ->active()
            ->when($request->boolean('in_stock_only'), fn ($q) => $q->where('quantity', '>', 0))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });

        if ($request->filled('category_id') && Schema::hasColumn('stock_items', 'category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('category') && Schema::hasColumn('stock_items', 'category')) {
            $query->where('category', $request->get('category'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        $data = $paginator->getCollection()->map(fn (StockItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'quantity' => (float) $item->quantity,
            'unit' => $item->unit,
            'price' => (float) ($item->selling_price ?? 0),
            'cost' => (float) ($item->cost_price ?? 0),
            'is_low_stock' => $item->is_low_stock,
            'image' => $item->image ? asset('storage/' . $item->image) : null,
            'category_id' => Schema::hasColumn('stock_items', 'category_id') ? $item->category_id : null,
            'category' => Schema::hasColumn('stock_items', 'category') ? $item->category : null,
        ]);

        return $this->paginated($paginator, $data);
    }

    /**
     * Customer lookup for credit sales.
     * GET /api/v1/business/sales/customers
     */
    public function customers(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 50);
        $query = Customer::forBusiness($business->id, $this->branchId())
            ->active()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $paginator = $query->orderBy('name')->paginate($perPage);
        $data = $paginator->getCollection()->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'balance' => (float) $customer->balance,
            'status' => $customer->status,
            'branch_id' => $customer->branch_id,
        ]);

        return $this->paginated($paginator, $data);
    }

    /**
     * Sales history list.
     * GET /api/v1/business/sales/history
     */
    public function history(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $perPage = min((int) $request->get('per_page', 20), 50);
        $query = Sale::query()
            ->forBusiness($business->id, $this->branchId())
            ->with(['customer:id,name', 'branch:id,name', 'createdBy:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('sale_type'), fn ($q) => $q->where('sale_type', $request->get('sale_type')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->get('payment_status')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->get('customer_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->get('search');
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('sale_number', 'like', "%{$search}%")
                        ->orWhere('receipt_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->get('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->get('to')))
            ->latest('sold_at');

        $paginator = $query->paginate($perPage);
        $data = $paginator->getCollection()->map(fn (Sale $sale) => $this->formatSaleSummary($sale));

        return $this->paginated($paginator, $data);
    }

    /**
     * Complete a sale.
     * POST /api/v1/business/sales/checkout
     */
    public function checkout(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|integer|exists:customers,id|required_if:sale_type,credit',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'sale_type' => 'required|string|in:cash,credit',
            'payment_method' => 'nullable|string|max:50',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'sold_at' => 'nullable|date',
            'idempotency_key' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.stock_item_id' => 'required|integer|exists:stock_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_discount' => 'nullable|numeric|min:0',
            'items.*.line_tax' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        try {
            $sale = $this->checkoutSaleService->execute(
                payload: $validator->validated(),
                businessId: $business->id,
                branchId: $this->branchId(),
                userId: $this->user()->id,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $exception->errors(),
            ]);
        }

        $receipt = $this->receiptPdfService->generate($sale);
        $sale = $sale->fresh(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);

        return $this->success([
            'sale' => $this->formatSaleDetail($sale),
            'receipt' => $receipt,
        ], 'Sale completed successfully', 201);
    }

    /**
     * Show sale details.
     * GET /api/v1/business/sales/{sale}
     */
    public function show(Sale $sale): JsonResponse
    {
        $this->authorizeSale($sale);
        $sale->load(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);

        return $this->success([
            'sale' => $this->formatSaleDetail($sale),
        ]);
    }

    /**
     * Generate or fetch sale receipt PDF.
     * GET /api/v1/business/sales/{sale}/receipt-pdf
     */
    public function receiptPdf(Sale $sale): JsonResponse
    {
        $this->authorizeSale($sale);
        $receipt = $this->receiptPdfService->generate($sale);
        $sale->load(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);

        return $this->success([
            'sale' => $this->formatSaleSummary($sale),
            'receipt' => $receipt,
            'generated_at' => now()->toIso8601String(),
        ], 'Sale receipt generated successfully');
    }

    /**
     * Printer-ready payload for mobile integrations.
     * GET /api/v1/business/sales/{sale}/print-payload
     */
    public function printPayload(Sale $sale): JsonResponse
    {
        $this->authorizeSale($sale);
        $sale->load(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);

        return $this->success([
            'receipt_number' => $sale->receipt_number,
            'sale_number' => $sale->sale_number,
            'business_name' => $sale->business?->name,
            'branch_name' => $sale->branch?->name,
            'sale_date' => $sale->sold_at?->toIso8601String(),
            'payment_type' => $sale->sale_type,
            'payment_status' => $sale->payment_status,
            'customer_name' => $sale->customer?->name,
            'cashier_name' => $sale->createdBy?->name,
            'currency' => $sale->business?->currency ?? 'USD',
            'lines' => $sale->items->map(fn (SaleItem $item) => [
                'name' => $item->product_name_snapshot,
                'qty' => (float) $item->quantity,
                'price' => (float) $item->unit_price,
                'total' => (float) $item->line_total,
            ])->values(),
            'totals' => [
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount_total,
                'tax' => (float) $sale->tax_total,
                'total' => (float) $sale->total,
                'paid' => (float) $sale->amount_paid,
                'due' => (float) $sale->amount_due,
            ],
        ]);
    }

    /**
     * Void a completed sale and reverse stock/customer/account effects.
     * POST /api/v1/business/sales/{sale}/void
     */
    public function void(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeSale($sale);

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return $this->error('Only completed sales can be voided', 'INVALID_SALE_STATUS', 422);
        }

        DB::transaction(function () use ($sale, $validator) {
            $sale->load(['items', 'payments', 'customer']);

            foreach ($sale->items as $item) {
                if (!$item->stock_item_id) {
                    continue;
                }

                $stock = StockItem::query()
                    ->where('business_id', $sale->business_id)
                    ->where('id', $item->stock_item_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    continue;
                }

                $quantityBefore = (float) $stock->quantity;
                $stock->increment('quantity', (float) $item->quantity);
                $stock->refresh();

                $stock->movements()->create([
                    'branch_id' => $stock->branch_id,
                    'type' => 'increase',
                    'quantity' => (float) $item->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => (float) $stock->quantity,
                    'reason' => 'Sale void',
                    'reference' => $sale->sale_number,
                    'source_type' => 'sale_void',
                    'source_id' => $sale->id,
                    'created_by' => $this->user()->id,
                    'meta' => [
                        'sale_number' => $sale->sale_number,
                    ],
                ]);
            }

            if ($sale->sale_type === 'credit' && $sale->customer) {
                $customer = Customer::query()->where('id', $sale->customer_id)->lockForUpdate()->first();

                if ($customer) {
                    $customer->decrement('balance', (float) $sale->total);
                    $customer->refresh();

                    CustomerTransaction::create([
                        'customer_id' => $customer->id,
                        'branch_id' => $customer->branch_id,
                        'type' => 'credit',
                        'amount' => (float) $sale->total,
                        'description' => 'Void sale ' . $sale->sale_number,
                        'reference' => $sale->sale_number,
                        'source_type' => 'sale_void',
                        'source_id' => $sale->id,
                        'balance_after' => (float) $customer->balance,
                        'transaction_date' => now()->toDateString(),
                        'created_by' => $this->user()->id,
                        'meta' => [
                            'sale_number' => $sale->sale_number,
                        ],
                    ]);
                }
            }

            $incomeTransactions = IncomeTransaction::query()
                ->where('business_id', $sale->business_id)
                ->where('source_type', 'sale')
                ->where('source_id', $sale->id)
                ->get();

            foreach ($incomeTransactions as $incomeTransaction) {
                if ($incomeTransaction->account_id) {
                    $account = Account::query()->where('id', $incomeTransaction->account_id)->lockForUpdate()->first();
                    if ($account) {
                        $account->debit((float) $incomeTransaction->amount);
                    }
                }

                $incomeTransaction->delete();
            }

            $sale->payments()->update([
                'status' => 'cancelled',
            ]);

            $sale->update([
                'status' => Sale::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $this->user()->id,
                'void_reason' => $validator->validated()['reason'] ?? 'Voided from sales module',
            ]);

            ActivityLogger::sale('voided', $sale, [
                'name' => $sale->sale_number,
                'amount' => $sale->total,
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
            ]);
        });

        $sale->refresh()->load(['items', 'payments', 'customer', 'branch', 'business', 'createdBy']);

        return $this->success([
            'sale' => $this->formatSaleDetail($sale),
        ], 'Sale voided successfully');
    }

    private function authorizeSale(Sale $sale): void
    {
        if ($sale->business_id !== $this->business()?->id) {
            abort(403, 'Unauthorized access');
        }
    }

    private function formatSaleSummary(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'receipt_number' => $sale->receipt_number,
            'status' => $sale->status,
            'sale_type' => $sale->sale_type,
            'payment_status' => $sale->payment_status,
            'total' => (float) $sale->total,
            'amount_paid' => (float) $sale->amount_paid,
            'amount_due' => (float) $sale->amount_due,
            'profit_total' => (float) $sale->profit_total,
            'customer' => $sale->customer ? [
                'id' => $sale->customer->id,
                'name' => $sale->customer->name,
            ] : null,
            'branch' => $sale->branch ? [
                'id' => $sale->branch->id,
                'name' => $sale->branch->name,
            ] : null,
            'cashier_name' => $sale->createdBy?->name,
            'sold_at' => $sale->sold_at?->toIso8601String(),
            'receipt_pdf_url' => $sale->receipt_pdf_path ? asset('storage/' . $sale->receipt_pdf_path) : null,
        ];
    }

    private function formatSaleDetail(Sale $sale): array
    {
        return array_merge($this->formatSaleSummary($sale), [
            'subtotal' => (float) $sale->subtotal,
            'discount_total' => (float) $sale->discount_total,
            'tax_total' => (float) $sale->tax_total,
            'cost_total' => (float) $sale->cost_total,
            'notes' => $sale->notes,
            'items' => $sale->items->map(fn (SaleItem $item) => [
                'id' => $item->id,
                'stock_item_id' => $item->stock_item_id,
                'product' => $item->product_name_snapshot,
                'sku' => $item->sku_snapshot,
                'unit' => $item->unit_snapshot,
                'qty' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'cost_price' => (float) $item->cost_price_snapshot,
                'line_discount' => (float) $item->line_discount,
                'line_tax' => (float) $item->line_tax,
                'line_total' => (float) $item->line_total,
                'profit' => round((float) $item->line_total - ((float) $item->quantity * (float) $item->cost_price_snapshot), 2),
            ])->values(),
            'payments' => $sale->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_method' => $payment->payment_method,
                'payment_type' => $payment->payment_type,
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
