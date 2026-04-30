<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends BaseController
{
    /**
     * GET /api/v1/business/sales/reports/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validated = $this->validateReportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $salesQuery = Sale::query()
            ->forBusiness($business->id, $validated['branch_id'] ?? $this->branchId())
            ->completed()
            ->when($validated['customer_id'] ?? null, fn ($q, $customerId) => $q->where('customer_id', $customerId))
            ->whereDate('sold_at', '>=', $validated['from'])
            ->whereDate('sold_at', '<=', $validated['to']);

        $reportRows = collect($this->buildItemsReport(
            businessId: $business->id,
            branchId: $validated['branch_id'] ?? $this->branchId(),
            businessName: $business->name,
            currency: $business->currency ?? 'USD',
            from: $validated['from'],
            to: $validated['to'],
            customerId: $validated['customer_id'] ?? null,
            stockItemId: $validated['stock_item_id'] ?? null,
        )['rows']);

        return $this->success([
            'filters' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
            ],
            'summary' => [
                'total_sales' => round((float) (clone $salesQuery)->sum('total'), 2),
                'total_profit' => round((float) (clone $salesQuery)->sum('profit_total'), 2),
                'cash_sales' => round((float) (clone $salesQuery)->where('sale_type', 'cash')->sum('total'), 2),
                'credit_sales' => round((float) (clone $salesQuery)->where('sale_type', 'credit')->sum('total'), 2),
                'total_transactions' => (clone $salesQuery)->count(),
                'top_product' => $reportRows->sortByDesc('price')->first()['product'] ?? null,
            ],
        ]);
    }

    /**
     * GET /api/v1/business/sales/reports/trends
     */
    public function trends(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validated = $this->validateReportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $groupBy = $request->get('group_by', 'day');
        $dateSelect = $groupBy === 'month'
            ? "DATE_FORMAT(sold_at, '%Y-%m-01')"
            : 'DATE(sold_at)';

        $rows = Sale::query()
            ->forBusiness($business->id, $validated['branch_id'] ?? $this->branchId())
            ->completed()
            ->when($validated['customer_id'] ?? null, fn ($q, $customerId) => $q->where('customer_id', $customerId))
            ->whereDate('sold_at', '>=', $validated['from'])
            ->whereDate('sold_at', '<=', $validated['to'])
            ->selectRaw("{$dateSelect} as period, SUM(total) as total_sales, SUM(profit_total) as total_profit, COUNT(*) as total_transactions")
            ->groupBy(DB::raw($dateSelect))
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'total_sales' => round((float) $row->total_sales, 2),
                'total_profit' => round((float) $row->total_profit, 2),
                'total_transactions' => (int) $row->total_transactions,
            ])
            ->values();

        return $this->success([
            'filters' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
                'group_by' => $groupBy,
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * GET /api/v1/business/sales/reports/top-products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validated = $this->validateReportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $rows = collect($this->buildItemsReport(
            businessId: $business->id,
            branchId: $validated['branch_id'] ?? $this->branchId(),
            businessName: $business->name,
            currency: $business->currency ?? 'USD',
            from: $validated['from'],
            to: $validated['to'],
            customerId: $validated['customer_id'] ?? null,
            stockItemId: $validated['stock_item_id'] ?? null,
        )['rows'])
            ->sortByDesc('price')
            ->take(min((int) $request->get('limit', 10), 50))
            ->values();

        return $this->success([
            'filters' => [
                'from' => $validated['from'],
                'to' => $validated['to'],
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * Grouped sales report by product.
     * GET /api/v1/business/sales/reports/items
     */
    public function items(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validated = $this->validateReportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $report = $this->buildItemsReport(
            businessId: $business->id,
            branchId: $validated['branch_id'] ?? $this->branchId(),
            businessName: $business->name,
            currency: $business->currency ?? 'USD',
            from: $validated['from'],
            to: $validated['to'],
            customerId: $validated['customer_id'] ?? null,
            stockItemId: $validated['stock_item_id'] ?? null,
        );

        return $this->success([
            'filters' => $report['filters'],
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'print' => [
                'supported_formats' => ['pdf', 'xlsx'],
            ],
        ]);
    }

    /**
     * Export grouped sales report by product.
     * GET /api/v1/business/sales/reports/items/export?format=pdf
     */
    public function export(Request $request): JsonResponse
    {
        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $validated = $this->validateReportRequest($request, true);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $report = $this->buildItemsReport(
            businessId: $business->id,
            branchId: $validated['branch_id'] ?? $this->branchId(),
            businessName: $business->name,
            currency: $business->currency ?? 'USD',
            from: $validated['from'],
            to: $validated['to'],
            customerId: $validated['customer_id'] ?? null,
            stockItemId: $validated['stock_item_id'] ?? null,
        );

        $format = $validated['format'];
        $timestamp = now()->format('Ymd_His');
        $extension = $format === 'xlsx' ? 'xlsx' : 'pdf';
        $filename = "sales_items_report_{$timestamp}.{$extension}";
        $path = "exports/{$business->id}/sales/{$filename}";

        if ($format === 'xlsx') {
            Excel::store(
                new ArrayReportExport($report['columns'], $report['rows']),
                $path,
                'public'
            );
        } else {
            $pdf = Pdf::loadView('exports.report', [
                'title' => $report['title'],
                'columns' => $report['columns'],
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'meta' => $report['meta'],
            ])->setPaper('a4', 'portrait');

            Storage::disk('public')->put($path, $pdf->output());
        }

        $downloadUrl = asset('storage/' . $path);

        return $this->success([
            'report' => 'sales_items',
            'format' => $format,
            'file_name' => $filename,
            'file_path' => $path,
            'download_url' => $downloadUrl,
            'share_url' => $downloadUrl,
            'print_url' => $format === 'pdf' ? $downloadUrl : null,
            'mime_type' => $format === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'filters' => $report['filters'],
            'summary' => $report['summary'],
            'generated_at' => now()->toIso8601String(),
        ], 'Sales report export is ready');
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function validateReportRequest(Request $request, bool $withFormat = false): array|JsonResponse
    {
        $rules = [
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'stock_item_id' => 'nullable|integer|exists:stock_items,id',
        ];

        if ($withFormat) {
            $rules['format'] = 'required|string|in:pdf,xlsx';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error('Validation failed', 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();
        $data['from'] = isset($data['from'])
            ? Carbon::parse($data['from'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $data['to'] = isset($data['to'])
            ? Carbon::parse($data['to'])->toDateString()
            : now()->endOfMonth()->toDateString();

        return $data;
    }

    /**
     * @return array{
     *   title: string,
     *   columns: array<string,string>,
     *   rows: array<int,array<string,mixed>>,
     *   summary: array<string,mixed>,
     *   filters: array<string,mixed>,
     *   meta: array<string,mixed>
     * }
     */
    private function buildItemsReport(
        int $businessId,
        ?int $branchId,
        string $businessName,
        string $currency,
        string $from,
        string $to,
        ?int $customerId = null,
        ?int $stockItemId = null,
    ): array {
        $rows = $this->itemsQuery($businessId, $branchId, $from, $to, $customerId, $stockItemId)
            ->get()
            ->map(function ($row) {
                $price = round((float) $row->price, 2);
                $cost = round((float) $row->cost, 2);

                return [
                    'stock_item_id' => $row->stock_item_id,
                    'product' => $row->product,
                    'qty' => round((float) $row->qty, 2),
                    'price' => $price,
                    'cost' => $cost,
                    'profit' => round($price - $cost, 2),
                ];
            })
            ->values();

        $summary = [
            'total_products' => $rows->count(),
            'total_qty' => round((float) $rows->sum('qty'), 2),
            'total_price' => round((float) $rows->sum('price'), 2),
            'total_cost' => round((float) $rows->sum('cost'), 2),
            'total_profit' => round((float) $rows->sum('profit'), 2),
            'period_from' => $from,
            'period_to' => $to,
        ];

        return [
            'title' => 'Sales Items Report',
            'columns' => [
                'product' => 'Product',
                'qty' => 'Qty',
                'price' => "Price ({$currency})",
                'cost' => "Cost ({$currency})",
                'profit' => "Profit ({$currency})",
            ],
            'rows' => $rows->all(),
            'summary' => $summary,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'stock_item_id' => $stockItemId,
            ],
            'meta' => [
                'report_key' => 'sales_items',
                'business_name' => $businessName,
                'currency' => $currency,
                'generated_label' => 'Generated at',
                'generated_at_display' => now()->format('d/m/Y, H:i:s'),
                'app_name' => 'Kobac',
                'summary_labels' => [
                    'total_products' => 'Total Products',
                    'total_qty' => 'Total Qty',
                    'total_price' => "Total Price ({$currency})",
                    'total_cost' => "Total Cost ({$currency})",
                    'total_profit' => "Total Profit ({$currency})",
                    'period_from' => 'From Date',
                    'period_to' => 'To Date',
                ],
            ],
        ];
    }

    private function itemsQuery(
        int $businessId,
        ?int $branchId,
        string $from,
        string $to,
        ?int $customerId = null,
        ?int $stockItemId = null,
    ) {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->when($branchId, fn ($query) => $query->where('sales.branch_id', $branchId))
            ->when($customerId, fn ($query) => $query->where('sales.customer_id', $customerId))
            ->when($stockItemId, fn ($query) => $query->where('sale_items.stock_item_id', $stockItemId))
            ->whereDate('sales.sold_at', '>=', $from)
            ->whereDate('sales.sold_at', '<=', $to)
            ->groupBy('sale_items.stock_item_id', 'sale_items.product_name_snapshot')
            ->orderBy('sale_items.product_name_snapshot')
            ->selectRaw('
                sale_items.stock_item_id as stock_item_id,
                sale_items.product_name_snapshot as product,
                SUM(sale_items.quantity) as qty,
                SUM(sale_items.line_total) as price,
                SUM(sale_items.quantity * sale_items.cost_price_snapshot) as cost
            ');
    }
}
