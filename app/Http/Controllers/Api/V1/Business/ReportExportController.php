<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Exports\ArrayReportExport;
use App\Http\Controllers\Api\V1\BaseController;
use App\Models\ExpenseTransaction;
use App\Models\IncomeTransaction;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\VendorTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends BaseController
{
    /**
     * Export business reports in PDF / Excel.
     * GET /api/v1/business/reports/export?report=stock&format=xlsx
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report' => 'required|string|in:stock,profit_loss,activity',
            'format' => 'required|string|in:pdf,xlsx',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->error(__('mobile.errors.validation_failed'), 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $business = $this->business();
        if (!$business) {
            return $this->error('Business not set up', 'NOT_SETUP', 404);
        }

        $data = $validator->validated();
        $report = $data['report'];
        $format = $data['format'];
        $branchId = $data['branch_id'] ?? $this->branchId();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null;

        $dataset = match ($report) {
            'stock' => $this->buildStockDataset($business->id, $branchId),
            'profit_loss' => $this->buildProfitLossDataset($business->id, $branchId, $from, $to),
            'activity' => $this->buildActivityDataset($business->id, $branchId, $from, $to),
            default => null,
        };

        if (!$dataset) {
            return $this->error('Unsupported report type', 'UNSUPPORTED_REPORT', 422);
        }

        $timestamp = now()->format('Ymd_His');
        $extension = $format === 'xlsx' ? 'xlsx' : 'pdf';
        $filename = "{$report}_{$timestamp}.{$extension}";
        $path = "exports/{$business->id}/{$filename}";

        if ($format === 'xlsx') {
            Excel::store(
                new ArrayReportExport($dataset['columns'], $dataset['rows']),
                $path,
                'public'
            );
        } else {
            $pdf = Pdf::loadView('exports.report', [
                'title' => $dataset['title'],
                'columns' => $dataset['columns'],
                'rows' => $dataset['rows'],
                'summary' => $dataset['summary'],
                'meta' => $dataset['meta'],
            ])->setPaper('a4', 'portrait');

            Storage::disk('public')->put($path, $pdf->output());
        }

        return $this->success([
            'report' => $report,
            'format' => $format,
            'file_name' => $filename,
            'file_path' => $path,
            'download_url' => asset('storage/' . $path),
            'summary' => $dataset['summary'],
            'generated_at' => now()->toIso8601String(),
        ], __('mobile.messages.export_ready'));
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildStockDataset(int $businessId, ?int $branchId): array
    {
        $items = StockItem::forBusiness($businessId, $branchId)
            ->orderBy('name')
            ->get();

        $rows = $items->map(function (StockItem $item) {
            $costValue = $item->quantity * ($item->cost_price ?? 0);
            $sellingValue = $item->quantity * ($item->selling_price ?? 0);

            return [
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'cost_price' => (float) ($item->cost_price ?? 0),
                'selling_price' => (float) ($item->selling_price ?? 0),
                'cost_value' => (float) $costValue,
                'selling_value' => (float) $sellingValue,
                'status' => $item->is_active ? 'active' : 'inactive',
            ];
        })->values()->all();

        $totalCostValue = $items->sum(fn (StockItem $item) => $item->quantity * ($item->cost_price ?? 0));
        $totalSellingValue = $items->sum(fn (StockItem $item) => $item->quantity * ($item->selling_price ?? 0));

        return [
            'title' => 'Stock Report',
            'columns' => [
                'name' => 'Name',
                'sku' => 'SKU',
                'quantity' => 'Quantity',
                'unit' => 'Unit',
                'cost_price' => 'Cost Price',
                'selling_price' => 'Selling Price',
                'cost_value' => 'Cost Value',
                'selling_value' => 'Selling Value',
                'status' => 'Status',
            ],
            'rows' => $rows,
            'summary' => [
                'total_items' => $items->count(),
                'total_quantity' => (float) $items->sum('quantity'),
                'total_cost_value' => (float) $totalCostValue,
                'total_selling_value' => (float) $totalSellingValue,
                'potential_profit' => (float) ($totalSellingValue - $totalCostValue),
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildProfitLossDataset(int $businessId, ?int $branchId, ?Carbon $from, ?Carbon $to): array
    {
        $periodFrom = $from ?? Carbon::now()->startOfMonth();
        $periodTo = $to ?? Carbon::now()->endOfMonth();

        $incomeByCategory = IncomeTransaction::forBusiness($businessId, $branchId)
            ->dateRange($periodFrom, $periodTo)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $expenseByCategory = ExpenseTransaction::forBusiness($businessId, $branchId)
            ->dateRange($periodFrom, $periodTo)
            ->selectRaw('COALESCE(category, "other") as category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        $rows = [];

        foreach ($incomeByCategory as $income) {
            $rows[] = [
                'entry_type' => 'income',
                'category' => (string) $income->category,
                'amount' => (float) $income->total,
            ];
        }

        foreach ($expenseByCategory as $expense) {
            $rows[] = [
                'entry_type' => 'expense',
                'category' => (string) $expense->category,
                'amount' => (float) $expense->total,
            ];
        }

        $totalIncome = (float) $incomeByCategory->sum('total');
        $totalExpense = (float) $expenseByCategory->sum('total');
        $profitLoss = $totalIncome - $totalExpense;
        $profitMarginPercent = $totalIncome > 0
            ? round(($profitLoss / $totalIncome) * 100, 2)
            : 0.0;

        return [
            'title' => 'Profit & Loss Report',
            'columns' => [
                'entry_type' => 'Type',
                'category' => 'Category',
                'amount' => 'Amount',
            ],
            'rows' => $rows,
            'summary' => [
                'period_from' => $periodFrom->toDateString(),
                'period_to' => $periodTo->toDateString(),
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'profit_loss' => $profitLoss,
                'profit_margin_percent' => (float) $profitMarginPercent,
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ];
    }

    /**
     * @return array{
     *  title: string,
     *  columns: array<string,string>,
     *  rows: array<int,array<string,mixed>>,
     *  summary: array<string,mixed>,
     *  meta: array<string,mixed>
     * }
     */
    private function buildActivityDataset(int $businessId, ?int $branchId, ?Carbon $from, ?Carbon $to): array
    {
        $rows = collect();

        $incomeQuery = IncomeTransaction::forBusiness($businessId, $branchId)->with('createdBy:id,name');
        if ($from) {
            $incomeQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $incomeQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($incomeQuery->latest('transaction_date')->take(500)->get() as $income) {
            $rows->push([
                'type' => 'income',
                'date' => $income->transaction_date?->toDateString(),
                'description' => $income->description ?? 'Income recorded',
                'amount' => (float) $income->amount,
                'quantity' => null,
                'reference' => $income->reference,
                'created_by' => $income->createdBy?->name,
                'timestamp' => optional($income->created_at)->toIso8601String(),
            ]);
        }

        $expenseQuery = ExpenseTransaction::forBusiness($businessId, $branchId)->with('createdBy:id,name');
        if ($from) {
            $expenseQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $expenseQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($expenseQuery->latest('transaction_date')->take(500)->get() as $expense) {
            $rows->push([
                'type' => 'expense',
                'date' => $expense->transaction_date?->toDateString(),
                'description' => $expense->description ?? 'Expense recorded',
                'amount' => (float) $expense->amount,
                'quantity' => null,
                'reference' => $expense->reference,
                'created_by' => $expense->createdBy?->name,
                'timestamp' => optional($expense->created_at)->toIso8601String(),
            ]);
        }

        $vendorQuery = VendorTransaction::query()
            ->whereHas('vendor', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['vendor:id,name', 'createdBy:id,name']);

        if ($from) {
            $vendorQuery->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $vendorQuery->whereDate('transaction_date', '<=', $to);
        }

        foreach ($vendorQuery->latest('transaction_date')->take(500)->get() as $vendorTransaction) {
            $isCredit = $vendorTransaction->type === 'credit';
            $rows->push([
                'type' => $isCredit ? 'vendor_credit' : 'vendor_debit',
                'date' => $vendorTransaction->transaction_date?->toDateString(),
                'description' => $vendorTransaction->description ?: ($isCredit ? 'Vendor credited' : 'Vendor debited'),
                'amount' => (float) $vendorTransaction->amount,
                'quantity' => null,
                'reference' => $vendorTransaction->vendor?->name,
                'created_by' => $vendorTransaction->createdBy?->name,
                'timestamp' => optional($vendorTransaction->created_at)->toIso8601String(),
            ]);
        }

        $stockQuery = StockMovement::query()
            ->whereHas('stockItem', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->with(['stockItem:id,name,sku', 'createdBy:id,name']);

        if ($from) {
            $stockQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $stockQuery->whereDate('created_at', '<=', $to);
        }

        foreach ($stockQuery->latest()->take(500)->get() as $movement) {
            $isIncrease = $movement->type === 'increase';
            $rows->push([
                'type' => $isIncrease ? 'stock_increase' : 'stock_decrease',
                'date' => optional($movement->created_at)->toDateString(),
                'description' => $movement->reason ?: ($isIncrease ? 'Stock increased' : 'Stock decreased'),
                'amount' => null,
                'quantity' => (float) $movement->quantity,
                'reference' => $movement->stockItem?->name,
                'created_by' => $movement->createdBy?->name,
                'timestamp' => optional($movement->created_at)->toIso8601String(),
            ]);
        }

        $sorted = $rows->sortByDesc('timestamp')->values();

        return [
            'title' => 'Activity Report',
            'columns' => [
                'type' => 'Type',
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
                'quantity' => 'Quantity',
                'reference' => 'Reference',
                'created_by' => 'Created By',
            ],
            'rows' => $sorted->map(function (array $row) {
                unset($row['timestamp']);
                return $row;
            })->all(),
            'summary' => [
                'total_records' => $sorted->count(),
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ];
    }
}

